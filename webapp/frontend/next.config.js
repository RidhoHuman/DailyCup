/** @type {import('next').NextConfig} */
const nextConfig = {
  typescript: {
    ignoreBuildErrors: true,
  },
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