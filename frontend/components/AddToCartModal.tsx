'use client';

import { useState, useEffect } from 'react';
import { Product } from '@/utils/api';

interface ProductModifier {
  id: number;
  modifier_type: string;
  modifier_name: string;
  is_required: boolean;
  options: ModifierOption[];
}

interface ModifierOption {
  id: number;
  option_value: string;
  option_label: string;
  price_adjustment: number;
  is_default: boolean;
}

interface VariantSelection {
  sugar_level?: string;
  ice_level?: string;
  [key: string]: string | undefined;
}

interface AddToCartModalProps {
  product: Product | null;
  isOpen: boolean;
  onClose: () => void;
  onAddToCart: (product: Product, modifiers: VariantSelection, totalPrice: number) => void;
}

export default function AddToCartModal({
  product,
  isOpen,
  onClose,
  onAddToCart
}: AddToCartModalProps) {
  const [selectedModifiers, setSelectedModifiers] = useState<VariantSelection>({});
  const [quantity, setQuantity] = useState(1);
  const [modifiers, setModifiers] = useState<ProductModifier[]>([]);

  // Reset state when product changes or modal closes
  useEffect(() => {
    if (product && isOpen) {
      // Load default modifiers
      loadDefaultModifiers();
      setQuantity(1);
    }
  }, [product, isOpen]);

  const loadDefaultModifiers = () => {
    // Mock modifiers - in production, fetch from API based on product
    const mockModifiers: ProductModifier[] = [
      {
        id: 1,
        modifier_type: 'sugar_level',
        modifier_name: 'Sugar Level',
        is_required: true,
        options: [
          { id: 1, option_value: '0%', option_label: 'No Sugar (0%)', price_adjustment: 0, is_default: false },
          { id: 2, option_value: '50%', option_label: 'Half Sweet (50%)', price_adjustment: 0, is_default: true },
          { id: 3, option_value: '100%', option_label: 'Full Sweet (100%)', price_adjustment: 0, is_default: false }
        ]
      },
      {
        id: 2,
        modifier_type: 'ice_level',
        modifier_name: 'Ice Level',
        is_required: true,
        options: [
          { id: 4, option_value: 'less', option_label: 'Less Ice', price_adjustment: 0, is_default: false },
          { id: 5, option_value: 'normal', option_label: 'Normal Ice', price_adjustment: 0, is_default: true },
          { id: 6, option_value: 'extra', option_label: 'Extra Ice', price_adjustment: 0, is_default: false }
        ]
      }
    ];

    setModifiers(mockModifiers);

    // Set default selections
    const defaults: VariantSelection = {};
    mockModifiers.forEach(modifier => {
      const defaultOption = modifier.options.find(opt => opt.is_default);
      if (defaultOption) {
        defaults[modifier.modifier_type] = defaultOption.option_value;
      }
    });
    setSelectedModifiers(defaults);
  };

  const handleModifierChange = (modifierType: string, optionValue: string) => {
    setSelectedModifiers(prev => ({
      ...prev,
      [modifierType]: optionValue
    }));
  };

  const calculateTotalPrice = (): number => {
    if (!product) return 0;

    let total: number = product.base_price || 0;

    // Add price adjustments from selected modifiers
    modifiers.forEach(modifier => {
      const selectedValue = selectedModifiers[modifier.modifier_type];
      const selectedOption = modifier.options.find(opt => opt.option_value === selectedValue);
      if (selectedOption && selectedOption.price_adjustment) {
        total += selectedOption.price_adjustment;
      }
    });

    return total * quantity;
  };

  const handleAddToCart = () => {
    if (!product) return;

    // Validate required modifiers
    const missingRequired = modifiers
      .filter(m => m.is_required)
      .some(m => !selectedModifiers[m.modifier_type]);

    if (missingRequired) {
      alert('Please select all required options');
      return;
    }

    onAddToCart(product, selectedModifiers, calculateTotalPrice());
    onClose();
  };

  if (!isOpen || !product) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
      <div className="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
          <h2 className="text-2xl font-bold text-gray-900">Customize Your Order</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {/* Product Info */}
        <div className="px-6 py-4 border-b">
          <div className="flex gap-4">
            {product.image && (
              <img
                src={product.image}
                alt={product.name}
                className="w-20 h-20 object-cover rounded-lg"
              />
            )}
            <div className="flex-1">
              <h3 className="text-xl font-semibold text-gray-900">{product.name}</h3>
              <p className="text-sm text-gray-600 mt-1">{product.description}</p>
              <p className="text-lg font-bold text-[#a97456] mt-2">
                Rp {product.base_price.toLocaleString('id-ID')}
              </p>
            </div>
          </div>
        </div>

        {/* Modifiers */}
        <div className="px-6 py-4 space-y-6">
          {modifiers.map((modifier) => (
            <div key={modifier.id}>
              <label className="block text-sm font-semibold text-gray-900 mb-3">
                {modifier.modifier_name}
                {modifier.is_required && <span className="text-red-500 ml-1">*</span>}
              </label>
              <div className="space-y-2">
                {modifier.options.map((option) => {
                  const isSelected = selectedModifiers[modifier.modifier_type] === option.option_value;
                  return (
                    <button
                      key={option.id}
                      onClick={() => handleModifierChange(modifier.modifier_type, option.option_value)}
                      className={`w-full text-left px-4 py-3 rounded-lg border-2 transition-all ${
                        isSelected
                          ? 'border-[#a97456] bg-[#a97456]/5'
                          : 'border-gray-200 hover:border-gray-300'
                      }`}
                    >
                      <div className="flex items-center justify-between">
                        <span className="font-medium text-gray-900">{option.option_label}</span>
                        {option.price_adjustment !== 0 && (
                          <span className="text-sm text-gray-600">
                            +Rp {option.price_adjustment.toLocaleString('id-ID')}
                          </span>
                        )}
                      </div>
                      {isSelected && (
                        <div className="mt-1">
                          <span className="inline-block w-5 h-5 rounded-full bg-[#a97456] text-white text-xs flex items-center justify-center">
                            ✓
                          </span>
                        </div>
                      )}
                    </button>
                  );
                })}
              </div>
            </div>
          ))}

          {/* Quantity */}
          <div>
            <label className="block text-sm font-semibold text-gray-900 mb-3">
              Quantity
            </label>
            <div className="flex items-center gap-4">
              <button
                onClick={() => setQuantity(Math.max(1, quantity - 1))}
                className="w-10 h-10 rounded-full border-2 border-gray-300 hover:border-[#a97456] text-gray-700 hover:text-[#a97456] transition-colors flex items-center justify-center font-bold"
                disabled={quantity <= 1}
              >
                −
              </button>
              <span className="text-xl font-bold text-gray-900 min-w-[3rem] text-center">
                {quantity}
              </span>
              <button
                onClick={() => setQuantity(quantity + 1)}
                className="w-10 h-10 rounded-full border-2 border-gray-300 hover:border-[#a97456] text-gray-700 hover:text-[#a97456] transition-colors flex items-center justify-center font-bold"
              >
                +
              </button>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="sticky bottom-0 bg-white border-t px-6 py-4">
          <div className="flex items-center justify-between mb-4">
            <span className="text-gray-600">Total Price:</span>
            <span className="text-2xl font-bold text-[#a97456]">
              Rp {calculateTotalPrice().toLocaleString('id-ID')}
            </span>
          </div>
          <button
            onClick={handleAddToCart}
            className="w-full bg-[#a97456] hover:bg-[#8b5e3c] text-white font-bold py-4 rounded-lg transition-colors shadow-lg"
          >
            Add to Cart
          </button>
        </div>
      </div>
    </div>
  );
}
