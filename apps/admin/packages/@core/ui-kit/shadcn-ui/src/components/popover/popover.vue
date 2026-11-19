<script setup lang="ts">
import type {
  PopoverContentProps,
  PopoverRootEmits,
  PopoverRootProps,
} from 'reka-ui';

import type { ClassType } from '@vben-core/typings';

import { computed } from 'vue';

import { PopoverArrow, useForwardPropsEmits } from 'reka-ui';

import {
  PopoverContent,
  Popover as PopoverRoot,
  PopoverTrigger,
} from '../../ui';

interface Props extends PopoverRootProps {
  class?: ClassType;
  contentClass?: ClassType;
  contentProps?: PopoverContentProps;
  /** 是否显示指向触发器的指示箭头 */
  showArrow?: boolean;
  triggerClass?: ClassType;
}

const props = withDefaults(defineProps<Props>(), {
  showArrow: false,
});

const emits = defineEmits<PopoverRootEmits>();

const delegatedProps = computed(() => {
  const {
    class: _cls,
    contentClass: _,
    contentProps: _cProps,
    showArrow: _showArrow,
    triggerClass: _tClass,
    ...delegated
  } = props;

  return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);

const mergedContentProps = computed(() => {
  const base = { ...(props.contentProps ?? {}) } as PopoverContentProps &
    Record<string, unknown>;
  if (props.showArrow && base.sideOffset === undefined) {
    // 给箭头留出间距，避免贴边裁切
    base.sideOffset = 8;
  }
  return base;
});
</script>

<template>
  <PopoverRoot v-bind="forwarded">
    <PopoverTrigger :class="triggerClass">
      <slot name="trigger"></slot>

      <PopoverContent
        :class="contentClass"
        class="side-content z-popup"
        v-bind="mergedContentProps"
      >
        <slot></slot>
        <PopoverArrow
          v-if="showArrow"
          :height="6"
          :width="12"
          class="fill-popover stroke-border stroke-1"
        />
      </PopoverContent>
    </PopoverTrigger>
  </PopoverRoot>
</template>
