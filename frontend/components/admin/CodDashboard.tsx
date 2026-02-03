'use client';

import { useState, useEffect } from 'react';
import apiClient from '@/lib/api-client';
import CodTrackingCard from '@/components/cod/CodTrackingCard';

interface CodOrder {
  order_number: string;
  customer_name: string;
  customer_phone: string;
  customer_address: string;
  total: number;
  status: string;
  cod_status?: string;
  payment_received: boolean;
  created_at: string;
}

interface ApiResponse {
  success: boolean;
  orders?: CodOrder[];
  message?: string;
}

export default function CodDashboard() {
  const [orders, setOrders] = useState<CodOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState<'all' | 'pending' | 'active' | 'completed'>('all');
  const [selectedOrder, setSelectedOrder] = useState<string | null>(null);

  useEffect(() => {
    loadOrders();
  }, []);

  const loadOrders = async () => {
    try {
      setLoading(true);
      // Fetch all COD orders
      const response = await apiClient.get('/orders.php?payment_method=cod') as ApiResponse;
      
      if (response.success && response.orders) {
        setOrders(response.orders);
      }
    } catch (err) {
      console.error('Error loading COD orders:', err);
    } finally {
      setLoading(false);
    }
  };

  const filteredOrders = orders.filter(order => {
    if (filter === 'pending') return order.cod_status === 'pending' || order.cod_status === 'confirmed';
    if (filter === 'active') return ['packed', 'out_for_delivery', 'delivered'].includes(order.cod_status || '');
    if (filter === 'completed') return order.payment_received;
    return true;
  });

  const stats = {
    total: orders.length,
    pending: orders.filter(o => o.cod_status === 'pending' || o.cod_status === 'confirmed').length,
    active: orders.filter(o => ['packed', 'out_for_delivery', 'delivered'].includes(o.cod_status || '')).length,
    completed: orders.filter(o => o.payment_received).length,
    totalRevenue: orders.reduce((sum, o) => o.payment_received ? sum + Number(o.total) : sum, 0),
    pendingRevenue: orders.reduce((sum, o) => !o.payment_received ? sum + Number(o.total) : sum, 0)
  };

  if (loading) {
    return (
      <div className="p-6">
        <div className="animate-pulse space-y-4">
          <div className="h-8 bg-gray-200 rounded w-1/4"></div>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            {[1, 2, 3, 4].map(i => (
              <div key={i} className="h-24 bg-gray-200 rounded"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold text-gray-900">💰 COD Management</h1>
        <p className="text-gray-600 mt-1">Monitor dan kelola pesanan Cash on Delivery</p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg shadow p-6">
          <div className="text-sm text-gray-600 mb-1">Total Orders</div>
          <div className="text-3xl font-bold text-gray-900">{stats.total}</div>
        </div>
        <div className="bg-yellow-50 rounded-lg shadow p-6">
          <div className="text-sm text-yellow-800 mb-1">Pending</div>
          <div className="text-3xl font-bold text-yellow-900">{stats.pending}</div>
          <div className="text-xs text-yellow-600 mt-1">
            Rp {stats.pendingRevenue.toLocaleString('id-ID')}
          </div>
        </div>
        <div className="bg-blue-50 rounded-lg shadow p-6">
          <div className="text-sm text-blue-800 mb-1">Dalam Proses</div>
          <div className="text-3xl font-bold text-blue-900">{stats.active}</div>
        </div>
        <div className="bg-green-50 rounded-lg shadow p-6">
          <div className="text-sm text-green-800 mb-1">Completed</div>
          <div className="text-3xl font-bold text-green-900">{stats.completed}</div>
          <div className="text-xs text-green-600 mt-1">
            Rp {stats.totalRevenue.toLocaleString('id-ID')}
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4">
        <div className="flex gap-2 flex-wrap">
          <button
            onClick={() => setFilter('all')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              filter === 'all'
                ? 'bg-[#a97456] text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Semua ({stats.total})
          </button>
          <button
            onClick={() => setFilter('pending')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              filter === 'pending'
                ? 'bg-yellow-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Pending ({stats.pending})
          </button>
          <button
            onClick={() => setFilter('active')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              filter === 'active'
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Dalam Proses ({stats.active})
          </button>
          <button
            onClick={() => setFilter('completed')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              filter === 'completed'
                ? 'bg-green-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Completed ({stats.completed})
          </button>
        </div>
      </div>

      {/* Orders List */}
      {filteredOrders.length === 0 ? (
        <div className="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
          <p className="text-gray-600">Tidak ada pesanan untuk filter ini</p>
        </div>
      ) : (
        <div className="space-y-4">
          {filteredOrders.map((order) => (
            <div
              key={order.order_number}
              className="bg-white rounded-lg shadow hover:shadow-md transition-shadow"
            >
              <div
                className="p-6 cursor-pointer"
                onClick={() =>
                  setSelectedOrder(selectedOrder === order.order_number ? null : order.order_number)
                }
              >
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                      <h3 className="font-bold text-gray-900">{order.order_number}</h3>
                      <span
                        className={`px-2 py-1 rounded text-xs font-medium ${
                          order.payment_received
                            ? 'bg-green-100 text-green-800'
                            : 'bg-yellow-100 text-yellow-800'
                        }`}
                      >
                        {order.payment_received ? '✅ Lunas' : '⏳ Belum Bayar'}
                      </span>
                    </div>
                    <div className="text-sm text-gray-600 space-y-1">
                      <p>👤 {order.customer_name}</p>
                      <p>📞 {order.customer_phone}</p>
                      <p>📍 {order.customer_address}</p>
                      <p className="text-xs text-gray-500">
                        🕐 {new Date(order.created_at).toLocaleString('id-ID')}
                      </p>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="text-2xl font-bold text-[#a97456]">
                      Rp {Number(order.total).toLocaleString('id-ID')}
                    </div>
                    <button
                      className="mt-2 text-sm text-blue-600 hover:text-blue-700 font-medium"
                      onClick={(e) => {
                        e.stopPropagation();
                        setSelectedOrder(selectedOrder === order.order_number ? null : order.order_number);
                      }}
                    >
                      {selectedOrder === order.order_number ? '▲ Sembunyikan' : '▼ Lihat Detail'}
                    </button>
                  </div>
                </div>
              </div>

              {/* Tracking Details */}
              {selectedOrder === order.order_number && (
                <div className="border-t p-6 bg-gray-50">
                  <CodTrackingCard orderId={order.order_number} isAdmin={true} />
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
