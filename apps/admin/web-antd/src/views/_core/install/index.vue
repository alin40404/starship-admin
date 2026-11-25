<script lang="ts" setup>
import type { InstallApi } from '#/api/core/install';

import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import { LOGIN_PATH } from '@vben/constants';
import { IconifyIcon } from '@vben/icons';
import { preferences } from '@vben/preferences';

import {
  Alert,
  Button,
  Card,
  Form,
  FormItem,
  Input,
  InputPassword,
  Result,
  Select,
  Space,
  Steps,
  Table,
  Tag,
  message,
} from 'ant-design-vue';

import {
  fetchInstallRequirements,
  runInstall,
  saveInstallDatabase,
} from '#/api/core/install';
import { $t } from '#/locales';
import { setCachedInstallStatus } from '#/utils/install-status';

defineOptions({ name: 'InstallWizard' });

const router = useRouter();

const current = ref(0);
const loading = ref(false);
const requirements = ref<InstallApi.Requirements | null>(null);
const adminUsername = ref('admin');

const dbForm = reactive({
  connection: 'mysql' as InstallApi.DatabaseParams['connection'],
  host: '127.0.0.1',
  port: '3306',
  database: 'starship_admin',
  username: 'root',
  password: '',
  prefix: 'sa_',
  app_url: 'http://127.0.0.1:8000',
  cors_allowed_origins: '',
});

const adminForm = reactive({
  username: 'admin',
  password: '',
  passwordConfirm: '',
  email: 'admin@starship.org',
});

const stepItems = computed(() => [
  { title: $t('install.steps.requirements') },
  { title: $t('install.steps.database') },
  { title: $t('install.steps.install') },
  { title: $t('install.steps.done') },
]);

const connectionOptions = computed(() => [
  { label: $t('install.database.mysql'), value: 'mysql' },
  { label: $t('install.database.sqlite'), value: 'sqlite' },
  { label: $t('install.database.pgsql'), value: 'pgsql' },
]);

const isSqlite = computed(() => dbForm.connection === 'sqlite');

/** 上下表共用同一套百分比列宽（合计 100%），才能竖线对齐 */
const REQUIREMENT_COL_WIDTHS = {
  first: '40%',
  second: '40%',
  status: '20%',
  path: '80%',
} as const;

const phpColumns = computed(() => [
  {
    title: $t('install.requirements.minVersion'),
    dataIndex: 'required',
    key: 'required',
    width: REQUIREMENT_COL_WIDTHS.first,
  },
  {
    title: $t('install.requirements.currentVersion'),
    dataIndex: 'current',
    key: 'current',
    width: REQUIREMENT_COL_WIDTHS.second,
  },
  {
    title: $t('install.requirements.status'),
    key: 'status',
    width: REQUIREMENT_COL_WIDTHS.status,
  },
]);

const phpRows = computed(() =>
  requirements.value
    ? [
        {
          key: 'php',
          required: requirements.value.php.required,
          current: requirements.value.php.current,
          passed: requirements.value.php.passed,
        },
      ]
    : [],
);

const extensionColumns = computed(() => [
  {
    title: $t('install.requirements.extension'),
    dataIndex: 'name',
    key: 'name',
    width: REQUIREMENT_COL_WIDTHS.first,
  },
  {
    title: $t('install.requirements.type'),
    key: 'type',
    width: REQUIREMENT_COL_WIDTHS.second,
  },
  {
    title: $t('install.requirements.status'),
    key: 'status',
    width: REQUIREMENT_COL_WIDTHS.status,
  },
]);

const directoryColumns = computed(() => [
  {
    title: $t('install.requirements.path'),
    dataIndex: 'path',
    key: 'path',
    width: REQUIREMENT_COL_WIDTHS.path,
  },
  {
    title: $t('install.requirements.status'),
    key: 'status',
    width: REQUIREMENT_COL_WIDTHS.status,
  },
]);

async function loadRequirements() {
  loading.value = true;
  try {
    requirements.value = await fetchInstallRequirements();
  } finally {
    loading.value = false;
  }
}

async function onRequirementsNext() {
  if (!requirements.value?.passed) {
    message.warning($t('install.requirements.hint'));
    return;
  }
  current.value = 1;
}

