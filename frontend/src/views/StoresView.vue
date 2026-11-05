<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../lib/api';

const prefs = ref({ city: '', postal_code: '', radius_km: 15, search_mode: 'both' });
const brands = ref([]);
const stores = ref([]);
const favorites = ref([]);
const notice = ref('');
const error = ref('');

async function load() {
  try {
    const [p, b, s, f] = await Promise.all([
      api.get('/v1/preferences'),
      api.get('/v1/brands'),
      api.get('/v1/stores'),
      api.get('/v1/favorite-stores'),
    ]);
    prefs.value = p.data;
    brands.value = b.data;
    stores.value = s.data.data;
    favorites.value = f.data;
  } catch {
    error.value = 'Impossible de charger les magasins.';
  }
}

async function save() {
  error.value = '';
  notice.value = '';
  try {
    prefs.value = (await api.put('/v1/preferences', {
      city: prefs.value.city,
      postal_code: prefs.value.postal_code,
      radius_km: prefs.value.radius_km,
      search_mode: prefs.value.search_mode,
    })).data;
    notice.value = 'Préférences enregistrées';
  } catch {
    error.value = 'Enregistrement impossible';
  }
}

async function toggle(store) {
  error.value = '';
  try {
    if (favorites.value.some((s) => s.id === store.id)) {
      await api.delete(`/v1/favorite-stores/${store.id}`);
    } else {
      await api.post('/v1/favorite-stores', { store_id: store.id });
    }
    favorites.value = (await api.get('/v1/favorite-stores')).data;
  } catch {
    error.value = 'Modification impossible';
  }
}
onMounted(load);
</script>

<template>
  <h1>Mes magasins</h1>
  <section class="card">
    <h2>Recherche géographique</h2>
    <form @submit.prevent="save">
      <label>Commune
        <input v-model="prefs.city" placeholder="Ex. Nantes">
      </label>
      <label>Code postal
        <input v-model="prefs.postal_code" placeholder="Ex. 44000">
      </label>
      <label>Rayon (km)
        <input v-model.number="prefs.radius_km" type="number" min="1" max="100" required>
      </label>
      <label>Mode
        <select v-model="prefs.search_mode">
          <option value="both">Favoris + proximité</option>
          <option value="favorites">Favoris uniquement</option>
          <option value="radius">Proximité uniquement</option>
        </select>
      </label>
      <button>Enregistrer</button>
    </form>
    <p v-if="notice" class="success" role="status">{{ notice }}</p>
    <p v-if="error" class="error" role="alert">{{ error }}</p>
  </section>
  <section class="card">
    <h2>Enseignes initiales</h2>
    <p>{{ brands.map((brand) => brand.name).join(' · ') }}</p>
    <h2>Drives référencés</h2>
    <p v-if="!stores.length">Aucun Drive importé pour l'instant. La découverte géographique sera ajoutée ultérieurement.</p>
    <ul>
      <li v-for="store in stores" :key="store.id">
        {{ store.name }} — {{ store.city }}
        <button class="small" @click="toggle(store)">
          {{ favorites.some((s) => s.id === store.id) ? 'Retirer des favoris' : 'Ajouter aux favoris' }}
        </button>
      </li>
    </ul>
  </section>
</template>
