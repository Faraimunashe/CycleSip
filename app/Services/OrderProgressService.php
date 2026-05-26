<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Payments\PaymentMethodService;

class OrderProgressService
{
    public function __construct(
        private readonly PaymentMethodService $paymentMethodService,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function stepsForOrder(Order $order): array
    {
        $order->loadMissing('timeline');
        $timelineStatuses = $order->timeline->pluck('status')->all();
        $paymentMethod = PaymentMethod::query()->where('code', $order->payment_method)->first();
        $isPrepay = $paymentMethod?->isPrepay() ?? in_array($order->payment_method, ['ecocash', 'innbucks'], true);

        $steps = [];

        if ($isPrepay) {
            $steps = array_merge($steps, $this->prepaySteps($order, $timelineStatuses));
        } else {
            $steps[] = $this->makeStep(
                key: 'payment_on_delivery',
                label: 'Pay on delivery ('.strtoupper($order->payment_method).')',
                type: 'payment',
                completed: in_array($order->payment_status, [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PENDING_COLLECTION], true),
                at: $order->placed_at?->toIso8601String(),
            );
        }

        foreach ($this->fulfillmentSteps() as $fulfillmentStep) {
            $status = $fulfillmentStep['status'];
            $completed = $this->fulfillmentStepCompleted($order, $status, $timelineStatuses);
            $steps[] = $this->makeStep(
                key: $fulfillmentStep['key'],
                label: $fulfillmentStep['label'],
                type: 'fulfillment',
                completed: $completed,
                at: $this->fulfillmentTimestamp($order, $status, $completed),
            );
        }

        return $steps;
    }

    /**
     * @param  list<string>  $timelineStatuses
     * @return list<array<string, mixed>>
     */
    private function prepaySteps(Order $order, array $timelineStatuses): array
    {
        $paid = $order->payment_status === Order::PAYMENT_STATUS_PAID
            || in_array(Order::TIMELINE_PAYMENT_PAID, $timelineStatuses, true);

        $processing = in_array($order->payment_status, [Order::PAYMENT_STATUS_PROCESSING, Order::PAYMENT_STATUS_PAID], true)
            || in_array(Order::TIMELINE_PAYMENT_PROCESSING, $timelineStatuses, true);

        $failed = $order->payment_status === Order::PAYMENT_STATUS_FAILED
            || in_array(Order::TIMELINE_PAYMENT_FAILED, $timelineStatuses, true);

        return [
            $this->makeStep(
                key: 'payment_initiated',
                label: 'Payment initiated',
                type: 'payment',
                completed: true,
                at: $order->placed_at?->toIso8601String(),
            ),
            $this->makeStep(
                key: 'payment_processing',
                label: $failed ? 'Payment failed' : 'Payment processing',
                type: 'payment',
                completed: $processing || $failed,
                at: null,
                failed: $failed,
            ),
            $this->makeStep(
                key: 'payment_paid',
                label: 'Payment confirmed',
                type: 'payment',
                completed: $paid,
                at: $order->paid_at?->toIso8601String(),
            ),
        ];
    }

    /**
     * @return list<array{key: string, label: string, status: string}>
     */
    private function fulfillmentSteps(): array
    {
        return [
            ['key' => 'order_placed', 'label' => 'Order placed', 'status' => Order::STATUS_PENDING],
            ['key' => 'broadcast_to_riders', 'label' => 'Sent to riders', 'status' => Order::STATUS_BROADCAST_TO_RIDERS],
            ['key' => 'accepted_by_rider', 'label' => 'Rider accepted', 'status' => Order::STATUS_ACCEPTED_BY_RIDER],
            ['key' => 'en_route_to_store', 'label' => 'Heading to store', 'status' => Order::STATUS_EN_ROUTE_TO_STORE],
            ['key' => 'collecting_items', 'label' => 'Collecting items', 'status' => Order::STATUS_COLLECTING_ITEMS],
            ['key' => 'en_route_to_customer', 'label' => 'Out for delivery', 'status' => Order::STATUS_EN_ROUTE_TO_CUSTOMER],
            ['key' => 'delivered', 'label' => 'Delivered', 'status' => Order::STATUS_DELIVERED],
            ['key' => 'completed', 'label' => 'Completed', 'status' => Order::STATUS_COMPLETED],
        ];
    }

    /**
     * @param  list<string>  $timelineStatuses
     */
    private function fulfillmentStepCompleted(Order $order, string $status, array $timelineStatuses): bool
    {
        if (in_array($status, $timelineStatuses, true)) {
            return true;
        }

        $orderStatus = $order->status;

        if ($orderStatus === Order::STATUS_CANCELLED) {
            return false;
        }

        $pipeline = [
            Order::STATUS_PENDING,
            Order::STATUS_BROADCAST_TO_RIDERS,
            Order::STATUS_ACCEPTED_BY_RIDER,
            Order::STATUS_EN_ROUTE_TO_STORE,
            Order::STATUS_VERIFYING_STOCK,
            Order::STATUS_COLLECTING_ITEMS,
            Order::STATUS_ADJUSTED,
            Order::STATUS_EN_ROUTE_TO_CUSTOMER,
            Order::STATUS_DELIVERED,
            Order::STATUS_COMPLETED,
        ];

        $targetIndex = array_search($status, $pipeline, true);
        $currentIndex = array_search($orderStatus, $pipeline, true);

        if ($targetIndex === false || $currentIndex === false) {
            return false;
        }

        return $currentIndex >= $targetIndex;
    }

    private function fulfillmentTimestamp(Order $order, string $status, bool $completed): ?string
    {
        if (! $completed) {
            return null;
        }

        return match ($status) {
            Order::STATUS_ACCEPTED_BY_RIDER => $order->accepted_at?->toIso8601String(),
            Order::STATUS_DELIVERED => $order->delivered_at?->toIso8601String(),
            Order::STATUS_COMPLETED => $order->completed_at?->toIso8601String(),
            default => $order->placed_at?->toIso8601String(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function makeStep(
        string $key,
        string $label,
        string $type,
        bool $completed,
        ?string $at,
        bool $failed = false,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'completed' => $completed,
            'failed' => $failed,
            'at' => $at,
        ];
    }
}
