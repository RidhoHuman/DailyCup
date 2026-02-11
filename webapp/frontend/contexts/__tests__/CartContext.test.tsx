/**
 * Unit Tests for Cart Context
 * Tests: add item, remove item, update quantity, clear cart, localStorage persistence
 */

import { renderHook, act } from '@testing-library/react'
import { CartProvider, useCart } from '@/contexts/CartContext'
import { ReactNode } from 'react'

// Mock localStorage
const localStorageMock = (() => {
  let store: { [key: string]: string } = {}

  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value.toString()
    },
    removeItem: (key: string) => {
      delete store[key]
    },
    clear: () => {
      store = {}
    },
  }
})()

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock,
})

const mockProduct = {
  id: 1,
  name: 'Cappuccino',
  base_price: 35000,
  description: 'Rich espresso',
  image: '/images/cappuccino.jpg',
  is_featured: true,
  stock: 10,
  category_name: 'Coffee',
  variants: {},
}

const wrapper = ({ children }: { children: ReactNode }) => (
  <CartProvider>{children}</CartProvider>
)

describe('Cart Context', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('initializes with empty cart', () => {
    const { result } = renderHook(() => useCart(), { wrapper })

    expect(result.current.items).toEqual([])
    expect(result.current.total).toBe(0)
    expect(result.current.itemCount).toBe(0)
  })

  it('adds item to cart', () => {
    const { result } = renderHook(() => useCart(), { wrapper })

    act(() => {
      result.current.addItem(mockProduct, {}, 1)
    })

    expect(result.current.items).toHaveLength(1)
    expect(result.current.items[0].product.name).toBe('Cappuccino')
    expect(result.current.items[0].quantity).toBe(1)
    expect(result.current.itemCount).toBe(1)
  })

  it('increases quantity when adding same item', () => {
    const { result } = renderHook(() => useCart(), { wrapper })

    act(() => {
      result.current.addItem(mockProduct, {}, 1)
      result.current.addItem(mockProduct, {}, 1)
    })

    expect(result.current.items).toHaveLength(1)
    expect(result.current.items[0].quantity).toBe(2)
    expect(result.current.itemCount).toBe(2)
  })

  it('adds items with different variants separately', () => {
    const { result } = renderHook(() => useCart(), { wrapper })

    act(() => {
      result.current.addItem(mockProduct, { size: 'Small' }, 1)
      result.current.addItem(mockProduct, { size: 'Large' }, 1)
    })

    expect(result.current.items).toHaveLength(2)
    expect(result.current.itemCount).toBe(2)
  })

  it('removes item from cart', () => {
    const { result } = renderHook(() => useCart(), { wrapper })

    act(() => {
      result.current.addItem(mockProduct, {}, 1)
    })

    const itemId = result.current.items[0].id

    act(() => {
      result.current.removeItem(itemId)
    })

    expect(result.current.items).toHaveLength(0)
    expect(result.current.itemCount).toBe(0)
  })

  it('updates item quantity', () => {
    const { result } = renderHook(() => useCart(), { wrapper })

    act(() => {
      result.current.addItem(mockProduct, {}, 1)
    })

    const itemId = result.current.items[0].id

    act(() => {
      result.current.updateQuantity(itemId, 5)
    })

    expect(result.current.items[0].quantity).toBe(5)
    expect(result.current.itemCount).toBe(5)
  })

  it('removes item when quantity set to 0', () => {
    const { result } = renderHook(() => useCart(), { wrapper })

    act(() => {
      result.current.addItem(mockProduct, {}, 1)
    })

    const itemId = result.current.items[0].id

    act(() => {
      result.current.updateQuantity(itemId, 0)
    })

    expect(result.current.items).toHaveLength(0)
  })

  it('clears entire cart', () => {
    const { result } = renderHook(() => useCart(), { wrapper })

    act(() => {
      result.current.addItem(mockProduct, {}, 1)
      result.current.addItem({ ...mockProduct, id: 2, name: 'Latte' }, {}, 1)
    })

    expect(result.current.items).toHaveLength(2)

    act(() => {
      result.current.clearCart()
    })

    expect(result.current.items).toHaveLength(0)
    expect(result.current.total).toBe(0)
  })

  it('calculates total correctly', () => {
    const { result } = renderHook(() => useCart(), { wrapper })

    act(() => {
      result.current.addItem(mockProduct, {}, 2) // 35000 * 2 = 70000
    })

    expect(result.current.total).toBe(70000)
  })

  it('persists cart to localStorage', () => {
    const { result } = renderHook(() => useCart(), { wrapper })

    act(() => {
      result.current.addItem(mockProduct, {}, 1)
    })

    const savedCart = localStorage.getItem('dailycup_cart')
    expect(savedCart).toBeTruthy()

    const parsedCart = JSON.parse(savedCart!)
    expect(parsedCart).toHaveLength(1)
    expect(parsedCart[0].product.name).toBe('Cappuccino')
  })

  it('loads cart from localStorage on mount', () => {
    // Pre-populate localStorage
    const cartData = [{
      id: 'test-id',
      product: mockProduct,
      selectedVariants: {},
      quantity: 3,
      price: 35000,
    }]
    localStorage.setItem('dailycup_cart', JSON.stringify(cartData))

    const { result } = renderHook(() => useCart(), { wrapper })

    expect(result.current.items).toHaveLength(1)
    expect(result.current.items[0].quantity).toBe(3)
    expect(result.current.itemCount).toBe(3)
  })

  it('handles multiple items correctly', () => {
    const { result } = renderHook(() => useCart(), { wrapper })

    const product2 = { ...mockProduct, id: 2, name: 'Latte', base_price: 32000 }
    const product3 = { ...mockProduct, id: 3, name: 'Espresso', base_price: 25000 }

    act(() => {
      result.current.addItem(mockProduct, {}, 2)
      result.current.addItem(product2, {}, 1)
      result.current.addItem(product3, {}, 3)
    })

    expect(result.current.items).toHaveLength(3)
    expect(result.current.itemCount).toBe(6)
    
    // Total: (35000 * 2) + (32000 * 1) + (25000 * 3) = 177000
    expect(result.current.total).toBe(177000)
  })
})
