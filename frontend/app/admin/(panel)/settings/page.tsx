"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api-client";

interface Settings {
  store_name: string;
  store_email: string;
  store_phone: string;
  store_address: string;
  tax_rate: number;
  delivery_fee: number;
  min_order: number;
  enable_notifications: number;
  enable_inventory_alerts: number;
}

export default function AdminSettingsPage() {
  const [settings, setSettings] = useState({
    storeName: "DailyCup Coffee",
    storeEmail: "info@dailycup.com",
    storePhone: "+62 812-3456-7890",
    storeAddress: "Jl. Coffee Street No. 123, Jakarta",
    taxRate: "11",
    deliveryFee: "15000",
    minOrder: "50000",
    enableNotifications: true,
    enableInventoryAlerts: true,
  });

  const [saved, setSaved] = useState(false);
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    try {
      setLoading(true);
      const response = await api.get<{ success: boolean; data: Settings }>('/admin/settings.php');
      if (response.success && response.data) {
        const data = response.data;
        setSettings({
          storeName: data.store_name || "DailyCup Coffee",
          storeEmail: data.store_email || "info@dailycup.com",
          storePhone: data.store_phone || "+62 812-3456-7890",
          storeAddress: data.store_address || "Jl. Coffee Street No. 123, Jakarta",
          taxRate: (data.tax_rate || 11).toString(),
          deliveryFee: (data.delivery_fee || 15000).toString(),
          minOrder: (data.min_order || 50000).toString(),
          enableNotifications: Boolean(data.enable_notifications),
          enableInventoryAlerts: Boolean(data.enable_inventory_alerts),
        });
      }
    } catch (error) {
      console.error('Error fetching settings:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    
    try {
      // Convert camelCase to snake_case for backend
      const backendData = {
        store_name: settings.storeName,
        store_email: settings.storeEmail,
        store_phone: settings.storePhone,
        store_address: settings.storeAddress,
        tax_rate: parseFloat(settings.taxRate),
        delivery_fee: parseInt(settings.deliveryFee),
        min_order: parseInt(settings.minOrder),
        enable_notifications: settings.enableNotifications ? 1 : 0,
        enable_inventory_alerts: settings.enableInventoryAlerts ? 1 : 0,
      };
      
      const response = await api.put<{ success: boolean; message: string }>('/admin/settings.php', backendData);
      if (response.success) {
        setSaved(true);
        setTimeout(() => setSaved(false), 3000);
      }
    } catch (error) {
      console.error('Error saving settings:', error);
      alert('Failed to save settings');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-gray-500">Loading settings...</div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Settings</h1>
        <p className="text-gray-500">Configure your store settings</p>
      </div>

      <form onSubmit={handleSave} className="space-y-6">
        {/* Store Information */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h2 className="text-lg font-bold text-gray-800 mb-4">Store Information</h2>
          
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Store Name
              </label>
              <input
                type="text"
                value={settings.storeName}
                onChange={(e) => setSettings({ ...settings, storeName: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Email
                </label>
                <input
                  type="email"
                  value={settings.storeEmail}
                  onChange={(e) => setSettings({ ...settings, storeEmail: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Phone
                </label>
                <input
                  type="tel"
                  value={settings.storePhone}
                  onChange={(e) => setSettings({ ...settings, storePhone: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Address
              </label>
              <textarea
                value={settings.storeAddress}
                onChange={(e) => setSettings({ ...settings, storeAddress: e.target.value })}
                rows={3}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              />
            </div>
          </div>
        </div>

        {/* Pricing Settings */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h2 className="text-lg font-bold text-gray-800 mb-4">Pricing Settings</h2>
          
          <div className="grid grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Tax Rate (%)
              </label>
              <input
                type="number"
                value={settings.taxRate}
                onChange={(e) => setSettings({ ...settings, taxRate: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Delivery Fee (IDR)
              </label>
              <input
                type="number"
                value={settings.deliveryFee}
                onChange={(e) => setSettings({ ...settings, deliveryFee: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Min Order (IDR)
              </label>
              <input
                type="number"
                value={settings.minOrder}
                onChange={(e) => setSettings({ ...settings, minOrder: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              />
            </div>
          </div>
        </div>

        {/* Notifications */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h2 className="text-lg font-bold text-gray-800 mb-4">Notifications</h2>
          
          <div className="space-y-4">
            <label className="flex items-center gap-3 cursor-pointer">
              <input
                type="checkbox"
                checked={settings.enableNotifications}
                onChange={(e) => setSettings({ ...settings, enableNotifications: e.target.checked })}
                className="w-5 h-5 text-[#a97456] rounded focus:ring-2 focus:ring-[#a97456]"
              />
              <div>
                <div className="font-medium text-gray-800">Email Notifications</div>
                <div className="text-sm text-gray-500">Receive email alerts for new orders</div>
              </div>
            </label>

            <label className="flex items-center gap-3 cursor-pointer">
              <input
                type="checkbox"
                checked={settings.enableInventoryAlerts}
                onChange={(e) => setSettings({ ...settings, enableInventoryAlerts: e.target.checked })}
                className="w-5 h-5 text-[#a97456] rounded focus:ring-2 focus:ring-[#a97456]"
              />
              <div>
                <div className="font-medium text-gray-800">Low Stock Alerts</div>
                <div className="text-sm text-gray-500">Get notified when products are running low</div>
              </div>
            </label>
          </div>
        </div>

        {/* Save Button */}
        <div className="flex items-center gap-4">
          <button
            type="submit"
            disabled={saving}
            className="px-6 py-3 bg-[#a97456] text-white rounded-lg font-medium hover:bg-[#8f6249] transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            {saving ? (
              <>
                <i className="bi bi-arrow-repeat animate-spin"></i>
                Saving...
              </>
            ) : (
              <>
                <i className="bi bi-check-circle"></i>
                Save Changes
              </>
            )}
          </button>
          {saved && (
            <span className="text-green-600 font-medium flex items-center gap-2">
              <i className="bi bi-check-circle-fill"></i>
              Settings saved successfully!
            </span>
          )}
        </div>
      </form>
    </div>
  );
}
