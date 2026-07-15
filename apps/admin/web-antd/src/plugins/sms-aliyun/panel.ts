import type { PluginPanelModule } from '../types';

import { defineAsyncComponent } from 'vue';

const panels: PluginPanelModule['panels'] = [
  {
    id: 'sms-aliyun-templates',
    component: defineAsyncComponent(() => import('./templates.vue')),
  },
];

export default { panels } satisfies PluginPanelModule;
