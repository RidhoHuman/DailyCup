import { useEffect, useRef, useState } from 'react';
import { kurirApi } from '@/lib/kurir-api';

interface LocationBroadcastOptions {
  enabled: boolean;
  interval?: number; // milliseconds, default 5000 (5 seconds)
  highAccuracy?: boolean;
}

interface LocationState {
  broadcasting: boolean;
  lastUpdate: Date | null;
  error: string | null;
  permissionDenied: boolean;
}

/**
 * Hook untuk broadcast lokasi kurir secara otomatis ke backend
 * Real-time location streaming setiap 5 detik saat enabled
 */
export function useKurirLocationBroadcast(options: LocationBroadcastOptions) {
  const { enabled, interval = 5000, highAccuracy = true } = options;
  
  const [state, setState] = useState<LocationState>({
    broadcasting: false,
    lastUpdate: null,
    error: null,
    permissionDenied: false,
  });

  const watchIdRef = useRef<number | null>(null);
  const intervalIdRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    if (!enabled) {
      // Cleanup if disabled
      if (watchIdRef.current !== null) {
        navigator.geolocation.clearWatch(watchIdRef.current);
        watchIdRef.current = null;
      }
      if (intervalIdRef.current) {
        clearInterval(intervalIdRef.current);
        intervalIdRef.current = null;
      }
      setState(prev => ({ ...prev, broadcasting: false }));
      return;
    }

    // Check if geolocation is supported
    if (!navigator.geolocation) {
      setState(prev => ({
        ...prev,
        error: 'Geolocation not supported by this browser',
        broadcasting: false,
      }));
      return;
    }

    // Request permission and start broadcasting
    const startBroadcasting = () => {
      const geoOptions: PositionOptions = {
        enableHighAccuracy: highAccuracy,
        timeout: 10000,
        maximumAge: 0,
      };

      // Use watchPosition for continuous updates
      watchIdRef.current = navigator.geolocation.watchPosition(
        async (position) => {
          const { latitude, longitude, accuracy, speed } = position.coords;

          try {
            // Send location to backend
            await kurirApi.updateLocation({
              latitude,
              longitude,
              accuracy: accuracy || undefined,
              speed: speed || undefined,
            });

            setState(prev => ({
              ...prev,
              broadcasting: true,
              lastUpdate: new Date(),
              error: null,
            }));

            console.log('[Location Broadcast]', {
              lat: latitude.toFixed(6),
              lng: longitude.toFixed(6),
              accuracy: accuracy ? `${accuracy.toFixed(0)}m` : 'N/A',
              speed: speed ? `${(speed * 3.6).toFixed(1)} km/h` : 'N/A',
            });
          } catch (error: any) {
            console.error('[Location Broadcast] Failed to send location:', error);
            setState(prev => ({
              ...prev,
              error: error.message || 'Failed to send location',
            }));
          }
        },
        (error) => {
          console.error('[Location Broadcast] Geolocation error:', error);
          
          let errorMessage = 'Failed to get location';
          let permissionDenied = false;

          switch (error.code) {
            case error.PERMISSION_DENIED:
              errorMessage = 'Location permission denied';
              permissionDenied = true;
              break;
            case error.POSITION_UNAVAILABLE:
              errorMessage = 'Location unavailable';
              break;
            case error.TIMEOUT:
              errorMessage = 'Location request timeout';
              break;
          }

          setState(prev => ({
            ...prev,
            broadcasting: false,
            error: errorMessage,
            permissionDenied,
          }));
        },
        geoOptions
      );

      setState(prev => ({ ...prev, broadcasting: true }));
    };

    startBroadcasting();

    // Cleanup on unmount
    return () => {
      if (watchIdRef.current !== null) {
        navigator.geolocation.clearWatch(watchIdRef.current);
      }
      if (intervalIdRef.current) {
        clearInterval(intervalIdRef.current);
      }
    };
  }, [enabled, interval, highAccuracy]);

  return state;
}
