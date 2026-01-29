'use client';

import { useState, useEffect } from 'react';
import { Bell, BellOff, Mail, Clock, ShoppingBag, CreditCard, Gift, MessageSquare, Shield } from 'lucide-react';
import { useAuthStore } from '@/lib/stores/auth-store';
import api from '@/lib/api-client';
import toast from 'react-hot-toast';
import pushManager from '@/lib/pushManager';

interface NotificationPreferences {
    id?: number;
    user_id?: number;
    push_enabled: boolean;
    email_enabled: boolean;
    order_updates: boolean;
    payment_updates: boolean;
    promotions: boolean;
    new_products: boolean;
    reviews: boolean;
    admin_messages: boolean;
    quiet_hours_enabled: boolean;
    quiet_hours_start: string;
    quiet_hours_end: string;
}

interface PreferencesResponse {
    data: {
        success: boolean;
        preferences: NotificationPreferences;
    };
}

export default function NotificationSettings() {
    const { user, token } = useAuthStore();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [preferences, setPreferences] = useState<NotificationPreferences>({
        push_enabled: true,
        email_enabled: true,
        order_updates: true,
        payment_updates: true,
        promotions: true,
        new_products: false,
        reviews: true,
        admin_messages: true,
        quiet_hours_enabled: false,
        quiet_hours_start: '22:00',
        quiet_hours_end: '08:00',
    });
    const [pushSubscribed, setPushSubscribed] = useState(false);
    const [pushSupported, setPushSupported] = useState(false);

    useEffect(() => {
        if (user && token) {
            fetchPreferences();
            checkPushStatus();
        }
    }, [user, token]);

    const fetchPreferences = async () => {
        try {
            setLoading(true);
            const response = await api.get('/notifications/preferences.php') as PreferencesResponse;

            if (response.data.success) {
                setPreferences(response.data.preferences);
            }
        } catch (error) {
            console.error('Failed to load preferences:', error);
            toast.error('Gagal memuat pengaturan notifikasi');
        } finally {
            setLoading(false);
        }
    };

    const checkPushStatus = async () => {
        const supported = pushManager.isSupported();
        setPushSupported(supported);

        if (supported) {
            const subscribed = await pushManager.isSubscribed();
            setPushSubscribed(subscribed);
        }
    };

    const updatePreference = async (field: keyof NotificationPreferences, value: boolean | string) => {
        try {
            setSaving(true);

            const updatedPreferences = {
                ...preferences,
                [field]: value,
            };

            setPreferences(updatedPreferences);

            await api.put('/notifications/preferences.php', { [field]: value });

            toast.success('Pengaturan disimpan');
        } catch (error) {
            console.error('Failed to update preference:', error);
            toast.error('Gagal menyimpan pengaturan');
            // Revert on error
            fetchPreferences();
        } finally {
            setSaving(false);
        }
    };

    const handlePushToggle = async () => {
        if (!pushSupported) {
            toast.error('Browser Anda tidak mendukung push notifications');
            return;
        }

        try {
            if (pushSubscribed) {
                // Unsubscribe
                const subscription = await pushManager.getSubscription();
                if (subscription) {
                    // Send endpoint as query parameter for DELETE (safer / compatible with some servers)
                    const url = `/notifications/push_subscribe.php?endpoint=${encodeURIComponent(subscription.endpoint)}`;
                    await api.delete(url);
                    await pushManager.unsubscribe();
                    setPushSubscribed(false);
                    toast.success('Push notifications dinonaktifkan');
                }
            } else {
                // Subscribe
                const registration = await navigator.serviceWorker.ready;
                await pushManager.initialize(registration);

                const subscriptionData = await pushManager.subscribe();

                if (subscriptionData) {
                    // Convert to plain object payload to satisfy API client typing
                    const payload = {
                        endpoint: subscriptionData.endpoint,
                        keys: subscriptionData.keys,
                    };

                    await api.post('/notifications/push_subscribe.php', payload);
                    setPushSubscribed(true);
                    toast.success('Push notifications diaktifkan!');

                    // Show test notification
                    setTimeout(() => {
                        pushManager.showTestNotification();
                    }, 1000);
                }
            }

            await updatePreference('push_enabled', !pushSubscribed);
        } catch (error: any) {
            console.error('Push toggle error:', error);
            toast.error(error.message || 'Gagal mengubah pengaturan push');
        }
    };

    const testNotification = async () => {
        try {
            if (!pushSubscribed) {
                toast.error('Aktifkan push notifications terlebih dahulu');
                return;
            }

            await pushManager.showTestNotification();
            toast.success('Test notification dikirim!');
        } catch (error) {
            console.error('Test notification error:', error);
            toast.error('Gagal mengirim test notification');
        }
    };

    if (!user) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <div className="text-center">
                    <Bell className="w-16 h-16 mx-auto text-gray-400 mb-4" />
                    <h2 className="text-2xl font-bold text-gray-900 mb-2">Login Required</h2>
                    <p className="text-gray-600">Please login to manage notification settings</p>
                </div>
            </div>
        );
    }

    if (loading) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500 mx-auto"></div>
                    <p className="mt-4 text-gray-600">Loading preferences...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-4xl mx-auto px-4">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 mb-2">Notification Settings</h1>
                    <p className="text-gray-600">
                        Kelola bagaimana Anda ingin menerima notifikasi dari DailyCup
                    </p>
                </div>

                <div className="space-y-6">
                    {/* Push Notifications Section */}
                    <div className="bg-white rounded-lg shadow-sm p-6">
                        <div className="flex items-start justify-between mb-6">
                            <div className="flex items-center space-x-3">
                                <div className="p-2 bg-orange-100 rounded-lg">
                                    <Bell className="w-6 h-6 text-orange-600" />
                                </div>
                                <div>
                                    <h2 className="text-lg font-semibold text-gray-900">Push Notifications</h2>
                                    <p className="text-sm text-gray-600">
                                        Terima notifikasi langsung di browser Anda
                                    </p>
                                </div>
                            </div>
                            <button
                                onClick={handlePushToggle}
                                disabled={!pushSupported || saving}
                                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${pushSubscribed && preferences.push_enabled
                                        ? 'bg-orange-600'
                                        : 'bg-gray-300'
                                    } ${!pushSupported ? 'opacity-50 cursor-not-allowed' : ''}`}
                            >
                                <span
                                    className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${pushSubscribed && preferences.push_enabled ? 'translate-x-6' : 'translate-x-1'
                                        }`}
                                />
                            </button>
                        </div>

                        {pushSupported && pushSubscribed && (
                            <button
                                onClick={testNotification}
                                className="w-full px-4 py-2 bg-orange-50 text-orange-600 rounded-lg hover:bg-orange-100 transition-colors"
                            >
                                🔔 Test Push Notification
                            </button>
                        )}

                        {!pushSupported && (
                            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <p className="text-sm text-yellow-800">
                                    Browser Anda tidak mendukung push notifications. Gunakan Chrome, Firefox, atau Edge versi terbaru.
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Email Notifications */}
                    <div className="bg-white rounded-lg shadow-sm p-6">
                        <div className="flex items-start justify-between">
                            <div className="flex items-center space-x-3">
                                <div className="p-2 bg-blue-100 rounded-lg">
                                    <Mail className="w-6 h-6 text-blue-600" />
                                </div>
                                <div>
                                    <h2 className="text-lg font-semibold text-gray-900">Email Notifications</h2>
                                    <p className="text-sm text-gray-600">
                                        Terima notifikasi melalui email
                                    </p>
                                </div>
                            </div>
                            <button
                                onClick={() => updatePreference('email_enabled', !preferences.email_enabled)}
                                disabled={saving}
                                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${preferences.email_enabled ? 'bg-blue-600' : 'bg-gray-300'
                                    }`}
                            >
                                <span
                                    className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${preferences.email_enabled ? 'translate-x-6' : 'translate-x-1'
                                        }`}
                                />
                            </button>
                        </div>
                    </div>

                    {/* Notification Types */}
                    <div className="bg-white rounded-lg shadow-sm p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Jenis Notifikasi</h2>
                        <p className="text-sm text-gray-600 mb-6">
                            Pilih jenis notifikasi yang ingin Anda terima
                        </p>

                        <div className="space-y-4">
                            <NotificationToggle
                                icon={ShoppingBag}
                                label="Order Updates"
                                description="Status pesanan, konfirmasi, dan pengiriman"
                                enabled={preferences.order_updates}
                                onChange={(value) => updatePreference('order_updates', value)}
                                disabled={saving}
                                color="green"
                            />

                            <NotificationToggle
                                icon={CreditCard}
                                label="Payment Updates"
                                description="Konfirmasi pembayaran dan invoice"
                                enabled={preferences.payment_updates}
                                onChange={(value) => updatePreference('payment_updates', value)}
                                disabled={saving}
                                color="purple"
                            />

                            <NotificationToggle
                                icon={Gift}
                                label="Promotions & Offers"
                                description="Promo, diskon, dan penawaran khusus"
                                enabled={preferences.promotions}
                                onChange={(value) => updatePreference('promotions', value)}
                                disabled={saving}
                                color="pink"
                            />

                            <NotificationToggle
                                icon={Bell}
                                label="New Products"
                                description="Produk dan menu baru"
                                enabled={preferences.new_products}
                                onChange={(value) => updatePreference('new_products', value)}
                                disabled={saving}
                                color="indigo"
                            />

                            <NotificationToggle
                                icon={MessageSquare}
                                label="Reviews & Feedback"
                                description="Balasan pada review Anda"
                                enabled={preferences.reviews}
                                onChange={(value) => updatePreference('reviews', value)}
                                disabled={saving}
                                color="blue"
                            />

                            <NotificationToggle
                                icon={Shield}
                                label="Admin Messages"
                                description="Pesan penting dari admin"
                                enabled={preferences.admin_messages}
                                onChange={(value) => updatePreference('admin_messages', value)}
                                disabled={saving}
                                color="red"
                            />
                        </div>
                    </div>

                    {/* Quiet Hours */}
                    <div className="bg-white rounded-lg shadow-sm p-6">
                        <div className="flex items-start justify-between mb-6">
                            <div className="flex items-center space-x-3">
                                <div className="p-2 bg-indigo-100 rounded-lg">
                                    <Clock className="w-6 h-6 text-indigo-600" />
                                </div>
                                <div>
                                    <h2 className="text-lg font-semibold text-gray-900">Quiet Hours</h2>
                                    <p className="text-sm text-gray-600">
                                        Nonaktifkan notifikasi pada jam tertentu
                                    </p>
                                </div>
                            </div>
                            <button
                                onClick={() => updatePreference('quiet_hours_enabled', !preferences.quiet_hours_enabled)}
                                disabled={saving}
                                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${preferences.quiet_hours_enabled ? 'bg-indigo-600' : 'bg-gray-300'
                                    }`}
                            >
                                <span
                                    className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${preferences.quiet_hours_enabled ? 'translate-x-6' : 'translate-x-1'
                                        }`}
                                />
                            </button>
                        </div>

                        {preferences.quiet_hours_enabled && (
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Mulai
                                    </label>
                                    <input
                                        type="time"
                                        value={preferences.quiet_hours_start}
                                        onChange={(e) => updatePreference('quiet_hours_start', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Selesai
                                    </label>
                                    <input
                                        type="time"
                                        value={preferences.quiet_hours_end}
                                        onChange={(e) => updatePreference('quiet_hours_end', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Save indicator */}
                {saving && (
                    <div className="fixed bottom-4 right-4 bg-white rounded-lg shadow-lg p-4 flex items-center space-x-3">
                        <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-orange-500"></div>
                        <span className="text-gray-700">Menyimpan...</span>
                    </div>
                )}
            </div>
        </div>
    );
}

interface NotificationToggleProps {
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    description: string;
    enabled: boolean;
    onChange: (value: boolean) => void;
    disabled?: boolean;
    color: string;
}

function NotificationToggle({
    icon: Icon,
    label,
    description,
    enabled,
    onChange,
    disabled,
    color
}: NotificationToggleProps) {
    const colorClasses = {
        green: 'bg-green-100 text-green-600',
        purple: 'bg-purple-100 text-purple-600',
        pink: 'bg-pink-100 text-pink-600',
        indigo: 'bg-indigo-100 text-indigo-600',
        blue: 'bg-blue-100 text-blue-600',
        red: 'bg-red-100 text-red-600',
    }[color];

    return (
        <div className="flex items-start justify-between py-3 border-b border-gray-100 last:border-0">
            <div className="flex items-start space-x-3">
                <div className={`p-2 rounded-lg ${colorClasses}`}>
                    <Icon className="w-5 h-5" />
                </div>
                <div>
                    <h3 className="font-medium text-gray-900">{label}</h3>
                    <p className="text-sm text-gray-600">{description}</p>
                </div>
            </div>
            <button
                onClick={() => onChange(!enabled)}
                disabled={disabled}
                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${enabled ? 'bg-orange-600' : 'bg-gray-300'
                    }`}
            >
                <span
                    className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${enabled ? 'translate-x-6' : 'translate-x-1'
                        }`}
                />
            </button>
        </div>
    );
}
