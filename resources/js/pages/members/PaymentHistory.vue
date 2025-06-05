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
          <h1 class="text-2xl font-bold mt-2">Lịch sử thanh toán</h1>
        </div>
        
        <div class="flex space-x-2">
          <a-button type="primary" @click="showAddPaymentModal = true">
            <template #icon><PlusOutlined /></template>
            Thêm giao dịch
          </a-button>
        </div>
      </div>
      
      <!-- Bộ lọc -->
      <a-card class="shadow mb-6">
        <a-form layout="inline" class="flex flex-wrap gap-4">
          <a-form-item label="Loại giao dịch">
            <a-select v-model:value="filters.type" style="width: 200px" allowClear>
              <a-select-option value="">Tất cả</a-select-option>
              <a-select-option value="membership">Gói tập</a-select-option>
              <a-select-option value="product">Sản phẩm</a-select-option>
              <a-select-option value="pt">PT</a-select-option>
              <a-select-option value="other">Khác</a-select-option>
            </a-select>
          </a-form-item>
          
          <a-form-item label="Trạng thái">
            <a-select v-model:value="filters.status" style="width: 200px" allowClear>
              <a-select-option value="">Tất cả</a-select-option>
              <a-select-option value="completed">Đã hoàn thành</a-select-option>
              <a-select-option value="pending">Đang xử lý</a-select-option>
              <a-select-option value="refunded">Đã hoàn tiền</a-select-option>
            </a-select>
          </a-form-item>
          
          <a-form-item label="Từ ngày">
            <a-date-picker v-model:value="filters.startDate" format="DD/MM/YYYY" />
          </a-form-item>
          
          <a-form-item label="Đến ngày">
            <a-date-picker v-model:value="filters.endDate" format="DD/MM/YYYY" />
          </a-form-item>
          
          <a-form-item>
            <a-button type="primary" @click="applyFilters">Lọc</a-button>
            <a-button class="ml-2" @click="resetFilters">Đặt lại</a-button>
          </a-form-item>
        </a-form>
      </a-card>
      
      <!-- Thống kê -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
        <a-card class="shadow">
          <template #title>Tổng thanh toán</template>
          <div class="text-2xl font-bold text-primary">{{ formatCurrency(summary.total) }}</div>
          <div class="text-sm mt-2">{{ summary.count }} giao dịch</div>
        </a-card>
        
        <a-card class="shadow">
          <template #title>Gói tập</template>
          <div class="text-2xl font-bold text-blue-600">{{ formatCurrency(summary.membership) }}</div>
          <div class="text-sm mt-2">{{ summary.membershipCount }} giao dịch</div>
        </a-card>
        
        <a-card class="shadow">
          <template #title>Sản phẩm & PT</template>
          <div class="text-2xl font-bold text-green-600">{{ formatCurrency(summary.other) }}</div>
          <div class="text-sm mt-2">{{ summary.otherCount }} giao dịch</div>
        </a-card>
      </div>
      
      <!-- Danh sách giao dịch -->
      <a-card class="shadow">
        <template #title>Lịch sử giao dịch</template>
        <a-table
          :dataSource="filteredPayments"
          :columns="columns"
          :pagination="{ pageSize: 10 }"
          :rowKey="record => record.id"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'date'">
              <div>{{ record.date }}</div>
              <div class="text-xs text-muted-foreground">{{ record.time }}</div>
            </template>
            
            <template v-else-if="column.key === 'description'">
              <div class="font-medium">{{ record.description }}</div>
              <div class="text-xs text-muted-foreground">{{ getTransactionTypeText(record.type) }}</div>
            </template>
            
            <template v-else-if="column.key === 'amount'">
              <div 
                :class="{
                  'font-medium': true,
                  'text-red-600': record.amountType === 'debit',
                  'text-green-600': record.amountType === 'credit'
                }"
              >
                {{ record.amountType === 'debit' ? '-' : '+' }} {{ formatCurrency(record.amount) }}
              </div>
            </template>
            
            <template v-else-if="column.key === 'method'">
              <a-tag>{{ getPaymentMethodText(record.method) }}</a-tag>
            </template>
            
            <template v-else-if="column.key === 'status'">
              <a-tag :color="getStatusColor(record.status)">
                {{ getStatusText(record.status) }}
              </a-tag>
            </template>
            
            <template v-else-if="column.key === 'actions'">
              <a-dropdown>
                <a-button>
                  <template #icon><MoreOutlined /></template>
                </a-button>
                <template #overlay>
                  <a-menu>
                    <a-menu-item key="1" @click="viewDetail(record)">
                      <EyeOutlined />
                      <span>Xem chi tiết</span>
                    </a-menu-item>
                    <a-menu-item key="2" @click="printReceipt(record)">
                      <PrinterOutlined />
                      <span>In hoá đơn</span>
                    </a-menu-item>
                    <a-menu-item v-if="record.status === 'completed'" key="3" @click="refundPayment(record)">
                      <RollbackOutlined />
                      <span>Hoàn tiền</span>
                    </a-menu-item>
                  </a-menu>
                </template>
              </a-dropdown>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
    
    <!-- Modal thêm giao dịch -->
    <a-modal
      v-model:visible="showAddPaymentModal"
      title="Thêm giao dịch mới"
      :footer="null"
      width="700px"
    >
      <a-form :model="paymentForm" layout="vertical">
        <a-form-item label="Loại giao dịch" required>
          <a-select v-model:value="paymentForm.type" placeholder="Chọn loại giao dịch">
            <a-select-option value="membership">Gói tập</a-select-option>
            <a-select-option value="product">Sản phẩm</a-select-option>
            <a-select-option value="pt">PT</a-select-option>
            <a-select-option value="other">Khác</a-select-option>
          </a-select>
        </a-form-item>
        
        <a-form-item label="Mô tả" required>
          <a-input v-model:value="paymentForm.description" placeholder="Nhập mô tả giao dịch" />
        </a-form-item>
        
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="Số tiền" required>
              <a-input-number 
                v-model:value="paymentForm.amount" 
                :formatter="value => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                :parser="value => value.replace(/\$\s?|(,*)/g, '')"
                :min="0"
                style="width: 100%"
              />
            </a-form-item>
          </a-col>
          
          <a-col :span="12">
            <a-form-item label="Loại" required>
              <a-select v-model:value="paymentForm.amountType">
                <a-select-option value="debit">Thanh toán (trừ tiền)</a-select-option>
                <a-select-option value="credit">Nạp tiền (cộng tiền)</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
        </a-row>
        
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="Phương thức thanh toán" required>
              <a-select v-model:value="paymentForm.method">
                <a-select-option value="cash">Tiền mặt</a-select-option>
                <a-select-option value="transfer">Chuyển khoản</a-select-option>
                <a-select-option value="card">Thẻ tín dụng/ghi nợ</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          
          <a-col :span="12">
            <a-form-item label="Trạng thái" required>
              <a-select v-model:value="paymentForm.status">
                <a-select-option value="completed">Đã hoàn thành</a-select-option>
                <a-select-option value="pending">Đang xử lý</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
        </a-row>
        
        <a-form-item label="Ghi chú">
          <a-textarea v-model:value="paymentForm.notes" :rows="4" placeholder="Nhập ghi chú cho giao dịch" />
        </a-form-item>
        
        <div class="flex justify-end mt-4">
          <a-space>
            <a-button @click="showAddPaymentModal = false">
              Huỷ
            </a-button>
            <a-button type="primary" @click="submitPaymentForm">
              Thêm giao dịch
            </a-button>
          </a-space>
        </div>
      </a-form>
    </a-modal>
  </MainLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { useRoute } from 'vue-router';
