<script lang="ts" setup>
/**
 * MailSmtp L2：高级设置
 * - 连接概览（只读，来自已保存配置）
 * - 扩展选项：reply_to / timeout / verify_peer
 * - 通知收件人清单 → recipients_json
 */
import type { TableColumnsType } from 'ant-design-vue';

import type { VbenFormSchema } from '#/adapter/form';

import { computed, reactive, ref, watch } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import {
  Button,
  Card,
  Descriptions,
  Form,
  Input,
  InputNumber,
  Popconfirm,
  Switch,
  Table,
  Tag,
  message,
} from 'ant-design-vue';

import { useVbenForm, z } from '#/adapter/form';
import { updatePluginConfig } from '#/features/system/api/dev-plugin';
import { $t } from '#/locales';

type RecipientRole = 'alert' | 'billing' | 'ops';

interface RecipientRow {
  id: string;
  email: string;
  name: string;
  role: RecipientRole;
}

const props = defineProps<{
  slug: string;
  config: Record<string, string | number | boolean | null>;
}>();

const savingOptions = ref(false);
const savingList = ref(false);
const editingId = ref<string | null>(null);
const recipients = ref<RecipientRow[]>([]);

const optionsForm = reactive({
  reply_to: '',
  timeout: 30,
  verify_peer: true,
});

const display = computed(() => {
  const c = props.config;
  const fromAddr = String(c.from_address ?? '').trim();
  const fromName = String(c.from_name ?? '').trim();
  const from =
    fromAddr === ''
      ? ''
      : fromName
        ? `${fromName} <${fromAddr}>`
        : fromAddr;

  return {
    host: String(c.host ?? '').trim(),
    port: String(c.port ?? '').trim(),
    encryption: String(c.encryption ?? '').trim(),
    username: String(c.username ?? '').trim(),
    from,
  };
});

const configReady = computed(() => {
  return (
    display.value.host !== '' &&
    display.value.port !== '' &&
    display.value.username !== ''
  );
});

const roleOptions = computed(() => [
  { value: 'alert', label: $t('mail-smtp.roleAlert') },
  { value: 'billing', label: $t('mail-smtp.roleBilling') },
  { value: 'ops', label: $t('mail-smtp.roleOps') },
]);

function roleLabel(role: RecipientRole) {
  return roleOptions.value.find((o) => o.value === role)?.label ?? role;
}

function dash(value: string) {
  return value || $t('mail-smtp.emptyValue');
}

function parseRecipients(raw: unknown): RecipientRow[] {
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
        id: String(item.id ?? `rcpt-${index}`),
        email: String(item.email ?? '').trim(),
        name: String(item.name ?? '').trim(),
        role: (['alert', 'billing', 'ops'].includes(String(item.role))
          ? String(item.role)
          : 'alert') as RecipientRole,
      }))
      .filter((row) => row.email !== '');
  } catch {
    return [];
  }
}

function hydrateFromConfig() {
  const c = props.config;
  optionsForm.reply_to = String(c.reply_to ?? '');
  const timeout = Number(c.timeout ?? 30);
  optionsForm.timeout = Number.isFinite(timeout) && timeout > 0 ? timeout : 30;
  const peer = c.verify_peer;
  optionsForm.verify_peer = !(
    peer === false ||
    peer === 0 ||
    peer === '0' ||
    peer === 'false'
  );
  recipients.value = parseRecipients(c.recipients_json);
}

watch(
  () => props.config,
  () => hydrateFromConfig(),
  { immediate: true, deep: true },
);

async function saveOptions() {
  savingOptions.value = true;
  try {
    await updatePluginConfig(props.slug, {
      reply_to: optionsForm.reply_to.trim(),
      timeout: String(optionsForm.timeout),
      verify_peer: optionsForm.verify_peer ? '1' : '0',
    });
    message.success($t('mail-smtp.optionsSaved'));
  } finally {
    savingOptions.value = false;
  }
}

async function persistRecipients(next: RecipientRow[]) {
  savingList.value = true;
  try {
    await updatePluginConfig(props.slug, {
      recipients_json: JSON.stringify(next),
    });
    recipients.value = next;
  } finally {
    savingList.value = false;
  }
}

function isEmail(value: string) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function buildRecipientSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('mail-smtp.emailPlaceholder'),
      },
      fieldName: 'email',
      label: $t('mail-smtp.email'),
      rules: z
        .string()
        .min(1, { message: $t('mail-smtp.emailRequired') })
        .refine((v) => isEmail(v.trim()), {
          message: $t('mail-smtp.emailInvalid'),
        }),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('mail-smtp.namePlaceholder'),
      },
      fieldName: 'name',
      label: $t('mail-smtp.name'),
    },
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        options: roleOptions.value,
      },
      fieldName: 'role',
      label: $t('mail-smtp.role'),
      rules: z.enum(['alert', 'billing', 'ops']),
    },
  ];
}

