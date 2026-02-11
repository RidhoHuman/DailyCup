"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { api } from "@/lib/api-client";
import InvoiceButton from "@/components/InvoiceButton";

interface OrderItem {
  id: number;
  product_id: number;
  product_name: string;
  quantity: number;
  price: number;
  subtotal: number;
}

interface Order {
  id: string;
  order_number: string;
  user_id?: number;
  customer_name: string;
  email: string;
  phone: string;
  total_amount: number;
  discount_amount: number;
  final_amount: number;
  delivery_method?: string;
  delivery_address?: string;
  status: string;
  payment_status: string;
  payment_method: string;
  items: OrderItem[];
  items_count?: number;
  created_at: string;
  updated_at?: string;
  kurir_id?: number;
  kurir_name?: string;
  notes?: string;
}

interface Kurir {
  id: number;
  name: string;
  status: string;
}

export default function AdminOrdersPage() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [kurirs, setKurirs] = useState<Kurir[]>([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState("all");
  const [paymentStatusFilter, setPaymentStatusFilter] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

  useEffect(() => {
    fetchOrders();
    fetchKurirs();
  }, []);

  const fetchOrders = async () => {
    try {
      setLoading(true);
      const response = await api.get<{ success: boolean; orders: Order[] }>(
        "/orders.php?limit=100&include_items=1",
        { requiresAuth: true }
      );
      if (response.success && response.orders) {
        setOrders(response.orders);
      } else {
        console.warn('[Orders] API returned no orders data');
        setOrders([]); // Set empty array instead of leaving undefined
      }
    } catch (error) {
      console.error("[Orders] Error fetching orders:", error);
      setOrders([]); // Set empty array on error
    } finally {
      setLoading(false);
    }
  };

  const fetchKurirs = async () => {
    try {
      const response = await api.get<{ success: boolean; kurirs: Kurir[] }>(
        "/kurir.php?status=available",
        { requiresAuth: true }
      );
      if (response.success) {
        setKurirs(response.kurirs);
      }
    } catch (error) {
      console.error("Error fetching kurirs:", error);
    }
  };

  const handleUpdateStatus = async (orderId: string, newStatus: string) => {
    try {
      const response = await api.put<{ success: boolean; message?: string }>(`/orders.php?id=${orderId}`, {
        status: newStatus
      }, { requiresAuth: true });
      
      if (response.success) {
        // Check if payment was auto-updated
        if (response.message && response.message.includes('auto-updated')) {
          alert(`✅ ${response.message}`);
        }
        
        fetchOrders();
        if (selectedOrder && selectedOrder.id === orderId) {
          // Refresh the selected order details to get updated payment status
          viewOrderDetails(selectedOrder);
        }
      }
    } catch (error) {
      console.error("Error updating status:", error);
      alert("Failed to update order status");
    }
  };

  const handleUpdatePaymentStatus = async (orderId: string, newPaymentStatus: string) => {
    try {
      await api.put<{ success: boolean }>(`/orders.php?id=${orderId}`, {
        payment_status: newPaymentStatus
      }, { requiresAuth: true });
      
      fetchOrders();
      if (selectedOrder && selectedOrder.id === orderId) {
        setSelectedOrder({ ...selectedOrder, payment_status: newPaymentStatus });
      }
    } catch (error) {
      console.error("Error updating payment status:", error);
      alert("Failed to update payment status");
    }
  };

  const handleAssignKurir = async (orderId: string, kurirId: number) => {
    try {
      await api.put<{ success: boolean }>(`/orders.php?id=${orderId}`, {
        kurir_id: kurirId
      }, { requiresAuth: true });
      
      fetchOrders();
      if (selectedOrder) {
        const kurir = kurirs.find(k => k.id === kurirId);
        if (kurir) {
          setSelectedOrder({ ...selectedOrder, kurir_id: kurirId, kurir_name: kurir.name });
        }
      }
      alert("Kurir assigned successfully");
    } catch (error) {
      console.error("Error assigning kurir:", error);
      alert("Failed to assign kurir");
    }
  };

  const viewOrderDetails = async (order: Order) => {
    try {
      console.log('[Orders] Fetching order details for:', order.id);
      
      const response = await api.get<{ success: boolean; order: Order }>(
        `/orders.php?id=${order.id}&include_items=1`,
        { requiresAuth: true }
      );
      
      console.log('[Orders] API response:', response);
      
      if (response.success && response.order) {
        // Map API response to frontend Order interface
        const orderData: Order = {
          ...response.order,
          // Ensure items is an array
          items: Array.isArray(response.order.items) ? response.order.items : []
        };
        
        console.log('[Orders] Setting order data:', orderData);
        setSelectedOrder(orderData);
      } else {
        console.error('[Orders] Invalid API response:', response);
        alert('Failed to load order details: Invalid response format');
      }
    } catch (error: any) {
      console.error('[Orders] Error fetching order details:', error);
      console.error('[Orders] Error details:', {
        message: error.message,
        stack: error.stack,
        orderId: order.id
      });
      alert(`Failed to load order details: ${error.message || 'Unknown error'}`);
    }
  };

  // SAFETY: Handle undefined orders gracefully
  const filteredOrders = (orders || []).filter((order) => {
    if (statusFilter !== "all" && order.status !== statusFilter) return false;
    if (paymentStatusFilter !== "all" && order.payment_status !== paymentStatusFilter) return false;
    
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      return (
        order.order_number?.toLowerCase().includes(query) ||
        order.customer_name?.toLowerCase().includes(query) ||
        order.email?.toLowerCase().includes(query) ||
        order.phone?.toLowerCase().includes(query)
      );
    }
    
    return true;
  });

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      pending: "bg-yellow-100 text-yellow-700 border-yellow-300",
      confirmed: "bg-blue-100 text-blue-700 border-blue-300",
      processing: "bg-purple-100 text-purple-700 border-purple-300",
      ready: "bg-cyan-100 text-cyan-700 border-cyan-300",
      delivering: "bg-indigo-100 text-indigo-700 border-indigo-300",
      completed: "bg-green-100 text-green-700 border-green-300",
      cancelled: "bg-red-100 text-red-700 border-red-300",
      refunded: "bg-gray-100 text-gray-700 border-gray-300",
    };
    return colors[status] || "bg-gray-100 text-gray-700 border-gray-300";
  };

  const getPaymentStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      pending: "bg-yellow-100 text-yellow-700",
      paid: "bg-green-100 text-green-700",
      failed: "bg-red-100 text-red-700",
      refunded: "bg-gray-100 text-gray-700",
    };
    return colors[status] || "bg-gray-100 text-gray-700";
  };

  const formatCurrency = (amount: number | string) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0
    }).format(Number(amount) || 0);
  };

  const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    const d = new Date(dateString.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dateString;
    return d.toLocaleString("id-ID", {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading && orders.length === 0) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto mb-4"></div>
          <p className="text-gray-500">Loading orders...</p>
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
            <h1 className="text-2xl font-bold text-gray-800 mb-2">Order Management</h1>
            <p className="text-gray-500">Manage all orders, status, and deliveries</p>
          </div>
          <Link
            href="/admin/orders/kanban"
            className="px-6 py-3 bg-[#a97456] text-white rounded-lg hover:bg-[#8b6043] transition-colors font-medium"
          >
            <i className="bi bi-kanban mr-2"></i>
            Kanban View
          </Link>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {/* Search */}
          <div>
            <div className="relative">
              <i className="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
              <input
                type="text"
                placeholder="Search orders..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              />
            </div>
          </div>

          {/* Order Status Filter */}
          <div>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
            >
              <option value="all">All Status</option>
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="processing">Processing</option>
              <option value="ready">Ready</option>
              <option value="delivering">Delivering</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          {/* Payment Status Filter */}
          <div>
            <select
              value={paymentStatusFilter}
              onChange={(e) => setPaymentStatusFilter(e.target.value)}
              className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
            >
              <option value="all">All Payments</option>
              <option value="pending">Payment Pending</option>
              <option value="paid">Paid</option>
              <option value="failed">Failed</option>
              <option value="refunded">Refunded</option>
            </select>
          </div>
        </div>
      </div>

      {/* Orders Table */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {filteredOrders.length === 0 ? (
          <div className="p-12 text-center text-gray-500">
            <i className="bi bi-box-seam text-6xl text-gray-300 mb-4"></i>
            <p className="text-lg">No orders found</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Order #</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Customer</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Items</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Total</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Status</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Payment</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Date</th>
                  <th className="px-6 py-4 text-right text-sm font-semibold text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {filteredOrders.map((order) => (
                  <tr key={order.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4 font-mono text-sm font-medium text-gray-800">
                      #{order.order_number}
                    </td>
                    <td className="px-6 py-4">
                      <div>
                        <div className="font-medium text-gray-800">{order.customer_name}</div>
                        <div className="text-xs text-gray-500">{order.email}</div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-gray-600">
                      {Array.isArray(order.items) ? order.items.length : order.items} items
                    </td>
                    <td className="px-6 py-4 font-semibold text-gray-800">
                      {formatCurrency(order.final_amount)}
                    </td>
                    <td className="px-6 py-4">
                      <select
                        value={order.status}
                        onChange={(e) => handleUpdateStatus(order.id, e.target.value)}
                        className={`px-3 py-1 rounded-full text-xs font-medium border ${getStatusColor(order.status)} cursor-pointer`}
                      >
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="processing">Processing</option>
                        <option value="ready">Ready</option>
                        <option value="delivering">Delivering</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                      </select>
                    </td>
                    <td className="px-6 py-4">
                      <span className={`px-3 py-1 rounded-full text-xs font-medium ${getPaymentStatusColor(order.payment_status)}`}>
                        {order.payment_status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      {formatDate(order.created_at)}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <InvoiceButton orderId={order.order_number} variant="icon" />
                        <button 
                          onClick={() => viewOrderDetails(order)}
                          className="px-3 py-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm"
                        >
                          <i className="bi bi-eye mr-1"></i>
                          View
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

      {/* Order Details Modal - Redesigned */}
      {selectedOrder && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" onClick={() => setSelectedOrder(null)}>
          <div className="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] flex flex-col" onClick={(e) => e.stopPropagation()}>
            {/* Header - Fixed */}
            <div className="flex-shrink-0 bg-gradient-to-r from-[#a97456] to-[#8b6043] text-white px-6 py-4 rounded-t-2xl">
              <div className="flex justify-between items-center">
                <div>
                  <div className="flex items-center gap-3">
                    <i className="bi bi-receipt-cutoff text-2xl"></i>
                    <div>
                      <h2 className="text-xl font-bold">#{selectedOrder.order_number}</h2>
                      <p className="text-white/80 text-sm">{formatDate(selectedOrder.created_at)}</p>
                    </div>
                  </div>
                </div>
                <button
                  onClick={() => setSelectedOrder(null)}
                  className="w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 transition-colors flex items-center justify-center"
                >
                  <i className="bi bi-x-lg text-xl"></i>
                </button>
              </div>
              
              {/* Status badges in header */}
              <div className="flex gap-2 mt-3">
                <span className={`px-3 py-1 rounded-full text-xs font-semibold ${getStatusColor(selectedOrder.status)} border`}>
                  {selectedOrder.status}
                </span>
                <span className={`px-3 py-1 rounded-full text-xs font-semibold ${getPaymentStatusColor(selectedOrder.payment_status)}`}>
                  {selectedOrder.payment_status}
                </span>
                {selectedOrder.kurir_name && (
                  <span className="px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white">
                    <i className="bi bi-bicycle mr-1"></i>{selectedOrder.kurir_name}
                  </span>
                )}
              </div>
            </div>

            {/* Scrollable Content */}
            <div className="flex-1 overflow-y-auto px-6 py-5 space-y-4">
              {/* Customer Info - Compact Grid */}
              <div className="bg-gray-50 rounded-xl p-4 border border-gray-100">
                <h3 className="font-bold text-gray-800 mb-3 text-sm uppercase tracking-wide flex items-center gap-2">
                  <i className="bi bi-person-circle text-[#a97456]"></i>
                  Customer
                </h3>
                <div className="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                  <div>
                    <div className="text-gray-500 text-xs">Name</div>
                    <div className="font-semibold text-gray-900">{selectedOrder.customer_name}</div>
                  </div>
                  <div>
                    <div className="text-gray-500 text-xs">Phone</div>
                    <div className="font-semibold text-gray-900">{selectedOrder.phone || '-'}</div>
                  </div>
                  <div className="col-span-2">
                    <div className="text-gray-500 text-xs">Email</div>
                    <div className="font-medium text-gray-700">{selectedOrder.email}</div>
                  </div>
                  <div>
                    <div className="text-gray-500 text-xs">Payment</div>
                    <div className="font-medium text-gray-700 capitalize">{selectedOrder.payment_method}</div>
                  </div>
                  <div>
                    <div className="text-gray-500 text-xs">Delivery</div>
                    <div className="font-medium text-gray-700 capitalize">{selectedOrder.delivery_method || '-'}</div>
                  </div>
                  {selectedOrder.delivery_address && (
                    <div className="col-span-2">
                      <div className="text-gray-500 text-xs">Address</div>
                      <div className="font-medium text-gray-700">{selectedOrder.delivery_address}</div>
                    </div>
                  )}
                </div>
              </div>

              {/* Order Items - Compact List */}
              <div className="bg-gray-50 rounded-xl p-4 border border-gray-100">
                <h3 className="font-bold text-gray-800 mb-3 text-sm uppercase tracking-wide flex items-center gap-2">
                  <i className="bi bi-bag-check text-[#a97456]"></i>
                  Items ({Array.isArray(selectedOrder.items) ? selectedOrder.items.length : 0})
                </h3>
                <div className="space-y-2">
                  {Array.isArray(selectedOrder.items) && selectedOrder.items.length > 0 ? (
                    selectedOrder.items.map((item, idx) => (
                      <div key={item.id || idx} className="flex justify-between items-center bg-white p-3 rounded-lg border border-gray-100">
                        <div className="flex-1">
                          <div className="font-semibold text-gray-900 text-sm">{item.product_name}</div>
                          <div className="text-xs text-gray-500">
                            {formatCurrency(item.price)} × {item.quantity}
                          </div>
                        </div>
                        <div className="font-bold text-[#a97456]">
                          {formatCurrency(item.subtotal)}
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="text-center py-4 text-gray-400 text-sm">No items</div>
                  )}
                </div>
              </div>

              {/* Order Summary - Highlighted */}
              <div className="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border-2 border-[#a97456]/20">
                <h3 className="font-bold text-gray-800 mb-3 text-sm uppercase tracking-wide flex items-center gap-2">
                  <i className="bi bi-calculator text-[#a97456]"></i>
                  Summary
                </h3>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span className="font-semibold">{formatCurrency(selectedOrder.total_amount)}</span>
                  </div>
                  {(selectedOrder.discount_amount ?? 0) > 0 && (
                    <div className="flex justify-between text-green-600">
                      <span>Discount</span>
                      <span className="font-semibold">-{formatCurrency(selectedOrder.discount_amount)}</span>
                    </div>
                  )}
                  <div className="border-t-2 border-[#a97456]/20 pt-2 flex justify-between items-center">
                    <span className="font-bold text-gray-900">Total</span>
                    <span className="font-bold text-2xl text-[#a97456]">{formatCurrency(selectedOrder.final_amount)}</span>
                  </div>
                </div>
              </div>

              {/* Status Management - Side by Side */}
              <div className="grid grid-cols-2 gap-3">
                <div className="bg-white rounded-xl p-3 border border-gray-200">
                  <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 block">
                    Order Status
                  </label>
                  <select
                    value={selectedOrder.status}
                    onChange={(e) => handleUpdateStatus(selectedOrder.id, e.target.value)}
                    className={`w-full px-3 py-2 rounded-lg border-2 font-semibold text-sm ${getStatusColor(selectedOrder.status)} cursor-pointer focus:ring-2 focus:ring-[#a97456] focus:outline-none`}
                  >
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="processing">Processing</option>
                    <option value="ready">Ready</option>
                    <option value="delivering">Delivering</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                </div>

                <div className="bg-white rounded-xl p-3 border border-gray-200">
                  <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 block flex items-center justify-between">
                    <span>Payment Status</span>
                    {selectedOrder.payment_method?.toLowerCase() === 'cash' && (
                      <span className="text-xs font-normal text-gray-400 normal-case">
                        <i className="bi bi-lightning-charge-fill text-yellow-500"></i> Auto-managed
                      </span>
                    )}
                  </label>
                  <select
                    value={selectedOrder.payment_status}
                    onChange={(e) => handleUpdatePaymentStatus(selectedOrder.id, e.target.value)}
                    className={`w-full px-3 py-2 rounded-lg font-semibold text-sm ${getPaymentStatusColor(selectedOrder.payment_status)} cursor-pointer focus:ring-2 focus:ring-[#a97456] focus:outline-none border-2`}
                  >
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                  </select>
                  {selectedOrder.payment_method?.toLowerCase() === 'cash' && (
                    <div className="mt-1.5 text-xs text-gray-500 flex items-start gap-1">
                      <i className="bi bi-info-circle text-blue-500 mt-0.5"></i>
                      <span>Auto-updated to <strong>Paid</strong> when order is <strong>Completed</strong></span>
                    </div>
                  )}
                  {['transfer', 'qris'].includes(selectedOrder.payment_method?.toLowerCase() || '') && (
                    <div className="mt-1.5 text-xs text-gray-500 flex items-start gap-1">
                      <i className="bi bi-shield-check text-green-500 mt-0.5"></i>
                      <span>Xendit webhook will auto-update upon payment confirmation</span>
                    </div>
                  )}
                </div>
              </div>

              {/* Kurir Assignment */}
              <div className="bg-white rounded-xl p-3 border border-gray-200">
                <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 block flex items-center gap-2">
                  <i className="bi bi-bicycle text-[#a97456]"></i>
                  Assign Courier
                </label>
                <select
                  value={selectedOrder.kurir_id || ''}
                  onChange={(e) => handleAssignKurir(selectedOrder.id, Number(e.target.value))}
                  className="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#a97456] focus:border-[#a97456] text-sm font-medium"
                >
                  <option value="">Select Courier</option>
                  {kurirs.map((kurir) => (
                    <option key={kurir.id} value={kurir.id}>
                      {kurir.name} ({kurir.status})
                    </option>
                  ))}
                </select>
              </div>

              {/* Notes */}
              {selectedOrder.notes && (
                <div className="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-3">
                  <div className="flex items-start gap-2">
                    <i className="bi bi-sticky text-yellow-600 mt-0.5"></i>
                    <div>
                      <h4 className="font-semibold text-yellow-900 text-sm mb-1">Notes</h4>
                      <p className="text-yellow-800 text-sm">{selectedOrder.notes}</p>
                    </div>
                  </div>
                </div>
              )}
            </div>

            {/* Footer - Fixed */}
            <div className="flex-shrink-0 bg-gray-50 border-t border-gray-200 px-6 py-4 rounded-b-2xl flex gap-3">
              <button
                onClick={() => setSelectedOrder(null)}
                className="flex-1 px-4 py-2.5 border-2 border-gray-300 rounded-xl font-semibold hover:bg-gray-100 transition-colors text-gray-700"
              >
                <i className="bi bi-x-circle mr-2"></i>
                Close
              </button>
              <InvoiceButton 
                orderId={selectedOrder.order_number} 
                label="View Invoice"
                className="flex-1 bg-[#a97456] hover:bg-[#8b6043] text-white font-semibold py-2.5 px-4 rounded-xl transition-colors"
              />
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
