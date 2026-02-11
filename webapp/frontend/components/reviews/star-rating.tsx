'use client';

import { useState } from 'react';
import Image from 'next/image';
import { cn } from '@/lib/utils';
import { formatRelativeTime } from '@/lib/utils';

// Star Rating Component
interface StarRatingProps {
  rating: number;
  maxRating?: number;
  size?: 'sm' | 'md' | 'lg';
  interactive?: boolean;
  onChange?: (rating: number) => void;
  className?: string;
}

export function StarRating({
  rating,
  maxRating = 5,
  size = 'md',
  interactive = false,
  onChange,
  className,
}: StarRatingProps) {
  const [hoverRating, setHoverRating] = useState(0);

  const sizes = {
    sm: 'w-4 h-4',
    md: 'w-5 h-5',
    lg: 'w-6 h-6',
  };

  const displayRating = hoverRating || rating;

  return (
    <div className={cn('flex items-center gap-0.5', className)}>
      {Array.from({ length: maxRating }).map((_, index) => {
        const starValue = index + 1;
        const isFilled = starValue <= displayRating;
        const isHalfFilled = !isFilled && starValue - 0.5 <= displayRating;

        return (
          <button
            key={index}
            type="button"
            disabled={!interactive}
            onClick={() => onChange?.(starValue)}
            onMouseEnter={() => interactive && setHoverRating(starValue)}
            onMouseLeave={() => interactive && setHoverRating(0)}
            className={cn(
              'transition-colors',
              interactive && 'cursor-pointer hover:scale-110 transition-transform'
            )}
            aria-label={`${starValue} stars`}
          >
            <svg
              className={cn(
                sizes[size],
                isFilled
                  ? 'text-yellow-400'
                  : isHalfFilled
                  ? 'text-yellow-400'
                  : 'text-gray-300 dark:text-gray-600'
              )}
              fill={isFilled ? 'currentColor' : 'none'}
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth={1.5}
            >
              {isHalfFilled ? (
                // Half star
                <>
                  <defs>
                    <linearGradient id={`half-${index}`}>
                      <stop offset="50%" stopColor="currentColor" />
                      <stop offset="50%" stopColor="transparent" />
                    </linearGradient>
                  </defs>
                  <path
                    fill={`url(#half-${index})`}
                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                  />
                </>
              ) : (
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                />
              )}
            </svg>
          </button>
        );
      })}
    </div>
  );
}

// Rating Summary Component
interface RatingSummaryProps {
  averageRating: number;
  totalReviews: number;
  distribution?: { stars: number; count: number }[];
  className?: string;
}

export function RatingSummary({
  averageRating,
  totalReviews,
  distribution,
  className,
}: RatingSummaryProps) {
  const defaultDistribution = distribution || [
    { stars: 5, count: 0 },
    { stars: 4, count: 0 },
    { stars: 3, count: 0 },
    { stars: 2, count: 0 },
    { stars: 1, count: 0 },
  ];

  const maxCount = Math.max(...defaultDistribution.map((d) => d.count));

  return (
    <div className={cn('flex flex-col sm:flex-row gap-6', className)}>
      {/* Average rating */}
      <div className="text-center sm:text-left">
        <div className="text-5xl font-bold text-gray-900 dark:text-gray-100">
          {averageRating.toFixed(1)}
        </div>
        <StarRating rating={averageRating} size="md" className="justify-center sm:justify-start mt-2" />
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          {totalReviews.toLocaleString()} reviews
        </p>
      </div>

      {/* Distribution bars */}
      <div className="flex-1 space-y-2">
        {defaultDistribution
          .sort((a, b) => b.stars - a.stars)
          .map(({ stars, count }) => (
            <div key={stars} className="flex items-center gap-2">
              <span className="text-sm text-gray-600 dark:text-gray-400 w-2">
                {stars}
              </span>
              <svg className="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
              <div className="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div
                  className="h-full bg-yellow-400 rounded-full transition-all duration-500"
                  style={{
                    width: `${maxCount > 0 ? (count / maxCount) * 100 : 0}%`,
                  }}
                />
              </div>
              <span className="text-sm text-gray-500 dark:text-gray-400 w-8 text-right">
                {count}
              </span>
            </div>
          ))}
      </div>
    </div>
  );
}

