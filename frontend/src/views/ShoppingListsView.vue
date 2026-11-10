<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../lib/api';

const lists = ref([]);
const selected = ref(null);
const newName = ref('');
const itemName = ref('');
const itemQuantity = ref(1);
const itemUnit = ref('unit');
const busy = ref(false);
const error = ref('');
const notice = ref('');

function message(err) {
  return err.response?.data?.message || 'Une erreur est survenue. Réessaie.';
}

async function refresh() {
  const { data } = await api.get('/v1/shopping-lists');
  lists.value = data;
  if (selected.value) {
    selected.value = data.find((list) => list.id === selected.value.id) || null;
  }
}

async function run(action) {
  busy.value = true;
  error.value = '';
  notice.value = '';
  try {
    await action();
  } catch (err) {
    error.value = message(err);
  } finally {
    busy.value = false;
  }
}

function selectList(list) {
  selected.value = list;
  error.value = '';
  notice.value = '';
}

function createList() {
  if (!newName.value.trim()) return;
  run(async () => {
    const { data } = await api.post('/v1/shopping-lists', { name: newName.value.trim() });
    newName.value = '';
    await refresh();
    selected.value = lists.value.find((list) => list.id === data.id) || data;
    notice.value = 'Liste créée.';
  });
}

function addItem() {
  if (!selected.value || !itemName.value.trim()) return;
  run(async () => {
    await api.post(`/v1/shopping-lists/${selected.value.id}/items`, {
      custom_name: itemName.value.trim(),
      quantity: Number(itemQuantity.value),
      unit: itemUnit.value,
    });
    itemName.value = '';
    itemQuantity.value = 1;
    await refresh();
  });
}

function toggleItem(item) {
  run(async () => {
    await api.patch(`/v1/shopping-lists/${selected.value.id}/items/${item.id}`, {
      is_checked: !item.is_checked,
    });
    await refresh();
  });
}

function removeItem(item) {
  run(async () => {
    await api.delete(`/v1/shopping-lists/${selected.value.id}/items/${item.id}`);
    await refresh();
  });
}

function duplicateList() {
  run(async () => {
    const { data } = await api.post(`/v1/shopping-lists/${selected.value.id}/duplicate`);
    await refresh();
    selected.value = lists.value.find((list) => list.id === data.id) || data;
    notice.value = 'Liste dupliquée.';
  });
}

function deleteList() {
  if (!selected.value || !window.confirm('Supprimer définitivement cette liste ?')) return;
  run(async () => {
    await api.delete(`/v1/shopping-lists/${selected.value.id}`);
    selected.value = null;
    await refresh();
    notice.value = 'Liste supprimée.';
  });
}

const checkedCount = computed(() => selected.value?.items?.filter((item) => item.is_checked).length ?? 0);

onMounted(() => run(refresh));
</script>

<template>
  <h1>Mes listes de courses</h1>
  <p>Prépare tes courses et coche les articles au fur et à mesure.</p>

  <p v-if="error" class="error" role="alert">{{ error }}</p>
  <p v-if="notice" class="success" role="status">{{ notice }}</p>

  <section class="card">
    <h2>Nouvelle liste</h2>
    <form @submit.prevent="createList">
      <label>Nom de la liste
        <input v-model="newName" maxlength="255" placeholder="Courses de la semaine" required />
      </label>
      <button :disabled="busy || !newName.trim()">Créer la liste</button>
    </form>
  </section>

  <section class="card">
    <h2>Mes listes ({{ lists.length }})</h2>
    <p v-if="!lists.length">Aucune liste pour le moment.</p>
    <div v-else class="shopping-list-choices">
      <button v-for="list in lists" :key="list.id" type="button"
        :disabled="busy" :aria-pressed="selected?.id === list.id"
        :class="{ selected: selected?.id === list.id }" @click="selectList(list)">
        {{ list.name }} ({{ list.items?.length ?? 0 }})
      </button>
    </div>
  </section>

  <section v-if="selected" class="card">
    <div class="shopping-list-header">
      <div>
        <h2>{{ selected.name }}</h2>
        <p>{{ checkedCount }} / {{ selected.items?.length ?? 0 }} articles cochés</p>
      </div>
      <div class="shopping-list-actions">
        <button type="button" :disabled="busy" @click="duplicateList">Dupliquer</button>
        <button type="button" class="danger" :disabled="busy" @click="deleteList">Supprimer</button>
      </div>
    </div>

    <form @submit.prevent="addItem">
      <label>Article
        <input v-model="itemName" maxlength="255" placeholder="Ex. : Pommes" required />
      </label>
      <div class="shopping-list-fields">
        <label>Quantité
          <input v-model.number="itemQuantity" type="number" min="0.001" max="9999999" step="0.001" required />
        </label>
        <label>Unité
          <select v-model="itemUnit">
            <option value="unit">Pièce(s)</option>
            <option value="g">g</option>
            <option value="kg">kg</option>
            <option value="ml">ml</option>
            <option value="l">L</option>
          </select>
        </label>
      </div>
      <button :disabled="busy || !itemName.trim() || !(itemQuantity > 0)">Ajouter l'article</button>
    </form>

    <ul v-if="selected.items?.length" class="shopping-items">
      <li v-for="item in selected.items" :key="item.id">
        <label class="shopping-check">
          <input type="checkbox" :checked="item.is_checked" :disabled="busy" @change="toggleItem(item)" />
          <span :class="{ done: item.is_checked }">{{ item.custom_name || item.product?.name }} — {{ Number(item.quantity) }} {{ item.unit === 'unit' ? 'pièce(s)' : item.unit }}</span>
        </label>
        <button type="button" class="link" :disabled="busy" :aria-label="`Retirer ${item.custom_name || item.product?.name}`" @click="removeItem(item)">Retirer</button>
      </li>
    </ul>
    <p v-else>Aucun article. Ajoute ton premier produit ci-dessus.</p>
  </section>
</template>

<style scoped>
.shopping-list-choices, .shopping-list-actions { display: flex; flex-wrap: wrap; gap: 10px; }
.shopping-list-choices button { background: #e4f0e8; color: #176b49; }
.shopping-list-choices button.selected { background: #176b49; color: white; }
.shopping-list-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
.shopping-list-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.shopping-items { padding: 0; list-style: none; }
.shopping-items li { display: flex; justify-content: space-between; align-items: center; gap: 14px; border-bottom: 1px solid #dce8df; padding: 12px 0; }
.shopping-check { display: flex; align-items: center; gap: 10px; font-weight: 400; }
.shopping-check input { width: auto; }
.done { text-decoration: line-through; color: #687a70; }
button.danger { background: #a52c35; }
@media (max-width: 600px) { .shopping-list-fields { grid-template-columns: 1fr; } }
</style>
