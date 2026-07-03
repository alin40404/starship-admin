<script setup lang="ts">
import type { VbenFormSchema } from '#/adapter/form';

import { computed } from 'vue';

import { ProfilePasswordSetting, z } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { updateMyPasswordApi } from '#/api';
import { useAuthStore } from '#/store';

const authStore = useAuthStore();

const formSchema = computed((): VbenFormSchema[] => {
  return [
    {
      fieldName: 'oldPassword',
      label: '旧密码',
      component: 'InputPassword',
      componentProps: {
        placeholder: '请输入旧密码',
      },
      rules: 'required',
    },
    {
      fieldName: 'newPassword',
      label: '新密码',
      component: 'InputPassword',
      componentProps: {
        passwordStrength: true,
        placeholder: '请输入新密码',
      },
      rules: 'required',
    },
    {
      fieldName: 'confirmPassword',
      label: '确认密码',
      component: 'InputPassword',
      componentProps: {
        passwordStrength: true,
        placeholder: '请再次输入新密码',
      },
      dependencies: {
        rules(values) {
          const newPassword = values?.newPassword ?? '';
          return z
            .string({ required_error: '请再次输入新密码' })
            .min(1, { message: '请再次输入新密码' })
            .refine((value) => value === newPassword, {
              message: '两次输入的密码不一致',
            });
        },
        triggerFields: ['newPassword'],
      },
    },
  ];
});

async function handleSubmit(values: Record<string, string>) {
  await updateMyPasswordApi({
    new_password: values.newPassword ?? '',
    old_password: values.oldPassword ?? '',
  });
  message.success('密码修改成功，请重新登录');
  await new Promise((resolve) => setTimeout(resolve, 1200));
  await authStore.logout(false);
}
</script>
<template>
  <ProfilePasswordSetting
    class="w-full max-w-xl"
    :form-schema="formSchema"
    @submit="handleSubmit"
  />
</template>
