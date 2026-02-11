'use client';

import { Star } from 'lucide-react';

interface StarRatingProps {
  rating: number;
  maxRating?: number;
  size?: 'sm' | 'md' | 'lg';
  showValue?: boolean;
  interactive?: boolean;
  onChange?: (rating: number) => void;
}

export default function StarRating({
  rating,
  maxRating = 5,
  size = 'md',
  showValue = false,
  interactive = false,
  onChange
}: StarRatingProps) {
  const sizeClasses = {
    sm: 'w-4 h-4',
    md: 'w-5 h-5',
    lg: 'w-6 h-6'
  };

  const handleClick = (value: number) => {
    if (interactive && onChange) {
      onChange(value);
    }
  };

  const renderStars = () => {
    const stars = [];
    for (let i = 1; i <= maxRating; i++) {
      const isFilled = i <= Math.floor(rating);
      const isHalf = i === Math.ceil(rating) && rating % 1 !== 0;

      stars.push(
        <button
          key={i}
          type="button"
          onClick={() => handleClick(i)}
          disabled={!interactive}
          className={`relative ${interactive ? 'cursor-pointer hover:scale-110 transition-transform' : 'cursor-default'}`}
          aria-label={`Rate ${i} stars`}
        >
          {isHalf ? (
            <div className="relative">
              {/* Background empty star */}
              <Star className={`${sizeClasses[size]} text-gray-300`} />
              {/* Half-filled star overlay */}
              <div className="absolute top-0 left-0 overflow-hidden" style={{ width: '50%' }}>
                <Star className={`${sizeClasses[size]} fill-yellow-400 text-yellow-400`} />
              </div>
            </div>
          ) : (
            <Star
              className={`${sizeClasses[size]} ${
                isFilled
                  ? 'fill-yellow-400 text-yellow-400'
                  : interactive
                  ? 'text-gray-300 hover:text-yellow-400'
                  : 'text-gray-300'
              }`}
            />
          )}
        </button>
      );
    }
    return stars;
  };

  return (
    <div className="flex items-center gap-1">
      {renderStars()}
      {showValue && (
        <span className="ml-2 text-sm font-medium text-gray-700">
          {rating.toFixed(1)}
        </span>
      )}
    </div>
  );
}
