<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Header và các nút điều hướng -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0 mb-6">
        <div>
          <div class="flex items-center space-x-2">
            <router-link to="/members" class="text-primary hover:underline">
              <span>← Danh sách hội viên</span>
            </router-link>
          </div>
          <h1 class="text-2xl font-bold mt-2">{{ member.name }}</h1>
        </div>
        
        <div class="flex space-x-2">
          <a-button 
            class="flex items-center" 
            type="primary"
            ghost
            @click="showCheckInModal = true"
          >
            <template #icon><CheckCircleOutlined /></template>
            <span>Check-in</span>
          </a-button>
          <a-button class="flex items-center" type="primary">
            <template #icon><EditOutlined /></template>
            <span>Chỉnh sửa</span>
          </a-button>
          <a-dropdown>
            <a-button>
              <template #icon><MoreOutlined /></template>
            </a-button>
            <template #overlay>
              <a-menu>
                <a-menu-item key="1">
                  <LockOutlined />
                  <span>Khoá tài khoản</span>
                </a-menu-item>
                <a-menu-item key="2">
                  <PrinterOutlined />
                  <span>In thẻ hội viên</span>
                </a-menu-item>
                <a-menu-item key="3">
                  <MailOutlined />
                  <span>Gửi email</span>
                </a-menu-item>
              </a-menu>
            </template>
          </a-dropdown>
        </div>
      </div>
      
      <!-- Thông tin chính -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Hồ sơ hội viên -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="p-6 flex flex-col items-center">
            <div class="h-24 w-24 rounded-full bg-muted/50 flex items-center justify-center text-2xl font-semibold mb-4">
              {{ member.name.charAt(0) }}
            </div>
            <h2 class="text-xl font-semibold">{{ member.name }}</h2>
            <p class="text-muted-foreground">Mã hội viên: {{ member.memberCode }}</p>
            
            <div 
              :class="{
                'px-2 py-1 rounded-full text-xs mt-2': true,
                'bg-green-100 text-green-800': member.status === 'active',
                'bg-red-100 text-red-800': member.status === 'expired',
                'bg-yellow-100 text-yellow-800': member.status === 'pending'
              }"
            >
              {{ getMemberStatusText(member.status) }}
            </div>
          </div>
          
          <div class="border-t">
            <div class="p-4">
              <h3 class="text-sm font-medium text-muted-foreground mb-3">THÔNG TIN CÁ NHÂN</h3>
              
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Điện thoại</span>
                  <span class="font-medium">{{ member.phone }}</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Email</span>
                  <span class="font-medium">{{ member.email }}</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Ngày sinh</span>
                  <span class="font-medium">{{ member.dateOfBirth }}</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Giới tính</span>
                  <span class="font-medium">{{ member.gender }}</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Địa chỉ</span>
                  <span class="font-medium">{{ member.address }}</span>
                </div>
              </div>
            </div>
            
            <div class="border-t p-4">
              <h3 class="text-sm font-medium text-muted-foreground mb-3">CHỈ SỐ CƠ THỂ</h3>
              
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Chiều cao</span>
                  <span class="font-medium">{{ member.height }} cm</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Cân nặng</span>
                  <span class="font-medium">{{ member.weight }} kg</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-muted-foreground">BMI</span>
                  <span 
                    :class="{
                      'font-medium': true,
                      'text-yellow-600': member.bmi < 18.5 || (member.bmi >= 25 && member.bmi < 30),
                      'text-red-600': member.bmi >= 30,
                      'text-green-600': member.bmi >= 18.5 && member.bmi < 25
                    }"
                  >
                    {{ member.bmi }} ({{ getBmiCategory(member.bmi) }})
                  </span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Tỷ lệ mỡ</span>
                  <span class="font-medium">{{ member.bodyFat }}%</span>
                </div>
              </div>
            </div>

            <div class="border-t p-4">
              <h3 class="text-sm font-medium text-muted-foreground mb-3">GHI CHÚ</h3>
              <p class="text-sm mb-2">{{ member.notes || 'Chưa có ghi chú' }}</p>
              <button class="text-xs text-primary hover:underline">+ Thêm ghi chú</button>
            </div>
          </div>
        </div>
        
        <!-- Gói tập và lịch sử thanh toán -->
        <div class="space-y-6">
          <!-- Gói tập hiện tại -->
          <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b">
              <h3 class="font-medium">Gói tập hiện tại</h3>
            </div>
            
            <div class="p-4">
              <div class="mb-4">
                <div class="text-lg font-medium text-primary">{{ member.currentPackage.name }}</div>
                <div class="text-sm text-muted-foreground">{{ member.currentPackage.description }}</div>
              </div>
              
              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <div class="text-sm text-muted-foreground">Ngày bắt đầu</div>
                  <div class="font-medium">{{ member.currentPackage.startDate }}</div>
                </div>
                
                <div>
                  <div class="text-sm text-muted-foreground">Ngày kết thúc</div>
                  <div class="font-medium">{{ member.currentPackage.endDate }}</div>
                </div>
                
                <div>
                  <div class="text-sm text-muted-foreground">Ngày còn lại</div>
                  <div 
                    :class="{
                      'font-medium': true,
                      'text-red-600': member.currentPackage.daysLeft <= 7,
                      'text-yellow-600': member.currentPackage.daysLeft > 7 && member.currentPackage.daysLeft <= 30,
                      'text-green-600': member.currentPackage.daysLeft > 30
                    }"
                  >
                    {{ member.currentPackage.daysLeft }} ngày
                  </div>
                </div>
                
                <div>
                  <div class="text-sm text-muted-foreground">Giá trị</div>
                  <div class="font-medium">{{ formatCurrency(member.currentPackage.price) }}</div>
                </div>
              </div>
              
              <div class="flex space-x-2">
                <button class="w-full px-3 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
                  Gia hạn gói tập
                </button>
                <button class="w-full px-3 py-2 bg-muted hover:bg-muted/80 rounded-md transition-colors">
                  Đổi gói tập
                </button>
              </div>
            </div>
          </div>
          
          <!-- Lịch sử thanh toán -->
          <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b flex justify-between items-center">
              <h3 class="font-medium">Lịch sử thanh toán</h3>
              <button class="text-xs text-primary hover:underline">Xem tất cả</button>
            </div>
            
            <div class="divide-y">
              <div v-for="(payment, index) in member.paymentHistory.slice(0, 3)" :key="index" class="p-4">
                <div class="flex justify-between items-center">
                  <div>
                    <div class="font-medium">{{ payment.description }}</div>
                    <div class="text-xs text-muted-foreground">{{ payment.date }}</div>
                  </div>
                  <div class="font-medium">{{ formatCurrency(payment.amount) }}</div>
                </div>
              </div>
              
              <div v-if="member.paymentHistory.length > 3" class="p-4 text-center">
                <button class="text-primary hover:underline text-sm">Xem thêm giao dịch</button>
              </div>
              
              <div v-if="member.paymentHistory.length === 0" class="p-4 text-center text-muted-foreground">
                Chưa có giao dịch nào
              </div>
            </div>
          </div>
        </div>
        
        <!-- Lịch sử hoạt động và phiên tập -->
        <div class="space-y-6">
          <!-- Thống kê check-in -->
          <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-medium mb-3">Thống kê check-in</h3>
            
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-muted/10 p-3 rounded-lg">
                <div class="text-sm text-muted-foreground">Tổng check-in</div>
                <div class="text-xl font-bold">{{ member.stats.totalCheckins }}</div>
              </div>
              
              <div class="bg-muted/10 p-3 rounded-lg">
                <div class="text-sm text-muted-foreground">Tháng này</div>
                <div class="text-xl font-bold">{{ member.stats.monthlyCheckins }}</div>
              </div>
              
              <div class="bg-muted/10 p-3 rounded-lg">
                <div class="text-sm text-muted-foreground">Lần check-in gần nhất</div>
                <div class="font-medium">{{ member.stats.lastCheckin }}</div>
              </div>
              
              <div class="bg-muted/10 p-3 rounded-lg">
                <div class="text-sm text-muted-foreground">Tần suất trung bình</div>
                <div class="font-medium">{{ member.stats.frequency }} lần/tuần</div>
              </div>
            </div>
            
            <!-- Biểu đồ lịch sử check-in (với ECharts) -->
            <div class="mt-4">
              <div class="text-sm font-medium mb-2">Lịch sử check-in 7 ngày qua</div>
              <div class="h-24">
                <v-chart class="w-full h-full" :option="checkinChartOption" />
              </div>
            </div>
          </div>
          
          <!-- Phiên PT gần đây -->
          <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b flex justify-between items-center">
              <h3 class="font-medium">Phiên PT gần đây</h3>
              <button class="text-xs text-primary hover:underline">Lịch sử đầy đủ</button>
            </div>
            
            <div class="divide-y">
              <div v-for="(session, index) in member.ptSessions.slice(0, 3)" :key="index" class="p-4">
                <div class="flex justify-between items-start">
                  <div>
                    <div class="font-medium">{{ session.trainerName }}</div>
                    <div class="text-xs text-muted-foreground">{{ session.date }}, {{ session.time }}</div>
                    <div v-if="session.notes" class="text-xs text-muted-foreground mt-1">
                      {{ session.notes }}
                    </div>
                  </div>
                  <div 
                    :class="{
                      'px-2 py-1 rounded-full text-xs': true,
                      'bg-green-100 text-green-800': session.status === 'completed',
                      'bg-red-100 text-red-800': session.status === 'cancelled',
                      'bg-blue-100 text-blue-800': session.status === 'scheduled'
                    }"
                  >
                    {{ getSessionStatusText(session.status) }}
                  </div>
                </div>
              </div>
              
              <div v-if="member.ptSessions.length === 0" class="p-4 text-center text-muted-foreground">
                Chưa có phiên PT nào
              </div>
              
              <div v-else-if="member.ptSessions.length > 3" class="p-4 text-center">
                <button class="text-primary hover:underline text-sm">Xem thêm phiên PT</button>
              </div>
            </div>
          </div>
          
          <!-- Buổi tập sắp tới -->
          <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
              <h3 class="font-medium">Buổi tập sắp tới</h3>
            </div>
            
            <div v-if="member.upcomingSessions.length === 0" class="p-4 text-center text-muted-foreground">
              Không có buổi tập nào sắp tới
            </div>
            
            <div v-else class="divide-y">
              <div v-for="(session, index) in member.upcomingSessions" :key="index" class="p-4">
                <div class="flex items-start justify-between">
                  <div class="flex items-start space-x-3">
                    <div class="bg-muted/30 rounded-lg p-2 text-center min-w-10">
                      <div class="text-sm font-medium">{{ session.dayMonth }}</div>
                      <div class="text-xs">{{ session.dayName }}</div>
                    </div>
                    
                    <div>
                      <div class="font-medium">{{ session.title }}</div>
                      <div class="text-xs text-muted-foreground">{{ session.time }} • {{ session.location }}</div>
                      <div v-if="session.trainerName" class="text-xs text-muted-foreground">
                        HLV: {{ session.trainerName }}
                      </div>
                    </div>
                  </div>
                  
                  <button class="text-xs text-red-600 hover:underline">Huỷ</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal check-in -->
    <CheckInModal
      :is-open="showCheckInModal"
      :member="member"
      @close="showCheckInModal = false"
      @confirm="handleCheckin"
      @update:isOpen="val => showCheckInModal = val"
    />
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import MainLayout from '../../layouts/MainLayout.vue';
import { 
  CheckCircleOutlined, 
  EditOutlined, 
  MoreOutlined, 
  LockOutlined,
  PrinterOutlined,
  MailOutlined
} from '@ant-design/icons-vue';
import CheckInModal from '../../components/members/CheckInModal.vue';

