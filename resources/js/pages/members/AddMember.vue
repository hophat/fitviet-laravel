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
          <h1 class="text-2xl font-bold mt-2">Thêm hội viên mới</h1>
        </div>
      </div>
      
      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Thông tin cá nhân -->
        <a-card title="Thông tin cá nhân" :bordered="false" class="shadow">
          <a-form :model="formData" layout="vertical">
            <a-row :gutter="24">
              <a-col :xs="24" :md="12">
                <a-form-item label="Họ và tên" required>
                  <a-input v-model:value="formData.name" placeholder="Nhập họ và tên" />
                </a-form-item>
              </a-col>
              
              <a-col :xs="24" :md="12">
                <a-form-item label="Số điện thoại" required>
                  <a-input v-model:value="formData.phone" placeholder="Nhập số điện thoại" />
                </a-form-item>
              </a-col>
              
              <a-col :xs="24" :md="12">
                <a-form-item label="Email">
                  <a-input v-model:value="formData.email" placeholder="Nhập email" />
                </a-form-item>
              </a-col>
              
              <a-col :xs="24" :md="12">
                <a-form-item label="Ngày sinh">
                  <a-date-picker 
                    v-model:value="formData.dateOfBirth" 
                    format="DD/MM/YYYY" 
                    style="width: 100%"
                  />
                </a-form-item>
              </a-col>
              
              <a-col :xs="24" :md="12">
                <a-form-item label="Giới tính">
                  <a-select v-model:value="formData.gender" placeholder="Chọn giới tính">
                    <a-select-option value="Nam">Nam</a-select-option>
                    <a-select-option value="Nữ">Nữ</a-select-option>
                    <a-select-option value="Khác">Khác</a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              
              <a-col :xs="24" :md="12">
                <a-form-item label="CMND/CCCD">
                  <a-input v-model:value="formData.idCard" placeholder="Nhập số CMND/CCCD" />
                </a-form-item>
              </a-col>
              
              <a-col :xs="24">
                <a-form-item label="Địa chỉ">
                  <a-input v-model:value="formData.address" placeholder="Nhập địa chỉ" />
                </a-form-item>
              </a-col>
            </a-row>
          </a-form>
        </a-card>
        
        <!-- Thông tin chỉ số cơ thể -->
        <a-card title="Chỉ số cơ thể" :bordered="false" class="shadow">
          <a-form :model="formData" layout="vertical">
            <a-row :gutter="24">
              <a-col :xs="24" :md="8">
                <a-form-item label="Chiều cao (cm)">
                  <a-input-number 
                    v-model:value="formData.height" 
                    placeholder="Ví dụ: 170" 
                    style="width: 100%"
                  />
                </a-form-item>
              </a-col>
              
              <a-col :xs="24" :md="8">
                <a-form-item label="Cân nặng (kg)">
                  <a-input-number 
                    v-model:value="formData.weight" 
                    placeholder="Ví dụ: 65" 
                    style="width: 100%"
                  />
                </a-form-item>
              </a-col>
              
              <a-col :xs="24" :md="8">
                <a-form-item label="Tỷ lệ mỡ (%)">
                  <a-input-number 
                    v-model:value="formData.bodyFat" 
                    placeholder="Ví dụ: 18" 
                    style="width: 100%"
                  />
                </a-form-item>
              </a-col>
            </a-row>
            
            <a-alert 
              v-if="bmi" 
              :message="`Chỉ số BMI: ${bmi.toFixed(1)} - ${getBmiCategory(bmi)}`"
              :type="getBmiAlertType(bmi)"
              show-icon
            />
          </a-form>
        </a-card>
        
        <!-- Thông tin gói tập -->
        <a-card title="Đăng ký gói tập" :bordered="false" class="shadow">
          <a-form :model="formData" layout="vertical">
            <a-form-item label="Chọn gói tập" required>
              <a-select 
                v-model:value="formData.packageId" 
                placeholder="Chọn gói tập"
                style="width: 100%"
              >
                <a-select-option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                  {{ pkg.name }} - {{ formatCurrency(pkg.price) }}
                </a-select-option>
              </a-select>
            </a-form-item>
            
            <a-card v-if="selectedPackage" class="mb-4" :bordered="false" style="background: #f5f5f5">
              <template #title>
                <div class="text-primary">{{ selectedPackage.name }}</div>
              </template>
              <a-descriptions :column="2">
                <a-descriptions-item label="Thời hạn">{{ selectedPackage.duration }} tháng</a-descriptions-item>
                <a-descriptions-item label="Giá gói tập">{{ formatCurrency(selectedPackage.price) }}</a-descriptions-item>
              </a-descriptions>
              
              <div class="mt-4">
                <div class="font-medium mb-2">Quyền lợi của gói:</div>
                <a-space direction="vertical">
                  <div v-for="(feature, index) in selectedPackage.features" :key="index">
                    <a-tag color="success">{{ feature }}</a-tag>
                  </div>
                </a-space>
              </div>
            </a-card>
            
            <a-row :gutter="24">
              <a-col :xs="24" :md="12">
                <a-form-item label="Ngày bắt đầu" required>
                  <a-date-picker 
                    v-model:value="formData.startDate" 
                    format="DD/MM/YYYY"
                    style="width: 100%"
                  />
                </a-form-item>
              </a-col>
              
              <a-col :xs="24" :md="12">
                <a-form-item label="Ngày kết thúc">
                  <a-input 
                    :value="endDate" 
                    disabled
                    style="width: 100%"
                  />
                </a-form-item>
              </a-col>
            </a-row>
            
            <a-form-item label="Phương thức thanh toán" required>
              <a-select 
                v-model:value="formData.paymentMethod" 
                placeholder="Chọn phương thức thanh toán"
              >
                <a-select-option value="cash">Tiền mặt</a-select-option>
                <a-select-option value="transfer">Chuyển khoản</a-select-option>
                <a-select-option value="card">Thẻ tín dụng/ghi nợ</a-select-option>
                <a-select-option value="qr">Quét mã QR</a-select-option>
              </a-select>
            </a-form-item>
            
            <a-form-item label="Ghi chú">
              <a-textarea 
                v-model:value="formData.notes" 
                placeholder="Nhập ghi chú về hội viên..." 
                :rows="4"
              />
            </a-form-item>
          </a-form>
        </a-card>
        
        <div class="flex justify-end space-x-3">
          <router-link to="/members">
            <a-button>Huỷ</a-button>
          </router-link>
          <a-button type="primary" html-type="submit">Thêm hội viên</a-button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import MainLayout from '../../layouts/MainLayout.vue';
