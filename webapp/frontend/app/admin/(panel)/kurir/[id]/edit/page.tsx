"use client";

import { useState, useEffect } from "react";
import { useRouter, useParams } from "next/navigation";
import Link from "next/link";
import { api } from "@/lib/api-client";
import { getErrorMessage } from '@/lib/utils';
import toast from "react-hot-toast";

interface KurirForm {
  name: string;
  phone: string;
  email: string;
  vehicle_type: string;
  vehicle_number: string;
  status: string;
}

export default function KurirEditPage() {
  const router = useRouter();
  const params = useParams();
  const id = params.id as string;

  const [form, setForm] = useState<KurirForm>({
    name: "",
    phone: "",
    email: "",
    vehicle_type: "motor",
    vehicle_number: "",
    status: "available",
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    fetchKurirDetail();
  }, [id]);

  const fetchKurirDetail = async () => {
    try {
      setLoading(true);
      const response = await api.get<{ success: boolean; kurir: Record<string, unknown> }>(
        `/kurir.php?id=${id}`,
        { requiresAuth: true }
      );

      if (response.success && response.kurir) {
        const kurir = response.kurir;
        setForm({
          name: String(kurir.name || ""),
          phone: String(kurir.phone || ""),
          email: String(kurir.email || ""),
          vehicle_type: String(kurir.vehicle_type || "motor"),
          vehicle_number: String(kurir.vehicle_number || ""),
          status: String(kurir.status || "available"),
        });
      } else {
        toast.error("Kurir tidak ditemukan");
        router.push("/admin/kurir");
      }
    } catch (error: unknown) {
      console.error("Error fetching kurir:", getErrorMessage(error));
      toast.error(getErrorMessage(error) || "Gagal memuat data kurir");
      router.push("/admin/kurir");
    } finally {
      setLoading(false);
    }
  };

  const updateField = (field: keyof KurirForm, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors((prev) => {
        const newErrors = { ...prev };
        delete newErrors[field];
        return newErrors;
      });
    }
  };

  const validate = () => {
    const newErrors: Record<string, string> = {};

    if (!form.name.trim()) {
      newErrors.name = "Nama wajib diisi";
    } else if (form.name.trim().length < 2) {
      newErrors.name = "Nama minimal 2 karakter";
    }

    if (!form.phone.trim()) {
      newErrors.phone = "Nomor HP wajib diisi";
    } else if (!/^08\d{8,11}$/.test(form.phone)) {
      newErrors.phone = "Format nomor HP tidak valid (08xxxxxxxxxx)";
    }

    if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
      newErrors.email = "Format email tidak valid";
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validate()) {
      toast.error("Mohon periksa form kembali");
      return;
    }

    setSaving(true);
    try {
      const response = await api.put<{ success: boolean; message?: string }>(
        `/kurir.php?id=${id}`,
        { ...form } as Record<string, unknown>,
        { requiresAuth: true }
      );

      if (response.success) {
        toast.success("Data kurir berhasil diupdate");
        router.push(`/admin/kurir/${id}`);
      } else {
        toast.error(response.message || "Gagal update data kurir");
      }
    } catch (error: unknown) {
      console.error("Error updating kurir:", getErrorMessage(error));
      toast.error(getErrorMessage(error) || "Gagal update data kurir");
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto mb-4"></div>
          <p className="text-gray-500">Memuat data kurir...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-2xl">
      {/* Header */}
      <div className="mb-6">
        <Link
          href={`/admin/kurir/${id}`}
          className="text-[#a97456] hover:underline mb-4 inline-flex items-center gap-2"
        >
          <i className="bi bi-arrow-left"></i> Kembali ke Detail
        </Link>
        <h1 className="text-2xl font-bold text-gray-800 mt-4">
          <i className="bi bi-pencil mr-2"></i>Edit Kurir
        </h1>
        <p className="text-gray-500">Update informasi kurir</p>
      </div>

      {/* Form Card */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form onSubmit={handleSubmit} className="space-y-5">
          {/* Name */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Nama Lengkap <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              value={form.name}
              onChange={(e) => updateField("name", e.target.value)}
              className={`w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-[#a97456] focus:border-transparent transition ${
                errors.name ? "border-red-300" : "border-gray-200"
              }`}
              placeholder="Masukkan nama lengkap"
            />
            {errors.name && (
              <p className="text-red-500 text-xs mt-1">{errors.name}</p>
            )}
          </div>

          {/* Phone */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Nomor HP <span className="text-red-500">*</span>
            </label>
            <input
              type="tel"
              value={form.phone}
              onChange={(e) => updateField("phone", e.target.value)}
              className={`w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-[#a97456] focus:border-transparent transition ${
                errors.phone ? "border-red-300" : "border-gray-200"
              }`}
              placeholder="08xxxxxxxxxx"
            />
            {errors.phone && (
              <p className="text-red-500 text-xs mt-1">{errors.phone}</p>
            )}
          </div>

          {/* Email */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Email <span className="text-gray-400 text-xs">(opsional)</span>
            </label>
            <input
              type="email"
              value={form.email}
              onChange={(e) => updateField("email", e.target.value)}
              className={`w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-[#a97456] focus:border-transparent transition ${
                errors.email ? "border-red-300" : "border-gray-200"
              }`}
              placeholder="email@contoh.com"
            />
            {errors.email && (
              <p className="text-red-500 text-xs mt-1">{errors.email}</p>
            )}
          </div>

          {/* Vehicle Type */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Jenis Kendaraan
            </label>
            <div className="grid grid-cols-3 gap-3">
              {[
                { value: "motor", label: "Motor", icon: "🏍️" },
                { value: "mobil", label: "Mobil", icon: "🚗" },
                { value: "sepeda", label: "Sepeda", icon: "🚲" },
              ].map((vehicle) => (
                <button
                  key={vehicle.value}
                  type="button"
                  onClick={() => updateField("vehicle_type", vehicle.value)}
                  className={`p-4 rounded-xl border-2 text-center transition-all ${
                    form.vehicle_type === vehicle.value
                      ? "border-[#a97456] bg-[#a97456]/10 text-[#a97456] ring-2 ring-[#a97456]/20"
                      : "border-gray-200 text-gray-500 hover:border-gray-300"
                  }`}
                >
                  <div className="text-3xl mb-1">{vehicle.icon}</div>
                  <div className="text-sm font-medium">{vehicle.label}</div>
                </button>
              ))}
            </div>
          </div>

          {/* Vehicle Number */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Plat Nomor
            </label>
            <input
              type="text"
              value={form.vehicle_number}
              onChange={(e) => updateField("vehicle_number", e.target.value.toUpperCase())}
              className="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a97456] focus:border-transparent transition uppercase"
              placeholder="B 1234 XYZ"
            />
          </div>

          {/* Status */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Status
            </label>
            <select
              value={form.status}
              onChange={(e) => updateField("status", e.target.value)}
              className="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#a97456] focus:border-transparent transition"
            >
              <option value="available">Available</option>
              <option value="busy">Busy</option>
              <option value="offline">Offline</option>
            </select>
          </div>

          {/* Actions */}
          <div className="flex items-center gap-3 pt-4 border-t border-gray-100">
            <button
              type="submit"
              disabled={saving}
              className="flex-1 px-6 py-3 bg-[#a97456] text-white rounded-xl hover:bg-[#8b6043] transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {saving ? (
                <span className="flex items-center justify-center gap-2">
                  <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  Menyimpan...
                </span>
              ) : (
                <>
                  <i className="bi bi-check-circle mr-2"></i>Simpan Perubahan
                </>
              )}
            </button>
            <Link
              href={`/admin/kurir/${id}`}
              className="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-colors font-medium text-center"
            >
              Batal
            </Link>
          </div>
        </form>
      </div>
    </div>
  );
}
