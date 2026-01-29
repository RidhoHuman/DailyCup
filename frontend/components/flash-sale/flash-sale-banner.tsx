'use client';

import { useState, useEffect, useSyncExternalStore, useCallback } from 'react';
import Image from 'next/image';
import { cn, formatCurrency } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';

interface FlashSaleProduct {
  id: string;
  name: string;
  image?: string;
  originalPrice: number;
  salePrice: number;
  stock: number;
  sold: number;
}

interface FlashSaleBannerProps {
  title?: string;
  endTime: Date | string;
    products: FlashSaleProduct[];
  onProductClick?: (productId: string) => void;
  className?: string;
}

// Hydration-safe mounting helper
const emptySubscribe = () => () => {};
const getSnapshot = () => true;
const getServerSnapshot = () => false;

export function FlashSaleBanner({
  title = '⚡ Flash Sale',
  endTime,
  products,
  onProductClick,
  className,
}: FlashSaleBannerProps) {
  const [timeLeft, setTimeLeft] = useState({ hours: 0, minutes: 0, seconds: 0, isExpired: false });
  const mounted = useSyncExternalStore(emptySubscribe, getSnapshot, getServerSnapshot);

  const calculateTimeLeft = useCallback(() => {
    const end = new Date(endTime).getTime();
    const now = Date.now();
    const diff = Math.max(0, end - now);

    return {
      hours: Math.floor(diff / (1000 * 60 * 60)),
      minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)),
      seconds: Math.floor((diff % (1000 * 60)) / 1000),
      isExpired: diff <= 0,
    };
  }, [endTime]);

  useEffect(() => {
    if (!mounted) return;
    
    // Use timeout to avoid synchronous setState
    const initialTimeout = setTimeout(() => {
      setTimeLeft(calculateTimeLeft());
    }, 0);
    
    const timer = setInterval(() => {
      setTimeLeft(calculateTimeLeft());
    }, 1000);

    return () => {
      clearTimeout(initialTimeout);
      clearInterval(timer);
    };
  }, [mounted, calculateTimeLeft]);

  if (!mounted) {
    return null; // Prevent hydration mismatch
  }

  if (timeLeft.isExpired) {
    return null;
  }

  return (
    <div
      className={cn(
        'bg-gradient-to-r from-red-500 to-orange-500 rounded-2xl overflow-hidden',
        className
      )}
    >
      {/* Header */}
      <div className="p-4 flex items-center justify-between">
        <h2 className="text-xl font-bold text-white">{title}</h2>
        
        {/* Countdown */}
        <div className="flex items-center gap-1">
          <span className="text-white/80 text-sm mr-2">Ends in</span>
          {[
            { value: timeLeft.hours, label: 'H' },
            { value: timeLeft.minutes, label: 'M' },
            { value: timeLeft.seconds, label: 'S' },
          ].map((item, index) => (
            <div key={index} className="flex items-center">
              <div className="bg-white/20 backdrop-blur rounded px-2 py-1">
                <span className="text-white font-mono font-bold text-lg">
                  {String(item.value).padStart(2, '0')}
                </span>
              </div>
              {index < 2 && (
                <span className="text-white font-bold mx-0.5">:</span>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Products scroll */}
      <div className="px-4 pb-4 overflow-x-auto">
        <div className="flex gap-3">
          {products.map((product) => {
            const discount = Math.round(
              ((product.originalPrice - product.salePrice) / product.originalPrice) * 100
            );
            const stockPercent = Math.round(
              (product.sold / (product.stock + product.sold)) * 100
            );

            return (
              <div
                key={product.id}
                onClick={() => onProductClick?.(product.id)}
                className="flex-shrink-0 w-32 bg-white dark:bg-gray-800 rounded-xl overflow-hidden cursor-pointer hover:shadow-lg transition-shadow"
              >
                {/* Image */}
                <div className="relative aspect-square bg-gray-100 dark:bg-gray-700">
                  {product.image ? (
                    <Image
                      src={product.image}
                      alt={product.name}
                      fill
                      className="object-cover"
                      sizes="128px"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-4xl">
                      ☕
                    </div>
                  )}
                  
                  {/* Discount badge */}
                  <Badge
                    variant="danger"
                    size="sm"
                    className="absolute top-1 left-1"
                  >
                    -{discount}%
                  </Badge>
                </div>

                {/* Info */}
                <div className="p-2">
                  <p className="text-xs text-gray-600 dark:text-gray-400 truncate">
                    {product.name}
                  </p>
                  <p className="font-bold text-red-600 text-sm">
                    {formatCurrency(product.salePrice)}
                  </p>
                  <p className="text-xs text-gray-400 line-through">
                    {formatCurrency(product.originalPrice)}
                  </p>

                  {/* Stock bar */}
                  <div className="mt-1.5">
                    <div className="h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                      <div
                        className={cn(
                          'h-full rounded-full transition-all',
                          stockPercent > 80
                            ? 'bg-red-500'
                            : stockPercent > 50
                            ? 'bg-orange-500'
                            : 'bg-green-500'
                        )}
                        style={{ width: `${stockPercent}%` }}
                      />
                    </div>
                    <p className="text-[10px] text-gray-500 mt-0.5">
                      {product.sold} sold
                    </p>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}

// Countdown Timer Component (standalone)
interface CountdownTimerProps {
  endTime: Date | string;
  onExpire?: () => void;
  size?: 'sm' | 'md' | 'lg';
  variant?: 'default' | 'danger' | 'dark';
  className?: string;
}

export function CountdownTimer({
  endTime,
  onExpire,
  size = 'md',
  variant = 'default',
  className,
}: CountdownTimerProps) {
  const [timeLeft, setTimeLeft] = useState({
    days: 0,
    hours: 0,
    minutes: 0,
    seconds: 0,
    isExpired: false,
  });

  useEffect(() => {
    function calculate() {
      const end = new Date(endTime).getTime();
      const now = Date.now();
      const diff = Math.max(0, end - now);

      const isExpired = diff <= 0;
      if (isExpired && onExpire) {
        onExpire();
      }

      return {
        days: Math.floor(diff / (1000 * 60 * 60 * 24)),
        hours: Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
        minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)),
        seconds: Math.floor((diff % (1000 * 60)) / 1000),
        isExpired,
      };
    }

    // Run immediately in effect callback (not synchronously)
    const initialTimeout = setTimeout(() => {
      setTimeLeft(calculate());
    }, 0);
    
    const timer = setInterval(() => setTimeLeft(calculate()), 1000);
    
    return () => {
      clearTimeout(initialTimeout);
      clearInterval(timer);
    };
  }, [endTime, onExpire]);

  if (timeLeft.isExpired) {
    return (
      <span className={cn('text-red-500 font-medium', className)}>
        Expired
      </span>
    );
  }

  const sizes = {
    sm: 'text-sm gap-1',
    md: 'text-base gap-2',
    lg: 'text-xl gap-3',
  };

  const variants = {
    default: 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100',
    danger: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    dark: 'bg-gray-900 text-white',
  };

  const units = [
    { value: timeLeft.days, label: 'd' },
    { value: timeLeft.hours, label: 'h' },
    { value: timeLeft.minutes, label: 'm' },
    { value: timeLeft.seconds, label: 's' },
  ].filter((u, i) => i > 0 || u.value > 0); // Hide days if 0

  return (
    <div className={cn('flex items-center', sizes[size], className)}>
      {units.map((unit, index) => (
        <div key={index} className="flex items-center">
          <span
            className={cn(
              'font-mono font-bold rounded px-1.5 py-0.5',
              variants[variant]
            )}
          >
            {String(unit.value).padStart(2, '0')}
            <span className="text-xs font-normal opacity-70">{unit.label}</span>
          </span>
          {index < units.length - 1 && (
            <span className="mx-0.5 opacity-50">:</span>
          )}
        </div>
      ))}
    </div>
  );
}
