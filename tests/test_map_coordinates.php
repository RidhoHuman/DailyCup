<?php
require_once __DIR__ . '/../config/constants.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Map Coordinates</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        #testMap {
            height: 600px;
            width: 100%;
            border: 2px solid #6F4E37;
            border-radius: 10px;
            margin-top: 20px;
        }
        .info-box {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .coord {
            font-weight: bold;
            color: #6F4E37;
            font-size: 18px;
        }
        h1 {
            color: #6F4E37;
        }
        .status {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <h1>🗺️ Test Map Coordinates - DailyCup</h1>
    
    <div class="info-box">
        <h3>Koordinat dari constants.php:</h3>
        <p>
            <strong>Store Name:</strong> <?php echo STORE_NAME; ?><br>
            <strong>Address:</strong> <?php echo str_replace("\n", " ", STORE_ADDRESS); ?><br>
            <strong>Phone:</strong> <?php echo STORE_PHONE; ?>
        </p>
        <p class="coord">
            📍 Latitude: <?php echo STORE_LATITUDE; ?><br>
            📍 Longitude: <?php echo STORE_LONGITUDE; ?>
        </p>
        <p>
            <a href="https://www.google.com/maps?q=<?php echo STORE_LATITUDE; ?>,<?php echo STORE_LONGITUDE; ?>" 
               target="_blank" 
               style="color: #6F4E37; font-weight: bold;">
                🗺️ Lihat di Google Maps
            </a>
        </p>
    </div>

    <div id="status" class="status"></div>

    <div id="testMap"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const statusDiv = document.getElementById('status');
        
        try {
            // Get coordinates from PHP
            const STORE_LAT = <?php echo STORE_LATITUDE; ?>;
            const STORE_LNG = <?php echo STORE_LONGITUDE; ?>;
            const STORE_NAME = <?php echo json_encode(STORE_NAME); ?>;
            const STORE_ADDRESS = <?php echo json_encode(str_replace("\n", "<br>", STORE_ADDRESS)); ?>;
            
            console.log('Store Coordinates:', STORE_LAT, STORE_LNG);
            console.log('Store Name:', STORE_NAME);
            console.log('Store Address:', STORE_ADDRESS);
            
            // Validate coordinates
            if (!STORE_LAT || !STORE_LNG || isNaN(STORE_LAT) || isNaN(STORE_LNG)) {
                throw new Error('Invalid coordinates: LAT=' + STORE_LAT + ', LNG=' + STORE_LNG);
            }
            
            // Initialize map
            const map = L.map('testMap').setView([STORE_LAT, STORE_LNG], 15);
            
            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);
            
            // Create custom store icon
            const storeIcon = L.divIcon({
                className: 'custom-icon',
                html: '<div style="background: #28a745; color: white; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.4); border: 4px solid white; font-size: 24px;">🏪</div>',
                iconSize: [50, 50],
                iconAnchor: [25, 25]
            });
            
            // Add store marker
            const marker = L.marker([STORE_LAT, STORE_LNG], {icon: storeIcon})
                .addTo(map)
                .bindPopup('<strong>' + STORE_NAME + '</strong><br>' + STORE_ADDRESS + '<br><br><small>Lat: ' + STORE_LAT + '<br>Lng: ' + STORE_LNG + '</small>')
                .openPopup();
            
            // Add circle around store
            L.circle([STORE_LAT, STORE_LNG], {
                color: '#28a745',
                fillColor: '#28a745',
                fillOpacity: 0.2,
                radius: 500 // 500 meters
            }).addTo(map);
            
            statusDiv.className = 'status success';
            statusDiv.innerHTML = '✅ Map berhasil di-render!<br>Pin toko muncul di koordinat: ' + STORE_LAT + ', ' + STORE_LNG;
            
        } catch (error) {
            console.error('Error:', error);
            statusDiv.className = 'status error';
            statusDiv.innerHTML = '❌ Error: ' + error.message + '<br>Check browser console (F12) untuk detail.';
        }
    </script>
</body>
</html>