import MainLayout from '../../layouts/MainLayout.vue';
import { 
  PlusOutlined,
  MoreOutlined, 
  EyeOutlined, 
  PrinterOutlined, 
  RollbackOutlined 
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';

const route = useRoute();
const memberId = route.params.id;

// Dữ liệu giả
const payments = ref([
  {
    id: 1,
    date: '10/01/2025',
    time: '10:15',
    description: 'Thanh toán Gói Premium',
    type: 'membership',
    amount: 12000000,
    amountType: 'debit',
    method: 'card',
    status: 'completed',
    notes: 'Gói tập 12 tháng'
  },
  {
    id: 2,
    date: '15/02/2025',
    time: '15:30',
    description: 'Mua nước uống và phụ kiện',
    type: 'product',
    amount: 500000,
    amountType: 'debit',
    method: 'cash',
    status: 'completed',
    notes: '2 chai nước, 1 dây nhảy, 1 khăn'
  },
  {
    id: 3,
    date: '05/03/2025',
    time: '18:45',
    description: 'Gói 10 buổi PT',
    type: 'pt',
    amount: 2500000,
    amountType: 'debit',
    method: 'transfer',
    status: 'completed',
    notes: 'PT với HLV Lê Văn Huấn'
  },
  {
    id: 4,
    date: '12/03/2025',
    time: '09:20',
    description: 'Whey Protein 1kg',
    type: 'product',
    amount: 850000,
    amountType: 'debit',
    method: 'cash',
    status: 'completed',
    notes: ''
  },
  {
    id: 5,
    date: '20/03/2025',
    time: '14:15',
    description: 'Hoàn tiền 2 buổi PT',
    type: 'pt',
    amount: 500000,
    amountType: 'credit',
    method: 'transfer',
    status: 'refunded',
    notes: 'Hoàn tiền do HLV nghỉ phép'
  }
]);

// Modal thêm giao dịch
const showAddPaymentModal = ref(false);
const paymentForm = reactive({
  type: undefined,
  description: '',
  amount: 0,
  amountType: 'debit',
  method: undefined,
  status: 'completed',
  notes: ''
});

// Bộ lọc
const filters = reactive({
  type: '',
  status: '',
  startDate: null,
  endDate: null
});

// Tổng hợp thống kê
const summary = computed(() => {
  const totalAmount = payments.value.reduce((acc, payment) => {
    if (payment.amountType === 'debit') {
      return acc + payment.amount;
    } else {
      return acc - payment.amount;
    }
  }, 0);
  
  const membershipAmount = payments.value.filter(p => p.type === 'membership').reduce((acc, payment) => {
    if (payment.amountType === 'debit') {
      return acc + payment.amount;
    } else {
      return acc - payment.amount;
    }
  }, 0);
  
  const otherAmount = totalAmount - membershipAmount;
  
  return {
    total: totalAmount,
    count: payments.value.length,
    membership: membershipAmount,
    membershipCount: payments.value.filter(p => p.type === 'membership').length,
    other: otherAmount,
    otherCount: payments.value.filter(p => p.type !== 'membership').length
  };
});

// Lọc giao dịch
const filteredPayments = computed(() => {
  return payments.value.filter(payment => {
    if (filters.type && payment.type !== filters.type) return false;
    if (filters.status && payment.status !== filters.status) return false;
    
    if (filters.startDate && filters.endDate) {
      const paymentDate = dayjs(payment.date, 'DD/MM/YYYY');
      const start = dayjs(filters.startDate);
      const end = dayjs(filters.endDate);
      if (paymentDate.isBefore(start) || paymentDate.isAfter(end)) return false;
    }
    
    return true;
  });
});

// Các cột cho bảng
const columns = [
  {
    title: 'Ngày',
    key: 'date',
    dataIndex: 'date',
    width: '15%',
  },
  {
    title: 'Mô tả',
    key: 'description',
    dataIndex: 'description',
    width: '30%',
  },
  {
    title: 'Số tiền',
    key: 'amount',
    dataIndex: 'amount',
    width: '15%',
    sorter: (a, b) => {
      const aVal = a.amountType === 'debit' ? a.amount : -a.amount;
      const bVal = b.amountType === 'debit' ? b.amount : -b.amount;
      return aVal - bVal;
    },
  },
  {
    title: 'Phương thức',
    key: 'method',
    dataIndex: 'method',
    width: '15%',
  },
  {
    title: 'Trạng thái',
    key: 'status',
    dataIndex: 'status',
    width: '15%',
  },
  {
    title: 'Thao tác',
    key: 'actions',
    width: '10%',
  },
];

// Định dạng tiền tệ
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0
  }).format(amount);
};

