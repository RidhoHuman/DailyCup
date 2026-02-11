'use client';

import { useWishlistStore, WishlistItem } from '@/lib/stores/wishlist-store';
import { useCart } from '@/contexts/CartContext';
import Header from '@/components/Header';
import { WishlistButton } from '@/components/wishlist/wishlist-button';
import Link from 'next/link';
import Image from 'next/image';
import { useSyncExternalStore } from 'react';

// Helper for hydration-safe mounting
const emptySubscribe = () => () => {};
const getSnapshot = () => true;
const getServerSnapshot = () => false;

export default function WishlistPage() {
  const { items } = useWishlistStore();
  const { addItem } = useCart();
  
  // Hydration-safe way to check if mounted
  const mounted = useSyncExternalStore(emptySubscribe, getSnapshot, getServerSnapshot);

  if (!mounted) {
    return null; // or a loading spinner
  }

  const handleAddToCart = (item: WishlistItem) => {
    addItem(
      {
        id: typeof item.id === 'string' ? parseInt(item.id) : item.id,
        name: item.name,
        base_price: item.price,
        description: '',
        image: item.image ?? null,
        is_featured: false,
        stock: 999,
        category_name: 'Wishlist Item',
        variants: {}
      },
      {},
      1
    );
    // Optional: remove from wishlist after adding to cart
    // removeItem(item.id);
  };

  return (
    <div className="min-h-screen bg-[var(--background)] text-[var(--foreground)] transition-colors duration-300">
      <Header />
      
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="flex items-center space-x-4 mb-8">
          <h1 className="text-3xl font-bold font-['Russo_One'] text-[#a15e3f]">My Wishlist</h1>
          <span className="bg-[#a15e3f]/10 text-[#a15e3f] px-3 py-1 rounded-full text-sm font-semibold">
            {items.length} items
          </span>
        </div>

        {items.length === 0 ? (
          <div className="text-center py-20 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div className="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
              <i className="bi bi-heart text-4xl text-gray-400"></i>
            </div>
            <h2 className="text-2xl font-semibold mb-2"> Your wishlist is empty</h2>
            <p className="text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">
              Save items you love here to buy them later.
            </p>
            <Link 
              href="/" 
              className="inline-block bg-[#a15e3f] text-white px-8 py-3 rounded-full font-semibold hover:bg-[#8a4f35] transition-colors"
            >
              Start Shopping
            </Link>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {items.map((item) => (
              <div 
                key={item.id} 
                className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden group hover:shadow-md transition-all"
              >
                <div className="relative h-48 bg-gray-100 dark:bg-gray-700">
                  <WishlistButton 
                    productId={item.id} 
                    className="absolute top-2 right-2 z-10 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-full shadow-sm"
                  />
                  {item.image ? (
                    <Image
                      src={item.image.startsWith('http') || item.image.startsWith('/') ? item.image : `/uploads/products/${item.image}`}
                      alt={item.name}
                      fill
                      className="object-cover group-hover:scale-105 transition-transform duration-300"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <i className="bi bi-cup text-3xl text-gray-300"></i>
                    </div>
                  )}
                </div>
                
                <div className="p-4">
                  <h3 className="font-semibold text-lg line-clamp-1 mb-1" title={item.name}>
                    {item.name}
                  </h3>
                  <div className="flex items-center justify-between mt-4">
                    <span className="font-bold text-[#a15e3f]">
                      Rp {item.price.toLocaleString('id-ID')}
                    </span>
                    <button
                      onClick={() => handleAddToCart(item)}
                      className="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center hover:bg-[#a15e3f] hover:text-white transition-colors"
                      title="Add to cart"
                    >
                      <i className="bi bi-cart-plus"></i>
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}
