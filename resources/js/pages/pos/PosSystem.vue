<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Header và thông tin đơn hàng -->
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-3 lg:space-y-0 mb-6">
        <div>
          <h1 class="text-2xl font-bold">Hệ thống POS</h1>
          <p class="text-muted-foreground">Bán hàng và dịch vụ tại quầy</p>
        </div>
        
        <div class="flex items-center space-x-4">
          <div class="bg-muted/30 px-4 py-2 rounded-md">
            <span class="text-sm text-muted-foreground">Đơn hàng hôm nay:</span>
            <span class="font-medium ml-1">{{ orderStats.today }}</span>
          </div>
          <div class="bg-muted/30 px-4 py-2 rounded-md">
            <span class="text-sm text-muted-foreground">Doanh thu hôm nay:</span>
            <span class="font-medium ml-1">{{ formatCurrency(orderStats.revenue) }}</span>
          </div>
        </div>
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Danh sách sản phẩm -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Bộ lọc sản phẩm -->
          <div class="bg-white p-4 rounded-lg shadow">
            <div class="flex flex-wrap gap-2 mb-3">
              <a-button 
                v-for="category in categories" 
                :key="category.id"
                @click="selectedCategory = category.id"
                :type="selectedCategory === category.id ? 'primary' : 'default'"
              >
                {{ category.name }}
              </a-button>
              <a-button 
                @click="selectedCategory = null"
                :type="selectedCategory === null ? 'primary' : 'default'"
              >
                Tất cả
              </a-button>
            </div>
            
            <div class="relative">
              <a-input 
                v-model:value="searchQuery" 
                placeholder="Tìm kiếm sản phẩm..." 
                allow-clear
              >
                <template #prefix>
                  <SearchOutlined />
                </template>
              </a-input>
            </div>
          </div>
          
          <!-- Danh sách sản phẩm -->
          <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-lg font-medium mb-4">Sản phẩm</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
              <a-card 
                v-for="(product, index) in filteredProducts" 
                :key="index"
                hoverable
                class="cursor-pointer"
                @click="addItemToCart(product)"
              >
                <template #cover>
                  <div class="h-32 bg-muted/30 rounded-md flex items-center justify-center">
                    <span class="text-3xl">{{ product.emoji }}</span>
                  </div>
                </template>
                <a-card-meta :title="product.name">
                  <template #description>
                    <div class="flex justify-between items-center mt-2">
                      <span class="text-primary font-bold">{{ formatCurrency(product.price) }}</span>
                      <a-button type="primary" size="small">+ Thêm</a-button>
                    </div>
                  </template>
                </a-card-meta>
              </a-card>
            </div>
          </div>
        </div>
        
        <!-- Giỏ hàng và thanh toán -->
        <div class="space-y-4">
          <a-card title="Đơn hàng hiện tại">
            <template #extra>
              <a-button type="link" @click="clearCart" danger>Xóa tất cả</a-button>
            </template>
            
            <!-- Khách hàng -->
            <div class="border-b pb-3 mb-3">
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium">Khách hàng</span>
                <a-button type="link" @click="showMemberModal = true">
                  <template #icon><UserAddOutlined /></template>
                  Chọn hội viên
                </a-button>
              </div>
              <div v-if="customer" class="flex items-center">
                <a-avatar class="mr-2">{{ customer.name.charAt(0) }}</a-avatar>
                <div>
                  <div class="text-sm font-medium">{{ customer.name }}</div>
                  <div class="text-xs text-muted-foreground">{{ customer.membership }}</div>
                </div>
              </div>
              <div v-else class="text-sm text-muted-foreground">
                Khách vãng lai
              </div>
            </div>
            
            <!-- Sản phẩm trong giỏ -->
            <div v-if="cart.length === 0" class="text-center py-8 text-muted-foreground">
              <InboxOutlined style="font-size: 32px" />
              <p class="mt-2">Giỏ hàng trống</p>
            </div>
            <a-list v-else class="cart-list">
              <a-list-item v-for="(item, index) in cart" :key="index">
                <div class="w-full flex items-center justify-between">
                  <div class="flex items-center">
                    <span class="text-xl mr-2">{{ item.emoji }}</span>
                    <div>
                      <p class="text-sm font-medium">{{ item.name }}</p>
                      <p class="text-xs text-muted-foreground">{{ formatCurrency(item.price) }} x {{ item.quantity }}</p>
                    </div>
                  </div>
                  <a-space>
                    <a-button-group>
                      <a-button @click="decreaseQuantity(index)">
                        <template #icon><MinusOutlined /></template>
                      </a-button>
                      <a-button disabled>{{ item.quantity }}</a-button>
                      <a-button @click="increaseQuantity(index)">
                        <template #icon><PlusOutlined /></template>
                      </a-button>
                    </a-button-group>
                  </a-space>
                </div>
              </a-list-item>
            </a-list>
            
            <!-- Tổng tiền và thanh toán -->
            <div v-if="cart.length > 0" class="space-y-3 mt-4 pt-3 border-t">
              <!-- Tổng số lượng -->
              <div class="flex justify-between text-sm">
                <span>Số lượng:</span>
                <span>{{ totalQuantity }} sản phẩm</span>
              </div>
              
              <!-- Tổng tiền hàng -->
              <div class="flex justify-between text-sm">
                <span>Tạm tính:</span>
                <span>{{ formatCurrency(subtotal) }}</span>
              </div>
              
              <!-- Giảm giá -->
              <div class="flex justify-between text-sm">
                <span>Giảm giá:</span>
                <div class="flex items-center">
                  <span>{{ formatCurrency(discount) }}</span>
                  <a-button type="link" size="small" @click="showDiscountModal = true">
                    <template #icon><PlusOutlined /></template>
                  </a-button>
                </div>
              </div>
              
              <!-- Thành tiền -->
              <div class="flex justify-between font-bold text-lg pt-2 border-t">
                <span>Tổng cộng:</span>
                <span>{{ formatCurrency(total) }}</span>
              </div>
              
              <!-- Các phương thức thanh toán -->
              <a-radio-group v-model:value="paymentMethod" class="w-full flex justify-between mt-3">
                <a-radio-button value="cash">
                  <DollarOutlined /> Tiền mặt
                </a-radio-button>
                <a-radio-button value="card">
                  <CreditCardOutlined /> Thẻ
                </a-radio-button>
                <a-radio-button value="transfer">
                  <BankOutlined /> Chuyển khoản
                </a-radio-button>
              </a-radio-group>
              
              <!-- Nút thanh toán -->
              <a-button 
                type="primary" 
                size="large"
                block
                @click="showPaymentModal = true"
              >
                <template #icon><ShoppingCartOutlined /></template>
                Thanh toán
              </a-button>
            </div>
          </a-card>
          
          <!-- Đơn hàng gần đây -->
          <a-card title="Đơn hàng gần đây">
            <a-list item-layout="horizontal" :data-source="recentOrders">
              <template #renderItem="{ item }">
                <a-list-item>
                  <div class="w-full flex justify-between">
                    <div>
                      <p class="text-sm font-medium">#{{ item.id }}</p>
                      <p class="text-xs text-muted-foreground">{{ item.time }}</p>
                    </div>
                    <div class="text-right">
                      <p class="font-medium">{{ formatCurrency(item.total) }}</p>
                      <p class="text-xs text-muted-foreground">{{ item.items }} sản phẩm</p>
                    </div>
                  </div>
                </a-list-item>
              </template>
            </a-list>
          </a-card>
        </div>
      </div>
    </div>
    
    <!-- Modal Chọn Hội Viên -->
    <a-modal 
      v-model:visible="showMemberModal" 
      title="Chọn hội viên"
      width="700px"
      :footer="null"
    >
      <a-input-search 
        v-model:value="memberSearchQuery" 
        placeholder="Tìm kiếm hội viên..." 
        enter-button 
        allow-clear
        @search="searchMembers"
      />
      
      <a-table 
        :columns="memberColumns" 
        :data-source="filteredMembers"
        :pagination="{ pageSize: 5 }"
        :rowKey="record => record.id"
        class="mt-4"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'action'">
            <a-button type="primary" size="small" @click="selectMember(record)">
              Chọn
            </a-button>
          </template>
        </template>
      </a-table>
    </a-modal>
    
    <!-- Modal Giảm Giá -->
    <a-modal
      v-model:visible="showDiscountModal"
      title="Áp dụng giảm giá"
      :footer="null"
    >
      <a-form layout="vertical">
        <a-form-item label="Loại giảm giá">
          <a-radio-group v-model:value="discountType">
            <a-radio value="percent">Theo phần trăm (%)</a-radio>
            <a-radio value="amount">Theo số tiền</a-radio>
          </a-radio-group>
        </a-form-item>
        
        <a-form-item label="Giá trị">
          <a-input-number
            v-if="discountType === 'percent'"
            v-model:value="discountValue"
            :min="0"
            :max="100"
            style="width: 100%"
          />
          <a-input-number
            v-else
            v-model:value="discountValue"
            :min="0"
            :max="subtotal"
            style="width: 100%"
            :formatter="value => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
            :parser="value => value.replace(/\$\s?|(,*)/g, '')"
          />
        </a-form-item>
        
        <a-form-item label="Lý do giảm giá">
          <a-input v-model:value="discountReason" placeholder="Nhập lý do giảm giá" />
        </a-form-item>
        
        <div class="flex justify-end">
          <a-space>
            <a-button @click="showDiscountModal = false">Huỷ</a-button>
            <a-button type="primary" @click="applyDiscount">Áp dụng</a-button>
          </a-space>
        </div>
      </a-form>
    </a-modal>
    
    <!-- Modal Thanh Toán -->
    <a-modal
      v-model:visible="showPaymentModal"
      title="Xác nhận thanh toán"
      :footer="null"
      width="600px"
    >
      <div class="space-y-4">
        <div class="p-4 bg-muted/20 rounded">
          <div class="mb-3">
            <h3 class="font-medium">Thông tin khách hàng</h3>
            <p>{{ customer ? customer.name : 'Khách vãng lai' }}</p>
          </div>
          
          <h3 class="font-medium mb-2">Sản phẩm</h3>
          <a-table 
            :columns="paymentItemColumns" 
            :data-source="cart" 
            :pagination="false"
            size="small"
          />
          
          <div class="flex flex-col items-end mt-4 space-y-2">
            <div class="flex justify-between w-1/2">
              <span>Tạm tính:</span>
              <span>{{ formatCurrency(subtotal) }}</span>
            </div>
            <div class="flex justify-between w-1/2">
              <span>Giảm giá:</span>
              <span>{{ formatCurrency(discount) }}</span>
            </div>
            <div class="flex justify-between w-1/2 font-bold">
              <span>Tổng cộng:</span>
              <span>{{ formatCurrency(total) }}</span>
            </div>
          </div>
        </div>
        
        <a-divider />
        
        <div class="flex justify-between items-center">
          <div>
            <h3 class="font-medium">Phương thức thanh toán</h3>
            <p>{{ getPaymentMethodText(paymentMethod) }}</p>
          </div>
          
          <a-input-number
            v-if="paymentMethod === 'cash'"
            v-model:value="cashReceived"
            :min="total"
            style="width: 200px"
            :formatter="value => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
            :parser="value => value.replace(/\$\s?|(,*)/g, '')"
            placeholder="Số tiền nhận"
          />
        </div>
        
        <div v-if="paymentMethod === 'cash' && cashReceived >= total" class="flex justify-between items-center bg-muted/20 p-3 rounded">
          <span>Tiền thừa:</span>
          <span class="font-bold">{{ formatCurrency(cashReceived - total) }}</span>
        </div>
        
        <div v-if="paymentMethod === 'card'" class="space-y-2">
          <a-form layout="vertical">
            <a-form-item label="Số thẻ">
              <a-input placeholder="XXXX-XXXX-XXXX-XXXX" />
            </a-form-item>
            <a-row :gutter="16">
              <a-col :span="12">
                <a-form-item label="Ngày hết hạn">
                  <a-input placeholder="MM/YY" />
                </a-form-item>
              </a-col>
              <a-col :span="12">
                <a-form-item label="CVV">
                  <a-input placeholder="XXX" />
                </a-form-item>
              </a-col>
            </a-row>
          </a-form>
        </div>
        
        <div v-if="paymentMethod === 'transfer'" class="text-center p-4 border rounded">
          <p class="font-medium">Thông tin chuyển khoản</p>
          <p>Ngân hàng: VietComBank</p>
          <p>Số tài khoản: 1234567890</p>
          <p>Tên tài khoản: CÔNG TY TNHH FITVIET</p>
          <p>Nội dung: {{ getTransferContent() }}</p>
        </div>
        
        <div class="flex justify-end">
          <a-space>
            <a-button @click="showPaymentModal = false">Huỷ</a-button>
            <a-button 
              type="primary" 
              @click="processPayment"
              :disabled="!isPaymentValid"
            >
              Hoàn tất thanh toán
            </a-button>
          </a-space>
        </div>
      </div>
    </a-modal>
    
    <!-- Modal Hoá Đơn -->
    <a-modal
      v-model:visible="showReceiptModal"
      title="Hoá đơn"
      width="400px"
      :footer="null"
    >
      <div id="receipt-content">
        <div class="text-center mb-4">
          <h2 class="text-lg font-bold">FIT VIET GYM</h2>
          <p>Số 123 Đường Nguyễn Huệ, Quận 1, TP.HCM</p>
          <p>Tel: 0123.456.789</p>
          <h3 class="text-lg font-bold mt-3">HOÁ ĐƠN BÁN HÀNG</h3>
          <p class="text-sm">Số HĐ: {{ currentOrderId }}</p>
          <p class="text-sm">Ngày: {{ getCurrentDate() }}</p>
        </div>
        
        <div class="mb-3">
          <p><span class="font-medium">Khách hàng:</span> {{ customer ? customer.name : 'Khách vãng lai' }}</p>
          <p v-if="customer"><span class="font-medium">Mã hội viên:</span> {{ customer.id }}</p>
        </div>
        
        <a-divider />
        
        <table class="w-full mb-4">
          <thead>
            <tr class="border-b text-left">
              <th class="pb-1">Sản phẩm</th>
              <th class="pb-1">SL</th>
              <th class="pb-1 text-right">Đơn giá</th>
              <th class="pb-1 text-right">Thành tiền</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in cart" :key="index" class="border-b">
              <td class="py-1">{{ item.name }}</td>
              <td class="py-1">{{ item.quantity }}</td>
              <td class="py-1 text-right">{{ formatCurrency(item.price) }}</td>
              <td class="py-1 text-right">{{ formatCurrency(item.price * item.quantity) }}</td>
            </tr>
          </tbody>
        </table>
        
        <div class="flex flex-col items-end space-y-1 mb-4">
          <div class="flex justify-between w-2/3">
            <span>Tạm tính:</span>
            <span>{{ formatCurrency(subtotal) }}</span>
          </div>
          <div class="flex justify-between w-2/3">
            <span>Giảm giá:</span>
            <span>{{ formatCurrency(discount) }}</span>
          </div>
          <div class="flex justify-between w-2/3 font-bold">
            <span>Tổng cộng:</span>
            <span>{{ formatCurrency(total) }}</span>
          </div>
        </div>
        
        <div class="mb-3">
          <p><span class="font-medium">Phương thức thanh toán:</span> {{ getPaymentMethodText(paymentMethod) }}</p>
          <p v-if="paymentMethod === 'cash'">
            <span class="font-medium">Tiền nhận:</span> {{ formatCurrency(cashReceived) }} - 
            <span class="font-medium">Tiền thừa:</span> {{ formatCurrency(cashReceived - total) }}
          </p>
        </div>
        
        <div class="text-center mt-6">
          <p>Xin cảm ơn Quý khách đã mua hàng!</p>
          <p>------------------------------</p>
        </div>
      </div>
      
      <div class="flex justify-center mt-4 hide-on-print">
        <a-space>
          <a-button @click="showReceiptModal = false">Đóng</a-button>
          <a-button type="primary" @click="printReceipt">In hoá đơn</a-button>
        </a-space>
      </div>
    </a-modal>
  </MainLayout>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import { message } from 'ant-design-vue';
