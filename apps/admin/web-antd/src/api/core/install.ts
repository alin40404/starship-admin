import { baseRequestClient } from '#/api/request';

export namespace InstallApi {
  export interface Status {
    installed: boolean;
    lock_path: string;
    /** false 表示 PHP 内置开发服务器，禁止 Web 安装向导 */
    web_install_allowed: boolean;
    runtime: string;
  }

  export interface RequirementItem {
    name?: string;
    path?: string;
    required?: boolean;
    loaded?: boolean;
    writable?: boolean;
    passed: boolean;
  }

  export interface Requirements {
    passed: boolean;
    php: {
      label: string;
      required: string;
      current: string;
      passed: boolean;
    };
    extensions: Array<{
      name: string;
      required: boolean;
      loaded: boolean;
      passed: boolean;
    }>;
    directories: Array<{
      path: string;
      writable: boolean;
      passed: boolean;
    }>;
  }

  export interface DatabaseParams {
    connection: 'mysql' | 'pgsql' | 'sqlite';
    host?: string;
    port?: number | string;
    database?: string;
    username?: string;
    password?: string;
    prefix?: string;
    app_url?: string;
    cors_allowed_origins?: string;
  }

  export type InstallStep = 'prepare' | 'migrate' | 'seed' | 'finalize';

  export interface RunParams {
    step: InstallStep;
    site_name?: string;
    username?: string;
    password: string;
    email?: string;
  }

  export interface RunResult {
    step: InstallStep;
    percent: number;
    next_step: InstallStep | null;
    installed: boolean;
    admin_username: string;
  }
}

export function fetchInstallStatus() {
  return baseRequestClient.get<InstallApi.Status>('/install/status');
}

export function fetchInstallRequirements() {
  return baseRequestClient.get<InstallApi.Requirements>('/install/requirements');
}

export function saveInstallDatabase(data: InstallApi.DatabaseParams) {
  return baseRequestClient.post<{ connection: string; database: string }>(
    '/install/database',
    data,
  );
}

export function runInstall(data: InstallApi.RunParams) {
  return baseRequestClient.post<InstallApi.RunResult>('/install/run', data);
}
