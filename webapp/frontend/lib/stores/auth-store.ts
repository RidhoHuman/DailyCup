import { create } from "zustand";
import { persist, createJSONStorage } from "zustand/middleware";
import { useState, useEffect } from "react";

export interface User {
  id: string;
  name: string;
  email: string;
  phone?: string;
  address?: string;
  loyaltyPoints: number;
  role: "customer" | "admin";
  profilePicture?: string;
  joinDate: string;
}

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  _hasHydrated: boolean;
  
  // Actions
  login: (user: User, token: string) => void;
  logout: () => void;
  updateUser: (userData: Partial<User>) => void;
  setLoading: (loading: boolean) => void;
  addLoyaltyPoints: (points: number) => void;
  redeemPoints: (points: number) => boolean;
  setHasHydrated: (state: boolean) => void;
}

export const useAuthStore = create<AuthState>()(
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

      login: (user, token) => {
        set({
          user,
          token,
          isAuthenticated: true,
          isLoading: false,
        });
        // Backwards-compatibility: mirror token to top-level localStorage key `token`
        if (typeof window !== 'undefined') {
          try {
            localStorage.setItem('token', String(token));
          } catch (e) { /* ignore */ }
        }
      },

      logout: () => {
        set({
          user: null,
          token: null,
          isAuthenticated: false,
        });
        // Clear any other persisted data if needed
        if (typeof window !== "undefined") {
          try { localStorage.removeItem('token'); } catch(e) {}
          localStorage.removeItem("dailycup-wishlist");
          localStorage.removeItem("dailycup-recently-viewed");
        }
      },

      updateUser: (userData) => {
        const currentUser = get().user;
        if (currentUser) {
          set({
            user: { ...currentUser, ...userData },
          });
        }
      },

      setLoading: (loading) => {
        set({ isLoading: loading });
      },

      addLoyaltyPoints: (points) => {
        const currentUser = get().user;
        if (currentUser) {
          set({
            user: {
              ...currentUser,
              loyaltyPoints: currentUser.loyaltyPoints + points,
            },
          });
        }
      },

      redeemPoints: (points) => {
        const currentUser = get().user;
        if (currentUser && currentUser.loyaltyPoints >= points) {
          set({
            user: {
              ...currentUser,
              loyaltyPoints: currentUser.loyaltyPoints - points,
            },
          });
          return true;
        }
        return false;
      },
    }),
    {
      name: "dailycup-auth",
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
      onRehydrateStorage: () => (state) => {
        state?.setHasHydrated(true);        // Mirror token into legacy `token` key so older helpers/scripts still work
        try {
          const t = state?.token;
          if (typeof window !== 'undefined' && t) {
            localStorage.setItem('token', String(t));
          }
        } catch (e) { /* ignore */ }      },
    }
  )
);

// Custom hook to wait for auth hydration
export function useAuthHydration() {
  const hasHydrated = useAuthStore((state) => state._hasHydrated);
  const [isHydrated, setIsHydrated] = useState(hasHydrated);

  useEffect(() => {
    // Subscribe to hydration state changes
    const unsubscribe = useAuthStore.subscribe(
      (state) => {
        if (state._hasHydrated) {
          setIsHydrated(true);
        }
      }
    );

    // Check if already hydrated
    if (useAuthStore.getState()._hasHydrated) {
      setIsHydrated(true);
    }

    return () => unsubscribe();
  }, []);

  return isHydrated;
}
