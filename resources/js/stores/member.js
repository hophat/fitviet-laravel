import { defineStore } from 'pinia';
import axios from 'axios';

export const useMemberStore = defineStore('member', {
  state: () => ({
    member: null,
    members: [],
    loading: false,
    error: null,
    currentMembershipInfo: null,
    checkInHistory: [],
    statistics: {
      totalVisits: 0,
      thisMonthVisits: 0,
      activeMemberships: 0,
      upcomingSessions: 0
    }
  }),

  getters: {
    isActiveMember: (state) => {
      if (!state.currentMembershipInfo) return false;
      const now = new Date();
      const endDate = new Date(state.currentMembershipInfo.end_date);
      return endDate > now && state.currentMembershipInfo.status === 'active';
    },
    
    membershipDaysRemaining: (state) => {
      if (!state.currentMembershipInfo) return 0;
      const now = new Date();
      const endDate = new Date(state.currentMembershipInfo.end_date);
      const days = Math.ceil((endDate - now) / (1000 * 60 * 60 * 24));
      return days > 0 ? days : 0;
    }
  },

  actions: {
    async fetchMember(memberId) {
      this.loading = true;
      try {
        const response = await axios.get(`/api/members/${memberId}`);
        this.member = response.data.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load member';
        console.error('Error fetching member:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchMembers() {
      this.loading = true;
      try {
        const response = await axios.get('/api/members');
        this.members = response.data.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load members';
        console.error('Error fetching members:', error);
      } finally {
        this.loading = false;
      }
    },

    async createMember(memberData) {
      try {
        const response = await axios.post('/api/members', memberData);
        this.members.push(response.data.data);
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create member';
        throw error;
      }
    },

    async updateMember(memberId, memberData) {
      try {
        const response = await axios.put(`/api/members/${memberId}`, memberData);
        const index = this.members.findIndex(m => m.id === memberId);
        if (index !== -1) {
          this.members[index] = response.data.data;
        }
        if (this.member?.id === memberId) {
          this.member = response.data.data;
        }
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update member';
        throw error;
      }
    },

    async deleteMember(memberId) {
      try {
        await axios.delete(`/api/members/${memberId}`);
        this.members = this.members.filter(m => m.id !== memberId);
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete member';
        throw error;
      }
    },

    async fetchMembershipInfo(memberId) {
      try {
        const response = await axios.get(`/api/members/${memberId}/membership`);
        this.currentMembershipInfo = response.data.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load membership info';
        console.error('Error fetching membership info:', error);
      }
    },

    async checkIn(memberId) {
      try {
        const response = await axios.post(`/api/members/${memberId}/check-in`);
        this.checkInHistory.unshift(response.data.data);
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to check in';
        throw error;
      }
    },

    async fetchCheckInHistory(memberId) {
      try {
        const response = await axios.get(`/api/members/${memberId}/check-ins`);
        this.checkInHistory = response.data.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load check-in history';
        console.error('Error fetching check-in history:', error);
      }
    },

    async fetchStatistics(memberId) {
      try {
        const response = await axios.get(`/api/members/${memberId}/statistics`);
        this.statistics = response.data.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load statistics';
        console.error('Error fetching statistics:', error);
      }
    },

    clearMember() {
      this.member = null;
      this.currentMembershipInfo = null;
      this.checkInHistory = [];
      this.statistics = {
        totalVisits: 0,
        thisMonthVisits: 0,
        activeMemberships: 0,
        upcomingSessions: 0
      };
      this.error = null;
    }
  }
});