<script lang="ts" setup>
import type { VbenFormSchema } from '@vben/common-ui';
import type { Recordable } from '@vben/types';

import { computed, ref, useTemplateRef } from 'vue';

import { AuthenticationCodeLogin, z } from '@vben/common-ui';
import { $t } from '@vben/locales';

import { message } from 'ant-design-vue';

import { sendSmsCodeApi } from '#/api/core/auth';
import { useAuthStore } from '#/store';

defineOptions({ name: 'CodeLogin' });

const authStore = useAuthStore();
const loading = ref(false);
const CODE_LENGTH = 6;
const loginRef =
  useTemplateRef<InstanceType<typeof AuthenticationCodeLogin>>('loginRef');

const formSchema = computed((): VbenFormSchema[] => {
  return [
    {
      component: 'VbenInput',
      componentProps: {
        placeholder: $t('authentication.mobile'),
      },
      fieldName: 'phoneNumber',
      label: $t('authentication.mobile'),
      rules: z
        .string()
        .min(1, { message: $t('authentication.mobileTip') })
        .refine((v) => /^\d{11}$/.test(v), {
          message: $t('authentication.mobileErrortip'),
        }),
    },
    {
      component: 'VbenPinInput',
      componentProps: {
        codeLength: CODE_LENGTH,
        createText: (countdown: number) => {
          const text =
            countdown > 0
              ? $t('authentication.sendText', [countdown])
              : $t('authentication.sendCode');
          return text;
        },
        handleSendCode: async () => {
          loading.value = true;
          const formApi = loginRef.value?.getFormApi();
          if (!formApi) {
            loading.value = false;
            throw new Error('formApi is not ready');
          }
          await formApi.validateField('phoneNumber');
          const isPhoneReady = await formApi.isFieldValid('phoneNumber');
          if (!isPhoneReady) {
            loading.value = false;
            throw new Error('Phone number is not ready');
          }
          const { phoneNumber } = await formApi.getValues();
          try {
            await sendSmsCodeApi(phoneNumber);
            message.success($t('authentication.codeSent'));
          } finally {
            loading.value = false;
          }
        },
        placeholder: $t('authentication.code'),
      },
      fieldName: 'code',
      label: $t('authentication.code'),
      rules: z.string().length(CODE_LENGTH, {
        message: $t('authentication.codeTip', [CODE_LENGTH]),
      }),
    },
  ];
});

async function handleLogin(values: Recordable<any>) {
  await authStore.authSmsLogin({
    mobile: values.phoneNumber,
    code: values.code,
  });
}
</script>

<template>
  <AuthenticationCodeLogin
    ref="loginRef"
    :form-schema="formSchema"
    :loading="loading || authStore.loginLoading"
    @submit="handleLogin"
  />
</template>
