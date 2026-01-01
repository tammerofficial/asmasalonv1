import { ref } from 'vue';
import { usePOSStore } from '@/stores/posStore';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import api from '@/utils/api';

export function usePOSIntegration() {
  const posStore = usePOSStore();
  const { t } = useTranslation();
  const toast = useToast();
  const isProcessing = ref(false);

  async function selectActiveCustomer(customer) {
    posStore.selectedCustomerId = customer.id || customer.customer_id;
    posStore.clearCustomerAlerts();
    posStore.setLastVisitDetails({ date: null, services: [], total: 0, staff_name: '' });
    
    // Fetch detailed loyalty and financial data
    await Promise.all([
      posStore.fetchLoyaltyData(posStore.selectedCustomerId),
      posStore.fetchCustomerFinancials(posStore.selectedCustomerId)
    ]);

    toast.success(t('pos.customerSelected') || 'تم اختيار العميلة: ' + (customer.name || customer.customer_name));
  }

  async function callNextInQueue() {
    try {
      const response = await api.post('/queue/call-next');
      if (response.data?.data) {
        const ticket = response.data.data;
        await api.post(`/queue/${ticket.id}/call`);
        toast.success(`${t('queue.ticketCalled')}: ${ticket.ticket_number || ticket.id}`);
        await posStore.fetchAllData();
        return ticket;
      } else {
        toast.warning(t('queue.noWaitingTickets'));
      }
    } catch (error) {
      console.error('Error calling next:', error);
      toast.error(t('queue.errorCallingTicket'));
    }
    return null;
  }

  async function callSpecificTicket(ticketId) {
    try {
      await api.post(`/queue/${ticketId}/call`);
      toast.success(t('queue.ticketCalled') || 'تم استدعاء التذكرة');
      await posStore.fetchAllData();
    } catch (error) {
      console.error('Error calling ticket:', error);
      toast.error(t('queue.errorCallingTicket'));
    }
  }

  async function serveTicket(ticketId) {
    try {
      await api.post(`/queue/${ticketId}/start`);
      toast.success(t('queue.serviceStarted') || 'بدء الخدمة');
      await posStore.fetchAllData();
    } catch (error) {
      console.error('Error starting service:', error);
      toast.error(t('queue.errorStartingService'));
    }
  }

  async function redeemLoyaltyPoints(amount) {
    if (!posStore.selectedCustomerId) return false;
    try {
      const response = await api.post('/loyalty/redeem', { 
        customer_id: posStore.selectedCustomerId,
        points: amount,
        description: t('pos.redeemPointsDescription') || 'استبدال نقاط من خلال POS'
      });
      if (response.data?.success) {
        toast.success(t('loyalty.redeemed') || 'تم استبدال النقاط بنجاح');
        await posStore.fetchLoyaltyData(posStore.selectedCustomerId);
        return true;
      }
    } catch (error) {
      console.error('Error redeeming points:', error);
      toast.error(t('loyalty.errorRedeeming') || 'خطأ في استبدال النقاط');
    }
    return false;
  }

  async function processBookingArrival(booking) {
    try {
      isProcessing.value = true;
      // 1. Select the customer
      if (booking.customer_id) {
        await selectActiveCustomer({ id: booking.customer_id, name: booking.customer_name });
      }

      // 2. Add the service to the cart
      if (booking.service_id) {
        posStore.addToCart({
          service_id: booking.service_id,
          name: booking.service_name,
          unit_price: booking.price,
          quantity: 1,
          staff_id: booking.staff_id
        });
      }

      // 3. Optional: Mark as arrived in backend (if endpoint exists)
      // await api.post(`/bookings/${booking.id}/arrive`);

      toast.success(t('pos.bookingProcessed') || 'تم تحويل الحجز إلى سلة المشتريات');
      return true;
    } catch (error) {
      console.error('Error processing booking arrival:', error);
      toast.error(t('pos.errorProcessingArrival'));
      return false;
    } finally {
      isProcessing.value = false;
    }
  }

  async function processQueueTicketArrival(ticket) {
    try {
      isProcessing.value = true;
      // 1. Select the customer if known
      if (ticket.customer_id) {
        await selectActiveCustomer({ id: ticket.customer_id, name: ticket.customer_name });
      }

      // 2. Add the service to the cart
      if (ticket.service_id) {
        posStore.addToCart({
          service_id: ticket.service_id,
          name: ticket.service_name,
          unit_price: ticket.price,
          quantity: 1,
          staff_id: ticket.staff_id
        });
      }

      // 3. Start serving the ticket
      await serveTicket(ticket.id);

      toast.success(t('pos.queueProcessed') || 'تم تحويل التذكرة إلى سلة المشتريات وبدء الخدمة');
      return true;
    } catch (error) {
      console.error('Error processing queue arrival:', error);
      toast.error(t('pos.errorProcessingArrival'));
      return false;
    } finally {
      isProcessing.value = false;
    }
  }

  async function processCheckout(clientSideId) {
    if (posStore.cart.length === 0) {
      toast.error(t('pos.emptyCart'));
      return false;
    }

    isProcessing.value = true;
    try {
      const payload = {
        customer_id: posStore.selectedCustomerId ? Number(posStore.selectedCustomerId) : null,
        items: posStore.cart.map(item => ({
          service_id: item.service_id || null,
          product_id: item.product_id || null,
          staff_id: item.staff_id || null,
          quantity: item.quantity,
          unit_price: item.unit_price,
          name: item.name,
        })),
        payment_method: posStore.paymentMethod,
        discount: posStore.discount || 0,
        client_side_id: clientSideId,
        // Support for split payments
        is_split_payment: posStore.splitPayments.some(p => p.amount > 0),
        split_payments: posStore.splitPayments.filter(p => p.amount > 0)
      };

      const response = await api.post('/pos/process', payload);
      
      if (response.data?.success) {
        toast.success(t('pos.orderProcessed') || 'تمت معالجة الطلب بنجاح');
        posStore.clearCart();
        posStore.clearSplitPayments();
        await posStore.fetchAllData();
        return response.data.data;
      } else {
        toast.error(response.data?.message || t('pos.errorProcessing'));
        return false;
      }
    } catch (error) {
      console.error('Error in checkout:', error);
      toast.error(t('pos.errorProcessing') || 'خطأ في معالجة الطلب');
      return false;
    } finally {
      isProcessing.value = false;
    }
  }

  function formatCurrency(amount) {
    return new Intl.NumberFormat('en-KW', {
      style: 'currency',
      currency: 'KWD',
      minimumFractionDigits: 3,
    }).format(amount || 0);
  }

  return {
    isProcessing,
    selectActiveCustomer,
    processCheckout,
    formatCurrency,
    processBookingArrival,
    processQueueTicketArrival,
    callNextInQueue,
    callSpecificTicket,
    serveTicket,
    redeemLoyaltyPoints
  };
}

