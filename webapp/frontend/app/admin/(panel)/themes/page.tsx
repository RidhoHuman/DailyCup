'use client';

import { useState, useEffect } from 'react';
import { useAuthStore } from '@/lib/stores/auth-store';

// Helper function to get auth token
const getAuthToken = (): string | null => {
  try {
    const authData = localStorage.getItem('dailycup-auth');
    if (authData) {
      const parsed = JSON.parse(authData);
      return parsed?.state?.token || null;
    }
  } catch (error) {
    console.error('Error reading auth token:', error);
  }
  return null;
};

interface Theme {
  id: number;
  name: string;
  slug: string;
  description: string;
  is_active: boolean;
  start_date: string | null;
  end_date: string | null;
  colors: {
    primary: string;
    secondary: string;
    accent: string;
    background: string;
    text: string;
    navBackground: string;
    navText: string;
  };
  images: {
    banner: string | null;
    logo: string | null;
    background: string | null;
  };
  custom_css: string;
}

export default function ThemesPage() {
  const { user } = useAuthStore();
  const [themes, setThemes] = useState<Theme[]>([]);
  const [loading, setLoading] = useState(true);
  const [activating, setActivating] = useState<number | null>(null);

  useEffect(() => {
    fetchThemes();
  }, []);

  const fetchThemes = async () => {
    setLoading(true);
    try {
      const token = getAuthToken();
      const response = await fetch(
        '/api/themes.php?action=get_all',
        {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
        }
      );

      const data = await response.json();
      if (data.success) {
        setThemes(data.themes);
      }
    } catch (error) {
      console.error('Error fetching themes:', error);
    } finally {
      setLoading(false);
    }
  };

  const activateTheme = async (slug: string, themeId: number) => {
    if (!confirm(`Activate ${slug} theme?`)) return;

    setActivating(themeId);
    try {
      const token = getAuthToken();
      const response = await fetch(
        '/api/themes.php',
        {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            action: 'activate',
            slug: slug,
          }),
        }
      );

      const data = await response.json();
      if (data.success) {
        alert('Theme activated! Refresh the page to see changes.');
        fetchThemes();
      } else {
        alert('Error: ' + data.message);
      }
    } catch (error) {
      console.error('Error activating theme:', error);
      alert('Error activating theme');
    } finally {
      setActivating(null);
    }
  };

  const isDateActive = (startDate: string | null, endDate: string | null) => {
    if (!startDate && !endDate) return true; // Permanent theme
    
    const today = new Date();
    const start = startDate ? new Date(startDate) : null;
    const end = endDate ? new Date(endDate) : null;
    
    if (start && today < start) return false;
    if (end && today > end) return false;
    
    return true;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading themes...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6">
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Seasonal Themes</h1>
        <p className="text-gray-600 mt-2">
          Manage website themes and seasonal appearances
        </p>
      </div>

      {/* Active Theme Banner */}
      {themes.find((t) => t.is_active) && (
        <div className="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
          <div className="flex items-center">
            <svg
              className="w-6 h-6 text-green-500 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <span className="font-semibold text-green-800">
              Active Theme: {themes.find((t) => t.is_active)?.name}
            </span>
          </div>
        </div>
      )}

      {/* Themes Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {themes.map((theme) => {
          const dateActive = isDateActive(theme.start_date, theme.end_date);
          
          return (
            <div
              key={theme.id}
              className={`bg-white rounded-lg shadow-md overflow-hidden ${
                theme.is_active ? 'ring-4 ring-green-500' : ''
              }`}
            >
              {/* Theme Preview */}
              <div
                className="h-32 p-4 flex items-center justify-center"
                style={{
                  background: `linear-gradient(135deg, ${theme.colors.primary} 0%, ${theme.colors.secondary} 100%)`,
                }}
              >
                <h3 className="text-2xl font-bold" style={{ color: theme.colors.navText }}>
                  {theme.name}
                </h3>
              </div>

              {/* Theme Info */}
              <div className="p-4">
                <div className="mb-3">
                  <p className="text-sm text-gray-600">{theme.description}</p>
                </div>

                {/* Date Range */}
                {(theme.start_date || theme.end_date) && (
                  <div className="mb-3">
                    <div className="flex items-center text-sm text-gray-500">
                      <svg
                        className="w-4 h-4 mr-1"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                      </svg>
                      {theme.start_date && new Date(theme.start_date).toLocaleDateString('id-ID')}
                      {theme.start_date && theme.end_date && ' - '}
                      {theme.end_date && new Date(theme.end_date).toLocaleDateString('id-ID')}
                    </div>
                    {!dateActive && (
                      <span className="inline-block mt-1 text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
                        Not in date range
                      </span>
                    )}
                  </div>
                )}

                {/* Color Palette */}
                <div className="mb-3">
                  <p className="text-xs text-gray-500 mb-1">Color Palette:</p>
                  <div className="flex space-x-1">
                    <div
                      className="w-8 h-8 rounded border border-gray-300"
                      style={{ backgroundColor: theme.colors.primary }}
                      title="Primary"
                    ></div>
                    <div
                      className="w-8 h-8 rounded border border-gray-300"
                      style={{ backgroundColor: theme.colors.secondary }}
                      title="Secondary"
                    ></div>
                    <div
                      className="w-8 h-8 rounded border border-gray-300"
                      style={{ backgroundColor: theme.colors.accent }}
                      title="Accent"
                    ></div>
                    <div
                      className="w-8 h-8 rounded border border-gray-300"
                      style={{ backgroundColor: theme.colors.navBackground }}
                      title="Nav Background"
                    ></div>
                  </div>
                </div>

                {/* Status Badge */}
                <div className="mb-3">
                  {theme.is_active ? (
                    <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                      <svg
                        className="w-4 h-4 mr-1"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clipRule="evenodd"
                        />
                      </svg>
                      Active
                    </span>
                  ) : (
                    <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                      Inactive
                    </span>
                  )}
                </div>

                {/* Action Button */}
                {!theme.is_active && (
                  <button
                    onClick={() => activateTheme(theme.slug, theme.id)}
                    disabled={activating === theme.id || !dateActive}
                    className={`w-full py-2 px-4 rounded font-medium transition-colors ${
                      dateActive
                        ? 'bg-blue-600 text-white hover:bg-blue-700 disabled:bg-blue-400'
                        : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                    }`}
                  >
                    {activating === theme.id ? (
                      <span className="flex items-center justify-center">
                        <svg
                          className="animate-spin h-5 w-5 mr-2"
                          fill="none"
                          viewBox="0 0 24 24"
                        >
                          <circle
                            className="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            strokeWidth="4"
                          ></circle>
                          <path
                            className="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                          ></path>
                        </svg>
                        Activating...
                      </span>
                    ) : (
                      'Activate Theme'
                    )}
                  </button>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Info Section */}
      <div className="mt-8 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
        <div className="flex">
          <svg
            className="w-6 h-6 text-blue-500 mr-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <div>
            <h4 className="font-semibold text-blue-800">About Seasonal Themes</h4>
            <p className="text-sm text-blue-700 mt-1">
              Seasonal themes allow you to customize the look and feel of your website based on
              holidays, seasons, or special events. Only themes within their active date range can
              be activated.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
