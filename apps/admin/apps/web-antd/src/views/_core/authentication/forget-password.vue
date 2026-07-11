<script lang="ts" setup>
import type { VbenFormSchema } from '@vben/common-ui';
import type { Recordable } from '@vben/types';

import { computed, ref } from 'vue';

import { AuthenticationForgetPassword, z } from '@vben/common-ui';
import { $t } from '@vben/locales';

import { message } from 'ant-design-vue';

import { forgotPasswordApi } from '#/api';

defineOptions({ name: 'ForgetPassword' });

const loading = ref(false);

const formSchema = computed((): VbenFormSchema[] => {
  return [
    {
      component: 'VbenInput',
      componentProps: {
        placeholder: 'example@example.com',
      },
      fieldName: 'email',
      label: $t('authentication.email'),
      rules: z
        .string()
        .min(1, { message: $t('authentication.emailTip') })
        .email($t('authentication.emailValidErrorTip')),
    },
  ];
});

async function handleSubmit(values: Recordable<any>) {
  loading.value = true;
  try {
    await forgotPasswordApi({ email: String(values.email ?? '').trim() });
    message.success($t('authentication.sendResetLinkSuccess'));
  } catch {
    // 错误提示由请求拦截器统一处理
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <AuthenticationForgetPassword
    :form-schema="formSchema"
    :loading="loading"
    @submit="handleSubmit"
  />
</template>
