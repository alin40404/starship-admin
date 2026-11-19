<script lang="ts" setup>
import type {
  VxeGridDefines,
  VxeGridInstance,
  VxeGridListeners,
  VxeGridPropTypes,
  VxeGridProps as VxeTableGridProps,
  VxeToolbarPropTypes,
} from 'vxe-table';

import type { SetupContext } from 'vue';

import type { VbenFormProps } from '@vben-core/form-ui';

import type { ExtendedVxeGridApi, VxeGridProps } from './types';

import {
  computed,
  nextTick,
  onMounted,
  onUnmounted,
  ref,
  toRaw,
  useSlots,
  useTemplateRef,
  watch,
} from 'vue';

import { usePriorityValues } from '@vben/hooks';
import { Filter, RotateCcw } from '@vben/icons';
import { $t } from '@vben/locales';
import { usePreferences } from '@vben/preferences';
import {
  cloneDeep,
  cn,
  isBoolean,
  isEqual,
  mergeWithArrayOverride,
} from '@vben/utils';
import { onClickOutside } from '@vueuse/core';

import {
  countFilledSearchFields,
  resolveCrudSearchViews,
} from '@vben-core/form-ui';
import { globalShareState } from '@vben-core/shared/global-state';
import { VbenHelpTooltip } from '@vben-core/shadcn-ui';

import { VxeButton } from 'vxe-pc-ui';
import { VxeGrid, VxeUI } from 'vxe-table';

import { extendProxyOptions } from './extends';
import { useTableForm } from './init';

import 'vxe-table/styles/cssvar.scss';
import 'vxe-pc-ui/styles/cssvar.scss';
import './style.css';

interface Props extends VxeGridProps {
  api: ExtendedVxeGridApi;
}

const props = withDefaults(defineProps<Props>(), {});

const FORM_SLOT_PREFIX = 'form-';

const TOOLBAR_ACTIONS = 'toolbar-actions';
const TOOLBAR_TOOLS = 'toolbar-tools';
const TABLE_TITLE = 'table-title';

const gridRef = useTemplateRef<VxeGridInstance>('gridRef');

const state = props.api?.useStore?.();

const {
  gridOptions,
  class: className,
  gridClass,
  gridEvents,
  formOptions,
  tableTitle,
  tableData,
  tableTitleHelp,
  showSearchForm,
  separator,
} = usePriorityValues(props, state);

const { isMobile } = usePreferences();

/** 未显式配置时：PC 展开、移动端收起 */
const resolvedShowSearchForm = computed(() => {
  const configured = showSearchForm.value;
  if (isBoolean(configured)) {
    return configured;
  }
  return !isMobile.value;
});

/** 移动端搜索走底部弹窗，不在表格内联展开 */
const showInlineSearchForm = computed(
  () => !isMobile.value && resolvedShowSearchForm.value,
);

const searchDrawerOpen = ref(false);
const filterPanelOpen = ref(false);
const filterCount = ref(0);
const filterTriggerRef = ref<HTMLElement | null>(null);
const filterPanelRef = ref<HTMLElement | null>(null);
const filterPanelStyle = ref<Record<string, string>>({});
/** 重置表单时跳过 submitOnChange 触发的列表查询 */
const skipSearchSubmit = ref(false);
const components = globalShareState.getComponents();

/** Ant Design 下拉/日期面板挂在 body，点击选项不能当成面板外点击 */
const FILTER_PANEL_POPUP_SELECTOR = [
  '.ant-select-dropdown',
  '.ant-tree-select-dropdown',
  '.ant-cascader-dropdown',
  '.ant-picker-dropdown',
  '.ant-picker-panel-container',
  '.ant-dropdown',
  '.ant-modal',
  '.ant-modal-root',
  '.ant-popover',
].join(',');

onClickOutside(
  filterPanelRef,
  (event) => {
    const target = event.target as HTMLElement | null;
    if (target?.closest?.(FILTER_PANEL_POPUP_SELECTOR)) {
      return;
    }
    filterPanelOpen.value = false;
  },
  { ignore: [filterTriggerRef] },
);

