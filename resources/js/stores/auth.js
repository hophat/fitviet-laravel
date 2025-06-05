import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null
  }),
  
  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user && state.user.role === 'admin',
    isOwner: (state) => state.user && state.user.role === 'owner',
    isStaff: (state) => state.user && state.user.role === 'staff',
    isMember: (state) => state.user && state.user.role === 'member',
    canManageProducts: (state) => state.user && ['admin', 'owner', 'staff'].includes(state.user.role),
    canViewReports: (state) => state.user && ['admin', 'owner'].includes(state.user.role),
  },
  
  actions: {
    async login(credentials) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('/api/login', credentials);
        this.setUser(response.data.user);
        this.setToken(response.data.token);
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Đã xảy ra lỗi khi đăng nhập';
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    async register(userData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('/api/register', userData);
        this.setUser(response.data.user);
        this.setToken(response.data.token);
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Đã xảy ra lỗi khi đăng ký';
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    async logout() {
      this.loading = true;
      this.error = null;
      
      try {
        await axios.post('/api/logout', {}, {
          headers: {
            Authorization: `Bearer ${this.token}`
          }
        });
      } catch (error) {
        console.error('Lỗi khi đăng xuất:', error);
      } finally {
        this.clearAuth();
        this.loading = false;
      }
    },
    
    async fetchUser() {
      if (!this.token) return;
      
      this.loading = true;
      
      try {
        const response = await axios.get('/api/user', {
          headers: {
            Authorization: `Bearer ${this.token}`
          }
        });
        this.setUser(response.data);
      } catch (error) {
        this.error = error.response?.data?.message || 'Không thể lấy thông tin người dùng';
        this.clearAuth();
      } finally {
        this.loading = false;
      }
    },
    
    setUser(user) {
      this.user = user;
    },
    
    setToken(token) {
      this.token = token;
      localStorage.setItem('token', token);
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    },
    
    clearAuth() {
      this.user = null;
      this.token = null;
      localStorage.removeItem('token');
      delete axios.defaults.headers.common['Authorization'];
    }
  }
}); 