'use client';

import { useState, useEffect } from 'react';
import { useKurirStore } from '@/lib/stores/kurir-store';
import { kurirApi } from '@/lib/kurir-api';
import toast from 'react-hot-toast';
import { useRouter } from 'next/navigation';
import { getErrorMessage } from '@/lib/utils';

interface ProfileData {
  name: string;
  phone: string;
  email: string;
  vehicleType: string;
  vehicleNumber: string;
  rating: number;
  totalDeliveries: number;
  joinDate: string;
  photo: string | null;
}

export default function KurirProfilePage() {
  const { updateUser, logout } = useKurirStore();
  const router = useRouter();
  const [profile, setProfile] = useState<ProfileData | null>(null);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [changingPw, setChangingPw] = useState(false);

  // Edit fields
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [vehicleType, setVehicleType] = useState('');
  const [vehicleNumber, setVehicleNumber] = useState('');

  // Password fields
  const [oldPw, setOldPw] = useState('');
  const [newPw, setNewPw] = useState('');
  const [confirmPw, setConfirmPw] = useState('');

  useEffect(() => {
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      const res = await kurirApi.getProfile();
      if (res.success) {
        // API may return data directly or wrapped as { user: {...}, stats: {...} }
        const raw: any = res.data;
        const u = raw.user ?? raw;

        const p: ProfileData = {
          name: u.name ?? '',
          phone: u.phone ?? '',
          email: u.email ?? '',
          vehicleType: u.vehicle_type ?? u.vehicleType ?? '',
          vehicleNumber: u.vehicle_number ?? u.vehicleNumber ?? '',
          rating: Number(u.rating) || 0,
          totalDeliveries: Number(u.total_deliveries ?? u.totalDeliveries ?? 0) || 0,
          joinDate: u.created_at ?? u.joinDate ?? '',
          photo: u.photo ?? null,
        };

        setProfile(p);
        setName(p.name); setPhone(p.phone); setEmail(p.email);
        setVehicleType(p.vehicleType); setVehicleNumber(p.vehicleNumber);
      }
    } catch (err: unknown) {
      toast.error(getErrorMessage(err) || 'Gagal memuat profil');
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async () => {
    if (!name.trim() || !phone.trim()) {
      toast.error('Nama dan nomor HP wajib diisi');
      return;
    }
    setSaving(true);
    try {
      const res = await kurirApi.updateProfile({ name, phone, email, vehicle_type: vehicleType, vehicle_number: vehicleNumber });
      if (res.success) {
        toast.success('Profil berhasil diperbarui');
        updateUser({ name, phone, email, vehicleType, vehicleNumber });
        setEditing(false);
        fetchProfile();
      }
    } catch (err: unknown) {
      toast.error(getErrorMessage(err) || 'Gagal menyimpan');
    } finally {
      setSaving(false);
    }
  };

  const handleChangePassword = async () => {
    if (!oldPw || !newPw) { toast.error('Isi semua field password'); return; }
    if (newPw.length < 6) { toast.error('Password baru minimal 6 karakter'); return; }
    if (newPw !== confirmPw) { toast.error('Konfirmasi password tidak cocok'); return; }
    setSaving(true);
    try {
      const res = await kurirApi.updateProfile({ current_password: oldPw, new_password: newPw });
      if (res.success) {
        toast.success('Password berhasil diubah');
        setChangingPw(false);
        setOldPw(''); setNewPw(''); setConfirmPw('');
      }
    } catch (err: unknown) {
      toast.error(getErrorMessage(err) || 'Gagal mengganti password');
    } finally {
      setSaving(false);
    }
  };

  const handleLogout = () => {
    logout();
    router.push('/kurir/login');
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-60">
        <div className="w-8 h-8 border-3 border-amber-200 border-t-amber-600 rounded-full animate-spin"></div>
      </div>
    );
  }

  if (!profile) return null;

  const vehicleLabel: Record<string, string> = { motor: '🏍️ Motor', mobil: '🚗 Mobil', sepeda: '🚲 Sepeda' };

  return (
    <div className="space-y-4 pb-4">
      {/* Profile Card */}
      <div className="bg-gradient-to-br from-amber-600 to-amber-700 rounded-2xl p-5 text-white relative overflow-hidden">
        <div className="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div className="flex items-center gap-4">
          <div className="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0 overflow-hidden">
            {profile.photo ? (
              <img src={profile.photo} alt="Photo" className="w-full h-full object-cover rounded-full" />
            ) : (
              <i className="bi bi-person-fill text-3xl text-white/70"></i>
            )}
          </div>
          <div>
            <h1 className="text-xl font-bold">{profile.name}</h1>
            <p className="text-amber-100 text-sm">{profile.phone}</p>
            <p className="text-amber-200 text-xs mt-0.5">Bergabung {profile.joinDate ? new Date(profile.joinDate).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) : '-'}</p>
          </div>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 gap-3">
        <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl p-4 border border-gray-100 dark:border-gray-700 text-center">
          <div className="flex items-center justify-center gap-1 mb-1">
            <i className="bi bi-star-fill text-yellow-500 text-lg"></i>
            <span className="text-2xl font-bold text-gray-800 dark:text-white">{profile.rating.toFixed(1)}</span>
          </div>
          <p className="text-xs text-gray-500">Rating</p>
        </div>
        <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl p-4 border border-gray-100 dark:border-gray-700 text-center">
          <div className="flex items-center justify-center gap-1 mb-1">
            <i className="bi bi-box-seam text-amber-600 text-lg"></i>
            <span className="text-2xl font-bold text-gray-800 dark:text-white">{profile.totalDeliveries}</span>
          </div>
          <p className="text-xs text-gray-500">Total Pengiriman</p>
        </div>
      </div>

      {/* Info / Edit Form */}
      <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-sm font-bold text-gray-800 dark:text-white">Informasi</h2>
          {!editing && (
            <button onClick={() => setEditing(true)} className="text-xs text-amber-600 font-medium hover:underline">
              <i className="bi bi-pencil mr-1"></i>Edit
            </button>
          )}
        </div>

        {editing ? (
          <div className="space-y-3">
            <div>
              <label className="text-xs text-gray-500 mb-1 block">Nama</label>
              <input value={name} onChange={e => setName(e.target.value)}
                className="w-full px-3 py-2.5 bg-gray-50 dark:bg-[#333] border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 outline-none" />
            </div>
            <div>
              <label className="text-xs text-gray-500 mb-1 block">Nomor HP</label>
              <input value={phone} onChange={e => setPhone(e.target.value)}
                className="w-full px-3 py-2.5 bg-gray-50 dark:bg-[#333] border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 outline-none" />
            </div>
            <div>
              <label className="text-xs text-gray-500 mb-1 block">Email</label>
              <input value={email} onChange={e => setEmail(e.target.value)} type="email"
                className="w-full px-3 py-2.5 bg-gray-50 dark:bg-[#333] border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 outline-none" />
            </div>
            <div>
              <label className="text-xs text-gray-500 mb-1 block">Kendaraan</label>
              <div className="grid grid-cols-3 gap-2">
                {['motor', 'mobil', 'sepeda'].map(v => (
                  <button key={v} onClick={() => setVehicleType(v)}
                    className={`py-2 rounded-xl text-sm font-medium border transition-colors ${
                      vehicleType === v ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 text-amber-600' :
                      'border-gray-200 dark:border-gray-600 text-gray-500 hover:border-gray-300'
                    }`}>
                    {vehicleLabel[v] || v}
                  </button>
                ))}
              </div>
            </div>
            <div>
              <label className="text-xs text-gray-500 mb-1 block">Nomor Kendaraan</label>
              <input value={vehicleNumber} onChange={e => setVehicleNumber(e.target.value)}
                className="w-full px-3 py-2.5 bg-gray-50 dark:bg-[#333] border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 outline-none uppercase" />
            </div>
            <div className="flex gap-2 pt-2">
              <button onClick={() => setEditing(false)} className="flex-1 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300">
                Batal
              </button>
              <button onClick={handleSave} disabled={saving}
                className={`flex-1 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-medium transition-colors ${saving ? 'opacity-60' : ''}`}>
                {saving ? 'Menyimpan...' : 'Simpan'}
              </button>
            </div>
          </div>
        ) : (
          <div className="space-y-3">
            {[
              { icon: 'bi-person', label: 'Nama', value: profile.name },
              { icon: 'bi-telephone', label: 'Telepon', value: profile.phone },
              { icon: 'bi-envelope', label: 'Email', value: profile.email || '-' },
              { icon: 'bi-truck', label: 'Kendaraan', value: vehicleLabel[profile.vehicleType] || profile.vehicleType },
              { icon: 'bi-hash', label: 'Nomor Kendaraan', value: profile.vehicleNumber || '-' },
            ].map((item, idx) => (
              <div key={idx} className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-full bg-amber-50 dark:bg-amber-900/10 flex items-center justify-center flex-shrink-0">
                  <i className={`bi ${item.icon} text-amber-600 dark:text-amber-400 text-sm`}></i>
                </div>
                <div>
                  <p className="text-[10px] text-gray-400 uppercase tracking-wider">{item.label}</p>
                  <p className="text-sm font-medium text-gray-700 dark:text-gray-200">{item.value}</p>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Change Password */}
      <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        {!changingPw ? (
          <button onClick={() => setChangingPw(true)} className="w-full flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
            <span className="flex items-center gap-2"><i className="bi bi-lock text-gray-400"></i> Ubah Password</span>
            <i className="bi bi-chevron-right text-gray-400 text-xs"></i>
          </button>
        ) : (
          <div className="space-y-3">
            <h3 className="text-sm font-bold text-gray-800 dark:text-white">Ubah Password</h3>
            <input type="password" placeholder="Password saat ini" value={oldPw} onChange={e => setOldPw(e.target.value)}
              className="w-full px-3 py-2.5 bg-gray-50 dark:bg-[#333] border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 outline-none" />
            <input type="password" placeholder="Password baru" value={newPw} onChange={e => setNewPw(e.target.value)}
              className="w-full px-3 py-2.5 bg-gray-50 dark:bg-[#333] border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 outline-none" />
            <input type="password" placeholder="Konfirmasi password baru" value={confirmPw} onChange={e => setConfirmPw(e.target.value)}
              className="w-full px-3 py-2.5 bg-gray-50 dark:bg-[#333] border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 outline-none" />
            <div className="flex gap-2">
              <button onClick={() => { setChangingPw(false); setOldPw(''); setNewPw(''); setConfirmPw(''); }}
                className="flex-1 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300">
                Batal
              </button>
              <button onClick={handleChangePassword} disabled={saving}
                className={`flex-1 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-medium transition-colors ${saving ? 'opacity-60' : ''}`}>
                {saving ? 'Menyimpan...' : 'Ubah'}
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Logout */}
      <button onClick={handleLogout}
        className="w-full py-3 bg-red-50 dark:bg-red-900/10 text-red-600 dark:text-red-400 rounded-2xl text-sm font-medium flex items-center justify-center gap-2 hover:bg-red-100 transition-colors">
        <i className="bi bi-box-arrow-left"></i> Keluar
      </button>
    </div>
  );
}