import axios from 'axios';
import MainLayout from '../../layouts/MainLayout.vue';
import {
  SearchOutlined,
  PlusOutlined,
  MinusOutlined,
  UserAddOutlined,
  InboxOutlined,
  ShoppingCartOutlined,
  DollarOutlined,
  CreditCardOutlined,
  BankOutlined
} from '@ant-design/icons-vue';

// Modals và form state
const showMemberModal = ref(false);
const showDiscountModal = ref(false);
const showPaymentModal = ref(false);
const showReceiptModal = ref(false);

// Data cho modal chọn hội viên
const memberSearchQuery = ref('');
const members = ref([]);
const isLoadingMembers = ref(false);

// Lọc từ API
const filteredMembers = ref([]);

// Tải dữ liệu hội viên khi mở modal
const loadMembers = async (search = '') => {
  try {
    isLoadingMembers.value = true;
    const response = await axios.get('/api/members', {
      params: { search, per_page: 20 }
    });
    members.value = response.data.data;
    filteredMembers.value = response.data.data;
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu hội viên:', error);
    message.error('Không thể tải danh sách hội viên');
  } finally {
    isLoadingMembers.value = false;
  }
};

// Tìm kiếm hội viên
const searchMembers = async () => {
  await loadMembers(memberSearchQuery.value);
};