async function onSaveDatabase() {
  loading.value = true;
  try {
    const payload: InstallApi.DatabaseParams = {
      connection: dbForm.connection,
      app_url: dbForm.app_url,
      prefix: dbForm.prefix,
      cors_allowed_origins: dbForm.cors_allowed_origins || undefined,
    };

    if (dbForm.connection === 'sqlite') {
      payload.database = dbForm.database || 'database/database.sqlite';
    } else {
      payload.host = dbForm.host;
      payload.port = dbForm.port;
      payload.database = dbForm.database;
      payload.username = dbForm.username;
      payload.password = dbForm.password;
    }

    await saveInstallDatabase(payload);
    message.success($t('install.database.testAndSave'));
    current.value = 2;
  } finally {
    loading.value = false;
  }
}

async function onRunInstall() {
  if (adminForm.password.length < 6) {
    message.error($t('install.install.passwordMin'));
    return;
  }
  if (adminForm.password !== adminForm.passwordConfirm) {
    message.error($t('install.install.passwordMismatch'));
    return;
  }

  loading.value = true;
  try {
    const result = await runInstall({
      username: adminForm.username,
      password: adminForm.password,
      email: adminForm.email,
    });
    adminUsername.value = result.admin_username;
    setCachedInstallStatus(true);
    current.value = 3;
  } finally {
    loading.value = false;
  }
}

function onConnectionChange(value: InstallApi.DatabaseParams['connection']) {
  dbForm.connection = value;
  if (value === 'sqlite') {
    dbForm.database = 'database/database.sqlite';
  } else if (value === 'pgsql') {
    dbForm.port = '5432';
    if (dbForm.database === 'database/database.sqlite') {
      dbForm.database = 'starship_admin';
    }
  } else {
    dbForm.port = '3306';
    if (dbForm.database === 'database/database.sqlite') {
      dbForm.database = 'starship_admin';
    }
  }
}

function goLogin() {
  router.replace(LOGIN_PATH);
}

onMounted(() => {
  void loadRequirements();
});
</script>

