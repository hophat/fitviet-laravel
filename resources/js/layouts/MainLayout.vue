<template>
  <div class="h-screen flex">
    <!-- Sidebar -->
    <div class="bg-primary w-64 text-white p-4 flex flex-col">
      <div class="text-xl font-bold mb-6">FitViet Gym</div>
      
      <!-- Navigation Menu -->
      <nav class="flex-1">
        <ul class="space-y-2">
          <li>
            <router-link to="/" class="flex items-center p-2 rounded-md hover:bg-primary-foreground/10 transition-colors" :class="{ 'bg-primary-foreground/10': $route.path === '/' }">
              <span class="mr-3">📊</span>
              <span>Dashboard</span>
            </router-link>
          </li>
          <li>
            <router-link to="/members" class="flex items-center p-2 rounded-md hover:bg-primary-foreground/10 transition-colors" :class="{ 'bg-primary-foreground/10': $route.path.startsWith('/members') }">
              <span class="mr-3">👥</span>
              <span>Hội viên</span>
            </router-link>
          </li>
          <li>
            <router-link to="/trainers" class="flex items-center p-2 rounded-md hover:bg-primary-foreground/10 transition-colors" :class="{ 'bg-primary-foreground/10': $route.path.startsWith('/trainers') }">
              <span class="mr-3">💪</span>
              <span>Huấn luyện viên</span>
            </router-link>
          </li>
          <li>
            <router-link to="/pos" class="flex items-center p-2 rounded-md hover:bg-primary-foreground/10 transition-colors" :class="{ 'bg-primary-foreground/10': $route.path.startsWith('/pos') }">
              <span class="mr-3">🛒</span>
              <span>Bán hàng (POS)</span>
            </router-link>
          </li>
          <li>
            <router-link to="/products" class="flex items-center p-2 rounded-md hover:bg-primary-foreground/10 transition-colors" :class="{ 'bg-primary-foreground/10': $route.path === '/products' }">
              <span class="mr-3">📦</span>
              <span>Sản phẩm</span>
            </router-link>
          </li>
          <li>
            <router-link to="/products/inventory" class="flex items-center p-2 rounded-md hover:bg-primary-foreground/10 transition-colors" :class="{ 'bg-primary-foreground/10': $route.path === '/products/inventory' }">
              <span class="mr-3">🧮</span>
              <span>Quản lý kho</span>
            </router-link>
          </li>
          <li>
            <router-link to="/orders" class="flex items-center p-2 rounded-md hover:bg-primary-foreground/10 transition-colors" :class="{ 'bg-primary-foreground/10': $route.path.startsWith('/orders') }">
              <span class="mr-3">📋</span>
              <span>Đơn hàng</span>
            </router-link>
          </li>
          <li>
            <router-link to="/reports" class="flex items-center p-2 rounded-md hover:bg-primary-foreground/10 transition-colors" :class="{ 'bg-primary-foreground/10': $route.path.startsWith('/reports') }">
              <span class="mr-3">📈</span>
              <span>Báo cáo</span>
            </router-link>
          </li>
          <li>
            <router-link to="/settings" class="flex items-center p-2 rounded-md hover:bg-primary-foreground/10 transition-colors" :class="{ 'bg-primary-foreground/10': $route.path.startsWith('/settings') }">
              <span class="mr-3">⚙️</span>
              <span>Cài đặt</span>
            </router-link>
          </li>
        </ul>
      </nav>
      
      <!-- User profile & logout -->
      <div class="mt-auto pt-4 border-t border-white/20">
        <div class="flex items-center mb-4">
          <div class="w-8 h-8 rounded-full bg-white/30 flex items-center justify-center mr-2">
            {{ user?.name?.charAt(0) || 'U' }}
          </div>
          <div>
            <div class="font-medium">{{ user?.name || 'User' }}</div>
            <div class="text-sm opacity-70">{{ user?.role || 'Owner' }}</div>
          </div>
        </div>
        <button @click="logout" class="w-full p-2 rounded-md bg-primary-foreground/10 hover:bg-primary-foreground/20 transition-colors text-center">
          Đăng xuất
        </button>
      </div>
    </div>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Top header -->
      <header class="bg-background border-b h-14 flex items-center px-6">
        <div class="flex-1">
          <h1 class="text-xl font-semibold">{{ pageTitle }}</h1>
        </div>
        <div class="flex items-center space-x-2">
          <button class="p-2 rounded-md hover:bg-muted transition-colors">
            <span>🔔</span>
          </button>
          <button class="p-2 rounded-md hover:bg-muted transition-colors">
            <span>⚙️</span>
          </button>
        </div>
      </header>
      
      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-6 bg-muted/30">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();

// Mock user data - sẽ được thay thế bằng dữ liệu thực từ store
const user = ref({
  name: 'Admin',
  role: 'Owner'
});

// Page title dựa vào route hiện tại
const pageTitle = computed(() => {
  const routeName = route.name;
  const titles = {
    'dashboard': 'Dashboard',
    'members': 'Quản lý hội viên',
    'members.detail': 'Thông tin hội viên',
    'members.add': 'Thêm hội viên mới',
    'members.payment-history': 'Lịch sử thanh toán',
    'members.schedule': 'Lịch tập của hội viên',
    'trainers': 'Quản lý huấn luyện viên',
    'pos': 'Hệ thống POS',
    'products': 'Quản lý sản phẩm',
    'products.inventory': 'Quản lý kho hàng',
    'products.create': 'Thêm sản phẩm mới',
    'products.edit': 'Chỉnh sửa sản phẩm',
    'orders': 'Quản lý đơn hàng',
    'orders.detail': 'Chi tiết đơn hàng',
    'reports': 'Báo cáo',
    'settings': 'Cài đặt'
  };
  
  return titles[routeName] || 'FitViet Gym';
});

// Logout function
const logout = () => {
  // Xoá token và thông tin người dùng từ localStorage
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  
  // Chuyển hướng về trang login
  router.push('/login');
};
</script> 