// Data cho các form
const discountType = ref('percent'); // 'percent' hoặc 'amount'
const discountValue = ref(0);
const discountReason = ref('');
const paymentMethod = ref('cash');
const cashReceived = ref(0);
const currentOrderId = ref('');

// Columns cho bảng sản phẩm trong modal thanh toán
const paymentItemColumns = [
  { title: 'Sản phẩm', dataIndex: 'name', key: 'name' },
  { title: 'Đơn giá', dataIndex: 'price', key: 'price', 
    customRender: ({ text }) => formatCurrency(text) },
  { title: 'SL', dataIndex: 'quantity', key: 'quantity' },
  { title: 'Thành tiền', key: 'total', 
    customRender: ({ record }) => formatCurrency(record.price * record.quantity) }
];

// Columns cho bảng hội viên
const memberColumns = [
  { title: 'Mã hội viên', dataIndex: 'id', key: 'id' },
  { title: 'Tên hội viên', dataIndex: 'name', key: 'name' },
  { title: 'Số điện thoại', dataIndex: 'phone', key: 'phone' },
  { title: 'Gói thành viên', key: 'membership', 
    customRender: ({ record }) => record.membership ? record.membership.name : 'Không có' },
  { title: 'Thao tác', key: 'action' }
];

// Trạng thái và data chính
const categories = ref([]);
const products = ref([]);
const isLoadingProducts = ref(false);
const isLoadingCategories = ref(false);

