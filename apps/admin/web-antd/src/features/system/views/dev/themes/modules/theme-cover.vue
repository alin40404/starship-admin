<script lang="ts" setup>
import { onBeforeUnmount, ref, watch } from 'vue';

import { IconifyIcon } from '@vben/icons';

import { fetchThemeCoverBlob } from '#/features/system/api/dev-theme';

const props = defineProps<{
  coverUrl?: null | string;
  slug: string;
  title: string;
}>();

const objectUrl = ref('');
const loading = ref(false);
const failed = ref(false);

async function loadCover() {
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value);
    objectUrl.value = '';
  }

  if (!props.coverUrl) {
    failed.value = false;
    return;
  }

  loading.value = true;
  failed.value = false;
  try {
    const blob = await fetchThemeCoverBlob(props.slug);
    objectUrl.value = URL.createObjectURL(blob);
  } catch {
    failed.value = true;
  } finally {
    loading.value = false;
  }
}

watch(
  () => [props.slug, props.coverUrl] as const,
  () => {
    void loadCover();
  },
  { immediate: true },
);

onBeforeUnmount(() => {
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value);
  }
});
</script>

<template>
  <div class="theme-cover">
    <img
      v-if="objectUrl"
      :alt="title"
      class="theme-cover__img"
      :src="objectUrl"
    />
    <div v-else class="theme-cover__placeholder">
      <IconifyIcon
        class="size-10 text-muted-foreground/50"
        :icon="failed ? 'lucide:image-off' : 'lucide:image'"
      />
      <span class="mt-2 text-xs text-muted-foreground">{{ title }}</span>
    </div>
  </div>
</template>

<style scoped>
.theme-cover {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  background: hsl(var(--muted) / 0.45);
}

.theme-cover__img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.theme-cover__placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}
</style>
