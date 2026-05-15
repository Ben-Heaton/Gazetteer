<?php
    
    // Two PHP routines to return information from countryBorders.geo.json
    // Routine 2: Return JSON containing just the feature for the selected country.

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* ==== UNCOMMENT DURING DEV ============================================ */
    //ini_set('display_errors', 'On');
    //error_reporting(E_ALL);

    // Receive the variable sent from JavaScript AJAX.
    $isoA2 = $_POST['isoA2'];

    // Making sure the variable is in upper case.
    $isoA2Safe = strtoupper($isoA2); 


    /* ==== READY, Decoding the GeoJSON into an associative array =========== */
    
    // File location.
    $jsonFileLocation = "../libraries/geoJSONData/countryBorders.geo.json";
    
    // Reads entire file into a string.
    $readJson = file_get_contents($jsonFileLocation);
    
    // Turns JSON into an associative array if true.
    $decoded = JSON_decode($readJson, true);

    
    /* ==== Extracting the country feature ================================== */
    // As the code in populateCountrySelectR1.php has already checked countryBorders.geo.json is ok to use, there is no need here, I think.

    // Empty array ready for info.
    $selectedCountryFeature = [];

    // Push the feature for the selected country into the array.
    foreach ($decoded['features'] as $country) {
        if ($country['properties']['iso_a2'] === $isoA2Safe) {
            $selectedCountryFeature = [
                'name' => $country['properties']['name'],
                'iso_a2' => $country['properties']['iso_a2'],
                'iso_a3' => $country['properties']['iso_a3'],
                'iso_n3' => $country['properties']['iso_n3'],
                'coordinates' => $country['geometry']['coordinates'],
            ];
            // Stop going through geoJson once country has been found.
            break;
        }
    }

    /* ========== Encoding and sending new JSON ========== */

    // Encode array as a JSON & send it back to AJAX. 'JSON_PRETTY_PRINT' option allows whitespace to format data.
    $encoded = JSON_encode($selectedCountryFeature, JSON_PRETTY_PRINT);
    echo $encoded;
    
?>