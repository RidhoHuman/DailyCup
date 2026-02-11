"use client";

import { useEffect, useRef, useState } from "react";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import { calculateDistance, calculateBearing, formatDistance, calculateETA } from "@/lib/distance-calculator";

interface Location {
  lat: number;
  lng: number;
}

interface LeafletMapTrackerProps {
  courierLocation?: Location;
  customerLocation?: Location;
  orderId?: string;
}

// Fix Leaflet default marker icons
const getDefaultIcon = () => {
  return L.icon({
    iconUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png",
    iconRetinaUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png",
    shadowUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
  });
};

export default function LeafletMapTracker({
  courierLocation,
  customerLocation,
  orderId,
}: LeafletMapTrackerProps) {
  const mapRef = useRef<HTMLDivElement>(null);
  const [map, setMap] = useState<L.Map | null>(null);
  const [courierMarker, setCourierMarker] = useState<L.Marker | null>(null);
  const [distance, setDistance] = useState<number>(0);
  const [eta, setEta] = useState<number>(0);

  // Default locations (Malang area - sesuai outlet DailyCup)
  const dummyCourierLocation: Location = courierLocation || {
    lat: -7.98970,  // Malang
    lng: 112.61070,
  };

  const dummyCustomerLocation: Location = customerLocation || {
    lat: -7.97800,  // Malang
    lng: 112.63400,
  };

  useEffect(() => {
    if (!mapRef.current || map) return;

    // Initialize map
    const centerLat = (dummyCourierLocation.lat + dummyCustomerLocation.lat) / 2;
    const centerLng = (dummyCourierLocation.lng + dummyCustomerLocation.lng) / 2;

    const mapInstance = L.map(mapRef.current).setView([centerLat, centerLng], 14);

    // Add OpenStreetMap tile layer (FREE!)
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
    }).addTo(mapInstance);

    setMap(mapInstance);

    // Add customer marker (destination) with green icon
    const customerIcon = L.divIcon({
      html: '<div style="background-color: #4CAF50; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">📍</div>',
      iconSize: [30, 30],
      iconAnchor: [15, 15],
      className: "",
    });

    L.marker([dummyCustomerLocation.lat, dummyCustomerLocation.lng], {
      icon: customerIcon,
    })
      .addTo(mapInstance)
      .bindPopup("<b>Delivery Destination</b><br>Your location");

    // Add courier marker with custom motorcycle icon
    const courierIcon = L.divIcon({
      html: '<div style="background-color: #a97456; width: 35px; height: 35px; border-radius: 50%; border: 3px solid white; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.4); transform: rotate(0deg);">🏍️</div>',
      iconSize: [35, 35],
      iconAnchor: [17.5, 17.5],
      className: "courier-marker",
    });

    const marker = L.marker([dummyCourierLocation.lat, dummyCourierLocation.lng], {
      icon: courierIcon,
    })
      .addTo(mapInstance)
      .bindPopup("<b>Courier</b><br>On the way!");

    setCourierMarker(marker);

    // Draw route line
    const routeLine = L.polyline(
      [
        [dummyCourierLocation.lat, dummyCourierLocation.lng],
        [dummyCustomerLocation.lat, dummyCustomerLocation.lng],
      ],
      {
        color: "#a97456",
        weight: 4,
        opacity: 0.7,
        dashArray: "10, 10",
      }
    ).addTo(mapInstance);

    // Add arrow decorator (direction indicator)
    const arrowIcon = L.divIcon({
      html: '<div style="color: #a97456; font-size: 16px;">▶</div>',
      iconSize: [16, 16],
      className: "",
    });

    // Fit bounds to show both markers
    const bounds = L.latLngBounds(
      [dummyCourierLocation.lat, dummyCourierLocation.lng],
      [dummyCustomerLocation.lat, dummyCustomerLocation.lng]
    );
    mapInstance.fitBounds(bounds, { padding: [50, 50] });

    // Calculate initial distance
    const dist = calculateDistance(dummyCourierLocation, dummyCustomerLocation);
    setDistance(dist);
    setEta(calculateETA(dist));

    // Cleanup
    return () => {
      mapInstance.remove();
    };
  }, []);

  // Simulate courier movement
  useEffect(() => {
    if (!map || !courierMarker) return;

    const interval = setInterval(() => {
      const currentPos = courierMarker.getLatLng();
      const destPos = L.latLng(dummyCustomerLocation.lat, dummyCustomerLocation.lng);

      // Calculate new position (move 2% closer to destination)
      const newLat = currentPos.lat + (destPos.lat - currentPos.lat) * 0.02;
      const newLng = currentPos.lng + (destPos.lng - currentPos.lng) * 0.02;

      const newPos = L.latLng(newLat, newLng);
      courierMarker.setLatLng(newPos);

      // Calculate bearing/heading for rotation
      const bearing = calculateBearing(
        { lat: currentPos.lat, lng: currentPos.lng },
        { lat: destPos.lat, lng: destPos.lng }
      );

      // Update marker with rotation
      const courierIconRotated = L.divIcon({
        html: `<div style="background-color: #a97456; width: 35px; height: 35px; border-radius: 50%; border: 3px solid white; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.4); transform: rotate(${bearing}deg);">🏍️</div>`,
        iconSize: [35, 35],
        iconAnchor: [17.5, 17.5],
        className: "courier-marker",
      });

      courierMarker.setIcon(courierIconRotated);

      // Update distance and ETA
      const dist = calculateDistance(
        { lat: newPos.lat, lng: newPos.lng },
        dummyCustomerLocation
      );
      setDistance(dist);
      setEta(calculateETA(dist));

      // Stop when close enough (< 50 meters)
      if (dist < 0.05) {
        clearInterval(interval);
        courierMarker.bindPopup("<b>Courier Arrived!</b><br>Delivery in progress").openPopup();
      }
    }, 2000); // Update every 2 seconds

    return () => clearInterval(interval);
  }, [map, courierMarker]);

  return (
    <div className="w-full">
      {/* Map Container */}
      <div ref={mapRef} className="w-full h-[400px] rounded-lg shadow-lg z-0"></div>

      {/* Info Cards */}
      <div className="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-white border-2 border-[#a97456] rounded-lg p-4">
          <div className="flex items-center gap-2 mb-2">
            <i className="bi bi-rulers text-[#a97456] text-xl"></i>
            <span className="text-xs font-semibold text-gray-600">DISTANCE</span>
          </div>
          <p className="text-2xl font-bold text-[#a97456]">{formatDistance(distance)}</p>
          <p className="text-xs text-gray-500 mt-1">Jarak ke tujuan</p>
        </div>

        <div className="bg-white border-2 border-blue-500 rounded-lg p-4">
          <div className="flex items-center gap-2 mb-2">
            <i className="bi bi-clock text-blue-500 text-xl"></i>
            <span className="text-xs font-semibold text-gray-600">ESTIMATED TIME</span>
          </div>
          <p className="text-2xl font-bold text-blue-600">{eta} min</p>
          <p className="text-xs text-gray-500 mt-1">Perkiraan sampai</p>
        </div>

        <div className="bg-white border-2 border-green-500 rounded-lg p-4">
          <div className="flex items-center gap-2 mb-2">
            <i className="bi bi-truck text-green-500 text-xl"></i>
            <span className="text-xs font-semibold text-gray-600">STATUS</span>
          </div>
          <p className="text-lg font-bold text-green-600">
            {distance < 0.5 ? "Hampir Sampai!" : "Dalam Perjalanan"}
          </p>
          <p className="text-xs text-gray-500 mt-1">Real-time tracking</p>
        </div>
      </div>

      {/* Technology Badge */}
      <div className="mt-4 bg-gradient-to-r from-blue-50 to-green-50 border border-blue-200 rounded-lg p-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <i className="bi bi-globe text-blue-600"></i>
            <span className="text-sm font-medium text-gray-700">
              Powered by <strong>OpenStreetMap</strong> + <strong>Leaflet.js</strong>
            </span>
          </div>
          <span className="text-xs px-3 py-1 bg-green-500 text-white rounded-full font-semibold">
            100% FREE
          </span>
        </div>
        <p className="text-xs text-gray-600 mt-1">
          🚀 No API Key Required • Distance calculated with Haversine Formula
        </p>
      </div>
    </div>
  );
}
