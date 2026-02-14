'use client';

import { useEffect, useState } from 'react';
import { api, endpoints } from '@/lib/api-client';

export default function CustomersPage() {
  interface OrderSummary { id: number; order_number: string; total: number; status: string; created_at: string; }
  interface Customer { id: number; name: string; phone?: string; email?: string; total_spend?: number; orders_count?: number; last_order_at?: string; joined_at?: string; orders?: OrderSummary[]; }

  const [customers, setCustomers] = useState<Customer[]>([]);
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(50);
  const [total, setTotal] = useState(0);
  const [search, setSearch] = useState('');
  const [segment, setSegment] = useState('');
  const [loading, setLoading] = useState(false);
  const [selected, setSelected] = useState<Customer|null>(null);
  const [broadcastOpen, setBroadcastOpen] = useState(false);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [template, setTemplate] = useState('gajian');
  const [sending, setSending] = useState(false);

  const fetchCustomers = async () => {
    setLoading(true);
    try {
      const q = new URLSearchParams();
      q.set('page', String(page)); q.set('limit', String(limit));
      if (search) q.set('search', search);
      if (segment) q.set('segment', segment);
      const res = await api.get<{ success: boolean; customers?: Customer[]; total?: number }>(endpoints.admin.customers() + '&' + q.toString());
      if (res.success) { setCustomers(res.customers || []); setTotal(res.total || 0); }
    } catch (e: unknown) { console.error(e); }
    setLoading(false);
  };

  useEffect(()=>{ fetchCustomers(); }, [page, limit, search, segment]);

  const openDetail = async (id:number) => {
    try {
      const res = await api.get<{ success: boolean; customer?: Customer }>(endpoints.admin.customer(id));
      if (res.success) setSelected(res.customer || null);
    } catch (e: unknown) { console.error(e); }
  };

  const toggleSelect = (id:number) => {
    setSelectedIds(prev => prev.includes(id) ? prev.filter(x=>x!==id) : [...prev,id]);
  };

  type BroadcastPayload = { template: string; user_ids?: number[]; segment?: string };
  const sendBroadcast = async () => {
    if (!confirm('Send broadcast?')) return;
    setSending(true);
    try {
      const payload: BroadcastPayload = { template };
      if (selectedIds.length) payload.user_ids = selectedIds;
      else if (segment) payload.segment = segment;
      else { alert('Select users or segment'); setSending(false); return; }

      const res = await api.post<{ success: boolean; sent?: number; failed?: number }>(endpoints.admin.broadcast(), payload);
      if (res.success) { alert('Sent: ' + (res.sent ?? 0) + ', failed: ' + (res.failed ?? 0)); setBroadcastOpen(false); setSelectedIds([]); fetchCustomers(); }
      else alert('Broadcast failed');
    } catch (e: unknown) { const msg = e instanceof Error ? e.message : String(e); alert('Failed: ' + msg); }
    setSending(false);
  };

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-4">CRM — Customers</h1>
      <div className="bg-white p-4 rounded mb-4">
        <div className="flex gap-3 items-center">
          <input placeholder="Search name, email, phone" className="p-2 border flex-1" value={search} onChange={(e)=>setSearch(e.target.value)} />
          <select className="p-2 border" value={segment} onChange={(e)=>{ setSegment(e.target.value); setPage(1); }}>
            <option value="">All</option>
            <option value="new">New (&lt;30d)</option>
            <option value="vip">VIP (&gt; Rp500.000)</option>
            <option value="passive">Passive (&gt; 60d)</option>
          </select>
          <button onClick={()=>setBroadcastOpen(true)} className="px-3 py-1 bg-blue-600 text-white rounded">Broadcast</button>
        </div>
      </div>

      <div className="bg-white p-4 rounded">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-xs text-gray-500">
              <tr><th className="px-3 py-2">Sel</th><th className="px-3 py-2">Name</th><th className="px-3 py-2">Phone</th><th className="px-3 py-2">Email</th><th className="px-3 py-2">Total Spend</th><th className="px-3 py-2">Orders</th><th className="px-3 py-2">Last Order</th></tr>
            </thead>
            <tbody>
              {loading ? <tr><td colSpan={7} className="p-4 text-center">Loading...</td></tr> : (
                customers.map(c => (
                  <tr key={c.id} className="border-t hover:bg-gray-50 cursor-pointer">
                    <td className="px-3 py-2"><input type="checkbox" checked={selectedIds.includes(c.id)} onChange={()=>toggleSelect(c.id)} /></td>
                    <td className="px-3 py-2" onClick={()=>openDetail(c.id)}>{c.name}</td>
                    <td className="px-3 py-2">{c.phone}</td>
                    <td className="px-3 py-2">{c.email}</td>
                    <td className="px-3 py-2">Rp {Number(c.total_spend||0).toLocaleString()}</td>
                    <td className="px-3 py-2">{c.orders_count||0}</td>
                    <td className="px-3 py-2">{c.last_order_at||c.joined_at}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
        <div className="flex items-center justify-between mt-3">
          <div>Page {page} • {Math.ceil(total/limit) || 1}</div>
          <div className="space-x-2">
            <button onClick={()=>setPage(p=>Math.max(1,p-1))} disabled={page<=1} className="px-3 py-1 border rounded">Prev</button>
            <button onClick={()=>setPage(p=>p+1)} className="px-3 py-1 border rounded">Next</button>
          </div>
        </div>
      </div>

      {selected && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center">
          <div className="bg-white rounded p-4 w-3/4 max-h-[80vh] overflow-auto">
            <div className="flex justify-between items-center mb-2">
              <h4 className="font-semibold">{selected.name}</h4>
              <button onClick={()=>setSelected(null)} className="px-2 py-1 border rounded">Close</button>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <p className="text-sm">Phone: {selected.phone}</p>
                <p className="text-sm">Email: {selected.email}</p>
                <p className="text-sm">Total Spend: Rp {Number(selected.total_spend||0).toLocaleString()}</p>
              </div>
              <div>
                <h5 className="font-semibold">Recent Orders</h5>
                <ul>
                  {selected.orders && selected.orders.length ? selected.orders.map((o: OrderSummary) => (<li key={o.id} className="text-sm">{o.order_number} — Rp {Number(o.total).toLocaleString()} — {o.status} — {o.created_at}</li>)) : <li className="text-sm">No orders</li>}
                </ul>
              </div>
            </div>
          </div>
        </div>
      )}

      {broadcastOpen && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center">
          <div className="bg-white rounded p-4 w-1/2">
            <h4 className="font-semibold mb-2">Broadcast</h4>
            <div className="mb-3">
              <label className="text-sm">Template</label>
              <select className="p-2 border w-full" value={template} onChange={(e)=>setTemplate(e.target.value)}>
                <option value="gajian">Promo Gajian</option>
                <option value="reminder">Pengingat Checkout</option>
              </select>
            </div>
            <div className="mb-3">
              <p className="text-sm">Recipients: {selectedIds.length} selected • segment: {segment || 'none'}</p>
            </div>
            <div className="flex justify-end gap-2">
              <button onClick={()=>setBroadcastOpen(false)} className="px-3 py-1 border rounded">Cancel</button>
              <button onClick={sendBroadcast} disabled={sending} className="px-3 py-1 bg-blue-600 text-white rounded">Send</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
