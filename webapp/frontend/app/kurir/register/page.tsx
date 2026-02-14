'use client';

import { useState, useEffect, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useKurirStore } from '@/lib/stores/kurir-store';
import { kurirApi } from '@/lib/kurir-api';
import Link from 'next/link';
import toast from 'react-hot-toast';
import { getErrorMessage } from '@/lib/utils';

function KurirRegisterForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const invitationCode = searchParams.get('code');
  
  const { login } = useKurirStore();
  const [form, setForm] = useState({
    name: '', phone: '', email: '', password: '', confirmPassword: '',
    vehicle_type: 'motor', vehicle_number: '', invitation_code: invitationCode || '',
  });
  const [verifyingCode, setVerifyingCode] = useState(false);
  const [codeValid, setCodeValid] = useState<boolean | null>(null);
  const [showPassword, setShowPassword] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isLoading, setIsLoading] = useState(false);

  // Verify invitation code on mount
  useEffect(() => {
    if (invitationCode) {
      verifyInvitationCode(invitationCode);
    }
  }, [invitationCode]);

  const verifyInvitationCode = async (code: string) => {
    setVerifyingCode(true);
    try {
      const res = await fetch('/api/kurir/verify-invitation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code }),
      });
      const data = await res.json();
      
      if (data.success) {
        setCodeValid(true);
        // Pre-fill data from invitation
        if (data.data) {
          setForm(prev => ({
            ...prev,
            name: data.data.name || '',
            phone: data.data.phone || '',
            email: data.data.email || '',
            vehicle_type: data.data.vehicle_type || 'motor',
          }));
        }
      } else {
        setCodeValid(false);
        toast.error(data.message || 'Kode undangan tidak valid');
      }
    } catch (err) {
      setCodeValid(false);
      toast.error('Gagal memverifikasi kode undangan');
    } finally {
      setVerifyingCode(false);
    }
  };

  const update = (field: string, value: string) => {
    setForm(prev => ({ ...prev, [field]: value }));
    if (errors[field]) setErrors(prev => { const n = { ...prev }; delete n[field]; return n; });
  };

  const validate = () => {
    const e: Record<string, string> = {};
    if (!form.name.trim() || form.name.length < 2) e.name = 'Nama minimal 2 karakter';
    if (!form.phone.trim()) e.phone = 'Nomor HP wajib diisi';
    if (!form.password || form.password.length < 6) e.password = 'Password minimal 6 karakter';
    if (form.password !== form.confirmPassword) e.confirmPassword = 'Password tidak cocok';
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!validate()) return;

    setIsLoading(true);
    try {
      const res = await kurirApi.register({
        name: form.name, phone: form.phone, password: form.password,
        email: form.email || undefined,
        vehicle_type: form.vehicle_type,
        vehicle_number: form.vehicle_number || undefined,
        invitation_code: form.invitation_code,
      });
      if (res.success && res.token) {
        login(res.user, res.token);
        toast.success('Pendaftaran berhasil!');
        router.push('/kurir');
      } else {
        setErrors({ general: res.message || 'Pendaftaran gagal' });
      }
    } catch (err: unknown) {
      setErrors({ general: getErrorMessage(err) || 'Pendaftaran gagal' });
    } finally {
      setIsLoading(false);
    }
  };

  const vehicleOptions = [
    { value: 'motor', label: 'Motor', icon: 'bi-scooter' },
    { value: 'mobil', label: 'Mobil', icon: 'bi-car-front' },
    { value: 'sepeda', label: 'Sepeda', icon: 'bi-bicycle' },
  ];

  // Show loading while verifying code
  if (verifyingCode) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-amber-50 to-orange-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center">
        <div className="text-center">
          <div className="w-12 h-12 border-4 border-amber-200 border-t-amber-600 rounded-full animate-spin mx-auto mb-4"></div>
          <p className="text-gray-600 dark:text-gray-400">Memverifikasi kode undangan...</p>
        </div>
      </div>
    );
  }

  // Show error if code is invalid and code was provided
  if (invitationCode && codeValid === false) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-amber-50 to-orange-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
        <div className="max-w-md w-full bg-white dark:bg-[#2a2a2a] rounded-2xl shadow-lg p-8 text-center">
          <div className="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <i className="bi bi-x-circle text-3xl text-red-600"></i>
          </div>
          <h2 className="text-xl font-bold text-gray-900 dark:text-white mb-2">Kode Undangan Tidak Valid</h2>
          <p className="text-gray-600 dark:text-gray-400 mb-6">
            Kode undangan yang Anda gunakan tidak valid, sudah digunakan, atau telah kedaluwarsa.
          </p>
          <Link href="/kurir/info" className="inline-block px-6 py-3 bg-amber-600 text-white rounded-xl hover:bg-amber-700 transition-colors">
            Pelajari Lebih Lanjut
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-amber-50 to-orange-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
      <div className="max-w-md w-full space-y-6 py-8">
        {/* Header */}
        <div className="text-center">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-2xl mb-4">
            <i className="bi bi-truck text-3xl text-amber-700 dark:text-amber-400"></i>
          </div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Daftar Kurir</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm mt-1">Khusus Karyawan DailyCup</p>
          {codeValid && (
            <div className="mt-2 inline-flex items-center gap-2 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs">
              <i className="bi bi-check-circle-fill"></i>
              Kode undangan valid
            </div>
          )}
        </div>

        {/* Card */}
        <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl shadow-lg p-6">
          {errors.general && (
            <div className="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
              <p className="text-red-600 dark:text-red-400 text-sm flex items-center gap-2">
                <i className="bi bi-exclamation-circle"></i> {errors.general}
              </p>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            {/* Name */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap</label>
              <input type="text" value={form.name} onChange={(e) => update('name', e.target.value)}
                className={`w-full px-4 py-3 border rounded-xl bg-gray-50 dark:bg-[#333] text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent ${errors.name ? 'border-red-300' : 'border-gray-200 dark:border-gray-600'}`}
                placeholder="Nama lengkap" />
              {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
            </div>

            {/* Phone */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nomor HP</label>
              <input type="tel" value={form.phone} onChange={(e) => update('phone', e.target.value)}
                className={`w-full px-4 py-3 border rounded-xl bg-gray-50 dark:bg-[#333] text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent ${errors.phone ? 'border-red-300' : 'border-gray-200 dark:border-gray-600'}`}
                placeholder="08xxxxxxxxxx" />
              {errors.phone && <p className="text-red-500 text-xs mt-1">{errors.phone}</p>}
            </div>

            {/* Email (optional) */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span className="text-gray-400">(opsional)</span></label>
              <input type="email" value={form.email} onChange={(e) => update('email', e.target.value)}
                className="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-[#333] text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                placeholder="email@contoh.com" />
            </div>

            {/* Vehicle Type */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jenis Kendaraan</label>
              <div className="grid grid-cols-3 gap-2">
                {vehicleOptions.map(v => (
                  <button key={v.value} type="button" onClick={() => update('vehicle_type', v.value)}
                    className={`p-3 rounded-xl border text-center transition-all ${
                      form.vehicle_type === v.value
                        ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 ring-2 ring-amber-200'
                        : 'border-gray-200 dark:border-gray-600 text-gray-500 hover:border-gray-300'
                    }`}>
                    <i className={`bi ${v.icon} text-xl block mb-1`}></i>
                    <span className="text-xs font-medium">{v.label}</span>
                  </button>
                ))}
              </div>
            </div>

            {/* Vehicle Number */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Plat Nomor <span className="text-gray-400">(opsional)</span></label>
              <input type="text" value={form.vehicle_number} onChange={(e) => update('vehicle_number', e.target.value.toUpperCase())}
                className="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-[#333] text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent uppercase"
                placeholder="B 1234 XYZ" />
            </div>

            {/* Password */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
              <div className="relative">
                <input type={showPassword ? 'text' : 'password'} value={form.password} onChange={(e) => update('password', e.target.value)}
                  className={`w-full px-4 py-3 pr-12 border rounded-xl bg-gray-50 dark:bg-[#333] text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent ${errors.password ? 'border-red-300' : 'border-gray-200 dark:border-gray-600'}`}
                  placeholder="Minimal 6 karakter" />
                <button type="button" onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                  <i className={`bi ${showPassword ? 'bi-eye-slash' : 'bi-eye'}`}></i>
                </button>
              </div>
              {errors.password && <p className="text-red-500 text-xs mt-1">{errors.password}</p>}
            </div>

            {/* Confirm Password */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi Password</label>
              <input type="password" value={form.confirmPassword} onChange={(e) => update('confirmPassword', e.target.value)}
                className={`w-full px-4 py-3 border rounded-xl bg-gray-50 dark:bg-[#333] text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent ${errors.confirmPassword ? 'border-red-300' : 'border-gray-200 dark:border-gray-600'}`}
                placeholder="Ulangi password" />
              {errors.confirmPassword && <p className="text-red-500 text-xs mt-1">{errors.confirmPassword}</p>}
            </div>

            <button type="submit" disabled={isLoading}
              className="w-full py-3 bg-amber-600 hover:bg-amber-700 disabled:bg-amber-400 text-white font-semibold rounded-xl transition-colors flex items-center justify-center gap-2 mt-2">
              {isLoading ? (
                <><div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div> Mendaftar...</>
              ) : (
                <><i className="bi bi-person-plus"></i> Daftar Sekarang</>
              )}
            </button>
          </form>
        </div>

        <p className="text-center text-sm text-gray-500 dark:text-gray-400">
          Sudah punya akun?{' '}
          <Link href="/kurir/login" className="text-amber-600 hover:text-amber-700 font-semibold">Masuk</Link>
        </p>

        <p className="text-center">
          <Link href="/" className="text-xs text-gray-400 hover:text-gray-600">← Kembali ke DailyCup</Link>
        </p>
      </div>
    </div>
  );
}

export default function KurirRegisterPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-gradient-to-br from-amber-50 to-orange-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center">
        <div className="text-center">
          <div className="w-12 h-12 border-4 border-amber-200 border-t-amber-600 rounded-full animate-spin mx-auto mb-4"></div>
          <p className="text-gray-600 dark:text-gray-400">Memuat...</p>
        </div>
      </div>
    }>
      <KurirRegisterForm />
    </Suspense>
  );
}
