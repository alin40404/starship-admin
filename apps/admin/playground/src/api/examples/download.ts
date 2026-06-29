import type { RequestResponse } from '@vben/request';

import { VBEN_LOGO_URL } from '@vben/constants';

import { requestClient } from '../request';

/**
 * 下载文件，获取Blob
 * @returns Blob
 */
async function downloadFile1() {
  return requestClient.download<Blob>(VBEN_LOGO_URL);
}

/**
 * 下载文件，获取完整的Response
 * @returns RequestResponse<Blob>
 */
async function downloadFile2() {
  return requestClient.download<RequestResponse<Blob>>(VBEN_LOGO_URL, {
    responseReturn: 'raw',
  });
}

export { downloadFile1, downloadFile2 };