function getFilterPanelBoundary() {
  // 优先限制在表格卡片内，避免盖住侧栏
  const gridEl = (gridRef.value as { $el?: HTMLElement } | null)?.$el;
  const host =
    (gridEl?.closest?.('.bg-card') as HTMLElement | null) ||
    (gridEl?.closest?.('[class*="rounded"]') as HTMLElement | null) ||
    gridEl ||
    null;
  if (host) {
    const r = host.getBoundingClientRect();
    return { left: r.left + 8, right: r.right - 8 };
  }
  return { left: 8, right: window.innerWidth - 8 };
}

function getFilterPanelPreferredWidth() {
  const fields = searchViews.value.pcPanel;
  const count = Math.max(1, fields.length);
  const cols = Math.min(3, count);
  const hasWideField = fields.some((item) => {
    const name = String(item.component ?? '');
    return ['RangePicker', 'DatePicker', 'ApiTreeSelect', 'TreeSelect'].includes(
      name,
    );
  });
  const colWidth = hasWideField ? 280 : 220;
  const gap = 16;
  const padding = 32;
  return cols * colWidth + (cols - 1) * gap + padding;
}

function updateFilterPanelPosition() {
  const trigger = filterTriggerRef.value;
  if (!trigger) {
    return;
  }
  const rect = trigger.getBoundingClientRect();
  const boundary = getFilterPanelBoundary();
  const maxWidth = Math.max(220, boundary.right - boundary.left);
  const width = Math.min(getFilterPanelPreferredWidth(), maxWidth, 780);
  const cols = Math.min(3, Math.max(1, searchViews.value.pcPanel.length));

  // 相对筛选按钮水平居中；贴边时再夹紧，避免越出内容区
  let left = rect.left + rect.width / 2 - width / 2;
  if (left < boundary.left) {
    left = boundary.left;
  }
  if (left + width > boundary.right) {
    left = boundary.right - width;
  }

  // 箭头对准按钮中心，并限制在面板内
  let arrowLeft = rect.left + rect.width / 2 - left;
  arrowLeft = Math.max(18, Math.min(width - 18, arrowLeft));

  filterPanelStyle.value = {
    position: 'fixed',
    top: `${Math.round(rect.bottom + 8)}px`,
    left: `${Math.round(left)}px`,
    width: `${Math.round(width)}px`,
    zIndex: '1100',
    '--search-filter-arrow-left': `${Math.round(arrowLeft)}px`,
    '--search-filter-cols': String(cols),
  };
}

function onFilterPanelReposition() {
  if (!filterPanelOpen.value) {
    return;
  }
  updateFilterPanelPosition();
}

watch(filterPanelOpen, async (open) => {
  if (!open) {
    return;
  }
  await nextTick();
  updateFilterPanelPosition();
});

const isSearchDrawerOpen = computed(() => searchDrawerOpen.value);
const searchViews = computed(() =>
  resolveCrudSearchViews(formOptions.value?.schema),
);
const hasFilterPanel = computed(() => searchViews.value.pcPanel.length > 0);

const isSearchPanelActive = computed(() => {
  if (isMobile.value && formOptions.value) {
    return isSearchDrawerOpen.value;
  }
  return resolvedShowSearchForm.value;
});

const isSeparator = computed(() => {
  if (
    !formOptions.value ||
    showInlineSearchForm.value === false ||
    separator.value === false
  ) {
    return false;
  }
  if (separator.value === true || separator.value === undefined) {
    return true;
  }
  return separator.value.show !== false;
});
const separatorBg = computed(() => {
  return !separator.value ||
    isBoolean(separator.value) ||
    !separator.value.backgroundColor
    ? undefined
    : separator.value.backgroundColor;
});
const slots: SetupContext['slots'] = useSlots();

async function getMergedSearchValues() {
  const inlineValues = (await formApi.getValues()) ?? {};
  const panelValues = hasFilterPanel.value
    ? ((await panelFormApi.getValues()) ?? {})
    : {};
  return {
    ...inlineValues,
    ...panelValues,
  };
}

async function reloadWithMergedValues(force = false) {
  const formValues = await getMergedSearchValues();
  formApi.setLatestSubmissionValues(toRaw(formValues));
  if (force || formOptions.value?.submitOnChange !== false) {
    await props.api.reload(formValues);
  }
}

