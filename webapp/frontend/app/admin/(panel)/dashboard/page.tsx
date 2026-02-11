"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { api } from "@/lib/api-client";

interface DashboardStats {
  totalRevenue: number;
  totalOrders: number;
  pendingOrders: number;
  totalCustomers: number;
  totalProducts: number;
  availableKurir: number;
  pendingReviews: number;
  lowStockProducts: number;
  revenueTrend: number;
  ordersTrend: number;
  customersTrend: number;
}

interface RecentOrder {
  id: string;
  order_number: string;
  customer_name: string;
  email: string;
  final_amount: number;
  status: string;
  payment_status: string;
  items: number;
  created_at: string;
}

interface TopProduct {
  product_id: number;
  product_name: string;
  total_sold: number;
  total_revenue: number;
}

interface Alert {
  type: 'warning' | 'info' | 'danger';
  icon: string;
  message: string;
  link: string;
  count: number;
}

export default function AdminDashboardPage() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [recentOrders, setRecentOrders] = useState<RecentOrder[]>([]);
  const [topProducts, setTopProducts] = useState<TopProduct[]>([]);
  const [alerts, setAlerts] = useState<Alert[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);

      // Fetch analytics
      const analyticsRes = await api.get<{
        success: boolean;
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
          status_distribution: any[];
        };
        customers: {
          total: number;
          new: number;
          top_customers: any[];
        };
        products: {
          top_selling: TopProduct[];
          category_performance: any[];
        };
      }>('/analytics.php?period=30days', { requiresAuth: true });

      // Fetch recent orders
      const ordersRes = await api.get<{
        success: boolean;
        orders: RecentOrder[];
      }>('/orders.php?limit=10&sort=created_at_desc', { requiresAuth: true });

      // Fetch general stats
      const statsRes = await api.get<{
        success: boolean;
        stats: {
          total_customers: number;
          total_products: number;
          available_kurir: number;
          pending_reviews: number;
          low_stock_products: number;
        };
      }>('/dashboard_stats.php', { requiresAuth: true });

      if (analyticsRes.success) {
        const { revenue, orders, products } = analyticsRes;
        
        // Calculate pending orders from status distribution
        const pendingCount = (orders.status_distribution || []).find(
          (s: any) => s.status === 'pending'
        )?.count || 0;
        
        setStats({
          totalRevenue: revenue.total,
          totalOrders: orders.total,
          pendingOrders: pendingCount,
          totalCustomers: statsRes.success ? statsRes.stats.total_customers : 0,
          totalProducts: statsRes.success ? statsRes.stats.total_products : 0,
          availableKurir: statsRes.success ? statsRes.stats.available_kurir : 0,
          pendingReviews: statsRes.success ? statsRes.stats.pending_reviews : 0,
          lowStockProducts: statsRes.success ? statsRes.stats.low_stock_products : 0,
          revenueTrend: revenue.growth_percentage,
          ordersTrend: orders.growth_percentage,
          customersTrend: 0
        });

        if (products?.top_selling && Array.isArray(products.top_selling)) {
          setTopProducts(products.top_selling.slice(0, 5));
        }
      }

      if (ordersRes.success && ordersRes.orders) {
        setRecentOrders(ordersRes.orders);
      }

      // Generate alerts
      const newAlerts: Alert[] = [];
      
      // Calculate pending orders count
      const pendingCount = analyticsRes.success
        ? (analyticsRes.orders.status_distribution || []).find(
            (s: any) => s.status === 'pending'
          )?.count || 0
        : 0;
      
      if (pendingCount > 0) {
        newAlerts.push({
          type: 'warning',
          icon: 'bi-exclamation-triangle',
          message: 'Pending orders waiting for confirmation',
          link: '/admin/orders?status=pending',
          count: pendingCount
        });
      }

      if (statsRes.success && statsRes.stats.pending_reviews > 0) {
        newAlerts.push({
          type: 'info',
          icon: 'bi-star',
          message: 'Product reviews pending moderation',
          link: '/admin/reviews?status=pending',
          count: statsRes.stats.pending_reviews
        });
      }

      if (statsRes.success && statsRes.stats.low_stock_products > 0) {
        newAlerts.push({
          type: 'danger',
          icon: 'bi-box-seam',
          message: 'Products running low on stock',
          link: '/admin/products?filter=low_stock',
          count: statsRes.stats.low_stock_products
        });
      }

      setAlerts(newAlerts);

    } catch (error) {
      console.error('Error fetching dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      'pending': 'bg-yellow-100 text-yellow-700',
      'confirmed': 'bg-blue-100 text-blue-700',
      'processing': 'bg-purple-100 text-purple-700',
      'ready': 'bg-cyan-100 text-cyan-700',
      'delivering': 'bg-indigo-100 text-indigo-700',
      'completed': 'bg-green-100 text-green-700',
      'cancelled': 'bg-red-100 text-red-700'
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
  };

  const getPaymentStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      'pending': 'bg-yellow-100 text-yellow-700',
      'paid': 'bg-green-100 text-green-700',
      'failed': 'bg-red-100 text-red-700',
      'refunded': 'bg-gray-100 text-gray-700'
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
  };

  const getAlertColor = (type: string) => {
    const colors: Record<string, string> = {
      'warning': 'bg-yellow-50 border-yellow-200 text-yellow-800',
      'info': 'bg-blue-50 border-blue-200 text-blue-800',
      'danger': 'bg-red-50 border-red-200 text-red-800'
    };
    return colors[type] || 'bg-gray-50 border-gray-200 text-gray-800';
  };

  if (loading && !stats) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto mb-4"></div>
          <p className="text-gray-500">Loading dashboard...</p>
        </div>
      </div>
    );
  }

  return (
    <div>
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-800 mb-2">Dashboard Overview</h1>
        <p className="text-gray-500">Welcome back! Here&apos;s what&apos;s happening at DailyCup today.</p>
      </div>

      {/* Alerts */}
      {(alerts?.length ?? 0) > 0 && (
        <div className="space-y-3 mb-8">
          {alerts?.map((alert, index) => (
            <Link
              key={`alert-${alert.type}-${index}`}
              href={alert.link}
              className={`block border rounded-lg p-4 transition-all hover:shadow-md ${getAlertColor(alert.type)}`}
            >
              <div className="flex items-center gap-3">
                <i className={`${alert.icon} text-2xl`}></i>
                <div className="flex-1">
                  <span className="font-medium">{alert.message}</span>
                </div>
                <div className="flex items-center gap-2">
                  <span className="font-bold text-lg">{alert.count}</span>
                  <i className="bi bi-arrow-right"></i>
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {/* Revenue */}
        <div className="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-lg shadow-green-500/20 p-6 text-white">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
              <i className="bi bi-currency-dollar text-2xl"></i>
            </div>
            {stats && stats.revenueTrend !== 0 && (
              <div className={`flex items-center gap-1 text-sm ${stats.revenueTrend >= 0 ? 'text-green-100' : 'text-red-100'}`}>
                <i className={`bi bi-arrow-${stats.revenueTrend >= 0 ? 'up' : 'down'}`}></i>
                <span>{Math.abs(stats.revenueTrend)}%</span>
              </div>
            )}
          </div>
          <h3 className="text-3xl font-bold mb-1">{stats ? formatCurrency(stats.totalRevenue) : 'Rp 0'}</h3>
          <p className="text-green-100 text-sm">Total Revenue</p>
        </div>

        {/* Orders */}
        <div className="bg-gradient-to-br from-[#a97456] to-[#8b6043] rounded-2xl shadow-lg shadow-[#a97456]/20 p-6 text-white">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
              <i className="bi bi-bag-check-fill text-2xl"></i>
            </div>
            {stats && stats.ordersTrend !== 0 && (
              <div className={`flex items-center gap-1 text-sm ${stats.ordersTrend >= 0 ? 'text-green-100' : 'text-red-100'}`}>
                <i className={`bi bi-arrow-${stats.ordersTrend >= 0 ? 'up' : 'down'}`}></i>
                <span>{Math.abs(stats.ordersTrend)}%</span>
              </div>
            )}
          </div>
          <h3 className="text-3xl font-bold mb-1">{stats?.totalOrders || 0}</h3>
          <p className="text-orange-100 text-sm">Total Orders</p>
        </div>

        {/* Pending Orders */}
        <div className="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl shadow-lg shadow-orange-500/20 p-6 text-white">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
              <i className="bi bi-clock-history text-2xl"></i>
            </div>
          </div>
          <h3 className="text-3xl font-bold mb-1">{stats?.pendingOrders || 0}</h3>
          <p className="text-orange-100 text-sm">Pending Orders</p>
        </div>

        {/* Customers */}
        <div className="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg shadow-blue-500/20 p-6 text-white">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
              <i className="bi bi-people-fill text-2xl"></i>
            </div>
          </div>
          <h3 className="text-3xl font-bold mb-1">{stats?.totalCustomers || 0}</h3>
          <p className="text-blue-100 text-sm">Total Customers</p>
        </div>
      </div>

      {/* Secondary Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center">
              <i className="bi bi-cup-straw"></i>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-800">{stats?.totalProducts || 0}</p>
              <p className="text-sm text-gray-500">Products</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-cyan-100 text-cyan-600 rounded-lg flex items-center justify-center">
              <i className="bi bi-bicycle"></i>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-800">{stats?.availableKurir || 0}</p>
              <p className="text-sm text-gray-500">Available Kurir</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center">
              <i className="bi bi-star"></i>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-800">{stats?.pendingReviews || 0}</p>
              <p className="text-sm text-gray-500">Pending Reviews</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-red-100 text-red-600 rounded-lg flex items-center justify-center">
              <i className="bi bi-box-seam"></i>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-800">{stats?.lowStockProducts || 0}</p>
              <p className="text-sm text-gray-500">Low Stock</p>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Recent Orders */}
        <div className="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="flex justify-between items-center mb-6">
            <h3 className="text-lg font-bold text-gray-800">Recent Orders</h3>
            <Link href="/admin/orders" className="text-[#a97456] text-sm font-medium hover:underline">
              View All
            </Link>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left">
              <thead>
                <tr className="border-b border-gray-100 text-gray-400 text-sm">
                  <th className="pb-3 font-medium">Order #</th>
                  <th className="pb-3 font-medium">Customer</th>
                  <th className="pb-3 font-medium">Status</th>
                  <th className="pb-3 font-medium">Payment</th>
                  <th className="pb-3 font-medium text-right">Amount</th>
                </tr>
              </thead>
              <tbody className="text-sm">
                {(recentOrders?.length ?? 0) === 0 ? (
                  <tr>
                    <td colSpan={5} className="py-8 text-center text-gray-500">
                      No orders yet
                    </td>
                  </tr>
                ) : (
                  recentOrders?.map((order) => (
                    <tr key={order.id} className="border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors">
                      <td className="py-4 font-mono font-medium text-gray-600">#{order.order_number}</td>
                      <td className="py-4">
                        <div className="font-medium text-gray-800">{order.customer_name}</div>
                        <div className="text-xs text-gray-500">{order.email}</div>
                      </td>
                      <td className="py-4">
                        <span className={`px-3 py-1 ${getStatusColor(order.status)} rounded-full text-xs font-medium capitalize`}>
                          {order.status}
                        </span>
                      </td>
                      <td className="py-4">
                        <span className={`px-3 py-1 ${getPaymentStatusColor(order.payment_status)} rounded-full text-xs font-medium capitalize`}>
                          {order.payment_status}
                        </span>
                      </td>
                      <td className="py-4 text-right font-medium">{formatCurrency(order.final_amount)}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Top Selling Products */}
        <div className="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="flex justify-between items-center mb-6">
            <h3 className="text-lg font-bold text-gray-800">Top Selling</h3>
            <Link href="/admin/analytics" className="text-[#a97456] text-sm font-medium hover:underline">
              View Report
            </Link>
          </div>

          <div className="space-y-4">
            {(topProducts?.length ?? 0) === 0 ? (
              <p className="text-gray-500 text-center py-8 text-sm">No products data</p>
            ) : (
              topProducts?.map((product, index) => (
                <div key={`product-${product.product_id}-${index}`} className="flex items-center gap-4">
                  <div className="w-10 h-10 bg-[#a97456] text-white rounded-lg flex items-center justify-center font-bold text-sm">
                    #{index + 1}
                  </div>
                  <div className="flex-1">
                    <h4 className="font-medium text-gray-800 text-sm">{product.product_name}</h4>
                    <p className="text-xs text-gray-500">{product.total_sold} sales</p>
                  </div>
                  <span className="font-bold text-[#a97456] text-sm">{formatCurrency(product.total_revenue)}</span>
                </div>
              ))
            )}
          </div>

          <Link
            href="/admin/products"
            className="w-full mt-6 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors block text-center"
          >
            View All Products
          </Link>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="mt-8 bg-gradient-to-br from-[#a97456] to-[#8b6043] rounded-2xl shadow-lg p-6 text-white">
        <h3 className="text-lg font-bold mb-4">Quick Actions</h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <Link
            href="/admin/orders/create"
            className="bg-white/10 hover:bg-white/20 rounded-lg p-4 text-center transition-colors"
          >
            <i className="bi bi-plus-circle text-3xl mb-2"></i>
            <p className="text-sm font-medium">New Order</p>
          </Link>
          <Link
            href="/admin/products/create"
            className="bg-white/10 hover:bg-white/20 rounded-lg p-4 text-center transition-colors"
          >
            <i className="bi bi-cup-straw text-3xl mb-2"></i>
            <p className="text-sm font-medium">Add Product</p>
          </Link>
          <Link
            href="/admin/users"
            className="bg-white/10 hover:bg-white/20 rounded-lg p-4 text-center transition-colors"
          >
            <i className="bi bi-people text-3xl mb-2"></i>
            <p className="text-sm font-medium">Manage Users</p>
          </Link>
          <Link
            href="/admin/analytics"
            className="bg-white/10 hover:bg-white/20 rounded-lg p-4 text-center transition-colors"
          >
            <i className="bi bi-graph-up text-3xl mb-2"></i>
            <p className="text-sm font-medium">View Analytics</p>
          </Link>
        </div>
      </div>
    </div>
  );
}
