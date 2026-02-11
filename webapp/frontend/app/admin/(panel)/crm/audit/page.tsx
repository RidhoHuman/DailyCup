'use client';
import { useEffect, useState } from 'react';
import { api } from '@/lib/api-client';

export default function AuditPage(){
  const [date,setDate] = useState(new Date().toISOString().slice(0,10));
  const [logs,setLogs] = useState<any[]>([]);
  const [loading,setLoading] = useState(false);

  useEffect(()=>{ fetch(); }, [date]);
  const fetch = async ()=>{
    setLoading(true);
    try {
      const res:any = await api.get('admin/audit.php?action=list&date='+date);
      if (res.success) setLogs(res.logs || []);
    } catch(e){ console.error(e); }
    setLoading(false);
  };

  const exportCsv = ()=>{ window.location.href = '/backend/api/admin/audit.php?action=export&date='+date; };

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-4">Audit Log</h1>
      <div className="bg-white p-4 rounded mb-4 flex items-center gap-3">
        <input type="date" className="p-2 border" value={date} onChange={(e)=>setDate(e.target.value)} />
        <button className="px-3 py-1 bg-blue-600 text-white rounded" onClick={fetch}>Refresh</button>
        <button className="px-3 py-1 border rounded" onClick={exportCsv}>Export CSV</button>
      </div>
      <div className="bg-white p-4 rounded">
        {loading ? <div>Loading...</div> : (
          <ul className="space-y-2 text-sm">
            {logs.map(l => <li key={l.id}><strong>{l.timestamp}</strong> • <em>{l.action}</em> • {l.level} • {l.user_id||'system'} • {l.data?.type || ''}</li>)}
          </ul>
        )}
      </div>
    </div>
  );
}