// Tìm kiếm và lọc
const searchQuery = ref('');
const selectedCategory = ref(null);

// Giỏ hàng
const cart = ref([]);
const discount = ref(0);

// Khách hàng
const customer = ref(null); // Khách vãng lai mặc định

// Thống kê đơn hàng hôm nay
const orderStats = ref({
  today: 0,
  revenue: 0
});

// Đơn hàng gần đây
const recentOrders = ref([]);
const isLoadingOrders = ref(false);

// Tải dữ liệu khi component mounted
onMounted(async () => {
  await Promise.all([
    loadCategories(),
    loadProducts(),
    loadRecentOrders(),
    loadOrderStats()
  ]);
});

// Tải danh mục sản phẩm
const loadCategories = async () => {
  try {
    isLoadingCategories.value = true;
    const response = await axios.get('/api/products/categories');
    categories.value = response.data;
  } catch (error) {
    console.error('Lỗi khi tải danh mục:', error);
    message.error('Không thể tải danh mục sản phẩm');
  } finally {
    isLoadingCategories.value = false;
  }
};

// Tải sản phẩm
const loadProducts = async () => {
  try {
    isLoadingProducts.value = true;
    const params = {};
    
    if (searchQuery.value) {
      params.search = searchQuery.value;
    }
    
    if (selectedCategory.value) {
      params.category_id = selectedCategory.value;
    }
    
    const response = await axios.get('/api/products', { params });
    products.value = response.data.data.map(product => ({
      ...product,
      emoji: getProductEmoji(product) // Lấy emoji dựa trên loại sản phẩm
    }));
  } catch (error) {
    console.error('Lỗi khi tải sản phẩm:', error);
    message.error('Không thể tải danh sách sản phẩm');
  } finally {
    isLoadingProducts.value = false;
  }
};

