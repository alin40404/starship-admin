<script lang="ts" setup>
import type { ThemeRow } from '#/features/system/api/dev-theme';

import { Tag } from 'ant-design-vue';

import {
  getThemeStatusLabel,
  getThemeStatusTagColor,
  THEME_PERMISSIONS,
  THEME_STATUS,
} from '#/features/system/constants/theme-permissions';
import { $t } from '#/locales';

import ThemeCover from './theme-cover.vue';

export type ThemeAccessChecker = {
  can: (code: string) => boolean;
};

const props = defineProps<{
  access: ThemeAccessChecker;
  loading?: boolean;
  rows: ThemeRow[];
}>();

const emit = defineEmits<{
  action: [code: string, row: ThemeRow];
}>();

function canInstall(row: ThemeRow) {
  return (
    props.access.can(THEME_PERMISSIONS.INSTALL) &&
    row.status === THEME_STATUS.DISCOVERED
  );
}

function canEnable(row: ThemeRow) {
  return (
    props.access.can(THEME_PERMISSIONS.ENABLE) &&
    [THEME_STATUS.INSTALLED, THEME_STATUS.DISABLED].includes(row.status)
  );
}

function canDisable(row: ThemeRow) {
  return (
    props.access.can(THEME_PERMISSIONS.DISABLE) &&
    row.status === THEME_STATUS.ENABLED
  );
}

function canUninstall(row: ThemeRow) {
  return (
    props.access.can(THEME_PERMISSIONS.UNINSTALL) &&
    [THEME_STATUS.INSTALLED, THEME_STATUS.DISABLED].includes(row.status)
  );
}

function canDelete(row: ThemeRow) {
  if (!props.access.can(THEME_PERMISSIONS.DELETE)) {
    return false;
  }
  // 仅「未安装」可删：须先卸载；启用态/当前激活不可删
  if (row.status !== THEME_STATUS.DISCOVERED || row.is_active) {
    return false;
  }
  if (typeof row.deletable === 'boolean') {
    return row.deletable;
  }
  return true;
}

function canSwitch(row: ThemeRow) {
  return (
    props.access.can(THEME_PERMISSIONS.SWITCH) &&
    row.status === THEME_STATUS.ENABLED &&
    !row.is_active
  );
}
</script>

<template>
  <div class="theme-flow">
    <div v-if="loading" class="theme-flow__empty text-muted-foreground">
      {{ $t('common.loading') }}
    </div>
    <div v-else-if="rows.length === 0" class="theme-flow__empty">
      {{ $t('dev.themes.empty') }}
    </div>
    <div v-else class="theme-flow__grid">
      <article
        v-for="row in rows"
        :key="row.slug"
        class="theme-card"
        :class="{
          'is-enabled': row.status === THEME_STATUS.ENABLED,
          'is-active': row.is_active,
        }"
      >
        <div class="theme-card__cover" @click="emit('action', 'detail', row)">
          <ThemeCover
            :cover-url="row.cover_url"
            :slug="row.slug"
            :title="row.title"
          />
          <div v-if="row.is_active" class="theme-card__badge">
            {{ $t('dev.themes.activeBadge') }}
          </div>
        </div>

        <div class="theme-card__body">
          <div class="theme-card__title-row">
            <h3 class="theme-card__title" :title="row.title">{{ row.title }}</h3>
            <Tag :color="getThemeStatusTagColor(row.status)" class="!m-0 shrink-0">
              {{ getThemeStatusLabel(row.status) }}
            </Tag>
          </div>
          <p class="theme-card__meta">
            <span>{{ row.slug }}</span>
            <span>·</span>
            <span>{{ row.type }}</span>
            <span>·</span>
            <span>v{{ row.version }}</span>
          </p>
          <p class="theme-card__desc">
            {{ row.description || $t('dev.themes.noDescription') }}
          </p>

          <div class="theme-card__actions">
            <button
              v-if="canInstall(row)"
              class="theme-card__btn theme-card__btn--primary"
              type="button"
              @click="emit('action', 'install', row)"
            >
              {{ $t('dev.shared.install') }}
            </button>
            <button
              v-if="canEnable(row)"
              class="theme-card__btn theme-card__btn--success"
              type="button"
              @click="emit('action', 'enable', row)"
            >
              {{ $t('dev.shared.enable') }}
            </button>
            <button
              v-if="canSwitch(row)"
              class="theme-card__btn theme-card__btn--primary"
              type="button"
              @click="emit('action', 'switch', row)"
            >
              {{ $t('dev.themes.switch') }}
            </button>
            <button
              v-if="canDisable(row)"
              class="theme-card__btn theme-card__btn--warning"
              type="button"
              @click="emit('action', 'disable', row)"
            >
              {{ $t('dev.shared.disable') }}
            </button>
            <button
              v-if="canUninstall(row)"
              class="theme-card__btn theme-card__btn--danger"
              type="button"
              @click="emit('action', 'uninstall', row)"
            >
              {{ $t('dev.shared.uninstall') }}
            </button>
            <button
              v-if="canDelete(row)"
              class="theme-card__btn theme-card__btn--danger"
              type="button"
              @click="emit('action', 'delete', row)"
            >
              {{ $t('dev.themes.delete') }}
            </button>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<style scoped>
