"use client";

import { useState, useEffect, useRef } from "react";
import { api } from "@/lib/api-client";

// Configuration
const TAX_PERCENTAGE = 0.02; // 11% tax (ubah nilai ini untuk mengatur persentase pajak, misal: 0.02 untuk 2%)

interface Order {
  id: string;
  customer: string;
  email: string;
  total: number;
  status: string;
  items: number;
  date: string;
}

export default function AdminOrdersPage() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState("all");
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const printRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    fetchOrders();
  }, []);

  const fetchOrders = async () => {
    try {
      setLoading(true);
      const response = await api.get<{ success: boolean; data: Order[] }>(
        "/get_recent_orders.php?limit=100"
      );
      if (response.success) {
        setOrders(response.data);
      }
    } catch (error) {
      console.error("Error fetching orders:", error);
    } finally {
      setLoading(false);
    }
  };

  const filteredOrders = orders.filter((order) => {
    if (filter === "all") return true;
    return order.status === filter;
  });

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

  const handlePrintReceipt = () => {
    if (!selectedOrder) return;
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    if (!printWindow) {
      alert('Pop-up blocker aktif! Harap izinkan pop-up untuk mencetak.');
      return;
    }

    const receiptHTML = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>Receipt - ${selectedOrder.id}</title>
        <style>
          @media print {
            @page {
              size: A4;
              margin: 20mm;
            }
            body {
              margin: 0;
            }
            .no-print {
              display: none !important;
            }
          }
          
          body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
          }
          
          .receipt-header {
            text-align: center;
            border-bottom: 2px solid #a97456;
            padding-bottom: 20px;
            margin-bottom: 30px;
          }
          
          .receipt-header h1 {
            margin: 0;
            color: #a97456;
            font-size: 32px;
          }
          
          .receipt-header p {
            margin: 5px 0;
            color: #666;
          }
          
          .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
          }
          
          .info-section h3 {
            margin: 0 0 10px 0;
            color: #a97456;
            font-size: 14px;
            text-transform: uppercase;
          }
          
          .info-section p {
            margin: 5px 0;
            font-size: 14px;
          }
          
          .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
          }
          
          .receipt-table th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
          }
          
          .receipt-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
          }
          
          .receipt-total {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #a97456;
          }
          
          .receipt-total .total-row {
            display: flex;
            justify-content: flex-end;
            gap: 40px;
            margin: 10px 0;
          }
          
          .receipt-total .grand-total {
            font-size: 24px;
            font-weight: bold;
            color: #a97456;
          }
          
          .receipt-footer {
            margin-top: 40px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
            font-size: 12px;
            color: #666;
          }
          
          .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
          }
          
          .status-paid { background-color: #d4edda; color: #155724; }
          .status-pending { background-color: #fff3cd; color: #856404; }
          .status-failed { background-color: #f8d7da; color: #721c24; }
          
          .print-button {
            background-color: #a97456;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin: 20px 0;
          }
          
          .print-button:hover {
            background-color: #8f6249;
          }
        </style>
      </head>
      <body>
        <div class="receipt-header">
          <h1>DailyCup Coffee</h1>
          <p>Jl. Kopi Nikmat No. 123, Jakarta</p>
          <p>Telp: (021) 1234-5678 | Email: info@dailycup.com</p>
        </div>
        
        <div class="receipt-info">
          <div class="info-section">
            <h3>Order Information</h3>
            <p><strong>Order ID:</strong> ${selectedOrder.id}</p>
            <p><strong>Date:</strong> ${new Date(selectedOrder.date).toLocaleString('id-ID', {
              dateStyle: 'full',
              timeStyle: 'short'
            })}</p>
            <p><strong>Status:</strong> <span class="status-badge status-${selectedOrder.status}">${selectedOrder.status}</span></p>
          </div>
          
          <div class="info-section">
            <h3>Customer Details</h3>
            <p><strong>Name:</strong> ${selectedOrder.customer}</p>
            <p><strong>Email:</strong> ${selectedOrder.email}</p>
          </div>
        </div>
        
        <table class="receipt-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Quantity</th>
              <th>Price</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Order Items</td>
              <td>${selectedOrder.items}</td>
              <td>${formatCurrency(selectedOrder.total / selectedOrder.items)}</td>
              <td>${formatCurrency(selectedOrder.total)}</td>
            </tr>
          </tbody>
        </table>
        
        <div class="receipt-total">
          <div class="total-row">
            <span>Subtotal:</span>
            <span>${formatCurrency(selectedOrder.total)}</span>
          </div>
          <div class="total-row">
            <span>Tax (${(TAX_PERCENTAGE * 100).toFixed(0)}%):</span>
            <span>${formatCurrency(selectedOrder.total * TAX_PERCENTAGE)}</span>
          </div>
          <div class="total-row grand-total">
            <span>Grand Total:</span>
            <span>${formatCurrency(selectedOrder.total * (1 + TAX_PERCENTAGE))}</span>
          </div>
        </div>
        
        <div class="receipt-footer">
          <p>Terima kasih atas pesanan Anda!</p>
          <p>Untuk pertanyaan, hubungi customer service kami</p>
          <p style="margin-top: 20px;">Dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
        </div>
        
        <div class="no-print" style="text-align: center;">
          <button class="print-button" onclick="window.print()">🖨️ Print Receipt</button>
          <button class="print-button" onclick="window.close()" style="background-color: #6c757d;">Close</button>
        </div>
      </body>
      </html>
    `;

    printWindow.document.write(receiptHTML);
    printWindow.document.close();
    
    // Auto print after load
    printWindow.onload = () => {
      setTimeout(() => {
        printWindow.print();
      }, 250);
    };
  };

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Orders Management</h1>
        <p className="text-gray-500">View and manage all customer orders</p>
      </div>

      {/* Filter Tabs */}
      <div className="flex gap-2 mb-6">
        {["all", "paid", "pending", "failed"].map((status) => (
          <button
            key={status}
            onClick={() => setFilter(status)}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              filter === status
                ? "bg-[#a97456] text-white"
                : "bg-white text-gray-600 hover:bg-gray-50 border border-gray-200"
            }`}
          >
            {status.charAt(0).toUpperCase() + status.slice(1)}
          </button>
        ))}
      </div>

      {/* Orders Table */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="p-12 text-center text-gray-500">Loading orders...</div>
        ) : filteredOrders.length === 0 ? (
          <div className="p-12 text-center text-gray-500">No orders found</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr className="text-left text-xs font-semibold text-gray-500 uppercase">
                  <th className="px-6 py-4">Order ID</th>
                  <th className="px-6 py-4">Customer</th>
                  <th className="px-6 py-4">Items</th>
                  <th className="px-6 py-4">Total</th>
                  <th className="px-6 py-4">Status</th>
                  <th className="px-6 py-4">Date</th>
                  <th className="px-6 py-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {filteredOrders.map((order) => (
                  <tr key={order.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4 font-mono text-sm text-gray-600">
                      {order.id}
                    </td>
                    <td className="px-6 py-4">
                      <div>
                        <div className="font-medium text-gray-800">
                          {order.customer}
                        </div>
                        <div className="text-xs text-gray-500">{order.email}</div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-gray-600">{order.items} items</td>
                    <td className="px-6 py-4 font-semibold text-gray-800">
                      {formatCurrency(order.total)}
                    </td>
                    <td className="px-6 py-4">
                      <span
                        className={`px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(
                          order.status
                        )}`}
                      >
                        {order.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      {new Date(order.date).toLocaleDateString("id-ID")}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <button 
                        onClick={() => setSelectedOrder(order)}
                        className="text-[#a97456] hover:text-[#8f6249] font-medium text-sm flex items-center gap-1 ml-auto"
                      >
                        <i className="bi bi-eye"></i>
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

      {/* Order Details Modal */}
      {selectedOrder && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <div className="flex justify-between items-start mb-6">
              <div>
                <h2 className="text-2xl font-bold text-gray-800">Order Details</h2>
                <p className="text-sm text-gray-500 mt-1">Order #{selectedOrder.id}</p>
              </div>
              <button
                onClick={() => setSelectedOrder(null)}
                className="text-gray-400 hover:text-gray-600 text-2xl"
              >
                <i className="bi bi-x-lg"></i>
              </button>
            </div>

            <div className="space-y-4">
              <div className="bg-gray-50 rounded-lg p-4">
                <h3 className="font-semibold text-gray-800 mb-3">Customer Information</h3>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-gray-600">Name:</span>
                    <span className="font-medium text-gray-800">{selectedOrder.customer}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Email:</span>
                    <span className="font-medium text-gray-800">{selectedOrder.email}</span>
                  </div>
                </div>
              </div>

              <div className="bg-gray-50 rounded-lg p-4">
                <h3 className="font-semibold text-gray-800 mb-3">Order Summary</h3>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-gray-600">Items:</span>
                    <span className="font-medium text-gray-800">{selectedOrder.items} items</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Total Amount:</span>
                    <span className="font-bold text-gray-800 text-lg">{formatCurrency(selectedOrder.total)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Status:</span>
                    <span className={`px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(selectedOrder.status)}`}>
                      {selectedOrder.status}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Date:</span>
                    <span className="font-medium text-gray-800">
                      {new Date(selectedOrder.date).toLocaleString("id-ID")}
                    </span>
                  </div>
                </div>
              </div>

              <div className="flex gap-3 pt-4">
                <button
                  onClick={() => setSelectedOrder(null)}
                  className="flex-1 px-4 py-2 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                >
                  Close
                </button>
                <button
                  onClick={handlePrintReceipt}
                  className="flex-1 px-4 py-2 bg-[#a97456] text-white rounded-lg font-medium hover:bg-[#8f6249] transition-colors flex items-center justify-center gap-2"
                >
                  <i className="bi bi-printer"></i>
                  Print Receipt
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
