<script setup lang="ts">
import type { Props } from './types';

import { preferences } from '@vben-core/preferences';
import {
  Card,
  Separator,
  Tabs,
  TabsList,
  TabsTrigger,
  VbenAvatar,
} from '@vben-core/shadcn-ui';

import { Page } from '../../components';

defineOptions({
  name: 'ProfileUI',
});

withDefaults(defineProps<Props>(), {
  title: '关于项目',
  tabs: () => [],
});

const tabsValue = defineModel<string>('modelValue');
</script>
<template>
  <Page auto-content-height>
    <div class="flex size-full min-h-0 flex-col gap-4 lg:flex-row">
      <Card class="w-full flex-none lg:w-1/6">
        <div
          class="mt-4 flex items-center gap-4 px-4 lg:h-40 lg:flex-col lg:justify-center lg:px-0"
        >
          <VbenAvatar
            :src="userInfo?.avatar ?? preferences.app.defaultAvatar"
            class="size-16 shrink-0 lg:size-20"
          />
          <div class="min-w-0 lg:text-center">
            <div class="truncate text-lg font-semibold">
              {{ userInfo?.realName ?? '' }}
            </div>
            <div class="truncate text-sm text-foreground/80">
              {{ userInfo?.username ?? '' }}
            </div>
          </div>
        </div>
        <Separator class="my-4" />
        <Tabs
          v-model="tabsValue"
          orientation="vertical"
          class="m-4 hidden lg:block"
        >
          <TabsList class="grid w-full grid-cols-1 bg-card">
            <TabsTrigger
              v-for="tab in tabs"
              :key="tab.value"
              :value="tab.value"
              class="h-12 justify-start data-[state=active]:bg-primary data-[state=active]:text-primary-foreground"
            >
              {{ tab.label }}
            </TabsTrigger>
          </TabsList>
        </Tabs>
        <Tabs v-model="tabsValue" class="mx-4 mb-4 lg:hidden">
          <TabsList class="flex w-full bg-card">
            <TabsTrigger
              v-for="tab in tabs"
              :key="tab.value"
              :value="tab.value"
              class="h-10 flex-1 justify-center data-[state=active]:bg-primary data-[state=active]:text-primary-foreground"
            >
              {{ tab.label }}
            </TabsTrigger>
          </TabsList>
        </Tabs>
      </Card>
      <Card class="w-full min-w-0 flex-auto p-4 sm:p-6 lg:p-8">
        <slot name="content"></slot>
      </Card>
    </div>
  </Page>
</template>
