'use client';

import { MapContainer, TileLayer, Marker, useMapEvents } from 'react-leaflet';
import type { LeafletMouseEvent } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Fix for default marker icon
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

interface GeocodeMapProps {
  center: { lat: number; lng: number };
  onLocationSelect: (coords: { lat: number; lng: number }) => void;
}

function MapClickHandler({ onLocationSelect }: { onLocationSelect: (coords: { lat: number; lng: number }) => void }) {
  useMapEvents({
    click(e: LeafletMouseEvent) {
      onLocationSelect(e.latlng);
    },
  });
  return null;
}

export default function GeocodeMap({ center, onLocationSelect }: GeocodeMapProps) {
  return (
    <MapContainer 
      center={[center.lat, center.lng]} 
      zoom={13} 
      style={{ height: '100%', width: '100%' }}
    >
      <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
      <Marker position={[center.lat, center.lng]} />
      <MapClickHandler onLocationSelect={onLocationSelect} />
    </MapContainer>
  );
}
