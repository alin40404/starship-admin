<script lang="ts" setup>
import { onBeforeUnmount, ref, watch } from 'vue';

import { fetchPluginCoverBlob } from '#/features/system/api/dev-plugin';

const props = defineProps<{
  coverUrl?: null | string;
  fallbackGlyph: string;
  slug: string;
  title: string;
}>();

const objectUrl = ref('');

async function loadCover() {
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value);
    objectUrl.value = '';
  }

  if (!props.coverUrl) {
    return;
  }

  try {
    const blob = await fetchPluginCoverBlob(props.slug);
    objectUrl.value = URL.createObjectURL(blob);
  } catch {
    // 回退字母占位
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
  <div class="plugin-cover">
    <img
      v-if="objectUrl"
      :alt="title"
      class="plugin-cover__img"
      :src="objectUrl"
    />
    <span v-else class="plugin-cover__glyph">{{ fallbackGlyph }}</span>
  </div>
</template>

<style scoped>
.plugin-cover {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.plugin-cover__img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.plugin-cover__glyph {
  color: hsl(var(--foreground) / 0.72);
  font-size: 1.45rem;
  font-weight: 700;
  line-height: 1;
}
</style>
