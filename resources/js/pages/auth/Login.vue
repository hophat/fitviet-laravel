<template>
  <div class="min-h-screen flex items-center justify-center bg-muted/10">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-primary">FitViet Gym</h1>
        <p class="text-muted-foreground">Đăng nhập để quản lý phòng tập</p>
      </div>
      
      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label for="email" class="block text-sm font-medium mb-1">Email</label>
          <input 
            type="email" 
            id="email" 
            v-model="email"
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50"
            placeholder="admin@fitviet.com"
            required
          />
        </div>
        
        <div>
          <label for="password" class="block text-sm font-medium mb-1">Mật khẩu</label>
          <input 
            type="password" 
            id="password" 
            v-model="password"
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50"
            placeholder="••••••••"
            required
          />
        </div>
        
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="remember" 
              v-model="remember"
              class="h-4 w-4 text-primary focus:ring-primary/50 border-gray-300 rounded"
            />
            <label for="remember" class="ml-2 block text-sm">Ghi nhớ đăng nhập</label>
          </div>
          
          <a href="#" class="text-sm text-primary hover:underline">Quên mật khẩu?</a>
        </div>
        
        <div v-if="authStore.error" class="text-destructive text-sm p-2 bg-destructive/10 rounded-md">
          {{ authStore.error }}
        </div>
        
        <button 
          type="submit" 
          class="w-full bg-primary text-primary-foreground py-2 px-4 rounded-md hover:bg-primary/90 transition-colors"
          :disabled="authStore.loading"
        >
          <span v-if="authStore.loading">Đang đăng nhập...</span>
          <span v-else>Đăng nhập</span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const email = ref('');
const password = ref('');
const remember = ref(false);

const handleLogin = async () => {
  try {
    await authStore.login({
      email: email.value,
      password: password.value
    });
    router.push('/');
  } catch (error) {
    console.error('Login failed:', error);
  }
};
</script> 