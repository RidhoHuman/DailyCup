import { NextRequest, NextResponse } from 'next/server';

export async function GET(request: NextRequest) {
  const { searchParams } = new URL(request.url);
  const orderId = searchParams.get('orderId');

  if (!orderId) {
    return NextResponse.json({ success: false, message: 'Order ID required' }, { status: 400 });
  }

  try {
    const apiUrl = process.env.BACKEND_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost/DailyCup/webapp/backend/api';
    const response = await fetch(`${apiUrl}/sync_xendit_status.php?orderId=${encodeURIComponent(orderId)}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    const data = await response.json();
    return NextResponse.json(data);
  } catch (error) {
    console.error('Sync Xendit error:', error);
    return NextResponse.json({ success: false, message: 'Failed to sync with Xendit' }, { status: 500 });
  }
}
