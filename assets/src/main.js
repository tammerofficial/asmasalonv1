import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import './style.css';

// CoreUI imports
import CoreuiVue from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import * as icons from '@coreui/icons';
import '@coreui/coreui/dist/css/coreui.min.css';
import './styles/coreui-custom.scss';

// Wait for DOM to be ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}

function initApp() {
    const appElement = document.getElementById('asmaa-salon-vue-root');
    if (!appElement) {
        console.error('Asmaa Salon: App element #asmaa-salon-vue-root not found');
        return;
    }

    try {
        const app = createApp(App);
        const pinia = createPinia();

        app.use(pinia);
        app.use(router);
        app.use(CoreuiVue);

        // Register CIcon globally
        app.component('CIcon', CIcon);

        // Provide icons globally
        app.provide('icons', icons);

        app.mount('#asmaa-salon-vue-root');
    } catch (error) {
        console.error('Asmaa Salon: Error initializing Vue app', error);
        appElement.innerHTML = '<div style="padding: 20px;"><h2>Asmaa Salon</h2><p>خطأ في تحميل التطبيق. يرجى التحقق من وحدة التحكم.</p></div>';
    }
}
