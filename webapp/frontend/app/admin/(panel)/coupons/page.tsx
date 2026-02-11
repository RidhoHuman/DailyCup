"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { api } from "@/lib/api-client";

interface Coupon {
  id: number;
  code: string;
  name: string;
  description: string;
  discount_type: 'percentage' | 'fixed';
  discount_value: number;
  min_purchase: number;
  max_discount: number | null;
  usage_limit: number | null;
  usage_count: number;
  start_date: string;
  end_date: string;
  is_active: boolean;
  created_at: string;
}

export default function AdminCouponsPage() {
  const [coupons, setCoupons] = useState<Coupon[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [formData, setFormData] = useState({
    code: '',
    name: '',
    description: '',
    discount_type: 'percentage' as 'percentage' | 'fixed',
    discount_value: 0,
    min_purchase: 0,
    max_discount: '',
    usage_limit: '',
    start_date: '',
    end_date: '',
    is_active: true
  });

  useEffect(() => {
    fetchCoupons();
  }, []);

  const fetchCoupons = async () => {
    try {
      setLoading(true);
      const response = await api.get<{ success: boolean; coupons: Coupon[] }>(
        '/coupons.php',
        { requiresAuth: true }
      );
      if (response.success) {
        setCoupons(response.coupons);
      }
    } catch (error) {
      console.error('Error fetching coupons:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      if (editingId) {
        await api.put(`/coupons.php?id=${editingId}`, formData, { requiresAuth: true });
      } else {
        await api.post('/coupons.php', formData, { requiresAuth: true });
      }

      setShowForm(false);
      resetForm();
      fetchCoupons();
    } catch (error) {
      console.error('Error saving coupon:', error);
      alert('Failed to save coupon');
    }
  };

  const handleEdit = (coupon: Coupon) => {
    setEditingId(coupon.id);
    setFormData({
      code: coupon.code,
      name: coupon.name,
      description: coupon.description,
      discount_type: coupon.discount_type,
      discount_value: coupon.discount_value,
      min_purchase: coupon.min_purchase,
      max_discount: coupon.max_discount?.toString() || '',
      usage_limit: coupon.usage_limit?.toString() || '',
      start_date: coupon.start_date.substring(0, 16),
      end_date: coupon.end_date.substring(0, 16),
      is_active: coupon.is_active
    });
    setShowForm(true);
  };

  const handleDelete = async (id: number, code: string) => {
    if (!confirm(`Are you sure you want to delete coupon "${code}"?`)) return;

    try {
      await api.delete(`/coupons.php?id=${id}`, { requiresAuth: true });
      fetchCoupons();
    } catch (error) {
      console.error('Error deleting coupon:', error);
      alert('Failed to delete coupon');
    }
  };

  const handleToggleActive = async (id: number, currentStatus: boolean) => {
    try {
      await api.put(`/coupons.php?id=${id}`, {
        is_active: !currentStatus
      }, { requiresAuth: true });
      fetchCoupons();
    } catch (error) {
      console.error('Error toggling coupon:', error);
      alert('Failed to update coupon status');
    }
  };

  const resetForm = () => {
    setEditingId(null);
    setFormData({
      code: '',
      name: '',
      description: '',
      discount_type: 'percentage',
      discount_value: 0,
      min_purchase: 0,
      max_discount: '',
      usage_limit: '',
      start_date: '',
      end_date: '',
      is_active: true
    });
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
      year: 'numeric'
    });
  };

  const isExpired = (endDate: string) => {
    return new Date(endDate) < new Date();
  };

  const isUpcoming = (startDate: string) => {
    return new Date(startDate) > new Date();
  };

  if (loading && coupons.length === 0) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto mb-4"></div>
          <p className="text-gray-500">Loading coupons...</p>
        </div>
      </div>
    );
  }

  return (
    <div>
      {/* Header */}
      <div className="mb-8">
        <div className="flex justify-between items-center mb-4">
          <div>
            <h1 className="text-2xl font-bold text-gray-800 mb-2">Coupon Management</h1>
            <p className="text-gray-500">Create and manage discount coupons & vouchers</p>
          </div>
          <button
            onClick={() => setShowForm(true)}
            className="px-6 py-3 bg-[#a97456] text-white rounded-lg hover:bg-[#8b6043] transition-colors font-medium"
          >
            <i className="bi bi-plus-circle mr-2"></i>
            Create Coupon
          </button>
        </div>
      </div>

      {/* Coupon Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center">
              <i className="bi bi-check-circle text-2xl"></i>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-800">
                {coupons.filter(c => c.is_active && !isExpired(c.end_date)).length}
              </p>
              <p className="text-sm text-gray-500">Active Coupons</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center">
              <i className="bi bi-clock text-2xl"></i>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-800">
                {coupons.filter(c => isUpcoming(c.start_date)).length}
              </p>
              <p className="text-sm text-gray-500">Scheduled</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-red-100 text-red-600 rounded-lg flex items-center justify-center">
              <i className="bi bi-x-circle text-2xl"></i>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-800">
                {coupons.filter(c => isExpired(c.end_date)).length}
              </p>
              <p className="text-sm text-gray-500">Expired</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
              <i className="bi bi-ticket-perforated text-2xl"></i>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-800">{coupons.length}</p>
              <p className="text-sm text-gray-500">Total Coupons</p>
            </div>
          </div>
        </div>
      </div>

      {/* Coupons Table */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {coupons.length === 0 ? (
          <div className="p-12 text-center text-gray-500">
            <i className="bi bi-ticket-perforated text-6xl text-gray-300 mb-4"></i>
            <p className="text-lg">No coupons found</p>
            <p className="text-sm">Click &quot;Create Coupon&quot; to get started</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Code</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Name</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Discount</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Usage</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Period</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Status</th>
                  <th className="px-6 py-4 text-right text-sm font-semibold text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {coupons.map((coupon) => (
                  <tr key={coupon.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4">
                      <code className="px-3 py-1 bg-gray-100 text-gray-800 rounded font-mono font-bold">
                        {coupon.code}
                      </code>
                    </td>
                    <td className="px-6 py-4">
                      <div className="font-medium text-gray-800">{coupon.name}</div>
                      <div className="text-sm text-gray-500">{coupon.description}</div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="font-semibold text-[#a97456]">
                        {coupon.discount_type === 'percentage'
                          ? `${coupon.discount_value}%`
                          : formatCurrency(coupon.discount_value)}
                      </div>
                      <div className="text-xs text-gray-500">
                        Min: {formatCurrency(coupon.min_purchase)}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="text-sm">
                        <span className="font-medium text-gray-800">{coupon.usage_count}</span>
                        <span className="text-gray-500">
                          {coupon.usage_limit ? ` / ${coupon.usage_limit}` : ' / ∞'}
                        </span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      <div>{formatDate(coupon.start_date)}</div>
                      <div className="text-xs text-gray-500">to {formatDate(coupon.end_date)}</div>
                    </td>
                    <td className="px-6 py-4">
                      {isExpired(coupon.end_date) ? (
                        <span className="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">
                          Expired
                        </span>
                      ) : isUpcoming(coupon.start_date) ? (
                        <span className="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">
                          Scheduled
                        </span>
                      ) : coupon.is_active ? (
                        <span className="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                          Active
                        </span>
                      ) : (
                        <span className="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium">
                          Inactive
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={() => handleEdit(coupon)}
                          className="px-3 py-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm"
                          title="Edit"
                        >
                          <i className="bi bi-pencil"></i>
                        </button>
                        <button
                          onClick={() => handleToggleActive(coupon.id, coupon.is_active)}
                          className={`px-3 py-1.5 rounded-lg transition-colors text-sm ${
                            coupon.is_active
                              ? 'bg-yellow-500 text-white hover:bg-yellow-600'
                              : 'bg-green-500 text-white hover:bg-green-600'
                          }`}
                          title={coupon.is_active ? 'Deactivate' : 'Activate'}
                        >
                          <i className={`bi ${coupon.is_active ? 'bi-pause' : 'bi-play'}`}></i>
                        </button>
                        <button
                          onClick={() => handleDelete(coupon.id, coupon.code)}
                          className="px-3 py-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm"
                          title="Delete"
                        >
                          <i className="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Create/Edit Modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
          <div className="bg-white rounded-2xl shadow-xl max-w-2xl w-full my-8">
            <div className="border-b border-gray-100 px-6 py-4">
              <div className="flex justify-between items-center">
                <h2 className="text-xl font-bold text-gray-800">
                  {editingId ? 'Edit Coupon' : 'Create New Coupon'}
                </h2>
                <button
                  onClick={() => { setShowForm(false); resetForm(); }}
                  className="text-gray-400 hover:text-gray-600 text-2xl"
                >
                  <i className="bi bi-x-lg"></i>
                </button>
              </div>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Code */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Coupon Code *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.code}
                    onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent font-mono"
                    placeholder="SUMMER2026"
                  />
                </div>

                {/* Name */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Display Name *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                    placeholder="Summer Sale 2026"
                  />
                </div>
              </div>

              {/* Description */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Description
                </label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  rows={2}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  placeholder="Get special discount for summer season"
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Discount Type */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Discount Type *
                  </label>
                  <select
                    value={formData.discount_type}
                    onChange={(e) => setFormData({ ...formData, discount_type: e.target.value as 'percentage' | 'fixed' })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  >
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount (Rp)</option>
                  </select>
                </div>

                {/* Discount Value */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Discount Value *
                  </label>
                  <input
                    type="number"
                    required
                    min="0"
                    step="0.01"
                    value={formData.discount_value}
                    onChange={(e) => setFormData({ ...formData, discount_value: parseFloat(e.target.value) })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                    placeholder={formData.discount_type === 'percentage' ? '10' : '50000'}
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Min Purchase */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Minimum Purchase (Rp)
                  </label>
                  <input
                    type="number"
                    min="0"
                    value={formData.min_purchase}
                    onChange={(e) => setFormData({ ...formData, min_purchase: parseInt(e.target.value) })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                    placeholder="0"
                  />
                </div>

                {/* Max Discount */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Max Discount (Rp) - Optional
                  </label>
                  <input
                    type="number"
                    min="0"
                    value={formData.max_discount}
                    onChange={(e) => setFormData({ ...formData, max_discount: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                    placeholder="Leave empty for no limit"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Start Date */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Start Date *
                  </label>
                  <input
                    type="datetime-local"
                    required
                    value={formData.start_date}
                    onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  />
                </div>

                {/* End Date */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    End Date *
                  </label>
                  <input
                    type="datetime-local"
                    required
                    value={formData.end_date}
                    onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  />
                </div>
              </div>

              {/* Usage Limit */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Usage Limit - Optional
                </label>
                <input
                  type="number"
                  min="0"
                  value={formData.usage_limit}
                  onChange={(e) => setFormData({ ...formData, usage_limit: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  placeholder="Leave empty for unlimited usage"
                />
              </div>

              {/* Active Status */}
              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="is_active"
                  checked={formData.is_active}
                  onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                  className="w-4 h-4 text-[#a97456] border-gray-300 rounded focus:ring-[#a97456]"
                />
                <label htmlFor="is_active" className="text-sm font-medium text-gray-700">
                  Activate this coupon immediately
                </label>
              </div>

              <div className="flex gap-3 pt-4">
                <button
                  type="button"
                  onClick={() => { setShowForm(false); resetForm(); }}
                  className="flex-1 px-4 py-2 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 px-4 py-2 bg-[#a97456] text-white rounded-lg font-medium hover:bg-[#8b6043] transition-colors"
                >
                  {editingId ? 'Update Coupon' : 'Create Coupon'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
