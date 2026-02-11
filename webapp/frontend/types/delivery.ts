export interface Order {
  id: number;
  order_number: string;
  user_id: number;
  kurir_id?: number;
  total_amount: number;
  final_amount: number;
  delivery_address?: string;
  delivery_distance?: number;
  customer_notes?: string;
  status: OrderStatus;
  payment_method: string;
  payment_status: PaymentStatus;
  expires_at?: string;
  assigned_at?: string;
  pickup_time?: string;
  delivery_time?: string;
  created_at: string;
  updated_at: string;
  
  // Customer info
  customer_name?: string;
  customer_phone?: string;
  customer_email?: string;
  trust_score?: number;
  total_successful_orders?: number;
  is_verified_user?: boolean;
  
  // Kurir info
  kurir_name?: string;
  kurir_phone?: string;
  vehicle_type?: string;
  
  // COD specific
  cod_amount_limit?: number;
  minutes_remaining?: number;
  recent_cancellations?: number;
  risk_level?: 'low' | 'medium' | 'high';
  is_expired?: boolean;
  is_expiring_soon?: boolean;
  
  // Delivery tracking
  progress?: number;
  minutes_since_assigned?: number;
  warning?: string;

  // Delivery photo proof
  kurir_departure_photo?: string;
  kurir_arrival_photo?: string;
  kurir_arrived_at?: string;
  actual_delivery_time?: number;

  // Geocoding
  geocode_status?: 'pending' | 'ok' | 'failed';
  geocode_error?: string;
}

export type OrderStatus = 
  | 'pending'
  | 'confirmed'
  | 'processing'
  | 'ready'
  | 'delivering'
  | 'completed'
  | 'cancelled';

export type PaymentStatus =
  | 'pending'
  | 'paid'
  | 'failed'
  | 'refunded';

export interface Kurir {
  id: number;
  name: string;
  phone: string;
  email?: string;
  photo?: string;
  vehicle_type: 'motor' | 'mobil' | 'sepeda';
  vehicle_number?: string;
  status: 'available' | 'busy' | 'offline';
  rating: number;
  total_deliveries: number;
  is_active: boolean;
  created_at: string;
  latitude?: number;
  longitude?: number;
  location_updated_at?: string;
  active_deliveries?: number;
  today_deliveries?: number;
  today_earnings?: number;
  is_available?: boolean;
  location_is_fresh?: boolean;
}

export interface OrderItem {
  id: number;
  order_id: number;
  product_id: number;
  product_name: string;
  size?: string;
  temperature?: string;
  quantity: number;
  unit_price: number;
  subtotal: number;
  notes?: string;
  addons?: string;
  addons_parsed?: Array<{ name: string; price: number }>;
}

export interface OrderStatusLog {
  id: number;
  order_id: number;
  from_status?: string;
  to_status: string;
  changed_by_type: 'system' | 'admin' | 'kurir' | 'customer';
  changed_by_id?: number;
  changed_by_name?: string;
  reason?: string;
  notes?: string;
  created_at: string;
}

export interface DeliveryHistory {
  id: number;
  order_id: number;
  kurir_id: number;
  kurir_name: string;
  status: string;
  notes?: string;
  location_lat?: number;
  location_lng?: number;
  photo_proof?: string;
  created_at: string;
}
