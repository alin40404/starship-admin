import { baseRequestClient, requestClient } from '#/api/request';

export namespace AuthApi {
  /** 登录接口参数 */
  export interface LoginParams {
    password?: string;
    username?: string;
  }

  /**
   * 与 Vben 登录页约定一致：返回 accessToken 供 store 写入。
   * 实际后端为 Starship：`{ token, admin, permissions, menus }`。
   */
  export interface LoginResult {
    accessToken: string;
  }

  export interface RefreshTokenResult {
    data: string;
    status: number;
  }
}

/** Laravel 登录响应 data 段 */
interface LaravelLoginData {
  token: string;
  token_type?: string;
}

/**
 * 登录（Starship：`POST /admin-api/auth/login`）
 * 仅提交账号密码，忽略表单中的滑块等前端校验字段。
 */
export async function loginApi(data: AuthApi.LoginParams) {
  const result = await requestClient.post<LaravelLoginData>('/auth/login', {
    username: data.username,
    password: data.password,
  });
  return { accessToken: result.token } as AuthApi.LoginResult;
}

/**
 * 刷新 accessToken（当前后端未实现，保留以兼容 Vben 请求层）
 */
export async function refreshTokenApi() {
  return baseRequestClient.post<AuthApi.RefreshTokenResult>('/auth/refresh', {
    withCredentials: true,
  });
}

/**
 * 退出登录（需携带 Bearer，与 requestClient 一致）
 */
export async function logoutApi() {
  return baseRequestClient.post<null>('/auth/logout');
}

/**
 * 权限码列表（Starship：`GET /admin-api/auth/permissions` → `{ permissions: string[] }`）
 */
export async function getAccessCodesApi() {
  const data = await requestClient.get<{ permissions: string[] }>(
    '/auth/permissions',
  );
  return data.permissions;
}
