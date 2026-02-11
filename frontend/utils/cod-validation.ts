/**
 * Smart COD (Cash on Delivery) Validation
 * Implements business logic to prevent COD fraud
 * COD is only available for users with at least 1 successful cashless payment
 */

import api from '@/lib/api-client';

export interface CodEligibility {
  isEligible: boolean;
  reason: string;
  successfulOrders: number;
  requirementMet: boolean;
}

/**
 * Check if user is eligible for COD payment method
 * Business Rule: COD only available after 1 successful cashless order
 * 
 * @param userId User ID to check
 * @returns Promise with eligibility status
 */
export async function checkCodEligibility(userId: number | null): Promise<CodEligibility> {
  // If no user (guest checkout), COD not allowed
  if (!userId) {
    return {
      isEligible: false,
      reason: 'Please login or register to use COD payment',
      successfulOrders: 0,
      requirementMet: false
    };
  }

  try {
    // Fetch user's order history
    const response = await api.get<{
      success: boolean;
      orders?: Array<{
        payment_status: string;
        payment_method: string;
      }>;
    }>(`/orders.php?user_id=${userId}`);

    if (!response.success || !response.orders) {
      // Default to not eligible if can't fetch orders
      return {
        isEligible: false,
        reason: 'Unable to verify order history',
        successfulOrders: 0,
        requirementMet: false
      };
    }

    // Count successful cashless orders (paid orders with non-COD payment)
    const successfulCashlessOrders = response.orders.filter(
      order => 
        order.payment_status === 'paid' && 
        order.payment_method !== 'cod'
    ).length;

    // COD eligible if user has at least 1 successful cashless order
    const isEligible = successfulCashlessOrders >= 1;

    return {
      isEligible,
      reason: isEligible
        ? 'COD payment available'
        : 'COD will be available after your first successful cashless payment. This helps us prevent fraud and ensure better service for everyone.',
      successfulOrders: successfulCashlessOrders,
      requirementMet: isEligible
    };
  } catch (error) {
    console.error('Error checking COD eligibility:', error);
    
    // On error, default to not eligible for safety
    return {
      isEligible: false,
      reason: 'Unable to verify COD eligibility. Please try again or contact support.',
      successfulOrders: 0,
      requirementMet: false
    };
  }
}

/**
 * Get COD eligibility explanation text for UI
 * @param eligibility CodEligibility object
 * @returns User-friendly explanation text
 */
export function getCodEligibilityMessage(eligibility: CodEligibility): {
  title: string;
  message: string;
  type: 'success' | 'warning' | 'info';
} {
  if (eligibility.isEligible) {
    return {
      title: '✅ COD Available',
      message: 'You can now use Cash on Delivery payment method.',
      type: 'success'
    };
  }

  if (eligibility.successfulOrders === 0) {
    return {
      title: '🔒 COD Not Available Yet',
      message: 'Complete your first order with online payment to unlock COD. This helps us maintain service quality and prevent fraud.',
      type: 'info'
    };
  }

  return {
    title: '⚠️ COD Unavailable',
    message: eligibility.reason,
    type: 'warning'
  };
}

/**
 * Validate COD availability for current order
 * Can include additional checks like order amount limit, delivery area, etc.
 * 
 * @param userId User ID
 * @param orderTotal Total order amount
 * @param deliveryMethod Delivery method (instant/pickup)
 * @returns Promise with detailed COD validation result
 */
export async function validateCodForOrder(
  userId: number | null,
  orderTotal: number,
  deliveryMethod: 'instant' | 'pickup'
): Promise<{
  allowed: boolean;
  reasons: string[];
}> {
  const reasons: string[] = [];

  // Check basic eligibility
  const eligibility = await checkCodEligibility(userId);
  if (!eligibility.isEligible) {
    reasons.push(eligibility.reason);
  }

  // Additional business rules can be added here:
  
  // Example: COD limit per order
  const COD_MAX_AMOUNT = 500000; // Rp 500,000
  if (orderTotal > COD_MAX_AMOUNT) {
    reasons.push(`COD is not available for orders above Rp ${COD_MAX_AMOUNT.toLocaleString('id-ID')}`);
  }

  // Example: COD not available for pickup
  // if (deliveryMethod === 'pickup') {
  //   reasons.push('COD is only available for delivery orders');
  // }

  return {
    allowed: reasons.length === 0,
    reasons
  };
}
