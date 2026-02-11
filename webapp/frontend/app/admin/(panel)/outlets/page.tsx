"use client";

import { useState, useEffect, useCallback } from "react";
import { api } from "@/lib/api-client";

interface Outlet {
  id: number;
  name: string;
  code: string | null;
  address: string;
  city: string | null;
  province: string | null;
  latitude: string;
  longitude: string;
  phone: string | null;
  email: string | null;
  delivery_radius_km: string;
  is_active: number;
  opening_time: string;
  closing_time: string;
  created_at: string;
  updated_at: string;
}

interface OutletForm {
  name: string;
  code: string;
  address: string;
  city: string;
  province: string;
  latitude: string;
  longitude: string;
  phone: string;
  email: string;
  delivery_radius_km: string;
  opening_time: string;
  closing_time: string;
  is_active: boolean;
}

const initialForm: OutletForm = {
  name: "",
  code: "",
  address: "",
  city: "",
  province: "",
  latitude: "",
  longitude: "",
  phone: "",
  email: "",
  delivery_radius_km: "30",
  opening_time: "08:00",
  closing_time: "22:00",
  is_active: true,
};

export default function OutletsPage() {
  const [outlets, setOutlets] = useState<Outlet[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState<OutletForm>(initialForm);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const fetchOutlets = useCallback(async () => {
    try {
      const res = await api.get<{ success: boolean; outlets: Outlet[] }>(
        "/outlets.php?active=0",
        { requiresAuth: true }
      );
      if (res.success) {
        setOutlets(res.outlets || []);
      }
    } catch (err) {
      console.error("Error fetching outlets:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchOutlets();
  }, [fetchOutlets]);

  const openCreateModal = () => {
    setForm(initialForm);
    setEditingId(null);
    setError("");
    setShowModal(true);
  };

  const openEditModal = (outlet: Outlet) => {
    setForm({
      name: outlet.name,
      code: outlet.code || "",
      address: outlet.address,
      city: outlet.city || "",
      province: outlet.province || "",
      latitude: outlet.latitude,
      longitude: outlet.longitude,
      phone: outlet.phone || "",
      email: outlet.email || "",
      delivery_radius_km: outlet.delivery_radius_km,
      opening_time: outlet.opening_time.substring(0, 5),
      closing_time: outlet.closing_time.substring(0, 5),
      is_active: outlet.is_active === 1,
    });
    setEditingId(outlet.id);
    setError("");
    setShowModal(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setSaving(true);

    try {
      const payload = {
        ...form,
        latitude: parseFloat(form.latitude),
        longitude: parseFloat(form.longitude),
        delivery_radius_km: parseFloat(form.delivery_radius_km),
        is_active: form.is_active ? 1 : 0,
      };

      if (editingId) {
        await api.put(`/outlets.php?id=${editingId}`, payload, { requiresAuth: true });
      } else {
        await api.post("/outlets.php", payload, { requiresAuth: true });
      }

      setShowModal(false);
      fetchOutlets();
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : "Failed to save outlet";
      setError(errorMessage);
    } finally {
      setSaving(false);
    }
  };

  const toggleActive = async (outlet: Outlet) => {
    try {
      await api.put(
        `/outlets.php?id=${outlet.id}`,
        { is_active: outlet.is_active === 1 ? 0 : 1 },
        { requiresAuth: true }
      );
      fetchOutlets();
    } catch (err) {
      console.error("Error toggling outlet:", err);
    }
  };

  const getCurrentLocation = () => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setForm({
            ...form,
            latitude: position.coords.latitude.toFixed(8),
            longitude: position.coords.longitude.toFixed(8),
          });
        },
        (error) => {
          alert("Gagal mendapatkan lokasi: " + error.message);
        }
      );
    } else {
      alert("Browser tidak mendukung geolocation");
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456]"></div>
      </div>
    );
  }

  return (
    <div>
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800 mb-1">
            <i className="bi bi-shop mr-2 text-[#a97456]"></i>
            Outlet Management
          </h1>
          <p className="text-gray-500 text-sm">
            Kelola cabang outlet dan radius pengiriman
          </p>
        </div>
        <button
          onClick={openCreateModal}
          className="px-4 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8b6043] transition-colors font-medium"
        >
          <i className="bi bi-plus-lg mr-2"></i>
          Tambah Outlet
        </button>
      </div>

      {/* Info Card */}
      <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <div className="flex gap-3">
          <i className="bi bi-info-circle text-blue-500 text-xl"></i>
          <div>
            <h3 className="font-semibold text-blue-800 mb-1">Tentang Radius Delivery</h3>
            <p className="text-sm text-blue-700">
              Setiap outlet memiliki radius pengiriman maksimal (default 30km). 
              Customer hanya bisa memesan delivery jika berada dalam radius salah satu outlet aktif.
            </p>
          </div>
        </div>
      </div>

      {/* Outlets Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {outlets.map((outlet) => (
          <div
            key={outlet.id}
            className={`bg-white rounded-2xl shadow-sm border overflow-hidden transition-all hover:shadow-md ${
              outlet.is_active ? "border-gray-100" : "border-red-200 opacity-60"
            }`}
          >
            <div className="p-5">
              <div className="flex justify-between items-start mb-3">
                <div>
                  <h3 className="font-bold text-gray-800">{outlet.name}</h3>
                  {outlet.code && (
                    <span className="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                      {outlet.code}
                    </span>
                  )}
                </div>
                <span
                  className={`px-2 py-1 rounded-full text-xs font-medium ${
                    outlet.is_active
                      ? "bg-green-100 text-green-700"
                      : "bg-red-100 text-red-700"
                  }`}
                >
                  {outlet.is_active ? "Aktif" : "Nonaktif"}
                </span>
              </div>

              <div className="space-y-2 text-sm text-gray-600 mb-4">
                <p className="flex items-start gap-2">
                  <i className="bi bi-geo-alt text-gray-400 mt-0.5"></i>
                  <span>{outlet.address}</span>
                </p>
                {outlet.city && (
                  <p className="flex items-center gap-2">
                    <i className="bi bi-building text-gray-400"></i>
                    <span>
                      {outlet.city}
                      {outlet.province && `, ${outlet.province}`}
                    </span>
                  </p>
                )}
                {outlet.phone && (
                  <p className="flex items-center gap-2">
                    <i className="bi bi-telephone text-gray-400"></i>
                    <span>{outlet.phone}</span>
                  </p>
                )}
                <p className="flex items-center gap-2">
                  <i className="bi bi-clock text-gray-400"></i>
                  <span>
                    {outlet.opening_time.substring(0, 5)} -{" "}
                    {outlet.closing_time.substring(0, 5)}
                  </span>
                </p>
              </div>

              {/* Delivery Radius Badge */}
              <div className="bg-gradient-to-r from-[#a97456]/10 to-[#a97456]/5 rounded-xl p-3 mb-4">
                <div className="flex items-center justify-between">
                  <span className="text-sm text-gray-600">Radius Delivery</span>
                  <span className="font-bold text-[#a97456] text-lg">
                    {parseFloat(outlet.delivery_radius_km)} km
                  </span>
                </div>
                <div className="mt-2 text-xs text-gray-500">
                  <i className="bi bi-pin-map mr-1"></i>
                  {outlet.latitude}, {outlet.longitude}
                </div>
              </div>

              {/* Actions */}
              <div className="flex gap-2">
                <button
                  onClick={() => openEditModal(outlet)}
                  className="flex-1 px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-colors"
                >
                  <i className="bi bi-pencil mr-1"></i>
                  Edit
                </button>
                <button
                  onClick={() => toggleActive(outlet)}
                  className={`flex-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                    outlet.is_active
                      ? "bg-red-100 text-red-700 hover:bg-red-200"
                      : "bg-green-100 text-green-700 hover:bg-green-200"
                  }`}
                >
                  <i
                    className={`bi ${
                      outlet.is_active ? "bi-x-circle" : "bi-check-circle"
                    } mr-1`}
                  ></i>
                  {outlet.is_active ? "Nonaktifkan" : "Aktifkan"}
                </button>
                <a
                  href={`https://www.google.com/maps?q=${outlet.latitude},${outlet.longitude}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm font-medium transition-colors"
                >
                  <i className="bi bi-map"></i>
                </a>
              </div>
            </div>
          </div>
        ))}

        {outlets.length === 0 && (
          <div className="col-span-full bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <i className="bi bi-shop text-6xl text-gray-300 mb-4 block"></i>
            <p className="text-gray-500 text-lg mb-4">Belum ada outlet</p>
            <button
              onClick={openCreateModal}
              className="px-4 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8b6043]"
            >
              Tambah Outlet Pertama
            </button>
          </div>
        )}
      </div>

      {/* Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-gray-100">
              <h2 className="text-xl font-bold text-gray-800">
                {editingId ? "Edit Outlet" : "Tambah Outlet Baru"}
              </h2>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              {error && (
                <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                  {error}
                </div>
              )}

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Nama Outlet *
                  </label>
                  <input
                    type="text"
                    value={form.name}
                    onChange={(e) => setForm({ ...form, name: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                    placeholder="DailyCup Malang - Sukun"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Kode Outlet
                  </label>
                  <input
                    type="text"
                    value={form.code}
                    onChange={(e) => setForm({ ...form, code: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                    placeholder="MLG-SUKUN"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Alamat *
                </label>
                <textarea
                  value={form.address}
                  onChange={(e) => setForm({ ...form, address: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  rows={2}
                  placeholder="Jl. Sukun No. 123, Kec. Sukun"
                  required
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Kota
                  </label>
                  <input
                    type="text"
                    value={form.city}
                    onChange={(e) => setForm({ ...form, city: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                    placeholder="Malang"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Provinsi
                  </label>
                  <input
                    type="text"
                    value={form.province}
                    onChange={(e) => setForm({ ...form, province: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                    placeholder="Jawa Timur"
                  />
                </div>
              </div>

              {/* Coordinates */}
              <div className="bg-gray-50 rounded-xl p-4">
                <div className="flex justify-between items-center mb-3">
                  <label className="text-sm font-medium text-gray-700">
                    Koordinat Lokasi *
                  </label>
                  <button
                    type="button"
                    onClick={getCurrentLocation}
                    className="text-sm text-[#a97456] hover:underline"
                  >
                    <i className="bi bi-crosshair mr-1"></i>
                    Gunakan lokasi saya
                  </button>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs text-gray-500 mb-1">Latitude</label>
                    <input
                      type="text"
                      value={form.latitude}
                      onChange={(e) => setForm({ ...form, latitude: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                      placeholder="-7.9897"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs text-gray-500 mb-1">Longitude</label>
                    <input
                      type="text"
                      value={form.longitude}
                      onChange={(e) => setForm({ ...form, longitude: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                      placeholder="112.6107"
                      required
                    />
                  </div>
                </div>
              </div>

              {/* Delivery Radius */}
              <div className="bg-gradient-to-r from-[#a97456]/10 to-[#a97456]/5 rounded-xl p-4">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  <i className="bi bi-broadcast mr-1"></i>
                  Radius Delivery Maksimal (km)
                </label>
                <div className="flex items-center gap-4">
                  <input
                    type="range"
                    min="5"
                    max="100"
                    step="5"
                    value={form.delivery_radius_km}
                    onChange={(e) =>
                      setForm({ ...form, delivery_radius_km: e.target.value })
                    }
                    className="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-[#a97456]"
                  />
                  <div className="w-20">
                    <input
                      type="number"
                      value={form.delivery_radius_km}
                      onChange={(e) =>
                        setForm({ ...form, delivery_radius_km: e.target.value })
                      }
                      className="w-full px-3 py-2 border border-gray-200 rounded-lg text-center font-bold text-[#a97456]"
                      min="1"
                      max="200"
                    />
                  </div>
                </div>
                <p className="text-xs text-gray-500 mt-2">
                  Customer di luar radius ini tidak bisa memesan delivery
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Telepon
                  </label>
                  <input
                    type="text"
                    value={form.phone}
                    onChange={(e) => setForm({ ...form, phone: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                    placeholder="0341-000000"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Email
                  </label>
                  <input
                    type="email"
                    value={form.email}
                    onChange={(e) => setForm({ ...form, email: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                    placeholder="outlet@dailycup.com"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Jam Buka
                  </label>
                  <input
                    type="time"
                    value={form.opening_time}
                    onChange={(e) => setForm({ ...form, opening_time: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Jam Tutup
                  </label>
                  <input
                    type="time"
                    value={form.closing_time}
                    onChange={(e) => setForm({ ...form, closing_time: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  />
                </div>
              </div>

              <div className="flex items-center gap-3">
                <input
                  type="checkbox"
                  id="is_active"
                  checked={form.is_active}
                  onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
                  className="w-4 h-4 rounded border-gray-300 text-[#a97456] focus:ring-[#a97456]"
                />
                <label htmlFor="is_active" className="text-sm text-gray-700">
                  Outlet aktif dan menerima pesanan
                </label>
              </div>

              <div className="flex gap-3 pt-4 border-t border-gray-100">
                <button
                  type="button"
                  onClick={() => setShowModal(false)}
                  className="flex-1 px-4 py-2 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={saving}
                  className="flex-1 px-4 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8b6043] disabled:opacity-50"
                >
                  {saving ? (
                    <>
                      <i className="bi bi-arrow-repeat animate-spin mr-2"></i>
                      Menyimpan...
                    </>
                  ) : (
                    <>
                      <i className="bi bi-check-lg mr-2"></i>
                      {editingId ? "Simpan Perubahan" : "Tambah Outlet"}
                    </>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
