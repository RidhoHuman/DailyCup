'use client';

import { useState, useEffect } from 'react';
import { formatPoints, getLoyaltyTier } from '@/utils/loyalty-points';
import { useAuthStore } from '@/lib/stores/auth-store';
import api from '@/lib/api-client';

export default function LoyaltyProgressBar() {
  const { user } = useAuthStore();
  const [points, setPoints] = useState(0);
  const [totalEarned, setTotalEarned] = useState(0);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (user) {
      fetchLoyaltyPoints();
    }
  }, [user]);

  const fetchLoyaltyPoints = async () => {
    try {
      const response = await api.get<{
        success: boolean;
        loyalty_points?: number;
        total_points_earned?: number;
      }>(`/user/loyalty_points.php`);

      if (response.success) {
        setPoints(response.loyalty_points || 0);
        setTotalEarned(response.total_points_earned || 0);
      }
    } catch (error) {
      console.error('Error fetching loyalty points:', error);
    } finally {
      setLoading(false);
    }
  };

  if (!user || loading) return null;

  const tier = getLoyaltyTier(totalEarned);

  return (
    <div className="flex items-center gap-2 px-3 py-2 bg-white rounded-lg shadow-sm border border-gray-200">
      <span className="text-xl">{tier.icon}</span>
      <div className="flex flex-col">
        <div className="flex items-center gap-2">
          <span className="text-xs font-semibold" style={{ color: tier.color }}>
            {tier.tier}
          </span>
          <span className="text-sm font-bold text-[#a97456]">
            {formatPoints(points)}
          </span>
        </div>
        {tier.nextTier && (
          <span className="text-xs text-gray-500">
            {tier.pointsToNextTier} to {tier.nextTier}
          </span>
        )}
      </div>
    </div>
  );
}
