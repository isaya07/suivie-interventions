<template>
  <div class="space-y-4">
    <!-- Zone de glisser-déposer -->
    <div
      @drop="handleDrop"
      @dragover="handleDragOver"
      @dragenter="handleDragEnter"
      @dragleave="handleDragLeave"
      :class="[
        'border-2 border-dashed rounded-lg p-6 text-center transition-colors',
        isDragging
          ? 'border-blue-500 bg-blue-50'
          : 'border-gray-300 hover:border-gray-400',
      ]"
    >
      <input
        ref="fileInput"
        type="file"
        multiple
        accept="image/*,application/pdf,.doc,.docx,.txt"
        class="hidden"
        @change="handleFileSelect"
      />

      <svg
        class="mx-auto h-12 w-12 text-gray-400"
        stroke="currentColor"
        fill="none"
        viewBox="0 0 48 48"
      >
        <path
          d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>

      <div class="mt-4">
        <p class="text-lg text-gray-600">
          Glissez-déposez vos fichiers ici, ou
          <button
            type="button"
            @click="$refs.fileInput.click()"
            class="text-blue-600 hover:text-blue-800 font-medium"
          >
            parcourez
          </button>
        </p>
        <p class="text-sm text-gray-500 mt-1">
          PNG, JPG, PDF, DOC jusqu'à 10MB
        </p>
      </div>
    </div>

    <!-- Fichiers sélectionnés -->
    <div v-if="selectedFiles.length > 0" class="space-y-2">
      <h4 class="font-medium text-gray-900">Fichiers sélectionnés:</h4>
      <div class="space-y-2">
        <div
          v-for="(file, index) in selectedFiles"
          :key="index"
          class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
        >
          <div class="flex items-center">
            <svg
              class="h-5 w-5 text-gray-400 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              ></path>
            </svg>
            <span class="text-sm text-gray-900">{{ file.name }}</span>
            <span class="text-xs text-gray-500 ml-2"
              >({{ formatFileSize(file.size) }})</span
            >
          </div>
          <button
            @click="removeSelectedFile(index)"
            class="text-red-600 hover:text-red-800"
          >
            <svg
              class="h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              ></path>
            </svg>
          </button>
        </div>
      </div>

      <button @click="uploadFiles" :disabled="uploading" class="btn-primary">
        {{
          uploading
            ? "Upload en cours..."
            : `Télécharger ${selectedFiles.length} fichier(s)`
        }}
      </button>
    </div>

    <!-- Liste des fichiers existants -->
    <div v-if="interventionFiles.length > 0" class="mt-8">
      <h4 class="font-medium text-gray-900 mb-4">
        Fichiers de l'intervention:
      </h4>
      <div class="space-y-2">
        <div
          v-for="file in interventionFiles"
          :key="file.id"
          class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg"
        >
          <div class="flex items-center">
            <svg
              class="h-5 w-5 text-gray-400 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              ></path>
            </svg>
            <span class="text-sm text-gray-900">{{ file.nom_original }}</span>
            <span class="text-xs text-gray-500 ml-2"
              >({{ formatFileSize(file.taille) }})</span
            >
          </div>
          <div class="flex space-x-2">
            <button
              @click="downloadFile(file.id)"
              class="text-blue-600 hover:text-blue-800 text-sm"
            >
              Télécharger
            </button>
            <button
              @click="deleteFile(file.id)"
              class="text-red-600 hover:text-red-800 text-sm"
            >
              Supprimer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  interventionId: {
    type: [String, Number],
    required: true,
  },
});

const {
  uploadFiles: upload,
  getInterventionFiles,
  deleteFile: removeFile,
  downloadFile,
  uploading,
} = useUpload();

const selectedFiles = ref([]);
const interventionFiles = ref([]);
const isDragging = ref(false);

const handleDragOver = (e) => {
  e.preventDefault();
};

const handleDragEnter = (e) => {
  e.preventDefault();
  isDragging.value = true;
};

const handleDragLeave = (e) => {
  e.preventDefault();
  if (!e.relatedTarget || !e.currentTarget.contains(e.relatedTarget)) {
    isDragging.value = false;
  }
};

const handleDrop = (e) => {
  e.preventDefault();
  isDragging.value = false;

  const files = Array.from(e.dataTransfer.files);
  addFiles(files);
};

const handleFileSelect = (e) => {
  const files = Array.from(e.target.files);
  addFiles(files);
};

const addFiles = (files) => {
  const validFiles = files.filter((file) => {
    // Vérifier la taille (10MB max)
    if (file.size > 10 * 1024 * 1024) {
      alert(`Le fichier ${file.name} est trop volumineux (max 10MB)`);
      return false;
    }

    // Vérifier le type
    const validTypes = [
      "image/",
      "application/pdf",
      "application/msword",
      "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      "text/",
    ];
    const isValidType = validTypes.some((type) => file.type.startsWith(type));

    if (!isValidType) {
      alert(`Le type de fichier ${file.name} n'est pas supporté`);
      return false;
    }

    return true;
  });

  selectedFiles.value.push(...validFiles);
};

const removeSelectedFile = (index) => {
  selectedFiles.value.splice(index, 1);
};

const uploadFiles = async () => {
  if (selectedFiles.value.length === 0) return;

  const result = await upload(selectedFiles.value, props.interventionId);

  if (result.success) {
    selectedFiles.value = [];
    await loadInterventionFiles();
  }
};

const deleteFile = async (fileId) => {
  if (confirm("Êtes-vous sûr de vouloir supprimer ce fichier ?")) {
    const success = await removeFile(fileId);
    if (success) {
      await loadInterventionFiles();
    }
  }
};

const loadInterventionFiles = async () => {
  interventionFiles.value = await getInterventionFiles(props.interventionId);
};

const formatFileSize = (bytes) => {
  if (bytes === 0) return "0 Bytes";

  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

onMounted(() => {
  loadInterventionFiles();
});
</script>
