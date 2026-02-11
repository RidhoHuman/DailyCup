"use client";

import Header from "../../components/Header";
import { useCart } from "../../contexts/CartContext";
import { useState } from "react";
import { useRouter } from "next/navigation";
import AddressForm from "@/components/checkout/AddressForm";
import PaymentMethodSelector, { PaymentMethodType } from "@/components/checkout/PaymentMethodSelector";
import Link from "next/link";
import Image from "next/image";
import { api as apiClient } from "@/lib/api-client";
import { useAuthStore } from "@/lib/stores/auth-store";

// Define form data type for checkout
interface CheckoutFormData {
  fullName: string;
  email: string;
  phone: string;
  province: string;
  city: string;
  district: string;
  addressDetail: string;
  saveAddress?: boolean;
}

interface OrderResponse {
    success: boolean;
    orderId: string;
    mock?: boolean;
    redirect?: string;
    midtrans?: unknown;
    xendit?: unknown;
    invoice_url?: string;
}

export default function CheckoutPage() {
  const { state, clearCart } = useCart();
  const router = useRouter();
  const { user } = useAuthStore();
  
  const [selectedPayment, setSelectedPayment] = useState<PaymentMethodType | null>('xendit');
  const [isProcessing, setIsProcessing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Cart Calculation
  const deliveryFee = 15000; 
  const subtotal = state.total;
  const total = subtotal + deliveryFee;

  if (state.itemCount === 0) {
    return (
      <div className="min-h-screen bg-[#f5f0ec]">
        <Header />
        <div className="max-w-7xl mx-auto px-4 py-20 text-center">
            <div className="w-24 h-24 bg-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm">
                <i className="bi bi-cart-x text-4xl text-gray-300"></i>
            </div>
            <h1 className="text-2xl font-bold text-gray-800 mb-4">Your cart is empty</h1>
            <p className="text-gray-600 mb-8">Looks like you haven&apos;t added any coffee yet.</p>
            <Link href="/menu" className="bg-[#a97456] text-white px-8 py-3 rounded-full hover:bg-[#8a4f35] transition-colors">
                Browse Menu
            </Link>
        </div>
      </div>
    );
  }

  const handleCheckoutSubmit = async (formData: CheckoutFormData) => {
    setIsProcessing(true);
    setError(null);

    try {
      // Map cart items to API format
      const items = state.items.map(item => ({
        id: item.product.id, // Already a number, no need to parseInt
        name: item.product.name,
        price: item.totalPrice / item.quantity,
        quantity: item.quantity,
        size: item.selectedVariants?.size,
        temperature: item.selectedVariants?.temperature,
        // Calculate subtotal for this item including variants if needed?
        // For now using base price to match mock data structure
      }));

      const deliveryAddress = `${formData.addressDetail}, ${formData.district}, ${formData.city}, ${formData.province}`;

      // Call API
      const response = await apiClient.post<OrderResponse>('/create_order.php', {
        items,
        total: total,
        subtotal: subtotal,
        deliveryFee: deliveryFee,
        discount: 0,
        customer: {
          name: formData.fullName,
          email: formData.email,
          phone: formData.phone,
          address: deliveryAddress
        },
        paymentMethod: selectedPayment,
        deliveryMethod: 'delivery',
        notes: '' // Could add notes field to form
      });

      if (response.success && response.orderId) {
        // Clear cart
        clearCart();
        
        // Handle redirect based on payment gateway response
        if (response.invoice_url) {
            // Xendit: redirect to payment page with invoice_url
            const paymentUrl = `/checkout/payment?orderId=${response.orderId}&invoice_url=${encodeURIComponent(response.invoice_url)}`;
            router.push(paymentUrl);
        } else if (response.redirect) {
             // Mock payment: use the redirect URL from backend
             router.push(response.redirect);
        } else {
             // Fallback: go to success page
             router.push(`/checkout/success?orderId=${response.orderId}`);
        }
      } else {
        throw new Error("Failed to create order");
      }

    } catch (err: unknown) {
        console.error("Checkout Error:", err);
        const msg = err instanceof Error ? err.message : "Checkout failed. Please try again.";
        setError(msg);
    } finally {
        setIsProcessing(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#f5f0ec] dark:bg-[#1a1a1a] transition-colors duration-300">
      <Header />
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 className="text-3xl font-bold text-[#a97456] font-['Russo_One'] mb-8">Checkout</h1>
        
        {error && (
            <div className="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded-lg flex items-center gap-2">
                <i className="bi bi-exclamation-circle-fill"></i>
                {error}
            </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          {/* LEFT COLUMN: Shipping Form */}
          <div className="lg:col-span-2 space-y-6">
            <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
              <h2 className="text-xl font-semibold mb-6 flex items-center gap-2">
                <span className="w-8 h-8 bg-[#a97456] text-white rounded-full flex items-center justify-center text-sm">1</span>
                Shipping Information
              </h2>
              
              <AddressForm id="checkout-form" onSubmit={handleCheckoutSubmit} />

            </div>

             {/* Payment Method Selector */}
             <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <h2 className="text-xl font-semibold mb-6 flex items-center gap-2">
                    <span className="w-8 h-8 bg-[#a97456] text-white rounded-full flex items-center justify-center text-sm">2</span>
                    Payment Method
                </h2>
                
                <PaymentMethodSelector 
                    selected={selectedPayment} 
                    onSelect={setSelectedPayment} 
                />
             </div>
          </div>


          {/* RIGHT COLUMN: Order Summary */}
          <div className="lg:col-span-1">
            <div className="bg-white dark:bg-[#2a2a2a] p-6 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 sticky top-24">
              <h2 className="text-xl font-semibold mb-6">Order Summary</h2>
              
              <div className="space-y-4 mb-6 max-h-60 overflow-y-auto custom-scrollbar">
                {state.items.map((item) => (
                    <div key={item.id} className="flex gap-4">
                        <div className="w-16 h-16 bg-gray-100 rounded-lg relative overflow-hidden flex-shrink-0">
                             {item.product.image ? (
                                <Image src={`/products/${item.product.image}`} alt={item.product.name} fill className="object-cover" />
                             ) : (
                                <div className="w-full h-full flex items-center justify-center text-gray-400">☕</div>
                             )}
                             <span className="absolute bottom-0 right-0 bg-black/50 text-white text-xs px-1.5 py-0.5 rounded-tl">
                                x{item.quantity}
                             </span>
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium truncate">{item.product.name}</p>
                            <p className="text-xs text-gray-500">
                                {item.selectedVariants ? 
                                    Object.values(item.selectedVariants).join(', ') : 'Standard'}
                            </p>
                            <p className="text-sm font-semibold text-[#a97456]">Rp {item.totalPrice.toLocaleString()}</p>
                        </div>
                    </div>
                ))}
              </div>

              <div className="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Subtotal</span>
                  <span className="font-medium">Rp {subtotal.toLocaleString()}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Shipping (JNE Reg)</span>
                  <span className="font-medium">Rp {deliveryFee.toLocaleString()}</span>
                </div>
                <div className="flex justify-between text-lg font-bold pt-2 border-t mt-2">
                  <span>Total</span>
                  <span className="text-[#a97456]">Rp {total.toLocaleString()}</span>
                </div>
              </div>

              <button 
                type="submit" 
                form="checkout-form"
                disabled={isProcessing}
                className="w-full mt-6 bg-[#a97456] text-white py-4 rounded-xl font-bold shadow-lg hover:shadow-xl hover:bg-[#8f6249] transition-all transform active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                {isProcessing ? (
                    <>
                        <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        Processing...
                    </>
                ) : (
                    <>
                        Place Order <i className="bi bi-arrow-right"></i>
                    </>
                )}
              </button>
              
              <p className="text-xs text-center text-gray-400 mt-4">
                <i className="bi bi-shield-lock-fill mr-1"></i>
                Secure Encrypted Payment
              </p>
            </div>
          </div>

        </div>
      </div>
    </div>
  );
}

