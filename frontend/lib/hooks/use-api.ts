import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://dailycup.test/webapp/backend/api";

// Types
export interface Product {
  id: string;
  name: string;
  description?: string;
  price: number;
  category?: string;
  image?: string;
  stock?: number;
  featured?: boolean;
  rating?: number;
  reviewCount?: number;
  variants?: {
    sizes?: { name: string; priceModifier: number }[];
    temperatures?: { name: string; priceModifier: number }[];
  };
}

export interface Category {
  id: string;
  name: string;
  description?: string;
  productCount?: number;
}

export interface Order {
  id: string;
  items: OrderItem[];
  total: number;
  status: string;
  createdAt: string;
  updatedAt?: string;
  customer?: {
    name: string;
    email: string;
    phone?: string;
    address?: string;
  };
  paymentMethod?: string;
  paymentStatus?: string;
  trackingNumber?: string;
  estimatedDelivery?: string;
  scheduledFor?: string;
}

export interface OrderItem {
  id: string;
  name: string;
  price: number;
  quantity: number;
  size?: string;
  temperature?: string;
  image?: string;
}

export interface Review {
  id: string;
  productId: string;
  userId: string;
  userName: string;
  rating: number;
  comment: string;
  createdAt: string;
  helpful?: number;
  images?: string[];
}

// Fetch wrapper with error handling
async function fetchAPI<T>(endpoint: string, options?: RequestInit): Promise<T> {
  const token = typeof window !== "undefined" 
    ? localStorage.getItem("dailycup-auth") 
    : null;
  
  const authData = token ? JSON.parse(token) : null;
  
  const headers: HeadersInit = {
    "Content-Type": "application/json",
    ...(authData?.state?.token && { Authorization: `Bearer ${authData.state.token}` }),
    ...options?.headers,
  };

  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: "Request failed" }));
    throw new Error(error.message || `HTTP error ${response.status}`);
  }

  return response.json();
}

// ============== PRODUCTS ==============

export function useProducts(params?: { category?: string; search?: string; sort?: string }) {
  const queryParams = new URLSearchParams();
  if (params?.category) queryParams.set("category", params.category);
  if (params?.search) queryParams.set("search", params.search);
  if (params?.sort) queryParams.set("sort", params.sort);

  const queryString = queryParams.toString();
  const endpoint = `/products.php${queryString ? `?${queryString}` : ""}`;

  return useQuery({
    queryKey: ["products", params],
    queryFn: () => fetchAPI<{ products: Product[] }>(endpoint),
    select: (data) => data.products,
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
}

export function useProduct(id: string) {
  return useQuery({
    queryKey: ["product", id],
    queryFn: () => fetchAPI<{ product: Product }>(`/products.php?id=${id}`),
    select: (data) => data.product,
    enabled: !!id,
  });
}

// ============== CATEGORIES ==============

export function useCategories() {
  return useQuery({
    queryKey: ["categories"],
    queryFn: () => fetchAPI<{ categories: Category[] }>("/categories.php"),
    select: (data) => data.categories,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

// ============== ORDERS ==============

export function useOrders(params?: { status?: string; page?: number; limit?: number }) {
  const queryParams = new URLSearchParams();
  if (params?.status) queryParams.set("status", params.status);
  if (params?.page) queryParams.set("page", params.page.toString());
  if (params?.limit) queryParams.set("limit", params.limit.toString());

  const queryString = queryParams.toString();
  const endpoint = `/orders.php${queryString ? `?${queryString}` : ""}`;

  return useQuery({
    queryKey: ["orders", params],
    queryFn: () => fetchAPI<{ orders: Order[]; total: number; page: number }>(endpoint),
    staleTime: 30 * 1000, // 30 seconds
  });
}

export function useOrder(id: string) {
  return useQuery({
    queryKey: ["order", id],
    queryFn: () => fetchAPI<{ success: boolean; order: Order }>(`/get_order.php?id=${id}`),
    select: (data) => data.order,
    enabled: !!id,
    refetchInterval: (query) => {
      // Poll every 5 seconds if order is pending/processing
      const order = query.state.data?.order;
      if (order && ["pending", "processing", "paid"].includes(order.status)) {
        return 5000;
      }
      return false;
    },
  });
}

export function useCreateOrder() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (orderData: {
      items: OrderItem[];
      total: number;
      customer: { name: string; email: string; phone?: string; address?: string };
      scheduledFor?: string;
    }) =>
      fetchAPI<{ success: boolean; orderId: string; invoice_url?: string }>("/create_order.php", {
        method: "POST",
        body: JSON.stringify(orderData),
      }),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ["orders"] });
      toast.success("Order created successfully!", {
        description: `Order ID: ${data.orderId}`,
      });
    },
    onError: (error: Error) => {
      toast.error("Failed to create order", {
        description: error.message,
      });
    },
  });
}

