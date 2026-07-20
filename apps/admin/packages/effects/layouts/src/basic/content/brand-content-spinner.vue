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
    /** Logo 图片地址，默认使用应用 public 下的全出血 Logo */
    logoSrc?: string;
    minLoadingTime?: number;
  }>(),
  {
    logoSrc: '/logo-fullbleed.png',
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
    <div v-if="renderSpinner" class="brand-spinner">
      <div class="brand-spinner__ring" aria-hidden="true"></div>
      <div class="brand-spinner__logo-stack">
        <img
          class="brand-spinner__logo brand-spinner__logo--gray"
          :src="logoSrc"
          alt=""
          width="48"
          height="48"
        />
        <div class="brand-spinner__logo-color" aria-hidden="true">
          <img
            class="brand-spinner__logo"
            :src="logoSrc"
            alt=""
            width="48"
            height="48"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.brand-spinner {
  position: relative;
  display: flex;
  width: 84px;
  height: 84px;
  align-items: center;
  justify-content: center;
}

.brand-spinner__ring {
  position: absolute;
  inset: 0;
  border: 2px solid hsl(var(--primary) / 18%);
  border-top-color: hsl(var(--primary));
  border-radius: 50%;
  animation: brand-spinner-spin 0.9s linear infinite;
}

.brand-spinner__logo-stack {
  position: relative;
  width: 48px;
  height: 48px;
}

.brand-spinner__logo {
  display: block;
  width: 48px;
  height: 48px;
  object-fit: contain;
  background: transparent;
}

.brand-spinner__logo--gray {
  filter: grayscale(1);
  opacity: 0.42;
}

.brand-spinner__logo-color {
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 0;
  overflow: hidden;
  mask-image: linear-gradient(to top, #000 72%, transparent 100%);
  -webkit-mask-image: linear-gradient(to top, #000 72%, transparent 100%);
  animation: brand-spinner-rise 2.4s ease-in-out infinite;
}

.brand-spinner__logo-color .brand-spinner__logo {
  position: absolute;
  bottom: 0;
  left: 0;
}

@keyframes brand-spinner-spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes brand-spinner-rise {
  0% {
    height: 0%;
  }

  45% {
    height: 100%;
  }

  60% {
    height: 100%;
  }

  100% {
    height: 0%;
  }
}
</style>
