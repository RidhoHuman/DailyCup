'use client';

import React, { useState, useEffect } from 'react';
import { api } from '@/lib/api-client';
import type { Order } from '@/types/delivery';
import OrderStatusBadge from '@/components/admin/OrderStatusBadge';

export default function CODApprovalPanel() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    fetchPendingOrders();
    // Auto-refresh every 30 seconds
    const interval = setInterval(fetchPendingOrders, 30000);
    return () => clearInterval(interval);
  }, []);

  const fetchPendingOrders = async () => {
    try {
      const response: any = await api.get('/get_pending_cod_orders.php');
      if (response.success) {
        setOrders(response.orders);
      }
    } catch (error) {
      console.error('Failed to fetch pending orders:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (orderId: number) => {
    if (!confirm('Konfirmasi pesanan COD ini?')) return;
    
    setActionLoading(true);
    try {
      const response: any = await api.post('/admin_confirm_cod.php', {
        order_id: orderId,
        action: 'approve'
      });
      
      if (response.success) {
        alert(`✅ Pesanan disetujui! Kurir: ${response.kurir_name}`);
        fetchPendingOrders();
        setSelectedOrder(null);
      }
    } catch (error: any) {
      alert('❌ Gagal: ' + (error.message || 'Unknown error'));
    } finally {
      setActionLoading(false);
    }
  };

  const handleReject = async (orderId: number, isFraud: boolean = false) => {
    const reason = prompt('Alasan reject:');
    if (!reason) return;
    
    setActionLoading(true);
    try {
      const response: any = await api.post('/admin_confirm_cod.php', {
        order_id: orderId,
        action: 'reject',
        reason,
        is_fraud: isFraud
      });
      
      if (response.success) {
        alert(isFraud ? '✅ Pesanan ditolak & user diblacklist!' : '✅ Pesanan ditolak!');
        fetchPendingOrders();
        setSelectedOrder(null);
      }
    } catch (error: any) {
      alert('❌ Gagal: ' + (error.message || 'Unknown error'));
    } finally {
      setActionLoading(false);
    }
  };

  const getRiskBadge = (order: Order) => {
    const colors = {
      low: 'bg-green-100 text-green-800',
      medium: 'bg-yellow-100 text-yellow-800',
      high: 'bg-red-100 text-red-800'
    };
    
    return (
      <span className={`px-2 py-1 text-xs font-medium rounded ${colors[order.risk_level || 'low']}`}>
        Risk: {order.risk_level?.toUpperCase()}
      </span>
    );
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading COD orders...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-900">COD Order Approval</h1>
          <p className="mt-2 text-gray-600">
            Review dan konfirmasi pesanan COD sebelum ditugaskan ke kurir
          </p>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div className="bg-white rounded-lg shadow p-6">
            <div className="text-sm font-medium text-gray-600">Pending COD Orders</div>
            <div className="mt-2 text-3xl font-bold text-blue-600">{orders.length}</div>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <div className="text-sm font-medium text-gray-600">High Risk</div>
            <div className="mt-2 text-3xl font-bold text-red-600">
              {orders.filter(o => o.risk_level === 'high').length}
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <div className="text-sm font-medium text-gray-600">Expiring Soon</div>
            <div className="mt-2 text-3xl font-bold text-orange-600">
              {orders.filter(o => o.is_expiring_soon).length}
            </div>
          </div>
        </div>

        {/* Orders Table */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          {orders.length === 0 ? (
            <div className="text-center py-12">
              <div className="text-6xl mb-4">🎉</div>
              <h3 className="text-lg font-medium text-gray-900">Tidak ada COD pending!</h3>
              <p className="mt-2 text-gray-600">Semua pesanan COD sudah dikonfirmasi</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Order Info
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Customer
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Amount & Distance
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Risk Level
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Expires
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {orders.map((order) => (
                    <tr key={order.id} className={order.is_expiring_soon ? 'bg-orange-50' : ''}>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">
                          #{order.order_number}
                        </div>
                        <div className="text-sm text-gray-500">
                          {new Date(order.created_at).toLocaleString('id-ID')}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="text-sm font-medium text-gray-900">
                          {order.customer_name}
                        </div>
                        <div className="text-sm text-gray-500">{order.customer_phone}</div>
                        <div className="mt-1 flex items-center gap-2">
                          <span className="text-xs text-gray-500">
                            Trust: {order.trust_score}
                          </span>
                          {order.is_verified_user && (
                            <span className="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
                              Verified
                            </span>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-bold text-gray-900">
                          Rp {order.final_amount.toLocaleString('id-ID')}
                        </div>
                        <div className="text-sm text-gray-500">
                          📍 {order.delivery_distance?.toFixed(1)} KM
                        </div>
                        <div className="text-xs text-gray-400">
                          Limit: Rp {order.cod_amount_limit?.toLocaleString('id-ID')}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {getRiskBadge(order)}
                        {(order.recent_cancellations ?? 0) > 0 && (
                          <div className="mt-1 text-xs text-red-600">
                            ⚠️ {order.recent_cancellations} cancel (30d)
                          </div>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className={`text-sm font-medium ${
                          order.is_expired ? 'text-red-600' :
                          order.is_expiring_soon ? 'text-orange-600' :
                          'text-gray-900'
                        }`}>
                          {order.is_expired ? '⏰ EXPIRED' :
                           order.minutes_remaining ? `${order.minutes_remaining} min` : 'N/A'}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div className="flex gap-2">
                          <button
                            onClick={() => handleApprove(order.id)}
                            disabled={actionLoading}
                            className="px-3 py-1.5 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
                          >
                            ✅ Approve
                          </button>
                          <button
                            onClick={() => handleReject(order.id, false)}
                            disabled={actionLoading}
                            className="px-3 py-1.5 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
                          >
                            ❌ Reject
                          </button>
                          {order.risk_level === 'high' && (
                            <button
                              onClick={() => handleReject(order.id, true)}
                              disabled={actionLoading}
                              className="px-3 py-1.5 bg-black text-white rounded hover:bg-gray-800 disabled:opacity-50"
                              title="Reject & Blacklist"
                            >
                              🚫 Fraud
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
