import { NextRequest, NextResponse } from 'next/server';

const BACKEND_URL = process.env.BACKEND_URL || 'http://localhost/DailyCup/webapp/backend/api';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { code } = body;

    if (!code) {
      return NextResponse.json(
        { success: false, message: 'Kode undangan wajib diisi' },
        { status: 400 }
      );
    }

    // Verify invitation code with backend
    const response = await fetch(`${BACKEND_URL}/kurir_verify_invitation.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ invitation_code: code }),
    });

    const data = await response.json();
    return NextResponse.json(data, { status: response.status });
  } catch (error) {
    console.error('Verify invitation error:', error);
    return NextResponse.json(
      { success: false, message: 'Gagal memverifikasi kode undangan' },
      { status: 500 }
    );
  }
}
