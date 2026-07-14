<script lang="ts" setup>
import type { VbenFormSchema } from '@vben/common-ui';
import type { Recordable } from '@vben/types';

import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { AuthenticationRegister, z } from '@vben/common-ui';
import { LOGIN_PATH } from '@vben/constants';
import { $t } from '@vben/locales';

import { Spin } from 'ant-design-vue';
import { message } from 'ant-design-vue';

import { resetPasswordApi, verifyResetPasswordTokenApi } from '#/api';

defineOptions({ name: 'ResetPassword' });

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const verifying = ref(true);
const linkValid = ref(false);

const email = computed(() => String(route.query.email ?? '').trim());
const token = computed(() => String(route.query.token ?? '').trim());

const formSchema = computed((): VbenFormSchema[] => {
  return [
    {
      component: 'VbenInputPassword',
      componentProps: {
        passwordStrength: true,
        placeholder: $t('authentication.password'),
      },
      fieldName: 'password',
      label: $t('authentication.password'),
      renderComponentContent() {
        return {
          strengthText: () => $t('authentication.passwordStrength'),
        };
      },
      rules: z.string().min(1, { message: $t('authentication.passwordTip') }),
    },
    {
      component: 'VbenInputPassword',
      componentProps: {
        placeholder: $t('authentication.confirmPassword'),
      },
      dependencies: {
        rules(values) {
          const { password } = values;
          return z
            .string({ required_error: $t('authentication.passwordTip') })
            .min(1, { message: $t('authentication.passwordTip') })
            .refine((value) => value === password, {
              message: $t('authentication.confirmPasswordTip'),
            });
        },
        triggerFields: ['password'],
      },
      fieldName: 'confirmPassword',
      label: $t('authentication.confirmPassword'),
    },
  ];
});

onMounted(async () => {
  if (!email.value || !token.value) {
    message.warning($t('authentication.resetPasswordInvalidLink'));
    await router.replace(LOGIN_PATH);
    return;
  }

  try {
    await verifyResetPasswordTokenApi({
      email: email.value,
      token: token.value,
    });
    linkValid.value = true;
  } catch {
    await router.replace(LOGIN_PATH);
  } finally {
    verifying.value = false;
  }
});

async function handleSubmit(values: Recordable<any>) {
  if (!email.value || !token.value || !linkValid.value) {
    return;
  }

  loading.value = true;
  try {
    await resetPasswordApi({
      email: email.value,
      password: String(values.password ?? ''),
      token: token.value,
    });
    message.success($t('authentication.resetPasswordSuccess'));
    await router.replace(LOGIN_PATH);
  } catch {
    // 错误提示由请求拦截器统一处理
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div v-if="verifying" class="flex min-h-[240px] items-center justify-center">
    <Spin :tip="$t('authentication.resetPasswordVerifying')" />
  </div>

  <AuthenticationRegister
    v-else-if="linkValid"
    :form-schema="formSchema"
    :loading="loading"
    :login-path="LOGIN_PATH"
    :sub-title="$t('authentication.resetPasswordSubtitle')"
    :submit-button-text="$t('authentication.resetPasswordSubmit')"
    :title="$t('authentication.resetPassword')"
    @submit="handleSubmit"
  />
</template>