// Lấy văn bản cho trạng thái
const getStatusText = (status) => {
  const statusMap = {
    'completed': 'Đã hoàn thành',
    'pending': 'Đang xử lý',
    'refunded': 'Đã hoàn tiền'
  };
  
  return statusMap[status] || status;
};

// Lấy màu cho trạng thái
const getStatusColor = (status) => {
  const colorMap = {
    'completed': 'success',
    'pending': 'warning',
    'refunded': 'default'
  };
  
  return colorMap[status] || 'default';
};

// Lấy văn bản cho phương thức thanh toán
const getPaymentMethodText = (method) => {
  const methodMap = {
    'cash': 'Tiền mặt',
    'transfer': 'Chuyển khoản',
    'card': 'Thẻ tín dụng/ghi nợ'
  };
  
  return methodMap[method] || method;
};

// Lấy văn bản cho loại giao dịch
const getTransactionTypeText = (type) => {
  const typeMap = {
    'membership': 'Gói tập',
    'product': 'Sản phẩm',
    'pt': 'Huấn luyện viên',
    'other': 'Khác'
  };
  
  return typeMap[type] || type;
};

// Áp dụng bộ lọc
const applyFilters = () => {
  // Không cần làm gì vì đã dùng computed
  message.success('Đã áp dụng bộ lọc');
};

