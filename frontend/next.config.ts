import type { NextConfig } from 'next'

// Updated: February 3, 2026 - Latest deployment with all fixes
const nextConfig: NextConfig = {
  // Image optimization
  images: {
    unoptimized: true, // Disable optimization to fix JFIF 400 errors on Vercel
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'decagonal-subpolygonally-brecken.ngrok-free.dev',
        pathname: '/DailyCup/webapp/backend/uploads/**',
      },
      {
        protocol: 'https',
        hostname: 'decagonal-subpolygonally-brecken.ngrok-free.dev',
        pathname: '/**',
      },

    ],
    formats: ['image/avif', 'image/webp'],
    deviceSizes: [640, 750, 828, 1080, 1200, 1920, 2048, 3840],
    imageSizes: [16, 32, 48, 64, 96, 128, 256, 384],
    // Allow JFIF files (treated as JPEG)
    dangerouslyAllowSVG: true,
  },

  // Compression
  compress: true,

  // React strict mode
  reactStrictMode: true,

  // Generate static pages where possible
  output: 'standalone',

  // Headers for security and caching
  async headers() {
    return [
      {
        source: '/:all*(svg|jpg|jpeg|png|gif|webp|avif)',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=31536000, immutable',
          },
        ],
      },
      {
        source: '/:all*(woff|woff2|ttf|otf)',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=31536000, immutable',
          },
        ],
      },
      {
        source: '/sw.js',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=0, must-revalidate',
          },
          {
            key: 'Service-Worker-Allowed',
            value: '/',
          },
        ],
      },
    ]
  },

  // Rewrites for API proxy
  // Using ngrok tunnel to local Laragon backend
  // Ngrok serves from Laragon root, so path is relative to www folder
  async rewrites() {
    let apiUrl = process.env.NEXT_PUBLIC_API_URL || 'https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api';
    
    // FIX: Remove duplicate https:// if exists (common mistake in env vars)
    // Converts "https://https//..." to "https://..."
    apiUrl = apiUrl.replace(/^(https?:\/\/)(https?:?\/\/)+/i, '$1');
    
    console.log('[Next.js] API Rewrite URL:', apiUrl);
    
    return [
      {
        source: '/api/:path*',
        destination: `${apiUrl}/:path*`,
      },
    ]
  },
}

export default nextConfig
