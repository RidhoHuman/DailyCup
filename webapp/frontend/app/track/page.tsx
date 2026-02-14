'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Header from '@/components/Header';
import { api } from '@/lib/api-client';
import { getErrorMessage } from '@/lib/utils';

export default function TrackOrderPage() {
  const router = useRouter();
  const [orderNumber, setOrderNumber] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleTrackOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!orderNumber.trim()) {
      setError('Please enter order number');
      return;
    }

    setLoading(true);
    setError('');

    try {
      // Validate order exists
      const response = await api.get<{ success: boolean; order?: { id: string } }>(`/orders/track_order.php?order_number=${orderNumber.trim()}`);
      
      if (response.success && response.order) {
        // Redirect to order detail page
        router.push(`/orders/${response.order.id}`);
      } else {
        setError('Order not found. Please check your order number.');
      }
    } catch (err: unknown) {
      setError(getErrorMessage(err) || 'Failed to track order. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#f6efe9]">
      <Header />
      
      <div className="max-w-2xl mx-auto px-4 py-12">
        <div className="bg-white rounded-2xl shadow-xl p-8">
          {/* Header */}
          <div className="text-center mb-8">
            <div className="inline-flex items-center justify-center w-20 h-20 bg-[#a97456] rounded-full mb-4">
              <i className="bi bi-geo-alt text-white text-4xl"></i>
            </div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Track Your Order</h1>
            <p className="text-gray-600">
              Enter your order number to see real-time delivery status
            </p>
          </div>

          {/* Form */}
          <form onSubmit={handleTrackOrder} className="space-y-6">
            <div>
              <label htmlFor="orderNumber" className="block text-sm font-medium text-gray-700 mb-2">
                Order Number
              </label>
              <input
                type="text"
                id="orderNumber"
                value={orderNumber}
                onChange={(e) => setOrderNumber(e.target.value)}
                placeholder="ORD-1234567890-1234"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent text-lg"
                disabled={loading}
              />
              <p className="mt-2 text-sm text-gray-500">
                You can find your order number in the confirmation email
              </p>
            </div>

            {/* Error Message */}
            {error && (
              <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                <div className="flex items-center">
                  <i className="bi bi-exclamation-circle text-red-600 text-xl mr-3"></i>
                  <p className="text-sm text-red-800">{error}</p>
                </div>
              </div>
            )}

            {/* Submit Button */}
            <button
              type="submit"
              disabled={loading}
              className="w-full bg-[#a97456] text-white py-4 rounded-lg hover:bg-[#8a5a3d] disabled:opacity-50 disabled:cursor-not-allowed font-medium text-lg transition-colors flex items-center justify-center"
            >
              {loading ? (
                <>
                  <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                  Tracking...
                </>
              ) : (
                <>
                  <i className="bi bi-search mr-2"></i>
                  Track Order
                </>
              )}
            </button>
          </form>

          {/* Divider */}
          <div className="relative my-8">
            <div className="absolute inset-0 flex items-center">
              <div className="w-full border-t border-gray-300"></div>
            </div>
            <div className="relative flex justify-center text-sm">
              <span className="px-4 bg-white text-gray-500">OR</span>
            </div>
          </div>

          {/* Alternative Options */}
          <div className="space-y-3">
            <a
              href="/orders"
              className="block w-full text-center px-4 py-3 border-2 border-[#a97456] text-[#a97456] rounded-lg hover:bg-[#a97456] hover:text-white transition-colors font-medium"
            >
              <i className="bi bi-list-ul mr-2"></i>
              View All My Orders
            </a>
            
            <a
              href="/menu"
              className="block w-full text-center px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
            >
              <i className="bi bi-cup-straw mr-2"></i>
              Continue Shopping
            </a>
          </div>
        </div>

        {/* Help Section */}
        <div className="mt-8 text-center">
          <p className="text-gray-600 mb-2">Need help?</p>
          <a href="/contact" className="text-[#a97456] hover:underline font-medium">
            Contact Customer Support
          </a>
        </div>
      </div>
    </div>
  );
}
