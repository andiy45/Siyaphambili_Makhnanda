<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    
    <!-- IMPORTANT: Leaflet CSS must be loaded first -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Your custom styles -->
    <style>
        /* IMPORTANT: Give the map a height */
        #map {
            height: 400px;
            width: 100%;
            margin: 20px 0;
            border: 2px solid #ccc;
            border-radius: 8px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }
        
        .close-btn-pop {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .hero {
            background-color: #f0f0f0;
            padding: 40px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <!-- Modal for messages -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <p id="messageContent"
               data-message="<?php echo isset($_SESSION['success']) ? $_SESSION['success'] : ''; ?>"
               data-message-type="<?php echo isset($_SESSION['success']) ? 'success' : ''; ?>"></p>
            <button class="close-btn-pop" onclick="closeModal()">Close</button>
        </div>
    </div>

    <div class="container">
        <!-- the head of the page -->
        <section class="hero">
            <div class="hero-content">
                <div>
                    <h2>Saving our community<br>
                    One Fix at a Time</h2>
                    <p>Easily report your issues and get community updates without hassle</p>
                </div>
            </div>
        </section>
        <br>

        <div class="card contact">
            <h4 class="card-title" id="headings">How to contact us?</h4>
            <h4 id="text"> 
                Email: Mymakhanda@gmail.com <br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Mymakhanda@ru.ac.za <br><br>
                Physical Address: City Hall, High Street, Makhanda, 6139<br>
                Postal Address: P.O. Box 176, Makhanda, 6140<br>
                Email: info@makana.gov.za<br>
                Fax: 046 622 9488 <br><br>
                Telephone:&nbsp;&nbsp;<a href="tel:+27 86 048 9892">+27 86 048 9892</a> <br>
                Office hours: 08:00 am - 16:30pm <br>
                Office: City Hall room 3 
            </h4>
        </div>

        <!-- Map section -->
        <div id="map"></div>

        <!-- the login and sign up area -->
        <?php
        if (file_exists('../landing_page/login.php')) {
            require_once('../landing_page/login.php');
        } else {
            echo '<p>Login system not available</p>';
        }
        ?>
    </div>

    <!-- Leaflet JavaScript - Load AFTER the map div exists -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Map initialization script -->
    <script>
        // Wait for the DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Check if the map container exists
            var mapContainer = document.getElementById('map');
            if (!mapContainer) {
                console.error('Map container not found!');
                return;
            }
            
            try {
                // Coordinates for Makhanda office
                var latitude = -33.3088;
                var longitude = 26.5328;

                // Creating the map
                var map = L.map('map').setView([latitude, longitude], 14);

                // Adding OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map);

                // Adding a marker
                var marker = L.marker([latitude, longitude]).addTo(map);

                // Popup when marker is clicked
                marker.bindPopup(
                    "<b>Makana Local Municipality</b><br>Makhanda, Eastern Cape"
                ).openPopup();

                console.log('Map initialized successfully!');
                
            } catch (error) {
                console.error('Error initializing map:', error);
                document.getElementById('map').innerHTML = '<p style="color:red;">Error loading map. Please check console for details.</p>';
            }
        });
    </script>

    <script>
        // Close modal function
        function closeModal() {
            document.getElementById('messageModal').style.display = 'none';
        }

        // Show modal if there's a message
        document.addEventListener('DOMContentLoaded', function() {
            var messageElement = document.getElementById('messageContent');
            var message = messageElement.getAttribute('data-message');
            if (message) {
                document.getElementById('messageModal').style.display = 'block';
            }
        });
    </script>
</body>
</html>