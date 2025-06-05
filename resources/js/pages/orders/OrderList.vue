<template>
  <div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Danh sách đơn hàng</h1>
      <div class="flex gap-2">
        <a-button type="primary" @click="refreshOrders">
          <template #icon><reload-outlined /></template>
          Làm mới
        </a-button>
        <a-button type="primary">
          <router-link to="/pos" class="text-white">
            <template #icon><plus-outlined /></template>
            Tạo đơn hàng mới
          </router-link>
        </a-button>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
      <div class="flex flex-wrap gap-4 mb-4">
        <a-input-search
          v-model:value="searchQuery"
          placeholder="Tìm theo mã đơn hàng, tên khách hàng"
          style="width: 300px"
          @search="handleSearch"
        />
        
        <a-range-picker
          v-model:value="dateRange"
          @change="handleDateRangeChange"
          format="DD/MM/YYYY"
        />
        
        <a-select
          v-model:value="statusFilter"
          placeholder="Lọc theo trạng thái"
          style="width: 180px"
          @change="handleStatusChange"
        >
          <a-select-option value="">Tất cả trạng thái</a-select-option>
          <a-select-option value="pending">Chờ xử lý</a-select-option>
          <a-select-option value="processing">Đang xử lý</a-select-option>
          <a-select-option value="completed">Hoàn thành</a-select-option>
          <a-select-option value="cancelled">Đã hủy</a-select-option>
          <a-select-option value="refunded">Đã hoàn tiền</a-select-option>
        </a-select>
      </div>
      
      <a-table
        :columns="columns"
        :data-source="orders"
        :loading="loading"
        :pagination="pagination"
        @change="handleTableChange"
        row-key="id"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'status'">
            <a-tag :color="getStatusColor(record.status)">
              {{ getStatusText(record.status) }}
            </a-tag>
          </template>
          
          <template v-if="column.key === 'action'">
            <div class="flex space-x-2">
              <a-button type="primary" size="small">
                <router-link :to="`/orders/${record.id}`">
                  Chi tiết
                </router-link>
              </a-button>
              <a-button type="default" size="small" @click="printOrder(record.id)">
                In hóa đơn
              </a-button>
            </div>
          </template>
        </template>
      </a-table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { ReloadOutlined, PlusOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import axios from 'axios';

// State
const orders = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const dateRange = ref([]);
const statusFilter = ref('');
const pagination = ref({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  pageSizeOptions: ['10', '20', '50', '100'],
});

// Table columns
const columns = [
  {
    title: 'Mã đơn hàng',
    dataIndex: 'order_number',
    key: 'order_number',
    sorter: true,
  },
  {
    title: 'Khách hàng',
    dataIndex: 'customer_name',
    key: 'customer_name',
  },
  {
    title: 'Ngày tạo',
    dataIndex: 'created_at',
    key: 'created_at',
    sorter: true,
  },
  {
    title: 'Tổng tiền',
    dataIndex: 'total_amount',
    key: 'total_amount',
    sorter: true,
    align: 'right',
  },
  {
    title: 'Trạng thái',
    dataIndex: 'status',
    key: 'status',
    filters: [
      { text: 'Chờ xử lý', value: 'pending' },
      { text: 'Đang xử lý', value: 'processing' },
      { text: 'Hoàn thành', value: 'completed' },
      { text: 'Đã hủy', value: 'cancelled' },
      { text: 'Đã hoàn tiền', value: 'refunded' },
    ],
  },
  {
    title: 'Thao tác',
    key: 'action',
    fixed: 'right',
    width: 200,
  },
];

// Methods
const fetchOrders = async () => {
  loading.value = true;
  try {
    // Xây dựng query params
    const params = {
      page: pagination.value.current,
      per_page: pagination.value.pageSize,
      search: searchQuery.value || undefined,
      status: statusFilter.value || undefined,
      sort_field: 'created_at',
      sort_order: 'desc',
    };
    
    // Thêm date range nếu được chọn
    if (dateRange.value && dateRange.value.length === 2) {
      params.from_date = dateRange.value[0].format('YYYY-MM-DD');
      params.to_date = dateRange.value[1].format('YYYY-MM-DD');
    }
    
    const response = await axios.get('/api/orders', { params });
    orders.value = response.data.data.map(order => ({
      ...order,
      key: order.id
    }));
    pagination.value.total = response.data.total;
  } catch (error) {
    console.error('Failed to fetch orders:', error);
    message.error('Không thể tải danh sách đơn hàng');
  } finally {
    loading.value = false;
  }
};

const refreshOrders = () => {
  fetchOrders();
};

const handleSearch = () => {
  pagination.value.current = 1;
  fetchOrders();
};

const handleDateRangeChange = () => {
  pagination.value.current = 1;
  fetchOrders();
};

const handleStatusChange = () => {
  pagination.value.current = 1;
  fetchOrders();
};

const handleTableChange = (pag, filters, sorter) => {
  pagination.value.current = pag.current;
  pagination.value.pageSize = pag.pageSize;
  fetchOrders();
};

const printOrder = (orderId) => {
  // Triển khai chức năng in đơn hàng
  message.info(`Đang chuẩn bị in đơn hàng #${orderId}`);
  // window.open(`/orders/${orderId}/print`, '_blank');
};

const getStatusColor = (status) => {
  const colors = {
    pending: 'blue',
    processing: 'orange',
    completed: 'green',
    cancelled: 'red',
    refunded: 'purple',
  };
  return colors[status] || 'default';
};

const getStatusText = (status) => {
  const statusText = {
    pending: 'Chờ xử lý',
    processing: 'Đang xử lý',
    completed: 'Hoàn thành',
    cancelled: 'Đã hủy',
    refunded: 'Hoàn tiền',
  };
  return statusText[status] || status;
};

// Lifecycle hooks
onMounted(() => {
  fetchOrders();
});
</script> 