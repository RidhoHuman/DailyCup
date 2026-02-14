"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api-client";
import { getErrorMessage } from '@/lib/utils';
import toast from "react-hot-toast";
import Link from "next/link";

interface Invitation {
  id: number;
  invitation_code: string;
  invited_name: string;
  invited_phone: string;
  invited_email: string;
  vehicle_type: string;
  status: 'pending' | 'used' | 'expired';
  created_at: string;
  expires_at: string;
  used_at: string | null;
  used_by: number | null;
  kurir_name: string | null;
}

export default function KurirInvitationsPage() {
  const [invitations, setInvitations] = useState<Invitation[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [creating, setCreating] = useState(false);
  const [statusFilter, setStatusFilter] = useState<string>('all');
  
  const [form, setForm] = useState({
    name: '',
    phone: '',
    email: '',
    vehicle_type: 'motor',
    notes: '',
    expires_days: '7'
  });

  useEffect(() => {
    fetchInvitations();
  }, [statusFilter]);

  const fetchInvitations = async () => {
    try {
      setLoading(true);
      const url = statusFilter === 'all' 
        ? '/get_kurir_invitations.php'
        : `/get_kurir_invitations.php?status=${statusFilter}`;
      
      const res = await api.get<{ success: boolean; invitations: Invitation[] }>(url, { requiresAuth: true });
      if (res.success) {
        setInvitations(res.invitations || []);
      }
    } catch (error) {
      console.error('Error fetching invitations:', error);
    } finally {
      setLoading(false);
    }
  };

  const generateInvitation = async (e: React.FormEvent) => {
    e.preventDefault();
    setCreating(true);
    
    try {
      const res = await api.post<{ success: boolean; message: string; invitation: Invitation }>(
        '/create_kurir_invitation.php',
        {
          invited_name: form.name,
          invited_phone: form.phone,
          invited_email: form.email || null,
          vehicle_type: form.vehicle_type,
          notes: form.notes || null,
          expires_days: parseInt(form.expires_days)
        },
        { requiresAuth: true }
      );
      
      if (res.success) {
        toast.success('Undangan kurir berhasil dibuat!');
        setShowModal(false);
        setForm({ name: '', phone: '', email: '', vehicle_type: 'motor', notes: '', expires_days: '7' });
        fetchInvitations();
      } else {
        toast.error(res.message || 'Gagal membuat undangan');
      }
    } catch (error: unknown) {
      toast.error(getErrorMessage(error) || 'Gagal membuat undangan');
    } finally {
      setCreating(false);
    }
  };

  const copyInvitationLink = (code: string) => {
    const link = `${window.location.origin}/kurir/register?code=${code}`;
    navigator.clipboard.writeText(link);
    toast.success('Link undangan disalin!');
  };

  const deleteInvitation = async (id: number) => {
    if (!confirm('Hapus undangan ini?')) return;
    
    try {
      await api.delete(`/delete_kurir_invitation.php?id=${id}`, { requiresAuth: true });
      toast.success('Undangan dihapus');
      fetchInvitations();
    } catch (error: unknown) {
      toast.error(getErrorMessage(error) || 'Gagal menghapus undangan');
    }
  };

  const getStatusBadge = (status: string) => {
    const badges = {
      pending: 'bg-yellow-100 text-yellow-700',
      used: 'bg-green-100 text-green-700',
      expired: 'bg-red-100 text-red-700'
    };
    return badges[status as keyof typeof badges] || 'bg-gray-100 text-gray-700';
  };

  const getStatusLabel = (status: string) => {
    const labels = {
      pending: 'Menunggu',
      used: 'Digunakan',
      expired: 'Kedaluwarsa'
    };
    return labels[status as keyof typeof labels] || status;
  };

  return (
    <div>
      {/* Header */}
      <div className="mb-8">
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div>
            <h1 className="text-2xl font-bold text-gray-800 mb-2">
              <i className="bi bi-ticket-perforated mr-2"></i>Undangan Kurir
            </h1>
            <p className="text-gray-500">Kelola undangan pendaftaran kurir internal</p>
          </div>
          <button
            onClick={() => setShowModal(true)}
            className="px-4 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8b6043] transition-colors flex items-center gap-2"
          >
            <i className="bi bi-plus-circle"></i> Buat Undangan Baru
          </button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        {[
          { label: 'Total', count: invitations.length, color: 'blue', icon: 'bi-ticket' },
          { label: 'Menunggu', count: invitations.filter(i => i.status === 'pending').length, color: 'yellow', icon: 'bi-clock' },
          { label: 'Digunakan', count: invitations.filter(i => i.status === 'used').length, color: 'green', icon: 'bi-check-circle' },
          { label: 'Kedaluwarsa', count: invitations.filter(i => i.status === 'expired').length, color: 'red', icon: 'bi-x-circle' }
        ].map((stat, idx) => (
          <div key={idx} className="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div className="flex items-center gap-3">
              <div className={`w-10 h-10 bg-${stat.color}-100 text-${stat.color}-600 rounded-lg flex items-center justify-center`}>
                <i className={`bi ${stat.icon}`}></i>
              </div>
              <div>
                <p className="text-2xl font-bold text-gray-800">{stat.count}</p>
                <p className="text-sm text-gray-500">{stat.label}</p>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Filter */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <div className="flex items-center gap-2">
          <i className="bi bi-funnel text-gray-400"></i>
          <span className="text-sm font-medium text-gray-700">Filter:</span>
          {['all', 'pending', 'used', 'expired'].map(status => (
            <button
              key={status}
              onClick={() => setStatusFilter(status)}
              className={`px-3 py-1 rounded-lg text-sm transition-colors ${
                statusFilter === status
                  ? 'bg-[#a97456] text-white'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              }`}
            >
              {status === 'all' ? 'Semua' : getStatusLabel(status)}
            </button>
          ))}
        </div>
      </div>

      {/* Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center h-64">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456]"></div>
          </div>
        ) : invitations.length === 0 ? (
          <div className="text-center py-12">
            <i className="bi bi-inbox text-4xl text-gray-300 mb-2"></i>
            <p className="text-gray-500">Belum ada undangan</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Calon Kurir</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kendaraan</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dibuat</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kedaluwarsa</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {invitations.map(inv => (
                  <tr key={inv.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-4 py-3">
                      <code className="text-xs font-mono bg-gray-100 px-2 py-1 rounded">{inv.invitation_code}</code>
                    </td>
                    <td className="px-4 py-3">
                      <div>
                        <p className="font-medium text-gray-800">{inv.invited_name}</p>
                        <p className="text-xs text-gray-500">{inv.invited_phone}</p>
                        {inv.invited_email && <p className="text-xs text-gray-500">{inv.invited_email}</p>}
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <span className="text-sm capitalize">{inv.vehicle_type}</span>
                    </td>
                    <td className="px-4 py-3">
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusBadge(inv.status)}`}>
                        {getStatusLabel(inv.status)}
                      </span>
                      {inv.status === 'used' && inv.kurir_name && (
                        <p className="text-xs text-gray-500 mt-1">oleh {inv.kurir_name}</p>
                      )}
                    </td>
                    <td className="px-4 py-3 text-sm text-gray-600">
                      {new Date(inv.created_at).toLocaleDateString('id-ID')}
                    </td>
                    <td className="px-4 py-3 text-sm text-gray-600">
                      {new Date(inv.expires_at).toLocaleDateString('id-ID')}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <div className="flex items-center justify-end gap-2">
                        {inv.status === 'pending' && (
                          <button
                            onClick={() => copyInvitationLink(inv.invitation_code)}
                            className="text-blue-600 hover:text-blue-700 text-sm"
                            title="Copy link"
                          >
                            <i className="bi bi-link-45deg"></i>
                          </button>
                        )}
                        {inv.status !== 'used' && (
                          <button
                            onClick={() => deleteInvitation(inv.id)}
                            className="text-red-600 hover:text-red-700 text-sm"
                            title="Hapus"
                          >
                            <i className="bi bi-trash"></i>
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Create Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-xl font-bold text-gray-800">Buat Undangan Kurir</h3>
              <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-gray-600">
                <i className="bi bi-x-lg"></i>
              </button>
            </div>
            
            <form onSubmit={generateInvitation} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Nama Calon Kurir</label>
                <input
                  type="text"
                  value={form.name}
                  onChange={(e) => setForm({...form, name: e.target.value})}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  required
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Nomor HP</label>
                <input
                  type="tel"
                  value={form.phone}
                  onChange={(e) => setForm({...form, phone: e.target.value})}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  placeholder="08xxxxxxxxxx"
                  required
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Email (opsional)</label>
                <input
                  type="email"
                  value={form.email}
                  onChange={(e) => setForm({...form, email: e.target.value})}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Jenis Kendaraan</label>
                <select
                  value={form.vehicle_type}
                  onChange={(e) => setForm({...form, vehicle_type: e.target.value})}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                >
                  <option value="motor">Motor</option>
                  <option value="mobil">Mobil</option>
                  <option value="sepeda">Sepeda</option>
                </select>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Berlaku Selama (hari)</label>
                <input
                  type="number"
                  value={form.expires_days}
                  onChange={(e) => setForm({...form, expires_days: e.target.value})}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  min="1"
                  max="30"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                <textarea
                  value={form.notes}
                  onChange={(e) => setForm({...form, notes: e.target.value})}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  rows={2}
                  placeholder="Catatan internal..."
                ></textarea>
              </div>
              
              <div className="flex gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => setShowModal(false)}
                  className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={creating}
                  className="flex-1 px-4 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8b6043] disabled:bg-gray-400"
                >
                  {creating ? 'Membuat...' : 'Buat Undangan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
