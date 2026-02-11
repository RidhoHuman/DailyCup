"use client";

import Link from "next/link";
import Header from "../../../components/Header";
import { useEffect, useState, Suspense } from "react";
import { useCart } from "../../../contexts/CartContext";
import { useSearchParams } from "next/navigation";
import { api } from "@/lib/api-client";

interface OrderDetails {
    order_number: string;
    status: string;
    payment_method: string;
    payment_status: string;
    total_amount: number;
    estimated_ready_at?: string;
}

function SuccessContent() {
  const { clearCart } = useCart();
  const searchParams = useSearchParams();
  const orderId = searchParams.get('orderId');
  
  const [order, setOrder] = useState<OrderDetails | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Clear the cart when reaching success page
    clearCart();

    if (orderId) {
        // Fetch order details to confirm status
        api.get<OrderDetails>(`/get_order.php?orderId=${orderId}`)
           .then((data) => {
               console.log("Order fetched:", data);
               setOrder(data);
           })
           .catch(err => console.error("Failed to fetch order", err))
           .finally(() => setLoading(false));
    } else {
        setLoading(false);
    }
  }, [clearCart, orderId]);

  const isCOD = order?.payment_method === 'cod';
  const isPaid = order?.payment_status === 'paid';

  return (
      <div className="max-w-2xl mx-auto px-4 py-20 text-center">
        <div className={`w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm animate-bounce-slow ${isCOD ? 'bg-orange-100 dark:bg-orange-900/30' : 'bg-green-100 dark:bg-green-900/30'}`}>
            <i className={`bi ${isCOD ? 'bi-hourglass-split text-orange-600 dark:text-orange-400' : 'bi-check-lg text-green-600 dark:text-green-400'} text-4xl`}></i>
        </div>
        
        <h1 className="text-3xl font-bold text-gray-800 dark:text-white mb-2">
            {isCOD ? 'Order Placed - Waiting Confirmation' : 'Order Placed Successfully!'}
        </h1>
        <p className="text-gray-600 dark:text-gray-300 mb-8">
            {isCOD 
                ? "Your COD order has been placed. Please wait for the admin to confirm your order."
                : "Thank you for your purchase. We have received your order and are preparing it with love ☕"}
        </p>

        <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 mb-8 text-left max-w-md mx-auto">
            <h3 className="text-sm uppercase tracking-wider text-gray-500 font-semibold mb-4 border-b pb-2">Order Details</h3>
            <div className="flex justify-between mb-2">
                <span className="text-gray-600 dark:text-gray-400">Order ID</span>
                <span className="font-mono font-medium">#{orderId || (loading ? 'Loading...' : 'Unknown')}</span>
            </div>
            <div className="flex justify-between mb-2">
                <span className="text-gray-600 dark:text-gray-400">Status</span>
                <span className={`font-medium ${isCOD ? 'text-orange-600' : 'text-green-600'}`}>
                    {order?.status ? order.status.toUpperCase() : (loading ? '...' : 'PENDING')}
                </span>
            </div>
            {order?.total_amount && (
                <div className="flex justify-between mb-2">
                    <span className="text-gray-600 dark:text-gray-400">Total</span>
                    <span className="font-medium text-[#a97456]">Rp {Number(order.total_amount).toLocaleString()}</span>
                </div>
            )}
             <p className="text-xs text-gray-400 mt-4">
                * A confirmation email has been sent to your inbox.
            </p>
        </div>

        <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link href="/track" className="bg-[#a97456] text-white px-8 py-3 rounded-xl hover:bg-[#8a4f35] transition-colors font-medium shadow-md hover:shadow-lg">
                <i className="bi bi-geo-alt-fill mr-2"></i> Track Order
            </Link>
            <Link href="/menu" className="bg-white dark:bg-[#333] text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 px-8 py-3 rounded-xl hover:bg-gray-50 dark:hover:bg-[#444] transition-colors font-medium">
                Order Again
            </Link>
        </div>
      </div>
  );
}

export default function CheckoutSuccessPage() {
    return (
        <div className="min-h-screen bg-[#f5f0ec] dark:bg-[#1a1a1a] transition-colors duration-300">
            <Header />
            <Suspense fallback={<div className="text-center py-20">Loading order details...</div>}>
                <SuccessContent />
            </Suspense>
        </div>
    );
}
