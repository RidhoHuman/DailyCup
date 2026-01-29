'use client';

import { useEffect } from 'react';
import Script from 'next/script';

// GA4 Types
declare global {
  interface Window {
    gtag: (
      command: 'config' | 'event' | 'js' | 'set',
      targetId: string,
      params?: Record<string, unknown>
    ) => void;
    dataLayer: unknown[];
  }
}

interface GA4ProviderProps {
  measurementId: string;
  children: React.ReactNode;
}

export function GA4Provider({ measurementId, children }: GA4ProviderProps) {
  useEffect(() => {
    // Initialize dataLayer
    window.dataLayer = window.dataLayer || [];
    window.gtag = function gtag(...args: unknown[]) {
      window.dataLayer.push(args);
    };
    window.gtag('js', new Date().toISOString());
    window.gtag('config', measurementId, {
      page_title: document.title,
      page_location: window.location.href,
    });
  }, [measurementId]);

  return (
    <>
      <Script
        src={`https://www.googletagmanager.com/gtag/js?id=${measurementId}`}
        strategy="afterInteractive"
      />
      {children}
    </>
  );
}

// Analytics Event Types
interface BaseEvent {
  event_category?: string;
  event_label?: string;
  value?: number;
}

interface PurchaseEvent extends BaseEvent {
  transaction_id: string;
  value: number;
  currency: string;
  items: Array<{
    item_id: string;
    item_name: string;
    price: number;
    quantity: number;
    item_category?: string;
  }>;
}

interface ProductEvent extends BaseEvent {
  item_id: string;
  item_name: string;
  price?: number;
  item_category?: string;
}

// Analytics Helper Functions
export const analytics = {
  // Page views are tracked automatically by GA4, but you can track virtual page views
  pageView: (path: string, title?: string) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('config', process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID || '', {
        page_path: path,
        page_title: title,
      });
    }
  },

  // E-commerce: View Product
  viewProduct: (product: ProductEvent) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'view_item', {
        items: [{
          item_id: product.item_id,
          item_name: product.item_name,
          price: product.price,
          item_category: product.item_category,
        }],
      });
    }
  },

  // E-commerce: Add to Cart
  addToCart: (product: ProductEvent & { quantity: number }) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'add_to_cart', {
        currency: 'IDR',
        value: (product.price || 0) * product.quantity,
        items: [{
          item_id: product.item_id,
          item_name: product.item_name,
          price: product.price,
          quantity: product.quantity,
          item_category: product.item_category,
        }],
      });
    }
  },

  // E-commerce: Remove from Cart
  removeFromCart: (product: ProductEvent & { quantity: number }) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'remove_from_cart', {
        currency: 'IDR',
        value: (product.price || 0) * product.quantity,
        items: [{
          item_id: product.item_id,
          item_name: product.item_name,
          price: product.price,
          quantity: product.quantity,
        }],
      });
    }
  },

  // E-commerce: Begin Checkout
  beginCheckout: (items: Array<{ id: string; name: string; price: number; quantity: number }>, total: number) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'begin_checkout', {
        currency: 'IDR',
        value: total,
        items: items.map(item => ({
          item_id: item.id,
          item_name: item.name,
          price: item.price,
          quantity: item.quantity,
        })),
      });
    }
  },

  // E-commerce: Purchase
  purchase: (data: PurchaseEvent) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'purchase', {
        transaction_id: data.transaction_id,
        value: data.value,
        currency: data.currency,
        items: data.items,
      });
    }
  },

  // E-commerce: Add to Wishlist
  addToWishlist: (product: ProductEvent) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'add_to_wishlist', {
        items: [{
          item_id: product.item_id,
          item_name: product.item_name,
          price: product.price,
        }],
      });
    }
  },

  // User Actions
  search: (searchTerm: string) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'search', {
        search_term: searchTerm,
      });
    }
  },

  login: (method: string = 'email') => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'login', {
        method,
      });
    }
  },

  signUp: (method: string = 'email') => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'sign_up', {
        method,
      });
    }
  },

  // Custom Events
  track: (eventName: string, params?: Record<string, unknown>) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', eventName, params);
    }
  },

  // A/B Testing Events
  abTestExposure: (testName: string, variant: string) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'ab_test_exposure', {
        test_name: testName,
        variant,
      });
    }
  },

  abTestConversion: (testName: string, variant: string, conversionType: string) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'ab_test_conversion', {
        test_name: testName,
        variant,
        conversion_type: conversionType,
      });
    }
  },

  // Error Tracking (simple - for complex use Sentry)
  trackError: (error: Error, context?: Record<string, unknown>) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'exception', {
        description: error.message,
        fatal: false,
        ...context,
      });
    }
  },

  // Set User Properties
  setUserProperties: (properties: Record<string, string | number | boolean>) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('set', 'user_properties', properties);
    }
  },

  // Set User ID
  setUserId: (userId: string) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('config', process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID || '', {
        user_id: userId,
      });
    }
  },
};