async function refreshFilterCount() {
  if (!hasFilterPanel.value) {
    filterCount.value = 0;
    return;
  }
  const panelValues = (await panelFormApi.getValues()) ?? {};
  filterCount.value = countFilledSearchFields(
    panelValues,
    searchViews.value.pcPanel,
    formOptions.value?.fieldMappingTime as
      | Array<[string, [string, string], ...any[]]>
      | undefined,
  );
}

const [Form, formApi] = useTableForm({
  compact: true,
  handleSubmit: async () => {
    if (skipSearchSubmit.value) {
      return;
    }
    await reloadWithMergedValues(true);
  },
  handleReset: async () => {
    await handleSearchReset();
  },
  commonConfig: {
    componentProps: {
      class: 'w-full',
    },
    // PC 浮动标签模式：不显示左侧外置 label
    hideLabel: true,
  },
  floatLabel: true,
  layout: 'inline',
  resetButtonOptions: {
    show: false,
  },
  showActionIcons: true,
  showCollapseButton: false,
  submitButtonOptions: {
    show: false,
  },
  submitOnChange: true,
  wrapperClass: 'vxe-grid-search-toolbar',
});

const [PanelForm, panelFormApi] = useTableForm({
  // 对齐 CRM 筛选面板：上标签 + 控件纵向排列
  compact: true,
  commonConfig: {
    componentProps: {
      class: 'w-full',
    },
    hideLabel: false,
    labelClass: 'search-filter-label',
  },
  floatLabel: false,
  handleValuesChange: async () => {
    await refreshFilterCount();
    if (formOptions.value?.submitOnChange !== false) {
      await reloadWithMergedValues(true);
    }
  },
  layout: 'vertical',
  showCollapseButton: false,
  showDefaultActions: false,
  submitOnChange: false,
  wrapperClass: 'search-filter-panel-grid',
});

watch(
  isMobile,
  (mobile) => {
    const views = resolveCrudSearchViews(formOptions.value?.schema);
    formApi.setState({
      actionLayout: 'inline',
      commonConfig: mobile
        ? {
            componentProps: { class: 'w-full' },
            hideLabel: false,
            labelClass: 'justify-start',
          }
        : {
            componentProps: { class: 'w-full' },
            hideLabel: true,
          },
      floatLabel: !mobile,
      layout: mobile ? 'vertical' : 'inline',
      schema: mobile ? views.mobileDrawer : views.pcInline,
      resetButtonOptions: { show: false },
      showActionIcons: !mobile,
      showCollapseButton: false,
      showDefaultActions: !mobile,
      submitButtonOptions: { show: false },
      wrapperClass: mobile ? 'grid-cols-1' : 'vxe-grid-search-toolbar',
    });
  },
  { immediate: true },
);

async function handleMobileSearchReset() {
  await formApi.resetForm();
  if (hasFilterPanel.value) {
    await panelFormApi.resetForm();
  }
  const formValues = await getMergedSearchValues();
  formApi.setLatestSubmissionValues(formValues);
  filterCount.value = 0;
  await props.api.reload(formValues);
}

async function handleMobileSearchSubmit() {
  const { valid } = await formApi.validate();
  if (!valid) {
    return;
  }
  const formValues = await getMergedSearchValues();
  formApi.setLatestSubmissionValues(toRaw(formValues));
  searchDrawerOpen.value = false;
  await props.api.reload(formValues);
}

async function handleFilterPanelReset() {
  await panelFormApi.resetForm();
  await refreshFilterCount();
  await reloadWithMergedValues(true);
}

async function handleSearchReset() {
  const prevValues = await getMergedSearchValues();
  // 拦住 submitOnChange，避免与下面手动 reload 重复请求
  skipSearchSubmit.value = true;
  try {
    await formApi.resetForm();
    if (hasFilterPanel.value) {
      await panelFormApi.resetForm();
    }
    await nextTick();
    const formValues = await getMergedSearchValues();
    formApi.setLatestSubmissionValues(toRaw(formValues));
    filterCount.value = 0;
    // 字段确实变了才重新查表；没变就不请求
    if (!isEqual(prevValues, formValues)) {
      await props.api.reload(formValues);
    }
    // 等过值变更防抖后再恢复 submitOnChange
    await new Promise((resolve) => setTimeout(resolve, 320));
  } finally {
    skipSearchSubmit.value = false;
  }
}

