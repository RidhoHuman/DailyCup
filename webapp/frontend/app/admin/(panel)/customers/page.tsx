"use client";

import { useState } from "react";
import Link from "next/link";

interface Customer {
  id: number;
  name: string;
  email: string;
  orders: number;
  spent: number;
  joined: string;
}

export default function AdminCustomersPage() {
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null);

  // Mock data - replace with API call
  const customers = [
    { id: 1, name: "Ridho", email: "ridhohuman11@gmail.com", orders: 15, spent: 1250000, joined: "2026-01-15" },
    { id: 2, name: "John Doe", email: "john@example.com", orders: 8, spent: 640000, joined: "2026-01-20" },
    { id: 3, name: "Jane Smith", email: "jane@example.com", orders: 12, spent: 980000, joined: "2026-01-18" },
  ];

  const filteredCustomers = customers.filter(
    (customer) =>
      customer.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      customer.email.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
    }).format(amount);
  };

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Customers</h1>
        <p className="text-gray-500">Manage customer accounts and information</p>
      </div>

      {/* Search Bar */}
      <div className="mb-6">
        <input
          type="text"
          placeholder="Search customers by name or email..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="w-full md:w-96 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
        />
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="text-sm text-gray-500 mb-1">Total Customers</div>
          <div className="text-3xl font-bold text-gray-800">{customers.length}</div>
        </div>
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="text-sm text-gray-500 mb-1">Total Revenue</div>
          <div className="text-3xl font-bold text-gray-800">
            {formatCurrency(customers.reduce((sum, c) => sum + c.spent, 0))}
          </div>
        </div>
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div className="text-sm text-gray-500 mb-1">Avg Orders/Customer</div>
          <div className="text-3xl font-bold text-gray-800">
            {(customers.reduce((sum, c) => sum + c.orders, 0) / customers.length).toFixed(1)}
          </div>
        </div>
      </div>

      {/* Customers Table */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr className="text-left text-xs font-semibold text-gray-500 uppercase">
                <th className="px-6 py-4">Customer</th>
                <th className="px-6 py-4">Orders</th>
                <th className="px-6 py-4">Total Spent</th>
                <th className="px-6 py-4">Joined</th>
                <th className="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {filteredCustomers.map((customer) => (
                <tr key={customer.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-6 py-4">
                    <div>
                      <div className="font-medium text-gray-800">{customer.name}</div>
                      <div className="text-sm text-gray-500">{customer.email}</div>
                    </div>
                  </td>
                  <td className="px-6 py-4 text-gray-600">{customer.orders}</td>
                  <td className="px-6 py-4 font-semibold text-gray-800">
                    {formatCurrency(customer.spent)}
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-600">
                    {new Date(customer.joined).toLocaleDateString("id-ID")}
                  </td>
                  <td className="px-6 py-4 text-right">
                    <button 
                      onClick={() => setSelectedCustomer(customer)}
                      className="text-[#a97456] hover:text-[#8f6249] font-medium text-sm flex items-center gap-1 ml-auto"
                    >
                      <i className="bi bi-person-circle"></i>
                      View Profile
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Customer Profile Modal */}
      {selectedCustomer && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl max-w-2xl w-full p-6">
            <div className="flex justify-between items-start mb-6">
              <div>
                <h2 className="text-2xl font-bold text-gray-800">Customer Profile</h2>
                <p className="text-sm text-gray-500 mt-1">Customer ID: #{selectedCustomer.id}</p>
              </div>
              <button
                onClick={() => setSelectedCustomer(null)}
                className="text-gray-400 hover:text-gray-600 text-2xl"
              >
                <i className="bi bi-x-lg"></i>
              </button>
            </div>

            <div className="space-y-4">
              <div className="flex items-center gap-4 pb-4 border-b">
                <div className="w-20 h-20 rounded-full bg-[#a97456] flex items-center justify-center text-white text-3xl font-bold">
                  {selectedCustomer.name.charAt(0)}
                </div>
                <div>
                  <h3 className="text-xl font-bold text-gray-800">{selectedCustomer.name}</h3>
                  <p className="text-gray-600">{selectedCustomer.email}</p>
                  <p className="text-sm text-gray-500 mt-1">
                    Member since {new Date(selectedCustomer.joined).toLocaleDateString("id-ID", { 
                      year: 'numeric', 
                      month: 'long', 
                      day: 'numeric' 
                    })}
                  </p>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="bg-blue-50 rounded-lg p-4">
                  <div className="text-sm text-blue-600 mb-1">Total Orders</div>
                  <div className="text-3xl font-bold text-blue-700">{selectedCustomer.orders}</div>
                </div>
                <div className="bg-green-50 rounded-lg p-4">
                  <div className="text-sm text-green-600 mb-1">Total Spent</div>
                  <div className="text-2xl font-bold text-green-700">{formatCurrency(selectedCustomer.spent)}</div>
                </div>
              </div>

              <div className="bg-gray-50 rounded-lg p-4">
                <h4 className="font-semibold text-gray-800 mb-2">Customer Stats</h4>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-gray-600">Average Order Value:</span>
                    <span className="font-medium text-gray-800">
                      {formatCurrency(selectedCustomer.spent / selectedCustomer.orders)}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Status:</span>
                    <span className="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">
                      Active Customer
                    </span>
                  </div>
                </div>
              </div>

              <div className="flex gap-3 pt-4">
                <button
                  onClick={() => setSelectedCustomer(null)}
                  className="flex-1 px-4 py-2 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                >
                  Close
                </button>
                <Link
                  href={`/admin/customers/${selectedCustomer.id}/orders`}
                  className="flex-1 px-4 py-2 bg-[#a97456] text-white rounded-lg font-medium hover:bg-[#8f6249] transition-colors text-center flex items-center justify-center gap-2"
                >
                  <i className="bi bi-bag"></i>
                  View Order History
                </Link>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
