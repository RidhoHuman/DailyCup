'use client';

import { formatCurrency } from '@/lib/utils';
import { Card } from '@/components/ui/card';
import { WishlistButton } from '@/components/wishlist/wishlist-button';
import Image from 'next/image';

interface Product {
  id: string;
  name: string;
  price: number;
  image?: string;
  category?: string;
}

interface RecentlyViewedProps {
  products: Product[];
  onProductClick?: (productId: string) => void;
  onAddToCart?: (product: Product) => void;
  maxItems?: number;
  className?: string;
}

export function RecentlyViewed({
  products,
  onProductClick,
  onAddToCart,
  maxItems = 6,
  className,
}: RecentlyViewedProps) {
  const displayProducts = products.slice(0, maxItems);

  if (displayProducts.length === 0) {
    return null;
  }

  return (
    <section className={className}>
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Recently Viewed
        </h2>
        {products.length > maxItems && (
          <button className="text-sm text-amber-600 hover:text-amber-700">
            View All
          </button>
        )}
      </div>

      <div className="overflow-x-auto -mx-4 px-4">
        <div className="flex gap-4" style={{ minWidth: 'max-content' }}>
          {displayProducts.map((product) => (
            <div
              key={product.id}
              className="w-40 flex-shrink-0"
            >
              <Card
                variant="outlined"
                padding="none"
                hover
                className="overflow-hidden group"
              >
                {/* Image */}
                <div
                  className="aspect-square bg-gray-100 dark:bg-gray-700 relative cursor-pointer"
                  onClick={() => onProductClick?.(product.id)}
                >
                  {product.image ? (
                    <Image
                      src={product.image}
                      alt={product.name}
                      fill
                      className="object-cover transition-transform group-hover:scale-105"
                      sizes="160px"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-3xl text-gray-400">
                      ☕
                    </div>
                  )}

                  {/* Wishlist button */}
                  <div className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <WishlistButton
                      productId={product.id}
                      productName={product.name}
                      size="sm"
                    />
                  </div>
                </div>

                {/* Info */}
                <div className="p-3">
                  <p
                    className="text-sm font-medium text-gray-900 dark:text-gray-100 truncate cursor-pointer hover:text-amber-600"
                    onClick={() => onProductClick?.(product.id)}
                  >
                    {product.name}
                  </p>
                  {product.category && (
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                      {product.category}
                    </p>
                  )}
                  <p className="font-bold text-amber-600 mt-1">
                    {formatCurrency(product.price)}
                  </p>

                  {onAddToCart && (
                    <button
                      onClick={() => onAddToCart(product)}
                      className="w-full mt-2 py-1.5 text-sm font-medium text-amber-600 bg-amber-50 dark:bg-amber-900/20 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors"
                    >
                      Add to Cart
                    </button>
                  )}
                </div>
              </Card>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

// Product Recommendations
interface ProductRecommendationsProps {
  title?: string;
  products: Product[];
  onProductClick?: (productId: string) => void;
  onAddToCart?: (product: Product) => void;
  loading?: boolean;
  className?: string;
}

export function ProductRecommendations({
  title = 'Recommended for You',
  products,
  onProductClick,
  onAddToCart,
  loading = false,
  className,
}: ProductRecommendationsProps) {
  if (loading) {
    return (
      <section className={className}>
        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
          {title}
        </h2>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="animate-pulse">
              <div className="aspect-square bg-gray-200 dark:bg-gray-700 rounded-xl" />
              <div className="mt-2 space-y-2">
                <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4" />
                <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2" />
              </div>
            </div>
          ))}
        </div>
      </section>
    );
  }

  if (products.length === 0) {
    return null;
  }

  return (
    <section className={className}>
      <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        {title}
      </h2>

      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        {products.map((product) => (
          <Card
            key={product.id}
            variant="default"
            padding="none"
            hover
            className="overflow-hidden group"
          >
            {/* Image */}
            <div
              className="aspect-square bg-gray-100 dark:bg-gray-700 relative cursor-pointer overflow-hidden"
              onClick={() => onProductClick?.(product.id)}
            >
              {product.image ? (
                <Image
                  src={product.image}
                  alt={product.name}
                  fill
                  className="object-cover transition-transform duration-300 group-hover:scale-110"
                  sizes="(max-width: 768px) 50vw, 25vw"
                />
              ) : (
                <div className="w-full h-full flex items-center justify-center text-4xl text-gray-400">
                  ☕
                </div>
              )}

              {/* Wishlist button */}
              <div className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <WishlistButton
                  productId={product.id}
                  productName={product.name}
                  size="sm"
                />
              </div>

              {/* Quick add overlay */}
              {onAddToCart && (
                <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-3 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      onAddToCart(product);
                    }}
                    className="w-full py-2 bg-white text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors"
                  >
                    Quick Add
                  </button>
                </div>
              )}
            </div>

            {/* Info */}
            <div className="p-4">
              <p
                className="font-medium text-gray-900 dark:text-gray-100 truncate cursor-pointer hover:text-amber-600"
                onClick={() => onProductClick?.(product.id)}
              >
                {product.name}
              </p>
              {product.category && (
                <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                  {product.category}
                </p>
              )}
              <p className="font-bold text-amber-600 mt-2">
                {formatCurrency(product.price)}
              </p>
            </div>
          </Card>
        ))}
      </div>
    </section>
  );
}

// "You May Also Like" Section
interface YouMayAlsoLikeProps {
  currentProductId: string;
  products: Product[];
  onProductClick?: (productId: string) => void;
  className?: string;
}

export function YouMayAlsoLike({
  currentProductId,
  products,
  onProductClick,
  className,
}: YouMayAlsoLikeProps) {
  // Filter out current product
  const filteredProducts = products.filter((p) => p.id !== currentProductId);

  if (filteredProducts.length === 0) {
    return null;
  }

  return (
    <ProductRecommendations
      title="You May Also Like"
      products={filteredProducts.slice(0, 4)}
      onProductClick={onProductClick}
      className={className}
    />
  );
}
