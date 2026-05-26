import { onUnmounted } from 'vue';

export function useOrderRealtime(orderId, onUpdate) {
  let channel = null;

  const connect = () => {
    if (!window.Echo || !orderId) {
      return;
    }

    channel = window.Echo.private(`orders.${orderId}`)
      .listen('.order.status.changed', payload => onUpdate?.({ type: 'status', payload }))
      .listen('.rider.location.updated', payload => onUpdate?.({ type: 'location', payload }));
  };

  const disconnect = () => {
    if (!window.Echo || !orderId) {
      return;
    }

    window.Echo.leave(`private-orders.${orderId}`);
    channel = null;
  };

  connect();

  onUnmounted(disconnect);

  return { reconnect: connect, disconnect };
}

export function useRiderMarketplaceRealtime(onOrderAvailable) {
  let channel = null;

  const connect = () => {
    if (!window.Echo) {
      return;
    }

    channel = window.Echo.private('riders.marketplace')
      .listen('.order.available', payload => onOrderAvailable?.(payload));
  };

  const disconnect = () => {
    if (!window.Echo) {
      return;
    }

    window.Echo.leave('private-riders.marketplace');
    channel = null;
  };

  connect();
  onUnmounted(disconnect);

  return { reconnect: connect, disconnect };
}
