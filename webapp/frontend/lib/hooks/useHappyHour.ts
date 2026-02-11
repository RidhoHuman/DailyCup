/**
 * Happy Hour Hook
 * 
 * Purpose: Fetch real-time Happy Hour discount for a product
 * Used by: ProductCard, ProductDetail, Cart components
 * 
 * Features:
 * - Real-time check based on current time
 * - Automatic refetch every minute (to catch time changes)
 * - Returns discount info or null if no active Happy Hour
 */

import { useState, useEffect } from 'react';
import api from '@/lib/api-client';

interface HappyHourDiscount {
  schedule_id: number;
  schedule_name: string;
  start_time: string;
  end_time: string;
  discount_percentage: number;
  original_price: number;
  discount_amount: number;
  final_price: number;
  savings: number;
  applied_via: 'category' | 'manual';
  category: string;
}

interface HappyHourResponse {
  success: boolean;
  has_discount: boolean;
  discount?: HappyHourDiscount;
  message?: string;
}

export function useHappyHour(productId: number | string | null) {
  const [discount, setDiscount] = useState<HappyHourDiscount | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!productId) {
      setLoading(false);
      return;
    }

    const checkHappyHour = async () => {
      try {
        const response = await api.get<HappyHourResponse>(
          `/happy_hour/check_active.php?product_id=${productId}`
        );

        if (response.success && response.has_discount && response.discount) {
          setDiscount(response.discount);
        } else {
          setDiscount(null);
        }
      } catch (error) {
        console.error('Error checking Happy Hour:', error);
        setDiscount(null);
      } finally {
        setLoading(false);
      }
    };

    // Initial check
    checkHappyHour();

    // Refetch every 60 seconds to catch time-based changes
    // (e.g., Happy Hour starts/ends while user is browsing)
    const interval = setInterval(checkHappyHour, 60000);

    return () => clearInterval(interval);
  }, [productId]);

  return {
    discount,
    hasDiscount: discount !== null,
    loading,
  };
}
