'use client';

import { useCart } from '../../contexts/CartContext';
import Header from '../../components/Header';
import Image from 'next/image';
import Link from 'next/link';
import { useState } from 'react';
import { useRouter } from 'next/navigation';

export default function CartPage() {
  const { state, removeItem, updateQuantity, clearCart } = useCart();
  const router = useRouter();
  const [couponCode, setCouponCode] = useState("");
  const [appliedCoupon, setAppliedCoupon] = useState<string | null>(null);
  const [couponError, setCouponError] = useState("");
  const [deliveryMethod, setDeliveryMethod] = useState("delivery");

  // Coupon definitions (in a real app, this would come from API)
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
    
    // Navigate to checkout
    router.push('/checkout');
  };

  if (state.items.length === 0) {
    return (
      <div className="min-h-screen bg-[#f5f0ec]">
        <Header />
        <div className="max-w-4xl mx-auto px-4 py-8">
          <h1 className="text-3xl font-bold text-[#a97456] mb-8">My Cart</h1>
          <div className="text-center py-16">
            <div className="mb-4">
              <i className="bi bi-cart text-6xl text-gray-400"></i>
            </div>
            <h2 className="text-2xl font-semibold text-gray-700 mb-2">Your cart is empty</h2>
            <p className="text-gray-500 mb-6">Add some delicious items to get started!</p>
            <Link
              href="/"
              className="inline-block bg-[#a97456] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#8a5a3d] transition-colors"
            >
              Browse Menu
            </Link>
          </div>
        </div>
      </div>
    );
  }

  const deliveryFee = deliveryMethod === "delivery" ? 10000 : 0;
  
  // Calculate discount based on applied coupon
  const calculateDiscount = () => {
    if (!appliedCoupon) return 0;
    
    const coupon = coupons[appliedCoupon as keyof typeof coupons];
    if (coupon.type === "percentage") {
      return Math.round(state.total * coupon.discount);
    } else if (coupon.type === "fixed") {
      return coupon.discount;
    } else if (coupon.type === "shipping") {
      return deliveryFee;
    }
    return 0;
  };
  
  const discount = calculateDiscount();
  const finalTotal = state.total + deliveryFee - discount;

  return (
    <div className="min-h-screen bg-[#f5f0ec]">
      <Header />
      <div className="max-w-6xl mx-auto px-4 py-8">
        <div className="flex justify-between items-center mb-8">
          <h1 className="text-3xl font-bold text-[#a97456]">My Cart</h1>
          <button
            onClick={clearCart}
            className="text-red-600 hover:text-red-800 font-medium"
          >
            Clear Cart
          </button>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Cart Items */}
          <div className="lg:col-span-2 space-y-4">
            {state.items.map((item) => (
              <div key={item.id} className="bg-white rounded-lg p-6 shadow-sm border">
                <div className="flex items-center gap-4">
                  <div className="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                    {item.product.image ? (
                      <Image
                        src={`/products/${item.product.image}`}
                        alt={item.product.name}
                        width={80}
                        height={80}
                        className="rounded-lg object-cover"
                      />
                    ) : (
                      <i className="bi bi-cup text-3xl text-gray-400"></i>
                    )}
                  </div>

                  <div className="flex-1">
                    <h3 className="font-semibold text-lg text-gray-800">{item.product.name}</h3>
                    <p className="text-gray-600 text-sm mb-2">{item.product.description}</p>

                    <div className="flex items-center gap-4 text-sm text-gray-600">
                      {item.size && <span>Size: {item.size}</span>}
                      {item.temperature && <span>Temp: {item.temperature}</span>}
                    </div>

                    <div className="flex items-center justify-between mt-4">
                      <div className="flex items-center gap-3">
                        <button
                          onClick={() => updateQuantity(item.id, item.quantity - 1)}
                          className="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-gray-300"
                        >
                          -
                        </button>
                        <span className="font-medium">{item.quantity}</span>
                        <button
                          onClick={() => updateQuantity(item.id, item.quantity + 1)}
                          className="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-gray-300"
                        >
                          +
                        </button>
                      </div>

                      <div className="text-right">
                        <p className="font-semibold text-lg text-[#a97456]">
                          Rp {(item.totalPrice / item.quantity).toLocaleString()}
                        </p>
                        <button
                          onClick={() => removeItem(item.id)}
                          className="text-red-500 text-sm hover:text-red-700"
                        >
                          Remove
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Order Summary */}
          <div className="bg-white rounded-lg p-6 shadow-sm border h-fit">
            <h2 className="text-xl font-semibold text-gray-800 mb-6">Order Summary</h2>

            <div className="space-y-3 mb-6">
              <div className="flex justify-between">
                <span>Subtotal ({state.itemCount} items)</span>
                <span>Rp {state.total.toLocaleString()}</span>
              </div>
              <div className="flex justify-between">
                <span>Delivery Fee</span>
                <span>Rp {deliveryFee.toLocaleString()}</span>
              </div>
              <div className="flex justify-between">
                <span>Discount</span>
                <span className="text-green-600">-Rp {discount.toLocaleString()}</span>
              </div>
              <hr />
              <div className="flex justify-between font-semibold text-lg">
                <span>Total</span>
                <span className="text-[#a97456]">Rp {finalTotal.toLocaleString()}</span>
              </div>
            </div>

            <div className="mb-6">
              <label className="block text-sm font-medium mb-2">Coupon Code</label>
              <div className="flex gap-2">
                <input
                  type="text"
                  value={couponCode}
                  onChange={(e) => setCouponCode(e.target.value)}
                  placeholder="Enter coupon code"
                  className="flex-1 p-2 border border-gray-300 rounded text-sm"
                />
                <button 
                  onClick={applyCoupon}
                  className="px-4 py-2 bg-[#a97456] text-white rounded text-sm hover:bg-[#8a5a3d] transition-colors"
                >
                  Apply
                </button>
              </div>
              {couponError && (
                <p className="text-red-500 text-sm mt-1">{couponError}</p>
              )}
              {appliedCoupon && (
                <div className="mt-2 p-2 bg-green-50 border border-green-200 rounded text-sm">
                  <div className="flex justify-between items-center">
                    <span className="text-green-700">
                      ✓ {coupons[appliedCoupon as keyof typeof coupons].description}
                    </span>
                    <button 
                      onClick={removeCoupon}
                      className="text-red-500 hover:text-red-700 text-sm"
                    >
                      Remove
                    </button>
                  </div>
                </div>
              )}
            </div>

            <div className="mb-6">
              <label className="block text-sm font-medium mb-2">Delivery Method</label>
              <div className="space-y-2">
                <label className="flex items-center">
                  <input 
                    type="radio" 
                    name="delivery" 
                    value="delivery" 
                    checked={deliveryMethod === "delivery"}
                    onChange={(e) => setDeliveryMethod(e.target.value)}
                    className="mr-2" 
                  />
                  <span className="text-sm">Delivery (Rp {deliveryFee.toLocaleString()})</span>
                </label>
                <label className="flex items-center">
                  <input 
                    type="radio" 
                    name="delivery" 
                    value="dine-in" 
                    checked={deliveryMethod === "dine-in"}
                    onChange={(e) => setDeliveryMethod(e.target.value)}
                    className="mr-2" 
                  />
                  <span className="text-sm">Dine In (Free)</span>
                </label>
              </div>
            </div>

            <button 
              onClick={handleCheckout}
              className="w-full bg-[#a97456] text-white py-3 rounded-lg font-semibold hover:bg-[#8a5a3d] transition-colors"
            >
              Proceed to Checkout
            </button>

            <Link
              href="/"
              className="block text-center mt-4 text-[#a97456] hover:text-[#8a5a3d] font-medium"
            >
              Continue Shopping
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}