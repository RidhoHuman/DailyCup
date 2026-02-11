'use client';

import { useState, useEffect } from 'react';
import { useAuthStore } from '@/lib/stores/auth-store';
import { api, endpoints } from '@/lib/api-client';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import { Line, Bar, Doughnut } from 'react-chartjs-2';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

interface AnalyticsData {
  period: string;
  date_range: { start: string; end: string };
  revenue: {
    total: number;
    avg_order_value: number;
    highest_order: number;
    growth_percentage: number;
  };
  orders: {
    total: number;
    completed: number;
    cancelled: number;
    growth_percentage: number;
    status_distribution: Array<{ status: string; count: number }>;
  };
  customers: {
    total: number;
    new: number;
    top_customers: Array<{
      id: number;
      name: string;
      email: string;
      total_orders: number;
      total_spent: number;
    }>;
  };
  products: {
    top_selling: Array<{
      id: number;
      name: string;
      category: string;
      price: number;
      times_ordered: number;
      total_quantity: number;
      total_revenue: number;
    }>;
    category_performance: Array<{
      category: string;
      orders: number;
      items_sold: number;
      revenue: number;
    }>;
  };
  trends: {
    daily_revenue: Array<{ date: string; orders: number; revenue: number }>;
    peak_hours: Array<{ hour: number; orders: number; revenue: number }>;
  };
  payment_methods: Array<{
    payment_method: string;
    count: number;
    revenue: number;
  }>;
  reviews: {
    avg_rating: number;
    total: number;
  };
}

