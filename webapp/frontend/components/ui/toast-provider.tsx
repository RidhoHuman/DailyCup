"use client";

import { Toaster, toast } from "sonner";
import { useUIStore } from "@/lib/stores/ui-store";

export function ToastProvider() {
  const theme = useUIStore((state) => state.theme);
  
  return (
    <Toaster
      position="top-right"
      expand={false}
      richColors
      closeButton
      theme={theme === "system" ? undefined : theme}
      toastOptions={{
        duration: 4000,
        style: {
          background: "var(--toast-bg, white)",
          color: "var(--toast-color, #333)",
          border: "1px solid var(--toast-border, #e5e7eb)",
        },
      }}
    />
  );
}

// Export toast helper functions
export const showToast = {
  success: (message: string) => toast.success(message),
  error: (message: string) => toast.error(message),
  info: (message: string) => toast.info(message),
  warning: (message: string) => toast.warning(message),
  loading: (message: string) => toast.loading(message),
  promise: <T,>(
    promise: Promise<T>,
    messages: { loading: string; success: string; error: string }
  ) => toast.promise(promise, messages),
};

// Re-export toast function for direct use
export { toast };
