"use client";

import { useState, useEffect, useCallback } from "react";
import { api } from "@/lib/api-client";

interface AnalyticsData {
  revenue: {
    total_revenue: number;
    total_orders: number;
    paid_orders: number;
    pending_orders: number;
    average_order_value: number;
    conversion_rate: number;
  };
  best_sellers: Array<{
    product_id: number;
    product_name: string;
    total_sold: number;
    total_revenue: string;
  }>;
  payment_methods: Array<{
    payment_method: string;
    count: number;
    total_amount: string;
  }>;
  order_status: Array<{
    payment_status: string;
    count: number;
    total_amount: string;
  }>;
  comparison: {
    revenue_change: number;
    orders_change: number;
  };
}

export default function AdminAnalyticsPage() {
  const [period, setPeriod] = useState("30days");
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);

  const fetchAnalytics = useCallback(async () => {
    try {
      setLoading(true);
      const response = await api.get<{ success: boolean; analytics: AnalyticsData }>(
        `/analytics.php?period=${period}`
      );
      if (response.success) {
        setAnalytics(response.analytics);
      }
    } catch (error) {
      console.error("Error fetching analytics:", error);
    } finally {
      setLoading(false);
    }
  }, [period]);

  useEffect(() => {
    fetchAnalytics();
  }, [fetchAnalytics]);

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
    }).format(amount);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-gray-500">Loading analytics...</div>
      </div>
    );
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">Analytics & Reports</h1>
          <p className="text-gray-500">Detailed analytics and business insights</p>
        </div>
        
        {/* Period Selector */}
        <select
          value={period}
          onChange={(e) => setPeriod(e.target.value)}
          className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
        >
          <option value="7days">Last 7 Days</option>
          <option value="30days">Last 30 Days</option>
          <option value="90days">Last 90 Days</option>
          <option value="year">Last Year</option>
        </select>
      </div>

      {/* Revenue Overview */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="text-sm text-gray-500 mb-1">Total Revenue</div>
          <div className="text-2xl font-bold text-gray-800">
            {formatCurrency(analytics?.revenue?.total_revenue || 0)}
          </div>
          <div className="text-xs text-green-600 mt-1">
            {(analytics?.comparison?.revenue_change ?? 0) > 0 ? "+" : ""}
            {analytics?.comparison?.revenue_change?.toFixed(1) ?? "0"}% vs previous
          </div>
        </div>
        
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="text-sm text-gray-500 mb-1">Total Orders</div>
          <div className="text-2xl font-bold text-gray-800">
            {analytics?.revenue?.total_orders || 0}
          </div>
          <div className="text-xs text-gray-600 mt-1">
            {analytics?.revenue?.paid_orders || 0} paid
          </div>
        </div>
        
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="text-sm text-gray-500 mb-1">Avg Order Value</div>
          <div className="text-2xl font-bold text-gray-800">
            {formatCurrency(analytics?.revenue?.average_order_value || 0)}
          </div>
        </div>
        
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="text-sm text-gray-500 mb-1">Conversion Rate</div>
          <div className="text-2xl font-bold text-gray-800">
            {analytics?.revenue?.conversion_rate?.toFixed(1) || 0}%
          </div>
        </div>
      </div>

      {/* Best Selling Products */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 className="text-lg font-bold text-gray-800 mb-4">Best Selling Products</h2>
        <div className="space-y-3">
          {analytics?.best_sellers?.slice(0, 5).map((product, index) => (
            <div key={index} className="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
              <div className="flex-1">
                <div className="font-medium text-gray-800">{product.product_name}</div>
                <div className="text-sm text-gray-500">{product.total_sold} sold</div>
              </div>
              <div className="font-semibold text-gray-800">
                {formatCurrency(parseFloat(product.total_revenue))}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Payment Methods & Order Status */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h2 className="text-lg font-bold text-gray-800 mb-4">Payment Methods</h2>
          <div className="space-y-3">
            {analytics?.payment_methods?.map((method, index) => (
              <div key={index} className="flex items-center justify-between">
                <span className="text-gray-600">{method.payment_method || "Unknown"}</span>
                <div className="flex items-center gap-2">
                  <span className="text-sm text-gray-500">{method.count} orders</span>
                  <span className="font-semibold text-gray-800">
                    {formatCurrency(parseFloat(method.total_amount))}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h2 className="text-lg font-bold text-gray-800 mb-4">Order Status</h2>
          <p className="text-sm text-gray-500 mb-4">Distribusi status pesanan dalam bentuk diagram lingkaran. Warna menunjukkan status: <span className="text-green-600">Hijau</span> = Paid, <span className="text-yellow-600">Kuning</span> = Pending, <span className="text-red-600">Merah</span> = Failed, <span className="text-gray-600">Abu-abu</span> = Refunded</p>
          {/* Pie Chart Visualization */}
          <div className="flex items-center justify-center mb-6">
            <div className="relative w-48 h-48">
              {(() => {
                const statusData = analytics?.order_status || [];
                const total = statusData.reduce((sum, s) => sum + s.count, 0) || 1;
                let currentAngle = 0;
                
                const colors: Record<string, string> = {
                  'paid': '#22c55e',
                  'pending': '#eab308',
                  'failed': '#ef4444',
                  'refunded': '#6b7280'
                };
                
                const segments = statusData.map(status => {
                  const percentage = (status.count / total) * 100;
                  const startAngle = currentAngle;
                  currentAngle += (percentage / 100) * 360;
                  return { ...status, percentage, startAngle, color: colors[status.payment_status] || '#6b7280' };
                });
                
                // Create conic gradient
                const gradientStops = segments.map((s, i) => {
                  const start = segments.slice(0, i).reduce((acc, seg) => acc + seg.percentage, 0);
                  const end = start + s.percentage;
                  return `${s.color} ${start}% ${end}%`;
                }).join(', ');
                
                return (
                  <div 
                    className="w-48 h-48 rounded-full"
                    style={{ 
                      background: segments.length > 0 
                        ? `conic-gradient(${gradientStops})` 
                        : '#e5e7eb'
                    }}
                  >
                    <div className="absolute inset-4 bg-white rounded-full flex items-center justify-center">
                      <div className="text-center">
                        <div className="text-2xl font-bold text-gray-800">{total}</div>
                        <div className="text-xs text-gray-500">Orders</div>
                      </div>
                    </div>
                  </div>
                );
              })()}
            </div>
          </div>
          
          {/* Legend */}
          <div className="space-y-2">
            {analytics?.order_status?.map((status, index) => {
              const colors: Record<string, string> = {
                'paid': 'bg-green-500',
                'pending': 'bg-yellow-500',
                'failed': 'bg-red-500',
                'refunded': 'bg-gray-500'
              };
              return (
                <div key={index} className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className={`w-3 h-3 rounded-full ${colors[status.payment_status] || 'bg-gray-500'}`}></span>
                    <span className="capitalize text-gray-600">{status.payment_status}</span>
                  </div>
                  <span className="text-sm font-medium text-gray-800">{status.count}</span>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}
