"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api-client";
import ImageUpload from "@/components/ImageUpload";

interface Category {
  id: number;
  name: string;
  description: string | null;
  image: string | null;
  product_count: number;
}

export default function CategoriesPage() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    image: null as File | null,
  });

  useEffect(() => {
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    try {
      setLoading(true);
      const response = await api.get<{ success: boolean; data: Category[] }>('/categories.php');
      if (response.success) {
        setCategories(response.data);
      }
    } catch (error) {
      console.error('Error fetching categories:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    try {
      if (editingId) {
        // Update
        await api.put(`/categories.php?id=${editingId}`, formData, { requiresAuth: true });
        alert('Category updated successfully!');
      } else {
        // Create
        await api.post('/categories.php', formData, { requiresAuth: true });
        alert('Category created successfully!');
      }
      
      setShowForm(false);
      setEditingId(null);
      setFormData({ name: "", description: "", image: null });
      fetchCategories();
    } catch (error) {
      console.error('Error saving category:', error);
      alert('Failed to save category');
    }
  };

  const handleEdit = (category: Category) => {
    setEditingId(category.id);
    setFormData({
      name: category.name,
      description: category.description || "",
      image: null,
    });
    setShowForm(true);
  };

  const handleDelete = async (id: number, name: string) => {
    try {
      // Fetch fresh category data to verify product count
      const freshData = await api.get<{ success: boolean; data: Category[] }>('/categories.php');
      
      if (freshData.success) {
        const category = freshData.data.find(c => c.id === id);
        
        if (category && category.product_count > 0) {
          alert(
            `⚠️ Cannot Delete Category\n\n` +
            `"${name}" has ${category.product_count} product(s).\n\n` +
            `Please remove or reassign all products from this category before deleting.`
          );
          return;
        }
      }

      const confirmed = confirm(
        `🗑️ Delete Category\n\n` +
        `Are you sure you want to delete "${name}"?\n\n` +
        `This action cannot be undone.`
      );
      
      if (!confirmed) return;

      const response = await api.delete<{ success: boolean; message: string }>(`/categories.php?id=${id}`, { requiresAuth: true });
      
      if (response.success) {
        alert('✅ Category deleted successfully!');
        await fetchCategories();
      } else {
        throw new Error(response.message || 'Delete failed');
      }
    } catch (error: unknown) {
      console.error('Error deleting category:', error);
      const errorMsg = error && typeof error === 'object' && 'message' in error 
        ? String(error.message) 
        : 'Failed to delete category';
      alert(`❌ Error: ${errorMsg}`);
    }
  };

  const handleCancel = () => {
    setShowForm(false);
    setEditingId(null);
    setFormData({ name: "", description: "", image: null });
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">Categories Management</h1>
          <p className="text-gray-500">Manage product categories</p>
        </div>
        <button
          onClick={() => setShowForm(true)}
          className="bg-[#a97456] text-white px-4 py-2 rounded-lg font-medium hover:bg-[#8f6249] transition-colors flex items-center gap-2"
        >
          <i className="bi bi-plus-lg"></i> Add Category
        </button>
      </div>

      {/* Form Modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <h2 className="text-xl font-bold text-gray-800 mb-4">
              {editingId ? 'Edit Category' : 'New Category'}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Category Name *
                </label>
                <input
                  type="text"
                  required
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  placeholder="e.g., Coffee"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Description
                </label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  rows={3}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  placeholder="Category description..."
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Category Image
                </label>
                <ImageUpload
                  type="category"
                  resourceId={editingId ? String(editingId) : "new"}
                  currentImage={undefined}
                  onUploadSuccess={(url) => {
                    alert('Category image uploaded successfully!');
                    fetchCategories();
                  }}
                  onUploadError={(error) => {
                    alert(`Upload failed: ${error}`);
                  }}
                />
              </div>
              <div className="flex gap-3 pt-4">
                <button
                  type="submit"
                  className="flex-1 bg-[#a97456] text-white py-2 rounded-lg font-medium hover:bg-[#8f6249] transition-colors"
                >
                  {editingId ? 'Update' : 'Create'}
                </button>
                <button
                  type="button"
                  onClick={handleCancel}
                  className="px-6 py-2 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Categories Table */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="p-12 text-center text-gray-500">Loading categories...</div>
        ) : categories.length === 0 ? (
          <div className="p-12 text-center text-gray-500">
            No categories found. Click &quot;Add Category&quot; to create one.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr className="text-left text-xs font-semibold text-gray-500 uppercase">
                  <th className="px-6 py-4">Name</th>
                  <th className="px-6 py-4">Description</th>
                  <th className="px-6 py-4">Products</th>
                  <th className="px-6 py-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {categories.map((category) => (
                  <tr key={category.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4 font-medium text-gray-800">
                      {category.name}
                    </td>
                    <td className="px-6 py-4 text-gray-600">
                      {category.description || '-'}
                    </td>
                    <td className="px-6 py-4">
                      <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {category.product_count} products
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex justify-end gap-2">
                        <button
                          onClick={() => handleEdit(category)}
                          className="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all"
                        >
                          <i className="bi bi-pencil"></i>
                        </button>
                        <button
                          onClick={() => handleDelete(category.id, category.name)}
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
        )}
      </div>
    </div>
  );
}
