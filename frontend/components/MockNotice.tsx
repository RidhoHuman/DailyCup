'use client';

import { useEffect, useState } from 'react';

export default function MockNotice() {
  const [show, setShow] = useState(false);
  const [endpoint, setEndpoint] = useState<string | null>(null);

  useEffect(() => {
    const handler = (e: Event) => {
      const detail = (e as CustomEvent).detail;
      setEndpoint(detail?.endpoint || null);
      setShow(true);
    };

    window.addEventListener('api:mock', handler as EventListener);
    return () => window.removeEventListener('api:mock', handler as EventListener);
  }, []);

  if (!show) return null;

  return (
    <div className="fixed top-6 right-6 z-50 max-w-xs w-full bg-yellow-50 border border-yellow-300 text-yellow-900 p-3 rounded shadow">
      <div className="flex items-start gap-3">
        <div className="flex-1">
          <strong className="block">Using mock data</strong>
          <p className="text-xs mt-1">Some API calls failed, app is using local mock data{endpoint ? ` for ${endpoint}` : ''}.</p>
        </div>
        <div>
          <button
            onClick={() => setShow(false)}
            className="text-sm text-yellow-800 hover:text-yellow-900 px-2 py-1"
            aria-label="Dismiss mock notice"
          >
            ✕
          </button>
        </div>
      </div>
    </div>
  );
}
