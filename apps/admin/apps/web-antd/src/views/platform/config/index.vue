<script lang="ts" setup>
import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

import { Tabs } from 'ant-design-vue';

import { CONFIG_PERMISSIONS } from '#/constants/config-permissions';
import { PARAM_PERMISSIONS } from '#/constants/param-permissions';

import RuntimeParams from './modules/runtime-params.vue';
import SiteSettings from './modules/site-settings.vue';

const { hasAccessByCodes } = useAccess();

const activeTab = ref('site');

const canManageSite = () => hasAccessByCodes([CONFIG_PERMISSIONS.MANAGE]);
const canManageParams = () =>
  hasAccessByCodes([CONFIG_PERMISSIONS.MANAGE, PARAM_PERMISSIONS.MANAGE]);
</script>

<template>
  <Page auto-content-height>
    <Tabs v-model:active-key="activeTab">
      <Tabs.TabPane key="site" tab="站点设置">
        <SiteSettings :can-manage="canManageSite()" />
      </Tabs.TabPane>
      <Tabs.TabPane key="param" tab="运行参数">
        <RuntimeParams :can-manage="canManageParams()" />
      </Tabs.TabPane>
    </Tabs>
  </Page>
</template>
