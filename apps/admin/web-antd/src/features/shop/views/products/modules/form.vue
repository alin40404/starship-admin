<script lang="ts" setup>
import type { ProductRow, ProductSavePayload } from '#/features/shop/api';

import { computed, onMounted, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createProduct,
  fetchBrandOptions,
  fetchProduct,
  fetchShopCategoryOptions,
  SHOP_FULFILLMENT_TYPE,
  SHOP_PRODUCT_TYPE,
  updateProduct,
} from '#/features/shop/api';
import { $t } from '#/locales';

import {
  buildProductFormSchema,
  defaultFulfillmentForProductType,
} from '../data';

const emits = defineEmits<{ success: [] }>();
const { isMobile } = usePreferences();
const editingId = ref<number | null>(null);
const categoryOptions = ref<{ label: string; value: number }[]>([]);
const brandOptions = ref<{ label: string; value: number }[]>([]);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildProductFormSchema({}),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

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
  formApi.setState({
    schema: buildProductFormSchema({
      brandOptions: brandOptions.value,
      categoryOptions: categoryOptions.value,
    }),
  });
}

function toPayload(values: Record<string, unknown>): ProductSavePayload {
  const price = Math.round(Number(values.price_yuan ?? 0) * 100);
  const stock = Number(values.stock ?? 0);
  const productType = String(values.product_type ?? SHOP_PRODUCT_TYPE.PHYSICAL);
  const fulfillmentType = String(
    values.fulfillment_type ?? defaultFulfillmentForProductType(productType),
  );

  return {
    product_type: productType,
    fulfillment_type: fulfillmentType,
    category_id: values.category_id ? Number(values.category_id) : null,
    brand_id: values.brand_id ? Number(values.brand_id) : null,
    name: String(values.name ?? ''),
    subtitle: (values.subtitle as string) || null,
    price,
    stock,
    cover: (values.cover as string) || null,
    description: (values.description as string) || null,
    status: Number(values.status ?? 1),
    extra: null,
    skus: [
      {
        price,
        stock,
        status: 1,
      },
    ],
  };
}

async function handleConfirm(popupApi: PopupApi) {
  const { valid } = await formApi.validate();
  if (!valid) return;

  const values = await formApi.getValues();
  popupApi.lock();
  try {
    const payload = toPayload(values);
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
  if (!isOpen) return;
  await refreshOptions();
  formApi.resetForm();
  const data = popupApi.getData<ProductRow | Record<string, never>>();
  const id =
    data && typeof (data as ProductRow).id === 'number'
      ? (data as ProductRow).id
      : null;
  editingId.value = id;

  if (id) {
    const detail = await fetchProduct(id);
    const defaultSku = detail.skus?.[0];
    const productType = detail.product_type || SHOP_PRODUCT_TYPE.PHYSICAL;
    await formApi.setValues({
      product_type: productType,
      fulfillment_type:
        detail.fulfillment_type ||
        defaultFulfillmentForProductType(productType),
      category_id: detail.category_id ?? undefined,
      brand_id: detail.brand_id ?? undefined,
      name: detail.name,
      subtitle: detail.subtitle ?? '',
      price_yuan: (defaultSku?.price ?? detail.price) / 100,
      stock: defaultSku?.stock ?? detail.stock,
      cover: detail.cover ?? '',
      description: detail.description ?? '',
      status: detail.status,
    });
  } else {
    await formApi.setValues({
      product_type: SHOP_PRODUCT_TYPE.PHYSICAL,
      fulfillment_type: SHOP_FULFILLMENT_TYPE.SHIPPING,
      category_id: undefined,
      brand_id: undefined,
      name: '',
      subtitle: '',
      price_yuan: 0,
      stock: 0,
      cover: '',
      description: '',
      status: 1,
    });
  }
}

const [Modal, modalApi] = useVbenModal({
  destroyOnClose: true,
  fullscreenButton: false,
  async onConfirm() {
    await handleConfirm(modalApi as PopupApi);
  },
  async onOpenChange(isOpen: boolean) {
    await handleOpenChange(isOpen, modalApi as PopupApi);
  },
});

const [Drawer, drawerApi] = useVbenDrawer({
  destroyOnClose: true,
  async onConfirm() {
    await handleConfirm(drawerApi as PopupApi);
  },
  async onOpenChange(isOpen: boolean) {
    await handleOpenChange(isOpen, drawerApi as PopupApi);
  },
});

function open(data: ProductRow | Record<string, never> = {}) {
  const api = (isMobile.value ? drawerApi : modalApi) as PopupApi;
  api.setData(data);
  api.open();
}

onMounted(() => {
  void refreshOptions();
});

defineExpose({ open });
</script>

<template>
  <Modal
    v-if="!isMobile"
    :title="formTitle"
    class="w-full max-w-full md:w-[720px] md:max-w-[720px]"
  >
    <Form />
  </Modal>
  <Drawer v-else :title="formTitle" class="w-full">
    <Form />
  </Drawer>
</template>
