"use client";

import { useState, useEffect } from "react";
import { useRouter, useParams } from "next/navigation";
import Link from "next/link";
import { api } from "@/lib/api-client";
import toast from "react-hot-toast";

interface Kurir {
  id: number;
  name: string;
  phone: string;
  email: string;
  photo: string | null;
  vehicle_type: string;
  vehicle_number: string;
  status: string;
  rating: string;
  total_deliveries: number;
  is_active: number;
  created_at: string;
  updated_at: string;
  active_deliveries: number;
  today_deliveries: number;
  today_earnings: string;
  total_completed: number;
  avg_rating: string | null;
  total_reviews: number;
  latitude: string | null;
  longitude: string | null;
  location_updated_at: string | null;
}

export default function KurirDetailPage() {
  const router = useRouter();
  const params = useParams();
  const id = params.id as string;

  const [kurir, setKurir] = useState<Kurir | null>(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    fetchKurirDetail();
  }, [id]);

  const fetchKurirDetail = async () => {
    try {
      setLoading(true);
      const response = await api.get<{ success: boolean; kurir: Kurir }>(
        `/kurir.php?id=${id}`,
        { requiresAuth: true }
      );

      if (response.success && response.kurir) {
        setKurir(response.kurir);
      } else {
        toast.error("Kurir tidak ditemukan");
        router.push("/admin/kurir");
      }
    } catch (error: any) {
      console.error("Error fetching kurir:", error);
      toast.error(error.message || "Gagal memuat data kurir");
      router.push("/admin/kurir");
    } finally {
      setLoading(false);
    }
  };

  const handleStatusChange = async (newStatus: string) => {
    if (!kurir) return;
    setActionLoading(true);
    try {
      await api.put(
        `/kurir.php?id=${kurir.id}`,
        { status: newStatus },
        { requiresAuth: true }
      );
      toast.success("Status berhasil diupdate");
      fetchKurirDetail();
    } catch (error: any) {
      toast.error(error.message || "Gagal update status");
    } finally {
      setActionLoading(false);
    }
  };

  const handleToggleActive = async () => {
    if (!kurir) return;
    const action = kurir.is_active ? "suspend" : "aktifkan";
    if (!confirm(`Yakin ingin ${action} kurir ini?`)) return;

    setActionLoading(true);
    try {
      await api.put(
        `/kurir.php?id=${kurir.id}`,
        {
          is_active: kurir.is_active ? 0 : 1,
          status: kurir.is_active ? "offline" : "available",
        },
        { requiresAuth: true }
      );
      toast.success(`Kurir berhasil di-${action}`);
      fetchKurirDetail();
    } catch (error: any) {
      toast.error(error.message || `Gagal ${action} kurir`);
    } finally {
      setActionLoading(false);
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

  if (!kurir) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">Kurir tidak ditemukan</p>
        <Link
          href="/admin/kurir"
          className="inline-block mt-4 text-[#a97456] hover:underline"
        >
          ← Kembali ke daftar kurir
        </Link>
      </div>
    );
  }

  const isActive = kurir.is_active === 1;
  const getVehicleIcon = (type: string) => {
    return type === "motor" ? "🏍️" : type === "mobil" ? "🚗" : "🚲";
  };

  return (
    <div>
      {/* Header */}
      <div className="mb-6">
        <Link
          href="/admin/kurir"
          className="text-[#a97456] hover:underline mb-4 inline-flex items-center gap-2"
        >
          <i className="bi bi-arrow-left"></i> Kembali ke Daftar Kurir
        </Link>
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mt-4">
          <div>
            <h1 className="text-2xl font-bold text-gray-800">
              Detail Kurir
            </h1>
            <p className="text-gray-500">Informasi lengkap kurir</p>
          </div>
          <div className="flex gap-2">
            <Link
              href={`/admin/kurir/${kurir.id}/edit`}
              className="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors font-medium"
            >
              <i className="bi bi-pencil mr-2"></i>Edit
            </Link>
            <button
              onClick={handleToggleActive}
              disabled={actionLoading}
              className={`px-4 py-2 rounded-lg font-medium transition-colors disabled:opacity-50 ${
                isActive
                  ? "bg-red-500 hover:bg-red-600 text-white"
                  : "bg-green-500 hover:bg-green-600 text-white"
              }`}
            >
              {actionLoading ? (
                <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
              ) : (
                <>
                  <i className={`bi ${isActive ? "bi-slash-circle" : "bi-check-circle"} mr-2`}></i>
                  {isActive ? "Suspend" : "Aktifkan"}
                </>
              )}
            </button>
          </div>
        </div>
      </div>

      {/* Status Badge */}
      {!isActive && (
        <div className="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-6">
          <span className="text-red-600 font-bold">
            <i className="bi bi-exclamation-triangle mr-2"></i>
            KURIR SUSPENDED - Tidak dapat menerima pesanan
          </span>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column - Main Info */}
        <div className="lg:col-span-2 space-y-6">
          {/* Profile Card */}
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 className="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
              <i className="bi bi-person-circle text-[#a97456]"></i>
              Informasi Pribadi
            </h2>
            <div className="flex items-start gap-6">
              <div className="w-24 h-24 bg-[#a97456] text-white rounded-full flex items-center justify-center font-bold text-3xl overflow-hidden flex-shrink-0">
                {kurir.photo ? (
                  <img
                    src={kurir.photo}
                    alt={kurir.name}
                    className="w-full h-full object-cover"
                  />
                ) : (
                  kurir.name.charAt(0).toUpperCase()
                )}
              </div>
              <div className="flex-1 space-y-3">
                <div>
                  <label className="text-xs text-gray-500 uppercase font-medium">Nama Lengkap</label>
                  <p className="text-lg font-bold text-gray-800">{kurir.name}</p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div>
                    <label className="text-xs text-gray-500 uppercase font-medium">No. Telepon</label>
                    <p className="text-gray-800 flex items-center gap-2">
                      <i className="bi bi-telephone text-[#a97456]"></i>
                      <a href={`tel:${kurir.phone}`} className="hover:text-[#a97456]">
                        {kurir.phone}
                      </a>
                    </p>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500 uppercase font-medium">Email</label>
                    <p className="text-gray-800 flex items-center gap-2">
                      <i className="bi bi-envelope text-[#a97456]"></i>
                      {kurir.email || "-"}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Vehicle Info */}
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 className="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
              <i className="bi bi-bicycle text-[#a97456]"></i>
              Kendaraan
            </h2>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="text-xs text-gray-500 uppercase font-medium">Jenis Kendaraan</label>
                <p className="text-gray-800 font-medium flex items-center gap-2">
                  <span className="text-2xl">{getVehicleIcon(kurir.vehicle_type)}</span>
                  <span className="capitalize">{kurir.vehicle_type}</span>
                </p>
              </div>
              <div>
                <label className="text-xs text-gray-500 uppercase font-medium">Plat Nomor</label>
                <p className="text-gray-800 font-bold text-lg">{kurir.vehicle_number}</p>
              </div>
            </div>
          </div>

          {/* Location Info */}
          {kurir.latitude && kurir.longitude && (
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <h2 className="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i className="bi bi-geo-alt text-[#a97456]"></i>
                Lokasi Terakhir
              </h2>
              <div className="space-y-2">
                <p className="text-gray-600">
                  <span className="font-medium">Koordinat:</span> {kurir.latitude}, {kurir.longitude}
                </p>
                <p className="text-gray-600">
                  <span className="font-medium">Update terakhir:</span>{" "}
                  {kurir.location_updated_at
                    ? new Date(kurir.location_updated_at).toLocaleString("id-ID")
                    : "-"}
                </p>
                <a
                  href={`https://www.google.com/maps?q=${kurir.latitude},${kurir.longitude}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-block mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                >
                  <i className="bi bi-map mr-2"></i>Lihat di Google Maps
                </a>
              </div>
            </div>
          )}
        </div>

        {/* Right Column - Stats & Status */}
        <div className="space-y-6">
          {/* Status Control */}
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 className="text-lg font-bold text-gray-800 mb-4">Status</h2>
            <div className="space-y-3">
              <div>
                <label className="text-xs text-gray-500 uppercase font-medium block mb-2">
                  Status Aktif
                </label>
                <span
                  className={`inline-block px-3 py-1.5 rounded-full text-sm font-bold ${
                    isActive
                      ? "bg-green-100 text-green-700"
                      : "bg-red-100 text-red-700"
                  }`}
                >
                  {isActive ? "✓ Aktif" : "✗ Suspended"}
                </span>
              </div>
              {isActive && (
                <div>
                  <label className="text-xs text-gray-500 uppercase font-medium block mb-2">
                    Status Operasional
                  </label>
                  <select
                    value={kurir.status}
                    onChange={(e) => handleStatusChange(e.target.value)}
                    disabled={actionLoading}
                    className="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  >
                    <option value="available">Available</option>
                    <option value="busy">Busy</option>
                    <option value="offline">Offline</option>
                  </select>
                </div>
              )}
            </div>
          </div>

          {/* Statistics */}
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 className="text-lg font-bold text-gray-800 mb-4">Statistik</h2>
            <div className="space-y-4">
              <div className="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                <div className="flex items-center gap-3">
                  <i className="bi bi-star-fill text-2xl text-yellow-500"></i>
                  <div>
                    <p className="text-xs text-gray-500">Rating</p>
                    <p className="font-bold text-lg">
                      {Number(kurir.rating).toFixed(1)} / 5.0
                    </p>
                  </div>
                </div>
              </div>
              <div className="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                <div className="flex items-center gap-3">
                  <i className="bi bi-box-seam text-2xl text-blue-500"></i>
                  <div>
                    <p className="text-xs text-gray-500">Total Pengiriman</p>
                    <p className="font-bold text-lg">{kurir.total_deliveries}</p>
                  </div>
                </div>
              </div>
              <div className="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                <div className="flex items-center gap-3">
                  <i className="bi bi-check-circle-fill text-2xl text-green-500"></i>
                  <div>
                    <p className="text-xs text-gray-500">Selesai</p>
                    <p className="font-bold text-lg">{kurir.total_completed}</p>
                  </div>
                </div>
              </div>
              <div className="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                <div className="flex items-center gap-3">
                  <i className="bi bi-hourglass-split text-2xl text-purple-500"></i>
                  <div>
                    <p className="text-xs text-gray-500">Aktif Sekarang</p>
                    <p className="font-bold text-lg">{kurir.active_deliveries}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Today's Stats */}
          <div className="bg-gradient-to-br from-[#a97456] to-[#8b6043] rounded-2xl shadow-sm p-6 text-white">
            <h2 className="text-lg font-bold mb-4">Hari Ini</h2>
            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-white/80">Pengiriman</span>
                <span className="font-bold text-xl">{kurir.today_deliveries}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-white/80">Pendapatan</span>
                <span className="font-bold text-xl">
                  Rp {Number(kurir.today_earnings).toLocaleString("id-ID")}
                </span>
              </div>
            </div>
          </div>

          {/* Registration Info */}
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 className="text-lg font-bold text-gray-800 mb-4">Informasi Akun</h2>
            <div className="space-y-2 text-sm">
              <div>
                <span className="text-gray-500">Terdaftar:</span>
                <p className="font-medium">
                  {new Date(kurir.created_at).toLocaleDateString("id-ID", {
                    day: "numeric",
                    month: "long",
                    year: "numeric",
                  })}
                </p>
              </div>
              <div>
                <span className="text-gray-500">Update terakhir:</span>
                <p className="font-medium">
                  {new Date(kurir.updated_at).toLocaleDateString("id-ID", {
                    day: "numeric",
                    month: "long",
                    year: "numeric",
                  })}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
