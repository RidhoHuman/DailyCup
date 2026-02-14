/**
 * Kurir API helper
 * Uses the same base api-client but reads token from kurir store
 */

import type { KurirUser } from '@/lib/stores/kurir-store';
import type { Order } from '@/types/delivery';

const API_BASE = '/api/kurir';

function getKurirToken(): string | null {
  if (typeof window === 'undefined') return null;
  try {
    const raw = localStorage.getItem('dailycup-kurir');
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    return parsed?.state?.token || null;
  } catch { return null; }
}

async function kurirFetch<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const token = getKurirToken();
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...(options.headers as Record<string, string> || {}),
  };
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const url = `${API_BASE}/${endpoint.replace(/^\//, '')}`;
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 30000);

  try {
    const res = await fetch(url, {
      ...options,
      headers,
      signal: controller.signal,
      credentials: 'include',
    });
    clearTimeout(timeout);
    const data = await res.json();
    if (!res.ok) throw new Error(data?.error || data?.message || `HTTP ${res.status}`);
    return data as T;
  } catch (err) {
    clearTimeout(timeout);
    throw err;
  }
}

export const kurirApi = {
  // Auth
  login: (phone: string, password: string) =>
    kurirFetch<{ success: boolean; message: string; user: KurirUser; token: string }>('login.php', {
      method: 'POST', body: JSON.stringify({ phone, password }),
    }),

  register: (data: { name: string; phone: string; password: string; email?: string; vehicle_type?: string; vehicle_number?: string; invitation_code?: string }) =>
    kurirFetch<{ success: boolean; message: string; user: KurirUser; token: string }>('register.php', {
      method: 'POST', body: JSON.stringify(data),
    }),

  // Profile
  getProfile: () => kurirFetch<{ success: boolean; data: { stats?: { activeOrders?: number; todayDeliveries?: number; todayEarnings?: number }; user?: KurirUser } }>('me.php'),
  updateProfile: (data: Record<string, string>) =>
    kurirFetch<{ success: boolean; message: string }>('me.php', { method: 'PUT', body: JSON.stringify(data), }),
  updateStatus: (status: 'available' | 'offline') =>
    kurirFetch<{ success: boolean; message: string; status: string }>('me.php?action=status', {
      method: 'POST', body: JSON.stringify({ status }),
    }),

  // Orders
  getOrders: (status = 'active', page = 1, limit = 10) =>
    kurirFetch<{ success: boolean; data: Order[]; pagination: { page: number; per_page: number; total: number } }>(`orders.php?status=${status}&page=${page}&limit=${limit}`),
  getOrderDetail: (orderId: string) =>
    kurirFetch<{ success: boolean; data: Order }>(`order_detail.php?order_id=${orderId}`),
  updateOrderStatus: (orderId: string | number, status: string) =>
    kurirFetch<{ success: boolean; message: string; data?: Partial<Order> }>('update_status.php', {
      method: 'POST', body: JSON.stringify({ order_id: orderId, status }),
    }),

  // Available Orders (for kurir to claim)
  getAvailableOrders: (page = 1, limit = 10) =>
    kurirFetch<{ 
      success: boolean; 
      data: Order[]; 
      kurir_status: string;
      active_orders_count: number;
      pagination: { page: number; per_page: number; total: number };
    }>(`available_orders.php?page=${page}&limit=${limit}`),
  
  // Claim an order
  claimOrder: (orderNumber: string) =>
    kurirFetch<{ 
      success: boolean; 
      message: string; 
      order?: Order;
      active_orders_count?: number;
      error?: string;
    }>('claim_order.php', {
      method: 'POST', body: JSON.stringify({ order_id: orderNumber }),
    }),

  // Delivery Photo Upload
  uploadDeliveryPhoto: async (orderId: string | number, type: 'departure' | 'arrival', photo: File, coords?: { latitude: number; longitude: number }) => {
    const token = getKurirToken();
    const formData = new FormData();
    formData.append('photo', photo);
    formData.append('order_id', String(orderId));
    formData.append('type', type);
    if (coords) {
      formData.append('latitude', String(coords.latitude));
      formData.append('longitude', String(coords.longitude));
    }

    const headers: Record<string, string> = {};
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const res = await fetch(`${API_BASE}/upload_delivery_photo.php`, {
      method: 'POST',
      headers,
      body: formData,
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data?.error || data?.message || `HTTP ${res.status}`);
    return data as { success: boolean; message: string; data: { orderNumber: string; photoUrl: string; previousStatus: string; newStatus: string; actualDeliveryTime?: number } };
  },

  // Location
  updateLocation: (data: { latitude: number; longitude: number; accuracy?: number; speed?: number }) =>
    kurirFetch<{ success: boolean; message: string }>('location.php', {
      method: 'POST', body: JSON.stringify(data),
    }),
};
