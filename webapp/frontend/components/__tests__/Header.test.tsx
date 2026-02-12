import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import Header from '../Header';
import { CartProvider, useCart } from '@/contexts/CartContext';

function AddToCartTester({ product }: { product: any }) {
  const { addItem } = useCart();
  return (
    <button onClick={() => addItem(product, {}, 1)}>Add (test)</button>
  );
}

const mockProduct = {
  id: '100',
  name: 'Test Coffee',
  price: 10000,
  variants: {},
  stock: 10,
};

describe('Header', () => {
  it('shows cart badge when item added', () => {
    render(
      <CartProvider>
        <Header />
        <AddToCartTester product={mockProduct} />
      </CartProvider>
    );

    // badge not present initially
    expect(screen.queryByTestId('cart-badge')).toBeNull();

    // add item and expect badge to appear
    fireEvent.click(screen.getByText('Add (test)'));

    const badge = screen.getByTestId('cart-badge');
    expect(badge).toBeInTheDocument();
    expect(badge).toHaveTextContent('1');
  });
});