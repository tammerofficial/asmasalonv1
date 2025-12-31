<template>
  <CContainer fluid class="p-0">
    <div class="asmaa-salon-layout" :class="{ 'theme-dark': isDark, 'theme-light': !isDark, 'display-mode': isDisplayMode }">
      <Sidebar v-if="!isDisplayMode" ref="sidebarRef" />
      <div class="asmaa-salon-content">
        <Topbar v-if="!isDisplayMode" @toggle-sidebar="toggleSidebar" />
        <main class="asmaa-salon-main">
          <router-view v-slot="{ Component }">
            <keep-alive>
              <component :is="Component" v-if="$route.meta.keepAlive" />
            </keep-alive>
            <component :is="Component" v-if="!$route.meta.keepAlive" />
          </router-view>
        </main>
      </div>
    </div>
  </CContainer>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { CContainer } from '@coreui/vue';
import Sidebar from './components/Layout/Sidebar.vue';
import Topbar from './components/Layout/Topbar.vue';
import { useUiStore } from './stores/ui';
import { useTranslation } from './composables/useTranslation';

const sidebarRef = ref(null);
const uiStore = useUiStore();
const { dir } = useTranslation();

const isDark = computed(() => uiStore.theme === 'dark');

// Check if we're in display mode (from data-mode attribute or route)
const isDisplayMode = computed(() => {
  const root = document.getElementById('asmaa-salon-vue-root');
  return root?.dataset.mode === 'display' || window.location.hash.includes('/display/');
});

const toggleSidebar = () => {
  if (sidebarRef.value) {
    sidebarRef.value.toggleVisibility();
  }
};

onMounted(() => {
  // Theme and locale are initialized in their respective stores/composables
});
</script>

<style scoped>
#asmaa-salon-vue-root {
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.asmaa-salon-layout {
  display: flex;
  width: 100%;
  height: 100vh;
  background: var(--bg-secondary);
  overflow: hidden;
  font-family: var(--font-family-base);
  font-size: var(--font-size-base);
  line-height: var(--line-height-normal);
  transition: background-color 0.3s ease;
}

.asmaa-salon-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: margin 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Display mode - full screen, no sidebar/topbar */
.asmaa-salon-layout.display-mode .asmaa-salon-content {
  margin-left: 0;
  margin-right: 0;
}

.asmaa-salon-layout.display-mode {
  background: #1a1a1a;
}

/* LTR Layout */
[dir="ltr"] .asmaa-salon-content {
  margin-left: 260px;
}

[dir="ltr"] .asmaa-salon-sidebar.hide ~ .asmaa-salon-content {
  margin-left: 0;
}

/* RTL Layout */
[dir="rtl"] .asmaa-salon-content {
  margin-right: 260px;
}

[dir="rtl"] .asmaa-salon-sidebar.hide ~ .asmaa-salon-content {
  margin-right: 0;
}

.asmaa-salon-main {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
  background: var(--bg-secondary);
  transition: background-color 0.3s ease;
}

@media (max-width: 1024px) {
  [dir="ltr"] .asmaa-salon-content,
  [dir="rtl"] .asmaa-salon-content {
    margin-left: 0;
    margin-right: 0;
  }
}
</style>
