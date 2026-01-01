import { defineStore } from 'pinia';
import api, { prefetch } from '@/utils/api';

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    items: [],
    unreadCount: 0,
    loading: false,
    lastId: 0,
    pollingId: null,
    prefetched: false,
  }),

  getters: {
    unreadItems(state) {
      return state.items.filter((n) => !n.read_at);
    },
  },

  actions: {
    async fetch({ unreadOnly = false, perPage = 10, noCache = true } = {}) {
      this.loading = true;
      try {
        const res = await api.get('/notifications', {
          params: {
            per_page: perPage,
            unread_only: unreadOnly ? 1 : 0,
          },
          noCache,
        });
        const data = res.data?.data || {};
        this.items = Array.isArray(data.items) ? data.items : [];
        this.unreadCount = Number(data.unread_count || 0);
        this.lastId = this.items.length ? Number(this.items[0]?.id || 0) : this.lastId;
      } finally {
        this.loading = false;
      }
    },

    async fetchNew({ noCache = true } = {}) {
      const sinceId = Number(this.lastId || 0);
      if (!sinceId) {
        return this.fetch({ noCache });
      }

      const res = await api.get('/notifications', {
        params: { per_page: 10, page: 1, since_id: sinceId },
        noCache,
      });
      const data = res.data?.data || {};
      const newItems = Array.isArray(data.items) ? data.items : [];
      if (newItems.length) {
        // API returns newest first
        this.items = [...newItems, ...this.items].slice(0, 50);
        this.lastId = Number(newItems[0]?.id || this.lastId);
      }
      this.unreadCount = Number(data.unread_count || this.unreadCount);
    },

    async markRead(id) {
      const notifId = Number(id);
      if (!notifId) return;

      await api.post(`/notifications/${notifId}/read`, {});
      this.items = this.items.map((n) =>
        Number(n.id) === notifId ? { ...n, read_at: n.read_at || new Date().toISOString() } : n
      );
      // Refresh unread count cheaply
      await this.fetch({ unreadOnly: false, perPage: 10, noCache: true });
    },

    async markAllRead() {
      await api.post('/notifications/read-all', {});
      this.items = this.items.map((n) => ({ ...n, read_at: n.read_at || new Date().toISOString() }));
      this.unreadCount = 0;
    },

    startPolling(intervalMs = 20000) {
      this.stopPolling();
      this.pollingId = window.setInterval(() => {
        this.fetchNew({ noCache: true }).catch(() => {});
      }, intervalMs);
    },

    stopPolling() {
      if (this.pollingId) {
        window.clearInterval(this.pollingId);
        this.pollingId = null;
      }
    },

    async prefetchNotifications(params = {}) {
      try {
        const response = await prefetch('notifications', {
          ...params,
          per_page: 50,
        });
        
        if (response.data && response.data.data) {
          const data = response.data.data;
          this.items = Array.isArray(data.items) ? data.items : [];
          this.unreadCount = Number(data.unread_count || 0);
          this.lastId = this.items.length ? Number(this.items[0]?.id || 0) : this.lastId;
          this.prefetched = true;
        }
        
        return response.data;
      } catch (error) {
        console.error('Error prefetching notifications:', error);
      }
    },
  },
});

