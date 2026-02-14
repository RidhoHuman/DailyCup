"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api-client";
import { getErrorMessage } from '@/lib/utils';
import Link from "next/link";

interface GDPRRequest {
  id: string;
  request_type: 'data_export' | 'account_deletion';
  status: 'pending' | 'processing' | 'completed' | 'rejected';
  reason: string | null;
  admin_notes: string | null;
  export_file_path: string | null;
  created_at: string;
  updated_at: string;
  completed_at: string | null;
}

export default function CustomerGDPRPage() {
  const [requests, setRequests] = useState<GDPRRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [showExportModal, setShowExportModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // Form states
  const [exportReason, setExportReason] = useState('');
  const [deleteReason, setDeleteReason] = useState('');
  const [deleteConfirm, setDeleteConfirm] = useState('');

  useEffect(() => {
    fetchRequests();
  }, []);

  const fetchRequests = async () => {
    try {
      setLoading(true);
      const response = await api.get<{success: boolean; requests: GDPRRequest[]}>('/gdpr.php', { requiresAuth: true });
      if (response.success) {
        setRequests(response.requests);
      }
    } catch (error) {
      console.error('Failed to fetch GDPR requests:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleRequestExport = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      setSubmitting(true);
      const response = await api.post<{success: boolean}>('/gdpr.php', {
        request_type: 'data_export',
        reason: exportReason || null
      }, { requiresAuth: true });

      if (response.success) {
        alert('Data export request submitted successfully! We will process your request within 30 days.');
        setShowExportModal(false);
        setExportReason('');
        await fetchRequests();
      }
    } catch (error: unknown) {
      console.error('Failed to request export:', getErrorMessage(error));
      alert(getErrorMessage(error) || 'Failed to submit request');
    } finally {
      setSubmitting(false);
    }
  };

  const handleRequestDeletion = async (e: React.FormEvent) => {
    e.preventDefault();

    if (deleteConfirm !== 'DELETE') {
      alert('Please type DELETE to confirm account deletion');
      return;
    }

    if (!deleteReason.trim()) {
      alert('Please provide a reason for account deletion');
      return;
    }

    try {
      setSubmitting(true);
      const response = await api.post<{success: boolean}>('/gdpr.php', {
        request_type: 'account_deletion',
        reason: deleteReason
      }, { requiresAuth: true });

      if (response.success) {
        alert('Account deletion request submitted successfully! We will process your request within 30 days. You will receive a confirmation email.');
        setShowDeleteModal(false);
        setDeleteReason('');
        setDeleteConfirm('');
        await fetchRequests();
      }
    } catch (error: unknown) {
      console.error('Failed to request deletion:', getErrorMessage(error));
      alert(getErrorMessage(error) || 'Failed to submit request');
    } finally {
      setSubmitting(false);
    }
  };

  const handleCancelRequest = async (requestId: string) => {
    if (!confirm('Cancel this request?')) return;

    try {
      const response = await api.delete<{success: boolean}>(`/gdpr.php?id=${requestId}`, { requiresAuth: true });
      if (response.success) {
        alert('Request cancelled successfully');
        await fetchRequests();
      }
    } catch (error) {
      console.error('Failed to cancel request:', error);
      alert('Failed to cancel request');
    }
  };

  const hasPendingExport = requests.some(r => r.request_type === 'data_export' && ['pending', 'processing'].includes(r.status));
  const hasPendingDeletion = requests.some(r => r.request_type === 'account_deletion' && ['pending', 'processing'].includes(r.status));

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'text-yellow-600 bg-yellow-100';
      case 'processing': return 'text-blue-600 bg-blue-100';
      case 'completed': return 'text-green-600 bg-green-100';
      case 'rejected': return 'text-red-600 bg-red-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <i className="bi bi-arrow-repeat animate-spin text-4xl text-[#a97456] mb-4"></i>
          <p className="text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8 max-w-4xl">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-800 mb-2">Privacy & Data</h1>
        <p className="text-gray-500">Manage your personal data and privacy settings</p>
      </div>

      {/* GDPR Info Card */}
      <div className="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
        <div className="flex items-start gap-4">
          <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
            <i className="bi bi-shield-check text-2xl text-blue-600"></i>
          </div>
          <div>
            <h3 className="font-semibold text-blue-900 mb-2">Your Data Rights</h3>
            <p className="text-blue-800 text-sm leading-relaxed">
              Under GDPR regulations, you have the right to request a copy of your personal data and the right to request deletion of your account. 
              We process all requests within 30 days.
            </p>
          </div>
        </div>
      </div>

      {/* Action Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        {/* Data Export Card */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
            <i className="bi bi-download text-3xl text-blue-600"></i>
          </div>
          <h3 className="text-xl font-bold text-gray-800 mb-2">Export My Data</h3>
          <p className="text-gray-600 mb-4">
            Download a copy of all your personal data stored in our system including orders, reviews, and account information.
          </p>
          <button
            onClick={() => setShowExportModal(true)}
            disabled={hasPendingExport}
            className="w-full px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
          >
            {hasPendingExport ? (
              <>
                <i className="bi bi-clock mr-2"></i>
                Request Pending
              </>
            ) : (
              <>
                <i className="bi bi-download mr-2"></i>
                Request Data Export
              </>
            )}
          </button>
        </div>

        {/* Account Deletion Card */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
            <i className="bi bi-trash text-3xl text-red-600"></i>
          </div>
          <h3 className="text-xl font-bold text-gray-800 mb-2">Delete My Account</h3>
          <p className="text-gray-600 mb-4">
            Permanently delete your account and all associated data. This action cannot be undone.
          </p>
          <button
            onClick={() => setShowDeleteModal(true)}
            disabled={hasPendingDeletion}
            className="w-full px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
          >
            {hasPendingDeletion ? (
              <>
                <i className="bi bi-clock mr-2"></i>
                Request Pending
              </>
            ) : (
              <>
                <i className="bi bi-trash mr-2"></i>
                Request Account Deletion
              </>
            )}
          </button>
        </div>
      </div>

      {/* Request History */}
      {requests.length > 0 && (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="p-6 border-b border-gray-100">
            <h3 className="text-xl font-bold text-gray-800">Request History</h3>
          </div>
          <div className="divide-y divide-gray-100">
            {requests.map((request) => (
              <div key={request.id} className="p-6">
                <div className="flex items-start justify-between mb-3">
                  <div className="flex-1">
                    <div className="flex items-center gap-3 mb-2">
                      <h4 className="font-semibold text-gray-800">
                        {request.request_type === 'data_export' ? (
                          <>
                            <i className="bi bi-download mr-2 text-blue-600"></i>
                            Data Export Request
                          </>
                        ) : (
                          <>
                            <i className="bi bi-trash mr-2 text-red-600"></i>
                            Account Deletion Request
                          </>
                        )}
                      </h4>
                      <span className={`px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(request.status)}`}>
                        {request.status}
                      </span>
                    </div>
                    <p className="text-sm text-gray-500">
                      Submitted on {new Date(request.created_at).toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                      })}
                    </p>
                  </div>
                  {request.status === 'pending' && (
                    <button
                      onClick={() => handleCancelRequest(request.id)}
                      className="text-red-600 hover:text-red-700 text-sm font-medium"
                    >
                      Cancel
                    </button>
                  )}
                </div>

                {request.reason && (
                  <div className="bg-gray-50 rounded-lg p-3 mb-3">
                    <p className="text-sm text-gray-700">
                      <span className="font-medium">Your reason:</span> {request.reason}
                    </p>
                  </div>
                )}

                {request.admin_notes && (
                  <div className="bg-blue-50 rounded-lg p-3 mb-3">
                    <p className="text-sm text-blue-900">
                      <span className="font-medium">Admin response:</span> {request.admin_notes}
                    </p>
                  </div>
                )}

                {request.status === 'completed' && request.export_file_path && (
                  <a
                    href={`${process.env.NEXT_PUBLIC_API_URL?.replace('/api', '')}/${request.export_file_path}`}
                    download
                    className="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-sm font-medium"
                  >
                    <i className="bi bi-download mr-2"></i>
                    Download Your Data
                  </a>
                )}

                {request.completed_at && (
                  <p className="text-xs text-gray-500 mt-2">
                    Completed on {new Date(request.completed_at).toLocaleDateString('id-ID', {
                      day: '2-digit',
                      month: 'long',
                      year: 'numeric'
                    })}
                  </p>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Export Modal */}
      {showExportModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-2xl shadow-xl max-w-lg w-full">
            <div className="p-6 border-b border-gray-100">
              <div className="flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-800">Request Data Export</h2>
                <button
                  onClick={() => setShowExportModal(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <i className="bi bi-x-lg text-xl"></i>
                </button>
              </div>
            </div>
            <form onSubmit={handleRequestExport} className="p-6 space-y-4">
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p className="text-sm text-blue-900">
                  We will prepare a complete copy of your personal data in JSON format. This includes:
                </p>
                <ul className="mt-2 text-sm text-blue-800 space-y-1 ml-4 list-disc">
                  <li>Account information</li>
                  <li>Order history and details</li>
                  <li>Reviews and ratings</li>
                  <li>Addresses and preferences</li>
                  <li>Loyalty points history</li>
                </ul>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Reason (Optional)
                </label>
                <textarea
                  value={exportReason}
                  onChange={(e) => setExportReason(e.target.value)}
                  placeholder="Why do you need a copy of your data?"
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent resize-none"
                  rows={3}
                />
              </div>

              <div className="flex gap-3 pt-4">
                <button
                  type="button"
                  onClick={() => setShowExportModal(false)}
                  className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  className="flex-1 px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                >
                  {submitting ? (
                    <>
                      <i className="bi bi-arrow-repeat animate-spin mr-2"></i>
                      Submitting...
                    </>
                  ) : (
                    <>
                      <i className="bi bi-send mr-2"></i>
                      Submit Request
                    </>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Delete Modal */}
      {showDeleteModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-2xl shadow-xl max-w-lg w-full">
            <div className="p-6 border-b border-gray-100">
              <div className="flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-800">Request Account Deletion</h2>
                <button
                  onClick={() => setShowDeleteModal(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <i className="bi bi-x-lg text-xl"></i>
                </button>
              </div>
            </div>
            <form onSubmit={handleRequestDeletion} className="p-6 space-y-4">
              <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                <div className="flex items-start gap-3">
                  <i className="bi bi-exclamation-triangle text-2xl text-red-600 flex-shrink-0"></i>
                  <div>
                    <p className="font-semibold text-red-900 mb-1">Warning: This action is irreversible!</p>
                    <p className="text-sm text-red-800">
                      Deleting your account will permanently remove:
                    </p>
                    <ul className="mt-2 text-sm text-red-800 space-y-1 ml-4 list-disc">
                      <li>All personal information</li>
                      <li>Order history</li>
                      <li>Reviews and ratings</li>
                      <li>Loyalty points</li>
                      <li>Saved addresses</li>
                    </ul>
                  </div>
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Reason for deletion <span className="text-red-500">*</span>
                </label>
                <textarea
                  value={deleteReason}
                  onChange={(e) => setDeleteReason(e.target.value)}
                  placeholder="Please tell us why you want to delete your account..."
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent resize-none"
                  rows={3}
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Type "DELETE" to confirm <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={deleteConfirm}
                  onChange={(e) => setDeleteConfirm(e.target.value)}
                  placeholder="DELETE"
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  required
                />
              </div>

              <div className="flex gap-3 pt-4">
                <button
                  type="button"
                  onClick={() => setShowDeleteModal(false)}
                  className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={submitting || deleteConfirm !== 'DELETE'}
                  className="flex-1 px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                >
                  {submitting ? (
                    <>
                      <i className="bi bi-arrow-repeat animate-spin mr-2"></i>
                      Submitting...
                    </>
                  ) : (
                    <>
                      <i className="bi bi-trash mr-2"></i>
                      Submit Deletion Request
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
