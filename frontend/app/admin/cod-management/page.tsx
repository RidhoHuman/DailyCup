'use client';

import { useEffect, useState } from 'react';
import { api } from '@/lib/api-client';
import { Package, Truck, CheckCircle, Clock, Phone, MapPin, DollarSign, Search, Filter, Eye, Edit } from 'lucide-react';
import Link from 'next/link';

interface CODOrder {
  id: number;
  order_id: string;
  courier_name: string | null;
  courier_phone: string | null;
  tracking_number: string | null;
  status: 'pending' | 'confirmed' | 'packed' | 'out_for_delivery' | 'delivered' | 'payment_received' | 'cancelled';
  payment_received: number;
  payment_amount: number | null;
  customer_name: string;
  customer_phone: string;
  customer_address: string;
  total: number;
  order_status: string;
  created_at: string;
  updated_at: string;
}

interface UpdateModalData {
  orderId: string;
  action: 'update_status' | 'assign_courier' | 'confirm_payment';
  currentStatus?: string;
}

export default function CODManagementPage() {
  const [orders, setOrders] = useState<CODOrder[]>([]);
  const [filteredOrders, setFilteredOrders] = useState<CODOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [paymentFilter, setPaymentFilter] = useState<string>('all');
  
  const [showUpdateModal, setShowUpdateModal] = useState(false);
  const [updateModalData, setUpdateModalData] = useState<UpdateModalData | null>(null);
  const [updateForm, setUpdateForm] = useState<any>({});
  const [updating, setUpdating] = useState(false);

  useEffect(() => {
    fetchCODOrders();
  }, []);

  useEffect(() => {
    filterOrders();
  }, [orders, searchTerm, statusFilter, paymentFilter]);

  const fetchCODOrders = async () => {
    try {
      setLoading(true);
      setError('');
      const response = await api.get('/cod_tracking.php?all=1');
      
      if (response.data.success) {
        setOrders(response.data.data || []);
      } else {
        setError(response.data.message || 'Failed to load COD orders');
      }
    } catch (err: any) {
      console.error('Error fetching COD orders:', err);
      setError(err.response?.data?.message || 'Failed to load COD orders');
    } finally {
      setLoading(false);
    }
  };

  const filterOrders = () => {
    let filtered = [...orders];

    // Search filter
    if (searchTerm) {
      const term = searchTerm.toLowerCase();
      filtered = filtered.filter(order => 
        order.order_id.toLowerCase().includes(term) ||
        order.customer_name.toLowerCase().includes(term) ||
        order.customer_phone.includes(term) ||
        (order.courier_name?.toLowerCase().includes(term) || false)
      );
    }

    // Status filter
    if (statusFilter !== 'all') {
      filtered = filtered.filter(order => order.status === statusFilter);
    }

    // Payment filter
    if (paymentFilter === 'paid') {
      filtered = filtered.filter(order => order.payment_received === 1);
    } else if (paymentFilter === 'unpaid') {
      filtered = filtered.filter(order => order.payment_received === 0);
    }

    setFilteredOrders(filtered);
  };

  const openUpdateModal = (orderId: string, action: UpdateModalData['action'], currentStatus?: string) => {
    setUpdateModalData({ orderId, action, currentStatus });
    setUpdateForm({});
    setShowUpdateModal(true);
  };

  const handleUpdate = async () => {
    if (!updateModalData) return;

    try {
      setUpdating(true);
      const payload: any = {
        order_id: updateModalData.orderId,
        action: updateModalData.action,
        ...updateForm,
      };

      const response = await api.post('/cod_tracking.php', payload);
      
      if (response.data.success) {
        alert('COD tracking updated successfully!');
        setShowUpdateModal(false);
        fetchCODOrders();
      } else {
        alert(response.data.message || 'Update failed');
      }
    } catch (err: any) {
      console.error('Error updating COD tracking:', err);
      alert(err.response?.data?.message || 'Failed to update tracking');
    } finally {
      setUpdating(false);
    }
  };

  const getStatusBadge = (status: string) => {
    const badges: Record<string, { color: string; label: string }> = {
      pending: { color: 'bg-gray-100 text-gray-800', label: 'Pending' },
      confirmed: { color: 'bg-blue-100 text-blue-800', label: 'Confirmed' },
      packed: { color: 'bg-purple-100 text-purple-800', label: 'Packed' },
      out_for_delivery: { color: 'bg-yellow-100 text-yellow-800', label: 'Out for Delivery' },
      delivered: { color: 'bg-green-100 text-green-800', label: 'Delivered' },
      payment_received: { color: 'bg-emerald-100 text-emerald-800', label: 'Payment Received' },
      cancelled: { color: 'bg-red-100 text-red-800', label: 'Cancelled' },
    };
    const badge = badges[status] || badges.pending;
    return <span className={`px-2 py-1 rounded-full text-xs font-medium ${badge.color}`}>{badge.label}</span>;
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading COD orders...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-800 mb-2">COD Order Management</h1>
          <p className="text-gray-600">Manage Cash on Delivery orders and tracking</p>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow p-6 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            {/* Search */}
            <div className="md:col-span-2">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={20} />
                <input
                  type="text"
                  placeholder="Search by order ID, customer name, phone..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                />
              </div>
            </div>

            {/* Status Filter */}
            <div>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              >
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="packed">Packed</option>
                <option value="out_for_delivery">Out for Delivery</option>
                <option value="delivered">Delivered</option>
                <option value="payment_received">Payment Received</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>

            {/* Payment Filter */}
            <div>
              <select
                value={paymentFilter}
                onChange={(e) => setPaymentFilter(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              >
                <option value="all">All Payments</option>
                <option value="paid">Paid</option>
                <option value="unpaid">Unpaid</option>
              </select>
            </div>
          </div>

          {/* Results Count */}
          <div className="mt-4 text-sm text-gray-600">
            Showing {filteredOrders.length} of {orders.length} orders
          </div>
        </div>

        {/* Error Message */}
        {error && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p className="text-red-600">{error}</p>
          </div>
        )}

        {/* Orders Table */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Order Info
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Customer
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Courier
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Payment
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Amount
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredOrders.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="px-6 py-8 text-center text-gray-500">
                      No COD orders found
                    </td>
                  </tr>
                ) : (
                  filteredOrders.map((order) => (
                    <tr key={order.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm">
                          <div className="font-medium text-gray-900">{order.order_id}</div>
                          <div className="text-gray-500">{formatDate(order.created_at)}</div>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="text-sm">
                          <div className="font-medium text-gray-900">{order.customer_name}</div>
                          <div className="text-gray-500">{order.customer_phone}</div>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="text-sm">
                          {order.courier_name ? (
                            <>
                              <div className="font-medium text-gray-900">{order.courier_name}</div>
                              <div className="text-gray-500">{order.courier_phone}</div>
                            </>
                          ) : (
                            <button
                              onClick={() => openUpdateModal(order.order_id, 'assign_courier')}
                              className="text-[#a97456] hover:text-[#8b5e3c] text-xs font-medium"
                            >
                              + Assign Courier
                            </button>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {getStatusBadge(order.status)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {order.payment_received === 1 ? (
                          <span className="flex items-center gap-1 text-green-600">
                            <CheckCircle size={16} />
                            <span className="text-sm font-medium">Paid</span>
                          </span>
                        ) : (
                          <button
                            onClick={() => openUpdateModal(order.order_id, 'confirm_payment')}
                            className="text-yellow-600 hover:text-yellow-700 text-xs font-medium"
                          >
                            Mark as Paid
                          </button>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {formatCurrency(order.total)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div className="flex items-center justify-end gap-2">
                          <Link
                            href={`/orders/${order.order_id}/tracking`}
                            className="text-blue-600 hover:text-blue-700"
                            title="View Tracking"
                          >
                            <Eye size={18} />
                          </Link>
                          <button
                            onClick={() => openUpdateModal(order.order_id, 'update_status', order.status)}
                            className="text-[#a97456] hover:text-[#8b5e3c]"
                            title="Update Status"
                          >
                            <Edit size={18} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Update Modal */}
        {showUpdateModal && updateModalData && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
              <h2 className="text-xl font-bold text-gray-800 mb-4">
                {updateModalData.action === 'update_status' && 'Update Order Status'}
                {updateModalData.action === 'assign_courier' && 'Assign Courier'}
                {updateModalData.action === 'confirm_payment' && 'Confirm Payment'}
              </h2>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Order ID
                  </label>
                  <input
                    type="text"
                    value={updateModalData.orderId}
                    disabled
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                  />
                </div>

                {updateModalData.action === 'update_status' && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      New Status
                    </label>
                    <select
                      value={updateForm.status || ''}
                      onChange={(e) => setUpdateForm({ ...updateForm, status: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456]"
                    >
                      <option value="">Select status</option>
                      <option value="pending">Pending</option>
                      <option value="confirmed">Confirmed</option>
                      <option value="packed">Packed</option>
                      <option value="out_for_delivery">Out for Delivery</option>
                      <option value="delivered">Delivered</option>
                      <option value="payment_received">Payment Received</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </div>
                )}

                {updateModalData.action === 'assign_courier' && (
                  <>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Courier Name
                      </label>
                      <input
                        type="text"
                        value={updateForm.courier_name || ''}
                        onChange={(e) => setUpdateForm({ ...updateForm, courier_name: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456]"
                        placeholder="Enter courier name"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Courier Phone
                      </label>
                      <input
                        type="tel"
                        value={updateForm.courier_phone || ''}
                        onChange={(e) => setUpdateForm({ ...updateForm, courier_phone: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456]"
                        placeholder="Enter phone number"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Tracking Number (Optional)
                      </label>
                      <input
                        type="text"
                        value={updateForm.tracking_number || ''}
                        onChange={(e) => setUpdateForm({ ...updateForm, tracking_number: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456]"
                        placeholder="Enter tracking number"
                      />
                    </div>
                  </>
                )}

                {updateModalData.action === 'confirm_payment' && (
                  <>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Payment Amount
                      </label>
                      <input
                        type="number"
                        value={updateForm.payment_amount || ''}
                        onChange={(e) => setUpdateForm({ ...updateForm, payment_amount: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456]"
                        placeholder="Enter amount received"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Notes (Optional)
                      </label>
                      <textarea
                        value={updateForm.payment_notes || ''}
                        onChange={(e) => setUpdateForm({ ...updateForm, payment_notes: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456]"
                        rows={3}
                        placeholder="Any notes about the payment..."
                      />
                    </div>
                  </>
                )}
              </div>

              <div className="mt-6 flex gap-3">
                <button
                  onClick={() => setShowUpdateModal(false)}
                  disabled={updating}
                  className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                >
                  Cancel
                </button>
                <button
                  onClick={handleUpdate}
                  disabled={updating}
                  className="flex-1 px-4 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8b5e3c] disabled:opacity-50"
                >
                  {updating ? 'Updating...' : 'Update'}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