const showTableTitle = computed(() => {
  // 只判断插槽是否存在，禁止在 computed 中调用 slots.xxx()（会脱离 render 上下文）
  return !!slots[TABLE_TITLE] || !!tableTitle.value;
});

const showToolbar = computed(() => {
  return (
    !!slots[TOOLBAR_ACTIONS] ||
    !!slots[TOOLBAR_TOOLS] ||
    showTableTitle.value
  );
});

const toolbarOptions = computed(() => {
  const hasActions = !!slots[TOOLBAR_ACTIONS] || showTableTitle.value;
  const hasTools = !!slots[TOOLBAR_TOOLS];
  const searchBtn: VxeToolbarPropTypes.ToolConfig = {
    code: 'search',
    icon: 'vxe-icon-search',
    circle: true,
    status: isSearchPanelActive.value ? 'primary' : undefined,
    title: isSearchPanelActive.value
      ? $t('common.hideSearchPanel')
      : $t('common.showSearchPanel'),
  };
  // 将搜索按钮合并到用户配置的toolbarConfig.tools中
  const toolbarConfig: VxeGridPropTypes.ToolbarConfig = {
    tools: (gridOptions.value?.toolbarConfig?.tools ??
      []) as VxeToolbarPropTypes.ToolConfig[],
  };
  if (gridOptions.value?.toolbarConfig?.search && !!formOptions.value) {
    toolbarConfig.tools = Array.isArray(toolbarConfig.tools)
      ? [...toolbarConfig.tools, searchBtn]
      : [searchBtn];
  }

  if (!showToolbar.value) {
    toolbarConfig.enabled = false;
    return { toolbarConfig };
  }

  // 强制使用固定的toolbar配置，不允许用户自定义
  // 减少配置的复杂度，以及后续维护的成本
  toolbarConfig.slots = {
    ...(hasActions ? { buttons: TOOLBAR_ACTIONS } : {}),
    ...(hasTools ? { tools: TOOLBAR_TOOLS } : {}),
  };
  return { toolbarConfig };
});

const options = computed(() => {
  const globalGridConfig = VxeUI?.getConfig()?.grid ?? {};

  const mergedOptions: VxeTableGridProps = cloneDeep(
    mergeWithArrayOverride(
      {},
      toRaw(toolbarOptions.value),
      toRaw(gridOptions.value),
      globalGridConfig,
    ),
  );

  if (mergedOptions.proxyConfig) {
    const { ajax } = mergedOptions.proxyConfig;
    mergedOptions.proxyConfig.enabled = !!ajax;
    // 不自动加载数据, 由组件控制
    mergedOptions.proxyConfig.autoLoad = false;
  }

  if (mergedOptions.pagerConfig) {
    const mobileLayouts = [
      'PrevJump',
      'PrevPage',
      'Number',
      'NextPage',
      'NextJump',
    ] as any;
    const layouts = [
      'Total',
      'Sizes',
      'Home',
      ...mobileLayouts,
      'End',
    ] as readonly string[];
    mergedOptions.pagerConfig = mergeWithArrayOverride(
      {},
      mergedOptions.pagerConfig,
      {
        pageSize: globalGridConfig?.pagerConfig?.pageSize ?? 20,
        background: true,
        pageSizes: [10, 20, 30, 50, 100, 200],
        className: 'mt-2 w-full',
        layouts: isMobile.value ? mobileLayouts : layouts,
        size: 'mini' as const,
      },
    );
  }
  // 移动端收窄表格内虚拟滚动条（X/Y 轴）
  if (isMobile.value) {
    mergedOptions.scrollbarConfig = mergeWithArrayOverride(
      {},
      mergedOptions.scrollbarConfig,
      {
        width: 3,
        height: 3,
      },
    );
  }
  if (mergedOptions.formConfig) {
    mergedOptions.formConfig.enabled = false;
    if (tableData.value && tableData.value.length > 0) {
      mergedOptions.data = tableData.value;
    }
  }
  // 默认空状态走 renderer，避免 vxe callSlot(#empty) 在卸载/异步更新时触发 Vue slot 警告与 unmount 崩溃
  if (
    !slots.empty &&
    mergedOptions.emptyText === undefined &&
    mergedOptions.emptyRender === undefined
  ) {
    mergedOptions.emptyRender = { name: 'VbenTableEmpty' };
  }
  return mergedOptions;
});

