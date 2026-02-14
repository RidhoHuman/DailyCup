/**
 * Nominatim Geocoding Service (OpenStreetMap)
 * FREE Geocoding API - No API Key Required!
 */

export interface GeocodingResult {
  lat: number;
  lon: number;
  display_name: string;
  address?: {
    road?: string;
    suburb?: string;
    city?: string;
    country?: string;
    postcode?: string;
  };
}

const NOMINATIM_BASE_URL = 'https://nominatim.openstreetmap.org';

// User-Agent required by Nominatim usage policy
const USER_AGENT = 'DailyCup-Delivery-App/1.0';

/**
 * Search address and get coordinates
 * @param address Full address string
 * @returns Array of geocoding results
 */
export async function geocodeAddress(address: string): Promise<GeocodingResult[]> {
  try {
    const params = new URLSearchParams({
      q: address,
      format: 'json',
      addressdetails: '1',
      limit: '5',
    });

    const response = await fetch(`${NOMINATIM_BASE_URL}/search?${params}`, {
      headers: {
        'User-Agent': USER_AGENT,
      },
    });

    if (!response.ok) {
      throw new Error('Geocoding request failed');
    }

    const data = await response.json();
    
    return data.map((result: unknown) => {
      const r = result as Record<string, unknown>;
      return {
        lat: parseFloat(String(r.lat)),
        lon: parseFloat(String(r.lon)),
        display_name: String(r.display_name),
        address: r.address as GeocodingResult['address'] | undefined,
      } as GeocodingResult;
    });
  } catch (error) {
    console.error('Geocoding error:', error);
    return [];
  }
}

/**
 * Reverse geocode: Get address from coordinates
 * @param lat Latitude
 * @param lon Longitude
 * @returns Address information
 */
export async function reverseGeocode(lat: number, lon: number): Promise<GeocodingResult | null> {
  try {
    const params = new URLSearchParams({
      lat: lat.toString(),
      lon: lon.toString(),
      format: 'json',
      addressdetails: '1',
    });

    const response = await fetch(`${NOMINATIM_BASE_URL}/reverse?${params}`, {
      headers: {
        'User-Agent': USER_AGENT,
      },
    });

    if (!response.ok) {
      throw new Error('Reverse geocoding request failed');
    }

    const data = await response.json();
    
    return {
      lat: parseFloat(data.lat),
      lon: parseFloat(data.lon),
      display_name: data.display_name,
      address: data.address,
    };
  } catch (error) {
    console.error('Reverse geocoding error:', error);
    return null;
  }
}

/**
 * Search places (e.g., "cafe near Jakarta")
 * @param query Search query
 * @param countryCode Optional country code (e.g., 'id' for Indonesia)
 * @returns Array of place results
 */
export async function searchPlaces(
  query: string,
  countryCode?: string
): Promise<GeocodingResult[]> {
  try {
    const params = new URLSearchParams({
      q: query,
      format: 'json',
      addressdetails: '1',
      limit: '10',
    });

    if (countryCode) {
      params.append('countrycodes', countryCode);
    }

    const response = await fetch(`${NOMINATIM_BASE_URL}/search?${params}`, {
      headers: {
        'User-Agent': USER_AGENT,
      },
    });

    if (!response.ok) {
      throw new Error('Search request failed');
    }

    const data = await response.json();
    
    return data.map((result: unknown) => {
      const r = result as Record<string, unknown>;
      return {
        lat: parseFloat(String(r.lat)),
        lon: parseFloat(String(r.lon)),
        display_name: String(r.display_name),
        address: r.address as GeocodingResult['address'] | undefined,
      } as GeocodingResult;
    });
  } catch (error) {
    console.error('Search error:', error);
    return [];
  }
}
