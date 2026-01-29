'use client';

import { useUIStore } from '@/lib/stores/ui-store';
import { useSyncExternalStore } from 'react';

// Hydration-safe mounting helper
const emptySubscribe = () => () => {};
const getSnapshot = () => true;
const getServerSnapshot = () => false;

export function ThemeToggle() {
  const { theme, setTheme } = useUIStore();
  const mounted = useSyncExternalStore(emptySubscribe, getSnapshot, getServerSnapshot);

  const resolvedTheme = theme === 'system'
    ? (typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
    : theme;

  const toggleTheme = () => {
    setTheme(resolvedTheme === 'dark' ? 'light' : 'dark');
  };

  if (!mounted) {
    return <div className="w-10 h-10" />;
  }

  return (
    <button
      onClick={toggleTheme}
      className="p-2 text-gray-700 hover:text-[#a97456] dark:text-gray-300 dark:hover:text-[#a97456] transition-colors rounded-full hover:bg-gray-100 dark:hover:bg-gray-800"
      title={`Toggle theme (Current: ${theme})`}
      aria-label="Toggle theme"
    >
      {resolvedTheme === 'dark' ? (
        <i className="bi bi-sun-fill text-xl"></i>
      ) : (
        <i className="bi bi-moon-stars-fill text-xl"></i>
      )}
    </button>
  );
}

// Dropdown version for more explicit control
export function ThemeDropdown() {
  const { theme, setTheme } = useUIStore();

  return (
    <div className="relative group">
      <button
        className="
          flex items-center gap-2 px-3 py-2 rounded-lg
          text-gray-600 dark:text-gray-300
          hover:bg-gray-100 dark:hover:bg-gray-800
          transition-colors duration-200
        "
      >
        <span className="text-sm font-medium capitalize">{theme}</span>
        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      <div className="
        absolute right-0 mt-1 w-32 py-1
        bg-white dark:bg-gray-800
        border border-gray-200 dark:border-gray-700
        rounded-lg shadow-lg
        opacity-0 invisible group-hover:opacity-100 group-hover:visible
        transition-all duration-200
        z-50
      ">
        {(['light', 'dark', 'system'] as const).map((t) => (
          <button
            key={t}
            onClick={() => setTheme(t)}
            className={`
              w-full px-4 py-2 text-left text-sm capitalize
              transition-colors duration-150
              ${theme === t 
                ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400' 
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'}
            `}
          >
            {t}
          </button>
        ))}
      </div>
    </div>
  );
}
