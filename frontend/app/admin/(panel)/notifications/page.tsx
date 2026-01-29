"use client";

import { useState, useEffect } from "react";
import { useNotificationStore, fetchNotifications, Notification } from '@/lib/stores/notification-store';
import { formatDistanceToNow } from 'date-fns';
import { id as localeId } from 'date-fns/locale';

export default function NotificationsPage() {
  const { 
    notifications: storeNotifications,
    unreadCount: storeUnreadCount,
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
    incrementOffset
  } = useNotificationStore();

  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
    loadNotifications(true);
  }, []);

  async function loadNotifications(refresh = false) {
    if (isLoading) return;
    
    try {
      setLoading(true);
      
      if (refresh) {
        resetPagination();
      }
      
      const currentOffset = refresh ? 0 : offset;
      const result = await fetchNotifications(50, currentOffset); // Load more for full page
      
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

  const notifications = storeNotifications;
  const unreadCount = storeUnreadCount;

  const getTypeColor = (icon: string) => {
    const iconMap: Record<string, string> = {
      'cart-check': 'bg-blue-100 text-blue-600',
      'credit-card-2-front': 'bg-green-100 text-green-600',
      'box-seam': 'bg-indigo-100 text-indigo-600',
      'truck': 'bg-purple-100 text-purple-600',
      'house-check': 'bg-emerald-100 text-emerald-600',
      'x-circle': 'bg-red-100 text-red-600',
      'tag': 'bg-orange-100 text-orange-600',
      'star': 'bg-yellow-100 text-yellow-600',
      'bell': 'bg-gray-100 text-gray-600',
    };
    return iconMap[icon] || 'bg-gray-100 text-gray-600';
  };

  const getIconClass = (icon: string): string => {
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
  };

  const getTimeAgo = (dateStr: string): string => {
    try {
      const date = new Date(dateStr);
      return formatDistanceToNow(date, { addSuffix: true, locale: localeId });
    } catch {
      return dateStr;
    }
  };

  const handleMarkAsRead = (id: number) => {
    markAsRead(id);
  };

  const handleMarkAllAsRead = () => {
    markAllAsRead();
  };

  const deleteNotification = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus notifikasi ini?')) {
      removeNotification(id);
    }
  };

  const clearAll = () => {
    if (confirm('Apakah Anda yakin ingin menghapus semua notifikasi? Tindakan ini tidak dapat dibatalkan.')) {
      notifications.forEach(n => removeNotification(n.id));
    }
  };

  if (!mounted) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <i className="bi bi-arrow-clockwise animate-spin text-4xl text-gray-400"></i>
      </div>
    );
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">Notifications</h1>
          <p className="text-gray-500">
            {unreadCount > 0 
              ? `You have ${unreadCount} unread notification${unreadCount > 1 ? 's' : ''}`
              : 'All caught up!'}
          </p>
        </div>
        <div className="flex gap-2">
          {unreadCount > 0 && (
            <button
              onClick={handleMarkAllAsRead}
              className="px-4 py-2 text-[#a97456] hover:bg-[#a97456] hover:text-white border border-[#a97456] rounded-lg font-medium transition-colors"
            >
              Tandai Semua Dibaca
            </button>
          )}
          {notifications.length > 0 && (
            <button
              onClick={clearAll}
              className="px-4 py-2 text-red-600 hover:bg-red-600 hover:text-white border border-red-600 rounded-lg font-medium transition-colors"
            >
              Hapus Semua
            </button>
          )}
        </div>
      </div>

      {isLoading && notifications.length === 0 ? (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
          <i className="bi bi-arrow-clockwise animate-spin text-4xl text-gray-400 mb-4"></i>
          <p className="text-gray-500">Memuat notifikasi...</p>
        </div>
      ) : (
        <div className="space-y-3">
          {notifications.length === 0 ? (
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
              <i className="bi bi-bell-slash text-6xl text-gray-300 mb-4"></i>
              <p className="text-gray-500 text-lg">Tidak ada notifikasi</p>
              <p className="text-gray-400 text-sm mt-2">Anda sudah up to date!</p>
            </div>
          ) : (
            notifications.map((notif) => (
              <div
                key={notif.id}
                className={`bg-white rounded-xl shadow-sm border border-gray-100 p-5 transition-all hover:shadow-md ${
                  !notif.is_read ? 'bg-blue-50 border-blue-200' : ''
                }`}
              >
                <div className="flex items-start gap-4">
                  {/* Icon */}
                  <div className={`w-12 h-12 rounded-full ${getTypeColor(notif.icon)} flex items-center justify-center flex-shrink-0`}>
                    <i className={`bi ${getIconClass(notif.icon)} text-xl`}></i>
                  </div>

                  {/* Content */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-4 mb-1">
                      <h3 className="font-semibold text-gray-800 flex items-center gap-2">
                        {notif.title}
                        {!notif.is_read && (
                          <span className="w-2 h-2 bg-blue-500 rounded-full"></span>
                        )}
                      </h3>
                      <span className="text-xs text-gray-500 whitespace-nowrap">{getTimeAgo(notif.created_at)}</span>
                    </div>
                    <p className="text-sm text-gray-600 mb-3">{notif.message}</p>
                    
                    {/* Actions */}
                    <div className="flex items-center gap-3">
                      {!notif.is_read ? (
                        <button
                          onClick={() => handleMarkAsRead(notif.id)}
                          className="flex items-center gap-1 text-xs text-[#a97456] hover:text-[#8f6249] font-medium"
                        >
                          <i className="bi bi-check-circle"></i>
                          Tandai Dibaca
                        </button>
                      ) : (
                        <span className="text-xs text-gray-400 flex items-center gap-1">
                          <i className="bi bi-check-circle-fill"></i>
                          Dibaca
                        </span>
                      )}
                      <button
                        onClick={() => deleteNotification(notif.id)}
                        className="flex items-center gap-1 text-xs text-red-600 hover:text-red-700 font-medium"
                      >
                        <i className="bi bi-trash"></i>
                        Hapus
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            ))
          )}

          {/* Load more button */}
          {hasMore && notifications.length > 0 && (
            <button
              onClick={() => loadNotifications()}
              disabled={isLoading}
              className="w-full py-4 bg-white rounded-xl shadow-sm border border-gray-100 text-[#a97456] hover:bg-gray-50 disabled:opacity-50 transition-colors"
            >
              {isLoading ? (
                <><i className="bi bi-arrow-clockwise animate-spin mr-2"></i>Memuat...</>
              ) : (
                'Muat Lebih Banyak'
              )}
            </button>
          )}
        </div>
      )}
    </div>
  );
}