// Đặt lại bộ lọc
const resetFilters = () => {
  filters.type = '';
  filters.status = '';
  filters.startDate = null;
  filters.endDate = null;
  message.success('Đã đặt lại bộ lọc');
};

// Thêm giao dịch mới
const submitPaymentForm = () => {
  if (!paymentForm.type || !paymentForm.description || !paymentForm.amount || !paymentForm.method) {
    message.error('Vui lòng điền đầy đủ thông tin bắt buộc!');
    return;
  }
  
  // Tạo giao dịch mới
  const newPayment = {
    id: payments.value.length + 1,
    date: dayjs().format('DD/MM/YYYY'),
    time: dayjs().format('HH:mm'),
    description: paymentForm.description,
    type: paymentForm.type,
    amount: paymentForm.amount,
    amountType: paymentForm.amountType,
    method: paymentForm.method,
    status: paymentForm.status,
    notes: paymentForm.notes
  };
  
  // Thêm vào danh sách
  payments.value.unshift(newPayment);
  
  // Đóng modal và reset form
  showAddPaymentModal.value = false;
  Object.keys(paymentForm).forEach(key => {
    if (key === 'amount') {
      paymentForm[key] = 0;
    } else if (key === 'amountType') {
      paymentForm[key] = 'debit';
    } else if (key === 'status') {
      paymentForm[key] = 'completed';
    } else {
      paymentForm[key] = '';
    }
  });
  
  message.success('Đã thêm giao dịch mới!');
};

// Xem chi tiết giao dịch
const viewDetail = (record) => {
  message.info(`Xem chi tiết giao dịch: ${record.description}`);
};

// In hoá đơn
const printReceipt = (record) => {
  message.info(`In hoá đơn cho giao dịch: ${record.description}`);
  // Trong thực tế sẽ có code in hoá đơn ở đây
};

// Hoàn tiền
const refundPayment = (record) => {
  if (record.status === 'refunded') {
    message.warning('Giao dịch này đã được hoàn tiền!');
    return;
  }
  
  // Tạo giao dịch hoàn tiền mới
  const refundPayment = {
    id: payments.value.length + 1,
    date: dayjs().format('DD/MM/YYYY'),
    time: dayjs().format('HH:mm'),
    description: `Hoàn tiền: ${record.description}`,
    type: record.type,
    amount: record.amount,
    amountType: 'credit',
    method: record.method,
    status: 'refunded',
    notes: `Hoàn tiền cho giao dịch #${record.id}`
  };
  
  // Thêm vào danh sách
  payments.value.unshift(refundPayment);
  
  // Cập nhật trạng thái giao dịch gốc
  const original = payments.value.find(p => p.id === record.id);
  if (original) {
    original.status = 'refunded';
  }
  
  message.success('Đã hoàn tiền thành công!');
};
</script> 