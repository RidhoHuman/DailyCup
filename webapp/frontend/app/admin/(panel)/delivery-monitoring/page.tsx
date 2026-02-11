"use client";

import { useState, useEffect, useCallback, useRef } from "react";
import dynamic from "next/dynamic";
import { api } from "@/lib/api-client";

// Dynamic import for map component (client-side only)

const DeliveryMap = dynamic(
  () => import("@/components/admin/DeliveryMonitorMap"),
  { 
    ssr: false, 
    loading: () => (
      <div className="h-[400px] bg-gray-100 rounded-2xl animate-pulse flex items-center justify-center">
        <div className="text-gray-500">Loading map...</div>
      </div>
    )
  }
);

interface Delivery {
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
  kurir_id?: number | null;
  kurir_name: string | null;
  kurir_phone: string | null;
  vehicle_type: string | null;
  vehicle_number?: string | null;
  kurir_status: string | null;
  kurir_lat: number | null;
  kurir_lng: number | null;
  delivery_lat?: number | null;
  delivery_lng?: number | null;
  accuracy?: number | null;
  speed?: number | null;
  location_updated_at: string | null;
  assigned_at: string | null;
  pickup_time: string | null;
  created_at: string;
  progress: number;
  warning?: string;
  minutes_since_assigned: number | null;
  minutes_since_pickup: number | null;
}

interface Stats {
  total_active: number;
  confirmed: number;
  processing: number;
  ready: number;
  delivering: number;
  cod_orders: number;
}

