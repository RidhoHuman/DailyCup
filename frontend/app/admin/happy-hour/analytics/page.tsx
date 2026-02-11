'use client';

import { useEffect, useState } from 'react';
import { api } from '@/lib/api-client';
import {
  TrendingUp,
  TrendingDown,
  DollarSign,
  ShoppingCart,
  Users,
  Package,
  Clock,
  Calendar,
  BarChart3,
  PieChart,
} from 'lucide-react';
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
  Filler,
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

interface HappyHourAnalytics {
  overall: {
    total_orders: number;
    unique_customers: number;
    total_items_sold: number;
    total_original_revenue: number;
    total_discount_given: number;
    total_actual_revenue: number;
    avg_discount_percentage: number;
  };
  comparison: {
    happy_hour_orders: number;
    normal_orders: number;
    happy_hour_revenue: number;
    normal_revenue: number;
    happy_hour_avg_order: number;
    normal_avg_order: number;
  };
  top_products: Array<{
    product_id: number;
    product_name: string;
    times_ordered: number;
    total_quantity: number;
    total_revenue: number;
  }>;
  schedules_performance: Array<{
    schedule_id: number;
    schedule_name: string;
    total_orders: number;
    total_revenue: number;
    avg_discount: number;
  }>;
  daily_trend?: Array<{
    date: string;
    orders: number;
    revenue: number;
  }>;
}

