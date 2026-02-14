'use client';

import { useEffect, useState, useCallback } from 'react';
import { api } from '@/lib/api-client';

export default function AuditPage() {
  // Definisi tipe data
  interface AuditLog { 
    id: number; 
    timestamp: string; 
    action: string; 
    level?: string; 
    user_id?: number | string; 
    data?: { type?: string } | Record<string, unknown>; 
  }

  // State
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
  const [logs, setLogs] = useState<AuditLog[]>([]);
  const [loading, setLoading] = useState(false);

  // 1. Menggunakan useCallback agar fungsi ini tidak dibuat ulang setiap render
  // Ini mencegah infinite loop pada useEffect
  const fetchLogs = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get<{ success: boolean; logs?: AuditLog[] }>(
        `/admin/audit.php?action=list&date=${date}`
      );
      if (res.success) {
        setLogs(res.logs || []);
      } else {
        setLogs([]);
      }
    } catch (e: unknown) {
      console.error(e);
      setLogs([]);
    } finally {
      setLoading(false);
    }
  }, [date]); // Fungsi hanya berubah jika 'date' berubah

  // 2. useEffect sekarang aman karena fetchLogs sudah stabil
  useEffect(() => {
    fetchLogs();
  }, [fetchLogs]);

  // 3. Perbaikan URL Export agar dinamis mengikuti Environment Variable
  const exportCsv = () => {
    const baseUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
    // Kita gunakan window.open agar lebih aman
    window.open(`${baseUrl}/admin/audit.php?action=export&date=${date}`, '_blank');
  };

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-4">Audit Log</h1>
      
      <div className="bg-white p-4 rounded mb-4 flex flex-wrap items-center gap-3 shadow-sm border border-gray-100">
        <input 
          type="date" 
          className="p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
          value={date} 
          onChange={(e) => setDate(e.target.value)} 
        />
        <button 
          className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors disabled:opacity-50" 
          onClick={fetchLogs}
          disabled={loading}
        >
          {loading ? 'Loading...' : 'Refresh'}
        </button>
        <button 
          className="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 transition-colors" 
          onClick={exportCsv}
        >
          Export CSV
        </button>
      </div>

      <div className="bg-white p-4 rounded shadow-sm border border-gray-100">
        {loading ? (
          <div className="text-center py-8 text-gray-500">Loading data...</div>
        ) : logs.length === 0 ? (
          <div className="text-center py-8 text-gray-400">No logs found for this date.</div>
        ) : (
          <ul className="space-y-0 divide-y divide-gray-100 text-sm">
            {logs.map((l) => (
              <li key={l.id} className="py-3 hover:bg-gray-50 transition-colors px-2">
                <div className="flex flex-col sm:flex-row sm:items-center gap-2">
                  <span className="font-mono text-gray-500 text-xs w-36 shrink-0">{l.timestamp}</span>
                  <div className="flex-1">
                    <span className="font-bold text-gray-800 mr-2 uppercase text-xs tracking-wider bg-gray-100 px-2 py-0.5 rounded">
                        {l.action}
                    </span>
                    <span className="text-gray-600">
                        {typeof l.data?.type === 'string' ? l.data.type : ''}
                    </span>
                  </div>
                  <div className="text-xs text-gray-400">
                    {l.level || 'INFO'} • User: {l.user_id || 'System'}
                  </div>
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}