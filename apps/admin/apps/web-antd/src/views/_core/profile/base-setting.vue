<script setup lang="ts">
import type { VbenFormSchema } from '#/adapter/form';

import { computed, onMounted, ref } from 'vue';

import { ProfileBaseSetting, z } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { getMyProfileApi, updateMyProfileApi } from '#/api';
import { uploadAttachment } from '#/api/system/attachment';
import { validateUploadFile } from '#/utils/upload-policy';
import { useAuthStore } from '#/store';

const profileBaseSettingRef = ref<any>();
const authStore = useAuthStore();

function normalizeAvatarToFileList(avatar?: null | string) {
  if (!avatar) {
    return [];
  }
  return [
    {
      name: 'avatar',
      status: 'done',
      uid: 'avatar-existing',
      url: avatar,
    },
  ];
}

function onAvatarChange(_event: any) {}

async function onAvatarUpload(options: any) {
  const file = options?.file as File | undefined;
  if (!file) {
    options?.onError?.(new Error('missing file'));
    return;
  }

  const validationError = validateUploadFile(file, 'image');
  if (validationError) {
    message.error(validationError);
    options?.onError?.(new Error(validationError));
    return;
  }

  try {
    const row = await uploadAttachment(file);
    options?.onSuccess?.(row);
  } catch (error) {
    options?.onError?.(error);
    throw error;
  }
}

const formSchema = computed((): VbenFormSchema[] => {
  return [
    {
      fieldName: 'username',
      component: 'Input',
      componentProps: {
        disabled: true,
      },
      label: '用户名',
    },
    {
      fieldName: 'nickname',
      component: 'Input',
      label: '昵称',
    },
    {
      fieldName: 'real_name',
      component: 'Input',
      label: '真实姓名',
    },
    {
      fieldName: 'avatar',
      component: 'AvatarUploadField',
      componentProps: {
        customRequest: onAvatarUpload,
        handleChange: onAvatarChange,
      },
      formItemClass: 'avatar-upload-form-item',
      label: '头像',
    },
    {
      fieldName: 'email',
      component: 'Input',
      label: '邮箱',
      rules: z
        .string({ required_error: '请输入邮箱' })
        .min(1, { message: '请输入邮箱' })
        .email('请输入正确的邮箱格式'),
    },
    {
      fieldName: 'mobile',
      component: 'Input',
      label: '手机',
      rules: z
        .string({ required_error: '请输入手机号' })
        .regex(/^1[3-9]\d{9}$/, '请输入正确的手机号格式'),
    },
  ];
});

async function loadProfile() {
  const data = await getMyProfileApi();
  profileBaseSettingRef.value?.getFormApi().setValues({
    avatar: normalizeAvatarToFileList(data.avatar),
    email: data.email ?? '',
    mobile: data.mobile ?? '',
    nickname: data.nickname ?? '',
    real_name: data.real_name ?? '',
    username: data.username,
  });
}

async function handleSubmit(values: Record<string, any>) {
  const avatarFiles = Array.isArray(values.avatar) ? values.avatar : [];
  const avatarFile = avatarFiles[0];
  const avatar = avatarFile?.response?.file_url ?? avatarFile?.url ?? '';

  await updateMyProfileApi({
    avatar,
    email: values.email ?? '',
    mobile: values.mobile ?? '',
    nickname: values.nickname ?? '',
    real_name: values.real_name ?? '',
  });
  await authStore.fetchUserInfo();
  message.success('个人信息已更新');
  await loadProfile();
}

onMounted(async () => {
  await loadProfile();
});
</script>
<template>
  <ProfileBaseSetting
    ref="profileBaseSettingRef"
    class="w-full max-w-xl"
    :form-schema="formSchema"
    @submit="handleSubmit"
  />
</template>
