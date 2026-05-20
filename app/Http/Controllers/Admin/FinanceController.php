<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\RiderEarning;
use App\Models\Transaction;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Finance/Index', [
            'summary' => [
                'gross_revenue' => (float) Transaction::query()->where('status', 'success')->sum('amount'),
                'pending_payouts' => (float) Payout::query()->where('status', 'queued')->sum('amount'),
                'settled_rider_earnings' => (float) RiderEarning::query()->where('status', 'settled')->sum('net_amount'),
            ],
            'transactions' => Transaction::query()->latest()->paginate(12),
            'payouts' => Payout::query()->latest()->limit(10)->get(),
            'payment_breakdown' => Transaction::query()
                ->selectRaw('method, SUM(amount) as total')
                ->groupBy('method')
                ->get(),
        ]);
    }
}
