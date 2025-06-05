<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Header và các nút điều hướng -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0 mb-6">
        <div>
          <div class="flex items-center space-x-2">
            <router-link to="/orders" class="text-primary hover:underline">
              <span>← Danh sách đơn hàng</span>
            </router-link>
          </div>
          <h1 class="text-2xl font-bold mt-2">Đơn hàng #{{ order.id }}</h1>
        </div>
        
        <div class="flex space-x-2">
          <a-button @click="printOrder" type="primary" ghost>
            <template #icon><PrinterOutlined /></template>
            In đơn hàng
          </a-button>
          
          <a-dropdown v-if="order.status === 'completed' && order.payment_status === 'completed'">
            <a-button>
              <template #icon><EllipsisOutlined /></template>
              Thao tác
            </a-button>
            <template #overlay>
              <a-menu>
                <a-menu-item key="1" @click="showRefundModal = true">
                  <RollbackOutlined />
                  <span>Hoàn tiền</span>
                </a-menu-item>
              </a-menu>
            </template>
          </a-dropdown>
        </div>
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Thông tin đơn hàng -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Chi tiết đơn hàng -->
          <a-card title="Chi tiết đơn hàng">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Sản phẩm
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Giá
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Số lượng
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Thành tiền
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(item, index) in order.items" :key="index">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="h-10 w-10 rounded bg-gray-100 flex items-center justify-center mr-3">
                          <span class="text-xl">📦</span>
                        </div>
                        <div>
                          <div class="font-medium">{{ item.product?.name || 'Sản phẩm đã xóa' }}</div>
                          <div class="text-xs text-gray-500">{{ item.product?.sku || '' }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      {{ formatCurrency(item.price) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      {{ item.quantity }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap font-medium">
                      {{ formatCurrency(item.total) }}
                    </td>
                  </tr>
                  
                  <!-- Tổng cộng -->
                  <tr class="bg-gray-50">
                    <td colspan="3" class="px-6 py-4 text-right font-medium">
                      Tạm tính:
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap font-medium">
                      {{ formatCurrency(order.subtotal) }}
                    </td>
                  </tr>
                  <tr v-if="order.discount" class="bg-gray-50">
                    <td colspan="3" class="px-6 py-4 text-right font-medium">
                      Giảm giá:
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-red-600">
                      - {{ formatCurrency(order.discount) }}
                    </td>
                  </tr>
                  <tr class="bg-gray-50">
                    <td colspan="3" class="px-6 py-4 text-right font-medium">
                      Tổng cộng:
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-lg">
                      {{ formatCurrency(order.total) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </a-card>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Thông tin thanh toán -->
            <a-card title="Thông tin thanh toán">
              <div class="space-y-4">
                <div class="flex justify-between">
                  <span class="text-gray-500">Phương thức thanh toán:</span>
                  <span class="font-medium">{{ getPaymentMethodText(order.payment_method) }}</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-gray-500">Trạng thái thanh toán:</span>
                  <a-tag :color="getPaymentStatusColor(order.payment_status)">
                    {{ getPaymentStatusText(order.payment_status) }}
                  </a-tag>
                </div>
                
                <div v-if="order.payment_status === 'refunded'" class="border-t pt-4 mt-4">
                  <div class="font-medium mb-2">Thông tin hoàn tiền:</div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Số tiền hoàn:</span>
                    <span class="font-medium">{{ formatCurrency(order.refund_amount) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Ngày hoàn tiền:</span>
                    <span>{{ formatDateTime(order.refunded_at) }}</span>
                  </div>
                  <div class="mt-2">
                    <span class="text-gray-500">Lý do hoàn tiền:</span>
                    <p class="mt-1">{{ order.refund_reason }}</p>
                  </div>
                </div>
              </div>
            </a-card>
            
            <!-- Lịch sử đơn hàng -->
            <a-card title="Lịch sử đơn hàng">
              <a-timeline>
                <a-timeline-item>
                  <template #dot>
                    <CheckCircleOutlined style="font-size: 16px;" />
                  </template>
                  <div class="font-medium">Đơn hàng đã tạo</div>
                  <div class="text-sm text-gray-500">{{ formatDateTime(order.created_at) }}</div>
                </a-timeline-item>
                
                <a-timeline-item v-if="order.status === 'completed'">
                  <template #dot>
                    <CheckCircleOutlined style="font-size: 16px;" />
                  </template>
                  <div class="font-medium">Đơn hàng hoàn thành</div>
                  <div class="text-sm text-gray-500">{{ formatDateTime(order.updated_at) }}</div>
                </a-timeline-item>
                
                <a-timeline-item v-if="order.payment_status === 'completed'">
                  <template #dot>
                    <DollarOutlined style="font-size: 16px;" />
                  </template>
                  <div class="font-medium">Thanh toán thành công</div>
                  <div class="text-sm text-gray-500">{{ formatDateTime(order.updated_at) }}</div>
                </a-timeline-item>
                
                <a-timeline-item v-if="order.payment_status === 'refunded'" color="red">
                  <template #dot>
                    <RollbackOutlined style="font-size: 16px;" />
                  </template>
                  <div class="font-medium">Đã hoàn tiền</div>
                  <div class="text-sm text-gray-500">{{ formatDateTime(order.refunded_at) }}</div>
                </a-timeline-item>
              </a-timeline>
            </a-card>
          </div>
          
          <!-- Ghi chú đơn hàng -->
          <a-card title="Ghi chú đơn hàng" v-if="order.notes">
            <p>{{ order.notes }}</p>
          </a-card>
        </div>
        
        <!-- Thông tin khách hàng và tóm tắt -->
        <div class="space-y-6">
          <!-- Thông tin đơn hàng -->
          <a-card title="Thông tin đơn hàng">
            <div class="space-y-4">
              <div class="flex justify-between">
                <span class="text-gray-500">Mã đơn hàng:</span>
                <span class="font-medium">#{{ order.id }}</span>
              </div>
              
              <div class="flex justify-between">
                <span class="text-gray-500">Ngày tạo:</span>
                <span>{{ formatDateTime(order.created_at) }}</span>
              </div>
              
              <div class="flex justify-between">
                <span class="text-gray-500">Trạng thái:</span>
                <a-tag :color="getOrderStatusColor(order.status)">
                  {{ getOrderStatusText(order.status) }}
                </a-tag>
              </div>
              
              <div class="flex justify-between">
                <span class="text-gray-500">Người tạo:</span>
                <span>Admin</span>
              </div>
            </div>
          </a-card>
          
          <!-- Thông tin khách hàng -->
          <a-card title="Thông tin khách hàng">
            <div v-if="order.member">
              <div class="flex items-center mb-4">
                <a-avatar :size="40" class="bg-primary mr-3">
                  {{ order.member.name.charAt(0) }}
                </a-avatar>
                <div>
                  <div class="font-medium">{{ order.member.name }}</div>
                  <router-link :to="`/members/${order.member.id}`" class="text-primary text-sm hover:underline">
                    Xem hồ sơ
                  </router-link>
                </div>
              </div>
              
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-500">Số điện thoại:</span>
                  <span>{{ order.member.phone }}</span>
                </div>
                
                <div class="flex justify-between" v-if="order.member.email">
                  <span class="text-gray-500">Email:</span>
                  <span>{{ order.member.email }}</span>
                </div>
              </div>
            </div>
            <div v-else>
              <div class="text-center py-4">
                <div class="text-gray-400 text-4xl mb-2">👤</div>
                <div class="font-medium">Khách vãng lai</div>
                <div class="text-sm text-gray-500">Khách hàng không có thông tin</div>
              </div>
            </div>
          </a-card>
          
          <!-- Hành động -->
          <a-card title="Hành động">
            <div class="space-y-2">
              <a-button block @click="printOrder">
                <template #icon><PrinterOutlined /></template>
                In đơn hàng
              </a-button>
              
              <a-button 
                block 
                type="primary" 
                danger 
                v-if="order.status === 'completed' && order.payment_status === 'completed'"
                @click="showRefundModal = true"
              >
                <template #icon><RollbackOutlined /></template>
                Hoàn tiền
              </a-button>
            </div>
          </a-card>
        </div>
      </div>
    </div>
    
    <!-- Modal hoàn tiền -->
    <a-modal
      v-model:visible="showRefundModal"
      title="Hoàn tiền đơn hàng"
      @ok="handleRefund"
      :confirmLoading="refundLoading"
      :maskClosable="false"
    >
      <a-form layout="vertical">
        <a-form-item label="Số tiền hoàn lại" required>
          <a-input-number 
            v-model:value="refundForm.refund_amount" 
            style="width: 100%"
            :max="order.total"
            :min="0"
            :formatter="value => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
            :parser="value => value.replace(/\$\s?|(,*)/g, '')"
          />
        </a-form-item>
        
        <a-form-item label="Lý do hoàn tiền" required>
          <a-textarea v-model:value="refundForm.refund_reason" :rows="3" />
        </a-form-item>
        
        <a-form-item>
          <a-checkbox v-model:checked="refundForm.restore_stock">
            Khôi phục số lượng sản phẩm về kho
          </a-checkbox>
        </a-form-item>
      </a-form>
    </a-modal>
    
    <!-- Modal in đơn hàng -->
    <a-modal
      v-model:visible="showPrintModal"
      title="In đơn hàng"
      :footer="null"
      width="400px"
    >
      <div id="print-content">
        <div class="text-center mb-6">
          <h2 class="text-xl font-bold">FIT VIET GYM</h2>
          <p>Số 123 Đường Nguyễn Huệ, Quận 1, TP.HCM</p>
          <p>Tel: 0123.456.789</p>
          
          <h3 class="text-lg font-bold mt-4">HÓA ĐƠN BÁN HÀNG</h3>
          <p>Mã đơn hàng: #{{ order.id }}</p>
          <p>Ngày: {{ formatDate(order.created_at) }}</p>
        </div>
        
        <div class="mb-4">
          <p><strong>Khách hàng:</strong> {{ order.member ? order.member.name : 'Khách vãng lai' }}</p>
          <p v-if="order.member && order.member.phone"><strong>SĐT:</strong> {{ order.member.phone }}</p>
        </div>
        
        <div class="mb-4">
          <table class="w-full mb-4">
            <thead>
              <tr class="border-b text-left">
                <th class="pb-1">Sản phẩm</th>
                <th class="pb-1 text-right">SL</th>
                <th class="pb-1 text-right">Đơn giá</th>
                <th class="pb-1 text-right">Thành tiền</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in order.items" :key="index" class="border-b">
                <td class="py-1">{{ item.product?.name || 'Sản phẩm đã xóa' }}</td>
                <td class="py-1 text-right">{{ item.quantity }}</td>
                <td class="py-1 text-right">{{ formatCurrency(item.price) }}</td>
                <td class="py-1 text-right">{{ formatCurrency(item.total) }}</td>
              </tr>
            </tbody>
          </table>
          
          <div class="flex justify-between">
            <span>Tạm tính:</span>
            <span>{{ formatCurrency(order.subtotal) }}</span>
          </div>
          
          <div v-if="order.discount" class="flex justify-between">
            <span>Giảm giá:</span>
            <span>{{ formatCurrency(order.discount) }}</span>
          </div>
          
          <div class="flex justify-between font-bold pt-2 border-t mt-2">
            <span>Tổng cộng:</span>
            <span>{{ formatCurrency(order.total) }}</span>
          </div>
        </div>
        
        <div class="mb-4">
          <p><strong>Phương thức thanh toán:</strong> {{ getPaymentMethodText(order.payment_method) }}</p>
          <p><strong>Trạng thái thanh toán:</strong> {{ getPaymentStatusText(order.payment_status) }}</p>
        </div>
        
        <div class="text-center mt-6">
          <p>Cảm ơn quý khách đã mua hàng!</p>
          <p>Hẹn gặp lại quý khách.</p>
        </div>
      </div>
      
      <div class="flex justify-center mt-4">
        <a-space>
          <a-button @click="showPrintModal = false">Đóng</a-button>
          <a-button type="primary" @click="handlePrint">In hóa đơn</a-button>
        </a-space>
      </div>
    </a-modal>
  </MainLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { message } from 'ant-design-vue';
import axios from 'axios';
import dayjs from 'dayjs';
import MainLayout from '../../layouts/MainLayout.vue';
import { 
  PrinterOutlined,
  EllipsisOutlined,
  RollbackOutlined,
  CheckCircleOutlined,
  DollarOutlined
} from '@ant-design/icons-vue';

const route = useRoute();
const router = useRouter();
const orderId = route.params.id;

// Data
const order = ref({
  id: 0,
  member: null,
  items: [],
  subtotal: 0,
  discount: 0,
  total: 0,
  status: 'pending',
  payment_status: 'pending',
  payment_method: 'cash',
  notes: '',
  created_at: null,
  updated_at: null,
  refund_amount: null,
  refund_reason: null,
  refunded_at: null
});

const loading = ref(true);
const showRefundModal = ref(false);
const showPrintModal = ref(false);
const refundLoading = ref(false);

// Form hoàn tiền
const refundForm = reactive({
  refund_amount: 0,
  refund_reason: '',
  restore_stock: true
});

// Tải dữ liệu khi component mounted
onMounted(async () => {
  await loadOrderDetail();
  
  // Kiểm tra xem có yêu cầu in đơn hàng không
  if (route.query.print === 'true') {
    showPrintModal.value = true;
  }
});

// Tải chi tiết đơn hàng
const loadOrderDetail = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/orders/${orderId}`);
    order.value = response.data;
    
    // Chuẩn bị dữ liệu cho form hoàn tiền
    refundForm.refund_amount = order.value.total;
  } catch (error) {
    console.error('Lỗi khi tải chi tiết đơn hàng:', error);
    message.error('Không thể tải chi tiết đơn hàng');
    router.push('/orders');
  } finally {
    loading.value = false;
  }
};

// Xử lý hoàn tiền
const handleRefund = async () => {
  if (!refundForm.refund_amount || refundForm.refund_amount <= 0) {
    message.error('Vui lòng nhập số tiền hoàn lại hợp lệ');
    return;
  }
  
  if (!refundForm.refund_reason) {
    message.error('Vui lòng nhập lý do hoàn tiền');
    return;
  }
  
  try {
    refundLoading.value = true;
    
    await axios.post(`/api/orders/${order.value.id}/refund`, {
      refund_amount: refundForm.refund_amount,
      refund_reason: refundForm.refund_reason,
      restore_stock: refundForm.restore_stock
    });
    
    message.success('Hoàn tiền đơn hàng thành công');
    showRefundModal.value = false;
    
    // Làm mới dữ liệu
    await loadOrderDetail();
    
  } catch (error) {
    console.error('Lỗi khi hoàn tiền đơn hàng:', error);
    message.error('Không thể hoàn tiền đơn hàng: ' + (error.response?.data?.message || 'Lỗi không xác định'));
  } finally {
    refundLoading.value = false;
  }
};

// In đơn hàng
const printOrder = () => {
  showPrintModal.value = true;
};

// Xử lý in
const handlePrint = () => {
  // CSS để ẩn các phần khác khi in
  const style = document.createElement('style');
  style.innerHTML = `
    @media print {
      body * {
        visibility: hidden;
      }
      #print-content, #print-content * {
        visibility: visible;
      }
      #print-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
      }
    }
  `;
  document.head.appendChild(style);
  
  window.print();
  
  // Xóa style sau khi in
  document.head.removeChild(style);
};

// Format tiền tệ
const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return '0 ₫';
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0
  }).format(amount);
};

// Format ngày
const formatDate = (dateString) => {
  if (!dateString) return '';
  return dayjs(dateString).format('DD/MM/YYYY');
};

// Format ngày giờ
const formatDateTime = (dateString) => {
  if (!dateString) return '';
  return dayjs(dateString).format('DD/MM/YYYY HH:mm');
};

// Text hiển thị trạng thái đơn hàng
const getOrderStatusText = (status) => {
  const statusMap = {
    'pending': 'Đang xử lý',
    'processing': 'Đang xử lý',
    'completed': 'Hoàn thành',
    'cancelled': 'Đã hủy'
  };
  
  return statusMap[status] || status;
};

// Màu cho trạng thái đơn hàng
const getOrderStatusColor = (status) => {
  const statusColorMap = {
    'pending': 'orange',
    'processing': 'blue',
    'completed': 'green',
    'cancelled': 'red'
  };
  
  return statusColorMap[status] || 'default';
};

// Text hiển thị trạng thái thanh toán
const getPaymentStatusText = (status) => {
  const statusMap = {
    'pending': 'Chờ thanh toán',
    'completed': 'Đã thanh toán',
    'refunded': 'Đã hoàn tiền'
  };
  
  return statusMap[status] || status;
};

// Màu cho trạng thái thanh toán
const getPaymentStatusColor = (status) => {
  const statusColorMap = {
    'pending': 'orange',
    'completed': 'green',
    'refunded': 'purple'
  };
  
  return statusColorMap[status] || 'default';
};

// Text hiển thị phương thức thanh toán
const getPaymentMethodText = (method) => {
  const methodMap = {
    'cash': 'Tiền mặt',
    'card': 'Thẻ',
    'transfer': 'Chuyển khoản'
  };
  
  return methodMap[method] || method;
};
</script>

<style>
/* CSS cho in ấn */
@media print {
  body * {
    visibility: hidden;
  }
  #print-content, #print-content * {
    visibility: visible;
  }
  #print-content {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}
</style> 