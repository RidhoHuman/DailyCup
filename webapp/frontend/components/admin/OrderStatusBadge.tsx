import React from 'react';
import type { OrderStatus } from '@/types/delivery';

interface OrderStatusBadgeProps {
  status: OrderStatus;
  size?: 'sm' | 'md' | 'lg';
  showIcon?: boolean;
}

const statusConfig: Record<OrderStatus, {
  label: string;
  color: string;
  bgColor: string;
  icon: string;
}> = {
  pending: {
    label: 'Menunggu',
    color: 'text-yellow-700',
    bgColor: 'bg-yellow-100',
    icon: '⏳'
  },
  confirmed: {
    label: 'Dikonfirmasi',
    color: 'text-blue-700',
    bgColor: 'bg-blue-100',
    icon: '✅'
  },
  processing: {
    label: 'Diproses',
    color: 'text-purple-700',
    bgColor: 'bg-purple-100',
    icon: '👨‍🍳'
  },
  ready: {
    label: 'Siap',
    color: 'text-indigo-700',
    bgColor: 'bg-indigo-100',
    icon: '📦'
  },
  delivering: {
    label: 'Dikirim',
    color: 'text-orange-700',
    bgColor: 'bg-orange-100',
    icon: '🚴'
  },
  completed: {
    label: 'Selesai',
    color: 'text-green-700',
    bgColor: 'bg-green-100',
    icon: '🎉'
  },
  cancelled: {
    label: 'Dibatalkan',
    color: 'text-red-700',
    bgColor: 'bg-red-100',
    icon: '❌'
  }
};

export default function OrderStatusBadge({ 
  status, 
  size = 'md',
  showIcon = true 
}: OrderStatusBadgeProps) {
  const config = statusConfig[status] || statusConfig.pending;
  
  const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-3 py-1 text-sm',
    lg: 'px-4 py-2 text-base'
  };
  
  return (
    <span 
      className={`inline-flex items-center gap-1.5 font-medium rounded-full ${config.color} ${config.bgColor} ${sizeClasses[size]}`}
    >
      {showIcon && <span>{config.icon}</span>}
      <span>{config.label}</span>
    </span>
  );
}
