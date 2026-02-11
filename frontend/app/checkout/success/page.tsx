"use client";

import Link from "next/link";
import Header from "../../../components/Header";
import { useEffect, useState } from "react";
import { useCart } from "../../../contexts/CartContext";

export default function CheckoutSuccessPage() {
  const { clearCart } = useCart();
  const [orderId, setOrderId] = useState<string>('');

  useEffect(() => {
    // Generate order ID once on mount (use timeout to avoid synchronous setState)
    setTimeout(() => {
      setOrderId(`ORD-${Math.floor(Math.random() * 1000000)}`);
    }, 0);
    // Clear the cart when reaching success page
    clearCart();
  }, [clearCart]);

  return (
    <div className="min-h-screen bg-[#f5f0ec] dark:bg-[#1a1a1a] transition-colors duration-300">
      <Header />
      
      <div className="max-w-2xl mx-auto px-4 py-20 text-center">
        <div className="w-24 h-24 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm animate-bounce-slow">
            <i className="bi bi-check-lg text-4xl text-green-600 dark:text-green-400"></i>
        </div>
        
        <h1 className="text-3xl font-bold text-gray-800 dark:text-white mb-2">Order Placed Successfully!</h1>
        <p className="text-gray-600 dark:text-gray-300 mb-8">
            Thank you for your purchase. We have received your order and are preparing it with love ☕
        </p>

        <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 mb-8 text-left max-w-md mx-auto">
            <h3 className="text-sm uppercase tracking-wider text-gray-500 font-semibold mb-4 border-b pb-2">Order Details</h3>
            <div className="flex justify-between mb-2">
                <span className="text-gray-600 dark:text-gray-400">Order ID</span>
                <span className="font-mono font-medium">#{orderId || 'Loading...'}</span>
            </div>
            <div className="flex justify-between mb-2">
                <span className="text-gray-600 dark:text-gray-400">Estimated Delivery</span>
                <span className="font-medium text-[#a97456]">30-45 Mins</span>
            </div>
             <p className="text-xs text-gray-400 mt-4">
                * A confirmation email has been sent to your inbox.
            </p>
        </div>

        <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link href="/menu" className="bg-[#a97456] text-white px-8 py-3 rounded-xl font-medium hover:bg-[#8f6249] transition-colors shadow-lg hover:shadow-xl">
                Order More Coffee
            </Link>
            <Link href="/" className="bg-white dark:bg-[#333] text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 px-8 py-3 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-[#444] transition-colors">
                Back to Home
            </Link>
        </div>
      </div>
    </div>
  );
}
