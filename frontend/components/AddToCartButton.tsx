"use client";

import { useCart } from "../contexts/CartContext";
import { Product } from "../utils/api";

export interface AddToCartButtonProps {
  product: Product;
  selectedVariants?: Record<string, string>;
  finalPrice?: number;
  className?: string;
}

export default function AddToCartButton({
  product,
  selectedVariants = {},
  finalPrice,
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

    // Pass selectedVariants to cart context; price is computed in reducer
    addItem(product, selectedVariants, 1);
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
