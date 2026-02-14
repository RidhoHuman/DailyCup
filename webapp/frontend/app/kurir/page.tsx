'use client';

import { useState, useEffect, useCallback } from 'react';
import { useKurirStore } from '@/lib/stores/kurir-store';
import { kurirApi } from '@/lib/kurir-api';
import { useKurirLocationBroadcast } from '@/hooks/useKurirLocationBroadcast';
import Link from 'next/link';
import toast from 'react-hot-toast';
import { getErrorMessage } from '@/lib';
import type { Order } from '@/types/delivery';

interface OrderItem {
  id: number;
  orderNumber: string;
  status: string;
  paymentMethod: string;
  paymentStatus: string;
  deliveryAddress: string;
  customerNotes: string | null;
  finalAmount: number;
  customer: { name: string; phone: string; };
  itemCount: number;
  createdAt: string;
  assignedAt: string | null;
}

interface AvailableOrder {
  id: number;
  order_number: string;
  status: string;
  payment_method: string;
  delivery_address: string;
  customer_notes: string | null;
  total_amount: number;
  customer: { name: string; phone: string; };
  created_at: string;
}

interface ProfileStats {
  activeOrders: number;
  todayDeliveries: number;
  todayEarnings: number;
}

export default function KurirDashboard() {
  const { user, setStatus } = useKurirStore();
  const [orders, setOrders] = useState<OrderItem[]>([]);
  const [availableOrders, setAvailableOrders] = useState<AvailableOrder[]>([]);
  const [stats, setStats] = useState<ProfileStats>({ activeOrders: 0, todayDeliveries: 0, todayEarnings: 0 });
  const [loading, setLoading] = useState(true);
  const [toggling, setToggling] = useState(false);
  const [activeTab, setActiveTab] = useState<'active' | 'available'>('active');
  const [claimingOrder, setClaimingOrder] = useState<string | null>(null);
  const [loadingAvailable, setLoadingAvailable] = useState(false);

  const fetchData = useCallback(async () => {
    try {
      const [profileRes, ordersRes] = await Promise.all([
        kurirApi.getProfile(),
        kurirApi.getOrders('active'),
      ]);
      if (profileRes.success) {
        // normalize/guard stats so we always pass a complete ProfileStats object
        setStats({
          activeOrders: profileRes.data?.stats?.activeOrders ?? 0,
          todayDeliveries: profileRes.data?.stats?.todayDeliveries ?? 0,
          todayEarnings: profileRes.data?.stats?.todayEarnings ?? 0,
        });
      }

      // Normalize incoming Order[] into our OrderItem[] shape to satisfy state typing
      if (ordersRes.success && Array.isArray(ordersRes.data)) {
        setOrders(
          ordersRes.data.map((o: Order) => ({
            id: (o.id ?? 0) as number,
            orderNumber: o.order_number ?? String(o.id ?? ''),
            status: o.status ?? '',
            paymentMethod: o.payment_method ?? '',
            paymentStatus: o.payment_status ?? '',
            deliveryAddress: o.delivery_address ?? '',
            customerNotes: o.customer_notes ?? null,
            finalAmount: o.final_amount ?? o.total_amount ?? 0,
            customer: { name: o.customer_name ?? 'Unknown', phone: o.customer_phone ?? '' },
            itemCount: 1,
            createdAt: o.created_at ?? '',
            assignedAt: o.assigned_at ?? null,
          } as OrderItem))
        );
      } else if (ordersRes.success) {
        setOrders([]);
      }
    } catch (err) {
      console.error('Dashboard fetch error:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchAvailableOrders = useCallback(async () => {
    if (user?.status === 'offline') {
      setAvailableOrders([]);
      return;
    }
    setLoadingAvailable(true);
    try {
      const res = await kurirApi.getAvailableOrders();
      if (res.success) {
        // normalize API response into AvailableOrder[] so TypeScript and UI fields align
        setAvailableOrders(
          res.data.map((o: { id?: number; order_number?: string; orderNumber?: string; status?: string; payment_method?: string; paymentMethod?: string; delivery_address?: string; deliveryAddress?: string; customer_notes?: string | null; customerNotes?: string | null; total_amount?: number; totalAmount?: number; customer?: { name?: string; phone?: string } | null; customer_name?: string; customerName?: string; customer_phone?: string; customerPhone?: string; created_at?: string; createdAt?: string }) => ({
            id: o.id as number,
            order_number: o.order_number ?? o.orderNumber ?? '',
            status: o.status ?? '',
            payment_method: o.payment_method ?? o.paymentMethod ?? '',
            delivery_address: o.delivery_address ?? o.deliveryAddress ?? '',
            customer_notes: o.customer_notes ?? o.customerNotes ?? null,
            total_amount: o.total_amount ?? o.totalAmount ?? 0,
            customer: o.customer ?? {
              name: o.customer_name ?? o.customerName ?? 'Unknown',
              phone: o.customer_phone ?? o.customerPhone ?? '',
            },
            created_at: o.created_at ?? o.createdAt ?? '',
          } as AvailableOrder))
        );
      }
    } catch (err) {
      console.error('Available orders fetch error:', err);
    } finally {
      setLoadingAvailable(false);
    }
  }, [user?.status]);

  useEffect(() => { fetchData(); }, [fetchData]);

  // Fetch available orders when tab changes or status changes
  useEffect(() => {
    if (activeTab === 'available') {
      fetchAvailableOrders();
    }
  }, [activeTab, fetchAvailableOrders]);

  // Auto-refresh available orders every 10 seconds when on available tab
  useEffect(() => {
    if (activeTab !== 'available' || user?.status === 'offline') return;
    const interval = setInterval(fetchAvailableOrders, 10000);
    return () => clearInterval(interval);
  }, [activeTab, user?.status, fetchAvailableOrders]);

  const handleClaimOrder = async (orderNumber: string) => {
    setClaimingOrder(orderNumber);
    try {
      const res = await kurirApi.claimOrder(orderNumber);
      if (res.success) {
        toast.success(res.message || 'Pesanan berhasil diambil!');
        // Refresh both lists
        await Promise.all([fetchData(), fetchAvailableOrders()]);
        // Switch to active tab to show claimed order
        setActiveTab('active');
      } else {
        toast.error(res.error || 'Gagal mengambil pesanan');
      }
    } catch (err: unknown) {
      toast.error(getErrorMessage(err) || 'Gagal mengambil pesanan');
    } finally {
      setClaimingOrder(null);
    }
  };

  // Real-time GPS location broadcasting (every 5 seconds when available/busy)
  const locationState = useKurirLocationBroadcast({
    enabled: user?.status === 'available' || user?.status === 'busy',
    interval: 5000, // 5 seconds
    highAccuracy: true,
  });

  // Show permission error toast once
  useEffect(() => {
    if (locationState.permissionDenied) {
      toast.error('Izin lokasi ditolak. Aktifkan lokasi untuk melanjutkan.', { duration: 5000 });
    }
  }, [locationState.permissionDenied]);

  const toggleOnline = async () => {
    if (!user) return;
    const newStatus = user.status === 'available' ? 'offline' : 'available';
    setToggling(true);
    try {
      const res = await kurirApi.updateStatus(newStatus);
      if (res.success) {
        setStatus(newStatus);
        toast.success(newStatus === 'available' ? 'Anda sekarang Online' : 'Anda sekarang Offline');
      }
    } catch (err: unknown) {
      toast.error(getErrorMessage(err) || 'Gagal mengubah status');
    } finally {
      setToggling(false);
    }
  };

  const statusColors: Record<string, string> = {
    confirmed: 'bg-blue-100 text-blue-700',
    processing: 'bg-yellow-100 text-yellow-700',
    ready: 'bg-purple-100 text-purple-700',
    delivering: 'bg-amber-100 text-amber-700',
  };
  const statusLabels: Record<string, string> = {
    confirmed: 'Dikonfirmasi', processing: 'Diproses', ready: 'Siap Antar', delivering: 'Sedang Antar',
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-60">
        <div className="w-8 h-8 border-3 border-amber-200 border-t-amber-600 rounded-full animate-spin"></div>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      {/* Greeting */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-lg font-bold text-gray-800 dark:text-white">Halo, {user?.name} 👋</h1>
          <p className="text-xs text-gray-500 dark:text-gray-400">
            {user?.vehicleType === 'motor' ? '🏍️' : user?.vehicleType === 'mobil' ? '🚗' : '🚲'} {user?.vehicleNumber || user?.vehicleType}
          </p>
        </div>
        <button onClick={toggleOnline} disabled={toggling || user?.status === 'busy'}
          className={`relative w-14 h-7 rounded-full transition-colors ${
            user?.status === 'available' ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'
          } ${toggling ? 'opacity-50' : ''} ${user?.status === 'busy' ? 'cursor-not-allowed' : 'cursor-pointer'}`}
          title={user?.status === 'busy' ? 'Selesaikan pesanan terlebih dahulu' : undefined}>
          <div className={`absolute top-0.5 w-6 h-6 bg-white rounded-full shadow transition-transform ${
            user?.status === 'available' ? 'translate-x-7' : 'translate-x-0.5'
          }`}></div>
        </button>
      </div>

      {/* Location Broadcast Status */}
      {(user?.status === 'available' || user?.status === 'busy') && (
        <div className={`rounded-xl p-3 border ${
          locationState.broadcasting 
            ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' 
            : locationState.permissionDenied
            ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
            : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800'
        }`}>
          <div className="flex items-center gap-2">
            {locationState.broadcasting ? (
              <>
                <span className="relative flex h-3 w-3">
                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span className="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <div className="flex-1">
                  <p className="text-xs font-semibold text-green-700 dark:text-green-300">
                    📍 Live Tracking Aktif
                  </p>
                  {locationState.lastUpdate && (
                    <p className="text-[10px] text-green-600 dark:text-green-400">
                      Update terakhir: {locationState.lastUpdate.toLocaleTimeString('id-ID')}
                    </p>
                  )}
                </div>
              </>
            ) : locationState.permissionDenied ? (
              <>
                <i className="bi bi-exclamation-triangle text-red-500"></i>
                <p className="text-xs font-medium text-red-700 dark:text-red-300">
                  Izin lokasi ditolak. Aktifkan di pengaturan browser.
                </p>
              </>
            ) : (
              <>
                <div className="w-3 h-3 border-2 border-yellow-500 border-t-transparent rounded-full animate-spin"></div>
                <p className="text-xs font-medium text-yellow-700 dark:text-yellow-300">
                  Menghubungkan GPS...
                </p>
              </>
            )}
          </div>
        </div>
      )}

      {/* Stats */}
      <div className="grid grid-cols-3 gap-3">
        <div className="bg-white dark:bg-[#2a2a2a] rounded-xl p-3 text-center border border-gray-100 dark:border-gray-700">
          <p className="text-2xl font-bold text-amber-600">{stats.activeOrders}</p>
          <p className="text-[10px] text-gray-500 mt-0.5">Pesanan Aktif</p>
        </div>
        <div className="bg-white dark:bg-[#2a2a2a] rounded-xl p-3 text-center border border-gray-100 dark:border-gray-700">
          <p className="text-2xl font-bold text-green-600">{stats.todayDeliveries}</p>
          <p className="text-[10px] text-gray-500 mt-0.5">Antar Hari Ini</p>
        </div>
        <div className="bg-white dark:bg-[#2a2a2a] rounded-xl p-3 text-center border border-gray-100 dark:border-gray-700">
          <p className="text-lg font-bold text-blue-600">Rp {(stats.todayEarnings / 1000).toFixed(0)}k</p>
          <p className="text-[10px] text-gray-500 mt-0.5">Pendapatan</p>
        </div>
      </div>

      {/* Tabs */}
      <div className="flex bg-gray-100 dark:bg-gray-800 rounded-xl p-1">
        <button
          onClick={() => setActiveTab('active')}
          className={`flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-all ${
            activeTab === 'active'
              ? 'bg-white dark:bg-gray-700 text-amber-600 shadow-sm'
              : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'
          }`}
        >
          <i className="bi bi-box-seam mr-1.5"></i>
          Pesanan Saya {orders.length > 0 && <span className="ml-1 bg-amber-100 text-amber-600 px-1.5 py-0.5 rounded-full text-xs">{orders.length}</span>}
        </button>
        <button
          onClick={() => setActiveTab('available')}
          className={`flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-all ${
            activeTab === 'available'
              ? 'bg-white dark:bg-gray-700 text-green-600 shadow-sm'
              : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'
          }`}
        >
          <i className="bi bi-collection mr-1.5"></i>
          Ambil Pesanan {availableOrders.length > 0 && <span className="ml-1 bg-green-100 text-green-600 px-1.5 py-0.5 rounded-full text-xs">{availableOrders.length}</span>}
        </button>
      </div>

      {/* Active Orders Tab */}
      {activeTab === 'active' && (
      <div>
        <h2 className="font-bold text-gray-800 dark:text-gray-100 mb-3 flex items-center gap-2">
          <i className="bi bi-box-seam text-amber-600"></i> Pesanan Aktif
        </h2>

        {orders.length === 0 ? (
          <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl p-8 text-center border border-gray-100 dark:border-gray-700">
            <i className="bi bi-inbox text-4xl text-gray-300 dark:text-gray-600 mb-3 block"></i>
            <p className="text-gray-500 dark:text-gray-400 text-sm">
              {user?.status === 'available' ? 'Belum ada pesanan. Coba ambil di tab "Ambil Pesanan"' : 'Aktifkan status Online untuk menerima pesanan'}
            </p>
            {user?.status === 'available' && (
              <button
                onClick={() => setActiveTab('available')}
                className="mt-4 px-4 py-2 bg-green-500 text-white rounded-lg text-sm font-medium hover:bg-green-600 transition-colors"
              >
                Lihat Pesanan Tersedia
              </button>
            )}
          </div>
        ) : (
          <div className="space-y-3">
            {orders.map(order => (
              <Link key={order.id} href={`/kurir/order/${order.orderNumber}`}
                className="block bg-white dark:bg-[#2a2a2a] rounded-xl p-4 border border-gray-100 dark:border-gray-700 hover:border-amber-200 hover:shadow-md transition-all">
                <div className="flex items-start justify-between mb-2">
                  <div>
                    <span className="font-semibold text-sm text-gray-800 dark:text-gray-100">#{order.orderNumber.split('-').pop()}</span>
                    <span className={`ml-2 text-[10px] px-2 py-0.5 rounded-full font-medium ${statusColors[order.status] || 'bg-gray-100 text-gray-600'}`}>
                      {statusLabels[order.status] || order.status}
                    </span>
                  </div>
                  <span className="text-xs text-gray-400">{new Date(order.createdAt).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</span>
                </div>

                <div className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 mb-2">
                  <i className="bi bi-person"></i>
                  <span>{order.customer.name}</span>
                  <span className="text-gray-300">•</span>
                  <span>{order.itemCount} item</span>
                </div>

                <div className="flex items-start gap-2 text-xs text-gray-500 dark:text-gray-400 mb-3">
                  <i className="bi bi-geo-alt text-red-400 mt-0.5 flex-shrink-0"></i>
                  <span className="line-clamp-1">{order.deliveryAddress}</span>
                </div>

                <div className="flex items-center justify-between pt-2 border-t border-gray-50 dark:border-gray-700">
                  <span className="font-bold text-amber-700 dark:text-amber-400 text-sm">
                    Rp {order.finalAmount.toLocaleString('id-ID')}
                  </span>
                  {order.paymentMethod === 'cod' && (
                    <span className="text-[10px] bg-red-50 text-red-600 px-2 py-0.5 rounded-full font-medium">
                      COD
                    </span>
                  )}
                  <i className="bi bi-chevron-right text-gray-300"></i>
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
      )}

      {/* Available Orders Tab */}
      {activeTab === 'available' && (
        <div>
          <h2 className="font-bold text-gray-800 dark:text-gray-100 mb-3 flex items-center gap-2">
            <i className="bi bi-collection text-green-600"></i> Pesanan Tersedia
            <button 
              onClick={fetchAvailableOrders} 
              disabled={loadingAvailable}
              className="ml-auto text-xs text-gray-400 hover:text-gray-600"
            >
              <i className={`bi bi-arrow-clockwise ${loadingAvailable ? 'animate-spin' : ''}`}></i>
            </button>
          </h2>

          {user?.status === 'offline' ? (
            <div className="bg-yellow-50 dark:bg-yellow-900/20 rounded-2xl p-6 text-center border border-yellow-200 dark:border-yellow-800">
              <i className="bi bi-toggle-off text-4xl text-yellow-400 mb-3 block"></i>
              <p className="text-yellow-700 dark:text-yellow-300 text-sm font-medium">
                Aktifkan status Online untuk melihat dan mengambil pesanan
              </p>
            </div>
          ) : loadingAvailable ? (
            <div className="flex items-center justify-center h-40">
              <div className="w-8 h-8 border-3 border-green-200 border-t-green-600 rounded-full animate-spin"></div>
            </div>
          ) : availableOrders.length === 0 ? (
            <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl p-8 text-center border border-gray-100 dark:border-gray-700">
              <i className="bi bi-check-circle text-4xl text-green-400 mb-3 block"></i>
              <p className="text-gray-500 dark:text-gray-400 text-sm">
                Semua pesanan sudah diambil. Refresh untuk cek pesanan baru.
              </p>
            </div>
          ) : (
            <div className="space-y-3">
              {availableOrders.map(order => (
                <div key={order.id} className="bg-white dark:bg-[#2a2a2a] rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                  <div className="flex items-start justify-between mb-2">
                    <div>
                      <span className="font-semibold text-sm text-gray-800 dark:text-gray-100">
                        #{order.order_number.split('-').pop()}
                      </span>
                      <span className="ml-2 text-[10px] px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700">
                        Tersedia
                      </span>
                    </div>
                    <span className="text-xs text-gray-400">
                      {new Date(order.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
                    </span>
                  </div>

                  <div className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 mb-2">
                    <i className="bi bi-person"></i>
                    <span>{order.customer.name}</span>
                    {order.payment_method === 'cod' && (
                      <>
                        <span className="text-gray-300">•</span>
                        <span className="text-red-500 font-medium">COD</span>
                      </>
                    )}
                  </div>

                  <div className="flex items-start gap-2 text-xs text-gray-500 dark:text-gray-400 mb-3">
                    <i className="bi bi-geo-alt text-red-400 mt-0.5 flex-shrink-0"></i>
                    <span className="line-clamp-2">{order.delivery_address}</span>
                  </div>

                  <div className="flex items-center justify-between pt-3 border-t border-gray-50 dark:border-gray-700">
                    <span className="font-bold text-green-600 text-sm">
                      Rp {order.total_amount.toLocaleString('id-ID')}
                    </span>
                    <button
                      onClick={() => handleClaimOrder(order.order_number)}
                      disabled={claimingOrder === order.order_number}
                      className="px-4 py-1.5 bg-green-500 text-white rounded-lg text-xs font-medium hover:bg-green-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5"
                    >
                      {claimingOrder === order.order_number ? (
                        <>
                          <div className="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                          Mengambil...
                        </>
                      ) : (
                        <>
                          <i className="bi bi-hand-index"></i>
                          Ambil Pesanan
                        </>
                      )}
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
