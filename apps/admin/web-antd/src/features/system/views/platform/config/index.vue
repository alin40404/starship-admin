<script lang="ts" setup>
import { computed, ref, useTemplateRef } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

import { $t } from '@vben/locales';

import { Button } from 'ant-design-vue';

import { CONFIG_PERMISSIONS } from '#/features/system/constants/config-permissions';
import { PARAM_PERMISSIONS } from '#/features/system/constants/param-permissions';

import {
  getConfigGroupDescriptions,
  getConfigGroupLabels,
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
    label: getConfigGroupLabels().basic!,
    description: getConfigGroupDescriptions().basic!,
  },
  {
    key: 'param',
    label: $t('platform.config.groups.runtime'),
    description: $t('platform.config.groupDescriptions.runtime'),
  },
  {
    key: 'sms',
    label: getConfigGroupLabels().sms!,
    description: getConfigGroupDescriptions().sms!,
  },
  {
    key: 'inbox',
    label: getConfigGroupLabels().inbox!,
    description: getConfigGroupDescriptions().inbox!,
  },
  {
    key: 'email',
    label: getConfigGroupLabels().email!,
    description: getConfigGroupDescriptions().email!,
  },
  {
    key: 'storage',
    label: getConfigGroupLabels().storage!,
    description: getConfigGroupDescriptions().storage!,
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
        class="config-section-tabs flex shrink-0 flex-row gap-2 overflow-x-auto overscroll-x-contain border-b border-border p-2 md:w-[200px] md:flex-col md:gap-0 md:overflow-x-visible md:overflow-y-auto md:border-b-0 md:border-r md:p-0"
      >
        <button
          v-for="item in sections"
          :key="item.key"
          class="config-section-item"
          :class="{ 'is-active': activeSection === item.key }"
          type="button"
          @click="activeSection = item.key"
        >
          <div class="config-section-item__label">{{ item.label }}</div>
          <div
            class="config-section-item__desc"
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
            class="config-save-btn w-full shrink-0 sm:w-auto"
            type="primary"
            @click="onSettingsSave"
          >
            {{ $t('platform.config.saveConfig') }}
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

.config-section-item {
  flex-shrink: 0;
  scroll-snap-align: start;
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  background: transparent;
  padding: 6px 14px;
  text-align: center;
  transition:
    background-color 0.2s,
    border-color 0.2s,
    color 0.2s;
  cursor: pointer;
}

.config-section-item__label {
  font-size: 14px;
  line-height: 1.25;
  white-space: nowrap;
}

.config-section-item__desc {
  display: none;
}

.config-section-item:hover {
  border-color: hsl(var(--primary) / 0.35);
  background-color: hsl(var(--accent));
  color: hsl(var(--accent-foreground));
}

.config-section-item.is-active {
  border-color: hsl(var(--primary) / 0.25);
  background-color: hsl(var(--primary) / 0.08);
  color: hsl(var(--primary));
  font-weight: 500;
}

.config-section-item.is-active .config-section-item__label {
  color: hsl(var(--primary));
}

@media (max-width: 767px) {
  .config-save-btn.ant-btn {
    justify-content: center;
  }
}

@media (min-width: 768px) {
  .config-section-tabs {
    flex-direction: column;
    gap: 0;
    overflow-x: visible;
    overflow-y: auto;
  }

  .config-section-item {
    width: 100%;
    border: 0;
    border-left: 2px solid transparent;
    border-radius: 0;
    padding: 17px 12px;
    text-align: left;
    color: hsl(var(--foreground));
  }

  .config-section-item__desc {
    display: block;
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 12px;
    line-height: 1.25;
    color: hsl(var(--muted-foreground) / 0.55);
  }

  .config-section-item:hover {
    border-color: transparent;
    border-left-color: hsl(var(--border));
    background-color: hsl(var(--accent));
    color: hsl(var(--accent-foreground));
  }

  .config-section-item:hover .config-section-item__desc {
    color: hsl(var(--muted-foreground));
  }

  .config-section-item.is-active {
    border-color: transparent;
    border-left-color: hsl(var(--primary));
    background-color: hsl(var(--primary) / 0.08);
    color: hsl(var(--primary));
  }

  .config-section-item.is-active .config-section-item__desc {
    color: hsl(var(--muted-foreground));
  }
}
</style>
