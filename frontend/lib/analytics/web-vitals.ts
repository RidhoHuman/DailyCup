/**
 * Web Vitals Monitoring
 * 
 * Tracks Core Web Vitals for performance monitoring
 * Free to use - data can be sent to GA4 or any analytics service
 */

type WebVitalMetric = {
  name: 'CLS' | 'FCP' | 'FID' | 'INP' | 'LCP' | 'TTFB';
  value: number;
  rating: 'good' | 'needs-improvement' | 'poor';
  delta: number;
  id: string;
  navigationType: 'navigate' | 'reload' | 'back-forward' | 'prerender';
};

type WebVitalHandler = (metric: WebVitalMetric) => void;

// Thresholds based on Google's recommendations
const thresholds = {
  CLS: { good: 0.1, poor: 0.25 },
  FCP: { good: 1800, poor: 3000 },
  FID: { good: 100, poor: 300 },
  INP: { good: 200, poor: 500 },
  LCP: { good: 2500, poor: 4000 },
  TTFB: { good: 800, poor: 1800 },
};

function getRating(name: WebVitalMetric['name'], value: number): WebVitalMetric['rating'] {
  const threshold = thresholds[name];
  if (value <= threshold.good) return 'good';
  if (value <= threshold.poor) return 'needs-improvement';
  return 'poor';
}

// Report to Google Analytics
function reportToGA(metric: WebVitalMetric) {
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', metric.name, {
      event_category: 'Web Vitals',
      event_label: metric.id,
      value: Math.round(metric.name === 'CLS' ? metric.value * 1000 : metric.value),
      non_interaction: true,
      metric_rating: metric.rating,
    });
  }
}

// Report to custom endpoint
async function reportToEndpoint(metric: WebVitalMetric, endpoint: string) {
  try {
    await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ...metric,
        url: window.location.href,
        userAgent: navigator.userAgent,
        timestamp: new Date().toISOString(),
      }),
      // Use keepalive to ensure the request completes even if the page unloads
      keepalive: true,
    });
  } catch (error) {
    console.warn('[WebVitals] Failed to report metric:', error);
  }
}

export function initWebVitals(options?: {
  reportToGA?: boolean;
  customEndpoint?: string;
  onMetric?: WebVitalHandler;
}) {
  const { reportToGA: shouldReportToGA = true, customEndpoint, onMetric } = options || {};

  // Dynamic import web-vitals library
  import('web-vitals').then(({ onCLS, onFCP, onINP, onLCP, onTTFB }) => {
    const handleMetric = (metric: { name: string; value: number; delta: number; id: string; navigationType: string }) => {
      const webVitalMetric: WebVitalMetric = {
        name: metric.name as WebVitalMetric['name'],
        value: metric.value,
        rating: getRating(metric.name as WebVitalMetric['name'], metric.value),
        delta: metric.delta,
        id: metric.id,
        navigationType: metric.navigationType as WebVitalMetric['navigationType'],
      };

      // Report to GA4
      if (shouldReportToGA) {
        reportToGA(webVitalMetric);
      }

      // Report to custom endpoint
      if (customEndpoint) {
        reportToEndpoint(webVitalMetric, customEndpoint);
      }

      // Custom handler
      if (onMetric) {
        onMetric(webVitalMetric);
      }

      // Log in development
      if (process.env.NODE_ENV === 'development') {
        console.log(`[WebVitals] ${metric.name}:`, {
          value: metric.value,
          rating: webVitalMetric.rating,
        });
      }
    };

    onCLS(handleMetric);
    onFCP(handleMetric);
    onINP(handleMetric);
    onLCP(handleMetric);
    onTTFB(handleMetric);
  }).catch((error) => {
    console.warn('[WebVitals] Failed to load web-vitals library:', error);
  });
}

// Performance monitoring utilities
export const performance = {
  // Measure time for an operation
  measure: async <T>(name: string, fn: () => Promise<T>): Promise<T> => {
    const start = globalThis.performance.now();
    try {
      return await fn();
    } finally {
      const duration = globalThis.performance.now() - start;
      if (process.env.NODE_ENV === 'development') {
        console.log(`[Performance] ${name}: ${duration.toFixed(2)}ms`);
      }
    }
  },

  // Mark a point in time
  mark: (name: string) => {
    if (typeof globalThis.performance !== 'undefined') {
      globalThis.performance.mark(name);
    }
  },

  // Measure between two marks
  measureBetween: (name: string, startMark: string, endMark: string) => {
    if (typeof globalThis.performance !== 'undefined') {
      try {
        globalThis.performance.measure(name, startMark, endMark);
        const entries = globalThis.performance.getEntriesByName(name);
        const lastEntry = entries[entries.length - 1];
        return lastEntry?.duration;
      } catch {
        return undefined;
      }
    }
    return undefined;
  },
};
