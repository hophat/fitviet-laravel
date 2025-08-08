<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Header và các nút điều hướng -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0 mb-6">
        <div>
          <div class="flex items-center space-x-2">
            <router-link :to="`/members/${memberId}`" class="text-primary hover:underline">
              <span>← Thông tin hội viên</span>
            </router-link>
          </div>
          <h1 class="text-2xl font-bold mt-2">Lịch tập của hội viên</h1>
        </div>
        
        <div class="flex space-x-2">
          <a-button type="primary" @click="showAddSessionModal = true">
            <template #icon><PlusOutlined /></template>
            Thêm buổi tập
          </a-button>
        </div>
      </div>
      
      <!-- Tab điều hướng -->
      <a-tabs v-model:activeKey="activeTab">
        <a-tab-pane key="calendar" tab="Lịch tập">
          <div class="p-4 bg-white rounded-lg shadow">
            <!-- Thanh công cụ lịch -->
            <div class="flex items-center justify-between mb-4">
              <div class="flex space-x-2">
                <a-button-group>
                  <a-button type="primary" :disabled="viewMode === 'month'" @click="viewMode = 'month'">Tháng</a-button>
                  <a-button type="primary" :disabled="viewMode === 'week'" @click="viewMode = 'week'">Tuần</a-button>
                  <a-button type="primary" :disabled="viewMode === 'day'" @click="viewMode = 'day'">Ngày</a-button>
                </a-button-group>
                
                <a-button class="ml-2" @click="today">Hôm nay</a-button>
              </div>
              
              <div class="flex items-center space-x-2">
                <a-button @click="prev">
                  <template #icon><LeftOutlined /></template>
                </a-button>
                
                <span class="text-lg font-medium">{{ currentDateDisplay }}</span>
                
                <a-button @click="next">
                  <template #icon><RightOutlined /></template>
                </a-button>
              </div>
            </div>
            
            <!-- Lịch tháng -->
            <div v-if="viewMode === 'month'" class="overflow-hidden rounded-lg border">
              <div class="grid grid-cols-7 bg-muted/20">
                <div v-for="day in weekDays" :key="day" class="py-2 text-center font-medium border-r last:border-r-0">
                  {{ day }}
                </div>
              </div>
              
              <div class="grid grid-cols-7 grid-rows-6 h-[700px]">
                <div 
                  v-for="(day, index) in calendarDays" 
                  :key="index" 
                  :class="{
                    'border-r border-b p-1 h-full': true,
                    'bg-muted/10': day.isOtherMonth,
                    'bg-primary/5': day.isToday
                  }"
                >
                  <div class="flex justify-between items-start">
                    <span 
                      :class="{
                        'flex items-center justify-center h-7 w-7 rounded-full': true,
                        'font-bold bg-primary text-white': day.isToday,
                        'text-muted-foreground': day.isOtherMonth
                      }"
                    >
                      {{ day.date.date() }}
                    </span>
                    
                    <a-dropdown v-if="!day.isOtherMonth">
                      <a-button type="text" size="small">
                        <MoreOutlined />
                      </a-button>
                      <template #overlay>
                        <a-menu>
                          <a-menu-item key="add" @click="openAddSessionModal(day.date)">
                            <PlusOutlined />
                            <span>Thêm buổi tập</span>
                          </a-menu-item>
                        </a-menu>
                      </template>
                    </a-dropdown>
                  </div>
                  
                  <div class="mt-1 max-h-[80%] overflow-y-auto">
                    <div 
                      v-for="session in getSessionsForDay(day.date)" 
                      :key="session.id"
                      :class="{
                        'mb-1 p-1 rounded text-xs cursor-pointer': true,
                        'bg-blue-100 text-blue-800': session.type === 'pt',
                        'bg-green-100 text-green-800': session.type === 'class',
                        'bg-purple-100 text-purple-800': session.type === 'gym'
                      }"
                      @click="viewSessionDetail(session)"
                    >
                      <div class="font-medium truncate">{{ session.title }}</div>
                      <div>{{ session.time }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Lịch tuần -->
            <div v-else-if="viewMode === 'week'" class="overflow-x-auto rounded-lg border">
              <div class="grid grid-cols-8 min-w-[800px]">
                <div class="p-2 border-r border-b"></div>
                <div 
                  v-for="(day, index) in weekDaysWithDates" 
                  :key="index" 
                  :class="{
                    'p-2 text-center border-r border-b last:border-r-0': true,
                    'bg-primary/5 font-bold': day.isToday,
                    'bg-muted/20': !day.isToday
                  }"
                >
                  <div>{{ day.name }}</div>
                  <div 
                    :class="{
                      'inline-flex items-center justify-center h-7 w-7 rounded-full mt-1': true,
                      'bg-primary text-white': day.isToday,
                    }"
                  >
                    {{ day.date.date() }}
                  </div>
                </div>
              </div>
              
              <div class="grid grid-cols-8 min-w-[800px]">
                <div 
                  v-for="hour in hoursOfDay" 
                  :key="hour" 
                  class="border-r border-b p-1 text-xs text-muted-foreground h-20"
                >
                  {{ hour }}:00
                </div>
                
                <template v-for="(day, dayIndex) in weekDaysWithDates" :key="dayIndex">
                  <div 
                    :class="{
                      'relative border-r border-b last:border-r-0 h-20': true,
                      'bg-primary/5': day.isToday
                    }"
                    v-for="hour in hoursOfDay" 
                    :key="`${dayIndex}-${hour}`"
                  >
                    <div 
                      v-for="session in getSessionsForHour(day.date, hour)" 
                      :key="session.id"
                      :class="{
                        'absolute p-1 rounded text-xs cursor-pointer overflow-hidden': true,
                        'bg-blue-100 text-blue-800': session.type === 'pt',
                        'bg-green-100 text-green-800': session.type === 'class',
                        'bg-purple-100 text-purple-800': session.type === 'gym'
                      }"
                      :style="getSessionStyle(session, hour)"
                      @click="viewSessionDetail(session)"
                    >
                      <div class="font-medium">{{ session.title }}</div>
                      <div>{{ session.time }}</div>
                    </div>
                  </div>
                </template>
              </div>
            </div>
            
            <!-- Lịch ngày -->
            <div v-else class="overflow-x-auto rounded-lg border">
              <div class="p-4 bg-muted/20 text-center font-bold">
                {{ formatDayDetail(currentDate) }}
              </div>
              
              <div class="min-w-[600px]">
                <div 
                  v-for="hour in hoursOfDay" 
                  :key="hour" 
                  class="grid grid-cols-[80px_1fr] border-b last:border-b-0"
                >
                  <div class="p-2 border-r text-muted-foreground">
                    {{ hour }}:00
                  </div>
                  
                  <div class="relative min-h-[80px]">
                    <div 
                      v-for="session in getSessionsForHour(currentDate, hour)" 
                      :key="session.id"
                      :class="{
                        'absolute p-2 rounded cursor-pointer': true,
                        'bg-blue-100 text-blue-800': session.type === 'pt',
                        'bg-green-100 text-green-800': session.type === 'class',
                        'bg-purple-100 text-purple-800': session.type === 'gym'
                      }"
                      :style="getSessionStyleDay(session, hour)"
                      @click="viewSessionDetail(session)"
                    >
                      <div class="font-medium">{{ session.title }}</div>
                      <div class="text-xs">{{ session.time }}</div>
                      <div class="text-xs">{{ session.location }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a-tab-pane>
        <a-tab-pane key="list" tab="Danh sách buổi tập">
          <div class="bg-white rounded-lg shadow">
            <a-card :bordered="false">
              <template #title>
                <div class="flex justify-between items-center">
                  <span>Buổi tập sắp tới</span>
                  <div>
                    <a-radio-group v-model:value="listFilter" button-style="solid">
                      <a-radio-button value="upcoming">Sắp tới</a-radio-button>
                      <a-radio-button value="completed">Đã hoàn thành</a-radio-button>
                      <a-radio-button value="all">Tất cả</a-radio-button>
                    </a-radio-group>
                  </div>
                </div>
              </template>
              
              <a-list
                :dataSource="filteredSessions"
                :pagination="{ pageSize: 5 }"
              >
                <template #renderItem="{ item }">
                  <a-list-item>
                    <a-card style="width: 100%" :bordered="false">
                      <template #title>
                        <div class="flex justify-between">
                          <span>{{ item.title }}</span>
                          <a-tag :color="getSessionTypeColor(item.type)">{{ getSessionTypeText(item.type) }}</a-tag>
                        </div>
                      </template>
                      
                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                          <div class="flex items-center mb-2">
                            <CalendarOutlined class="mr-2" />
                            <span>{{ formatFullDate(item.start) }}</span>
                          </div>
                          <div class="flex items-center mb-2">
                            <ClockCircleOutlined class="mr-2" />
                            <span>{{ item.time }}</span>
                          </div>
                          <div class="flex items-center">
                            <EnvironmentOutlined class="mr-2" />
                            <span>{{ item.location }}</span>
                          </div>
                        </div>
                        
                        <div>
                          <div v-if="item.trainer" class="flex items-center mb-2">
                            <UserOutlined class="mr-2" />
                            <span>HLV: {{ item.trainer }}</span>
                          </div>
                          <div v-if="item.notes" class="flex items-start">
                            <FileTextOutlined class="mr-2 mt-1" />
                            <span>{{ item.notes }}</span>
                          </div>
                        </div>
                        
                        <div class="flex justify-end">
                          <a-space>
                            <a-button v-if="isUpcoming(item)" type="primary" @click="viewSessionDetail(item)">
                              Chi tiết
                            </a-button>
                            <a-button v-if="isUpcoming(item)" danger @click="cancelSession(item)">
                              Huỷ buổi tập
                            </a-button>
                            <a-button v-if="isCompleted(item)" @click="addReview(item)">
                              Đánh giá
                            </a-button>
                          </a-space>
                        </div>
                      </div>
                    </a-card>
                  </a-list-item>
                </template>
                
                <template #empty>
                  <div class="p-8 text-center">
                    <InboxOutlined style="font-size: 48px" class="text-muted-foreground mb-4" />
                    <p>Không có buổi tập nào</p>
                  </div>
                </template>
              </a-list>
            </a-card>
          </div>
        </a-tab-pane>
      </a-tabs>
    </div>
    
    <!-- Modal thêm buổi tập -->
    <a-modal
      v-model:visible="showAddSessionModal"
      title="Thêm buổi tập mới"
      :footer="null"
      width="700px"
    >
      <a-form :model="sessionForm" layout="vertical">
        <a-form-item label="Loại buổi tập" required>
          <a-radio-group v-model:value="sessionForm.type" button-style="solid">
            <a-radio-button value="pt">Buổi PT</a-radio-button>
            <a-radio-button value="class">Lớp tập nhóm</a-radio-button>
            <a-radio-button value="gym">Tập tự do</a-radio-button>
          </a-radio-group>
        </a-form-item>
        
        <a-form-item label="Tiêu đề" required>
          <a-input v-model:value="sessionForm.title" placeholder="Nhập tiêu đề buổi tập" />
        </a-form-item>
        
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="Ngày" required>
              <a-date-picker 
                v-model:value="sessionForm.date" 
                style="width: 100%" 
                format="DD/MM/YYYY"
              />
            </a-form-item>
          </a-col>
          
          <a-col :span="12">
            <a-form-item label="Thời gian" required>
              <a-row :gutter="8">
                <a-col :span="11">
                  <a-time-picker 
                    v-model:value="sessionForm.startTime" 
                    format="HH:mm" 
                    style="width: 100%"
                  />
                </a-col>
                <a-col :span="2" class="text-center">-</a-col>
                <a-col :span="11">
                  <a-time-picker 
                    v-model:value="sessionForm.endTime" 
                    format="HH:mm" 
                    style="width: 100%"
                  />
                </a-col>
              </a-row>
            </a-form-item>
          </a-col>
        </a-row>
        
        <a-form-item label="Địa điểm" required>
          <a-select v-model:value="sessionForm.location" placeholder="Chọn địa điểm">
            <a-select-option value="Phòng gym chính">Phòng gym chính</a-select-option>
            <a-select-option value="Phòng PT 1">Phòng PT 1</a-select-option>
            <a-select-option value="Phòng PT 2">Phòng PT 2</a-select-option>
            <a-select-option value="Phòng Yoga">Phòng Yoga</a-select-option>
            <a-select-option value="Phòng Pilates">Phòng Pilates</a-select-option>
          </a-select>
        </a-form-item>
        
        <a-form-item v-if="sessionForm.type === 'pt'" label="Huấn luyện viên" required>
          <a-select v-model:value="sessionForm.trainer" placeholder="Chọn huấn luyện viên">
            <a-select-option v-for="trainer in trainers" :key="trainer.id" :value="trainer.name">
              {{ trainer.name }} - {{ trainer.specialty }}
            </a-select-option>
          </a-select>
        </a-form-item>
        
        <a-form-item v-if="sessionForm.type === 'class'" label="Tên lớp" required>
          <a-select v-model:value="sessionForm.class" placeholder="Chọn lớp tập">
            <a-select-option value="Yoga cơ bản">Yoga cơ bản</a-select-option>
            <a-select-option value="Zumba">Zumba</a-select-option>
            <a-select-option value="Spinning">Spinning</a-select-option>
            <a-select-option value="Pilates">Pilates</a-select-option>
            <a-select-option value="Cardio HIIT">Cardio HIIT</a-select-option>
          </a-select>
        </a-form-item>
        
        <a-form-item label="Ghi chú">
          <a-textarea v-model:value="sessionForm.notes" :rows="4" placeholder="Nhập ghi chú cho buổi tập" />
        </a-form-item>
        
        <div class="flex justify-end mt-4">
          <a-space>
            <a-button @click="showAddSessionModal = false">
              Huỷ
            </a-button>
            <a-button type="primary" @click="submitSessionForm">
              Thêm buổi tập
            </a-button>
          </a-space>
        </div>
      </a-form>
    </a-modal>
    
    <!-- Modal chi tiết buổi tập -->
    <a-modal
      v-model:visible="showSessionDetailModal"
      :title="selectedSession ? selectedSession.title : 'Chi tiết buổi tập'"
      :footer="null"
      width="600px"
    >
      <div v-if="selectedSession" class="space-y-4">
        <a-descriptions bordered>
          <a-descriptions-item label="Loại buổi tập" :span="3">
            <a-tag :color="getSessionTypeColor(selectedSession.type)">
              {{ getSessionTypeText(selectedSession.type) }}
            </a-tag>
          </a-descriptions-item>
          
          <a-descriptions-item label="Ngày" :span="2">
            {{ formatFullDate(selectedSession.start) }}
          </a-descriptions-item>
          
          <a-descriptions-item label="Thời gian">
            {{ selectedSession.time }}
          </a-descriptions-item>
          
          <a-descriptions-item label="Địa điểm" :span="3">
            {{ selectedSession.location }}
          </a-descriptions-item>
          
          <a-descriptions-item v-if="selectedSession.trainer" label="Huấn luyện viên" :span="3">
            {{ selectedSession.trainer }}
          </a-descriptions-item>
          
          <a-descriptions-item v-if="selectedSession.notes" label="Ghi chú" :span="3">
            {{ selectedSession.notes }}
          </a-descriptions-item>
        </a-descriptions>
        
        <div class="flex justify-end space-x-3 mt-4">
          <a-button @click="showSessionDetailModal = false">
            Đóng
          </a-button>
          <a-button v-if="isUpcoming(selectedSession)" type="primary" @click="showRescheduleModal = true">
            Đổi lịch
          </a-button>
          <a-button v-if="isUpcoming(selectedSession)" danger @click="cancelSession(selectedSession)">
            Huỷ buổi tập
          </a-button>
        </div>
      </div>
    </a-modal>
  </MainLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { PlusOutlined, LeftOutlined, RightOutlined, MoreOutlined, CalendarOutlined, ClockCircleOutlined, EnvironmentOutlined, UserOutlined, FileTextOutlined, InboxOutlined } from '@ant-design/icons-vue';
import { useMemberStore } from '@/stores/member';
import { useSessionStore } from '@/stores/session';
import { useUtils } from '@/composables/utils';
import MainLayout from '../../layouts/MainLayout.vue';

const router = useRouter();
const memberStore = useMemberStore();
const sessionStore = useSessionStore();
const utils = useUtils();

const props = defineProps({
  memberId: {
    type: String,
    required: true
  }
});

const showAddSessionModal = ref(false);
const activeTab = ref('calendar');
const viewMode = ref('month');
const currentDate = ref(new Date());

const weekDays = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
const weekDaysWithDates = computed(() => {
  const days = [];
  for (let i = 0; i < 7; i++) {
    const date = new Date(currentDate.value);
    date.setDate(currentDate.value.getDate() + i - currentDate.value.getDay() + 1);
    days.push({
      name: weekDays[date.getDay()],
      date: date
    });
  }
  return days;
});

const hoursOfDay = ['08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20'];

const calendarDays = computed(() => {
  const days = [];
  const month = currentDate.value.getMonth();
  const year = currentDate.value.getFullYear();
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month, 0).getDate();

  for (let i = 0; i < firstDay; i++) {
    days.push({
      date: new Date(year, month, 0 - i),
      isOtherMonth: true
    });
  }

  for (let i = 1; i <= daysInMonth; i++) {
    days.push({
      date: new Date(year, month, i),
      isToday: i === currentDate.value.getDate()
    });
  }

  const remainingDays = 7 - (days.length % 7);
  for (let i = 0; i < remainingDays; i++) {
    days.push({
      date: new Date(year, month, daysInMonth + i + 1),
      isOtherMonth: true
    });
  }

  return days;
});

