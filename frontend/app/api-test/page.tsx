'use client';

import { useState } from 'react';
import { api, endpoints } from '@/lib';

interface ApiResult {
  endpoint: string;
  data?: unknown;
  apiUrl?: string;
  status?: number;
}

export default function ApiTestPage() {
  const [result, setResult] = useState<ApiResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const testProducts = async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await api.get(endpoints.products.list());
      setResult({ endpoint: 'Products', data });
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Unknown error';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const testCategories = async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await api.get(endpoints.categories.list());
      setResult({ endpoint: 'Categories', data });
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Unknown error';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const testDirectFetch = async () => {
    setLoading(true);
    setError(null);
    try {
      const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'Not set';
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if ((apiUrl || '').includes('ngrok-free.app')) headers['ngrok-skip-browser-warning'] = 'true';
      const response = await fetch(`${apiUrl}/products.php`, { headers });
      const data = await response.json();
      setResult({ 
        endpoint: 'Direct Fetch', 
        apiUrl,
        status: response.status,
        data 
      });
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Unknown error';
      setError(`Direct fetch failed: ${errorMessage}`);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-100 p-8">
      <div className="max-w-4xl mx-auto">
        <h1 className="text-3xl font-bold text-gray-800 mb-2">🔌 API Connection Test</h1>
        <p className="text-gray-600 mb-8">
          Testing connection between Frontend (Next.js) and Backend (PHP/Laragon)
        </p>

        <div className="bg-white rounded-xl shadow-sm p-6 mb-6">
          <h2 className="text-lg font-semibold mb-4">Environment Info</h2>
          <div className="bg-gray-50 rounded-lg p-4 font-mono text-sm">
            <p><span className="text-gray-500">API URL:</span> {process.env.NEXT_PUBLIC_API_URL || 'Not configured'}</p>
            <p><span className="text-gray-500">App Name:</span> {process.env.NEXT_PUBLIC_APP_NAME || 'Not set'}</p>
            <p><span className="text-gray-500">Mock Data:</span> {process.env.NEXT_PUBLIC_ENABLE_MOCK_DATA || 'false'}</p>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm p-6 mb-6">
          <h2 className="text-lg font-semibold mb-4">Test Endpoints</h2>
          <div className="flex flex-wrap gap-3">
            <button
              onClick={testProducts}
              disabled={loading}
              className="px-4 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8a5a3d] disabled:opacity-50 transition-colors"
            >
              {loading ? 'Loading...' : 'Test Products API'}
            </button>
            <button
              onClick={testCategories}
              disabled={loading}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
            >
              {loading ? 'Loading...' : 'Test Categories API'}
            </button>
            <button
              onClick={testDirectFetch}
              disabled={loading}
              className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors"
            >
              {loading ? 'Loading...' : 'Test Direct Fetch'}
            </button>
          </div>
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
            <h3 className="text-red-800 font-semibold mb-2">❌ Error</h3>
            <p className="text-red-600 font-mono text-sm">{error}</p>
            <div className="mt-4 text-sm text-red-700">
              <p className="font-semibold">Possible causes:</p>
              <ul className="list-disc list-inside mt-2 space-y-1">
                <li>Laragon/Apache is not running</li>
                <li>CORS blocking the request</li>
                <li>Wrong API URL in .env.local</li>
                <li>PHP error in backend</li>
              </ul>
            </div>
          </div>
        )}

        {result && (
          <div className="bg-green-50 border border-green-200 rounded-xl p-6">
            <h3 className="text-green-800 font-semibold mb-2">✅ Success - {result.endpoint}</h3>
            <pre className="bg-white border rounded-lg p-4 overflow-auto max-h-96 text-sm">
              {JSON.stringify(result.data, null, 2)}
            </pre>
          </div>
        )}
      </div>
    </div>
  );
}
