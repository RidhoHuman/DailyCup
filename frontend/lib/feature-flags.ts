/**
 * Feature Flags System
 * 
 * This allows enabling/disabling features without deploying new code.
 * In production, these could be loaded from a remote config service.
 */

export interface FeatureFlags {
  // UI Features
  darkMode: boolean;
  newCheckoutUI: boolean;
  infiniteScroll: boolean;
  pullToRefresh: boolean;
  loadingSkeletons: boolean;
  
  // E-Commerce Features
  wishlist: boolean;
  recentlyViewed: boolean;
  productReviews: boolean;
  loyaltyRedemption: boolean;
  flashSale: boolean;
  giftCards: boolean;
  orderScheduling: boolean;
  repeatOrder: boolean;
  
  // Security Features
  jwtAuth: boolean;
  rateLimiting: boolean;
  csrfProtection: boolean;
  hmacWebhook: boolean;
  
  // Analytics
  sentryEnabled: boolean;
  analyticsEnabled: boolean;
  abTesting: boolean;
}

// Default feature flags configuration
const defaultFlags: FeatureFlags = {
  // UI Features - enabling most by default
  darkMode: true,
  newCheckoutUI: true,
  infiniteScroll: true,
  pullToRefresh: true,
  loadingSkeletons: true,
  
  // E-Commerce Features
  wishlist: true,
  recentlyViewed: true,
  productReviews: true,
  loyaltyRedemption: true,
  flashSale: true,
  giftCards: false, // Not implemented yet
  orderScheduling: true,
  repeatOrder: true,
  
  // Security Features
  jwtAuth: true,
  rateLimiting: true,
  csrfProtection: true,
  hmacWebhook: true,
  
  // Analytics
  sentryEnabled: false, // Enable when Sentry DSN is configured
  analyticsEnabled: false, // Enable when GA4 is configured
  abTesting: true,
};

// Environment-specific overrides
const envOverrides: Partial<FeatureFlags> = {
  // In development, we might want to disable some features
  sentryEnabled: process.env.NEXT_PUBLIC_SENTRY_DSN ? true : false,
  analyticsEnabled: process.env.NEXT_PUBLIC_GA_ID ? true : false,
};

// Merge defaults with environment overrides
export const featureFlags: FeatureFlags = {
  ...defaultFlags,
  ...envOverrides,
};

/**
 * Check if a feature is enabled
 */
export function isFeatureEnabled(feature: keyof FeatureFlags): boolean {
  return featureFlags[feature] ?? false;
}

/**
 * Get all enabled features
 */
export function getEnabledFeatures(): (keyof FeatureFlags)[] {
  return (Object.keys(featureFlags) as (keyof FeatureFlags)[]).filter(
    (key) => featureFlags[key]
  );
}

/**
 * A/B Testing helper
 * Assigns user to a variant based on their ID
 */
export function getABTestVariant(
  experimentName: string,
  userId?: string
): "A" | "B" {
  if (!featureFlags.abTesting) {
    return "A"; // Default to variant A if A/B testing is disabled
  }
  
  // Use userId or generate random assignment
  const id = userId || Math.random().toString();
  
  // Simple hash function to determine variant
  let hash = 0;
  const str = experimentName + id;
  for (let i = 0; i < str.length; i++) {
    const char = str.charCodeAt(i);
    hash = (hash << 5) - hash + char;
    hash = hash & hash; // Convert to 32bit integer
  }
  
  return Math.abs(hash) % 2 === 0 ? "A" : "B";
}

/**
 * React hook for A/B testing
 */
export function useABTest(experimentName: string, userId?: string): {
  variant: "A" | "B";
  isVariantA: boolean;
  isVariantB: boolean;
} {
  const variant = getABTestVariant(experimentName, userId);
  return {
    variant,
    isVariantA: variant === "A",
    isVariantB: variant === "B",
  };
}
