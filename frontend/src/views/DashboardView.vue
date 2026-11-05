<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../lib/api';

const prefs = ref(null);
const error = ref('');

onMounted(async () => {
  try {
    prefs.value = (await api.get('/v1/preferences')).data;
  } catch {
    error.value = 'Impossible de charger les préférences.';
  }
});
</script>

<template>
  <h1>Bienvenue sur Courses Malines</h1>
  <p>Comparez bientôt vos courses dans vos Drives favoris, sans sacrifier la qualité.</p>
  <section class="card">
    <h2>Votre configuration</h2>
    <p v-if="error" role="alert">{{ error }}</p>
    <template v-else-if="prefs">
      <p>Mode : {{ prefs.search_mode === 'both' ? 'Favoris + proximité' : prefs.search_mode }}</p>
      <p>Rayon : {{ prefs.radius_km }} km</p>
      <p>Commune : {{ prefs.city || 'À renseigner' }}</p>
      <RouterLink to="/stores">Configurer mes magasins →</RouterLink>
    </template>
    <p v-else>Chargement…</p>
  </section>
</template>
