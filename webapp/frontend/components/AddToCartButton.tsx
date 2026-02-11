"use client";

import { useRouter } from "next/navigation";
import { useCart } from "../contexts/CartContext";
import { Product } from "../utils/api";

export interface HappyHourDiscount {
  schedule_id: number;
  schedule_name: string;
  discount_percentage: number;
  start_time: string;
  end_time: string;
  final_price: number;
  original_price: number;
  savings: number;
  applied_via: 'category' | 'manual';
}

export interface AddToCartButtonProps {
  product: Product;
  selectedVariants?: Record<string, string>;
  finalPrice?: number;
  happyHourDiscount?: HappyHourDiscount;
  className?: string;
}

export default function AddToCartButton({
  product,
  selectedVariants = {},
  finalPrice,
  happyHourDiscount,
  className = "",
}: AddToCartButtonProps) {
  const { addItem } = useCart();
  const router = useRouter();

  const requiredVariants = Object.keys(product.variants || {});
  const hasVariants = requiredVariants.length > 0;
  const allVariantsSelected = requiredVariants.every(
    (v) => selectedVariants[v]
  );

  const handleClick = () => {
    // If product has variants and not all selected, go to product detail
    if (hasVariants && !allVariantsSelected) {
      router.push(`/product/${product.id}`);
      return;
    }

    // If stock is 0, do nothing
    if (product.stock === 0) return;

    // Otherwise, add to cart
    const cartItemData = {
      ...product,
      happyHour: happyHourDiscount ? {
        scheduleName: happyHourDiscount.schedule_name,
        discountPercentage: happyHourDiscount.discount_percentage,
        originalPrice: happyHourDiscount.original_price,
        discountedPrice: happyHourDiscount.final_price,
        savings: happyHourDiscount.savings,
      } : undefined
    };

    addItem(cartItemData, selectedVariants, 1);
  };

  const getButtonText = () => {
    if (product.stock === 0) return 'Sold Out';
    if (hasVariants && !allVariantsSelected) return 'Select Options';
    return 'Add to Cart';
  };

  const isDisabled = product.stock === 0;

  return (
    <button
      onClick={handleClick}
      disabled={isDisabled}
      className={`${
        isDisabled
          ? 'bg-gray-400 cursor-not-allowed' 
          : 'bg-[#a97456] hover:bg-[#8a5a3d]'
      } text-white px-4 py-2 w-full rounded-lg font-semibold transition-colors ${className}`}
    >
      {getButtonText()}
    </button>
  );
}
