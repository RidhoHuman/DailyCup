import type { NextConfig } from 'next'

// Updated: February 4, 2026 - Force deployment trigger
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
    
    // Derived backend root for file proxy
    // Goal: Get the base URL that maps to the 'webapp' folder
    // Input: .../webapp/backend/api
    // Output: .../webapp
    
    let backendRoot = apiUrl;
    if (apiUrl.endsWith('/api')) {
      backendRoot = backendRoot.slice(0, -4); // Remove /api
    }
    if (backendRoot.endsWith('/backend')) {
      backendRoot = backendRoot.slice(0, -8); // Remove /backend
    }
    // Remove trailing slash if present
    if (backendRoot.endsWith('/')) {
        backendRoot = backendRoot.slice(0, -1);
    }

    // FIX: Remove duplicate https:// if exists (common mistake in env vars)
    // Converts "https://https//..." to "https://..."
    apiUrl = apiUrl.replace(/^(https?:\/\/)(https?:?\/\/)+/i, '$1');
    backendRoot = backendRoot.replace(/^(https?:\/\/)(https?:?\/\/)+/i, '$1');
    
    console.log('[Next.js] API Rewrite URL:', apiUrl);
    console.log('[Next.js] Uploads Rewrite Root:', backendRoot);
    
    return [
      {
        source: '/api/:path*',
        destination: `${apiUrl}/:path*`,
      },
      {
        source: '/uploads/:path*',
        destination: `${backendRoot}/uploads/:path*`,
      },
      // Local fallback if structure differs
      {
        source: '/DailyCup/webapp/uploads/:path*',
        destination: `${backendRoot}/uploads/:path*`,
      }
    ]
  },
}

export default nextConfig
