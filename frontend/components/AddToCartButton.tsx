"use client";

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

  const handleAddToCart = () => {
    // Ensure all required variants are selected
    const requiredVariants = Object.keys(product.variants || {});
    const allVariantsSelected = requiredVariants.every(
      (v) => selectedVariants[v]
    );

    if (requiredVariants.length > 0 && !allVariantsSelected) {
      // This case should ideally be prevented by disabling the button
      alert("Please select all product options.");
      return;
    }

    // Create cart item with Happy Hour metadata
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

    // Pass selectedVariants to cart context; price is computed in reducer
    addItem(cartItemData, selectedVariants, 1);
  };

  const isAddToCartDisabled = () => {
    if (product.stock === 0) return true;
    const requiredVariants = Object.keys(product.variants || {});
    console.log('isAddToCartDisabled for', product.name, ': requiredVariants=', requiredVariants, 'selectedVariants=', selectedVariants);
    if (requiredVariants.length === 0) return false; // No variants required
    return requiredVariants.some(variant => !selectedVariants[variant]);
  };

  return (
    <button
      onClick={handleAddToCart}
      disabled={isAddToCartDisabled()}
      className={`bg-[#a97456] text-white px-4 py-2 w-full rounded-lg font-semibold hover:bg-[#8a5a3d] transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed ${className}`}
    >
      Add to Cart
    </button>
  );
}