<template>
  <div class="install-page">
    <div class="install-page__panel">
      <div class="install-page__header">
        <div class="install-page__brand">
          {{ preferences.app.name || 'Starship Admin' }}
        </div>
        <h1 class="install-page__title">{{ $t('install.title') }}</h1>
        <p class="install-page__subtitle">{{ $t('install.subtitle') }}</p>
      </div>

      <Steps
        :current="current"
        :items="stepItems"
        class="install-page__steps"
        size="small"
      />

      <Card class="install-page__card" :bordered="false">
        <template v-if="current === 0">
          <Alert
            v-if="requirements && !requirements.passed"
            class="mb-4"
            type="warning"
            show-icon
            :message="$t('install.requirements.hint')"
          />

          <div v-if="requirements" class="space-y-4">
            <div>
              <div class="install-page__section-title mb-2">
                {{ $t('install.requirements.php') }}
              </div>
              <Table
                class="install-page__table install-page__table--3col"
                bordered
                :columns="phpColumns"
                :data-source="phpRows"
                :pagination="false"
                row-key="key"
                size="small"
              >
                <template #bodyCell="{ column, record }">
                  <template v-if="column.key === 'status'">
                    <IconifyIcon
                      class="size-4"
                      :class="
                        record.passed
                          ? 'text-success'
                          : 'text-destructive'
                      "
                      :icon="record.passed ? 'lucide:check' : 'lucide:x'"
                      :title="
                        record.passed
                          ? $t('install.requirements.passed')
                          : $t('install.requirements.failed')
                      "
                    />
                  </template>
                </template>
              </Table>
            </div>

            <div>
              <div class="install-page__section-title mb-2">
                {{ $t('install.requirements.extensions') }}
              </div>
              <Table
                class="install-page__table install-page__table--3col"
                bordered
                :columns="extensionColumns"
                :data-source="requirements.extensions"
                :pagination="false"
                row-key="name"
                size="small"
              >
                <template #bodyCell="{ column, record }">
                  <template v-if="column.key === 'name'">
                    <Tag>{{ record.name }}</Tag>
                  </template>
                  <template v-else-if="column.key === 'type'">
                    {{
                      record.required
                        ? $t('install.requirements.required')
                        : $t('install.requirements.optional')
                    }}
                  </template>
                  <template v-else-if="column.key === 'status'">
                    <IconifyIcon
                      class="size-4"
                      :class="
                        record.loaded
                          ? 'text-success'
                          : record.required
                            ? 'text-destructive'
                            : 'text-muted-foreground'
                      "
                      :icon="record.loaded ? 'lucide:check' : 'lucide:x'"
                      :title="
                        record.loaded
                          ? $t('install.requirements.passed')
                          : $t('install.requirements.failed')
                      "
                    />
                  </template>
                </template>
              </Table>
            </div>

            <div>
              <div class="install-page__section-title mb-2">
                {{ $t('install.requirements.directories') }}
              </div>
              <Table
                class="install-page__table install-page__table--2col"
                bordered
                :columns="directoryColumns"
                :data-source="requirements.directories"
                :pagination="false"
                row-key="path"
                size="small"
              >
                <template #bodyCell="{ column, record }">
                  <template v-if="column.key === 'status'">
                    <IconifyIcon
                      class="size-4"
                      :class="
                        record.writable
                          ? 'text-success'
                          : 'text-destructive'
                      "
                      :icon="record.writable ? 'lucide:check' : 'lucide:x'"
                      :title="
                        record.writable
                          ? $t('install.requirements.passed')
                          : $t('install.requirements.failed')
                      "
                    />
                  </template>
                </template>
              </Table>
            </div>
          </div>

          <div class="install-page__actions">
            <Space>
              <Button :loading="loading" @click="loadRequirements">
                {{ $t('install.requirements.reload') }}
              </Button>
              <Button
                type="primary"
                :disabled="!requirements?.passed"
                :loading="loading"
                @click="onRequirementsNext"
              >
                {{ $t('install.requirements.next') }}
              </Button>
            </Space>
          </div>
        </template>

        <template v-else-if="current === 1">
          <Form layout="vertical">
            <FormItem :label="$t('install.database.connection')">
              <Select
                :options="connectionOptions"
                :value="dbForm.connection"
                @update:value="
                  (value) =>
                    onConnectionChange(
                      value as InstallApi.DatabaseParams['connection'],
                    )
                "
              />
            </FormItem>

            <template v-if="isSqlite">
              <FormItem :label="$t('install.database.sqlitePath')">
                <Input v-model:value="dbForm.database" />
              </FormItem>
            </template>
            <template v-else>
              <FormItem :label="$t('install.database.host')">
                <Input v-model:value="dbForm.host" />
              </FormItem>
              <FormItem :label="$t('install.database.port')">
                <Input v-model:value="dbForm.port" />
              </FormItem>
              <FormItem :label="$t('install.database.name')">
                <Input v-model:value="dbForm.database" />
              </FormItem>
              <FormItem :label="$t('install.database.username')">
                <Input v-model:value="dbForm.username" />
              </FormItem>
              <FormItem :label="$t('install.database.password')">
                <InputPassword v-model:value="dbForm.password" />
              </FormItem>
            </template>

            <FormItem :label="$t('install.database.prefix')">
              <Input v-model:value="dbForm.prefix" />
            </FormItem>
            <FormItem :label="$t('install.database.appUrl')">
              <Input v-model:value="dbForm.app_url" />
            </FormItem>
            <FormItem :label="$t('install.database.cors')">
              <Input v-model:value="dbForm.cors_allowed_origins" />
            </FormItem>
          </Form>

          <div class="install-page__actions">
            <Space>
              <Button @click="current = 0">
                {{ $t('install.database.back') }}
              </Button>
              <Button type="primary" :loading="loading" @click="onSaveDatabase">
                {{ $t('install.database.testAndSave') }}
              </Button>
            </Space>
          </div>
        </template>

        <template v-else-if="current === 2">
          <Alert
            v-if="loading"
            class="mb-4"
            type="info"
            show-icon
            :message="$t('install.install.running')"
          />

          <Form layout="vertical">
            <FormItem :label="$t('install.install.adminUsername')">
              <Input v-model:value="adminForm.username" />
            </FormItem>
            <FormItem :label="$t('install.install.adminEmail')">
              <Input v-model:value="adminForm.email" />
            </FormItem>
            <FormItem :label="$t('install.install.adminPassword')">
              <InputPassword v-model:value="adminForm.password" />
            </FormItem>
            <FormItem :label="$t('install.install.adminPasswordConfirm')">
              <InputPassword v-model:value="adminForm.passwordConfirm" />
            </FormItem>
          </Form>

          <div class="install-page__actions">
            <Space>
              <Button :disabled="loading" @click="current = 1">
                {{ $t('install.install.back') }}
              </Button>
              <Button type="primary" :loading="loading" @click="onRunInstall">
                {{ $t('install.install.run') }}
              </Button>
            </Space>
          </div>
        </template>

        <template v-else>
          <Result
            status="success"
            :title="$t('install.done.success')"
            :sub-title="$t('install.done.desc')"
          >
            <template #extra>
              <div class="mb-4 text-muted-foreground">
                {{ $t('install.done.username') }}：{{ adminUsername }}
              </div>
              <Button type="primary" @click="goLogin">
                {{ $t('install.done.goLogin') }}
              </Button>
            </template>
          </Result>
        </template>
      </Card>
    </div>
  </div>
