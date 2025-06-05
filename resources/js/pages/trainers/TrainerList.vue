<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Header và chức năng tìm kiếm -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0 mb-6">
        <div>
          <h1 class="text-2xl font-bold">Quản lý huấn luyện viên</h1>
          <p class="text-muted-foreground">Quản lý thông tin và lịch PT của huấn luyện viên</p>
        </div>
        
        <div class="flex space-x-2">
          <a-input-search
            v-model:value="searchQuery"
            placeholder="Tìm kiếm huấn luyện viên..."
            class="md:w-64" 
            @search="onSearch"
            :allowClear="true"
          />
          
          <a-button type="primary" @click="showAddTrainerModal = true">
            <template #icon><UserAddOutlined /></template>
            Thêm huấn luyện viên
          </a-button>
        </div>
      </div>
      
      <!-- Bộ lọc -->
      <div class="bg-white p-4 rounded-lg shadow">
        <a-form layout="inline" class="flex flex-wrap gap-4">
          <a-form-item label="Trạng thái">
            <a-select v-model:value="filters.status" style="width: 180px" allowClear>
              <a-select-option value="">Tất cả</a-select-option>
              <a-select-option value="active">Đang hoạt động</a-select-option>
              <a-select-option value="on-leave">Nghỉ phép</a-select-option>
              <a-select-option value="inactive">Không hoạt động</a-select-option>
            </a-select>
          </a-form-item>
          
          <a-form-item label="Chuyên môn">
            <a-select v-model:value="filters.specialty" style="width: 180px" allowClear>
              <a-select-option value="">Tất cả</a-select-option>
              <a-select-option value="strength">Strength training</a-select-option>
              <a-select-option value="cardio">Cardio</a-select-option>
              <a-select-option value="yoga">Yoga</a-select-option>
              <a-select-option value="functional">Functional training</a-select-option>
              <a-select-option value="weight-loss">Weight loss</a-select-option>
            </a-select>
          </a-form-item>
          
          <a-form-item label="Sắp xếp theo">
            <a-select v-model:value="filters.sortBy" style="width: 180px">
              <a-select-option value="name">Tên</a-select-option>
              <a-select-option value="rating">Đánh giá</a-select-option>
              <a-select-option value="sessions">Số buổi tập</a-select-option>
              <a-select-option value="experience">Kinh nghiệm</a-select-option>
            </a-select>
          </a-form-item>
          
          <a-form-item>
            <a-button @click="resetFilters">
              Đặt lại bộ lọc
            </a-button>
          </a-form-item>
        </a-form>
      </div>
      
      <!-- Danh sách huấn luyện viên -->
      <a-card class="shadow">
        <a-table
          :dataSource="filteredTrainers"
          :columns="columns"
          :pagination="{ 
            pageSize: 10,
            showSizeChanger: true,
            showTotal: total => `Hiển thị ${Math.min(total, filteredTrainers.length > 0 ? 1 : 0)} đến ${Math.min(total, filteredTrainers.length)} của ${total} huấn luyện viên` 
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
            
            <template v-else-if="column.key === 'specialty'">
              <div>{{ record.specialty }}</div>
              <div class="text-xs text-muted-foreground">{{ record.experience }}</div>
            </template>
            
            <template v-else-if="column.key === 'status'">
              <a-tag :color="getStatusColor(record.status)">
                {{ getTrainerStatusText(record.status) }}
              </a-tag>
            </template>
            
            <template v-else-if="column.key === 'sessions'">
              <div>{{ record.totalSessions }} buổi</div>
              <div class="text-xs text-muted-foreground">{{ record.upcomingSessions }} buổi sắp tới</div>
            </template>
            
            <template v-else-if="column.key === 'rating'">
              <div class="flex items-center">
                <a-rate :value="record.rating" disabled allow-half :count="5" />
                <span class="ml-2">{{ record.rating }}</span>
              </div>
              <div class="text-xs text-muted-foreground">{{ record.reviewCount }} đánh giá</div>
            </template>
            
            <template v-else-if="column.key === 'actions'">
              <a-space>
                <a-button type="primary" size="small" @click="viewSchedule(record)">
                  Lịch tập
                </a-button>
                <a-button size="small" @click="viewDetail(record)">
                  Chi tiết
                </a-button>
              </a-space>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
  </MainLayout>

  <!-- Modal thêm huấn luyện viên -->
  <a-modal 
    v-model:visible="showAddTrainerModal"
    title="Thêm huấn luyện viên mới" 
    :footer="null"
    width="700px"
  >
    <a-form :model="trainerForm" layout="vertical">
      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="Họ và tên" required>
            <a-input v-model:value="trainerForm.name" placeholder="Nhập họ và tên" />
          </a-form-item>
        </a-col>
        
        <a-col :span="12">
          <a-form-item label="Số điện thoại" required>
            <a-input v-model:value="trainerForm.phone" placeholder="Nhập số điện thoại" />
          </a-form-item>
        </a-col>
        
        <a-col :span="12">
          <a-form-item label="Email">
            <a-input v-model:value="trainerForm.email" placeholder="Nhập email" />
          </a-form-item>
        </a-col>
        
        <a-col :span="12">
          <a-form-item label="Ngày sinh">
            <a-date-picker v-model:value="trainerForm.dateOfBirth" style="width: 100%" format="DD/MM/YYYY" />
          </a-form-item>
        </a-col>
        
        <a-col :span="12">
          <a-form-item label="Chuyên môn" required>
            <a-select v-model:value="trainerForm.specialty" placeholder="Chọn chuyên môn">
              <a-select-option value="strength">Strength training</a-select-option>
              <a-select-option value="cardio">Cardio</a-select-option>
              <a-select-option value="yoga">Yoga</a-select-option>
              <a-select-option value="functional">Functional training</a-select-option>
              <a-select-option value="weight-loss">Weight loss</a-select-option>
            </a-select>
          </a-form-item>
        </a-col>
        
        <a-col :span="12">
          <a-form-item label="Kinh nghiệm (năm)" required>
            <a-input-number v-model:value="trainerForm.experienceYears" :min="0" style="width: 100%" />
          </a-form-item>
        </a-col>
        
        <a-col :span="24">
          <a-form-item label="Giới thiệu">
            <a-textarea v-model:value="trainerForm.bio" :rows="4" placeholder="Nhập thông tin giới thiệu về huấn luyện viên" />
          </a-form-item>
        </a-col>
        
        <a-col :span="24">
          <a-form-item label="Chứng chỉ">
            <a-select 
              v-model:value="trainerForm.certifications" 
              mode="tags" 
              placeholder="Nhập các chứng chỉ" 
              style="width: 100%"
            />
          </a-form-item>
        </a-col>
      </a-row>
      
      <div class="flex justify-end mt-4">
        <a-space>
          <a-button @click="showAddTrainerModal = false">
            Huỷ
          </a-button>
          <a-button type="primary" @click="submitTrainerForm">
            Thêm huấn luyện viên
          </a-button>
        </a-space>
      </div>
    </a-form>
  </a-modal>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import MainLayout from '../../layouts/MainLayout.vue';
