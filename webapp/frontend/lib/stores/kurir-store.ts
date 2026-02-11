import { create } from "zustand";
import { persist, createJSONStorage } from "zustand/middleware";
import { useState, useEffect } from "react";

export interface KurirUser {
  id: number;
  name: string;
  phone: string;
  email?: string | null;
  photo?: string | null;
  vehicleType: string;
  vehicleNumber?: string | null;
  status: "available" | "busy" | "offline";
  rating: number;
  totalDeliveries: number;
  joinDate: string;
}

interface KurirAuthState {
  user: KurirUser | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  _hasHydrated: boolean;

  login: (user: KurirUser, token: string) => void;
  logout: () => void;
  updateUser: (data: Partial<KurirUser>) => void;
  setStatus: (status: KurirUser["status"]) => void;
  setLoading: (loading: boolean) => void;
  setHasHydrated: (state: boolean) => void;
}

export const useKurirStore = create<KurirAuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,
      _hasHydrated: false,

      setHasHydrated: (state) => {
        set({ _hasHydrated: state });
      },

      login: (user, token) => set({ user, token, isAuthenticated: true, isLoading: false }),

      logout: () => {
        set({ user: null, token: null, isAuthenticated: false });
        if (typeof window !== "undefined") {
          localStorage.removeItem("dailycup-kurir");
        }
      },

      updateUser: (data) => {
        const current = get().user;
        if (current) set({ user: { ...current, ...data } });
      },

      setStatus: (status) => {
        const current = get().user;
        if (current) set({ user: { ...current, status } });
      },

      setLoading: (loading) => set({ isLoading: loading }),
    }),
    {
      name: "dailycup-kurir",
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({ user: state.user, token: state.token, isAuthenticated: state.isAuthenticated }),
      onRehydrateStorage: () => (state) => {
        state?.setHasHydrated(true);
      },
    }
  )
);

// Custom hook to wait for kurir auth hydration
export function useKurirHydration() {
  const hasHydrated = useKurirStore((state) => state._hasHydrated);
  const [isHydrated, setIsHydrated] = useState(hasHydrated);

  useEffect(() => {
    const unsubscribe = useKurirStore.subscribe(
      (state) => {
        if (state._hasHydrated) {
          setIsHydrated(true);
        }
      }
    );

    if (useKurirStore.getState()._hasHydrated) {
      setIsHydrated(true);
    }

    return () => unsubscribe();
  }, []);

  return isHydrated;
}
