'use client';

import React, { useState, useEffect } from 'react';
import { useParams } from 'next/navigation';
import { api } from '@/lib/api-client';
import type { Order, OrderItem, OrderStatusLog, DeliveryHistory, Kurir } from '@/types/delivery';
import OrderStatusBadge from '@/components/admin/OrderStatusBadge';
import DeliveryTimeline from '@/components/admin/DeliveryTimeline';
import Link from 'next/link';

export default function OrderDetailPage() {
  const params = useParams();
  const orderId = params?.id as string;
  
  const [order, setOrder] = useState<Order | null>(null);
  const [items, setItems] = useState<OrderItem[]>([]);
  const [history, setHistory] = useState<OrderStatusLog[]>([]);
  const [deliveryHistory, setDeliveryHistory] = useState<DeliveryHistory[]>([]);
  const [kurirLocation, setKurirLocation] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [availableKurirs, setAvailableKurirs] = useState<Kurir[]>([]);
  const [showAssignModal, setShowAssignModal] = useState(false);
  const [assignLoading, setAssignLoading] = useState(false);

  useEffect(() => {
    if (orderId) {
      fetchOrderDetail();
      fetchAvailableKurirs();
      // Auto-refresh every 15 seconds
      const interval = setInterval(fetchOrderDetail, 15000);
      return () => clearInterval(interval);
    }
  }, [orderId]);

  const fetchOrderDetail = async () => {
    try {
      const response: any = await api.get(`/get_order_detail.php?order_id=${orderId}`);
      if (response.success) {
        setOrder(response.order);
        setItems(response.items);
        setHistory(response.history);
        setDeliveryHistory(response.delivery_history || []);
        setKurirLocation(response.kurir_location);
      }
    } catch (error) {
      console.error('Failed to fetch order detail:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchAvailableKurirs = async () => {
    try {
      const response: any = await api.get('/get_kurir_list.php?status=available');
      if (response.success) {
        setAvailableKurirs(response.kurirs);
      }
    } catch (error) {
      console.error('Failed to fetch kurirs:', error);
    }
  };

  const handleAssignKurir = async (kurirId: number) => {
    if (!confirm('Assign kurir ini ke pesanan?')) return;
    
    setAssignLoading(true);
    try {
      const response: any = await api.post('/manual_assign_kurir.php', {
        order_id: order?.id,
        kurir_id: kurirId,
        notes: 'Manually assigned by admin'
      });
      
      if (response.success) {
        alert('✅ Kurir berhasil ditugaskan!');
        setShowAssignModal(false);
        fetchOrderDetail();
        fetchAvailableKurirs();
      }
    } catch (error: any) {
      alert('❌ Gagal: ' + (error.message || 'Unknown error'));
    } finally {
      setAssignLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading order...</p>
        </div>
      </div>
    );
  }

  if (!order) {
    return (
      <div className="min-h-screen bg-gray-50 p-6">
        <div className="max-w-4xl mx-auto text-center">
          <div className="text-6xl mb-4">❌</div>
          <h1 className="text-2xl font-bold text-gray-900">Order Not Found</h1>
          <Link href="/admin/deliveries" className="mt-4 inline-block text-blue-600 hover:underline">
            ← Back to Deliveries
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-6xl mx-auto">
        {/* Back Button */}
        <Link 
          href="/admin/deliveries"
          className="inline-flex items-center gap-2 text-blue-600 hover:underline mb-6"
        >
          ← Back to Deliveries
        </Link>

        {/* Header */}
        <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">
                Order #{order.order_number}
              </h1>
              <p className="mt-1 text-gray-600">
                Created: {new Date(order.created_at).toLocaleString('id-ID')}
              </p>
            </div>
            <OrderStatusBadge status={order.status} size="lg" />
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left Column */}
          <div className="lg:col-span-2 space-y-6">
            {/* Customer Info */}
            <div className="bg-white rounded-lg shadow p-6">
              <h2 className="text-lg font-bold text-gray-900 mb-4">👤 Customer Information</h2>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <div className="text-sm text-gray-500">Name</div>
                  <div className="font-medium text-gray-900">{order.customer_name}</div>
                </div>
                <div>
                  <div className="text-sm text-gray-500">Phone</div>
                  <div className="font-medium text-gray-900">{order.customer_phone}</div>
                </div>
                <div className="col-span-2">
                  <div className="text-sm text-gray-500">Email</div>
                  <div className="font-medium text-gray-900">{order.customer_email}</div>
                </div>
                <div>
                  <div className="text-sm text-gray-500">Trust Score</div>
                  <div className="font-medium text-gray-900">
                    {order.trust_score}
                    {order.is_verified_user && (
                      <span className="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
                        Verified
                      </span>
                    )}
                  </div>
                </div>
                <div>
                  <div className="text-sm text-gray-500">Total Orders</div>
                  <div className="font-medium text-gray-900">{order.total_successful_orders || 0}</div>
                </div>
              </div>
              {order.delivery_address && (
                <div className="mt-4">
                  <div className="text-sm text-gray-500">Delivery Address</div>
                  <div className="font-medium text-gray-900">{order.delivery_address}</div>

                  {/* Geocode status and controls */}
                  <div className="mt-3 flex items-center gap-3">
                    <div className="text-xs text-gray-500">Geocode status:</div>
                    <div className={`text-sm font-medium ${order.geocode_status === 'ok' ? 'text-green-600' : order.geocode_status === 'failed' ? 'text-red-600' : 'text-yellow-600'}`}>
                      {order.geocode_status || 'pending'}
                    </div>
                    {order.geocode_status === 'failed' && order.geocode_error && (
                      <div className="text-xs text-red-600">({order.geocode_error.substring(0, 120)})</div>
                    )}

                    <button
                      onClick={async () => {
                        if (!confirm('Run geocode now for this order?')) return;
                        try {
                          const res: any = await api.get(`/geocode_order.php?order_id=${order.id}`);
                          if (res.success) {
                            alert('Geocoded: ' + res.lat + ', ' + res.lon);
                            fetchOrderDetail();
                          } else {
                            alert('Geocode failed: ' + (res.message || JSON.stringify(res.raw)));
                            fetchOrderDetail();
                          }
                        } catch (err: any) {
                          alert('Request failed: ' + (err.message || err));
                        }
                      }}
                      className="ml-auto px-3 py-1 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700"
                    >
                      Geocode Now
                    </button>
                  </div>

                </div>
              )}
            </div>

            {/* Order Items */}
            <div className="bg-white rounded-lg shadow p-6">
              <h2 className="text-lg font-bold text-gray-900 mb-4">🛍️ Order Items</h2>
              <div className="space-y-3">
                {items.map((item) => (
                  <div key={item.id} className="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div className="flex-1">
                      <div className="font-medium text-gray-900">{item.product_name}</div>
                      <div className="text-sm text-gray-500">
                        {item.size && `Size: ${item.size}`}
                        {item.temperature && ` • Temp: ${item.temperature}`}
                        {item.notes && ` • Note: ${item.notes}`}
                      </div>
                      {item.addons_parsed && item.addons_parsed.length > 0 && (
                        <div className="text-xs text-gray-500 mt-1">
                          + {item.addons_parsed.map(a => a.name).join(', ')}
                        </div>
                      )}
                    </div>
                    <div className="text-right">
                      <div className="font-medium text-gray-900">x{item.quantity}</div>
                      <div className="text-sm text-gray-600">
                        Rp {item.subtotal.toLocaleString('id-ID')}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              <div className="mt-4 pt-4 border-t">
                <div className="flex justify-between items-center">
                  <span className="text-lg font-bold text-gray-900">Total</span>
                  <span className="text-2xl font-bold text-blue-600">
                    Rp {order.final_amount.toLocaleString('id-ID')}
                  </span>
                </div>
              </div>
            </div>

            {/* Timeline */}
            <div className="bg-white rounded-lg shadow p-6">
              <h2 className="text-lg font-bold text-gray-900 mb-4">📊 Order Timeline</h2>
              <DeliveryTimeline history={history} currentStatus={order.status} />
            </div>
          </div>

          {/* Right Column */}
          <div className="space-y-6">
            {/* Payment Info */}
            <div className="bg-white rounded-lg shadow p-6">
              <h2 className="text-lg font-bold text-gray-900 mb-4">💳 Payment</h2>
              <div className="space-y-3">
                <div>
                  <div className="text-sm text-gray-500">Method</div>
                  <div className="font-bold text-gray-900 uppercase">
                    {order.payment_method === 'cod' ? '💵 COD' : '💳 Online'}
                  </div>
                </div>
                <div>
                  <div className="text-sm text-gray-500">Status</div>
                  <span className={`inline-block px-2 py-1 text-xs font-medium rounded ${
                    order.payment_status === 'paid' ? 'bg-green-100 text-green-800' :
                    order.payment_status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-red-100 text-red-800'
                  }`}>
                    {order.payment_status.toUpperCase()}
                  </span>
                </div>
              </div>
            </div>

            {/* Delivery Photo Proof */}
            {(order.kurir_departure_photo || order.kurir_arrival_photo) && (
              <div className="bg-white rounded-lg shadow p-6">
                <h2 className="text-lg font-bold text-gray-900 mb-4">📸 Bukti Pengiriman</h2>
                <div className="space-y-4">
                  {order.kurir_departure_photo && (
                    <div>
                      <div className="text-sm text-gray-500 mb-2 font-medium">Foto Keberangkatan</div>
                      <div className="rounded-lg overflow-hidden border border-gray-200">
                        <img 
                          src={order.kurir_departure_photo?.startsWith('http') 
                            ? order.kurir_departure_photo 
                            : `http://localhost/DailyCup/webapp/backend/${order.kurir_departure_photo}`} 
                          alt="Foto keberangkatan kurir"
                          className="w-full h-auto max-h-64 object-cover cursor-pointer hover:opacity-90 transition-opacity"
                          onClick={() => window.open(
                            order.kurir_departure_photo?.startsWith('http') 
                              ? order.kurir_departure_photo 
                              : `http://localhost/DailyCup/webapp/backend/${order.kurir_departure_photo}`,
                            '_blank'
                          )}
                        />
                      </div>
                      <p className="text-xs text-gray-400 mt-1">Klik foto untuk memperbesar</p>
                    </div>
                  )}
                  {order.kurir_arrival_photo && (
                    <div>
                      <div className="text-sm text-gray-500 mb-2 font-medium">Foto Tiba di Tujuan</div>
                      <div className="rounded-lg overflow-hidden border border-gray-200">
                        <img 
                          src={order.kurir_arrival_photo?.startsWith('http') 
                            ? order.kurir_arrival_photo 
                            : `http://localhost/DailyCup/webapp/backend/${order.kurir_arrival_photo}`} 
                          alt="Foto sampai tujuan"
                          className="w-full h-auto max-h-64 object-cover cursor-pointer hover:opacity-90 transition-opacity"
                          onClick={() => window.open(
                            order.kurir_arrival_photo?.startsWith('http') 
                              ? order.kurir_arrival_photo 
                              : `http://localhost/DailyCup/webapp/backend/${order.kurir_arrival_photo}`,
                            '_blank'
                          )}
                        />
                      </div>
                      <p className="text-xs text-gray-400 mt-1">Klik foto untuk memperbesar</p>
                    </div>
                  )}
                  {order.actual_delivery_time && (
                    <div className="bg-blue-50 rounded-lg p-3">
                      <div className="text-sm text-blue-600 font-medium">
                        ⏱️ Waktu Pengiriman Aktual: {order.actual_delivery_time} menit
                      </div>
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* Kurir Info */}
            {order.kurir_id ? (
              <div className="bg-white rounded-lg shadow p-6">
                <h2 className="text-lg font-bold text-gray-900 mb-4">🏍️ Kurir</h2>
                <div className="space-y-3">
                  <div>
                    <div className="text-sm text-gray-500">Name</div>
                    <div className="font-medium text-gray-900">{order.kurir_name}</div>
                  </div>
                  <div>
                    <div className="text-sm text-gray-500">Phone</div>
                    <div className="font-medium text-gray-900">{order.kurir_phone}</div>
                  </div>
                  <div>
                    <div className="text-sm text-gray-500">Vehicle</div>
                    <div className="font-medium text-gray-900">
                      {order.vehicle_type?.toUpperCase()}
                    </div>
                  </div>
                  {kurirLocation && (
                    <button
                      onClick={() => window.open(
                        `https://maps.google.com/?q=${kurirLocation.latitude},${kurirLocation.longitude}`,
                        '_blank'
                      )}
                      className="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                      📍 View on Map
                    </button>
                  )}
                </div>
              </div>
            ) : (
              <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <h2 className="text-lg font-bold text-yellow-800 mb-2">⚠️ No Kurir Assigned</h2>
                <p className="text-sm text-yellow-700 mb-4">
                  Pesanan ini belum ditugaskan ke kurir
                </p>
                <button
                  onClick={() => setShowAssignModal(true)}
                  className="w-full px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700"
                >
                  Assign Kurir
                </button>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Assign Kurir Modal */}
      {showAssignModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 className="text-xl font-bold text-gray-900 mb-4">Assign Kurir</h3>
            
            <div className="space-y-3 max-h-96 overflow-y-auto">
              {availableKurirs.map((kurir) => (
                <button
                  key={kurir.id}
                  onClick={() => handleAssignKurir(kurir.id)}
                  disabled={assignLoading}
                  className="w-full flex items-center justify-between p-3 border rounded hover:bg-gray-50 disabled:opacity-50"
                >
                  <div className="text-left">
                    <div className="font-medium text-gray-900">{kurir.name}</div>
                    <div className="text-sm text-gray-500">{kurir.phone}</div>
                  </div>
                  <span className="text-green-600">✅</span>
                </button>
              ))}
              
              {availableKurirs.length === 0 && (
                <div className="text-center py-8 text-gray-500">
                  Tidak ada kurir available
                </div>
              )}
            </div>

            <button
              onClick={() => setShowAssignModal(false)}
              className="mt-4 w-full px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
            >
              Cancel
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
