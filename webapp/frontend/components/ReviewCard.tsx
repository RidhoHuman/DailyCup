'use client';

import { useState } from 'react';
import { ThumbsUp, ThumbsDown, CheckCircle } from 'lucide-react';
import StarRating from './StarRating';

interface Review {
  id: number;
  product_id: number;
  user_id: number;
  rating: number;
  review_title: string;
  review_text: string;
  helpful_count: number;
  verified_purchase: boolean;
  status: string;
  created_at: string;
  updated_at: string;
  user_name: string;
  user_email: string;
}

interface ReviewCardProps {
  review: Review;
  onHelpful?: (reviewId: number, isHelpful: boolean) => void;
}

export default function ReviewCard({ review, onHelpful }: ReviewCardProps) {
  const [helpfulClicked, setHelpfulClicked] = useState(false);
  const [localHelpfulCount, setLocalHelpfulCount] = useState(review.helpful_count);

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const handleHelpful = (isHelpful: boolean) => {
    if (!helpfulClicked) {
      setHelpfulClicked(true);
      if (isHelpful) {
        setLocalHelpfulCount(prev => prev + 1);
      }
      onHelpful?.(review.id, isHelpful);
    }
  };

  // Get initials for avatar
  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map(word => word[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  return (
    <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
      {/* Header */}
      <div className="flex items-start gap-4 mb-4">
        {/* Avatar */}
        <div className="flex-shrink-0">
          <div className="w-12 h-12 rounded-full bg-gradient-to-br from-coffee-600 to-coffee-800 flex items-center justify-center text-white font-semibold">
            {getInitials(review.user_name)}
          </div>
        </div>

        {/* User Info & Rating */}
        <div className="flex-1">
          <div className="flex items-center gap-2 mb-1">
            <h4 className="font-semibold text-gray-900">{review.user_name}</h4>
            {review.verified_purchase && (
              <div className="flex items-center gap-1 px-2 py-0.5 bg-green-50 text-green-700 rounded-full text-xs font-medium">
                <CheckCircle className="w-3 h-3" />
                <span>Verified Purchase</span>
              </div>
            )}
          </div>
          <div className="flex items-center gap-3">
            <StarRating rating={review.rating} size="sm" />
            <span className="text-sm text-gray-500">{formatDate(review.created_at)}</span>
          </div>
        </div>
      </div>

      {/* Review Content */}
      <div className="mb-4">
        <h5 className="font-semibold text-gray-900 mb-2">{review.review_title}</h5>
        <p className="text-gray-700 leading-relaxed whitespace-pre-wrap">{review.review_text}</p>
      </div>

      {/* Helpful Section */}
      <div className="flex items-center gap-4 pt-4 border-t border-gray-100">
        <span className="text-sm text-gray-600">Was this helpful?</span>
        <div className="flex items-center gap-2">
          <button
            onClick={() => handleHelpful(true)}
            disabled={helpfulClicked}
            className={`flex items-center gap-1 px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
              helpfulClicked
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : 'bg-gray-50 text-gray-700 hover:bg-green-50 hover:text-green-700'
            }`}
          >
            <ThumbsUp className="w-4 h-4" />
            <span>Yes</span>
            {localHelpfulCount > 0 && (
              <span className="ml-1 text-xs">({localHelpfulCount})</span>
            )}
          </button>
          <button
            onClick={() => handleHelpful(false)}
            disabled={helpfulClicked}
            className={`flex items-center gap-1 px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
              helpfulClicked
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : 'bg-gray-50 text-gray-700 hover:bg-red-50 hover:text-red-700'
            }`}
          >
            <ThumbsDown className="w-4 h-4" />
            <span>No</span>
          </button>
        </div>
      </div>
    </div>
  );
}
