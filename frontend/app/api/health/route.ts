/**
 * Health Check Endpoint
 * Tests if backend API is reachable from Vercel
 */

import { NextResponse } from 'next/server';

export const runtime = 'nodejs';

export async function GET() {
  const BACKEND_URL = process.env.NEXT_PUBLIC_API_URL || 'https://api.dailycup.com';
  
  const results = {
    backend_url: BACKEND_URL,
    timestamp: new Date().toISOString(),
    tests: {} as Record<string, any>
  };

  // Test 1: Basic connectivity
  try {
    const response = await fetch(`${BACKEND_URL}/products.php`, {
      method: 'GET',
      headers: { 'Content-Type': 'application/json' }
    });
    
    const data = await response.json();
    
    results.tests.products = {
      status: response.status,
      ok: response.ok,
      success: data.success || false,
      productsCount: data.data?.length || 0,
      error: null
    };
  } catch (error) {
    results.tests.products = {
      status: 0,
      ok: false,
      error: error instanceof Error ? error.message : 'Unknown error'
    };
  }

  // Test 2: CORS headers
  try {
    const response = await fetch(`${BACKEND_URL}/categories.php`);
    results.tests.cors = {
      'access-control-allow-origin': response.headers.get('access-control-allow-origin'),
      'access-control-allow-methods': response.headers.get('access-control-allow-methods'),
    };
  } catch (error) {
    results.tests.cors = {
      error: error instanceof Error ? error.message : 'Unknown error'
    };
  }

  return NextResponse.json(results, {
    headers: {
      'Cache-Control': 'no-store, no-cache, must-revalidate',
    }
  });
}
