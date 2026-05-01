<?php
    
    // Two PHP routines to return information from countryBorders.geo.json
    // Routine 1: Return JSON containing just codes and names (sorted) to populate the select.

    /* ==== Decoding the GeoJSON into an associative array ================== */
    $errorFlag = false;
    
    // File location.
    $jsonFileLocation = "../libraries/geoJSONData/countryBorders.geo.json";
    
    // Reads entire file into a string.
    $readJson = file_get_contents($jsonFileLocation);
    
    // Turns JSON into an associative array if true.
    $decoded = JSON_decode($readJson, true);

    /* ========== Data extraction (if possible) ============================= */
    
    // Empty array for data (if no errors).
    $extractedCountriesInfo = [];
    
    // First loop through JSON and check to see if it's ok to use before any data extraction. 
    foreach ($decoded['features'] as $country) {
        $countryProperties = $country['properties'];
        $countryCoords = $country['geometry']['coordinates'];
        
        // First check if any country property is blank. empty() returns true if the value is a falsy.
        if (empty($countryProperties['name']) || empty($countryProperties['iso_a2']) || empty($countryProperties['iso_a3']) ||empty($countryProperties['iso_n3'])) {
            $errorFlag = true;
            $extractedCountriesInfo = array(
                "errorMessage" => "One or more feature properties are missing from countryBorders.geo.json",
            );

            // Second check to if any country's polygons are blank.
        } elseif (empty($countryCoords)) {
            $errorFlag = true;
            $extractedCountriesInfo = array(
                "errorMessage" => "One or more feature coordinates are missing from countryBorders.geo.json",
            );

        } else {
            // The GeoJSON is safe. Extracting required data.
            $extractedCountriesInfo[] = [
                'name'   => $countryProperties['name'],
                'iso_a2' => $countryProperties['iso_a2'],
                'iso_a3' => $countryProperties['iso_a3'],
                'iso_n3' => $countryProperties['iso_n3'],
            ];
        }
    }

    /* ========== Encoding and sending new JSON ========== */

    if ($errorFlag === true) {
        // Encode array (an error message) as a JSON & send it back to AJAX. JS will then inform the user.
        $encoded = JSON_encode($extractedCountriesInfo, JSON_PRETTY_PRINT);
        echo $encoded;

    } else {
        // Sort 'extractedCountriesInfo' array item (another array) according to value of its 'name'.
        $country = array_column($extractedCountriesInfo, 'name');
        array_multisort($country, SORT_ASC, $extractedCountriesInfo);

        // Encode array as a JSON & send it back to AJAX. 'JSON_PRETTY_PRINT' option allows whitespace to format data.
        $encoded = JSON_encode($extractedCountriesInfo, JSON_PRETTY_PRINT);
        echo $encoded;
    }

?>