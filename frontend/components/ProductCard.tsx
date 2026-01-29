"use client";

import { useState, useMemo } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { Product } from '../utils/api';
import AddToCartButton from './AddToCartButton';
import { WishlistButton } from './wishlist/wishlist-button';
import StarRating from './StarRating';
import { SocialShare } from './SocialShare';

interface ProductCardProps {
  product: Product;
}

export default function ProductCard({ product }: ProductCardProps) {
  // State to hold selected variants
  const [selectedVariants, setSelectedVariants] = useState<Record<string, string>>(() => {
    const initialVariants: Record<string, string> = {};
    if (product.variants) {
      Object.keys(product.variants).forEach(key => {
        const variant = product.variants?.[key];
        if (variant && variant.length > 0) {
          initialVariants[key] = variant[0].value;
        }
      });
    }
    return initialVariants;
  });

  // Calculate the final price based on selected variants
  const finalPrice = useMemo(() => {
    let price = product.price || product.base_price || 0;
    if (product.variants) {
      Object.entries(selectedVariants).forEach(([variantType, value]) => {
        const variantOptions = product.variants?.[variantType];
        const variant = variantOptions?.find(v => v.value === value);
        if (variant) {
          price += variant.price_adjustment;
        }
      });
    }
    return price;
  }, [product, selectedVariants]);

  const handleVariantChange = (variantType: string, value: string) => {
    setSelectedVariants(prev => ({
      ...prev,
      [variantType]: value,
    }));
  };

  return (
    <div
      key={product.id}
      className="bg-white dark:bg-[#2a2a2a] rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-all flex flex-col border dark:border-gray-700"
    >
      <div className="relative h-48 bg-gradient-to-br from-[#f6efe9] to-[#e8d5c4] dark:from-gray-700 dark:to-gray-600">
        <div className="absolute top-2 left-2 z-10 flex gap-1">
           <WishlistButton 
              productId={String(product.id)}
              productName={product.name}
              productPrice={finalPrice}
              productImage={product.image || undefined}
              className="bg-white/80 hover:bg-white rounded-full shadow-sm backdrop-blur-sm"
           />
           <SocialShare
              url={`/product/${product.id}`}
              title={product.name}
              description={product.description || `${product.name} - Rp ${finalPrice.toLocaleString()}`}
              variant="icon"
              className="bg-white/80 hover:bg-white shadow-sm backdrop-blur-sm"
           />
        </div>
        {product.image ? (
          <Image
            src={`/products/${product.image}`}
            alt={product.name}
            fill
            className="object-cover"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <i className="bi bi-cup text-4xl text-[#a97456]"></i>
          </div>
        )}
        {product.is_featured && (
          <div className="absolute top-2 right-2 bg-[#a97456] text-white px-2 py-1 rounded-full text-xs font-semibold">
            Featured
          </div>
        )}
      </div>

      <div className="p-4 flex flex-col flex-grow">
        <div className="mb-2">
          <span className="text-xs text-[#a97456] font-medium bg-[#a97456]/10 px-2 py-1 rounded">
            {product.category_name}
          </span>
        </div>

        <Link href={`/product/${product.id}`}>
          <h3 className="font-semibold text-gray-900 dark:text-gray-100 mb-2 line-clamp-2 flex-grow hover:text-coffee-600 transition-colors cursor-pointer">
            {product.name}
          </h3>
        </Link>

        {/* Rating Display */}
        {product.average_rating && product.total_reviews ? (
          <div className="flex items-center gap-2 mb-3">
            <StarRating rating={product.average_rating} size="sm" />
            <span className="text-xs text-gray-600 dark:text-gray-400">
              ({product.total_reviews})
            </span>
          </div>
        ) : (
          <div className="mb-3 h-5" /> 
        )}
        
        {/* Variant Selection */}
        {product.variants && Object.keys(product.variants).length > 0 && (
          <div className="space-y-2 mb-3">
            {Object.entries(product.variants).map(([type, options]) => (
              <div key={type}>
                <label className="text-sm font-medium text-gray-600 dark:text-gray-400 capitalize">{type}</label>
                <div className="flex flex-wrap gap-2 mt-1">
                  {options.map(option => (
                    <button
                      key={option.value}
                      onClick={() => handleVariantChange(type, option.value)}
                      className={`px-3 py-1 text-sm rounded-full border transition-colors ${
                        selectedVariants[type] === option.value
                          ? 'bg-[#a97456] text-white border-[#a97456]'
                          : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600'
                      }`}
                    >
                      {option.value}
                    </button>
                  ))}
                </div>
              </div>
            ))}
          </div>
        )}

        <div className="mt-auto">
          <div className="flex items-center justify-between mb-3">
            <div className="flex items-center gap-1">
              <span className="text-lg font-bold text-[#a97456]">
                Rp {finalPrice.toLocaleString()}
              </span>
              {product.stock <= 5 && product.stock > 0 && (
                <span className="text-xs text-orange-600 bg-orange-100 px-2 py-1 rounded">
                  Low stock
                </span>
              )}
              {product.stock === 0 && (
                <span className="text-xs text-red-600 bg-red-100 px-2 py-1 rounded">
                  Out of stock
                </span>
              )}
            </div>
          </div>

          <AddToCartButton
            product={product}
            selectedVariants={selectedVariants}
            finalPrice={finalPrice}
            className={`w-full ${product.stock === 0 ? "opacity-50 cursor-not-allowed" : ""}`}
          />
        </div>
      </div>
    </div>
  );
}