const route = useRoute();
const checkinNote = ref('');
const showCheckInModal = ref(false);

// Giả lập dữ liệu chi tiết hội viên
const member = ref({
  id: 1,
  memberCode: 'MEM00123',
  name: 'Nguyễn Văn A',
  phone: '0901234567',
  email: 'nguyenvana@gmail.com',
  dateOfBirth: '15/05/1992',
  gender: 'Nam',
  address: '123 Đường Lê Lợi, Quận 1, TP.HCM',
  height: 175,
  weight: 70,
  bmi: 22.9,
  bodyFat: 18,
  notes: 'Hội viên tập trung vào tăng cơ, giảm mỡ. Cần tư vấn chế độ dinh dưỡng.',
  status: 'active',
  joinDate: '10/01/2025',
  
  currentPackage: {
    name: 'Gói Premium',
    description: 'Gói tập hạng cao với đầy đủ quyền lợi',
    startDate: '10/01/2025',
    endDate: '10/01/2026',
    daysLeft: 180,
    price: 12000000
  },
  
  paymentHistory: [
    {
      id: 1,
      date: '10/01/2025',
      amount: 12000000,
      description: 'Thanh toán Gói Premium',
      method: 'Thẻ tín dụng',
      status: 'completed'
    },
    {
      id: 2,
      date: '15/02/2025',
      amount: 500000,
      description: 'Mua nước uống và phụ kiện',
      method: 'Tiền mặt',
      status: 'completed'
    },
    {
      id: 3,
      date: '05/03/2025',
      amount: 2500000,
      description: 'Gói 10 buổi PT',
      method: 'Chuyển khoản',
      status: 'completed'
    }
  ],
  
  stats: {
    totalCheckins: 56,
    monthlyCheckins: 12,
    lastCheckin: 'Hôm nay, 07:45',
    frequency: 3.5,
    weeklyCheckins: [
      { label: 'T2', count: 1 },
      { label: 'T3', count: 1 },
      { label: 'T4', count: 0 },
      { label: 'T5', count: 1 },
      { label: 'T6', count: 0 },
      { label: 'T7', count: 1 },
      { label: 'CN', count: 0 }
    ]
  },
  
  ptSessions: [
    {
      id: 1,
      trainerName: 'Lê Văn Huấn',
      date: '15/05/2025',
      time: '17:30 - 18:30',
      status: 'completed',
      notes: 'Tập trung vào bài tập ngực và vai'
    },
    {
      id: 2,
      trainerName: 'Lê Văn Huấn',
      date: '17/05/2025',
      time: '17:30 - 18:30',
      status: 'completed',
      notes: 'Tập trung vào bài tập chân'
    },
    {
      id: 3,
      trainerName: 'Trần Thị Minh',
      date: '20/05/2025',
      time: '17:30 - 18:30',
      status: 'cancelled',
      notes: 'Hội viên huỷ do bận việc cá nhân'
    }
  ],
  
  upcomingSessions: [
    {
      id: 1,
      title: 'Buổi tập PT',
      dayMonth: '22/05',
      dayName: 'Thứ 2',
      time: '17:30 - 18:30',
      location: 'Phòng PT 2',
      trainerName: 'Lê Văn Huấn'
    },
    {
      id: 2,
      title: 'Lớp Yoga cơ bản',
      dayMonth: '24/05',
      dayName: 'Thứ 4',
      time: '18:00 - 19:00',
      location: 'Phòng Yoga',
      trainerName: null
    }
  ]
});

