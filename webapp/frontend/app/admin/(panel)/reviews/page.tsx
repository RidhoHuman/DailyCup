"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api-client";

interface Review {
  id: number;
  product_id: number;
  product_name?: string;
  user_id: number;
  user_name: string;
  user_email: string;
  rating: number;
  review_title: string;
  review_text: string;
  helpful_count: number;
  verified_purchase: boolean;
  status: 'pending' | 'approved' | 'rejected';
  created_at: string;
  updated_at: string;
}

export default function ReviewsModerationPage() {
  const [reviews, setReviews] = useState<Review[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState<'all' | 'pending' | 'approved' | 'rejected'>('all');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  useEffect(() => {
    fetchReviews();
  }, [filter, page]);

  const fetchReviews = async () => {
    try {
      setLoading(true);
      const response = await api.get<{
        success: boolean;
        reviews: Review[];
        pagination: {
          page: number;
          limit: number;
          total: number;
          total_pages: number;
        };
      }>(`/reviews.php?page=${page}&limit=20`, { requiresAuth: true });

      if (response.success) {
        let filteredReviews = response.reviews;
        
        // Apply filter
        if (filter !== 'all') {
          filteredReviews = filteredReviews.filter(r => r.status === filter);
        }
        
        setReviews(filteredReviews);
        setTotalPages(response.pagination.total_pages);
      }
    } catch (error) {
      console.error('Error fetching reviews:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (reviewId: number) => {
    try {
      await api.put(`/reviews.php?review_id=${reviewId}`, {
        status: 'approved'
      }, { requiresAuth: true });
      
      fetchReviews();
    } catch (error) {
      console.error('Error approving review:', error);
      alert('Failed to approve review');
    }
  };

  const handleReject = async (reviewId: number) => {
    if (!confirm('Are you sure you want to reject this review?')) return;
    
    try {
      await api.put(`/reviews.php?review_id=${reviewId}`, {
        status: 'rejected'
      }, { requiresAuth: true });
      
      fetchReviews();
    } catch (error) {
      console.error('Error rejecting review:', error);
      alert('Failed to reject review');
    }
  };

  const handleDelete = async (reviewId: number) => {
    if (!confirm('Are you sure you want to delete this review? This action cannot be undone.')) return;
    
    try {
      await api.delete(`/reviews.php?review_id=${reviewId}`, { requiresAuth: true });
      fetchReviews();
    } catch (error) {
      console.error('Error deleting review:', error);
      alert('Failed to delete review');
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      'pending': 'bg-yellow-100 text-yellow-700 border-yellow-300',
      'approved': 'bg-green-100 text-green-700 border-green-300',
      'rejected': 'bg-red-100 text-red-700 border-red-300'
    };
    return colors[status] || 'bg-gray-100 text-gray-700 border-gray-300';
  };

  const renderStars = (rating: number) => {
    return (
      <div className="flex gap-1 text-yellow-400">
        {[1, 2, 3, 4, 5].map((star) => (
          <i 
            key={star} 
            className={`bi ${star <= rating ? 'bi-star-fill' : 'bi-star'}`}
          />
        ))}
      </div>
    );
  };

  if (loading && reviews.length === 0) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto mb-4"></div>
          <p className="text-gray-500">Loading reviews...</p>
        </div>
      </div>
    );
  }

  return (
    <div>
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-800 mb-2">Reviews Moderation</h1>
        <p className="text-gray-500">Manage and moderate product reviews from customers</p>
      </div>

      {/* Filter Tabs */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div className="flex flex-wrap gap-3">
          <button
            onClick={() => { setFilter('all'); setPage(1); }}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              filter === 'all'
                ? 'bg-[#a97456] text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            All Reviews
          </button>
          <button
            onClick={() => { setFilter('pending'); setPage(1); }}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              filter === 'pending'
                ? 'bg-yellow-500 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Pending
          </button>
          <button
            onClick={() => { setFilter('approved'); setPage(1); }}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              filter === 'approved'
                ? 'bg-green-500 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Approved
          </button>
          <button
            onClick={() => { setFilter('rejected'); setPage(1); }}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              filter === 'rejected'
                ? 'bg-red-500 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Rejected
          </button>
        </div>
      </div>

      {/* Reviews List */}
      <div className="space-y-4">
        {reviews.length === 0 ? (
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <i className="bi bi-star text-6xl text-gray-300 mb-4"></i>
            <p className="text-gray-500 text-lg">No reviews found</p>
          </div>
        ) : (
          reviews.map((review) => (
            <div 
              key={review.id}
              className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow"
            >
              <div className="flex items-start justify-between mb-4">
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-2">
                    {renderStars(review.rating)}
                    <span className={`px-3 py-1 rounded-full text-xs font-medium border ${getStatusColor(review.status)}`}>
                      {review.status.charAt(0).toUpperCase() + review.status.slice(1)}
                    </span>
                    {review.verified_purchase && (
                      <span className="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium border border-blue-200">
                        <i className="bi bi-patch-check-fill mr-1"></i>
                        Verified Purchase
                      </span>
                    )}
                  </div>
                  <h3 className="font-bold text-lg text-gray-800 mb-1">{review.review_title}</h3>
                  <p className="text-gray-600 mb-3">{review.review_text}</p>
                  
                  <div className="flex items-center gap-4 text-sm text-gray-500">
                    <span>
                      <i className="bi bi-person mr-1"></i>
                      {review.user_name}
                    </span>
                    <span>
                      <i className="bi bi-envelope mr-1"></i>
                      {review.user_email}
                    </span>
                    <span>
                      <i className="bi bi-clock mr-1"></i>
                      {formatDate(review.created_at)}
                    </span>
                    <span>
                      <i className="bi bi-hand-thumbs-up mr-1"></i>
                      {review.helpful_count} helpful
                    </span>
                  </div>
                </div>

                <div className="flex gap-2 ml-4">
                  {review.status === 'pending' && (
                    <>
                      <button
                        onClick={() => handleApprove(review.id)}
                        className="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium"
                        title="Approve"
                      >
                        <i className="bi bi-check-circle mr-2"></i>
                        Approve
                      </button>
                      <button
                        onClick={() => handleReject(review.id)}
                        className="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors font-medium"
                        title="Reject"
                      >
                        <i className="bi bi-x-circle mr-2"></i>
                        Reject
                      </button>
                    </>
                  )}
                  
                  {review.status === 'approved' && (
                    <button
                      onClick={() => handleReject(review.id)}
                      className="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors font-medium"
                      title="Reject"
                    >
                      <i className="bi bi-x-circle mr-2"></i>
                      Reject
                    </button>
                  )}
                  
                  {review.status === 'rejected' && (
                    <button
                      onClick={() => handleApprove(review.id)}
                      className="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium"
                      title="Approve"
                    >
                      <i className="bi bi-check-circle mr-2"></i>
                      Approve
                    </button>
                  )}

                  <button
                    onClick={() => handleDelete(review.id)}
                    className="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors font-medium"
                    title="Delete"
                  >
                    <i className="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          ))
        )}
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex justify-center items-center gap-2 mt-8">
          <button
            onClick={() => setPage(p => Math.max(1, p - 1))}
            disabled={page === 1}
            className="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <i className="bi bi-chevron-left"></i>
          </button>
          
          <span className="px-4 py-2 text-gray-700">
            Page {page} of {totalPages}
          </span>
          
          <button
            onClick={() => setPage(p => Math.min(totalPages, p + 1))}
            disabled={page === totalPages}
            className="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <i className="bi bi-chevron-right"></i>
          </button>
        </div>
      )}
    </div>
  );
}
