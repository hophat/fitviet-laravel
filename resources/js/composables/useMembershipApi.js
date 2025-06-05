import axios from 'axios';
import { ref } from 'vue';

export function useMembershipApi() {
  const loading = ref(false);
  const error = ref(null);

  // Lấy danh sách gói thành viên
  const fetchMemberships = async (params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.get('/api/memberships', { params });
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi tải danh sách gói thành viên';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Lấy thông tin chi tiết gói thành viên
  const fetchMembership = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.get(`/api/memberships/${id}`);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi tải thông tin gói thành viên';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Tạo gói thành viên mới
  const createMembership = async (membershipData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.post('/api/memberships', membershipData);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi tạo gói thành viên';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Cập nhật thông tin gói thành viên
  const updateMembership = async (id, membershipData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.put(`/api/memberships/${id}`, membershipData);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi cập nhật gói thành viên';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Xóa gói thành viên
  const deleteMembership = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      await axios.delete(`/api/memberships/${id}`);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi xóa gói thành viên';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Lấy danh sách hội viên trong gói
  const fetchMembershipMembers = async (id, params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.get(`/api/memberships/${id}/members`, { params });
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi tải danh sách hội viên trong gói';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  // Thêm hội viên vào gói tập
  const assignMemberToMembership = async (membershipId, memberId, data) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await axios.post(`/api/memberships/${membershipId}/members/${memberId}`, data);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Đã xảy ra lỗi khi thêm hội viên vào gói tập';
      throw error.value;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    fetchMemberships,
    fetchMembership,
    createMembership,
    updateMembership,
    deleteMembership,
    fetchMembershipMembers,
    assignMemberToMembership
  };
} 