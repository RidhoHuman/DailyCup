"use client";

import Link from "next/link";
import { useState } from "react";
import Header from "../../components/Header";

interface Order {
  id: string;
  date: string;
  status: "pending" | "processing" | "shipped" | "delivered" | "cancelled";
  total: number;
  items: Array<{
    name: string;
    quantity: number;
    price: number;
  }>;
}

export default function OrdersPage() {
  const [filter, setFilter] = useState("all");
  const [showDemoBanner, setShowDemoBanner] = useState(true);

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

  // Mock orders data
  const [orders] = useState<Order[]>([
    {
      id: "ORD-99881",
      date: "2026-01-22",
      status: "processing",
      total: 130000,
      items: [
        { name: "Caramel Macchiato", quantity: 2, price: 45000 },
        { name: "Croissant Butter", quantity: 1, price: 25000 }
      ]
    },
    {
      id: "ORD-001",
      date: "2024-01-15",
      status: "delivered",
      total: 75000,
      items: [
        { name: "Espresso", quantity: 2, price: 25000 },
        { name: "Cappuccino", quantity: 1, price: 35000 }
      ]
    },
    {
      id: "ORD-002",
      date: "2024-01-10",
      status: "shipped",
      total: 45000,
      items: [
        { name: "Latte", quantity: 1, price: 38000 },
        { name: "Croissant", quantity: 1, price: 7000 }
      ]
    }
  ]);

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

  return (
    <div className="min-h-screen bg-[#f6efe9]">
      <Header />

      {/* Demo Banner */}
      {showDemoBanner && (
        <div className="bg-amber-100 border-l-4 border-amber-500 p-4 mx-4 mt-4 rounded-r-lg">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <i className="bi bi-info-circle text-amber-600 text-xl"></i>
            </div>
            <div className="ml-3">
              <p className="text-sm text-amber-800 font-medium">
                <strong>Mode Demo</strong> - Riwayat pesanan ini menggunakan data simulasi.
              </p>
              <p className="text-sm text-amber-700 mt-1">
                Sistem order tracking dan database akan diintegrasikan pada fase checkout & payment.
              </p>
            </div>
            <div className="ml-auto">
              <button 
                onClick={() => setShowDemoBanner(false)}
                className="text-amber-600 hover:text-amber-800 text-sm font-medium"
              >
                <i className="bi bi-x text-lg"></i>
              </button>
            </div>
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