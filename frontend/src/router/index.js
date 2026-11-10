import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import LoginView from '../views/LoginView.vue';
import DashboardView from '../views/DashboardView.vue';
import StoresView from '../views/StoresView.vue';
import ShoppingListsView from '../views/ShoppingListsView.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', component: LoginView, meta: { public: true } },
    { path: '/', component: DashboardView },
    { path: '/stores', component: StoresView },
    { path: '/shopping-lists', component: ShoppingListsView },
  ],
});

router.beforeEach((to) => {
  const auth = useAuthStore();
  if (!to.meta.public && !auth.token) return '/login';
  if (to.path === '/login' && auth.token) return '/';
});

export default router;
