'use client';

import { useState, useEffect } from 'react';
import apiClient from '@/lib/api-client';

interface CodTracking {
  id: number;
  order_id: string;
  courier_name?: string;
  courier_phone?: string;
  tracking_number?: string;
  status: 'pending' | 'confirmed' | 'packed' | 'out_for_delivery' | 'delivered' | 'payment_received' | 'cancelled';
  payment_received: boolean;
  payment_received_at?: string;
  payment_amount?: number;
  receiver_name?: string;
  receiver_relation?: string;
  notes?: string;
  confirmed_at?: string;
  packed_at?: string;
  out_for_delivery_at?: string;
  delivered_at?: string;
  created_at: string;
  updated_at: string;
}

interface StatusHistory {
  id: number;
  order_id: string;
  status: string;
  notes?: string;
  created_at: string;
}

interface CodTrackingProps {
  orderId: string;
  isAdmin?: boolean;
}

const STATUS_LABELS = {
  pending: 'Menunggu Konfirmasi',
  confirmed: 'Dikonfirmasi',
  packed: 'Dikemas',
  out_for_delivery: 'Dalam Pengiriman',
  delivered: 'Terkirim',
  payment_received: 'Pembayaran Diterima',
  cancelled: 'Dibatalkan'
};

const STATUS_COLORS = {
  pending: 'bg-gray-100 text-gray-800',
  confirmed: 'bg-blue-100 text-blue-800',
  packed: 'bg-indigo-100 text-indigo-800',
  out_for_delivery: 'bg-yellow-100 text-yellow-800',
  delivered: 'bg-green-100 text-green-800',
  payment_received: 'bg-emerald-100 text-emerald-800',
  cancelled: 'bg-red-100 text-red-800'
};

const STATUS_ICONS = {
  pending: '⏳',
  confirmed: '✅',
  packed: '📦',
  out_for_delivery: '🚚',
  delivered: '🏠',
  payment_received: '💰',
  cancelled: '❌'
};

