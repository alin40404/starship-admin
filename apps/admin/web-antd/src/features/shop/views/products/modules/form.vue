<script lang="ts" setup>
import type {
  ProductMediaItem,
  ProductRow,
  ProductSavePayload,
  ProductSkuRow,
} from '#/features/shop/api';

import { computed, reactive, ref } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { Button, message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { isVideoUrl } from '#/components/attachment-field/utils';
import {
  createProduct,
  fetchBrandOptions,
  fetchProduct,
  fetchShopCategoryOptions,
  SHOP_FULFILLMENT_TYPE,
  SHOP_PRODUCT_STATUS,
  SHOP_PRODUCT_TYPE,
  updateProduct,
} from '#/features/shop/api';
import { $t } from '#/locales';

/** 全屏弹窗内容区宽度，标题/按钮与之左右对齐 */
const FORM_CONTENT_MAX_CLASS = 'max-w-[1200px]';

import {
  buildDetailFormSchema,
  buildOrganizationFormSchema,
  buildSkuCompareAtPriceCents,
  buildSkuExtra,
  buildStatusFormSchema,
  buildTitleFormSchema,
  createEmptySkuRow,
  defaultFulfillmentForProductType,
  inferVariablesFromSkus,
  parseProductVariables,
  skuRowsFromProduct,
  type ProductVariable,
  type SkuFormRow,
} from '../data';
import ProductMediaField from './product-media-field.vue';
import SkuEditor from './sku-editor.vue';

const emits = defineEmits<{ success: [] }>();

const editingId = ref<number | null>(null);
const categoryOptions = ref<{ label: string; value: number }[]>([]);
const brandOptions = ref<{ label: string; value: number }[]>([]);
const mediaUrls = ref<string[]>([]);

const enableMulti = ref(false);
const variables = ref<ProductVariable[]>([]);
const skuRows = ref<SkuFormRow[]>([createEmptySkuRow()]);
const skuEditorRef = ref<{ validate: () => string | null } | null>(null);

function normalizeMediaUrls(detail: ProductRow): string[] {
  if (detail.media?.length) {
    return detail.media
      .map((item) => String(item.url ?? '').trim())
      .filter(Boolean);
  }
  return [];
}

function buildMediaPayload(urls: string[]): ProductMediaItem[] | null {
  const list = urls.map((url) => String(url ?? '').trim()).filter(Boolean);
  if (list.length === 0) {
    return null;
  }

  return list.map((url) => ({
    type: isVideoUrl(url) ? 'video' : 'image',
    url,
  }));
}

const formCommon = {
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical' as const,
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 gap-y-1',
};

const [TitleForm, titleFormApi] = useVbenForm({
  ...formCommon,
  schema: buildTitleFormSchema(),
});

const [DetailForm, detailFormApi] = useVbenForm({
  ...formCommon,
  schema: buildDetailFormSchema(),
});

const [StatusForm, statusFormApi] = useVbenForm({
  ...formCommon,
  schema: buildStatusFormSchema(),
});

const [OrgForm, orgFormApi] = useVbenForm(
  reactive({
    ...formCommon,
    schema: buildOrganizationFormSchema({}),
  }) as any,
);

function refreshOrgSchema() {
  orgFormApi.setState({
    schema: buildOrganizationFormSchema({
      brandOptions: brandOptions.value,
      categoryOptions: categoryOptions.value,
    }),
  });
}

const formTitle = computed(() => (editingId.value ? '编辑商品' : '新增商品'));

type PopupApi = {
  close: () => void;
  getData: <T = Record<string, never>>() => T;
  lock: () => void;
  open: () => void;
  setData: <T>(data: T) => void;
  unlock: () => void;
};

async function refreshOptions() {
  const [cats, brands] = await Promise.all([
    fetchShopCategoryOptions(),
    fetchBrandOptions(),
  ]);
  categoryOptions.value = cats;
  brandOptions.value = brands.map((item) => ({
    label: item.name,
    value: item.id,
  }));
  refreshOrgSchema();
}

function buildSkusPayload(): ProductSkuRow[] {
  return skuRows.value.map((row) => ({
    id: row.id,
    sku_code: row.sku_code?.trim() || undefined,
    specs: enableMulti.value ? row.specs : null,
    price: Math.round(Number(row.price_yuan ?? 0) * 100),
    compare_at_price: buildSkuCompareAtPriceCents(row),
    stock: Number(row.stock ?? 0),
    status: Number(row.status ?? 1),
    extra: buildSkuExtra(row),
  }));
}

function toPayload(
  title: Record<string, unknown>,
  detail: Record<string, unknown>,
  status: Record<string, unknown>,
  org: Record<string, unknown>,
): ProductSavePayload {
  const media = buildMediaPayload(mediaUrls.value);
  const productType = String(org.product_type ?? SHOP_PRODUCT_TYPE.PHYSICAL);
  const fulfillmentType = String(
    org.fulfillment_type ?? defaultFulfillmentForProductType(productType),
  );
  const skus = buildSkusPayload();
  const price = skus.length
    ? Math.min(...skus.map((item) => item.price))
    : 0;
  const stock = skus.reduce((sum, item) => sum + Number(item.stock ?? 0), 0);

  const cleanedVariables = enableMulti.value
    ? variables.value
        .map((item) => ({
          name: item.name.trim(),
          values: item.values.map((v) => v.trim()).filter(Boolean),
        }))
        .filter((item) => item.name && item.values.length > 0)
    : [];

  return {
    product_type: productType,
    fulfillment_type: fulfillmentType,
    category_id: org.category_id ? Number(org.category_id) : null,
    brand_id: org.brand_id ? Number(org.brand_id) : null,
    name: String(title.name ?? ''),
    subtitle: (title.subtitle as string) || null,
    price,
    stock,
    media,
    description: (detail.description as string) || null,
    status: Number(status.status ?? SHOP_PRODUCT_STATUS.DRAFT),
    sort: Number(org.sort ?? 0),
    extra: cleanedVariables.length > 0 ? { variables: cleanedVariables } : null,
    skus,
  };
}

function resetSkuState() {
  enableMulti.value = false;
  variables.value = [];
  skuRows.value = [createEmptySkuRow()];
}

async function handleConfirm(popupApi: PopupApi) {
  const [{ valid: titleValid }, { valid: orgValid }] = await Promise.all([
    titleFormApi.validate(),
    orgFormApi.validate(),
  ]);
  if (!titleValid || !orgValid) return;

  const skuError = skuEditorRef.value?.validate() ?? null;
  if (skuError) {
    message.error(skuError);
    return;
  }

  const [title, detail, status, org] = await Promise.all([
    titleFormApi.getValues(),
    detailFormApi.getValues(),
    statusFormApi.getValues(),
    orgFormApi.getValues(),
  ]);

  popupApi.lock();
  try {
    const payload = toPayload(title, detail, status, org);
    if (!editingId.value) {
      await createProduct(payload);
      message.success($t('crud.created'));
    } else {
      await updateProduct(editingId.value, payload);
      message.success($t('crud.saved'));
    }
    popupApi.close();
    emits('success');
  } finally {
    popupApi.unlock();
  }
}

async function handleOpenChange(isOpen: boolean, popupApi: PopupApi) {
  if (!isOpen) {
    editingId.value = null;
    mediaUrls.value = [];
    resetSkuState();
    return;
  }

  await refreshOptions();
  titleFormApi.resetForm();
  detailFormApi.resetForm();
  statusFormApi.resetForm();
  orgFormApi.resetForm();
  mediaUrls.value = [];
  resetSkuState();

  const data = popupApi.getData<ProductRow | Record<string, never>>();
  const id =
    data && typeof (data as ProductRow).id === 'number'
      ? (data as ProductRow).id
      : null;
  editingId.value = id;

  if (id) {
    const detail = await fetchProduct(id);
    const productType = detail.product_type || SHOP_PRODUCT_TYPE.PHYSICAL;
    const parsedVariables = parseProductVariables(detail.extra);
    const inferredVariables = inferVariablesFromSkus(detail.skus);
    const rows = skuRowsFromProduct(detail.skus);
    const multi =
      parsedVariables.length > 0 ||
      inferredVariables.length > 0 ||
      (detail.skus?.length ?? 0) > 1;

    variables.value =
      parsedVariables.length > 0
        ? parsedVariables
        : inferredVariables.length > 0
          ? inferredVariables
          : multi
            ? [{ name: '', values: [] }]
            : [];
    skuRows.value = rows;
    enableMulti.value = multi;
    mediaUrls.value = normalizeMediaUrls(detail);

    await Promise.all([
      titleFormApi.setValues({
        name: detail.name,
        subtitle: detail.subtitle ?? '',
      }),
      detailFormApi.setValues({
        description: detail.description ?? '',
      }),
      statusFormApi.setValues({
        status: detail.status,
      }),
      orgFormApi.setValues({
        product_type: productType,
        fulfillment_type:
          detail.fulfillment_type ||
          defaultFulfillmentForProductType(productType),
        category_id: detail.category_id ?? undefined,
        brand_id: detail.brand_id ?? undefined,
        sort: detail.sort ?? 0,
      }),
    ]);
  } else {
    mediaUrls.value = [];
    await Promise.all([
      titleFormApi.setValues({ name: '', subtitle: '' }),
      detailFormApi.setValues({ description: '' }),
      statusFormApi.setValues({ status: SHOP_PRODUCT_STATUS.DRAFT }),
      orgFormApi.setValues({
        product_type: SHOP_PRODUCT_TYPE.PHYSICAL,
        fulfillment_type: SHOP_FULFILLMENT_TYPE.SHIPPING,
        category_id: undefined,
        brand_id: undefined,
        sort: 0,
      }),
    ]);
  }
}

const [Modal, modalApi] = useVbenModal({
  contentClass: 'bg-muted/40',
  destroyOnClose: true,
  // 标题落在内容区左缘；底栏按钮落在内容区右缘（头尾条仍通栏）
  footerClass: '!justify-center px-5',
  fullscreen: true,
  fullscreenButton: false,
  headerClass: 'px-5 *:mx-auto *:w-full *:max-w-[1200px]',
  async onConfirm() {
    await handleConfirm(modalApi as PopupApi);
  },
  async onOpenChange(isOpen: boolean) {
    await handleOpenChange(isOpen, modalApi as PopupApi);
  },
});

const submitting = modalApi.useStore((s) => s.submitting);
const confirmLoading = modalApi.useStore((s) => s.confirmLoading);

function open(data: ProductRow | Record<string, never> = {}) {
  modalApi.setData(data);
  modalApi.open();
}

defineExpose({ open });
</script>

<template>
  <Modal :title="formTitle">
    <!-- 弹窗全屏，内容区限宽居中（对齐 Shopify） -->
    <div class="mx-auto w-full" :class="FORM_CONTENT_MAX_CLASS">
      <div
        class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_280px]"
      >
        <div class="flex min-w-0 flex-col gap-4">
          <section
            class="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <h3 class="mb-3 text-sm font-semibold">标题</h3>
            <TitleForm />
          </section>

          <section
            class="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <ProductMediaField v-model="mediaUrls" />
          </section>

          <section
            class="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <h3 class="mb-3 text-sm font-semibold">商品详情</h3>
            <DetailForm />
          </section>

          <section
            class="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <h3 class="mb-3 text-sm font-semibold">价格与库存</h3>
            <SkuEditor
              ref="skuEditorRef"
              v-model:enable-multi="enableMulti"
              v-model:skus="skuRows"
              v-model:variables="variables"
            />
          </section>
        </div>

        <div class="flex flex-col gap-4 lg:sticky lg:top-0">
          <section
            class="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <h3 class="mb-3 text-sm font-semibold">状态</h3>
            <StatusForm />
          </section>

          <section
            class="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <h3 class="mb-3 text-sm font-semibold">商品组织</h3>
            <OrgForm />
          </section>
        </div>
      </div>
    </div>

    <template #footer>
      <div
        class="flex w-full items-center justify-end gap-2"
        :class="FORM_CONTENT_MAX_CLASS"
      >
        <Button :disabled="submitting" @click="modalApi.onCancel()">
          {{ $t('common.cancel') }}
        </Button>
        <Button
          type="primary"
          :loading="confirmLoading || submitting"
          :disabled="submitting"
          @click="modalApi.onConfirm()"
        >
          {{ $t('common.confirm') }}
        </Button>
      </div>
    </template>
  </Modal>
</template>
