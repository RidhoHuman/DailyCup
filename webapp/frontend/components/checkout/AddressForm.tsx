'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useState, useEffect, useCallback } from 'react';
import { provinces, cities, districts } from '@/data/indonesia-areas';
import { useAuthStore } from '@/lib/stores/auth-store';
import { api } from '@/lib/api-client';

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
  // Add lat/lng for delivery validation
  latitude: z.number().optional(),
  longitude: z.number().optional(),
});

type CheckoutFormValues = z.infer<typeof checkoutSchema>;

interface AddressFormProps {
  onSubmit: (data: CheckoutFormValues) => void;
  defaultValues?: Partial<CheckoutFormValues>;
  id?: string;
  onDeliveryCheck?: (available: boolean, outletName?: string, distance?: number) => void;
}

interface DeliveryCheckResult {
  available: boolean;
  nearest_outlet?: {
    name: string;
    distance_km: number;
    max_radius_km: number;
    city: string;
  };
  reason?: string;
}

export default function AddressForm({ onSubmit, id, onDeliveryCheck }: AddressFormProps) {
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

  // Location & Delivery Check State
  const [location, setLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [locationLoading, setLocationLoading] = useState(false);
  const [locationError, setLocationError] = useState<string | null>(null);
  const [deliveryCheck, setDeliveryCheck] = useState<DeliveryCheckResult | null>(null);
  const [checkingDelivery, setCheckingDelivery] = useState(false);
  const [reverseGeocodingDone, setReverseGeocodingDone] = useState(false);

  // Reverse geocoding function using OpenStreetMap Nominatim
  const reverseGeocode = useCallback(async (lat: number, lng: number) => {
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&addressdetails=1`,
        {
          headers: {
            'Accept-Language': 'id',
            'User-Agent': 'DailyCup/1.0'
          }
        }
      );
      const data = await response.json();
      
      if (data && data.address) {
        const address = data.address;
        
        // Find matching province
        const provinceName = address.state || '';
        const matchedProvince = provinces.find(p => 
          p.name.toLowerCase().includes(provinceName.toLowerCase()) ||
          provinceName.toLowerCase().includes(p.name.toLowerCase())
        );
        
        if (matchedProvince) {
          setValue('province', matchedProvince.id, { shouldValidate: true });
          
          // Wait for cities to load then match city
          setTimeout(() => {
            const cityName = address.city || address.county || address.town || '';
            const availCities = cities[matchedProvince.id] || [];
            const matchedCity = availCities.find(c => 
              c.name.toLowerCase().includes(cityName.toLowerCase()) ||
              cityName.toLowerCase().includes(c.name.toLowerCase())
            );
            
            if (matchedCity) {
              setValue('city', matchedCity.id, { shouldValidate: true });
              
              // Wait for districts to load then match district
              setTimeout(() => {
                const districtName = address.suburb || address.neighbourhood || address.village || address.subdistrict || '';
                const availDistricts = districts[matchedCity.id] || districts['DEFAULT'];
                const matchedDistrict = availDistricts.find(d => 
                  d.name.toLowerCase().includes(districtName.toLowerCase()) ||
                  districtName.toLowerCase().includes(d.name.toLowerCase())
                );
                
                if (matchedDistrict) {
                  setValue('district', matchedDistrict.id, { shouldValidate: true });
                }
                setReverseGeocodingDone(true);
              }, 200);
            } else {
              setReverseGeocodingDone(true);
            }
          }, 200);
        } else {
          setReverseGeocodingDone(true);
        }
        
        // Also try to fill address detail with road/house number
        const roadInfo = [address.road, address.house_number].filter(Boolean).join(' ');
        if (roadInfo) {
          const currentDetail = watch('addressDetail') || '';
          if (!currentDetail) {
            setValue('addressDetail', roadInfo);
          }
        }
      }
    } catch (error) {
      console.error('Reverse geocoding error:', error);
      setReverseGeocodingDone(true);
    }
  }, [setValue, watch]);

  // Check delivery availability when location changes
  const checkDeliveryAvailability = useCallback(async (lat: number, lng: number) => {
    setCheckingDelivery(true);
    try {
      const response = await api.get<{ success: boolean; delivery: DeliveryCheckResult }>(
        `/check_delivery.php?lat=${lat}&lng=${lng}`
      );
      if (response.success) {
        setDeliveryCheck(response.delivery);
        if (onDeliveryCheck) {
          onDeliveryCheck(
            response.delivery.available,
            response.delivery.nearest_outlet?.name,
            response.delivery.nearest_outlet?.distance_km
          );
        }
      }
    } catch (err) {
      console.error('Delivery check error:', err);
      setDeliveryCheck(null);
    } finally {
      setCheckingDelivery(false);
    }
  }, [onDeliveryCheck]);

  // Get current location
  const getCurrentLocation = useCallback(() => {
    if (!navigator.geolocation) {
      setLocationError('Browser tidak mendukung geolocation');
      return;
    }

    setLocationLoading(true);
    setLocationError(null);
    setReverseGeocodingDone(false);

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        setLocation({ lat, lng });
        setValue('latitude', lat);
        setValue('longitude', lng);
        setLocationLoading(false);
        
        // Auto-check delivery availability
        await checkDeliveryAvailability(lat, lng);
        
        // Auto-fill address from GPS using reverse geocoding
        await reverseGeocode(lat, lng);
      },
      (error) => {
        setLocationLoading(false);
        switch (error.code) {
          case error.PERMISSION_DENIED:
            setLocationError('Izin lokasi ditolak. Mohon aktifkan GPS dan izinkan akses lokasi.');
            break;
          case error.POSITION_UNAVAILABLE:
            setLocationError('Lokasi tidak tersedia. Coba lagi nanti.');
            break;
          case error.TIMEOUT:
            setLocationError('Waktu habis. Coba lagi.');
            break;
          default:
            setLocationError('Gagal mendapatkan lokasi');
        }
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      }
    );
  }, [setValue, checkDeliveryAvailability, reverseGeocode]);

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
      // Don't reset if we're doing reverse geocoding (auto-fill)
      if (reverseGeocodingDone || !locationLoading) {
        // Only reset if user manually changed province
        const currentCity = watch('city');
        const availCities = cities[selectedProvince] || [];
        if (currentCity && !availCities.find(c => c.id === currentCity)) {
          setValue('city', '');
          setValue('district', '');
        }
      }
    }
  }, [selectedProvince, setValue, reverseGeocodingDone, locationLoading, watch]);

  useEffect(() => {
    if (selectedCity) {
      setAvailableDistricts(districts[selectedCity] || districts['DEFAULT']);
      // Don't reset if we're doing reverse geocoding (auto-fill)
      if (reverseGeocodingDone || !locationLoading) {
        // Only reset if user manually changed city
        const currentDistrict = watch('district');
        const availDistricts = districts[selectedCity] || districts['DEFAULT'];
        if (currentDistrict && !availDistricts.find(d => d.id === currentDistrict)) {
          setValue('district', '');
        }
      }
    }
  }, [selectedCity, setValue, reverseGeocodingDone, locationLoading, watch]);

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

      {/* Location Picker for Delivery Radius Check */}
      <div className={`p-4 rounded-xl border-2 transition-all ${
        deliveryCheck?.available === true
          ? 'bg-green-50 border-green-200'
          : deliveryCheck?.available === false
          ? 'bg-red-50 border-red-200'
          : 'bg-blue-50 border-blue-200'
      }`}>
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center gap-2">
            <i className="bi bi-geo-alt-fill text-lg text-[#a97456]"></i>
            <span className="font-medium text-gray-700">Validasi Lokasi Delivery</span>
          </div>
          <button
            type="button"
            onClick={getCurrentLocation}
            disabled={locationLoading}
            className="px-4 py-2 bg-[#a97456] text-white text-sm rounded-lg hover:bg-[#8b6043] transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            {locationLoading ? (
              <>
                <i className="bi bi-arrow-repeat animate-spin"></i>
                Mencari...
              </>
            ) : (
              <>
                <i className="bi bi-crosshair"></i>
                Gunakan Lokasi Saya
              </>
            )}
          </button>
        </div>

        {locationError && (
          <div className="text-sm text-red-600 bg-red-100 p-2 rounded-lg mb-3">
            <i className="bi bi-exclamation-circle mr-1"></i>
            {locationError}
          </div>
        )}

        {checkingDelivery && (
          <div className="text-sm text-blue-600 flex items-center gap-2">
            <i className="bi bi-arrow-repeat animate-spin"></i>
            Memeriksa jangkauan delivery...
          </div>
        )}

        {deliveryCheck && !checkingDelivery && (
          <div className={`text-sm ${deliveryCheck.available ? 'text-green-700' : 'text-red-700'}`}>
            {deliveryCheck.available ? (
              <div className="flex items-start gap-2">
                <i className="bi bi-check-circle-fill text-green-500 mt-0.5"></i>
                <div>
                  <p className="font-medium">Delivery Tersedia!</p>
                  <p className="text-green-600">
                    Lokasi Anda {deliveryCheck.nearest_outlet?.distance_km}km dari outlet 
                    <strong> {deliveryCheck.nearest_outlet?.name}</strong>
                  </p>
                </div>
              </div>
            ) : (
              <div className="flex items-start gap-2">
                <i className="bi bi-x-circle-fill text-red-500 mt-0.5"></i>
                <div>
                  <p className="font-medium">Delivery Tidak Tersedia</p>
                  <p className="text-red-600">
                    Lokasi Anda {deliveryCheck.nearest_outlet?.distance_km}km dari outlet terdekat.
                    Maksimal jangkauan delivery adalah {deliveryCheck.nearest_outlet?.max_radius_km}km.
                  </p>
                  <p className="text-gray-500 text-xs mt-1">
                    Silakan pilih metode Takeaway atau Dine-in
                  </p>
                </div>
              </div>
            )}
          </div>
        )}

        {!location && !locationLoading && !locationError && (
          <p className="text-xs text-gray-500">
            <i className="bi bi-info-circle mr-1"></i>
            Klik tombol di atas untuk memvalidasi apakah lokasi Anda dalam jangkauan delivery (maks. 30km dari outlet)
          </p>
        )}

        {location && (
          <input type="hidden" {...register('latitude')} value={location.lat} />
        )}
        {location && (
          <input type="hidden" {...register('longitude')} value={location.lng} />
        )}
      </div>

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
