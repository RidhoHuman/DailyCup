/**
 * Haversine Distance Calculator
 * Calculate distance between two coordinates in kilometers
 * 100% FREE - No API needed!
 */

export interface Coordinate {
  lat: number;
  lng: number;
}

/**
 * Calculate distance between two points using Haversine formula
 * @param point1 First coordinate (lat, lng)
 * @param point2 Second coordinate (lat, lng)
 * @returns Distance in kilometers
 */
export function calculateDistance(point1: Coordinate, point2: Coordinate): number {
  const R = 6371; // Earth's radius in kilometers

  const dLat = toRadians(point2.lat - point1.lat);
  const dLng = toRadians(point2.lng - point1.lng);

  const lat1 = toRadians(point1.lat);
  const lat2 = toRadians(point2.lat);

  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.sin(dLng / 2) * Math.sin(dLng / 2) * Math.cos(lat1) * Math.cos(lat2);

  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c; // Distance in km
}

/**
 * Convert degrees to radians
 */
function toRadians(degrees: number): number {
  return degrees * (Math.PI / 180);
}

/**
 * Check if location is within radius (default 5km for delivery)
 * @param storeLocation Store/origin coordinates
 * @param userLocation User/destination coordinates
 * @param radiusKm Maximum delivery radius in kilometers (default: 5)
 * @returns true if within radius
 */
export function isWithinDeliveryRadius(
  storeLocation: Coordinate,
  userLocation: Coordinate,
  radiusKm: number = 5
): boolean {
  const distance = calculateDistance(storeLocation, userLocation);
  return distance <= radiusKm;
}

/**
 * Calculate bearing (heading) between two points
 * @returns Bearing in degrees (0-360)
 */
export function calculateBearing(point1: Coordinate, point2: Coordinate): number {
  const dLng = toRadians(point2.lng - point1.lng);
  const lat1 = toRadians(point1.lat);
  const lat2 = toRadians(point2.lat);

  const y = Math.sin(dLng) * Math.cos(lat2);
  const x =
    Math.cos(lat1) * Math.sin(lat2) -
    Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLng);

  const bearing = Math.atan2(y, x);
  return (toDegrees(bearing) + 360) % 360;
}

function toDegrees(radians: number): number {
  return radians * (180 / Math.PI);
}

/**
 * Format distance for display
 */
export function formatDistance(km: number): string {
  if (km < 1) {
    return `${Math.round(km * 1000)} m`;
  }
  return `${km.toFixed(2)} km`;
}

/**
 * Calculate ETA based on distance and average speed
 * @param distanceKm Distance in kilometers
 * @param speedKmH Average speed in km/h (default: 30 for motorcycle in city)
 * @returns ETA in minutes
 */
export function calculateETA(distanceKm: number, speedKmH: number = 30): number {
  const hours = distanceKm / speedKmH;
  return Math.round(hours * 60); // Convert to minutes
}
