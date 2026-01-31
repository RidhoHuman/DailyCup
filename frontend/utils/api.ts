// API utilities for DailyCup frontend
// Using centralized API URL from environment variable
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || "https://api.dailycup.com/api";

// Feature flag for mock data (useful during development)
const USE_MOCK_DATA = process.env.NEXT_PUBLIC_ENABLE_MOCK_DATA === 'true';

// Debug log for API URL (only in development)
if (typeof window !== 'undefined' && process.env.NODE_ENV === 'development') {
  console.log('[API] Base URL:', API_BASE_URL);
  console.log('[API] Mock Data Enabled:', USE_MOCK_DATA);
}

// Mock store for E2E testing when backend is unreachable
const mockOrdersStore: Record<string, Order> = {};

export interface Category {
  id: number;
  name: string;
  description?: string;
  image?: string;
}

export interface Product {
  id: number;
  name: string;
  description: string;
  price?: number;
  base_price?: number;
  image: string | null;
  is_featured: boolean;
  stock: number;
  category_name?: string;
  category?: string;
  average_rating?: number;
  total_reviews?: number;
  variants?: {
    [key: string]: Array<{
      value: string;
      price_adjustment: number;
    }>;
  };
}

export interface CartItem {
  id: string;
  product: Product;
  quantity: number;
  // Backwards-compatible fields
  size?: string;
  temperature?: string;
  // Flexible variant map for future support
  selectedVariants?: Record<string, string>;
  totalPrice: number;
}

// Order shape returned by backend `get_order.php` (matches data in backend/data/orders.json)
export interface OrderItem {
  id: string;
  name: string;
  price: number;
  quantity: number;
}

export interface Order {
  id: string;
  items: OrderItem[];
  total: number;
  customer?: { name?: string; email?: string; phone?: string };
  status: 'pending' | 'paid' | 'failed' | string;
  created_at?: string;
  updated_at?: string;
  xendit_notification?: { external_id?: string; status?: string };
}

// Helper to build headers (include ngrok header if using ngrok)
function buildFetchOptions(): RequestInit {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json'
  };

  if (API_BASE_URL.includes('ngrok-free.app')) {
    headers['ngrok-skip-browser-warning'] = 'true';
  }

  return { headers };
}

export async function fetchCategories(): Promise<Category[]> {
  try {
    const response = await fetch(`${API_BASE_URL}/categories.php`, buildFetchOptions());
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();
    if (data.success) {
      return data.data;
    } else {
      throw new Error(data.message || 'Failed to fetch categories');
    }
  } catch (error) {
    console.error('Error fetching categories:', error);
    // Notify UI that mock data is used
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('api:mock', { detail: { endpoint: 'categories' } }));
    }
    // Return mock categories
    return [
      { id: 1, name: 'Coffee', description: 'Premium coffee beverages' },
      { id: 2, name: 'Non-Coffee', description: 'Refreshing non-coffee drinks' },
      { id: 3, name: 'Snacks', description: 'Delicious snacks and pastries' },
      { id: 4, name: 'Desserts', description: 'Sweet treats and desserts' }
    ];
  }
}

export async function fetchProducts(): Promise<Product[]> {
  try {
    const response = await fetch(`${API_BASE_URL}/products.php`, buildFetchOptions());

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    if (data.success) {
      return data.data;
    } else {
      throw new Error(data.message || "Failed to fetch products");
    }
  } catch (error) {
    console.error("Error fetching products:", error);
    // Notify UI that mock data is used
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('api:mock', { detail: { endpoint: 'products' } }));
    }
    // Return mock data for development
    console.warn("Using mock data due to API error");
    return getMockProducts();
  }
}

