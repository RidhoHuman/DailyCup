'use client';

import { useState, useEffect } from 'react';
import Image from 'next/image';
import { fetchProducts, Product } from '../utils/api';
import AddToCartButton from './AddToCartButton';
import { SocialShare } from './SocialShare';
import { getImageUrl } from '@/lib/storage';

export default function FeaturedProducts() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadProducts = async () => {
      try {
        const data = await fetchProducts();
        // Show only featured products or first 6 products
        const featured = data.filter(p => p.is_featured).slice(0, 6);
        setProducts(featured.length > 0 ? featured : data.slice(0, 6));
      } catch (error) {
        console.error('Error loading products:', error);
        // Fallback to mock data
        setProducts([
          {
            id: 1,
            name: 'Espresso',
            description: 'Rich and bold espresso shot',
            base_price: 25000,
            image: null,
            is_featured: true,
            stock: 100,
            category_name: 'Coffee',
            variants: {
              size: [
                { value: 'Regular', price_adjustment: 0 },
                { value: 'Large', price_adjustment: 5000 }
              ],
              temperature: [
                { value: 'Hot', price_adjustment: 0 }
              ]
            }
          },
          {
            id: 2,
            name: 'Cappuccino',
            description: 'Classic cappuccino with perfect foam',
            base_price: 35000,
            image: null,
            is_featured: true,
            stock: 100,
            category_name: 'Coffee',
            variants: {
              size: [
                { value: 'Regular', price_adjustment: 0 },
                { value: 'Large', price_adjustment: 5000 }
              ],
              temperature: [
                { value: 'Hot', price_adjustment: 0 },
                { value: 'Iced', price_adjustment: 2000 }
              ]
            }
          }
        ]);
      } finally {
        setLoading(false);
      }
    };

    loadProducts();
  }, []);

  if (loading) {
    return (
      <section className="py-16 px-6">
        <div className="max-w-7xl mx-auto">
          <h2 className="text-4xl font-bold text-center text-[#a15e3f] mb-12 font-['Quantico']">
            Featured Products
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[...Array(6)].map((_, i) => (
              <div key={i} className="bg-white rounded-2xl shadow-lg overflow-hidden animate-pulse">
                <div className="h-48 bg-gray-200"></div>
                <div className="p-6">
                  <div className="h-6 bg-gray-200 rounded mb-2"></div>
                  <div className="h-4 bg-gray-200 rounded mb-4"></div>
                  <div className="h-10 bg-gray-200 rounded"></div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>
    );
  }

  return (
    <section className="py-16 px-6">
      <div className="max-w-7xl mx-auto">
        <h2 className="text-4xl font-bold text-center text-[#a15e3f] mb-12 font-['Quantico']">
          Featured Products
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {products.map((product) => (
            <div key={product.id} className="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow relative group">
              <div className="relative h-48 bg-gradient-to-br from-[#f6efe9] to-[#e8d5c4]">
                <div className="absolute top-2 right-2 z-10 opacity-0 group-hover:opacity-100 transition-opacity">
                  <SocialShare
                    url={`/product/${product.id}`}
                    title={product.name}
                    description={product.description || ''}
                    variant="icon"
                    className="bg-white/90 hover:bg-white shadow-md backdrop-blur-sm"
                  />
                </div>
                {product.image ? (
                  <Image
                    src={getImageUrl(product.image) || '/assets/image/cup.png'}
                    alt={product.name}
                    fill
                    className="object-cover"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center">
                    <i className="bi bi-cup text-6xl text-[#a97456]"></i>
                  </div>
                )}
                <div className="absolute top-4 left-4 bg-[#a97456] text-white px-2 py-1 rounded-full text-xs font-semibold">
                  {product.category_name}
                </div>
              </div>

              <div className="p-6">
                <h3 className="text-xl font-semibold text-gray-800 mb-2">{product.name}</h3>
                <p className="text-gray-600 text-sm mb-4 line-clamp-2">{product.description}</p>
                <div className="flex items-center justify-between">
                  <span className="text-2xl font-bold text-[#a97456]">
                    Rp {(product.price || product.base_price || 0).toLocaleString()}
                  </span>
                  <AddToCartButton product={product} className="text-sm px-3 py-2" />
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}