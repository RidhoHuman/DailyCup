'use client';

import { useState, useEffect } from 'react';

interface XenditCheckoutModalProps {
  checkoutUrl: string;
  orderId: string;
  onClose: () => void;
  onSuccess: () => void;
}

export default function XenditCheckoutModal({ 
  checkoutUrl, 
  orderId,
  onClose, 
  onSuccess 
}: XenditCheckoutModalProps) {
  const [loading, setLoading] = useState(true);

  // Listen untuk message dari Xendit iframe
  useEffect(() => {
    const handleMessage = (event: MessageEvent) => {
      // Verify origin untuk security
      if (!event.origin.includes('xendit.co')) return;

      console.log('[XenditModal] Received message:', event.data);

      // Xendit akan kirim message ketika pembayaran selesai
      if (event.data.status === 'COMPLETED' || event.data.status === 'PAID') {
        console.log('[XenditModal] Payment completed!');
        onSuccess();
        
        // Auto-close modal setelah 2 detik
        setTimeout(() => {
          onClose();
        }, 2000);
      }
    };

    window.addEventListener('message', handleMessage);
    return () => window.removeEventListener('message', handleMessage);
  }, [onSuccess, onClose]);

  // ESC key to close
  useEffect(() => {
    const handleEsc = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    window.addEventListener('keydown', handleEsc);
    return () => window.removeEventListener('keydown', handleEsc);
  }, [onClose]);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm">
      <div className="relative w-full max-w-4xl h-[90vh] bg-white rounded-2xl overflow-hidden shadow-2xl mx-4">
        {/* Header */}
        <div className="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 flex items-center justify-between">
          <div>
            <h2 className="text-xl font-bold">Complete Payment</h2>
            <p className="text-sm text-blue-100">Order #{orderId}</p>
          </div>
          
          {/* Close Button */}
          <button
            onClick={onClose}
            className="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors flex items-center gap-2"
          >
            <i className="bi bi-x-lg"></i>
            Close
          </button>
        </div>

        {/* Loading Spinner */}
        {loading && (
          <div className="absolute inset-0 flex items-center justify-center bg-white z-10">
            <div className="text-center">
              <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mx-auto mb-4"></div>
              <p className="text-lg font-medium text-gray-700">Loading payment page...</p>
              <p className="text-sm text-gray-500 mt-2">Redirecting to Xendit secure checkout</p>
            </div>
          </div>
        )}

        {/* Xendit Iframe */}
        <iframe
          src={checkoutUrl}
          className="w-full h-full border-0"
          onLoad={() => {
            console.log('[XenditModal] Iframe loaded');
            setLoading(false);
          }}
          title="Xendit Payment"
          allow="payment"
          sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation"
        />
      </div>
    </div>
  );
}
