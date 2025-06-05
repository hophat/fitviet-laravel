<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Header và các nút điều hướng -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold">Quản lý kho hàng</h1>
          <p class="text-muted-foreground">Kiểm tra tồn kho và điều chỉnh số lượng sản phẩm</p>
        </div>
        
        <a-button type="primary" @click="showBulkUpdateModal = true">
          <template #icon><ReloadOutlined /></template>
          Cập nhật hàng loạt
        </a-button>
      </div>
      
      <!-- Các bộ lọc và tìm kiếm -->
      <a-card class="mb-6">
        <a-row :gutter="16">
          <a-col :span="8">
            <a-input-search
              v-model:value="searchQuery"
              placeholder="Tìm kiếm sản phẩm..."
              enter-button
              @search="loadProducts"
            />
          </a-col>
          
          <a-col :span="8">
            <a-select
              v-model:value="filterCategory"
              style="width: 100%"
              placeholder="Lọc theo danh mục"
              allow-clear
              @change="loadProducts"
            >
              <a-select-option :value="null">Tất cả danh mục</a-select-option>
              <a-select-option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </a-select-option>
            </a-select>
          </a-col>
          
          <a-col :span="8">
            <a-select
              v-model:value="filterStock"
              style="width: 100%"
              placeholder="Lọc theo tồn kho"
              @change="loadProducts"
            >
              <a-select-option value="all">Tất cả sản phẩm</a-select-option>
              <a-select-option value="low">Sản phẩm sắp hết (≤ 10)</a-select-option>
              <a-select-option value="out">Sản phẩm hết hàng</a-select-option>
            </a-select>
          </a-col>
        </a-row>
      </a-card>
      
      <!-- Danh sách sản phẩm -->
      <a-card title="Danh sách sản phẩm">
        <template #extra>
          <router-link to="/products/create">
            <a-button type="primary">
              <template #icon><PlusOutlined /></template>
              Thêm sản phẩm
            </a-button>
          </router-link>
        </template>
        
        <a-table
          :columns="columns"
          :data-source="products"
          :loading="loading"
          :pagination="pagination"
          @change="handleTableChange"
          :row-key="record => record.id"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'category'">
              {{ getCategoryName(record.category_id) }}
            </template>
            
            <template v-else-if="column.key === 'price'">
              {{ formatCurrency(record.price) }}
            </template>
            
            <template v-else-if="column.key === 'stock_quantity'">
              <a-statistic
                :value="record.stock_quantity ?? 0"
                :value-style="getStockStyle(record.stock_quantity)"
              />
            </template>
            
            <template v-else-if="column.key === 'actions'">
              <a-space>
                <a-button size="small" @click="showUpdateModal(record)">
                  <template #icon><EditOutlined /></template>
                  Cập nhật
                </a-button>
                <router-link :to="`/products/${record.id}/edit`">
                  <a-button size="small">
                    <template #icon><SettingOutlined /></template>
                    Chi tiết
                  </a-button>
                </router-link>
              </a-space>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
    
    <!-- Modal cập nhật tồn kho -->
    <a-modal
      v-model:visible="showUpdateStockModal"
      :title="`Cập nhật tồn kho: ${selectedProduct?.name || ''}`"
      @ok="updateStock"
      :confirmLoading="updateLoading"
    >
      <a-form layout="vertical">
        <a-form-item label="Thao tác">
          <a-radio-group v-model:value="stockForm.operation">
            <a-radio-button value="add">Thêm (+)</a-radio-button>
            <a-radio-button value="subtract">Trừ (-)</a-radio-button>
            <a-radio-button value="set">Đặt lại</a-radio-button>
          </a-radio-group>
        </a-form-item>
        
        <a-form-item label="Số lượng">
          <a-input-number v-model:value="stockForm.quantity" :min="1" style="width: 100%" />
        </a-form-item>
        
        <a-form-item label="Ghi chú">
          <a-textarea v-model:value="stockForm.note" placeholder="Nhập lý do điều chỉnh" :rows="3" />
        </a-form-item>
      </a-form>
      
      <div class="mt-4 p-3 bg-gray-100 rounded-md">
        <div class="flex justify-between">
          <span>Số lượng hiện tại:</span>
          <span class="font-medium">{{ selectedProduct?.stock_quantity ?? 0 }}</span>
        </div>
        <div class="flex justify-between mt-2">
          <span>Số lượng sau điều chỉnh:</span>
          <span class="font-bold" :class="{'text-red-500': getEstimatedStock() < 0, 'text-green-500': getEstimatedStock() > 0}">
            {{ getEstimatedStock() }}
          </span>
        </div>
      </div>
    </a-modal>
    
    <!-- Modal cập nhật hàng loạt -->
    <a-modal
      v-model:visible="showBulkUpdateModal" 
      title="Cập nhật hàng loạt"
      @ok="bulkUpdateStock"
      :confirmLoading="bulkUpdateLoading"
      width="700px"
    >
      <a-form layout="vertical">
        <a-form-item label="Chọn danh mục">
          <a-select
            v-model:value="bulkForm.category_id"
            style="width: 100%"
            placeholder="Chọn danh mục"
            @change="loadProductsForBulk"
          >
            <a-select-option :value="null">Tất cả danh mục</a-select-option>
            <a-select-option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </a-select-option>
          </a-select>
        </a-form-item>
        
        <a-form-item label="Thao tác">
          <a-radio-group v-model:value="bulkForm.operation">
            <a-radio-button value="add">Thêm (+)</a-radio-button>
            <a-radio-button value="subtract">Trừ (-)</a-radio-button>
            <a-radio-button value="set">Đặt lại</a-radio-button>
          </a-radio-group>
        </a-form-item>
        
        <a-form-item label="Số lượng">
          <a-input-number v-model:value="bulkForm.quantity" :min="1" style="width: 100%" />
        </a-form-item>
        
        <a-form-item label="Ghi chú">
          <a-textarea v-model:value="bulkForm.note" placeholder="Nhập lý do điều chỉnh" :rows="2" />
        </a-form-item>
        
        <a-form-item label="Sản phẩm áp dụng">
          <a-table
            :columns="bulkColumns"
            :data-source="bulkProducts"
            :pagination="{ pageSize: 5 }"
            size="small"
            :row-key="record => record.id"
          >
            <template #bodyCell="{ column, record }">
              <template v-if="column.key === 'select'">
                <a-checkbox
                  v-model:checked="record.selected"
                  @change="() => record.selected = !record.selected"
                />
              </template>
              
              <template v-else-if="column.key === 'stock_quantity'">
                {{ record.stock_quantity ?? 0 }}
              </template>
              
              <template v-else-if="column.key === 'new_quantity'">
                <span :class="{'text-red-500': getNewQuantity(record) < 0}">
                  {{ getNewQuantity(record) }}
                </span>
              </template>
            </template>
          </a-table>
        </a-form-item>
      </a-form>
    </a-modal>
  </MainLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { message } from 'ant-design-vue';
