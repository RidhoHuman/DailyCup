"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { api } from "@/lib/api-client";

interface Category {
  id: number;
  name: string;
}

export default function CreateProductPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [categories, setCategories] = useState<Category[]>([]);
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    price: "",
    category_id: "",
    stock: "",
    image: null as File | null,
  });

  useEffect(() => {
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    try {
      const response = await api.get<{ success: boolean; data: Category[] }>('/categories.php');
      if (response.success) {
        setCategories(response.data);
        if (response.data.length > 0) {
          setFormData(prev => ({ ...prev, category_id: response.data[0].id.toString() }));
        }
      }
    } catch (error) {
      console.error('Error fetching categories:', error);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const response = await api.post<{ success: boolean; message?: string }>('/products.php', {
        name: formData.name,
        description: formData.description,
        price: parseFloat(formData.price),
        category_id: parseInt(formData.category_id),
        stock: parseInt(formData.stock),
        is_featured: 0,
      });
      
      if (response.success) {
        alert("Product created successfully!");
        router.push("/admin/products");
      }
    } catch (error) {
      console.error("Error creating product:", error);
      alert("Failed to create product");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-3xl">
      <div className="mb-6">
        <Link 
          href="/admin/products"
          className="text-[#a97456] hover:text-[#8f6249] flex items-center gap-2 mb-4"
        >
          <i className="bi bi-arrow-left"></i> Back to Products
        </Link>
        <h1 className="text-2xl font-bold text-gray-800">Add New Product</h1>
        <p className="text-gray-500">Create a new product for your store</p>
      </div>

      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Product Name */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Product Name *
            </label>
            <input
              type="text"
              required
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              placeholder="e.g., Caffe Latte"
            />
          </div>

          {/* Description */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Description
            </label>
            <textarea
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              rows={4}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              placeholder="Describe your product..."
            />
          </div>

          {/* Category & Price */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Category *
              </label>
              <select
                value={formData.category_id}
                onChange={(e) => setFormData({ ...formData, category_id: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                required
              >
                {categories.length === 0 ? (
                  <option value="">No categories available</option>
                ) : (
                  categories.map(cat => (
                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                  ))
                )}
              </select>
              {categories.length === 0 && (
                <p className="text-xs text-red-500 mt-1">
                  Please create a category first in <Link href="/admin/categories" className="underline">Category Management</Link>
                </p>
              )}
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Price (IDR) *
              </label>
              <input
                type="number"
                required
                value={formData.price}
                onChange={(e) => setFormData({ ...formData, price: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                placeholder="25000"
              />
            </div>
          </div>

          {/* Stock */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Initial Stock *
            </label>
            <input
              type="number"
              required
              value={formData.stock}
              onChange={(e) => setFormData({ ...formData, stock: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              placeholder="50"
            />
          </div>

          {/* Image Upload (TODO: Implement file upload) */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Product Image (Coming Soon)
            </label>
            <input
              type="file"
              accept="image/*"
              disabled
              onChange={(e) => setFormData({ ...formData, image: e.target.files?.[0] || null })}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent opacity-50"
            />
            <p className="text-xs text-gray-500 mt-1">
              Image upload will be implemented soon
            </p>
          </div>

          {/* Actions */}
          <div className="flex gap-3 pt-4 border-t">
            <button
              type="submit"
              disabled={loading || categories.length === 0}
              className="flex-1 bg-[#a97456] text-white py-3 rounded-lg font-medium hover:bg-[#8f6249] transition-colors disabled:opacity-50"
            >
              {loading ? "Creating..." : "Create Product"}
            </button>
            <Link
              href="/admin/products"
              className="px-6 py-3 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors text-center"
            >
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </div>
  );
}
