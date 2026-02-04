"use client";

import Link from "next/link";
import { useState, useEffect } from "react";
import Header from "../../components/Header";
import { useAuthStore } from "@/lib/stores/auth-store";
import api from "@/lib/api-client";

interface Order {
  id: string;
  order_id?: string;
  date: string;
  created_at?: string;
  status: "pending" | "processing" | "shipped" | "delivered" | "cancelled" | "paid" | "failed";
  total: number;
  total_amount?: number;
  items: Array<{
    name: string;
    product_name?: string;
    quantity: number;
    price: number;
  }>;
}

export default function OrdersPage() {
  const { isAuthenticated } = useAuthStore();
  const [filter, setFilter] = useState("all");
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchOrders = async () => {
      if (!isAuthenticated) {
        setLoading(false);
        return;
      }

      try {
        const response = await api.get<{ data: { orders: any[] } }>('/orders/user_orders.php', { requiresAuth: true });
        
        if (response.data && response.data.orders) {
          // Transform API response to match our interface
          const transformedOrders: Order[] = response.data.orders.map((order: any) => ({
            id: order.order_id || order.id,
            date: order.created_at || order.date,
            status: order.status,
            total: parseFloat(order.total_amount || order.total || 0),
            items: order.items || []
          }));
          
          setOrders(transformedOrders);
        }
      } catch (err: any) {
        console.error('Failed to fetch orders:', err);
        setError(err.message || 'Failed to load orders');
      } finally {
        setLoading(false);
      }
    };

    fetchOrders();
  }, [isAuthenticated]);

  const handleReorder = (orderId: string) => {
    // TODO: Implement reorder functionality
    alert(`Reordering items from order ${orderId}. This will add items back to cart.`);
    console.log("Reorder order:", orderId);
  };

  const handleCancelOrder = (orderId: string) => {
    if (confirm(`Are you sure you want to cancel order ${orderId}?`)) {
      // TODO: Implement cancel order API call
      alert(`Order ${orderId} has been cancelled.`);
      console.log("Cancel order:", orderId);
    }
  };

  // Mock orders data - REMOVED, now using real API
  const getStatusColor = (status: Order["status"]) => {
    switch (status) {
      case "pending": return "bg-yellow-100 text-yellow-800";
      case "processing": return "bg-blue-100 text-blue-800";
      case "shipped": return "bg-purple-100 text-purple-800";
      case "delivered": return "bg-green-100 text-green-800";
      case "cancelled": return "bg-red-100 text-red-800";
      default: return "bg-gray-100 text-gray-800";
    }
  };

  const filteredOrders = orders.filter(order => {
    if (filter === "all") return true;
    return order.status === filter;
  });

  // Loading state
  if (loading) {
    return (
      <div className="min-h-screen bg-[#f6efe9]">
        <Header />
        <div className="max-w-6xl mx-auto px-4 py-8">
          <div className="animate-pulse">
            <div className="h-8 bg-gray-200 rounded w-1/4 mb-6"></div>
            <div className="space-y-4">
              {[...Array(3)].map((_, i) => (
                <div key={i} className="bg-white rounded-lg p-6 h-32"></div>
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Not authenticated
  if (!isAuthenticated) {
    return (
      <div className="min-h-screen bg-[#f6efe9]">
        <Header />
        <div className="max-w-6xl mx-auto px-4 py-8 text-center">
          <i className="bi bi-lock text-6xl text-gray-400 mb-4"></i>
          <h2 className="text-2xl font-bold mb-2">Login Required</h2>
          <p className="text-gray-600 mb-6">Please login to view your order history</p>
          <Link href="/login" className="bg-[#a97456] text-white px-6 py-3 rounded-lg hover:bg-[#8a5a3d]">
            Login Now
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#f6efe9]">
      <Header />

      {/* Error Banner */}
      {error && (
        <div className="bg-red-100 border-l-4 border-red-500 p-4 mx-4 mt-4 rounded-r-lg">
          <div className="flex items-center">
            <i className="bi bi-exclamation-triangle text-red-600 text-xl mr-3"></i>
            <p className="text-sm text-red-800">{error}</p>
          </div>
        </div>
      )}

      <div className="max-w-6xl mx-auto px-4 py-8">
        <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
          {/* Header */}
          <div className="bg-[#a97456] text-white p-6">
            <h1 className="text-3xl font-bold">Order History</h1>
            <p className="text-amber-100 mt-2">Track and manage your orders</p>
          </div>

          {/* Filters */}
          <div className="p-6 border-b">
            <div className="flex flex-wrap gap-2">
              <button
                onClick={() => setFilter("all")}
                className={`px-4 py-2 rounded-lg font-medium ${
                  filter === "all"
                    ? "bg-[#a97456] text-white"
                    : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                }`}
              >
                All Orders
              </button>
              <button
                onClick={() => setFilter("pending")}
                className={`px-4 py-2 rounded-lg font-medium ${
                  filter === "pending"
                    ? "bg-[#a97456] text-white"
                    : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                }`}
              >
                Pending
              </button>
              <button
                onClick={() => setFilter("processing")}
                className={`px-4 py-2 rounded-lg font-medium ${
                  filter === "processing"
                    ? "bg-[#a97456] text-white"
                    : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                }`}
              >
                Processing
              </button>
              <button
                onClick={() => setFilter("shipped")}
                className={`px-4 py-2 rounded-lg font-medium ${
                  filter === "shipped"
                    ? "bg-[#a97456] text-white"
                    : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                }`}
              >
                Shipped
              </button>
              <button
                onClick={() => setFilter("delivered")}
                className={`px-4 py-2 rounded-lg font-medium ${
                  filter === "delivered"
                    ? "bg-[#a97456] text-white"
                    : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                }`}
              >
                Delivered
              </button>
            </div>
          </div>

          {/* Orders List */}
          <div className="p-6">
            {filteredOrders.length === 0 ? (
              <div className="text-center py-12">
                <i className="bi bi-receipt text-6xl text-gray-300"></i>
                <h3 className="text-xl font-semibold text-gray-800 mt-4">No orders found</h3>
                <p className="text-gray-500 mt-2">Try adjusting your filter or place your first order</p>
              </div>
            ) : (
              <div className="space-y-4">
                {filteredOrders.map((order) => (
                  <div key={order.id} className="border rounded-lg p-6 hover:shadow-md transition-shadow">
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between">
                      <div className="flex-1">
                        <div className="flex items-center space-x-4 mb-2">
                          <h3 className="text-lg font-semibold text-gray-800">Order #{order.id}</h3>
                          <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(order.status)}`}>
                            {order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                          </span>
                        </div>
                        <p className="text-gray-600 mb-2">{new Date(order.date).toLocaleDateString()}</p>
                        <div className="space-y-1">
                          {order.items.map((item, index) => (
                            <p key={index} className="text-sm text-gray-600">
                              {item.quantity}x {item.name} - Rp {item.price.toLocaleString()}
                            </p>
                          ))}
                        </div>
                      </div>
                      <div className="mt-4 md:mt-0 md:text-right">
                        <p className="text-xl font-bold text-[#a97456] mb-2">
                          Rp {order.total.toLocaleString()}
                        </p>
                        <div className="flex flex-col space-y-2">
                          <Link 
                            href={`/orders/${order.id}`}
                            className="px-4 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8a5a3d] text-sm text-center font-medium shadow-sm hover:shadow"
                          >
                             Track Order & Details
                          </Link>
                          <div className="flex space-x-2">
                            {(order.status === "delivered" || order.status === "shipped") && (
                              <button 
                                onClick={() => handleReorder(order.id)}
                                className="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700"
                              >
                                Reorder
                              </button>
                            )}
                            {(order.status === "pending" || order.status === "processing") && (
                              <button 
                                onClick={() => handleCancelOrder(order.id)}
                                className="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700"
                              >
                                Cancel
                              </button>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}