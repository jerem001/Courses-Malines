import { defineStore } from 'pinia';
import { api } from '../lib/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: sessionStorage.getItem('cm_token'),
    user: null,
  }),
  actions: {
    async login(email, password) {
      const { data } = await api.post('/v1/auth/login', { email, password });
      this.token = data.token;
      this.user = data.user;
      sessionStorage.setItem('cm_token', data.token);
    },
    async me() {
      const { data } = await api.get('/v1/auth/me');
      this.user = data;
    },
    async logout() {
      try {
        await api.post('/v1/auth/logout');
      } finally {
        this.token = null;
        this.user = null;
        sessionStorage.removeItem('cm_token');
      }
    },
  },
});
