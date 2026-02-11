// Mock data for Indonesian regions
// In a real app, this would come from an API (e.g., RajaOngkir)

export interface Region {
  id: string;
  name: string;
}

export const provinces: Region[] = [
  { id: "AC", name: "Aceh" },
  { id: "SU", name: "Sumatera Utara" },
  { id: "SB", name: "Sumatera Barat" },
  { id: "RI", name: "Riau" },
  { id: "JK", name: "DKI Jakarta" },
  { id: "JB", name: "Jawa Barat" },
  { id: "JT", name: "Jawa Tengah" },
  { id: "JI", name: "Jawa Timur" },
  { id: "YO", name: "DI Yogyakarta" },
  { id: "BT", name: "Banten" },
  { id: "BA", name: "Bali" },
];

export const cities: Record<string, Region[]> = {
  "JK": [
    { id: "JK01", name: "Jakarta Pusat" },
    { id: "JK02", name: "Jakarta Selatan" },
    { id: "JK03", name: "Jakarta Barat" },
    { id: "JK04", name: "Jakarta Timur" },
    { id: "JK05", name: "Jakarta Utara" },
  ],
  "JB": [
    { id: "JB01", name: "Bandung" },
    { id: "JB02", name: "Bogor" },
    { id: "JB03", name: "Bekasi" },
    { id: "JB04", name: "Depok" },
  ],
  "JT": [
    { id: "JT01", name: "Semarang" },
    { id: "JT02", name: "Surakarta (Solo)" },
    { id: "JT03", name: "Magelang" },
  ],
  "JI": [
    { id: "JI01", name: "Surabaya" },
    { id: "JI02", name: "Malang" },
    { id: "JI03", name: "Kediri" },
  ],
   "YO": [
    { id: "YO01", name: "Yogyakarta" },
    { id: "YO02", name: "Sleman" },
    { id: "YO03", name: "Bantul" },
  ],
  "BA": [
    { id: "BA01", name: "Denpasar" },
    { id: "BA02", name: "Badung" },
    { id: "BA03", name: "Gianyar" },
  ],
};

export const districts: Record<string, Region[]> = {
  // Jakarta Selatan
  "JK02": [
    { id: "JK0201", name: "Kebayoran Baru" },
    { id: "JK0202", name: "Kebayoran Lama" },
    { id: "JK0203", name: "Tebet" },
    { id: "JK0204", name: "Setiabudi" },
    { id: "JK0205", name: "Cilandak" },
  ],
  // Bandung
  "JB01": [
    { id: "JB0101", name: "Coblong" },
    { id: "JB0102", name: "Cicendo" },
    { id: "JB0103", name: "Sumur Bandung" },
    { id: "JB0104", name: "Sukajadi" },
  ],
  // Malang - Kecamatan
  "JI02": [
    { id: "JI0201", name: "Klojen" },
    { id: "JI0202", name: "Blimbing" },
    { id: "JI0203", name: "Kedungkandang" },
    { id: "JI0204", name: "Sukun" },
    { id: "JI0205", name: "Lowokwaru" },
  ],
  // Surabaya - Kecamatan
  "JI01": [
    { id: "JI0101", name: "Sukolilo" },
    { id: "JI0102", name: "Gubeng" },
    { id: "JI0103", name: "Rungkut" },
    { id: "JI0104", name: "Tegalsari" },
    { id: "JI0105", name: "Wonokromo" },
  ],
  // Default fallback for others
  "DEFAULT": [
    { id: "Df01", name: "Kecamatan Kota" },
    { id: "Df02", name: "Kecamatan Barat" },
    { id: "Df03", name: "Kecamatan Timur" },
  ]
};
