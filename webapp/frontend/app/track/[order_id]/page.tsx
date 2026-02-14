"use client";

import { useState, useEffect, useRef } from "react";
import { useParams } from "next/navigation";
import { api } from "@/lib/api-client";
import Header from "@/components/Header";
import LeafletMapTracker from "@/components/LeafletMapTracker";
import { getErrorMessage } from '@/lib/utils';

interface OrderTracking {
  order: {
    order_id: string;
    customer_name: string;
    customer_address: string;
    total: number;
    status: string;
    payment_method: string;
    created_at: string;
    estimated_delivery?: string;
    completed_at?: string;
  };
  courier?: {
    name: string;
    phone: string;
    vehicle_type: string;
    photo?: string;
    current_location?: { lat: number; lng: number };
  };
  status_history: Array<{
    status: string;
    message?: string;
    created_at: string;
  }>;
  items: Array<{
    product_name: string;
    variant?: string;
    quantity: number;
    price: number;
  }>;
  cod_verification?: {
    is_verified: boolean;
    is_trusted_user: boolean;
  };
}

const STATUS_STEPS = [
  { key: 'pending_payment', label: 'Menunggu Pembayaran', icon: 'bi-clock' },
  { key: 'waiting_confirmation', label: 'Konfirmasi', icon: 'bi-check-circle' },
  { key: 'queueing', label: 'Antrian', icon: 'bi-list-ul' },
  { key: 'preparing', label: 'Diproses', icon: 'bi-gear' },
  { key: 'on_delivery', label: 'Dikirim', icon: 'bi-truck' },
  { key: 'completed', label: 'Selesai', icon: 'bi-check-all' },
];

