import type { Metadata } from "next";
import { Poppins, Russo_One, Quantico, Itim } from "next/font/google";
import { Providers } from "./providers";
import OfflineBanner from "@/components/OfflineBanner";
import PWAInstallPrompt from "@/components/PWAInstallPrompt";
import UpdatePrompt from "@/components/UpdatePrompt";
import "./globals.css";

// Force deployment trigger

const poppins = Poppins({
  subsets: ["latin"],
  weight: ["300", "400", "600"],
  variable: "--font-poppins",
});

const russoOne = Russo_One({
  subsets: ["latin"],
  weight: ["400"],
  variable: "--font-russo-one",
});

const quantico = Quantico({
  subsets: ["latin"],
  weight: ["400", "700"],
  variable: "--font-quantico",
});

const itim = Itim({
  subsets: ["latin"],
  weight: ["400"],
  variable: "--font-itim",
});

export const metadata: Metadata = {
  title: {
    default: "DailyCup — Discover Your Perfect Cup",
    template: "%s | DailyCup",
  },
  description: "Where every pour meets precision and every sip tells a story of excellence. Order premium coffee online with fast delivery.",
  applicationName: "DailyCup",
  keywords: ["coffee", "cafe", "espresso", "cappuccino", "latte", "coffee delivery", "artisan coffee", "specialty coffee", "online coffee shop"],
  authors: [{ name: "DailyCup Team" }],
  creator: "DailyCup",
  publisher: "DailyCup",
  metadataBase: new URL(process.env.NEXT_PUBLIC_APP_URL || 'https://dailycup.com'),
  alternates: {
    canonical: '/',
  },
  openGraph: {
    type: 'website',
    locale: 'id_ID',
    url: '/',
    siteName: 'DailyCup',
    title: 'DailyCup — Discover Your Perfect Cup',
    description: 'Where every pour meets precision and every sip tells a story of excellence. Order premium coffee online with fast delivery.',
    images: [
      {
        url: '/assets/image/og-image.jpg',
        width: 1200,
        height: 630,
        alt: 'DailyCup - Premium Coffee Delivery',
      },
    ],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'DailyCup — Discover Your Perfect Cup',
    description: 'Where every pour meets precision and every sip tells a story of excellence.',
    images: ['/assets/image/og-image.jpg'],
    creator: '@dailycup',
  },
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      'max-video-preview': -1,
      'max-image-preview': 'large',
      'max-snippet': -1,
    },
  },
  icons: {
    icon: "/assets/image/cup.png",
    apple: "/assets/image/cup.png",
  },
  manifest: "/manifest.json",
  appleWebApp: {
    capable: true,
    statusBarStyle: "default",
    title: "DailyCup",
  },
  verification: {
    google: 'your-google-verification-code', // Add after Google Search Console setup
  },
  formatDetection: {
    telephone: false,
  },
  viewport: {
    width: "device-width",
    initialScale: 1,
    maximumScale: 5,
    userScalable: true,
  },
  themeColor: [
    { media: "(prefers-color-scheme: light)", color: "#a15e3f" },
    { media: "(prefers-color-scheme: dark)", color: "#1a1a1a" },
  ],
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="id" suppressHydrationWarning>
      <head>
        <link rel="icon" type="image/png" sizes="80x80" href="/assets/image/cup.png?v=1" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
        <meta name="theme-color" content="#a97456" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="default" />
        <meta name="apple-mobile-web-app-title" content="DailyCup" />
        {/* Structured Data (JSON-LD) */}
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{
            __html: JSON.stringify({
              "@context": "https://schema.org",
              "@type": "Restaurant",
              "name": "DailyCup",
              "description": "Premium coffee delivery service offering artisan espresso, specialty drinks, and quality beverages delivered to your door",
              "url": process.env.NEXT_PUBLIC_APP_URL || "https://dailycup.com",
              "logo": `${process.env.NEXT_PUBLIC_APP_URL || "https://dailycup.com"}/assets/image/cup.png`,
              "image": `${process.env.NEXT_PUBLIC_APP_URL || "https://dailycup.com"}/assets/image/og-image.jpg`,
              "servesCuisine": "Coffee & Beverages",
              "priceRange": "$$",
              "telephone": "+62-812-3456-7890",
              "email": "support@dailycup.com",
              "address": {
                "@type": "PostalAddress",
                "streetAddress": "Jl. Kopi Nusantara No. 123",
                "addressLocality": "Jakarta Selatan",
                "addressRegion": "DKI Jakarta",
                "postalCode": "12345",
                "addressCountry": "ID"
              },
              "openingHoursSpecification": [
                {
                  "@type": "OpeningHoursSpecification",
                  "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
                  "opens": "07:00",
                  "closes": "22:00"
                },
                {
                  "@type": "OpeningHoursSpecification",
                  "dayOfWeek": ["Saturday", "Sunday"],
                  "opens": "08:00",
                  "closes": "23:00"
                }
              ],
              "sameAs": [
                "https://www.instagram.com/dailycup.id",
                "https://www.facebook.com/dailycup.id"
              ]
            })
          }}
        />
      </head>
      <body
        className={`${poppins.variable} ${russoOne.variable} ${quantico.variable} ${itim.variable} antialiased`}
        style={{
          fontFamily: 'var(--font-poppins), system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial',
        }}
      >
        <Providers>
          <OfflineBanner />
          <UpdatePrompt />
          <PWAInstallPrompt />
          {children}
        </Providers>
      </body>
    </html>
  );
}
