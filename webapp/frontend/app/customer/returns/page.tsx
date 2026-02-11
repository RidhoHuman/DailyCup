"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api-client";
import Link from "next/link";
import Image from "next/image";

interface Order {
  id: string;
  order_number: string;
  total: number;
  status: string;
  created_at: string;
}

interface OrderItem {
  id: string;
  product_id: string;
  product_name: string;
  variant: string | null;
  quantity: number;
  price: number;
  image: string | null;
}

interface ReturnItem {
  product_name: string;
  variant?: string;
  quantity: number;
  price: number;
}

interface ProductReturn {
  id: string;
  return_number: string;
  order_number: string;
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
}

export default function CustomerReturnsPage() {
  const [returns, setReturns] = useState<ProductReturn[]>([]);
  const [orders, setOrders] = useState<Order[]>([]);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [orderItems, setOrderItems] = useState<OrderItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // Form states
  const [selectedItems, setSelectedItems] = useState<{[key: string]: {selected: boolean; quantity: number}}>({});
  const [reason, setReason] = useState('defective');
  const [description, setDescription] = useState('');
  const [refundMethod, setRefundMethod] = useState('original_payment');

  useEffect(() => {
    fetchReturns();
    fetchOrders();
  }, []);

  const fetchReturns = async () => {
    try {
      setLoading(true);
      const response = await api.get<{success: boolean; returns: ProductReturn[]}>('/returns.php', { requiresAuth: true });
      if (response.success) {
        setReturns(response.returns);
      }
    } catch (error) {
      console.error('Failed to fetch returns:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchOrders = async () => {
    try {
      // Fetch completed/delivered orders only
      const response = await api.get<{success: boolean; orders: Order[]}>('/orders.php?status=completed', { requiresAuth: true });
      if (response.success) {
        setOrders(response.orders);
      }
    } catch (error) {
      console.error('Failed to fetch orders:', error);
    }
  };

  const handleSelectOrder = async (order: Order) => {
    setSelectedOrder(order);
    
    // Fetch order items
    try {
      const response = await api.get<{success: boolean; items: OrderItem[]}>(`/orders.php?id=${order.id}&include_items=true`, { requiresAuth: true });
      if (response.success) {
        setOrderItems(response.items || []);
        // Initialize selection state
        const initialSelection: {[key: string]: {selected: boolean; quantity: number}} = {};
        response.items?.forEach((item: OrderItem) => {
          initialSelection[item.id] = { selected: false, quantity: 1 };
        });
        setSelectedItems(initialSelection);
      }
    } catch (error) {
      console.error('Failed to fetch order items:', error);
    }
  };

  const handleToggleItem = (itemId: string, maxQty: number) => {
    setSelectedItems(prev => ({
      ...prev,
      [itemId]: {
        selected: !prev[itemId]?.selected,
        quantity: prev[itemId]?.quantity || 1
      }
    }));
  };

  const handleQuantityChange = (itemId: string, qty: number, maxQty: number) => {
    if (qty < 1 || qty > maxQty) return;
    
    setSelectedItems(prev => ({
      ...prev,
      [itemId]: {
        ...prev[itemId],
        quantity: qty
      }
    }));
  };

  const handleSubmitReturn = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!selectedOrder) return;

    // Get selected items
    const itemsToReturn = orderItems
      .filter(item => selectedItems[item.id]?.selected)
      .map(item => ({
        product_name: item.product_name,
        variant: item.variant,
        quantity: selectedItems[item.id].quantity,
        price: item.price
      }));

    if (itemsToReturn.length === 0) {
      alert('Please select at least one item to return');
      return;
    }

    if (!description.trim()) {
      alert('Please provide a detailed description');
      return;
    }

    try {
      setSubmitting(true);
      const response = await api.post<{success: boolean; return_number: string}>('/returns.php', {
        order_id: selectedOrder.id,
        items: itemsToReturn,
        reason,
        description,
        refund_method: refundMethod
      }, { requiresAuth: true });

      if (response.success) {
        alert(`Return request submitted successfully!\nReturn Number: ${response.return_number}`);
        setShowCreateModal(false);
        setSelectedOrder(null);
        setOrderItems([]);
        setSelectedItems({});
        setReason('defective');
        setDescription('');
        setRefundMethod('original_payment');
        await fetchReturns();
      }
    } catch (error: any) {
      console.error('Failed to submit return:', error);
      alert(error.response?.data?.message || 'Failed to submit return request');
    } finally {
      setSubmitting(false);
    }
  };

  const handleCancelReturn = async (returnId: string) => {
    if (!confirm('Cancel this return request?')) return;

    try {
      const response = await api.delete<{success: boolean}>(`/returns.php?id=${returnId}`, { requiresAuth: true });
      if (response.success) {
        alert('Return request cancelled');
        await fetchReturns();
      }
    } catch (error) {
      console.error('Failed to cancel return:', error);
      alert('Failed to cancel return');
    }
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

  const calculateRefundAmount = () => {
    return orderItems
      .filter(item => selectedItems[item.id]?.selected)
      .reduce((sum, item) => sum + (item.price * selectedItems[item.id].quantity), 0);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <i className="bi bi-arrow-repeat animate-spin text-4xl text-[#a97456] mb-4"></i>
          <p className="text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8 max-w-6xl">
      <div className="mb-8 flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-800 mb-2">Product Returns</h1>
          <p className="text-gray-500">Manage your return requests</p>
        </div>
        <button
          onClick={() => setShowCreateModal(true)}
          className="px-6 py-3 bg-[#a97456] text-white rounded-lg hover:bg-[#8b5e3c] transition-colors font-medium"
        >
          <i className="bi bi-plus-lg mr-2"></i>
          Request Return
        </button>
      </div>

      {/* Return Requests */}
      {returns.length === 0 ? (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
          <i className="bi bi-arrow-return-left text-6xl text-gray-300 mb-4"></i>
          <h3 className="text-xl font-semibold text-gray-800 mb-2">No Return Requests</h3>
          <p className="text-gray-500 mb-6">You haven't requested any returns yet</p>
          <button
            onClick={() => setShowCreateModal(true)}
            className="px-6 py-3 bg-[#a97456] text-white rounded-lg hover:bg-[#8b5e3c] transition-colors font-medium"
          >
            Request Your First Return
          </button>
        </div>
      ) : (
        <div className="space-y-4">
          {returns.map((returnItem) => (
            <div key={returnItem.id} className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
              <div className="p-6">
                <div className="flex items-start justify-between mb-4">
                  <div>
                    <div className="flex items-center gap-3 mb-2">
                      <span className="font-mono font-semibold text-gray-800">{returnItem.return_number}</span>
                      <span className={`px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(returnItem.status)}`}>
                        {returnItem.status}
                      </span>
                    </div>
                    <p className="text-sm text-gray-500">
                      Order: <Link href={`/track/${returnItem.order_number}`} className="text-[#a97456] hover:underline font-mono">{returnItem.order_number}</Link>
                    </p>
                    <p className="text-sm text-gray-500">
                      Created: {new Date(returnItem.created_at).toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                      })}
                    </p>
                  </div>
                  {returnItem.status === 'pending' && (
                    <button
                      onClick={() => handleCancelReturn(returnItem.id)}
                      className="text-red-600 hover:text-red-700 text-sm font-medium"
                    >
                      Cancel Request
                    </button>
                  )}
                </div>

                {/* Items */}
                <div className="bg-gray-50 rounded-lg p-4 mb-4">
                  <h4 className="font-semibold text-gray-800 mb-3">Returned Items</h4>
                  <div className="space-y-2">
                    {returnItem.items.map((item, idx) => (
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
                  </div>
                  <div className="border-t border-gray-200 pt-3 mt-3">
                    <div className="flex justify-between items-center">
                      <p className="font-semibold text-gray-800">Refund Amount</p>
                      <p className="text-xl font-bold text-[#a97456]">
                        Rp {returnItem.refund_amount.toLocaleString('id-ID')}
                      </p>
                    </div>
                  </div>
                </div>

                {/* Reason & Description */}
                <div className="mb-4">
                  <p className="text-sm font-medium text-gray-700 mb-1 capitalize">
                    Reason: {returnItem.reason.replace('_', ' ')}
                  </p>
                  <p className="text-gray-700">{returnItem.description}</p>
                </div>

                {/* Admin Response */}
                {returnItem.admin_notes && (
                  <div className="bg-blue-50 rounded-lg p-4 mb-4">
                    <p className="font-medium text-blue-900 mb-1">Admin Response:</p>
                    <p className="text-blue-800">{returnItem.admin_notes}</p>
                  </div>
                )}

                {/* Rejection */}
                {returnItem.rejection_reason && (
                  <div className="bg-red-50 rounded-lg p-4">
                    <p className="font-medium text-red-900 mb-1">Rejection Reason:</p>
                    <p className="text-red-800">{returnItem.rejection_reason}</p>
                  </div>
                )}

                {/* Success Message */}
                {returnItem.status === 'refunded' && (
                  <div className="bg-green-50 rounded-lg p-4">
                    <div className="flex items-start gap-3">
                      <i className="bi bi-check-circle text-2xl text-green-600"></i>
                      <div>
                        <p className="font-medium text-green-900">Refund Processed!</p>
                        <p className="text-sm text-green-800">
                          Your refund of Rp {returnItem.refund_amount.toLocaleString('id-ID')} has been processed via {returnItem.refund_method.replace('_', ' ')}.
                        </p>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Create Return Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 overflow-y-auto">
          <div className="bg-white rounded-2xl shadow-xl max-w-3xl w-full my-8">
            <div className="p-6 border-b border-gray-100">
              <div className="flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-800">Request Product Return</h2>
                <button
                  onClick={() => {
                    setShowCreateModal(false);
                    setSelectedOrder(null);
                    setOrderItems([]);
                  }}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <i className="bi bi-x-lg text-xl"></i>
                </button>
              </div>
            </div>

            {!selectedOrder ? (
              // Order Selection
              <div className="p-6">
                <h3 className="font-semibold text-gray-800 mb-4">Select Order to Return</h3>
                {orders.length === 0 ? (
                  <div className="text-center py-8 text-gray-500">
                    <p>No completed orders available for return</p>
                  </div>
                ) : (
                  <div className="space-y-3 max-h-96 overflow-y-auto">
                    {orders.map((order) => (
                      <button
                        key={order.id}
                        onClick={() => handleSelectOrder(order)}
                        className="w-full p-4 border border-gray-200 rounded-lg hover:border-[#a97456] hover:bg-gray-50 transition-colors text-left"
                      >
                        <div className="flex justify-between items-start">
                          <div>
                            <p className="font-mono font-semibold text-gray-800">{order.order_number}</p>
                            <p className="text-sm text-gray-500">
                              {new Date(order.created_at).toLocaleDateString('id-ID')}
                            </p>
                          </div>
                          <p className="font-semibold text-[#a97456]">
                            Rp {order.total.toLocaleString('id-ID')}
                          </p>
                        </div>
                      </button>
                    ))}
                  </div>
                )}
              </div>
            ) : (
              // Return Form
              <form onSubmit={handleSubmitReturn} className="p-6 space-y-6 max-h-[calc(90vh-200px)] overflow-y-auto">
                <div>
                  <h3 className="font-semibold text-gray-800 mb-3">Select Items to Return</h3>
                  <div className="space-y-3">
                    {orderItems.map((item) => (
                      <div
                        key={item.id}
                        className={`p-4 border rounded-lg cursor-pointer transition-colors ${
                          selectedItems[item.id]?.selected
                            ? 'border-[#a97456] bg-amber-50'
                            : 'border-gray-200 hover:border-gray-300'
                        }`}
                        onClick={() => handleToggleItem(item.id, item.quantity)}
                      >
                        <div className="flex items-start gap-4">
                          <input
                            type="checkbox"
                            checked={selectedItems[item.id]?.selected || false}
                            onChange={() => handleToggleItem(item.id, item.quantity)}
                            className="mt-1"
                          />
                          <div className="flex-1">
                            <p className="font-medium text-gray-800">{item.product_name}</p>
                            {item.variant && <p className="text-sm text-gray-500">{item.variant}</p>}
                            <p className="text-sm text-gray-600">
                              Price: Rp {item.price.toLocaleString('id-ID')} × {item.quantity}
                            </p>
                            {selectedItems[item.id]?.selected && (
                              <div className="mt-2 flex items-center gap-3">
                                <label className="text-sm font-medium text-gray-700">Quantity to return:</label>
                                <input
                                  type="number"
                                  min="1"
                                  max={item.quantity}
                                  value={selectedItems[item.id].quantity}
                                  onChange={(e) => handleQuantityChange(item.id, parseInt(e.target.value), item.quantity)}
                                  onClick={(e) => e.stopPropagation()}
                                  className="w-20 px-3 py-1 border border-gray-300 rounded-lg"
                                />
                                <span className="text-sm text-gray-500">of {item.quantity}</span>
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Return Reason <span className="text-red-500">*</span>
                  </label>
                  <select
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                    required
                  >
                    <option value="defective">Defective Product</option>
                    <option value="wrong_item">Wrong Item Received</option>
                    <option value="not_as_described">Not as Described</option>
                    <option value="damaged">Damaged During Shipping</option>
                    <option value="quality_issue">Quality Issue</option>
                    <option value="changed_mind">Changed Mind</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Detailed Description <span className="text-red-500">*</span>
                  </label>
                  <textarea
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                    placeholder="Please describe the issue in detail..."
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent resize-none"
                    rows={4}
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Refund Method
                  </label>
                  <select
                    value={refundMethod}
                    onChange={(e) => setRefundMethod(e.target.value)}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  >
                    <option value="original_payment">Original Payment Method</option>
                    <option value="store_credit">Store Credit</option>
                    <option value="bank_transfer">Bank Transfer</option>
                  </select>
                </div>

                {/* Refund Summary */}
                {Object.values(selectedItems).some(item => item.selected) && (
                  <div className="bg-amber-50 rounded-lg p-4">
                    <div className="flex justify-between items-center">
                      <span className="font-semibold text-gray-800">Estimated Refund Amount:</span>
                      <span className="text-2xl font-bold text-[#a97456]">
                        Rp {calculateRefundAmount().toLocaleString('id-ID')}
                      </span>
                    </div>
                  </div>
                )}

                <div className="flex gap-3 pt-4">
                  <button
                    type="button"
                    onClick={() => {
                      setSelectedOrder(null);
                      setOrderItems([]);
                    }}
                    className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                  >
                    Back
                  </button>
                  <button
                    type="submit"
                    disabled={submitting || !Object.values(selectedItems).some(item => item.selected)}
                    className="flex-1 px-6 py-3 bg-[#a97456] text-white rounded-lg hover:bg-[#8b5e3c] disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                  >
                    {submitting ? (
                      <>
                        <i className="bi bi-arrow-repeat animate-spin mr-2"></i>
                        Submitting...
                      </>
                    ) : (
                      <>
                        <i className="bi bi-send mr-2"></i>
                        Submit Return Request
                      </>
                    )}
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
