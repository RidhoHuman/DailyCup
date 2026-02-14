"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api-client";
import { getErrorMessage } from '@/lib/utils';
import { useAuthStore } from "@/lib/stores/auth-store";
import { useRouter } from "next/navigation";

interface Order {
  id: number;
  order_number: string;
  customer_name: string;
  customer_phone: string;
  total: number;
  status: string;
  payment_method: string;
  created_at: string;
  courier_id?: number;
  courier_name?: string;
  estimated_delivery?: string;
}

interface Courier {
  id: number;
  name: string;
  phone: string;
  vehicle_type: string;
  is_available: boolean;
}

const STATUS_COLUMNS = [
  { key: 'pending_payment', label: 'Menunggu Pembayaran', color: 'bg-red-100 border-red-300' },
  { key: 'waiting_confirmation', label: 'Konfirmasi COD', color: 'bg-yellow-100 border-yellow-300' },
  { key: 'queueing', label: 'Antrian', color: 'bg-blue-100 border-blue-300' },
  { key: 'preparing', label: 'Diproses', color: 'bg-purple-100 border-purple-300' },
  { key: 'on_delivery', label: 'Dalam Perjalanan', color: 'bg-orange-100 border-orange-300' },
  { key: 'completed', label: 'Selesai', color: 'bg-green-100 border-green-300' },
  { key: 'cancelled', label: 'Dibatalkan', color: 'bg-gray-100 border-gray-300' },
];

