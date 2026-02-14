'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useKurirStore } from '@/lib/stores/kurir-store';
import { kurirApi } from '@/lib/kurir-api';
import Link from 'next/link';
import toast from 'react-hot-toast';
import { getErrorMessage } from '@/lib/utils';

export default function KurirLoginPage() {
  const router = useRouter();
  const { login } = useKurirStore();
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (!phone.trim()) { setError('Nomor HP wajib diisi'); return; }
    if (!password.trim()) { setError('Password wajib diisi'); return; }

    setIsLoading(true);
    try {
      const res = await kurirApi.login(phone, password);
      if (res.success && res.token) {
        login(res.user, res.token);
        toast.success('Login berhasil!');
        router.push('/kurir');
      } else {
        setError(res.message || 'Login gagal');
      }
    } catch (err: unknown) {
      setError(getErrorMessage(err) || 'Login gagal. Periksa koneksi Anda.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-amber-50 to-orange-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
      <div className="max-w-md w-full space-y-6">
        {/* Header */}
        <div className="text-center">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-2xl mb-4">
            <i className="bi bi-truck text-3xl text-amber-700 dark:text-amber-400"></i>
          </div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">DailyVery</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm mt-1">Kurir Delivery Panel</p>
        </div>

        {/* Card */}
        <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl shadow-lg p-6">
          <h2 className="text-xl font-bold text-gray-800 dark:text-gray-100 mb-1">Masuk</h2>
          <p className="text-sm text-gray-500 dark:text-gray-400 mb-6">Gunakan nomor HP yang terdaftar</p>

          {error && (
            <div className="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
              <p className="text-red-600 dark:text-red-400 text-sm flex items-center gap-2">
                <i className="bi bi-exclamation-circle"></i> {error}
              </p>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nomor HP</label>
              <div className="relative">
                <i className="bi bi-phone absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="tel" value={phone} onChange={(e) => setPhone(e.target.value)}
                  className="w-full pl-10 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-[#333] text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                  placeholder="08xxxxxxxxxx" />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
              <div className="relative">
                <i className="bi bi-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type={showPassword ? 'text' : 'password'} value={password} onChange={(e) => setPassword(e.target.value)}
                  className="w-full pl-10 pr-12 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-[#333] text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                  placeholder="Masukkan password" />
                <button type="button" onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                  <i className={`bi ${showPassword ? 'bi-eye-slash' : 'bi-eye'}`}></i>
                </button>
              </div>
            </div>

            <button type="submit" disabled={isLoading}
              className="w-full py-3 bg-amber-600 hover:bg-amber-700 disabled:bg-amber-400 text-white font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
              {isLoading ? (
                <><div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div> Masuk...</>
              ) : (
                <><i className="bi bi-box-arrow-in-right"></i> Masuk</>
              )}
            </button>
          </form>
        </div>

        {/* Register link */}
        <p className="text-center text-sm text-gray-500 dark:text-gray-400">
          Belum punya akun?{' '}
          <Link href="/kurir/register" className="text-amber-600 hover:text-amber-700 font-semibold">
            Daftar Kurir
          </Link>
        </p>

        <p className="text-center">
          <Link href="/" className="text-xs text-gray-400 hover:text-gray-600">
            ← Kembali ke DailyCup
          </Link>
        </p>
      </div>
    </div>
  );
}