.theme-flow {
  height: 100%;
  min-height: 0;
  overflow: auto;
  padding: 4px 2px 16px;
}

.theme-flow__empty {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 240px;
  color: hsl(var(--muted-foreground));
  font-size: 0.875rem;
}

.theme-flow__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.theme-card {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid hsl(var(--border));
  border-radius: 12px;
  background: hsl(var(--card));
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.2s ease;
}

.theme-card:hover {
  border-color: hsl(var(--primary) / 0.45);
  box-shadow: 0 10px 28px hsl(var(--foreground) / 0.1);
  transform: translateY(-3px);
}

.theme-card.is-enabled {
  border-color: hsl(142 71% 45% / 0.5);
  box-shadow: 0 0 0 1px hsl(142 71% 45% / 0.14);
}

.theme-card.is-enabled:hover {
  border-color: hsl(142 71% 42% / 0.75);
  box-shadow:
    0 0 0 1px hsl(142 71% 42% / 0.22),
    0 10px 28px hsl(142 71% 35% / 0.16);
  transform: translateY(-3px);
}

.theme-card.is-active {
  border-color: hsl(142 71% 42% / 0.75);
  box-shadow:
    0 0 0 1px hsl(142 71% 42% / 0.28),
    0 8px 20px hsl(142 71% 35% / 0.12);
}

.theme-card.is-active:hover {
  border-color: hsl(142 71% 38% / 0.9);
  box-shadow:
    0 0 0 1px hsl(142 71% 38% / 0.36),
    0 12px 30px hsl(142 71% 35% / 0.2);
  transform: translateY(-3px);
}

.theme-card__cover {
  position: relative;
  cursor: pointer;
  overflow: hidden;
  border-bottom: 1px solid hsl(var(--border));
}

.theme-card__cover :deep(.theme-cover__img) {
  transition: transform 0.25s ease;
}

.theme-card:hover .theme-card__cover :deep(.theme-cover__img) {
  transform: scale(1.04);
}

.theme-card__badge {
  position: absolute;
  top: 10px;
  left: 10px;
  padding: 3px 10px;
  border-radius: 999px;
  background: hsl(142 71% 35%);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  line-height: 1.5;
  letter-spacing: 0.02em;
}

.theme-card__body {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 8px;
  padding: 14px 14px 12px;
}

.theme-card__title-row {
  display: flex;
  gap: 8px;
  align-items: flex-start;
  justify-content: space-between;
}

.theme-card__title {
  margin: 0;
  overflow: hidden;
  color: hsl(var(--foreground));
  font-size: 1rem;
  font-weight: 600;
  line-height: 1.35;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.theme-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin: 0;
  color: hsl(var(--muted-foreground));
  font-size: 12px;
}

.theme-card__desc {
  display: -webkit-box;
  flex: 1;
  margin: 0;
  overflow: hidden;
  color: hsl(var(--muted-foreground));
  font-size: 13px;
  line-height: 1.5;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.theme-card__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 4px;
}

.theme-card__btn {
  padding: 4px 10px;
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  background: transparent;
  color: hsl(var(--foreground));
  font-size: 12px;
  line-height: 1.5;
  cursor: pointer;
}

.theme-card__btn:hover {
  border-color: hsl(var(--primary) / 0.45);
  color: hsl(var(--primary));
}

.theme-card__btn--primary {
  border-color: hsl(var(--primary) / 0.45);
  background: hsl(var(--primary));
  color: #fff;
}

.theme-card__btn--primary:hover {
  color: #fff;
  filter: brightness(1.05);
}

.theme-card__btn--success {
  border-color: hsl(142 71% 45% / 0.4);
  background: hsl(142 71% 40%);
  color: #fff;
}

.theme-card__btn--success:hover {
  color: #fff;
  filter: brightness(1.05);
}

.theme-card__btn--warning {
  border-color: hsl(32 95% 44% / 0.4);
  background: hsl(32 95% 44%);
  color: #fff;
}

.theme-card__btn--warning:hover {
  color: #fff;
  filter: brightness(1.05);
}

.theme-card__btn--danger {
  border-color: hsl(0 84% 60% / 0.35);
  color: hsl(0 72% 51%);
}

.theme-card__btn--danger:hover {
  border-color: hsl(0 84% 60% / 0.55);
  color: hsl(0 72% 45%);
}
</style>
