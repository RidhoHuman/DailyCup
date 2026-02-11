"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api-client";
import Image from "next/image";

interface ReturnItem {
  product_name: string;
  variant?: string;
  quantity: number;
  price: number;
}

interface ProductReturn {
  id: string;
  return_number: string;
  order_id: string;
  order_number: string;
  order_total: number;
  user_id: string;
  customer_name: string;
  customer_email: string;
  items: ReturnItem[];
  reason: string;
  description: string;
  images: string[] | null;
  refund_method: string;
  refund_amount: number;
  status: 'pending' | 'approved' | 'rejected' | 'refunded' | 'completed';
  admin_notes: string | null;
  rejection_reason: string | null;
  created_at: string;
  updated_at: string;
  processed_at: string | null;
  processed_by: string | null;
  processed_by_name: string | null;
}

export default function AdminReturnsPage() {
  const [returns, setReturns] = useState<ProductReturn[]>([]);
  const [selectedReturn, setSelectedReturn] = useState<ProductReturn | null>(null);
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);
  
  // Filters
  const [statusFilter, setStatusFilter] = useState<'all' | 'pending' | 'approved' | 'rejected' | 'refunded' | 'completed'>('all');

  // Modals
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [showApproveModal, setShowApproveModal] = useState(false);
  const [rejectionReason, setRejectionReason] = useState('');
  const [adminNotes, setAdminNotes] = useState('');
  const [refundAmount, setRefundAmount] = useState(0);

  useEffect(() => {
    fetchReturns();
    const interval = setInterval(fetchReturns, 15000);
    return () => clearInterval(interval);
  }, [statusFilter]);

  const fetchReturns = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (statusFilter !== 'all') params.append('status', statusFilter);
      
      const response = await api.get<{success: boolean; returns: ProductReturn[]}>(`/returns.php?${params}`, { requiresAuth: true });
      if (response.success) {
        setReturns(response.returns);
      }
    } catch (error) {
      console.error('Failed to fetch returns:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async () => {
    if (!selectedReturn) return;

    try {
      setProcessing(true);
      const response = await api.put<{success: boolean}>('/returns.php', {
        id: selectedReturn.id,
        status: 'approved',
        admin_notes: adminNotes,
        refund_amount: refundAmount
      }, { requiresAuth: true });

      if (response.success) {
        alert('Return approved successfully!');
        setShowApproveModal(false);
        setAdminNotes('');
        await fetchReturns();
        setSelectedReturn(null);
      }
    } catch (error) {
      console.error('Failed to approve return:', error);
      alert('Failed to approve return');
    } finally {
      setProcessing(false);
    }
  };

  const handleReject = async () => {
    if (!selectedReturn || !rejectionReason.trim()) {
      alert('Please provide a reason for rejection');
      return;
    }

    try {
      setProcessing(true);
      const response = await api.put<{success: boolean}>('/returns.php', {
        id: selectedReturn.id,
        status: 'rejected',
        rejection_reason: rejectionReason
      }, { requiresAuth: true });

      if (response.success) {
        alert('Return rejected');
        setShowRejectModal(false);
        setRejectionReason('');
        await fetchReturns();
        setSelectedReturn(null);
      }
    } catch (error) {
      console.error('Failed to reject return:', error);
      alert('Failed to reject return');
    } finally {
      setProcessing(false);
    }
  };

  const handleMarkRefunded = async (returnId: string) => {
    if (!confirm('Mark this return as refunded?')) return;

    try {
      const response = await api.put<{success: boolean}>('/returns.php', {
        id: returnId,
        status: 'refunded'
      }, { requiresAuth: true });

      if (response.success) {
        await fetchReturns();
        if (selectedReturn?.id === returnId) {
          setSelectedReturn(null);
        }
      }
    } catch (error) {
      console.error('Failed to mark as refunded:', error);
      alert('Failed to update status');
    }
  };

  const handleMarkCompleted = async (returnId: string) => {
    if (!confirm('Mark this return as completed?')) return;

    try {
      const response = await api.put<{success: boolean}>('/returns.php', {
        id: returnId,
        status: 'completed'
      }, { requiresAuth: true });

      if (response.success) {
        await fetchReturns();
        if (selectedReturn?.id === returnId) {
          setSelectedReturn(null);
        }
      }
    } catch (error) {
      console.error('Failed to mark as completed:', error);
      alert('Failed to update status');
    }
  };

  const stats = {
    total: returns.length,
    pending: returns.filter(r => r.status === 'pending').length,
    approved: returns.filter(r => r.status === 'approved').length,
    refunded: returns.filter(r => r.status === 'refunded').length,
    rejected: returns.filter(r => r.status === 'rejected').length,
    total_refund: returns.filter(r => r.status === 'refunded').reduce((sum, r) => sum + r.refund_amount, 0)
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'text-yellow-600 bg-yellow-100';
      case 'approved': return 'text-blue-600 bg-blue-100';
      case 'rejected': return 'text-red-600 bg-red-100';
      case 'refunded': return 'text-green-600 bg-green-100';
      case 'completed': return 'text-gray-600 bg-gray-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  if (loading && returns.length === 0) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <i className="bi bi-arrow-repeat animate-spin text-4xl text-[#a97456] mb-4"></i>
          <p className="text-gray-600">Loading returns...</p>
        </div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800 mb-2">Return Management</h1>
        <p className="text-gray-500">Manage product return requests</p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Total</p>
              <h3 className="text-2xl font-bold text-gray-800">{stats.total}</h3>
            </div>
            <i className="bi bi-arrow-return-left text-2xl text-gray-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Pending</p>
              <h3 className="text-2xl font-bold text-yellow-600">{stats.pending}</h3>
            </div>
            <i className="bi bi-clock text-2xl text-yellow-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Approved</p>
              <h3 className="text-2xl font-bold text-blue-600">{stats.approved}</h3>
            </div>
            <i className="bi bi-check-circle text-2xl text-blue-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Refunded</p>
              <h3 className="text-2xl font-bold text-green-600">{stats.refunded}</h3>
            </div>
            <i className="bi bi-cash-coin text-2xl text-green-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Rejected</p>
              <h3 className="text-2xl font-bold text-red-600">{stats.rejected}</h3>
            </div>
            <i className="bi bi-x-circle text-2xl text-red-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Total Refund</p>
              <h3 className="text-xl font-bold text-[#a97456]">
                Rp {stats.total_refund.toLocaleString('id-ID')}
              </h3>
            </div>
            <i className="bi bi-cash-stack text-2xl text-[#a97456]"></i>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
        <div className="flex gap-2">
          <button
            onClick={() => setStatusFilter('all')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              statusFilter === 'all' ? 'bg-[#a97456] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            All
          </button>
          <button
            onClick={() => setStatusFilter('pending')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              statusFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            Pending
          </button>
          <button
            onClick={() => setStatusFilter('approved')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              statusFilter === 'approved' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            Approved
          </button>
          <button
            onClick={() => setStatusFilter('refunded')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              statusFilter === 'refunded' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            Refunded
          </button>
          <button
            onClick={() => setStatusFilter('rejected')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              statusFilter === 'rejected' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            Rejected
          </button>
          <button
            onClick={() => setStatusFilter('completed')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              statusFilter === 'completed' ? 'bg-gray-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            Completed
          </button>
        </div>
      </div>

      {/* Returns Table */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {returns.length === 0 ? (
          <div className="p-12 text-center text-gray-500">
            <i className="bi bi-inbox text-6xl mb-4"></i>
            <p className="text-lg">No returns found</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr className="text-left text-xs font-semibold text-gray-500 uppercase">
                  <th className="px-6 py-4">Return #</th>
                  <th className="px-6 py-4">Customer</th>
                  <th className="px-6 py-4">Order</th>
                  <th className="px-6 py-4">Items</th>
                  <th className="px-6 py-4">Refund Amount</th>
                  <th className="px-6 py-4">Status</th>
                  <th className="px-6 py-4">Created</th>
                  <th className="px-6 py-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {returns.map((returnItem) => (
                  <tr key={returnItem.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4">
                      <span className="font-mono text-sm font-medium text-gray-800">
                        {returnItem.return_number}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <div className="font-medium text-gray-800">{returnItem.customer_name}</div>
                      <div className="text-sm text-gray-500">{returnItem.customer_email}</div>
                    </td>
                    <td className="px-6 py-4">
                      <span className="font-mono text-sm text-gray-600">{returnItem.order_number}</span>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      {returnItem.items.length} item(s)
                    </td>
                    <td className="px-6 py-4">
                      <span className="font-semibold text-[#a97456]">
                        Rp {returnItem.refund_amount.toLocaleString('id-ID')}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(returnItem.status)}`}>
                        {returnItem.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      {new Date(returnItem.created_at).toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                      })}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <button
                        onClick={() => {
                          setSelectedReturn(returnItem);
                          setRefundAmount(returnItem.refund_amount);
                        }}
                        className="text-[#a97456] hover:text-[#8b5e3c] font-medium text-sm"
                      >
                        View Details
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Return Detail Modal */}
      {selectedReturn && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 overflow-y-auto">
          <div className="bg-white rounded-2xl shadow-xl max-w-3xl w-full my-8">
            <div className="p-6 border-b border-gray-100">
              <div className="flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-800">Return Request Details</h2>
                <button
                  onClick={() => setSelectedReturn(null)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <i className="bi bi-x-lg text-xl"></i>
                </button>
              </div>
            </div>
            
            <div className="p-6 space-y-6 max-h-[calc(90vh-200px)] overflow-y-auto">
              {/* Header Info */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-gray-500">Return Number</p>
                  <p className="font-mono font-semibold text-gray-800">{selectedReturn.return_number}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Order Number</p>
                  <p className="font-mono font-semibold text-gray-800">{selectedReturn.order_number}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Customer</p>
                  <p className="font-medium text-gray-800">{selectedReturn.customer_name}</p>
                  <p className="text-sm text-gray-500">{selectedReturn.customer_email}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">Status</p>
                  <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(selectedReturn.status)}`}>
                    {selectedReturn.status}
                  </span>
                </div>
              </div>

              {/* Items */}
              <div>
                <h3 className="font-semibold text-gray-800 mb-3">Returned Items</h3>
                <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                  {selectedReturn.items.map((item, idx) => (
                    <div key={idx} className="flex justify-between items-start">
                      <div>
                        <p className="font-medium text-gray-800">{item.product_name}</p>
                        {item.variant && <p className="text-sm text-gray-500">{item.variant}</p>}
                        <p className="text-sm text-gray-600">Qty: {item.quantity}</p>
                      </div>
                      <p className="font-semibold text-gray-800">
                        Rp {(item.price * item.quantity).toLocaleString('id-ID')}
                      </p>
                    </div>
                  ))}
                  <div className="border-t border-gray-200 pt-3 mt-3">
                    <div className="flex justify-between items-center">
                      <p className="font-semibold text-gray-800">Total Refund Amount</p>
                      <p className="text-xl font-bold text-[#a97456]">
                        Rp {selectedReturn.refund_amount.toLocaleString('id-ID')}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Reason */}
              <div>
                <h3 className="font-semibold text-gray-800 mb-3">Return Reason</h3>
                <div className="bg-gray-50 rounded-lg p-4">
                  <p className="text-sm font-medium text-gray-700 mb-2 capitalize">{selectedReturn.reason.replace('_', ' ')}</p>
                  <p className="text-gray-700">{selectedReturn.description}</p>
                </div>
              </div>

              {/* Images */}
              {selectedReturn.images && selectedReturn.images.length > 0 && (
                <div>
                  <h3 className="font-semibold text-gray-800 mb-3">Evidence Photos</h3>
                  <div className="grid grid-cols-3 gap-3">
                    {selectedReturn.images.map((img, idx) => (
                      <div key={idx} className="relative aspect-square rounded-lg overflow-hidden border border-gray-200">
                        <Image
                          src={img}
                          alt={`Evidence ${idx + 1}`}
                          fill
                          className="object-cover"
                        />
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Admin Notes */}
              {selectedReturn.admin_notes && (
                <div>
                  <h3 className="font-semibold text-gray-800 mb-3">Admin Notes</h3>
                  <div className="bg-blue-50 rounded-lg p-4">
                    <p className="text-gray-700">{selectedReturn.admin_notes}</p>
                  </div>
                </div>
              )}

              {/* Rejection Reason */}
              {selectedReturn.rejection_reason && (
                <div>
                  <h3 className="font-semibold text-gray-800 mb-3">Rejection Reason</h3>
                  <div className="bg-red-50 rounded-lg p-4">
                    <p className="text-red-900">{selectedReturn.rejection_reason}</p>
                  </div>
                </div>
              )}

              {/* Metadata */}
              <div className="bg-gray-50 rounded-lg p-4 text-sm text-gray-600 space-y-1">
                <p>Created: {new Date(selectedReturn.created_at).toLocaleString('id-ID')}</p>
                {selectedReturn.processed_at && (
                  <p>Processed: {new Date(selectedReturn.processed_at).toLocaleString('id-ID')} by {selectedReturn.processed_by_name}</p>
                )}
                <p>Refund Method: {selectedReturn.refund_method.replace('_', ' ')}</p>
              </div>
            </div>

            {/* Actions */}
            <div className="p-6 border-t border-gray-100">
              {selectedReturn.status === 'pending' && (
                <div className="flex gap-3">
                  <button
                    onClick={() => setShowApproveModal(true)}
                    className="flex-1 px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium"
                  >
                    <i className="bi bi-check-circle mr-2"></i>
                    Approve Return
                  </button>
                  <button
                    onClick={() => setShowRejectModal(true)}
                    className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                  >
                    <i className="bi bi-x-circle mr-2"></i>
                    Reject Return
                  </button>
                </div>
              )}
              {selectedReturn.status === 'approved' && (
                <button
                  onClick={() => handleMarkRefunded(selectedReturn.id)}
                  className="w-full px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium"
                >
                  <i className="bi bi-cash-coin mr-2"></i>
                  Mark as Refunded
                </button>
              )}
              {selectedReturn.status === 'refunded' && (
                <button
                  onClick={() => handleMarkCompleted(selectedReturn.id)}
                  className="w-full px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors font-medium"
                >
                  <i className="bi bi-check-all mr-2"></i>
                  Mark as Completed
                </button>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Approve Modal */}
      {showApproveModal && selectedReturn && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-[60]">
          <div className="bg-white rounded-2xl shadow-xl max-w-md w-full">
            <div className="p-6 border-b border-gray-100">
              <h3 className="text-xl font-bold text-gray-800">Approve Return</h3>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Refund Amount (Rp)
                </label>
                <input
                  type="number"
                  value={refundAmount}
                  onChange={(e) => setRefundAmount(parseFloat(e.target.value))}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Admin Notes (Optional)
                </label>
                <textarea
                  value={adminNotes}
                  onChange={(e) => setAdminNotes(e.target.value)}
                  placeholder="Add notes about this approval..."
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent resize-none"
                  rows={3}
                />
              </div>
            </div>
            <div className="p-6 border-t border-gray-100 flex gap-3">
              <button
                onClick={() => setShowApproveModal(false)}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
              >
                Cancel
              </button>
              <button
                onClick={handleApprove}
                disabled={processing}
                className="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
              >
                {processing ? 'Processing...' : 'Approve'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Reject Modal */}
      {showRejectModal && selectedReturn && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-[60]">
          <div className="bg-white rounded-2xl shadow-xl max-w-md w-full">
            <div className="p-6 border-b border-gray-100">
              <h3 className="text-xl font-bold text-gray-800">Reject Return</h3>
            </div>
            <div className="p-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Reason for rejection <span className="text-red-500">*</span>
              </label>
              <textarea
                value={rejectionReason}
                onChange={(e) => setRejectionReason(e.target.value)}
                placeholder="Explain why this return is being rejected..."
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent resize-none"
                rows={4}
                required
              />
            </div>
            <div className="p-6 border-t border-gray-100 flex gap-3">
              <button
                onClick={() => {
                  setShowRejectModal(false);
                  setRejectionReason('');
                }}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
              >
                Cancel
              </button>
              <button
                onClick={handleReject}
                disabled={processing || !rejectionReason.trim()}
                className="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
              >
                {processing ? 'Processing...' : 'Reject'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
