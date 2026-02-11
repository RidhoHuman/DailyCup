"use client";

import { QueryProvider } from "@/lib/query-client";
import { ToastProvider } from "@/components/ui/toast-provider";
import { ErrorBoundary } from "@/components/ui/error-boundary";
import { CartProvider } from "@/contexts/CartContext";
import ClientUI from "@/components/ClientUI";
import NotificationProvider from "@/components/NotificationProvider";
import { useEffect } from "react";
import { useUIStore } from "@/lib/stores/ui-store";
import { initSentry } from "@/lib/analytics/sentry";
import { initWebVitals } from "@/lib/analytics/web-vitals";
import { registerServiceWorker } from "@/lib/serviceWorker";

// Initialize Sentry error tracking
if (typeof window !== 'undefined') {
  initSentry();
}

function ThemeProvider({ children }: { children: React.ReactNode }) {
  const theme = useUIStore((state) => state.theme);

  useEffect(() => {
    const root = document.documentElement;
    
    if (theme === "system") {
      const systemDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
      root.classList.toggle("dark", systemDark);
    } else {
      root.classList.toggle("dark", theme === "dark");
    }
  }, [theme]);

  return <>{children}</>;
}

// Analytics initialization component
function AnalyticsProvider({ children }: { children: React.ReactNode }) {
  useEffect(() => {
    // Initialize Web Vitals monitoring
    initWebVitals({
      reportToGA: !!process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID,
    });

    // Register Service Worker for PWA
    registerServiceWorker();
  }, []);

  return <>{children}</>;
}

export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <ErrorBoundary>
      <QueryProvider>
        <CartProvider>
          <ThemeProvider>
            <AnalyticsProvider>
              <NotificationProvider>
                {/* Global mock-data notice for dev when API fallsback to mock */}
                <div id="__client_ui_root">
                  <ClientUI />
                </div>
                {children}
                <ToastProvider />
              </NotificationProvider>
            </AnalyticsProvider>
          </ThemeProvider>
        </CartProvider>
      </QueryProvider>
    </ErrorBoundary>
  );
}
