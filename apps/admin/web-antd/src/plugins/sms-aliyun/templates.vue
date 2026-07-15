<script lang="ts" setup>
/**
 * SmsAliyun L2 样例：抽屉内「短信模板管理」
 * - 列表 / 新建 / 编辑 / 删除
 * - 设为默认 → 写入配置 template_code
 * - 清单持久化到 templates_json（不进 Schema，避免当普通字符串字段渲染）
 */
import type { TableColumnsType } from 'ant-design-vue';

import type { VbenFormSchema } from '#/adapter/form';

import { computed, ref, watch } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import {
  Button,
  Popconfirm,
  Table,
  Tag,
  message,
} from 'ant-design-vue';

import { useVbenForm, z } from '#/adapter/form';
import { updatePluginConfig } from '#/features/system/api/dev-plugin';
import { $t } from '#/locales';

export type SmsTemplateScene = 'verify' | 'notify' | 'marketing';

export interface SmsTemplateRow {
  id: string;
  code: string;
  name: string;
  content: string;
  scene: SmsTemplateScene;
}

const props = defineProps<{
  slug: string;
  config: Record<string, string | number | boolean | null>;
}>();

const saving = ref(false);
const editingId = ref<string | null>(null);
const rows = ref<SmsTemplateRow[]>([]);
const defaultCode = ref('');

const signName = computed(() => String(props.config.sign_name ?? '').trim());

const sceneOptions = computed(() => [
  { value: 'verify', label: $t('sms-aliyun.sceneVerify') },
  { value: 'notify', label: $t('sms-aliyun.sceneNotify') },
  { value: 'marketing', label: $t('sms-aliyun.sceneMarketing') },
]);

function sceneLabel(scene: SmsTemplateScene) {
  return (
    sceneOptions.value.find((o) => o.value === scene)?.label ?? scene
  );
}

function parseTemplates(raw: unknown): SmsTemplateRow[] {
  if (typeof raw !== 'string' || raw.trim() === '') {
    return [];
  }
  try {
    const data = JSON.parse(raw) as unknown;
    if (!Array.isArray(data)) {
      return [];
    }
    return data
      .filter((item): item is Record<string, unknown> => !!item && typeof item === 'object')
      .map((item, index) => ({
        id: String(item.id ?? `tpl-${index}`),
        code: String(item.code ?? ''),
        name: String(item.name ?? ''),
        content: String(item.content ?? ''),
        scene: (['verify', 'notify', 'marketing'].includes(String(item.scene))
          ? String(item.scene)
          : 'verify') as SmsTemplateScene,
      }))
      .filter((row) => row.code !== '');
  } catch {
    return [];
  }
}

function hydrateFromConfig() {
  rows.value = parseTemplates(props.config.templates_json);
  defaultCode.value = String(props.config.template_code ?? '');
}

watch(
  () => props.config,
  () => hydrateFromConfig(),
  { immediate: true, deep: true },
);

async function persist(nextRows: SmsTemplateRow[], nextDefault?: string) {
  saving.value = true;
  try {
    const payload: Record<string, unknown> = {
      templates_json: JSON.stringify(nextRows),
    };
    if (nextDefault !== undefined) {
      payload.template_code = nextDefault;
    }
    await updatePluginConfig(props.slug, payload);
    rows.value = nextRows;
    if (nextDefault !== undefined) {
      defaultCode.value = nextDefault;
    }
  } finally {
    saving.value = false;
  }
}

function buildTemplateSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('sms-aliyun.codePlaceholder'),
      },
      fieldName: 'code',
      label: $t('sms-aliyun.code'),
      rules: z.string().min(1, { message: $t('sms-aliyun.codeRequired') }),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('sms-aliyun.namePlaceholder'),
      },
      fieldName: 'name',
      label: $t('sms-aliyun.name'),
    },
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        options: sceneOptions.value,
      },
      fieldName: 'scene',
      label: $t('sms-aliyun.scene'),
      rules: z.enum(['verify', 'notify', 'marketing']),
    },
    {
      component: 'Textarea',
      componentProps: {
        class: 'w-full',
        placeholder: $t('sms-aliyun.contentPlaceholder'),
        rows: 3,
      },
      fieldName: 'content',
      help: $t('sms-aliyun.contentHelp'),
      label: $t('sms-aliyun.content'),
    },
  ];
}

const [TemplateForm, templateFormApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildTemplateSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1',
});

const [TemplateModal, templateModalApi] = useVbenModal({
  destroyOnClose: true,
  fullscreenButton: false,
  async onConfirm() {
    const { valid } = await templateFormApi.validate();
    if (!valid) return;

    const values = (await templateFormApi.getValues()) as {
      code: string;
      name?: string;
      content?: string;
      scene: SmsTemplateScene;
    };
    const code = values.code.trim();
    const duplicate = rows.value.some(
      (r) => r.code === code && r.id !== editingId.value,
    );
    if (duplicate) {
      message.warning($t('sms-aliyun.codeDuplicate'));
      return;
    }

    templateModalApi.lock();
    try {
      let next: SmsTemplateRow[];
      if (editingId.value) {
        next = rows.value.map((r) =>
          r.id === editingId.value
            ? {
                ...r,
                code,
                name: String(values.name ?? '').trim(),
                content: String(values.content ?? '').trim(),
                scene: values.scene,
              }
            : r,
        );
      } else {
        next = [
          ...rows.value,
          {
            id: `tpl-${Date.now()}`,
            code,
            name: String(values.name ?? '').trim(),
            content: String(values.content ?? '').trim(),
            scene: values.scene,
          },
        ];
      }
      await persist(next);
      templateModalApi.close();
      message.success($t('sms-aliyun.saved'));
    } finally {
      templateModalApi.unlock();
    }
  },
  async onOpenChange(isOpen: boolean) {
    if (!isOpen) return;
    templateFormApi.resetForm();
    templateFormApi.setState({ schema: buildTemplateSchema() });
    const data = templateModalApi.getData<SmsTemplateRow | Record<string, never>>();
    const id =
      data && typeof (data as SmsTemplateRow).id === 'string'
        ? (data as SmsTemplateRow).id
        : null;
    editingId.value = id;
    if (id) {
      const row = data as SmsTemplateRow;
      templateFormApi.setValues({
        code: row.code,
        name: row.name,
        content: row.content,
        scene: row.scene,
      });
    } else {
      templateFormApi.setValues({
        code: '',
        name: '',
        content: '',
        scene: 'verify',
      });
    }
  },
});

