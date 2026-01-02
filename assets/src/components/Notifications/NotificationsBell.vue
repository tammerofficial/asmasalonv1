<template>
  <div class="notif-wrapper" ref="rootRef">
    <button class="notif-btn" type="button" @click="toggle" :title="t('notifications.title')">
      <CIcon icon="cil-bell" size="lg" />
      <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
    </button>

    <div v-if="open" class="notif-dropdown">
      <div class="notif-header">
        <div class="notif-title">{{ t('notifications.title') }}</div>
        <div class="notif-actions">
          <button class="link-btn" type="button" @click="markAllRead" :disabled="unreadCount === 0">
            {{ t('notifications.markAllRead') }}
          </button>
          <button class="link-btn" type="button" @click="goToAll">
            {{ t('notifications.viewAll') }}
          </button>
        </div>
      </div>

      <div class="notif-list">
        <div v-if="loading" class="notif-empty">{{ t('common.loading') }}</div>
        <div v-else-if="items.length === 0" class="notif-empty">{{ t('notifications.empty') }}</div>

        <button
          v-else
          v-for="n in items"
          :key="n.id"
          class="notif-item"
          :class="{ unread: !n.read_at }"
          type="button"
          @click="openNotification(n)"
        >
          <div class="notif-item-title">{{ pickText(n, 'title') }}</div>
          <div class="notif-item-msg">{{ pickText(n, 'message') }}</div>
          <div class="notif-item-meta">
            <span v-if="!n.read_at" class="dot" />
            <span class="time">{{ formatTime(n.created_at) }}</span>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { CIcon } from '@coreui/icons-vue';
import { useRouter } from 'vue-router';
import { useTranslation } from '@/composables/useTranslation';
import { useNotificationsStore } from '@/stores/notifications';

const router = useRouter();
const { t, currentLocale } = useTranslation();
const notifications = useNotificationsStore();

const open = ref(false);
const rootRef = ref(null);

const items = computed(() => notifications.items.slice(0, 8));
const unreadCount = computed(() => notifications.unreadCount);
const loading = computed(() => notifications.loading);

const toggle = async () => {
  open.value = !open.value;
  if (open.value) {
    await notifications.fetch({ perPage: 10, noCache: true });
  }
};

const close = () => {
  open.value = false;
};

const onDocClick = (e) => {
  const root = rootRef.value;
  if (!root) return;
  if (!root.contains(e.target)) close();
};

const goToAll = () => {
  close();
  router.push('/notifications');
};

const markAllRead = async () => {
  await notifications.markAllRead();
};

const openNotification = async (n) => {
  if (!n.read_at) {
    await notifications.markRead(n.id);
  }

  // Optional navigation if backend provided action.route
  try {
    const data = n?.data ? JSON.parse(n.data) : {};
    const route = data?.action?.route;
    const query = data?.action?.query || null;
    if (route) {
      close();
      router.push({ path: route, query: query || undefined });
      return;
    }
  } catch {
    // ignore
  }
};

const pickText = (n, field) => {
  try {
    const data = n?.data ? JSON.parse(n.data) : {};
    const lang = currentLocale.value || 'ar';
    const v = lang === 'ar' ? data?.[`${field}_ar`] : data?.[`${field}_en`];
    return v || data?.[`${field}_ar`] || data?.[`${field}_en`] || '—';
  } catch {
    return '—';
  }
};

const formatTime = (dt) => {
  if (!dt) return '';
  try {
    return new Date(dt.replace(' ', 'T')).toLocaleString(currentLocale.value === 'ar' ? 'ar-KW' : 'en-US', {
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return String(dt);
  }
};

onMounted(async () => {
  await notifications.fetch({ perPage: 10, noCache: true });
  notifications.startPolling(20000);
  document.addEventListener('click', onDocClick);
});

onBeforeUnmount(() => {
  notifications.stopPolling();
  document.removeEventListener('click', onDocClick);
});
</script>

<style scoped>
.notif-wrapper {
  position: relative;
}

.notif-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 10px;
  border: none;
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.notif-btn:hover {
  color: var(--text-primary);
  background: var(--asmaa-primary-soft);
}

.notif-badge {
  position: absolute;
  top: 6px;
  right: 6px;
  min-width: 18px;
  height: 18px;
  padding: 0 6px;
  border-radius: 999px;
  background: var(--asmaa-danger, #ef4444);
  color: #fff;
  font-size: 11px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--bg-primary);
}

[dir="rtl"] .notif-badge {
  right: auto;
  left: 6px;
}

.notif-dropdown {
  position: absolute;
  top: 48px;
  right: 0;
  width: 380px;
  max-width: calc(100vw - 32px);
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 14px;
  box-shadow: var(--shadow-lg);
  overflow: hidden;
  z-index: 2000;
}

[dir="rtl"] .notif-dropdown {
  right: auto;
  left: 0;
}

.notif-header {
  padding: 12px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-secondary);
}

.notif-title {
  font-weight: 900;
  color: var(--text-primary);
}

.notif-actions {
  display: flex;
  gap: 10px;
}

.link-btn {
  border: none;
  background: transparent;
  color: var(--asmaa-primary);
  font-weight: 800;
  cursor: pointer;
  font-size: 12px;
  padding: 6px 8px;
  border-radius: 8px;
}

.link-btn:hover {
  background: var(--asmaa-primary-soft);
}

.link-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.notif-list {
  max-height: 420px;
  overflow: auto;
}

.notif-empty {
  padding: 18px 14px;
  color: var(--text-muted);
  font-weight: 700;
}

.notif-item {
  width: 100%;
  text-align: left;
  border: none;
  background: transparent;
  padding: 12px 14px;
  border-bottom: 1px solid var(--border-color);
  cursor: pointer;
  transition: background 0.15s;
}

[dir="rtl"] .notif-item {
  text-align: right;
}

.notif-item:hover {
  background: var(--bg-secondary);
}

.notif-item.unread {
  background: rgba(142, 126, 120, 0.08);
}

.notif-item-title {
  font-weight: 900;
  color: var(--text-primary);
  font-size: 13px;
  margin-bottom: 4px;
}

.notif-item-msg {
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 600;
  line-height: 1.4;
}

.notif-item-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  color: var(--text-muted);
  font-size: 11px;
  font-weight: 700;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--asmaa-primary);
  display: inline-block;
}
</style>