export default function OrderKanbanPage() {
  const router = useRouter();
  const { user, isAuthenticated } = useAuthStore();
  const [orders, setOrders] = useState<Order[]>([]);
  const [couriers, setCouriers] = useState<Courier[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [showAssignModal, setShowAssignModal] = useState(false);

  useEffect(() => {
    if (!isAuthenticated || user?.role !== 'admin') {
      router.push('/admin');
      return;
    }

    fetchOrders();
    fetchCouriers();
    
    // Auto-refresh every 10 seconds
    const interval = setInterval(fetchOrders, 10000);
    return () => clearInterval(interval);
  }, [isAuthenticated, user, router]);

  const fetchOrders = async () => {
    try {
      const response = await api.get<{success: boolean; data: Order[]}>('/orders.php');
      if (response.success) {
        setOrders(response.data || []);
      }
    } catch (error) {
      console.error('Failed to fetch orders:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchCouriers = async () => {
    try {
      // TODO: Create courier API endpoint
      // For now, use dummy data
      setCouriers([
        { id: 1, name: 'Budi Santoso', phone: '08123456789', vehicle_type: 'motorcycle', is_available: true },
        { id: 2, name: 'Andi Wijaya', phone: '08198765432', vehicle_type: 'motorcycle', is_available: true },
        { id: 3, name: 'Siti Nurhaliza', phone: '08567891234', vehicle_type: 'car', is_available: true },
      ]);
    } catch (error) {
      console.error('Failed to fetch couriers:', error);
    }
  };

  const updateOrderStatus = async (orderId: string, newStatus: string) => {
    try {
      const response = await api.post<{success: boolean; message?: string}>('/orders/update_status.php', {
        order_id: orderId,
        status: newStatus,
        message: `Status updated to ${newStatus}`
      });

      if (response.success) {
        alert('Status updated successfully!');
        fetchOrders();
      }
    } catch (error: unknown) {
      alert(`Failed to update status: ${getErrorMessage(error) || 'Unknown error'}`);
    }
  };

  const assignCourier = async (courierId: number) => {
    if (!selectedOrder) return;

    try {
      const response = await api.post<{success: boolean; message?: string}>('/orders/assign_courier.php', {
        order_id: selectedOrder.order_number,
        courier_id: courierId
      });

      if (response.success) {
        alert('Courier assigned successfully!');
        setShowAssignModal(false);
        setSelectedOrder(null);
        fetchOrders();
      }
    } catch (error: unknown) {
      alert(`Failed to assign courier: ${getErrorMessage(error) || 'Unknown error'}`);
    }
  };

  const getOrdersByStatus = (status: string) => {
    return orders.filter(order => order.status === status);
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <i className="bi bi-arrow-repeat animate-spin text-4xl text-[#a97456]"></i>
          <p className="mt-4 text-gray-600">Loading orders...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-[1800px] mx-auto">
        {/* Header */}
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-800">Order Kanban Board</h1>
          <p className="text-gray-600">Real-time order tracking & management</p>
        </div>

        {/* Kanban Board */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4">
          {STATUS_COLUMNS.map(column => (
            <div key={column.key} className="flex flex-col">
              {/* Column Header */}
              <div className={`${column.color} border-2 rounded-t-lg p-3`}>
                <h3 className="font-semibold text-sm">{column.label}</h3>
                <span className="text-xs text-gray-600">
                  {getOrdersByStatus(column.key).length} orders
                </span>
              </div>

              {/* Column Content */}
              <div className="bg-white border-2 border-t-0 border-gray-200 rounded-b-lg p-2 min-h-[500px] max-h-[600px] overflow-y-auto">
                <div className="space-y-2">
                  {getOrdersByStatus(column.key).map(order => (
                    <div
                      key={order.id}
                      className="bg-white border border-gray-200 rounded-lg p-3 hover:shadow-md transition-shadow cursor-pointer"
                      onClick={() => router.push(`/admin/orders/${order.id}`)}
                    >
                      <div className="flex justify-between items-start mb-2">
                        <span className="text-xs font-mono text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">
                          #{order.order_number.slice(0, 8)}
                        </span>
                        <span className="text-xs text-gray-400">
                          {(() => {
                            const date = new Date(order.created_at);
                            return !isNaN(date.getTime()) 
                              ? date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) 
                              : '-';
                          })()}
                        </span>
                      </div>

                      <h4 className="font-medium text-sm text-gray-800 mb-1">
                        {order.customer_name}
                      </h4>

                      <p className="text-xs text-gray-500 mb-2">
                        <i className="bi bi-telephone mr-1"></i>
                        {order.customer_phone}
                      </p>

                      <div className="flex justify-between items-center">
                        <span className="text-sm font-bold text-[#a97456]">
                          {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(order.total)}
                        </span>
                        <span className={`text-[10px] px-2 py-0.5 rounded-full font-medium ${
                          order.payment_method === 'cod' 
                            ? 'bg-yellow-100 text-yellow-700'
                            : 'bg-green-100 text-green-700'
                        }`}>
                          {order.payment_method?.toUpperCase()}
                        </span>
                      </div>

                      {order.courier_name && (
                        <div className="mt-2 pt-2 border-t text-xs text-gray-600">
                          <i className="bi bi-person-badge mr-1"></i>
                          {order.courier_name}
                        </div>
                      )}

                      {/* Action Buttons */}
                      <div className="mt-3 pt-2 border-t border-gray-100 flex gap-2">
                        {column.key === 'preparing' && (
                          <button
                            onClick={(e) => {
                              e.stopPropagation();
                              setSelectedOrder(order);
                              setShowAssignModal(true);
                            }}
                            className="flex-1 text-xs bg-[#a97456] text-white py-1.5 rounded hover:bg-[#8f6249] transition-colors"
                          >
                            Assign Courier
                          </button>
                        )}

                        {column.key === 'queueing' && (
                          <button
                            onClick={(e) => {
                              e.stopPropagation();
                              updateOrderStatus(order.order_number, 'preparing');
                            }}
                            className="flex-1 text-xs bg-purple-600 text-white py-1.5 rounded hover:bg-purple-700 transition-colors"
                          >
                            Start Preparing
                          </button>
                        )}
                        
                        <button 
                          onClick={(e) => {
                             e.stopPropagation();
                             router.push(`/admin/orders/${order.id}`);
                          }}
                          className="px-2 py-1.5 text-xs text-gray-500 hover:text-gray-700 bg-gray-50 hover:bg-gray-200 rounded transition-colors"
                          title="View Details"
                        >
                          <i className="bi bi-eye"></i>
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Assign Courier Modal */}
      {showAssignModal && selectedOrder && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <h2 className="text-xl font-bold text-gray-800 mb-4">
              Assign Courier
            </h2>
            <p className="text-sm text-gray-600 mb-4">
              Order: #{selectedOrder.order_number}
            </p>

            <div className="space-y-2">
              {couriers.filter(c => c.is_available).map(courier => (
                <button
                  key={courier.id}
                  onClick={() => assignCourier(courier.id)}
                  className="w-full text-left p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="font-medium text-gray-800">{courier.name}</p>
                      <p className="text-xs text-gray-500">{courier.phone}</p>
                    </div>
                    <div className="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded">
                      {courier.vehicle_type}
                    </div>
                  </div>
                </button>
              ))}
            </div>

            <button
              onClick={() => {
                setShowAssignModal(false);
                setSelectedOrder(null);
              }}
              className="mt-4 w-full py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Cancel
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
