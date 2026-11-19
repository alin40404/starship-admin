<script setup lang="ts">
import { computed, toRaw, unref, watch } from 'vue';

import { Filter, RotateCcw } from '@vben-core/icons';
import { useSimpleLocale } from '@vben-core/composables';
import { VbenExpandableArrow } from '@vben-core/shadcn-ui';
import { cn, isFunction, triggerWindowResize } from '@vben-core/shared/utils';

import { COMPONENT_MAP } from '../config';
import { injectFormProps } from '../use-form-context';

const { $t } = useSimpleLocale();

const [rootProps, form] = injectFormProps();

const collapsed = defineModel({ default: false });

const resetButtonOptions = computed(() => {
  return {
    content: `${$t.value('reset')}`,
    show: true,
    ...unref(rootProps).resetButtonOptions,
  };
});

const submitButtonOptions = computed(() => {
  return {
    content: `${$t.value('submit')}`,
    show: true,
    ...unref(rootProps).submitButtonOptions,
  };
});

const showActionIcons = computed(() => {
  const props = unref(rootProps) as Record<string, any>;
  const storeState = props.formApi?.store?.state as
    | Record<string, any>
    | undefined;
  return !!(
    props.showActionIcons ||
    props.floatLabel ||
    storeState?.showActionIcons ||
    storeState?.floatLabel
  );
});
const showFilterButton = computed(() => {
  const props = unref(rootProps) as Record<string, any>;
  const storeState = props.formApi?.store?.state as
    | Record<string, any>
    | undefined;
  return !!(props.showFilterButton || storeState?.showFilterButton);
});
const filterCount = computed(() => {
  const props = unref(rootProps) as Record<string, any>;
  const storeState = props.formApi?.store?.state as
    | Record<string, any>
    | undefined;
  return props.filterCount ?? storeState?.filterCount ?? 0;
});

const resetButtonBindProps = computed(() => {
  const { content: _content, show: _show, ...rest } = resetButtonOptions.value;
  return rest;
});

const submitButtonBindProps = computed(() => {
  const { content: _content, show: _show, ...rest } = submitButtonOptions.value;
  return rest;
});

async function handleSubmit(e: Event) {
  e?.preventDefault();
  e?.stopPropagation();
  const props = unref(rootProps);
  if (!props.formApi) {
    return;
  }

  const { valid } = await props.formApi.validate();
  if (!valid) {
    return;
  }

  const values = toRaw(await props.formApi.getValues()) ?? {};
  await props.handleSubmit?.(values);
}

async function handleReset(e: Event) {
  e?.preventDefault();
  e?.stopPropagation();
  const props = unref(rootProps);

  const values = toRaw(await props.formApi?.getValues()) ?? {};

  if (isFunction(props.handleReset)) {
    await props.handleReset?.(values);
  } else {
    form.resetForm();
  }
}

function handleFilterClick(e: Event) {
  e?.preventDefault();
  e?.stopPropagation();
  unref(rootProps).handleFilterClick?.();
}

watch(
  () => collapsed.value,
  () => {
    const props = unref(rootProps);
    if (props.collapseTriggerResize) {
      triggerWindowResize();
    }
  },
);

const isFloatSearchToolbar = computed(() => {
  const props = unref(rootProps) as Record<string, any>;
  const storeState = props.formApi?.store?.state as
    | Record<string, any>
    | undefined;
  return !!(props.floatLabel || storeState?.floatLabel);
});

const actionWrapperClass = computed(() => {
  const props = unref(rootProps);
  const actionLayout = props.actionLayout || 'rowEnd';
  const actionPosition = props.actionPosition || 'right';
  const floatToolbar = isFloatSearchToolbar.value;

  const cls = [
    'flex',
    'items-center',
    'gap-2.5',
    props.compact ? 'pb-2' : 'pb-4',
    floatToolbar || props.layout === 'inline' ? 'self-center' : '',
    props.layout === 'vertical' && !floatToolbar ? 'self-end' : '',
    props.layout === 'inline' || floatToolbar ? '' : 'w-full',
    // 紧挨字段，不要被 justify-end 拉到最右侧
    floatToolbar ? 'shrink-0 !w-auto !pb-0 !justify-start' : '',
    props.actionWrapperClass,
  ];

  switch (actionLayout) {
    case 'newLine': {
      cls.push('col-span-full');
      break;
    }
    case 'rowEnd': {
      if (!floatToolbar && props.layout !== 'inline') {
        cls.push('col-[-2/-1]');
      }
      break;
    }
  }

  if (!floatToolbar) {
    switch (actionPosition) {
      case 'center': {
        cls.push('justify-center');
        break;
      }
      case 'left': {
        cls.push('justify-start');
        break;
      }
      default: {
        cls.push('justify-end');
        break;
      }
    }
  }

  return cls.join(' ');
});

defineExpose({
  handleReset,
  handleSubmit,
});
</script>
<template>
  <div :class="cn(actionWrapperClass)">
    <template v-if="rootProps.actionButtonsReverse">
      <slot name="submit-before"></slot>

      <component
        :is="COMPONENT_MAP.PrimaryButton"
        v-if="submitButtonOptions.show"
        type="button"
        v-bind="submitButtonBindProps"
        @click="handleSubmit"
      >
        {{ submitButtonOptions.content }}
      </component>
    </template>

    <slot name="reset-before"></slot>

    <component
      :is="COMPONENT_MAP.DefaultButton"
      v-if="showFilterButton"
      type="button"
      class="search-filter-trigger"
      @click="handleFilterClick"
    >
      <template v-if="showActionIcons" #icon>
        <Filter class="size-4" />
      </template>
      {{ $t('filter') }}
      <span v-if="filterCount > 0" class="search-filter-count">
        {{ filterCount }}
      </span>
    </component>

    <component
      :is="COMPONENT_MAP.DefaultButton"
      v-if="resetButtonOptions.show"
      type="button"
      class="search-reset-trigger"
      v-bind="resetButtonBindProps"
      @click="handleReset"
    >
      <template v-if="showActionIcons" #icon>
        <RotateCcw class="size-4" />
      </template>
      {{ resetButtonOptions.content }}
    </component>

    <template v-if="!rootProps.actionButtonsReverse">
      <slot name="submit-before"></slot>

      <component
        :is="COMPONENT_MAP.PrimaryButton"
        v-if="submitButtonOptions.show"
        type="button"
        v-bind="submitButtonBindProps"
        @click="handleSubmit"
      >
        {{ submitButtonOptions.content }}
      </component>
    </template>

    <slot name="expand-before"></slot>

    <VbenExpandableArrow
      class="ml-[-0.3em]"
      v-if="rootProps.showCollapseButton"
      v-model:model-value="collapsed"
    >
      <span>{{ collapsed ? $t('expand') : $t('collapse') }}</span>
    </VbenExpandableArrow>

    <slot name="expand-after"></slot>
  </div>
</template>