const currentDateDisplay = computed(() => {
  return currentDate.value.toLocaleDateString('vi-VN');
});

const today = () => {
  currentDate.value = new Date();
};

const prev = () => {
  currentDate.value.setDate(currentDate.value.getDate() - 7);
};

const next = () => {
  currentDate.value.setDate(currentDate.value.getDate() + 7);
};

const getSessionsForDay = (date) => {
  return sessionStore.sessions.filter(session => {
    const sessionDate = new Date(session.date);
    return sessionDate.toDateString() === date.toDateString();
  });
};

const getSessionsForHour = (date, hour) => {
  return sessionStore.sessions.filter(session => {
    const sessionDate = new Date(session.date);
    return sessionDate.getHours() === parseInt(hour);
  });
};

const getSessionStyle = (session, hour) => {
  const date = new Date(session.date);
  const sessionTime = parseInt(hour);
  const sessionEndTime = sessionTime + 1;
  const sessionStart = date.getHours() + date.getMinutes() / 60;
  const sessionEnd = sessionEndTime + date.getMinutes() / 60;
  const sessionDuration = sessionEnd - sessionStart;

  const start = sessionStart - date.getHours();
  const end = sessionEnd - date.getHours();

  return {
    height: `${sessionDuration * 100}%`,
    top: `${start * 100}%`,
    left: `${start * 100}%`,
    width: `${sessionDuration * 100}%`
  };
};

const getSessionStyleDay = (session, hour) => {
  const date = new Date(session.date);
  const sessionTime = parseInt(hour);
  const sessionEndTime = sessionTime + 1;
  const sessionStart = date.getHours() + date.getMinutes() / 60;
  const sessionEnd = sessionEndTime + date.getMinutes() / 60;
  const sessionDuration = sessionEnd - sessionStart;

  const start = sessionStart - date.getHours();
  const end = sessionEnd - date.getHours();

  return {
    height: `${sessionDuration * 100}%`,
    top: `${start * 100}%`,
    left: `${start * 100}%`,
    width: `${sessionDuration * 100}%`
  };
};

const viewSessionDetail = (session) => {
  router.push({
    name: 'session-detail',
    params: {
      sessionId: session.id
    }
  });
};

const openAddSessionModal = (date) => {
  showAddSessionDetailModal.value = true;
  selectedDate.value = date;
};
</script>

<style scoped>
/* Add your styles here */
</style> 