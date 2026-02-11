'use client';

import { useEffect, useRef, useCallback, ReactNode } from 'react';

interface InfiniteScrollProps {
  children: ReactNode;
  loadMore: () => void;
  hasMore: boolean;
  isLoading: boolean;
  threshold?: number;
  loader?: ReactNode;
  endMessage?: ReactNode;
  className?: string;
}

export function InfiniteScroll({
  children,
  loadMore,
  hasMore,
  isLoading,
  threshold = 100,
  loader,
  endMessage,
  className,
}: InfiniteScrollProps) {
  const observerRef = useRef<IntersectionObserver | null>(null);
  const sentinelRef = useRef<HTMLDivElement>(null);

  const handleObserver = useCallback(
    (entries: IntersectionObserverEntry[]) => {
      const target = entries[0];
      if (target.isIntersecting && hasMore && !isLoading) {
        loadMore();
      }
    },
    [hasMore, isLoading, loadMore]
  );

  useEffect(() => {
    const sentinel = sentinelRef.current;
    if (!sentinel) return;

    observerRef.current = new IntersectionObserver(handleObserver, {
      root: null,
      rootMargin: `${threshold}px`,
      threshold: 0,
    });

    observerRef.current.observe(sentinel);

    return () => {
      if (observerRef.current) {
        observerRef.current.disconnect();
      }
    };
  }, [handleObserver, threshold]);

  const defaultLoader = (
    <div className="flex justify-center py-4">
      <svg
        className="animate-spin h-8 w-8 text-amber-600"
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
    </div>
  );

  const defaultEndMessage = (
    <div className="text-center py-4 text-gray-500 dark:text-gray-400">
      <p>You&apos;ve reached the end!</p>
    </div>
  );

  return (
    <div className={className}>
      {children}
      
      {/* Sentinel element for intersection observer */}
      <div ref={sentinelRef} className="h-1" />
      
      {/* Loading indicator */}
      {isLoading && (loader || defaultLoader)}
      
      {/* End message */}
      {!hasMore && !isLoading && (endMessage ?? defaultEndMessage)}
    </div>
  );
}

// Hook version for more control
export function useInfiniteScroll(
  callback: () => void,
  hasMore: boolean,
  options?: { threshold?: number; disabled?: boolean }
) {
  const { threshold = 100, disabled = false } = options || {};
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (disabled || !hasMore) return;

    const element = ref.current;
    if (!element) return;

    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting) {
          callback();
        }
      },
      {
        root: null,
        rootMargin: `${threshold}px`,
        threshold: 0,
      }
    );

    observer.observe(element);

    return () => observer.disconnect();
  }, [callback, hasMore, threshold, disabled]);

  return ref;
}
