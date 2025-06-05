<template>
  <MainLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold">{{ isEditing ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' }}</h1>
          <p class="text-muted-foreground">{{ isEditing ? 'Cập nhật thông tin sản phẩm' : 'Tạo sản phẩm mới trong hệ thống' }}</p>
        </div>
        
        <div class="space-x-2">
          <router-link to="/products">
            <a-button>
              <template #icon><ArrowLeftOutlined /></template>
              Quay lại
            </a-button>
          </router-link>
        </div>
      </div>
      
      <a-card>
        <a-spin :spinning="loading" tip="Đang xử lý...">
          <a-form 
            layout="vertical" 
            :model="form" 
            @finish="onSubmit"
          >
            <a-row :gutter="16">
              <a-col :span="16">
                <a-form-item 
                  label="Tên sản phẩm" 
                  name="name" 
                  :rules="[{ required: true, message: 'Vui lòng nhập tên sản phẩm' }]"
                >
                  <a-input v-model:value="form.name" placeholder="Nhập tên sản phẩm" />
                </a-form-item>
              </a-col>
              
              <a-col :span="8">
                <a-form-item 
                  label="Mã sản phẩm (SKU)" 
                  name="sku"
                >
                  <a-input v-model:value="form.sku" placeholder="Nhập mã sản phẩm" />
                </a-form-item>
              </a-col>
            </a-row>
            
            <a-row :gutter="16">
              <a-col :span="12">
                <a-form-item 
                  label="Danh mục" 
                  name="category_id" 
                  :rules="[{ required: true, message: 'Vui lòng chọn danh mục' }]"
                >
                  <a-select 
                    v-model:value="form.category_id" 
                    placeholder="Chọn danh mục"
                  >
                    <a-select-option v-for="category in categories" :key="category.id" :value="category.id">
                      {{ category.name }}
                    </a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              
              <a-col :span="12">
                <a-form-item 
                  label="Giá bán" 
                  name="price" 
                  :rules="[{ required: true, message: 'Vui lòng nhập giá bán' }]"
                >
                  <a-input-number 
                    v-model:value="form.price" 
                    :min="0" 
                    class="w-full"
                    :formatter="value => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                    :parser="value => value.replace(/\$\s?|(,*)/g, '')"
                    placeholder="Nhập giá bán"
                  />
                </a-form-item>
              </a-col>
            </a-row>
            
            <a-row :gutter="16">
              <a-col :span="12">
                <a-form-item 
                  label="Số lượng tồn kho" 
                  name="stock_quantity"
                >
                  <a-input-number 
                    v-model:value="form.stock_quantity" 
                    :min="0" 
                    class="w-full"
                    placeholder="Nhập số lượng tồn kho"
                  />
                </a-form-item>
              </a-col>
              
              <a-col :span="12">
                <a-form-item 
                  label="Hình ảnh URL" 
                  name="image"
                >
                  <a-input v-model:value="form.image" placeholder="Nhập đường dẫn hình ảnh" />
                </a-form-item>
              </a-col>
            </a-row>
            
            <a-form-item 
              label="Mô tả sản phẩm" 
              name="description"
            >
              <a-textarea 
                v-model:value="form.description" 
                placeholder="Nhập mô tả sản phẩm"
                :rows="4" 
              />
            </a-form-item>
            
            <a-form-item>
              <a-space>
                <a-button type="primary" html-type="submit">
                  {{ isEditing ? 'Cập nhật sản phẩm' : 'Tạo sản phẩm' }}
                </a-button>
                <a-button @click="resetForm">Làm mới</a-button>
                <a-popconfirm
                  v-if="isEditing"
                  title="Bạn có chắc chắn muốn xóa sản phẩm này?"
                  ok-text="Xóa"
                  cancel-text="Hủy"
                  @confirm="deleteProduct"
                >
                  <a-button danger>Xóa sản phẩm</a-button>
                </a-popconfirm>
              </a-space>
            </a-form-item>
          </a-form>
        </a-spin>
      </a-card>
      
      <!-- Lịch sử cập nhật tồn kho (chỉ hiển thị khi chỉnh sửa) -->
      <a-card v-if="isEditing" title="Lịch sử cập nhật tồn kho">
        <a-table
          :columns="stockHistoryColumns"
          :data-source="stockHistory"
          :pagination="{ pageSize: 5 }"
          size="small"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'operation'">
              <a-tag :color="getOperationColor(record.operation)">
                {{ getOperationLabel(record.operation) }}
              </a-tag>
            </template>
            
            <template v-else-if="column.key === 'quantity_change'">
              <span :class="{ 'text-red-500': record.quantity_change < 0, 'text-green-500': record.quantity_change > 0 }">
                {{ record.quantity_change > 0 ? '+' : '' }}{{ record.quantity_change }}
              </span>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { message } from 'ant-design-vue';
