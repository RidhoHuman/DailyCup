"use client";

import { useState, useEffect } from "react";

// 1. Kita definisikan Interface agar tidak perlu pakai 'any'
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

// Interface untuk menangani response API umum
interface ApiResponse {
  success: boolean;
  analytics: AnalyticsData;
  message?: string;
}

export default function AdminAnalyticsPage() {
  const [period, setPeriod] = useState("30days");
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // 2. useEffect yang Benar (Fungsi fetch didefinisikan DI DALAM useEffect)
  // Ini menghilangkan warning "missing dependency"
  useEffect(() => {
    let isMounted = true;

    const fetchAnalytics = async () => {
      setLoading(true);
      setError(null);
      try {
        const headers: Record<string, string> = {
          "Content-Type": "application/json",
          "ngrok-skip-browser-warning": "true",
        };

        const apiUrl = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";
        const res = await fetch(`${apiUrl}/admin/analytics.php?period=${period}`, {
          headers,
        });

        const contentType = res.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
          throw new Error("Server response was not JSON");
        }

        // Kita casting result ke tipe ApiResponse agar aman
        const data = (await res.json()) as ApiResponse;

        if (isMounted) {
            if (data.success) {
                setAnalytics(data.analytics);
            } else {
                setError(data.message || "Failed to load data");
            }
        }
      } catch (err) {
        // 3. Menangani Error tanpa 'any'
        console.error("Error fetching analytics:", err);
        if (isMounted) {
            // Cek apakah err adalah instance dari Error standar
            if (err instanceof Error) {
                setError(err.message);
            } else {
                setError("An unknown error occurred");
            }
        }
      } finally {
        if (isMounted) setLoading(false);
      }
    };

    fetchAnalytics();

    return () => {
      isMounted = false;
    };
  }, [period]); // Dependency aman

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const renderPieChart = () => {
    const statusData = analytics?.order_status || [];
    const total = statusData.reduce((sum, s) => sum + s.count, 0) || 1;
    let currentAngle = 0;

    const colors: Record<string, string> = {
      paid: "#22c55e",
      pending: "#eab308",
      failed: "#ef4444",
      refunded: "#6b7280",
    };

    const segments = statusData.map((status) => {
      const percentage = (status.count / total) * 100;
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      const startAngle = currentAngle; // Biarkan ini atau hapus jika tidak dipakai logika lanjutan
      currentAngle += (percentage / 100) * 360;
      return {
        ...status,
        percentage,
        color: colors[status.payment_status] || "#6b7280",
      };
    });

    const gradientStops = segments
      .map((s, i) => {
        const start = segments
          .slice(0, i)
          .reduce((acc, seg) => acc + seg.percentage, 0);
        const end = start + s.percentage;
        return `${s.color} ${start}% ${end}%`;
      })
      .join(", ");

    return (
      <div
        className="w-48 h-48 rounded-full shadow-inner mx-auto relative"
        style={{
          background:
            segments.length > 0
              ? `conic-gradient(${gradientStops})`
              : "#e5e7eb",
        }}
      >
        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div className="w-32 h-32 bg-white rounded-full flex items-center justify-center shadow-sm">
                <div className="text-center">
                    <div className="text-2xl font-bold text-gray-800">{total}</div>
                    <div className="text-xs text-gray-500">Orders</div>
                </div>
            </div>
        </div>
      </div>
    );
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456]"></div>
      </div>
    );
  }

  if (error) {
    return (
        <div className="p-8 text-center">
            <div className="text-red-500 text-xl mb-2">Failed to load analytics</div>
            <p className="text-gray-500">{error}</p>
            <button onClick={() => window.location.reload()} className="mt-4 text-blue-600 underline">Try Reloading</button>
        </div>
    );
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">
            Analytics & Reports
          </h1>
          <p className="text-gray-500">Detailed analytics and business insights</p>
        </div>

        <select
          value={period}
          onChange={(e) => setPeriod(e.target.value)}
          className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent outline-none"
        >
          <option value="7days">Last 7 Days</option>
          <option value="30days">Last 30 Days</option>
          <option value="90days">Last 90 Days</option>
          <option value="year">Last Year</option>
        </select>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="text-sm text-gray-500 mb-1">Total Revenue</div>
          <div className="text-2xl font-bold text-gray-800">
            {formatCurrency(analytics?.revenue?.total_revenue || 0)}
          </div>
          <div className="text-xs text-green-600 mt-1">
            {(analytics?.comparison?.revenue_change ?? 0) > 0 ? "+" : ""}
            {analytics?.comparison?.revenue_change?.toFixed(1) ?? "0"}% vs
            previous
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

      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 className="text-lg font-bold text-gray-800 mb-4">
          Best Selling Products
        </h2>
        <div className="space-y-3">
          {analytics?.best_sellers?.length === 0 && <p className="text-gray-400">No sales yet.</p>}
          {analytics?.best_sellers?.map((product, index) => (
            <div
              key={index}
              className="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"
            >
              <div className="flex-1">
                <div className="font-medium text-gray-800">
                  {product.product_name}
                </div>
                <div className="text-sm text-gray-500">
                  {product.total_sold} sold
                </div>
              </div>
              <div className="font-semibold text-gray-800">
                {formatCurrency(parseFloat(product.total_revenue))}
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h2 className="text-lg font-bold text-gray-800 mb-4">
            Payment Methods
          </h2>
          <div className="space-y-3">
            {analytics?.payment_methods?.length === 0 && <p className="text-gray-400">No data available.</p>}
            {analytics?.payment_methods?.map((method, index) => (
              <div key={index} className="flex items-center justify-between">
                <span className="text-gray-600">
                  {method.payment_method || "Unknown"}
                </span>
                <div className="flex items-center gap-2">
                  <span className="text-sm text-gray-500">
                    {method.count} orders
                  </span>
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
          <p className="text-sm text-gray-500 mb-4">
            Distribusi status pesanan.
          </p>
          
          <div className="flex items-center justify-center mb-6">
             {renderPieChart()}
          </div>

          <div className="space-y-2 mt-4">
            {analytics?.order_status?.map((status, index) => {
              const colors: Record<string, string> = {
                paid: "bg-green-500",
                pending: "bg-yellow-500",
                failed: "bg-red-500",
                refunded: "bg-gray-500",
              };
              return (
                <div key={index} className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span
                      className={`w-3 h-3 rounded-full ${
                        colors[status.payment_status] || "bg-gray-500"
                      }`}
                    ></span>
                    <span className="capitalize text-gray-600">
                      {status.payment_status}
                    </span>
                  </div>
                  <span className="text-sm font-medium text-gray-800">
                    {status.count}
                  </span>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}