const [RecipientForm, recipientFormApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildRecipientSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1',
});

const [RecipientModal, recipientModalApi] = useVbenModal({
  destroyOnClose: true,
  fullscreenButton: false,
  async onConfirm() {
    const { valid } = await recipientFormApi.validate();
    if (!valid) return;

    const values = (await recipientFormApi.getValues()) as {
      email: string;
      name?: string;
      role: RecipientRole;
    };
    const email = values.email.trim().toLowerCase();
    const duplicate = recipients.value.some(
      (r) => r.email.toLowerCase() === email && r.id !== editingId.value,
    );
    if (duplicate) {
      message.warning($t('mail-smtp.emailDuplicate'));
      return;
    }

    recipientModalApi.lock();
    try {
      let next: RecipientRow[];
      if (editingId.value) {
        next = recipients.value.map((r) =>
          r.id === editingId.value
            ? {
                ...r,
                email,
                name: String(values.name ?? '').trim(),
                role: values.role,
              }
            : r,
        );
      } else {
        next = [
          ...recipients.value,
          {
            id: `rcpt-${Date.now()}`,
            email,
            name: String(values.name ?? '').trim(),
            role: values.role,
          },
        ];
      }
      await persistRecipients(next);
      recipientModalApi.close();
      message.success($t('mail-smtp.saved'));
    } finally {
      recipientModalApi.unlock();
    }
  },
  async onOpenChange(isOpen: boolean) {
    if (!isOpen) return;
    recipientFormApi.resetForm();
    recipientFormApi.setState({ schema: buildRecipientSchema() });
    const data = recipientModalApi.getData<RecipientRow | Record<string, never>>();
    const id =
      data && typeof (data as RecipientRow).id === 'string'
        ? (data as RecipientRow).id
        : null;
    editingId.value = id;
    if (id) {
      const row = data as RecipientRow;
      recipientFormApi.setValues({
        email: row.email,
        name: row.name,
        role: row.role,
      });
    } else {
      recipientFormApi.setValues({
        email: '',
        name: '',
        role: 'alert',
      });
    }
  },
});

const recipientModalTitle = computed(() =>
  editingId.value
    ? $t('mail-smtp.modalEdit')
    : $t('mail-smtp.modalCreate'),
);

function openCreate() {
  editingId.value = null;
  recipientModalApi.setData({});
  recipientModalApi.open();
}

function openEdit(row: RecipientRow) {
  editingId.value = row.id;
  recipientModalApi.setData(row);
  recipientModalApi.open();
}

async function removeRow(row: RecipientRow) {
  await persistRecipients(recipients.value.filter((r) => r.id !== row.id));
  message.success($t('mail-smtp.removed'));
}

const columns = computed<TableColumnsType<RecipientRow>>(() => [
  {
    title: $t('mail-smtp.email'),
    dataIndex: 'email',
    key: 'email',
    ellipsis: true,
  },
  {
    title: $t('mail-smtp.name'),
    dataIndex: 'name',
    key: 'name',
    width: 120,
    ellipsis: true,
  },
  {
    title: $t('mail-smtp.role'),
    dataIndex: 'role',
    key: 'role',
    width: 88,
  },
  {
    title: $t('mail-smtp.actions'),
    key: 'actions',
    width: 120,
  },
]);
</script>

