'use client';

import { createContext, useContext, useEffect, useState, useSyncExternalStore, ReactNode } from 'react';

type Theme = 'light' | 'dark' | 'system';

interface ThemeContextType {
  theme: Theme;
  setTheme: (theme: Theme) => void;
  resolvedTheme: 'light' | 'dark';
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

// Hydration-safe mounting helper
const emptySubscribe = () => () => {};
const getSnapshot = () => true;
const getServerSnapshot = () => false;

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [theme, setThemeState] = useState<Theme>('system');
  const [resolvedTheme, setResolvedTheme] = useState<'light' | 'dark'>('light');
  const mounted = useSyncExternalStore(emptySubscribe, getSnapshot, getServerSnapshot);

  // Load theme from localStorage on mount  
  useEffect(() => {
    if (!mounted) return;
    const savedTheme = localStorage.getItem('theme') as Theme | null;
    if (savedTheme) {
      // Use timeout to avoid synchronous setState in effect
      setTimeout(() => setThemeState(savedTheme), 0);
    }
  }, [mounted]);

  // Apply theme to document
  useEffect(() => {
    if (!mounted) return;

    const root = document.documentElement;
    let resolved: 'light' | 'dark';

    if (theme === 'system') {
      const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
      resolved = mediaQuery.matches ? 'dark' : 'light';

      // Listen for system theme changes
      const handler = (e: MediaQueryListEvent) => {
        const newResolved = e.matches ? 'dark' : 'light';
        setTimeout(() => setResolvedTheme(newResolved), 0);
        root.classList.toggle('dark', e.matches);
      };

      mediaQuery.addEventListener('change', handler);
      
      // Set initial resolved theme with timeout
      setTimeout(() => setResolvedTheme(resolved), 0);
      root.classList.toggle('dark', resolved === 'dark');
      
      return () => mediaQuery.removeEventListener('change', handler);
    } else {
      resolved = theme;
    }

    setTimeout(() => setResolvedTheme(resolved), 0);
    root.classList.toggle('dark', resolved === 'dark');
  }, [theme, mounted]);

  const setTheme = (newTheme: Theme) => {
    setThemeState(newTheme);
    localStorage.setItem('theme', newTheme);
  };

  // Prevent flash of wrong theme
  if (!mounted) {
    return <div style={{ visibility: 'hidden' }}>{children}</div>;
  }

  return (
    <ThemeContext.Provider value={{ theme, setTheme, resolvedTheme }}>
      {children}
    </ThemeContext.Provider>
  );
}

export function useTheme() {
  const context = useContext(ThemeContext);
  if (!context) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return context;
}
