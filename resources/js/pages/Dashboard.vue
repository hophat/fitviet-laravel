<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Thống kê tổng quan -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mr-3">
              <span class="text-xl">👥</span>
            </div>
            <div>
              <h3 class="text-sm font-medium text-muted-foreground">Lượt check-in hôm nay</h3>
              <p class="text-2xl font-bold">{{ stats.checkinsToday }}</p>
            </div>
          </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mr-3">
              <span class="text-xl">💰</span>
            </div>
            <div>
              <h3 class="text-sm font-medium text-muted-foreground">Doanh thu hôm nay</h3>
              <p class="text-2xl font-bold">{{ formatCurrency(stats.revenueToday) }}</p>
            </div>
          </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mr-3">
              <span class="text-xl">📉</span>
            </div>
            <div>
              <h3 class="text-sm font-medium text-muted-foreground">Chi phí hôm nay</h3>
              <p class="text-2xl font-bold">{{ formatCurrency(stats.expensesToday) }}</p>
            </div>
          </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-3">
              <span class="text-xl">📈</span>
            </div>
            <div>
              <h3 class="text-sm font-medium text-muted-foreground">Lợi nhuận hôm nay</h3>
              <p class="text-2xl font-bold">{{ formatCurrency(stats.profitToday) }}</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Lịch PT hôm nay -->
      <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Lịch PT hôm nay</h2>
        <div v-if="ptSchedule.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Thời gian</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Huấn luyện viên</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Hội viên</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Gói tập</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Trạng thái</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(schedule, index) in ptSchedule" :key="index">
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ schedule.time }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ schedule.trainer }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ schedule.member }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ schedule.package }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span 
                    :class="{
                      'px-2 py-1 rounded-full text-xs': true,
                      'bg-green-100 text-green-800': schedule.status === 'completed',
                      'bg-blue-100 text-blue-800': schedule.status === 'scheduled',
                      'bg-yellow-100 text-yellow-800': schedule.status === 'in-progress',
                      'bg-red-100 text-red-800': schedule.status === 'cancelled',
                    }"
                  >
                    {{ getStatusText(schedule.status) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-center py-4 text-muted-foreground">
          Không có lịch PT nào hôm nay
        </div>
      </div>
      
      <!-- Mini POS -->
      <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Bán hàng nhanh (Mini POS)</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div>
            <h3 class="text-sm font-medium mb-2">Chọn sản phẩm</h3>
            <div class="space-y-3">
              <div v-for="(product, index) in popularProducts" :key="index" class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="h-10 w-10 rounded bg-muted/50 mr-3"></div>
                  <div>
                    <p class="font-medium">{{ product.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ formatCurrency(product.price) }}</p>
                  </div>
                </div>
                <button 
                  @click="addToCart(product)"
                  class="p-1 bg-primary/10 hover:bg-primary/20 rounded-md text-sm text-primary"
                >
                  + Thêm
                </button>
              </div>
            </div>
          </div>
          
          <div>
            <h3 class="text-sm font-medium mb-2">Giỏ hàng</h3>
            <div class="space-y-3 mb-4">
              <div v-if="cart.length === 0" class="text-center py-4 text-muted-foreground border rounded-md">
                Giỏ hàng trống
              </div>
              <div v-else v-for="(item, index) in cart" :key="index" class="flex items-center justify-between">
                <div class="flex items-center">
                  <div>
                    <p class="font-medium">{{ item.name }} x {{ item.quantity }}</p>
                    <p class="text-sm text-muted-foreground">{{ formatCurrency(item.price * item.quantity) }}</p>
                  </div>
                </div>
                <button 
                  @click="removeFromCart(index)"
                  class="p-1 bg-destructive/10 hover:bg-destructive/20 rounded-md text-sm text-destructive"
                >
                  Xóa
                </button>
              </div>
            </div>
            
            <div v-if="cart.length > 0" class="border-t pt-3">
              <div class="flex justify-between mb-2">
                <span>Tổng cộng:</span>
                <span class="font-bold">{{ formatCurrency(cartTotal) }}</span>
              </div>
              <button class="w-full bg-primary text-primary-foreground p-2 rounded-md hover:bg-primary/90 transition-colors">
                Thanh toán
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import MainLayout from '../layouts/MainLayout.vue';

// Mock data - sẽ được thay thế bằng API calls
const stats = ref({
  checkinsToday: 42,
  revenueToday: 5750000,
  expensesToday: 1280000,
  profitToday: 4470000
});

const ptSchedule = ref([
  { time: '07:00 - 08:00', trainer: 'Nguyễn Văn A', member: 'Trần Thị B', package: 'Gói PT cá nhân', status: 'completed' },
  { time: '08:00 - 09:00', trainer: 'Phạm Văn C', member: 'Lê Thị D', package: 'Gói PT đôi', status: 'completed' },
  { time: '10:00 - 11:00', trainer: 'Nguyễn Văn A', member: 'Vũ Văn E', package: 'Gói PT cá nhân', status: 'in-progress' },
  { time: '15:00 - 16:00', trainer: 'Trần Văn F', member: 'Nguyễn Thị G', package: 'Gói Yoga 1-1', status: 'scheduled' },
  { time: '16:00 - 17:00', trainer: 'Phạm Văn C', member: 'Hoàng Văn H', package: 'Gói PT cá nhân', status: 'scheduled' },
  { time: '18:00 - 19:00', trainer: 'Trần Văn F', member: 'Đỗ Thị I', package: 'Gói PT cá nhân', status: 'scheduled' },
]);

const popularProducts = ref([
  { id: 1, name: 'Nước suối', price: 10000 },
  { id: 2, name: 'Nước ion', price: 15000 },
  { id: 3, name: 'Sinh tố protein', price: 45000 },
  { id: 4, name: 'Găng tay tập', price: 120000 },
  { id: 5, name: 'Khăn thể thao', price: 35000 },
]);

const cart = ref([]);

// Tính tổng giỏ hàng
const cartTotal = computed(() => {
  return cart.value.reduce((total, item) => total + (item.price * item.quantity), 0);
});

// Format tiền VND
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0
  }).format(amount);
};

// Thêm sản phẩm vào giỏ hàng
const addToCart = (product) => {
  const existingItem = cart.value.find(item => item.id === product.id);
  
  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cart.value.push({
      id: product.id,
      name: product.name,
      price: product.price,
      quantity: 1
    });
  }
};

// Xóa sản phẩm khỏi giỏ hàng
const removeFromCart = (index) => {
  cart.value.splice(index, 1);
};

// Chuyển đổi trạng thái
const getStatusText = (status) => {
  const statusMap = {
    'completed': 'Hoàn thành',
    'in-progress': 'Đang diễn ra',
    'scheduled': 'Lên lịch',
    'cancelled': 'Đã hủy'
  };
  
  return statusMap[status] || status;
};
</script> 