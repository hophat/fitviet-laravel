import { ref } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/vi';

dayjs.locale('vi');

export function useUtils() {
  // Date formatting functions
  const formatDate = (date) => {
    return dayjs(date).format('DD/MM/YYYY');
  };

  const formatDateTime = (date) => {
    return dayjs(date).format('DD/MM/YYYY HH:mm');
  };

  const formatTime = (date) => {
    return dayjs(date).format('HH:mm');
  };

  const formatFullDate = (date) => {
    return dayjs(date).format('dddd, DD [tháng] MM [năm] YYYY');
  };

  const formatDayDetail = (date) => {
    return dayjs(date).format('dddd, DD [tháng] MM [năm] YYYY');
  };

  const formatMonth = (date) => {
    return dayjs(date).format('MMMM YYYY');
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND'
    }).format(amount);
  };

  const formatNumber = (number) => {
    return new Intl.NumberFormat('vi-VN').format(number);
  };

  // Status helpers
  const getOrderStatusColor = (status) => {
    const statusColors = {
      pending: 'warning',
      processing: 'processing',
      completed: 'success',
      cancelled: 'error',
      refunded: 'default'
    };
    return statusColors[status] || 'default';
  };

  const getOrderStatusText = (status) => {
    const statusTexts = {
      pending: 'Chờ xử lý',
      processing: 'Đang xử lý',
      completed: 'Hoàn thành',
      cancelled: 'Đã hủy',
      refunded: 'Đã hoàn tiền'
    };
    return statusTexts[status] || status;
  };

  const getMembershipStatusColor = (status) => {
    const statusColors = {
      active: 'success',
      expired: 'error',
      pending: 'warning',
      suspended: 'default'
    };
    return statusColors[status] || 'default';
  };

  const getMembershipStatusText = (status) => {
    const statusTexts = {
      active: 'Đang hoạt động',
      expired: 'Hết hạn',
      pending: 'Chờ kích hoạt',
      suspended: 'Tạm ngưng'
    };
    return statusTexts[status] || status;
  };

  // Session type helpers
  const getSessionTypeColor = (type) => {
    const typeColors = {
      pt: 'blue',
      class: 'green',
      gym: 'purple'
    };
    return typeColors[type] || 'default';
  };

  const getSessionTypeText = (type) => {
    const typeTexts = {
      pt: 'Buổi PT',
      class: 'Lớp tập nhóm',
      gym: 'Tập tự do'
    };
    return typeTexts[type] || type;
  };

  // Date comparison helpers
  const isToday = (date) => {
    return dayjs(date).isSame(dayjs(), 'day');
  };

  const isPast = (date) => {
    return dayjs(date).isBefore(dayjs(), 'day');
  };

  const isFuture = (date) => {
    return dayjs(date).isAfter(dayjs(), 'day');
  };

  const isUpcoming = (item) => {
    if (!item.start) return false;
    return dayjs(item.start).isAfter(dayjs());
  };

  const isCompleted = (item) => {
    if (!item.start) return false;
    return dayjs(item.start).isBefore(dayjs());
  };

  // Validation helpers
  const isValidEmail = (email) => {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  };

  const isValidPhone = (phone) => {
    const re = /^(0[3|5|7|8|9])+([0-9]{8})$/;
    return re.test(phone);
  };

  return {
    formatDate,
    formatDateTime,
    formatTime,
    formatFullDate,
    formatDayDetail,
    formatMonth,
    formatCurrency,
    formatNumber,
    getOrderStatusColor,
    getOrderStatusText,
    getMembershipStatusColor,
    getMembershipStatusText,
    getSessionTypeColor,
    getSessionTypeText,
    isToday,
    isPast,
    isFuture,
    isUpcoming,
    isCompleted,
    isValidEmail,
    isValidPhone
  };
}