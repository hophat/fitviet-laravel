<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Header và chức năng tìm kiếm -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0 mb-6">
        <div>
          <h1 class="text-2xl font-bold">Quản lý gói tập</h1>
          <p class="text-muted-foreground">Thiết lập và quản lý các gói tập cho hội viên</p>
        </div>
        
        <div class="flex space-x-2">
          <div class="relative">
            <input 
              type="text" 
              placeholder="Tìm kiếm gói tập..." 
              v-model="searchQuery"
              class="px-3 py-2 border rounded-md w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-primary/50"
            />
            <span class="absolute right-3 top-2">🔍</span>
          </div>
          
          <button class="bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90 transition-colors">
            + Thêm gói tập
          </button>
        </div>
      </div>
      
      <!-- Bộ lọc -->
      <div class="bg-white p-4 rounded-lg shadow">
        <div class="flex flex-wrap gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Trạng thái</label>
            <select v-model="filters.status" class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50">
              <option value="">Tất cả</option>
              <option value="active">Đang hoạt động</option>
              <option value="inactive">Không hoạt động</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Thời hạn</label>
            <select v-model="filters.duration" class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50">
              <option value="">Tất cả</option>
              <option value="1">1 tháng</option>
              <option value="3">3 tháng</option>
              <option value="6">6 tháng</option>
              <option value="12">12 tháng</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Sắp xếp theo</label>
            <select v-model="filters.sortBy" class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50">
              <option value="name">Tên</option>
              <option value="price">Giá</option>
              <option value="duration">Thời hạn</option>
            </select>
          </div>
          
          <div class="flex items-end">
            <button 
              @click="resetFilters"
              class="px-4 py-2 bg-muted hover:bg-muted/80 rounded-md transition-colors"
            >
              Đặt lại bộ lọc
            </button>
          </div>
        </div>
      </div>
      
      <!-- Danh sách gói tập -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-muted/30">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Gói tập</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Thời hạn</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Giá</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Quyền lợi</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Trạng thái</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(membership, index) in filteredMemberships" :key="index" class="hover:bg-muted/10">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="font-medium">{{ membership.name }}</div>
                  <div class="text-xs text-muted-foreground">{{ membership.description }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  {{ formatDuration(membership.duration) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="font-medium text-primary">{{ formatPrice(membership.price) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-1">
                    <span 
                      v-for="(feature, featureIndex) in membership.features" 
                      :key="featureIndex"
                      class="inline-block px-2 py-1 text-xs bg-muted/30 rounded-full"
                    >
                      {{ feature }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    :class="{
                      'px-2 py-1 rounded-full text-xs': true,
                      'bg-green-100 text-green-800': membership.status === 'active',
                      'bg-red-100 text-red-800': membership.status === 'inactive'
                    }"
                  >
                    {{ getMembershipStatusText(membership.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex space-x-2">
                    <button class="p-1 bg-blue-100 hover:bg-blue-200 rounded-md text-sm text-blue-800 transition-colors">
                      Sửa
                    </button>
                    <button 
                      v-if="membership.status === 'active'"
                      class="p-1 bg-red-100 hover:bg-red-200 rounded-md text-sm text-red-800 transition-colors"
                    >
                      Vô hiệu
                    </button>
                    <button 
                      v-else
                      class="p-1 bg-green-100 hover:bg-green-200 rounded-md text-sm text-green-800 transition-colors"
                    >
                      Kích hoạt
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Phân trang -->
        <div class="bg-muted/10 px-6 py-3 border-t flex items-center justify-between">
          <div>
            <p class="text-sm text-muted-foreground">
              Hiển thị <span class="font-medium">1</span> đến <span class="font-medium">{{ memberships.length }}</span> của <span class="font-medium">{{ memberships.length }}</span> gói tập
            </p>
          </div>
          <div class="flex space-x-1">
            <button class="px-3 py-1 bg-white rounded-md border hover:bg-muted/10 transition-colors">
              Trước
            </button>
            <button class="px-3 py-1 bg-primary/10 text-primary rounded-md border border-primary/30 transition-colors">
              1
            </button>
            <button class="px-3 py-1 bg-white rounded-md border hover:bg-muted/10 transition-colors">
              Tiếp
            </button>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import MainLayout from '../../layouts/MainLayout.vue';

// Mock data - sẽ được thay thế bằng API calls
const memberships = ref([
  { 
    id: 1, 
    name: 'Gói Cơ Bản', 
    description: 'Sử dụng phòng tập không giới hạn', 
    duration: 1, 
    price: 300000, 
    features: ['Phòng tập không giới hạn', 'Tủ đồ cá nhân'],
    status: 'active',
    max_freeze_days: 0,
    guest_passes: 0,
    pt_sessions: 0
  },
  { 
    id: 2, 
    name: 'Gói Standard 3 tháng', 
    description: 'Phòng tập và các lớp tập theo nhóm', 
    duration: 3, 
    price: 750000, 
    features: ['Phòng tập không giới hạn', 'Lớp tập nhóm', 'Tủ đồ cá nhân', 'Đánh giá thể chất'],
    status: 'active',
    max_freeze_days: 7,
    guest_passes: 1,
    pt_sessions: 0
  },
  { 
    id: 3, 
    name: 'Gói VIP 6 tháng', 
    description: 'Trải nghiệm tập luyện cao cấp', 
    duration: 6, 
    price: 1500000, 
    features: ['Phòng tập không giới hạn', 'Lớp tập nhóm', 'Tủ đồ cá nhân', 'Đánh giá thể chất', '2 buổi PT', 'Hỗ trợ dinh dưỡng'],
    status: 'active',
    max_freeze_days: 15,
    guest_passes: 3,
    pt_sessions: 2
  },
  { 
    id: 4, 
    name: 'Gói Platinum 12 tháng', 
    description: 'Trọn gói cao cấp nhất', 
    duration: 12, 
    price: 2500000, 
    features: ['Phòng tập không giới hạn', 'Lớp tập nhóm', 'Tủ đồ VIP', 'Đánh giá thể chất', '5 buổi PT', 'Hỗ trợ dinh dưỡng', 'Khăn tập miễn phí', 'Giặt đồ'],
    status: 'active',
    max_freeze_days: 30,
    guest_passes: 5,
    pt_sessions: 5
  },
  { 
    id: 5, 
    name: 'Gói Cặp Đôi 3 tháng', 
    description: 'Dành cho 2 người tập cùng nhau', 
    duration: 3, 
    price: 1200000, 
    features: ['Phòng tập không giới hạn', 'Lớp tập nhóm', 'Tủ đồ cá nhân', 'Đánh giá thể chất', '1 buổi PT chung'],
    status: 'inactive',
    max_freeze_days: 7,
    guest_passes: 0,
    pt_sessions: 1
  },
]);

// Bộ lọc và tìm kiếm
const searchQuery = ref('');
const filters = ref({
  status: '',
  duration: '',
  sortBy: 'name'
});

// Reset bộ lọc
const resetFilters = () => {
  filters.value = {
    status: '',
    duration: '',
    sortBy: 'name'
  };
  searchQuery.value = '';
};

// Lọc và sắp xếp memberships
const filteredMemberships = computed(() => {
  let result = [...memberships.value];
  
  // Lọc theo trạng thái
  if (filters.value.status) {
    result = result.filter(membership => membership.status === filters.value.status);
  }
  
  // Lọc theo thời hạn
  if (filters.value.duration) {
    result = result.filter(membership => membership.duration == filters.value.duration);
  }
  
  // Tìm kiếm
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(membership => 
      membership.name.toLowerCase().includes(query) || 
      membership.description.toLowerCase().includes(query)
    );
  }
  
  // Sắp xếp
  result.sort((a, b) => {
    if (filters.value.sortBy === 'name') {
      return a.name.localeCompare(b.name);
    } else if (filters.value.sortBy === 'price') {
      return a.price - b.price;
    } else if (filters.value.sortBy === 'duration') {
      return a.duration - b.duration;
    }
    
    return 0;
  });
  
  return result;
});

// Định dạng thời hạn
const formatDuration = (duration) => {
  return `${duration} tháng`;
};

// Định dạng giá
const formatPrice = (price) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0
  }).format(price);
};

// Chuyển đổi trạng thái
const getMembershipStatusText = (status) => {
  const statusMap = {
    'active': 'Đang hoạt động',
    'inactive': 'Không hoạt động'
  };
  
  return statusMap[status] || status;
};
</script> 