import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: () => import('../pages/Dashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/auth/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/members',
    name: 'members',
    component: () => import('../pages/members/MemberList.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/members/add',
    name: 'members.add',
    component: () => import('../pages/members/AddMember.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/members/:id',
    name: 'members.detail',
    component: () => import('../pages/members/MemberDetail.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/members/:id/payment-history',
    name: 'members.payment-history',
    component: () => import('../pages/members/PaymentHistory.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/members/:id/schedule',
    name: 'members.schedule',
    component: () => import('../pages/members/MemberSchedule.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/trainers',
    name: 'trainers',
    component: () => import('../pages/trainers/TrainerList.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/pos',
    name: 'pos',
    component: () => import('../pages/pos/PosSystem.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/schedules',
    name: 'schedules',
    component: () => import('../pages/schedules/ScheduleCalendar.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/memberships',
    name: 'memberships',
    component: () => import('../pages/memberships/MembershipList.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/products',
    name: 'products',
    component: () => import('../pages/products/ProductList.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/products/inventory',
    name: 'products.inventory',
    component: () => import('../pages/products/Inventory.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/products/create',
    name: 'products.create',
    component: () => import('../pages/products/ProductForm.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/products/:id/edit',
    name: 'products.edit',
    component: () => import('../pages/products/ProductForm.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/reports',
    name: 'reports',
    component: () => import('../pages/reports/ReportDashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/orders',
    name: 'orders',
    component: () => import('../pages/orders/OrderList.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/orders/:id',
    name: 'orders.detail',
    component: () => import('../pages/orders/OrderDetail.vue'),
    meta: { requiresAuth: true }
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Kiểm tra xác thực người dùng trước khi chuyển trang
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token) {
      next({ name: 'login' });
    } else {
      next();
    }
  } else if (to.matched.some(record => record.meta.guest)) {
    if (token) {
      next({ name: 'dashboard' });
    } else {
      next();
    }
  } else {
    next();
  }
});

export default router; 