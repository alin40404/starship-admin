import type { LaravelPaginate } from '#/features/system/api/types';

import { requestClient } from '#/api/request';

export interface GameRow {
  id: number;
  name: string;
  code: string | null;
  cover: string | null;
  platform: string | null;
  publisher: string | null;
  status: number;
  sort: number;
  description: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface GameSavePayload {
  name: string;
  code?: string | null;
  cover?: string | null;
  platform?: string | null;
  publisher?: string | null;
  status?: number;
  sort?: number;
  description?: string | null;
}

export interface GameProductRow {
  id: number;
  game_id: number;
  name: string;
  sku_code: string | null;
  product_kind: string;
  price: number;
  stock: number;
  cover: string | null;
  description: string | null;
  extra: Record<string, unknown> | null;
  status: number;
  sort: number;
  game?: { id: number; name: string } | null;
  created_at?: string;
  updated_at?: string;
}

export interface GameProductSavePayload {
  game_id: number;
  name: string;
  sku_code?: string | null;
  product_kind?: string;
  price?: number;
  stock?: number;
  cover?: string | null;
  description?: string | null;
  extra?: Record<string, unknown> | null;
  status?: number;
  sort?: number;
}

export interface GameOrderRow {
  id: number;
  order_no: string;
  game_id: number;
  product_id: number;
  product_name: string;
  amount: number;
  quantity: number;
  status: number;
  player_account: string | null;
  server_name: string | null;
  role_id: string | null;
  fulfill_status: string | null;
  created_at?: string;
  game?: { id: number; name: string } | null;
}

export function fetchGamePage(params: Record<string, unknown>) {
  return requestClient.get<LaravelPaginate<GameRow>>('/game-shop/games', {
    params,
  });
}

export function fetchGame(id: number) {
  return requestClient.get<GameRow>(`/game-shop/games/${id}`);
}

export function fetchGameOptions() {
  return requestClient.get<{ label: string; value: number }[]>(
    '/game-shop/game-options',
  );
}

export function createGame(data: GameSavePayload) {
  return requestClient.post<GameRow>('/game-shop/games', data);
}

export function updateGame(id: number, data: Partial<GameSavePayload>) {
  return requestClient.put<GameRow>(`/game-shop/games/${id}`, data);
}

export function updateGameStatus(id: number, status: number) {
  return requestClient.put(`/game-shop/games/${id}/status`, { status });
}

export function deleteGame(id: number) {
  return requestClient.delete(`/game-shop/games/${id}`);
}

export function fetchGameProductPage(params: Record<string, unknown>) {
  return requestClient.get<LaravelPaginate<GameProductRow>>(
    '/game-shop/products',
    { params },
  );
}

export function fetchGameProduct(id: number) {
  return requestClient.get<GameProductRow>(`/game-shop/products/${id}`);
}

export function createGameProduct(data: GameProductSavePayload) {
  return requestClient.post<GameProductRow>('/game-shop/products', data);
}

export function updateGameProduct(
  id: number,
  data: Partial<GameProductSavePayload>,
) {
  return requestClient.put<GameProductRow>(`/game-shop/products/${id}`, data);
}

export function updateGameProductStatus(id: number, status: number) {
  return requestClient.put(`/game-shop/products/${id}/status`, { status });
}

export function deleteGameProduct(id: number) {
  return requestClient.delete(`/game-shop/products/${id}`);
}

export function fetchGameOrderPage(params: Record<string, unknown>) {
  return requestClient.get<LaravelPaginate<GameOrderRow>>(
    '/game-shop/orders',
    { params },
  );
}