// Xử lý check-in
const handleCheckin = (data) => {
  // Gọi API để check-in
  console.log('Check-in data:', data);
  
  // Cập nhật dữ liệu thống kê
  member.value.stats.totalCheckins++;
  member.value.stats.monthlyCheckins++;
  member.value.stats.lastCheckin = 'Vừa xong';
  member.value.stats.weeklyCheckins[new Date().getDay() === 0 ? 6 : new Date().getDay() - 1].count++;
};

// Format tiền tệ
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0
  }).format(amount);
};

// Chuyển đổi trạng thái hội viên
const getMemberStatusText = (status) => {
  const statusMap = {
    'active': 'Đang hoạt động',
    'expired': 'Hết hạn',
    'pending': 'Chờ kích hoạt'
  };
  
  return statusMap[status] || status;
};

// Chuyển đổi trạng thái phiên PT
const getSessionStatusText = (status) => {
  const statusMap = {
    'completed': 'Đã hoàn thành',
    'cancelled': 'Đã huỷ',
    'scheduled': 'Đã lên lịch'
  };
  
  return statusMap[status] || status;
};

// Chuyển đổi chỉ số BMI
const getBmiCategory = (bmi) => {
  if (bmi < 18.5) return 'Thiếu cân';
  if (bmi >= 18.5 && bmi < 25) return 'Bình thường';
  if (bmi >= 25 && bmi < 30) return 'Thừa cân';
  return 'Béo phì';
};

// Tạo biểu đồ check-in
const checkinChartOption = computed(() => {
  return {
    tooltip: {
      trigger: 'axis',
      formatter: '{b}: {c} lần check-in'
    },
    grid: {
      top: '5%',
      right: '1%',
      bottom: '8%',
      left: '1%',
      containLabel: true
    },
    xAxis: {
      type: 'category',
      data: member.value.stats.weeklyCheckins.map(day => day.label)
    },
    yAxis: {
      type: 'value',
      minInterval: 1,
      max: 3,
      axisLine: { show: false },
      axisTick: { show: false },
      axisLabel: { show: false },
      splitLine: { show: false }
    },
    series: [
      {
        name: 'Check-in',
        type: 'bar',
        data: member.value.stats.weeklyCheckins.map(day => day.count),
        itemStyle: {
          color: '#1677ff'
        }
      }
    ]
  };
});

onMounted(() => {
  // Ở đây sẽ gọi API để lấy dữ liệu chi tiết hội viên theo ID
  // const memberId = route.params.id;
  // fetchMemberDetail(memberId);
});
</script> 