export { setupVbenForm } from './config';

export type {
  BaseFormComponentType,
  ExtendedFormApi,
  VbenFormProps,
  FormSchema as VbenFormSchema,
} from './types';

export {
  countFilledSearchFields,
  isSearchFieldEmpty,
  resolveCrudSearchViews,
} from './crud-search-schema';
export type { CrudSearchViews } from './crud-search-schema';

export * from './use-vben-form';
// export { default as VbenForm } from './vben-form.vue';
export * as z from 'zod';