export default function CodTrackingCard({ orderId, isAdmin = false }: CodTrackingProps) {
  const [tracking, setTracking] = useState<CodTracking | null>(null);
  const [history, setHistory] = useState<StatusHistory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    loadTracking();
  }, [orderId]);

  const loadTracking = async () => {
    try {
      setLoading(true);
      const response = await apiClient.get<{
        success: boolean;
        tracking: CodTracking | null;
        history?: StatusHistory[];
        message?: string;
      }>(`/cod_tracking.php?order_id=${orderId}`);

      if (response.success) {
        setTracking(response.tracking);
        setHistory(response.history || []);
      } else {
        setError(response.message || 'Gagal memuat tracking');
      }
    } catch (err: any) {
      console.error('Error loading COD tracking:', err);
      setError(err.response?.data?.message || 'Gagal memuat tracking');
    } finally {
      setLoading(false);
    }
  };

  const updateStatus = async (newStatus: string, notes?: string) => {
    try {
      const response = await apiClient.post('/cod_tracking.php', {
        order_id: orderId,
        action: 'update_status',
        status: newStatus,
        notes
      }) as { success: boolean; message?: string };

      if (response.success) {
        await loadTracking(); // Reload tracking info
        alert('Status berhasil diperbarui');
      }
    } catch (err: any) {
      alert(err.response?.data?.message || 'Gagal memperbarui status');
    }
  };

  const confirmPayment = async (paymentAmount?: number, receiverName?: string) => {
    try {
      const response = await apiClient.post('/cod_tracking.php', {
        order_id: orderId,
        action: 'confirm_payment',
        payment_amount: paymentAmount,
        receiver_name: receiverName
      }) as { success: boolean; message?: string };

      if (response.success) {
        await loadTracking();
        alert('Pembayaran berhasil dikonfirmasi');
      }
    } catch (err: any) {
      alert(err.response?.data?.message || 'Gagal konfirmasi pembayaran');
    }
  };

  if (loading) {
    return (
      <div className="bg-white rounded-lg shadow p-6">
        <div className="animate-pulse">
          <div className="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
          <div className="space-y-3">
            <div className="h-4 bg-gray-200 rounded"></div>
            <div className="h-4 bg-gray-200 rounded w-5/6"></div>
          </div>
        </div>
      </div>
    );
  }

  if (error && !tracking) {
    return (
      <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <p className="text-yellow-800">⚠️ {error}</p>
      </div>
    );
  }

  if (!tracking) {
    return (
      <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <p className="text-gray-600">📦 Tracking info belum tersedia. Admin akan segera memproses pesanan Anda.</p>
      </div>
    );
  }

  const currentStatus = tracking.status;
  const statusColor = STATUS_COLORS[currentStatus] || STATUS_COLORS.pending;
  const statusLabel = STATUS_LABELS[currentStatus] || currentStatus;
  const statusIcon = STATUS_ICONS[currentStatus] || '📋';

  return (
    <div className="bg-white rounded-lg shadow-lg overflow-hidden">
      {/* Header */}
      <div className={`${statusColor} px-6 py-4 border-b`}>
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-lg font-bold flex items-center gap-2">
              <span className="text-2xl">{statusIcon}</span>
              Status Pengiriman COD
            </h3>
            <p className="text-sm opacity-75 mt-1">Order: {orderId}</p>
          </div>
          <div className="text-right">
            <div className="text-xl font-bold">{statusLabel}</div>
            {tracking.tracking_number && (
              <div className="text-sm opacity-75">Resi: {tracking.tracking_number}</div>
            )}
          </div>
        </div>
      </div>

      {/* Tracking Details */}
      <div className="px-6 py-4 space-y-4">
        {/* Courier Info */}
        {(tracking.courier_name || tracking.courier_phone) && (
          <div className="bg-gray-50 rounded-lg p-4">
            <h4 className="font-semibold text-gray-700 mb-2">🚚 Informasi Kurir</h4>
            {tracking.courier_name && (
              <p className="text-sm text-gray-600">Nama: {tracking.courier_name}</p>
            )}
            {tracking.courier_phone && (
              <p className="text-sm text-gray-600">Telepon: {tracking.courier_phone}</p>
            )}
          </div>
        )}

        {/* Payment Status */}
        <div className={`rounded-lg p-4 ${tracking.payment_received ? 'bg-green-50' : 'bg-amber-50'}`}>
          <h4 className="font-semibold text-gray-700 mb-2">
            {tracking.payment_received ? '✅ Pembayaran Diterima' : '⏳ Menunggu Pembayaran'}
          </h4>
          {tracking.payment_received && tracking.payment_received_at && (
            <p className="text-sm text-gray-600">
              Diterima: {new Date(tracking.payment_received_at).toLocaleString('id-ID')}
            </p>
          )}
          {tracking.payment_amount && (
            <p className="text-sm text-gray-600">
              Jumlah: Rp {tracking.payment_amount.toLocaleString('id-ID')}
            </p>
          )}
          {tracking.receiver_name && (
            <p className="text-sm text-gray-600">Diterima oleh: {tracking.receiver_name}</p>
          )}
        </div>

        {/* Status Timeline */}
        <div>
          <h4 className="font-semibold text-gray-700 mb-3">📋 Riwayat Status</h4>
          <div className="space-y-3">
            {history.map((item, index) => (
              <div key={item.id} className="flex gap-3">
                <div className="flex-shrink-0">
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm
                    ${index === 0 ? 'bg-[#a97456] text-white' : 'bg-gray-200 text-gray-600'}`}>
                    {index + 1}
                  </div>
                </div>
                <div className="flex-1">
                  <div className="font-medium text-gray-900">
                    {STATUS_LABELS[item.status as keyof typeof STATUS_LABELS] || item.status}
                  </div>
                  {item.notes && (
                    <p className="text-sm text-gray-600">{item.notes}</p>
                  )}
                  <p className="text-xs text-gray-500 mt-1">
                    {new Date(item.created_at).toLocaleString('id-ID')}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Notes */}
        {tracking.notes && (
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 className="font-semibold text-blue-900 mb-1">📝 Catatan</h4>
            <p className="text-sm text-blue-800">{tracking.notes}</p>
          </div>
        )}

        {/* Admin Actions */}
        {isAdmin && (
          <div className="border-t pt-4 mt-4 space-y-2">
            <h4 className="font-semibold text-gray-700 mb-3">Admin Actions</h4>
            <div className="flex flex-wrap gap-2">
              {currentStatus === 'pending' && (
                <button
                  onClick={() => updateStatus('confirmed', 'Pesanan dikonfirmasi admin')}
                  className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                >
                  ✅ Konfirmasi Pesanan
                </button>
              )}
              {currentStatus === 'confirmed' && (
                <button
                  onClick={() => updateStatus('packed', 'Pesanan sudah dikemas')}
                  className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm"
                >
                  📦 Tandai Dikemas
                </button>
              )}
              {currentStatus === 'packed' && (
                <button
                  onClick={() => updateStatus('out_for_delivery', 'Pesanan dalam perjalanan')}
                  className="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 text-sm"
                >
                  🚚 Kirim Pesanan
                </button>
              )}
              {currentStatus === 'out_for_delivery' && (
                <button
                  onClick={() => updateStatus('delivered', 'Pesanan telah sampai')}
                  className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
                >
                  🏠 Tandai Terkirim
                </button>
              )}
              {currentStatus === 'delivered' && !tracking.payment_received && (
                <button
                  onClick={() => confirmPayment()}
                  className="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm"
                >
                  💰 Konfirmasi Pembayaran
                </button>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
