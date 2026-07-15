export interface PluginPanelDefinition {
  id: string;
  component: ReturnType<typeof import('vue').defineAsyncComponent> | object;
}

export interface PluginPanelModule {
  panels: PluginPanelDefinition[];
}
