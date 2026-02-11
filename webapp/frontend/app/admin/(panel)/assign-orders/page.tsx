"use client";

import { useState, useEffect, useCallback } from "react";
import { api } from "@/lib/api-client";
import toast from "react-hot-toast";

interface Order {
  id: number;
  order_number: string;
  status: string;
  final_amount: number;
  delivery_address: string;
  delivery_distance: number | null;
  payment_method: string;
  payment_status: string;
  customer_name: string;
  customer_phone: string;
  kurir_id: number | null;
  kurir_name: string | null;
  created_at: string;
  assigned_at: string | null;
}

interface Kurir {
  id: number;
  name: string;
  phone: string;
  vehicle_type: string;
  vehicle_number: string;
  status: string;
  rating: number;
  active_deliveries: number;
  is_active: boolean | number;
  is_available: boolean;
}

type TabType = 'unassigned' | 'assigned' | 'all';

export default function AssignOrdersPage() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [kurirs, setKurirs] = useState<Kurir[]>([]);
  const [loading, setLoading] = useState(true);
  const [tab, setTab] = useState<TabType>('unassigned');
  const [assigning, setAssigning] = useState<number | null>(null);
  const [selectedKurir, setSelectedKurir] = useState<Record<number, number>>({});
  const [showKurirModal, setShowKurirModal] = useState<number | null>(null);

  const fetchData = useCallback(async () => {
    try {
      setLoading(true);
      const [trackingRes, kurirRes] = await Promise.all([
        api.get<{ success: boolean; deliveries: Order[]; stats: any }>(
          '/get_delivery_tracking.php',
          { requiresAuth: true }
        ),
        api.get<{ success: boolean; kurirs: Kurir[] }>(
          '/get_kurir_list.php',
          { requiresAuth: true }
        ),
      ]);

      if (trackingRes.success) {
        setOrders(trackingRes.deliveries || []);
      }
      if (kurirRes.success) {
        setKurirs(kurirRes.kurirs || []);
      }
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchData(); }, [fetchData]);

  const filteredOrders = orders.filter(o => {
    if (tab === 'unassigned') return !o.kurir_id;
    if (tab === 'assigned') return !!o.kurir_id;
    return true;
  });

  const availableKurirs = kurirs.filter(k => k.is_available || k.status === 'available');

  const handleManualAssign = async (orderId: number) => {
    const kurirId = selectedKurir[orderId];
    if (!kurirId) {
      toast.error('Pilih kurir terlebih dahulu');
      return;
    }
    setAssigning(orderId);
    try {
      const res = await api.post<{ success: boolean; message: string }>(
        '/manual_assign_kurir.php',
        { order_id: orderId, kurir_id: kurirId },
        { requiresAuth: true }
      );
      if (res.success) {
        toast.success('Kurir berhasil di-assign!');
        setShowKurirModal(null);
        fetchData();
      }
    } catch (error: any) {
      toast.error(error.message || 'Gagal assign kurir');
    } finally {
      setAssigning(null);
    }
  };

  const handleAutoAssign = async (orderId: number) => {
    if (!confirm('Auto-assign kurir available dengan beban paling sedikit?')) return;
    setAssigning(orderId);
    try {
      // Pick best available kurir (least active deliveries, highest rating)
      const bestKurir = availableKurirs
        .sort((a, b) => a.active_deliveries - b.active_deliveries || b.rating - a.rating)[0];

      if (!bestKurir) {
        toast.error('Tidak ada kurir available saat ini');
        setAssigning(null);
        return;
      }

      const res = await api.post<{ success: boolean; message: string }>(
        '/manual_assign_kurir.php',
        { order_id: orderId, kurir_id: bestKurir.id, notes: 'Auto-assigned by admin' },
        { requiresAuth: true }
      );
      if (res.success) {
        toast.success(`Auto-assigned ke ${bestKurir.name}`);
        fetchData();
      }
    } catch (error: any) {
      toast.error(error.message || 'Gagal auto-assign');
    } finally {
      setAssigning(null);
    }
  };

  const formatCurrency = (val: number) => `Rp ${val.toLocaleString('id-ID')}`;
  const formatTime = (d: string) => new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });

  const statusColors: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-700',
    confirmed: 'bg-blue-100 text-blue-700',
    processing: 'bg-yellow-100 text-yellow-700',
    ready: 'bg-purple-100 text-purple-700',
    delivering: 'bg-green-100 text-green-700',
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto mb-4"></div>
          <p className="text-gray-500">Loading data...</p>
        </div>
      </div>
    );
  }

  const unassignedCount = orders.filter(o => !o.kurir_id).length;
  const assignedCount = orders.filter(o => !!o.kurir_id).length;

  return (
    <div>
      {/* Header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800 mb-2">
          <i className="bi bi-people-fill mr-2"></i>Assign Order ke Kurir
        </h1>
        <p className="text-gray-500">Tugaskan pesanan delivery ke kurir secara manual atau otomatis</p>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
          <i className="bi bi-exclamation-triangle-fill text-2xl text-red-500 mb-1 block"></i>
          <h3 className="text-2xl font-bold text-gray-800">{unassignedCount}</h3>
          <p className="text-gray-500 text-xs">Belum Assign</p>
        </div>
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
          <i className="bi bi-check-circle-fill text-2xl text-green-500 mb-1 block"></i>
          <h3 className="text-2xl font-bold text-gray-800">{assignedCount}</h3>
          <p className="text-gray-500 text-xs">Sudah Assign</p>
        </div>
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
          <i className="bi bi-bicycle text-2xl text-blue-500 mb-1 block"></i>
          <h3 className="text-2xl font-bold text-gray-800">{availableKurirs.length}</h3>
          <p className="text-gray-500 text-xs">Kurir Available</p>
        </div>
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
          <i className="bi bi-bag-check text-2xl text-[#a97456] mb-1 block"></i>
          <h3 className="text-2xl font-bold text-gray-800">{orders.length}</h3>
          <p className="text-gray-500 text-xs">Total Aktif</p>
        </div>
      </div>

      {/* Tabs */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 mb-6 flex gap-2">
        {([
          { key: 'unassigned', label: `Belum Assign (${unassignedCount})`, color: 'bg-red-500' },
          { key: 'assigned', label: `Sudah Assign (${assignedCount})`, color: 'bg-green-500' },
          { key: 'all', label: `Semua (${orders.length})`, color: 'bg-[#a97456]' },
        ] as { key: TabType; label: string; color: string }[]).map(t => (
          <button
            key={t.key}
            onClick={() => setTab(t.key)}
            className={`flex-1 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors ${
              tab === t.key ? `${t.color} text-white` : 'text-gray-600 hover:bg-gray-100'
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>

      {/* Orders List */}
      {filteredOrders.length === 0 ? (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
          <i className="bi bi-inbox text-6xl text-gray-300 mb-4 block"></i>
          <p className="text-gray-500">Tidak ada pesanan {tab === 'unassigned' ? 'yang belum di-assign' : ''}</p>
        </div>
      ) : (
        <div className="space-y-4">
          {filteredOrders.map(order => (
            <div key={order.id} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-all">
              <div className="flex flex-col lg:flex-row lg:items-center gap-4">
                {/* Order Info */}
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-2">
                    <span className="font-bold text-gray-800">#{order.order_number?.split('-').pop()}</span>
                    <span className={`px-2 py-0.5 rounded-full text-[10px] font-medium ${statusColors[order.status] || 'bg-gray-100 text-gray-600'}`}>
                      {order.status?.toUpperCase()}
                    </span>
                    {order.payment_method === 'cod' && (
                      <span className="px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-50 text-red-600">COD</span>
                    )}
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    <p className="text-gray-600">
                      <i className="bi bi-person text-gray-400 mr-1"></i>{order.customer_name}
                      <span className="text-gray-400 ml-1">({order.customer_phone})</span>
                    </p>
                    <p className="text-gray-600">
                      <i className="bi bi-cash text-gray-400 mr-1"></i>{formatCurrency(order.final_amount)}
                    </p>
                    <p className="text-gray-500 truncate col-span-full">
                      <i className="bi bi-geo-alt text-gray-400 mr-1"></i>{order.delivery_address}
                    </p>
                    <p className="text-gray-400 text-xs">
                      <i className="bi bi-clock mr-1"></i>{formatTime(order.created_at)}
                      {order.delivery_distance && ` • ${order.delivery_distance} km`}
                    </p>
                  </div>
                </div>

                {/* Kurir Assignment */}
                <div className="flex-shrink-0 w-full lg:w-72">
                  {order.kurir_id && order.kurir_name ? (
                    <div className="bg-green-50 border border-green-200 rounded-xl p-3">
                      <p className="text-green-700 text-sm font-medium mb-1">
                        <i className="bi bi-check-circle mr-1"></i>Assigned
                      </p>
                      <p className="text-green-800 font-bold">{order.kurir_name}</p>
                      {order.assigned_at && (
                        <p className="text-green-600 text-xs mt-1">{formatTime(order.assigned_at)}</p>
                      )}
                      <button
                        onClick={() => setShowKurirModal(order.id)}
                        className="mt-2 text-xs text-green-600 hover:text-green-800 underline"
                      >
                        <i className="bi bi-arrow-repeat mr-1"></i>Ganti kurir
                      </button>
                    </div>
                  ) : (
                    <div className="bg-red-50 border border-red-200 rounded-xl p-3">
                      <p className="text-red-600 text-sm font-medium mb-2">
                        <i className="bi bi-exclamation-triangle mr-1"></i>Belum ada kurir
                      </p>
                      <div className="flex gap-2">
                        <button
                          onClick={() => setShowKurirModal(order.id)}
                          className="flex-1 px-3 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8b6043] text-xs font-medium transition-colors"
                        >
                          <i className="bi bi-person-plus mr-1"></i>Manual
                        </button>
                        <button
                          onClick={() => handleAutoAssign(order.id)}
                          disabled={assigning === order.id || availableKurirs.length === 0}
                          className="flex-1 px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-xs font-medium transition-colors disabled:opacity-50"
                        >
                          {assigning === order.id ? (
                            <span className="flex items-center justify-center gap-1">
                              <div className="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            </span>
                          ) : (
                            <><i className="bi bi-lightning-fill mr-1"></i>Auto</>
                          )}
                        </button>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Kurir Selection Modal */}
      {showKurirModal !== null && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
          onClick={() => setShowKurirModal(null)}>
          <div className="bg-white rounded-2xl max-w-lg w-full max-h-[80vh] overflow-hidden"
            onClick={e => e.stopPropagation()}>
            <div className="p-5 border-b border-gray-100">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-bold text-gray-800">Pilih Kurir</h3>
                <button onClick={() => setShowKurirModal(null)} className="text-gray-400 hover:text-gray-600">
                  <i className="bi bi-x-lg"></i>
                </button>
              </div>
              <p className="text-sm text-gray-500 mt-1">Order #{orders.find(o => o.id === showKurirModal)?.order_number?.split('-').pop()}</p>
            </div>
            <div className="p-4 overflow-y-auto max-h-[50vh] space-y-2">
              {kurirs.filter(k => (k.is_active === true || k.is_active === 1) && k.status !== 'offline').length === 0 ? (
                <p className="text-gray-500 text-center py-8">Tidak ada kurir online saat ini</p>
              ) : (
                kurirs
                  .filter(k => (k.is_active === true || k.is_active === 1) && k.status !== 'offline')
                  .sort((a, b) => a.active_deliveries - b.active_deliveries)
                  .map(kurir => (
                    <button
                      key={kurir.id}
                      onClick={() => setSelectedKurir(prev => ({ ...prev, [showKurirModal!]: kurir.id }))}
                      className={`w-full p-3 rounded-xl border-2 text-left transition-all ${
                        selectedKurir[showKurirModal!] === kurir.id
                          ? 'border-[#a97456] bg-[#a97456]/5'
                          : 'border-gray-100 hover:border-gray-300'
                      }`}
                    >
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-[#a97456] text-white rounded-full flex items-center justify-center font-bold flex-shrink-0">
                          {kurir.name.charAt(0)}
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="font-medium text-gray-800">{kurir.name}</p>
                          <p className="text-xs text-gray-500">
                            {kurir.vehicle_type} • {kurir.vehicle_number} • <i className="bi bi-star-fill text-yellow-400"></i> {Number(kurir.rating).toFixed(1)}
                          </p>
                        </div>
                        <div className="text-right flex-shrink-0">
                          <span className={`px-2 py-0.5 rounded-full text-[10px] font-medium ${
                            kurir.status === 'available' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'
                          }`}>{kurir.status}</span>
                          <p className="text-xs text-gray-400 mt-1">{kurir.active_deliveries} order aktif</p>
                        </div>
                      </div>
                    </button>
                  ))
              )}
            </div>
            <div className="p-4 border-t border-gray-100 flex gap-3">
              <button onClick={() => setShowKurirModal(null)}
                className="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium text-sm">
                Batal
              </button>
              <button
                onClick={() => handleManualAssign(showKurirModal!)}
                disabled={!selectedKurir[showKurirModal!] || assigning === showKurirModal}
                className="flex-1 px-4 py-2.5 bg-[#a97456] text-white rounded-lg hover:bg-[#8b6043] font-medium text-sm disabled:opacity-50 transition-colors"
              >
                {assigning === showKurirModal ? (
                  <span className="flex items-center justify-center gap-2">
                    <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    Assigning...
                  </span>
                ) : (
                  <><i className="bi bi-check-circle mr-1"></i>Assign Kurir</>
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
