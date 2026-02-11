/**
 * Loyalty Points System
 * Handles points earning and redemption logic
 */

/**
 * Loyalty Points Configuration
 * These values should match backend loyalty_rules table
 */
export const LOYALTY_CONFIG = {
  // Earn rate: 1 point per Rp 10,000 spent
  EARN_RATE: 10000, // Spend Rp 10,000 = 1 point
  
  // Redeem rate: 1 point = Rp 500 discount
  REDEEM_VALUE: 500, // 1 point = Rp 500
  
  // Minimum points required to redeem
  MIN_REDEEM_POINTS: 10,
  
  // Maximum percentage of order that can be paid with points
  MAX_REDEEM_PERCENTAGE: 50 // Max 50% of order total
};

/**
 * Calculate points earned from order amount
 * Formula: Total Amount / EARN_RATE (rounded down)
 * Example: Rp 50,000 / 10,000 = 5 points
 * 
 * @param orderAmount Total order amount in Rupiah
 * @returns Points earned (integer)
 */
export function calculatePointsEarned(orderAmount: number): number {
  if (orderAmount <= 0) return 0;
  return Math.floor(orderAmount / LOYALTY_CONFIG.EARN_RATE);
}

/**
 * Calculate discount amount from points
 * Formula: Points * REDEEM_VALUE
 * Example: 10 points * Rp 500 = Rp 5,000 discount
 * 
 * @param points Number of points to redeem
 * @returns Discount amount in Rupiah
 */
export function calculateDiscountFromPoints(points: number): number {
  if (points < LOYALTY_CONFIG.MIN_REDEEM_POINTS) return 0;
  return points * LOYALTY_CONFIG.REDEEM_VALUE;
}

/**
 * Calculate maximum points that can be redeemed for an order
 * Limits redemption to MAX_REDEEM_PERCENTAGE of order total
 * 
 * @param orderAmount Total order amount
 * @param availablePoints User's available points balance
 * @returns Maximum points that can be redeemed
 */
export function getMaxRedeemablePoints(
  orderAmount: number,
  availablePoints: number
): number {
  // Maximum discount allowed (50% of order)
  const maxDiscountAmount = orderAmount * (LOYALTY_CONFIG.MAX_REDEEM_PERCENTAGE / 100);
  
  // Maximum points needed for that discount
  const maxPointsForDiscount = Math.floor(maxDiscountAmount / LOYALTY_CONFIG.REDEEM_VALUE);
  
  // Return the lesser of available points or max allowed points
  return Math.min(availablePoints, maxPointsForDiscount);
}

/**
 * Validate points redemption
 * 
 * @param pointsToRedeem Points user wants to redeem
 * @param availablePoints User's current points balance
 * @param orderAmount Total order amount
 * @returns Validation result with errors if any
 */
export function validatePointsRedemption(
  pointsToRedeem: number,
  availablePoints: number,
  orderAmount: number
): {
  valid: boolean;
  errors: string[];
  maxAllowedPoints: number;
} {
  const errors: string[] = [];
  
  // Check minimum redemption
  if (pointsToRedeem < LOYALTY_CONFIG.MIN_REDEEM_POINTS) {
    errors.push(`Minimum ${LOYALTY_CONFIG.MIN_REDEEM_POINTS} points required to redeem`);
  }
  
  // Check if user has enough points
  if (pointsToRedeem > availablePoints) {
    errors.push(`You only have ${availablePoints} points available`);
  }
  
  // Check maximum redemption limit
  const maxPoints = getMaxRedeemablePoints(orderAmount, availablePoints);
  if (pointsToRedeem > maxPoints) {
    errors.push(`Maximum ${maxPoints} points can be redeemed for this order (${LOYALTY_CONFIG.MAX_REDEEM_PERCENTAGE}% of total)`);
  }
  
  return {
    valid: errors.length === 0,
    errors,
    maxAllowedPoints: maxPoints
  };
}

/**
 * Calculate order totals with points redemption
 * 
 * @param subtotal Order subtotal (before discount and delivery)
 * @param deliveryFee Delivery fee
 * @param pointsToRedeem Points user wants to redeem
 * @returns Detailed order calculation
 */
export function calculateOrderWithPoints(
  subtotal: number,
  deliveryFee: number,
  pointsToRedeem: number
): {
  subtotal: number;
  deliveryFee: number;
  pointsDiscount: number;
  pointsUsed: number;
  grandTotal: number;
  pointsEarned: number; // Points to be earned after order completion
} {
  const pointsDiscount = calculateDiscountFromPoints(pointsToRedeem);
  
  // Grand total cannot be negative
  const grandTotal = Math.max(0, subtotal + deliveryFee - pointsDiscount);
  
  // Points earned from grand total (after discount)
  const pointsEarned = calculatePointsEarned(grandTotal);
  
  return {
    subtotal,
    deliveryFee,
    pointsDiscount,
    pointsUsed: pointsToRedeem,
    grandTotal,
    pointsEarned
  };
}

/**
 * Format points display for UI
 * @param points Number of points
 * @returns Formatted string
 */
export function formatPoints(points: number): string {
  return points.toLocaleString('id-ID') + ' pts';
}

/**
 * Get loyalty tier based on total points earned (lifetime)
 * This is for gamification and UI display
 * 
 * @param totalPointsEarned Lifetime points earned
 * @returns Loyalty tier information
 */
export function getLoyaltyTier(totalPointsEarned: number): {
  tier: 'Bronze' | 'Silver' | 'Gold' | 'Platinum';
  color: string;
  icon: string;
  nextTier: string | null;
  pointsToNextTier: number;
} {
  if (totalPointsEarned >= 1000) {
    return {
      tier: 'Platinum',
      color: '#E5E4E2',
      icon: '💎',
      nextTier: null,
      pointsToNextTier: 0
    };
  } else if (totalPointsEarned >= 500) {
    return {
      tier: 'Gold',
      color: '#FFD700',
      icon: '🏆',
      nextTier: 'Platinum',
      pointsToNextTier: 1000 - totalPointsEarned
    };
  } else if (totalPointsEarned >= 200) {
    return {
      tier: 'Silver',
      color: '#C0C0C0',
      icon: '🥈',
      nextTier: 'Gold',
      pointsToNextTier: 500 - totalPointsEarned
    };
  } else {
    return {
      tier: 'Bronze',
      color: '#CD7F32',
      icon: '🥉',
      nextTier: 'Silver',
      pointsToNextTier: 200 - totalPointsEarned
    };
  }
}
