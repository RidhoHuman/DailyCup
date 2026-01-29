'use client';

export type PaymentMethodType = 'transfer_bca' | 'transfer_mandiri' | 'gopay' | 'cod';

interface PaymentMethodSelectorProps {
  selected: PaymentMethodType | null;
  onSelect: (method: PaymentMethodType) => void;
}

export default function PaymentMethodSelector({ selected, onSelect }: PaymentMethodSelectorProps) {
  
  const methods = [
    {
      id: 'transfer_bca',
      title: 'BCA Virtual Account',
      description: 'Cek otomatis, tanpa konfirmasi manual',
      icon: 'bi-bank',
      color: 'text-blue-600',
      tag: 'Automatic'
    },
    {
      id: 'transfer_mandiri',
      title: 'Mandiri Virtual Account',
      description: 'Cek otomatis, tanpa konfirmasi manual',
      icon: 'bi-bank2',
      color: 'text-indigo-600',
      tag: 'Automatic'
    },
    {
      id: 'gopay',
      title: 'GoPay / QRIS',
      description: 'Scan QR Code untuk membayar',
      icon: 'bi-qr-code-scan',
      color: 'text-green-600',
      tag: 'Instant'
    },
    {
      id: 'cod',
      title: 'Cash on Delivery (COD)',
      description: 'Bayar tunai saat kurir sampai',
      icon: 'bi-cash-coin',
      color: 'text-[#a97456]',
      tag: 'Manual'
    }
  ];

  return (
    <div className="space-y-4">
      {methods.map((method) => {
        const isSelected = selected === method.id;
        return (
          <div
            key={method.id}
            onClick={() => onSelect(method.id as PaymentMethodType)}
            className={`
              relative flex items-center p-4 border rounded-xl cursor-pointer transition-all duration-200
              ${isSelected 
                ? 'border-[#a97456] bg-[#fdf8f6] ring-1 ring-[#a97456]' 
                : 'border-gray-200 hover:border-gray-300 bg-white dark:bg-[#333] dark:border-gray-600'}
            `}
          >
            {/* Radio Circle */}
            <div className={`
              w-5 h-5 rounded-full border flex items-center justify-center mr-4 flex-shrink-0
              ${isSelected ? 'border-[#a97456]' : 'border-gray-300 dark:border-gray-500'}
            `}>
              {isSelected && <div className="w-2.5 h-2.5 rounded-full bg-[#a97456]"></div>}
            </div>

            {/* Icon */}
            <div className={`
              w-10 h-10 rounded-lg flex items-center justify-center text-xl mr-4 flex-shrink-0 bg-gray-50 dark:bg-gray-800
              ${method.color}
            `}>
              <i className={`bi ${method.icon}`}></i>
            </div>

            {/* Info */}
            <div className="flex-1">
              <div className="flex items-center justify-between">
                <h3 className="font-semibold text-gray-800 dark:text-gray-100">{method.title}</h3>
                {method.tag && (
                  <span className={`
                    text-[10px] uppercase font-bold px-2 py-0.5 rounded-full
                    ${isSelected ? 'bg-[#a97456] text-white' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'}
                  `}>
                    {method.tag}
                  </span>
                )}
              </div>
              <p className="text-sm text-gray-500 dark:text-gray-400">{method.description}</p>
            </div>
          </div>
        );
      })}
    </div>
  );
}