</template>

<style scoped>
.install-page {
  min-height: 100vh;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 2rem 1rem 3rem;
  background:
    radial-gradient(
      1200px 600px at 10% -10%,
      hsl(var(--primary) / 12%),
      transparent 60%
    ),
    hsl(var(--background-deep, var(--background)));
}

.install-page__panel {
  width: min(880px, 100%);
}

.install-page__header {
  margin-bottom: 1.5rem;
  text-align: center;
}

.install-page__brand {
  margin-bottom: 0.5rem;
  font-size: calc(var(--font-size-base) * 20 / 16);
  font-weight: 700;
  letter-spacing: 0.02em;
}

.install-page__title {
  margin: 0;
  font-size: calc(var(--font-size-base) * 24 / 16);
  font-weight: 600;
}

.install-page__subtitle {
  margin: 0.5rem 0 0;
  color: hsl(var(--muted-foreground));
  font-size: var(--admin-font-control, calc(var(--font-size-base) * 13 / 16));
}

.install-page__steps {
  margin-bottom: 1rem;
}

.install-page__card {
  background: hsl(var(--card, var(--background)));
  box-shadow: 0 1px 2px hsl(var(--foreground) / 6%);
}

.install-page__section-title {
  font-size: calc(var(--font-size-base) * 15 / 16);
  font-weight: 600;
  line-height: 1.4;
}

.install-page__card :deep(.ant-table) {
  width: 100%;
  font-size: var(--admin-font-control, calc(var(--font-size-base) * 13 / 16));
}

.install-page__card :deep(.ant-table table) {
  width: 100%;
  table-layout: fixed;
}

.install-page__table--3col :deep(.ant-table colgroup col:nth-child(1)),
.install-page__table--3col :deep(.ant-table-thead > tr > th:nth-child(1)),
.install-page__table--3col :deep(.ant-table-tbody > tr > td:nth-child(1)) {
  width: 40% !important;
}

.install-page__table--3col :deep(.ant-table colgroup col:nth-child(2)),
.install-page__table--3col :deep(.ant-table-thead > tr > th:nth-child(2)),
.install-page__table--3col :deep(.ant-table-tbody > tr > td:nth-child(2)) {
  width: 40% !important;
}

.install-page__table--3col :deep(.ant-table colgroup col:nth-child(3)),
.install-page__table--3col :deep(.ant-table-thead > tr > th:nth-child(3)),
.install-page__table--3col :deep(.ant-table-tbody > tr > td:nth-child(3)),
.install-page__table--2col :deep(.ant-table colgroup col:nth-child(2)),
.install-page__table--2col :deep(.ant-table-thead > tr > th:nth-child(2)),
.install-page__table--2col :deep(.ant-table-tbody > tr > td:nth-child(2)) {
  width: 20% !important;
}

.install-page__table--2col :deep(.ant-table colgroup col:nth-child(1)),
.install-page__table--2col :deep(.ant-table-thead > tr > th:nth-child(1)),
.install-page__table--2col :deep(.ant-table-tbody > tr > td:nth-child(1)) {
  width: 80% !important;
}

.install-page__card :deep(.ant-table-thead > tr > th),
.install-page__card :deep(.ant-table-tbody > tr > td),
.install-page__card :deep(.ant-table .ant-tag) {
  font-size: inherit;
}

.install-page__actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 1.5rem;
}
</style>
