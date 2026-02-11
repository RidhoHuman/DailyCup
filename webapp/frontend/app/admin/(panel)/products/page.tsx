'use client';

import Link from 'next/link';
import Image from 'next/image';
import { useState, useEffect } from 'react';
import { api } from '@/lib/api-client';

interface Product {
  id: number;
  name: string;
  price?: number;
  base_price?: number;
  category: string; // Updated to match API
  stock: number; // Updated to match API
  image: string | null;
}

export default function AdminProductsPage() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchProducts();
  }, []);

  const fetchProducts = async () => {
    try {
      setLoading(true);
      const response = await api.get<{ success: boolean; data: Product[] }>('/products.php');
      if (response.success) {
        setProducts(response.data);
      }
    } catch (error) {
      console.error('Error fetching products:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: number, name: string) => {
    if (!confirm(`Are you sure you want to delete "${name}"?`)) return;

    try {
      const response = await api.delete(`/products.php?id=${id}`) as { success: boolean };
      if (response.success) {
        alert('Product deleted successfully!');
        fetchProducts();
      }
    } catch (error) {
      console.error('Error deleting product:', error);
      alert('Failed to delete product');
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <div>
           <h1 className="text-2xl font-bold text-gray-800">Products Management</h1>
           <p className="text-gray-500">Manage your coffee and pastry inventory.</p>
        </div>
        <div className="flex gap-3">
          <Link 
            href="/admin/categories"
            className="bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-lg font-medium hover:bg-gray-50 transition-colors flex items-center gap-2"
          >
            <i className="bi bi-folder"></i> Manage Categories
          </Link>
          <Link 
            href="/admin/products/create"
            className="bg-[#a97456] text-white px-4 py-2 rounded-lg font-medium hover:bg-[#8f6249] transition-colors flex items-center gap-2"
          >
            <i className="bi bi-plus-lg"></i> Add New Product
          </Link>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {loading ? (
          <div className="p-12 text-center text-gray-500">Loading products...</div>
        ) : products.length === 0 ? (
          <div className="p-12 text-center text-gray-500">No products found</div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                    <th className="px-6 py-4">Product</th>
                    <th className="px-6 py-4">Category</th>
                    <th className="px-6 py-4">Price</th>
                    <th className="px-6 py-4">Stock</th>
                    <th className="px-6 py-4 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {products.map((product) => (
                    <tr key={product.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          <div className="w-10 h-10 rounded-lg bg-gray-100 relative overflow-hidden">
                            {product.image ? (
                              <Image 
                                src={product.image.startsWith('http') || product.image.startsWith('/') ? product.image : `/uploads/products/${product.image}`}
                                alt={product.name} 
                                fill 
                                className="object-cover" 
                              />
                            ) : (
                              <div className="w-full h-full flex items-center justify-center text-gray-400">
                                <i className="bi bi-image"></i>
                              </div>
                            )}
                          </div>
                          <span className="font-medium text-gray-800">{product.name}</span>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                          {product.category || 'Uncategorized'}
                        </span>
                      </td>
                      <td className="px-6 py-4 font-medium text-gray-600">
                        Rp {parseFloat((product.price || product.base_price || 0).toString()).toLocaleString('id-ID')}
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          <div className={`w-2 h-2 rounded-full ${product.stock > 20 ? 'bg-green-500' : product.stock > 0 ? 'bg-orange-500' : 'bg-red-500'}`}></div>
                          <span className="text-sm text-gray-600">{product.stock} units</span>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-right">
                        <div className="flex justify-end gap-2">
                          <Link
                            href={`/admin/products/edit/${product.id}`}
                            className="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all"
                          >
                            <i className="bi bi-pencil"></i>
                          </Link>
                          <button
                            onClick={() => handleDelete(product.id, product.name)}
                            className="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all"
                          >
                            <i className="bi bi-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            
            <div className="px-6 py-4 border-t border-gray-200 flex justify-between items-center text-sm text-gray-500">
              <span>Showing {products.length} entries</span>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
