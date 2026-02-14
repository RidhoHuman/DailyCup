'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { api } from '@/lib/api-client';
import { Package, Truck, CheckCircle, Clock, User, Phone, MapPin, DollarSign, FileText } from 'lucide-react';
import { getErrorMessage } from '@/lib/utils';

interface CODTracking {
  id: number;
  order_id: string;
  courier_name: string | null;
  courier_phone: string | null;
  tracking_number: string | null;
  status: 'pending' | 'confirmed' | 'packed' | 'out_for_delivery' | 'delivered' | 'payment_received' | 'cancelled';
  payment_received: number;
  payment_received_at: string | null;
  payment_amount: number | null;
  payment_notes: string | null;
  receiver_name: string | null;
  receiver_relation: string | null;
  delivery_photo_url: string | null;
  confirmed_at: string | null;
  packed_at: string | null;
  out_for_delivery_at: string | null;
  delivered_at: string | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
  // Order details
  order_number: string;
  customer_name: string;
  customer_phone: string;
  customer_address: string;
  total: number;
  order_status: string;
}

export default function CODTrackingPage() {
  const params = useParams();
  const router = useRouter();
  const orderId = params.id as string;
  
  const [tracking, setTracking] = useState<CODTracking | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchTracking();
  }, [orderId]);

  const fetchTracking = async () => {
    try {
      setLoading(true);
      setError('');
      const response = await api.get<unknown>(`/cod_tracking.php?order_id=${orderId}`);
      const r = response as { data?: { success?: boolean; data?: CODTracking; message?: string } };
      
      if (r.data?.success) {
        setTracking(r.data.data || null);
      } else {
        setError(r.data?.message || 'Failed to load tracking info');
      }
    } catch (err: unknown) {
      console.error('Error fetching COD tracking:', getErrorMessage(err));
      setError((err as any)?.response?.data?.message || 'Failed to load tracking information');
    } finally {
      setLoading(false);
    }
  };

  const getStatusSteps = () => {
    const steps = [
      { key: 'pending', label: 'Order Placed', icon: Clock, time: tracking?.created_at },
      { key: 'confirmed', label: 'Confirmed', icon: CheckCircle, time: tracking?.confirmed_at },
      { key: 'packed', label: 'Packed', icon: Package, time: tracking?.packed_at },
      { key: 'out_for_delivery', label: 'Out for Delivery', icon: Truck, time: tracking?.out_for_delivery_at },
      { key: 'delivered', label: 'Delivered', icon: CheckCircle, time: tracking?.delivered_at },
      { key: 'payment_received', label: 'Payment Received', icon: DollarSign, time: tracking?.payment_received_at },
    ];

    const currentStatusIndex = steps.findIndex(s => s.key === tracking?.status);
    
    return steps.map((step, index) => ({
      ...step,
      completed: index <= currentStatusIndex,
      active: index === currentStatusIndex,
    }));
  };

  const formatDate = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading tracking information...</p>
        </div>
      </div>
    );
  }

  if (error || !tracking) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-lg shadow-lg p-8 max-w-md w-full text-center">
          <div className="text-red-500 text-5xl mb-4">⚠️</div>
          <h2 className="text-2xl font-bold text-gray-800 mb-2">Tracking Not Available</h2>
          <p className="text-gray-600 mb-6">{error || 'No tracking information found for this order'}</p>
          <button
            onClick={() => router.push('/orders')}
            className="bg-[#a97456] text-white px-6 py-2 rounded-lg hover:bg-[#8b5e3c] transition"
          >
            Back to Orders
          </button>
        </div>
      </div>
    );
  }

  const statusSteps = getStatusSteps();
  const isCancelled = tracking.status === 'cancelled';

  return (
    <div className="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-4xl mx-auto">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h1 className="text-2xl font-bold text-gray-800">COD Order Tracking</h1>
              <p className="text-gray-600">Order #{tracking.order_number}</p>
            </div>
            <button
              onClick={() => router.push('/orders')}
              className="text-[#a97456] hover:text-[#8b5e3c] font-medium"
            >
              ← Back to Orders
            </button>
          </div>

          {/* Order Summary */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t">
            <div className="flex items-start gap-3">
              <User className="text-gray-400 mt-1" size={20} />
              <div>
                <p className="text-sm text-gray-500">Customer</p>
                <p className="font-medium text-gray-800">{tracking.customer_name}</p>
              </div>
            </div>
            <div className="flex items-start gap-3">
              <Phone className="text-gray-400 mt-1" size={20} />
              <div>
                <p className="text-sm text-gray-500">Phone</p>
                <p className="font-medium text-gray-800">{tracking.customer_phone}</p>
              </div>
            </div>
            <div className="flex items-start gap-3">
              <DollarSign className="text-gray-400 mt-1" size={20} />
              <div>
                <p className="text-sm text-gray-500">Total Amount</p>
                <p className="font-medium text-gray-800">{formatCurrency(tracking.total)}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Status Timeline */}
        {!isCancelled && (
          <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 className="text-xl font-bold text-gray-800 mb-6">Delivery Status</h2>
            <div className="relative">
              {/* Progress Line */}
              <div className="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>
              <div 
                className="absolute left-6 top-0 w-0.5 bg-[#a97456] transition-all duration-500"
                style={{ 
                  height: `${(statusSteps.filter(s => s.completed).length - 1) / (statusSteps.length - 1) * 100}%` 
                }}
              ></div>

              {/* Steps */}
              <div className="space-y-8">
                {statusSteps.map((step, index) => {
                  const Icon = step.icon;
                  return (
                    <div key={step.key} className="relative flex items-start gap-4">
                      {/* Icon */}
                      <div className={`
                        relative z-10 flex items-center justify-center w-12 h-12 rounded-full border-4 border-white
                        ${step.completed ? 'bg-[#a97456] text-white' : 'bg-gray-200 text-gray-400'}
                        ${step.active ? 'ring-4 ring-[#a97456]/20' : ''}
                      `}>
                        <Icon size={20} />
                      </div>

                      {/* Content */}
                      <div className="flex-1 pt-2">
                        <div className="flex items-center justify-between">
                          <h3 className={`font-semibold ${step.completed ? 'text-gray-800' : 'text-gray-400'}`}>
                            {step.label}
                          </h3>
                          {step.time && (
                            <span className="text-sm text-gray-500">{formatDate(step.time)}</span>
                          )}
                        </div>
                        {step.active && !step.completed && (
                          <p className="text-sm text-gray-500 mt-1">In progress...</p>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        )}

        {/* Cancelled Status */}
        {isCancelled && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
            <div className="flex items-center gap-3 text-red-600">
              <div className="text-3xl">❌</div>
              <div>
                <h3 className="font-bold text-lg">Order Cancelled</h3>
                <p className="text-sm text-red-500">This order has been cancelled</p>
              </div>
            </div>
          </div>
        )}

        {/* Delivery Information */}
        {(tracking.courier_name || tracking.tracking_number) && (
          <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
              <Truck className="text-[#a97456]" size={24} />
              Delivery Information
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {tracking.courier_name && (
                <div>
                  <p className="text-sm text-gray-500">Courier Name</p>
                  <p className="font-medium text-gray-800">{tracking.courier_name}</p>
                </div>
              )}
              {tracking.courier_phone && (
                <div>
                  <p className="text-sm text-gray-500">Courier Phone</p>
                  <p className="font-medium text-gray-800">{tracking.courier_phone}</p>
                </div>
              )}
              {tracking.tracking_number && (
                <div>
                  <p className="text-sm text-gray-500">Tracking Number</p>
                  <p className="font-medium text-gray-800">{tracking.tracking_number}</p>
                </div>
              )}
            </div>
          </div>
        )}

        {/* Delivery Address */}
        <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
          <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <MapPin className="text-[#a97456]" size={24} />
            Delivery Address
          </h2>
          <p className="text-gray-700">{tracking.customer_address}</p>
        </div>

        {/* Payment Information */}
        {tracking.payment_received === 1 && (
          <div className="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
            <h2 className="text-xl font-bold text-green-800 mb-4 flex items-center gap-2">
              <CheckCircle className="text-green-600" size={24} />
              Payment Confirmed
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p className="text-sm text-green-600">Amount Received</p>
                <p className="font-bold text-green-800 text-lg">
                  {formatCurrency(tracking.payment_amount || tracking.total)}
                </p>
              </div>
              <div>
                <p className="text-sm text-green-600">Payment Date</p>
                <p className="font-medium text-green-800">{formatDate(tracking.payment_received_at)}</p>
              </div>
              {tracking.payment_notes && (
                <div className="md:col-span-2">
                  <p className="text-sm text-green-600">Notes</p>
                  <p className="text-green-700">{tracking.payment_notes}</p>
                </div>
              )}
            </div>
          </div>
        )}

        {/* Receiver Information */}
        {tracking.receiver_name && (
          <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
              <User className="text-[#a97456]" size={24} />
              Received By
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p className="text-sm text-gray-500">Receiver Name</p>
                <p className="font-medium text-gray-800">{tracking.receiver_name}</p>
              </div>
              {tracking.receiver_relation && (
                <div>
                  <p className="text-sm text-gray-500">Relation</p>
                  <p className="font-medium text-gray-800">{tracking.receiver_relation}</p>
                </div>
              )}
            </div>
          </div>
        )}

        {/* Notes */}
        {tracking.notes && (
          <div className="bg-white rounded-lg shadow-lg p-6">
            <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
              <FileText className="text-[#a97456]" size={24} />
              Order Notes
            </h2>
            <p className="text-gray-700 whitespace-pre-wrap">{tracking.notes}</p>
          </div>
        )}
      </div>
    </div>
  );
}
