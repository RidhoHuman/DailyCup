'use client';

import { useEffect } from 'react';
import { useRouter, usePathname } from 'next/navigation';
import { useKurirStore, useKurirHydration } from '@/lib/stores/kurir-store';
import Link from 'next/link';

const publicPaths = ['/kurir/login', '/kurir/register'];

export default function KurirLayout({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, user, logout } = useKurirStore();
  const isHydrated = useKurirHydration();
  const router = useRouter();
  const pathname = usePathname();

  const isPublicPage = publicPaths.includes(pathname);

  useEffect(() => {
    // Wait for hydration before checking auth
    if (!isHydrated) return;
    
    if (!isAuthenticated && !isPublicPage) {
      router.push('/kurir/login');
    }
  }, [isAuthenticated, isPublicPage, router, isHydrated]);

  // Public pages (login/register) → no nav
  if (isPublicPage) {
    return <>{children}</>;
  }

  // Show loading while hydrating
  if (!isHydrated) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-[#1a1a1a] flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-amber-600"></div>
      </div>
    );
  }

  // Protected pages → show nav
  if (!isAuthenticated) return null;

  const navItems = [
    { href: '/kurir', label: 'Dashboard', icon: 'bi-grid-fill' },
    { href: '/kurir/orders', label: 'Riwayat', icon: 'bi-clock-history' },
    { href: '/kurir/profile', label: 'Profil', icon: 'bi-person-fill' },
  ];

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-[#1a1a1a] flex flex-col">
      {/* Top Bar */}
      <header className="bg-white dark:bg-[#2a2a2a] shadow-sm border-b border-gray-100 dark:border-gray-800 sticky top-0 z-50">
        <div className="max-w-lg mx-auto px-4 h-14 flex items-center justify-between">
          <Link href="/kurir" className="flex items-center gap-2">
            <span className="text-lg font-bold text-amber-700 dark:text-amber-400">DailyVery</span>
          </Link>
          <div className="flex items-center gap-3">
            <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
              user?.status === 'available' ? 'bg-green-100 text-green-700' :
              user?.status === 'busy' ? 'bg-amber-100 text-amber-700' :
              'bg-gray-100 text-gray-500'
            }`}>
              {user?.status === 'available' ? 'Online' : user?.status === 'busy' ? 'Sibuk' : 'Offline'}
            </span>
            <button onClick={() => { logout(); router.push('/kurir/login'); }}
              className="text-gray-400 hover:text-red-500 transition-colors" title="Logout">
              <i className="bi bi-box-arrow-right text-lg"></i>
            </button>
          </div>
        </div>
      </header>

      {/* Content */}
      <main className="flex-1 max-w-lg mx-auto w-full px-4 py-4 pb-20">
        {children}
      </main>

      {/* Bottom Nav */}
      <nav className="fixed bottom-0 left-0 right-0 bg-white dark:bg-[#2a2a2a] border-t border-gray-200 dark:border-gray-700 z-50">
        <div className="max-w-lg mx-auto flex justify-around py-2">
          {navItems.map(item => {
            const active = pathname === item.href || (item.href !== '/kurir' && pathname.startsWith(item.href));
            return (
              <Link key={item.href} href={item.href}
                className={`flex flex-col items-center gap-0.5 px-4 py-1 rounded-lg transition-colors ${
                  active ? 'text-amber-700 dark:text-amber-400' : 'text-gray-400 hover:text-gray-600'
                }`}>
                <i className={`bi ${item.icon} text-xl`}></i>
                <span className="text-[10px] font-medium">{item.label}</span>
              </Link>
            );
          })}
        </div>
      </nav>
    </div>
  );
}