import { 
  UserAddOutlined,
  SearchOutlined,
  CalendarOutlined 
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';

// Mock data - sẽ được thay thế bằng API calls
const trainers = ref([
  { 
    id: 1, 
    name: 'Nguyễn Văn A', 
    phone: '0901234567', 
    specialty: 'Strength training', 
    experience: '5 năm kinh nghiệm', 
    status: 'active',
    totalSessions: 245,
    upcomingSessions: 8,
    rating: 4.8,
    reviewCount: 120
  },
  { 
    id: 2, 
    name: 'Trần Văn B', 
    phone: '0901234568', 
    specialty: 'Cardio & HIIT', 
    experience: '3 năm kinh nghiệm', 
    status: 'active',
    totalSessions: 183,
    upcomingSessions: 5,
    rating: 4.6,
    reviewCount: 92
  },
  { 
    id: 3, 
    name: 'Lê Thị C', 
    phone: '0901234569', 
    specialty: 'Yoga & Pilates', 
    experience: '7 năm kinh nghiệm', 
    status: 'on-leave',
    totalSessions: 312,
    upcomingSessions: 0,
    rating: 4.9,
    reviewCount: 185
  },
  { 
    id: 4, 
    name: 'Phạm Văn D', 
    phone: '0901234570', 
    specialty: 'Weight Loss', 
    experience: '4 năm kinh nghiệm', 
    status: 'active',
    totalSessions: 210,
    upcomingSessions: 7,
    rating: 4.7,
    reviewCount: 95
  },
  { 
    id: 5, 
    name: 'Hoàng Thị E', 
    phone: '0901234571', 
    specialty: 'Functional Training', 
    experience: '6 năm kinh nghiệm', 
    status: 'inactive',
    totalSessions: 275,
    upcomingSessions: 0,
    rating: 4.5,
    reviewCount: 110
  },
]);

// Bộ lọc và tìm kiếm
const searchQuery = ref('');
const filters = ref({
  status: '',
  specialty: '',
  sortBy: 'name'
});

// Reset bộ lọc
const resetFilters = () => {
  filters.value = {
    status: '',
    specialty: '',
    sortBy: 'name'
  };
  searchQuery.value = '';
};

// Lọc và sắp xếp huấn luyện viên
const filteredTrainers = computed(() => {
  let result = [...trainers.value];
  
  // Lọc theo trạng thái
  if (filters.value.status) {
    result = result.filter(trainer => trainer.status === filters.value.status);
  }
  
  // Lọc theo chuyên môn
  if (filters.value.specialty) {
    const specialtyMap = {
      'strength': 'Strength training',
      'cardio': 'Cardio & HIIT',
      'yoga': 'Yoga & Pilates',
      'functional': 'Functional Training',
      'weight-loss': 'Weight Loss'
    };
    
    const targetSpecialty = specialtyMap[filters.value.specialty];
    result = result.filter(trainer => 
      trainer.specialty.toLowerCase().includes(targetSpecialty.toLowerCase())
    );
  }
  
  // Tìm kiếm
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(trainer => 
      trainer.name.toLowerCase().includes(query) || 
      trainer.phone.includes(query) ||
      trainer.specialty.toLowerCase().includes(query)
    );
  }
  
  // Sắp xếp
  result.sort((a, b) => {
    if (filters.value.sortBy === 'name') {
      return a.name.localeCompare(b.name);
    } else if (filters.value.sortBy === 'rating') {
      return b.rating - a.rating;
    } else if (filters.value.sortBy === 'sessions') {
      return b.totalSessions - a.totalSessions;
    }
    
    return 0;
  });
  
  return result;
});

