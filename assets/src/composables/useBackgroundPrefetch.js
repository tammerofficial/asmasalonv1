import { useBookingsStore } from '@/stores/bookings';
import { useNotificationsStore } from '@/stores/notifications';

/**
 * Composable for background prefetching of data
 * Implements Rule #5: Background Prefetch for Dashboard Performance
 */
export function useBackgroundPrefetch() {
  const bookingsStore = useBookingsStore();
  const notificationsStore = useNotificationsStore();

  /**
   * Prefetch all data with delays to avoid blocking dashboard
   */
  async function prefetchAllWithDelay() {
    try {
      // Immediate prefetch (essential data)
      await Promise.all([
        bookingsStore.prefetchServices(),
        bookingsStore.prefetchStaff(),
      ]);

      // Delay 1 second before prefetching appointments
      setTimeout(async () => {
        try {
          await bookingsStore.prefetchAppointments();
        } catch (error) {
          console.error('Error prefetching appointments:', error);
        }
      }, 1000);

      // Delay 2 seconds before prefetching notifications
      setTimeout(async () => {
        try {
          await notificationsStore.prefetchNotifications();
        } catch (error) {
          console.error('Error prefetching notifications:', error);
        }
      }, 2000);
    } catch (error) {
      console.error('Error in background prefetch:', error);
      // Silent error handling - don't throw
    }
  }

  /**
   * Prefetch specific resource
   */
  async function prefetchResource(resource, params = {}) {
    try {
      switch (resource) {
        case 'services':
          return await bookingsStore.prefetchServices(params);
        case 'staff':
          return await bookingsStore.prefetchStaff(params);
        case 'appointments':
        case 'bookings':
          return await bookingsStore.prefetchAppointments(params);
        case 'notifications':
          return await notificationsStore.prefetchNotifications(params);
        default:
          console.warn(`Unknown prefetch resource: ${resource}`);
          return null;
      }
    } catch (error) {
      console.error(`Error prefetching ${resource}:`, error);
      return null;
    }
  }

  return {
    prefetchAllWithDelay,
    prefetchResource,
  };
}

