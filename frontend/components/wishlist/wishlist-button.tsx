'use client';

import Image from 'next/image';
import { useWishlistStore } from '@/lib/stores/wishlist-store';
import { cn } from '@/lib/utils';
import { showToast } from '@/components/ui/toast-provider';

interface WishlistButtonProps {
  productId: string;
  productName?: string;
  productPrice?: number;
  productImage?: string;
  size?: 'sm' | 'md' | 'lg';
  variant?: 'icon' | 'full';
  className?: string;
}

export function WishlistButton({
  productId,
  productName = 'Product',
  productPrice = 0,
  productImage,
  size = 'md',
  variant = 'icon',
  className,
}: WishlistButtonProps) {
  const { isInWishlist, addItem, removeItem } = useWishlistStore();
  const isWishlisted = isInWishlist(productId);

  const handleToggle = () => {
    if (isWishlisted) {
      removeItem(productId);
      showToast.success(`${productName} removed from wishlist`);
    } else {
      addItem({ id: productId, name: productName, price: productPrice, image: productImage });
      showToast.success(`${productName} added to wishlist`);
    }
  };

  const sizes = {
    sm: 'w-4 h-4',
    md: 'w-5 h-5',
    lg: 'w-6 h-6',
  };

  const buttonSizes = {
    sm: 'p-1.5',
    md: 'p-2',
    lg: 'p-2.5',
  };

  if (variant === 'full') {
    return (
      <button
        onClick={handleToggle}
        className={cn(
          'flex items-center gap-2 px-4 py-2 rounded-lg transition-all duration-200',
          isWishlisted
            ? 'bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300',
          className
        )}
        aria-label={isWishlisted ? 'Remove from wishlist' : 'Add to wishlist'}
      >
        <svg
          className={cn(sizes[size], 'transition-transform duration-200')}
          fill={isWishlisted ? 'currentColor' : 'none'}
          viewBox="0 0 24 24"
          stroke="currentColor"
          strokeWidth={2}
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
          />
        </svg>
        <span className="text-sm font-medium">
          {isWishlisted ? 'Wishlisted' : 'Add to Wishlist'}
        </span>
      </button>
    );
  }

  return (
    <button
      onClick={handleToggle}
      className={cn(
        'rounded-full transition-all duration-200',
        buttonSizes[size],
        isWishlisted
          ? 'text-red-500 hover:text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/20'
          : 'text-gray-400 hover:text-red-500 bg-white/80 hover:bg-white dark:bg-gray-800/80 dark:hover:bg-gray-800',
        'shadow-sm hover:shadow',
        className
      )}
      aria-label={isWishlisted ? 'Remove from wishlist' : 'Add to wishlist'}
    >
      <svg
        className={cn(
          sizes[size],
          'transition-transform duration-200',
          isWishlisted && 'scale-110'
        )}
        fill={isWishlisted ? 'currentColor' : 'none'}
        viewBox="0 0 24 24"
        stroke="currentColor"
        strokeWidth={2}
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
        />
      </svg>
    </button>
  );
}

// Wishlist page component
interface WishlistItem {
  id: string;
  name: string;
  price: number;
  image?: string;
}

interface WishlistGridProps {
  items: WishlistItem[];
  onAddToCart?: (item: WishlistItem) => void;
  emptyMessage?: string;
}

export function WishlistGrid({
  items,
  onAddToCart,
  emptyMessage = 'Your wishlist is empty',
}: WishlistGridProps) {
  const { removeItem } = useWishlistStore();

  if (items.length === 0) {
    return (
      <div className="text-center py-12">
        <svg
          className="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
          />
        </svg>
        <p className="mt-4 text-gray-500 dark:text-gray-400">{emptyMessage}</p>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      {items.map((item) => (
        <div
          key={item.id}
          className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden group"
        >
          <div className="aspect-square bg-gray-100 dark:bg-gray-700 relative">
            {item.image ? (
              <Image
                src={item.image}
                alt={item.name}
                fill
                className="object-cover"
                sizes="(max-width: 768px) 50vw, 25vw"
              />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-gray-400">
                <svg className="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
            )}
            
            {/* Remove button */}
            <button
              onClick={() => {
                removeItem(item.id);
                showToast.success(`${item.name} removed from wishlist`);
              }}
              className="absolute top-2 right-2 p-1.5 bg-white/90 dark:bg-gray-800/90 rounded-full text-gray-500 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100"
              aria-label="Remove from wishlist"
            >
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div className="p-3">
            <h3 className="font-medium text-gray-900 dark:text-gray-100 truncate">
              {item.name}
            </h3>
            <p className="text-amber-600 font-semibold mt-1">
              Rp {item.price.toLocaleString('id-ID')}
            </p>
            
            {onAddToCart && (
              <button
                onClick={() => onAddToCart(item)}
                className="w-full mt-3 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors"
              >
                Add to Cart
              </button>
            )}
          </div>
        </div>
      ))}
    </div>
  );
}
