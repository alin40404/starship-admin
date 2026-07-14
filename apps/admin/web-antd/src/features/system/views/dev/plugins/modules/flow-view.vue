<script lang="ts" setup>
import type { PluginRow } from '#/features/system/api/dev-plugin';

import { IconifyIcon } from '@vben/icons';

import { Switch, Tag } from 'ant-design-vue';

import {
  PLUGIN_PERMISSIONS,
  PLUGIN_STATUS,
} from '#/features/system/constants/plugin-permissions';
import { $t } from '#/locales';

import type { PluginAccessChecker } from '../data';

const props = defineProps<{
  access: PluginAccessChecker;
  loading?: boolean;
  rows: PluginRow[];
  statusLoading?: Record<string, boolean>;
}>();

const emit = defineEmits<{
  action: [code: string, row: PluginRow];
  statusChange: [enabled: boolean, row: PluginRow];
}>();

function statusColor(status: number) {
  switch (status) {
    case PLUGIN_STATUS.ENABLED: {
      return 'success';
    }
    case PLUGIN_STATUS.INSTALLED: {
      return 'processing';
    }
    case PLUGIN_STATUS.DISABLED: {
      return 'warning';
    }
    default: {
      return 'default';
    }
  }
}

function canInstall(row: PluginRow) {
  return (
    props.access.can(PLUGIN_PERMISSIONS.INSTALL) &&
    row.status === PLUGIN_STATUS.DISCOVERED
  );
}

function canConfig(row: PluginRow) {
  return (
    props.access.can(PLUGIN_PERMISSIONS.CONFIG) &&
    row.has_config &&
    row.installed
  );
}

function canUninstall(row: PluginRow) {
  return (
    props.access.can(PLUGIN_PERMISSIONS.UNINSTALL) &&
    (row.status === PLUGIN_STATUS.INSTALLED ||
      row.status === PLUGIN_STATUS.DISABLED)
  );
}

function canToggleStatus(row: PluginRow) {
  if (!row.installed) {
    return false;
  }
  if (row.status === PLUGIN_STATUS.ENABLED) {
    return props.access.can(PLUGIN_PERMISSIONS.DISABLE);
  }
  return props.access.can(PLUGIN_PERMISSIONS.ENABLE);
}

function iconGlyph(row: PluginRow) {
  const source = row.name || row.slug || '?';
  return source.slice(0, 1).toUpperCase();
}

function onStatusChange(checked: boolean | string | number, row: PluginRow) {
  emit('statusChange', Boolean(checked), row);
}

function switchLabel(row: PluginRow) {
  if (row.status === PLUGIN_STATUS.ENABLED) {
    return $t('dev.plugins.statusEnabled');
  }
  if (!row.installed) {
    return $t('dev.plugins.statusDiscovered');
  }
  return $t('dev.plugins.switchOff');
}
</script>

<template>
  <div class="plugin-flow">
    <div v-if="loading" class="plugin-flow__empty text-muted-foreground">
      {{ $t('common.loading') }}
    </div>
    <div v-else-if="rows.length === 0" class="plugin-flow__empty">
      {{ $t('dev.plugins.empty') }}
    </div>
    <div v-else class="plugin-flow__grid">
      <article
        v-for="row in rows"
        :key="row.slug"
        class="plugin-card"
        :class="{ 'is-enabled': row.status === PLUGIN_STATUS.ENABLED }"
      >
        <div class="plugin-card__main">
          <button
            class="plugin-card__icon"
            type="button"
            @click="emit('action', 'detail', row)"
          >
            <span class="plugin-card__icon-glyph">{{ iconGlyph(row) }}</span>
            <IconifyIcon
              class="plugin-card__icon-mark"
              icon="lucide:puzzle"
            />
          </button>

          <div class="plugin-card__content">
            <div class="plugin-card__title-row">
              <h3
                class="plugin-card__title"
                :title="row.name"
                @click="emit('action', 'detail', row)"
              >
                {{ row.name }}
              </h3>
              <Tag :color="statusColor(row.status)" class="!m-0 shrink-0">
                {{ row.status_label }}
              </Tag>
            </div>
            <p class="plugin-card__meta">
              <span>{{ row.slug }}</span>
              <span v-if="row.type">·</span>
              <span v-if="row.type">{{ row.type_label || row.type }}</span>
              <span>·</span>
              <span>v{{ row.version }}</span>
            </p>
          </div>
        </div>

        <p class="plugin-card__desc">
          {{ row.description || $t('dev.themes.noDescription') }}
        </p>

        <div class="plugin-card__footer">
          <div class="plugin-card__switch">
            <Switch
              :checked="row.status === PLUGIN_STATUS.ENABLED"
              :disabled="!canToggleStatus(row) || !!statusLoading?.[row.slug]"
              :loading="!!statusLoading?.[row.slug]"
              size="small"
              @change="(checked) => onStatusChange(checked, row)"
            />
            <span class="plugin-card__switch-label">
              {{ switchLabel(row) }}
            </span>
          </div>

          <div class="plugin-card__actions">
            <button
              v-if="access.can(PLUGIN_PERMISSIONS.QUERY)"
              class="plugin-card__btn"
              type="button"
              @click="emit('action', 'detail', row)"
            >
              {{ $t('dev.shared.detail') }}
            </button>
            <button
              v-if="canConfig(row)"
              class="plugin-card__btn plugin-card__btn--primary"
              type="button"
              @click="emit('action', 'config', row)"
            >
              {{ $t('dev.shared.config') }}
            </button>
            <button
              v-if="canInstall(row)"
              class="plugin-card__btn plugin-card__btn--success"
              type="button"
              @click="emit('action', 'install', row)"
            >
              {{ $t('dev.shared.install') }}
            </button>
            <button
              v-if="canUninstall(row)"
              class="plugin-card__btn plugin-card__btn--danger"
              type="button"
              @click="emit('action', 'uninstall', row)"
            >
              {{ $t('dev.shared.uninstall') }}
            </button>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<style scoped>
