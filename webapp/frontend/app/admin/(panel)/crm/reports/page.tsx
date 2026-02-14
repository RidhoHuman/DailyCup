'use client';

import { useEffect, useState, useCallback } from 'react';
import { api, endpoints } from '@/lib/api-client';

export default function CRMReports() {
  interface TopCustomer { id: number; name: string; total_spend: number; orders_count: number; }
  interface TopProduct { id: number; name: string; qty_sold: number; unique_buyers: number; }

  const [topCustomers, setTopCustomers] = useState<TopCustomer[]>([]);
  const [topProducts, setTopProducts] = useState<TopProduct[]>([]);
  const [from, setFrom] = useState<string>(() => new Date(Date.now()-1000*60*60*24*30).toISOString().slice(0,10));
  const [to, setTo] = useState<string>(() => new Date().toISOString().slice(0,10));
  const [loading, setLoading] = useState(false);

  const fetchReports = useCallback(async () => {
    setLoading(true);
    try {
      const q = new URLSearchParams(); q.set('from', from); q.set('to', to);
      const res = await api.get<{ success: boolean; top_customers?: TopCustomer[]; top_products?: TopProduct[] }>(endpoints.admin.reports() + '&' + q.toString());
      if (res.success) { setTopCustomers(res.top_customers || []); setTopProducts(res.top_products || []); }
    } catch (e: unknown) { console.error(e); }
    setLoading(false);
  }, [from, to]);

  useEffect(()=>{ fetchReports(); }, [fetchReports]);

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-4">CRM Reports</h1>
      <div className="bg-white p-4 rounded mb-4">
        <div className="flex gap-3 items-center">
          <label className="text-sm">From</label>
          <input type="date" className="p-2 border" value={from} onChange={(e)=>setFrom(e.target.value)} />
          <label className="text-sm">To</label>
          <input type="date" className="p-2 border" value={to} onChange={(e)=>setTo(e.target.value)} />
          <button className="px-3 py-1 bg-blue-600 text-white rounded" onClick={fetchReports} disabled={loading}>{loading ? 'Loading...' : 'Refresh'}</button>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="bg-white p-4 rounded">
          <h3 className="font-semibold mb-2">Top 10 Customers</h3>
          <ol className="space-y-2">
            {topCustomers.map((c: TopCustomer, idx:number) => (<li key={c.id} className="text-sm">{idx+1}. {c.name} — Rp {Number(c.total_spend||0).toLocaleString()} ({c.orders_count} orders)</li>))}
          </ol>
        </div>

        <div className="bg-white p-4 rounded">
          <h3 className="font-semibold mb-2">Top Products</h3>
          <ol className="space-y-2">
            {topProducts.map((p: TopProduct, idx:number) => (<li key={p.id} className="text-sm">{idx+1}. {p.name} — {p.qty_sold} sold — {p.unique_buyers} buyers</li>))}
          </ol>
        </div>
      </div>
    </div>
  );
}
