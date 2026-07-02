<script lang="ts" setup>
import { computed, ref, useTemplateRef } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

import { Button } from 'ant-design-vue';

import { CONFIG_PERMISSIONS } from '#/constants/config-permissions';
import { PARAM_PERMISSIONS } from '#/constants/param-permissions';

import {
  CONFIG_GROUP_DESCRIPTIONS,
  CONFIG_GROUP_LABELS,
  CONFIG_SETTINGS_GROUPS,
  type ConfigSettingsGroup,
} from './data';
import RuntimeParams from './modules/runtime-params.vue';
import SettingsForm from './modules/settings-form.vue';

type ConfigSectionKey = ConfigSettingsGroup | 'param';

interface ConfigSection {
  description: string;
  key: ConfigSectionKey;
  label: string;
}

const { hasAccessByCodes } = useAccess();

const activeSection = ref<ConfigSectionKey>('basic');
const settingsFormRef = useTemplateRef<InstanceType<typeof SettingsForm>>(
  'settingsFormRef',
);

const canManageSite = () => hasAccessByCodes([CONFIG_PERMISSIONS.UPDATE]);
const canManageParams = () =>
  hasAccessByCodes([CONFIG_PERMISSIONS.UPDATE, PARAM_PERMISSIONS.MANAGE]);

const isSettingsSection = computed(() =>
  (CONFIG_SETTINGS_GROUPS as readonly string[]).includes(activeSection.value),
);

const activeSettingsGroup = computed(
  () => activeSection.value as ConfigSettingsGroup,
);

const showSettingsSave = computed(
  () => isSettingsSection.value && canManageSite(),
);

const settingsSaving = computed(
  () => settingsFormRef.value?.saving ?? false,
);

async function onSettingsSave() {
  await settingsFormRef.value?.onSave();
}

const sections: ConfigSection[] = [
  {
    key: 'basic',
    label: CONFIG_GROUP_LABELS.basic!,
    description: CONFIG_GROUP_DESCRIPTIONS.basic!,
  },
  {
    key: 'param',
    label: '运行参数',
    description: '维护可动态增删的运行参数',
  },
  {
    key: 'sms',
    label: CONFIG_GROUP_LABELS.sms!,
    description: CONFIG_GROUP_DESCRIPTIONS.sms!,
  },
  {
    key: 'inbox',
    label: CONFIG_GROUP_LABELS.inbox!,
    description: CONFIG_GROUP_DESCRIPTIONS.inbox!,
  },
  {
    key: 'email',
    label: CONFIG_GROUP_LABELS.email!,
    description: CONFIG_GROUP_DESCRIPTIONS.email!,
  },
];

const currentSection = computed(
  () => sections.find((item) => item.key === activeSection.value) ?? sections[0]!,
);
</script>

<template>
  <Page auto-content-height>
    <div
      class="bg-card flex h-full min-h-0 flex-col overflow-hidden rounded-lg border border-border md:min-h-[520px] md:flex-row"
    >
      <aside
        class="config-section-tabs flex shrink-0 flex-row gap-1 overflow-x-auto overscroll-x-contain border-b border-border p-2 md:w-[200px] md:flex-col md:overflow-x-visible md:overflow-y-auto md:border-b-0 md:border-r md:p-3"
      >
        <button
          v-for="item in sections"
          :key="item.key"
          class="shrink-0 snap-start rounded-md px-3 py-2 text-center transition-colors md:w-full md:flex-none md:px-3 md:py-2.5 md:text-left"
          :class="
            activeSection === item.key
              ? 'bg-primary/10 text-primary font-medium'
              : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
          "
          type="button"
          @click="activeSection = item.key"
        >
          <div class="whitespace-nowrap text-sm">{{ item.label }}</div>
          <div
            class="text-muted-foreground mt-0.5 hidden truncate text-xs leading-5 md:block"
            :title="item.description"
          >
            {{ item.description }}
          </div>
        </button>
      </aside>

      <main class="flex min-h-0 flex-1 flex-col overflow-hidden">
        <div
          class="flex shrink-0 flex-col gap-3 border-b border-border px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
        >
          <div class="min-w-0">
            <h3 class="text-base font-medium">{{ currentSection.label }}</h3>
            <p
              class="text-muted-foreground mt-1 truncate text-sm"
              :title="currentSection.description"
            >
              {{ currentSection.description }}
            </p>
          </div>
          <Button
            v-if="showSettingsSave"
            :loading="settingsSaving"
            class="w-full shrink-0 sm:w-auto"
            type="primary"
            @click="onSettingsSave"
          >
            保存配置
          </Button>
        </div>

        <SettingsForm
          v-if="isSettingsSection"
          ref="settingsFormRef"
          class="min-h-0 flex-1 px-5 py-3"
          :group-name="activeSettingsGroup"
          :can-manage="canManageSite()"
        />
        <RuntimeParams
          v-else
          class="min-h-0 flex-1 px-5 py-3"
          :can-manage="canManageParams()"
        />
      </main>
    </div>
  </Page>
</template>

<style scoped>
.config-section-tabs {
  -webkit-overflow-scrolling: touch;
  scroll-snap-type: x proximity;
  scrollbar-width: thin;
}

.config-section-tabs::-webkit-scrollbar {
  height: 4px;
}

.config-section-tabs::-webkit-scrollbar-thumb {
  border-radius: 9999px;
  background-color: hsl(var(--border));
}
</style>
