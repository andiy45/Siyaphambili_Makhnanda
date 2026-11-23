<?php

header('Content-Type: application/json');

$latitude = -33.28647;
$longitude = 26.52281;

$url = "https://api.open-meteo.com/v1/forecast?"
     . "latitude=" . $latitude
     . "&longitude=" . $longitude
     . "&current=temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m"
     . "&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max"
     . "&timezone=Africa%2FJohannesburg"
     . "&forecast_days=7";

$response = file_get_contents($url);

if ($response === false) {

    echo json_encode([
        "error" => true,
        "message" => "Unable to retrieve weather information."
    ]);

    exit;
}

echo $response;

?>