<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Header và chức năng tìm kiếm -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0 mb-6">
        <div>
          <h1 class="text-2xl font-bold">Quản lý hội viên</h1>
          <p class="text-muted-foreground">Quản lý thông tin và gói tập của hội viên</p>
        </div>
        
        <div class="flex space-x-2">
          <a-input-search
            v-model:value="searchQuery"
            placeholder="Tìm kiếm hội viên..."
            class="md:w-64" 
            @search="onSearch"
            :allowClear="true"
          />
          
          <router-link 
            to="/members/add"
            class="inline-flex items-center"
          >
            <a-button type="primary">
              <template #icon><PlusOutlined /></template>
              Thêm hội viên
            </a-button>
          </router-link>
        </div>
      </div>
      
      <!-- Bộ lọc -->
      <div class="bg-white p-4 rounded-lg shadow">
        <a-form layout="inline" class="flex flex-wrap gap-4">
          <a-form-item label="Trạng thái">
            <a-select v-model:value="filters.status" style="width: 180px" allowClear>
              <a-select-option value="">Tất cả</a-select-option>
              <a-select-option value="active">Đang hoạt động</a-select-option>
              <a-select-option value="expired">Hết hạn</a-select-option>
              <a-select-option value="pending">Chờ kích hoạt</a-select-option>
            </a-select>
          </a-form-item>
          
          <a-form-item label="Loại gói tập">
            <a-select v-model:value="filters.packageType" style="width: 180px" allowClear>
              <a-select-option value="">Tất cả</a-select-option>
              <a-select-option value="month">Gói tháng</a-select-option>
              <a-select-option value="quarter">Gói quý</a-select-option>
              <a-select-option value="year">Gói năm</a-select-option>
              <a-select-option value="pt">Gói PT</a-select-option>
            </a-select>
          </a-form-item>
          
          <a-form-item label="Sắp xếp theo">
            <a-select v-model:value="filters.sortBy" style="width: 180px">
              <a-select-option value="name">Tên</a-select-option>
              <a-select-option value="expiryDate">Ngày hết hạn</a-select-option>
              <a-select-option value="joinDate">Ngày tham gia</a-select-option>
              <a-select-option value="lastCheckIn">Lần check-in gần nhất</a-select-option>
            </a-select>
          </a-form-item>
          
          <a-form-item>
            <a-button @click="resetFilters">
              Đặt lại bộ lọc
            </a-button>
          </a-form-item>
        </a-form>
      </div>
      
      <!-- Danh sách hội viên -->
      <a-card class="shadow">
        <a-table
          :dataSource="filteredMembers"
          :columns="columns"
          :pagination="{ 
            pageSize: 10,
            showSizeChanger: true,
            showTotal: total => `Hiển thị ${Math.min(total, filteredMembers.length > 0 ? 1 : 0)} đến ${Math.min(total, filteredMembers.length)} của ${total} hội viên` 
          }"
          :rowKey="record => record.id"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'name'">
              <div class="flex items-center">
                <a-avatar :size="40" class="bg-primary mr-3">
                  {{ record.name.charAt(0) }}
                </a-avatar>
                <div>
                  <div class="font-medium">{{ record.name }}</div>
                  <div class="text-xs text-muted-foreground">{{ record.phone }}</div>
                </div>
              </div>
            </template>
            
            <template v-else-if="column.key === 'package'">
              <div>{{ record.package }}</div>
              <div class="text-xs text-muted-foreground">{{ record.packageType }}</div>
            </template>
            
            <template v-else-if="column.key === 'status'">
              <a-tag :color="getStatusColor(record.status)">
                {{ getMemberStatusText(record.status) }}
              </a-tag>
            </template>
            
            <template v-else-if="column.key === 'lastCheckIn'">
              <div>{{ record.lastCheckIn }}</div>
              <div v-if="record.status === 'active'" class="text-xs text-muted-foreground">
                {{ record.totalCheckIns }} lần check-in
              </div>
            </template>
            
            <template v-else-if="column.key === 'actions'">
              <a-space>
                <a-button type="primary" size="small" @click="openCheckInModal(record)">
                  Check-in
                </a-button>
                <router-link :to="`/members/${record.id}`">
                  <a-button size="small">Chi tiết</a-button>
                </router-link>
              </a-space>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
  </MainLayout>

  <!-- Thêm CheckInModal -->
  <CheckInModal 
    :is-open="showCheckInModal"
    :member="selectedMember"
    @close="showCheckInModal = false"
    @confirm="handleCheckin"
    @update:isOpen="val => showCheckInModal = val"
  />
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import MainLayout from '../../layouts/MainLayout.vue';
import CheckInModal from '../../components/members/CheckInModal.vue';
import { PlusOutlined, SearchOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import { useMemberApi } from '../../composables/useMemberApi';
import { useMembershipApi } from '../../composables/useMembershipApi';
import dayjs from 'dayjs';

// API
const { 
  fetchMembers, 
  loading: memberLoading,
  error: memberError 
} = useMemberApi();

const { 
  fetchMemberships,
  loading: membershipLoading
} = useMembershipApi();

// State
const members = ref([]);
const memberships = ref([]);
const totalMembers = ref(0);
const selectedMember = ref(null);
const showCheckInModal = ref(false);

// Bộ lọc và tìm kiếm
const searchQuery = ref('');
const filters = ref({
  status: '',
  packageType: '',
  sortBy: 'name',
  page: 1,
  perPage: 10
});

// Tải danh sách hội viên khi component được tạo
onMounted(async () => {
  await Promise.all([
    loadMembers(),
    loadMemberships()
  ]);
});

// Load danh sách hội viên
const loadMembers = async () => {
  try {
    const params = {
      search: searchQuery.value || undefined,
      status: filters.value.status || undefined,
      membership_type: filters.value.packageType || undefined,
      sort: filters.value.sortBy || 'name',
      page: filters.value.page,
      per_page: filters.value.perPage
    };
    
    const response = await fetchMembers(params);
    members.value = response.data;
    totalMembers.value = response.total;
  } catch (error) {
    console.error('Error loading members:', error);
    message.error('Không thể tải danh sách hội viên');
  }
};

// Load danh sách gói tập
const loadMemberships = async () => {
  try {
    const response = await fetchMemberships();
    memberships.value = response;
  } catch (error) {
    console.error('Error loading memberships:', error);
    message.error('Không thể tải danh sách gói tập');
  }
};

// Tìm kiếm
const onSearch = () => {
  filters.value.page = 1;
  loadMembers();
};

// Reset bộ lọc
const resetFilters = () => {
  filters.value = {
    status: '',
    packageType: '',
    sortBy: 'name',
    page: 1,
    perPage: 10
  };
  searchQuery.value = '';
  loadMembers();
};

// Phân trang thay đổi
const handleTableChange = (pagination) => {
  filters.value.page = pagination.current;
  filters.value.perPage = pagination.pageSize;
  loadMembers();
};

// Format status
const getMemberStatusText = (status) => {
  const statusMap = {
    'active': 'Đang hoạt động',
    'inactive': 'Không hoạt động',
    'pending': 'Chờ kích hoạt',
    'frozen': 'Tạm dừng'
  };
  return statusMap[status] || status;
};

// Get color based on status
const getStatusColor = (status) => {
  const colorMap = {
    'active': 'green',
    'inactive': 'red',
    'pending': 'blue',
    'frozen': 'orange'
  };
  return colorMap[status] || 'default';
};

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return dayjs(dateString).format('DD/MM/YYYY');
};

// Format membership days left
const formatDaysLeft = (endDate) => {
  if (!endDate) return 'N/A';
  const dayDiff = dayjs(endDate).diff(dayjs(), 'day');
  return dayDiff >= 0 ? `${dayDiff} ngày` : 'Hết hạn';
};

// Open check-in modal
const openCheckInModal = (member) => {
  selectedMember.value = member;
  showCheckInModal.value = true;
};

// Handle checkin
const handleCheckin = (result) => {
  message.success(`Check-in thành công cho ${result.data.member_name}`);
  loadMembers(); // Reload data
};

// Table columns
const columns = [
  {
    title: 'Hội viên',
    key: 'name',
    dataIndex: 'name'
  },
  {
    title: 'Gói tập',
    key: 'membership',
    dataIndex: 'membership'
  },
  {
    title: 'Trạng thái',
    key: 'status',
    dataIndex: 'status'
  },
  {
    title: 'Ngày hết hạn',
    key: 'membership_end_date',
    dataIndex: 'membership_end_date',
    sorter: true
  },
  {
    title: 'Check-in gần nhất',
    key: 'last_checkin',
    dataIndex: 'last_checkin'
  },
  {
    title: 'Thao tác',
    key: 'actions',
    width: '200px'
  }
];
</script> 