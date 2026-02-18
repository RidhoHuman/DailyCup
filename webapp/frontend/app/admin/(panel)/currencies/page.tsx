'use client';

import { useState, useEffect } from 'react';

// Helper function to get auth token
const getAuthToken = (): string | null => {
  try {
    const authData = localStorage.getItem('dailycup-auth');
    if (authData) {
      const parsed = JSON.parse(authData);
      if (parsed?.state?.token) return parsed.state.token;
    }
    // Fallback to legacy key
    const legacy = localStorage.getItem('token');
    if (legacy) return legacy;
  } catch (error) {
    console.error('Error reading auth token:', error);
  }
  return null;
};

interface Currency {
  id: number;
  code: string;
  name: string;
  symbol: string;
  decimal_places: number;
  is_active: boolean;
  is_base_currency: boolean;
  display_order: number;
}

interface ExchangeRate {
  from_currency: string;
  to_currency: string;
  rate: number;
  last_updated: string;
  source: string;
}

interface CurrencySettings {
  auto_update_rates?: string;
  rate_update_interval?: string;
  default_display_currency?: string;
  show_currency_selector?: string;
  rate_api_provider?: string;
}

export default function CurrenciesPage() {
  const [currencies, setCurrencies] = useState<Currency[]>([]);
  const [settings, setSettings] = useState<CurrencySettings>({});
  const [loading, setLoading] = useState(true);
  const [syncing, setSyncing] = useState(false);
  const [showAddModal, setShowAddModal] = useState(false);
  const [newCurrency, setNewCurrency] = useState({
    code: '',
    name: '',
    symbol: '',
    decimal_places: 2
  });

  useEffect(() => {
    loadCurrencies();
    loadSettings();
  }, []);

  const loadCurrencies = async () => {
    try {
      const token = getAuthToken();
      const response = await fetch('/api/currencies.php?action=list', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });
      
      const data = await response.json();
      if (data.success) {
        setCurrencies(data.currencies);
      }
    } catch (error) {
      console.error('Failed to load currencies:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadSettings = async () => {
    try {
      const token = getAuthToken();
      const response = await fetch('/api/currencies.php?action=get_settings', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });
      
      const data = await response.json();
      if (data.success) {
        setSettings(data.settings);
      }
    } catch (error) {
      console.error('Failed to load settings:', error);
    }
  };

  const toggleCurrencyStatus = async (currencyId: number, currentStatus: boolean) => {
    try {
      const token = getAuthToken();
      const response = await fetch('/api/currencies.php?action=update_status', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          currency_id: currencyId,
          is_active: !currentStatus
        })
      });

      const data = await response.json();
      if (data.success) {
        loadCurrencies();
      } else {
        alert(data.message);
      }
    } catch (error) {
      console.error('Failed to update currency status:', error);
      alert('Failed to update currency status');
    }
  };

  const syncExchangeRates = async () => {
    if (!confirm('Sync exchange rates from live API? This will overwrite manual rates.')) {
      return;
    }

    setSyncing(true);
    try {
      const token = getAuthToken();
      const response = await fetch('/api/currencies.php?action=sync_rates', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      const data = await response.json();
      if (data.success) {
        alert(`✓ ${data.message}\nLast sync: ${data.last_sync}`);
        loadCurrencies();
      } else {
        alert('Failed: ' + data.message);
      }
    } catch (error) {
      console.error('Failed to sync rates:', error);
      alert('Failed to sync exchange rates');
    } finally {
      setSyncing(false);
    }
  };

  const updateSetting = async (key: string, value: string) => {
    try {
      const token = getAuthToken();
      const response = await fetch('/api/currencies.php?action=update_settings', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ [key]: value })
      });

      const data = await response.json();
      if (data.success) {
        setSettings(prev => ({ ...prev, [key]: value }));
      }
    } catch (error) {
      console.error('Failed to update setting:', error);
    }
  };

  const addCurrency = async () => {
    if (!newCurrency.code || !newCurrency.name || !newCurrency.symbol) {
      alert('Please fill all required fields');
      return;
    }

    try {
      const token = getAuthToken();
      const response = await fetch('/api/currencies.php?action=add_currency', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(newCurrency)
      });

      const data = await response.json();
      if (data.success) {
        alert('✓ Currency added successfully');
        setShowAddModal(false);
        setNewCurrency({ code: '', name: '', symbol: '', decimal_places: 2 });
        loadCurrencies();
      } else {
        alert('Failed: ' + data.message);
      }
    } catch (error) {
      console.error('Failed to add currency:', error);
      alert('Failed to add currency');
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-gray-600">Loading currencies...</div>
      </div>
    );
  }

  const baseCurrency = currencies.find(c => c.is_base_currency);

  return (
    <>
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">Multi-Currency Management</h1>
        <p className="mt-2 text-sm text-gray-600">
          Manage currencies and exchange rates for your store
        </p>
      </div>

      {/* Settings Card */}
      <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Currency Settings</h2>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label className="flex items-center space-x-3">
              <input
                type="checkbox"
                checked={settings.auto_update_rates === 'true'}
                onChange={(e) => updateSetting('auto_update_rates', e.target.checked ? 'true' : 'false')}
                className="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
              />
              <span className="text-sm font-medium text-gray-700">
                Auto-update exchange rates daily
              </span>
            </label>
            <p className="ml-7 mt-1 text-xs text-gray-500">
              Automatically fetch latest rates from exchangerate-api.com
            </p>
          </div>

          <div>
            <label className="flex items-center space-x-3">
              <input
                type="checkbox"
                checked={settings.show_currency_selector === 'true'}
                onChange={(e) => updateSetting('show_currency_selector', e.target.checked ? 'true' : 'false')}
                className="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
              />
              <span className="text-sm font-medium text-gray-700">
                Show currency selector to customers
              </span>
            </label>
            <p className="ml-7 mt-1 text-xs text-gray-500">
              Allow customers to view prices in their preferred currency
            </p>
          </div>
        </div>

        <div className="mt-6 pt-6 border-t border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-sm font-medium text-gray-900">Base Currency</h3>
              <p className="text-xs text-gray-500 mt-1">
                All prices are stored in {baseCurrency?.code} ({baseCurrency?.symbol})
              </p>
            </div>
            
            <button
              onClick={syncExchangeRates}
              disabled={syncing}
              className={`px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors ${
                syncing
                  ? 'bg-gray-400 cursor-not-allowed'
                  : 'bg-blue-600 hover:bg-blue-700'
              }`}
            >
              {syncing ? '⏳ Syncing...' : '🔄 Sync Exchange Rates Now'}
            </button>
          </div>
        </div>
      </div>

      {/* Currencies List */}
      <div className="bg-white rounded-lg shadow-sm">
        <div className="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
          <h2 className="text-lg font-semibold text-gray-900">
            Available Currencies ({currencies.length})
          </h2>
          
          <button
            onClick={() => setShowAddModal(true)}
            className="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
          >
            ➕ Add Currency
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Currency
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Code
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Symbol
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Decimals
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Type
                </th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {currencies.map((currency) => (
                <tr key={currency.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-medium text-gray-900">
                      {currency.name}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900 font-mono font-semibold">
                      {currency.code}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900 font-semibold">
                      {currency.symbol}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-600">
                      {currency.decimal_places}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span
                      className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                        currency.is_active
                          ? 'bg-green-100 text-green-800'
                          : 'bg-gray-100 text-gray-800'
                      }`}
                    >
                      {currency.is_active ? '✓ Active' : '✗ Disabled'}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    {currency.is_base_currency && (
                      <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                        ⭐ Base
                      </span>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    {!currency.is_base_currency && (
                      <button
                        onClick={() => toggleCurrencyStatus(currency.id, currency.is_active)}
                        className={`px-3 py-1 rounded text-xs font-medium transition-colors ${
                          currency.is_active
                            ? 'bg-red-100 text-red-700 hover:bg-red-200'
                            : 'bg-green-100 text-green-700 hover:bg-green-200'
                        }`}
                      >
                        {currency.is_active ? 'Disable' : 'Enable'}
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>

      {/* Add Currency Modal */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Add New Currency</h3>
            
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Currency Code *
                </label>
                <input
                  type="text"
                  maxLength={3}
                  value={newCurrency.code}
                  onChange={(e) => setNewCurrency({ ...newCurrency, code: e.target.value.toUpperCase() })}
                  placeholder="USD"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Currency Name *
                </label>
                <input
                  type="text"
                  value={newCurrency.name}
                  onChange={(e) => setNewCurrency({ ...newCurrency, name: e.target.value })}
                  placeholder="US Dollar"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Symbol *
                </label>
                <input
                  type="text"
                  value={newCurrency.symbol}
                  onChange={(e) => setNewCurrency({ ...newCurrency, symbol: e.target.value })}
                  placeholder="$"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Decimal Places
                </label>
                <input
                  type="number"
                  min="0"
                  max="8"
                  value={newCurrency.decimal_places}
                  onChange={(e) => setNewCurrency({ ...newCurrency, decimal_places: parseInt(e.target.value) })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>

            <div className="mt-6 flex space-x-3">
              <button
                onClick={() => {
                  setShowAddModal(false);
                  setNewCurrency({ code: '', name: '', symbol: '', decimal_places: 2 });
                }}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={addCurrency}
                className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
              >
                Add Currency
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
