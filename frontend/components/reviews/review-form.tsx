'use client';

import { useState } from 'react';
import { StarRating } from './star-rating';
import { Button } from '@/components/ui/button';
import { showToast } from '@/components/ui/toast-provider';
import { cn } from '@/lib/utils';

interface ReviewFormProps {
  productId: string;
  productName?: string;
  onSubmit: (data: ReviewFormData) => Promise<void>;
  onCancel?: () => void;
  className?: string;
}

interface ReviewFormData {
  rating: number;
  title: string;
  content: string;
  images?: File[];
}

export function ReviewForm({
  productName,
  onSubmit,
  onCancel,
  className,
}: ReviewFormProps) {
  const [rating, setRating] = useState(0);
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [images, setImages] = useState<File[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState<{ rating?: string; content?: string }>({});

  const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(e.target.files || []);
    if (files.length + images.length > 5) {
      showToast.error('Maximum 5 images allowed');
      return;
    }
    setImages((prev) => [...prev, ...files].slice(0, 5));
  };

  const removeImage = (index: number) => {
    setImages((prev) => prev.filter((_, i) => i !== index));
  };

  const validate = (): boolean => {
    const newErrors: typeof errors = {};
    
    if (rating === 0) {
      newErrors.rating = 'Please select a rating';
    }
    
    if (content.trim().length < 10) {
      newErrors.content = 'Review must be at least 10 characters';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validate()) return;

    setIsSubmitting(true);
    try {
      await onSubmit({
        rating,
        title: title.trim(),
        content: content.trim(),
        images: images.length > 0 ? images : undefined,
      });
      
      // Reset form
      setRating(0);
      setTitle('');
      setContent('');
      setImages([]);
      showToast.success('Review submitted successfully!');
    } catch {
      showToast.error('Failed to submit review. Please try again.');;
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className={cn('space-y-4', className)}>
      {productName && (
        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Review: {productName}
        </h3>
      )}

      {/* Rating */}
      <div>
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Your Rating *
        </label>
        <StarRating
          rating={rating}
          interactive
          onChange={setRating}
          size="lg"
        />
        {errors.rating && (
          <p className="mt-1 text-sm text-red-500">{errors.rating}</p>
        )}
      </div>

      {/* Title (optional) */}
      <div>
        <label
          htmlFor="review-title"
          className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
        >
          Review Title (optional)
        </label>
        <input
          id="review-title"
          type="text"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          placeholder="Summarize your review"
          maxLength={100}
          className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
        />
      </div>

      {/* Content */}
      <div>
        <label
          htmlFor="review-content"
          className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
        >
          Your Review *
        </label>
        <textarea
          id="review-content"
          value={content}
          onChange={(e) => setContent(e.target.value)}
          placeholder="Share your experience with this product..."
          rows={4}
          maxLength={1000}
          className={cn(
            'w-full px-4 py-2 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-amber-500 focus:border-transparent resize-none',
            errors.content
              ? 'border-red-500'
              : 'border-gray-300 dark:border-gray-600'
          )}
        />
        <div className="flex justify-between mt-1">
          {errors.content ? (
            <p className="text-sm text-red-500">{errors.content}</p>
          ) : (
            <span />
          )}
          <span className="text-xs text-gray-500">
            {content.length}/1000
          </span>
        </div>
      </div>

      {/* Image upload */}
      <div>
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Add Photos (optional)
        </label>
        
        <div className="flex flex-wrap gap-2">
          {images.map((file, index) => (
            <div key={index} className="relative">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src={URL.createObjectURL(file)}
                alt={`Upload ${index + 1}`}
                className="w-16 h-16 object-cover rounded-lg"
              />
              <button
                type="button"
                onClick={() => removeImage(index)}
                className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600"
              >
                ×
              </button>
            </div>
          ))}
          
          {images.length < 5 && (
            <label className="w-16 h-16 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center cursor-pointer hover:border-amber-500 transition-colors">
              <input
                type="file"
                accept="image/*"
                onChange={handleImageChange}
                className="hidden"
                multiple
              />
              <svg
                className="w-6 h-6 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 4v16m8-8H4"
                />
              </svg>
            </label>
          )}
        </div>
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Max 5 photos, each up to 5MB
        </p>
      </div>

      {/* Submit buttons */}
      <div className="flex gap-3 justify-end pt-2">
        {onCancel && (
          <Button
            type="button"
            variant="ghost"
            onClick={onCancel}
            disabled={isSubmitting}
          >
            Cancel
          </Button>
        )}
        <Button
          type="submit"
          variant="primary"
          isLoading={isSubmitting}
        >
          Submit Review
        </Button>
      </div>
    </form>
  );
}