function onToolbarToolClick(event: VxeGridDefines.ToolbarToolClickEventParams) {
  if (event.code === 'search') {
    onSearchBtnClick();
  }
  (
    gridEvents.value?.toolbarToolClick as VxeGridListeners['toolbarToolClick']
  )?.(event);
}

function onSearchBtnClick() {
  if (isMobile.value && formOptions.value) {
    searchDrawerOpen.value = !searchDrawerOpen.value;
    return;
  }
  props.api?.toggleSearchForm?.(!resolvedShowSearchForm.value);
}

const events = computed(() => {
  return {
    ...gridEvents.value,
    toolbarToolClick: onToolbarToolClick,
  };
});

const delegatedSlots = computed(() => {
  const resultSlots: string[] = [];

  for (const key of Object.keys(slots)) {
    if (
      !['empty', 'form', 'loading', TOOLBAR_ACTIONS, TOOLBAR_TOOLS].includes(
        key,
      )
    ) {
      resultSlots.push(key);
    }
  }
  return resultSlots;
});

const delegatedFormSlots = computed(() => {
  const resultSlots: string[] = [];

  for (const key of Object.keys(slots)) {
    if (key.startsWith(FORM_SLOT_PREFIX)) {
      resultSlots.push(key);
    }
  }
  return resultSlots.map((key) => key.replace(FORM_SLOT_PREFIX, ''));
});

/** 仅在业务侧显式传入 #empty 时走插槽；默认空状态已改用 emptyRender */
const hasEmptySlot = computed(() => !!slots.empty);
/** 仅在业务侧显式传入 #loading 时走插槽，避免 vxe 包成 VxeLoading default 槽触发 Vue 警告 */
const hasLoadingSlot = computed(() => !!slots.loading);

async function init() {
  await nextTick();
  // 快速切页时可能已卸载，避免对销毁中的 grid 继续 query
  if (!gridRef.value) {
    return;
  }
  const globalGridConfig = VxeUI?.getConfig()?.grid ?? {};
  const defaultGridOptions: VxeTableGridProps = mergeWithArrayOverride(
    {},
    toRaw(gridOptions.value),
    toRaw(globalGridConfig),
  );
  // 内部主动加载数据，防止form的默认值影响
  const autoLoad = defaultGridOptions.proxyConfig?.autoLoad;
  const enableProxyConfig = options.value.proxyConfig?.enabled;
  if (enableProxyConfig && autoLoad) {
    const formValues = formOptions.value
      ? await getMergedSearchValues()
      : {};
    if (formOptions.value) {
      formApi.setLatestSubmissionValues(toRaw(formValues));
    }
    if (!gridRef.value) {
      return;
    }
    props.api.grid.commitProxy?.('query', formValues);
  }

  // form 由 vben-form代替，所以不适配formConfig，这里给出警告
  const formConfig = gridOptions.value?.formConfig;
  // 处理某个页面加载多个Table时，第2个之后的Table初始化报出警告
  // 因为第一次初始化之后会把defaultGridOptions和gridOptions合并后缓存进State
  if (formConfig && formConfig.enabled) {
    console.warn(
      '[Vben Vxe Table]: The formConfig in the grid is not supported, please use the `formOptions` props',
    );
  }
  props.api?.setState?.({ gridOptions: defaultGridOptions });
  // form 由 vben-form 代替，所以需要保证query相关事件可以拿到参数
  extendProxyOptions(props.api, defaultGridOptions, () =>
    formApi.getLatestSubmissionValues(),
  );
}

