import { ImageResponse } from 'next/og'

export const runtime = 'edge'

export const alt = 'DailyCup - Premium Coffee Delivery'
export const size = {
  width: 1200,
  height: 630,
}
export const contentType = 'image/png'

export default async function Image() {
  return new ImageResponse(
    (
      <div
        style={{
          fontSize: 128,
          background: 'linear-gradient(135deg, #2d1810 0%, #6f4e37 100%)',
          width: '100%',
          height: '100%',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          color: 'white',
          fontFamily: 'system-ui',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', marginBottom: 20 }}>
          <div
            style={{
              width: 120,
              height: 120,
              background: 'white',
              borderRadius: '50%',
              marginRight: 30,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontSize: 80,
            }}
          >
            ☕
          </div>
          <div style={{ fontSize: 100, fontWeight: 'bold' }}>DailyCup</div>
        </div>
        <div style={{ fontSize: 40, opacity: 0.9, textAlign: 'center', maxWidth: 900 }}>
          Discover Your Perfect Cup
        </div>
      </div>
    ),
    {
      ...size,
    }
  )
}
