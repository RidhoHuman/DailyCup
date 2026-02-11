"use client";

import { useState, useEffect, useCallback } from "react";
import Link from "next/link";
import { api } from "@/lib/api-client";
import toast from "react-hot-toast";

interface Kurir {
  id: number;
  name: string;
  phone: string;
  email: string;
  photo: string | null;
  vehicle_type: 'motor' | 'mobil' | 'sepeda';
  vehicle_number: string;
  status: 'available' | 'busy' | 'offline';
  rating: number;
  total_deliveries: number;
  is_active: boolean | number;
  created_at: string;
  active_deliveries?: number;
  today_deliveries?: number;
  today_earnings?: number;
  latitude?: number;
  longitude?: number;
  location_is_fresh?: boolean;
}

interface KurirStats {
  total_kurirs: number;
  available: number;
  busy: number;
  offline: number;
}

export default function KurirManagementPage() {
  const [kurirs, setKurirs] = useState<Kurir[]>([]);
  const [stats, setStats] = useState<KurirStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [showInactive, setShowInactive] = useState(false);
  const [actionLoading, setActionLoading] = useState<number | null>(null);

  const fetchData = useCallback(async () => {
    try {
      setLoading(true);
      let url = `/get_kurir_list.php?include_inactive=${showInactive || statusFilter === 'suspended'}`;
      if (statusFilter !== 'all' && statusFilter !== 'suspended') url += `&status=${statusFilter}`;

      const response = await api.get<{
        success: boolean;
        kurirs: Kurir[];
        stats: KurirStats;
      }>(url, { requiresAuth: true });

      if (response.success) {
        let filtered = response.kurirs || [];
        if (statusFilter === 'suspended') {
          filtered = filtered.filter(k => !k.is_active || k.is_active === 0);
        }
        setKurirs(filtered);
        setStats(response.stats || null);
      }
    } catch (error) {
      console.error('Error fetching kurirs:', error);
    } finally {
      setLoading(false);
    }
  }, [statusFilter, showInactive]);

  useEffect(() => { fetchData(); }, [fetchData]);

  const handleApprove = async (kurir: Kurir) => {
    if (!confirm(`Setujui dan aktifkan kurir "${kurir.name}"?`)) return;
    setActionLoading(kurir.id);
    try {
      await api.put(`/kurir.php?id=${kurir.id}`, {
        is_active: 1,
        status: 'available'
      }, { requiresAuth: true });
      toast.success(`${kurir.name} berhasil disetujui!`);
      fetchData();
    } catch (error: any) {
      toast.error(error.message || 'Gagal menyetujui kurir');
    } finally {
      setActionLoading(null);
    }
  };

  const handleSuspend = async (kurir: Kurir) => {
    if (!confirm(`Suspend kurir "${kurir.name}"? Kurir tidak akan bisa menerima pesanan.`)) return;
    setActionLoading(kurir.id);
    try {
      await api.put(`/kurir.php?id=${kurir.id}`, {
        is_active: 0,
        status: 'offline'
      }, { requiresAuth: true });
      toast.success(`${kurir.name} berhasil di-suspend`);
      fetchData();
    } catch (error: any) {
      toast.error(error.message || 'Gagal suspend kurir');
    } finally {
      setActionLoading(null);
    }
  };

  const handleUpdateKurirStatus = async (kurirId: number, newStatus: string) => {
    try {
      await api.put(`/kurir.php?id=${kurirId}`, { status: newStatus }, { requiresAuth: true });
      toast.success('Status kurir diperbarui');
      fetchData();
    } catch (error: any) {
      toast.error(error.message || 'Gagal update status');
    }
  };

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      'available': 'bg-green-100 text-green-700 border-green-300',
      'busy': 'bg-yellow-100 text-yellow-700 border-yellow-300',
      'offline': 'bg-gray-100 text-gray-700 border-gray-300'
    };
    return colors[status] || 'bg-gray-100 text-gray-700 border-gray-300';
  };

  const getVehicleEmoji = (type: string) => {
    return type === 'motor' ? '🏍️' : type === 'mobil' ? '🚗' : '🚲';
  };

  const isActive = (kurir: Kurir) => kurir.is_active === true || kurir.is_active === 1;

  if (loading && kurirs.length === 0) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto mb-4"></div>
          <p className="text-gray-500">Loading kurir data...</p>
        </div>
      </div>
    );
  }

  return (
    <div>
      {/* Header */}
      <div className="mb-8">
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
          <div>
            <h1 className="text-2xl font-bold text-gray-800 mb-2">
              <i className="bi bi-bicycle mr-2"></i>Kurir Management
            </h1>
            <p className="text-gray-500">Kelola kurir, approve pendaftaran, dan monitor status</p>
          </div>
          <Link
            href="/admin/kurir/create"
            className="px-6 py-3 bg-[#a97456] text-white rounded-lg hover:bg-[#8b6043] transition-colors font-medium"
          >
            <i className="bi bi-plus-circle mr-2"></i>Tambah Kurir
          </Link>
        </div>
      </div>

      {/* Statistics Cards */}
      {stats && (
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <i className="bi bi-people-fill text-3xl text-blue-500 mb-2 block"></i>
            <h3 className="text-2xl font-bold text-gray-800">{stats.total_kurirs}</h3>
            <p className="text-gray-500 text-xs">Total Kurir</p>
          </div>
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <i className="bi bi-check-circle-fill text-3xl text-green-500 mb-2 block"></i>
            <h3 className="text-2xl font-bold text-gray-800">{stats.available}</h3>
            <p className="text-gray-500 text-xs">Available</p>
          </div>
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <i className="bi bi-hourglass-split text-3xl text-yellow-500 mb-2 block"></i>
            <h3 className="text-2xl font-bold text-gray-800">{stats.busy}</h3>
            <p className="text-gray-500 text-xs">Busy</p>
          </div>
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <i className="bi bi-moon-fill text-3xl text-gray-400 mb-2 block"></i>
            <h3 className="text-2xl font-bold text-gray-800">{stats.offline}</h3>
            <p className="text-gray-500 text-xs">Offline</p>
          </div>
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <i className="bi bi-slash-circle-fill text-3xl text-red-400 mb-2 block"></i>
            <h3 className="text-2xl font-bold text-gray-800">
              {kurirs.filter(k => !isActive(k)).length || 0}
            </h3>
            <p className="text-gray-500 text-xs">Suspended</p>
          </div>
        </div>
      )}

      {/* Filter */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
        <div className="flex flex-wrap gap-2">
          {[
            { key: 'all', label: 'Semua', color: 'bg-[#a97456]' },
            { key: 'available', label: 'Available', color: 'bg-green-500' },
            { key: 'busy', label: 'Busy', color: 'bg-yellow-500' },
            { key: 'offline', label: 'Offline', color: 'bg-gray-500' },
            { key: 'suspended', label: 'Suspended', color: 'bg-red-500' },
          ].map(f => (
            <button
              key={f.key}
              onClick={() => setStatusFilter(f.key)}
              className={`px-4 py-2 rounded-lg font-medium text-sm transition-colors ${
                statusFilter === f.key
                  ? `${f.color} text-white`
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              {f.label}
            </button>
          ))}
        </div>
      </div>

      {/* Kurir Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {kurirs.length === 0 ? (
          <div className="col-span-full bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <i className="bi bi-bicycle text-6xl text-gray-300 mb-4 block"></i>
            <p className="text-gray-500 text-lg">Tidak ada kurir ditemukan</p>
          </div>
        ) : (
          kurirs.map((kurir) => (
            <div
              key={kurir.id}
              className={`bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all ${
                !isActive(kurir) ? 'opacity-60 border-red-200' : ''
              }`}
            >
              {/* Suspended badge */}
              {!isActive(kurir) && (
                <div className="bg-red-50 border border-red-200 rounded-lg px-3 py-1.5 mb-4 text-center">
                  <span className="text-red-600 text-xs font-bold"><i className="bi bi-slash-circle mr-1"></i>SUSPENDED</span>
                </div>
              )}

              {/* Header */}
              <div className="flex items-start gap-4 mb-4">
                <div className="w-14 h-14 bg-[#a97456] text-white rounded-full flex items-center justify-center font-bold text-lg overflow-hidden flex-shrink-0">
                  {kurir.photo ? (
                    <img src={kurir.photo} alt={kurir.name} className="w-full h-full object-cover" />
                  ) : (
                    kurir.name.charAt(0).toUpperCase()
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <h3 className="font-bold text-gray-800 truncate">{kurir.name}</h3>
                  <p className="text-sm text-gray-500 flex items-center gap-1.5">
                    <i className="bi bi-telephone text-xs"></i>
                    <a href={`tel:${kurir.phone}`} className="hover:text-[#a97456]">{kurir.phone}</a>
                  </p>
                  <p className="text-sm text-gray-500 flex items-center gap-1.5">
                    <span>{getVehicleEmoji(kurir.vehicle_type)}</span>
                    <span>{kurir.vehicle_type} • {kurir.vehicle_number}</span>
                  </p>
                </div>
              </div>

              {/* Status & Stats */}
              <div className="flex items-center justify-between mb-4 pb-4 border-b border-gray-100">
                {isActive(kurir) ? (
                  <select
                    value={kurir.status}
                    onChange={(e) => handleUpdateKurirStatus(kurir.id, e.target.value)}
                    className={`px-3 py-1 rounded-full text-xs font-medium border ${getStatusColor(kurir.status)} cursor-pointer`}
                  >
                    <option value="available">Available</option>
                    <option value="busy">Busy</option>
                    <option value="offline">Offline</option>
                  </select>
                ) : (
                  <span className="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-300">
                    Inactive
                  </span>
                )}
                <div className="flex items-center gap-4">
                  <div className="text-center">
                    <div className="flex items-center gap-1 text-yellow-500">
                      <i className="bi bi-star-fill text-xs"></i>
                      <span className="text-sm font-bold text-gray-800">{Number(kurir.rating).toFixed(1)}</span>
                    </div>
                  </div>
                  <div className="text-center">
                    <span className="text-sm font-bold text-gray-800">{kurir.total_deliveries}</span>
                    <p className="text-[10px] text-gray-400">deliveries</p>
                  </div>
                </div>
              </div>

              {/* Active Orders */}
              {kurir.active_deliveries && kurir.active_deliveries > 0 ? (
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-2.5 mb-4">
                  <span className="text-blue-700 text-xs font-medium">
                    <i className="bi bi-box-seam mr-1"></i>
                    {kurir.active_deliveries} aktif
                    {kurir.today_deliveries ? ` • ${kurir.today_deliveries} hari ini` : ''}
                  </span>
                </div>
              ) : null}

              {/* Actions */}
              <div className="flex items-center gap-2">
                <Link
                  href={`/admin/kurir/${kurir.id}`}
                  className="flex-1 px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-xs text-center font-medium"
                >
                  <i className="bi bi-eye mr-1"></i>Detail
                </Link>
                <Link
                  href={`/admin/kurir/${kurir.id}/edit`}
                  className="flex-1 px-3 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors text-xs text-center font-medium"
                >
                  <i className="bi bi-pencil mr-1"></i>Edit
                </Link>
                {isActive(kurir) ? (
                  <button
                    onClick={() => handleSuspend(kurir)}
                    disabled={actionLoading === kurir.id}
                    className="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-xs font-medium disabled:opacity-50"
                    title="Suspend"
                  >
                    {actionLoading === kurir.id ? (
                      <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    ) : (
                      <i className="bi bi-slash-circle"></i>
                    )}
                  </button>
                ) : (
                  <button
                    onClick={() => handleApprove(kurir)}
                    disabled={actionLoading === kurir.id}
                    className="px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-xs font-medium disabled:opacity-50"
                    title="Approve / Aktifkan"
                  >
                    {actionLoading === kurir.id ? (
                      <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    ) : (
                      <i className="bi bi-check-circle"></i>
                    )}
                  </button>
                )}
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}
