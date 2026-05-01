<?php

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
    
    /* ==== GETTING READY =================================================== */

    // Allows access to my stored api keys.
    $configPath = realpath(__DIR__ . '/../config/config.php');
    if ($configPath === false) {
        echo("config.php file path issue");
        exit;
    }
    require($configPath);

    // Recieve the variable sent from JavaScript AJAX & make it safe.
    $alphaTwoUpperSafe = trim(strtoupper($_POST['alphaTwoUpper']));

    // Insert the sent variable into the API URL address.
    $url = "https://airlabs.co/api/v9/airports?country_code=$alphaTwoUpperSafe&api_key=" . INTERNATIONALAIRPORTS_KEY;

    
    /* ==== MAKING THE API CALL ============================================= */

    // Initiate the cURL object.
    $ch = curl_init();

    // Setting the cURL parameters.
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute and storing the cURL result.
    $result = curl_exec($ch);

    // A variable for response http codes, curl error numbers, and readable error message respectively.
    $cURL_ERROR_NUMBER = curl_errno($ch);
    $cURL_ERROR_MESSAGE = curl_error($ch);

    // Closing cURL. 
    curl_close($ch);


    /* ==== CHECKING FOR ERRORS WITH API CALL =============================== */
    // Empty array for data or errors
    $infoArray = array();

    // Decoding the json into ass. array to check for errors.
    $decoded = json_decode($result, true);

    // Is the curl ok?
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray = array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );

        // Is the response a valid json?
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMessage" => json_last_error_msg()
        );

        // Third the api has responded but says something went wrong.
        // More info here: https://airlabs.co/docs/#docs_Errors
    } elseif (isset($decoded['error'])) {
        $infoArray = array(
            "code" => $decoded['error']['code'],
            "message" => $decoded['error']['message'],
        );

        // All good. Extracting info.
    } else {
        foreach($decoded['response'] as $airport) {
            array_push($infoArray, [
                "iata_code" => $airport['iata_code'],
                "icao_code" => $airport['icao_code'],
                "airportName" => $airport['name'],
                "airportLat" => $airport['lat'],
                "airportLng" => $airport['lng']
            ]); 
        }
    }
    
    /* ==== READY TO SEND =================================================== */
    
    // Convert into a JSON and send back to ajax.
    $result = json_encode($infoArray, JSON_PRETTY_PRINT);
    echo $result;
    
?>