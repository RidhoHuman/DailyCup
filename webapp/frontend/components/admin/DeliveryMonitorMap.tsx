"use client";

import { useEffect, useRef } from "react";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

interface KurirLocation {
  lat: number;
  lng: number;
  updated_at: string;
  accuracy: number | null;
  speed: number | null;
}

interface OrderInfo {
  order_id: number;
  order_number: string;
  customer_name: string;
  delivery_address: string;
  destination?: {
    lat: number;
    lng: number;
  } | null;
  status: string;
}

interface ActiveKurir {
  id: number;
  name: string;
  phone: string;
  vehicle_type: string;
  location: KurirLocation | null;
  orders: OrderInfo[];
}

interface Props {
  kurirs: ActiveKurir[];
  onKurirSelect?: (kurirId: number) => void;
}

export default function DeliveryMonitorMap({ kurirs, onKurirSelect }: Props) {
  const mapRef = useRef<L.Map | null>(null);
  const markersRef = useRef<Map<number, L.Marker>>(new Map());
  const destinationMarkersRef = useRef<Map<number, L.Marker[]>>(new Map());

  useEffect(() => {
    // Initialize map
    if (!mapRef.current) {
      const map = L.map("delivery-monitor-map").setView([-7.7956, 110.3695], 13); // Yogyakarta center

      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
      }).addTo(map);

      mapRef.current = map;
    }

    return () => {
      if (mapRef.current) {
        mapRef.current.remove();
        mapRef.current = null;
      }
    };
  }, []);

  useEffect(() => {
    if (!mapRef.current) return;

    const map = mapRef.current;

    // Clear old markers
    markersRef.current.forEach((marker) => marker.remove());
    markersRef.current.clear();

    destinationMarkersRef.current.forEach((markers) => {
      markers.forEach((marker) => marker.remove());
    });
    destinationMarkersRef.current.clear();

    // If no kurirs, center on Yogyakarta
    if (kurirs.length === 0) {
      map.setView([-7.7956, 110.3695], 13);
      return;
    }

    const bounds = L.latLngBounds([]);

    // Add markers for each kurir
    kurirs.forEach((kurir) => {
      if (!kurir.location) return;

      const { lat, lng } = kurir.location;
      const latLng = L.latLng(lat, lng);
      bounds.extend(latLng);

      // Create custom kurir icon
      const iconHtml = `
        <div style="position: relative;">
          <div style="
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border: 3px solid white;
            font-size: 1.5rem;
          ">
            <i class="bi bi-bicycle"></i>
          </div>
          <div style="
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            color: #059669;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            white-space: nowrap;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
          ">
            ${kurir.name.split(' ')[0]}
          </div>
        </div>
      `;

      const kurirIcon = L.divIcon({
        html: iconHtml,
        className: "custom-kurir-marker",
        iconSize: [50, 50],
        iconAnchor: [25, 25],
      });

      // Create marker
      const marker = L.marker(latLng, { icon: kurirIcon });

      // Popup content
      const popupContent = `
        <div style="min-width: 200px;">
          <h3 style="font-weight: bold; margin-bottom: 8px; color: #059669;">
            <i class="bi bi-person-badge"></i> ${kurir.name}
          </h3>
          <p style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">
            <i class="bi bi-telephone"></i> ${kurir.phone}
          </p>
          <p style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">
            <i class="bi bi-bicycle"></i> ${kurir.vehicle_type}
          </p>
          ${kurir.location.speed ? `
            <p style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">
              <i class="bi bi-speedometer"></i> ${Math.round(kurir.location.speed)} km/h
            </p>
          ` : ''}
          <hr style="margin: 8px 0; border-color: #e5e7eb;" />
          <p style="font-size: 11px; font-weight: bold; color: #4b5563; margin-bottom: 4px;">
            ACTIVE ORDERS (${kurir.orders.length}):
          </p>
          ${kurir.orders.map(order => `
            <div style="background: #f3f4f6; padding: 6px; border-radius: 6px; margin-bottom: 4px;">
              <p style="font-size: 11px; font-weight: bold; color: #1f2937;">#${order.order_number}</p>
              <p style="font-size: 10px; color: #6b7280;">${order.customer_name}</p>
              <p style="font-size: 10px; color: #9ca3af;">${order.delivery_address.substring(0, 40)}...</p>
            </div>
          `).join('')}
          <p style="font-size: 10px; color: #9ca3af; margin-top: 8px;">
            Last update: ${new Date(kurir.location.updated_at).toLocaleTimeString('id-ID')}
          </p>
        </div>
      `;

      marker.bindPopup(popupContent, { maxWidth: 300 });

      // Click handler
      marker.on("click", () => {
        if (onKurirSelect) {
          onKurirSelect(kurir.id);
        }
      });

      marker.addTo(map);
      markersRef.current.set(kurir.id, marker);

      // Add destination markers for this kurir's orders
      const destMarkers: L.Marker[] = [];
      kurir.orders.forEach((order) => {
        // Skip drawing destination if coordinates are missing/null/0
        if (!order.destination || !order.destination.lat || !order.destination.lng) return;

        const destLatLng = L.latLng(order.destination.lat, order.destination.lng);
        bounds.extend(destLatLng);

        const destIcon = L.divIcon({
          html: `
            <div style="
              background: #ef4444;
              color: white;
              border-radius: 50%;
              width: 30px;
              height: 30px;
              display: flex;
              align-items: center;
              justify-content: center;
              box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
              border: 2px solid white;
              font-size: 1rem;
            ">
              <i class="bi bi-geo-alt-fill"></i>
            </div>
          `,
          className: "custom-destination-marker",
          iconSize: [30, 30],
          iconAnchor: [15, 15],
        });

        const destMarker = L.marker(destLatLng, { icon: destIcon });

        const destPopup = `
          <div style="min-width: 180px;">
            <h4 style="font-weight: bold; margin-bottom: 6px; color: #ef4444;">
              <i class="bi bi-geo-alt"></i> Destination
            </h4>
            <p style="font-size: 12px; color: #374151; margin-bottom: 4px;">
              <strong>#${order.order_number}</strong>
            </p>
            <p style="font-size: 11px; color: #6b7280; margin-bottom: 4px;">
              ${order.customer_name}
            </p>
            <p style="font-size: 10px; color: #9ca3af;">
              ${order.delivery_address}
            </p>
            <p style="font-size: 10px; color: #059669; margin-top: 6px; font-weight: bold;">
              Kurir: ${kurir.name}
            </p>
          </div>
        `;

        destMarker.bindPopup(destPopup);
        destMarker.addTo(map);
        destMarkers.push(destMarker);

        // Draw line from kurir to destination
        const line = L.polyline([latLng, destLatLng], {
          color: "#3b82f6",
          weight: 2,
          opacity: 0.6,
          dashArray: "5, 10",
        }).addTo(map);

        destMarkers.push(line as any); // Store line with markers for cleanup
      });

      destinationMarkersRef.current.set(kurir.id, destMarkers);
    });

    // Fit map to bounds with padding
    if (bounds.isValid()) {
      map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
    }
  }, [kurirs, onKurirSelect]);

  return (
    <div
      id="delivery-monitor-map"
      className="w-full h-[600px] rounded-xl overflow-hidden shadow-inner"
      style={{ zIndex: 1 }}
    />
  );
}