// Chuyển đổi trạng thái
const getTrainerStatusText = (status) => {
  const statusMap = {
    'active': 'Đang hoạt động',
    'on-leave': 'Nghỉ phép',
    'inactive': 'Không hoạt động'
  };
  
  return statusMap[status] || status;
};

// Modal thêm huấn luyện viên
const showAddTrainerModal = ref(false);
const trainerForm = reactive({
  name: '',
  phone: '',
  email: '',
  dateOfBirth: null,
  specialty: undefined,
  experienceYears: 0,
  bio: '',
  certifications: []
});

// Hàm xử lý thêm huấn luyện viên mới
const submitTrainerForm = () => {
  if (!trainerForm.name || !trainerForm.phone || !trainerForm.specialty) {
    message.error('Vui lòng điền đầy đủ thông tin bắt buộc!');
    return;
  }
  
  // Mô phỏng API gọi để thêm huấn luyện viên
  const newTrainer = {
    id: trainers.value.length + 1,
    name: trainerForm.name,
    phone: trainerForm.phone,
    specialty: trainerForm.specialty === 'strength' ? 'Strength training' :
               trainerForm.specialty === 'cardio' ? 'Cardio & HIIT' :
               trainerForm.specialty === 'yoga' ? 'Yoga & Pilates' :
               trainerForm.specialty === 'functional' ? 'Functional Training' : 'Weight Loss',
    experience: `${trainerForm.experienceYears} năm kinh nghiệm`,
    status: 'active',
    totalSessions: 0,
    upcomingSessions: 0,
    rating: 0,
    reviewCount: 0
  };
  
  // Thêm huấn luyện viên mới vào danh sách
  trainers.value.push(newTrainer);
  
  // Reset form và đóng modal
  Object.keys(trainerForm).forEach(key => {
    trainerForm[key] = key === 'experienceYears' ? 0 : key === 'certifications' ? [] : '';
  });
  showAddTrainerModal.value = false;
  
  message.success('Thêm huấn luyện viên thành công!');
};

// Xử lý khi tìm kiếm
const onSearch = (value) => {
  searchQuery.value = value;
};

// Định nghĩa cột cho bảng
const columns = [
  {
    title: 'Huấn luyện viên',
    dataIndex: 'name',
    key: 'name',
    sorter: (a, b) => a.name.localeCompare(b.name),
  },
  {
    title: 'Chuyên môn',
    dataIndex: 'specialty',
    key: 'specialty',
  },
  {
    title: 'Trạng thái',
    dataIndex: 'status',
    key: 'status',
    filters: [
      { text: 'Đang hoạt động', value: 'active' },
      { text: 'Nghỉ phép', value: 'on-leave' },
      { text: 'Không hoạt động', value: 'inactive' },
    ],
    onFilter: (value, record) => record.status === value,
  },
  {
    title: 'Số buổi tập',
    dataIndex: 'totalSessions',
    key: 'sessions',
    sorter: (a, b) => a.totalSessions - b.totalSessions,
  },
  {
    title: 'Đánh giá',
    dataIndex: 'rating',
    key: 'rating',
    sorter: (a, b) => a.rating - b.rating,
  },
  {
    title: 'Thao tác',
    key: 'actions',
  },
];

// Hàm lấy màu cho trạng thái
const getStatusColor = (status) => {
  const statusColorMap = {
    'active': 'success',
    'inactive': 'error',
    'on-leave': 'warning'
  };
  
  return statusColorMap[status] || 'default';
};

// Xem lịch tập của huấn luyện viên
const viewSchedule = (trainer) => {
  message.info(`Đang mở lịch tập của ${trainer.name}`);
  // Trong thực tế sẽ điều hướng đến trang lịch tập
  // router.push(`/trainers/${trainer.id}/schedule`);
};

// Xem chi tiết huấn luyện viên
const viewDetail = (trainer) => {
  message.info(`Đang mở chi tiết của ${trainer.name}`);
  // Trong thực tế sẽ điều hướng đến trang chi tiết
  // router.push(`/trainers/${trainer.id}`);
};
</script> 