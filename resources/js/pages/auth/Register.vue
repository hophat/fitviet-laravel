<template>
  <div class="min-h-screen flex items-center justify-center bg-muted/10">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-primary">Đăng ký</h1>
      </div>
      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label for="name" class="block text-sm font-medium mb-1">Họ và tên</label>
          <input
            type="text"
            id="name"
            v-model="name"
            required
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50"
            placeholder="Nguyễn Văn A"
          />
        </div>
        <div>
          <label for="email" class="block text-sm font-medium mb-1">Email</label>
          <input
            type="email"
            id="email"
            v-model="email"
            required
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50"
            placeholder="email@example.com"
          />
        </div>
        <div>
          <label for="password" class="block text-sm font-medium mb-1">Mật khẩu</label>
          <input
            type="password"
            id="password"
            v-model="password"
            required
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50"
            placeholder="••••••••"
          />
        </div>
        <div>
          <label for="password_confirmation" class="block text-sm font-medium mb-1">Xác nhận mật khẩu</label>
          <input
            type="password"
            id="password_confirmation"
            v-model="confirmPassword"
            required
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50"
            placeholder="••••••••"
          />
        </div>
        <div>
          <label for="role" class="block text-sm font-medium mb-1">Vai trò</label>
          <select
            id="role"
            v-model="role"
            required
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50"
          >
            <option value="member">Member</option>
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
            <option value="owner">Owner</option>
          </select>
        </div>
        <div v-if="error" class="text-destructive text-sm p-2 bg-destructive/10 rounded-md">
          {{ error }}
        </div>
        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-primary text-primary-foreground py-2 px-4 rounded-md hover:bg-primary/90 transition-colors"
        >
          <span v-if="loading">Đang đăng ký...</span>
          <span v-else>Đăng ký</span>
        </button>
      </form>
      <p class="mt-4 text-center text-sm">
        Đã có tài khoản?
        <router-link to="/login" class="text-primary hover:underline">Đăng nhập</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const name = ref('');
const email = ref('');
const password = ref('');
const confirmPassword = ref('');
const role = ref('member');
const loading = ref(false);
const error = ref('');

const handleRegister = async () => {
  loading.value = true;
  error.value = '';

  try {
    const response = await axios.post('/api/auth/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: confirmPassword.value,
      role: role.value,
    });
    const { token: authToken, user: authUser } = response.data;

    localStorage.setItem('token', authToken);
    localStorage.setItem('user', JSON.stringify(authUser));
    axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`;

    router.push('/');
  } catch (err) {
    if (err.response && err.response.data.errors) {
      const msgs = Object.values(err.response.data.errors).flat();
      error.value = msgs.join(' ');
    } else if (err.response && err.response.data.message) {
      error.value = err.response.data.message;
    } else {
      error.value = 'Đã xảy ra lỗi khi đăng ký';
    }
  } finally {
    loading.value = false;
  }
};
</script>