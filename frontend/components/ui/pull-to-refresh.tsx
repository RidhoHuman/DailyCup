'use client';

import { useState, useRef, useCallback, ReactNode, TouchEvent } from 'react';

interface PullToRefreshProps {
  children: ReactNode;
  onRefresh: () => Promise<void>;
  threshold?: number;
  maxPull?: number;
  disabled?: boolean;
  className?: string;
  refreshingText?: string;
  pullingText?: string;
  releaseText?: string;
}

export function PullToRefresh({
  children,
  onRefresh,
  threshold = 80,
  maxPull = 120,
  disabled = false,
  className,
  refreshingText = 'Refreshing...',
  pullingText = 'Pull to refresh',
  releaseText = 'Release to refresh',
}: PullToRefreshProps) {
  const [pullDistance, setPullDistance] = useState(0);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const startY = useRef(0);
  const containerRef = useRef<HTMLDivElement>(null);

  const canPull = useCallback(() => {
    if (disabled || isRefreshing) return false;
    
    // Only allow pull when scrolled to top
    const container = containerRef.current;
    if (!container) return false;
    
    // Check if we're at the top of the scroll area
    return window.scrollY === 0;
  }, [disabled, isRefreshing]);

  const handleTouchStart = useCallback(
    (e: TouchEvent) => {
      if (!canPull()) return;
      startY.current = e.touches[0].clientY;
    },
    [canPull]
  );

  const handleTouchMove = useCallback(
    (e: TouchEvent) => {
      if (!canPull() || startY.current === 0) return;

      const currentY = e.touches[0].clientY;
      const diff = currentY - startY.current;

      if (diff > 0) {
        // Apply resistance - the further you pull, the harder it gets
        const resistance = 0.5;
        const newDistance = Math.min(diff * resistance, maxPull);
        setPullDistance(newDistance);
      }
    },
    [canPull, maxPull]
  );

  const handleTouchEnd = useCallback(async () => {
    if (pullDistance === 0) return;

    if (pullDistance >= threshold) {
      setIsRefreshing(true);
      setPullDistance(threshold * 0.5); // Keep some distance while refreshing
      
      try {
        await onRefresh();
      } finally {
        setIsRefreshing(false);
        setPullDistance(0);
      }
    } else {
      setPullDistance(0);
    }
    
    startY.current = 0;
  }, [pullDistance, threshold, onRefresh]);

  const progress = Math.min((pullDistance / threshold) * 100, 100);
  const shouldTrigger = pullDistance >= threshold;

  return (
    <div
      ref={containerRef}
      className={className}
      onTouchStart={handleTouchStart}
      onTouchMove={handleTouchMove}
      onTouchEnd={handleTouchEnd}
      style={{ touchAction: pullDistance > 0 ? 'none' : 'auto' }}
    >
      {/* Pull indicator */}
      <div
        className="flex flex-col items-center justify-end overflow-hidden transition-all duration-200"
        style={{ 
          height: pullDistance,
          opacity: pullDistance > 0 ? 1 : 0,
        }}
      >
        <div className="flex flex-col items-center py-2">
          {isRefreshing ? (
            <svg
              className="animate-spin h-6 w-6 text-amber-600"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              />
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
              />
            </svg>
          ) : (
            <svg
              className={`h-6 w-6 text-amber-600 transition-transform duration-200 ${
                shouldTrigger ? 'rotate-180' : ''
              }`}
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M19 14l-7 7m0 0l-7-7m7 7V3"
              />
            </svg>
          )}
          <span className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {isRefreshing
              ? refreshingText
              : shouldTrigger
              ? releaseText
              : pullingText}
          </span>
          {!isRefreshing && (
            <div className="w-16 h-1 bg-gray-200 dark:bg-gray-700 rounded-full mt-2 overflow-hidden">
              <div
                className="h-full bg-amber-500 rounded-full transition-all duration-100"
                style={{ width: `${progress}%` }}
              />
            </div>
          )}
        </div>
      </div>

      {/* Content */}
      <div
        style={{
          transform: pullDistance > 0 ? `translateY(${Math.max(0, pullDistance - threshold * 0.5)}px)` : 'none',
          transition: pullDistance === 0 ? 'transform 0.2s ease-out' : 'none',
        }}
      >
        {children}
      </div>
    </div>
  );
}