// Tải đơn hàng gần đây
const loadRecentOrders = async () => {
  try {
    isLoadingOrders.value = true;
    const response = await axios.get('/api/orders', {
      params: {
        per_page: 5,
        sort: 'created_at',
        order: 'desc'
      }
    });
    
    recentOrders.value = response.data.data.map(order => ({
      id: order.id,
      time: new Date(order.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}),
      total: order.total,
      items: order.items.length
    }));
  } catch (error) {
    console.error('Lỗi khi tải đơn hàng gần đây:', error);
  } finally {
    isLoadingOrders.value = false;
  }
};

// Tải thống kê đơn hàng hôm nay
const loadOrderStats = async () => {
  try {
    const today = new Date().toISOString().split('T')[0];
    const response = await axios.get('/api/orders/report', {
      params: {
        from_date: today,
        to_date: today,
        group_by: 'day'
      }
    });
    
    if (response.data.data.length > 0) {
      const todayStats = response.data.data[0];
      orderStats.value = {
        today: todayStats.order_count,
        revenue: todayStats.total_revenue
      };
    } else {
      orderStats.value = {
        today: 0,
        revenue: 0
      };
    }
  } catch (error) {
    console.error('Lỗi khi tải thống kê:', error);
  }
};

// Lọc sản phẩm theo danh mục và tìm kiếm
const filteredProducts = computed(() => {
  return products.value;
});

