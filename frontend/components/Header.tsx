'use client';

import Link from 'next/link';
import { useState, useEffect } from 'react';
import { useCart } from '../contexts/CartContext';
import { ThemeToggle } from './theme/theme-toggle';
import { useWishlistStore } from '@/lib/stores/wishlist-store';
import { useAuthStore } from '@/lib/stores/auth-store';
import CartSidebar from './CartSidebar';
import NotificationBell from './notifications/NotificationBell';

export default function Header() {
  const { state } = useCart();
  const wishlistStore = useWishlistStore();
  const { user, isAuthenticated, logout } = useAuthStore();
  const [showCart, setShowCart] = useState(false);
  const [mounted, setMounted] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [showProfileMenu, setShowProfileMenu] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  return (
    <>
      <header className="bg-white dark:bg-[#2a2a2a] shadow-sm border-b dark:border-gray-700 transition-colors duration-300">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <Link href="/" className="flex items-center space-x-2">
              <div className="w-8 h-8 bg-[#a97456] rounded-full flex items-center justify-center">
                <i className="bi bi-cup text-white"></i>
              </div>
              <span className="text-xl font-bold text-black dark:text-white">DailyCup</span>
            </Link>

            <nav className="hidden md:flex space-x-8">
              <Link href="/" className="text-gray-700 dark:text-gray-200 hover:text-[#a97456] dark:hover:text-[#a97456] font-medium transition-colors">
                Home
              </Link>
              <Link href="/menu" className="text-gray-700 dark:text-gray-200 hover:text-[#a97456] dark:hover:text-[#a97456] font-medium transition-colors">
                Menu
              </Link>
              <Link href="/cart" className="text-gray-700 dark:text-gray-200 hover:text-[#a97456] dark:hover:text-[#a97456] font-medium transition-colors">
                Cart
              </Link>
              <Link href="/about" className="text-gray-700 dark:text-gray-200 hover:text-[#a97456] dark:hover:text-[#a97456] font-medium transition-colors">
                About
              </Link>
              <Link href="/profile" className="text-gray-700 dark:text-gray-200 hover:text-[#a97456] dark:hover:text-[#a97456] font-medium transition-colors">
                Profile
              </Link>
            </nav>

            <div className="flex items-center space-x-2 sm:space-x-4">
              <ThemeToggle />

              {/* Notification Bell - only for authenticated users */}
              <NotificationBell />

              <Link href="/wishlist" className="relative p-2 text-gray-700 hover:text-[#a97456] dark:text-gray-300 dark:hover:text-[#a97456]">
                <i className="bi bi-heart text-xl"></i>
                {mounted && wishlistStore.items.length > 0 && (
                  <span className="absolute -top-1 -right-1 bg-[#a97456] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                    {wishlistStore.items.length}
                  </span>
                )}
              </Link>

              <button
                onClick={() => setShowCart(!showCart)}
                className="relative p-2 text-gray-700 hover:text-[#a97456] dark:text-gray-300 dark:hover:text-[#a97456]"
                suppressHydrationWarning={true}
              >
                <i className="bi bi-cart text-xl"></i>
                {mounted && state.itemCount > 0 && (
                  <span className="absolute -top-1 -right-1 bg-[#a97456] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                    {state.itemCount}
                  </span>
                )}
              </button>

              {mounted && isAuthenticated ? (
                <div className="relative">
                  <button 
                    onClick={() => setShowProfileMenu(!showProfileMenu)}
                    className="flex items-center space-x-2 text-gray-700 hover:text-[#a97456] dark:text-gray-300 dark:hover:text-[#a97456]"
                  >
                    {user?.profilePicture ? (
                        <img src={user.profilePicture} alt={user.name} className="w-8 h-8 rounded-full object-cover border border-gray-200" />
                    ) : (
                        <div className="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-[#a97456] font-bold text-sm">
                            {user?.name?.charAt(0) || 'U'}
                        </div>
                    )}
                  </button>
                  
                  {showProfileMenu && (
                    <div className="absolute right-0 mt-2 w-48 bg-white dark:bg-[#2a2a2a] rounded-xl shadow-lg py-1 border border-gray-100 dark:border-gray-700 z-50 animate-in fade-in zoom-in-95 duration-200">
                      <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                        <p className="text-sm font-medium text-gray-900 dark:text-white truncate">{user?.name}</p>
                        <p className="text-xs text-gray-500 dark:text-gray-400 truncate">{user?.email}</p>
                      </div>
                      
                      {user?.role === 'admin' && (
                        <Link href="/admin/dashboard" className="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-amber-50 dark:hover:bg-gray-700 hover:text-[#a97456]">
                          Admin Dashboard
                        </Link>
                      )}
                      
                      <Link href="/profile" className="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-amber-50 dark:hover:bg-gray-700 hover:text-[#a97456]">
                        My Profile
                      </Link>
                      <Link href="/orders" className="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-amber-50 dark:hover:bg-gray-700 hover:text-[#a97456]">
                        My Orders
                      </Link>
                      
                      <button 
                        onClick={() => {
                            logout();
                            setShowProfileMenu(false);
                            window.location.href = '/';
                        }}
                        className="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                      >
                        Sign Out
                      </button>
                    </div>
                  )}
                </div>
              ) : (
                <Link href="/login" className="text-gray-700 hover:text-[#a97456] dark:text-gray-300 dark:hover:text-[#a97456]">
                  <i className="bi bi-person text-xl"></i>
                </Link>
              )}
              
              <button 
                  onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                  className="md:hidden p-2 text-gray-700 hover:text-[#a97456] dark:text-gray-300 dark:hover:text-[#a97456] transition-colors"
                  aria-label="Toggle mobile menu"
               >
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {isMobileMenuOpen ? (
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    ) : (
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                    )}
                  </svg>
               </button>
            </div>
          </div>
        </div>

        {/* Mobile Menu Dropdown */}
        {isMobileMenuOpen && (
          <div className="md:hidden border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-[#2a2a2a] absolute w-full left-0 z-50 shadow-lg animate-in slide-in-from-top-5 duration-200">
            <nav className="flex flex-col px-4 py-4 space-y-2">
              <Link 
                href="/" 
                className="block px-4 py-3 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-[#a97456] rounded-xl transition-all"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Home
              </Link>
              <Link 
                href="/menu" 
                className="block px-4 py-3 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-[#a97456] rounded-xl transition-all"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Menu
              </Link>
              <Link 
                href="/cart" 
                className="block px-4 py-3 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-[#a97456] rounded-xl transition-all"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Cart
              </Link>
              <Link 
                href="/wishlist" 
                className="block px-4 py-3 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-[#a97456] rounded-xl transition-all"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Wishlist
              </Link>
               <Link 
                href="/profile" 
                className="block px-4 py-3 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-[#a97456] rounded-xl transition-all"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Profile
              </Link>
               <Link 
                href="/about" 
                className="block px-4 py-3 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-[#a97456] rounded-xl transition-all"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                About Us
              </Link>
            </nav>
          </div>
        )}
      </header>

      {showCart && <CartSidebar />}
    </>
  );
}
