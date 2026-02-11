"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api-client";

interface GDPRRequest {
  id: string;
  user_id: string;
  user_name: string;
  user_email: string;
  request_type: 'data_export' | 'account_deletion';
  status: 'pending' | 'processing' | 'completed' | 'rejected';
  reason: string | null;
  admin_notes: string | null;
  export_file_path: string | null;
  created_at: string;
  updated_at: string;
  completed_at: string | null;
  processed_by: string | null;
  processed_by_name: string | null;
}

export default function AdminGDPRPage() {
  const [requests, setRequests] = useState<GDPRRequest[]>([]);
  const [selectedRequest, setSelectedRequest] = useState<GDPRRequest | null>(null);
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);
  
  // Filters
  const [statusFilter, setStatusFilter] = useState<'all' | 'pending' | 'processing' | 'completed' | 'rejected'>('all');
  const [typeFilter, setTypeFilter] = useState<'all' | 'data_export' | 'account_deletion'>('all');

  // Modal states
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectNotes, setRejectNotes] = useState('');

  useEffect(() => {
    fetchRequests();
    const interval = setInterval(fetchRequests, 15000); // Refresh every 15s
    return () => clearInterval(interval);
  }, [statusFilter, typeFilter]);

  const fetchRequests = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (statusFilter !== 'all') params.append('status', statusFilter);
      if (typeFilter !== 'all') params.append('type', typeFilter);
      
      const response = await api.get<{success: boolean; requests: GDPRRequest[]}>(`/gdpr.php?${params}`, { requiresAuth: true });
      if (response.success) {
        setRequests(response.requests);
      }
    } catch (error) {
      console.error('Failed to fetch GDPR requests:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleExportData = async (requestId: string) => {
    if (!confirm('Export user data? This will generate a JSON file with all user information.')) return;

    try {
      setProcessing(true);
      const response = await api.post<{success: boolean; file_path: string}>(`/gdpr.php?action=export&request_id=${requestId}`, {}, { requiresAuth: true });
      
      if (response.success) {
        alert('Data exported successfully! File: ' + response.file_path);
        await fetchRequests();
        setSelectedRequest(null);
      }
    } catch (error) {
      console.error('Failed to export data:', error);
      alert('Failed to export data');
    } finally {
      setProcessing(false);
    }
  };

  const handleDeleteAccount = async (requestId: string, userName: string) => {
    if (!confirm(`⚠️ WARNING: Delete account for "${userName}"?\n\nThis action is IRREVERSIBLE and will:\n- Delete user account permanently\n- Remove all user data from the system\n- Cancel all active orders\n\nType "DELETE" to confirm`)) return;

    const confirmation = prompt('Type DELETE to confirm account deletion:');
    if (confirmation !== 'DELETE') {
      alert('Account deletion cancelled');
      return;
    }

    try {
      setProcessing(true);
      const response = await api.put<{success: boolean}>('/gdpr.php', {
        id: requestId,
        action: 'delete_account'
      }, { requiresAuth: true });
      
      if (response.success) {
        alert('Account deleted successfully');
        await fetchRequests();
        setSelectedRequest(null);
      }
    } catch (error) {
      console.error('Failed to delete account:', error);
      alert('Failed to delete account');
    } finally {
      setProcessing(false);
    }
  };

  const handleUpdateStatus = async (requestId: string, status: string, notes?: string) => {
    try {
      setProcessing(true);
      const response = await api.put<{success: boolean}>('/gdpr.php', {
        id: requestId,
        status,
        admin_notes: notes
      }, { requiresAuth: true });
      
      if (response.success) {
        await fetchRequests();
        setSelectedRequest(null);
        setShowRejectModal(false);
        setRejectNotes('');
      }
    } catch (error) {
      console.error('Failed to update status:', error);
      alert('Failed to update status');
    } finally {
      setProcessing(false);
    }
  };

  const stats = {
    total: requests.length,
    pending: requests.filter(r => r.status === 'pending').length,
    processing: requests.filter(r => r.status === 'processing').length,
    completed: requests.filter(r => r.status === 'completed').length,
    data_export: requests.filter(r => r.request_type === 'data_export').length,
    account_deletion: requests.filter(r => r.request_type === 'account_deletion').length
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'text-yellow-600 bg-yellow-100';
      case 'processing': return 'text-blue-600 bg-blue-100';
      case 'completed': return 'text-green-600 bg-green-100';
      case 'rejected': return 'text-red-600 bg-red-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'data_export': return 'text-blue-600 bg-blue-100';
      case 'account_deletion': return 'text-red-600 bg-red-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  if (loading && requests.length === 0) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <i className="bi bi-arrow-repeat animate-spin text-4xl text-[#a97456] mb-4"></i>
          <p className="text-gray-600">Loading GDPR requests...</p>
        </div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800 mb-2">GDPR Management</h1>
        <p className="text-gray-500">Manage data export and account deletion requests</p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Total</p>
              <h3 className="text-2xl font-bold text-gray-800">{stats.total}</h3>
            </div>
            <i className="bi bi-shield-check text-2xl text-gray-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Pending</p>
              <h3 className="text-2xl font-bold text-yellow-600">{stats.pending}</h3>
            </div>
            <i className="bi bi-clock text-2xl text-yellow-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Processing</p>
              <h3 className="text-2xl font-bold text-blue-600">{stats.processing}</h3>
            </div>
            <i className="bi bi-arrow-repeat text-2xl text-blue-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Completed</p>
              <h3 className="text-2xl font-bold text-green-600">{stats.completed}</h3>
            </div>
            <i className="bi bi-check-circle text-2xl text-green-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Data Export</p>
              <h3 className="text-2xl font-bold text-blue-600">{stats.data_export}</h3>
            </div>
            <i className="bi bi-download text-2xl text-blue-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Deletions</p>
              <h3 className="text-2xl font-bold text-red-600">{stats.account_deletion}</h3>
            </div>
            <i className="bi bi-trash text-2xl text-red-600"></i>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
        <div className="flex flex-col md:flex-row gap-4">
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value as any)}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
          >
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="completed">Completed</option>
            <option value="rejected">Rejected</option>
          </select>
          <select
            value={typeFilter}
            onChange={(e) => setTypeFilter(e.target.value as any)}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
          >
            <option value="all">All Types</option>
            <option value="data_export">Data Export</option>
            <option value="account_deletion">Account Deletion</option>
          </select>
        </div>
      </div>

      {/* Requests Table */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {requests.length === 0 ? (
          <div className="p-12 text-center text-gray-500">
            <i className="bi bi-inbox text-6xl mb-4"></i>
            <p className="text-lg">No GDPR requests found</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr className="text-left text-xs font-semibold text-gray-500 uppercase">
                  <th className="px-6 py-4">User</th>
                  <th className="px-6 py-4">Request Type</th>
                  <th className="px-6 py-4">Status</th>
                  <th className="px-6 py-4">Created</th>
                  <th className="px-6 py-4">Processed By</th>
                  <th className="px-6 py-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {requests.map((request) => (
                  <tr key={request.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4">
                      <div className="font-medium text-gray-800">{request.user_name}</div>
                      <div className="text-sm text-gray-500">{request.user_email}</div>
                    </td>
                    <td className="px-6 py-4">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getTypeColor(request.request_type)}`}>
                        {request.request_type === 'data_export' ? (
                          <>
                            <i className="bi bi-download mr-1"></i>
                            Data Export
                          </>
                        ) : (
                          <>
                            <i className="bi bi-trash mr-1"></i>
                            Account Deletion
                          </>
                        )}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(request.status)}`}>
                        {request.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      {new Date(request.created_at).toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                      })}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      {request.processed_by_name || '-'}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <button
                        onClick={() => setSelectedRequest(request)}
                        className="text-[#a97456] hover:text-[#8b5e3c] font-medium text-sm"
                      >
                        View Details
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Request Detail Modal */}
      {selectedRequest && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-gray-100">
              <div className="flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-800">GDPR Request Details</h2>
                <button
                  onClick={() => setSelectedRequest(null)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <i className="bi bi-x-lg text-xl"></i>
                </button>
              </div>
            </div>
            
            <div className="p-6 space-y-6">
              {/* User Info */}
              <div>
                <h3 className="font-semibold text-gray-800 mb-3">User Information</h3>
                <div className="bg-gray-50 rounded-lg p-4 space-y-2">
                  <div className="flex justify-between">
                    <span className="text-gray-600">Name:</span>
                    <span className="font-medium text-gray-800">{selectedRequest.user_name}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Email:</span>
                    <span className="font-medium text-gray-800">{selectedRequest.user_email}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">User ID:</span>
                    <span className="font-medium text-gray-800">{selectedRequest.user_id}</span>
                  </div>
                </div>
              </div>

              {/* Request Info */}
              <div>
                <h3 className="font-semibold text-gray-800 mb-3">Request Information</h3>
                <div className="bg-gray-50 rounded-lg p-4 space-y-2">
                  <div className="flex justify-between">
                    <span className="text-gray-600">Type:</span>
                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-medium ${getTypeColor(selectedRequest.request_type)}`}>
                      {selectedRequest.request_type.replace('_', ' ')}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Status:</span>
                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(selectedRequest.status)}`}>
                      {selectedRequest.status}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Created:</span>
                    <span className="font-medium text-gray-800">
                      {new Date(selectedRequest.created_at).toLocaleString('id-ID')}
                    </span>
                  </div>
                  {selectedRequest.completed_at && (
                    <div className="flex justify-between">
                      <span className="text-gray-600">Completed:</span>
                      <span className="font-medium text-gray-800">
                        {new Date(selectedRequest.completed_at).toLocaleString('id-ID')}
                      </span>
                    </div>
                  )}
                </div>
              </div>

              {/* Reason */}
              {selectedRequest.reason && (
                <div>
                  <h3 className="font-semibold text-gray-800 mb-3">User Reason</h3>
                  <div className="bg-gray-50 rounded-lg p-4">
                    <p className="text-gray-700">{selectedRequest.reason}</p>
                  </div>
                </div>
              )}

              {/* Admin Notes */}
              {selectedRequest.admin_notes && (
                <div>
                  <h3 className="font-semibold text-gray-800 mb-3">Admin Notes</h3>
                  <div className="bg-gray-50 rounded-lg p-4">
                    <p className="text-gray-700">{selectedRequest.admin_notes}</p>
                  </div>
                </div>
              )}

              {/* Export File */}
              {selectedRequest.export_file_path && (
                <div>
                  <h3 className="font-semibold text-gray-800 mb-3">Export File</h3>
                  <div className="bg-gray-50 rounded-lg p-4">
                    <a 
                      href={`${process.env.NEXT_PUBLIC_API_URL?.replace('/api', '')}/${selectedRequest.export_file_path}`}
                      download
                      className="text-[#a97456] hover:underline flex items-center gap-2"
                    >
                      <i className="bi bi-download"></i>
                      Download User Data (JSON)
                    </a>
                  </div>
                </div>
              )}

              {/* Actions */}
              {selectedRequest.status === 'pending' && (
                <div className="pt-4 border-t border-gray-200">
                  <h3 className="font-semibold text-gray-800 mb-3">Actions</h3>
                  <div className="flex gap-3">
                    {selectedRequest.request_type === 'data_export' ? (
                      <button
                        onClick={() => handleExportData(selectedRequest.id)}
                        disabled={processing}
                        className="flex-1 px-4 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                      >
                        <i className="bi bi-download mr-2"></i>
                        Export User Data
                      </button>
                    ) : (
                      <button
                        onClick={() => handleDeleteAccount(selectedRequest.id, selectedRequest.user_name)}
                        disabled={processing}
                        className="flex-1 px-4 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                      >
                        <i className="bi bi-trash mr-2"></i>
                        Delete Account
                      </button>
                    )}
                    <button
                      onClick={() => setShowRejectModal(true)}
                      disabled={processing}
                      className="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                    >
                      <i className="bi bi-x-circle mr-2"></i>
                      Reject Request
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Reject Modal */}
      {showRejectModal && selectedRequest && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-[60]">
          <div className="bg-white rounded-2xl shadow-xl max-w-md w-full">
            <div className="p-6 border-b border-gray-100">
              <h3 className="text-xl font-bold text-gray-800">Reject Request</h3>
            </div>
            <div className="p-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Reason for rejection (optional)
              </label>
              <textarea
                value={rejectNotes}
                onChange={(e) => setRejectNotes(e.target.value)}
                placeholder="Explain why this request is being rejected..."
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent resize-none"
                rows={4}
              />
            </div>
            <div className="p-6 border-t border-gray-100 flex gap-3">
              <button
                onClick={() => {
                  setShowRejectModal(false);
                  setRejectNotes('');
                }}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
              >
                Cancel
              </button>
              <button
                onClick={() => handleUpdateStatus(selectedRequest.id, 'rejected', rejectNotes)}
                disabled={processing}
                className="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
              >
                {processing ? 'Processing...' : 'Reject Request'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
