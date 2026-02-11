'use client';

import React, { useState, useEffect } from 'react';
import { api } from '@/lib/api-client';
import type { Order } from '@/types/delivery';
import OrderStatusBadge from '@/components/admin/OrderStatusBadge';
import Link from 'next/link';

export default function DeliveryMonitoringDashboard() {
  const [deliveries, setDeliveries] = useState<Order[]>([]);
  const [stats, setStats] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');
  const [kurirFilter, setKurirFilter] = useState('');

  useEffect(() => {
    fetchDeliveries();
    // Auto-refresh every 10 seconds for real-time tracking
    const interval = setInterval(fetchDeliveries, 10000);
    return () => clearInterval(interval);
  }, [statusFilter, kurirFilter]);

  const fetchDeliveries = async () => {
    try {
      const params = new URLSearchParams();
      if (statusFilter) params.append('status', statusFilter);
      if (kurirFilter) params.append('kurir_id', kurirFilter);
      
      const response: any = await api.get(`/get_delivery_tracking.php?${params.toString()}`);
      if (response.success) {
        setDeliveries(response.deliveries);
        setStats(response.stats);
      }
    } catch (error) {
      console.error('Failed to fetch deliveries:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusProgress = (status: string) => {
    const progress = {
      confirmed: 20,
      processing: 40,
      ready: 60,
      delivering: 80,
      completed: 100
    };
    return progress[status as keyof typeof progress] || 0;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading deliveries...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-900">📦 Delivery Monitoring</h1>
          <p className="mt-2 text-gray-600">Real-time tracking semua pengiriman aktif</p>
        </div>

        {/* Stats Cards */}
        {stats && (
          <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div className="bg-white rounded-lg shadow p-4">
              <div className="text-xs font-medium text-gray-600 uppercase">Total Active</div>
              <div className="mt-2 text-2xl font-bold text-gray-900">{stats.total_active}</div>
            </div>
            <div className="bg-blue-50 rounded-lg shadow p-4">
              <div className="text-xs font-medium text-blue-600 uppercase">Confirmed</div>
              <div className="mt-2 text-2xl font-bold text-blue-600">{stats.confirmed}</div>
            </div>
            <div className="bg-purple-50 rounded-lg shadow p-4">
              <div className="text-xs font-medium text-purple-600 uppercase">Processing</div>
              <div className="mt-2 text-2xl font-bold text-purple-600">{stats.processing}</div>
            </div>
            <div className="bg-indigo-50 rounded-lg shadow p-4">
              <div className="text-xs font-medium text-indigo-600 uppercase">Ready</div>
              <div className="mt-2 text-2xl font-bold text-indigo-600">{stats.ready}</div>
            </div>
            <div className="bg-orange-50 rounded-lg shadow p-4">
              <div className="text-xs font-medium text-orange-600 uppercase">Delivering</div>
              <div className="mt-2 text-2xl font-bold text-orange-600">{stats.delivering}</div>
            </div>
          </div>
        )}

        {/* Filters */}
        <div className="bg-white rounded-lg shadow p-4 mb-6">
          <div className="flex gap-4">
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-4 py-2 border rounded-lg"
            >
              <option value="">All Status</option>
              <option value="confirmed">Confirmed</option>
              <option value="processing">Processing</option>
              <option value="ready">Ready</option>
              <option value="delivering">Delivering</option>
            </select>

            <button
              onClick={fetchDeliveries}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              🔄 Refresh
            </button>

            <div className="ml-auto text-sm text-gray-500 py-2">
              Auto-refresh: 10s ⚡
            </div>
          </div>
        </div>

        {/* Deliveries Grid */}
        {deliveries.length === 0 ? (
          <div className="bg-white rounded-lg shadow p-12 text-center">
            <div className="text-6xl mb-4">🎉</div>
            <h3 className="text-lg font-medium text-gray-900">No Active Deliveries</h3>
            <p className="mt-2 text-gray-600">All deliveries completed!</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {deliveries.map((delivery) => (
              <div key={delivery.id} className="bg-white rounded-lg shadow-lg overflow-hidden">
                {/* Card Header */}
                <div className="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <h3 className="text-lg font-bold">#{delivery.order_number}</h3>
                      <p className="text-sm text-blue-100">{delivery.customer_name}</p>
                    </div>
                    <OrderStatusBadge status={delivery.status} size="lg" />
                  </div>
                </div>

                {/* Progress Bar */}
                <div className="px-4 pt-4">
                  <div className="relative pt-1">
                    <div className="flex mb-2 items-center justify-between">
                      <div className="text-xs font-semibold text-blue-600">
                        Progress
                      </div>
                      <div className="text-xs font-semibold text-blue-600">
                        {delivery.progress}%
                      </div>
                    </div>
                    <div className="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-100">
                      <div
                        style={{ width: `${delivery.progress}%` }}
                        className="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-600 transition-all duration-500"
                      />
                    </div>
                  </div>
                </div>

                {/* Card Body */}
                <div className="px-4 pb-4 space-y-3">
                  {/* Kurir Info */}
                  {delivery.kurir_name && (
                    <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                      <div className="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">
                        {delivery.kurir_name[0]}
                      </div>
                      <div>
                        <div className="font-medium text-gray-900">{delivery.kurir_name}</div>
                        <div className="text-sm text-gray-500">{delivery.kurir_phone}</div>
                      </div>
                      <div className="ml-auto">
                        {delivery.vehicle_type && (
                          <span className="text-2xl">
                            {delivery.vehicle_type === 'motor' ? '🏍️' : 
                             delivery.vehicle_type === 'mobil' ? '🚗' : '🚲'}
                          </span>
                        )}
                      </div>
                    </div>
                  )}

                  {/* Order Details */}
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <div className="text-xs text-gray-500">Amount</div>
                      <div className="font-bold text-gray-900">
                        Rp {delivery.final_amount.toLocaleString('id-ID')}
                      </div>
                    </div>
                    <div>
                      <div className="text-xs text-gray-500">Distance</div>
                      <div className="font-medium text-gray-900">
                        {delivery.delivery_distance?.toFixed(1)} KM
                      </div>
                    </div>
                    <div>
                      <div className="text-xs text-gray-500">Payment</div>
                      <div className="font-medium text-gray-900 uppercase">
                        {delivery.payment_method === 'cod' ? (
                          <span className="text-orange-600">💵 COD</span>
                        ) : (
                          <span className="text-green-600">💳 Online</span>
                        )}
                      </div>
                    </div>
                    <div>
                      <div className="text-xs text-gray-500">Time</div>
                      <div className="font-medium text-gray-900">
                        {delivery.minutes_since_assigned 
                          ? `${delivery.minutes_since_assigned} min ago`
                          : 'Just now'}
                      </div>
                    </div>
                  </div>

                  {/* Warning */}
                  {delivery.warning && (
                    <div className="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                      <div className="flex items-center gap-2">
                        <span className="text-yellow-600">⚠️</span>
                        <span className="text-sm text-yellow-800">{delivery.warning}</span>
                      </div>
                    </div>
                  )}

                  {/* Timestamps */}
                  <div className="text-xs text-gray-500 space-y-1">
                    {delivery.assigned_at && (
                      <div>Assigned: {new Date(delivery.assigned_at).toLocaleTimeString('id-ID')}</div>
                    )}
                    {delivery.pickup_time && (
                      <div>Picked up: {new Date(delivery.pickup_time).toLocaleTimeString('id-ID')}</div>
                    )}
                  </div>

                  {/* Action Button */}
                  <Link
                    href={`/admin/orders/${delivery.id}`}
                    className="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                  >
                    View Details
                  </Link>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