export default function DeliveryMonitoringPage() {
  const [deliveries, setDeliveries] = useState<Delivery[]>([]);
  const [stats, setStats] = useState<Stats | null>(null);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [autoRefresh, setAutoRefresh] = useState(true);
  const [lastUpdate, setLastUpdate] = useState<Date>(new Date());
  const [showMap, setShowMap] = useState(true);
  const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const fetchData = useCallback(async () => {
    try {
      let url = '/get_delivery_tracking.php?limit=100';
      if (statusFilter) url += `&status=${statusFilter}`;

      const res = await api.get<{ success: boolean; deliveries: Delivery[]; stats: Stats }>(
        url, { requiresAuth: true }
      );

      if (res.success) {
        setDeliveries(res.deliveries || []);
        setStats(res.stats || null);
        setLastUpdate(new Date());
      }
    } catch (error) {
      console.error('Error fetching deliveries:', error);
    } finally {
      setLoading(false);
    }
  }, [statusFilter]);

  useEffect(() => { fetchData(); }, [fetchData]);

  // Auto-refresh every 15 seconds
  useEffect(() => {
    if (autoRefresh) {
      intervalRef.current = setInterval(fetchData, 15000);
    }
    return () => {
      if (intervalRef.current) clearInterval(intervalRef.current);
    };
  }, [autoRefresh, fetchData]);

  // Transform deliveries data to map format (group by kurir)
  const mapKurirs = deliveries.reduce((acc, d) => {
    if (!d.kurir_name || !d.kurir_lat || !d.kurir_lng) return acc;

    const kurirId = Number(d.kurir_id) || 0;
    const existingKurir = acc.find(k => k.id === kurirId);

    const orderData = {
      order_id: d.id,
      order_number: d.order_number,
      customer_name: d.customer_name,
      delivery_address: d.delivery_address,
      destination: (d.delivery_lat && d.delivery_lng) ? { lat: Number(d.delivery_lat), lng: Number(d.delivery_lng) } : null,
      status: d.status,
      created_at: d.created_at,
    };

    if (existingKurir) {
      existingKurir.orders.push(orderData);
    } else {
      acc.push({
        id: kurirId || acc.length + 1,
        name: d.kurir_name,
        phone: d.kurir_phone || '',
        vehicle_type: d.vehicle_type || 'motor',
        vehicle_number: d.vehicle_number || '',
        location: {
          lat: Number(d.kurir_lat),
          lng: Number(d.kurir_lng),
          updated_at: d.location_updated_at || new Date().toISOString(),
          accuracy: d.accuracy ? Number(d.accuracy) : null,
          speed: d.speed ? Number(d.speed) : null,
        },
        orders: [orderData],
      });
    }
    return acc;
  }, [] as Array<{
    id: number;
    name: string;
    phone: string;
    vehicle_type: string;
    vehicle_number: string;
    location: { lat: number; lng: number; updated_at: string; accuracy: number | null; speed: number | null } | null;
    orders: Array<{ order_id: number; order_number: string; customer_name: string; delivery_address: string; destination: { lat: number; lng: number } | null; status: string; created_at: string }>;
  }>);

  const formatTime = (d: string) => new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
  const formatCurrency = (val: number) => `Rp ${val.toLocaleString('id-ID')}`;

  const statusConfig: Record<string, { label: string; color: string; icon: string; bg: string }> = {
    confirmed: { label: 'Dikonfirmasi', color: 'text-blue-700', icon: 'bi-check-circle', bg: 'bg-blue-100' },
    processing: { label: 'Diproses', color: 'text-yellow-700', icon: 'bi-gear', bg: 'bg-yellow-100' },
    ready: { label: 'Siap', color: 'text-purple-700', icon: 'bi-box-seam', bg: 'bg-purple-100' },
    delivering: { label: 'Diantar', color: 'text-green-700', icon: 'bi-truck', bg: 'bg-green-100' },
  };

  const progressColors: Record<string, string> = {
    confirmed: 'bg-blue-500',
    processing: 'bg-yellow-500',
    ready: 'bg-purple-500',
    delivering: 'bg-green-500',
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto mb-4"></div>
          <p className="text-gray-500">Loading monitoring data...</p>
        </div>
      </div>
    );
  }

  return (
    <div>
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800 mb-1">
            <i className="bi bi-broadcast-pin mr-2 text-green-500"></i>Monitoring Delivery
            <span className="ml-2 inline-flex items-center gap-1.5 px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-medium">
              <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>LIVE
            </span>
          </h1>
          <p className="text-gray-500 text-sm">Update terakhir: {lastUpdate.toLocaleTimeString('id-ID')}</p>
        </div>
        <div className="flex items-center gap-3">
          <label className="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" checked={autoRefresh} onChange={e => setAutoRefresh(e.target.checked)}
              className="w-4 h-4 rounded border-gray-300 text-[#a97456] focus:ring-[#a97456]" />
            Auto-refresh
          </label>
          <button onClick={fetchData} className="px-4 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8b6043] text-sm font-medium transition-colors">
            <i className="bi bi-arrow-clockwise mr-1"></i>Refresh
          </button>
        </div>
      </div>

      {/* Stats Pipeline */}
      {stats && (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
          <div className="grid grid-cols-3 md:grid-cols-6 gap-4 text-center">
            <div>
              <h4 className="text-2xl font-bold text-gray-800">{stats.total_active}</h4>
              <p className="text-xs text-gray-500">Total Aktif</p>
            </div>
            <div>
              <h4 className="text-2xl font-bold text-blue-600">{stats.confirmed}</h4>
              <p className="text-xs text-gray-500">Confirmed</p>
            </div>
            <div>
              <h4 className="text-2xl font-bold text-yellow-600">{stats.processing}</h4>
              <p className="text-xs text-gray-500">Processing</p>
            </div>
            <div>
              <h4 className="text-2xl font-bold text-purple-600">{stats.ready}</h4>
              <p className="text-xs text-gray-500">Ready</p>
            </div>
            <div>
              <h4 className="text-2xl font-bold text-green-600">{stats.delivering}</h4>
              <p className="text-xs text-gray-500">Delivering</p>
            </div>
            <div>
              <h4 className="text-2xl font-bold text-red-600">{stats.cod_orders}</h4>
              <p className="text-xs text-gray-500">COD Orders</p>
            </div>
          </div>
        </div>
      )}

      {/* GPS Live Map */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
        <div className="p-4 border-b border-gray-100 flex justify-between items-center">
          <h2 className="font-bold text-gray-800">
            <i className="bi bi-map mr-2 text-green-500"></i>
            GPS Live Tracking
            {mapKurirs.length > 0 && (
              <span className="ml-2 text-sm font-normal text-gray-500">
                ({mapKurirs.length} kurir aktif)
              </span>
            )}
          </h2>
          <button 
            onClick={() => setShowMap(!showMap)}
            className="text-sm text-gray-500 hover:text-gray-700"
          >
            <i className={`bi ${showMap ? 'bi-chevron-up' : 'bi-chevron-down'} mr-1`}></i>
            {showMap ? 'Sembunyikan' : 'Tampilkan'}
          </button>
        </div>
        {showMap && (
          <div className="p-4">
            {mapKurirs.length > 0 ? (
              <DeliveryMap kurirs={mapKurirs} />
            ) : (
              <div className="h-[300px] bg-gray-50 rounded-xl flex flex-col items-center justify-center text-gray-400">
                <i className="bi bi-geo-alt text-4xl mb-2"></i>
                <p className="text-sm">Tidak ada kurir dengan GPS aktif</p>
                <p className="text-xs mt-1">Kurir perlu mengaktifkan share location</p>
              </div>
            )}
          </div>
        )}
      </div>

      {/* Status Filter */}
      <div className="flex flex-wrap gap-2 mb-6">
        {[
          { key: '', label: 'Semua' },
          { key: 'confirmed', label: 'Confirmed' },
          { key: 'processing', label: 'Processing' },
          { key: 'ready', label: 'Ready' },
          { key: 'delivering', label: 'Delivering' },
        ].map(f => (
          <button key={f.key}
            onClick={() => setStatusFilter(f.key)}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
              statusFilter === f.key
                ? 'bg-[#a97456] text-white'
                : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
            }`}
          >
            {f.label}
          </button>
        ))}
      </div>

      {/* Deliveries List */}
      {deliveries.length === 0 ? (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
          <i className="bi bi-inbox text-6xl text-gray-300 mb-4 block"></i>
          <p className="text-gray-500 text-lg">Tidak ada delivery aktif saat ini</p>
        </div>
      ) : (
        <div className="space-y-4">
          {deliveries.map(d => {
            const cfg = statusConfig[d.status] || statusConfig.confirmed;
            return (
              <div key={d.id} className={`bg-white rounded-2xl shadow-sm border overflow-hidden transition-all hover:shadow-md ${
                d.warning ? 'border-orange-300' : 'border-gray-100'
              }`}>
                {/* Warning banner */}
                {d.warning && (
                  <div className="bg-orange-50 px-5 py-2 border-b border-orange-200">
                    <span className="text-orange-700 text-xs font-medium">
                      <i className="bi bi-exclamation-triangle-fill mr-1"></i>{d.warning}
                    </span>
                  </div>
                )}

                <div className="p-5">
                  <div className="flex flex-col lg:flex-row gap-4">
                    {/* Left: Order Info */}
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 mb-3">
                        <span className="font-bold text-gray-800">#{d.order_number?.split('-').pop()}</span>
                        <span className={`px-2.5 py-1 rounded-full text-[10px] font-bold ${cfg.bg} ${cfg.color}`}>
                          <i className={`bi ${cfg.icon} mr-1`}></i>{cfg.label}
                        </span>
                        {d.payment_method === 'cod' && (
                          <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-600">COD</span>
                        )}
                        <span className="text-gray-800 font-bold ml-auto">{formatCurrency(d.final_amount)}</span>
                      </div>

                      {/* Progress bar */}
                      <div className="h-2 bg-gray-100 rounded-full mb-3 overflow-hidden">
                        <div
                          className={`h-full rounded-full transition-all duration-500 ${progressColors[d.status] || 'bg-gray-400'}`}
                          style={{ width: `${d.progress}%` }}
                        ></div>
                      </div>

                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                        <p className="text-gray-600">
                          <i className="bi bi-person text-gray-400 mr-1.5"></i>
                          {d.customer_name}
                          <span className="text-gray-400 ml-1 text-xs">({d.customer_phone})</span>
                        </p>
                        <p className="text-gray-500 truncate">
                          <i className="bi bi-geo-alt text-gray-400 mr-1.5"></i>{d.delivery_address}
                        </p>
                      </div>
                    </div>

                    {/* Right: Kurir info */}
                    <div className="w-full lg:w-64 flex-shrink-0">
                      {d.kurir_name ? (
                        <div className="bg-gray-50 rounded-xl p-3">
                          <div className="flex items-center gap-3 mb-2">
                            <div className="w-9 h-9 bg-[#a97456] text-white rounded-full flex items-center justify-center text-sm font-bold">
                              {d.kurir_name.charAt(0)}
                            </div>
                            <div>
                              <p className="font-medium text-gray-800 text-sm">{d.kurir_name}</p>
                              <p className="text-xs text-gray-500">{d.vehicle_type} • {d.kurir_phone}</p>
                            </div>
                          </div>
                          {/* Location freshness */}
                          {d.kurir_lat && d.kurir_lng ? (
                            <div className="flex items-center gap-1 text-xs text-green-600">
                              <span className="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                              GPS aktif
                              {d.location_updated_at && (
                                <span className="text-gray-400 ml-1">
                                  ({Math.round((Date.now() - new Date(d.location_updated_at).getTime()) / 60000)} menit lalu)
                                </span>
                              )}
                            </div>
                          ) : (
                            <p className="text-xs text-gray-400"><i className="bi bi-geo-alt-fill mr-1"></i>GPS tidak aktif</p>
                          )}
                          {/* Timing */}
                          <div className="mt-2 flex gap-3 text-xs text-gray-500">
                            {d.assigned_at && <span>Assign: {formatTime(d.assigned_at)}</span>}
                            {d.pickup_time && <span>Pickup: {formatTime(d.pickup_time)}</span>}
                          </div>
                        </div>
                      ) : (
                        <div className="bg-red-50 border border-red-200 rounded-xl p-3 text-center">
                          <i className="bi bi-exclamation-circle text-red-400 text-xl block mb-1"></i>
                          <p className="text-red-600 text-xs font-medium">Kurir belum di-assign</p>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
