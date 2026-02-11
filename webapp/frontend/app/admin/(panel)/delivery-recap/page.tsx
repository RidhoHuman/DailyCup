"use client";

import { useState, useEffect, useCallback } from "react";
import { api } from "@/lib/api-client";
import toast from "react-hot-toast";

interface DeliveryStats {
  period: string;
  total_orders: number;
  completed_orders: number;
  cancelled_orders: number;
  total_revenue: number | null;
  cod_revenue: number | null;
  cod_orders: number;
  avg_delivery_time: number | null;
  success_rate: number;
}

interface KurirPerformance {
  id: number;
  name: string;
  phone: string;
  vehicle_type: string;
  total_deliveries: number;
  completed: number;
  avg_time: number | null;
  total_earnings: number | null;
  rating: number;
}

interface CodOrder {
  id: number;
  order_number: string;
  customer_name: string;
  customer_phone: string;
  final_amount: number;
  payment_status: string;
  delivery_address: string;
  kurir_name: string | null;
  status: string;
  created_at: string;
  risk_level?: string;
  trust_score?: number;
}

export default function DeliveryRecapPage() {
  const [period, setPeriod] = useState<string>("today");
  const [stats, setStats] = useState<DeliveryStats | null>(null);
  const [kurirs, setKurirs] = useState<KurirPerformance[]>([]);
  const [codOrders, setCodOrders] = useState<CodOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [codLoading, setCodLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState<number | null>(null);
  const [activeTab, setActiveTab] = useState<'recap' | 'cod'>('recap');

  const fetchStats = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get<{
        success: boolean;
        stats: any;
        top_kurirs: KurirPerformance[];
      }>(`/get_delivery_stats.php?period=${period}`, { requiresAuth: true });

      if (res.success) {
        setStats(res.stats || null);
        setKurirs(res.top_kurirs || []);
      }
    } catch (error) {
      console.error("Error fetching stats:", error);
    } finally {
      setLoading(false);
    }
  }, [period]);

  const fetchCodOrders = useCallback(async () => {
    setCodLoading(true);
    try {
      const res = await api.get<{
        success: boolean;
        orders: CodOrder[];
      }>("/get_pending_cod_orders.php", { requiresAuth: true });

      if (res.success) {
        setCodOrders(res.orders || []);
      }
    } catch (error) {
      console.error("Error fetching COD orders:", error);
    } finally {
      setCodLoading(false);
    }
  }, []);

  useEffect(() => { fetchStats(); }, [fetchStats]);
  useEffect(() => { fetchCodOrders(); }, [fetchCodOrders]);

  const handleCodAction = async (orderId: number, action: 'approve' | 'reject') => {
    setActionLoading(orderId);
    try {
      const res = await api.post<{ success: boolean; message: string }>(
        "/admin_confirm_cod.php",
        { order_id: orderId, action },
        { requiresAuth: true }
      );
      if (res.success) {
        toast.success(res.message || `Order berhasil di-${action}`);
        fetchCodOrders();
        fetchStats();
      } else {
        toast.error(res.message || "Gagal memproses order");
      }
    } catch {
      toast.error("Terjadi kesalahan");
    } finally {
      setActionLoading(null);
    }
  };

  const formatCurrency = (val: number | null | undefined) => {
    if (val === null || val === undefined) return "Rp 0";
    return `Rp ${Number(val).toLocaleString("id-ID")}`;
  };

  const riskColors: Record<string, string> = {
    low: "bg-green-100 text-green-700",
    medium: "bg-yellow-100 text-yellow-700",
    high: "bg-red-100 text-red-700",
  };

  return (
    <div>
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800 mb-1">
            <i className="bi bi-clipboard-data mr-2 text-[#a97456]"></i>Rekap Delivery & COD
          </h1>
          <p className="text-gray-500 text-sm">Statistik dan pengelolaan pembayaran COD</p>
        </div>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 mb-6 bg-gray-100 rounded-xl p-1 w-fit">
        {[
          { key: 'recap', label: 'Rekap Delivery', icon: 'bi-bar-chart' },
          { key: 'cod', label: `COD Orders${codOrders.length > 0 ? ` (${codOrders.length})` : ''}`, icon: 'bi-cash-stack' },
        ].map(tab => (
          <button key={tab.key}
            onClick={() => setActiveTab(tab.key as 'recap' | 'cod')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
              activeTab === tab.key
                ? 'bg-white shadow-sm text-[#a97456]'
                : 'text-gray-500 hover:text-gray-700'
            }`}>
            <i className={`bi ${tab.icon} mr-1.5`}></i>{tab.label}
          </button>
        ))}
      </div>

      {/* ============ TAB: Rekap Delivery ============ */}
      {activeTab === 'recap' && (
        <>
          {/* Period selector */}
          <div className="flex gap-2 mb-6">
            {[
              { key: "today", label: "Hari Ini" },
              { key: "week", label: "Minggu Ini" },
              { key: "month", label: "Bulan Ini" },
            ].map((p) => (
              <button key={p.key}
                onClick={() => setPeriod(p.key)}
                className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                  period === p.key
                    ? "bg-[#a97456] text-white"
                    : "bg-white border border-gray-200 text-gray-600 hover:bg-gray-50"
                }`}>
                {p.label}
              </button>
            ))}
          </div>

          {loading ? (
            <div className="flex items-center justify-center h-48">
              <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-[#a97456]"></div>
            </div>
          ) : (
            <>
              {/* Stats Cards */}
              {stats && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                  <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div className="flex items-center gap-3">
                      <div className="w-11 h-11 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i className="bi bi-bag-check text-blue-600 text-lg"></i>
                      </div>
                      <div>
                        <p className="text-2xl font-bold text-gray-800">{stats.total_orders}</p>
                        <p className="text-xs text-gray-500">Total Orders</p>
                      </div>
                    </div>
                  </div>
                  <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div className="flex items-center gap-3">
                      <div className="w-11 h-11 bg-green-100 rounded-xl flex items-center justify-center">
                        <i className="bi bi-check-circle text-green-600 text-lg"></i>
                      </div>
                      <div>
                        <p className="text-2xl font-bold text-gray-800">{stats.completed_orders}</p>
                        <p className="text-xs text-gray-500">Selesai ({stats.success_rate}%)</p>
                      </div>
                    </div>
                  </div>
                  <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div className="flex items-center gap-3">
                      <div className="w-11 h-11 bg-[#a97456]/10 rounded-xl flex items-center justify-center">
                        <i className="bi bi-wallet2 text-[#a97456] text-lg"></i>
                      </div>
                      <div>
                        <p className="text-2xl font-bold text-gray-800">{formatCurrency(stats.total_revenue)}</p>
                        <p className="text-xs text-gray-500">Total Revenue</p>
                      </div>
                    </div>
                  </div>
                  <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div className="flex items-center gap-3">
                      <div className="w-11 h-11 bg-red-100 rounded-xl flex items-center justify-center">
                        <i className="bi bi-cash-stack text-red-600 text-lg"></i>
                      </div>
                      <div>
                        <p className="text-2xl font-bold text-gray-800">{formatCurrency(stats.cod_revenue)}</p>
                        <p className="text-xs text-gray-500">COD Revenue ({stats.cod_orders})</p>
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* Additional Stats Row */}
              {stats && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                  <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 className="text-sm font-semibold text-gray-600 mb-3">
                      <i className="bi bi-clock mr-1.5"></i>Waktu Rata-rata Delivery
                    </h3>
                    <p className="text-3xl font-bold text-gray-800">
                      {stats.avg_delivery_time ? `${stats.avg_delivery_time} menit` : '-'}
                    </p>
                  </div>
                  <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 className="text-sm font-semibold text-gray-600 mb-3">
                      <i className="bi bi-x-circle mr-1.5"></i>Order Dibatalkan
                    </h3>
                    <p className="text-3xl font-bold text-red-600">{stats.cancelled_orders}</p>
                  </div>
                </div>
              )}

              {/* Kurir Performance Table */}
              <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="p-5 border-b border-gray-100">
                  <h3 className="font-semibold text-gray-800">
                    <i className="bi bi-trophy mr-2 text-yellow-500"></i>Performansi Kurir
                  </h3>
                </div>
                {kurirs.length === 0 ? (
                  <div className="p-8 text-center text-gray-400">Belum ada data kurir</div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="bg-gray-50">
                          <th className="text-left px-5 py-3 text-gray-500 font-medium">#</th>
                          <th className="text-left px-5 py-3 text-gray-500 font-medium">Kurir</th>
                          <th className="text-center px-5 py-3 text-gray-500 font-medium">Delivery</th>
                          <th className="text-center px-5 py-3 text-gray-500 font-medium">Selesai</th>
                          <th className="text-center px-5 py-3 text-gray-500 font-medium">Avg Time</th>
                          <th className="text-center px-5 py-3 text-gray-500 font-medium">Rating</th>
                          <th className="text-right px-5 py-3 text-gray-500 font-medium">Earnings</th>
                        </tr>
                      </thead>
                      <tbody>
                        {Array.isArray(kurirs) && kurirs.map((k, idx) => (
                          <tr key={k.id} className="border-t border-gray-50 hover:bg-gray-50/50">
                            <td className="px-5 py-3">
                              {idx < 3 ? (
                                <span className={`w-6 h-6 rounded-full inline-flex items-center justify-center text-xs font-bold ${
                                  idx === 0 ? 'bg-yellow-100 text-yellow-700' :
                                  idx === 1 ? 'bg-gray-100 text-gray-600' : 'bg-orange-100 text-orange-600'
                                }`}>{idx + 1}</span>
                              ) : <span className="text-gray-400">{idx + 1}</span>}
                            </td>
                            <td className="px-5 py-3">
                              <div className="flex items-center gap-2">
                                <div className="w-8 h-8 bg-[#a97456] text-white rounded-full flex items-center justify-center text-xs font-bold">
                                  {k.name.charAt(0)}
                                </div>
                                <div>
                                  <p className="font-medium text-gray-800">{k.name}</p>
                                  <p className="text-xs text-gray-400">{k.vehicle_type}</p>
                                </div>
                              </div>
                            </td>
                            <td className="px-5 py-3 text-center text-gray-700">{k.total_deliveries}</td>
                            <td className="px-5 py-3 text-center text-green-600 font-medium">{k.completed}</td>
                            <td className="px-5 py-3 text-center text-gray-600">
                              {k.avg_time ? `${Math.round(Number(k.avg_time))} min` : '-'}
                            </td>
                            <td className="px-5 py-3 text-center">
                              <span className="text-yellow-500">
                                <i className="bi bi-star-fill mr-0.5"></i>
                                {k.rating ? Number(k.rating).toFixed(1) : '-'}
                              </span>
                            </td>
                            <td className="px-5 py-3 text-right font-medium text-gray-800">{formatCurrency(k.total_earnings)}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            </>
          )}
        </>
      )}

      {/* ============ TAB: COD Orders ============ */}
      {activeTab === 'cod' && (
        <>
          {codLoading ? (
            <div className="flex items-center justify-center h-48">
              <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-[#a97456]"></div>
            </div>
          ) : codOrders.length === 0 ? (
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
              <i className="bi bi-check-circle text-6xl text-green-300 mb-4 block"></i>
              <p className="text-gray-500 text-lg">Semua order COD sudah diproses</p>
            </div>
          ) : (
            <div className="space-y-3">
              {codOrders.map((order) => (
                <div key={order.id} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                  <div className="flex flex-col lg:flex-row items-start gap-4">
                    {/* Order info */}
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 mb-2 flex-wrap">
                        <span className="font-bold text-gray-800">#{order.order_number?.split('-').pop()}</span>
                        <span className="text-lg font-bold text-[#a97456]">{formatCurrency(order.final_amount)}</span>
                        <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-600">COD</span>
                        {order.risk_level && (
                          <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${riskColors[order.risk_level] || riskColors.medium}`}>
                            Risk: {order.risk_level.toUpperCase()}
                          </span>
                        )}
                        {order.trust_score !== undefined && (
                          <span className="text-xs text-gray-400">Trust: {order.trust_score}%</span>
                        )}
                      </div>
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-1 text-sm">
                        <p className="text-gray-600">
                          <i className="bi bi-person text-gray-400 mr-1.5"></i>
                          {order.customer_name} <span className="text-gray-400">({order.customer_phone})</span>
                        </p>
                        <p className="text-gray-500 truncate">
                          <i className="bi bi-geo-alt text-gray-400 mr-1.5"></i>{order.delivery_address}
                        </p>
                        <p className="text-gray-500 text-xs">
                          <i className="bi bi-clock text-gray-400 mr-1.5"></i>
                          {new Date(order.created_at).toLocaleString('id-ID')}
                        </p>
                        {order.kurir_name && (
                          <p className="text-gray-500 text-xs">
                            <i className="bi bi-bicycle text-gray-400 mr-1.5"></i>{order.kurir_name}
                          </p>
                        )}
                      </div>
                    </div>

                    {/* Actions */}
                    <div className="flex gap-2 flex-shrink-0">
                      <button
                        onClick={() => handleCodAction(order.id, 'approve')}
                        disabled={actionLoading === order.id}
                        className="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 text-sm font-medium transition-colors disabled:opacity-50"
                      >
                        {actionLoading === order.id ? (
                          <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        ) : (
                          <><i className="bi bi-check-lg mr-1"></i>Approve</>
                        )}
                      </button>
                      <button
                        onClick={() => handleCodAction(order.id, 'reject')}
                        disabled={actionLoading === order.id}
                        className="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 text-sm font-medium transition-colors disabled:opacity-50"
                      >
                        <i className="bi bi-x-lg mr-1"></i>Reject
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </>
      )}
    </div>
  );
}
