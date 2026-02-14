'use client';

import { useParams } from 'next/navigation';
import { useState, useEffect } from 'react';
import Header from '../../../components/Header';
import Link from 'next/link';
import Image from 'next/image';

// Tipe data mock untuk Order Detail
interface OrderDetail {
  id: string;
  date: string;
  status: 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
  items: Array<{
    id: number;
    name: string;
    image: string;
    variant: string;
    price: number;
    quantity: number;
  }>;
  subtotal: number;
  deliveryFee: number;
  total: number;
  shippingAddress: {
    name: string;
    phone: string;
    address: string;
    city: string;
  };
  paymentMethod: string;
  timeline: Array<{
    status: string;
    date: string;
    completed: boolean;
    icon: string;
  }>;
}

export default function OrderTrackingPage() {
  const params = useParams();
  const { id } = params;
  
  const [order, setOrder] = useState<OrderDetail | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Simulasi fetch data dari API berdasarkan ID
    const fetchOrder = async () => {
      setLoading(true);
      await new Promise(resolve => setTimeout(resolve, 1000)); // Simulasi delay

      // Data Mock
      const mockOrder: OrderDetail = {
        id: id as string,
        date: '22 Jan 2026, 14:30',
        status: 'processing',
        items: [
          {
            id: 1,
            name: 'Caramel Macchiato',
            image: 'product_699045a386d37_1771062691.jfif',
            variant: 'Large, Hot',
            price: 45000,
            quantity: 2
          },
          {
            id: 2,
            name: 'Croissant Butter',
            image: 'product_699045c91881b_1771062729.png',
            variant: 'Standard',
            price: 25000,
            quantity: 1
          }
        ],
        subtotal: 115000,
        deliveryFee: 15000,
        total: 130000,
        shippingAddress: {
          name: 'Budi Santoso',
          phone: '081234567890',
          address: 'Jl. Sudirman No. 45, Kebayoran Baru',
          city: 'Jakarta Selatan'
        },
        paymentMethod: 'BCA Virtual Account',
        timeline: [
          { status: 'Order Placed', date: '22 Jan, 14:30', completed: true, icon: 'bi-cart-check' },
          { status: 'Payment Confirmed', date: '22 Jan, 14:35', completed: true, icon: 'bi-wallet2' },
          { status: 'Preparing Order', date: '22 Jan, 14:40', completed: true, icon: 'bi-cup-hot' },
          { status: 'Out for Delivery', date: 'Estimated 15:10', completed: false, icon: 'bi-truck' },
          { status: 'Delivered', date: '-', completed: false, icon: 'bi-house-door' }
        ]
      };

      setOrder(mockOrder);
      setLoading(false);
    };

    if (id) {
      fetchOrder();
    }
  }, [id]);

  if (loading) {
    return (
      <div className="min-h-screen bg-[#f5f0ec] dark:bg-[#1a1a1a]">
        <Header />
        <div className="flex justify-center items-center h-[60vh]">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-[#a97456]"></div>
        </div>
      </div>
    );
  }

  if (!order) return null;

  return (
    <div className="min-h-screen bg-[#f5f0ec] dark:bg-[#1a1a1a] transition-colors duration-300">
      <Header />

      <div className="max-w-4xl mx-auto px-4 py-8">
        {/* Back Button */}
        <Link href="/orders" className="inline-flex items-center text-gray-500 hover:text-[#a97456] mb-6 transition-colors">
          <i className="bi bi-arrow-left mr-2"></i> Back to Orders
        </Link>

        {/* Header Section */}
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
          <div>
            <h1 className="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-3">
              Order #{order.id}
              <span className="px-3 py-1 bg-amber-100 text-amber-800 text-xs rounded-full uppercase tracking-wider font-semibold">
                {order.status}
              </span>
            </h1>
            <p className="text-gray-500 dark:text-gray-400 text-sm mt-1">Placed on {order.date}</p>
          </div>
          <button className="bg-white dark:bg-[#333] border border-gray-200 dark:border-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-[#444] transition-colors">
            <i className="bi bi-file-earmark-text mr-2"></i> Invoice
          </button>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Column: Tracking & Details */}
          <div className="lg:col-span-2 space-y-6">
            
            {/* Tracking Timeline */}
            <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
              <h2 className="font-bold text-lg mb-6 flex items-center gap-2">
                <i className="bi bi-geo-alt text-[#a97456]"></i> Order Status
              </h2>
              
              <div className="relative pl-4">
                 {/* Line */}
                 <div className="absolute left-[27px] top-4 bottom-4 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                 <div className="space-y-8">
                    {order.timeline.map((step, index) => (
                      <div key={index} className="relative flex gap-4">
                        {/* Dot/Icon */}
                        <div className={`relative z-10 w-8 h-8 rounded-full flex items-center justify-center border-2 flex-shrink-0 transition-colors duration-300
                          ${step.completed 
                            ? 'bg-[#a97456] border-[#a97456] text-white' 
                            : 'bg-white dark:bg-[#333] border-gray-300 text-gray-400'
                          }
                        `}>
                          <i className={`bi ${step.icon} text-sm`}></i>
                        </div>
                        
                        {/* Content */}
                        <div className={`pt-1 ${step.completed ? 'opacity-100' : 'opacity-60 grayscale'}`}>
                          <h3 className="font-semibold text-gray-800 dark:text-gray-200">{step.status}</h3>
                          <p className="text-xs text-gray-500">{step.date}</p>
                        </div>
                      </div>
                    ))}
                 </div>
              </div>

              {/* Map Placeholder */}
              <div className="mt-8 h-48 bg-gray-100 dark:bg-[#333] rounded-xl flex items-center justify-center relative overflow-hidden group">
                  <div className="absolute inset-0 opacity-10 bg-[url('https://upload.wikimedia.org/wikipedia/commons/e/ec/World_map_blank_without_borders.svg')] bg-cover"></div>
                  <div className="text-gray-400 flex flex-col items-center">
                    <i className="bi bi-map text-3xl mb-2"></i>
                    <span className="text-sm">Live Driver Tracking Map</span>
                    <span className="text-xs">(Simulation)</span>
                  </div>
              </div>
            </div>

            {/* Order Items */}
            <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
              <h2 className="font-bold text-lg mb-6">Items Ordered</h2>
              <div className="space-y-4">
                {order.items.map((item) => (
                  <div key={item.id} className="flex gap-4">
                    <div className="w-20 h-20 bg-gray-100 rounded-lg relative overflow-hidden flex-shrink-0">
                         <Image src={`/products/${item.image}`} alt={item.name} fill className="object-cover" />
                    </div>
                    <div className="flex-1">
                        <h4 className="font-semibold text-gray-800 dark:text-gray-200">{item.name}</h4>
                        <p className="text-sm text-gray-500">{item.variant}</p>
                        <div className="flex justify-between items-end mt-2">
                           <p className="text-sm text-gray-600 dark:text-gray-400">x{item.quantity}</p>
                           <p className="font-semibold text-[#a97456]">Rp {(item.price * item.quantity).toLocaleString()}</p>
                        </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

          </div>

          {/* Right Column: Summary & Info */}
          <div className="lg:col-span-1 space-y-6">
            
            {/* Delivery Details */}
            <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
               <h3 className="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Delivery Address</h3>
               <p className="font-semibold text-gray-800 dark:text-gray-200">{order.shippingAddress.name}</p>
               <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">{order.shippingAddress.phone}</p>
               <p className="text-sm text-gray-600 dark:text-gray-400 mt-2 leading-relaxed">
                  {order.shippingAddress.address}<br />
                  {order.shippingAddress.city}
               </p>
            </div>

            {/* Payment Info */}
             <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
               <h3 className="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Payment Info</h3>
               <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                    <i className="bi bi-credit-card"></i>
                  </div>
                  <div>
                    <p className="font-medium text-gray-800 dark:text-gray-200">{order.paymentMethod}</p>
                    <p className="text-xs text-green-600 font-medium">Payment Verified</p>
                  </div>
               </div>
            </div>

            {/* Cost Summary */}
            <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
              <h3 className="font-bold text-lg mb-4">Order Summary</h3>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Subtotal</span>
                  <span className="font-medium">Rp {order.subtotal.toLocaleString()}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Delivery Fee</span>
                  <span className="font-medium">Rp {order.deliveryFee.toLocaleString()}</span>
                </div>
                <div className="border-t dark:border-gray-600 pt-3 mt-3 flex justify-between text-base font-bold">
                  <span>Total Paid</span>
                  <span className="text-[#a97456]">Rp {order.total.toLocaleString()}</span>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  );
}
