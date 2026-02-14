'use client';

import { useState, useEffect, useCallback, useRef } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { kurirApi } from '@/lib/kurir-api';
import toast from 'react-hot-toast';
import Link from 'next/link';
import { getErrorMessage } from '@/lib/utils';

interface OrderDetail {
  id: number;
  orderNumber: string;
  status: string;
  paymentMethod: string;
  paymentStatus: string;
  deliveryMethod: string;
  deliveryAddress: string;
  deliveryDistance: number | null;
  customerNotes: string | null;
  totalAmount: number;
  finalAmount: number;
  customer: { name: string; phone: string; email: string; };
  items: Array<{
    id: number; name: string; price: number; quantity: number;
    subtotal: number; variant: Record<string, string>; image: string | null;
  }>;
  timeline: Array<{ status: string; time: string | null; completed: boolean; }>;
  nextAction: { next: string; label: string; } | null;
  createdAt: string;
  assignedAt: string | null;
  pickupTime: string | null;
  completedAt: string | null;
  isCOD: boolean;
  codAmountLimit: number | null;
  kurirDeparturePhoto?: string | null;
  kurirArrivalPhoto?: string | null;
}

export default function KurirOrderDetailPage() {
  const params = useParams();
  const router = useRouter();
  const orderId = params.id as string;

  const [order, setOrder] = useState<OrderDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);

  // Photo upload states
  const [showPhotoModal, setShowPhotoModal] = useState(false);
  const [photoType, setPhotoType] = useState<'departure' | 'arrival'>('departure');
  const [selectedPhoto, setSelectedPhoto] = useState<File | null>(null);
  const [photoPreview, setPhotoPreview] = useState<string | null>(null);
  const [uploading, setUploading] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const fetchOrder = useCallback(async () => {
    try {
      const res = await kurirApi.getOrderDetail(orderId);
      if (res.success && res.data) {
        // cast via unknown to acknowledge runtime shape mismatch and avoid unsafe direct cast
        setOrder(res.data as unknown as OrderDetail);
      }
    } catch (err: unknown) {
      toast.error(getErrorMessage(err) || 'Gagal memuat detail pesanan');
    } finally {
      setLoading(false);
    }
  }, [orderId]);

  useEffect(() => { fetchOrder(); }, [fetchOrder]);

  // Determine if next action requires photo
  const requiresPhoto = (nextStatus: string) => {
    return nextStatus === 'delivering' || nextStatus === 'completed';
  };

  const handleActionButton = () => {
    if (!order?.nextAction) return;
    const nextStatus = order.nextAction.next;

    if (requiresPhoto(nextStatus)) {
      setPhotoType(nextStatus === 'delivering' ? 'departure' : 'arrival');
      setSelectedPhoto(null);
      setPhotoPreview(null);
      setShowPhotoModal(true);
    } else {
      handleUpdateStatus();
    }
  };

  const handleUpdateStatus = async () => {
    if (!order?.nextAction) return;
    setUpdating(true);
    try {
      const res = await kurirApi.updateOrderStatus(order.orderNumber, order.nextAction.next);
      if (res.success) {
        // res.data may be undefined or have a different shape; prefer known fallbacks
        const payload = res.data as unknown as { newStatus?: string; status?: string } | undefined;
        const newStatus = payload?.newStatus ?? payload?.status ?? order.nextAction.next;
        toast.success(`Status diperbarui: ${newStatus}`);
        fetchOrder();
      }
    } catch (err: unknown) {
      toast.error(getErrorMessage(err) || 'Gagal memperbarui status');
    } finally {
      setUpdating(false);
    }
  };

  const handlePhotoSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      toast.error('Ukuran foto maksimal 5MB');
      return;
    }

    // Validate file type
    if (!['image/jpeg', 'image/jpg', 'image/png', 'image/webp'].includes(file.type)) {
      toast.error('Format foto harus JPEG, PNG, atau WebP');
      return;
    }

    setSelectedPhoto(file);
    const reader = new FileReader();
    reader.onload = (ev) => setPhotoPreview(ev.target?.result as string);
    reader.readAsDataURL(file);
  };

  const handlePhotoUpload = async () => {
    if (!selectedPhoto || !order) return;

    setUploading(true);
    try {
      // Try to get GPS coordinates
      let coords: { latitude: number; longitude: number } | undefined;
      try {
        const pos = await new Promise<GeolocationPosition>((resolve, reject) => {
          navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true, timeout: 5000, maximumAge: 0,
          });
        });
        coords = { latitude: pos.coords.latitude, longitude: pos.coords.longitude };
      } catch {
        // GPS not available, continue without it
      }

      const res = await kurirApi.uploadDeliveryPhoto(
        order.orderNumber,
        photoType,
        selectedPhoto,
        coords
      );

      if (res.success) {
        toast.success(res.message);
        setShowPhotoModal(false);
        setSelectedPhoto(null);
        setPhotoPreview(null);

        if (res.data.newStatus === 'completed') {
          // Delay then redirect
          setTimeout(() => router.push('/kurir'), 1500);
        } else {
          fetchOrder();
        }
      }
    } catch (err: unknown) {
      toast.error(getErrorMessage(err) || 'Gagal upload foto');
    } finally {
      setUploading(false);
    }
  };

  const callCustomer = () => {
    if (order?.customer.phone) window.open(`tel:${order.customer.phone}`);
  };
  const waCustomer = () => {
    if (order?.customer.phone) {
      const phone = order.customer.phone.replace(/^0/, '62');
      window.open(`https://wa.me/${phone}?text=Halo, saya kurir DailyCup untuk pesanan %23${order.orderNumber}`);
    }
  };
  const openMaps = () => {
    if (order?.deliveryAddress) {
      window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(order.deliveryAddress)}`);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-60">
        <div className="w-8 h-8 border-3 border-amber-200 border-t-amber-600 rounded-full animate-spin"></div>
      </div>
    );
  }

  if (!order) {
    return (
      <div className="text-center py-12">
        <i className="bi bi-exclamation-circle text-4xl text-gray-300 mb-3 block"></i>
        <p className="text-gray-500">Pesanan tidak ditemukan</p>
        <Link href="/kurir" className="text-amber-600 text-sm mt-2 inline-block">← Kembali</Link>
      </div>
    );
  }

  const actionColors: Record<string, string> = {
    processing: 'bg-yellow-500 hover:bg-yellow-600',
    ready: 'bg-purple-500 hover:bg-purple-600',
    delivering: 'bg-blue-500 hover:bg-blue-600',
    completed: 'bg-green-500 hover:bg-green-600',
  };

  return (
    <div className="space-y-4 pb-4">
      {/* Back */}
      <button onClick={() => router.back()} className="text-gray-500 hover:text-amber-600 text-sm flex items-center gap-1">
        <i className="bi bi-arrow-left"></i> Kembali
      </button>

      {/* Order Header */}
      <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        <div className="flex items-center justify-between mb-2">
          <h1 className="text-lg font-bold text-gray-800 dark:text-white">#{order.orderNumber.split('-').pop()}</h1>
          <span className="text-xs text-gray-400">{new Date(order.createdAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}</span>
        </div>
        <div className="flex items-center gap-2 flex-wrap">
          <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
            order.status === 'delivering' ? 'bg-blue-100 text-blue-700' :
            order.status === 'completed' ? 'bg-green-100 text-green-700' :
            order.status === 'cancelled' ? 'bg-red-100 text-red-700' :
            'bg-amber-100 text-amber-700'
          }`}>{order.status}</span>
          {order.isCOD && <span className="text-xs px-2 py-0.5 rounded-full bg-red-50 text-red-600 font-medium">COD</span>}
          <span className="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{order.paymentStatus}</span>
        </div>
      </div>

      {/* Customer */}
      <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        <h2 className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Customer</h2>
        <div className="flex items-center justify-between">
          <div>
            <p className="font-semibold text-gray-800 dark:text-gray-100">{order.customer.name}</p>
            <p className="text-sm text-gray-500">{order.customer.phone}</p>
          </div>
          <div className="flex gap-2">
            <button onClick={callCustomer} className="w-9 h-9 rounded-full bg-green-50 text-green-600 flex items-center justify-center hover:bg-green-100 transition-colors">
              <i className="bi bi-telephone-fill text-sm"></i>
            </button>
            <button onClick={waCustomer} className="w-9 h-9 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-100 transition-colors">
              <i className="bi bi-whatsapp text-sm"></i>
            </button>
          </div>
        </div>
      </div>

      {/* Delivery Address */}
      <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        <h2 className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Alamat Pengiriman</h2>
        <p className="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{order.deliveryAddress}</p>
        {order.deliveryDistance && (
          <p className="text-xs text-gray-400 mt-1">Jarak: {order.deliveryDistance} km</p>
        )}
        {order.customerNotes && (
          <div className="mt-2 p-2 bg-amber-50 dark:bg-amber-900/10 rounded-lg">
            <p className="text-xs text-amber-700 dark:text-amber-400"><i className="bi bi-chat-text mr-1"></i> {order.customerNotes}</p>
          </div>
        )}
        <button onClick={openMaps}
          className="mt-3 w-full py-2.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl text-sm font-medium flex items-center justify-center gap-2 hover:bg-blue-100 transition-colors">
          <i className="bi bi-map"></i> Buka Google Maps
        </button>
      </div>

      {/* Items */}
      <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        <h2 className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Item Pesanan</h2>
        <div className="space-y-3">
          {order.items.map((item, idx) => (
            <div key={idx} className="flex items-center gap-3">
              <div className="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden">
                {item.image ? (
                  <img src={item.image.startsWith('http') || item.image.startsWith('/') ? item.image : `http://localhost/DailyCup/webapp/backend/uploads/products/${item.image}`}
                    alt={item.name} className="w-full h-full object-cover"
                    onError={(e) => { (e.target as HTMLImageElement).style.display = 'none'; }} />
                ) : (
                  <i className="bi bi-cup-hot text-gray-400"></i>
                )}
              </div>
              <div className="flex-1 min-w-0">
                <p className="font-medium text-sm text-gray-800 dark:text-gray-100 truncate">{item.name}</p>
                <p className="text-xs text-gray-400">
                  {Object.values(item.variant).filter(Boolean).join(', ') || 'Standard'} • x{item.quantity}
                </p>
              </div>
              <p className="text-sm font-semibold text-gray-700 dark:text-gray-200 flex-shrink-0">
                Rp {item.subtotal.toLocaleString('id-ID')}
              </p>
            </div>
          ))}
        </div>
        <div className="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-between">
          <span className="font-bold text-gray-800 dark:text-gray-100">Total</span>
          <span className="font-bold text-amber-700 dark:text-amber-400">Rp {order.finalAmount.toLocaleString('id-ID')}</span>
        </div>
        {order.isCOD && (
          <div className="mt-2 p-2 bg-red-50 dark:bg-red-900/10 rounded-lg">
            <p className="text-xs text-red-600 dark:text-red-400 font-medium">
              <i className="bi bi-cash-coin mr-1"></i> Tagih pembayaran COD: Rp {order.finalAmount.toLocaleString('id-ID')}
            </p>
          </div>
        )}
      </div>

      {/* Timeline */}
      <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
        <h2 className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Timeline</h2>
        <div className="space-y-3">
          {order.timeline.map((step, idx) => (
            <div key={idx} className="flex items-center gap-3">
              <div className={`w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-xs ${
                step.completed ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-400'
              }`}>
                {step.completed ? <i className="bi bi-check"></i> : <i className="bi bi-circle"></i>}
              </div>
              <div className="flex-1">
                <p className={`text-sm font-medium ${step.completed ? 'text-gray-800 dark:text-gray-100' : 'text-gray-400'}`}>{step.status}</p>
                {step.time && <p className="text-xs text-gray-400">{new Date(step.time).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}</p>}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Delivery Photos (if uploaded) */}
      {(order.kurirDeparturePhoto || order.kurirArrivalPhoto) && (
        <div className="bg-white dark:bg-[#2a2a2a] rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
          <h2 className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
            <i className="bi bi-camera mr-1"></i> Bukti Pengiriman
          </h2>
          <div className="grid grid-cols-2 gap-3">
            {order.kurirDeparturePhoto && (
              <div>
                <p className="text-xs text-gray-500 mb-1.5 font-medium">Foto Keberangkatan</p>
                <div className="aspect-square rounded-xl overflow-hidden bg-gray-100 border border-gray-200">
                  <img 
                    src={order.kurirDeparturePhoto.startsWith('http') ? order.kurirDeparturePhoto : `http://localhost/DailyCup/webapp/backend/${order.kurirDeparturePhoto}`} 
                    alt="Bukti keberangkatan"
                    className="w-full h-full object-cover" 
                  />
                </div>
              </div>
            )}
            {order.kurirArrivalPhoto && (
              <div>
                <p className="text-xs text-gray-500 mb-1.5 font-medium">Foto Sampai Tujuan</p>
                <div className="aspect-square rounded-xl overflow-hidden bg-gray-100 border border-gray-200">
                  <img 
                    src={order.kurirArrivalPhoto.startsWith('http') ? order.kurirArrivalPhoto : `http://localhost/DailyCup/webapp/backend/${order.kurirArrivalPhoto}`}
                    alt="Bukti sampai tujuan" 
                    className="w-full h-full object-cover" 
                  />
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Action Button */}
      {order.nextAction && (
        <div className="sticky bottom-16 pt-2">
          <button onClick={handleActionButton} disabled={updating}
            className={`w-full py-4 text-white font-bold rounded-2xl transition-colors flex items-center justify-center gap-2 text-base shadow-lg ${
              actionColors[order.nextAction.next] || 'bg-amber-600 hover:bg-amber-700'
            } ${updating ? 'opacity-60' : ''}`}>
            {updating ? (
              <><div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div> Memperbarui...</>
            ) : (
              <>
                <i className={`bi ${requiresPhoto(order.nextAction.next) ? 'bi-camera' : 'bi-arrow-right-circle'}`}></i>
                {requiresPhoto(order.nextAction.next) 
                  ? (order.nextAction.next === 'delivering' ? 'Upload Foto & Berangkat' : 'Upload Foto & Selesaikan')
                  : order.nextAction.label
                }
              </>
            )}
          </button>
        </div>
      )}

      {/* Photo Upload Modal */}
      {showPhotoModal && (
        <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm"
          onClick={(e) => e.target === e.currentTarget && !uploading && setShowPhotoModal(false)}>
          <div className="bg-white dark:bg-[#2a2a2a] w-full sm:w-[440px] sm:rounded-2xl rounded-t-3xl p-6 max-h-[90vh] overflow-y-auto animate-slide-up">
            {/* Modal Header */}
            <div className="flex items-center justify-between mb-4">
              <div>
                <h3 className="text-lg font-bold text-gray-800 dark:text-white">
                  {photoType === 'departure' ? '📸 Foto Keberangkatan' : '📸 Foto Sampai Tujuan'}
                </h3>
                <p className="text-xs text-gray-500 mt-0.5">
                  {photoType === 'departure' 
                    ? 'Ambil foto pesanan sebelum berangkat mengantar' 
                    : 'Ambil foto pesanan yang sudah diterima customer'}
                </p>
              </div>
              {!uploading && (
                <button onClick={() => setShowPhotoModal(false)}
                  className="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-colors">
                  <i className="bi bi-x-lg text-sm"></i>
                </button>
              )}
            </div>

            {/* Photo Preview / Upload Area */}
            <div className="mb-4">
              {photoPreview ? (
                <div className="relative">
                  <img src={photoPreview} alt="Preview" className="w-full aspect-[4/3] object-cover rounded-2xl border-2 border-amber-200" />
                  {!uploading && (
                    <button onClick={() => { setSelectedPhoto(null); setPhotoPreview(null); }}
                      className="absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-red-600 transition-colors">
                      <i className="bi bi-trash text-sm"></i>
                    </button>
                  )}
                </div>
              ) : (
                <button onClick={() => fileInputRef.current?.click()}
                  className="w-full aspect-[4/3] border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl flex flex-col items-center justify-center gap-3 hover:border-amber-400 hover:bg-amber-50/50 dark:hover:bg-amber-900/10 transition-colors">
                  <div className="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                    <i className="bi bi-camera text-3xl text-amber-600"></i>
                  </div>
                  <div className="text-center">
                    <p className="font-medium text-gray-700 dark:text-gray-300">Ambil Foto</p>
                    <p className="text-xs text-gray-400 mt-0.5">Ketuk untuk membuka kamera</p>
                  </div>
                </button>
              )}
              <input ref={fileInputRef} type="file" accept="image/*" capture="environment"
                className="hidden" onChange={handlePhotoSelect} />
            </div>

            {/* Info Box */}
            <div className="bg-amber-50 dark:bg-amber-900/10 rounded-xl p-3 mb-4">
              <div className="flex gap-2">
                <i className="bi bi-info-circle text-amber-600 flex-shrink-0 mt-0.5"></i>
                <div className="text-xs text-amber-700 dark:text-amber-400 space-y-1">
                  {photoType === 'departure' ? (
                    <>
                      <p className="font-medium">Tips Foto Keberangkatan:</p>
                      <ul className="list-disc list-inside space-y-0.5 text-amber-600">
                        <li>Pastikan pesanan terlihat jelas di foto</li>
                        <li>Foto menunjukkan pesanan siap diantar</li>
                        <li>Hindari foto buram atau gelap</li>
                      </ul>
                    </>
                  ) : (
                    <>
                      <p className="font-medium">Tips Foto Konfirmasi:</p>
                      <ul className="list-disc list-inside space-y-0.5 text-amber-600">
                        <li>Foto pesanan di tangan customer / di depan pintu</li>
                        <li>Pastikan alamat terlihat jika memungkinkan</li>
                        <li>Foto yang jelas sebagai bukti pengiriman</li>
                      </ul>
                    </>
                  )}
                </div>
              </div>
            </div>

            {/* Actions */}
            <div className="flex gap-3">
              {!uploading && (
                <button onClick={() => setShowPhotoModal(false)}
                  className="flex-1 py-3 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                  Batal
                </button>
              )}
              <button onClick={handlePhotoUpload} disabled={!selectedPhoto || uploading}
                className={`flex-1 py-3 rounded-xl font-medium text-white flex items-center justify-center gap-2 transition-colors ${
                  photoType === 'departure' ? 'bg-blue-500 hover:bg-blue-600' : 'bg-green-500 hover:bg-green-600'
                } ${(!selectedPhoto || uploading) ? 'opacity-50 cursor-not-allowed' : ''}`}>
                {uploading ? (
                  <><div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div> Mengunggah...</>
                ) : (
                  <>
                    <i className="bi bi-cloud-upload"></i>
                    {photoType === 'departure' ? 'Upload & Berangkat' : 'Upload & Selesaikan'}
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
