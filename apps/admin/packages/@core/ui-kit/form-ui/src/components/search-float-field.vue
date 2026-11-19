<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import { cn, isString } from '@vben-core/shared/utils';
import { VbenRenderContent } from '@vben-core/shadcn-ui';

import type { CustomRenderType } from '../types';

const props = withDefaults(
  defineProps<{
    filled?: boolean;
    label?: CustomRenderType;
    withIcon?: boolean;
  }>(),
  {
    filled: false,
    withIcon: false,
  },
);

const focused = ref(false);
let blurTimer: null | ReturnType<typeof setTimeout> = null;
const rootRef = ref<HTMLElement | null>(null);

const isActive = computed(() => focused.value || props.filled);
const labelText = computed(() => (isString(props.label) ? props.label : ''));

function clearBlurTimer() {
  if (blurTimer) {
    clearTimeout(blurTimer);
    blurTimer = null;
  }
}

function handleFocusIn() {
  clearBlurTimer();
  focused.value = true;
}

function handleFocusOut(event: FocusEvent) {
  const related = event.relatedTarget as Node | null;
  if (related && rootRef.value?.contains(related)) {
    return;
  }
  scheduleUnfocus();
}

function scheduleUnfocus() {
  clearBlurTimer();
  blurTimer = setTimeout(() => {
    if (rootRef.value?.contains(document.activeElement)) {
      return;
    }
    focused.value = false;
  }, 120);
}

function handleDocumentMouseDown(event: MouseEvent) {
  if (!focused.value || !rootRef.value) {
    return;
  }
  const target = event.target as Node | null;
  if (target && rootRef.value.contains(target)) {
    return;
  }
  focused.value = false;
}

onMounted(() => {
  document.addEventListener('mousedown', handleDocumentMouseDown, true);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleDocumentMouseDown, true);
  clearBlurTimer();
});
</script>

<template>
  <div
    ref="rootRef"
    :class="
      cn('search-float-field', {
        'is-active': isActive,
        'is-focused': focused,
        'has-prefix-icon': withIcon,
      })
    "
    @focusin="handleFocusIn"
    @focusout="handleFocusOut"
  >
    <span v-if="label" class="search-float-label">
      <VbenRenderContent v-if="!labelText" :content="label" />
      <template v-else>{{ labelText }}</template>
    </span>
    <div class="search-float-control">
      <slot :active="isActive" :focused="focused"></slot>
    </div>
  </div>
</template>
