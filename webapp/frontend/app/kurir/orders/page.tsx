'use client';

import { useState, useEffect, useCallback, useRef } from 'react';
import { kurirApi } from '@/lib/kurir-api';
import Link from 'next/link';

interface OrderItem {
  orderNumber: string;
  status: string;
  customerName: string;
  deliveryAddress: string;
  totalAmount: number;
  isCOD: boolean;
  itemCount: number;
  createdAt: string;
  completedAt: string | null;
}

export default function KurirOrderHistoryPage() {
  const [tab, setTab] = useState<'completed' | 'cancelled'>('completed');
  const [orders, setOrders] = useState<OrderItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [totalCount, setTotalCount] = useState(0);
  const loadedTab = useRef(tab);

  const fetchOrders = useCallback(async (pageNum: number, status: string, append = false) => {
    setLoading(true);
    try {
      const res = await kurirApi.getOrders(status, pageNum, 15);
      if (res.success) {
        const items = res.data || [];
        setOrders(prev => append ? [...prev, ...items] : items);
        setTotalCount(res.pagination?.total || items.length);
        setHasMore((res.pagination?.page || 1) < (res.pagination?.totalPages || 1));
      }
    } catch {
      // silent
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadedTab.current = tab;
    setPage(1);
    setOrders([]);
    fetchOrders(1, tab);
  }, [tab, fetchOrders]);

  const loadMore = () => {
    const next = page + 1;
    setPage(next);
    fetchOrders(next, tab, true);
  };

  const formatDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

  return (
    <div className="space-y-4">
      {/* Tabs */}
      <div className="flex bg-white dark:bg-[#2a2a2a] rounded-2xl p-1 border border-gray-100 dark:border-gray-700">
        {(['completed', 'cancelled'] as const).map(t => (
          <button key={t} onClick={() => setTab(t)}
            className={`flex-1 py-2.5 text-sm font-medium rounded-xl transition-colors ${
              tab === t ? 'bg-amber-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'
            }`}>
            {t === 'completed' ? 'Selesai' : 'Dibatalkan'}
          </button>
        ))}
      </div>

      {/* Count */}
      {!loading && (
        <p className="text-xs text-gray-400 px-1">{totalCount} pesanan</p>
      )}

      {/* List */}
      {loading && orders.length === 0 ? (
        <div className="flex items-center justify-center h-40">
          <div className="w-8 h-8 border-3 border-amber-200 border-t-amber-600 rounded-full animate-spin"></div>
        </div>
      ) : orders.length === 0 ? (
        <div className="text-center py-16">
          <i className={`bi ${tab === 'completed' ? 'bi-bag-check' : 'bi-x-circle'} text-4xl text-gray-300 block mb-3`}></i>
          <p className="text-gray-400 text-sm">Belum ada pesanan {tab === 'completed' ? 'selesai' : 'dibatalkan'}</p>
        </div>
      ) : (
        <div className="space-y-3">
          {orders.map(order => (
            <Link key={order.orderNumber} href={`/kurir/order/${order.orderNumber}`}
              className="block bg-white dark:bg-[#2a2a2a] rounded-2xl p-4 border border-gray-100 dark:border-gray-700 hover:border-amber-200 transition-colors">
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm font-bold text-gray-800 dark:text-gray-100">
                  #{order.orderNumber.split('-').pop()}
                </span>
                <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                  order.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                }`}>{order.status === 'completed' ? 'Selesai' : 'Batal'}</span>
              </div>
              <p className="text-sm text-gray-600 dark:text-gray-400 truncate">{order.customerName}</p>
              <p className="text-xs text-gray-400 truncate mt-0.5">{order.deliveryAddress}</p>
              <div className="flex items-center justify-between mt-2 pt-2 border-t border-gray-50 dark:border-gray-700">
                <span className="text-xs text-gray-400">
                  {formatDate(order.completedAt || order.createdAt)}
                </span>
                <div className="flex items-center gap-2">
                  {order.isCOD && <span className="text-xs text-red-500 font-medium">COD</span>}
                  <span className="text-sm font-bold text-amber-700 dark:text-amber-400">
                    Rp {order.totalAmount.toLocaleString('id-ID')}
                  </span>
                </div>
              </div>
            </Link>
          ))}

          {hasMore && (
            <button onClick={loadMore} disabled={loading}
              className="w-full py-3 text-sm text-amber-600 font-medium bg-amber-50 dark:bg-amber-900/10 rounded-2xl hover:bg-amber-100 transition-colors">
              {loading ? 'Memuat...' : 'Muat lebih banyak'}
            </button>
          )}
        </div>
      )}
    </div>
  );
}