// Tính tổng số lượng
const totalQuantity = computed(() => {
  return cart.value.reduce((total, item) => total + item.quantity, 0);
});

// Tính tạm tính
const subtotal = computed(() => {
  return cart.value.reduce((total, item) => total + (item.price * item.quantity), 0);
});

// Tính tổng cộng
const total = computed(() => {
  return subtotal.value - discount.value;
});

// Check điều kiện để có thể thanh toán
const isPaymentValid = computed(() => {
  if (cart.value.length === 0) return false;
  
  if (paymentMethod.value === 'cash') {
    return cashReceived.value >= total.value;
  }
  
  return true;
});

// Lấy emoji cho sản phẩm dựa vào loại
const getProductEmoji = (product) => {
  const categoryMap = {
    1: ['💧', '🥤', '🍵'], // Đồ uống
    2: ['💪', '🔋', '💊'], // Thực phẩm bổ sung
    3: ['🧤', '🔄', '🧩'], // Phụ kiện
    4: ['🏋️', '📅', '📆']  // Dịch vụ
  };
  
  if (product.category_id && categoryMap[product.category_id]) {
    const emojis = categoryMap[product.category_id];
    // Lấy emoji ngẫu nhiên từ danh sách theo category
    return emojis[Math.floor(Math.random() * emojis.length)];
  }
  
  return '📦'; // Emoji mặc định
};

