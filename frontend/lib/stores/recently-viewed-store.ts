import { create } from "zustand";
import { persist, createJSONStorage } from "zustand/middleware";

export interface RecentlyViewedItem {
  id: string;
  name: string;
  price: number;
  image?: string;
  category?: string;
  viewedAt: string;
}

interface RecentlyViewedState {
  items: RecentlyViewedItem[];
  maxItems: number;
  
  // Actions
  addItem: (item: Omit<RecentlyViewedItem, "viewedAt">) => void;
  clearHistory: () => void;
  getItems: (limit?: number) => RecentlyViewedItem[];
}

export const useRecentlyViewedStore = create<RecentlyViewedState>()(
  persist(
    (set, get) => ({
      items: [],
      maxItems: 20, // Keep last 20 viewed items

      addItem: (item) => {
        set((state) => {
          // Remove if already exists (to move to top)
          const filtered = state.items.filter((i) => i.id !== item.id);
          
          // Add to beginning
          const newItems = [
            { ...item, viewedAt: new Date().toISOString() },
            ...filtered,
          ].slice(0, state.maxItems); // Keep only maxItems
          
          return { items: newItems };
        });
      },

      clearHistory: () => {
        set({ items: [] });
      },

      getItems: (limit) => {
        const items = get().items;
        return limit ? items.slice(0, limit) : items;
      },
    }),
    {
      name: "dailycup-recently-viewed",
      storage: createJSONStorage(() => localStorage),
    }
  )
);
