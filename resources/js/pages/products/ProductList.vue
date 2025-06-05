<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Header và chức năng tìm kiếm -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0 mb-6">
        <div>
          <h1 class="text-2xl font-bold">Quản lý sản phẩm</h1>
          <p class="text-muted-foreground">Quản lý kho hàng và sản phẩm bán tại quầy</p>
        </div>
        
        <div class="flex space-x-2">
          <div class="relative">
            <input 
              type="text" 
              placeholder="Tìm kiếm sản phẩm..." 
              v-model="searchQuery"
              class="px-3 py-2 border rounded-md w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-primary/50"
            />
            <span class="absolute right-3 top-2">🔍</span>
          </div>
          
          <button class="bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90 transition-colors">
            + Thêm sản phẩm
          </button>
        </div>
      </div>
      
      <!-- Bộ lọc -->
      <div class="bg-white p-4 rounded-lg shadow">
        <div class="flex flex-wrap gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Danh mục</label>
            <select v-model="filters.category" class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50">
              <option value="">Tất cả</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Trạng thái</label>
            <select v-model="filters.status" class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50">
              <option value="">Tất cả</option>
              <option value="active">Đang bán</option>
              <option value="out_of_stock">Hết hàng</option>
              <option value="inactive">Không hoạt động</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Sắp xếp theo</label>
            <select v-model="filters.sortBy" class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50">
              <option value="name">Tên</option>
              <option value="price">Giá</option>
              <option value="stock">Tồn kho</option>
            </select>
          </div>
          
          <div class="flex items-end">
            <button 
              @click="resetFilters"
              class="px-4 py-2 bg-muted hover:bg-muted/80 rounded-md transition-colors"
            >
              Đặt lại bộ lọc
            </button>
          </div>
        </div>
      </div>
      
      <!-- Danh mục sản phẩm -->
      <div class="bg-white p-4 rounded-lg shadow">
        <div class="flex flex-wrap gap-2 mb-4">
          <button 
            @click="selectedCategory = null" 
            :class="{
              'px-3 py-1 rounded-md text-sm transition-colors': true,
              'bg-primary text-primary-foreground': selectedCategory === null,
              'bg-muted/30 hover:bg-muted/50': selectedCategory !== null
            }"
          >
            Tất cả
          </button>
          <button 
            v-for="category in categories" 
            :key="category.id"
            @click="selectedCategory = category.id"
            :class="{
              'px-3 py-1 rounded-md text-sm transition-colors': true,
              'bg-primary text-primary-foreground': selectedCategory === category.id,
              'bg-muted/30 hover:bg-muted/50': selectedCategory !== category.id
            }"
          >
            {{ category.name }}
          </button>
        </div>
      </div>
      
      <!-- Danh sách sản phẩm -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-muted/30">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Sản phẩm</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Danh mục</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Giá</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tồn kho</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Mã SKU</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Trạng thái</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(product, index) in filteredProducts" :key="index" class="hover:bg-muted/10">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="h-10 w-10 rounded bg-muted/20 flex items-center justify-center mr-3">
                      <span class="text-xl">{{ product.emoji }}</span>
                    </div>
                    <div>
                      <div class="font-medium">{{ product.name }}</div>
                      <div class="text-xs text-muted-foreground truncate max-w-xs">{{ product.description }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  {{ getCategoryName(product.category_id) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="font-medium text-primary">{{ formatPrice(product.price) }}</div>
                  <div class="text-xs text-muted-foreground">Giá gốc: {{ formatPrice(product.cost_price) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div 
                    :class="{
                      'font-medium': true,
                      'text-red-600': product.stock_quantity <= 5,
                      'text-amber-600': product.stock_quantity > 5 && product.stock_quantity <= 15,
                      'text-green-600': product.stock_quantity > 15
                    }"
                  >
                    {{ product.stock_quantity }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  {{ product.sku || '—' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    :class="{
                      'px-2 py-1 rounded-full text-xs': true,
                      'bg-green-100 text-green-800': product.status === 'active',
                      'bg-red-100 text-red-800': product.status === 'inactive',
                      'bg-amber-100 text-amber-800': product.status === 'out_of_stock'
                    }"
                  >
                    {{ getProductStatusText(product.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex space-x-2">
                    <button class="p-1 bg-blue-100 hover:bg-blue-200 rounded-md text-sm text-blue-800 transition-colors">
                      Sửa
                    </button>
                    <button class="p-1 bg-amber-100 hover:bg-amber-200 rounded-md text-sm text-amber-800 transition-colors">
                      Nhập hàng
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Phân trang -->
        <div class="bg-muted/10 px-6 py-3 border-t flex items-center justify-between">
          <div>
            <p class="text-sm text-muted-foreground">
              Hiển thị <span class="font-medium">1</span> đến <span class="font-medium">{{ filteredProducts.length }}</span> của <span class="font-medium">{{ products.length }}</span> sản phẩm
            </p>
          </div>
          <div class="flex space-x-1">
            <button class="px-3 py-1 bg-white rounded-md border hover:bg-muted/10 transition-colors">
              Trước
            </button>
            <button class="px-3 py-1 bg-primary/10 text-primary rounded-md border border-primary/30 transition-colors">
              1
            </button>
            <button class="px-3 py-1 bg-white rounded-md border hover:bg-muted/10 transition-colors">
              Tiếp
            </button>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import MainLayout from '../../layouts/MainLayout.vue';

// Mock data - Danh mục sản phẩm
const categories = ref([
  { id: 1, name: 'Đồ uống', description: 'Nước uống tại quầy', status: 'active', sort_order: 1 },
  { id: 2, name: 'Thực phẩm bổ sung', description: 'Protein, BCAA và các loại thực phẩm bổ sung', status: 'active', sort_order: 2 },
  { id: 3, name: 'Phụ kiện tập luyện', description: 'Dụng cụ và phụ kiện hỗ trợ tập luyện', status: 'active', sort_order: 3 },
  { id: 4, name: 'Dịch vụ', description: 'Các dịch vụ bổ sung', status: 'active', sort_order: 4 },
]);

// Mock data - Sản phẩm
const products = ref([
  { 
    id: 1, 
    name: 'Nước suối', 
    description: 'Nước suối tinh khiết 500ml', 
    price: 10000, 
    cost_price: 5000, 
    sku: 'DRINK001', 
    barcode: '8936012345671', 
    category_id: 1, 
    stock_quantity: 50,
    emoji: '💧',
    status: 'active' 
  },
  { 
    id: 2, 
    name: 'Nước ion', 
    description: 'Nước điện giải 500ml', 
    price: 20000, 
    cost_price: 12000, 
    sku: 'DRINK002', 
    barcode: '8936012345672', 
    category_id: 1, 
    stock_quantity: 30,
    emoji: '🥤',
    status: 'active' 
  },
  { 
    id: 3, 
    name: 'Sinh tố Protein', 
    description: 'Sinh tố protein bổ sung sau tập', 
    price: 45000, 
    cost_price: 25000, 
    sku: 'DRINK003', 
    barcode: '8936012345673', 
    category_id: 1, 
    stock_quantity: 15,
    emoji: '🥤',
    status: 'active' 
  },
  { 
    id: 4, 
    name: 'Whey Protein 1kg', 
    description: 'Whey protein isolate hỗ trợ phát triển cơ bắp', 
    price: 950000, 
    cost_price: 750000, 
    sku: 'SUPP001', 
    barcode: '8936012345674', 
    category_id: 2, 
    stock_quantity: 8,
    emoji: '💪',
    status: 'active' 
  },
  { 
    id: 5, 
    name: 'BCAA 200g', 
    description: 'BCAA hỗ trợ phục hồi cơ bắp', 
    price: 550000, 
    cost_price: 380000, 
    sku: 'SUPP002', 
    barcode: '8936012345675', 
    category_id: 2, 
    stock_quantity: 5,
    emoji: '🔋',
    status: 'active' 
  },
  { 
    id: 6, 
    name: 'Găng tay tập', 
    description: 'Găng tay hỗ trợ tập luyện tạ', 
    price: 180000, 
    cost_price: 120000, 
    sku: 'ACC001', 
    barcode: '8936012345676', 
    category_id: 3, 
    stock_quantity: 12,
    emoji: '🧤',
    status: 'active' 
  },
  { 
    id: 7, 
    name: 'Dây kháng lực', 
    description: 'Bộ dây kháng lực tập luyện đa năng', 
    price: 250000, 
    cost_price: 180000, 
    sku: 'ACC002', 
    barcode: '8936012345677', 
    category_id: 3, 
    stock_quantity: 0,
    emoji: '🔄',
    status: 'out_of_stock' 
  },
  { 
    id: 8, 
    name: 'PT 1 buổi', 
    description: 'Huấn luyện cá nhân 1 buổi', 
    price: 300000, 
    cost_price: 200000, 
    sku: 'SERV001', 
    barcode: null, 
    category_id: 4, 
    stock_quantity: 999,
    emoji: '🏋️',
    status: 'active' 
  },
  { 
    id: 9, 
    name: 'Đánh giá thể chất', 
    description: 'Đánh giá chỉ số cơ thể và tư vấn dinh dưỡng', 
    price: 200000, 
    cost_price: 100000, 
    sku: 'SERV002', 
    barcode: null, 
    category_id: 4, 
    stock_quantity: 999,
    emoji: '📊',
    status: 'inactive' 
  },
]);

// Bộ lọc và tìm kiếm
const searchQuery = ref('');
const filters = ref({
  category: '',
  status: '',
  sortBy: 'name'
});
const selectedCategory = ref(null);

// Reset bộ lọc
const resetFilters = () => {
  filters.value = {
    category: '',
    status: '',
    sortBy: 'name'
  };
  searchQuery.value = '';
  selectedCategory.value = null;
};

// Lọc sản phẩm
const filteredProducts = computed(() => {
  let result = [...products.value];
  
  // Lọc theo danh mục được chọn ở tab
  if (selectedCategory !== null) {
    result = result.filter(product => product.category_id === selectedCategory.value);
  }
  
  // Lọc theo danh mục từ dropdown
  if (filters.value.category) {
    result = result.filter(product => product.category_id == filters.value.category);
  }
  
  // Lọc theo trạng thái
  if (filters.value.status) {
    result = result.filter(product => product.status === filters.value.status);
  }
  
  // Tìm kiếm
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(product => 
      product.name.toLowerCase().includes(query) || 
      product.description.toLowerCase().includes(query) ||
      (product.sku && product.sku.toLowerCase().includes(query))
    );
  }
  
  // Sắp xếp
  result.sort((a, b) => {
    if (filters.value.sortBy === 'name') {
      return a.name.localeCompare(b.name);
    } else if (filters.value.sortBy === 'price') {
      return a.price - b.price;
    } else if (filters.value.sortBy === 'stock') {
      return b.stock_quantity - a.stock_quantity;
    }
    
    return 0;
  });
  
  return result;
});

// Lấy tên danh mục
const getCategoryName = (categoryId) => {
  const category = categories.value.find(cat => cat.id === categoryId);
  return category ? category.name : '';
};

// Định dạng giá
const formatPrice = (price) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0
  }).format(price);
};

// Chuyển đổi trạng thái sản phẩm
const getProductStatusText = (status) => {
  const statusMap = {
    'active': 'Đang bán',
    'inactive': 'Ngừng bán',
    'out_of_stock': 'Hết hàng'
  };
  
  return statusMap[status] || status;
};
</script> 