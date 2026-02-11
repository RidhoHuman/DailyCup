/**
 * Centralized API Client for DailyCup
 * 
 * This module provides a standardized way to communicate with the backend.
 * Features:
 * - Automatic base URL from environment variables
 * - Automatic token injection for authenticated requests
 * - Centralized error handling
 * - Request/Response interceptors
 */

// Use Next.js rewrites to proxy API calls
// Always use /api prefix for client-side calls (rewrites handle routing)
const API_BASE_URL = typeof window !== 'undefined' 
  ? '/api' // Client-side: always use Next.js rewrites
  : (process.env.NEXT_PUBLIC_API_URL || 'https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api');

// Custom error class for API errors
export class APIError extends Error {
  status: number;
  data: unknown;

  constructor(message: string, status: number, data?: unknown) {
    super(message);
    this.name = 'APIError';
    this.status = status;
    this.data = data;
  }
}

// Get auth token from localStorage (client-side only)
function getAuthToken(): string | null {
  if (typeof window === 'undefined') return null;
  
  try {
    const authData = localStorage.getItem('dailycup-auth');
    if (authData) {
      const parsed = JSON.parse(authData);
      // Zustand persist stores data in { state: { ... } } structure
      return parsed?.state?.token || parsed?.token || null;
    }
  } catch (error) {
    console.error('Error reading auth token:', error);
    return null;
  }
  return null;
}

// Request configuration interface
interface RequestConfig {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
  headers?: Record<string, string>;
  // Body can be a plain object/array or FormData for file uploads
  body?: Record<string, unknown> | unknown[] | FormData;
  requiresAuth?: boolean;
  timeout?: number;
}

/**
 * Main API request function
 */
async function apiRequest<T = any>(
  endpoint: string,
  config: RequestConfig = {}
): Promise<T> {
  const {
    method = 'GET',
    headers = {},
    body,
    requiresAuth = false,
    timeout = 30000,
  } = config;

  // Build URL
  const url = endpoint.startsWith('http') 
    ? endpoint 
    : `${API_BASE_URL}/${endpoint.replace(/^\//, '')}`;

  // Build headers
  // Do not set Content-Type by default because some requests (e.g. FormData) need the
  // browser to set the correct multipart boundary automatically.
  const requestHeaders: Record<string, string> = {
    'Accept': 'application/json',
    'ngrok-skip-browser-warning': '69420', // Bypass ngrok browser warning
    ...headers,
  };

  // Add auth token if required
  if (requiresAuth) {
    const token = getAuthToken();
    if (token) {
      requestHeaders['Authorization'] = `Bearer ${token}`;
    }
  }

  // Build fetch options
  const fetchOptions: RequestInit = {
    method,
    headers: requestHeaders,
    credentials: 'include', // Include cookies for session handling
  };

  // Add body for non-GET requests
  if (body && method !== 'GET') {
    if (typeof FormData !== 'undefined' && body instanceof FormData) {
      // Let the browser set the Content-Type with proper boundary
      fetchOptions.body = body as FormData;
    } else {
      requestHeaders['Content-Type'] = 'application/json';
      fetchOptions.body = JSON.stringify(body);
    }
  }

  // Create abort controller for timeout
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeout);
  fetchOptions.signal = controller.signal;

  try {
    const response = await fetch(url, fetchOptions);
    clearTimeout(timeoutId);

    // Parse response
    let data: unknown;
    const contentType = response.headers.get('content-type');
    if (contentType?.includes('application/json')) {
      data = await response.json();
    } else {
      data = await response.text();
    }

    // Handle HTTP errors
    if (!response.ok) {
      const errorData = data as Record<string, unknown>;
      throw new APIError(
        (errorData?.message as string) || (errorData?.error as string) || `HTTP Error ${response.status}`,
        response.status,
        data
      );
    }

    return data as T;
  } catch (error: unknown) {
    clearTimeout(timeoutId);

    const err = error as Error & { name?: string };
    
    // Handle abort (timeout)
    if (err.name === 'AbortError') {
      throw new APIError('Request timeout', 408);
    }

    // Handle network errors
    if (err.name === 'TypeError' && err.message?.includes('fetch')) {
      throw new APIError('Network error - Backend may be offline', 0);
    }

    // Re-throw API errors
    if (error instanceof APIError) {
      throw error;
    }

    // Generic error
    throw new APIError(err.message || 'Unknown error', 500);
  }
}

// ===========================================
// Convenience Methods
// ===========================================

export const api = {
  get: <T = any>(endpoint: string, config?: Omit<RequestConfig, 'method' | 'body'>) =>
    apiRequest<T>(endpoint, { ...config, method: 'GET' }),

  post: <T = any>(endpoint: string, body?: any, config?: Omit<RequestConfig, 'method'>) =>
    apiRequest<T>(endpoint, { ...config, method: 'POST', body }),

  put: <T = any>(endpoint: string, body?: any, config?: Omit<RequestConfig, 'method'>) =>
    apiRequest<T>(endpoint, { ...config, method: 'PUT', body }),

  delete: <T = any>(endpoint: string, config?: Omit<RequestConfig, 'method'>) =>
    apiRequest<T>(endpoint, { ...config, method: 'DELETE' }),

  patch: <T = any>(endpoint: string, body?: any, config?: Omit<RequestConfig, 'method'>) =>
    apiRequest<T>(endpoint, { ...config, method: 'PATCH', body }),
};

// ===========================================
// API Endpoints (Type-safe)
// ===========================================

export const endpoints = {
  // Products
  products: {
    list: () => 'products.php',
    single: (id: number) => `products.php?id=${id}`,
  },
  
  // Categories
  categories: {
    list: () => 'categories.php',
  },

  // Auth
  auth: {
    login: () => 'auth/login.php',
    register: () => 'auth/register.php',
    logout: () => 'auth/logout.php',
    profile: () => 'auth/profile.php',
  },

  // Orders
  orders: {
    create: () => 'create_order.php',
    get: (id: string) => `get_order.php?id=${id}`,
    list: () => 'orders.php',
    pay: () => 'pay_order.php',
  },

  // Admin
  admin: {
    dashboard: () => 'admin/dashboard.php',
    products: () => 'admin/products.php',
    orders: () => 'admin/orders.php',
    users: () => 'admin/users.php',
  },
};

export default api;
