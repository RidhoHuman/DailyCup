'use client';

import { createContext, useContext, useState, useEffect, ReactNode } from 'react';

interface Currency {
  id: number;
  code: string;
  name: string;
  symbol: string;
  decimal_places: number;
  is_base_currency: boolean;
}

interface CurrencyContextType {
  currentCurrency: Currency | null;
  availableCurrencies: Currency[];
  changeCurrency: (code: string) => void;
  convertPrice: (amount: number, fromCurrency?: string) => Promise<number>;
  formatPrice: (amount: number, fromCurrency?: string) => Promise<string>;
  loading: boolean;
}

const CurrencyContext = createContext<CurrencyContextType | undefined>(undefined);

export function CurrencyProvider({ children }: { children: ReactNode }) {
  const [currentCurrency, setCurrentCurrency] = useState<Currency | null>(null);
  const [availableCurrencies, setAvailableCurrencies] = useState<Currency[]>([]);
  const [loading, setLoading] = useState(true);

  // Load available currencies
  useEffect(() => {
    loadCurrencies();
  }, []);

  const loadCurrencies = async () => {
    try {
      const response = await fetch('/webapp/backend/api/currencies.php?action=active', {
        headers: { 'ngrok-skip-browser-warning': '69420' }
      });
      const data = await response.json();
      
      if (data.success) {
        setAvailableCurrencies(data.currencies);
        
        // Set initial currency from localStorage or default to base currency
        const savedCurrency = localStorage.getItem('selectedCurrency');
        if (savedCurrency) {
          const currency = data.currencies.find((c: Currency) => c.code === savedCurrency);
          if (currency) {
            setCurrentCurrency(currency);
          } else {
            setBaseCurrency(data.currencies);
          }
        } else {
          setBaseCurrency(data.currencies);
        }
      }
    } catch (error) {
      console.error('Failed to load currencies:', error);
    } finally {
      setLoading(false);
    }
  };

  const setBaseCurrency = (currencies: Currency[]) => {
    const base = currencies.find((c: Currency) => c.is_base_currency);
    if (base) {
      setCurrentCurrency(base);
    }
  };

  const changeCurrency = (code: string) => {
    const currency = availableCurrencies.find(c => c.code === code);
    if (currency) {
      setCurrentCurrency(currency);
      localStorage.setItem('selectedCurrency', code);
    }
  };

  const convertPrice = async (amount: number, fromCurrency?: string): Promise<number> => {
    if (!currentCurrency) return amount;
    
    const baseCurrency = availableCurrencies.find(c => c.is_base_currency);
    if (!baseCurrency) return amount;
    
    const from = fromCurrency || baseCurrency.code;
    const to = currentCurrency.code;
    
    // If same currency, no conversion needed
    if (from === to) return amount;
    
    try {
      const response = await fetch(
        `/webapp/backend/api/currencies.php?action=convert&amount=${amount}&from=${from}&to=${to}`,
        { headers: { 'ngrok-skip-browser-warning': '69420' } }
      );
      const data = await response.json();
      
      if (data.success) {
        return data.converted_amount;
      }
      
      return amount;
    } catch (error) {
      console.error('Failed to convert price:', error);
      return amount;
    }
  };

  const formatPrice = async (amount: number, fromCurrency?: string): Promise<string> => {
    if (!currentCurrency) return amount.toString();
    
    const converted = await convertPrice(amount, fromCurrency);
    const formatted = converted.toLocaleString('id-ID', {
      minimumFractionDigits: currentCurrency.decimal_places,
      maximumFractionDigits: currentCurrency.decimal_places
    });
    
    return `${currentCurrency.symbol}${formatted}`;
  };

  return (
    <CurrencyContext.Provider
      value={{
        currentCurrency,
        availableCurrencies,
        changeCurrency,
        convertPrice,
        formatPrice,
        loading
      }}
    >
      {children}
    </CurrencyContext.Provider>
  );
}

export function useCurrency() {
  const context = useContext(CurrencyContext);
  if (context === undefined) {
    throw new Error('useCurrency must be used within a CurrencyProvider');
  }
  return context;
}
