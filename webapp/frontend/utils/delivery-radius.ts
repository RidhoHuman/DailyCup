/**
 * Haversine Formula Implementation
 * Calculates the great-circle distance between two points on Earth
 * Used for delivery radius validation
 */

export interface Coordinates {
  lat: number;
  lng: number;
}

/**
 * Store location (default DailyCup HQ)
 * TODO: Move this to environment variables or database
 */
export const STORE_LOCATION: Coordinates = {
  lat: -6.2088, // Jakarta coordinates (example)
  lng: 106.8456
};

/**
 * Delivery configuration
 */
export const DELIVERY_CONFIG = {
  MAX_RADIUS_KM: 5, // Maximum delivery radius in kilometers
  DELIVERY_FEE_FLAT: 10000, // Flat delivery fee for instant delivery (Rp 10,000)
  FREE_DELIVERY_THRESHOLD: 100000 // Free delivery for orders above this amount
};

/**
 * Haversine Formula
 * Calculates distance between two geographic coordinates
 * 
 * Formula explanation:
 * 1. Convert degrees to radians
 * 2. Calculate differences in latitude and longitude
 * 3. Use haversine formula: a = sin²(Δlat/2) + cos(lat1) * cos(lat2) * sin²(Δlon/2)
 * 4. Calculate angular distance: c = 2 * atan2(√a, √(1−a))
 * 5. Multiply by Earth's radius to get distance in km
 * 
 * @param coord1 First coordinate {lat, lng}
 * @param coord2 Second coordinate {lat, lng}
 * @returns Distance in kilometers
 */
export function calculateDistance(coord1: Coordinates, coord2: Coordinates): number {
  const R = 6371; // Earth's radius in kilometers
  
  // Convert degrees to radians
  const toRadians = (degrees: number) => degrees * (Math.PI / 180);
  
  const lat1Rad = toRadians(coord1.lat);
  const lat2Rad = toRadians(coord2.lat);
  const deltaLatRad = toRadians(coord2.lat - coord1.lat);
  const deltaLngRad = toRadians(coord2.lng - coord1.lng);
  
  // Haversine formula
  const a = 
    Math.sin(deltaLatRad / 2) * Math.sin(deltaLatRad / 2) +
    Math.cos(lat1Rad) * Math.cos(lat2Rad) *
    Math.sin(deltaLngRad / 2) * Math.sin(deltaLngRad / 2);
  
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  
  const distance = R * c; // Distance in kilometers
  
  return Math.round(distance * 100) / 100; // Round to 2 decimal places
}

/**
 * Check if delivery address is within delivery radius
 * @param customerLocation Customer's delivery coordinates
 * @returns Object with isWithinRadius flag and calculated distance
 */
export function validateDeliveryRadius(customerLocation: Coordinates): {
  isWithinRadius: boolean;
  distance: number;
  maxRadius: number;
} {
  const distance = calculateDistance(STORE_LOCATION, customerLocation);
  
  return {
    isWithinRadius: distance <= DELIVERY_CONFIG.MAX_RADIUS_KM,
    distance,
    maxRadius: DELIVERY_CONFIG.MAX_RADIUS_KM
  };
}

/**
 * Calculate delivery fee based on distance and order total
 * @param customerLocation Customer coordinates
 * @param orderTotal Total order amount
 * @returns Delivery fee amount
 */
export function calculateDeliveryFee(
  customerLocation: Coordinates,
  orderTotal: number
): {
  fee: number;
  isFree: boolean;
  reason: string;
} {
  const validation = validateDeliveryRadius(customerLocation);
  
  // Check if within radius
  if (!validation.isWithinRadius) {
    return {
      fee: 0,
      isFree: false,
      reason: `Outside delivery radius (${validation.distance}km > ${validation.maxRadius}km)`
    };
  }
  
  // Check for free delivery threshold
  if (orderTotal >= DELIVERY_CONFIG.FREE_DELIVERY_THRESHOLD) {
    return {
      fee: 0,
      isFree: true,
      reason: `Free delivery for orders above Rp ${DELIVERY_CONFIG.FREE_DELIVERY_THRESHOLD.toLocaleString('id-ID')}`
    };
  }
  
  // Standard flat fee
  return {
    fee: DELIVERY_CONFIG.DELIVERY_FEE_FLAT,
    isFree: false,
    reason: `Flat delivery fee within ${validation.maxRadius}km radius`
  };
}

/**
 * Geocode address to coordinates using browser's geolocation API or external service
 * Note: This is a placeholder. In production, use Google Maps Geocoding API or similar
 * @param address Full delivery address
 * @returns Promise with coordinates
 */
export async function geocodeAddress(address: string): Promise<Coordinates | null> {
  // TODO: Integrate with actual geocoding service (Google Maps, OpenStreetMap, etc.)
  // For now, return null and let user manually pin location on map
  console.warn('geocodeAddress not implemented. Integrate with geocoding API.');
  return null;
}

/**
 * Get user's current location using browser geolocation
 * @returns Promise with current coordinates
 */
export function getCurrentLocation(): Promise<Coordinates> {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation is not supported by this browser'));
      return;
    }
    
    navigator.geolocation.getCurrentPosition(
      (position) => {
        resolve({
          lat: position.coords.latitude,
          lng: position.coords.longitude
        });
      },
      (error) => {
        reject(error);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      }
    );
  });
}
