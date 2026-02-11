'use client';

import { useParams, useRouter } from 'next/navigation';
import { useState, useEffect, useRef } from 'react';
import dynamic from 'next/dynamic';
import Header from '../../../components/Header';
import Link from 'next/link';
import Image from 'next/image';
import api from '@/lib/api-client';
import toast from 'react-hot-toast';

// Dynamic import for Leaflet (client-side only)
const LeafletMapTracker = dynamic(
  () => import('@/components/LeafletMapTracker'),
  { ssr: false, loading: () => <div className="h-48 bg-gray-100 dark:bg-[#333] rounded-xl animate-pulse flex items-center justify-center"><span className="text-gray-400">Loading map...</span></div> }
);

// Tipe data untuk Order Detail dari API
interface OrderDetail {
  id: string;
  date: string;
  status: 'pending' | 'confirmed' | 'processing' | 'ready' | 'delivering' | 'completed' | 'cancelled';
  payment_status?: 'pending' | 'paid' | 'failed' | 'refunded';
  items: Array<{
    id: number;
    name: string;
    image: string | null;
    variant: Record<string, string>;
    price: number;
    quantity: number;
  }>;
  subtotal: number;
  deliveryFee: number;
  discount?: number;
  total: number;
  shippingAddress: {
    name: string;
    phone: string;
    address: string;
    lat?: number;
    lng?: number;
  };
  paymentMethod: string;
  deliveryMethod?: string;
  notes?: string;
  timeline: Array<{
    status: string;
    date: string;
    completed: boolean;
    icon: string;
  }>;
  kurirDeparturePhoto?: string | null;
  kurirArrivalPhoto?: string | null;
  actualDeliveryTime?: number | null;
  kurir?: {
    id: number;
    name: string;
    phone: string;
    vehicle_type?: string;
    photo?: string;
  };
}

interface KurirLocation {
  lat: number;
  lng: number;
}

