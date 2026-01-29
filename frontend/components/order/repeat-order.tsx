'use client';

import { useState } from 'react';
import Image from 'next/image';
import { cn, formatCurrency, formatDate } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { StatusBadge } from '@/components/ui/badge';
import { showToast } from '@/components/ui/toast-provider';

interface OrderItem {
  id: string;
  name: string;
  quantity: number;
  price: number;
  image?: string;
  variants?: Record<string, string>;
}

interface Order {
  id: string;
  items: OrderItem[];
  total: number;
  status: 'pending' | 'processing' | 'paid' | 'shipped' | 'delivered' | 'cancelled' | 'failed';
  createdAt: string;
  deliveryAddress?: string;
}

interface RepeatOrderButtonProps {
  order: Order;
  onRepeat: (items: OrderItem[]) => Promise<void>;
  variant?: 'icon' | 'button' | 'full';
  className?: string;
}

export function RepeatOrderButton({
  order,
  onRepeat,
  variant = 'button',
  className,
}: RepeatOrderButtonProps) {
  const [isLoading, setIsLoading] = useState(false);

  const handleRepeat = async () => {
    setIsLoading(true);
    try {
      await onRepeat(order.items);
      showToast.success(`${order.items.length} items added to cart`);
    } catch {
      showToast.error('Failed to add items to cart');
    } finally {
      setIsLoading(false);
    }
  };

  if (variant === 'icon') {
    return (
      <button
        onClick={handleRepeat}
        disabled={isLoading}
        className={cn(
          'p-2 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors disabled:opacity-50',
          className
        )}
        title="Repeat this order"
      >
        {isLoading ? (
          <svg className="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        ) : (
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        )}
      </button>
    );
  }

  if (variant === 'full') {
    return (
      <Button
        onClick={handleRepeat}
        isLoading={isLoading}
        variant="primary"
        className={cn('w-full', className)}
        leftIcon={
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        }
      >
        Repeat Order ({order.items.length} items)
      </Button>
    );
  }

  return (
    <Button
      onClick={handleRepeat}
      isLoading={isLoading}
      variant="outline"
      size="sm"
      className={className}
    >
      Repeat
    </Button>
  );
}

// Order History Card
interface OrderHistoryCardProps {
  order: Order;
  onRepeat: (items: OrderItem[]) => Promise<void>;
  onViewDetails?: (orderId: string) => void;
  onTrack?: (orderId: string) => void;
  className?: string;
}

export function OrderHistoryCard({
  order,
  onRepeat,
  onViewDetails,
  onTrack,
  className,
}: OrderHistoryCardProps) {
  const canTrack = ['processing', 'paid', 'shipped'].includes(order.status);
  const previewItems = order.items.slice(0, 3);
  const remainingItems = order.items.length - 3;

  return (
    <Card variant="outlined" padding="none" className={className}>
      {/* Header */}
      <div className="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <div>
          <p className="text-sm text-gray-500 dark:text-gray-400">
            {formatDate(order.createdAt)}
          </p>
          <p className="font-medium text-gray-900 dark:text-gray-100">
            {order.id}
          </p>
        </div>
        <StatusBadge status={order.status} />
      </div>

      {/* Items preview */}
      <div className="p-4">
        <div className="space-y-3">
          {previewItems.map((item) => (
            <div key={item.id} className="flex items-center gap-3">
              <div className="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 flex-shrink-0 overflow-hidden relative">
                {item.image ? (
                  <Image
                    src={item.image}
                    alt={item.name}
                    fill
                    className="object-cover"
                    sizes="48px"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center text-xl">
                    ☕
                  </div>
                )}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                  {item.name}
                </p>
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  {item.quantity}x @ {formatCurrency(item.price)}
                </p>
              </div>
            </div>
          ))}
        </div>

        {remainingItems > 0 && (
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-2">
            +{remainingItems} more item{remainingItems > 1 ? 's' : ''}
          </p>
        )}
      </div>

      {/* Footer */}
      <div className="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        <div className="flex items-center justify-between mb-3">
          <span className="text-sm text-gray-500 dark:text-gray-400">Total</span>
          <span className="font-bold text-gray-900 dark:text-gray-100">
            {formatCurrency(order.total)}
          </span>
        </div>

        <div className="flex gap-2">
          {onViewDetails && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => onViewDetails(order.id)}
              className="flex-1"
            >
              Details
            </Button>
          )}
          
          {canTrack && onTrack && (
            <Button
              variant="outline"
              size="sm"
              onClick={() => onTrack(order.id)}
              className="flex-1"
            >
              Track
            </Button>
          )}
          
          <RepeatOrderButton
            order={order}
            onRepeat={onRepeat}
            variant="button"
            className="flex-1"
          />
        </div>
      </div>
    </Card>
  );
}

// Quick Reorder Section
interface QuickReorderProps {
  recentOrders: Order[];
  onRepeat: (items: OrderItem[]) => Promise<void>;
  className?: string;
}

export function QuickReorder({
  recentOrders,
  onRepeat,
  className,
}: QuickReorderProps) {
  if (recentOrders.length === 0) {
    return null;
  }

  // Get unique items from recent orders
  const recentItems = recentOrders
    .flatMap((order) => order.items)
    .reduce((acc, item) => {
      const existing = acc.find((i) => i.id === item.id);
      if (!existing) {
        acc.push(item);
      }
      return acc;
    }, [] as OrderItem[])
    .slice(0, 6);

  return (
    <div className={className}>
      <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
        Order Again
      </h3>
      
      <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
        {recentItems.map((item) => (
          <Card
            key={item.id}
            variant="outlined"
            padding="sm"
            hover
            className="cursor-pointer"
            onClick={() => onRepeat([item])}
          >
            <div className="flex items-center gap-2">
              <div className="w-10 h-10 rounded bg-gray-100 dark:bg-gray-700 flex-shrink-0 overflow-hidden relative">
                {item.image ? (
                  <Image
                    src={item.image}
                    alt={item.name}
                    fill
                    className="object-cover"
                    sizes="40px"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center">
                    ☕
                  </div>
                )}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                  {item.name}
                </p>
                <p className="text-xs text-amber-600">
                  {formatCurrency(item.price)}
                </p>
              </div>
            </div>
          </Card>
        ))}
      </div>
    </div>
  );
}
