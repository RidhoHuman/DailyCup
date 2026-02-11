"use client";

import { useState, useEffect } from "react";
import StatsCard from "@/components/admin/StatsCard";
import { api } from "@/lib/api-client";

interface DashboardStats {
  totalRevenue: number;
  totalOrders: number;
  pendingOrders: number;
  newCustomers: number;
  revenueTrend: number;
  ordersTrend: number;
}

interface RecentOrder {
  id: string;
  customer: string;
  email: string;
  total: number;
  status: string;
  items: number;
  date: string;
}

interface TopProduct {
  id: number;
  name: string;
  price: number;
  image: string | null;
  sold: number;
  revenue: number;
}

export default function AdminDashboardPage() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [recentOrders, setRecentOrders] = useState<RecentOrder[]>([]);
  const [topProducts, setTopProducts] = useState<TopProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      setError(null);

      // Use new analytics API with authentication
      const response = await api.get<{
        success: boolean;
        analytics: {
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
            total_revenue: number;
          }>;
          comparison: {
            revenue_change: number;
            orders_change: number;
          };
        };
      }>('/analytics.php?period=30days', { requiresAuth: true });

      if (response.success && response.analytics) {
        const { revenue, best_sellers, comparison } = response.analytics;
        
        // Map to existing interface
        setStats({
          totalRevenue: revenue.total_revenue,
          totalOrders: revenue.total_orders,
          pendingOrders: revenue.pending_orders,
          newCustomers: 0, // TODO: implement new customers tracking
          revenueTrend: comparison.revenue_change,
          ordersTrend: comparison.orders_change
        });

        // Map best sellers
        setTopProducts(best_sellers.slice(0, 5).map(p => ({
          id: p.product_id,
          name: p.product_name,
          price: 0,
          image: null,
          sold: parseInt(p.total_sold.toString()),
          revenue: parseFloat(p.total_revenue.toString())
        })));

        // Fetch recent orders separately (requires auth)
        const ordersRes = await api.get<{
          success: boolean;
          data: RecentOrder[];
        }>('/get_recent_orders.php?limit=10', { requiresAuth: true });
        
        if (ordersRes.success) {
          setRecentOrders(ordersRes.data);
        }
      }

    } catch (err) {
      console.error('Error fetching dashboard data:', err);
      setError(err instanceof Error ? err.message : 'Failed to load dashboard');
    } finally {
      setLoading(false);
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    });
  };

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      'pending': 'bg-yellow-100 text-yellow-700',
      'processing': 'bg-blue-100 text-blue-700',
      'paid': 'bg-green-100 text-green-700',
      'delivered': 'bg-green-100 text-green-700',
      'cancelled': 'bg-red-100 text-red-700',
      'failed': 'bg-red-100 text-red-700'
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto mb-4"></div>
          <p className="text-gray-500">Loading dashboard...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <p className="text-red-700 mb-4">{error}</p>
        <button 
          onClick={fetchDashboardData}
          className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
        >
          Retry
        </button>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
        <p className="text-gray-500">Welcome back, here&apos;s what&apos;s happening at DailyCup today.</p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <StatsCard 
          title="Total Revenue" 
          value={stats ? formatCurrency(stats.totalRevenue) : 'Rp 0'} 
          icon="bi-currency-dollar" 
          trend={stats ? `${Math.abs(stats.revenueTrend)}%` : '0%'} 
          trendUp={stats ? stats.revenueTrend >= 0 : true} 
          color="bg-green-500 shadow-green-500/20"
        />
        <StatsCard 
          title="Total Orders" 
          value={stats ? stats.totalOrders.toString() : '0'} 
          icon="bi-bag-check-fill" 
          trend={stats ? `${Math.abs(stats.ordersTrend)}%` : '0%'} 
          trendUp={stats ? stats.ordersTrend >= 0 : true}
          color="bg-[#a97456] shadow-[#a97456]/20"
        />
         <StatsCard 
          title="Pending Orders" 
          value={stats ? stats.pendingOrders.toString() : '0'} 
          icon="bi-clock-history" 
          trend="" 
          trendUp={false}
          color="bg-orange-500 shadow-orange-500/20"
        />
        <StatsCard 
          title="New Customers" 
          value={stats ? stats.newCustomers.toString() : '0'} 
          icon="bi-people-fill" 
          trend="Last 30 days" 
          trendUp={true}
          color="bg-blue-500 shadow-blue-500/20"
        />
      </div>

      {/* Recent Activity & Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* Recent Orders */}
        <div className="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div className="flex justify-between items-center mb-6">
                <h3 className="text-lg font-bold text-gray-800">Recent Orders</h3>
                <button 
                  onClick={() => window.location.href = '/admin/orders'}
                  className="text-[#a97456] text-sm font-medium hover:underline"
                >
                  View All
                </button>
            </div>
            
            <div className="overflow-x-auto">
                <table className="w-full text-left">
                    <thead>
                        <tr className="border-b border-gray-100 text-gray-400 text-sm">
                            <th className="pb-3 font-medium">Order ID</th>
                            <th className="pb-3 font-medium">Customer</th>
                            <th className="pb-3 font-medium">Items</th>
                            <th className="pb-3 font-medium">Date</th>
                            <th className="pb-3 font-medium">Status</th>
                            <th className="pb-3 font-medium text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody className="text-sm">
                        {recentOrders.length === 0 ? (
                          <tr>
                            <td colSpan={6} className="py-8 text-center text-gray-500">
                              No orders yet
                            </td>
                          </tr>
                        ) : (
                          recentOrders.map((order) => (
                            <tr key={order.id} className="border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors">
                              <td className="py-4 font-mono font-medium text-gray-600">#{order.id}</td>
                              <td className="py-4">{order.customer}</td>
                              <td className="py-4 text-gray-500">{order.items} Items</td>
                              <td className="py-4 text-gray-500">{formatDate(order.date)}</td>
                              <td className="py-4">
                                <span className={`px-3 py-1 ${getStatusColor(order.status)} rounded-full text-xs font-medium capitalize`}>
                                  {order.status}
                                </span>
                              </td>
                              <td className="py-4 text-right font-medium">{formatCurrency(order.total)}</td>
                            </tr>
                          ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>

        {/* Top Products */}
        <div className="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-lg font-bold text-gray-800 mb-6">Top Selling</h3>
            <div className="space-y-6">
                {topProducts.length === 0 ? (
                  <p className="text-gray-500 text-center py-8">No products data</p>
                ) : (
                  topProducts.map((product) => (
                    <div key={product.id} className="flex items-center gap-4">
                      <div className="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-xl overflow-hidden">
                        {product.image ? (
                          <img 
                            src={product.image} 
                            alt={product.name}
                            className="w-full h-full object-cover"
                          />
                        ) : (
                          '☕'
                        )}
                      </div>
                      <div className="flex-1">
                        <h4 className="font-medium text-gray-800">{product.name}</h4>
                        <p className="text-sm text-gray-500">{product.sold} sales</p>
                      </div>
                      <span className="font-bold text-[#a97456]">{formatCurrency(product.revenue)}</span>
                    </div>
                  ))
                )}
            </div>
            
            <button 
              onClick={() => window.location.href = '/admin/products'}
              className="w-full mt-6 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
            >
              View All Products
            </button>
        </div>

      </div>
    </div>
  );
}
