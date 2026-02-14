'use client';

import { useState, useEffect } from 'react';
import { AlertTriangle, RefreshCw } from 'lucide-react';

interface GeocodeFailureJob {
  job_id: number;
  order_id: number;
  order_number: string;
  delivery_address: string;
  last_error: string;
  attempts: number;
  created_at?: string;
  updated_at?: string;
}

interface ManualCoords {
  lat: number;
  lng: number;
}

export default function GeocodeFailuresPage() {
  const [jobs, setJobs] = useState<GeocodeFailureJob[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedJob, setSelectedJob] = useState<GeocodeFailureJob | null>(null);
  const [manualCoords, setManualCoords] = useState<ManualCoords>({ lat: -7.98, lng: 112.63 }); // Default Malang
  const [isMapReady, setIsMapReady] = useState(false);

  useEffect(() => {
    fetchJobs();
    // Load Leaflet CSS
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(link);
    
    setIsMapReady(true);
    
    return () => {
      document.head.removeChild(link);
    };
  }, []);

  const fetchJobs = async () => {
    setLoading(true);
    try {
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if ((process.env.NEXT_PUBLIC_API_URL || '').includes('ngrok-free.app') || (process.env.NEXT_PUBLIC_API_URL || '').includes('ngrok-free.dev') || (process.env.NEXT_PUBLIC_API_URL || '').includes('.ngrok.io')) {
        headers['ngrok-skip-browser-warning'] = '69420';
      }

      const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/admin/geocode/failed_jobs.php`, { headers });
      const data = await res.json();
      if (data.success) {
        setJobs(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch jobs', error);
    } finally {
      setLoading(false);
    }
  };

  const handleOpenFix = (job: GeocodeFailureJob) => {
    setSelectedJob(job);
    // Default to Malang if no known location
    setManualCoords({ lat: -7.98, lng: 112.63 });
  };

  const handleSaveFix = async () => {
    if (!selectedJob) return;
    try {
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if ((process.env.NEXT_PUBLIC_API_URL || '').includes('ngrok-free.app') || (process.env.NEXT_PUBLIC_API_URL || '').includes('ngrok-free.dev') || (process.env.NEXT_PUBLIC_API_URL || '').includes('.ngrok.io')) {
        headers['ngrok-skip-browser-warning'] = '69420';
      }

      const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/admin/geocode/manual_update.php`, {
        method: 'POST',
        headers,
        body: JSON.stringify({
          order_id: selectedJob.order_id,
          lat: manualCoords.lat,
          lng: manualCoords.lng
        })
      });
      const result = await res.json();
      if (result.success) {
        alert('Updated successfully');
        setSelectedJob(null);
        fetchJobs(); // Refresh list
      } else {
        alert('Failed: ' + result.error);
      }
    } catch (e) {
      alert('Error updating');
    }
  };

  useEffect(() => {
    if (selectedJob && isMapReady && typeof window !== 'undefined') {
      // Dynamically initialize Leaflet map
      import('leaflet').then((L) => {
        const mapElement = document.getElementById('geocode-map');
        if (mapElement && !mapElement.hasChildNodes()) {
          const map = L.map('geocode-map').setView([manualCoords.lat, manualCoords.lng], 13);
          
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
          }).addTo(map);
          
          const marker = L.marker([manualCoords.lat, manualCoords.lng]).addTo(map);
          
          map.on('click', (e: { latlng: { lat: number; lng: number } }) => {
            const { lat, lng } = e.latlng;
            setManualCoords({ lat, lng });
            marker.setLatLng(e.latlng);
          });
          
          // Store map instance for cleanup
          const _el = mapElement as HTMLElement & { __leaflet_map?: unknown; __leaflet_marker?: unknown };
          _el.__leaflet_map = map;
          _el.__leaflet_marker = marker;
        } else if (mapElement && mapElement.hasChildNodes()) {
          // Update existing map
          const map = (mapElement as any).__leaflet_map;
          const marker = (mapElement as any).__leaflet_marker;
          if (map && marker) {
            map.setView([manualCoords.lat, manualCoords.lng]);
            marker.setLatLng([manualCoords.lat, manualCoords.lng]);
          }
        }
      });
    }
    
    return () => {
      const mapElement = document.getElementById('geocode-map');
      if (mapElement && (mapElement as any).__leaflet_map) {
        (mapElement as any).__leaflet_map.remove();
        delete (mapElement as any).__leaflet_map;
        delete (mapElement as any).__leaflet_marker;
        while (mapElement.firstChild) {
          mapElement.removeChild(mapElement.firstChild);
        }
      }
    };
  }, [selectedJob, isMapReady]);

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-6 flex items-center">
        <AlertTriangle className="mr-2 text-red-500" />
        Geocode Failures & Manual Correction
      </h1>

      <button 
        onClick={fetchJobs} 
        className="mb-4 px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 flex items-center"
      >
        <RefreshCw className="mr-2 h-4 w-4" /> Refresh
      </button>

      {loading ? (
        <p>Loading...</p>
      ) : (
        <div className="overflow-x-auto bg-white rounded shadow">
          <table className="min-w-full text-left text-sm">
            <thead className="bg-gray-50 border-b">
              <tr>
                <th className="p-4">Order</th>
                <th className="p-4">Address</th>
                <th className="p-4">Error</th>
                <th className="p-4">Attempts</th>
                <th className="p-4">Action</th>
              </tr>
            </thead>
            <tbody>
              {jobs.length === 0 ? (
                <tr>
                  <td colSpan={5} className="p-6 text-center text-gray-500">
                    No failed jobs found.
                  </td>
                </tr>
              ) : (
                jobs.map((job) => (
                  <tr key={job.job_id} className="border-b hover:bg-gray-50">
                    <td className="p-4 font-medium">
                      <a 
                        href={`/admin/orders/${job.order_id}`} 
                        className="text-blue-600 hover:underline"
                      >
                        {job.order_number}
                      </a>
                    </td>
                    <td className="p-4 max-w-xs truncate" title={job.delivery_address}>
                      {job.delivery_address}
                    </td>
                    <td className="p-4 text-red-600">{job.last_error}</td>
                    <td className="p-4">{job.attempts}</td>
                    <td className="p-4">
                      <button 
                        onClick={() => handleOpenFix(job)}
                        className="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-xs font-semibold"
                      >
                        Fix Position
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      )}

      {/* Manual Fix Modal */}
      {selectedJob && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl overflow-hidden">
            <div className="p-4 border-b flex justify-between items-center bg-gray-50">
              <h3 className="font-bold">Manually Pin Location</h3>
              <button 
                onClick={() => setSelectedJob(null)} 
                className="text-gray-500 hover:text-gray-700"
              >
                ✕
              </button>
            </div>
            <div className="p-4">
              <p className="mb-2 text-sm text-gray-600">
                Address: <strong>{selectedJob.delivery_address}</strong>
              </p>
              <div className="bg-yellow-50 p-2 mb-4 text-xs text-yellow-700 rounded">
                Click on the map to set the correct delivery location.
              </div>
              
              <div id="geocode-map" className="h-64 sm:h-80 w-full mb-4 border rounded relative z-0"></div>

              <div className="flex justify-between items-center">
                <div className="text-xs text-gray-500">
                  Selected: {manualCoords.lat.toFixed(6)}, {manualCoords.lng.toFixed(6)}
                </div>
                <div className="space-x-2">
                  <button 
                    onClick={() => setSelectedJob(null)} 
                    className="px-4 py-2 border rounded hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button 
                    onClick={handleSaveFix} 
                    className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                  >
                    Save Coordinates
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
