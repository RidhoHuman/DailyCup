"use client";

import { useEffect, useRef, useState } from "react";

interface Location {
  lat: number;
  lng: number;
}

interface MapTrackerProps {
  courierLocation?: Location;
  customerLocation?: Location;
  orderId?: string;
}

declare global {
  interface Window {
    // permissive fallback so the file compiles without @types/google.maps
    google?: any;
    initMap?: () => void;
  }
}

export default function GoogleMapTracker({ 
  courierLocation, 
  customerLocation
}: MapTrackerProps) {
  const mapRef = useRef<HTMLDivElement>(null);
  const [map, setMap] = useState<any | null>(null);
  const [courierMarker, setCourierMarker] = useState<any | null>(null);
  const [apiKeyMissing, setApiKeyMissing] = useState(false);

  // Dummy locations for demonstration
  const dummyCourierLocation: Location = courierLocation || {
    lat: -6.200000,
    lng: 106.816666
  };

  const dummyCustomerLocation: Location = customerLocation || {
    lat: -6.195000,
    lng: 106.820000
  };

  useEffect(() => {
    // Check if API key is configured
    const apiKey = process.env.NEXT_PUBLIC_GOOGLE_MAPS_API_KEY;
    
    if (!apiKey || apiKey === 'YOUR_GOOGLE_MAPS_API_KEY_HERE') {
      setApiKeyMissing(true);
      return;
    }

    // Load Google Maps script
    if (!window.google) {
      const script = document.createElement('script');
      script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=geometry`;
      script.async = true;
      script.defer = true;
      script.onload = () => initializeMap();
      script.onerror = () => setApiKeyMissing(true);
      document.head.appendChild(script);
    } else {
      initializeMap();
    }
  }, []);

  const initializeMap = () => {
    if (!mapRef.current) return;

    // Initialize map centered between courier and customer
    const centerLat = (dummyCourierLocation.lat + dummyCustomerLocation.lat) / 2;
    const centerLng = (dummyCourierLocation.lng + dummyCustomerLocation.lng) / 2;

    const mapInstance = new window.google.maps.Map(mapRef.current, {
      center: { lat: centerLat, lng: centerLng },
      zoom: 14,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true,
      styles: [
        {
          featureType: "poi",
          elementType: "labels",
          stylers: [{ visibility: "off" }]
        }
      ]
    });

    setMap(mapInstance);

    // Add customer marker (destination)
    new window.google.maps.Marker({
      position: dummyCustomerLocation,
      map: mapInstance,
      title: "Delivery Destination",
      icon: {
        path: window.google.maps.SymbolPath.CIRCLE,
        scale: 10,
        fillColor: "#4CAF50",
        fillOpacity: 1,
        strokeColor: "#fff",
        strokeWeight: 2,
      },
      label: {
        text: "📍",
        fontSize: "18px",
      }
    });

    // Add courier marker (moving)
    const marker = new window.google.maps.Marker({
      position: dummyCourierLocation,
      map: mapInstance,
      title: "Courier Location",
      icon: {
        path: window.google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
        scale: 6,
        fillColor: "#a97456",
        fillOpacity: 1,
        strokeColor: "#fff",
        strokeWeight: 2,
        rotation: 0,
      },
      animation: window.google.maps.Animation.DROP,
    });

    setCourierMarker(marker);

    // Draw route (polyline)
    const routePath = new window.google.maps.Polyline({
      path: [dummyCourierLocation, dummyCustomerLocation],
      geodesic: true,
      strokeColor: "#a97456",
      strokeOpacity: 0.8,
      strokeWeight: 4,
      icons: [
        {
          icon: {
            path: window.google.maps.SymbolPath.FORWARD_OPEN_ARROW,
            scale: 3,
            strokeColor: "#a97456",
          },
          offset: "100%",
          repeat: "50px",
        },
      ],
    });

    routePath.setMap(mapInstance);

    // Fit bounds to show both markers
    const bounds = new window.google.maps.LatLngBounds();
    bounds.extend(dummyCourierLocation);
    bounds.extend(dummyCustomerLocation);
    mapInstance.fitBounds(bounds);
  };

  // Simulate courier movement (for demo)
  useEffect(() => {
    if (!map || !courierMarker) return;

    const interval = setInterval(() => {
      const currentPos = courierMarker.getPosition();
      const destPos = dummyCustomerLocation;

      // Move courier slightly towards destination
      const newLat = currentPos.lat() + (destPos.lat - currentPos.lat()) * 0.02;
      const newLng = currentPos.lng() + (destPos.lng - currentPos.lng()) * 0.02;

      const newPos = { lat: newLat, lng: newLng };
      courierMarker.setPosition(newPos);

      // Calculate heading/rotation
      const heading = window.google.maps.geometry.spherical.computeHeading(
        currentPos,
        new window.google.maps.LatLng(destPos)
      );

      courierMarker.setIcon({
        path: window.google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
        scale: 6,
        fillColor: "#a97456",
        fillOpacity: 1,
        strokeColor: "#fff",
        strokeWeight: 2,
        rotation: heading,
      });

      // Calculate distance
      const distance = window.google.maps.geometry.spherical.computeDistanceBetween(
        new window.google.maps.LatLng(newPos),
        new window.google.maps.LatLng(destPos)
      );

      // Stop when close enough (< 10 meters)
      if (distance < 10) {
        clearInterval(interval);
      }
    }, 2000); // Update every 2 seconds

    return () => clearInterval(interval);
  }, [map, courierMarker]);

  // Fallback UI when API key is missing
  if (apiKeyMissing) {
    return (
      <div className="w-full h-[400px] bg-gray-100 rounded-lg flex items-center justify-center">
        <div className="text-center p-6 max-w-md">
          <i className="bi bi-map text-6xl text-gray-400 mb-4"></i>
          <h3 className="text-xl font-bold text-gray-800 mb-2">
            Google Maps API Key Required
          </h3>
          <p className="text-sm text-gray-600 mb-4">
            To enable real-time tracking, add your Google Maps API key:
          </p>
          <div className="bg-gray-800 text-white p-3 rounded text-xs font-mono text-left mb-4">
            <p className="text-green-400"># frontend/.env.local</p>
            <p className="mt-2">NEXT_PUBLIC_GOOGLE_MAPS_API_KEY=your_api_key_here</p>
          </div>
          <p className="text-xs text-gray-500">
            Get your API key at:{" "}
            <a 
              href="https://console.cloud.google.com/google/maps-apis" 
              target="_blank" 
              rel="noopener noreferrer"
              className="text-blue-600 hover:underline"
            >
              Google Cloud Console
            </a>
          </p>
          
          {/* Dummy Map Preview */}
          <div className="mt-6 relative">
            <div className="aspect-video bg-gradient-to-br from-blue-200 to-green-200 rounded-lg overflow-hidden relative">
              <div className="absolute inset-0 bg-[url('/api/placeholder/400/300')] bg-cover opacity-30"></div>
              <div className="absolute top-4 left-4 bg-white px-3 py-2 rounded-lg shadow-lg">
                <p className="text-xs font-semibold text-gray-700">
                  🏍️ Courier: 2.3 km away
                </p>
              </div>
              <div className="absolute bottom-4 right-4 bg-white px-3 py-2 rounded-lg shadow-lg">
                <p className="text-xs font-semibold text-gray-700">
                  📍 Destination
                </p>
              </div>
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="bg-white/90 backdrop-blur px-4 py-2 rounded-lg">
                  <p className="text-sm font-medium text-gray-700">
                    Map Preview (Demo Mode)
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full">
      <div 
        ref={mapRef} 
        className="w-full h-[400px] rounded-lg shadow-lg"
      ></div>
      
      {/* Map Controls/Info */}
      <div className="mt-4 grid grid-cols-2 gap-4">
        <div className="bg-white border border-gray-200 rounded-lg p-3">
          <div className="flex items-center gap-2 mb-2">
            <i className="bi bi-geo-alt-fill text-green-600"></i>
            <span className="text-xs font-semibold text-gray-600">DESTINATION</span>
          </div>
          <p className="text-sm text-gray-800">
            {customerLocation 
              ? `${customerLocation.lat.toFixed(4)}, ${customerLocation.lng.toFixed(4)}`
              : "Jakarta Pusat"
            }
          </p>
        </div>
        
        <div className="bg-white border border-gray-200 rounded-lg p-3">
          <div className="flex items-center gap-2 mb-2">
            <i className="bi bi-truck text-[#a97456]"></i>
            <span className="text-xs font-semibold text-gray-600">COURIER</span>
          </div>
          <p className="text-sm text-gray-800">
            {courierLocation 
              ? `${courierLocation.lat.toFixed(4)}, ${courierLocation.lng.toFixed(4)}`
              : "On the way..."
            }
          </p>
        </div>
      </div>
    </div>
  );
}
