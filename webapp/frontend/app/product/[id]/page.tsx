'use client';

import { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { ShoppingCart, Heart, Share2, Star, Check } from 'lucide-react';
import { getImageUrl } from '@/lib/storage';
import StarRating from '@/components/StarRating';
import ReviewsList from '@/components/ReviewsList';
import { SocialShare } from '@/components/SocialShare';
import api from '@/lib/api-client';
import { useCart } from '@/contexts/CartContext';
import toast from 'react-hot-toast';

interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  image: string;
  category: string;
  stock: number;
  average_rating?: number;
  total_reviews?: number;
}

interface ProductResponse {
  data: {
    success: boolean;
    product: Product;
    message?: string;
  };
}

export default function ProductDetailPage() {
  const params = useParams();
  const router = useRouter();
  const productId = parseInt(params.id as string);
  
  const [product, setProduct] = useState<Product | null>(null);
  const [loading, setLoading] = useState(true);
  const [quantity, setQuantity] = useState(1);
  const [activeTab, setActiveTab] = useState<'description' | 'reviews'>('description');
  const { addItem } = useCart();

  useEffect(() => {
    fetchProduct();
  }, [productId]);

  const fetchProduct = async () => {
    try {
      setLoading(true);
      const response = await api.get(`/products.php?id=${productId}`) as ProductResponse;
      
      if (response.data.success && response.data.product) {
        setProduct(response.data.product);
      } else {
        toast.error('Product not found');
        router.push('/menu');
      }
    } catch (error) {
      console.error('Error fetching product:', error);
      toast.error('Failed to load product');
      router.push('/menu');
    } finally {
      setLoading(false);
    }
  };

  const handleAddToCart = () => {
    if (!product) return;

    addItem(
      {
        id: product.id,
        name: product.name,
        base_price: product.price,
        description: '',
        image: product.image,
        is_featured: false,
        stock: product.stock,
        category_name: product.category || 'Coffee',
        variants: {}
      },
      {},
      quantity
    );

    toast.success(`${quantity} ${product.name} added to cart!`);
  };

  const handleShare = async () => {
    if (navigator.share) {
      try {
        await navigator.share({
          title: product?.name,
          text: product?.description,
          url: window.location.href
        });
      } catch (error) {
        console.log('Share cancelled');
      }
    } else {
      // Fallback: copy to clipboard
      navigator.clipboard.writeText(window.location.href);
      toast.success('Link copied to clipboard!');
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="w-12 h-12 border-4 border-coffee-600 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Loading product...</p>
        </div>
      </div>
    );
  }

  if (!product) {
    return null;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Breadcrumb */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <nav className="flex items-center gap-2 text-sm">
            <a href="/" className="text-gray-500 hover:text-coffee-600">Home</a>
            <span className="text-gray-400">/</span>
            <a href="/menu" className="text-gray-500 hover:text-coffee-600">Menu</a>
            <span className="text-gray-400">/</span>
            <span className="text-gray-900 font-medium">{product.name}</span>
          </nav>
        </div>
      </div>

      {/* Product Details */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
          <div className="grid md:grid-cols-2 gap-8 p-8">
            {/* Product Image */}
            <div className="relative">
              <div className="aspect-square rounded-xl overflow-hidden bg-gray-100">
                <img
                  src={getImageUrl(product.image) || '/images/placeholder-product.jpg'}
                  alt={product.name}
                  className="w-full h-full object-cover"
                />
              </div>
              
              {/* Stock Badge */}
              {product.stock > 0 ? (
                <div className="absolute top-4 left-4 bg-green-500 text-white px-4 py-2 rounded-full font-semibold flex items-center gap-2">
                  <Check className="w-4 h-4" />
                  In Stock
                </div>
              ) : (
                <div className="absolute top-4 left-4 bg-red-500 text-white px-4 py-2 rounded-full font-semibold">
                  Out of Stock
                </div>
              )}
            </div>

            {/* Product Info */}
            <div className="flex flex-col">
              {/* Title & Category */}
              <div className="mb-4">
                <span className="inline-block px-3 py-1 bg-coffee-100 text-coffee-800 rounded-full text-sm font-medium mb-3">
                  {product.category}
                </span>
                <h1 className="text-4xl font-bold text-gray-900 mb-3">{product.name}</h1>
                
                {/* Rating */}
                {product.average_rating && product.total_reviews ? (
                  <div className="flex items-center gap-3">
                    <StarRating rating={product.average_rating} showValue size="md" />
                    <button
                      onClick={() => setActiveTab('reviews')}
                      className="text-coffee-600 hover:text-coffee-700 font-medium"
                    >
                      ({product.total_reviews} {product.total_reviews === 1 ? 'review' : 'reviews'})
                    </button>
                  </div>
                ) : (
                  <button
                    onClick={() => setActiveTab('reviews')}
                    className="text-gray-500 hover:text-coffee-600"
                  >
                    No reviews yet - Be the first!
                  </button>
                )}
              </div>

              {/* Price */}
              <div className="mb-6">
                <div className="text-4xl font-bold text-coffee-600">
                  Rp {product.price.toLocaleString('id-ID')}
                </div>
              </div>

              {/* Short Description */}
              <p className="text-gray-700 mb-8 leading-relaxed">
                {product.description}
              </p>

              {/* Quantity Selector */}
              <div className="mb-6">
                <label className="block text-sm font-semibold text-gray-900 mb-3">
                  Quantity
                </label>
                <div className="flex items-center gap-4">
                  <div className="flex items-center border-2 border-gray-300 rounded-lg overflow-hidden">
                    <button
                      onClick={() => setQuantity(Math.max(1, quantity - 1))}
                      className="px-4 py-3 bg-gray-50 hover:bg-gray-100 font-semibold text-gray-700 transition-colors"
                    >
                      -
                    </button>
                    <span className="px-8 py-3 font-semibold text-gray-900">{quantity}</span>
                    <button
                      onClick={() => setQuantity(Math.min(product.stock, quantity + 1))}
                      className="px-4 py-3 bg-gray-50 hover:bg-gray-100 font-semibold text-gray-700 transition-colors"
                      disabled={quantity >= product.stock}
                    >
                      +
                    </button>
                  </div>
                  <span className="text-sm text-gray-600">
                    {product.stock} items available
                  </span>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="flex gap-3 mb-6">
                <button
                  onClick={handleAddToCart}
                  disabled={product.stock === 0}
                  className="flex-1 bg-coffee-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-coffee-700 transition-colors flex items-center justify-center gap-2 disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                  <ShoppingCart className="w-5 h-5" />
                  <span>Add to Cart</span>
                </button>
                <button
                  className="p-4 border-2 border-gray-300 rounded-lg hover:border-red-500 hover:bg-red-50 transition-colors group"
                  aria-label="Add to wishlist"
                >
                  <Heart className="w-6 h-6 text-gray-600 group-hover:text-red-500 transition-colors" />
                </button>
                <div className="flex items-center">
                  <SocialShare
                    url={typeof window !== 'undefined' ? window.location.href : ''}
                    title={product.name}
                    description={product.description}
                    image={product.image}
                    variant="dropdown"
                  />
                </div>
              </div>

              {/* Product Features */}
              <div className="border-t pt-6 space-y-3">
                <div className="flex items-center gap-3 text-gray-700">
                  <Check className="w-5 h-5 text-green-600" />
                  <span>100% Premium Quality Coffee Beans</span>
                </div>
                <div className="flex items-center gap-3 text-gray-700">
                  <Check className="w-5 h-5 text-green-600" />
                  <span>Freshly Roasted</span>
                </div>
                <div className="flex items-center gap-3 text-gray-700">
                  <Check className="w-5 h-5 text-green-600" />
                  <span>Free Shipping on Orders Over Rp 100,000</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Tabs Section */}
        <div className="mt-12">
          {/* Tab Headers */}
          <div className="flex border-b border-gray-200 mb-8">
            <button
              onClick={() => setActiveTab('description')}
              className={`px-8 py-4 font-semibold transition-colors relative ${
                activeTab === 'description'
                  ? 'text-coffee-600'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              Description
              {activeTab === 'description' && (
                <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-coffee-600" />
              )}
            </button>
            <button
              onClick={() => setActiveTab('reviews')}
              className={`px-8 py-4 font-semibold transition-colors relative ${
                activeTab === 'reviews'
                  ? 'text-coffee-600'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              Reviews
              {product.total_reviews && product.total_reviews > 0 && (
                <span className="ml-2 px-2 py-1 bg-coffee-100 text-coffee-800 rounded-full text-xs">
                  {product.total_reviews}
                </span>
              )}
              {activeTab === 'reviews' && (
                <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-coffee-600" />
              )}
            </button>
          </div>

          {/* Tab Content */}
          <div className="bg-white rounded-xl p-8 shadow-sm">
            {activeTab === 'description' ? (
              <div className="prose max-w-none">
                <h3 className="text-2xl font-bold text-gray-900 mb-4">Product Description</h3>
                <p className="text-gray-700 leading-relaxed mb-6">{product.description}</p>
                
                <h4 className="text-xl font-bold text-gray-900 mb-3">Product Details</h4>
                <ul className="space-y-2 text-gray-700">
                  <li><strong>Category:</strong> {product.category}</li>
                  <li><strong>Price:</strong> Rp {product.price.toLocaleString('id-ID')}</li>
                  <li><strong>Availability:</strong> {product.stock > 0 ? `In Stock (${product.stock} items)` : 'Out of Stock'}</li>
                </ul>
              </div>
            ) : (
              <ReviewsList productId={product.id} productName={product.name} />
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
