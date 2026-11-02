<?php

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    //ini_set('display_errors', 'On');
    //error_reporting(E_ALL);
    
    /* ==== GETTING READY =================================================== */
    
    // Recieve the variable sent from the JavaScript AJAX.
    $alphaTwoUpper = $_POST['alphaTwoUpper'];

    // Make sure it's in uppercase.
    $alphaTwoUpperSafe = strtoupper($alphaTwoUpper);

    // Insert the variable into the API URL address.
    $url = "https://countries.dev/alpha/$alphaTwoUpperSafe?fields=name%2Ccapital%2Cpopulation%2Ccurrencies%2Carea%2Cregion";

    /* ==== MAKING THE API CALL ============================================= */

    // Initiate the cURL object.
    $ch = curl_init();

    // Setting the cURL parameters.
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute and store the cURL result.
    $result = curl_exec($ch);
  
    // Returns a cURL error number (0 = fine) and a human readable version respectively.
    $cURL_ERROR_NUMBER = curl_errno($ch);
    $cURL_ERROR_MESSAGE = curl_error($ch);

    // Closing cURL. 
    curl_close($ch);

    /* ==== CHECKING FOR ERRORS WITH API CALL =============================== */

    // Decoding array into ass. array to check it over for errors.
    $decoded = json_decode($result, true);

    // Empty array for either error or info json.
    $infoArray = array();

    // First, is there a problem with the cURL?
	// If the countries.dev API returns 404 then it means the ISOA2 dindn't go through correctly.
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray = array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );

        // Second, cURL worked but is the response a valid JSON?
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMessage" => json_last_error_msg()
        );

        // All good. Extracting info.
    } else {
        $infoArray = array(
            "countryName" => $decoded['name'],
            "capitalName" => $decoded['capital'],
            "population" => $decoded['population'],
            "currencyCode" => $decoded['currencies'][0]['code'],
            // The area value returned uses km2 units.
            "area" => $decoded['area'],
            "worldRegion" => $decoded['region']
        );
    }

    /* ==== READY TO SEND =================================================== */

    // Encode the new json and send back to AJAX.
    $finalResult = json_encode($infoArray, JSON_PRETTY_PRINT);
    echo $finalResult;
    
?>