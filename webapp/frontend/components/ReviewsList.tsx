'use client';

import { useState, useEffect } from 'react';
import { Star, MessageSquare, TrendingUp, Filter } from 'lucide-react';
import ReviewCard from './ReviewCard';
import ReviewForm from './ReviewForm';
import StarRating from './StarRating';
import api from '@/lib/api-client';
import { useAuthStore } from '@/lib/stores/auth-store';
import toast from 'react-hot-toast';

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

interface RatingSummary {
  product_id: number;
  total_reviews: number;
  average_rating: number;
  five_star: number;
  four_star: number;
  three_star: number;
  two_star: number;
  one_star: number;
}

interface ReviewsResponse {
  data: {
    success: boolean;
    reviews: Review[];
    summary: RatingSummary;
    message?: string;
  };
}

interface ReviewSubmitResponse {
  data: {
    success: boolean;
    message?: string;
    error?: string;
  };
}

interface ReviewsListProps {
  productId: number;
  productName: string;
}

export default function ReviewsList({ productId, productName }: ReviewsListProps) {
  const [reviews, setReviews] = useState<Review[]>([]);
  const [summary, setSummary] = useState<RatingSummary | null>(null);
  const [loading, setLoading] = useState(true);
  const [showReviewForm, setShowReviewForm] = useState(false);
  const [sortBy, setSortBy] = useState('recent'); // recent, helpful, rating_high, rating_low
  const { user } = useAuthStore();

  useEffect(() => {
    fetchReviews();
  }, [productId, sortBy]);

  const fetchReviews = async () => {
    try {
      setLoading(true);
      const response = await api.get(`/reviews.php?product_id=${productId}&sort=${sortBy}`) as ReviewsResponse;
      
      if (response.data.success) {
        setReviews(response.data.reviews);
        setSummary(response.data.summary);
      }
    } catch (error) {
      console.error('Error fetching reviews:', error);
      toast.error('Failed to load reviews');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmitReview = async (reviewData: any) => {
    try {
      const response = await api.post('/reviews.php', reviewData) as ReviewSubmitResponse;
      
      if (response.data.success) {
        toast.success('Review submitted successfully!');
        setShowReviewForm(false);
        fetchReviews(); // Refresh reviews
      }
    } catch (error: any) {
      if (error.response?.data?.error) {
        toast.error(error.response.data.error);
      } else {
        toast.error('Failed to submit review');
      }
      throw error;
    }
  };

  const handleHelpful = async (reviewId: number, isHelpful: boolean) => {
    try {
      // TODO: Implement helpful tracking API
      console.log('Mark review as helpful:', reviewId, isHelpful);
    } catch (error) {
      console.error('Error marking review as helpful:', error);
    }
  };

  const getRatingPercentage = (count: number) => {
    if (!summary || summary.total_reviews === 0) return 0;
    return Math.round((count / summary.total_reviews) * 100);
  };

  if (loading) {
    return (
      <div className="py-12 text-center">
        <div className="inline-block w-8 h-8 border-4 border-coffee-600 border-t-transparent rounded-full animate-spin" />
        <p className="mt-4 text-gray-600">Loading reviews...</p>
      </div>
    );
  }

  return (
    <div className="space-y-8">
      {/* Summary Section */}
      {summary && summary.total_reviews > 0 ? (
        <div className="bg-gradient-to-br from-coffee-50 to-orange-50 rounded-xl p-8 border border-coffee-100">
          <div className="grid md:grid-cols-2 gap-8">
            {/* Overall Rating */}
            <div className="flex flex-col items-center justify-center text-center">
              <div className="text-6xl font-bold text-coffee-900 mb-2">
                {summary.average_rating.toFixed(1)}
              </div>
              <StarRating rating={summary.average_rating} size="lg" />
              <p className="mt-3 text-gray-700 font-medium">
                Based on {summary.total_reviews} {summary.total_reviews === 1 ? 'review' : 'reviews'}
              </p>
            </div>

            {/* Rating Breakdown */}
            <div className="space-y-2">
              {[5, 4, 3, 2, 1].map((stars) => {
                const count = summary[`${['one', 'two', 'three', 'four', 'five'][stars - 1]}_star` as keyof RatingSummary] as number;
                const percentage = getRatingPercentage(count);
                
                return (
                  <div key={stars} className="flex items-center gap-3">
                    <span className="text-sm font-medium text-gray-700 w-12">{stars} star</span>
                    <div className="flex-1 h-3 bg-white rounded-full overflow-hidden">
                      <div
                        className="h-full bg-gradient-to-r from-yellow-400 to-yellow-500 transition-all duration-500"
                        style={{ width: `${percentage}%` }}
                      />
                    </div>
                    <span className="text-sm text-gray-600 w-12 text-right">{percentage}%</span>
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      ) : (
        <div className="bg-gray-50 rounded-xl p-12 text-center border border-gray-200">
          <MessageSquare className="w-16 h-16 text-gray-400 mx-auto mb-4" />
          <h3 className="text-xl font-semibold text-gray-900 mb-2">No reviews yet</h3>
          <p className="text-gray-600 mb-6">Be the first to review this product!</p>
        </div>
      )}

      {/* Write Review Button */}
      {user && !showReviewForm && (
        <div className="flex justify-center">
          <button
            onClick={() => setShowReviewForm(true)}
            className="bg-coffee-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-coffee-700 transition-colors flex items-center gap-2"
          >
            <Star className="w-5 h-5" />
            <span>Write a Review</span>
          </button>
        </div>
      )}

      {/* Review Form */}
      {showReviewForm && (
        <div className="mt-8">
          <ReviewForm
            productId={productId}
            productName={productName}
            onSubmit={handleSubmitReview}
            onCancel={() => setShowReviewForm(false)}
          />
        </div>
      )}

      {/* Reviews List */}
      {reviews.length > 0 && (
        <div>
          {/* Sort & Filter */}
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
              <MessageSquare className="w-6 h-6 text-coffee-600" />
              Customer Reviews
            </h3>
            <div className="flex items-center gap-2">
              <Filter className="w-5 h-5 text-gray-500" />
              <select
                value={sortBy}
                onChange={(e) => setSortBy(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-coffee-500 focus:border-transparent"
              >
                <option value="recent">Most Recent</option>
                <option value="helpful">Most Helpful</option>
                <option value="rating_high">Highest Rating</option>
                <option value="rating_low">Lowest Rating</option>
              </select>
            </div>
          </div>

          {/* Reviews */}
          <div className="space-y-4">
            {reviews.map((review) => (
              <ReviewCard
                key={review.id}
                review={review}
                onHelpful={handleHelpful}
              />
            ))}
          </div>
        </div>
      )}

      {/* Login Prompt */}
      {!user && !showReviewForm && (
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
          <p className="text-blue-900 font-medium mb-3">
            Want to share your experience?
          </p>
          <a
            href="/login"
            className="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
          >
            Sign in to write a review
          </a>
        </div>
      )}
    </div>
  );
}
