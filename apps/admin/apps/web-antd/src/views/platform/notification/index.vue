<script lang="ts" setup>
import { Fallback, Page } from '@vben/common-ui';
</script>

<template>
  <Page auto-content-height>
    <Fallback
      description="通知配置后端接口尚未实现，后续将支持邮件、短信、站内信等渠道配置。"
      status="coming-soon"
      title="通知配置"
    />
  </Page>
</template>