export default function HappyHourAnalyticsPage() {
  const [analytics, setAnalytics] = useState<HappyHourAnalytics | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [period, setPeriod] = useState('last_30_days');

  useEffect(() => {
    fetchAnalytics();
  }, [period]);

  const fetchAnalytics = async () => {
    try {
      setLoading(true);
      setError('');
      const response: any = await api.get(`/happy_hour/analytics.php?period=${period}`);

      if (response?.data) {
        setAnalytics(response.data);
      } else {
        setError('Failed to load analytics data');
      }
    } catch (err: any) {
      console.error('Error fetching analytics:', err);
      setError(err.response?.data?.error || 'Failed to load analytics');
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

  const formatNumber = (num: number) => {
    return new Intl.NumberFormat('id-ID').format(num);
  };

  const calculateROI = () => {
    if (!analytics) return 0;
    const { total_actual_revenue, total_discount_given } = analytics.overall;
    if (total_discount_given === 0) return 0;
    return ((total_actual_revenue / total_discount_given) * 100).toFixed(2);
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading analytics...</p>
        </div>
      </div>
    );
  }

  if (error || !analytics) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-lg shadow-lg p-8 max-w-md w-full text-center">
          <div className="text-red-500 text-5xl mb-4">⚠️</div>
          <h2 className="text-2xl font-bold text-gray-800 mb-2">Error Loading Analytics</h2>
          <p className="text-gray-600 mb-6">{error || 'Unable to load analytics data'}</p>
          <button
            onClick={fetchAnalytics}
            className="bg-[#a97456] text-white px-6 py-2 rounded-lg hover:bg-[#8b5e3c] transition"
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  const { overall, comparison, top_products, schedules_performance } = analytics;

  // Chart data
  const comparisonChartData = {
    labels: ['Happy Hour', 'Normal Orders'],
    datasets: [
      {
        label: 'Revenue',
        data: [comparison.happy_hour_revenue, comparison.normal_revenue],
        backgroundColor: ['rgba(169, 116, 86, 0.8)', 'rgba(107, 114, 128, 0.6)'],
        borderColor: ['#a97456', '#6b7280'],
        borderWidth: 2,
      },
    ],
  };

  const ordersComparisonData = {
    labels: ['Happy Hour Orders', 'Normal Orders'],
    datasets: [
      {
        data: [comparison.happy_hour_orders, comparison.normal_orders],
        backgroundColor: ['#a97456', '#e5e7eb'],
        borderWidth: 0,
      },
    ],
  };

  const topProductsChartData = {
    labels: top_products.slice(0, 5).map((p) => p.product_name),
    datasets: [
      {
        label: 'Quantity Sold',
        data: top_products.slice(0, 5).map((p) => p.total_quantity),
        backgroundColor: 'rgba(169, 116, 86, 0.8)',
        borderColor: '#a97456',
        borderWidth: 2,
      },
    ],
  };

  return (
    <div className="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-800 mb-2 flex items-center gap-2">
            <Clock className="text-[#a97456]" size={32} />
            Happy Hour Analytics
          </h1>
          <p className="text-gray-600">Performance insights and ROI analysis</p>
        </div>

        {/* Period Filter */}
        <div className="bg-white rounded-lg shadow p-4 mb-6">
          <div className="flex items-center gap-4">
            <Calendar className="text-gray-400" size={20} />
            <select
              value={period}
              onChange={(e) => setPeriod(e.target.value)}
              className="flex-1 max-w-xs px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
            >
              <option value="last_7_days">Last 7 Days</option>
              <option value="last_30_days">Last 30 Days</option>
              <option value="last_90_days">Last 90 Days</option>
              <option value="all_time">All Time</option>
            </select>
          </div>
        </div>

        {/* Key Metrics */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          {/* Total Revenue */}
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between mb-4">
              <div className="bg-green-100 p-3 rounded-full">
                <DollarSign className="text-green-600" size={24} />
              </div>
              <TrendingUp className="text-green-500" size={20} />
            </div>
            <h3 className="text-gray-500 text-sm font-medium mb-1">Happy Hour Revenue</h3>
            <p className="text-2xl font-bold text-gray-800">
              {formatCurrency(overall.total_actual_revenue)}
            </p>
            <p className="text-xs text-gray-500 mt-2">
              Original: {formatCurrency(overall.total_original_revenue)}
            </p>
          </div>

          {/* Total Orders */}
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between mb-4">
              <div className="bg-blue-100 p-3 rounded-full">
                <ShoppingCart className="text-blue-600" size={24} />
              </div>
            </div>
            <h3 className="text-gray-500 text-sm font-medium mb-1">Total Orders</h3>
            <p className="text-2xl font-bold text-gray-800">{formatNumber(overall.total_orders)}</p>
            <p className="text-xs text-gray-500 mt-2">
              {formatNumber(overall.total_items_sold)} items sold
            </p>
          </div>

          {/* Unique Customers */}
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between mb-4">
              <div className="bg-purple-100 p-3 rounded-full">
                <Users className="text-purple-600" size={24} />
              </div>
            </div>
            <h3 className="text-gray-500 text-sm font-medium mb-1">Unique Customers</h3>
            <p className="text-2xl font-bold text-gray-800">
              {formatNumber(overall.unique_customers)}
            </p>
            <p className="text-xs text-gray-500 mt-2">
              Avg: {(overall.total_orders / overall.unique_customers).toFixed(1)} orders/customer
            </p>
          </div>

          {/* Total Discount */}
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between mb-4">
              <div className="bg-orange-100 p-3 rounded-full">
                <Package className="text-orange-600" size={24} />
              </div>
              <TrendingDown className="text-orange-500" size={20} />
            </div>
            <h3 className="text-gray-500 text-sm font-medium mb-1">Total Discount Given</h3>
            <p className="text-2xl font-bold text-gray-800">
              {formatCurrency(overall.total_discount_given)}
            </p>
            <p className="text-xs text-gray-500 mt-2">
              Avg: {overall.avg_discount_percentage.toFixed(1)}% discount
            </p>
          </div>
        </div>

        {/* ROI Card */}
        <div className="bg-gradient-to-r from-[#a97456] to-[#8b5e3c] rounded-lg shadow-lg p-6 mb-6 text-white">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-white/80 text-sm font-medium mb-1">Return on Investment (ROI)</h3>
              <p className="text-4xl font-bold">{calculateROI()}%</p>
              <p className="text-white/80 text-sm mt-2">
                For every Rp 1,000 discount, you earn Rp {(parseFloat(String(calculateROI())) * 10).toFixed(0)}
              </p>
            </div>
            <div className="bg-white/20 p-4 rounded-full">
              <TrendingUp size={48} />
            </div>
          </div>
        </div>

        {/* Charts Row */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          {/* Revenue Comparison */}
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
              <BarChart3 className="text-[#a97456]" size={20} />
              Revenue Comparison
            </h3>
            <Bar
              data={comparisonChartData}
              options={{
                responsive: true,
                plugins: {
                  legend: { display: false },
                  tooltip: {
                    callbacks: {
                      label: (context) => formatCurrency(context.parsed.y),
                    },
                  },
                },
                scales: {
                  y: {
                    ticks: {
                      callback: (value) => formatCurrency(Number(value)),
                    },
                  },
                },
              }}
            />
            <div className="mt-4 grid grid-cols-2 gap-4 text-center">
              <div>
                <p className="text-sm text-gray-500">Avg Order (HH)</p>
                <p className="text-lg font-bold text-[#a97456]">
                  {formatCurrency(comparison.happy_hour_avg_order)}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Avg Order (Normal)</p>
                <p className="text-lg font-bold text-gray-600">
                  {formatCurrency(comparison.normal_avg_order)}
                </p>
              </div>
            </div>
          </div>

          {/* Orders Distribution */}
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
              <PieChart className="text-[#a97456]" size={20} />
              Orders Distribution
            </h3>
            <div className="max-w-xs mx-auto">
              <Doughnut
                data={ordersComparisonData}
                options={{
                  responsive: true,
                  plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                      callbacks: {
                        label: (context) => `${context.label}: ${formatNumber(context.parsed)}`,
                      },
                    },
                  },
                }}
              />
            </div>
            <div className="mt-4 text-center">
              <p className="text-sm text-gray-500">
                Happy Hour orders represent{' '}
                <span className="font-bold text-[#a97456]">
                  {(
                    (comparison.happy_hour_orders /
                      (comparison.happy_hour_orders + comparison.normal_orders)) *
                    100
                  ).toFixed(1)}
                  %
                </span>{' '}
                of total orders
              </p>
            </div>
          </div>
        </div>

        {/* Top Products */}
        <div className="bg-white rounded-lg shadow p-6 mb-6">
          <h3 className="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <Package className="text-[#a97456]" size={20} />
            Top 5 Products During Happy Hour
          </h3>
          <Bar
            data={topProductsChartData}
            options={{
              responsive: true,
              plugins: {
                legend: { display: false },
              },
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: {
                    precision: 0,
                  },
                },
              },
            }}
          />
          <div className="mt-6 overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Product
                  </th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                    Orders
                  </th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                    Quantity
                  </th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                    Revenue
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {top_products.slice(0, 5).map((product, index) => (
                  <tr key={product.product_id}>
                    <td className="px-4 py-3 whitespace-nowrap">
                      <div className="flex items-center gap-2">
                        <span className="font-bold text-[#a97456]">#{index + 1}</span>
                        <span className="font-medium text-gray-800">{product.product_name}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-center text-sm text-gray-600">
                      {formatNumber(product.times_ordered)}
                    </td>
                    <td className="px-4 py-3 text-center text-sm font-medium text-gray-800">
                      {formatNumber(product.total_quantity)}
                    </td>
                    <td className="px-4 py-3 text-right text-sm font-bold text-green-600">
                      {formatCurrency(product.total_revenue)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Schedule Performance */}
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <Clock className="text-[#a97456]" size={20} />
            Schedule Performance
          </h3>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Schedule
                  </th>
                  <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                    Orders
                  </th>
                  <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                    Avg Discount
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                    Revenue
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {schedules_performance.map((schedule) => (
                  <tr key={schedule.schedule_id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="font-medium text-gray-800">{schedule.schedule_name}</span>
                    </td>
                    <td className="px-6 py-4 text-center text-sm text-gray-600">
                      {formatNumber(schedule.total_orders)}
                    </td>
                    <td className="px-6 py-4 text-center text-sm font-medium text-orange-600">
                      {schedule.avg_discount.toFixed(1)}%
                    </td>
                    <td className="px-6 py-4 text-right text-sm font-bold text-green-600">
                      {formatCurrency(schedule.total_revenue)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