// Single Review Component
interface Review {
  id: string;
  author: string;
  avatar?: string;
  rating: number;
  date: string;
  content: string;
  helpful?: number;
  images?: string[];
  verified?: boolean;
}

interface ReviewCardProps {
  review: Review;
  onHelpful?: (reviewId: string) => void;
  className?: string;
}

export function ReviewCard({ review, onHelpful, className }: ReviewCardProps) {
  const [showFullContent, setShowFullContent] = useState(false);
  const maxLength = 200;
  const shouldTruncate = review.content.length > maxLength;

  return (
    <div
      className={cn(
        'border-b border-gray-200 dark:border-gray-700 py-4 last:border-0',
        className
      )}
    >
      <div className="flex items-start gap-3">
        {/* Avatar */}
        <div className="flex-shrink-0">
          {review.avatar ? (
            <Image
              src={review.avatar}
              alt={review.author}
              width={40}
              height={40}
              className="rounded-full object-cover"
            />
          ) : (
            <div className="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
              <span className="text-amber-600 font-semibold">
                {review.author.charAt(0).toUpperCase()}
              </span>
            </div>
          )}
        </div>

        <div className="flex-1 min-w-0">
          {/* Header */}
          <div className="flex items-center gap-2 flex-wrap">
            <span className="font-medium text-gray-900 dark:text-gray-100">
              {review.author}
            </span>
            {review.verified && (
              <span className="inline-flex items-center gap-1 text-xs text-green-600 bg-green-50 dark:bg-green-900/20 px-1.5 py-0.5 rounded">
                <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                </svg>
                Verified
              </span>
            )}
          </div>

          {/* Rating and date */}
          <div className="flex items-center gap-2 mt-1">
            <StarRating rating={review.rating} size="sm" />
            <span className="text-sm text-gray-500 dark:text-gray-400">
              {formatRelativeTime(review.date)}
            </span>
          </div>

          {/* Content */}
          <p className="mt-2 text-gray-700 dark:text-gray-300">
            {shouldTruncate && !showFullContent
              ? `${review.content.slice(0, maxLength)}...`
              : review.content}
          </p>
          {shouldTruncate && (
            <button
              onClick={() => setShowFullContent(!showFullContent)}
              className="text-sm text-amber-600 hover:text-amber-700 mt-1"
            >
              {showFullContent ? 'Show less' : 'Read more'}
            </button>
          )}

          {/* Images */}
          {review.images && review.images.length > 0 && (
            <div className="flex gap-2 mt-3 overflow-x-auto">
              {review.images.map((img, idx) => (
                <Image
                  key={idx}
                  src={img}
                  alt={`Review image ${idx + 1}`}
                  width={64}
                  height={64}
                  className="rounded-lg object-cover flex-shrink-0"
                />
              ))}
            </div>
          )}

          {/* Helpful button */}
          {onHelpful && (
            <button
              onClick={() => onHelpful(review.id)}
              className="flex items-center gap-1 mt-3 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
            >
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
              </svg>
              Helpful {review.helpful ? `(${review.helpful})` : ''}
            </button>
          )}
        </div>
      </div>
    </div>
  );
}

// Reviews List Component
interface ReviewsListProps {
  reviews: Review[];
  onHelpful?: (reviewId: string) => void;
  className?: string;
}

export function ReviewsList({ reviews, onHelpful, className }: ReviewsListProps) {
  if (reviews.length === 0) {
    return (
      <div className="text-center py-8">
        <p className="text-gray-500 dark:text-gray-400">No reviews yet. Be the first to review!</p>
      </div>
    );
  }

  return (
    <div className={className}>
      {reviews.map((review) => (
        <ReviewCard key={review.id} review={review} onHelpful={onHelpful} />
      ))}
    </div>
  );
}