// formOptions支持响应式；PC 按 inFilterPanel 拆工具栏 / 筛选面板
watch(
  formOptions,
  () => {
    const views = resolveCrudSearchViews(formOptions.value?.schema);
    const mobile = isMobile.value;

    formApi.setState((prev: Record<string, any>) => {
      const finalFormOptions: VbenFormProps = mergeWithArrayOverride(
        {},
        formOptions.value,
        prev,
      );
      const schema = mobile
        ? views.mobileDrawer
        : views.pcInline.length > 0 || views.pcPanel.length > 0
          ? views.pcInline
          : finalFormOptions.schema;

      return {
        ...finalFormOptions,
        actionLayout: 'inline',
        collapseTriggerResize: false,
        commonConfig: {
          ...finalFormOptions.commonConfig,
          componentProps: {
            class: 'w-full',
            ...finalFormOptions.commonConfig?.componentProps,
          },
          hideLabel: !mobile,
        },
        floatLabel: !mobile,
        layout: mobile ? 'vertical' : 'inline',
        schema,
        resetButtonOptions: {
          ...finalFormOptions.resetButtonOptions,
          show: false,
        },
        showActionIcons: !mobile,
        showCollapseButton: false,
        showDefaultActions: !mobile,
        submitButtonOptions: {
          ...finalFormOptions.submitButtonOptions,
          show: false,
        },
        submitOnChange: finalFormOptions.submitOnChange ?? true,
        wrapperClass: mobile
          ? 'grid-cols-1'
          : (finalFormOptions.wrapperClass ?? 'vxe-grid-search-toolbar'),
      };
    });

    panelFormApi.setState({
      compact: true,
      commonConfig: {
        componentProps: {
          class: 'w-full',
        },
        hideLabel: false,
        labelClass: 'search-filter-label',
      },
      fieldMappingTime: formOptions.value?.fieldMappingTime,
      floatLabel: false,
      layout: 'vertical',
      schema: views.pcPanel,
      showDefaultActions: false,
      wrapperClass: 'search-filter-panel-grid',
    });

    void refreshFilterCount();
  },
  {
    immediate: true,
  },
);

const isCompactForm = computed(() => {
  return formApi.getState()?.compact;
});

onMounted(() => {
  props.api?.mount?.(gridRef.value, formApi);
  init();
  window.addEventListener('resize', onFilterPanelReposition);
  window.addEventListener('scroll', onFilterPanelReposition, true);
});

onUnmounted(() => {
  window.removeEventListener('resize', onFilterPanelReposition);
  window.removeEventListener('scroll', onFilterPanelReposition, true);
  formApi?.unmount?.();
  props.api?.unmount?.();
});
</script>

