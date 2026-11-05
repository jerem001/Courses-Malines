<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const email = ref('');
const password = ref('');
const error = ref('');
const busy = ref(false);
const auth = useAuthStore();
const router = useRouter();

async function submit() {
  busy.value = true;
  error.value = '';
  try {
    await auth.login(email.value, password.value);
    router.push('/');
  } catch (e) {
    error.value = e.response?.data?.message || 'Connexion impossible';
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <section class="card narrow">
    <h1>Connexion</h1>
    <p>Votre espace privé Courses Malines</p>
    <form @submit.prevent="submit">
      <label>E-mail
        <input v-model="email" type="email" required autocomplete="username">
      </label>
      <label>Mot de passe
        <input v-model="password" type="password" required autocomplete="current-password">
      </label>
      <p v-if="error" class="error" role="alert">{{ error }}</p>
      <button :disabled="busy">{{ busy ? 'Connexion…' : 'Se connecter' }}</button>
    </form>
  </section>
</template>
