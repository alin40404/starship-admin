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
  /** 箭头 left 额外偏移（px），正数往右 */
  arrowOffsetX?: number;
  class?: ClassType;
  contentClass?: ClassType;
  contentProps?: PopoverContentProps;
  /** 是否显示指向触发器的指示箭头 */
  showArrow?: boolean;
  triggerClass?: ClassType;
}

const props = withDefaults(defineProps<Props>(), {
  arrowOffsetX: 0,
  showArrow: false,
});

const emits = defineEmits<PopoverRootEmits>();

const delegatedProps = computed(() => {
  const {
    arrowOffsetX: _arrowOffsetX,
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

const contentStyle = computed(() => {
  if (!props.showArrow || !props.arrowOffsetX) {
    return undefined;
  }
  return {
    // 作用到定位 span：自动 left + 该偏移
    '--vben-popover-arrow-offset-x': `${props.arrowOffsetX}px`,
  } as Record<string, string>;
});
</script>

<template>
  <PopoverRoot v-bind="forwarded">
    <PopoverTrigger :class="triggerClass">
      <slot name="trigger"></slot>

      <PopoverContent
        :class="[
          contentClass,
          showArrow && arrowOffsetX ? 'vben-popover--arrow-offset-x' : '',
        ]"
        class="side-content z-popup"
        :style="contentStyle"
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

<style>
/*
 * PopoverArrow 的定位写在外层 span 的 inline left 上，
 * class/style 只会落到内部 svg，所以用变量 + :has 给 span 加偏移。
 */
.vben-popover--arrow-offset-x > span:has(> svg.fill-popover) {
  margin-left: var(--vben-popover-arrow-offset-x, 0);
}
</style>
