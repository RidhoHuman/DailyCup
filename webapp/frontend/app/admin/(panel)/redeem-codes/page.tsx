"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api-client";

interface RedeemCode {
  id: number;
  code: string;
  points: number;
  is_used: boolean;
  used_by: string | null;
  created_at: string;
  used_at: string | null;
}

export default function AdminRedeemCodesPage() {
  const [codes, setCodes] = useState<RedeemCode[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [generating, setGenerating] = useState(false);
  const [formData, setFormData] = useState({
    points: 100,
    count: 1
  });
  const [filter, setFilter] = useState<'all' | 'available' | 'used'>('all');

  useEffect(() => {
    fetchCodes();
  }, []);

  const fetchCodes = async () => {
    try {
      setLoading(true);
      const response = await api.get<{ success: boolean; codes: RedeemCode[] }>(
        '/redeem_codes.php',
        { requiresAuth: true }
      );
      if (response.success) {
        setCodes(response.codes);
      }
    } catch (error) {
      console.error('Error fetching redeem codes:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleGenerate = async (e: React.FormEvent) => {
    e.preventDefault();

    if (formData.count > 100) {
      alert('Maximum 100 codes can be generated at once');
      return;
    }

    try {
      setGenerating(true);
      const response = await api.post<{success: boolean, message?: string}>('/redeem_codes.php', formData, { requiresAuth: true });
      
      if (response.success) {
        alert(`Successfully generated ${formData.count} redeem codes!`);
        setShowForm(false);
        setFormData({ points: 100, count: 1 });
        fetchCodes();
      }
    } catch (error) {
      console.error('Error generating codes:', error);
      alert('Failed to generate codes');
    } finally {
      setGenerating(false);
    }
  };

  const handleDelete = async (id: number, code: string) => {
    if (!confirm(`Are you sure you want to delete code "${code}"?`)) return;

    try {
      await api.delete(`/redeem_codes.php?id=${id}`, { requiresAuth: true });
      fetchCodes();
    } catch (error) {
      console.error('Error deleting code:', error);
      alert('Failed to delete code');
    }
  };

  const filteredCodes = codes.filter(code => {
    if (filter === 'available') return !code.is_used;
    if (filter === 'used') return code.is_used;
    return true;
  });

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading && codes.length === 0) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#a97456] mx-auto mb-4"></div>
          <p className="text-gray-500">Loading redeem codes...</p>
        </div>
      </div>
    );
  }

  return (
    <div>
      {/* Header */}
      <div className="mb-8">
        <div className="flex justify-between items-center mb-4">
          <div>
            <h1 className="text-2xl font-bold text-gray-800 mb-2">Redeem Codes Management</h1>
            <p className="text-gray-500">Generate and manage loyalty points redeem codes</p>
          </div>
          <button
            onClick={() => setShowForm(true)}
            className="px-6 py-3 bg-[#a97456] text-white rounded-lg hover:bg-[#8b6043] transition-colors font-medium"
          >
            <i className="bi bi-gear mr-2"></i>
            Generate Codes
          </button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center">
              <i className="bi bi-check-circle text-2xl"></i>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-800">
                {codes.filter(c => !c.is_used).length}
              </p>
              <p className="text-sm text-gray-500">Available Codes</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
              <i className="bi bi-check2-circle text-2xl"></i>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-800">
                {codes.filter(c => c.is_used).length}
              </p>
              <p className="text-sm text-gray-500">Used Codes</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center">
              <i className="bi bi-gem text-2xl"></i>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-800">
                {codes.reduce((sum, code) => sum + code.points, 0).toLocaleString()}
              </p>
              <p className="text-sm text-gray-500">Total Points</p>
            </div>
          </div>
        </div>
      </div>

      {/* Filter */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div className="flex flex-wrap gap-3">
          <button
            onClick={() => setFilter('all')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              filter === 'all'
                ? 'bg-[#a97456] text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            All Codes
          </button>
          <button
            onClick={() => setFilter('available')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              filter === 'available'
                ? 'bg-green-500 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Available
          </button>
          <button
            onClick={() => setFilter('used')}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              filter === 'used'
                ? 'bg-blue-500 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Used
          </button>
        </div>
      </div>

      {/* Codes Table */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {filteredCodes.length === 0 ? (
          <div className="p-12 text-center text-gray-500">
            <i className="bi bi-ticket-perforated text-6xl text-gray-300 mb-4"></i>
            <p className="text-lg">No redeem codes found</p>
            <p className="text-sm">Click &quot;Generate Codes&quot; to create new codes</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Code</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Points</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Status</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Used By</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Created At</th>
                  <th className="px-6 py-4 text-right text-sm font-semibold text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {filteredCodes.map((code) => (
                  <tr key={code.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4">
                      <code className="px-3 py-1 bg-purple-100 text-purple-800 rounded font-mono font-bold text-sm">
                        {code.code}
                      </code>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-2">
                        <i className="bi bi-gem text-purple-500"></i>
                        <span className="font-semibold text-gray-800">{code.points}</span>
                        <span className="text-sm text-gray-500">pts</span>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      {code.is_used ? (
                        <span className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                          Used
                        </span>
                      ) : (
                        <span className="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                          Available
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4">
                      {code.used_by ? (
                        <div>
                          <div className="font-medium text-gray-800">{code.used_by}</div>
                          {code.used_at && (
                            <div className="text-xs text-gray-500">{formatDate(code.used_at)}</div>
                          )}
                        </div>
                      ) : (
                        <span className="text-gray-400">-</span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      {formatDate(code.created_at)}
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={() => handleDelete(code.id, code.code)}
                          className="px-3 py-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm"
                          title="Delete"
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

      {/* Generate Modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl max-w-md w-full">
            <div className="border-b border-gray-100 px-6 py-4">
              <div className="flex justify-between items-center">
                <h2 className="text-xl font-bold text-gray-800">Generate Redeem Codes</h2>
                <button
                  onClick={() => setShowForm(false)}
                  className="text-gray-400 hover:text-gray-600 text-2xl"
                >
                  <i className="bi bi-x-lg"></i>
                </button>
              </div>
            </div>

            <form onSubmit={handleGenerate} className="p-6 space-y-4">
              {/* Points Value */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Points Value per Code *
                </label>
                <div className="relative">
                  <input
                    type="number"
                    required
                    min="1"
                    value={formData.points}
                    onChange={(e) => setFormData({ ...formData, points: parseInt(e.target.value) })}
                    className="w-full px-4 py-2 pr-16 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                    placeholder="e.g. 100"
                  />
                  <div className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">
                    points
                  </div>
                </div>
                <p className="text-xs text-gray-500 mt-1">How many loyalty points will users get?</p>
              </div>

              {/* Number of Codes */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Number of Codes *
                </label>
                <input
                  type="number"
                  required
                  min="1"
                  max="100"
                  value={formData.count}
                  onChange={(e) => setFormData({ ...formData, count: parseInt(e.target.value) })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  placeholder="1"
                />
                <p className="text-xs text-gray-500 mt-1">Maximum 100 codes at once</p>
              </div>

              {/* Summary */}
              <div className="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <h3 className="font-semibold text-purple-800 mb-2">Summary</h3>
                <div className="space-y-1 text-sm text-purple-700">
                  <div className="flex justify-between">
                    <span>Total codes:</span>
                    <span className="font-semibold">{formData.count}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Points per code:</span>
                    <span className="font-semibold">{formData.points} pts</span>
                  </div>
                  <div className="flex justify-between border-t border-purple-300 pt-1 mt-1">
                    <span className="font-bold">Total points value:</span>
                    <span className="font-bold">{(formData.count * formData.points).toLocaleString()} pts</span>
                  </div>
                </div>
              </div>

              <div className="flex gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => setShowForm(false)}
                  className="flex-1 px-4 py-2 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                  disabled={generating}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 px-4 py-2 bg-[#a97456] text-white rounded-lg font-medium hover:bg-[#8b6043] transition-colors disabled:opacity-50"
                  disabled={generating}
                >
                  {generating ? (
                    <>
                      <i className="bi bi-hourglass-split mr-2 animate-spin"></i>
                      Generating...
                    </>
                  ) : (
                    <>
                      <i className="bi bi-gear mr-2"></i>
                      Generate Now
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
