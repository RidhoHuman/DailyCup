"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { api } from "@/lib/api-client";

interface Order {
  id: string;
  order_number: string;
  total: number;
  status: string;
  items: number;
  date: string;
  payment_method: string;
}

interface Customer {
  id: number;
  name: string;
  email: string;
}

export default function CustomerOrderHistoryPage() {
  const params = useParams();
  const customerId = params.id as string;
  
  const [orders, setOrders] = useState<Order[]>([]);
  const [customer, setCustomer] = useState<Customer | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchCustomerOrders();
  }, [customerId]);

  const fetchCustomerOrders = async () => {
    try {
      setLoading(true);
      // Fetch customer's orders
      const response = await api.get<{ success: boolean; data: Order[] }>(
        `/orders.php?customer_id=${customerId}`
      );
      
      if (response.success) {
        setOrders(response.data || []);
      }
      
      // Mock customer data for now
      setCustomer({
        id: parseInt(customerId),
        name: "Customer " + customerId,
        email: "customer@example.com"
      });
    } catch (error) {
      console.error('Error fetching orders:', error);
      // Use mock data if API fails
      setOrders([
        { id: 'ORD-001', order_number: 'ORD-001', total: 85000, status: 'paid', items: 3, date: '2026-01-27', payment_method: 'QRIS' },
        { id: 'ORD-002', order_number: 'ORD-002', total: 125000, status: 'paid', items: 5, date: '2026-01-25', payment_method: 'Bank Transfer' },
        { id: 'ORD-003', order_number: 'ORD-003', total: 45000, status: 'pending', items: 2, date: '2026-01-20', payment_method: 'QRIS' },
      ]);
      setCustomer({
        id: parseInt(customerId),
        name: "Customer " + customerId,
        email: "customer@example.com"
      });
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      paid: "bg-green-100 text-green-700",
      pending: "bg-yellow-100 text-yellow-700",
      failed: "bg-red-100 text-red-700",
      refunded: "bg-gray-100 text-gray-700",
    };
    return colors[status] || "bg-gray-100 text-gray-700";
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
    }).format(amount);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-gray-500">Loading order history...</div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <Link 
          href="/admin/customers"
          className="text-[#a97456] hover:text-[#8f6249] flex items-center gap-2 mb-4"
        >
          <i className="bi bi-arrow-left"></i> Back to Customers
        </Link>
        <h1 className="text-2xl font-bold text-gray-800">Order History</h1>
        {customer && (
          <p className="text-gray-500">
            Orders from {customer.name} ({customer.email})
          </p>
        )}
      </div>

      {/* Summary Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="text-sm text-gray-500 mb-1">Total Orders</div>
          <div className="text-3xl font-bold text-gray-800">{orders.length}</div>
        </div>
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="text-sm text-gray-500 mb-1">Total Spent</div>
          <div className="text-3xl font-bold text-gray-800">
            {formatCurrency(orders.reduce((sum, o) => sum + (Number(o.total) || 0), 0))}
          </div>
        </div>
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="text-sm text-gray-500 mb-1">Completed Orders</div>
          <div className="text-3xl font-bold text-gray-800">
            {orders.filter(o => o.status === 'paid').length}
          </div>
        </div>
      </div>

      {/* Orders Table */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {orders.length === 0 ? (
          <div className="p-12 text-center text-gray-500">
            <i className="bi bi-bag-x text-4xl text-gray-300 mb-4"></i>
            <p>No orders found for this customer</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr className="text-left text-xs font-semibold text-gray-500 uppercase">
                  <th className="px-6 py-4">Order ID</th>
                  <th className="px-6 py-4">Items</th>
                  <th className="px-6 py-4">Total</th>
                  <th className="px-6 py-4">Payment Method</th>
                  <th className="px-6 py-4">Status</th>
                  <th className="px-6 py-4">Date</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {orders.map((order) => (
                  <tr key={order.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4 font-mono text-sm text-gray-600">
                      {order.order_number || order.id}
                    </td>
                    <td className="px-6 py-4 text-gray-600">{order.items} items</td>
                    <td className="px-6 py-4 font-semibold text-gray-800">
                      {formatCurrency(order.total)}
                    </td>
                    <td className="px-6 py-4 text-gray-600">{order.payment_method}</td>
                    <td className="px-6 py-4">
                      <span className={`px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(order.status)}`}>
                        {order.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      {new Date(order.date).toLocaleDateString("id-ID")}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
