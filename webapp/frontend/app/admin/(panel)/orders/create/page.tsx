"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api-client";
import Link from "next/link";

interface Product {
  id: number;
  name: string;
  price: number;
  description: string;
  image: string;
  category_name?: string;
  category?: string;
  stock: number;
}

interface CartItem {
  id: number;
  name: string;
  price: number;
  quantity: number;
  image: string;
  size?: string;
  temperature?: string;
  addons?: { code: string; name: string; price: number }[];
  notes?: string;
  basePrice?: number;
  sizeModifier?: number;
  addonsTotal?: number;
}

interface CustomerForm {
  name: string;
  email: string;
  phone: string;
  address: string;
}

interface User {
  id: number;
  name: string;
  email: string;
  phone: string;
  address?: string;
}

interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  order_number?: string;
}

export default function CreateOrderPage() {
  const router = useRouter();
  const [products, setProducts] = useState<Product[]>([]);
  const [filteredProducts, setFilteredProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("All");
  
  // Cart & Form State
  const [cart, setCart] = useState<CartItem[]>([]);
  const [customer, setCustomer] = useState<CustomerForm>({
    name: "",
    email: "",
    phone: "",
    address: "",
  });
  const [paymentMethod, setPaymentMethod] = useState("cash");
  const [deliveryMethod, setDeliveryMethod] = useState("takeaway"); // or 'delivery'
  const [notes, setNotes] = useState("");
  const [error, setError] = useState("");
  const [customerSearch, setCustomerSearch] = useState("");
  const [searchResults, setSearchResults] = useState<User[]>([]);
  const [showSearchResults, setShowSearchResults] = useState(false);

  // Xendit payment state
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [paymentData, setPaymentData] = useState<{
    orderNumber: string;
    invoiceUrl: string;
    qrCodeUrl?: string;
    total: number;
  } | null>(null);

  // Cash register state
  const [cashReceived, setCashReceived] = useState<number>(0);
  const [customCashInput, setCustomCashInput] = useState("");
  const [showCustomCash, setShowCustomCash] = useState(false);
  const [showReceipt, setShowReceipt] = useState(false);
  const [receiptData, setReceiptData] = useState<{
    orderNumber: string;
    total: number;
    cashReceived: number;
    change: number;
    items: CartItem[];
    customerName: string;
    paymentMethod: string;
    deliveryMethod: string;
  } | null>(null);

  // Product customization modal state
  const [showCustomizeModal, setShowCustomizeModal] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
  const [customization, setCustomization] = useState<{
    size: string;
    temperature: string;
    addons: { code: string; name: string; price: number }[];
    notes: string;
  }>({
    size: "M",
    temperature: "hot",
    addons: [],
    notes: "",
  });

  // Edit cart item modal state
  const [showEditModal, setShowEditModal] = useState(false);
  const [editingItemIndex, setEditingItemIndex] = useState<number | null>(null);
  const [editCustomization, setEditCustomization] = useState<{
    size: string;
    temperature: string;
    addons: { code: string; name: string; price: number }[];
    notes: string;
  }>({
    size: "M",
    temperature: "hot",
    addons: [],
    notes: "",
  });

  // Available customization options
  const SIZES = [
    { code: "S", name: "Small", modifier: 0 },
    { code: "M", name: "Medium", modifier: 5000 },
    { code: "L", name: "Large", modifier: 10000 },
  ];

  const ADDONS = [
    { code: "extra_espresso", name: "Extra Espresso Shot", price: 8000 },
    { code: "extra_sugar", name: "Extra Sugar", price: 2000 },
    { code: "extra_milk", name: "Extra Milk", price: 5000 },
    { code: "extra_whipped_cream", name: "Whipped Cream", price: 7000 },
  ];

  // Fetch Products
  useEffect(() => {
    const fetchProducts = async () => {
      try {
        const response = await api.get<ApiResponse<Product[]>>("/products.php");
        if (response.success && Array.isArray(response.data)) {
          setProducts(response.data);
          setFilteredProducts(response.data);
        }
      } catch (err) {
        console.error("Failed to fetch products:", err);
        setError("Failed to load products");
      } finally {
        setLoading(false);
      }
    };
    fetchProducts();
  }, []);

  // Filter Logic
  useEffect(() => {
    let result = products;
    
    if (searchQuery) {
      result = result.filter(p => 
        p.name.toLowerCase().includes(searchQuery.toLowerCase())
      );
    }

    if (selectedCategory !== "All") {
      result = result.filter(p => (p.category_name || p.category) === selectedCategory);
    }

    setFilteredProducts(result);
  }, [searchQuery, selectedCategory, products]);

  // Cart Functions
  const addToCart = (product: Product) => {
    setSelectedProduct(product);
    setCustomization({
      size: "M",
      temperature: "hot",
      addons: [],
      notes: "",
    });
    setShowCustomizeModal(true);
  };

  const confirmAddToCart = () => {
    if (!selectedProduct) return;

    const sizeObj = SIZES.find(s => s.code === customization.size);
    const basePrice = Number(selectedProduct.price);
    const sizeModifier = sizeObj?.modifier || 0;
    const addonsTotal = customization.addons.reduce((sum, addon) => sum + addon.price, 0);
    const finalPrice = basePrice + sizeModifier + addonsTotal;

    const cartItem: CartItem = {
      id: selectedProduct.id,
      name: selectedProduct.name,
      price: finalPrice,
      quantity: 1,
      image: selectedProduct.image,
      size: customization.size,
      temperature: customization.temperature,
      addons: [...customization.addons],
      notes: customization.notes,
      basePrice,
      sizeModifier,
      addonsTotal,
    };

    setCart(prev => [...prev, cartItem]);
    setShowCustomizeModal(false);
    setSelectedProduct(null);
  };

  // Open edit modal for cart item
  const openEditModal = (index: number) => {
    const item = cart[index];
    setEditingItemIndex(index);
    setEditCustomization({
      size: item.size || "M",
      temperature: item.temperature || "hot",
      addons: item.addons ? [...item.addons] : [],
      notes: item.notes || "",
    });
    setShowEditModal(true);
  };

  // Save edited cart item
  const saveEditedItem = () => {
    if (editingItemIndex === null) return;

    const item = cart[editingItemIndex];
    const sizeObj = SIZES.find(s => s.code === editCustomization.size);
    const basePrice = item.basePrice || item.price;
    const sizeModifier = sizeObj?.modifier || 0;
    const addonsTotal = editCustomization.addons.reduce((sum, addon) => sum + addon.price, 0);
    const finalPrice = basePrice + sizeModifier + addonsTotal;

    setCart(prev => prev.map((cartItem, i) => {
      if (i === editingItemIndex) {
        return {
          ...cartItem,
          size: editCustomization.size,
          temperature: editCustomization.temperature,
          addons: [...editCustomization.addons],
          notes: editCustomization.notes,
          price: finalPrice,
          sizeModifier,
          addonsTotal,
        };
      }
      return cartItem;
    }));

    setShowEditModal(false);
    setEditingItemIndex(null);
  };

  const updateQuantity = (index: number, delta: number) => {
    setCart(prev => prev.map((item, i) => {
      if (i === index) {
        const newQty = item.quantity + delta;
        return newQty > 0 ? { ...item, quantity: newQty } : item;
      }
      return item;
    }).filter(item => item.quantity > 0));
  };

  const removeFromCart = (index: number) => {
    setCart(prev => prev.filter((_, i) => i !== index));
  };

  const calculateTotal = () => {
    return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  };

  // Search customers
  const searchCustomers = async (query: string) => {
    if (query.length < 2) {
      setSearchResults([]);
      return;
    }
    try {
      const response = await api.get<ApiResponse<User[]>>(`/users.php?search=${encodeURIComponent(query)}`, { requiresAuth: true });
      if (response.success && Array.isArray(response.data)) {
        setSearchResults(response.data);
      }
    } catch (err) {
      console.error('Failed to search customers:', err);
    }
  };

  // Select customer from search
  const selectCustomer = (user: User) => {
    setCustomer({
      name: user.name,
      email: user.email || '',
      phone: user.phone || '',
      address: user.address || ''
    });
    setCustomerSearch(user.name + ' - ' + user.email);
    setShowSearchResults(false);
    setSearchResults([]);
  };

  // Cash register helpers
  const totalAmount = calculateTotal();
  const changeAmount = cashReceived - totalAmount;

  const selectCashDenomination = (amount: number | 'exact') => {
    if (amount === 'exact') {
      setCashReceived(totalAmount);
      setShowCustomCash(false);
      setCustomCashInput("");
    } else {
      setCashReceived(amount);
      setShowCustomCash(false);
      setCustomCashInput("");
    }
  };

  const handleCustomCashInput = (value: string) => {
    const num = value.replace(/[^0-9]/g, '');
    setCustomCashInput(num);
    setCashReceived(parseInt(num) || 0);
  };

  // Generate quick denominations based on total
  const getCashDenominations = (): { label: string; value: number | 'exact' }[] => {
    const denominations: { label: string; value: number | 'exact' }[] = [
      { label: 'Uang Pas', value: 'exact' },
    ];
    
    // Add smart denominations based on total
    const roundUps = [20000, 50000, 100000, 150000, 200000, 500000];
    const added = new Set<number>();
    
    for (const d of roundUps) {
      if (d >= totalAmount && !added.has(d)) {
        denominations.push({ label: `Rp ${d.toLocaleString('id-ID')}`, value: d });
        added.add(d);
        if (denominations.length >= 4) break; // max 4 quick buttons (including exact)
      }
    }
    
    return denominations;
  };

  // Submit Order
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (cart.length === 0) {
      alert("Cart is empty");
      return;
    }
    if (!customer.name) {
      alert("Customer Name is required");
      return;
    }

    // Validate cash payment
    if (paymentMethod === 'cash' && cashReceived < totalAmount) {
      alert(`Uang yang diterima (Rp ${cashReceived.toLocaleString()}) kurang dari total (Rp ${totalAmount.toLocaleString()})`);
      return;
    }

    setSubmitting(true);
    setError("");

    try {
      const subtotal = calculateTotal();
      const deliveryFee = 0; // For POS orders, no delivery fee
      const discount = 0;
      const total = subtotal + deliveryFee - discount;
      
      const payload = {
        items: cart.map(item => ({
          id: item.id,
          name: item.name,
          price: item.price,
          quantity: item.quantity,
          size: item.size,
          temperature: item.temperature,
          addons: item.addons ? JSON.stringify(item.addons) : null,
          notes: item.notes || null,
          base_price: item.basePrice,
          size_price_modifier: item.sizeModifier,
          addons_total: item.addonsTotal
        })),
        subtotal,
        deliveryFee,
        discount,
        total,
        customer: {
          name: customer.name,
          email: customer.email || `guest-${Date.now()}@dailycup.local`,
          phone: customer.phone || "0000000000",
          address: customer.address || ""
        },
        paymentMethod,
        deliveryMethod,
        notes,
        // Include cash info for record
        ...(paymentMethod === 'cash' ? { cashReceived, changeAmount: cashReceived - total } : {})
      };

      console.log('Sending order:', payload);
      const res = await api.post<ApiResponse<{order_number?: string; invoice_url?: string; xendit?: { invoice_url?: string; qr_code_url?: string } }>>("/create_order.php", payload, { requiresAuth: true });
      const r = res as ApiResponse<{order_number?: string; invoice_url?: string; xendit?: { invoice_url?: string; qr_code_url?: string } }>;
      
      if (r.success) {
        const orderNum = r.data?.order_number || r.order_number || (r as any).orderId || "NEW";
        const invoiceUrl = r.data?.invoice_url || r.order_number || r.data?.xendit?.invoice_url || r.order_number || (r as any).xendit?.invoice_url;
        
        // Show receipt modal for cash payments
        if (paymentMethod === 'cash') {
          setReceiptData({
            orderNumber: orderNum,
            total,
            cashReceived,
            change: cashReceived - total,
            items: [...cart],
            customerName: customer.name,
            paymentMethod: 'Cash',
            deliveryMethod
          });
          setShowReceipt(true);
          
          // Clear cart and form
          setCart([]);
          setCustomer({ name: "", email: "", phone: "", address: "" });
          setCustomerSearch("");
          setNotes("");
          setCashReceived(0);
          setCustomCashInput("");
          setShowCustomCash(false);
        } else if ((paymentMethod === 'transfer' || paymentMethod === 'qris') && invoiceUrl) {
          // Show Xendit payment modal
          setPaymentData({
            orderNumber: orderNum,
            invoiceUrl,
            qrCodeUrl: (res as any).xendit?.qr_code_url,
            total
          });
          setShowPaymentModal(true);
          
          // Clear cart and form
          setCart([]);
          setCustomer({ name: "", email: "", phone: "", address: "" });
          setCustomerSearch("");
          setNotes("");
          setCashReceived(0);
          setCustomCashInput("");
          setShowCustomCash(false);
        } else {
          alert("Order created successfully! Order #: " + orderNum);
          
          // Clear cart and form
          setCart([]);
          setCustomer({ name: "", email: "", phone: "", address: "" });
          setCustomerSearch("");
          setNotes("");
        }
      } else {
        setError(res.message || "Failed to create order");
      }
    } catch (err: unknown) {
      console.error('Order submission error:', err);
      setError((err as any)?.message || "An error occurred");
    } finally {
      setSubmitting(false);
    }
  };

  // Get unique categories for filter
  const categories = ["All", ...Array.from(new Set(products.map(p => p.category_name || p.category || "Uncategorized")))];

  return (
    <div className="p-6 h-[calc(100vh-80px)] overflow-hidden flex flex-col xl:flex-row gap-6">
      {/* Left Column: Product Selection */}
      <div className="flex-1 flex flex-col h-full bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {/* Header/Filter */}
        <div className="p-4 border-b border-gray-100 space-y-4">
            <div className="flex justify-between items-center">
                <h1 className="text-xl font-bold text-gray-800">New Order (POS)</h1>
                <Link href="/admin/orders" className="text-sm text-gray-500 hover:text-gray-800">
                    Cancel
                </Link>
            </div>
            
            <div className="flex gap-2">
                <input
                    type="text"
                    placeholder="Search products..."
                    className="flex-1 px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#a97456]"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                />
                <select 
                    className="px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#a97456]"
                    value={selectedCategory}
                    onChange={(e) => setSelectedCategory(e.target.value)}
                >
                    {categories.map(c => <option key={c} value={c}>{c}</option>)}
                </select>
            </div>
        </div>

        {/* Product Grid */}
        <div className="flex-1 overflow-y-auto p-4 bg-gray-50">
            {loading ? (
                <div className="text-center py-10">Loading catalog...</div>
            ) : filteredProducts.length === 0 ? (
                <div className="text-center py-10 text-gray-500">No products found</div>
            ) : (
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    {filteredProducts.map(product => (
                        <div 
                            key={product.id} 
                            className={`bg-white rounded-lg border p-3 flex flex-col transition-shadow hover:shadow-md cursor-pointer ${
                                (product.stock <= 0) ? "opacity-50 cursor-not-allowed" : ""
                            }`}
                            onClick={() => product.stock > 0 && addToCart(product)}
                        >
                            <div className="aspect-square bg-gray-100 rounded-md mb-2 overflow-hidden">
                                {product.image ? (
                                    <img 
                                        src={product.image?.startsWith('http') || product.image?.startsWith('/') ? product.image : `/uploads/products/${product.image}`} 
                                        alt={product.name} 
                                        className="w-full h-full object-cover" 
                                        onError={(e) => {
                                            (e.target as HTMLImageElement).style.display = 'none';
                                            (e.target as HTMLImageElement).parentElement!.innerHTML = '<div class="w-full h-full flex items-center justify-center text-gray-300"><i class="bi bi-image text-2xl"></i></div>';
                                        }}
                                    />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center text-gray-300">
                                        <i className="bi bi-image text-2xl"></i>
                                    </div>
                                )}
                            </div>
                            <h3 className="font-semibold text-sm line-clamp-1">{product.name}</h3>
                            <p className="text-[#a97456] font-bold text-sm mt-auto">
                                Rp {Number(product.price).toLocaleString()}
                            </p>
                            {product.stock <= 0 && (
                                <span className="text-xs text-red-500 font-bold mt-1">Out of Stock</span>
                            )}
                            {product.stock > 0 && product.stock <= 10 && (
                                <span className="text-xs text-orange-500 font-medium mt-1">Stock: {product.stock}</span>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
      </div>

      {/* Right Column: Checkout Panel */}
      <div className="w-full xl:w-[480px] flex flex-col bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden h-full">
        {/* Panel Header */}
        <div className="px-4 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between flex-shrink-0">
            <h2 className="font-bold text-gray-800">
              <i className="bi bi-receipt mr-2 text-[#a97456]"></i>
              Order Summary
            </h2>
            {cart.length > 0 && (
              <span className="text-xs font-semibold bg-[#a97456] text-white px-2 py-0.5 rounded-full">
                {cart.reduce((s, i) => s + i.quantity, 0)} items
              </span>
            )}
        </div>

        {/* Scrollable Content: Cart + Customer Info + Cash Register */}
        <div className="flex-1 overflow-y-auto min-h-0">
          {/* Cart Items */}
          <div className="p-3 space-y-2">
            {cart.length === 0 ? (
                <div className="text-center py-8 text-gray-400 flex flex-col items-center">
                    <i className="bi bi-cart text-3xl mb-2"></i>
                    <p className="text-sm">Cart is empty</p>
                    <p className="text-xs">Select products from the left to add.</p>
                </div>
            ) : (
                cart.map((item, idx) => (
                    <div key={`${item.id}-${idx}`} className="bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                        <div className="flex items-start gap-2">
                            <div className="w-9 h-9 bg-gray-200 rounded overflow-hidden flex-shrink-0">
                                {item.image ? (
                                    <img src={item.image?.startsWith('http') || item.image?.startsWith('/') ? item.image : `/uploads/products/${item.image}`} alt={item.name} className="w-full h-full object-cover" />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center text-gray-300">
                                        <i className="bi bi-image text-xs"></i>
                                    </div>
                                )}
                            </div>
                            <div className="flex-1 min-w-0">
                                <h4 className="font-medium text-xs text-gray-800 mb-1">{item.name}</h4>
                                
                                {/* Customization Details */}
                                {(item.size || item.temperature || (item.addons && item.addons.length > 0)) && (
                                    <div className="flex flex-wrap gap-1 mb-1.5">
                                        {item.size && (
                                            <span className="text-[10px] bg-white px-1.5 py-0.5 rounded border border-[#a97456]/30 text-[#a97456] font-semibold">
                                                {SIZES.find(s => s.code === item.size)?.name || item.size}
                                            </span>
                                        )}
                                        {item.temperature && (
                                            <span className="text-[10px] bg-white px-1.5 py-0.5 rounded border border-blue-300 text-blue-600 font-semibold">
                                                {item.temperature === 'hot' ? '🔥 Hot' : '❄️ Ice'}
                                            </span>
                                        )}
                                        {item.addons && item.addons.length > 0 && item.addons.map((addon, i) => (
                                            <span key={i} className="text-[10px] bg-white px-1.5 py-0.5 rounded border border-green-300 text-green-600 font-medium">
                                                +{addon.name}
                                            </span>
                                        ))}
                                    </div>
                                )}
                                
                                {/* Notes */}
                                {item.notes && (
                                    <div className="text-[10px] text-gray-500 italic mb-1.5 bg-yellow-50 border border-yellow-200 rounded px-1.5 py-1">
                                        📝 {item.notes}
                                    </div>
                                )}
                                
                                {/* Price Details */}
                                <div className="text-[10px] text-gray-500 space-y-0.5">
                                    {item.basePrice && (
                                        <>
                                            <div>Base: Rp {item.basePrice.toLocaleString()}</div>
                                            {item.sizeModifier! > 0 && (
                                                <div>Size: +Rp {item.sizeModifier!.toLocaleString()}</div>
                                            )}
                                            {item.addonsTotal! > 0 && (
                                                <div>Add-ons: +Rp {item.addonsTotal!.toLocaleString()}</div>
                                            )}
                                        </>
                                    )}
                                </div>
                                
                                <p className="text-xs text-[#a97456] font-bold mt-1">
                                    Rp {item.price.toLocaleString()} × {item.quantity} = Rp {(item.price * item.quantity).toLocaleString()}
                                </p>
                            </div>
                            <div className="flex flex-col gap-1 flex-shrink-0">
                                {/* Quantity controls */}
                                <div className="flex items-center gap-1">
                                     <button 
                                        onClick={(e) => { e.stopPropagation(); updateQuantity(idx, -1); }}
                                        className="w-6 h-6 flex items-center justify-center bg-white border border-gray-200 rounded text-xs font-bold hover:bg-gray-100"
                                        title="Kurangi"
                                     >-</button>
                                     <span className="text-xs font-bold w-5 text-center">{item.quantity}</span>
                                     <button 
                                        onClick={(e) => { e.stopPropagation(); updateQuantity(idx, 1); }}
                                        className="w-6 h-6 flex items-center justify-center bg-[#a97456] text-white rounded text-xs font-bold hover:bg-[#8b6043]"
                                        title="Tambah"
                                     >+</button>
                                </div>
                                {/* Action buttons */}
                                <div className="flex items-center gap-1">
                                     <button 
                                        onClick={(e) => { e.stopPropagation(); openEditModal(idx); }}
                                        className="w-6 h-6 flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-100 rounded"
                                        title="Edit"
                                    >
                                        <i className="bi bi-pencil text-xs"></i>
                                    </button>
                                     <button 
                                        onClick={(e) => { e.stopPropagation(); removeFromCart(idx); }}
                                        className="w-6 h-6 flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 rounded"
                                        title="Hapus"
                                    >
                                        <i className="bi bi-x-lg text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                ))
            )}
          </div>

          {/* Customer & Payment Section */}
          <div className="px-3 pb-3 space-y-2">
            <div className="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-2 border-t border-gray-100">Customer Info</div>
            
            {/* Customer Search/Autocomplete */}
            <div className="relative">
                <input 
                    type="text" 
                    placeholder="🔍 Search existing customer..." 
                    className="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456]/30 focus:border-[#a97456] outline-none"
                    value={customerSearch}
                    onChange={e => {
                        setCustomerSearch(e.target.value);
                        searchCustomers(e.target.value);
                        setShowSearchResults(true);
                    }}
                    onBlur={() => setTimeout(() => setShowSearchResults(false), 200)}
                    onFocus={() => customerSearch && setShowSearchResults(true)}
                />
                {showSearchResults && searchResults.length > 0 && (
                    <div className="absolute z-10 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-40 overflow-y-auto">
                        {searchResults.map(user => (
                            <div 
                                key={user.id}
                                className="px-3 py-1.5 hover:bg-gray-100 cursor-pointer border-b last:border-b-0"
                                onClick={() => selectCustomer(user)}
                            >
                                <div className="font-medium text-xs text-gray-800">{user.name}</div>
                                <div className="text-xs text-gray-500">{user.email}</div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
            
            <input 
                type="text" 
                placeholder="Customer Name *" 
                required
                className="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456]/30 focus:border-[#a97456] outline-none"
                value={customer.name}
                onChange={e => setCustomer({...customer, name: e.target.value})}
            />
            <div className="grid grid-cols-2 gap-2">
                <input 
                    type="email" 
                    placeholder="Email (optional)" 
                    className="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456]/30 focus:border-[#a97456] outline-none"
                    value={customer.email}
                    onChange={e => setCustomer({...customer, email: e.target.value})}
                />
                <input 
                    type="text" 
                    placeholder="Phone (optional)" 
                    className="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456]/30 focus:border-[#a97456] outline-none"
                    value={customer.phone}
                    onChange={e => setCustomer({...customer, phone: e.target.value})}
                />
            </div>
            <div className="grid grid-cols-3 gap-2">
                <select 
                    className="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456]/30 focus:border-[#a97456] outline-none"
                    value={deliveryMethod}
                    onChange={e => setDeliveryMethod(e.target.value)}
                >
                    <option value="takeaway">Pickup</option>
                    <option value="dine-in">Dine In</option>
                    <option value="delivery">Delivery</option>
                </select>
                <select 
                    className="col-span-2 w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#a97456]/30 focus:border-[#a97456] outline-none"
                    value={paymentMethod}
                    onChange={e => { setPaymentMethod(e.target.value); setCashReceived(0); setCustomCashInput(""); setShowCustomCash(false); }}
                >
                    <option value="cash">💵 Cash</option>
                    <option value="transfer">🏦 Bank Transfer</option>
                    <option value="qris">📱 QRIS</option>
                </select>
            </div>

            {/* Cash Register Section */}
            {paymentMethod === 'cash' && cart.length > 0 && (
                <div className="bg-amber-50 rounded-xl p-3 space-y-2.5 border border-amber-200/60">
                    <div className="flex items-center gap-2 text-sm font-semibold text-amber-800">
                        <i className="bi bi-cash-coin"></i>
                        <span>Cash Payment</span>
                    </div>
                    
                    {/* Quick Denomination Buttons */}
                    <div className="grid grid-cols-3 gap-1.5">
                        {getCashDenominations().map((d) => (
                            <button
                                key={String(d.value)}
                                type="button"
                                onClick={() => selectCashDenomination(d.value)}
                                className={`py-1.5 px-2 text-xs font-semibold rounded-lg border-2 transition-all ${
                                    (d.value === 'exact' && cashReceived === totalAmount && !showCustomCash) ||
                                    (d.value !== 'exact' && cashReceived === d.value && !showCustomCash)
                                        ? 'border-[#a97456] bg-[#a97456] text-white shadow-sm'
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-[#a97456]/50 hover:bg-white'
                                }`}
                            >
                                {d.label}
                            </button>
                        ))}
                        <button
                            type="button"
                            onClick={() => { setShowCustomCash(true); setCashReceived(0); setCustomCashInput(""); }}
                            className={`py-1.5 px-2 text-xs font-semibold rounded-lg border-2 transition-all ${
                                showCustomCash
                                    ? 'border-[#a97456] bg-[#a97456] text-white shadow-sm'
                                    : 'border-gray-200 bg-white text-gray-700 hover:border-[#a97456]/50 hover:bg-white'
                            }`}
                        >
                            Custom
                        </button>
                    </div>
                    
                    {/* Custom Cash Input */}
                    {showCustomCash && (
                        <div className="relative">
                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 font-medium">Rp</span>
                            <input
                                type="text"
                                placeholder="Masukkan jumlah..."
                                className="w-full pl-10 pr-3 py-2 text-sm border-2 border-[#a97456]/30 rounded-lg focus:outline-none focus:border-[#a97456] font-medium bg-white"
                                value={customCashInput}
                                onChange={e => handleCustomCashInput(e.target.value)}
                                autoFocus
                            />
                        </div>
                    )}
                    
                    {/* Cash Summary */}
                    {cashReceived > 0 && (
                        <div className="bg-white rounded-lg p-2.5 space-y-1 border border-amber-200/60">
                            <div className="flex justify-between text-xs">
                                <span className="text-gray-600">Diterima:</span>
                                <span className="font-bold text-gray-800">Rp {cashReceived.toLocaleString()}</span>
                            </div>
                            <div className={`flex justify-between text-sm font-bold ${changeAmount >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                <span>Kembalian:</span>
                                <span>{changeAmount >= 0 ? `Rp ${changeAmount.toLocaleString()}` : `Kurang Rp ${Math.abs(changeAmount).toLocaleString()}`}</span>
                            </div>
                        </div>
                    )}
                </div>
            )}
          </div>
        </div>

        {/* Pinned Bottom: Total + Submit (always visible) */}
        <div className="flex-shrink-0 border-t-2 border-gray-200 bg-white">
            {/* Total */}
            <div className="px-4 py-2.5 flex justify-between items-center bg-gradient-to-r from-[#a97456]/5 to-[#a97456]/10">
                <div>
                    <div className="text-xs text-gray-500">Total</div>
                    <div className="text-xl font-extrabold text-[#a97456]">
                        Rp {calculateTotal().toLocaleString()}
                    </div>
                </div>
                {paymentMethod === 'cash' && cashReceived > 0 && changeAmount >= 0 && (
                    <div className="text-right">
                        <div className="text-xs text-gray-500">Kembalian</div>
                        <div className="text-lg font-bold text-green-600">
                            Rp {changeAmount.toLocaleString()}
                        </div>
                    </div>
                )}
            </div>

            {error && (
                <div className="mx-4 mt-2 p-2 bg-red-100 text-red-600 text-xs rounded">
                    {error}
                </div>
            )}

            {/* Submit Button */}
            <div className="p-3">
                <button
                    onClick={handleSubmit}
                    disabled={submitting || cart.length === 0 || (paymentMethod === 'cash' && cashReceived < totalAmount)}
                    className="w-full py-3 bg-[#a97456] text-white rounded-xl font-bold hover:bg-[#8b6043] disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-lg shadow-[#a97456]/20 text-sm"
                >
                    {submitting ? (
                      <span className="flex items-center justify-center gap-2">
                        <i className="bi bi-arrow-repeat animate-spin"></i> Processing...
                      </span>
                    ) : paymentMethod === 'cash' && cashReceived < totalAmount && cart.length > 0
                        ? `💵 Cash belum cukup (kurang Rp ${(totalAmount - cashReceived).toLocaleString()})`
                        : (
                          <span className="flex items-center justify-center gap-2">
                            <i className="bi bi-check-circle"></i> Place Order
                          </span>
                        )
                    }
                </button>
            </div>
        </div>
      </div>

      {/* Product Customization Modal */}
      {showCustomizeModal && selectedProduct && (
        <div className="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95 fade-in duration-200">
            {/* Modal Header */}
            <div className="bg-gradient-to-r from-[#a97456] to-[#8b6043] text-white p-5">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-lg font-bold">{selectedProduct.name}</h3>
                  <p className="text-white/80 text-sm mt-0.5">
                    Base Price: Rp {Number(selectedProduct.price).toLocaleString()}
                  </p>
                </div>
                <button
                  onClick={() => setShowCustomizeModal(false)}
                  className="text-white/80 hover:text-white p-1"
                >
                  <i className="bi bi-x-lg text-xl"></i>
                </button>
              </div>
            </div>
            
            {/* Modal Body */}
            <div className="p-5 space-y-5 max-h-[70vh] overflow-y-auto">
              {/* Size Selection */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2.5">
                  <i className="bi bi-cup-hot mr-1.5 text-[#a97456]"></i>
                  Ukuran
                </label>
                <div className="grid grid-cols-3 gap-2">
                  {SIZES.map((size) => (
                    <button
                      key={size.code}
                      type="button"
                      onClick={() => setCustomization({ ...customization, size: size.code })}
                      className={`py-3 px-4 rounded-xl border-2 font-semibold transition-all ${
                        customization.size === size.code
                          ? "border-[#a97456] bg-[#a97456] text-white shadow-md"
                          : "border-gray-200 bg-white text-gray-700 hover:border-[#a97456]/50"
                      }`}
                    >
                      <div className="text-sm">{size.name}</div>
                      {size.modifier > 0 && (
                        <div className="text-xs mt-0.5 opacity-90">+Rp {size.modifier.toLocaleString()}</div>
                      )}
                    </button>
                  ))}
                </div>
              </div>

              {/* Temperature Selection */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2.5">
                  <i className="bi bi-thermometer-half mr-1.5 text-[#a97456]"></i>
                  Suhu
                </label>
                <div className="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    onClick={() => setCustomization({ ...customization, temperature: "hot" })}
                    className={`py-3 px-4 rounded-xl border-2 font-semibold transition-all ${
                      customization.temperature === "hot"
                        ? "border-[#a97456] bg-[#a97456] text-white shadow-md"
                        : "border-gray-200 bg-white text-gray-700 hover:border-[#a97456]/50"
                    }`}
                  >
                    <i className="bi bi-fire mr-1.5"></i>
                    Hot
                  </button>
                  <button
                    type="button"
                    onClick={() => setCustomization({ ...customization, temperature: "ice" })}
                    className={`py-3 px-4 rounded-xl border-2 font-semibold transition-all ${
                      customization.temperature === "ice"
                        ? "border-[#a97456] bg-[#a97456] text-white shadow-md"
                        : "border-gray-200 bg-white text-gray-700 hover:border-[#a97456]/50"
                    }`}
                  >
                    <i className="bi bi-snow mr-1.5"></i>
                    Ice
                  </button>
                </div>
              </div>

              {/* Add-ons Selection */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2.5">
                  <i className="bi bi-plus-circle mr-1.5 text-[#a97456]"></i>
                  Tambahan (Opsional)
                </label>
                <div className="space-y-2">
                  {ADDONS.map((addon) => {
                    const isSelected = customization.addons.some(a => a.code === addon.code);
                    return (
                      <button
                        key={addon.code}
                        type="button"
                        onClick={() => {
                          if (isSelected) {
                            setCustomization({
                              ...customization,
                              addons: customization.addons.filter(a => a.code !== addon.code)
                            });
                          } else {
                            setCustomization({
                              ...customization,
                              addons: [...customization.addons, addon]
                            });
                          }
                        }}
                        className={`w-full py-2.5 px-4 rounded-lg border-2 text-left transition-all flex items-center justify-between ${
                          isSelected
                            ? "border-[#a97456] bg-[#a97456]/10"
                            : "border-gray-200 bg-white hover:border-[#a97456]/30"
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <div className={`w-5 h-5 rounded border-2 flex items-center justify-center ${
                            isSelected ? "border-[#a97456] bg-[#a97456]" : "border-gray-300"
                          }`}>
                            {isSelected && <i className="bi bi-check text-white text-xs font-bold"></i>}
                          </div>
                          <span className="font-medium text-sm text-gray-800">{addon.name}</span>
                        </div>
                        <span className="text-sm font-semibold text-[#a97456]">
                          +Rp {addon.price.toLocaleString()}
                        </span>
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* Custom Notes */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2.5">
                  <i className="bi bi-pencil mr-1.5 text-[#a97456]"></i>
                  Catatan Khusus (Opsional)
                </label>
                <textarea
                  value={customization.notes}
                  onChange={(e) => setCustomization({ ...customization, notes: e.target.value })}
                  placeholder="Misal: Gula sedikit, Tidak terlalu panas, dll..."
                  className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-[#a97456] focus:ring-2 focus:ring-[#a97456]/20 outline-none resize-none text-sm"
                  rows={3}
                />
              </div>

              {/* Price Summary */}
              <div className="bg-amber-50 border-2 border-amber-200 rounded-xl p-4 space-y-2">
                <div className="text-xs font-semibold text-amber-800 uppercase tracking-wide mb-2">
                  Rincian Harga
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600">Base Price</span>
                  <span className="font-medium">Rp {Number(selectedProduct.price).toLocaleString()}</span>
                </div>
                {(() => {
                  const sizeObj = SIZES.find(s => s.code === customization.size);
                  const sizeModifier = sizeObj?.modifier || 0;
                  return sizeModifier > 0 && (
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-600">Size ({sizeObj?.name})</span>
                      <span className="font-medium text-[#a97456]">+Rp {sizeModifier.toLocaleString()}</span>
                    </div>
                  );
                })()}
                {customization.addons.length > 0 && customization.addons.map((addon) => (
                  <div key={addon.code} className="flex justify-between text-sm">
                    <span className="text-gray-600">{addon.name}</span>
                    <span className="font-medium text-[#a97456]">+Rp {addon.price.toLocaleString()}</span>
                  </div>
                ))}
                <div className="border-t border-amber-300 pt-2 mt-2 flex justify-between font-bold text-base">
                  <span>Total Harga</span>
                  <span className="text-[#a97456]">
                    Rp {(() => {
                      const sizeObj = SIZES.find(s => s.code === customization.size);
                      const sizeModifier = sizeObj?.modifier || 0;
                      const addonsTotal = customization.addons.reduce((sum, addon) => sum + addon.price, 0);
                      return (Number(selectedProduct.price) + sizeModifier + addonsTotal).toLocaleString();
                    })()}
                  </span>
                </div>
              </div>
            </div>
            
            {/* Modal Footer */}
            <div className="p-5 pt-0 flex gap-3">
              <button
                onClick={() => setShowCustomizeModal(false)}
                className="flex-1 py-3 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 transition-colors"
              >
                Batal
              </button>
              <button
                onClick={confirmAddToCart}
                className="flex-1 py-3 bg-gradient-to-r from-[#a97456] to-[#8b6043] text-white rounded-xl font-bold hover:shadow-lg transition-all"
              >
                <i className="bi bi-cart-plus mr-2"></i>
                Masukkan Keranjang
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Edit Cart Item Modal */}
      {showEditModal && editingItemIndex !== null && cart[editingItemIndex] && (
        <div className="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95 fade-in duration-200">
            {/* Modal Header */}
            <div className="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-5">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-lg font-bold">✏️ Edit Item</h3>
                  <p className="text-white/80 text-sm mt-0.5">
                    {cart[editingItemIndex].name}
                  </p>
                </div>
                <button
                  onClick={() => { setShowEditModal(false); setEditingItemIndex(null); }}
                  className="text-white/80 hover:text-white p-1"
                >
                  <i className="bi bi-x-lg text-xl"></i>
                </button>
              </div>
            </div>
            
            {/* Modal Body */}
            <div className="p-5 space-y-5 max-h-[70vh] overflow-y-auto">
              {/* Size Selection */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2.5">
                  <i className="bi bi-cup-hot mr-1.5 text-blue-600"></i>
                  Ukuran
                </label>
                <div className="grid grid-cols-3 gap-2">
                  {SIZES.map((size) => (
                    <button
                      key={size.code}
                      type="button"
                      onClick={() => setEditCustomization({ ...editCustomization, size: size.code })}
                      className={`py-3 px-4 rounded-xl border-2 font-semibold transition-all ${
                        editCustomization.size === size.code
                          ? "border-blue-600 bg-blue-600 text-white shadow-md"
                          : "border-gray-200 bg-white text-gray-700 hover:border-blue-600/50"
                      }`}
                    >
                      <div className="text-sm">{size.name}</div>
                      {size.modifier > 0 && (
                        <div className="text-xs mt-0.5 opacity-90">+Rp {size.modifier.toLocaleString()}</div>
                      )}
                    </button>
                  ))}
                </div>
              </div>

              {/* Temperature Selection */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2.5">
                  <i className="bi bi-thermometer-half mr-1.5 text-blue-600"></i>
                  Suhu
                </label>
                <div className="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    onClick={() => setEditCustomization({ ...editCustomization, temperature: "hot" })}
                    className={`py-3 px-4 rounded-xl border-2 font-semibold transition-all ${
                      editCustomization.temperature === "hot"
                        ? "border-blue-600 bg-blue-600 text-white shadow-md"
                        : "border-gray-200 bg-white text-gray-700 hover:border-blue-600/50"
                    }`}
                  >
                    <i className="bi bi-fire mr-1.5"></i>
                    Hot
                  </button>
                  <button
                    type="button"
                    onClick={() => setEditCustomization({ ...editCustomization, temperature: "ice" })}
                    className={`py-3 px-4 rounded-xl border-2 font-semibold transition-all ${
                      editCustomization.temperature === "ice"
                        ? "border-blue-600 bg-blue-600 text-white shadow-md"
                        : "border-gray-200 bg-white text-gray-700 hover:border-blue-600/50"
                    }`}
                  >
                    <i className="bi bi-snow mr-1.5"></i>
                    Ice
                  </button>
                </div>
              </div>

              {/* Add-ons Selection */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2.5">
                  <i className="bi bi-plus-circle mr-1.5 text-blue-600"></i>
                  Tambahan (Opsional)
                </label>
                <div className="space-y-2">
                  {ADDONS.map((addon) => {
                    const isSelected = editCustomization.addons.some(a => a.code === addon.code);
                    return (
                      <button
                        key={addon.code}
                        type="button"
                        onClick={() => {
                          if (isSelected) {
                            setEditCustomization({
                              ...editCustomization,
                              addons: editCustomization.addons.filter(a => a.code !== addon.code)
                            });
                          } else {
                            setEditCustomization({
                              ...editCustomization,
                              addons: [...editCustomization.addons, addon]
                            });
                          }
                        }}
                        className={`w-full py-2.5 px-4 rounded-lg border-2 text-left transition-all flex items-center justify-between ${
                          isSelected
                            ? "border-blue-600 bg-blue-50"
                            : "border-gray-200 bg-white hover:border-blue-600/30"
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <div className={`w-5 h-5 rounded border-2 flex items-center justify-center ${
                            isSelected ? "border-blue-600 bg-blue-600" : "border-gray-300"
                          }`}>
                            {isSelected && <i className="bi bi-check text-white text-xs font-bold"></i>}
                          </div>
                          <span className="font-medium text-sm text-gray-800">{addon.name}</span>
                        </div>
                        <span className="text-sm font-semibold text-blue-600">
                          +Rp {addon.price.toLocaleString()}
                        </span>
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* Custom Notes */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2.5">
                  <i className="bi bi-pencil mr-1.5 text-blue-600"></i>
                  Catatan Khusus (Opsional)
                </label>
                <textarea
                  value={editCustomization.notes}
                  onChange={(e) => setEditCustomization({ ...editCustomization, notes: e.target.value })}
                  placeholder="Misal: Gula sedikit, Tidak terlalu panas, dll..."
                  className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 outline-none resize-none text-sm"
                  rows={3}
                />
              </div>

              {/* Price Summary */}
              <div className="bg-blue-50 border-2 border-blue-200 rounded-xl p-4 space-y-2">
                <div className="text-xs font-semibold text-blue-800 uppercase tracking-wide mb-2">
                  Rincian Harga Baru
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600">Base Price</span>
                  <span className="font-medium">Rp {(cart[editingItemIndex].basePrice || cart[editingItemIndex].price).toLocaleString()}</span>
                </div>
                {(() => {
                  const sizeObj = SIZES.find(s => s.code === editCustomization.size);
                  const sizeModifier = sizeObj?.modifier || 0;
                  return sizeModifier > 0 && (
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-600">Size ({sizeObj?.name})</span>
                      <span className="font-medium text-blue-600">+Rp {sizeModifier.toLocaleString()}</span>
                    </div>
                  );
                })()}
                {editCustomization.addons.length > 0 && editCustomization.addons.map((addon) => (
                  <div key={addon.code} className="flex justify-between text-sm">
                    <span className="text-gray-600">{addon.name}</span>
                    <span className="font-medium text-blue-600">+Rp {addon.price.toLocaleString()}</span>
                  </div>
                ))}
                <div className="border-t border-blue-300 pt-2 mt-2 flex justify-between font-bold text-base">
                  <span>Total Harga</span>
                  <span className="text-blue-600">
                    Rp {(() => {
                      const basePrice = cart[editingItemIndex].basePrice || cart[editingItemIndex].price;
                      const sizeObj = SIZES.find(s => s.code === editCustomization.size);
                      const sizeModifier = sizeObj?.modifier || 0;
                      const addonsTotal = editCustomization.addons.reduce((sum, addon) => sum + addon.price, 0);
                      return (basePrice + sizeModifier + addonsTotal).toLocaleString();
                    })()}
                  </span>
                </div>
              </div>
            </div>
            
            {/* Modal Footer */}
            <div className="p-5 pt-0 flex gap-3">
              <button
                onClick={() => { setShowEditModal(false); setEditingItemIndex(null); }}
                className="flex-1 py-3 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 transition-colors"
              >
                Batal
              </button>
              <button
                onClick={saveEditedItem}
                className="flex-1 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-bold hover:shadow-lg transition-all"
              >
                <i className="bi bi-check-circle mr-2"></i>
                Simpan Perubahan
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Receipt Modal */}
      {showReceipt && receiptData && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-in zoom-in-95 fade-in duration-200">
            {/* Receipt Header */}
            <div className="bg-[#a97456] text-white p-5 text-center">
              <div className="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                <i className="bi bi-check-circle text-3xl"></i>
              </div>
              <h3 className="text-lg font-bold">Order Berhasil!</h3>
              <p className="text-white/80 text-sm mt-1">#{receiptData.orderNumber}</p>
            </div>
            
            {/* Receipt Body */}
            <div className="p-5 space-y-4">
              {/* Items */}
              <div className="space-y-1.5">
                {receiptData.items.map((item, i) => (
                  <div key={i} className="flex justify-between text-sm">
                    <span className="text-gray-600">{item.quantity}x {item.name}</span>
                    <span className="font-medium">Rp {(item.price * item.quantity).toLocaleString()}</span>
                  </div>
                ))}
              </div>
              
              <div className="border-t border-dashed border-gray-300 pt-3 space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600">Customer</span>
                  <span className="font-medium">{receiptData.customerName}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600">Metode</span>
                  <span className="font-medium">{receiptData.paymentMethod}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600">Tipe</span>
                  <span className="font-medium capitalize">{receiptData.deliveryMethod}</span>
                </div>
              </div>
              
              <div className="border-t border-dashed border-gray-300 pt-3 space-y-2">
                <div className="flex justify-between font-bold text-base">
                  <span>Total</span>
                  <span className="text-[#a97456]">Rp {receiptData.total.toLocaleString()}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600">Dibayar</span>
                  <span className="font-semibold">Rp {receiptData.cashReceived.toLocaleString()}</span>
                </div>
                {receiptData.change > 0 && (
                  <div className="flex justify-between bg-green-50 -mx-5 px-5 py-2.5 text-green-700 font-bold text-lg">
                    <span>Kembalian</span>
                    <span>Rp {receiptData.change.toLocaleString()}</span>
                  </div>
                )}
              </div>
            </div>
            
            {/* Receipt Footer */}
            <div className="p-5 pt-0 flex gap-2">
              <button
                onClick={() => { setShowReceipt(false); setReceiptData(null); }}
                className="flex-1 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors text-sm"
              >
                Tutup
              </button>
              <button
                onClick={() => { setShowReceipt(false); setReceiptData(null); }}
                className="flex-1 py-2.5 bg-[#a97456] text-white rounded-lg font-medium hover:bg-[#8b6043] transition-colors text-sm"
              >
                Order Baru
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Xendit Payment Modal */}
      {showPaymentModal && paymentData && (
        <div className="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in-95 fade-in duration-200">
            {/* Modal Header */}
            <div className="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 text-center">
              <div className="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                <i className="bi bi-qr-code text-3xl"></i>
              </div>
              <h3 className="text-lg font-bold">Order Berhasil Dibuat!</h3>
              <p className="text-white/80 text-sm mt-1">#{paymentData.orderNumber}</p>
              <p className="text-white/90 text-base font-semibold mt-2">
                Rp {paymentData.total.toLocaleString()}
              </p>
            </div>
            
            {/* Modal Body */}
            <div className="p-6 space-y-4">
              <div className="bg-blue-50 border-2 border-blue-200 rounded-xl p-4">
                <p className="text-sm text-blue-800 font-semibold mb-2">
                  💳 Pembayaran Online dengan Xendit
                </p>
                <p className="text-xs text-blue-600">
                  Customer dapat membayar melalui berbagai metode: Bank Transfer, E-Wallet, QRIS, Credit Card
                </p>
              </div>

              {/* Payment Link */}
              <div>
                <label className="block text-xs font-semibold text-gray-600 mb-2">Payment Link:</label>
                <div className="flex gap-2">
                  <input
                    type="text"
                    value={paymentData.invoiceUrl}
                    readOnly
                    className="flex-1 px-3 py-2 text-xs border border-gray-300 rounded-lg bg-gray-50 font-mono"
                  />
                  <button
                    onClick={() => {
                      navigator.clipboard.writeText(paymentData.invoiceUrl);
                      alert("Link copied!");
                    }}
                    className="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium transition-colors"
                    title="Copy Link"
                  >
                    <i className="bi bi-clipboard"></i>
                  </button>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="grid grid-cols-2 gap-2">
                <a
                  href={paymentData.invoiceUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-sm text-center transition-colors"
                >
                  <i className="bi bi-box-arrow-up-right mr-2"></i>
                  Open Payment
                </a>
                <a
                  href={`https://wa.me/?text=${encodeURIComponent(`Hi! Silakan bayar order Anda:\n\n*Order #${paymentData.orderNumber}*\nTotal: Rp ${paymentData.total.toLocaleString()}\n\nLink pembayaran:\n${paymentData.invoiceUrl}`)}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold text-sm text-center transition-colors"
                >
                  <i className="bi bi-whatsapp mr-2"></i>
                  Share WhatsApp
                </a>
              </div>

              {/* Info */}
              <div className="bg-gray-50 rounded-lg p-3">
                <p className="text-xs text-gray-600 leading-relaxed">
                  <i className="bi bi-info-circle mr-1"></i>
                  <strong>Status pembayaran akan otomatis ter-update</strong> ketika customer selesai membayar. 
                  Anda bisa menutup modal ini dan melayani customer lain.
                </p>
              </div>
            </div>
            
            {/* Modal Footer */}
            <div className="p-6 pt-0 flex gap-3">
              <button
                onClick={() => router.push('/admin/orders')}
                className="flex-1 py-3 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 transition-colors"
              >
                Lihat Orders
              </button>
              <button
                onClick={() => { setShowPaymentModal(false); setPaymentData(null); }}
                className="flex-1 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-colors"
              >
                Order Baru
              </button>
            </div>
          </div>
        </div>
      )}    </div>
  );
}