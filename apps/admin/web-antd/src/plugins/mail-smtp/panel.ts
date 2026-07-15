import type { PluginPanelModule } from '../types';

import { defineAsyncComponent } from 'vue';

/**
 * 后台前端面板登记（管理端 config-drawer ui=component）
 * id 须等于 plugin.json pages[].component
 */
const panels: PluginPanelModule['panels'] = [
  {
    id: 'mail-smtp-advanced',
    component: defineAsyncComponent(() => import('./advanced.vue')),
  },
];

export default { panels } satisfies PluginPanelModule;
