<script lang="ts" setup>
/**
 * MailSmtp 编译期自定义配置片段（Wave2）：由 custom-registry 注册，不运行时动态加载。
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
      :message="$t('dev.plugins.mailSmtpAdvancedTitle')"
      :description="$t('dev.plugins.mailSmtpAdvancedHint')"
    />
    <div class="text-muted-foreground text-sm">
      <div>{{ $t('dev.plugins.mailSmtpAdvancedHost') }}: {{ host || '—' }}</div>
      <div>
        {{ $t('dev.plugins.mailSmtpAdvancedEncryption') }}:
        {{ encryption || '—' }}
      </div>
    </div>
  </div>
</template>