export function useRepeatOrder() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (orderId: string) =>
      fetchAPI<{ success: boolean; orderId: string }>("/repeat_order.php", {
        method: "POST",
        body: JSON.stringify({ orderId }),
      }),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ["orders"] });
      toast.success("Order repeated!", {
        description: `New order ID: ${data.orderId}`,
      });
    },
    onError: (error: Error) => {
      toast.error("Failed to repeat order", {
        description: error.message,
      });
    },
  });
}

// ============== REVIEWS ==============

export function useProductReviews(productId: string) {
  return useQuery({
    queryKey: ["reviews", productId],
    queryFn: () => fetchAPI<{ reviews: Review[]; averageRating: number; totalReviews: number }>(
      `/reviews.php?productId=${productId}`
    ),
    enabled: !!productId,
    staleTime: 2 * 60 * 1000,
  });
}

export function useCreateReview() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (reviewData: {
      productId: string;
      rating: number;
      comment: string;
      images?: string[];
    }) =>
      fetchAPI<{ success: boolean; review: Review }>("/reviews.php", {
        method: "POST",
        body: JSON.stringify(reviewData),
      }),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ["reviews", variables.productId] });
      queryClient.invalidateQueries({ queryKey: ["product", variables.productId] });
      toast.success("Review submitted!", {
        description: "Thank you for your feedback.",
      });
    },
    onError: (error: Error) => {
      toast.error("Failed to submit review", {
        description: error.message,
      });
    },
  });
}

// ============== LOYALTY POINTS ==============

export function useLoyaltyPoints() {
  return useQuery({
    queryKey: ["loyaltyPoints"],
    queryFn: () => fetchAPI<{ points: number; history: Array<{ amount: number; type: string; date: string }> }>(
      "/loyalty.php"
    ),
    staleTime: 60 * 1000,
  });
}

export function useRedeemPoints() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: { points: number; orderId?: string }) =>
      fetchAPI<{ success: boolean; discount: number; remainingPoints: number }>("/loyalty.php", {
        method: "POST",
        body: JSON.stringify({ action: "redeem", ...data }),
      }),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ["loyaltyPoints"] });
      toast.success("Points redeemed!", {
        description: `You received Rp ${data.discount.toLocaleString()} discount.`,
      });
    },
    onError: (error: Error) => {
      toast.error("Failed to redeem points", {
        description: error.message,
      });
    },
  });
}

// ============== FLASH SALES ==============

export interface FlashSale {
  id: string;
  productId: string;
  product: Product;
  discountPercent: number;
  originalPrice: number;
  salePrice: number;
  startTime: string;
  endTime: string;
  stockLimit: number;
  soldCount: number;
}

export function useFlashSales() {
  return useQuery({
    queryKey: ["flashSales"],
    queryFn: () => fetchAPI<{ sales: FlashSale[] }>("/flash_sales.php"),
    select: (data) => data.sales,
    staleTime: 30 * 1000, // Refresh every 30 seconds
    refetchInterval: 60 * 1000, // Auto-refresh every minute
  });
}

// ============== COUPONS ==============

export function useValidateCoupon() {
  return useMutation({
    mutationFn: (data: { code: string; orderTotal: number }) =>
      fetchAPI<{ valid: boolean; discount: number; discountType: string; message?: string }>(
        "/coupons.php",
        {
          method: "POST",
          body: JSON.stringify(data),
        }
      ),
    onSuccess: (data) => {
      if (data.valid) {
        toast.success("Coupon applied!", {
          description: `You saved Rp ${data.discount.toLocaleString()}`,
        });
      } else {
        toast.error("Invalid coupon", {
          description: data.message || "This coupon cannot be applied.",
        });
      }
    },
    onError: (error: Error) => {
      toast.error("Failed to validate coupon", {
        description: error.message,
      });
    },
  });
}