import MainLayout from '../../layouts/MainLayout.vue';
import { ArrowLeftOutlined } from '@ant-design/icons-vue';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const productId = computed(() => route.params.id);
const isEditing = computed(() => !!productId.value);

// State
const loading = ref(false);
const categories = ref([]);
const stockHistory = ref([]);

// Form
const form = reactive({
  name: '',
  sku: '',
  description: '',
  price: 0,
  category_id: undefined,
  stock_quantity: 0,
  image: ''
});

// Columns for stock history table
const stockHistoryColumns = [
  { title: 'Ngày', dataIndex: 'date', key: 'date' },
  { title: 'Người cập nhật', dataIndex: 'user', key: 'user' },
  { title: 'Thao tác', dataIndex: 'operation', key: 'operation' },
  { title: 'Thay đổi', dataIndex: 'quantity_change', key: 'quantity_change' },
  { title: 'Sau cập nhật', dataIndex: 'new_quantity', key: 'new_quantity' },
  { title: 'Ghi chú', dataIndex: 'note', key: 'note' }
];

// Load data on mount
onMounted(async () => {
  await loadCategories();
  
  if (isEditing.value) {
    await loadProduct(productId.value);
    // await loadStockHistory(productId.value);
  }
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

// Load product details for editing
const loadProduct = async (id) => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/products/${id}`);
    
    // Cập nhật form với dữ liệu sản phẩm
    Object.keys(form).forEach(key => {
      if (response.data[key] !== undefined) {
        form[key] = response.data[key];
      }
    });
  } catch (error) {
    console.error('Lỗi khi tải thông tin sản phẩm:', error);
    message.error('Không thể tải thông tin sản phẩm');
    router.push('/products');
  } finally {
    loading.value = false;
  }
};

// Load stock history for a product
const loadStockHistory = async (id) => {
  try {
    // API endpoint chưa có, nên tạm thời dùng dữ liệu mẫu
    stockHistory.value = [
      {
        id: 1,
        date: '2023-10-15 14:30',
        user: 'Admin',
        operation: 'add',
        quantity_change: 50,
        new_quantity: 50,
        note: 'Nhập hàng lần đầu'
      },
      {
        id: 2,
        date: '2023-10-17 09:15',
        user: 'Admin',
        operation: 'subtract',
        quantity_change: -5,
        new_quantity: 45,
        note: 'Bán hàng'
      },
      {
        id: 3,
        date: '2023-10-20 16:45',
        user: 'Staff',
        operation: 'add',
        quantity_change: 10,
        new_quantity: 55,
        note: 'Nhập thêm hàng'
      }
    ];
  } catch (error) {
    console.error('Lỗi khi tải lịch sử tồn kho:', error);
  }
};

// Submit form
const onSubmit = async () => {
  try {
    loading.value = true;
    
    if (isEditing.value) {
      // Update existing product
      await axios.put(`/api/products/${productId.value}`, form);
      message.success('Cập nhật sản phẩm thành công');
    } else {
      // Create new product
      await axios.post('/api/products', form);
      message.success('Tạo sản phẩm mới thành công');
      resetForm();
    }
    
    if (!isEditing.value) {
      router.push('/products');
    }
  } catch (error) {
    console.error('Lỗi khi lưu sản phẩm:', error);
    message.error('Có lỗi xảy ra khi lưu sản phẩm');
  } finally {
    loading.value = false;
  }
};

// Reset form
const resetForm = () => {
  Object.keys(form).forEach(key => {
    form[key] = key === 'price' || key === 'stock_quantity' ? 0 : '';
  });
};

// Delete product
const deleteProduct = async () => {
  try {
    loading.value = true;
    await axios.delete(`/api/products/${productId.value}`);
    message.success('Xóa sản phẩm thành công');
    router.push('/products');
  } catch (error) {
    console.error('Lỗi khi xóa sản phẩm:', error);
    message.error('Có lỗi xảy ra khi xóa sản phẩm');
  } finally {
    loading.value = false;
  }
};

// Helpers for stock history display
const getOperationLabel = (operation) => {
  const labels = {
    'add': 'Thêm',
    'subtract': 'Trừ',
    'set': 'Đặt lại'
  };
  return labels[operation] || operation;
};

const getOperationColor = (operation) => {
  const colors = {
    'add': 'green',
    'subtract': 'red',
    'set': 'blue'
  };
  return colors[operation] || 'default';
};
</script> 