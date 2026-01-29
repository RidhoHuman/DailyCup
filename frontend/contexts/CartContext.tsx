'use client';

import React, { createContext, useContext, useReducer, useEffect, ReactNode } from 'react';
import { Product, CartItem } from '../utils/api';

interface CartState {
  items: CartItem[];
  total: number;
  itemCount: number;
}

type CartAction =
  | { type: 'ADD_ITEM'; payload: { product: Product; selectedVariants?: Record<string,string>; quantity?: number } }
  | { type: 'REMOVE_ITEM'; payload: { id: string } }
  | { type: 'UPDATE_QUANTITY'; payload: { id: string; quantity: number } }
  | { type: 'CLEAR_CART' }
  | { type: 'LOAD_CART'; payload: CartItem[] };
const initialState: CartState = {
  items: [],
  total: 0,
  itemCount: 0,
};

function cartReducer(state: CartState, action: CartAction): CartState {
  switch (action.type) {
    case 'ADD_ITEM': {
      const { product, selectedVariants = {}, quantity = 1 } = action.payload;

      // Build a deterministic itemId from product + selected variant values
      const variantKey = Object.keys(selectedVariants).sort().map(k => `${k}:${selectedVariants[k]}`).join('|');
      const itemId = `${product.id}-${variantKey}`;
      const existingItem = state.items.find(item => item.id === itemId);

      let newItems;
      if (existingItem) {
        newItems = state.items.map(item =>
          item.id === itemId
            ? { ...item, quantity: item.quantity + quantity, totalPrice: (item.totalPrice / item.quantity) * (item.quantity + quantity) }
            : item
        );
      } else {
        // Calculate price by applying variant adjustments dynamically
        let price = product.price || product.base_price || 0;
        Object.entries(selectedVariants).forEach(([variantType, value]) => {
          const adj = product.variants?.[variantType]?.find(v => v.value === value)?.price_adjustment || 0;
          price += adj;
        });

        const newItem: CartItem = {
          id: itemId,
          product,
          quantity,
          size: selectedVariants['size'] ?? undefined,
          temperature: selectedVariants['temperature'] ?? undefined,
          selectedVariants,
          totalPrice: price * quantity,
        };
        newItems = [...state.items, newItem];
      }

      const total = newItems.reduce((sum, item) => sum + item.totalPrice, 0);
      const itemCount = newItems.reduce((sum, item) => sum + item.quantity, 0);

      return { items: newItems, total, itemCount };
    }

    case 'REMOVE_ITEM': {
      const newItems = state.items.filter(item => item.id !== action.payload.id);
      const total = newItems.reduce((sum, item) => sum + item.totalPrice, 0);
      const itemCount = newItems.reduce((sum, item) => sum + item.quantity, 0);
      return { items: newItems, total, itemCount };
    }

    case 'UPDATE_QUANTITY': {
      const { id, quantity } = action.payload;
      if (quantity <= 0) {
        return cartReducer(state, { type: 'REMOVE_ITEM', payload: { id } });
      }

      const newItems = state.items.map(item => {
        if (item.id === id) {
          const newQuantity = quantity;
          const newTotalPrice = (item.totalPrice / item.quantity) * newQuantity;
          return { ...item, quantity: newQuantity, totalPrice: newTotalPrice };
        }
        return item;
      });

      const total = newItems.reduce((sum, item) => sum + item.totalPrice, 0);
      const itemCount = newItems.reduce((sum, item) => sum + item.quantity, 0);

      return { items: newItems, total, itemCount };
    }

    case 'CLEAR_CART':
      return initialState;

    case 'LOAD_CART':
      const loadedItems = action.payload;
      const total = loadedItems.reduce((sum, item) => sum + item.totalPrice, 0);
      const itemCount = loadedItems.reduce((sum, item) => sum + item.quantity, 0);
      return { items: loadedItems, total, itemCount };

    default:
      return state;
  }
}

interface CartContextType {
  state: CartState;
  addItem: (product: Product, selectedVariants?: Record<string,string>, quantity?: number) => void;
  removeItem: (id: string) => void;
  updateQuantity: (id: string, quantity: number) => void;
  clearCart: () => void;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

export function CartProvider({ children }: { children: ReactNode }) {
  const [state, dispatch] = useReducer(cartReducer, initialState);

  // Load cart from localStorage on mount
  useEffect(() => {
    const savedCart = localStorage.getItem('dailycup-cart');
    if (savedCart) {
      try {
        const cartItems: CartItem[] = JSON.parse(savedCart);
        dispatch({ type: 'LOAD_CART', payload: cartItems });
      } catch (error) {
        console.error('Error loading cart from localStorage:', error);
      }
    }
  }, []);

  // Save cart to localStorage whenever it changes
  useEffect(() => {
    localStorage.setItem('dailycup-cart', JSON.stringify(state.items));
  }, [state.items]);

  const addItem = (product: Product, selectedVariants: Record<string,string> = {}, quantity = 1) => {
    dispatch({ type: 'ADD_ITEM', payload: { product, selectedVariants, quantity } });
  };

  const removeItem = (id: string) => {
    dispatch({ type: 'REMOVE_ITEM', payload: { id } });
  };

  const updateQuantity = (id: string, quantity: number) => {
    dispatch({ type: 'UPDATE_QUANTITY', payload: { id, quantity } });
  };

  const clearCart = () => {
    dispatch({ type: 'CLEAR_CART' });
  };

  return (
    <CartContext.Provider value={{ state, addItem, removeItem, updateQuantity, clearCart }}>
      { children }
    </CartContext.Provider>
  );
}

export function useCart() {
  const context = useContext(CartContext);
  if (context === undefined) {
    throw new Error('useCart must be used within a CartProvider');
  }
  return context;
}