export default function AnalyticsPage() {
  const { user } = useAuthStore();
  const [period, setPeriod] = useState('30days');
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchAnalytics();
  }, [period]);

  const fetchAnalytics = async () => {
    setLoading(true);
    try {
      // CRITICAL: Get token from Zustand auth store (localStorage 'dailycup-auth')
      const authData = localStorage.getItem('dailycup-auth');
      const token = authData ? JSON.parse(authData)?.state?.token : null;
      
      if (!token) {
        console.error('[Analytics] No authentication token found');
        setLoading(false);
        return;
      }

      // Use Next.js API rewrites (/api) instead of hardcoded URL
      const response = await fetch(
        `/api/analytics.php?period=${period}`,
        {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
          credentials: 'include',
        }
      );

      if (!response.ok) {
        const errorText = await response.text();
        console.error('[Analytics] API Error:', response.status, errorText);
        throw new Error(`HTTP ${response.status}: ${errorText}`);
      }

      const data = await response.json();
      if (data.success) {
        setAnalytics(data);
      } else {
        console.error('[Analytics] API returned success=false:', data);
      }
    } catch (error) {
      console.error('[Analytics] Error fetching analytics:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const formatPercentage = (value: number) => {
    const sign = value >= 0 ? '+' : '';
    return `${sign}${value.toFixed(2)}%`;
  };

  if (loading || !analytics) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading analytics...</p>
        </div>
      </div>
    );
  }

  // Chart Data
  const dailyRevenueChart = {
    labels: analytics.trends.daily_revenue.map((d: { date: string }) =>
      new Date(d.date).toLocaleDateString('id-ID', { month: 'short', day: 'numeric' })
    ),
    datasets: [
      {
        label: 'Revenue',
        data: analytics.trends.daily_revenue.map((d: { revenue: number }) => d.revenue),
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.4,
      },
    ],
  };

  const peakHoursChart = {
    labels: analytics.trends.peak_hours.map((h: { hour: number }) => `${h.hour}:00`),
    datasets: [
      {
        label: 'Orders',
        data: analytics.trends.peak_hours.map((h: { orders: number }) => h.orders),
        backgroundColor: 'rgba(34, 197, 94, 0.8)',
      },
    ],
  };

  const categoryChart = {
    labels: analytics.products.category_performance.map((c: { category: string }) => c.category),
    datasets: [
      {
        label: 'Revenue',
        data: analytics.products.category_performance.map((c: { revenue: number }) => c.revenue),
        backgroundColor: [
          'rgba(239, 68, 68, 0.8)',
          'rgba(59, 130, 246, 0.8)',
          'rgba(34, 197, 94, 0.8)',
          'rgba(234, 179, 8, 0.8)',
          'rgba(168, 85, 247, 0.8)',
        ],
      },
    ],
  };

  const paymentMethodChart = {
    labels: analytics.payment_methods.map((p: { payment_method: string }) => p.payment_method),
    datasets: [
      {
        data: analytics.payment_methods.map((p: { count: number }) => p.count),
        backgroundColor: [
          'rgba(59, 130, 246, 0.8)',
          'rgba(34, 197, 94, 0.8)',
          'rgba(239, 68, 68, 0.8)',
        ],
      },
    ],
  };

  // Integration summary (Twilio) state
  const [integrationSummary, setIntegrationSummary] = useState<any[]>([]);

  useEffect(() => { fetchIntegrationSummary(); }, []);

  const fetchIntegrationSummary = async () => {
    try {
      const res: any = await api.get(endpoints.admin.analytics());
      if (res.success) setIntegrationSummary(res.summary || []);
    } catch (e) { console.error('Failed fetching integration analytics', e); }
  };

  // Provider breakdown state and fetcher
  const [selectedProvider, setSelectedProvider] = useState<string>('twilio');
  const [providerFrom, setProviderFrom] = useState<string>(new Date(Date.now()-1000*60*60*24*30).toISOString().slice(0,10));
  const [providerTo, setProviderTo] = useState<string>(new Date().toISOString().slice(0,10));
  const [providerSeries, setProviderSeries] = useState<any[]>([]);
  const [selectedChannel, setSelectedChannel] = useState('');
  const [sparklines, setSparklines] = useState<Record<string, number[]>>({});

  // Load sparkline data for each provider (last 7 days)
  const loadSparklines = async () => {
    try {
      for (const p of integrationSummary) {
        const q = new URLSearchParams();
        q.set('provider', p.provider);
        const dTo = new Date().toISOString().slice(0,10);
        const dFrom = new Date(Date.now()-1000*60*60*24*7).toISOString().slice(0,10);
        q.set('from', dFrom); q.set('to', dTo);
        const url = `admin/analytics.php?action=provider&${q.toString()}`;
        const res: any = await api.get(url);
        if (res.success) {
          const vals = (res.series || []).map((s:any)=>parseInt(s.total_messages||0));
          setSparklines(prev=>({...prev,[p.provider]: vals}));
        }
      }
    } catch (e) { console.error('Failed loading sparklines', e); }
  };

  useEffect(()=>{ if (integrationSummary.length) loadSparklines(); }, [integrationSummary]);

  // Color palette for providers
  const providerColors: string[] = ['rgb(59,130,246)','rgb(34,197,94)','rgb(239,68,68)','rgb(234,179,8)','rgb(168,85,247)'];
  const providerBg = (idx:number) => `rgba(${providerColors[idx%providerColors.length].slice(4,-1)},0.08)`;
  const providerBorder = (idx:number) => providerColors[idx%providerColors.length];
  const fetchProviderSeries = async (provider: string) => {
    try {
      const q = new URLSearchParams();
      q.set('provider', provider);
      q.set('from', providerFrom);
      q.set('to', providerTo);
      if (selectedChannel) q.set('channel', selectedChannel);
      const url = `admin/analytics.php?action=provider&${q.toString()}`;
      const res: any = await api.get(url);
      if (res.success) {
        setProviderSeries(res.series || []);
      }
    } catch (e) {
      console.error('Failed fetching provider series', e);
    }
  };

  // Fetch default series on load
  useEffect(()=>{
    if (integrationSummary.length) {
      const p = integrationSummary[0]?.provider || 'twilio';
      setSelectedProvider(p);
      fetchProviderSeries(p);
    }
  }, [integrationSummary]);

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
          <p className="text-gray-600 mt-1">
            {new Date(analytics.date_range.start).toLocaleDateString('id-ID')} -{' '}
            {new Date(analytics.date_range.end).toLocaleDateString('id-ID')}
          </p>
        </div>
        <select
          value={period}
          onChange={(e) => setPeriod(e.target.value)}
          className="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="7days">Last 7 Days</option>
          <option value="30days">Last 30 Days</option>
          <option value="90days">Last 90 Days</option>
          <option value="1year">Last Year</option>
          <option value="all">All Time</option>
        </select>
      </div>

      {/* Integration KPIs */}
      <div className="flex items-center justify-end gap-4 text-sm text-gray-500">
        <div className="flex items-center gap-2"><span className="text-green-600">▲</span> increase vs prev 24h</div>
        <div className="flex items-center gap-2"><span className="text-red-600">▼</span> decrease vs prev 24h</div>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {integrationSummary.length === 0 ? (
          <div className="bg-white p-4 rounded shadow">No integration data</div>
        ) : integrationSummary.map((s: any) => (
          <div key={s.provider} className="bg-white p-4 rounded shadow">
            <div className="flex items-center justify-between">
              <h4 className="font-semibold">{s.provider}</h4>
              <div style={{width:120,height:40}}>
                {/* sparkline */}
                {sparklines[s.provider] && sparklines[s.provider].length > 0 ? (
                  <Line data={{ labels: sparklines[s.provider].map((_,i)=>i), datasets:[{ data: sparklines[s.provider], borderColor: providerBorder(integrationSummary.findIndex(x=>x.provider===s.provider)), backgroundColor: providerBg(integrationSummary.findIndex(x=>x.provider===s.provider)), fill:true, pointRadius:0 }] }} options={{ responsive:true, maintainAspectRatio:true, plugins:{legend:{display:false}, tooltip:{enabled:false}}, scales:{x:{display:false},y:{display:false}}}} />
                ) : (
                  <div className="text-xs text-gray-400">No sparkline</div>
                )}
              </div>
            </div>
            <div className="text-sm">Sent (24h): <strong>{s.sent_last_24h}</strong> <span className={`ml-2 text-sm font-medium ${s.sent_delta_pct >= 0 ? 'text-green-600' : 'text-red-600'}`}>{s.sent_delta_pct >= 0 ? '▲' : '▼'} {formatPercentage(s.sent_delta_pct)}</span></div>
            <div className="text-sm">Failed (24h): <strong>{s.failed_last_24h}</strong></div>
            <div className="text-sm">Pending retries: <strong>{s.retry_scheduled_total}</strong></div>
            <div className="text-sm">Avg retries: <strong>{parseFloat(s.avg_retry_count||0).toFixed(2)}</strong></div>
          </div>
        ))}
      </div>

      {/* Provider Breakdown */}
      <section className="bg-white p-4 rounded shadow">
        <div className="flex items-center gap-3 mb-3">
          <label className="text-sm text-gray-600">Provider</label>
          <select multiple className="p-2 border" value={selectedProvider ? selectedProvider.split(',') : []} onChange={(e)=>{ const vals = Array.from(e.target.selectedOptions).map(o=>o.value); setSelectedProvider(vals.join(',')); fetchProviderSeries(vals.join(',')); }}>
            {integrationSummary.map((s: any)=>(<option key={s.provider} value={s.provider}>{s.provider}</option>))}
          </select>

          <label className="text-sm text-gray-600">Channel</label>
          <select className="p-2 border" onChange={(e)=>{ setSelectedChannel(e.target.value); }}>
            <option value="">All</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="sms">SMS</option>
          </select>

          <label className="text-sm text-gray-600">From</label>
          <input type="date" className="p-2 border" value={providerFrom} onChange={(e)=>setProviderFrom(e.target.value)} />
          <label className="text-sm text-gray-600">To</label>
          <input type="date" className="p-2 border" value={providerTo} onChange={(e)=>setProviderTo(e.target.value)} />

          <button className="px-3 py-1 bg-blue-600 text-white rounded" onClick={()=>fetchProviderSeries(selectedProvider)}>Load</button>
          <input type="number" min={1} placeholder="days (opt)" className="p-2 border w-36" id="refreshDaysInput" />
          <button className="px-3 py-1 bg-gray-200 rounded" onClick={async ()=>{
            const valEl = document.getElementById('refreshDaysInput') as HTMLInputElement | null;
            const days = valEl && valEl.value ? parseInt(valEl.value) : null;
            if (!confirm('Refresh analytics cache (materialized table)?')) return;
            try {
              const body = days ? { days } : {};
              const res: any = await api.post('admin/analytics.php?action=refresh_materialized', body);
              alert('Refresh started: ' + (res.success ? 'OK' : 'Failed'));
              fetchIntegrationSummary();
            } catch (e: any) { alert('Failed refresh: ' + (e?.message || '')); }
          }}>Refresh Cache</button>

          <button className="px-3 py-1 bg-red-500 text-white rounded ml-2" onClick={async ()=>{
            if (!confirm('Send a test analytics alert (will trigger email/Slack if configured)?')) return;
            try {
              const res: any = await api.post('admin/analytics.php?action=test_alert', { note: 'Triggered from Admin UI' });
              alert('Test alert sent: ' + (res.success ? 'OK' : 'Failed'));
            } catch (e: any) { alert('Failed to send test alert: ' + (e?.message || '')); }
          }}>Test Alerts</button>
        </div>

        {providerSeries.length === 0 ? (
          <div className="text-sm text-gray-500">No data for selected period</div>
        ) : (
          <div>
            <div className="mb-4">
              {/* Build a multi-series chart when multiple providers selected */}
              {(() => {
                const days = Array.from(new Set(providerSeries.map((d:any)=>d.day))).sort();
                const providers = Array.from(new Set(providerSeries.map((d:any)=>d.provider)));
                const datasets = providers.map((p, idx) => ({
                  label: p,
                  data: days.map(day => {
                    const row = providerSeries.find((r:any)=>r.provider === p && r.day === day);
                    return row ? (row.total_messages||0) : 0;
                  }),
                  borderColor: ['rgb(59,130,246)','rgb(34,197,94)','rgb(239,68,68)'][idx%3],
                  backgroundColor: 'rgba(59,130,246,0.08)',
                  fill: false
                }));

                return <>
                  <Line data={{ labels: days, datasets }} options={{responsive:true, plugins:{legend:{display:true, position:'bottom'}}}} />
                  <div className="flex gap-3 mt-2">
                    {providers.map((p, idx)=> (
                      <div key={p} className="flex items-center gap-2">
                        <span style={{width:12,height:12,background:providerBorder(idx),display:'inline-block',borderRadius:3}}></span>
                        <span className="text-sm text-gray-700">{p}</span>
                      </div>
                    ))}
                  </div>
                </>;
              })()}
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-gray-50 text-xs text-gray-500">
                  <tr><th className="px-3 py-2">Day</th><th className="px-3 py-2">Channel</th><th className="px-3 py-2">Sent</th><th className="px-3 py-2">Delivered</th><th className="px-3 py-2">Failed</th></tr>
                </thead>
                <tbody>
                  {providerSeries.map((d:any)=> (
                    <tr key={d.day + d.channel} className="border-t"><td className="px-3 py-2">{d.day}</td><td className="px-3 py-2">{d.channel}</td><td className="px-3 py-2">{d.total_messages}</td><td className="px-3 py-2">{d.delivered_count}</td><td className="px-3 py-2">{d.failed_count}</td></tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </section>

      {/* Key Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Total Revenue</p>
              <p className="text-2xl font-bold text-gray-900 mt-2">
                {formatCurrency(analytics.revenue.total)}
              </p>
              <p
                className={`text-sm mt-2 ${
                  analytics.revenue.growth_percentage >= 0 ? 'text-green-600' : 'text-red-600'
                }`}
              >
                {formatPercentage(analytics.revenue.growth_percentage)} from previous period
              </p>
            </div>
            <div className="p-3 bg-blue-100 rounded-full">
              <svg
                className="w-6 h-6 text-blue-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Total Orders</p>
              <p className="text-2xl font-bold text-gray-900 mt-2">{analytics.orders.total}</p>
              <p
                className={`text-sm mt-2 ${
                  analytics.orders.growth_percentage >= 0 ? 'text-green-600' : 'text-red-600'
                }`}
              >
                {formatPercentage(analytics.orders.growth_percentage)} from previous period
              </p>
            </div>
            <div className="p-3 bg-green-100 rounded-full">
              <svg
                className="w-6 h-6 text-green-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                />
              </svg>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Avg Order Value</p>
              <p className="text-2xl font-bold text-gray-900 mt-2">
                {formatCurrency(analytics.revenue.avg_order_value)}
              </p>
              <p className="text-sm text-gray-500 mt-2">
                Highest: {formatCurrency(analytics.revenue.highest_order)}
              </p>
            </div>
            <div className="p-3 bg-purple-100 rounded-full">
              <svg
                className="w-6 h-6 text-purple-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                />
              </svg>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">New Customers</p>
              <p className="text-2xl font-bold text-gray-900 mt-2">{analytics.customers.new}</p>
              <p className="text-sm text-gray-500 mt-2">
                Total: {analytics.customers.total} customers
              </p>
            </div>
            <div className="p-3 bg-yellow-100 rounded-full">
              <svg
                className="w-6 h-6 text-yellow-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>

      {/* Charts Row 1 */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Daily Revenue Trend</h2>
          <Line data={dailyRevenueChart} options={{ responsive: true, maintainAspectRatio: true }} />
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Peak Hours</h2>
          <Bar data={peakHoursChart} options={{ responsive: true, maintainAspectRatio: true }} />
        </div>
      </div>

      {/* Charts Row 2 */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Category Performance</h2>
          <Bar data={categoryChart} options={{ responsive: true, maintainAspectRatio: true }} />
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Payment Methods</h2>
          <Doughnut
            data={paymentMethodChart}
            options={{ responsive: true, maintainAspectRatio: true }}
          />
        </div>
      </div>

      {/* Top Products & Customers */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Top Selling Products</h2>
          <div className="space-y-3">
            {analytics.products.top_selling.slice(0, 5).map((product: any, index: number) => (
              <div key={product.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div className="flex items-center space-x-3">
                  <span className="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-semibold">
                    {index + 1}
                  </span>
                  <div>
                    <p className="font-medium text-gray-900">{product.name}</p>
                    <p className="text-sm text-gray-500">{product.category}</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="font-semibold text-gray-900">{formatCurrency(product.total_revenue)}</p>
                  <p className="text-sm text-gray-500">{product.total_quantity} sold</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Top Customers</h2>
          <div className="space-y-3">
            {analytics.customers.top_customers.slice(0, 5).map((customer: any, index: number) => (
              <div key={customer.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div className="flex items-center space-x-3">
                  <span className="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full font-semibold">
                    {index + 1}
                  </span>
                  <div>
                    <p className="font-medium text-gray-900">{customer.name}</p>
                    <p className="text-sm text-gray-500">{customer.email}</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="font-semibold text-gray-900">{formatCurrency(customer.total_spent)}</p>
                  <p className="text-sm text-gray-500">{customer.total_orders} orders</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Order Status & Reviews */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Order Status Distribution</h2>
          <div className="space-y-3">
            {analytics.orders.status_distribution.map((status: any) => {
              const percentage = (status.count / analytics.orders.total) * 100;
              return (
                <div key={status.status}>
                  <div className="flex justify-between mb-1">
                    <span className="text-sm font-medium text-gray-700 capitalize">
                      {status.status}
                    </span>
                    <span className="text-sm font-medium text-gray-700">
                      {status.count} ({percentage.toFixed(1)}%)
                    </span>
                  </div>
                  <div className="w-full bg-gray-200 rounded-full h-2">
                    <div
                      className="bg-blue-600 h-2 rounded-full"
                      style={{ width: `${percentage}%` }}
                    ></div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Reviews Overview</h2>
          <div className="flex items-center justify-center h-full">
            <div className="text-center">
              <div className="text-6xl font-bold text-gray-900">{analytics.reviews.avg_rating}</div>
              <div className="flex justify-center my-3">
                {[...Array(5)].map((_, i) => (
                  <svg
                    key={i}
                    className={`w-8 h-8 ${
                      i < Math.floor(analytics.reviews.avg_rating)
                        ? 'text-yellow-400'
                        : 'text-gray-300'
                    }`}
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                ))}
              </div>
              <p className="text-gray-600">
                Based on {analytics.reviews.total} reviews
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
