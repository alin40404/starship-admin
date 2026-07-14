export type PluginAccessChecker = {
  can: (code: string) => boolean;
};
