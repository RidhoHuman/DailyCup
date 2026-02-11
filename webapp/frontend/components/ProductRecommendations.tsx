'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { useAuthStore } from '@/lib/stores/auth-store';

interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  category: string;
  image: string;
  stock: number;
  avg_rating: number;
  review_count: number;
  reason?: string;
}

interface RecommendationsProps {
  type: 'related' | 'personalized' | 'trending' | 'cart';
  productId?: number;
  cartItems?: Array<{ product_id: number }>;
  limit?: number;
  title?: string;
}

export default function ProductRecommendations({
  type,
  productId,
  cartItems,
  limit = 8,
  title
}: RecommendationsProps) {
  const { user } = useAuthStore();
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchRecommendations();
  }, [type, productId, user]);

  const fetchRecommendations = async () => {
    setLoading(true);
    try {
      let url = `http://localhost/DailyCup/webapp/backend/api/recommendations.php?type=${type}&limit=${limit}`;
      
      if (productId) {
        url += `&product_id=${productId}`;
      }
      
      if (user?.id) {
        url += `&user_id=${user.id}`;
      }
      
      if (cartItems && cartItems.length > 0) {
        url += `&cart_items=${encodeURIComponent(JSON.stringify(cartItems))}`;
      }

      const response = await fetch(url);
      const data = await response.json();

      if (data.success) {
        setProducts(data.recommendations);
      }
    } catch (error) {
      console.error('Error fetching recommendations:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const getDefaultTitle = () => {
    switch (type) {
      case 'related':
        return 'Related Products';
      case 'personalized':
        return 'Recommended For You';
      case 'trending':
        return 'Trending Now';
      case 'cart':
        return 'You May Also Like';
      default:
        return 'Recommendations';
    }
  };

  if (loading) {
    return (
      <div className="py-8">
        <h2 className="text-2xl font-bold mb-6">{title || getDefaultTitle()}</h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="bg-gray-200 rounded-lg h-80 animate-pulse"></div>
          ))}
        </div>
      </div>
    );
  }

  if (products.length === 0) {
    return null;
  }

  return (
    <div className="py-8">
      <h2 className="text-2xl font-bold mb-6 text-gray-900">{title || getDefaultTitle()}</h2>
      
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        {products.map((product) => (
          <Link
            key={product.id}
            href={`/customer/product/${product.id}`}
            className="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden group"
          >
            <div className="relative h-48 bg-gray-100 overflow-hidden">
              <img
                src={product.image || '/images/placeholder-product.jpg'}
                alt={product.name}
                className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
              />
              {product.reason && (
                <div className="absolute top-2 right-2 bg-blue-600 text-white text-xs px-2 py-1 rounded-full">
                  {product.reason}
                </div>
              )}
              {product.avg_rating > 0 && (
                <div className="absolute bottom-2 left-2 bg-white/90 px-2 py-1 rounded flex items-center space-x-1">
                  <svg
                    className="w-4 h-4 text-yellow-400"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <span className="text-sm font-semibold text-gray-900">
                    {product.avg_rating.toFixed(1)}
                  </span>
                  <span className="text-xs text-gray-500">({product.review_count})</span>
                </div>
              )}
            </div>

            <div className="p-4">
              <div className="mb-2">
                <span className="text-xs font-medium text-blue-600 uppercase tracking-wide">
                  {product.category}
                </span>
              </div>
              
              <h3 className="text-lg font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">
                {product.name}
              </h3>
              
              <p className="text-sm text-gray-600 mb-3 line-clamp-2">
                {product.description}
              </p>

              <div className="flex items-center justify-between">
                <span className="text-xl font-bold text-blue-600">
                  {formatCurrency(product.price)}
                </span>
                
                {product.stock > 0 ? (
                  <span className="text-xs text-green-600 font-medium">In Stock</span>
                ) : (
                  <span className="text-xs text-red-600 font-medium">Out of Stock</span>
                )}
              </div>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}
