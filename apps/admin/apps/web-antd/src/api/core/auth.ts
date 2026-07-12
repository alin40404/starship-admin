import { baseRequestClient, requestClient } from '#/api/request';

export namespace AuthApi {
  /** 登录接口参数 */
  export interface LoginParams {
    password?: string;
    username?: string;
    /** 滑块验证码通过后为 true，后端在开启 admin.login.captcha 时校验 */
    captcha?: boolean;
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

  export interface ForgotPasswordParams {
    email: string;
  }

  export interface ResetPasswordParams {
    email: string;
    password: string;
    token: string;
  }

  export interface VerifyResetPasswordParams {
    email: string;
    token: string;
  }
}

/** Laravel 登录响应 data 段 */
interface LaravelLoginData {
  token: string;
  token_type?: string;
}

/**
 * 登录（Starship：`POST /admin-api/auth/login`）
 */
export async function loginApi(data: AuthApi.LoginParams) {
  const result = await requestClient.post<LaravelLoginData>('/auth/login', {
    username: data.username,
    password: data.password,
    ...(data.captcha ? { captcha: true } : {}),
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

/**
 * 忘记密码：发送重置邮件
 */
export async function forgotPasswordApi(data: AuthApi.ForgotPasswordParams) {
  return baseRequestClient.post<null>('/auth/forgot-password', data);
}

/**
 * 校验重置密码链接是否有效
 */
export async function verifyResetPasswordTokenApi(
  params: AuthApi.VerifyResetPasswordParams,
) {
  return baseRequestClient.get<{ valid: boolean }>('/auth/reset-password/verify', {
    params,
  });
}

/**
 * 重置密码（邮件链接携带 token）
 */
export async function resetPasswordApi(data: AuthApi.ResetPasswordParams) {
  return baseRequestClient.post<null>('/auth/reset-password', data);
}

/**
 * 发送短信验证码（手机号登录）
 */
export async function sendSmsCodeApi(mobile: string) {
  return baseRequestClient.post<null>('/auth/sms-code', { mobile });
}

/**
 * 短信验证码登录
 */
export async function smsLoginApi(data: { mobile: string; code: string }) {
  const result = await requestClient.post<LaravelLoginData>('/auth/sms-login', data);
  return { accessToken: result.token } as AuthApi.LoginResult;
}
