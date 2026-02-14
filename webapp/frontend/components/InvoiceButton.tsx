"use client";

import { useState } from "react";
import { api } from "@/lib/api-client";

interface InvoiceButtonProps {
  orderId: string;
  label?: string;
  className?: string;
  variant?: 'button' | 'icon';
}

export default function InvoiceButton({ 
  orderId, 
  label = "Download Invoice", 
  className = "",
  variant = 'button'
}: InvoiceButtonProps) {
  const [loading, setLoading] = useState(false);

  const viewInvoice = async () => {
    setLoading(true);
    try {
      // Open invoice in new window
      const invoiceUrl = `${process.env.NEXT_PUBLIC_API_URL}/invoice.php?order_id=${orderId}&format=html`;
      window.open(invoiceUrl, '_blank', 'width=900,height=800');
    } catch (error) {
      console.error('Failed to open invoice:', error);
      alert('Failed to open invoice');
    } finally {
      setLoading(false);
    }
  };

  const downloadPDF = async () => {
    setLoading(true);
    try {
      // Get invoice data
      const response = await api.get<{success: boolean; invoice: Record<string, unknown>}>(
        `/invoice.php?order_id=${orderId}&format=json`,
        { requiresAuth: true }
      );

      if (response.success) {
        // Open HTML invoice and trigger print
        viewInvoice();
      }
    } catch (error) {
      console.error('Failed to download invoice:', error);
      alert('Failed to download invoice');
    } finally {
      setLoading(false);
    }
  };

  if (variant === 'icon') {
    return (
      <button
        onClick={viewInvoice}
        disabled={loading}
        className={`w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-[#a97456] hover:bg-gray-100 transition-all disabled:opacity-50 ${className}`}
        title="View Invoice"
      >
        {loading ? (
          <i className="bi bi-arrow-repeat animate-spin"></i>
        ) : (
          <i className="bi bi-file-earmark-text"></i>
        )}
      </button>
    );
  }

  return (
    <button
      onClick={viewInvoice}
      disabled={loading}
      className={`inline-flex items-center gap-2 px-4 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8f6249] disabled:opacity-50 disabled:cursor-not-allowed transition-colors ${className}`}
    >
      {loading ? (
        <>
          <i className="bi bi-arrow-repeat animate-spin"></i>
          Loading...
        </>
      ) : (
        <>
          <i className="bi bi-file-earmark-pdf"></i>
          {label}
        </>
      )}
    </button>
  );
}
