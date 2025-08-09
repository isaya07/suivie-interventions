<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <div>
      <label for="nom" class="block text-sm font-medium text-gray-700">
        Nom complet *
      </label>
      <input
        id="nom"
        v-model="form.nom"
        type="text"
        required
        class="form-input"
        :class="{ 'border-red-500': errors.nom }"
        placeholder="Nom complet"
      />
      <p v-if="errors.nom" class="mt-1 text-sm text-red-600">
        {{ errors.nom }}
      </p>
    </div>

    <div>
      <label for="email" class="block text-sm font-medium text-gray-700">
        Email *
      </label>
      <input
        id="email"
        v-model="form.email"
        type="email"
        required
        class="form-input"
        :class="{ 'border-red-500': errors.email }"
        placeholder="email@exemple.com"
      />
      <p v-if="errors.email" class="mt-1 text-sm text-red-600">
        {{ errors.email }}
      </p>
    </div>

    <div v-if="!isEditing">
      <label for="password" class="block text-sm font-medium text-gray-700">
        Mot de passe *
      </label>
      <input
        id="password"
        v-model="form.password"
        type="password"
        :required="!isEditing"
        class="form-input"
        :class="{ 'border-red-500': errors.password }"
        placeholder="••••••••"
      />
      <p v-if="errors.password" class="mt-1 text-sm text-red-600">
        {{ errors.password }}
      </p>
    </div>

    <div>
      <label for="role" class="block text-sm font-medium text-gray-700">
        Rôle *
      </label>
      <select
        id="role"
        v-model="form.role"
        required
        class="form-input"
        :class="{ 'border-red-500': errors.role }"
      >
        <option value="">Sélectionner un rôle</option>
        <option value="technicien">Technicien</option>
        <option value="manager">Manager</option>
        <option value="admin">Administrateur</option>
      </select>
      <p v-if="errors.role" class="mt-1 text-sm text-red-600">
        {{ errors.role }}
      </p>
    </div>

    <div>
      <label for="telephone" class="block text-sm font-medium text-gray-700">
        Téléphone
      </label>
      <input
        id="telephone"
        v-model="form.telephone"
        type="tel"
        class="form-input"
        placeholder="06 12 34 56 78"
      />
    </div>

    <div class="flex items-center">
      <input
        id="actif"
        v-model="form.actif"
        type="checkbox"
        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
      />
      <label for="actif" class="ml-2 block text-sm text-gray-900">
        Utilisateur actif
      </label>
    </div>

    <div class="flex justify-end space-x-3 pt-4">
      <button type="button" @click="$emit('cancel')" class="btn-secondary">
        Annuler
      </button>
      <button type="submit" :disabled="loading" class="btn-primary">
        {{ loading ? "Enregistrement..." : isEditing ? "Modifier" : "Créer" }}
      </button>
    </div>
  </form>
</template>

<script setup>
const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
});

defineEmits(["submit", "cancel"]);

const loading = ref(false);
const errors = ref({});

const isEditing = computed(() => !!props.user);

const form = ref({
  nom: "",
  email: "",
  password: "",
  role: "",
  telephone: "",
  actif: true,
});

const validateForm = () => {
  errors.value = {};

  if (!form.value.nom.trim()) {
    errors.value.nom = "Le nom est requis";
  }

  if (!form.value.email.trim()) {
    errors.value.email = "L'email est requis";
  } else if (!/\S+@\S+\.\S+/.test(form.value.email)) {
    errors.value.email = "Format d'email invalide";
  }

  if (!isEditing.value && !form.value.password) {
    errors.value.password = "Le mot de passe est requis";
  } else if (form.value.password && form.value.password.length < 6) {
    errors.value.password =
      "Le mot de passe doit contenir au moins 6 caractères";
  }

  if (!form.value.role) {
    errors.value.role = "Le rôle est requis";
  }

  return Object.keys(errors.value).length === 0;
};

const handleSubmit = async () => {
  if (!validateForm()) return;

  loading.value = true;

  try {
    const userData = { ...form.value };

    if (isEditing.value) {
      userData.id = props.user.id;
      // Ne pas envoyer le mot de passe vide lors de l'édition
      if (!userData.password) {
        delete userData.password;
      }
    }

    $emit("submit", userData);
  } catch (error) {
    console.error("Erreur lors de la soumission:", error);
  } finally {
    loading.value = false;
  }
};

// Initialiser le formulaire si on édite
watch(
  () => props.user,
  (user) => {
    if (user) {
      form.value = {
        nom: user.nom || "",
        email: user.email || "",
        password: "",
        role: user.role || "",
        telephone: user.telephone || "",
        actif: user.actif !== undefined ? user.actif : true,
      };
    }
  },
  { immediate: true }
);
</script>
