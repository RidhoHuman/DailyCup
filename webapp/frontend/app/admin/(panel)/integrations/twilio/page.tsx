'use client';

import { useState, useEffect } from 'react';
import { api, endpoints } from '@/lib/api-client';

export default function TwilioIntegrationPage() {
  const [loading, setLoading] = useState(true);
  const [settings, setSettings] = useState<any>({});
  const [saving, setSaving] = useState(false);
  const [sendTo, setSendTo] = useState('');
  const [sendBody, setSendBody] = useState('');
  const [selectedProvider, setSelectedProvider] = useState('twilio');
  const [logs, setLogs] = useState<any[]>([]);
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(50);
  const [total, setTotal] = useState(0);
  const [statusFilter, setStatusFilter] = useState('');
  const [directionFilter, setDirectionFilter] = useState('');
  const [fromDate, setFromDate] = useState('');
  const [toDate, setToDate] = useState('');
  const [loadingLogs, setLoadingLogs] = useState(false);
  const [selectedLog, setSelectedLog] = useState<any>(null);

  // worker status & alerts
  const [workerStatus, setWorkerStatus] = useState<any>(null);
  const [workerLoading, setWorkerLoading] = useState(false);

  // provider settings UI
  const [providerName, setProviderName] = useState('twilio');
  const [providerSettings, setProviderSettings] = useState<Record<string,string>>({});
  const [providerSaving, setProviderSaving] = useState(false);

  useEffect(() => {
    fetchSettings();
    fetchWorkerStatus();
    fetchProviderSettings('twilio');
  }, []);

  useEffect(() => {
    fetchLogs(page);
  }, [page, limit, statusFilter, directionFilter, fromDate, toDate]);

  const fetchWorkerStatus = async () => {
    try {
      setWorkerLoading(true);
      const res: any = await api.get(endpoints.integrations.twilio.workerStatus());
      if (res.success) setWorkerStatus(res);
    } catch (e) {
      console.error('Failed fetching worker status', e);
    } finally {
      setWorkerLoading(false);
    }
  };

  const runWorkerNow = async () => {
    if (!confirm('Run worker now?')) return;
    try {
      setWorkerLoading(true);
      const res: any = await api.post(endpoints.integrations.twilio.runWorker());
      alert('Worker run: ' + (res.success ? 'OK' : 'Failed'));
      fetchWorkerStatus();
    } catch (e: any) {
      alert('Run failed: ' + (e.message || ''));
    } finally { setWorkerLoading(false); }
  };

  const sendTestAlert = async () => {
    if (!confirm('Send test security alert (Slack/email) now?')) return;
    try {
      const res: any = await api.post(endpoints.integrations.twilio.testAlert());
      alert(res.success ? 'Test alert sent' : 'Failed to send test alert');
    } catch (e: any) {
      alert('Failed: ' + (e.message || ''));
    }
  };

  const fetchProviderSettings = async (p = 'twilio') => {
    try {
      const url = endpoints.integrations.twilio.providerSettings() + '&provider=' + encodeURIComponent(p);
      const res: any = await api.get(url);
      if (res.success) {
        setProviderName(p);
        setProviderSettings(res.settings || {});
      }
    } catch (e) {
      console.error('Failed fetching provider settings', e);
    }
  };

  const saveProviderSettings = async () => {
    try {
      setProviderSaving(true);
      const payload = { provider: providerName, settings: providerSettings };
      const res: any = await api.post(endpoints.integrations.twilio.providerSettings(), payload);
      if (res.success) {
        alert('Provider settings saved');
      }
    } catch (e: any) {
      alert('Save failed: ' + (e.message || ''));
    } finally { setProviderSaving(false); }
  };

  const fetchSettings = async () => {
    try {
      setLoading(true);
      const res: any = await api.get(endpoints.integrations.twilio.settings());
      if (res.success) setSettings(res.settings || {});
    } catch (e) {
      console.error('Failed fetching settings', e);
    } finally {
      setLoading(false);
    }
  };

  const saveSettings = async () => {
    try {
      setSaving(true);
      await api.post(endpoints.integrations.twilio.saveSettings(), settings);
      alert('Settings saved');
      fetchSettings();
    } catch (e: any) {
      alert('Failed saving settings: ' + (e.message || e?.data?.message || ''));
    } finally {
      setSaving(false);
    }
  };

  const testCredentials = async () => {
    if (!confirm('Test Twilio credentials (will use env vars if set)?')) return;
    try {
      const payload: any = {};
      if (settings.twilio_account_sid) payload.account_sid = settings.twilio_account_sid;
      if (settings.twilio_auth_token) payload.auth_token = settings.twilio_auth_token;
      const res: any = await api.post('admin/credentials.php?action=test', payload);
      if (res?.success && res?.result) {
        if (res.result.success) {
          alert('Credentials valid (provider: ' + (res.result.provider || 'twilio') + ')');
        } else {
          alert('Credentials test failed: ' + (res.result.error || JSON.stringify(res.result)));
        }
      } else {
        alert('Test failed');
      }
    } catch (e: any) {
      alert('Test failed: ' + (e.message || ''));
    }
  };

  const fetchLogs = async (p = 1) => {
    try {
      setLoadingLogs(true);
      const q = new URLSearchParams();
      q.set('page', String(p));
      q.set('limit', String(limit));
      if (statusFilter) q.set('status', statusFilter);
      if (directionFilter) q.set('direction', directionFilter);
      if (fromDate) q.set('from', fromDate);
      if (toDate) q.set('to', toDate);

      const url = endpoints.integrations.twilio.logs() + '&' + q.toString();
      const res: any = await api.get(url);
      if (res.success) {
        setLogs(res.logs || []);
        setTotal(res.total || 0);
      }
    } catch (e) {
      console.error('Failed fetching logs', e);
    } finally {
      setLoadingLogs(false);
    }
  };

  const exportCSV = async () => {
    try {
      const q = new URLSearchParams();
      if (statusFilter) q.set('status', statusFilter);
      if (directionFilter) q.set('direction', directionFilter);
      if (fromDate) q.set('from', fromDate);
      if (toDate) q.set('to', toDate);
      q.set('limit', String(1000));
      q.set('page', '1');

      const url = endpoints.integrations.twilio.logs() + '&' + q.toString();
      const res: any = await api.get(url);
      if (!res.success) { alert('Export failed'); return; }
      const rows = res.logs || [];
      const csvRows = [];
      csvRows.push(['id','created_at','direction','to_number','from_number','status','body'].join(','));
      for (const r of rows) {
        const line = [r.id, r.created_at, r.direction, `"${r.to_number}"`, `"${(r.from_number||'')}"`, r.status, `"${(r.body||'').replace(/"/g,'""')}"`].join(',');
        csvRows.push(line);
      }
      const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
      const urlBlob = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = urlBlob;
      a.download = `twilio_logs_${new Date().toISOString().slice(0,10)}.csv`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(urlBlob);
    } catch (e) {
      alert('Export failed: ' + (e as any).message);
    }
  };

  const sendMessage = async () => {
    if (!sendTo || !sendBody) { alert('Fill to & body'); return; }
    try {
      const payload: any = { provider: selectedProvider, to: sendTo, body: sendBody };
      if (selectedProvider === 'twilio') {
        // ensure whatsapp prefix
        if (!sendTo.startsWith('whatsapp:') && sendTo.match(/^[+0-9]/)) {
          payload.to = 'whatsapp:' + sendTo;
        }
      }

      const res: any = await api.post(endpoints.integrations.send.send(), payload);
      if (res.success) {
        alert('Message sent');
        setSendTo(''); setSendBody('');
        fetchLogs();
      }
    } catch (e: any) {
      alert('Send failed: ' + (e.message || e?.data?.message || ''));
    }
  };

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-4">Twilio (WhatsApp) Integration</h1>

      <section className="bg-white rounded-lg p-4 mb-6">
        <div className="flex items-center justify-between">
          <h3 className="font-semibold mb-2">Settings</h3>
          <div className="space-x-2">
            <button onClick={fetchWorkerStatus} className="px-3 py-1 border rounded">Refresh Worker Status</button>
            <button onClick={runWorkerNow} disabled={workerLoading} className="px-3 py-1 bg-yellow-500 text-white rounded">Run Worker Now</button>
            <button onClick={sendTestAlert} className="px-3 py-1 bg-red-500 text-white rounded">Send Test Alert</button>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
          <input className="p-2 border" placeholder="Account SID" value={settings.twilio_account_sid || ''} onChange={(e)=>setSettings({...settings, twilio_account_sid: e.target.value})} />
          <input className="p-2 border" placeholder="Auth Token" value={settings.twilio_auth_token || ''} onChange={(e)=>setSettings({...settings, twilio_auth_token: e.target.value})} />
          <input className="p-2 border" placeholder="WhatsApp From (e.g. whatsapp:+1415...)" value={settings.twilio_whatsapp_from || ''} onChange={(e)=>setSettings({...settings, twilio_whatsapp_from: e.target.value})} />
          <input className="p-2 border" placeholder="Webhook Secret (optional)" value={settings.twilio_webhook_secret || ''} onChange={(e)=>setSettings({...settings, twilio_webhook_secret: e.target.value})} />
        </div>
        <div className="mt-3 flex items-center gap-3">
          <button onClick={saveSettings} disabled={saving} className="px-4 py-2 bg-blue-600 text-white rounded">Save Settings</button>
          <button onClick={testCredentials} className="px-4 py-2 bg-gray-200 rounded">Test Credentials</button>
          <div className="text-xs text-gray-500">Tip: you can set credentials via env vars in staging/production (`TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_WHATSAPP_FROM`) — the backend will prefer env vars.</div>
        </div>

        <div className="mt-3 p-3 bg-gray-50 rounded">
          <div className="text-sm text-gray-700">Worker status: {workerLoading ? 'loading...' : (workerStatus?.last_run || 'never')}</div>
          <div className="text-xs text-gray-500 mt-1">Summary: {workerStatus?.summary ? JSON.stringify(workerStatus.summary) : 'n/a'}</div>
        </div>
      </section>

      <section className="bg-white rounded-lg p-4 mb-6">
        <h3 className="font-semibold mb-2">Provider Settings</h3>
        <div className="flex items-center gap-3 mb-3">
          <select value={providerName} onChange={(e)=>{ setProviderName(e.target.value); fetchProviderSettings(e.target.value); }} className="p-2 border">
            <option value="twilio">Twilio</option>
            <option value="mock">Mock</option>
          </select>
          <button onClick={()=>fetchProviderSettings(providerName)} className="px-3 py-1 border rounded">Refresh</button>
        </div>

        <div className="space-y-2">
          {Object.keys(providerSettings).length === 0 ? (
            <div className="text-sm text-gray-500">No settings</div>
          ) : (
            Object.entries(providerSettings).map(([k,v]) => (
              <div key={k} className="flex gap-2 items-center">
                <div className="w-40 text-xs text-gray-600">{k}</div>
                <input className="flex-1 p-2 border" value={v} onChange={(e)=>setProviderSettings({...providerSettings, [k]: e.target.value})} />
              </div>
            ))
          )}

          <div className="flex gap-2">
            <input id="newKey" placeholder="key" className="p-2 border" />
            <input id="newValue" placeholder="value" className="p-2 border" />
            <button onClick={()=>{
              const k = (document.getElementById('newKey') as HTMLInputElement).value.trim();
              const v = (document.getElementById('newValue') as HTMLInputElement).value;
              if (!k) return alert('Enter key');
              setProviderSettings({...providerSettings, [k]: v});
              (document.getElementById('newKey') as HTMLInputElement).value = '';
              (document.getElementById('newValue') as HTMLInputElement).value = '';
            }} className="px-3 py-1 bg-gray-200">Add</button>
          </div>

          <div>
            <button onClick={saveProviderSettings} disabled={providerSaving} className="px-4 py-2 bg-indigo-600 text-white rounded">Save Provider Settings</button>
          </div>
        </div>
      </section>

      <section className="bg-white rounded-lg p-4 mb-6">
        <h3 className="font-semibold mb-2">Send Test Message</h3>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
          <select className="p-2 border" value={selectedProvider} onChange={(e)=>setSelectedProvider(e.target.value)}>
            <option value="twilio">Twilio (WhatsApp)</option>
            <option value="mock">Mock (Testing)</option>
          </select>
          <input className="p-2 border" placeholder="To (e.g., whatsapp:+62 or +62)" value={sendTo} onChange={(e)=>setSendTo(e.target.value)} />
          <input className="p-2 border" placeholder="Message body" value={sendBody} onChange={(e)=>setSendBody(e.target.value)} />
        </div>
        <div className="mt-3">
          <button onClick={sendMessage} className="px-4 py-2 bg-green-600 text-white rounded">Send</button>
        </div>
      </section>

      <section className="bg-white rounded-lg p-4">
        <h3 className="font-semibold mb-2">Message Logs</h3>

        <div className="flex items-center gap-3 mb-3">
          <div>
            <label className="text-xs text-gray-600">Direction</label>
            <select className="p-2 border ml-2" value={directionFilter} onChange={(e)=>{ setDirectionFilter(e.target.value); setPage(1); }}>
              <option value="">All</option>
              <option value="outbound">Outbound</option>
              <option value="inbound">Inbound</option>
            </select>
          </div>

          <div>
            <label className="text-xs text-gray-600">Status</label>
            <select className="p-2 border ml-2" value={statusFilter} onChange={(e)=>{ setStatusFilter(e.target.value); setPage(1); }}>
              <option value="">All</option>
              <option value="queued">queued</option>
              <option value="sent">sent</option>
              <option value="delivered">delivered</option>
              <option value="failed">failed</option>
              <option value="retry_scheduled">retry_scheduled</option>
            </select>
          </div>

          <div>
            <label className="text-xs text-gray-600">Per page</label>
            <select className="p-2 border ml-2" value={limit} onChange={(e)=>{ setLimit(parseInt(e.target.value)); setPage(1); }}>
              <option value={20}>20</option>
              <option value={50}>50</option>
              <option value={100}>100</option>
            </select>
          </div>

          <div className="ml-auto text-sm text-gray-600">Total: {total}</div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-left text-xs text-gray-500">
              <tr>
                <th className="px-3 py-2">Time</th>
                <th className="px-3 py-2">Direction</th>
                <th className="px-3 py-2">To</th>
                <th className="px-3 py-2">From</th>
                <th className="px-3 py-2">Status</th>
                <th className="px-3 py-2">Body</th>
              </tr>
            </thead>
            <tbody>
              {loadingLogs ? (
                <tr><td colSpan={6} className="p-4 text-center">Loading...</td></tr>
              ) : logs.length === 0 ? (
                <tr><td colSpan={6} className="p-4 text-center">No logs</td></tr>
              ) : (
                logs.map(l => (
                  <tr key={l.id} className="border-t hover:bg-gray-50 cursor-pointer" onClick={()=>setSelectedLog(l)}>
                    <td className="px-3 py-2">{new Date(l.created_at).toLocaleString()}</td>
                    <td className="px-3 py-2">{l.direction}</td>
                    <td className="px-3 py-2">{l.to_number}</td>
                    <td className="px-3 py-2">{l.from_number}</td>
                    <td className="px-3 py-2">{l.status}</td>
                    <td className="px-3 py-2">{l.body?.substring(0, 120)}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="flex items-center justify-between mt-3">
          <div className="text-sm text-gray-600">Page {page} • {(Math.ceil(total / limit) || 1)} pages</div>
          <div className="space-x-2">
            <button disabled={page <= 1} onClick={()=>setPage(p=>Math.max(1,p-1))} className="px-3 py-1 border rounded">Prev</button>
            <button disabled={page >= Math.ceil(total/limit)} onClick={()=>setPage(p=>p+1)} className="px-3 py-1 border rounded">Next</button>
          </div>
        </div>

        {selectedLog && (
          <div className="fixed inset-0 bg-black/40 flex items-center justify-center">
            <div className="bg-white rounded p-4 w-3/4 max-h-[80vh] overflow-auto">
              <div className="flex justify-between items-center mb-2">
                <h4 className="font-semibold">Message #{selectedLog.id}</h4>
                <button onClick={()=>setSelectedLog(null)} className="px-2 py-1 border rounded">Close</button>
              </div>
              <div className="space-y-3">
                <div className="text-sm text-gray-700">From: {selectedLog.from_number} • To: {selectedLog.to_number}</div>
                <div className="text-sm text-gray-700">Status: {selectedLog.status} • Direction: {selectedLog.direction}</div>
                <div className="text-sm bg-gray-50 p-3 rounded">{selectedLog.body}</div>

                {(() => {
                  let meta = selectedLog.metadata;
                  if (typeof meta === 'string' && meta) {
                    try { meta = JSON.parse(meta); } catch (e) { meta = null; }
                  }
                  if (meta && meta.attachments && meta.attachments.length) {
                    return (
                      <div>
                        <h5 className="font-semibold mb-2">Attachments</h5>
                        <div className="grid grid-cols-3 gap-3">
                          {meta.attachments.map((a: any, idx: number) => (
                            <div key={idx} className="border p-2 rounded">
                              {a.content_type?.startsWith('image/') ? (
                                <img src={a.path.startsWith('http') || a.path.startsWith('/') ? a.path : `/${a.path}`} alt={a.filename} className="w-full h-32 object-cover" />
                              ) : (
                                <a href={a.path} target="_blank" rel="noreferrer" className="text-blue-600">{a.filename}</a>
                              )}
                              <div className="text-xs text-gray-500 mt-2">{a.content_type || ''}</div>
                            </div>
                          ))}
                        </div>
                      </div>
                    );
                  }
                  return null;
                })()}

                <pre className="text-xs bg-gray-100 p-3 rounded">{JSON.stringify(selectedLog, null, 2)}</pre>
              </div>
            </div>
          </div>
        )}
      </section>
    </div>
  );
}
