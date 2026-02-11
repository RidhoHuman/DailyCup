'use client';

import { useMapEvents } from 'react-leaflet';
import type { LeafletMouseEvent } from 'leaflet';

interface MapEventsHandlerProps {
  onLocationSelect: (coords: { lat: number; lng: number }) => void;
}

export default function MapEventsHandler({ onLocationSelect }: MapEventsHandlerProps) {
  useMapEvents({
    click(e: LeafletMouseEvent) {
      onLocationSelect(e.latlng);
    },
  });
  return null;
}