.plugin-flow {
  height: 100%;
  min-height: 0;
  overflow: auto;
  padding: 4px 2px 16px;
}

.plugin-flow__empty {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 240px;
  color: hsl(var(--muted-foreground));
  font-size: 0.875rem;
}

.plugin-flow__grid {
  display: grid;
  /* min(100%, 340px) 避免窄屏下强制 340px 横向溢出 */
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
  gap: 20px;
}

.plugin-card {
  display: flex;
  flex-direction: column;
  gap: 14px;
  min-width: 0;
  min-height: 200px;
  padding: 20px 22px;
  border: 1px solid hsl(var(--border));
  border-radius: 12px;
  background: hsl(var(--card));
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.plugin-card:hover {
  border-color: hsl(var(--primary) / 0.35);
  box-shadow: 0 8px 24px hsl(var(--foreground) / 0.06);
}

.plugin-card.is-enabled {
  border-color: hsl(142 71% 45% / 0.45);
  box-shadow: 0 0 0 1px hsl(142 71% 45% / 0.12);
}

.plugin-card__main {
  display: flex;
  gap: 16px;
  min-width: 0;
}

.plugin-card__icon {
  position: relative;
  display: flex;
  flex: 0 0 64px;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  padding: 0;
  overflow: hidden;
  border: 1px solid hsl(var(--border));
  border-radius: 12px;
  background: hsl(var(--muted) / 0.45);
  cursor: pointer;
}

.plugin-card__icon:hover {
  border-color: hsl(var(--primary) / 0.4);
}

.plugin-card__icon-glyph {
  color: hsl(var(--foreground) / 0.72);
  font-size: 1.45rem;
  font-weight: 700;
  line-height: 1;
}

.plugin-card__icon-mark {
  position: absolute;
  right: 4px;
  bottom: 4px;
  width: 12px;
  height: 12px;
  color: hsl(var(--muted-foreground));
  opacity: 0.7;
}

.plugin-card__content {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
  justify-content: center;
  gap: 6px;
}

.plugin-card__title-row {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: space-between;
  min-width: 0;
}

.plugin-card__title {
  margin: 0;
  min-width: 0;
  overflow: hidden;
  color: hsl(var(--foreground));
  font-size: 1.125rem;
  font-weight: 600;
  line-height: 1.35;
  text-overflow: ellipsis;
  white-space: nowrap;
  cursor: pointer;
}

.plugin-card__title:hover {
  color: hsl(var(--primary));
}

.plugin-card__meta {
  margin: 0;
  min-width: 0;
  overflow: hidden;
  color: hsl(var(--muted-foreground));
  font-size: 12px;
  line-height: 1.4;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.plugin-card__desc {
  display: -webkit-box;
  flex: 1;
  margin: 0;
  overflow: hidden;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
  line-height: 1.55;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
}

.plugin-card__footer {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
  padding-top: 14px;
  border-top: 1px solid hsl(var(--border));
}

.plugin-card__switch {
  display: inline-flex;
  gap: 8px;
  align-items: center;
  min-height: 28px;
}

.plugin-card__switch-label {
  color: hsl(var(--muted-foreground));
  font-size: 12px;
  line-height: 1.4;
}

.plugin-card__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.plugin-card__btn {
  padding: 4px 10px;
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  background: transparent;
  color: hsl(var(--foreground));
  font-size: 12px;
  line-height: 1.5;
  cursor: pointer;
}

.plugin-card__btn:hover {
  border-color: hsl(var(--primary) / 0.45);
  color: hsl(var(--primary));
}

.plugin-card__btn--primary {
  border-color: hsl(var(--primary) / 0.45);
  background: hsl(var(--primary));
  color: #fff;
}

.plugin-card__btn--primary:hover {
  color: #fff;
  filter: brightness(1.05);
}

.plugin-card__btn--success {
  border-color: hsl(142 71% 45% / 0.4);
  background: hsl(142 71% 40%);
  color: #fff;
}

.plugin-card__btn--success:hover {
  color: #fff;
  filter: brightness(1.05);
}

.plugin-card__btn--danger {
  border-color: hsl(0 84% 60% / 0.35);
  color: hsl(0 72% 51%);
}

.plugin-card__btn--danger:hover {
  border-color: hsl(0 84% 60% / 0.55);
  color: hsl(0 72% 45%);
}

@media (max-width: 768px) {
  .plugin-flow {
    padding: 0 0 12px;
  }

  .plugin-flow__grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .plugin-card {
    min-height: 0;
    padding: 16px;
  }

  .plugin-card__icon {
    flex: 0 0 52px;
    width: 52px;
    height: 52px;
  }

  .plugin-card__icon-glyph {
    font-size: 1.25rem;
  }

  .plugin-card__title {
    font-size: 1rem;
  }

  .plugin-card__footer {
    gap: 8px;
  }
}
</style>
