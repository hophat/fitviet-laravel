<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Header và điều khiển -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0 mb-6">
        <div>
          <h1 class="text-2xl font-bold">Lịch tập và đặt lịch</h1>
          <p class="text-muted-foreground">Quản lý lịch tập của hội viên với huấn luyện viên</p>
        </div>
        
        <div class="flex space-x-2">
          <button class="bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90 transition-colors">
            + Đặt lịch mới
          </button>
          
          <select v-model="currentView" class="bg-background border px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50">
            <option value="month">Tháng</option>
            <option value="week">Tuần</option>
            <option value="day">Ngày</option>
            <option value="list">Danh sách</option>
          </select>
        </div>
      </div>
      
      <!-- Điều hướng lịch -->
      <div class="bg-white p-4 rounded-lg shadow">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-4">
            <button 
              @click="navigateCalendar('prev')"
              class="p-2 rounded-md bg-muted/30 hover:bg-muted/50"
            >
              &lt;
            </button>
            
            <button 
              @click="navigateCalendar('today')"
              class="px-4 py-2 rounded-md bg-muted/30 hover:bg-muted/50"
            >
              Hôm nay
            </button>
            
            <button 
              @click="navigateCalendar('next')"
              class="p-2 rounded-md bg-muted/30 hover:bg-muted/50"
            >
              &gt;
            </button>
            
            <h2 class="text-lg font-medium">{{ currentViewTitle }}</h2>
          </div>
          
          <div class="flex items-center space-x-2">
            <div class="flex items-center space-x-1">
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
              <span class="text-sm">Đã xác nhận</span>
            </div>
            <div class="flex items-center space-x-1">
              <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
              <span class="text-sm">Chờ xác nhận</span>
            </div>
            <div class="flex items-center space-x-1">
              <div class="w-3 h-3 rounded-full bg-red-500"></div>
              <span class="text-sm">Đã hủy</span>
            </div>
          </div>
        </div>
        
        <!-- Bộ lọc -->
        <div class="flex flex-wrap gap-3 mb-4">
          <div>
            <label class="block text-sm font-medium mb-1">Huấn luyện viên</label>
            <select v-model="filters.trainer" class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50">
              <option value="">Tất cả</option>
              <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">
                {{ trainer.name }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Trạng thái</label>
            <select v-model="filters.status" class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50">
              <option value="">Tất cả</option>
              <option value="confirmed">Đã xác nhận</option>
              <option value="pending">Chờ xác nhận</option>
              <option value="cancelled">Đã hủy</option>
            </select>
          </div>
        </div>
      </div>
      
      <!-- Hiển thị lịch -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <!-- Hiển thị lịch tháng -->
        <div v-if="currentView === 'month'" class="w-full">
          <!-- Header ngày trong tuần -->
          <div class="grid grid-cols-7 border-b">
            <div 
              v-for="day in weekdays" 
              :key="day" 
              class="p-2 text-center font-medium border-r last:border-r-0"
            >
              {{ day }}
            </div>
          </div>
          
          <!-- Hiển thị ngày -->
          <div class="grid grid-cols-7">
            <div 
              v-for="(day, index) in monthDays" 
              :key="index" 
              :class="{
                'min-h-32 border p-1 relative': true,
                'bg-muted/10': day.isWeekend,
                'bg-primary/5': day.isToday,
                'text-muted-foreground': !day.isCurrentMonth
              }"
            >
              <div class="text-right text-sm p-1">{{ day.date }}</div>
              
              <!-- Các buổi tập trong ngày -->
              <div class="space-y-1">
                <div 
                  v-for="(session, sessionIndex) in filterSessionsByDate(day.fullDate)" 
                  :key="sessionIndex"
                  :class="{
                    'p-1 rounded text-xs mb-1 truncate': true,
                    'bg-green-100 text-green-800': session.status === 'confirmed',
                    'bg-yellow-100 text-yellow-800': session.status === 'pending',
                    'bg-red-100 text-red-800': session.status === 'cancelled'
                  }"
                >
                  {{ formatTime(session.startTime) }} - {{ session.memberName }} / {{ session.trainerName }}
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Hiển thị lịch tuần -->
        <div v-else-if="currentView === 'week'" class="w-full">
          <!-- Header ngày trong tuần -->
          <div class="grid grid-cols-8 border-b">
            <div class="p-2 border-r"></div>
            <div 
              v-for="day in weekDays" 
              :key="day.fullDate" 
              :class="{
                'p-2 text-center font-medium border-r last:border-r-0': true,
                'bg-primary/5': day.isToday
              }"
            >
              <div>{{ day.name }}</div>
              <div>{{ day.date }}</div>
            </div>
          </div>
          
          <!-- Hiển thị giờ và buổi tập -->
          <div>
            <div 
              v-for="hour in hours" 
              :key="hour"
              class="grid grid-cols-8 border-b last:border-b-0"
            >
              <div class="p-2 text-center border-r text-sm">{{ hour }}:00</div>
              
              <div 
                v-for="day in weekDays" 
                :key="`${day.fullDate}-${hour}`"
                class="relative border-r last:border-r-0 min-h-16"
              >
                <!-- Sessions for this hour and day -->
                <div 
                  v-for="session in getSessionsByHourAndDay(hour, day.fullDate)" 
                  :key="session.id"
                  :class="{
                    'absolute rounded p-1 text-xs left-0 right-0 m-1': true,
                    'bg-green-100 text-green-800': session.status === 'confirmed',
                    'bg-yellow-100 text-yellow-800': session.status === 'pending',
                    'bg-red-100 text-red-800': session.status === 'cancelled'
                  }"
                  :style="`top: ${calculateSessionPosition(session, hour)}px; height: ${calculateSessionHeight(session)}px`"
                >
                  {{ formatTime(session.startTime) }} - {{ formatTime(session.endTime) }}<br>
                  {{ session.memberName }} / {{ session.trainerName }}
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Hiển thị lịch ngày -->
        <div v-else-if="currentView === 'day'" class="w-full">
          <div class="text-center p-4 font-medium">
            {{ formatDayHeader(currentDate) }}
          </div>
          
          <div class="border-t">
            <div 
              v-for="hour in hours" 
              :key="hour"
              class="grid grid-cols-12 border-b last:border-b-0"
            >
              <div class="col-span-1 p-2 text-center border-r text-sm">{{ hour }}:00</div>
              
              <div class="col-span-11 min-h-16 relative">
                <!-- Sessions for this hour and day -->
                <div 
                  v-for="session in getSessionsByHourAndDay(hour, currentDate)" 
                  :key="session.id"
                  :class="{
                    'absolute rounded p-2 text-sm left-0 right-0 m-1': true,
                    'bg-green-100 text-green-800': session.status === 'confirmed',
                    'bg-yellow-100 text-yellow-800': session.status === 'pending',
                    'bg-red-100 text-red-800': session.status === 'cancelled'
                  }"
                  :style="`top: ${calculateSessionPosition(session, hour)}px; height: ${calculateSessionHeight(session)}px`"
                >
                  <div class="font-medium">{{ formatTime(session.startTime) }} - {{ formatTime(session.endTime) }}</div>
                  <div>Hội viên: {{ session.memberName }}</div>
                  <div>Huấn luyện viên: {{ session.trainerName }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Hiển thị danh sách -->
        <div v-else class="w-full">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-muted/30">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Ngày</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Thời gian</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Hội viên</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Huấn luyện viên</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Trạng thái</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(session, index) in filteredSessions" :key="index" class="hover:bg-muted/10">
                <td class="px-6 py-4 whitespace-nowrap">
                  {{ formatDate(session.date) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  {{ formatTime(session.startTime) }} - {{ formatTime(session.endTime) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  {{ session.memberName }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  {{ session.trainerName }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    :class="{
                      'px-2 py-1 rounded-full text-xs': true,
                      'bg-green-100 text-green-800': session.status === 'confirmed',
                      'bg-yellow-100 text-yellow-800': session.status === 'pending',
                      'bg-red-100 text-red-800': session.status === 'cancelled'
                    }"
                  >
                    {{ getStatusText(session.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex space-x-2">
                    <button class="p-1 bg-blue-100 hover:bg-blue-200 rounded-md text-sm text-blue-800 transition-colors">
                      Chi tiết
                    </button>
                    <button 
                      v-if="session.status !== 'cancelled'"
                      class="p-1 bg-red-100 hover:bg-red-200 rounded-md text-sm text-red-800 transition-colors"
                    >
                      Hủy
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import MainLayout from '../../layouts/MainLayout.vue';

// Mock data - Sẽ được thay thế bằng API
const trainers = ref([
  { id: 1, name: 'Nguyễn Văn A' },
  { id: 2, name: 'Trần Văn B' },
  { id: 3, name: 'Lê Thị C' },
  { id: 4, name: 'Phạm Văn D' },
  { id: 5, name: 'Hoàng Thị E' },
]);

// Mock data - Session (lịch tập)
const sessions = ref([
  {
    id: 1,
    date: '2023-08-10',
    startTime: '09:00:00',
    endTime: '10:00:00',
    memberId: 1,
    memberName: 'Lê Thành Đạt',
    trainerId: 1,
    trainerName: 'Nguyễn Văn A',
    status: 'confirmed',
    notes: 'Tập luyện cơ bụng'
  },
  {
    id: 2,
    date: '2023-08-10',
    startTime: '10:30:00',
    endTime: '11:30:00',
    memberId: 2,
    memberName: 'Nguyễn Thị Bích',
    trainerId: 1,
    trainerName: 'Nguyễn Văn A',
    status: 'confirmed',
    notes: 'Cardio'
  },
  {
    id: 3,
    date: '2023-08-10',
    startTime: '14:00:00',
    endTime: '15:00:00',
    memberId: 3,
    memberName: 'Trần Minh Tuấn',
    trainerId: 2,
    trainerName: 'Trần Văn B',
    status: 'pending',
    notes: 'Tập luyện chân'
  },
  {
    id: 4,
    date: '2023-08-11',
    startTime: '08:00:00',
    endTime: '09:00:00',
    memberId: 4,
    memberName: 'Phạm Hải Yến',
    trainerId: 3,
    trainerName: 'Lê Thị C',
    status: 'confirmed',
    notes: 'Yoga'
  },
  {
    id: 5,
    date: '2023-08-11',
    startTime: '16:00:00',
    endTime: '17:00:00',
    memberId: 5,
    memberName: 'Hoàng Văn Nam',
    trainerId: 4,
    trainerName: 'Phạm Văn D',
    status: 'cancelled',
    notes: 'Functional training'
  },
  {
    id: 6,
    date: '2023-08-12',
    startTime: '10:00:00',
    endTime: '11:00:00',
    memberId: 1,
    memberName: 'Lê Thành Đạt',
    trainerId: 1,
    trainerName: 'Nguyễn Văn A',
    status: 'confirmed',
    notes: 'Tập luyện vai'
  },
]);

// Các biến trạng thái
const currentDate = ref(new Date());
const currentView = ref('month');
const filters = ref({
  trainer: '',
  status: ''
});

// Định nghĩa các ngày trong tuần
const weekdays = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];

// Tạo dữ liệu cho lịch theo tháng
const monthDays = computed(() => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();
  const today = new Date();
  
  // First day of month
  const firstDay = new Date(year, month, 1);
  // Last day of month
  const lastDay = new Date(year, month + 1, 0);
  
  // Get starting day of week (0-6, 0 is Sunday)
  const startDay = firstDay.getDay();
  
  // Previous month days to show
  const prevMonthDays = startDay;
  
  // Current month days
  const daysInMonth = lastDay.getDate();
  
  // Calculate total number of days to show (may include next month)
  // We want to show 42 days (6 weeks) for consistency
  const totalDays = Math.ceil((prevMonthDays + daysInMonth) / 7) * 7;
  
  const days = [];
  
  // Previous month days
  const prevMonthLastDay = new Date(year, month, 0).getDate();
  for (let i = prevMonthDays - 1; i >= 0; i--) {
    const date = prevMonthLastDay - i;
    days.push({
      date,
      fullDate: `${year}-${String(month).padStart(2, '0')}-${String(date).padStart(2, '0')}`,
      isCurrentMonth: false,
      isWeekend: (prevMonthDays - 1 - i) % 7 === 0 || (prevMonthDays - i) % 7 === 0,
      isToday: false
    });
  }
  
  // Current month days
  for (let i = 1; i <= daysInMonth; i++) {
    const day = new Date(year, month, i);
    const fullDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
    days.push({
      date: i,
      fullDate,
      isCurrentMonth: true,
      isWeekend: day.getDay() === 0 || day.getDay() === 6,
      isToday: day.getDate() === today.getDate() && 
               day.getMonth() === today.getMonth() && 
               day.getFullYear() === today.getFullYear()
    });
  }
  
  // Next month days
  const nextMonthDays = totalDays - days.length;
  for (let i = 1; i <= nextMonthDays; i++) {
    const fullDate = `${year}-${String(month + 2).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
    days.push({
      date: i,
      fullDate,
      isCurrentMonth: false,
      isWeekend: (days.length % 7 === 0) || (days.length % 7 === 6),
      isToday: false
    });
  }
  
  return days;
});

// Dữ liệu cho view theo tuần
const weekDays = computed(() => {
  const date = new Date(currentDate.value);
  const day = date.getDay();
  
  // Adjust to get the first day of the week (Sunday)
  date.setDate(date.getDate() - day);
  
  const days = [];
  const today = new Date();
  
  // Create array of 7 days
  for (let i = 0; i < 7; i++) {
    const currentDate = new Date(date);
    currentDate.setDate(date.getDate() + i);
    
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth() + 1;
    const dayOfMonth = currentDate.getDate();
    
    days.push({
      name: weekdays[i],
      date: dayOfMonth,
      fullDate: `${year}-${String(month).padStart(2, '0')}-${String(dayOfMonth).padStart(2, '0')}`,
      isToday: currentDate.getDate() === today.getDate() && 
               currentDate.getMonth() === today.getMonth() && 
               currentDate.getFullYear() === today.getFullYear()
    });
  }
  
  return days;
});

// Tiêu đề hiển thị
const currentViewTitle = computed(() => {
  const date = currentDate.value;
  const year = date.getFullYear();
  const month = date.getMonth();
  
  const monthNames = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
                      'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
  
  if (currentView.value === 'month') {
    return `${monthNames[month]} ${year}`;
  } else if (currentView.value === 'week') {
    const firstDay = weekDays.value[0];
    const lastDay = weekDays.value[6];
    return `${firstDay.date} - ${lastDay.date} ${monthNames[month]} ${year}`;
  } else if (currentView.value === 'day') {
    return formatDayHeader(currentDate.value.toISOString().split('T')[0]);
  } else {
    return 'Danh sách lịch tập';
  }
});

// Danh sách giờ cho view theo ngày và tuần
const hours = Array.from({ length: 14 }, (_, i) => i + 6); // 6:00 - 19:00

// Lọc các sessions
const filteredSessions = computed(() => {
  return sessions.value.filter(session => {
    // Lọc theo huấn luyện viên
    if (filters.value.trainer && session.trainerId != filters.value.trainer) {
      return false;
    }
    
    // Lọc theo trạng thái
    if (filters.value.status && session.status !== filters.value.status) {
      return false;
    }
    
    return true;
  });
});

// Lọc sessions theo ngày
const filterSessionsByDate = (date) => {
  return filteredSessions.value.filter(session => session.date === date);
};

// Lấy sessions theo giờ và ngày
const getSessionsByHourAndDay = (hour, date) => {
  return filteredSessions.value.filter(session => {
    const startHour = parseInt(session.startTime.split(':')[0]);
    const endHour = parseInt(session.endTime.split(':')[0]);
    
    return session.date === date && 
           ((startHour <= hour && endHour > hour) || 
            (startHour === hour));
  });
};

// Tính vị trí của session trong view ngày/tuần
const calculateSessionPosition = (session, hour) => {
  const startHour = parseInt(session.startTime.split(':')[0]);
  const startMinute = parseInt(session.startTime.split(':')[1]);
  
  if (startHour === hour) {
    return (startMinute / 60) * 60; // 60px per hour
  }
  
  return 0;
};

// Tính chiều cao của session
const calculateSessionHeight = (session) => {
  const startHour = parseInt(session.startTime.split(':')[0]);
  const startMinute = parseInt(session.startTime.split(':')[1]);
  const endHour = parseInt(session.endTime.split(':')[0]);
  const endMinute = parseInt(session.endTime.split(':')[1]);
  
  const durationInMinutes = (endHour - startHour) * 60 + (endMinute - startMinute);
  
  return (durationInMinutes / 60) * 60; // 60px per hour
};

// Điều hướng lịch
const navigateCalendar = (direction) => {
  const date = new Date(currentDate.value);
  
  if (direction === 'prev') {
    if (currentView.value === 'month') {
      date.setMonth(date.getMonth() - 1);
    } else if (currentView.value === 'week') {
      date.setDate(date.getDate() - 7);
    } else if (currentView.value === 'day') {
      date.setDate(date.getDate() - 1);
    }
  } else if (direction === 'next') {
    if (currentView.value === 'month') {
      date.setMonth(date.getMonth() + 1);
    } else if (currentView.value === 'week') {
      date.setDate(date.getDate() + 7);
    } else if (currentView.value === 'day') {
      date.setDate(date.getDate() + 1);
    }
  } else if (direction === 'today') {
    date.setTime(new Date().getTime());
  }
  
  currentDate.value = date;
};

// Format time
const formatTime = (time) => {
  if (!time) return '';
  return time.substring(0, 5);
};

// Format date
const formatDate = (date) => {
  if (!date) return '';
  const parts = date.split('-');
  return `${parts[2]}/${parts[1]}/${parts[0]}`;
};

// Format day header
const formatDayHeader = (date) => {
  if (!date) return '';
  
  const dateObj = new Date(date);
  const dayOfWeek = weekdays[dateObj.getDay()];
  const dayOfMonth = dateObj.getDate();
  const month = dateObj.getMonth() + 1;
  const year = dateObj.getFullYear();
  
  return `${dayOfWeek}, ${dayOfMonth}/${month}/${year}`;
};

// Get status text
const getStatusText = (status) => {
  const statusMap = {
    'confirmed': 'Đã xác nhận',
    'pending': 'Chờ xác nhận',
    'cancelled': 'Đã hủy'
  };
  
  return statusMap[status] || status;
};
</script> 