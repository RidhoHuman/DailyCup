"use client";

import { useState, useEffect } from 'react';
import { useAuthStore } from '@/lib/stores/auth-store';
import { api } from '@/lib/api-client';
import Link from 'next/link';

interface LoyaltyHistory {
  id: number;
  type: 'earn' | 'redeem' | 'expire';
  points: number;
  description: string;
  created_at: string;
}

export default function LoyaltyPointsPage() {
  const { user } = useAuthStore();
  const [redeemCode, setRedeemCode] = useState('');
  const [isRedeeming, setIsRedeeming] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error', text: string } | null>(null);
  const [history, setHistory] = useState<LoyaltyHistory[]>([]);
  const [isLoadingHistory, setIsLoadingHistory] = useState(true);

  useEffect(() => {
    fetchLoyaltyHistory();
  }, []);

  const fetchLoyaltyHistory = async () => {
    try {
      setIsLoadingHistory(true);
      const response = await api.get<{success: boolean, history: LoyaltyHistory[]}>('/loyalty_history.php', { requiresAuth: true });
      if (response.success) {
        setHistory(response.history || []);
      }
    } catch (error) {
      console.error('Error fetching loyalty history:', error);
    } finally {
      setIsLoadingHistory(false);
    }
  };

  const handleRedeemCode = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!redeemCode.trim()) {
      setMessage({ type: 'error', text: 'Please enter a redeem code' });
      return;
    }

    setIsRedeeming(true);
    setMessage(null);

    try {
      const response = await api.post<{success: boolean, points?: number, message?: string}>('/redeem_code.php', {
        code: redeemCode
      }, { requiresAuth: true });

      if (response.success) {
        setMessage({ 
          type: 'success', 
          text: `Successfully redeemed ${response.points || 0} points!` 
        });
        setRedeemCode('');
        
        // Refresh user data and history
        fetchLoyaltyHistory();
        // TODO: Refresh user points in store
      } else {
        setMessage({ 
          type: 'error', 
          text: response.message || 'Invalid or already used code' 
        });
      }
    } catch (error) {
      console.error('Redeem error:', error);
      setMessage({ 
        type: 'error', 
        text: 'Failed to redeem code. Please try again.' 
        });
    } finally {
      setIsRedeeming(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#f5f0ec] to-[#e8ddd4] dark:from-gray-900 dark:to-gray-800">
      {/* Header */}
      <div className="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex items-center gap-4">
            <Link 
              href="/profile" 
              className="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
            >
              <i className="bi bi-arrow-left text-xl"></i>
            </Link>
            <div>
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Loyalty Points</h1>
              <p className="text-gray-600 dark:text-gray-400 mt-1">Earn points with every purchase and redeem exclusive rewards</p>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {/* Points Balance Card */}
        <div className="bg-gradient-to-r from-[#a97456] to-[#8f6249] rounded-2xl p-8 text-white shadow-xl mb-8">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm opacity-90 mb-2">Your Balance</p>
              <h2 className="text-5xl font-bold">{user?.loyaltyPoints || 0}</h2>
              <p className="text-sm opacity-90 mt-2">Loyalty Points</p>
            </div>
            <div className="w-24 h-24 bg-white/10 rounded-full flex items-center justify-center">
              <i className="bi bi-star-fill text-5xl"></i>
            </div>
          </div>

          <div className="mt-6 pt-6 border-t border-white/20">
            <div className="grid grid-cols-3 gap-4 text-center">
              <div>
                <p className="text-2xl font-bold">10</p>
                <p className="text-xs opacity-75">Points per Rp 1K</p>
              </div>
              <div>
                <p className="text-2xl font-bold">100</p>
                <p className="text-xs opacity-75">Min Redeem</p>
              </div>
              <div>
                <p className="text-2xl font-bold">1K</p>
                <p className="text-xs opacity-75">= Rp 1 Value</p>
              </div>
            </div>
          </div>
        </div>

        {/* Redeem Code Form */}
        <div className="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8">
          <h3 className="text-xl font-semibold mb-4 flex items-center gap-2">
            <i className="bi bi-gift-fill text-[#a97456]"></i>
            Redeem Code
          </h3>
          
          <form onSubmit={handleRedeemCode} className="space-y-4">
            <div className="flex gap-3">
              <input
                type="text"
                value={redeemCode}
                onChange={(e) => {
                  setRedeemCode(e.target.value.toUpperCase());
                  setMessage(null);
                }}
                placeholder="Enter your code"
                className="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#a97456] dark:bg-gray-700 dark:text-white"
                maxLength={20}
              />
              <button
                type="submit"
                disabled={isRedeeming || !redeemCode.trim()}
                className="px-8 py-3 bg-[#a97456] text-white rounded-xl hover:bg-[#8f6249] disabled:opacity-50 disabled:cursor-not-allowed font-semibold transition-all shadow-lg hover:shadow-xl"
              >
                {isRedeeming ? (
                  <span className="flex items-center gap-2">
                    <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    Redeeming...
                  </span>
                ) : (
                  'Redeem'
                )}
              </button>
            </div>

            {message && (
              <div className={`p-4 rounded-xl flex items-center gap-2 ${
                message.type === 'success' 
                  ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800' 
                  : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800'
              }`}>
                <i className={`bi ${message.type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'}`}></i>
                <span>{message.text}</span>
              </div>
            )}
          </form>
        </div>

        {/* How to Earn Points */}
        <div className="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8">
          <h3 className="text-xl font-semibold mb-4 flex items-center gap-2">
            <i className="bi bi-info-circle-fill text-[#a97456]"></i>
            How to Earn Points
          </h3>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="flex items-start gap-3 p-4 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl">
              <div className="w-10 h-10 bg-blue-500 text-white rounded-full flex items-center justify-center flex-shrink-0">
                <i className="bi bi-cart-check-fill"></i>
              </div>
              <div>
                <h4 className="font-semibold text-gray-800 dark:text-white">Make Purchases</h4>
                <p className="text-sm text-gray-600 dark:text-gray-400">Earn 10 points for every Rp 1,000 spent</p>
              </div>
            </div>

            <div className="flex items-start gap-3 p-4 bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl">
              <div className="w-10 h-10 bg-purple-500 text-white rounded-full flex items-center justify-center flex-shrink-0">
                <i className="bi bi-gift-fill"></i>
              </div>
              <div>
                <h4 className="font-semibold text-gray-800 dark:text-white">Redeem Codes</h4>
                <p className="text-sm text-gray-600 dark:text-gray-400">Get bonus points from special promo codes</p>
              </div>
            </div>

            <div className="flex items-start gap-3 p-4 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl">
              <div className="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center flex-shrink-0">
                <i className="bi bi-star-fill"></i>
              </div>
              <div>
                <h4 className="font-semibold text-gray-800 dark:text-white">Write Reviews</h4>
                <p className="text-sm text-gray-600 dark:text-gray-400">Earn 50 points for each product review</p>
              </div>
            </div>

            <div className="flex items-start gap-3 p-4 bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-xl">
              <div className="w-10 h-10 bg-orange-500 text-white rounded-full flex items-center justify-center flex-shrink-0">
                <i className="bi bi-people-fill"></i>
              </div>
              <div>
                <h4 className="font-semibold text-gray-800 dark:text-white">Refer Friends</h4>
                <p className="text-sm text-gray-600 dark:text-gray-400">Get 200 points when friends make first order</p>
              </div>
            </div>
          </div>
        </div>

        {/* Points History */}
        <div className="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg">
          <h3 className="text-xl font-semibold mb-4 flex items-center gap-2">
            <i className="bi bi-clock-history text-[#a97456]"></i>
            Points History
          </h3>

          {isLoadingHistory ? (
            <div className="text-center py-8">
              <div className="w-12 h-12 border-4 border-gray-200 dark:border-gray-700 border-t-[#a97456] rounded-full animate-spin mx-auto mb-4"></div>
              <p className="text-gray-500">Loading history...</p>
            </div>
          ) : history.length === 0 ? (
            <div className="text-center py-12">
              <div className="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <i className="bi bi-inbox text-3xl text-gray-400"></i>
              </div>
              <p className="text-gray-500">No points history yet</p>
              <p className="text-sm text-gray-400 mt-2">Start earning points by making purchases!</p>
            </div>
          ) : (
            <div className="space-y-3">
              {history.map((item) => (
                <div 
                  key={item.id}
                  className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:shadow-md transition-shadow"
                >
                  <div className="flex items-center gap-4">
                    <div className={`w-10 h-10 rounded-full flex items-center justify-center ${
                      item.type === 'earn' 
                        ? 'bg-green-100 dark:bg-green-900/30 text-green-600' 
                        : item.type === 'redeem'
                        ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600'
                        : 'bg-red-100 dark:bg-red-900/30 text-red-600'
                    }`}>
                      <i className={`bi ${
                        item.type === 'earn' ? 'bi-plus-circle-fill' :
                        item.type === 'redeem' ? 'bi-gift-fill' :
                        'bi-x-circle-fill'
                      }`}></i>
                    </div>
                    <div>
                      <p className="font-medium text-gray-800 dark:text-white">{item.description}</p>
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        {new Date(item.created_at).toLocaleDateString('id-ID', {
                          day: 'numeric',
                          month: 'short',
                          year: 'numeric',
                          hour: '2-digit',
                          minute: '2-digit'
                        })}
                      </p>
                    </div>
                  </div>
                  <div className={`font-bold text-lg ${
                    item.type === 'earn' ? 'text-green-600' : 'text-red-600'
                  }`}>
                    {item.type === 'earn' ? '+' : '-'}{item.points}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

      </div>
    </div>
  );
}