import axios from 'axios';
import MainLayout from '../../layouts/MainLayout.vue';
import { 
  PlusOutlined, 
  ReloadOutlined, 
  EditOutlined, 
  SettingOutlined 
} from '@ant-design/icons-vue';

// State
const products = ref([]);
const categories = ref([]);
const loading = ref(false);
const pagination = ref({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  pageSizeOptions: ['10', '20', '50', '100']
});

// Filters
const searchQuery = ref('');
const filterCategory = ref(null);
const filterStock = ref('all');

// Modal state
const showUpdateStockModal = ref(false);
const showBulkUpdateModal = ref(false);
const updateLoading = ref(false);
const bulkUpdateLoading = ref(false);
const selectedProduct = ref(null);

// Forms
const stockForm = ref({
  operation: 'add',
  quantity: 1,
  note: ''
});

const bulkForm = ref({
  category_id: null,
  operation: 'add',
  quantity: 1,
  note: ''
});

const bulkProducts = ref([]);

// Table columns
const columns = [
  { title: 'Mã SP', dataIndex: 'sku', key: 'sku', width: '10%' },
  { title: 'Tên sản phẩm', dataIndex: 'name', key: 'name', width: '30%' },
  { title: 'Danh mục', dataIndex: 'category_id', key: 'category', width: '15%' },
  { title: 'Giá', dataIndex: 'price', key: 'price', width: '15%' },
  { title: 'Tồn kho', dataIndex: 'stock_quantity', key: 'stock_quantity', width: '15%' },
  { title: 'Thao tác', key: 'actions', width: '15%' }
];

const bulkColumns = [
  { title: 'Chọn', key: 'select', width: '10%' },
  { title: 'Tên sản phẩm', dataIndex: 'name', key: 'name', width: '40%' },
  { title: 'Tồn kho hiện tại', dataIndex: 'stock_quantity', key: 'stock_quantity', width: '25%' },
  { title: 'Tồn kho mới', key: 'new_quantity', width: '25%' }
];

// Load data
onMounted(async () => {
  await Promise.all([
    loadCategories(),
    loadProducts()
  ]);
});

// Load categories
const loadCategories = async () => {
  try {
    const response = await axios.get('/api/products/categories');
    categories.value = response.data;
  } catch (error) {
    console.error('Lỗi khi tải danh mục:', error);
    message.error('Không thể tải danh mục sản phẩm');
  }
};

