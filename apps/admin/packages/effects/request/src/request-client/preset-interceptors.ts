import type { RequestClient } from './request-client';
import type { MakeErrorMessageFn, ResponseInterceptorConfig } from './types';

import { $t } from '@vben/locales';
import { isFunction } from '@vben/utils';

import axios from 'axios';

function getResponsePayload(error: any): Record<string, any> {
  const responseData = error?.response?.data;
  if (responseData && typeof responseData === 'object') {
    return responseData;
  }

  if (error?.data && typeof error.data === 'object') {
    return error.data;
  }

  if (
    error &&
    typeof error === 'object' &&
    ('message' in error || 'code' in error)
  ) {
    return error;
  }

  return {};
}

function getHttpStatus(error: any): number | undefined {
  const status = error?.response?.status ?? error?.status;
  if (status === undefined || status === null || status === '') {
    return undefined;
  }

  const numericStatus = Number(status);
  return Number.isFinite(numericStatus) ? numericStatus : undefined;
}

function getResponseErrorMessage(error: any): string {
  const payload = getResponsePayload(error);
  const bodyMessage = payload?.error ?? payload?.message;
  return typeof bodyMessage === 'string' ? bodyMessage.trim() : '';
}

export const defaultResponseInterceptor = ({
  codeField = 'code',
  dataField = 'data',
  successCode = 0,
}: {
  /** 响应数据中代表访问结果的字段名 */
  codeField: string;
  /** 响应数据中装载实际数据的字段名，或者提供一个函数从响应数据中解析需要返回的数据 */
  dataField: ((response: any) => any) | string;
  /** 当codeField所指定的字段值与successCode相同时，代表接口访问成功。如果提供一个函数，则返回true代表接口访问成功 */
  successCode: ((code: any) => boolean) | number | string;
}): ResponseInterceptorConfig => {
  return {
    fulfilled: (response) => {
      const { config, data: responseData, status } = response;

      if (config.responseReturn === 'raw') {
        return response;
      }

      if (status >= 200 && status < 400) {
        if (config.responseReturn === 'body') {
          return responseData;
        } else if (
          isFunction(successCode)
            ? successCode(responseData[codeField])
            : responseData[codeField] === successCode
        ) {
          return isFunction(dataField)
            ? dataField(responseData)
            : responseData[dataField];
        }

        const businessError = Object.assign(
          new Error(
            typeof responseData?.message === 'string'
              ? responseData.message
              : 'Request failed',
          ),
          {
            config,
            data: responseData,
            response,
            status,
          },
        );
        throw businessError;
      }
      const transportError = Object.assign(
        new Error(`Request failed with status code ${status}`),
        {
          config,
          data: responseData,
          response,
          status,
        },
      );
      throw transportError;
    },
  };
};

export const authenticateResponseInterceptor = ({
  client,
  doReAuthenticate,
  doRefreshToken,
  enableRefreshToken,
  formatToken,
}: {
  client: RequestClient;
  doReAuthenticate: () => Promise<void>;
  doRefreshToken: () => Promise<string>;
  enableRefreshToken: boolean;
  formatToken: (token: string) => null | string;
}): ResponseInterceptorConfig => {
  return {
    rejected: async (error) => {
      const { config } = error;
      const status = getHttpStatus(error);
      // 401 或业务层返回的会话失效，统一走重新登录
      if (status !== 401) {
        throw error;
      }
      // 判断是否启用了 refreshToken 功能
      // 如果没有启用或者已经是重试请求了，直接跳转到重新登录
      if (!enableRefreshToken || config.__isRetryRequest) {
        error.__skipErrorMessage = true;
        await doReAuthenticate();
        throw error;
      }
      // 如果正在刷新 token，则将请求加入队列，等待刷新完成
      if (client.isRefreshing) {
        return new Promise((resolve) => {
          client.refreshTokenQueue.push((newToken: string) => {
            config.headers.Authorization = formatToken(newToken);
            resolve(client.request(config.url, { ...config }));
          });
        });
      }

      // 标记开始刷新 token
      client.isRefreshing = true;
      // 标记当前请求为重试请求，避免无限循环
      config.__isRetryRequest = true;

      try {
        const newToken = await doRefreshToken();

        // 处理队列中的请求
        client.refreshTokenQueue.forEach((callback) => callback(newToken));
        // 清空队列
        client.refreshTokenQueue = [];

        return client.request(error.config.url, { ...error.config });
      } catch (refreshError) {
        // 如果刷新 token 失败，处理错误（如强制登出或跳转登录页面）
        client.refreshTokenQueue.forEach((callback) => callback(''));
        client.refreshTokenQueue = [];
        console.error('Refresh token failed, please login again.');
        (refreshError as any).__skipErrorMessage = true;
        await doReAuthenticate();

        throw refreshError;
      } finally {
        client.isRefreshing = false;
      }
    },
  };
};

export const errorMessageResponseInterceptor = (
  makeErrorMessage?: MakeErrorMessageFn,
): ResponseInterceptorConfig => {
  return {
    rejected: (error: any) => {
      if (axios.isCancel(error) || error?.__skipErrorMessage) {
        return Promise.reject(error);
      }

      const err: string = error?.toString?.() ?? '';
      let errMsg = '';
      if (err?.includes('Network Error')) {
        errMsg = $t('ui.fallback.http.networkError');
      } else if (error?.message?.includes?.('timeout')) {
        errMsg = $t('ui.fallback.http.requestTimeout');
      }
      if (errMsg) {
        makeErrorMessage?.(errMsg, error);
        return Promise.reject(error);
      }

      const bodyMessage = getResponseErrorMessage(error);
      if (bodyMessage) {
        makeErrorMessage?.(bodyMessage, error);
        return Promise.reject(error);
      }

      let errorMessage: string;
      const status = getHttpStatus(error);

      switch (status) {
        case 400: {
          errorMessage = $t('ui.fallback.http.badRequest');
          break;
        }
        case 401: {
          errorMessage = $t('ui.fallback.http.unauthorized');
          break;
        }
        case 403: {
          errorMessage = $t('ui.fallback.http.forbidden');
          break;
        }
        case 404: {
          errorMessage = $t('ui.fallback.http.notFound');
          break;
        }
        case 408: {
          errorMessage = $t('ui.fallback.http.requestTimeout');
          break;
        }
        default: {
          errorMessage = $t('ui.fallback.http.internalServerError');
        }
      }
      makeErrorMessage?.(errorMessage, error);
      return Promise.reject(error);
    },
  };
};