import dayjs from 'dayjs';
import { message } from 'ant-design-vue';

const router = useRouter();

// Dữ liệu form
const formData = ref({
  name: '',
  phone: '',
  email: '',
  dateOfBirth: null,
  gender: undefined,
  idCard: '',
  address: '',
  height: null,
  weight: null,
  bodyFat: null,
  packageId: undefined,
  startDate: dayjs(),
  paymentMethod: undefined,
  notes: ''
});

// Danh sách gói tập
const packages = ref([
  { 
    id: 1, 
    name: 'Gói Cơ Bản', 
    description: 'Sử dụng phòng tập không giới hạn', 
    duration: 1, 
    price: 300000, 
    features: ['Phòng tập không giới hạn', 'Tủ đồ cá nhân'],
    status: 'active'
  },
  { 
    id: 2, 
    name: 'Gói Standard 3 tháng', 
    description: 'Phòng tập và các lớp tập theo nhóm', 
    duration: 3, 
    price: 750000, 
    features: ['Phòng tập không giới hạn', 'Lớp tập nhóm', 'Tủ đồ cá nhân', 'Đánh giá thể chất'],
    status: 'active'
  },
  { 
    id: 3, 
    name: 'Gói VIP 6 tháng', 
    description: 'Trải nghiệm tập luyện cao cấp', 
    duration: 6, 
    price: 1500000, 
    features: ['Phòng tập không giới hạn', 'Lớp tập nhóm', 'Tủ đồ cá nhân', 'Đánh giá thể chất', '2 buổi PT', 'Hỗ trợ dinh dưỡng'],
    status: 'active'
  },
  { 
    id: 4, 
    name: 'Gói Platinum 12 tháng', 
    description: 'Trọn gói cao cấp nhất', 
    duration: 12, 
    price: 2500000, 
    features: ['Phòng tập không giới hạn', 'Lớp tập nhóm', 'Tủ đồ VIP', 'Đánh giá thể chất', '5 buổi PT', 'Hỗ trợ dinh dưỡng', 'Khăn tập miễn phí', 'Giặt đồ'],
    status: 'active'
  }
]);

// Tính BMI 
const bmi = computed(() => {
  if (formData.value.height && formData.value.weight) {
    const heightInMeters = formData.value.height / 100;
    return formData.value.weight / (heightInMeters * heightInMeters);
  }
  return null;
});

// Phân loại BMI
const getBmiCategory = (bmi) => {
  if (bmi < 18.5) return 'Thiếu cân';
  if (bmi >= 18.5 && bmi < 25) return 'Bình thường';
  if (bmi >= 25 && bmi < 30) return 'Thừa cân';
  return 'Béo phì';
};

// Lấy thông tin gói tập được chọn
const selectedPackage = computed(() => {
  if (formData.value.packageId) {
    return packages.value.find(pkg => pkg.id == formData.value.packageId);
  }
  return null;
});

// Lấy loại cảnh báo dựa vào BMI
const getBmiAlertType = (bmi) => {
  if (bmi < 18.5 || (bmi >= 25 && bmi < 30)) return 'warning';
  if (bmi >= 30) return 'error';
  return 'success';
};

// Tính ngày kết thúc dựa vào ngày bắt đầu và thời hạn gói tập
const endDate = computed(() => {
  if (formData.value.startDate && selectedPackage.value) {
    const startDate = dayjs(formData.value.startDate);
    const endDate = startDate.add(selectedPackage.value.duration, 'month');
    
    return endDate.format('DD/MM/YYYY');
  }
  return '';
});

// Format tiền tệ
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0
  }).format(amount);
};

// Xử lý submit form
const submitForm = () => {
  // Kiểm tra dữ liệu
  if (!formData.value.name || !formData.value.phone || !formData.value.packageId || 
      !formData.value.startDate || !formData.value.paymentMethod) {
    message.error('Vui lòng điền đầy đủ thông tin bắt buộc!');
    return;
  }
  
  // Hiển thị dữ liệu form (sẽ thay bằng API call sau)
  console.log('Form data:', formData.value);
  
  // Trong thực tế sẽ gọi API để lưu thông tin hội viên
  // api.post('/api/members', formData.value)
  //   .then(response => {
  //     // Điều hướng đến trang chi tiết hội viên sau khi tạo thành công
  //     router.push(`/members/${response.data.id}`);
  //   })
  //   .catch(error => {
  //     console.error('Error creating member:', error);
  //   });
  
  // Giả lập thành công - điều hướng về trang danh sách hội viên
  message.success('Thêm hội viên thành công!');
  router.push('/members');
};
</script> 