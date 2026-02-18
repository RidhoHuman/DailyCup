/** @type {import('next').NextConfig} */
const nextConfig = {
  typescript: {
    ignoreBuildErrors: true,
  },
  // Fix for cross-origin request warning in development
  allowedDevOrigins: [
    'localhost:3000',
    '127.0.0.1:3000',
    'localhost:4000',
    '127.0.0.1:4000',
  ],
  images: {
    // 👇 INI KUNCINYA: Matikan optimasi agar gambar langsung dimuat dari Ngrok tanpa diproses server Vercel
    unoptimized: true, 
    
    remotePatterns: [
      {
        protocol: 'https',
        hostname: '**.ngrok-free.dev',
      },
      {
        protocol: 'https',
        hostname: 'localhost',
      },
    ],
  },
};

module.exports = nextConfig;