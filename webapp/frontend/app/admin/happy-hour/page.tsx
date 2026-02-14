'use client';

import { useState, useEffect } from 'react';
import api from '@/lib/api-client';
import { useAuthStore } from '@/lib/stores/auth-store';
import { getErrorMessage } from '@/lib/utils';
import { useRouter } from 'next/navigation';

// TypeScript interfaces
interface HappyHourSchedule {
  id: number;
  name: string;
  start_time: string;
  end_time: string;
  days_of_week: string[];
  discount_percentage: number;
  apply_to_category: string | null;
  is_active: boolean;
  product_count: number;
  products: HappyHourProduct[];
}

interface HappyHourProduct {
  id: number;
  name: string;
  category: string;
  original_price: number;
  discounted_price: number;
  savings: number;
  image: string;
}

interface Product {
  id: number;
  name: string;
  base_price: number;
  category_name: string;
  image: string;
}

export default function HappyHourManagementPage() {
  const { user } = useAuthStore();
  const router = useRouter();
  
  const [schedules, setSchedules] = useState<HappyHourSchedule[]>([]);
  const [allProducts, setAllProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingSchedule, setEditingSchedule] = useState<HappyHourSchedule | null>(null);
  
  // Form state
  const [formData, setFormData] = useState({
    name: '',
    start_time: '',
    end_time: '',
    days_of_week: [] as string[],
    discount_percentage: 20,
    apply_to_category: 'Coffee', // Default to Coffee category
    product_ids: [] as number[],
    is_active: true
  });

  // Check admin access
  useEffect(() => {
    if (!user || user.role !== 'admin') {
      router.push('/');
    } else {
      fetchSchedules();
      fetchProducts();
    }
  }, [user]);

  const fetchSchedules = async () => {
    try {
      setLoading(true);
      const response = await api.get<any>('/happy_hour/get_schedules.php', { requiresAuth: true });
      if (response.success) {
        setSchedules(response.schedules);
      }
    } catch (error) {
      console.error('Error fetching schedules:', error);
      alert('Gagal memuat jadwal Happy Hour');
    } finally {
      setLoading(false);
    }
  };

  const fetchProducts = async () => {
    try {
      const response = await api.get<any>('/products.php');
      if (response.success && response.products) {
        setAllProducts(response.products);
      }
    } catch (error) {
      console.error('Error fetching products:', error);
    }
  };

  const handleCreateNew = () => {
    setEditingSchedule(null);
    setFormData({
      name: '',
      start_time: '',
      end_time: '',
      days_of_week: [],
      discount_percentage: 20,
      apply_to_category: 'Coffee',
      product_ids: [],
      is_active: true
    });
    setShowModal(true);
  };

  const handleEdit = (schedule: HappyHourSchedule) => {
    setEditingSchedule(schedule);
    setFormData({
      name: schedule.name,
      start_time: schedule.start_time,
      end_time: schedule.end_time,
      days_of_week: schedule.days_of_week,
      discount_percentage: schedule.discount_percentage,
      apply_to_category: schedule.apply_to_category || 'Coffee',
      product_ids: schedule.products.map(p => p.id),
      is_active: schedule.is_active
    });
    setShowModal(true);
  };

  const handleSave = async () => {
    // Validation
    if (!formData.name || !formData.start_time || !formData.end_time) {
      alert('Mohon lengkapi nama dan waktu');
      return;
    }
    
    if (formData.days_of_week.length === 0) {
      alert('Pilih minimal 1 hari');
      return;
    }
    
    // Category-based: No need to check product_ids
    // Category is automatically applied to all products in that category

    try {
      const payload = {
        ...formData,
        ...(editingSchedule && { id: editingSchedule.id })
      };

      const response = await api.post<any>(
        '/happy_hour/manage_schedule.php',
        payload,
        { requiresAuth: true }
      );

      if (response.success) {
        alert(editingSchedule ? 'Jadwal berhasil diupdate!' : 'Jadwal berhasil dibuat!');
        setShowModal(false);
        fetchSchedules();
      }
    } catch (error: unknown) {
      console.error('Error saving schedule:', getErrorMessage(error));
      alert(getErrorMessage(error) || 'Gagal menyimpan jadwal');
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Yakin ingin menghapus jadwal ini?')) return;

    try {
      await api.delete(`/happy_hour/manage_schedule.php?id=${id}`, { requiresAuth: true });
      alert('Jadwal berhasil dihapus');
      fetchSchedules();
    } catch (error) {
      console.error('Error deleting schedule:', error);
      alert('Gagal menghapus jadwal');
    }
  };

  const toggleDay = (day: string) => {
    setFormData(prev => ({
      ...prev,
      days_of_week: prev.days_of_week.includes(day)
        ? prev.days_of_week.filter(d => d !== day)
        : [...prev.days_of_week, day]
    }));
  };

  const toggleProduct = (productId: number) => {
    setFormData(prev => ({
      ...prev,
      product_ids: prev.product_ids.includes(productId)
        ? prev.product_ids.filter(id => id !== productId)
        : [...prev.product_ids, productId]
    }));
  };

  const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
  const dayLabels: Record<string, string> = {
    monday: 'Senin',
    tuesday: 'Selasa',
    wednesday: 'Rabu',
    thursday: 'Kamis',
    friday: 'Jumat',
    saturday: 'Sabtu',
    sunday: 'Minggu'
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  function getImageUrl(image: string): string {
    if (!image) return '/assets/image/cup.png';
    if (image.startsWith('http') || image.startsWith('/')) return image;
    // Assume image is a filename stored in /uploads/products/
    return `/uploads/products/${image}`;
  }

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Happy Hour Management</h1>
            <p className="text-gray-600 mt-2">Kelola jadwal promosi diskon otomatis</p>
          </div>
          <button
            onClick={handleCreateNew}
            className="bg-[#a97456] hover:bg-[#8b5e3c] text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition-colors"
          >
            + Buat Jadwal Baru
          </button>
        </div>

        {/* Schedules List */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {schedules.map((schedule) => (
            <div
              key={schedule.id}
              className="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow"
            >
              <div className="p-6">
                {/* Header */}
                <div className="flex items-start justify-between mb-4">
                  <div>
                    <h3 className="text-xl font-bold text-gray-900">{schedule.name}</h3>
                    <p className="text-sm text-gray-500 mt-1">
                      {schedule.start_time} - {schedule.end_time}
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className={`px-3 py-1 rounded-full text-xs font-bold ${
                      schedule.is_active 
                        ? 'bg-green-100 text-green-700' 
                        : 'bg-gray-100 text-gray-600'
                    }`}>
                      {schedule.is_active ? 'Aktif' : 'Nonaktif'}
                    </span>
                  </div>
                </div>

                {/* Discount Badge */}
                <div className="bg-orange-50 border-2 border-orange-200 rounded-lg p-4 mb-4">
                  <div className="text-center">
                    <p className="text-sm text-orange-600 font-medium">Diskon</p>
                    <p className="text-3xl font-bold text-orange-600">
                      {schedule.discount_percentage}%
                    </p>
                    {schedule.apply_to_category && (
                      <p className="text-xs text-orange-700 mt-2 font-semibold">
                        ☕ Kategori: {schedule.apply_to_category}
                      </p>
                    )}
                  </div>
                </div>

                {/* Days */}
                <div className="mb-4">
                  <p className="text-xs font-semibold text-gray-600 uppercase mb-2">Hari Aktif</p>
                  <div className="flex flex-wrap gap-1">
                    {schedule.days_of_week.map(day => (
                      <span
                        key={day}
                        className="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-medium"
                      >
                        {dayLabels[day]}
                      </span>
                    ))}
                  </div>
                </div>

                {/* Products */}
                <div className="mb-4">
                  <p className="text-xs font-semibold text-gray-600 uppercase mb-2">
                    Produk ({schedule.product_count})
                  </p>
                  <div className="space-y-2 max-h-32 overflow-y-auto">
                    {schedule.products.slice(0, 3).map(product => (
                      <div key={product.id} className="flex items-center gap-2 text-sm">
                        <div className="w-8 h-8 bg-gray-200 rounded overflow-hidden flex-shrink-0">
                          {product.image && (
                            <img src={getImageUrl(product.image) || '/assets/image/cup.png'} alt={product.name} className="w-full h-full object-cover" />
                          )}
                        </div>
                        <span className="text-gray-700 truncate">{product.name}</span>
                      </div>
                    ))}
                    {schedule.product_count > 3 && (
                      <p className="text-xs text-gray-500">+{schedule.product_count - 3} lainnya</p>
                    )}
                  </div>
                </div>

                {/* Actions */}
                <div className="flex gap-2 pt-4 border-t">
                  <button
                    onClick={() => handleEdit(schedule)}
                    className="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 py-2 rounded-lg font-medium transition-colors"
                  >
                    Edit
                  </button>
                  <button
                    onClick={() => handleDelete(schedule.id)}
                    className="flex-1 bg-red-50 hover:bg-red-100 text-red-700 py-2 rounded-lg font-medium transition-colors"
                  >
                    Hapus
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>

        {schedules.length === 0 && (
          <div className="text-center py-12">
            <p className="text-gray-500">Belum ada jadwal Happy Hour</p>
            <button
              onClick={handleCreateNew}
              className="mt-4 text-[#a97456] hover:underline font-medium"
            >
              Buat jadwal pertama →
            </button>
          </div>
        )}
      </div>

      {/* Modal Form */}
      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
          <div className="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div className="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
              <h2 className="text-2xl font-bold text-gray-900">
                {editingSchedule ? 'Edit Happy Hour' : 'Buat Happy Hour Baru'}
              </h2>
              <button
                onClick={() => setShowModal(false)}
                className="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div className="p-6 space-y-6">
              {/* Name */}
              <div>
                <label className="block text-sm font-semibold text-gray-900 mb-2">
                  Nama Jadwal *
                </label>
                <input
                  type="text"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  placeholder="Contoh: Morning Rush, Afternoon Break"
                  className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-[#a97456] focus:outline-none"
                />
              </div>

              {/* Time */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-semibold text-gray-900 mb-2">
                    Jam Mulai *
                  </label>
                  <input
                    type="time"
                    value={formData.start_time}
                    onChange={(e) => setFormData({ ...formData, start_time: e.target.value })}
                    className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-[#a97456] focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block text-sm font-semibold text-gray-900 mb-2">
                    Jam Selesai *
                  </label>
                  <input
                    type="time"
                    value={formData.end_time}
                    onChange={(e) => setFormData({ ...formData, end_time: e.target.value })}
                    className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-[#a97456] focus:outline-none"
                  />
                </div>
              </div>

              {/* Discount */}
              <div>
                <label className="block text-sm font-semibold text-gray-900 mb-2">
                  Diskon (%) *
                </label>
                <input
                  type="number"
                  min="1"
                  max="100"
                  value={formData.discount_percentage}
                  onChange={(e) => setFormData({ ...formData, discount_percentage: Number(e.target.value) })}
                  className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-[#a97456] focus:outline-none"
                />
              </div>

              {/* Days */}
              <div>
                <label className="block text-sm font-semibold text-gray-900 mb-3">
                  Hari Aktif *
                </label>
                <div className="grid grid-cols-4 gap-2">
                  {days.map(day => (
                    <button
                      key={day}
                      type="button"
                      onClick={() => toggleDay(day)}
                      className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                        formData.days_of_week.includes(day)
                          ? 'bg-[#a97456] text-white'
                          : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                      }`}
                    >
                      {dayLabels[day]}
                    </button>
                  ))}
                </div>
              </div>

              {/* Category Selector (New!) */}
              <div className="bg-blue-50 border-2 border-blue-200 rounded-lg p-4">
                <label className="block text-sm font-semibold text-gray-900 mb-3">
                  ☕ Kategori Produk *
                </label>
                <p className="text-xs text-gray-600 mb-3">
                  Diskon akan otomatis diterapkan ke <strong>semua produk</strong> dalam kategori yang dipilih
                </p>
                <div className="grid grid-cols-3 gap-3">
                  <button
                    type="button"
                    onClick={() => setFormData({ ...formData, apply_to_category: 'Coffee' })}
                    className={`px-4 py-3 rounded-lg font-medium transition-all ${
                      formData.apply_to_category === 'Coffee'
                        ? 'bg-[#a97456] text-white shadow-lg scale-105'
                        : 'bg-white text-gray-700 hover:bg-gray-50 border-2 border-gray-300'
                    }`}
                  >
                    ☕ Coffee
                  </button>
                  <button
                    type="button"
                    onClick={() => setFormData({ ...formData, apply_to_category: 'Non-Coffee' })}
                    className={`px-4 py-3 rounded-lg font-medium transition-all ${
                      formData.apply_to_category === 'Non-Coffee'
                        ? 'bg-[#a97456] text-white shadow-lg scale-105'
                        : 'bg-white text-gray-700 hover:bg-gray-50 border-2 border-gray-300'
                    }`}
                  >
                    🥤 Non-Coffee
                  </button>
                  <button
                    type="button"
                    onClick={() => setFormData({ ...formData, apply_to_category: 'Snacks' })}
                    className={`px-4 py-3 rounded-lg font-medium transition-all ${
                      formData.apply_to_category === 'Snacks'
                        ? 'bg-[#a97456] text-white shadow-lg scale-105'
                        : 'bg-white text-gray-700 hover:bg-gray-50 border-2 border-gray-300'
                    }`}
                  >
                    🍪 Snacks
                  </button>
                </div>
                <div className="mt-3 p-3 bg-white rounded border border-blue-200">
                  <p className="text-sm text-gray-700">
                    <strong>Kategori dipilih:</strong> <span className="text-[#a97456] font-bold">{formData.apply_to_category}</span>
                  </p>
                  <p className="text-xs text-gray-500 mt-1">
                    💡 <strong>Rekomendasi:</strong> Gunakan kategori <strong>Coffee</strong> untuk Happy Hour agar efisien
                  </p>
                </div>
              </div>

              {/* Active Status */}
              <div className="flex items-center gap-3">
                <input
                  type="checkbox"
                  id="is_active"
                  checked={formData.is_active}
                  onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                  className="w-5 h-5 text-[#a97456] rounded focus:ring-[#a97456]"
                />
                <label htmlFor="is_active" className="text-sm font-medium text-gray-900">
                  Aktifkan jadwal ini
                </label>
              </div>
            </div>

            {/* Footer */}
            <div className="sticky bottom-0 bg-white border-t px-6 py-4 flex gap-3">
              <button
                onClick={() => setShowModal(false)}
                className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold transition-colors"
              >
                Batal
              </button>
              <button
                onClick={handleSave}
                className="flex-1 bg-[#a97456] hover:bg-[#8b5e3c] text-white py-3 rounded-lg font-semibold transition-colors"
              >
                {editingSchedule ? 'Update' : 'Simpan'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
