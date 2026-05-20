import { onBeforeUnmount, onMounted } from 'vue';

export function useOpsRealtime({ onOrderStatusChanged, onRiderStatusUpdated } = {}) {
  let ordersChannel = null;
  let ridersChannel = null;

  onMounted(() => {
    if (!window.Echo) {
      return;
    }

    ordersChannel = window.Echo.private('ops.orders').listen('.order.status.changed', payload => {
      if (typeof onOrderStatusChanged === 'function') {
        onOrderStatusChanged(payload);
      }
    });

    ridersChannel = window.Echo.private('ops.riders').listen('.rider.status.updated', payload => {
      if (typeof onRiderStatusUpdated === 'function') {
        onRiderStatusUpdated(payload);
      }
    });
  });

  onBeforeUnmount(() => {
    if (!window.Echo) {
      return;
    }

    if (ordersChannel) {
      window.Echo.leave('private-ops.orders');
    }

    if (ridersChannel) {
      window.Echo.leave('private-ops.riders');
    }
  });
}
