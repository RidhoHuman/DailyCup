'use client';

import { useState, useEffect, useRef } from 'react';
import Link from 'next/link';
import { useNotificationStore, fetchNotifications, Notification } from '@/lib/stores/notification-store';
import { useAuthStore } from '@/lib/stores/auth-store';
import { formatDistanceToNow } from 'date-fns';
import { id as localeId } from 'date-fns/locale';

export default function NotificationBell() {
  const { 
    notifications, 
    unreadCount, 
    isLoading,
    hasMore,
    offset,
    setNotifications, 
    appendNotifications,
    setUnreadCount,
    markAsRead, 
    markAllAsRead,
    removeNotification,
    setLoading,
    resetPagination,
    incrementOffset,
    startPolling,
    stopPolling
  } = useNotificationStore();
  
  const { isAuthenticated } = useAuthStore();
  const [isOpen, setIsOpen] = useState(false);
  const [mounted, setMounted] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const initialLoadDone = useRef(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  // Start/stop polling based on auth state
  useEffect(() => {
    if (!mounted) return;
    
    if (isAuthenticated) {
      startPolling();
      
      // Load notifications only once on mount
      if (!initialLoadDone.current) {
        initialLoadDone.current = true;
        loadNotifications().catch((err) => {
          console.error('Initial load failed:', err);
        });
      }
    } else {
      stopPolling();
      initialLoadDone.current = false;
    }
    
    return () => {
      stopPolling();
    };
  }, [isAuthenticated, mounted]);

  useEffect(() => {
    // Reset on unmount
    return () => {
      initialLoadDone.current = false;
    };
  }, []);

  // Close dropdown when clicking outside
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }
    
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  async function loadNotifications(refresh = false) {
    if (isLoading) return;
    
    try {
      setLoading(true);
      
      if (refresh) {
        resetPagination();
      }
      
      const currentOffset = refresh ? 0 : offset;
      const result = await fetchNotifications(20, currentOffset);
      
      // Handle unauthorized (user not logged in)
      if (result?.unauthorized) {
        setLoading(false);
        return;
      }
      
      if (result?.success) {
        const { notifications: newNotifs, unread_count, pagination } = result.data;
        
        if (refresh || currentOffset === 0) {
          setNotifications(newNotifs);
        } else {
          appendNotifications(newNotifs, pagination.has_more);
        }
        
        setUnreadCount(unread_count);
        
        if (!refresh) {
          incrementOffset(newNotifs.length);
        }
      }
    } catch (error) {
      console.error('Load notifications error:', error);
    } finally {
      setLoading(false);
    }
  }

  function handleNotificationClick(notification: Notification) {
    if (!notification.is_read) {
      markAsRead(notification.id);
    }
    setIsOpen(false);
  }

  function getIconClass(icon: string): string {
    const iconMap: Record<string, string> = {
      'cart-check': 'bi-cart-check',
      'credit-card-2-front': 'bi-credit-card-2-front',
      'box-seam': 'bi-box-seam',
      'truck': 'bi-truck',
      'house-check': 'bi-house-check',
      'x-circle': 'bi-x-circle',
      'tag': 'bi-tag',
      'bell': 'bi-bell',
      'star': 'bi-star',
    };
    return iconMap[icon] || 'bi-bell';
  }

  function getTimeAgo(dateStr: string): string {
    try {
      const date = new Date(dateStr);
      return formatDistanceToNow(date, { addSuffix: true, locale: localeId });
    } catch {
      return dateStr;
    }
  }

  if (!mounted || !isAuthenticated) {
    return null;
  }

  return (
    <div className="relative" ref={dropdownRef}>
      {/* Bell Button */}
      <button
        onClick={() => {
          setIsOpen(!isOpen);
          if (!isOpen) loadNotifications(true);
        }}
        className="relative p-2 text-gray-700 hover:text-[#a97456] dark:text-gray-300 dark:hover:text-[#a97456] transition-colors"
        aria-label="Notifications"
      >
        <i className="bi bi-bell text-xl"></i>
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-[20px] h-5 flex items-center justify-center px-1 animate-pulse">
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}
      </button>

      {/* Dropdown */}
      {isOpen && (
        <div className="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-[#2a2a2a] rounded-xl shadow-2xl border border-gray-100 dark:border-gray-700 z-50 animate-in fade-in zoom-in-95 duration-200 max-h-[80vh] overflow-hidden flex flex-col">
          {/* Header */}
          <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-[#a97456]/10 to-transparent">
            <h3 className="font-semibold text-gray-900 dark:text-white">
              Notifikasi
              {unreadCount > 0 && (
                <span className="ml-2 text-xs bg-[#a97456] text-white px-2 py-0.5 rounded-full">
                  {unreadCount} baru
                </span>
              )}
            </h3>
            {unreadCount > 0 && (
              <button
                onClick={() => markAllAsRead()}
                className="text-xs text-[#a97456] hover:underline"
              >
                Tandai semua dibaca
              </button>
            )}
          </div>

          {/* Notification List */}
          <div className="overflow-y-auto flex-1 max-h-96">
            {isLoading && notifications.length === 0 ? (
              <div className="p-8 text-center text-gray-500 dark:text-gray-400">
                <i className="bi bi-arrow-clockwise animate-spin text-2xl mb-2 block"></i>
                Memuat...
              </div>
            ) : notifications.length === 0 ? (
              <div className="p-8 text-center text-gray-500 dark:text-gray-400">
                <i className="bi bi-bell-slash text-4xl mb-2 block opacity-50"></i>
                <p>Belum ada notifikasi</p>
              </div>
            ) : (
              <>
                {notifications.map((notif) => (
                  <div
                    key={notif.id}
                    className={`relative group border-b border-gray-50 dark:border-gray-700/50 last:border-0 transition-colors ${
                      notif.is_read 
                        ? 'bg-white dark:bg-[#2a2a2a]' 
                        : 'bg-[#a97456]/5 dark:bg-[#a97456]/10'
                    }`}
                  >
                    {notif.action_url ? (
                      <Link
                        href={notif.action_url}
                        onClick={() => handleNotificationClick(notif)}
                        className="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                      >
                        <NotificationContent 
                          notification={notif} 
                          getIconClass={getIconClass}
                          getTimeAgo={getTimeAgo}
                        />
                      </Link>
                    ) : (
                      <div 
                        className="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                        onClick={() => handleNotificationClick(notif)}
                      >
                        <NotificationContent 
                          notification={notif} 
                          getIconClass={getIconClass}
                          getTimeAgo={getTimeAgo}
                        />
                      </div>
                    )}
                    
                    {/* Delete button */}
                    <button
                      onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        removeNotification(notif.id);
                      }}
                      className="absolute top-2 right-2 p-1.5 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                      title="Hapus notifikasi"
                    >
                      <i className="bi bi-x text-lg"></i>
                    </button>
                    
                    {/* Unread indicator */}
                    {!notif.is_read && (
                      <div className="absolute left-1.5 top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-[#a97456]"></div>
                    )}
                  </div>
                ))}
                
                {/* Load more button */}
                {hasMore && (
                  <button
                    onClick={() => loadNotifications()}
                    disabled={isLoading}
                    className="w-full py-3 text-sm text-[#a97456] hover:bg-gray-50 dark:hover:bg-gray-700/50 disabled:opacity-50"
                  >
                    {isLoading ? (
                      <><i className="bi bi-arrow-clockwise animate-spin mr-1"></i> Memuat...</>
                    ) : (
                      'Muat lebih banyak'
                    )}
                  </button>
                )}
              </>
            )}
          </div>

          {/* Footer */}
          <div className="px-4 py-2 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <Link
              href="/profile?tab=notifications"
              onClick={() => setIsOpen(false)}
              className="text-sm text-center text-[#a97456] hover:underline block"
            >
              Lihat semua notifikasi
            </Link>
          </div>
        </div>
      )}
    </div>
  );
}

// Separate component for notification content
function NotificationContent({ 
  notification, 
  getIconClass, 
  getTimeAgo 
}: { 
  notification: Notification;
  getIconClass: (icon: string) => string;
  getTimeAgo: (date: string) => string;
}) {
  return (
    <div className="flex gap-3 pl-3">
      <div className={`flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center ${
        notification.is_read 
          ? 'bg-gray-100 dark:bg-gray-700' 
          : 'bg-[#a97456]/20'
      }`}>
        <i className={`bi ${getIconClass(notification.icon)} text-lg ${
          notification.is_read 
            ? 'text-gray-500 dark:text-gray-400' 
            : 'text-[#a97456]'
        }`}></i>
      </div>
      <div className="flex-1 min-w-0">
        <p className={`text-sm font-medium truncate ${
          notification.is_read 
            ? 'text-gray-700 dark:text-gray-300' 
            : 'text-gray-900 dark:text-white'
        }`}>
          {notification.title}
        </p>
        <p className="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
          {notification.message}
        </p>
        <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">
          {getTimeAgo(notification.created_at)}
        </p>
      </div>
    </div>
  );
}
