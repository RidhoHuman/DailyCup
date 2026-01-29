'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';

interface AdminSidebarProps {
  isOpen?: boolean;
  onClose?: () => void;
}

export default function AdminSidebar({ isOpen = false, onClose }: AdminSidebarProps) {
  const pathname = usePathname();
  const router = useRouter();

  const handleLogout = () => {
    // Clear authentication
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    
    // Redirect to main customer storefront
    router.push('/');
  };

  const menuItems = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: 'bi-speedometer2' },
    { name: 'Orders', href: '/admin/orders', icon: 'bi-bag-check' },
    { name: 'Products', href: '/admin/products', icon: 'bi-cup-hot' },
    { name: 'Customers', href: '/admin/customers', icon: 'bi-people' },
    { name: 'Analytics', href: '/admin/analytics', icon: 'bi-graph-up' },
    { name: 'Settings', href: '/admin/settings', icon: 'bi-gear' },
  ];

  return (
    <>
      {/* Mobile Overlay */}
      {isOpen && (
        <div 
            className="fixed inset-0 bg-black/50 z-20 md:hidden glass-effect"
            onClick={onClose}
        />
      )}

      {/* Sidebar */}
      <div className={`
        fixed md:static inset-y-0 left-0 z-30
        w-64 bg-[#1a1a1a] text-white flex-shrink-0 min-h-screen 
        transform transition-transform duration-300 ease-in-out
        ${isOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'}
      `}>
      <div className="p-6 flex items-center justify-between gap-3">
        <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-[#a97456] rounded-lg flex items-center justify-center text-white font-bold font-['Russo_One'] shadow-sm">
                DC
            </div>
            <div>
                <h1 className="text-lg font-bold font-['Russo_One'] tracking-wide">DailyCup</h1>
                <p className="text-xs text-gray-500 uppercase tracking-wider">Admin Panel</p>
            </div>
        </div>
        {/* Mobile Close Button */}
        <button onClick={onClose} className="md:hidden text-gray-400 hover:text-white">
            <i className="bi bi-x-lg text-xl"></i>
        </button>
      </div>

      <nav className="mt-6 px-4 space-y-2">
        {menuItems.map((item) => {
          const isActive = pathname.startsWith(item.href);
          return (
            <Link
              key={item.href}
              href={item.href}
              onClick={() => onClose?.()} // Close sidebar on mobile nav click
              className={`flex items-center px-4 py-3 rounded-xl transition-all duration-200 group ${
                isActive
                  ? 'bg-[#a97456] text-white shadow-lg shadow-[#a97456]/20'
                  : 'text-gray-400 hover:bg-[#333] hover:text-white'
              }`}
            >
              <i className={`bi ${item.icon} text-lg mr-3 ${isActive ? 'text-white' : 'text-gray-500 group-hover:text-white'}`}></i>
              <span className="font-medium">{item.name}</span>
            </Link>
          );
        })}
      </nav>

      <div className="absolute bottom-0 left-0 right-0 p-4 md:block hidden">
        <div className="bg-[#2a2a2a] rounded-xl p-4 flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center overflow-hidden">
                 <i className="bi bi-person-fill text-gray-400 text-xl"></i>
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-white truncate">Admin User</p>
                <p className="text-xs text-gray-400 truncate">admin@dailycup.com</p>
            </div>
            <button 
              onClick={handleLogout}
              className="text-gray-400 hover:text-red-400 transition-colors"
              title="Logout"
            >
              <i className="bi bi-box-arrow-right text-lg"></i>
            </button>
        </div>
      </div>
    </div>
    </>
  );
}