// Thêm sản phẩm vào giỏ hàng
const addItemToCart = (product) => {
  const existingItem = cart.value.find(item => item.id === product.id);
  
  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cart.value.push({
      id: product.id,
      name: product.name,
      price: product.price,
      quantity: 1,
      emoji: product.emoji,
      product_id: product.id
    });
  }
  
  message.success(`Đã thêm ${product.name} vào giỏ hàng`);
};

// Tăng số lượng sản phẩm
const increaseQuantity = (index) => {
  cart.value[index].quantity += 1;
};

// Giảm số lượng sản phẩm
const decreaseQuantity = (index) => {
  if (cart.value[index].quantity > 1) {
    cart.value[index].quantity -= 1;
  } else {
    cart.value.splice(index, 1);
  }
};

// Xóa giỏ hàng
const clearCart = () => {
  cart.value = [];
  discount.value = 0;
  message.success('Đã xóa tất cả sản phẩm khỏi giỏ hàng');
};

// Chọn hội viên
const selectMember = (member) => {
  customer.value = member;
  showMemberModal.value = false;
  message.success(`Đã chọn hội viên: ${member.name}`);
};

// Áp dụng giảm giá
const applyDiscount = () => {
  if (discountType.value === 'percent') {
    discount.value = Math.floor(subtotal.value * (discountValue.value / 100));
  } else {
    discount.value = discountValue.value;
  }
  
  showDiscountModal.value = false;
  message.success(`Đã áp dụng giảm giá: ${formatCurrency(discount.value)}`);
};

// Lấy văn bản cho phương thức thanh toán
const getPaymentMethodText = (method) => {
  const methodMap = {
    'cash': 'Tiền mặt',
    'card': 'Thẻ tín dụng/ghi nợ',
    'transfer': 'Chuyển khoản'
  };
  
  return methodMap[method] || method;
};

// Tạo nội dung chuyển khoản
const getTransferContent = () => {
  const orderId = `FV${new Date().getTime().toString().slice(-6)}`;
  return orderId;
};

// Lấy ngày hiện tại cho hoá đơn
const getCurrentDate = () => {
  return new Date().toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Xử lý thanh toán
const processPayment = async () => {
  try {
    const orderData = {
      member_id: customer.value ? customer.value.id : null,
      discount: discount.value,
      payment_method: paymentMethod.value,
      payment_status: 'completed',
      notes: discountReason.value || '',
      items: cart.value.map(item => ({
        product_id: item.id,
        quantity: item.quantity,
        price: item.price
      }))
    };
    
    const response = await axios.post('/api/orders', orderData);
    
    // Lưu ID đơn hàng
    currentOrderId.value = response.data.id;
    
    // Hiển thị hoá đơn
    showPaymentModal.value = false;
    showReceiptModal.value = true;
    
    // Cập nhật lại dữ liệu
    await Promise.all([
      loadRecentOrders(),
      loadOrderStats()
    ]);
    
    message.success('Thanh toán thành công!');
  } catch (error) {
    console.error('Lỗi khi thanh toán:', error);
    message.error('Có lỗi xảy ra khi thanh toán');
  }
};

// In hoá đơn
const printReceipt = () => {
  window.print();
};

// Format tiền tệ
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0
  }).format(amount);
};
</script>

<style>
/* CSS cho in ấn */
@media print {
  body * {
    visibility: hidden;
  }
  
  .ant-modal-wrap, .ant-modal-wrap * {
    visibility: hidden;
  }
  
  .ant-modal-content {
    box-shadow: none !important;
  }
  
  #receipt-content, #receipt-content * {
    visibility: visible;
  }
  
  #receipt-content {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
  
  .hide-on-print {
    display: none !important;
  }
}

/* CSS cho danh sách giỏ hàng */
.cart-list {
  max-height: 300px;
  overflow-y: auto;
}
</style> 