// Track custom events in Google Analytics
export const trackEvent = (
  action: string,
  category: string,
  label?: string,
  value?: number
) => {
  if (typeof window !== 'undefined' && (window as any).gtag) {
    ;(window as any).gtag('event', action, {
      event_category: category,
      event_label: label,
      value: value,
    })
  }
}

// Track page views
export const trackPageView = (url: string) => {
  if (typeof window !== 'undefined' && (window as any).gtag) {
    ;(window as any).gtag('config', process.env.NEXT_PUBLIC_GA_ID, {
      page_path: url,
    })
  }
}

// E-commerce events
export interface GAItem { id?: string; name?: string; price?: number; quantity?: number; [key: string]: unknown }
export const trackPurchase = (transactionId: string, value: number, items: GAItem[]) => {
  if (typeof window !== 'undefined' && (window as any).gtag) {
    ;(window as any).gtag('event', 'purchase', {
      transaction_id: transactionId,
      value: value,
      currency: 'IDR',
      items: items,
    })
  }
}

export const trackAddToCart = (item: GAItem, value: number) => {
  if (typeof window !== 'undefined' && (window as any).gtag) {
    ;(window as any).gtag('event', 'add_to_cart', {
      currency: 'IDR',
      value: value,
      items: [item],
    })
  }
}

export const trackViewItem = (item: GAItem) => {
  if (typeof window !== 'undefined' && (window as any).gtag) {
    ;(window as any).gtag('event', 'view_item', {
      currency: 'IDR',
      value: (item.price as number) || 0,
      items: [item],
    })
  }
}

export const trackBeginCheckout = (value: number, items: GAItem[]) => {
  if (typeof window !== 'undefined' && (window as any).gtag) {
    ;(window as any).gtag('event', 'begin_checkout', {
      currency: 'IDR',
      value: value,
      items: items,
    })
  }
} 