<template>
  <div :class="cn('h-full rounded-md bg-card', className)">
    <VxeGrid
      ref="gridRef"
      :class="
        cn(
          'p-2',
          {
            'pt-0': showToolbar && !formOptions,
          },
          gridClass,
        )
      "
      v-bind="options"
      v-on="events"
    >
      <!-- 左侧操作区域或者title -->
      <template v-if="showToolbar" #toolbar-actions="slotProps">
        <slot v-if="showTableTitle" name="table-title">
          <div class="flex-center gap-1 text-[1rem] font-bold">
            {{ tableTitle }}
            <VbenHelpTooltip v-if="tableTitleHelp">
              {{ tableTitleHelp }}
            </VbenHelpTooltip>
          </div>
        </slot>
        <slot name="toolbar-actions" v-bind="slotProps"> </slot>
      </template>

      <!-- 继承默认的slot -->
      <template
        v-for="slotName in delegatedSlots"
        :key="slotName"
        #[slotName]="slotProps"
      >
        <slot :name="slotName" v-bind="slotProps"></slot>
      </template>
      <template #toolbar-tools="slotProps">
        <slot name="toolbar-tools" v-bind="slotProps"></slot>
        <VxeButton
          icon="vxe-icon-search"
          circle
          class="ml-2"
          v-if="gridOptions?.toolbarConfig?.search && !!formOptions"
          :status="isSearchPanelActive ? 'primary' : undefined"
          :title="$t('common.search')"
          @click="onSearchBtnClick"
        />
      </template>

      <!-- form表单 -->
      <template #form>
        <div
          v-if="formOptions && !isMobile"
          v-show="showInlineSearchForm"
          :class="
            cn(
              'relative rounded-sm py-3',
              isCompactForm
                ? isSeparator
                  ? 'pb-8'
                  : 'pb-4'
                : isSeparator
                  ? 'pb-4'
                  : 'pb-0',
            )
          "
        >
          <slot name="form">
            <Form>
              <template
                v-for="slotName in delegatedFormSlots"
                :key="slotName"
                #[slotName]="slotProps"
              >
                <slot
                  :name="`${FORM_SLOT_PREFIX}${slotName}`"
                  v-bind="slotProps"
                ></slot>
              </template>
              <template #reset-before="slotProps">
                <slot name="reset-before" v-bind="slotProps"></slot>
                <div
                  v-if="hasFilterPanel"
                  ref="filterTriggerRef"
                  class="search-filter-anchor"
                >
                  <component
                    :is="components.DefaultButton"
                    type="button"
                    class="search-filter-trigger"
                    @click="filterPanelOpen = !filterPanelOpen"
                  >
                    <template #icon>
                      <Filter class="size-4" />
                    </template>
                    {{ $t('common.filter') }}
                    <span
                      v-if="filterCount > 0"
                      class="search-filter-count"
                    >
                      {{ filterCount }}
                    </span>
                  </component>
                </div>
                <Teleport to="body">
                  <div
                    v-show="filterPanelOpen"
                    ref="filterPanelRef"
                    class="search-filter-panel"
                    :style="filterPanelStyle"
                    @mousedown.stop
                  >
                    <PanelForm />
                    <div class="search-filter-footer">
                      <component
                        :is="components.DefaultButton"
                        type="button"
                        @click="handleFilterPanelReset"
                      >
                        {{ $t('common.resetConditions') }}
                      </component>
                      <component
                        :is="components.PrimaryButton"
                        type="button"
                        @click="filterPanelOpen = false"
                      >
                        {{ $t('common.confirm') }}
                      </component>
                    </div>
                  </div>
                </Teleport>
                <component
                  :is="components.DefaultButton"
                  type="button"
                  class="search-reset-trigger"
                  @click="handleSearchReset"
                >
                  <template #icon>
                    <RotateCcw class="size-4" />
                  </template>
                  {{ $t('common.reset') }}
                </component>
              </template>
              <template #submit-before="slotProps">
                <slot name="submit-before" v-bind="slotProps"></slot>
              </template>
              <template #expand-before="slotProps">
                <slot name="expand-before" v-bind="slotProps"></slot>
              </template>
              <template #expand-after="slotProps">
                <slot name="expand-after" v-bind="slotProps"></slot>
              </template>
            </Form>
          </slot>
          <div
            v-if="isSeparator"
            :style="{
              ...(separatorBg ? { backgroundColor: separatorBg } : undefined),
            }"
            class="absolute bottom-1 -left-2 z-100 h-2 w-[calc(100%+1rem)] overflow-hidden bg-background-deep md:bottom-2 md:h-3"
          ></div>
        </div>
      </template>
      <!-- loading：仅业务自定义时挂插槽，默认用 vxe 自带 Loading -->
      <template v-if="hasLoadingSlot" #loading>
        <slot name="loading"></slot>
      </template>
      <!-- 自定义空状态（默认空状态见 emptyRender: VbenTableEmpty） -->
      <template v-if="hasEmptySlot" #empty="slotProps">
        <slot name="empty" v-bind="slotProps"></slot>
      </template>
    </VxeGrid>

    <component
      :is="components.Drawer"
      v-if="isMobile && formOptions && components.Drawer"
      v-model:open="searchDrawerOpen"
      class="vxe-grid-search-drawer"
      :height="480"
      placement="bottom"
      :title="$t('common.search')"
    >
      <Form>
        <template
          v-for="slotName in delegatedFormSlots"
          :key="slotName"
          #[slotName]="slotProps"
        >
          <slot :name="`${FORM_SLOT_PREFIX}${slotName}`" v-bind="slotProps"></slot>
        </template>
        <template #reset-before="slotProps">
          <slot name="reset-before" v-bind="slotProps"></slot>
        </template>
        <template #submit-before="slotProps">
          <slot name="submit-before" v-bind="slotProps"></slot>
        </template>
        <template #expand-before="slotProps">
          <slot name="expand-before" v-bind="slotProps"></slot>
        </template>
        <template #expand-after="slotProps">
          <slot name="expand-after" v-bind="slotProps"></slot>
        </template>
      </Form>
      <template #footer>
        <div class="vxe-grid-search-drawer__footer">
          <component
            :is="components.DefaultButton"
            @click="handleMobileSearchReset"
          >
            {{ $t('common.reset') }}
          </component>
          <component
            :is="components.PrimaryButton"
            @click="handleMobileSearchSubmit"
          >
            {{ $t('common.search') }}
          </component>
        </div>
      </template>
    </component>
  </div>
</template>
