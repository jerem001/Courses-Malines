<script setup>
import { ref } from 'vue';
import { api } from '../lib/api';

const raw = ref('');
const serviceId = ref('');
const observedAt = ref('');
const busy = ref(false);
const error = ref('');
const result = ref(null);

function inspect() {
  result.value = null;
  error.value = '';
  serviceId.value = '';
  try {
    const payload = JSON.parse(raw.value);
    if (!Array.isArray(payload?.data?.products)) throw new Error('Liste de produits absente.');
    const ids = new Set(payload.data.products.map(p => p?.attributes?.offerServiceId).filter(id => typeof id === 'string' && id));
    if (ids.size === 1) serviceId.value = [...ids][0];
  } catch {
    error.value = 'JSON Carrefour invalide ou liste de produits absente.';
  }
}

async function submit() {
  busy.value = true;
  error.value = '';
  result.value = null;
  try {
    const payload = JSON.parse(raw.value);
    if (!Array.isArray(payload?.data?.products)) throw new Error('Liste de produits absente.');
    const date = new Date(observedAt.value);
    if (!Number.isFinite(date.getTime()) || date > new Date()) throw new Error('Date du relevé invalide.');
    const { data } = await api.post('/v1/prices/import/carrefour', {
      payload, service_id: serviceId.value.trim(), observed_at: date.toISOString(),
    });
    result.value = data;
  } catch (err) {
    error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || err.message || 'Import impossible.';
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <section class="card">
    <h2>Importer des prix Carrefour</h2>
    <p>Colle uniquement la réponse JSON de recherche de produits obtenue lors de ta navigation. Ne colle ni cookies, ni jetons, ni données de compte.</p>
    <form @submit.prevent="submit">
      <label>Réponse JSON
        <textarea v-model="raw" rows="8" required :disabled="busy" @input="inspect" placeholder='{"data":{"products":[...]}}'></textarea>
      </label>
      <label>Identifiant du service Drive
        <input v-model="serviceId" required maxlength="100" :disabled="busy" placeholder="8138-150-1046" />
      </label>
      <label>Date et heure du relevé (heure locale)
        <input v-model="observedAt" type="datetime-local" required :disabled="busy" />
      </label>
      <button :disabled="busy || !raw.trim() || !serviceId.trim() || !observedAt">{{ busy ? 'Import en cours…' : 'Importer les prix' }}</button>
    </form>
    <p v-if="error" class="error" role="alert">{{ error }}</p>
    <div v-if="result" role="status">
      <p class="success">{{ result.observation_ids.length }} relevé(s) enregistré(s) ou déjà présent(s) pour le magasin n°{{ result.store_id }}.</p>
      <p v-if="result.unknown_gtins.length">Produits à ajouter au catalogue : {{ result.unknown_gtins.join(', ') }}</p>
      <p v-if="!result.observation_ids.length">Aucun prix enregistré. Vérifie le service Drive, la disponibilité et les produits du catalogue.</p>
    </div>
  </section>
</template>

<style scoped>
textarea { display: block; width: 100%; box-sizing: border-box; font: inherit; }
</style>