<template>
  <div class="mail-smtp-advanced plugin-form flex flex-col gap-4">
    <div>
      <div class="text-foreground text-base font-medium">
        {{ $t('mail-smtp.title') }}
      </div>
      <p class="text-muted-foreground mt-1 text-[12px] leading-relaxed">
        {{ $t('mail-smtp.hint') }}
      </p>
      <Tag class="mt-2" :color="configReady ? 'success' : 'warning'">
        {{ configReady ? $t('mail-smtp.readyOk') : $t('mail-smtp.readyMissing') }}
      </Tag>
    </div>

    <section class="space-y-2">
      <div class="text-foreground text-sm font-medium">
        {{ $t('mail-smtp.sectionConnection') }}
      </div>
      <Descriptions
        size="small"
        bordered
        :column="2"
        class="mail-smtp-advanced__desc"
      >
        <Descriptions.Item :label="$t('mail-smtp.host')">
          {{ dash(display.host) }}
        </Descriptions.Item>
        <Descriptions.Item :label="$t('mail-smtp.port')">
          {{ dash(display.port) }}
        </Descriptions.Item>
        <Descriptions.Item :label="$t('mail-smtp.encryption')">
          {{ dash(display.encryption) }}
        </Descriptions.Item>
        <Descriptions.Item :label="$t('mail-smtp.username')">
          {{ dash(display.username) }}
        </Descriptions.Item>
        <Descriptions.Item :label="$t('mail-smtp.from')" :span="2">
          {{ dash(display.from) }}
        </Descriptions.Item>
      </Descriptions>
    </section>

    <section class="space-y-2">
      <div class="flex items-center justify-between gap-2">
        <div class="text-foreground text-sm font-medium">
          {{ $t('mail-smtp.sectionOptions') }}
        </div>
        <Button
          class="mail-smtp-advanced__btn"
          type="primary"
          size="small"
          :loading="savingOptions"
          @click="saveOptions"
        >
          {{ $t('mail-smtp.saveOptions') }}
        </Button>
      </div>
      <Card size="small" class="mail-smtp-advanced__options-card" :bordered="true">
        <Form
          layout="vertical"
          class="mail-smtp-advanced__options"
          :colon="false"
        >
          <div
            class="grid grid-cols-1 gap-x-3 gap-y-2 sm:grid-cols-[minmax(0,1fr)_110px_auto]"
          >
            <Form.Item :label="$t('mail-smtp.replyTo')" class="!mb-0">
              <Input
                v-model:value="optionsForm.reply_to"
                :placeholder="$t('mail-smtp.replyToPlaceholder')"
              />
            </Form.Item>
            <Form.Item :label="$t('mail-smtp.timeout')" class="!mb-0">
              <InputNumber
                v-model:value="optionsForm.timeout"
                :min="5"
                :max="120"
                class="!w-full"
              />
            </Form.Item>
            <Form.Item :label="$t('mail-smtp.verifyPeer')" class="!mb-0">
              <div class="flex h-8 items-center">
                <Switch
                  v-model:checked="optionsForm.verify_peer"
                  size="small"
                />
              </div>
            </Form.Item>
          </div>
          <p class="text-muted-foreground mt-1.5 mb-0 text-[11px] leading-snug">
            {{ $t('mail-smtp.verifyPeerHelp') }}
          </p>
        </Form>
      </Card>
    </section>

    <section class="space-y-2">
      <div class="flex items-start justify-between gap-3">
        <div class="text-foreground text-sm font-medium">
          {{ $t('mail-smtp.sectionRecipients') }}
        </div>
        <Button
          class="mail-smtp-advanced__btn shrink-0"
          type="primary"
          size="small"
          :loading="savingList"
          @click="openCreate"
        >
          {{ $t('mail-smtp.addRecipient') }}
        </Button>
      </div>

      <Table
        class="mail-smtp-advanced__table"
        size="small"
        bordered
        :columns="columns"
        :data-source="recipients"
        :pagination="false"
        :locale="{ emptyText: $t('mail-smtp.emptyRecipients') }"
        row-key="id"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'role'">
            <Tag class="m-0">{{ roleLabel((record as RecipientRow).role) }}</Tag>
          </template>
          <template v-else-if="column.key === 'actions'">
            <div class="flex items-center gap-x-2">
              <Button
                type="link"
                size="small"
                class="!px-0"
                @click="openEdit(record as RecipientRow)"
              >
                {{ $t('mail-smtp.edit') }}
              </Button>
              <Popconfirm
                :title="$t('mail-smtp.confirmRemove')"
                @confirm="removeRow(record as RecipientRow)"
              >
                <Button
                  type="link"
                  size="small"
                  danger
                  class="!px-0"
                  :loading="savingList"
                >
                  {{ $t('mail-smtp.remove') }}
                </Button>
              </Popconfirm>
            </div>
          </template>
        </template>
      </Table>
    </section>

    <RecipientModal
      :title="recipientModalTitle"
      class="w-full max-w-full md:w-[480px] md:max-w-[480px]"
    >
      <div class="plugin-form">
        <RecipientForm />
      </div>
    </RecipientModal>
  </div>
</template>

<style scoped>
.mail-smtp-advanced__desc :deep(.ant-descriptions-item-label),
.mail-smtp-advanced__desc :deep(.ant-descriptions-item-content) {
  font-size: calc(var(--font-size-base, 16px) * 0.75);
}

.mail-smtp-advanced__options-card :deep(.ant-card-body) {
  padding: 12px;
}

.mail-smtp-advanced__btn :deep(span) {
  font-size: calc(var(--font-size-base, 16px) * 0.75);
}

.mail-smtp-advanced__table :deep(.ant-table) {
  font-size: calc(var(--font-size-base, 16px) * 0.75);
}

.mail-smtp-advanced__table :deep(.ant-table-thead > tr > th) {
  background: hsl(var(--muted) / 0.45);
  font-weight: 500;
}
</style>
