'use client';

import { useState, useEffect } from 'react';
import { Settings, Save, RotateCcw, Mail, Phone, MapPin, DollarSign, Award, Truck } from 'lucide-react';
import api from '@/lib/api-client';

interface Setting {
  key: string;
  value: unknown;
  type: string;
  label: string;
  description: string;
  is_public: boolean;
  updated_at: string;
}

interface SettingsByCategory {
  [category: string]: Setting[];
}

export default function AdminSettingsPage() {
  const [settingsByCategory, setSettingsByCategory] = useState<SettingsByCategory>({});
  const [originalValues, setOriginalValues] = useState<Record<string, unknown>>({});
  const [changedValues, setChangedValues] = useState<Record<string, unknown>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const getErrorMessage = (err: unknown): string | null => {
    if (!err) return null;
    if (typeof err === 'string') return err;
    if (err instanceof Error) return err.message;
    if (typeof err === 'object' && err !== null && 'message' in err && typeof (err as any).message === 'string') {
      return (err as any).message;
    }
    return null;
  };

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    setLoading(true);
    try {
      const response = await api.get<{ data: { by_category: SettingsByCategory; settings: Record<string, any> } }>(
        '/admin/settings/get.php',
        { requiresAuth: true }
      );

      if (response.data) {
        setSettingsByCategory(response.data.by_category);
        setOriginalValues(response.data.settings);
      }
    } catch (err: unknown) {
      setError(getErrorMessage(err) || 'Failed to load settings');
    } finally {
      setLoading(false);
    }
  };

  const handleValueChange = (key: string, value: unknown) => {
    setChangedValues(prev => ({
      ...prev,
      [key]: value
    }));
  };

  const handleSave = async () => {
    if (Object.keys(changedValues).length === 0) {
      setError('No changes to save');
      return;
    }

    setSaving(true);
    setError(null);
    setSuccessMessage(null);

    try {
      const response = await api.post<{ data: { updated: string[]; failed: Record<string, unknown>[] }; message: string }>(
        '/admin/settings/update.php',
        { settings: changedValues },
        { requiresAuth: true }
      );

      if (response.data) {
        setSuccessMessage(response.message || `${response.data.updated.length} settings updated successfully`);
        setChangedValues({});
        fetchSettings(); // Reload to get updated values
      }
    } catch (err: unknown) {
      setError(getErrorMessage(err) || 'Failed to save settings');
    } finally {
      setSaving(false);
    }
  };

  const handleReset = () => {
    setChangedValues({});
    setError(null);
    setSuccessMessage(null);
  };

  const getValue = (key: string) => {
    return changedValues[key] !== undefined ? changedValues[key] : originalValues[key];
  };

  const getCategoryIcon = (category: string) => {
    switch (category) {
      case 'contact': return <Mail className="w-5 h-5" />;
      case 'business': return <MapPin className="w-5 h-5" />;
      case 'payment': return <DollarSign className="w-5 h-5" />;
      case 'loyalty': return <Award className="w-5 h-5" />;
      case 'delivery': return <Truck className="w-5 h-5" />;
      default: return <Settings className="w-5 h-5" />;
    }
  };

  const renderInput = (setting: Setting) => {
    const value = getValue(setting.key);
    const hasChanged = changedValues[setting.key] !== undefined;

    const baseClass = `w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
      hasChanged ? 'border-blue-500 bg-blue-50' : 'border-gray-300'
    }`;

    const toSafeString = (v: unknown) => {
      if (v === undefined || v === null) return '';
      if (typeof v === 'string') return v;
      if (typeof v === 'number' || typeof v === 'boolean') return String(v);
      try {
        return JSON.stringify(v);
      } catch {
        return String(v);
      }
    };

    switch (setting.type) {
      case 'textarea': {
        const safeValue = toSafeString(value);
        return (
          <textarea
            value={safeValue}
            onChange={(e) => handleValueChange(setting.key, e.target.value)}
            className={baseClass}
            rows={3}
            placeholder={setting.description}
          />
        );
      }

      case 'number': {
        const safeValue = value === undefined || value === null ? '' : typeof value === 'number' ? value : String(value);
        return (
          <input
            type="number"
            value={safeValue as string | number}
            onChange={(e) => {
              const v = e.target.value;
              handleValueChange(setting.key, v === '' ? '' : parseFloat(v));
            }}
            className={baseClass}
            placeholder={setting.description}
          />
        );
      }

      default: {
        const safeValue = toSafeString(value);
        return (
          <input
            type={setting.type === 'email' ? 'email' : setting.type === 'url' ? 'url' : 'text'}
            value={safeValue}
            onChange={(e) => handleValueChange(setting.key, e.target.value)}
            className={baseClass}
            placeholder={setting.description}
          />
        );
      }
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  const hasChanges = Object.keys(changedValues).length > 0;

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
          <Settings className="w-8 h-8" />
          System Settings
        </h1>
        <p className="text-gray-600 mt-2">
          Manage business information, contact details, and system configuration
        </p>
      </div>

      {/* Alerts */}
      {error && (
        <div className="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
          <p className="text-red-800">{error}</p>
        </div>
      )}

      {successMessage && (
        <div className="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
          <p className="text-green-800">{successMessage}</p>
        </div>
      )}

      {/* Action Buttons */}
      {hasChanges && (
        <div className="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center justify-between">
          <div>
            <p className="font-semibold text-blue-900">Unsaved Changes</p>
            <p className="text-sm text-blue-700">{Object.keys(changedValues).length} settings modified</p>
          </div>
          <div className="flex gap-3">
            <button
              onClick={handleReset}
              className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center gap-2"
            >
              <RotateCcw className="w-4 h-4" />
              Reset
            </button>
            <button
              onClick={handleSave}
              disabled={saving}
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
            >
              <Save className="w-4 h-4" />
              {saving ? 'Saving...' : 'Save Changes'}
            </button>
          </div>
        </div>
      )}

      {/* Settings by Category */}
      <div className="space-y-6">
        {Object.entries(settingsByCategory).map(([category, settings]) => (
          <div key={category} className="bg-white rounded-lg shadow-sm border border-gray-200">
            <div className="p-4 border-b border-gray-200 bg-gray-50 flex items-center gap-3">
              {getCategoryIcon(category)}
              <h2 className="text-xl font-semibold capitalize">{category}</h2>
            </div>
            
            <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
              {settings.map((setting) => (
                <div key={setting.key} className="space-y-2">
                  <div className="flex items-center justify-between">
                    <label className="block font-medium text-gray-900">
                      {setting.label}
                    </label>
                    {setting.is_public && (
                      <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                        Public
                      </span>
                    )}
                  </div>
                  
                  {renderInput(setting)}
                  
                  {setting.description && (
                    <p className="text-sm text-gray-500">{setting.description}</p>
                  )}
                  
                  <p className="text-xs text-gray-400">
                    Last updated: {new Date(setting.updated_at).toLocaleString()}
                  </p>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>

      {/* Save Button (Bottom) */}
      {hasChanges && (
        <div className="mt-8 flex justify-end gap-3">
          <button
            onClick={handleReset}
            className="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center gap-2"
          >
            <RotateCcw className="w-4 h-4" />
            Reset All
          </button>
          <button
            onClick={handleSave}
            disabled={saving}
            className="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2 text-lg font-semibold"
          >
            <Save className="w-5 h-5" />
            {saving ? 'Saving...' : 'Save All Changes'}
          </button>
        </div>
      )}
    </div>
  );
}
