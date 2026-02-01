/**
 * API Proxy Route
 * Proxies all /api/* requests to the PHP backend
 * This bypasses CORS and SSL certificate issues
 */

import { NextRequest, NextResponse } from 'next/server';

const BACKEND_URL = process.env.NEXT_PUBLIC_API_URL || 'https://api.dailycup.com';

export const runtime = 'nodejs'; // Use Node.js runtime, not Edge

export async function GET(
  request: NextRequest,
  context: { params: Promise<{ path: string[] }> }
) {
  const params = await context.params;
  return proxyRequest(request, params.path, 'GET');
}

export async function POST(
  request: NextRequest,
  context: { params: Promise<{ path: string[] }> }
) {
  const params = await context.params;
  return proxyRequest(request, params.path, 'POST');
}

export async function PUT(
  request: NextRequest,
  context: { params: Promise<{ path: string[] }> }
) {
  const params = await context.params;
  return proxyRequest(request, params.path, 'PUT');
}

export async function DELETE(
  request: NextRequest,
  context: { params: Promise<{ path: string[] }> }
) {
  const params = await context.params;
  return proxyRequest(request, params.path, 'DELETE');
}

export async function PATCH(
  request: NextRequest,
  context: { params: Promise<{ path: string[] }> }
) {
  const params = await context.params;
  return proxyRequest(request, params.path, 'PATCH');
}

export async function OPTIONS(
  request: NextRequest,
  context: { params: Promise<{ path: string[] }> }
) {
  return new NextResponse(null, {
    status: 204,
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Authorization',
      'Access-Control-Max-Age': '86400',
    },
  });
}

async function proxyRequest(
  request: NextRequest,
  pathSegments: string[],
  method: string
) {
  try {
    const path = pathSegments.join('/');
    const url = `${BACKEND_URL}/${path}`;
    
    // Get search params from original request
    const searchParams = request.nextUrl.searchParams.toString();
    const fullUrl = searchParams ? `${url}?${searchParams}` : url;

    // Forward headers
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
    };

    // Forward Authorization header if present
    const authHeader = request.headers.get('Authorization');
    if (authHeader) {
      headers['Authorization'] = authHeader;
    }

    // Get request body for POST/PUT/PATCH
    let body: string | undefined;
    if (['POST', 'PUT', 'PATCH'].includes(method)) {
      try {
        const text = await request.text();
        body = text || undefined;
      } catch (e) {
        // No body
      }
    }

    // Make request to backend
    // Note: Vercel's fetch will accept the SSL cert from api.dailycup.com
    const response = await fetch(fullUrl, {
      method,
      headers,
      body,
    });

    // Get response data
    const contentType = response.headers.get('content-type');
    let data;
    
    if (contentType?.includes('application/json')) {
      data = await response.json();
    } else {
      data = await response.text();
    }

    // Return response with CORS headers
    return NextResponse.json(data, {
      status: response.status,
      headers: {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
        'Access-Control-Allow-Headers': 'Content-Type, Authorization',
      },
    });

  } catch (error) {
    console.error('Proxy error:', error);
    return NextResponse.json(
      { 
        error: 'Proxy error', 
        message: error instanceof Error ? error.message : 'Unknown error' 
      },
      { status: 500 }
    );
  }
}
