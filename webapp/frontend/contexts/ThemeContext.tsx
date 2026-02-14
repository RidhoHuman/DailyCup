'use client';

import { createContext, useContext, useState, useEffect, ReactNode } from 'react';

interface ThemeConfig {
  primary_color: string;
  secondary_color: string;
  accent_color: string;
  background_color: string;
  text_color: string;
  banner_image?: string;
  logo_variant?: string;
  css_overrides?: string;
}

interface SeasonalTheme {
  id: number;
  name: string;
  slug: string;
  description?: string;
  start_date: string;
  end_date: string;
  year_recurring: boolean;
  is_active: boolean;
  priority: number;
  theme_config: ThemeConfig;
  is_manual?: boolean;
}

interface ThemeContextType {
  currentTheme: SeasonalTheme | null;
  isLoading: boolean;
  applyTheme: (theme: SeasonalTheme | null) => void;
  refreshTheme: () => void;
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [currentTheme, setCurrentTheme] = useState<SeasonalTheme | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const applyTheme = (theme: SeasonalTheme | null) => {
    if (!theme) {
      // Remove custom CSS
      const styleElement = document.getElementById('seasonal-theme-styles');
      if (styleElement) {
        styleElement.remove();
      }
      // Reset to default colors
      document.documentElement.style.removeProperty('--theme-primary');
      document.documentElement.style.removeProperty('--theme-secondary');
      document.documentElement.style.removeProperty('--theme-accent');
      document.documentElement.style.removeProperty('--theme-background');
      document.documentElement.style.removeProperty('--theme-text');
      setCurrentTheme(null);
      return;
    }

    const config = theme.theme_config;

    // Set CSS variables
    document.documentElement.style.setProperty('--theme-primary', config.primary_color);
    document.documentElement.style.setProperty('--theme-secondary', config.secondary_color);
    document.documentElement.style.setProperty('--theme-accent', config.accent_color);
    document.documentElement.style.setProperty('--theme-background', config.background_color);
    document.documentElement.style.setProperty('--theme-text', config.text_color);

    // Apply custom CSS overrides
    if (config.css_overrides) {
      let styleElement = document.getElementById('seasonal-theme-styles');
      if (!styleElement) {
        styleElement = document.createElement('style');
        styleElement.id = 'seasonal-theme-styles';
        document.head.appendChild(styleElement);
      }
      styleElement.textContent = config.css_overrides;
    }

    setCurrentTheme(theme);
    
    // Store in localStorage for persistence
    localStorage.setItem('seasonal_theme', JSON.stringify(theme));
  };

  const fetchCurrentTheme = async () => {
    try {
      const response = await fetch('http://localhost/DailyCup/webapp/backend/api/themes.php?action=current', {
        headers: { 'ngrok-skip-browser-warning': '69420' }
      });
      const data = await response.json();

      if (data.success && data.theme) {
        applyTheme(data.theme);
      } else {
        // Check localStorage for cached theme
        const cached = localStorage.getItem('seasonal_theme');
        if (cached) {
          const cachedTheme = JSON.parse(cached);
          applyTheme(cachedTheme);
        }
      }
    } catch (error) {
      console.error('Error fetching theme:', error);
      // Try to use cached theme
      const cached = localStorage.getItem('seasonal_theme');
      if (cached) {
        try {
          const cachedTheme = JSON.parse(cached);
          applyTheme(cachedTheme);
        } catch (e) {
          console.error('Error parsing cached theme:', e);
        }
      }
    } finally {
      setIsLoading(false);
    }
  };

  const refreshTheme = () => {
    setIsLoading(true);
    fetchCurrentTheme();
  };

  useEffect(() => {
    fetchCurrentTheme();
    
    // Refresh theme daily
    const interval = setInterval(() => {
      fetchCurrentTheme();
    }, 24 * 60 * 60 * 1000); // 24 hours

    return () => clearInterval(interval);
  }, []);

  return (
    <ThemeContext.Provider value={{ currentTheme, isLoading, applyTheme, refreshTheme }}>
      {children}
    </ThemeContext.Provider>
  );
}

export function useTheme() {
  const context = useContext(ThemeContext);
  if (context === undefined) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return context;
}
