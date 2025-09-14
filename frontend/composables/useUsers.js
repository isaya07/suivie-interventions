export const useUsers = () => {
  const users = ref([]);
  const techniciens = ref([]);
  const loading = ref(false);

  const { $api } = useNuxtApp();

  const fetchUsers = async () => {
    loading.value = true;
    try {
      const response = await $api("/users.php");
      if (response.records) {
        users.value = response.records;
      }
    } catch (error) {
      console.error("Erreur lors de la récupération des utilisateurs:", error);
    } finally {
      loading.value = false;
    }
  };

  const fetchTechniciens = async () => {
    loading.value = true;
    try {
      const response = await $api("/users.php?techniciens=1");
      if (response.records) {
        techniciens.value = response.records;
      }
    } catch (error) {
      console.error("Erreur lors de la récupération des techniciens:", error);
    } finally {
      loading.value = false;
    }
  };

  const createUser = async (userData) => {
    loading.value = true;
    try {
      const response = await $api("/users.php", {
        method: "POST",
        body: userData,
      });

      if (response.success !== false) {
        await fetchUsers(); // Recharger la liste
        return { success: true };
      }

      return { success: false, message: response.message };
    } catch (error) {
      console.error("Erreur lors de la création de l'utilisateur:", error);
      return { success: false, message: "Erreur de création" };
    } finally {
      loading.value = false;
    }
  };

  const updateUser = async (userId, userData) => {
    loading.value = true;
    try {
      const response = await $api("/users.php", {
        method: "PUT",
        body: { id: userId, ...userData },
      });

      if (response.success !== false) {
        await fetchUsers(); // Recharger la liste
        return { success: true };
      }

      return { success: false, message: response.message };
    } catch (error) {
      console.error("Erreur lors de la mise à jour de l'utilisateur:", error);
      return { success: false, message: "Erreur de mise à jour" };
    } finally {
      loading.value = false;
    }
  };

  const deleteUser = async (userId) => {
    loading.value = true;
    try {
      const response = await $api("/users.php", {
        method: "DELETE",
        body: { id: userId },
      });

      if (response.success !== false) {
        await fetchUsers(); // Recharger la liste
        return { success: true };
      }

      return { success: false, message: response.message };
    } catch (error) {
      console.error("Erreur lors de la suppression de l'utilisateur:", error);
      return { success: false, message: "Erreur de suppression" };
    } finally {
      loading.value = false;
    }
  };

  return {
    users: readonly(users),
    techniciens: readonly(techniciens),
    loading: readonly(loading),
    fetchUsers,
    fetchTechniciens,
    createUser,
    updateUser,
    deleteUser,
  };
};