'use client';

import { useState, useEffect } from 'react';
import api from '@/lib/api-client';

interface CustomerSegment {
  user_id: number;
  name: string;
  email: string;
  phone: string;
  loyalty_points: number;
  total_orders: number;
  total_spent: number;
  avg_order_value: number;
  days_since_last_order: number;
  last_order_date: string;
  customer_segment: 'Champion' | 'Loyal' | 'At Risk' | 'New' | 'Promising' | 'Need Attention' | 'Other';
  recency_score: number;
  frequency_score: number;
  monetary_score: number;
}

export default function CrmAnalyticsDashboard() {
  const [customers, setCustomers] = useState<CustomerSegment[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState<string>('all');

  useEffect(() => {
    fetchCustomerData();
  }, []);

  const fetchCustomerData = async () => {
    try {
      const response = await api.get<{
        success: boolean;
        customers?: CustomerSegment[];
      }>('/admin/crm_analytics.php');

      if (response.success && response.customers) {
        setCustomers(response.customers);
      }
    } catch (error) {
      console.error('Error fetching CRM analytics:', error);
    } finally {
      setLoading(false);
    }
  };

  const filteredCustomers = filter === 'all' 
    ? customers 
    : customers.filter(c => c.customer_segment === filter);

  const segmentStats = {
    champion: customers.filter(c => c.customer_segment === 'Champion').length,
    loyal: customers.filter(c => c.customer_segment === 'Loyal').length,
    atRisk: customers.filter(c => c.customer_segment === 'At Risk').length,
    new: customers.filter(c => c.customer_segment === 'New').length,
    promising: customers.filter(c => c.customer_segment === 'Promising').length,
    needAttention: customers.filter(c => c.customer_segment === 'Need Attention').length
  };

  const segmentColors: Record<string, string> = {
    Champion: 'bg-purple-100 text-purple-800 border-purple-300',
    Loyal: 'bg-blue-100 text-blue-800 border-blue-300',
    'At Risk': 'bg-orange-100 text-orange-800 border-orange-300',
    New: 'bg-green-100 text-green-800 border-green-300',
    Promising: 'bg-cyan-100 text-cyan-800 border-cyan-300',
    'Need Attention': 'bg-red-100 text-red-800 border-red-300',
    Other: 'bg-gray-100 text-gray-800 border-gray-300'
  };

  if (loading) {
    return (
      <div className="p-6">
        <div className="animate-pulse space-y-4">
          <div className="h-8 bg-gray-200 rounded w-1/3"></div>
          <div className="grid grid-cols-3 gap-4">
            {[1, 2, 3, 4, 5, 6].map(i => (
              <div key={i} className="h-24 bg-gray-200 rounded"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold text-gray-900">📊 CRM Analytics Dashboard</h1>
        <p className="text-gray-600 mt-1">RFM (Recency, Frequency, Monetary) Customer Segmentation</p>
      </div>

      {/* Segment Stats */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <button
          onClick={() => setFilter('Champion')}
          className={`p-4 rounded-lg border-2 transition-all ${
            filter === 'Champion' ? 'ring-2 ring-purple-500' : ''
          } ${segmentColors.Champion}`}
        >
          <div className="text-2xl font-bold">{segmentStats.champion}</div>
          <div className="text-sm font-semibold">💎 Champions</div>
        </button>

        <button
          onClick={() => setFilter('Loyal')}
          className={`p-4 rounded-lg border-2 transition-all ${
            filter === 'Loyal' ? 'ring-2 ring-blue-500' : ''
          } ${segmentColors.Loyal}`}
        >
          <div className="text-2xl font-bold">{segmentStats.loyal}</div>
          <div className="text-sm font-semibold">🏆 Loyal</div>
        </button>

        <button
          onClick={() => setFilter('At Risk')}
          className={`p-4 rounded-lg border-2 transition-all ${
            filter === 'At Risk' ? 'ring-2 ring-orange-500' : ''
          } ${segmentColors['At Risk']}`}
        >
          <div className="text-2xl font-bold">{segmentStats.atRisk}</div>
          <div className="text-sm font-semibold">⚠️ At Risk</div>
        </button>

        <button
          onClick={() => setFilter('New')}
          className={`p-4 rounded-lg border-2 transition-all ${
            filter === 'New' ? 'ring-2 ring-green-500' : ''
          } ${segmentColors.New}`}
        >
          <div className="text-2xl font-bold">{segmentStats.new}</div>
          <div className="text-sm font-semibold">🌱 New</div>
        </button>

        <button
          onClick={() => setFilter('Promising')}
          className={`p-4 rounded-lg border-2 transition-all ${
            filter === 'Promising' ? 'ring-2 ring-cyan-500' : ''
          } ${segmentColors.Promising}`}
        >
          <div className="text-2xl font-bold">{segmentStats.promising}</div>
          <div className="text-sm font-semibold">📈 Promising</div>
        </button>

        <button
          onClick={() => setFilter('Need Attention')}
          className={`p-4 rounded-lg border-2 transition-all ${
            filter === 'Need Attention' ? 'ring-2 ring-red-500' : ''
          } ${segmentColors['Need Attention']}`}
        >
          <div className="text-2xl font-bold">{segmentStats.needAttention}</div>
          <div className="text-sm font-semibold">🚨 Need Attention</div>
        </button>
      </div>

      {/* Filter Bar */}
      <div className="flex items-center gap-2">
        <button
          onClick={() => setFilter('all')}
          className={`px-4 py-2 rounded-lg font-medium transition-colors ${
            filter === 'all'
              ? 'bg-[#a97456] text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          }`}
        >
          All Customers ({customers.length})
        </button>
        {filter !== 'all' && (
          <span className="text-sm text-gray-600">
            Showing {filteredCustomers.length} {filter} customers
          </span>
        )}
      </div>

      {/* Customer Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Customer
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Segment
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  RFM Score
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Orders
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Total Spent
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Last Order
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Points
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredCustomers.map((customer) => (
                <tr key={customer.user_id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div className="text-sm font-medium text-gray-900">{customer.name}</div>
                      <div className="text-xs text-gray-500">{customer.email}</div>
                      <div className="text-xs text-gray-500">{customer.phone}</div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border ${
                      segmentColors[customer.customer_segment]
                    }`}>
                      {customer.customer_segment}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div className="flex gap-1">
                      <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded font-mono text-xs">
                        R:{customer.recency_score}
                      </span>
                      <span className="px-2 py-1 bg-green-100 text-green-800 rounded font-mono text-xs">
                        F:{customer.frequency_score}
                      </span>
                      <span className="px-2 py-1 bg-purple-100 text-purple-800 rounded font-mono text-xs">
                        M:{customer.monetary_score}
                      </span>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                    {customer.total_orders}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    Rp {customer.total_spent.toLocaleString('id-ID')}
                    <div className="text-xs text-gray-500">
                      Avg: Rp {customer.avg_order_value.toLocaleString('id-ID')}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {customer.days_since_last_order} days ago
                    <div className="text-xs text-gray-400">
                      {new Date(customer.last_order_date).toLocaleDateString('id-ID')}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-semibold text-[#a97456]">
                    {customer.loyalty_points} pts
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {filteredCustomers.length === 0 && (
        <div className="text-center py-12 text-gray-500">
          No customers found in this segment
        </div>
      )}
    </div>
  );
}