// Load products with filters
const loadProducts = async (page = 1) => {
  try {
    loading.value = true;
    
    const params = {
      page,
      per_page: pagination.value.pageSize,
      sort: 'name',
      order: 'asc'
    };
    
    if (searchQuery.value) {
      params.search = searchQuery.value;
    }
    
    if (filterCategory.value) {
      params.category_id = filterCategory.value;
    }
    
    // Áp dụng lọc tồn kho
    if (filterStock.value === 'low') {
      params.low_stock = true;
    } else if (filterStock.value === 'out') {
      params.out_of_stock = true;
    }
    
    const response = await axios.get('/api/products', { params });
    
    products.value = response.data.data;
    pagination.value.total = response.data.total;
    pagination.value.current = response.data.current_page;
    
  } catch (error) {
    console.error('Lỗi khi tải sản phẩm:', error);
    message.error('Không thể tải danh sách sản phẩm');
  } finally {
    loading.value = false;
  }
};

// Load products for bulk update
const loadProductsForBulk = async () => {
  try {
    const params = {
      per_page: 100
    };
    
    if (bulkForm.value.category_id) {
      params.category_id = bulkForm.value.category_id;
    }
    
    const response = await axios.get('/api/products', { params });
    
    bulkProducts.value = response.data.data.map(product => ({
      ...product,
      selected: true
    }));
    
  } catch (error) {
    console.error('Lỗi khi tải sản phẩm cho cập nhật hàng loạt:', error);
    message.error('Không thể tải danh sách sản phẩm');
  }
};

// Table pagination
const handleTableChange = (pag) => {
  pagination.value.current = pag.current;
  pagination.value.pageSize = pag.pageSize;
  loadProducts(pag.current);
};

// Get category name by ID
const getCategoryName = (categoryId) => {
  const category = categories.value.find(c => c.id === categoryId);
  return category ? category.name : '-';
};

// Get stock style based on quantity
const getStockStyle = (quantity) => {
  if (quantity === null || quantity === undefined) return {};
  
  if (quantity <= 0) {
    return { color: '#f5222d' }; // Đỏ - hết hàng
  } else if (quantity <= 10) {
    return { color: '#fa8c16' }; // Cam - sắp hết
  } else {
    return { color: '#52c41a' }; // Xanh - còn hàng
  }
};

// Format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0
  }).format(amount);
};

// Show update modal for a product
const showUpdateModal = (product) => {
  selectedProduct.value = product;
  stockForm.value = {
    operation: 'add',
    quantity: 1,
    note: ''
  };
  showUpdateStockModal.value = true;
};

// Estimated stock after operation
const getEstimatedStock = () => {
  if (!selectedProduct.value) return 0;
  
  const currentStock = selectedProduct.value.stock_quantity || 0;
  const quantity = stockForm.value.quantity || 0;
  
  switch (stockForm.value.operation) {
    case 'add':
      return currentStock + quantity;
    case 'subtract':
      return Math.max(0, currentStock - quantity);
    case 'set':
      return quantity;
    default:
      return currentStock;
  }
};

// Get new quantity for bulk update
const getNewQuantity = (product) => {
  const currentStock = product.stock_quantity || 0;
  const quantity = bulkForm.value.quantity || 0;
  
  switch (bulkForm.value.operation) {
    case 'add':
      return currentStock + quantity;
    case 'subtract':
      return Math.max(0, currentStock - quantity);
    case 'set':
      return quantity;
    default:
      return currentStock;
  }
};

// Update stock for a single product
const updateStock = async () => {
  if (!selectedProduct.value) return;
  
  try {
    updateLoading.value = true;
    
    await axios.post(`/api/products/${selectedProduct.value.id}/stock`, {
      operation: stockForm.value.operation,
      quantity: stockForm.value.quantity
    });
    
    message.success('Đã cập nhật tồn kho thành công');
    showUpdateStockModal.value = false;
    
    // Reload products
    loadProducts(pagination.value.current);
    
  } catch (error) {
    console.error('Lỗi khi cập nhật tồn kho:', error);
    message.error('Không thể cập nhật tồn kho');
  } finally {
    updateLoading.value = false;
  }
};

// Bulk update stock
const bulkUpdateStock = async () => {
  const selectedProducts = bulkProducts.value.filter(p => p.selected);
  
  if (selectedProducts.length === 0) {
    message.warning('Vui lòng chọn ít nhất một sản phẩm');
    return;
  }
  
  try {
    bulkUpdateLoading.value = true;
    
    // Tạo các promises cho mỗi cập nhật sản phẩm
    const updatePromises = selectedProducts.map(product => 
      axios.post(`/api/products/${product.id}/stock`, {
        operation: bulkForm.value.operation,
        quantity: bulkForm.value.quantity
      })
    );
    
    // Thực hiện song song
    await Promise.all(updatePromises);
    
    message.success(`Đã cập nhật tồn kho cho ${selectedProducts.length} sản phẩm`);
    showBulkUpdateModal.value = false;
    
    // Reload products
    loadProducts(pagination.value.current);
    
  } catch (error) {
    console.error('Lỗi khi cập nhật hàng loạt:', error);
    message.error('Không thể cập nhật tồn kho hàng loạt');
  } finally {
    bulkUpdateLoading.value = false;
  }
};
</script> 