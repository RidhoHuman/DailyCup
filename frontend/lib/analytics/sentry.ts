/**
 * Sentry Integration for Error Tracking
 * 
 * Sentry provides FREE tier with 5K errors/month
 * Get your DSN from https://sentry.io
 */

// Note: For full Sentry SDK, install with: npm install @sentry/nextjs
// This is a lightweight alternative that works without the full SDK

interface SentryConfig {
  dsn: string;
  environment?: string;
  release?: string;
  tracesSampleRate?: number;
}

interface SentryEvent {
  message?: string;
  level: 'fatal' | 'error' | 'warning' | 'info' | 'debug';
  exception?: {
    type: string;
    value: string;
    stacktrace?: string;
  };
  tags?: Record<string, string>;
  extra?: Record<string, unknown>;
  user?: {
    id?: string;
    email?: string;
    username?: string;
  };
  contexts?: Record<string, unknown>;
  timestamp: string;
  platform: string;
}

class LightweightSentry {
  private dsn: string | null = null;
  private config: SentryConfig | null = null;
  private user: SentryEvent['user'] = undefined;
  private tags: Record<string, string> = {};

  init(config: SentryConfig) {
    this.dsn = config.dsn;
    this.config = config;
    
    // Add global error handler
    if (typeof window !== 'undefined') {
      window.addEventListener('error', (event) => {
        this.captureException(event.error || new Error(event.message));
      });

      window.addEventListener('unhandledrejection', (event) => {
        this.captureException(event.reason);
      });
    }
  }

  setUser(user: SentryEvent['user']) {
    this.user = user;
  }

  setTag(key: string, value: string) {
    this.tags[key] = value;
  }

  setTags(tags: Record<string, string>) {
    this.tags = { ...this.tags, ...tags };
  }

  private async sendEvent(event: SentryEvent) {
    if (!this.dsn) {
      console.warn('[Sentry] DSN not configured. Event not sent:', event);
      return;
    }

    // Parse DSN to get the endpoint
    const dsnMatch = this.dsn.match(/https:\/\/(.+?)@(.+?)\/(.+)/);
    if (!dsnMatch) {
      console.error('[Sentry] Invalid DSN format');
      return;
    }

    const [, publicKey, host, projectId] = dsnMatch;
    const endpoint = `https://${host}/api/${projectId}/store/`;

    try {
      await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Sentry-Auth': `Sentry sentry_version=7, sentry_key=${publicKey}`,
        },
        body: JSON.stringify({
          ...event,
          sdk: { name: 'dailycup-sentry', version: '1.0.0' },
          environment: this.config?.environment || 'production',
          release: this.config?.release,
          user: this.user,
          tags: this.tags,
        }),
      });
    } catch (error) {
      // Silently fail - we don't want error tracking to break the app
      console.warn('[Sentry] Failed to send event:', error);
    }
  }

  captureException(error: Error, context?: { tags?: Record<string, string>; extra?: Record<string, unknown> }) {
    const event: SentryEvent = {
      level: 'error',
      exception: {
        type: error.name,
        value: error.message,
        stacktrace: error.stack,
      },
      tags: { ...this.tags, ...context?.tags },
      extra: context?.extra,
      timestamp: new Date().toISOString(),
      platform: 'javascript',
      contexts: {
        browser: typeof navigator !== 'undefined' ? {
          name: navigator.userAgent,
        } : undefined,
        os: typeof navigator !== 'undefined' ? {
          name: navigator.platform,
        } : undefined,
      },
    };

    // Log to console in development
    if (process.env.NODE_ENV === 'development') {
      console.error('[Sentry] Captured exception:', error, context);
    }

    this.sendEvent(event);
  }

  captureMessage(message: string, level: SentryEvent['level'] = 'info', context?: { tags?: Record<string, string>; extra?: Record<string, unknown> }) {
    const event: SentryEvent = {
      message,
      level,
      tags: { ...this.tags, ...context?.tags },
      extra: context?.extra,
      timestamp: new Date().toISOString(),
      platform: 'javascript',
    };

    if (process.env.NODE_ENV === 'development') {
      console.log(`[Sentry][${level}]`, message, context);
    }

    this.sendEvent(event);
  }

  // Breadcrumbs for better debugging context
  private breadcrumbs: Array<{
    category: string;
    message: string;
    timestamp: string;
    data?: Record<string, unknown>;
  }> = [];

  addBreadcrumb(breadcrumb: {
    category: string;
    message: string;
    data?: Record<string, unknown>;
  }) {
    this.breadcrumbs.push({
      ...breadcrumb,
      timestamp: new Date().toISOString(),
    });

    // Keep only last 50 breadcrumbs
    if (this.breadcrumbs.length > 50) {
      this.breadcrumbs.shift();
    }
  }

  // Performance monitoring (basic)
  startTransaction(name: string, op: string) {
    const startTime = performance.now();
    
    return {
      name,
      op,
      finish: () => {
        const duration = performance.now() - startTime;
        this.addBreadcrumb({
          category: 'transaction',
          message: `${op}: ${name}`,
          data: { duration_ms: duration },
        });
      },
    };
  }
}

// Export singleton instance
export const sentry = new LightweightSentry();

// React Error Boundary integration
export function initSentry() {
  const dsn = process.env.NEXT_PUBLIC_SENTRY_DSN;
  
  if (!dsn) {
    console.warn('[Sentry] NEXT_PUBLIC_SENTRY_DSN not configured');
    return;
  }

  sentry.init({
    dsn,
    environment: process.env.NODE_ENV,
    release: process.env.NEXT_PUBLIC_APP_VERSION,
    tracesSampleRate: 0.1, // Sample 10% of transactions
  });
}

// Note: For full Sentry error boundary integration, use @sentry/nextjs
// This lightweight implementation focuses on error capturing only