export default function OrderTrackerPage() {
  const params = useParams();
  const orderId = params.order_id as string;
  const [tracking, setTracking] = useState<OrderTracking | null>(null);
  const [loading, setLoading] = useState(true);
  const [showOtpModal, setShowOtpModal] = useState(false);
  const [otpCode, setOtpCode] = useState('');
  const [otpInput, setOtpInput] = useState('');
  const [otpError, setOtpError] = useState('');
  const [realtimeConnected, setRealtimeConnected] = useState(false);
  const [lastLocationUpdate, setLastLocationUpdate] = useState<string>('');
  const eventSourceRef = useRef<EventSource | null>(null);

  useEffect(() => {
    if (orderId) {
      fetchTracking();
    }
  }, [orderId]);

  // Real-time SSE connection for kurir location updates
  useEffect(() => {
    if (!orderId || !tracking) return;

    // Only connect SSE when order has kurir assigned and is in delivery status
    const shouldTrackRealtime = tracking.courier && 
      ['processing', 'ready', 'delivering'].includes(tracking.order.status);

    if (!shouldTrackRealtime) {
      // Clean up existing connection if status changed
      if (eventSourceRef.current) {
        eventSourceRef.current.close();
        eventSourceRef.current = null;
        setRealtimeConnected(false);
      }
      return;
    }

    // Create SSE connection
    const baseUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/DailyCup/webapp/backend';
    // Normalize baseUrl to avoid duplicate /api (some envs include trailing /api)
    const baseRoot = baseUrl.replace(/\/api\/?$/i, '');
    const sseUrl = `${baseRoot}/api/realtime/track_kurir.php?order_id=${orderId}`;
    console.log('[SSE] Connecting to', sseUrl);
    
    const eventSource = new EventSource(sseUrl);
    eventSourceRef.current = eventSource;

    eventSource.addEventListener('init', (e) => {
      const data = JSON.parse(e.data);
      console.log('[SSE] Initialized:', data);
      setRealtimeConnected(true);
    });

    eventSource.addEventListener('location', (e) => {
      const data = JSON.parse(e.data);
      console.log('[SSE] Location update:', data);
      
      // Update courier location in tracking state
      setTracking(prev => {
        if (!prev) return prev;
        return {
          ...prev,
          courier: prev.courier ? {
            ...prev.courier,
            current_location: { lat: data.lat, lng: data.lng }
          } : undefined,
          order: {
            ...prev.order,
            status: data.status
          }
        };
      });
      
      setLastLocationUpdate(new Date().toLocaleTimeString('id-ID'));
    });

    eventSource.addEventListener('complete', (e) => {
      const data = JSON.parse(e.data);
      console.log('[SSE] Delivery complete:', data);
      setRealtimeConnected(false);
      // Refresh tracking to get final status
      fetchTracking();
      eventSource.close();
    });

    eventSource.addEventListener('ping', (e) => {
      // Keep-alive ping
      console.log('[SSE] Ping received');
    });

    eventSource.addEventListener('error', (e) => {
      console.error('[SSE] Connection error:', e);
      setRealtimeConnected(false);
    });

    eventSource.onerror = () => {
      console.log('[SSE] Connection lost, will retry...');
      setRealtimeConnected(false);
    };

    // Cleanup on unmount
    return () => {
      if (eventSource) {
        eventSource.close();
      }
    };
  }, [orderId, tracking?.courier, tracking?.order.status]);

  const fetchTracking = async () => {
    try {
      const response = await api.get<{success: boolean; order?: OrderTracking['order']; courier?: OrderTracking['courier']; status_history?: OrderTracking['status_history']; items?: OrderTracking['items'] }>(
        `/orders/tracking.php?order_id=${orderId}`,
        { requiresAuth: false }
      );
      
      if (response.success) {
        setTracking(response as unknown as OrderTracking);
      }
    } catch (error: unknown) {
      console.error('Failed to fetch tracking:', getErrorMessage(error));
    } finally {
      setLoading(false);
    }
  };

  const generateOtp = async () => {
    try {
      const response = await api.post<{success: boolean; auto_approved?: boolean; simulated_otp?: string; message?: string}>('/orders/cod_generate_otp.php', {
        order_id: orderId
      });

      if (response.success) {
        if (response.auto_approved) {
          alert('✅ Anda adalah pelanggan terpercaya! Pesanan otomatis dikonfirmasi.');
          fetchTracking();
        } else {
          setOtpCode(response.simulated_otp || '');
          setShowOtpModal(true);
          alert(`📱 SIMULATED OTP: ${response.simulated_otp}\n\nDalam sistem produksi, kode ini akan dikirim via WhatsApp.`);
        }
      }
    } catch (error: unknown) {
      alert(getErrorMessage(error) || 'Failed to generate OTP');
    }
  };

  const verifyOtp = async () => {
    try {
      setOtpError('');
      const response = await api.post<{success: boolean; message?: string}>('/orders/cod_verify_otp.php', {
        order_id: orderId,
        otp_code: otpInput
      });

      if (response.success) {
        alert('✅ OTP verified! Your order is now in queue.');
        setShowOtpModal(false);
        setOtpInput('');
        fetchTracking();
      }
    } catch (error: unknown) {
      setOtpError(getErrorMessage(error) || 'Invalid OTP');
    }
  };

  const getCurrentStepIndex = () => {
    if (!tracking) return 0;
    return STATUS_STEPS.findIndex(step => step.key === tracking.order.status);
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="flex items-center justify-center py-20">
          <div className="text-center">
            <i className="bi bi-arrow-repeat animate-spin text-4xl text-[#a97456]"></i>
            <p className="mt-4 text-gray-600">Loading tracking information...</p>
          </div>
        </div>
      </div>
    );
  }

  if (!tracking) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="flex items-center justify-center py-20">
          <div className="text-center">
            <i className="bi bi-x-circle text-6xl text-red-500"></i>
            <h2 className="mt-4 text-2xl font-bold text-gray-800">Order Not Found</h2>
            <p className="text-gray-600">We couldn't find order #{orderId}</p>
          </div>
        </div>
      </div>
    );
  }

  const currentStep = getCurrentStepIndex();
  const isCancelled = tracking.order.status === 'cancelled';

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />

      <div className="max-w-4xl mx-auto p-6 pt-24">
        {/* Order Header */}
        <div className="bg-white rounded-2xl shadow-lg p-6 mb-6">
          <div className="flex justify-between items-start">
            <div>
              <h1 className="text-2xl font-bold text-gray-800">
                Track Order
              </h1>
              <p className="text-gray-500 font-mono">#{tracking.order.order_id}</p>
              
              {/* Real-time Status Indicator */}
              {tracking.courier && ['processing', 'ready', 'delivering'].includes(tracking.order.status) && (
                <div className="mt-2 flex items-center gap-2">
                  {realtimeConnected ? (
                    <>
                      <div className="flex items-center gap-1.5 text-green-600">
                        <span className="relative flex h-2 w-2">
                          <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <span className="text-xs font-medium">Live Tracking</span>
                      </div>
                      {lastLocationUpdate && (
                        <span className="text-xs text-gray-500">
                          Last update: {lastLocationUpdate}
                        </span>
                      )}
                    </>
                  ) : (
                    <div className="flex items-center gap-1.5 text-gray-400">
                      <i className="bi bi-circle text-xs"></i>
                      <span className="text-xs">Connecting...</span>
                    </div>
                  )}
                </div>
              )}
            </div>
            <div className={`px-4 py-2 rounded-lg font-medium ${
              isCancelled ? 'bg-red-100 text-red-700' :
              tracking.order.status === 'completed' ? 'bg-green-100 text-green-700' :
              'bg-blue-100 text-blue-700'
            }`}>
              {tracking.order.status.replace('_', ' ').toUpperCase()}
            </div>
          </div>
        </div>

        {/* COD Verification Banner */}
        {tracking.order.payment_method === 'cod' && 
         tracking.order.status === 'waiting_confirmation' &&
         !tracking.cod_verification?.is_verified && (
          <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <h3 className="font-semibold text-yellow-800 mb-2">
              <i className="bi bi-exclamation-triangle mr-2"></i>
              COD Verification Required
            </h3>
            <p className="text-sm text-yellow-700 mb-3">
              Konfirmasi pesanan COD Anda dengan kode OTP
            </p>
            <button
              onClick={generateOtp}
              className="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700"
            >
              Generate OTP
            </button>
          </div>
        )}

        {/* Progress Timeline */}
        {!isCancelled && (
          <div className="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <h2 className="text-xl font-bold text-gray-800 mb-6">Order Progress</h2>
            
            <div className="relative">
              {/* Progress Line */}
              <div className="absolute top-6 left-6 right-6 h-1 bg-gray-200">
                <div 
                  className="h-full bg-[#a97456] transition-all duration-500"
                  style={{ width: `${(currentStep / (STATUS_STEPS.length - 1)) * 100}%` }}
                ></div>
              </div>

              {/* Steps */}
              <div className="relative flex justify-between">
                {STATUS_STEPS.map((step, index) => (
                  <div key={step.key} className="flex flex-col items-center">
                    <div className={`w-12 h-12 rounded-full flex items-center justify-center mb-2 ${
                      index <= currentStep 
                        ? 'bg-[#a97456] text-white' 
                        : 'bg-gray-200 text-gray-400'
                    }`}>
                      <i className={`${step.icon} text-xl`}></i>
                    </div>
                    <span className="text-xs text-center max-w-[80px]">
                      {step.label}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* Live Map Tracking */}
        {tracking.courier && ['delivering', 'on_delivery', 'ready'].includes(tracking.order.status) && (
          <div className="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <h2 className="text-xl font-bold text-gray-800 mb-4">
              <i className="bi bi-map mr-2"></i>
              Live Tracking - OpenStreetMap
            </h2>
            <LeafletMapTracker
              courierLocation={tracking.courier.current_location}
              customerLocation={undefined}
              orderId={tracking.order.order_id}
            />
          </div>
        )}

        {/* Courier Info */}
        {tracking.courier && (
          <div className="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <h2 className="text-xl font-bold text-gray-800 mb-4">
              <i className="bi bi-person-badge mr-2"></i>
              Courier Information
            </h2>
            <div className="flex items-center gap-4">
              <div className="w-16 h-16 bg-[#a97456] rounded-full flex items-center justify-center text-white text-2xl">
                <i className="bi bi-person"></i>
              </div>
              <div>
                <p className="font-semibold text-gray-800">{tracking.courier.name}</p>
                <p className="text-sm text-gray-500">
                  <i className="bi bi-telephone mr-1"></i>
                  {tracking.courier.phone}
                </p>
                <p className="text-sm text-gray-500">
                  <i className="bi bi-bicycle mr-1"></i>
                  {tracking.courier.vehicle_type}
                </p>
              </div>
            </div>

            {tracking.courier.photo && (
              <div className="mt-4 pt-4 border-t">
                <p className="text-sm text-gray-600 mb-2">Delivery Photo:</p>
                <img 
                  src={tracking.courier.photo} 
                  alt="Delivery proof" 
                  className="max-w-full rounded-lg"
                />
              </div>
            )}
          </div>
        )}

        {/* Order Items */}
        <div className="bg-white rounded-2xl shadow-lg p-6 mb-6">
          <h2 className="text-xl font-bold text-gray-800 mb-4">Order Items</h2>
          <div className="space-y-3">
            {tracking.items.map((item, index) => (
              <div key={index} className="flex justify-between items-center">
                <div>
                  <p className="font-medium text-gray-800">{item.product_name}</p>
                  {item.variant && (
                    <p className="text-sm text-gray-500">{item.variant}</p>
                  )}
                </div>
                <div className="text-right">
                  <p className="text-sm text-gray-600">{item.quantity}x</p>
                  <p className="font-semibold text-[#a97456]">
                    Rp {(item.price * item.quantity).toLocaleString('id-ID')}
                  </p>
                </div>
              </div>
            ))}
            <div className="pt-3 border-t flex justify-between items-center">
              <span className="font-bold text-gray-800">Total</span>
              <span className="font-bold text-xl text-[#a97456]">
                Rp {tracking.order.total.toLocaleString('id-ID')}
              </span>
            </div>
          </div>
        </div>

        {/* Status History */}
        <div className="bg-white rounded-2xl shadow-lg p-6">
          <h2 className="text-xl font-bold text-gray-800 mb-4">Status History</h2>
          <div className="space-y-3">
            {tracking.status_history.map((log, index) => (
              <div key={index} className="flex gap-3">
                <div className="w-2 h-2 bg-[#a97456] rounded-full mt-2"></div>
                <div className="flex-1">
                  <p className="font-medium text-gray-800">
                    {log.status.replace('_', ' ').toUpperCase()}
                  </p>
                  {log.message && (
                    <p className="text-sm text-gray-500">{log.message}</p>
                  )}
                  <p className="text-xs text-gray-400">
                    {new Date(log.created_at).toLocaleString('id-ID')}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* OTP Verification Modal */}
      {showOtpModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <h2 className="text-xl font-bold text-gray-800 mb-4">
              Verify COD Order
            </h2>
            
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
              <p className="text-sm text-blue-700">
                <strong>SIMULATED OTP:</strong> {otpCode}
              </p>
              <p className="text-xs text-blue-600 mt-1">
                In production, this would be sent to your WhatsApp
              </p>
            </div>

            <label className="block text-sm font-medium text-gray-700 mb-2">
              Enter OTP Code
            </label>
            <input
              type="text"
              value={otpInput}
              onChange={(e) => setOtpInput(e.target.value)}
              maxLength={6}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent mb-2"
              placeholder="123456"
            />
            
            {otpError && (
              <p className="text-red-500 text-sm mb-3">{otpError}</p>
            )}

            <div className="flex gap-3">
              <button
                onClick={verifyOtp}
                className="flex-1 bg-[#a97456] text-white py-2 rounded-lg hover:bg-[#8f6249]"
              >
                Verify
              </button>
              <button
                onClick={() => {
                  setShowOtpModal(false);
                  setOtpInput('');
                  setOtpError('');
                }}
                className="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
