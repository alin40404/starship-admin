<script lang="ts" setup>
/**
 * MailSmtp 后台前端：apps/admin/web-antd/src/plugins/mail-smtp
 * 文案：$t('mail-smtp.*') ← locales/{zh-CN|en-US}/mail-smtp.json
 * 导出包内对应 backend/
 */
import { computed } from 'vue';

import { Alert } from 'ant-design-vue';

import { $t } from '#/locales';

const props = defineProps<{
  slug: string;
  config: Record<string, string | number | boolean | null>;
}>();

const host = computed(() => String(props.config.host ?? ''));
const encryption = computed(() => String(props.config.encryption ?? 'ssl'));
</script>

<template>
  <div class="space-y-3">
    <Alert
      type="info"
      show-icon
      :message="$t('mail-smtp.advancedTitle')"
      :description="$t('mail-smtp.advancedHint')"
    />
    <div class="text-muted-foreground text-sm">
      <div>{{ $t('mail-smtp.host') }}: {{ host || '—' }}</div>
      <div>
        {{ $t('mail-smtp.encryption') }}:
        {{ encryption || '—' }}
      </div>
    </div>
  </div>
</template>
