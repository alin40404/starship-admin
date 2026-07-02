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
} from './data';
import RuntimeParams from './modules/runtime-params.vue';
import SettingsForm from './modules/settings-form.vue';

type ConfigSectionKey = 'basic' | 'email' | 'param';

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

const canManageSite = () => hasAccessByCodes([CONFIG_PERMISSIONS.MANAGE]);
const canManageParams = () =>
  hasAccessByCodes([CONFIG_PERMISSIONS.MANAGE, PARAM_PERMISSIONS.MANAGE]);

const showSettingsSave = computed(
  () =>
    (activeSection.value === 'basic' || activeSection.value === 'email') &&
    canManageSite(),
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
    key: 'email',
    label: CONFIG_GROUP_LABELS.email!,
    description: CONFIG_GROUP_DESCRIPTIONS.email!,
  },
  {
    key: 'param',
    label: '运行参数',
    description: '维护可动态增删的运行参数',
  },
];

const currentSection = computed(
  () => sections.find((item) => item.key === activeSection.value) ?? sections[0]!,
);
</script>

<template>
  <Page auto-content-height>
    <div
      class="bg-card flex h-full min-h-[520px] overflow-hidden rounded-lg border border-border"
    >
      <aside
        class="flex w-[200px] shrink-0 flex-col gap-1 border-r border-border p-3"
      >
        <button
          v-for="item in sections"
          :key="item.key"
          class="w-full rounded-md px-3 py-2.5 text-left transition-colors"
          :class="
            activeSection === item.key
              ? 'bg-primary/10 text-primary font-medium'
              : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
          "
          type="button"
          @click="activeSection = item.key"
        >
          <div class="truncate text-sm">{{ item.label }}</div>
          <div
            class="text-muted-foreground mt-0.5 truncate text-xs leading-5"
            :title="item.description"
          >
            {{ item.description }}
          </div>
        </button>
      </aside>

      <main class="flex min-h-0 flex-1 flex-col overflow-hidden p-5">
        <div
          class="mb-4 flex shrink-0 items-center justify-between gap-4 border-b border-border pb-4"
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
            class="shrink-0"
            type="primary"
            @click="onSettingsSave"
          >
            保存配置
          </Button>
        </div>

        <SettingsForm
          v-if="activeSection === 'basic'"
          ref="settingsFormRef"
          class="min-h-0 flex-1"
          group-name="basic"
          :can-manage="canManageSite()"
        />
        <SettingsForm
          v-else-if="activeSection === 'email'"
          ref="settingsFormRef"
          class="min-h-0 flex-1"
          group-name="email"
          :can-manage="canManageSite()"
        />
        <RuntimeParams
          v-else-if="activeSection === 'param'"
          class="min-h-0 flex-1"
          :can-manage="canManageParams()"
        />
      </main>
    </div>
  </Page>
</template>
