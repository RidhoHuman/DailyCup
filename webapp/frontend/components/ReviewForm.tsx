'use client';

import { useState } from 'react';
import { Star, Send, X } from 'lucide-react';
import StarRating from './StarRating';

interface ReviewFormProps {
  productId: number;
  productName: string;
  onSubmit: (reviewData: ReviewFormData) => Promise<void>;
  onCancel?: () => void;
}

export interface ReviewFormData {
  product_id: number;
  rating: number;
  review_title: string;
  review_text: string;
}

export default function ReviewForm({ productId, productName, onSubmit, onCancel }: ReviewFormProps) {
  const [rating, setRating] = useState(0);
  const [title, setTitle] = useState('');
  const [reviewText, setReviewText] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState<{ [key: string]: string }>({});

  const validateForm = () => {
    const newErrors: { [key: string]: string } = {};

    if (rating === 0) {
      newErrors.rating = 'Please select a rating';
    }

    if (!title.trim()) {
      newErrors.title = 'Please enter a review title';
    } else if (title.trim().length < 5) {
      newErrors.title = 'Title must be at least 5 characters';
    }

    if (!reviewText.trim()) {
      newErrors.reviewText = 'Please enter your review';
    } else if (reviewText.trim().length < 20) {
      newErrors.reviewText = 'Review must be at least 20 characters';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validateForm()) {
      return;
    }

    setIsSubmitting(true);

    try {
      const reviewData: ReviewFormData = {
        product_id: productId,
        rating,
        review_title: title.trim(),
        review_text: reviewText.trim()
      };

      await onSubmit(reviewData);

      // Reset form on success
      setRating(0);
      setTitle('');
      setReviewText('');
      setErrors({});
    } catch (error) {
      console.error('Error submitting review:', error);
      setErrors({ submit: 'Failed to submit review. Please try again.' });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-2xl font-bold text-gray-900">Write a Review</h3>
          <p className="text-gray-600 mt-1">Share your experience with {productName}</p>
        </div>
        {onCancel && (
          <button
            onClick={onCancel}
            className="p-2 hover:bg-gray-100 rounded-full transition-colors"
            aria-label="Close"
          >
            <X className="w-6 h-6 text-gray-500" />
          </button>
        )}
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Rating */}
        <div>
          <label className="block text-sm font-semibold text-gray-900 mb-3">
            Your Rating <span className="text-red-500">*</span>
          </label>
          <div className="flex items-center gap-2">
            <StarRating
              rating={rating}
              interactive
              onChange={setRating}
              size="lg"
            />
            {rating > 0 && (
              <span className="ml-2 text-lg font-medium text-gray-700">
                {rating} {rating === 1 ? 'Star' : 'Stars'}
              </span>
            )}
          </div>
          {errors.rating && (
            <p className="mt-2 text-sm text-red-600">{errors.rating}</p>
          )}
        </div>

        {/* Review Title */}
        <div>
          <label htmlFor="review-title" className="block text-sm font-semibold text-gray-900 mb-2">
            Review Title <span className="text-red-500">*</span>
          </label>
          <input
            id="review-title"
            type="text"
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            placeholder="Sum up your experience in one line"
            maxLength={255}
            className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-coffee-500 focus:border-transparent transition-all ${
              errors.title ? 'border-red-500' : 'border-gray-300'
            }`}
          />
          <div className="flex justify-between mt-1">
            {errors.title ? (
              <p className="text-sm text-red-600">{errors.title}</p>
            ) : (
              <p className="text-sm text-gray-500">Minimum 5 characters</p>
            )}
            <p className="text-sm text-gray-400">{title.length}/255</p>
          </div>
        </div>

        {/* Review Text */}
        <div>
          <label htmlFor="review-text" className="block text-sm font-semibold text-gray-900 mb-2">
            Your Review <span className="text-red-500">*</span>
          </label>
          <textarea
            id="review-text"
            value={reviewText}
            onChange={(e) => setReviewText(e.target.value)}
            placeholder="Tell us what you liked or didn't like about this product..."
            rows={6}
            maxLength={2000}
            className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-coffee-500 focus:border-transparent transition-all resize-none ${
              errors.reviewText ? 'border-red-500' : 'border-gray-300'
            }`}
          />
          <div className="flex justify-between mt-1">
            {errors.reviewText ? (
              <p className="text-sm text-red-600">{errors.reviewText}</p>
            ) : (
              <p className="text-sm text-gray-500">Minimum 20 characters</p>
            )}
            <p className="text-sm text-gray-400">{reviewText.length}/2000</p>
          </div>
        </div>

        {/* Guidelines */}
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h4 className="font-semibold text-blue-900 mb-2 text-sm">Review Guidelines</h4>
          <ul className="text-sm text-blue-800 space-y-1">
            <li>• Be honest and share your genuine experience</li>
            <li>• Focus on the product features and quality</li>
            <li>• Avoid inappropriate language or personal attacks</li>
            <li>• Reviews are publicly visible to all customers</li>
          </ul>
        </div>

        {/* Submit Error */}
        {errors.submit && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-4">
            <p className="text-sm text-red-800">{errors.submit}</p>
          </div>
        )}

        {/* Actions */}
        <div className="flex gap-3 pt-4">
          <button
            type="submit"
            disabled={isSubmitting}
            className="flex-1 bg-coffee-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-coffee-700 transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isSubmitting ? (
              <>
                <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                <span>Submitting...</span>
              </>
            ) : (
              <>
                <Send className="w-5 h-5" />
                <span>Submit Review</span>
              </>
            )}
          </button>
          {onCancel && (
            <button
              type="button"
              onClick={onCancel}
              disabled={isSubmitting}
              className="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Cancel
            </button>
          )}
        </div>
      </form>
    </div>
  );
}
