<script lang="ts" setup>
import { ref, watch } from 'vue';

import { cn } from '@vben-core/shared/utils';

import { useContentSpinner } from './use-content-spinner';

defineOptions({
  name: 'BrandContentSpinner',
});

const props = withDefaults(
  defineProps<{
    class?: string;
    /** Logo 图片地址，默认使用应用 public 下的品牌 Logo */
    logoSrc?: string;
    minLoadingTime?: number;
  }>(),
  {
    logoSrc: '/logo.png',
    minLoadingTime: 50,
  },
);

const { spinning } = useContentSpinner();

const showSpinner = ref(false);
const renderSpinner = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

watch(
  spinning,
  (show) => {
    if (!show) {
      showSpinner.value = false;
      if (timer) clearTimeout(timer);
      return;
    }

    timer = setTimeout(() => {
      showSpinner.value = true;
      renderSpinner.value = true;
    }, props.minLoadingTime);
  },
  { immediate: true },
);

function onTransitionEnd() {
  if (!showSpinner.value) {
    renderSpinner.value = false;
  }
}
</script>

<template>
  <div
    :class="
      cn(
        'flex-center bg-overlay-content absolute top-0 left-0 z-100 size-full backdrop-blur-xs transition-all duration-500',
        {
          'invisible opacity-0': !showSpinner,
        },
        props.class,
      )
    "
    @transitionend="onTransitionEnd"
  >
    <div
      v-if="renderSpinner"
      class="brand-spinner"
      role="status"
      aria-label="加载中"
    >
      <div class="brand-spinner__group" aria-hidden="true">
        <img
          class="brand-spinner__logo"
          :src="logoSrc"
          alt=""
          width="48"
          height="48"
        />
        <span class="brand-spinner__track"></span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.brand-spinner {
  --brand-spinner-track: #dbe4ee;
  --brand-spinner-accent: #006fe6;

  display: grid;
  place-items: center;
}

html.dark .brand-spinner,
.dark .brand-spinner {
  --brand-spinner-track: #2b3749;
  --brand-spinner-accent: #5ba5ff;
}

.brand-spinner__group {
  display: grid;
  justify-items: center;
  gap: 16px;
}

.brand-spinner__logo {
  display: block;
  width: 48px;
  height: 48px;
  object-fit: contain;
}

.brand-spinner__track {
  position: relative;
  overflow: hidden;
  width: 64px;
  height: 4px;
  background: var(--brand-spinner-track);
  clip-path: polygon(3px 0, 100% 0, calc(100% - 3px) 100%, 0 100%);
}

.brand-spinner__track::before {
  position: absolute;
  inset: 0 auto 0 0;
  width: 22px;
  background: var(--brand-spinner-accent);
  clip-path: polygon(3px 0, 100% 0, calc(100% - 3px) 100%, 0 100%);
  content: '';
  animation: brand-spinner-pass 1500ms cubic-bezier(0.4, 0, 0.3, 1) infinite;
}

@keyframes brand-spinner-pass {
  from {
    transform: translateX(-22px);
  }

  to {
    transform: translateX(64px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .brand-spinner__track::before {
    animation: none;
    transform: translateX(21px);
  }
}
</style>