export default function OrderTrackingPage() {
  const params = useParams();
  const router = useRouter();
  const { id } = params;
  
  const [order, setOrder] = useState<OrderDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [kurirLocation, setKurirLocation] = useState<KurirLocation | null>(null);
  const [realtimeConnected, setRealtimeConnected] = useState(false);
  const [lastUpdate, setLastUpdate] = useState<string>('');
  const eventSourceRef = useRef<EventSource | null>(null);

  // Real-time SSE for kurir tracking
  useEffect(() => {
    if (!id || !order) return;

    // Only track when order is being delivered
    const shouldTrack = ['processing', 'ready', 'delivering'].includes(order.status);
    
    if (!shouldTrack || !order.kurir) {
      if (eventSourceRef.current) {
        eventSourceRef.current.close();
        eventSourceRef.current = null;
        setRealtimeConnected(false);
      }
      return;
    }

    const baseUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/DailyCup/webapp/backend';
    // Normalize baseUrl to avoid duplicate /api
    const baseRoot = baseUrl.replace(/\/api\/?$/i, '');
    const sseUrl = `${baseRoot}/api/realtime/track_kurir.php?order_id=${id}`;
    console.log('[SSE] Connecting to', sseUrl);
    
    const eventSource = new EventSource(sseUrl);
    eventSourceRef.current = eventSource;

    eventSource.addEventListener('init', () => {
      setRealtimeConnected(true);
    });

    eventSource.addEventListener('location', (e) => {
      const data = JSON.parse(e.data);
      setKurirLocation({ lat: data.lat, lng: data.lng });
      setLastUpdate(new Date().toLocaleTimeString('id-ID'));
      
      // Update order status if changed
      if (data.status && data.status !== order.status) {
        setOrder(prev => prev ? { ...prev, status: data.status } : null);
      }
    });

    eventSource.addEventListener('complete', () => {
      setRealtimeConnected(false);
      // Refetch order to get final status
      fetchOrderData();
      eventSource.close();
    });

    eventSource.onerror = () => {
      setRealtimeConnected(false);
    };

    return () => {
      if (eventSource) {
        eventSource.close();
      }
    };
  }, [id, order?.status, order?.kurir]);

  const fetchOrderData = async () => {
    if (!id) return;
    
    setLoading(true);
    setError(null);
    
    try {
      const response = await api.get<{success: boolean; data: OrderDetail}>(`/orders/get_order_detail.php?order_id=${id}`);
      
      if (response.success && response.data) {
        setOrder(response.data);
      } else {
        setError('Order not found');
        toast.error('Order not found');
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to load order';
      console.error('Failed to fetch order:', err);
      setError(errorMessage);
      toast.error('Failed to load order details');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOrderData();
  }, [id]);
  if (loading) {
    return (
      <div className="min-h-screen bg-[#f5f0ec] dark:bg-[#1a1a1a]">
        <Header />
        <div className="flex justify-center items-center h-[60vh]">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-[#a97456]"></div>
        </div>
      </div>
    );
  }

  if (!order) return null;

  return (
    <div className="min-h-screen bg-[#f5f0ec] dark:bg-[#1a1a1a] transition-colors duration-300">
      <Header />

      <div className="max-w-4xl mx-auto px-4 py-8">
        {/* Back Button */}
        <Link href="/orders" className="inline-flex items-center text-gray-500 hover:text-[#a97456] mb-6 transition-colors">
          <i className="bi bi-arrow-left mr-2"></i> Back to Orders
        </Link>

        {/* Header Section */}
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
          <div>
            <h1 className="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-3">
              Order #{order.id}
              <span className="px-3 py-1 bg-amber-100 text-amber-800 text-xs rounded-full uppercase tracking-wider font-semibold">
                {order.status}
              </span>
            </h1>
            <p className="text-gray-500 dark:text-gray-400 text-sm mt-1">Placed on {order.date}</p>
          </div>
          <button className="bg-white dark:bg-[#333] border border-gray-200 dark:border-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-[#444] transition-colors">
            <i className="bi bi-file-earmark-text mr-2"></i> Invoice
          </button>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Column: Tracking & Details */}
          <div className="lg:col-span-2 space-y-6">
            
            {/* Tracking Timeline */}
            <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
              <h2 className="font-bold text-lg mb-6 flex items-center gap-2">
                <i className="bi bi-geo-alt text-[#a97456]"></i> Order Status
              </h2>
              
              <div className="relative pl-4">
                 {/* Line */}
                 <div className="absolute left-[27px] top-4 bottom-4 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                 <div className="space-y-8">
                    {order.timeline.map((step, index) => (
                      <div key={index} className="relative flex gap-4">
                        {/* Dot/Icon */}
                        <div className={`relative z-10 w-8 h-8 rounded-full flex items-center justify-center border-2 flex-shrink-0 transition-colors duration-300
                          ${step.completed 
                            ? 'bg-[#a97456] border-[#a97456] text-white' 
                            : 'bg-white dark:bg-[#333] border-gray-300 text-gray-400'
                          }
                        `}>
                          <i className={`bi ${step.icon} text-sm`}></i>
                        </div>
                        
                        {/* Content */}
                        <div className={`pt-1 ${step.completed ? 'opacity-100' : 'opacity-60 grayscale'}`}>
                          <h3 className="font-semibold text-gray-800 dark:text-gray-200">{step.status}</h3>
                          <p className="text-xs text-gray-500">{step.date}</p>
                        </div>
                      </div>
                    ))}
                 </div>
              </div>

              {/* Live Driver Tracking Map */}
              <div className="mt-8">
                {/* Kurir Info */}
                {order.kurir && (
                  <div className="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex items-center gap-3">
                    <div className="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                      {order.kurir.photo ? (
                        <img src={order.kurir.photo} alt={order.kurir.name} className="w-full h-full rounded-full object-cover" />
                      ) : (
                        order.kurir.name.charAt(0).toUpperCase()
                      )}
                    </div>
                    <div className="flex-1">
                      <p className="font-semibold text-gray-800 dark:text-gray-200">{order.kurir.name}</p>
                      <p className="text-xs text-gray-500">
                        <i className="bi bi-telephone mr-1"></i>{order.kurir.phone}
                        {order.kurir.vehicle_type && <span className="ml-2"><i className="bi bi-bicycle mr-1"></i>{order.kurir.vehicle_type}</span>}
                      </p>
                    </div>
                    {realtimeConnected && (
                      <span className="text-xs text-green-600 dark:text-green-400 flex items-center">
                        <span className="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></span>
                        Live
                      </span>
                    )}
                  </div>
                )}

                {/* Map or Placeholder */}
                {['processing', 'ready', 'delivering'].includes(order.status) && order.kurir ? (
                  <div className="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600">
                    <LeafletMapTracker
                      courierLocation={kurirLocation || undefined}
                      customerLocation={order.shippingAddress.lat && order.shippingAddress.lng ? {
                        lat: order.shippingAddress.lat,
                        lng: order.shippingAddress.lng
                      } : undefined}
                      orderId={order.id}
                    />
                    {lastUpdate && (
                      <div className="bg-gray-50 dark:bg-[#222] px-3 py-2 text-xs text-gray-500 flex justify-between">
                        <span>Last update: {lastUpdate}</span>
                        <span className={realtimeConnected ? 'text-green-600' : 'text-gray-400'}>
                          {realtimeConnected ? '● Connected' : '○ Connecting...'}
                        </span>
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="h-48 bg-gray-100 dark:bg-[#333] rounded-xl flex items-center justify-center relative overflow-hidden">
                    <div className="absolute inset-0 opacity-10 bg-[url('https://upload.wikimedia.org/wikipedia/commons/e/ec/World_map_blank_without_borders.svg')] bg-cover"></div>
                    <div className="text-gray-400 flex flex-col items-center">
                      <i className="bi bi-map text-3xl mb-2"></i>
                      <span className="text-sm">
                        {order.status === 'completed' ? 'Pesanan Selesai' : 
                         order.status === 'cancelled' ? 'Pesanan Dibatalkan' :
                         !order.kurir ? 'Menunggu Kurir' : 'Live Tracking'}
                      </span>
                      <span className="text-xs">
                        {order.status === 'pending' || order.status === 'confirmed' ? 'Map akan aktif saat pengiriman' : ''}
                      </span>
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Delivery Photo Proof */}
            {(order.kurirDeparturePhoto || order.kurirArrivalPhoto) && (
              <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <h2 className="font-bold text-lg mb-4 flex items-center gap-2">
                  <i className="bi bi-camera text-[#a97456]"></i> Bukti Pengiriman
                </h2>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  {order.kurirDeparturePhoto && (
                    <div>
                      <p className="text-sm text-gray-500 mb-2 font-medium">📦 Foto Keberangkatan</p>
                      <div className="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600">
                        <img
                          src={order.kurirDeparturePhoto.startsWith('http')
                            ? order.kurirDeparturePhoto
                            : `http://localhost/DailyCup/webapp/backend/${order.kurirDeparturePhoto}`}
                          alt="Bukti keberangkatan kurir"
                          className="w-full h-48 object-cover"
                        />
                      </div>
                    </div>
                  )}
                  {order.kurirArrivalPhoto && (
                    <div>
                      <p className="text-sm text-gray-500 mb-2 font-medium">✅ Foto Tiba di Tujuan</p>
                      <div className="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600">
                        <img
                          src={order.kurirArrivalPhoto.startsWith('http')
                            ? order.kurirArrivalPhoto
                            : `http://localhost/DailyCup/webapp/backend/${order.kurirArrivalPhoto}`}
                          alt="Bukti sampai tujuan"
                          className="w-full h-48 object-cover"
                        />
                      </div>
                    </div>
                  )}
                </div>
                {order.actualDeliveryTime && (
                  <div className="mt-3 bg-green-50 dark:bg-green-900/10 rounded-lg p-3 text-center">
                    <p className="text-sm text-green-700 dark:text-green-400">
                      <i className="bi bi-clock mr-1"></i>
                      Waktu pengiriman: <span className="font-bold">{order.actualDeliveryTime} menit</span>
                    </p>
                  </div>
                )}
              </div>
            )}

            {/* Order Items */}
            <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
              <h2 className="font-bold text-lg mb-6">Items Ordered</h2>
              <div className="space-y-4">
                {order.items.map((item, index) => (
                  <div key={`${item.id}-${index}`} className="flex gap-4">
                    <div className="w-20 h-20 bg-gray-100 rounded-lg relative overflow-hidden flex-shrink-0">
                      {item.image ? (
                        <Image 
                          src={item.image.startsWith('http') || item.image.startsWith('/') 
                            ? item.image 
                            : `http://localhost/DailyCup/webapp/backend/uploads/products/${item.image}`
                          } 
                          alt={item.name} 
                          fill 
                          className="object-cover"
                          onError={(e) => {
                            const target = e.target as HTMLImageElement;
                            target.src = '/assets/images/placeholder-product.png';
                          }}
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center bg-gray-200">
                          <i className="bi bi-cup-hot text-2xl text-gray-400"></i>
                        </div>
                      )}
                    </div>
                    <div className="flex-1">
                      <h3 className="font-semibold mb-1">{item.name}</h3>
                      <p className="text-sm text-gray-500 mb-2">
                        {Object.keys(item.variant).length > 0 
                          ? Object.values(item.variant).filter(Boolean).join(', ')
                          : 'Standard'}
                      </p>
                        <div className="flex justify-between items-end mt-2">
                           <p className="text-sm text-gray-600 dark:text-gray-400">x{item.quantity}</p>
                           <p className="font-semibold text-[#a97456]">Rp {(item.price * item.quantity).toLocaleString()}</p>
                        </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

          </div>

          {/* Right Column: Summary & Info */}
          <div className="lg:col-span-1 space-y-6">
            
            {/* Delivery Details */}
            <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
               <h3 className="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Delivery Address</h3>
               <p className="font-semibold text-gray-800 dark:text-gray-200">{order.shippingAddress.name}</p>
               <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">{order.shippingAddress.phone}</p>
               <p className="text-sm text-gray-600 dark:text-gray-400 mt-2 leading-relaxed">
                  {order.shippingAddress.address}
               </p>
            </div>

            {/* Payment Info */}
             <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
               <h3 className="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Payment Info</h3>
               <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                    <i className="bi bi-credit-card"></i>
                  </div>
                  <div>
                    <p className="font-medium text-gray-800 dark:text-gray-200">{order.paymentMethod}</p>
                    <p className="text-xs text-green-600 font-medium">Payment Verified</p>
                  </div>
               </div>
            </div>

            {/* Cost Summary */}
            <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
              <h3 className="font-bold text-lg mb-4">Order Summary</h3>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Subtotal</span>
                  <span className="font-medium">Rp {order.subtotal.toLocaleString()}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Delivery Fee</span>
                  <span className="font-medium">Rp {order.deliveryFee.toLocaleString()}</span>
                </div>
                <div className="border-t dark:border-gray-600 pt-3 mt-3 flex justify-between text-base font-bold">
                  <span>Total Paid</span>
                  <span className="text-[#a97456]">Rp {order.total.toLocaleString()}</span>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  );
}
