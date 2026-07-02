<script lang="ts" setup>
import { computed, ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

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

const canManageSite = () => hasAccessByCodes([CONFIG_PERMISSIONS.MANAGE]);
const canManageParams = () =>
  hasAccessByCodes([CONFIG_PERMISSIONS.MANAGE, PARAM_PERMISSIONS.MANAGE]);

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
          class="rounded-md px-3 py-2.5 text-left transition-colors"
          :class="
            activeSection === item.key
              ? 'bg-primary/10 text-primary font-medium'
              : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
          "
          type="button"
          @click="activeSection = item.key"
        >
          <div class="text-sm">{{ item.label }}</div>
          <div class="text-muted-foreground mt-0.5 text-xs leading-5">
            {{ item.description }}
          </div>
        </button>
      </aside>

      <main class="flex min-h-0 flex-1 flex-col overflow-hidden p-5">
        <div class="mb-4 shrink-0">
          <h3 class="text-base font-medium">{{ currentSection.label }}</h3>
          <p class="text-muted-foreground mt-1 text-sm">
            {{ currentSection.description }}
          </p>
        </div>

        <SettingsForm
          v-if="activeSection === 'basic'"
          class="min-h-0 flex-1"
          group-name="basic"
          :can-manage="canManageSite()"
        />
        <SettingsForm
          v-else-if="activeSection === 'email'"
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
