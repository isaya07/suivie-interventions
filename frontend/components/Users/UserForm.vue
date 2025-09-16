<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <div>
      <label for="nom" class="block text-sm font-medium text-gray-700">
        Nom complet *
      </label>
      <InputText
        id="nom"
        v-model="form.nom"
        type="text"
        required
        :invalid="!!errors.nom"
        placeholder="Nom complet"
        class="w-full"
      />
      <p v-if="errors.nom" class="mt-1 text-sm text-red-600">
        {{ errors.nom }}
      </p>
    </div>

    <div>
      <label for="email" class="block text-sm font-medium text-gray-700">
        Email *
      </label>
      <InputText
        id="email"
        v-model="form.email"
        type="email"
        required
        :invalid="!!errors.email"
        placeholder="email@exemple.com"
        class="w-full"
      />
      <p v-if="errors.email" class="mt-1 text-sm text-red-600">
        {{ errors.email }}
      </p>
    </div>

    <div v-if="!isEditing">
      <label for="password" class="block text-sm font-medium text-gray-700">
        Mot de passe *
      </label>
      <Password
        id="password"
        v-model="form.password"
        :required="!isEditing"
        :invalid="!!errors.password"
        placeholder="••••••••"
        :feedback="false"
        class="w-full"
      />
      <p v-if="errors.password" class="mt-1 text-sm text-red-600">
        {{ errors.password }}
      </p>
    </div>

    <div>
      <label for="role" class="block text-sm font-medium text-gray-700">
        Rôle *
      </label>
      <Select
        id="role"
        v-model="form.role"
        :options="[
          { label: 'Technicien', value: 'technicien' },
          { label: 'Manager', value: 'manager' },
          { label: 'Administrateur', value: 'admin' }
        ]"
        optionLabel="label"
        optionValue="value"
        placeholder="Sélectionner un rôle"
        required
        :invalid="!!errors.role"
        class="w-full"
      />
      <p v-if="errors.role" class="mt-1 text-sm text-red-600">
        {{ errors.role }}
      </p>
    </div>

    <div>
      <label for="telephone" class="block text-sm font-medium text-gray-700">
        Téléphone
      </label>
      <InputText
        id="telephone"
        v-model="form.telephone"
        type="tel"
        placeholder="06 12 34 56 78"
        class="w-full"
      />
    </div>

    <div class="flex items-center">
      <Checkbox
        id="actif"
        v-model="form.actif"
        :binary="true"
      />
      <label for="actif" class="ml-2 block text-sm text-gray-900">
        Utilisateur actif
      </label>
    </div>

    <div class="flex justify-end space-x-3 pt-4">
      <Button
        type="button"
        @click="$emit('cancel')"
        label="Annuler"
        severity="secondary"
        outlined
      />
      <Button
        type="submit"
        :disabled="loading"
        :loading="loading"
        :label="loading ? 'Enregistrement...' : isEditing ? 'Modifier' : 'Créer'"
      />
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
