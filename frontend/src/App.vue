<script setup>
import { useAuthStore } from './stores/auth';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();

async function logout() {
  await auth.logout();
  router.push('/login');
}
</script>

<template>
  <header>
    <div class="wrap bar">
      <strong>🛒 Courses Malines</strong>
      <nav v-if="auth.token">
        <RouterLink to="/">Accueil</RouterLink>
        <RouterLink to="/stores">Magasins</RouterLink>
        <RouterLink to="/shopping-lists">Listes de courses</RouterLink>
        <button class="link" @click="logout">Déconnexion</button>
      </nav>
    </div>
  </header>
  <main class="wrap">
    <RouterView />
  </main>
</template>
