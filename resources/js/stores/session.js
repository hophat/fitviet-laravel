import { defineStore } from 'pinia';
import axios from 'axios';

export const useSessionStore = defineStore('session', {
  state: () => ({
    sessions: [],
    loading: false,
    error: null,
  }),

  getters: {
    upcomingSessions: (state) => {
      const now = new Date();
      return state.sessions.filter(session => new Date(session.start) > now)
        .sort((a, b) => new Date(a.start) - new Date(b.start));
    },
    
    completedSessions: (state) => {
      const now = new Date();
      return state.sessions.filter(session => new Date(session.start) <= now)
        .sort((a, b) => new Date(b.start) - new Date(a.start));
    },
    
    sessionsByDate: (state) => (date) => {
      return state.sessions.filter(session => {
        const sessionDate = new Date(session.start);
        return sessionDate.toDateString() === date.toDateString();
      });
    }
  },

  actions: {
    async fetchSessions(memberId) {
      this.loading = true;
      try {
        const response = await axios.get(`/api/members/${memberId}/sessions`);
        this.sessions = response.data.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load sessions';
        console.error('Error fetching sessions:', error);
      } finally {
        this.loading = false;
      }
    },

    async createSession(sessionData) {
      try {
        const response = await axios.post('/api/sessions', sessionData);
        this.sessions.push(response.data.data);
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create session';
        throw error;
      }
    },

    async updateSession(sessionId, sessionData) {
      try {
        const response = await axios.put(`/api/sessions/${sessionId}`, sessionData);
        const index = this.sessions.findIndex(s => s.id === sessionId);
        if (index !== -1) {
          this.sessions[index] = response.data.data;
        }
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update session';
        throw error;
      }
    },

    async deleteSession(sessionId) {
      try {
        await axios.delete(`/api/sessions/${sessionId}`);
        this.sessions = this.sessions.filter(s => s.id !== sessionId);
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete session';
        throw error;
      }
    },

    clearSessions() {
      this.sessions = [];
      this.error = null;
    }
  }
});