const templateModalTitle = computed(() =>
  editingId.value
    ? $t('sms-aliyun.modalEdit')
    : $t('sms-aliyun.modalCreate'),
);

function openCreate() {
  editingId.value = null;
  templateModalApi.setData({});
  templateModalApi.open();
}

function openEdit(row: SmsTemplateRow) {
  editingId.value = row.id;
  templateModalApi.setData(row);
  templateModalApi.open();
}

async function removeRow(row: SmsTemplateRow) {
  const next = rows.value.filter((r) => r.id !== row.id);
  const nextDefault =
    defaultCode.value === row.code ? '' : undefined;
  await persist(next, nextDefault);
  message.success($t('sms-aliyun.removed'));
}

async function setAsDefault(row: SmsTemplateRow) {
  await persist(rows.value, row.code);
  message.success($t('sms-aliyun.defaultSet'));
}

const columns = computed<TableColumnsType<SmsTemplateRow>>(() => [
  {
    title: $t('sms-aliyun.code'),
    dataIndex: 'code',
    key: 'code',
    width: 128,
  },
  {
    title: $t('sms-aliyun.name'),
    dataIndex: 'name',
    key: 'name',
    ellipsis: true,
  },
  {
    title: $t('sms-aliyun.scene'),
    dataIndex: 'scene',
    key: 'scene',
    width: 72,
  },
  {
    title: $t('sms-aliyun.content'),
    dataIndex: 'content',
    key: 'content',
    ellipsis: true,
  },
  {
    title: $t('sms-aliyun.actions'),
    key: 'actions',
    width: 168,
  },
]);
</script>

<template>
  <div class="sms-aliyun-templates flex flex-col gap-3">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0 flex-1">
        <div class="text-foreground text-sm font-medium">
          {{ $t('sms-aliyun.title') }}
        </div>
        <p class="text-muted-foreground mt-0.5 text-xs leading-relaxed">
          {{ $t('sms-aliyun.hint') }}
          <span v-if="signName" class="ml-1 whitespace-nowrap">
            · {{ $t('sms-aliyun.signHint', { sign: signName }) }}
          </span>
        </p>
      </div>
      <Button
        class="shrink-0"
        type="primary"
        size="small"
        :loading="saving"
        @click="openCreate"
      >
        {{ $t('sms-aliyun.add') }}
      </Button>
    </div>

    <Table
      class="sms-aliyun-templates__table"
      size="small"
      bordered
      :columns="columns"
      :data-source="rows"
      :pagination="false"
      :locale="{ emptyText: $t('sms-aliyun.empty') }"
      row-key="id"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'code'">
          <div class="flex max-w-full items-center gap-1 overflow-hidden">
            <code class="truncate text-xs">{{ record.code }}</code>
            <Tag
              v-if="record.code === defaultCode"
              class="m-0 shrink-0"
              color="processing"
            >
              {{ $t('sms-aliyun.isDefault') }}
            </Tag>
          </div>
        </template>
        <template v-else-if="column.key === 'scene'">
          <span class="text-xs">{{ sceneLabel(record.scene) }}</span>
        </template>
        <template v-else-if="column.key === 'actions'">
          <div class="flex flex-wrap items-center gap-x-1">
            <Button
              v-if="(record as SmsTemplateRow).code !== defaultCode"
              type="link"
              size="small"
              class="!px-0"
              :loading="saving"
              @click="setAsDefault(record as SmsTemplateRow)"
            >
              {{ $t('sms-aliyun.setDefault') }}
            </Button>
            <Button
              type="link"
              size="small"
              class="!px-0"
              @click="openEdit(record as SmsTemplateRow)"
            >
              {{ $t('sms-aliyun.edit') }}
            </Button>
            <Popconfirm
              :title="$t('sms-aliyun.confirmRemove')"
              @confirm="removeRow(record as SmsTemplateRow)"
            >
              <Button
                type="link"
                size="small"
                danger
                class="!px-0"
                :loading="saving"
              >
                {{ $t('sms-aliyun.remove') }}
              </Button>
            </Popconfirm>
          </div>
        </template>
      </template>
    </Table>

    <TemplateModal
      :title="templateModalTitle"
      class="w-full max-w-full md:w-[520px] md:max-w-[520px]"
    >
      <TemplateForm />
    </TemplateModal>
  </div>
</template>

<style scoped>
.sms-aliyun-templates__table :deep(.ant-table) {
  font-size: 12px;
}

.sms-aliyun-templates__table :deep(.ant-table-thead > tr > th) {
  background: hsl(var(--muted) / 0.45);
  font-weight: 500;
}

.sms-aliyun-templates__table :deep(.ant-table-placeholder .ant-table-cell) {
  border-bottom: none;
}
</style>
