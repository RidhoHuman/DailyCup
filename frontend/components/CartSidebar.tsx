'use client';

import { useCart } from '../contexts/CartContext';
import Image from 'next/image';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/stores/auth-store';

export default function CartSidebar() {
  const router = useRouter();
  const { isAuthenticated } = useAuthStore();
  const { state, removeItem, updateQuantity } = useCart();
  const [couponCode, setCouponCode] = useState("");
  const [appliedCoupon, setAppliedCoupon] = useState<string | null>(null);
  const [couponError, setCouponError] = useState("");

  // Coupon definitions (same as cart page)
  const coupons = {
    "WELCOME10": { discount: 0.1, type: "percentage", description: "10% off welcome discount" },
    "SAVE5000": { discount: 5000, type: "fixed", description: "Rp 5,000 off" },
    "FREESHIP": { discount: 10000, type: "shipping", description: "Free shipping" }
  };

  const applyCoupon = () => {
    try {
      const code = couponCode.trim().toUpperCase();
      if (!code) return;

      if (coupons[code as keyof typeof coupons]) {
        console.log("Applying coupon:", code);
        setAppliedCoupon(code);
        setCouponError("");
        setCouponCode("");
      } else {
        setCouponError("Invalid coupon code");
        setAppliedCoupon(null);
      }
    } catch (err) {
      console.error("Error in applyCoupon:", err);
      setCouponError("Failed to apply coupon. Please try again.");
    }
  };

  const removeCoupon = () => {
    setAppliedCoupon(null);
    setCouponError("");
  };

  const handleCheckout = () => {
    if (state.items.length === 0) return;
    
    if (isAuthenticated) {
      router.push('/checkout');
    } else {
      router.push('/login');
    }
  };

  // Calculate discount
  const calculateDiscount = () => {
    if (!appliedCoupon) return 0;
    
    const coupon = coupons[appliedCoupon as keyof typeof coupons];
    const deliveryFee = 10000;
    
    if (coupon.type === "percentage") {
      return Math.round(state.total * coupon.discount);
    } else if (coupon.type === "fixed") {
      return coupon.discount;
    } else if (coupon.type === "shipping") {
      return deliveryFee;
    }
    return 0;
  };

  const deliveryFee = 10000;
  const discount = calculateDiscount();
  const finalTotal = state.total + deliveryFee - discount;

  if (state.items.length === 0) {
    return (
      <aside className="fixed right-4 top-20 w-96 h-[600px] bg-gray-100 rounded-lg border border-gray-200 shadow-lg z-50">
        <div className="p-4 bg-[#a97456] rounded-t-lg">
          <h2 className="text-white text-2xl font-bold text-center">My Cart</h2>
        </div>
        <div className="p-4 text-center text-gray-500">
          <div className="mb-4">
            <i className="bi bi-cart text-4xl"></i>
          </div>
          <p>Your cart is empty</p>
          <p className="text-sm">Add some delicious items!</p>
        </div>
      </aside>
    );
  }

  return (
    <aside className="fixed right-4 top-20 w-96 max-h-[600px] bg-gray-100 rounded-lg border border-gray-200 shadow-lg z-50 overflow-hidden">
      <div className="p-4 bg-[#a97456] rounded-t-lg">
        <h2 className="text-white text-2xl font-bold text-center">My Cart</h2>
      </div>

      <div className="flex-1 overflow-y-auto max-h-[400px] p-4">
        {state.items.map((item) => (
          <div key={item.id} className="flex items-center gap-3 mb-4 p-2 bg-white rounded-lg">
            <div className="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
              {item.product.image ? (
                <Image
                  src={`/products/${item.product.image}`}
                  alt={item.product.name}
                  width={64}
                  height={64}
                  className="rounded-lg object-cover"
                />
              ) : (
                <i className="bi bi-cup text-2xl text-gray-400"></i>
              )}
            </div>
            <div className="flex-1">
              <h3 className="font-semibold text-sm">{item.product.name}</h3>
              {item.size && <p className="text-xs text-gray-600">Size: {item.size}</p>}
              {item.temperature && <p className="text-xs text-gray-600">Temp: {item.temperature}</p>}
              <div className="flex items-center gap-2 mt-1">
                <button
                  onClick={() => updateQuantity(item.id, item.quantity - 1)}
                  className="w-6 h-6 bg-gray-200 rounded flex items-center justify-center text-sm"
                >
                  -
                </button>
                <span className="text-sm">{item.quantity}</span>
                <button
                  onClick={() => updateQuantity(item.id, item.quantity + 1)}
                  className="w-6 h-6 bg-gray-200 rounded flex items-center justify-center text-sm"
                >
                  +
                </button>
              </div>
            </div>
            <div className="text-right">
              <p className="font-semibold text-sm">Rp {item.totalPrice.toLocaleString()}</p>
              <button
                onClick={() => removeItem(item.id)}
                className="text-red-500 text-xs mt-1"
              >
                Remove
              </button>
            </div>
          </div>
        ))}
      </div>

      <div className="border-t border-gray-200 p-4 bg-white">
        <div className="space-y-2 mb-4">
          <div className="flex justify-between text-sm">
            <span>Subtotal:</span>
            <span>Rp {state.total.toLocaleString()}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span>Delivery Fee:</span>
            <span>Rp {deliveryFee.toLocaleString()}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span>Discount:</span>
            <span className="text-green-600">-Rp {discount.toLocaleString()}</span>
          </div>
          <hr />
          <div className="flex justify-between font-semibold">
            <span>Total:</span>
            <span>Rp {finalTotal.toLocaleString()}</span>
          </div>
        </div>

        <div className="mb-4">
          <input
            type="text"
            value={couponCode}
            onChange={(e) => setCouponCode(e.target.value)}
            placeholder="Apply Coupon Code"
            className="w-full p-2 border border-gray-300 rounded text-sm mb-2"
          />
          <button 
            onClick={applyCoupon}
            className="w-full bg-[#a97456] text-white py-2 rounded text-sm hover:bg-[#8a5a3d] transition-colors"
          >
            Apply Coupon
          </button>
          {couponError && (
            <p className="text-red-500 text-xs mt-1">{couponError}</p>
          )}
          {appliedCoupon && (
            <div className="mt-2 p-2 bg-green-50 border border-green-200 rounded text-xs">
              <div className="flex justify-between items-center">
                <span className="text-green-700">
                  ✓ {coupons[appliedCoupon as keyof typeof coupons].description}
                </span>
                <button 
                  onClick={removeCoupon}
                  className="text-red-500 hover:text-red-700 text-xs ml-2"
                >
                  ✕
                </button>
              </div>
            </div>
          )}
        </div>

        <button 
          onClick={handleCheckout}
          className="w-full bg-[#a97456] text-white py-3 rounded-lg font-semibold hover:bg-[#8a5a3d] transition-colors"
        >
          Checkout!
        </button>
      </div>
    </aside>
  );
}