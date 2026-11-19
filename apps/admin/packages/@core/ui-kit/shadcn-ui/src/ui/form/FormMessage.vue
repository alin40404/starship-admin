<script lang="ts" setup>
import { toValue } from 'vue';

import { ErrorMessage } from 'vee-validate';

import { useFormField } from './useFormField';

const { formMessageId, name } = useFormField();
</script>

<template>
  <ErrorMessage
    :id="formMessageId"
    :name="toValue(name)"
    as="p"
    class="text-destructive form-message-text"
  />
</template>

<style scoped>
.form-message-text {
  /* 校验错误单独档：base × 12/16（主题 16 → 12px） */
  font-size: calc(var(--font-size-base, 16px) * 12 / 16);
}
</style>
