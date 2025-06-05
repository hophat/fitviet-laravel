import axios from 'axios';
import { ref } from 'vue';

export function useMemberApi() {
  const loading = ref(false);
  const error = ref(null);

  // Lấy danh sách hội viên
  const fetchMembers = async (params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.get('/api/members', { params });
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi tải danh sách hội viên';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Lấy thông tin chi tiết hội viên
  const fetchMember = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.get(`/api/members/${id}`);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi tải thông tin hội viên';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Tạo hội viên mới
  const createMember = async (memberData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.post('/api/members', memberData);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi tạo hội viên';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Cập nhật thông tin hội viên
  const updateMember = async (id, memberData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.put(`/api/members/${id}`, memberData);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi cập nhật hội viên';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Xóa hội viên
  const deleteMember = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      await axios.delete(`/api/members/${id}`);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi xóa hội viên';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Lấy lịch sử đơn hàng của hội viên
  const fetchMemberOrders = async (id, params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.get(`/api/members/${id}/orders`, { params });
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi tải lịch sử đơn hàng';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Lấy lịch sử thanh toán của hội viên
  const fetchMemberPayments = async (id, params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.get(`/api/members/${id}/payments`, { params });
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi tải lịch sử thanh toán';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Lấy thông tin gói tập của hội viên
  const fetchMemberMembership = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.get(`/api/members/${id}/membership`);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi tải thông tin gói tập';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Check-in hội viên
  const checkInMember = async (id, checkInData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.post(`/api/members/${id}/checkin`, checkInData);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi check-in hội viên';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Gia hạn gói tập cho hội viên
  const renewMembership = async (id, membershipData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.post(`/api/members/${id}/renew`, membershipData);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi gia hạn gói tập';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    fetchMembers,
    fetchMember,
    createMember,
    updateMember,
    deleteMember,
    fetchMemberOrders,
    fetchMemberPayments,
    fetchMemberMembership,
    checkInMember,
    renewMembership
  };
} 