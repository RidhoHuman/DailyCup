/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useState, useEffect, useRef, Suspense } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import Header from "../../../components/Header";
import Link from "next/link";
import { fetchOrder, payOrder, Order } from "@/utils/api";
import XenditCheckoutModal from "@/components/checkout/XenditCheckoutModal";

function PaymentContent() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const orderId = searchParams.get('orderId');
  const midtoken = searchParams.get('midtoken');
  const mock = searchParams.get('mock');
  const invoiceUrl = searchParams.get('invoice_url'); // Xendit invoice URL
  const failed = searchParams.get('failed'); // Payment failed flag

  const [order, setOrder] = useState<Order | null>(null);
  const [status, setStatus] = useState('pending');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [paymentMethod, setPaymentMethod] = useState<string>('');
  const pollingIntervalRef = useRef<NodeJS.Timeout | null>(null);
  const [hasRedirected, setHasRedirected] = useState(false);
  const [showXenditModal, setShowXenditModal] = useState(false);
  const [syncing, setSyncing] = useState(false); // Start false for hydration, set true in useEffect
  const [mounted, setMounted] = useState(false); // Track client mount for hydration

  // Sync Xendit status (for development when webhook can't reach localhost)
  const syncXenditStatus = async (orderNumber: string): Promise<{ payment_status?: string; synced?: boolean } | null> => {
    try {
      const response = await fetch(`/api/sync-xendit?orderId=${encodeURIComponent(orderNumber)}`);
      const data = await response.json();
      console.log('Xendit sync result:', data);
      return data;
    } catch (err) {
      console.error('Xendit sync error:', err);
      return null;
    }
  };

  // Initial fetch
  useEffect(() => {
    setMounted(true); // Mark as mounted on client
    
    if (!orderId) {
      setError('No order ID provided');
      setLoading(false);
      return;
    }

    setSyncing(true); // Start syncing on client only

    const loadOrder = async () => {
      try {
        // IMPORTANT: Sync with Xendit API FIRST and use the result directly
        // This prevents showing "Pay Now" button for already-paid invoices
        const syncResult = await syncXenditStatus(orderId);
        
        // If sync shows it's already paid, update status immediately
        if (syncResult?.payment_status === 'paid') {
          setStatus('paid');
          setHasRedirected(true);
          setSyncing(false);
          router.push(`/checkout/success?orderId=${orderId}`);
          return; // Don't continue loading order details
        }
        
        // Then fetch the order details
        const res = await fetchOrder(orderId);
        if (res.success && res.order) {
          setOrder(res.order);
          const orderStatus = (res.order as any).payment_status ?? (res.order as any).status ?? 'pending';
          setStatus(orderStatus);
          setPaymentMethod((res.order as any).payment_method ?? 'online');
          
          // Double-check: If payment is already completed, redirect to success
          if (orderStatus === 'paid' && !hasRedirected) {
            setHasRedirected(true);
            router.push(`/checkout/success?orderId=${orderId}`);
            return;
          }
        } else {
          setError('Order not found');
        }
      } catch (err) {
        console.error('Error loading order:', err);
        setError('Failed to load order');
      } finally {
        setLoading(false);
        setSyncing(false);
      }
    };

    loadOrder();
  }, [orderId, router, hasRedirected]);

  // Status polling - only when payment is pending
  useEffect(() => {
    if (!orderId || status === 'paid' || status === 'failed' || hasRedirected) {
      // Stop polling if already paid or failed
      if (pollingIntervalRef.current) {
        clearInterval(pollingIntervalRef.current);
        pollingIntervalRef.current = null;
      }
      // Close modal if payment is completed while modal is open
      if (status === 'paid' && showXenditModal) {
        setShowXenditModal(false);
      }
      return;
    }

    // Start polling every 3 seconds for better UX
    pollingIntervalRef.current = setInterval(async () => {
      try {
        // Sync with Xendit first (in case payment completed)
        await syncXenditStatus(orderId);
        
        const res = await fetchOrder(orderId);
        if (res.success && res.order) {
          const newStatus = (res.order as any).payment_status ?? (res.order as any).status ?? 'pending';
          setOrder(res.order);
          setStatus(newStatus);

          // Redirect to success when paid
          if (newStatus === 'paid' && !hasRedirected) {
            setHasRedirected(true);
            if (pollingIntervalRef.current) {
              clearInterval(pollingIntervalRef.current);
              pollingIntervalRef.current = null;
            }
            setTimeout(() => {
              router.push(`/checkout/success?orderId=${orderId}`);
            }, 2000);
          }
        }
      } catch (err) {
        console.error('Polling error:', err);
        // Don't show error to user during polling
      }
    }, 3000); // Poll every 3 seconds

    // Cleanup on unmount
    return () => {
      if (pollingIntervalRef.current) {
        clearInterval(pollingIntervalRef.current);
        pollingIntervalRef.current = null;
      }
    };
  }, [orderId, status, router, hasRedirected, showXenditModal]);

  const handleMockPay = async (result: 'paid' | 'failed') => {
    if (!orderId) return;
    
    try {
      setLoading(true);
      await payOrder(orderId, result);
      
      // Refresh order data
      const res = await fetchOrder(orderId);
      if (res.success && res.order) {
        setOrder(res.order);
        setStatus(result);
      }
    } catch (err) {
      console.error('Payment simulation error:', err);
      setError('Failed to process payment');
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (currentStatus: string) => {
    switch (currentStatus) {
      case 'paid':
        return 'text-green-600';
      case 'failed':
        return 'text-red-600';
      case 'pending':
        return 'text-yellow-600';
      default:
        return 'text-gray-600';
    }
  };

  const getStatusMessage = (currentStatus: string) => {
    switch (currentStatus) {
      case 'paid':
        return '✓ Payment successful! Your order is being processed.';
      case 'failed':
        return '✗ Payment failed. Please try again or contact support.';
      case 'pending':
        return '⏳ Waiting for payment confirmation...';
      default:
        return 'Processing...';
    }
  };

  // Show loading while syncing payment status
  if ((loading || syncing) && !order) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-8">
        <div className="bg-white p-8 rounded-lg shadow text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto mb-4"></div>
          <p className="text-gray-600">Loading order details...</p>
        </div>
      </div>
    );
  }

  if (error || !order) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-8">
        <div className="bg-red-50 border border-red-200 p-6 rounded-lg">
          <h2 className="text-red-800 font-bold mb-2">Error</h2>
          <p className="text-red-600 mb-4">{error || 'Order not found'}</p>
          <Link href="/checkout" className="text-[#a97456] hover:underline">
            Return to Checkout
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold text-[#a97456] mb-6">Payment</h1>
      
      <div className="bg-white p-6 rounded-lg shadow mb-6">
        <div className="border-b pb-4 mb-4">
          <h2 className="text-xl font-semibold mb-2">Order Details</h2>
          <p className="text-gray-600">Order ID: <strong className="text-gray-900">{order.id}</strong></p>
          <p className="text-2xl font-bold text-[#a97456] mt-2">
            Total: Rp {order.total.toLocaleString('id-ID')}
          </p>
        </div>

        {/* Payment Status */}
        <div className={`p-4 rounded-lg mb-4 ${
          status === 'paid' ? 'bg-green-50 border border-green-200' :
          status === 'failed' ? 'bg-red-50 border border-red-200' :
          'bg-yellow-50 border border-yellow-200'
        }`}>
          <p className={`font-semibold ${getStatusColor(status)}`}>
            {getStatusMessage(status)}
          </p>
          {status === 'pending' && (
            <p className="text-sm text-gray-600 mt-2">
              This page will update automatically when payment is confirmed.
            </p>
          )}
        </div>

        {/* Payment Provider Info - Only show after sync completes */}
        {invoiceUrl && status === 'pending' && !syncing && (
          <div className="mb-4">
            <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg mb-3">
              <p className="text-sm text-blue-800 font-medium mb-1">💳 Xendit Payment Ready</p>
              <p className="text-sm text-blue-600 mb-3">
                Complete your payment securely through Xendit. Choose from various payment methods.
              </p>
            </div>
            
            {/* Xendit Invoice Button - Primary Action */}
            <button
              onClick={() => setShowXenditModal(true)}
              className="block w-full px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl text-center font-semibold text-lg"
            >
              🚀 Pay Now with Xendit
            </button>

            {/* Available Payment Methods Info */}
            <div className="mt-4 p-3 bg-gray-50 rounded-lg">
              <p className="text-xs text-gray-600 font-medium mb-2">Available Payment Methods:</p>
              <div className="flex flex-wrap gap-2">
                <span className="px-2 py-1 bg-white border border-gray-200 rounded text-xs">🏦 Virtual Account</span>
                <span className="px-2 py-1 bg-white border border-gray-200 rounded text-xs">💳 Credit Card</span>
                <span className="px-2 py-1 bg-white border border-gray-200 rounded text-xs">📱 E-Wallet</span>
                <span className="px-2 py-1 bg-white border border-gray-200 rounded text-xs">🏪 Retail Outlets</span>
                <span className="px-2 py-1 bg-white border border-gray-200 rounded text-xs">🔲 QRIS</span>
              </div>
            </div>

            <p className="text-xs text-gray-500 mt-3 text-center">
              ⚡ Secure payment powered by Xendit • Test mode active
            </p>
          </div>
        )}

        {midtoken && (
          <div className="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p className="text-sm text-blue-800 font-medium mb-1">Payment Gateway Detected</p>
            <p className="text-sm text-blue-600">
              Midtrans token received. In production, Snap payment modal would open automatically.
            </p>
          </div>
        )}

        {/* Mock Payment Buttons (for development/testing) */}
        {(mock === 'true' || (!invoiceUrl && !midtoken)) && status === 'pending' && (
          <div className="border-t pt-4">
            <p className="text-sm text-gray-500 mb-3">
              <strong>Development Mode:</strong> Simulate payment result
            </p>
            <div className="flex gap-3">
              <button 
                onClick={() => handleMockPay('paid')} 
                disabled={loading}
                className="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
              >
                {loading ? 'Processing...' : '✓ Simulate Success'}
              </button>
              <button 
                onClick={() => handleMockPay('failed')} 
                disabled={loading}
                className="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
              >
                {loading ? 'Processing...' : '✗ Simulate Failure'}
              </button>
            </div>
          </div>
        )}

        {/* Action Buttons */}
        <div className="mt-6 flex gap-3">
          {status === 'paid' && (
            <button
              onClick={() => router.push('/orders')}
              className="flex-1 px-6 py-3 bg-[#a97456] text-white rounded-lg hover:bg-[#8a4f35] transition-colors font-medium"
            >
              View My Orders
            </button>
          )}
          {status === 'failed' && (
            <button
              onClick={() => router.push('/checkout')}
              className="flex-1 px-6 py-3 bg-[#a97456] text-white rounded-lg hover:bg-[#8a4f35] transition-colors font-medium"
            >
              Try Again
            </button>
          )}
          <Link 
            href="/" 
            className="flex-1 px-6 py-3 border-2 border-[#a97456] text-[#a97456] rounded-lg hover:bg-[#a97456] hover:text-white transition-colors font-medium text-center"
          >
            Back to Home
          </Link>
        </div>
      </div>

      {/* Xendit Modal */}
      {/* Xendit Modal - Only show if payment is still pending */}
      {showXenditModal && invoiceUrl && status === 'pending' && (
        <XenditCheckoutModal
          checkoutUrl={invoiceUrl}
          orderId={order.id}
          onClose={() => setShowXenditModal(false)}
          onSuccess={() => {
            console.log('Payment success from modal!');
            setShowXenditModal(false);
            // Trigger immediate refresh
            if (orderId) {
              fetchOrder(orderId).then(res => {
                if (res.success && res.order) {
                  const newStatus = (res.order as any).payment_status ?? (res.order as any).status ?? 'pending';
                  setStatus(newStatus);
                  if (newStatus === 'paid') {
                    setTimeout(() => {
                      router.push(`/checkout/success?orderId=${orderId}`);
                    }, 1000);
                  }
                }
              });
            }
          }}
        />
      )}

      {/* Order Items Summary */}
      {order.items && order.items.length > 0 && (
        <div className="bg-white p-6 rounded-lg shadow">
          <h3 className="text-lg font-semibold mb-4">Order Items</h3>
          <div className="space-y-3">
            {order.items.map((item, index) => (
              <div key={index} className="flex justify-between items-center border-b pb-2">
                <div>
                  <p className="font-medium">{item.name}</p>
                  <p className="text-sm text-gray-500">Quantity: {item.quantity}</p>
                </div>
                <p className="font-semibold">
                  Rp {(item.price * item.quantity).toLocaleString('id-ID')}
                </p>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

export default function PaymentPage() {
  return (
    <div className="min-h-screen bg-[#f5f0ec]">
      <Header />
      <Suspense fallback={
        <div className="max-w-4xl mx-auto px-4 py-8">
          <div className="bg-white p-8 rounded-lg shadow text-center">
            <p>Loading...</p>
          </div>
        </div>
      }>
        <PaymentContent />
      </Suspense>
    </div>
  );
}
