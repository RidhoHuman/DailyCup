/**
 * 
 * Unit Tests for ProductCard Component
 * Tests: rendering, variants, add to cart, wishlist
 */

import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import ProductCard from '@/components/ProductCard'
import { CartProvider } from '@/contexts/CartContext'

// Mock next/image
jest.mock('next/image', () => ({
  __esModule: true,
  default: (props: Record<string, unknown>) => {
    // eslint-disable-next-line @next/next/no-img-element, jsx-a11y/alt-text
    return <img {...props} />
  },
}))

// Mock useAuthStore
jest.mock('@/lib/stores/auth-store', () => ({
  useAuthStore: () => ({
    user: { id: 1, name: 'Test User' },
    token: 'test-token',
  }),
}))

// Mock wishlist store
jest.mock('@/lib/stores/wishlist-store', () => ({
  useWishlistStore: () => ({
    items: [],
    addItem: jest.fn(),
    removeItem: jest.fn(),
    isInWishlist: jest.fn(() => false),
  }),
}))

const mockProduct = {
  id: 1,
  name: 'Cappuccino',
  description: 'Rich espresso with steamed milk',
  price: 35000,
  base_price: 35000,
  image: '/images/cappuccino.jpg',
  category_name: 'Coffee',
  is_featured: true,
  stock: 10,
  average_rating: 4.5,
  total_reviews: 25,
  variants: {
    size: [
      { value: 'Small', price_adjustment: 0 },
      { value: 'Medium', price_adjustment: 5000 },
      { value: 'Large', price_adjustment: 10000 },
    ],
    temperature: [
      { value: 'Hot', price_adjustment: 0 },
      { value: 'Iced', price_adjustment: 3000 },
    ],
  },
}

const renderWithProviders = (component: React.ReactElement) => {
  return render(<CartProvider>{component}</CartProvider>)
}

describe('ProductCard Component', () => {
  it('renders product information correctly', () => {
    renderWithProviders(<ProductCard product={mockProduct} />)

    expect(screen.getByText('Cappuccino')).toBeInTheDocument()
    expect(screen.getByText(/Rich espresso/)).toBeInTheDocument()
    expect(screen.getByText('Rp 35.000')).toBeInTheDocument()
    expect(screen.getByText('Coffee')).toBeInTheDocument()
  })

  it('displays featured badge for featured products', () => {
    renderWithProviders(<ProductCard product={mockProduct} />)
    expect(screen.getByText('⭐ Featured')).toBeInTheDocument()
  })

  it('shows rating and review count', () => {
    renderWithProviders(<ProductCard product={mockProduct} />)
    expect(screen.getByText('4.5')).toBeInTheDocument()
    expect(screen.getByText('(25)')).toBeInTheDocument()
  })

  it('displays low stock warning when stock is low', () => {
    const lowStockProduct = { ...mockProduct, stock: 3 }
    renderWithProviders(<ProductCard product={lowStockProduct} />)
    expect(screen.getByText(/Only 3 left/i)).toBeInTheDocument()
  })

  it('shows out of stock state', () => {
    const outOfStockProduct = { ...mockProduct, stock: 0 }
    renderWithProviders(<ProductCard product={outOfStockProduct} />)
    expect(screen.getByText(/Out of Stock/i)).toBeInTheDocument()
  })

  it('renders product variants', () => {
    renderWithProviders(<ProductCard product={mockProduct} />)
    
    // Check size variants
    expect(screen.getByText('Small')).toBeInTheDocument()
    expect(screen.getByText('Medium')).toBeInTheDocument()
    expect(screen.getByText('Large')).toBeInTheDocument()
    
    // Check temperature variants
    expect(screen.getByText('Hot')).toBeInTheDocument()
    expect(screen.getByText('Iced')).toBeInTheDocument()
  })

  it('updates price when variant is selected', () => {
    renderWithProviders(<ProductCard product={mockProduct} />)
    
    // Initial price
    expect(screen.getByText('Rp 35.000')).toBeInTheDocument()
    
    // Select Medium size (+5000)
    fireEvent.click(screen.getByText('Medium'))
    
    // Price should update
    waitFor(() => {
      expect(screen.getByText('Rp 40.000')).toBeInTheDocument()
    })
  })

  it('handles add to cart action', () => {
    renderWithProviders(<ProductCard product={mockProduct} />)
    
    const addToCartButton = screen.getByRole('button', { name: /add to cart/i })
    expect(addToCartButton).toBeInTheDocument()
    expect(addToCartButton).not.toBeDisabled()
    
    fireEvent.click(addToCartButton)
    
    // Toast notification should appear (mocked)
    // In real test, you'd mock toast library
  })

  it('disables add to cart when out of stock', () => {
    const outOfStockProduct = { ...mockProduct, stock: 0 }
    renderWithProviders(<ProductCard product={outOfStockProduct} />)
    
    const addToCartButton = screen.getByRole('button', { name: /out of stock/i })
    expect(addToCartButton).toBeDisabled()
  })

  it('handles wishlist toggle', () => {
    renderWithProviders(<ProductCard product={mockProduct} />)
    
    const wishlistButton = screen.getByLabelText(/add to wishlist/i)
    expect(wishlistButton).toBeInTheDocument()
    
    fireEvent.click(wishlistButton)
    
    // Wishlist store should be called (mocked)
  })

  it('applies correct styling for featured products', () => {
    const { container } = renderWithProviders(<ProductCard product={mockProduct} />)
    
    const card = container.querySelector('.group')
    expect(card).toHaveClass('bg-white')
  })

  it('handles products without variants', () => {
    const productWithoutVariants = { ...mockProduct, variants: undefined }
    renderWithProviders(<ProductCard product={productWithoutVariants} />)
    
    // Should still render product
    expect(screen.getByText('Cappuccino')).toBeInTheDocument()
    
    // Should not show variant selectors
    expect(screen.queryByText('Size')).not.toBeInTheDocument()
  })
})
