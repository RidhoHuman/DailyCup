'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useState, useEffect } from 'react';
import { provinces, cities, districts } from '@/data/indonesia-areas';
import { useAuthStore } from '@/lib/stores/auth-store';

// Validation Schema
const checkoutSchema = z.object({
  fullName: z.string().min(3, "Name must be at least 3 characters"),
  email: z.string().email("Invalid email address"),
  phone: z.string().min(10, "Phone number must be at least 10 digits").regex(/^[0-9]+$/, "Must be numbers only"),
  province: z.string().min(1, "Please select province"),
  city: z.string().min(1, "Please select city"),
  district: z.string().min(1, "Please select district"),
  addressDetail: z.string().min(10, "Please include street name, house number, etc."),
  saveAddress: z.boolean().optional(),
});

type CheckoutFormValues = z.infer<typeof checkoutSchema>;

interface AddressFormProps {
  onSubmit: (data: CheckoutFormValues) => void;
  defaultValues?: Partial<CheckoutFormValues>;
  id?: string;
}

export default function AddressForm({ onSubmit, id }: AddressFormProps) {
  const { user } = useAuthStore();
  
  // Initialize form
  const {
    register,
    handleSubmit,
    watch,
    setValue,
    formState: { errors, touchedFields }
  } = useForm<CheckoutFormValues>({
    resolver: zodResolver(checkoutSchema),
    mode: 'onChange', // Validate on change (Real-time)
    defaultValues: {
      fullName: user?.name || '',
      email: user?.email || '',
      saveAddress: true
    }
  });

  // Watch region selection for cascading dropdowns
  const selectedProvince = watch('province');
  const selectedCity = watch('city');

  interface RegionItem {
    id: string;
    name: string;
  }

  const [availableCities, setAvailableCities] = useState<RegionItem[]>([]);
  const [availableDistricts, setAvailableDistricts] = useState<RegionItem[]>([]);

  // CRM Logic: Load mock saved address if user logged in
  useEffect(() => {
    if (user) {
      // Simulation: In real app, this comes from API / user profile
      // console.log("Pre-filling address from CRM for user:", user.name);
    }
  }, [user]);

  // Logic for cascading dropdowns
  useEffect(() => {
    if (selectedProvince) {
      setAvailableCities(cities[selectedProvince] || []);
      // Reset child fields when parent changes
      setValue('city', '');
      setValue('district', '');
    }
  }, [selectedProvince, setValue]);

  useEffect(() => {
    if (selectedCity) {
      setAvailableDistricts(districts[selectedCity] || districts['DEFAULT']);
      setValue('district', '');
    }
  }, [selectedCity, setValue]);

  // Helper for inline validation icon
  const ValidationIcon = ({ fieldName }: { fieldName: keyof CheckoutFormValues }) => {
    if (!touchedFields[fieldName]) return null;
    return errors[fieldName] ? (
      <i className="bi bi-x-circle-fill text-red-500 absolute right-3 top-1/2 -translate-y-1/2"></i>
    ) : (
      <i className="bi bi-check-circle-fill text-green-500 absolute right-3 top-1/2 -translate-y-1/2"></i>
    );
  };

  return (
    <form id={id || "checkout-form"} onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      
      {/* Name & Contact */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="space-y-2">
          <label className="text-sm font-medium">Full Name</label>
          <div className="relative">
            <input
              {...register('fullName')}
              className={`w-full p-2 pl-3 pr-10 border rounded-lg focus:ring-2 outline-none transition-all ${
                errors.fullName ? 'border-red-500 focus:ring-red-200' : 'border-gray-300 focus:ring-[#a97456]'
              }`}
              placeholder="Jhon Doe"
            />
            <ValidationIcon fieldName="fullName" />
          </div>
          {errors.fullName && <p className="text-xs text-red-500">{errors.fullName.message}</p>}
        </div>

        <div className="space-y-2">
          <label className="text-sm font-medium">Email Address</label>
          <div className="relative">
            <input
              {...register('email')}
              className={`w-full p-2 pl-3 pr-10 border rounded-lg focus:ring-2 outline-none transition-all ${
                errors.email ? 'border-red-500 focus:ring-red-200' : 'border-gray-300 focus:ring-[#a97456]'
              }`}
              placeholder="name@example.com"
            />
            <ValidationIcon fieldName="email" />
          </div>
          {errors.email && <p className="text-xs text-red-500">{errors.email.message}</p>}
        </div>
      </div>

      <div className="space-y-2">
        <label className="text-sm font-medium">Phone Number (WhatsApp)</label>
        <div className="relative">
          <input
            {...register('phone')}
            className={`w-full p-2 pl-3 pr-10 border rounded-lg focus:ring-2 outline-none transition-all ${
              errors.phone ? 'border-red-500 focus:ring-red-200' : 'border-gray-300 focus:ring-[#a97456]'
            }`}
            placeholder="08123456789"
          />
           <ValidationIcon fieldName="phone" />
        </div>
        {errors.phone && <p className="text-xs text-red-500">{errors.phone.message}</p>}
      </div>

      <hr className="border-gray-200" />

      {/* Regioanl Dropdowns */}
      <h3 className="font-semibold text-gray-700">Shipping Address</h3>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="space-y-2">
          <label className="text-sm font-medium">Province</label>
          <select
            {...register('province')}
            className={`w-full p-2 border rounded-lg bg-white ${errors.province ? 'border-red-500' : 'border-gray-300'}`}
          >
            <option value="">Select Province</option>
            {provinces.map((p) => (
              <option key={p.id} value={p.id}>{p.name}</option>
            ))}
          </select>
          {errors.province && <p className="text-xs text-red-500">{errors.province.message}</p>}
        </div>

        <div className="space-y-2">
          <label className="text-sm font-medium">City / Kota</label>
          <select
            {...register('city')}
            disabled={!selectedProvince}
            className={`w-full p-2 border rounded-lg bg-white disabled:bg-gray-100 disabled:text-gray-400 ${errors.city ? 'border-red-500' : 'border-gray-300'}`}
          >
            <option value="">Select City</option>
            {availableCities.map((c) => (
              <option key={c.id} value={c.id}>{c.name}</option>
            ))}
          </select>
          {errors.city && <p className="text-xs text-red-500">{errors.city.message}</p>}
        </div>

        <div className="space-y-2">
          <label className="text-sm font-medium">District / Kecamatan</label>
          <select
            {...register('district')}
            disabled={!selectedCity}
            className={`w-full p-2 border rounded-lg bg-white disabled:bg-gray-100 disabled:text-gray-400 ${errors.district ? 'border-red-500' : 'border-gray-300'}`}
          >
            <option value="">Select District</option>
            {availableDistricts.map((d) => (
              <option key={d.id} value={d.id}>{d.name}</option>
            ))}
          </select>
          {errors.district && <p className="text-xs text-red-500">{errors.district.message}</p>}
        </div>
      </div>

      <div className="space-y-2">
        <label className="text-sm font-medium">Full Address Detail</label>
        <textarea
          {...register('addressDetail')}
          rows={3}
          className={`w-full p-2 border rounded-lg focus:ring-2 outline-none transition-all ${
            errors.addressDetail ? 'border-red-500 focus:ring-red-200' : 'border-gray-300 focus:ring-[#a97456]'
          }`}
          placeholder="Jl. Kebun Jeruk No. 12, RT/RW 01/02..."
        />
        {errors.addressDetail && <p className="text-xs text-red-500">{errors.addressDetail.message}</p>}
      </div>

      {/* CRM Feature: Save Address */}
      {user && (
        <div className="flex items-center space-x-2 bg-[#f6efe9] p-3 rounded-lg border border-[#e7d6cc]">
          <input
            type="checkbox"
            id="saveAddress"
            {...register('saveAddress')}
            className="w-4 h-4 text-[#a97456] rounded focus:ring-[#a97456]"
          />
          <label htmlFor="saveAddress" className="text-sm font-medium text-gray-700 cursor-pointer">
            Save this address for future purchases
          </label>
        </div>
      )}
    </form>
  );
}
