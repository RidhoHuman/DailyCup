'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useAuthStore } from '@/lib/stores/auth-store';

export default function AdminLoginPage() {
  const router = useRouter();
  const { login } = useAuthStore();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      let apiUrl = process.env.NEXT_PUBLIC_API_URL || 'https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api';
      
      // FIX: Remove duplicate https:// if exists (common mistake in env vars)
      apiUrl = apiUrl.replace(/^(https?:\/\/)(https?:?\/\/)+/i, '$1');
      
      const response = await fetch(`${apiUrl}/login.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'ngrok-skip-browser-warning': '69420' // Bypass ngrok browser warning
        },
        body: JSON.stringify({ email, password })
      });

      const data = await response.json();

      if (response.ok && data.success) {
        // Check if user is admin
        if (data.user.role !== 'admin') {
          setError('Access denied. Admin privileges required.');
          setLoading(false);
          return;
        }

        // Login successful and user is admin
        login(data.user, data.token);
        router.push('/admin/dashboard');
      } else {
        setError(data.message || 'Invalid credentials. Please try again.');
        setLoading(false);
      }
    } catch (err) {
      console.error('Login error:', err);
      setError('Connection error. Please check if the server is running.');
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#1a1a1a] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <div className="flex justify-center">
            <div className="w-16 h-16 bg-[#a97456] rounded-full flex items-center justify-center text-white text-2xl font-bold font-['Russo_One'] shadow-lg border-4 border-[#2a2a2a]">
                DC
            </div>
        </div>
        <h2 className="mt-6 text-center text-3xl font-extrabold text-white font-['Russo_One']">
          Admin Portal
        </h2>
        <p className="mt-2 text-center text-sm text-gray-400">
          DailyCup Internal Management System
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div className="bg-[#2a2a2a] py-8 px-4 shadow-xl sm:rounded-lg sm:px-10 border border-gray-700">
          <form className="space-y-6" onSubmit={handleLogin}>
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-300">
                Email address
              </label>
              <div className="mt-1">
                <input
                  id="email"
                  name="email"
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="appearance-none block w-full px-3 py-2 border border-gray-600 rounded-md shadow-sm placeholder-gray-500 bg-[#333] text-white focus:outline-none focus:ring-[#a97456] focus:border-[#a97456] sm:text-sm"
                />
              </div>
            </div>

            <div>
              <label htmlFor="password" className="block text-sm font-medium text-gray-300">
                Password
              </label>
              <div className="mt-1">
                <input
                  id="password"
                  name="password"
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="appearance-none block w-full px-3 py-2 border border-gray-600 rounded-md shadow-sm placeholder-gray-500 bg-[#333] text-white focus:outline-none focus:ring-[#a97456] focus:border-[#a97456] sm:text-sm"
                />
              </div>
            </div>

            {error && (
              <div className="rounded-md bg-red-900/30 p-4 border border-red-800">
                <div className="flex">
                  <div className="flex-shrink-0">
                    <i className="bi bi-exclamation-circle text-red-400"></i>
                  </div>
                  <div className="ml-3">
                    <h3 className="text-sm font-medium text-red-400">{error}</h3>
                  </div>
                </div>
              </div>
            )}

            <div>
              <button
                type="submit"
                disabled={loading}
                className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#a97456] hover:bg-[#8f6249] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#a97456] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {loading ? 'Signing in...' : 'Sign in'}
              </button>
            </div>
          </form>

          <div className="mt-6">
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-600" />
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-[#2a2a2a] text-gray-400">
                  Or go back to
                </span>
              </div>
            </div>

            <div className="mt-6 text-center">
               <Link href="/" className="text-[#a97456] hover:text-[#8f6249] font-medium text-sm">
                 Customer Storefront
               </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
