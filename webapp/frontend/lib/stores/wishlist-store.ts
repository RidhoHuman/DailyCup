import { create } from "zustand";
import { persist, createJSONStorage } from "zustand/middleware";

export interface WishlistItem {
  id: string;
  name: string;
  price: number;
  image?: string;
  category?: string;
  addedAt: string;
}

interface WishlistState {
  items: WishlistItem[];
  
  // Actions
  addItem: (item: Omit<WishlistItem, "addedAt">) => void;
  removeItem: (id: string) => void;
  isInWishlist: (id: string) => boolean;
  clearWishlist: () => void;
  getCount: () => number;
}

export const useWishlistStore = create<WishlistState>()(
  persist(
    (set, get) => ({
      items: [],

      addItem: (item) => {
        const exists = get().items.find((i) => i.id === item.id);
        if (!exists) {
          set((state) => ({
            items: [
              ...state.items,
              { ...item, addedAt: new Date().toISOString() },
            ],
          }));
        }
      },

      removeItem: (id) => {
        set((state) => ({
          items: state.items.filter((item) => item.id !== id),
        }));
      },

      isInWishlist: (id) => {
        return get().items.some((item) => item.id === id);
      },

      clearWishlist: () => {
        set({ items: [] });
      },

      getCount: () => {
        return get().items.length;
      },
    }),
    {
      name: "dailycup-wishlist",
      storage: createJSONStorage(() => localStorage),
    }
  )
);