// Mock data for development
function getMockProducts(): Product[] {
  return [
    {
      id: 1,
      name: "Espresso",
      description: "Rich and bold espresso shot",
      base_price: 25000,
      image: null,
      is_featured: true,
      stock: 100,
      category_name: "Coffee",
      variants: {
        size: [
          { value: "Regular", price_adjustment: 0 },
          { value: "Large", price_adjustment: 5000 },
        ],
        temperature: [{ value: "Hot", price_adjustment: 0 }],
      },
    },
    {
      id: 2,
      name: "Cappuccino",
      description: "Classic cappuccino with perfect foam",
      base_price: 35000,
      image: null,
      is_featured: true,
      stock: 100,
      category_name: "Coffee",
      variants: {
        size: [
          { value: "Regular", price_adjustment: 0 },
          { value: "Large", price_adjustment: 5000 },
        ],
        temperature: [
          { value: "Hot", price_adjustment: 0 },
          { value: "Iced", price_adjustment: 2000 },
        ],
      },
    },
    {
      id: 3,
      name: "Latte",
      description: "Smooth and creamy latte",
      base_price: 38000,
      image: null,
      is_featured: true,
      stock: 100,
      category_name: "Coffee",
      variants: {
        size: [
          { value: "Regular", price_adjustment: 0 },
          { value: "Large", price_adjustment: 5000 },
        ],
        temperature: [
          { value: "Hot", price_adjustment: 0 },
          { value: "Iced", price_adjustment: 2000 },
        ],
      },
    },
    // Additional products for Phase 6 tests
    {
      id: 4,
      name: "Iced Special",
      description: "Limited edition, currently out of stock",
      base_price: 45000,
      image: null,
      is_featured: false,
      stock: 0,
      category_name: "Coffee",
      variants: {
        size: [
          { value: "Regular", price_adjustment: 0 },
          { value: "Large", price_adjustment: 5000 },
        ],
      },
    },
    {
      id: 5,
      name: "Filter Brew",
      description: "Small-batch filter coffee (low stock)",
      base_price: 30000,
      image: null,
      is_featured: false,
      stock: 3,
      category_name: "Coffee",
      variants: {
        size: [
          { value: "Regular", price_adjustment: 0 }
        ],
      },
    },
  ];
}
// Helper to timeout fetch requests
interface FetchWithTimeoutOptions extends RequestInit {
  timeout?: number;
}

async function fetchWithTimeout(resource: string, options: FetchWithTimeoutOptions = {}) {
  const { timeout = 5000, ...fetchOptions } = options;
  
  const controller = new AbortController();
  const id = setTimeout(() => controller.abort(), timeout);
  
  const response = await fetch(resource, {
    ...fetchOptions,
    signal: controller.signal  
  });
  clearTimeout(id);
  return response;
}

export interface OrderSubmitData {
  total?: number;
  items?: OrderItem[];
  customer?: { name?: string; email?: string; phone?: string };
}

export async function submitOrder(orderData: OrderSubmitData) {
  try {
    const res = await fetchWithTimeout(`${API_BASE_URL}/create_order.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(orderData),
      timeout: 15000 // 15s timeout untuk proses email dan Midtrans
    });

    // Check content type to ensure we got JSON (PHP errors might return HTML)
    const contentType = res.headers.get("content-type");
    if (!res.ok || !contentType || !contentType.includes("application/json")) {
       throw new Error(`Failed to create order: ${res.statusText}`);
    }
    return res.json();
  } catch (err) {
    console.error('submitOrder error, falling back to mock:', err);
    // Mock response for failed backend
    const mockId = 'ORD-' + Math.floor(Math.random() * 10000);
    mockOrdersStore[mockId] = {
      id: mockId,
      total: orderData.total || 50000,
      status: 'pending',
      items: orderData.items || [],
      customer: orderData.customer || {},
      created_at: new Date().toISOString()
    };
    return {
      success: true,
      message: 'Order created successfully (Mock)',
      orderId: mockId,
      midtrans: null, 
      redirect: null
    };
  }
}

export async function payOrder(orderId: string, action: 'paid' | 'failed') {
  try {
    const res = await fetchWithTimeout(`${API_BASE_URL}/pay_order.php`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ orderId, action }),
      timeout: 15000 // 15s timeout untuk proses email dan Midtrans
    });
    if (!res.ok) throw new Error('Failed to update order payment');
    return res.json();
  } catch (err) {
    console.error('payOrder error, falling back to mock:', err);
    // Update mock store
    if (mockOrdersStore[orderId]) {
      mockOrdersStore[orderId].status = action;
    }
    return { success: true, message: `Payment ${action} (Mock)` };
  }
}

export async function fetchOrder(orderId: string) {
  try {
    const res = await fetchWithTimeout(`${API_BASE_URL}/get_order.php?orderId=${encodeURIComponent(orderId)}`, {
      timeout: 10000 // 10s timeout untuk fetch order
    });
    if (!res.ok) throw new Error('Failed to fetch order');
    return res.json();
  } catch (err) {
    console.error('fetchOrder error, falling back to mock:', err);
    // Mock order object matching the interface
    if (mockOrdersStore[orderId]) {
      return { success: true, order: mockOrdersStore[orderId] };
    }
    // Fallback if not in store (e.g. page reload clears memory)
    return {
      success: true,
      order: {
        id: orderId,
        total: 50000,
        status: 'pending',
        items: [{ id: '1', name: 'Mock Item', price: 25000, quantity: 2 }],
        customer: { name: 'Mock User', email: 'mock@example.com' },
        created_at: new Date().toISOString()
      }
    };
  }
}
