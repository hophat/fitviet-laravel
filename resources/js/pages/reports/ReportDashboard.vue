<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Header và điều khiển -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0 mb-6">
        <div>
          <h1 class="text-2xl font-bold">Báo cáo và thống kê</h1>
          <p class="text-muted-foreground">Thống kê doanh thu, hội viên và hoạt động phòng tập</p>
        </div>
        
        <div class="flex items-center space-x-4">
          <div class="flex space-x-2">
            <a-select v-model:value="selectedDateRange" style="width: 150px">
              <a-select-option value="today">Hôm nay</a-select-option>
              <a-select-option value="yesterday">Hôm qua</a-select-option>
              <a-select-option value="week">Tuần này</a-select-option>
              <a-select-option value="month">Tháng này</a-select-option>
              <a-select-option value="year">Năm nay</a-select-option>
              <a-select-option value="custom">Tùy chỉnh</a-select-option>
            </a-select>
            
            <a-button type="primary">
              <template #icon><ExportOutlined /></template>
              <span class="hidden md:inline">Xuất báo cáo</span>
              <span class="md:hidden">Xuất</span>
            </a-button>
          </div>
        </div>
      </div>
      
      <!-- Bộ lọc thời gian tùy chỉnh -->
      <div v-if="selectedDateRange === 'custom'" class="bg-white p-4 rounded-lg shadow mb-6">
        <div class="flex flex-wrap items-end gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Từ ngày</label>
            <a-date-picker v-model:value="dateRange.start" format="DD/MM/YYYY" />
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Đến ngày</label>
            <a-date-picker v-model:value="dateRange.end" format="DD/MM/YYYY" />
          </div>
          
          <a-button type="primary" @click="applyDateRange">
            Áp dụng
          </a-button>
        </div>
      </div>
      
      <!-- Thẻ thống kê tổng quan -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-muted-foreground">Doanh thu</p>
              <p class="text-2xl font-bold">{{ formatCurrency(summary.revenue) }}</p>
            </div>
            <div class="h-10 w-10 bg-primary/10 text-primary rounded-full flex items-center justify-center">
              <span class="text-lg">💰</span>
            </div>
          </div>
          <div class="mt-2 text-xs">
            <span 
              :class="{
                'text-green-600': summary.revenueChange > 0,
                'text-red-600': summary.revenueChange < 0,
                'text-muted-foreground': summary.revenueChange === 0
              }"
            >
              {{ formatChange(summary.revenueChange) }}
            </span>
            <span class="text-muted-foreground ml-1">so với kỳ trước</span>
          </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-muted-foreground">Đơn hàng</p>
              <p class="text-2xl font-bold">{{ summary.orders }}</p>
            </div>
            <div class="h-10 w-10 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center">
              <span class="text-lg">🧾</span>
            </div>
          </div>
          <div class="mt-2 text-xs">
            <span 
              :class="{
                'text-green-600': summary.ordersChange > 0,
                'text-red-600': summary.ordersChange < 0,
                'text-muted-foreground': summary.ordersChange === 0
              }"
            >
              {{ formatChange(summary.ordersChange) }}
            </span>
            <span class="text-muted-foreground ml-1">so với kỳ trước</span>
          </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-muted-foreground">Hội viên mới</p>
              <p class="text-2xl font-bold">{{ summary.newMembers }}</p>
            </div>
            <div class="h-10 w-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
              <span class="text-lg">👥</span>
            </div>
          </div>
          <div class="mt-2 text-xs">
            <span 
              :class="{
                'text-green-600': summary.newMembersChange > 0,
                'text-red-600': summary.newMembersChange < 0,
                'text-muted-foreground': summary.newMembersChange === 0
              }"
            >
              {{ formatChange(summary.newMembersChange) }}
            </span>
            <span class="text-muted-foreground ml-1">so với kỳ trước</span>
          </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-muted-foreground">Buổi tập</p>
              <p class="text-2xl font-bold">{{ summary.sessions }}</p>
            </div>
            <div class="h-10 w-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
              <span class="text-lg">🏋️</span>
            </div>
          </div>
          <div class="mt-2 text-xs">
            <span 
              :class="{
                'text-green-600': summary.sessionsChange > 0,
                'text-red-600': summary.sessionsChange < 0,
                'text-muted-foreground': summary.sessionsChange === 0
              }"
            >
              {{ formatChange(summary.sessionsChange) }}
            </span>
            <span class="text-muted-foreground ml-1">so với kỳ trước</span>
          </div>
        </div>
      </div>
      
      <!-- Biểu đồ doanh thu -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white p-4 rounded-lg shadow">
          <h2 class="text-lg font-medium mb-4">Doanh thu theo thời gian</h2>
          <div class="h-[300px]">
            <v-chart class="w-full h-full" :option="revenueChartOption" />
          </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow">
          <h2 class="text-lg font-medium mb-4">Phân bổ doanh thu</h2>
          <div class="h-[300px]">
            <v-chart class="w-full h-full" :option="pieChartOption" />
          </div>
        </div>
      </div>
      
      <!-- Doanh thu theo danh mục và sản phẩm bán chạy -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-4 rounded-lg shadow">
          <h2 class="text-lg font-medium mb-4">Doanh thu theo danh mục</h2>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-muted/30">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Danh mục</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Doanh thu</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tỉ lệ</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(category, index) in revenueByCategory" :key="index" class="hover:bg-muted/10">
                  <td class="px-4 py-3 whitespace-nowrap">
                    {{ category.name }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    {{ formatCurrency(category.revenue) }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-24 bg-muted/30 rounded-full h-2 mr-2">
                        <div 
                          :style="`width: ${category.percentage}%`"
                          class="bg-primary h-2 rounded-full"
                        ></div>
                      </div>
                      <span class="text-xs">{{ category.percentage }}%</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow">
          <h2 class="text-lg font-medium mb-4">Sản phẩm bán chạy</h2>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-muted/30">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Sản phẩm</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Số lượng</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Doanh thu</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(product, index) in topProducts" :key="index" class="hover:bg-muted/10">
                  <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="h-8 w-8 rounded bg-muted/20 flex items-center justify-center mr-3">
                        <span>{{ product.emoji }}</span>
                      </div>
                      <div>{{ product.name }}</div>
                    </div>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    {{ product.quantity }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    {{ formatCurrency(product.revenue) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <!-- Thống kê hội viên và huấn luyện viên -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-4 rounded-lg shadow">
          <h2 class="text-lg font-medium mb-4">Thống kê hội viên</h2>
          <div class="flex flex-col">
            <div class="flex space-x-4 mb-4">
              <div class="flex-1 bg-muted/10 p-3 rounded-lg">
                <div class="text-sm text-muted-foreground mb-1">Hội viên đang hoạt động</div>
                <div class="text-xl font-bold">{{ memberStats.active }}</div>
              </div>
              <div class="flex-1 bg-muted/10 p-3 rounded-lg">
                <div class="text-sm text-muted-foreground mb-1">Sắp hết hạn (30 ngày)</div>
                <div class="text-xl font-bold">{{ memberStats.expiring }}</div>
              </div>
              <div class="flex-1 bg-muted/10 p-3 rounded-lg">
                <div class="text-sm text-muted-foreground mb-1">Hết hạn</div>
                <div class="text-xl font-bold">{{ memberStats.expired }}</div>
              </div>
            </div>
            
            <!-- Biểu đồ gói tập -->
            <h3 class="text-base font-medium mb-3">Phân bổ theo gói tập</h3>
            <div v-for="(item, index) in membershipDistribution" :key="index" class="mb-3">
              <div class="flex justify-between items-center mb-1">
                <span class="text-sm">{{ item.name }}</span>
                <span class="text-sm font-medium">{{ item.count }} hội viên</span>
              </div>
              <div class="w-full bg-muted/30 rounded-full h-2">
                <div 
                  :style="`width: ${item.percentage}%`"
                  class="bg-blue-500 h-2 rounded-full"
                ></div>
              </div>
              <div class="text-xs text-muted-foreground mt-1">{{ item.percentage }}%</div>
            </div>
          </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow">
          <h2 class="text-lg font-medium mb-4">Thống kê huấn luyện viên</h2>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-muted/30">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Huấn luyện viên</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Buổi tập</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Đánh giá</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(trainer, index) in trainerStats" :key="index" class="hover:bg-muted/10">
                  <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="h-8 w-8 rounded-full bg-muted/20 flex items-center justify-center mr-3">
                        {{ trainer.name.charAt(0) }}
                      </div>
                      <div>{{ trainer.name }}</div>
                    </div>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    {{ trainer.sessions }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center">
                      <span class="text-yellow-500 mr-1">★</span>
                      <span>{{ trainer.rating }}/5</span>
                      <span class="text-xs text-muted-foreground ml-2">({{ trainer.reviews }})</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import dayjs from 'dayjs';
import MainLayout from '../../layouts/MainLayout.vue';
import { 
  ExportOutlined, 
  CalendarOutlined, 
  UserOutlined, 
  ShoppingOutlined, 
  DollarOutlined 
} from '@ant-design/icons-vue';

// Khoảng thời gian được chọn
const selectedDateRange = ref('month');
const dateRange = ref({
  start: dayjs(),
  end: dayjs()
});

// Áp dụng khoảng thời gian tùy chỉnh
const applyDateRange = () => {
  // Gọi API để lấy dữ liệu theo khoảng thời gian
  loadReportData();
};

// Mock data - Thống kê tổng quan
const summary = ref({
  revenue: 25800000,
  revenueChange: 12.5,
  orders: 185,
  ordersChange: 8.3,
  newMembers: 24,
  newMembersChange: -5.1,
  sessions: 312,
  sessionsChange: 15.2
});

// Mock data - Biểu đồ doanh thu
const revenueChart = ref([
  { label: 'T2', value: 1800000 },
  { label: 'T3', value: 2300000 },
  { label: 'T4', value: 1950000 },
  { label: 'T5', value: 2800000 },
  { label: 'T6', value: 3600000 },
  { label: 'T7', value: 4800000 },
  { label: 'CN', value: 3200000 }
]);

// Tạo dữ liệu biểu đồ doanh thu
const revenueChartOption = computed(() => {
  return {
    tooltip: {
      trigger: 'axis',
      formatter: '{b}: {c} đ'
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      containLabel: true
    },
    xAxis: {
      type: 'category',
      data: revenueChart.value.map(item => item.label)
    },
    yAxis: {
      type: 'value',
      axisLabel: {
        formatter: (value) => {
          if (value >= 1000000) {
            return (value / 1000000).toFixed(0) + 'tr';
          }
          return value;
        }
      }
    },
    series: [
      {
        name: 'Doanh thu',
        type: 'bar',
        data: revenueChart.value.map(item => item.value),
        itemStyle: {
          color: '#1677ff'
        }
      }
    ]
  };
});

// Mock data - Phân bổ doanh thu
const revenueSources = ref([
  { label: 'Gói tập', value: 15600000, percentage: 60, color: '#1677ff' },
  { label: 'Sản phẩm', value: 6450000, percentage: 25, color: '#52c41a' },
  { label: 'PT', value: 3870000, percentage: 15, color: '#1890ff' }
]);

// Tạo dữ liệu biểu đồ tròn
const pieChartOption = computed(() => {
  return {
    tooltip: {
      trigger: 'item',
      formatter: '{b}: {c} đ ({d}%)'
    },
    legend: {
      orient: 'vertical',
      right: 10,
      top: 'center',
      data: revenueSources.value.map(item => item.label)
    },
    series: [
      {
        name: 'Doanh thu',
        type: 'pie',
        radius: ['50%', '70%'],
        center: ['40%', '50%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 10,
          borderColor: '#fff',
          borderWidth: 2
        },
        label: {
          show: false,
          position: 'center'
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 16,
            fontWeight: 'bold'
          }
        },
        labelLine: {
          show: false
        },
        data: revenueSources.value.map(item => ({
          value: item.value,
          name: item.label,
          itemStyle: {
            color: item.color
          }
        }))
      }
    ]
  };
});

// Mock data - Doanh thu theo danh mục
const revenueByCategory = ref([
  { name: 'Gói tập', revenue: 15600000, percentage: 60 },
  { name: 'Thực phẩm bổ sung', revenue: 4320000, percentage: 16.7 },
  { name: 'Đồ uống', revenue: 1180000, percentage: 4.6 },
  { name: 'Phụ kiện tập luyện', revenue: 950000, percentage: 3.7 },
  { name: 'PT', revenue: 3870000, percentage: 15 }
]);

// Mock data - Sản phẩm bán chạy
const topProducts = ref([
  { name: 'Gói Platinum 12 tháng', quantity: 4, revenue: 10000000, emoji: '📆' },
  { name: 'Whey Protein 1kg', quantity: 9, revenue: 8550000, emoji: '💪' },
  { name: 'PT 1 buổi', quantity: 12, revenue: 3600000, emoji: '🏋️' },
  { name: 'BCAA 200g', quantity: 5, revenue: 2750000, emoji: '🔋' },
  { name: 'Gói Cơ Bản', quantity: 8, revenue: 2400000, emoji: '📆' }
]);

// Mock data - Thống kê hội viên
const memberStats = ref({
  active: 245,
  expiring: 28,
  expired: 36
});

// Mock data - Phân bổ gói tập
const membershipDistribution = ref([
  { name: 'Gói Cơ Bản', count: 85, percentage: 34.7 },
  { name: 'Gói Standard 3 tháng', count: 62, percentage: 25.3 },
  { name: 'Gói VIP 6 tháng', count: 54, percentage: 22 },
  { name: 'Gói Platinum 12 tháng', count: 32, percentage: 13.1 },
  { name: 'Gói Cặp Đôi', count: 12, percentage: 4.9 }
]);

// Mock data - Thống kê huấn luyện viên
const trainerStats = ref([
  { name: 'Nguyễn Văn A', sessions: 78, rating: 4.8, reviews: 56 },
  { name: 'Trần Văn B', sessions: 64, rating: 4.6, reviews: 42 },
  { name: 'Lê Thị C', sessions: 82, rating: 4.9, reviews: 68 },
  { name: 'Phạm Văn D', sessions: 46, rating: 4.5, reviews: 32 },
  { name: 'Hoàng Thị E', sessions: 42, rating: 4.7, reviews: 28 }
]);

// Định dạng tiền tệ
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0
  }).format(amount);
};

// Định dạng % thay đổi
const formatChange = (change) => {
  return (change > 0 ? '+' : '') + change.toFixed(1) + '%';
};

// Load dữ liệu báo cáo
const loadReportData = () => {
  // Trong thực tế, đây sẽ là API call
  console.log('Loading report data for:', selectedDateRange.value);
  console.log('Date range:', dateRange.value);
  
  // Mô phỏng việc tải dữ liệu
  // Trong thực tế sẽ gọi API: api.get('/api/reports', { params: { ... } })
};

// Theo dõi sự thay đổi của khoảng thời gian
watch(selectedDateRange, (newValue) => {
  if (newValue !== 'custom') {
    // Tự động tải dữ liệu khi thay đổi khoảng thời gian
    loadReportData();
  }
});

// Tải dữ liệu khi component được mount
loadReportData();
</script> 