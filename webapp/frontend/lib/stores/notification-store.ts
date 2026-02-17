import { create } from "zustand";

export interface Notification {
  id: number;
  type: string;
  title: string;
  message: string;
  data: Record<string, any>;
  icon: string;
  action_url: string | null;
  is_read: boolean;
  read_at: string | null;
  created_at: string;
}

interface NotificationState {
  notifications: Notification[];
  unreadCount: number;
  isLoading: boolean;
  hasMore: boolean;
  offset: number;
  pollingInterval: number | null;
  isConnected: boolean;
  
  // Actions
  setNotifications: (notifications: Notification[]) => void;
  addNotification: (notification: Notification) => void;
  appendNotifications: (notifications: Notification[], hasMore: boolean) => void;
  setUnreadCount: (count: number) => void;
  markAsRead: (id: number) => void;
  markAllAsRead: () => void;
  removeNotification: (id: number) => void;
  setLoading: (loading: boolean) => void;
  resetPagination: () => void;
  incrementOffset: (amount: number) => void;
  setConnected: (connected: boolean) => void;
  
  // Polling
  startPolling: () => void;
  stopPolling: () => void;
}

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/DailyCup/webapp/backend/api';

async function fetchWithAuth(url: string, options: RequestInit = {}) {
  const token = localStorage.getItem('dailycup-auth');
  let authToken = '';
  
  if (token) {
    try {
      const parsed = JSON.parse(token);
      authToken = parsed.state?.token || '';
    } catch {}
  }
  
  return fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'ngrok-skip-browser-warning': '69420',
      ...(authToken ? { 'Authorization': `Bearer ${authToken}` } : {}),
      ...options.headers,
    },
    credentials: 'include',
  });
}

export const useNotificationStore = create<NotificationState>((set, get) => ({
  notifications: [],
  unreadCount: 0,
  isLoading: false,
  hasMore: true,
  offset: 0,
  pollingInterval: null,
  isConnected: false,

  setNotifications: (notifications) => {
    // Deduplicate by ID
    const uniqueNotifications = notifications.filter((notif, index, self) => 
      index === self.findIndex((t) => t.id === notif.id)
    );
    set({ notifications: uniqueNotifications });
  },

  addNotification: (notification) => {
    set((state) => {
      // Check if notification already exists
      const exists = state.notifications.some(n => n.id === notification.id);
      if (exists) return state;
      
      return {
        notifications: [notification, ...state.notifications],
        unreadCount: state.unreadCount + (notification.is_read ? 0 : 1),
      };
    });
  },

  appendNotifications: (notifications, hasMore) => {
    set((state) => {
      // Get existing IDs
      const existingIds = new Set(state.notifications.map(n => n.id));
      // Filter out duplicates
      const newNotifications = notifications.filter(n => !existingIds.has(n.id));
      
      return {
        notifications: [...state.notifications, ...newNotifications],
        hasMore,
      };
    });
  },

  setUnreadCount: (count) => {
    set({ unreadCount: count });
  },

  markAsRead: (id) => {
    set((state) => ({
      notifications: state.notifications.map((n) =>
        n.id === id ? { ...n, is_read: true, read_at: new Date().toISOString() } : n
      ),
      unreadCount: Math.max(0, state.unreadCount - 1),
    }));
    
    // Call API
    fetchWithAuth(`${API_BASE_URL}/notifications/read.php`, {
      method: 'POST',
      body: JSON.stringify({ id }),
    }).catch(console.error);
  },

  markAllAsRead: () => {
    set((state) => ({
      notifications: state.notifications.map((n) => ({
        ...n,
        is_read: true,
        read_at: n.read_at || new Date().toISOString(),
      })),
      unreadCount: 0,
    }));
    
    // Call API
    fetchWithAuth(`${API_BASE_URL}/notifications/read.php`, {
      method: 'POST',
      body: JSON.stringify({ all: true }),
    }).catch(console.error);
  },

  removeNotification: (id) => {
    const notification = get().notifications.find((n) => n.id === id);
    set((state) => ({
      notifications: state.notifications.filter((n) => n.id !== id),
      unreadCount: notification && !notification.is_read 
        ? Math.max(0, state.unreadCount - 1) 
        : state.unreadCount,
    }));
    
    // Call API
    fetchWithAuth(`${API_BASE_URL}/notifications/delete.php?id=${id}`, {
      method: 'DELETE',
    }).catch(console.error);
  },

  setLoading: (loading) => {
    set({ isLoading: loading });
  },

  resetPagination: () => {
    set({ offset: 0, hasMore: true, notifications: [] });
  },

  incrementOffset: (amount) => {
    set((state) => ({ offset: state.offset + amount }));
  },

  setConnected: (connected: boolean) => {
    set({ isConnected: connected });
  },

  startPolling: () => {
    const { pollingInterval } = get();
    if (pollingInterval) return; // Already polling
    
    // Fetch immediately
    fetchUnreadCount();
    
    // Then poll every 30 seconds
    const interval = window.setInterval(fetchUnreadCount, 30000);
    set({ pollingInterval: interval });
    
    async function fetchUnreadCount() {
      try {
        const res = await fetchWithAuth(`${API_BASE_URL}/notifications/count.php`);
        if (res.ok) {
          const data = await res.json();
          set({ unreadCount: data.count || 0 });
        }
      } catch (error) {
        console.error('Failed to fetch notification count:', error);
      }
    }
  },

  stopPolling: () => {
    const { pollingInterval } = get();
    if (pollingInterval) {
      clearInterval(pollingInterval);
      set({ pollingInterval: null });
    }
  },
}));

// Helper hook for fetching notifications
export async function fetchNotifications(limit = 20, offset = 0, unreadOnly = false) {
  const params = new URLSearchParams({
    limit: String(limit),
    offset: String(offset),
    ...(unreadOnly ? { unread: '1' } : {}),
  });
  
  try {
    const res = await fetchWithAuth(`${API_BASE_URL}/notifications/get.php?${params}`);
    if (res.status === 401) {
      // Unauthorized: user not logged in or token expired
      return { success: false, unauthorized: true } as any;
    }

    if (!res.ok) throw new Error(`Failed to fetch notifications: ${res.status}`);
    return await res.json();
  } catch (error) {
    console.error('fetchNotifications error:', error);
    return null;
  }
}
