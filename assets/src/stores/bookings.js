import { defineStore } from 'pinia';
import api from '../utils/api';

export const useBookingsStore = defineStore('bookings', {
    state: () => ({
        services: {
            items: [],
            loading: false,
            error: null,
            pagination: {
                current_page: 1,
                per_page: 20,
                total: 0,
                total_pages: 0,
            },
        },
        staff: {
            items: [],
            loading: false,
            error: null,
            pagination: {
                current_page: 1,
                per_page: 20,
                total: 0,
                total_pages: 0,
            },
        },
        appointments: {
            items: [],
            loading: false,
            error: null,
            pagination: {
                current_page: 1,
                per_page: 20,
                total: 0,
                total_pages: 0,
            },
            filters: {
                date_from: '',
                date_to: '',
                status: '',
            },
        },
        settings: {
            general: {},
            url: {},
            calendar: {},
            company: {},
            customers: {},
            appointments: {},
            payments: {},
            appearance: {},
            business_hours: [],
            holidays: [],
            loading: false,
            saving: false,
            error: null,
        },
    }),

    actions: {
        // Services
        async fetchServices(params = {}) {
            this.services.loading = true;
            this.services.error = null;

            try {
                const response = await api.get('/services', {
                    params: {
                        page: this.services.pagination.current_page,
                        per_page: this.services.pagination.per_page,
                        ...params,
                    },
                });

                const payload = response.data?.data ?? response.data ?? {};
                this.services.items = (payload.items || []).map((s) => ({
                    ...s,
                    // Normalize to Bookings UI expected fields
                    title: s.title ?? s.name_ar ?? s.name ?? '',
                    duration: Number(s.duration ?? 0),
                    price: Number(s.price ?? 0),
                }));
                this.services.pagination = payload.pagination || this.services.pagination;
            } catch (error) {
                this.services.error = error.response?.data?.message || error.message || 'Failed to fetch services';
                console.error('Error fetching services:', error);
            } finally {
                this.services.loading = false;
            }
        },

        async createService(data) {
            this.services.loading = true;
            this.services.error = null;

            try {
                const payload = { ...(data || {}) };
                // Accept Bookings UI shape: { title, duration, price }
                if (payload.title && !payload.name) payload.name = payload.title;
                if (payload.title && !payload.name_ar) payload.name_ar = payload.title;

                const response = await api.post('/services', payload);
                return response.data?.data ?? response.data;
            } catch (error) {
                this.services.error = error.response?.data?.message || error.message || 'Failed to create service';
                throw error;
            } finally {
                this.services.loading = false;
            }
        },

        async updateService(id, data) {
            this.services.loading = true;
            this.services.error = null;

            try {
                const payload = { ...(data || {}) };
                if (payload.title && !payload.name) payload.name = payload.title;
                if (payload.title && !payload.name_ar) payload.name_ar = payload.title;

                const response = await api.put(`/services/${id}`, payload);
                return response.data?.data ?? response.data;
            } catch (error) {
                this.services.error = error.response?.data?.message || error.message || 'Failed to update service';
                throw error;
            } finally {
                this.services.loading = false;
            }
        },

        async deleteService(id) {
            this.services.loading = true;
            this.services.error = null;

            try {
                await api.delete(`/services/${id}`);
                return true;
            } catch (error) {
                this.services.error = error.response?.data?.message || error.message || 'Failed to delete service';
                throw error;
            } finally {
                this.services.loading = false;
            }
        },

        // Staff
        async fetchStaff(params = {}) {
            this.staff.loading = true;
            this.staff.error = null;

            try {
                const response = await api.get('/staff', {
                    params: {
                        page: this.staff.pagination.current_page,
                        per_page: this.staff.pagination.per_page,
                        ...params,
                    },
                });

                const payload = response.data?.data ?? response.data ?? {};
                this.staff.items = (payload.items || []).map((s) => ({
                    ...s,
                    // Normalize to Bookings UI expected fields
                    full_name: s.full_name ?? s.name ?? '',
                }));
                this.staff.pagination = payload.pagination || this.staff.pagination;
            } catch (error) {
                this.staff.error = error.response?.data?.message || error.message || 'Failed to fetch staff';
                console.error('Error fetching staff:', error);
            } finally {
                this.staff.loading = false;
            }
        },

        async createStaff(data) {
            this.staff.loading = true;
            this.staff.error = null;

            try {
                const payload = { ...(data || {}) };
                // Accept Bookings UI shape: { full_name, email, phone }
                if (payload.full_name && !payload.name) payload.name = payload.full_name;

                const response = await api.post('/staff', payload);
                return response.data?.data ?? response.data;
            } catch (error) {
                this.staff.error = error.response?.data?.message || error.message || 'Failed to create staff';
                throw error;
            } finally {
                this.staff.loading = false;
            }
        },

        async updateStaff(id, data) {
            this.staff.loading = true;
            this.staff.error = null;

            try {
                const payload = { ...(data || {}) };
                if (payload.full_name && !payload.name) payload.name = payload.full_name;

                const response = await api.put(`/staff/${id}`, payload);
                return response.data?.data ?? response.data;
            } catch (error) {
                this.staff.error = error.response?.data?.message || error.message || 'Failed to update staff';
                throw error;
            } finally {
                this.staff.loading = false;
            }
        },

        async deleteStaff(id) {
            this.staff.loading = true;
            this.staff.error = null;

            try {
                await api.delete(`/staff/${id}`);
                return true;
            } catch (error) {
                this.staff.error = error.response?.data?.message || error.message || 'Failed to delete staff';
                throw error;
            } finally {
                this.staff.loading = false;
            }
        },

        // Appointments (Bookings)
        async fetchAppointments(params = {}) {
            this.appointments.loading = true;
            this.appointments.error = null;

            try {
                const response = await api.get('/bookings', {
                    params: {
                        page: this.appointments.pagination.current_page,
                        per_page: this.appointments.pagination.per_page,
                        ...this.appointments.filters,
                        ...params,
                    },
                });

                const payload = response.data?.data ?? response.data ?? {};
                this.appointments.items = payload.items || [];
                this.appointments.pagination = payload.pagination || this.appointments.pagination;
            } catch (error) {
                this.appointments.error = error.response?.data?.message || error.message || 'Failed to fetch appointments';
                console.error('Error fetching appointments:', error);
            } finally {
                this.appointments.loading = false;
            }
        },

        async createAppointment(data) {
            this.appointments.loading = true;
            this.appointments.error = null;

            try {
                const response = await api.post('/bookings', data);
                return response.data?.data ?? response.data;
            } catch (error) {
                this.appointments.error = error.response?.data?.message || error.message || 'Failed to create appointment';
                throw error;
            } finally {
                this.appointments.loading = false;
            }
        },

        async updateAppointment(id, data) {
            this.appointments.loading = true;
            this.appointments.error = null;

            try {
                const response = await api.put(`/bookings/${id}`, data);
                return response.data?.data ?? response.data;
            } catch (error) {
                this.appointments.error = error.response?.data?.message || error.message || 'Failed to update appointment';
                throw error;
            } finally {
                this.appointments.loading = false;
            }
        },

        async deleteAppointment(id) {
            this.appointments.loading = true;
            this.appointments.error = null;

            try {
                await api.delete(`/bookings/${id}`);
                return true;
            } catch (error) {
                this.appointments.error = error.response?.data?.message || error.message || 'Failed to delete appointment';
                throw error;
            } finally {
                this.appointments.loading = false;
            }
        },

        // Settings
        async fetchSettings(section) {
            this.settings.loading = true;
            this.settings.error = null;

            try {
                const response = await api.get(`/booking/settings/${section}`);
                // Map section name to store property name
                const storeKey = section === 'business-hours' ? 'business_hours' : section;
                const payload = response.data?.data ?? response.data;
                this.settings[storeKey] = payload;
                return payload;
            } catch (error) {
                this.settings.error = error.response?.data?.message || error.message || 'Failed to fetch settings';
                throw error;
            } finally {
                this.settings.loading = false;
            }
        },

        async saveSettings(section, data) {
            this.settings.saving = true;
            this.settings.error = null;

            try {
                const response = await api.post(`/booking/settings/${section}`, data);
                // Map section name to store property name
                const storeKey = section === 'business-hours' ? 'business_hours' : section;
                const payload = response.data?.data ?? response.data;
                this.settings[storeKey] = payload;
                return payload;
            } catch (error) {
                this.settings.error = error.response?.data?.message || error.message || 'Failed to save settings';
                throw error;
            } finally {
                this.settings.saving = false;
            }
        },

        async fetchAllSettings() {
            this.settings.loading = true;
            this.settings.error = null;

            try {
                const response = await api.get('/booking/settings/all');
                const allSettings = response.data?.data ?? response.data;
                
                this.settings.general = allSettings.general || {};
                this.settings.url = allSettings.url || {};
                this.settings.calendar = allSettings.calendar || {};
                this.settings.company = allSettings.company || {};
                this.settings.customers = allSettings.customers || {};
                this.settings.appointments = allSettings.appointments || {};
                this.settings.payments = allSettings.payments || {};
                this.settings.appearance = allSettings.appearance || {};
                this.settings.business_hours = allSettings.business_hours || [];
                this.settings.holidays = allSettings.holidays || [];
                
                return allSettings;
            } catch (error) {
                this.settings.error = error.response?.data?.message || error.message || 'Failed to fetch settings';
                throw error;
            } finally {
                this.settings.loading = false;
            }
        },
    },
});

