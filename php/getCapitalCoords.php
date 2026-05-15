<?php

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    //ini_set('display_errors', 'On');
    //error_reporting(E_ALL);
    
    /* ==== GETTING READY =================================================== */

    // Getting access to required api key.
    $configPath = realpath(__DIR__ . '/../config/config.php');
    if ($configPath === false) {
        echo("config.php file path issue");
        exit;
    }
    require($configPath);

    // Retrieving variable from AJAX.
    $isoA2Code = $_POST['isoA2Code'];

    // Making sure code is in upper case.
    $isoA2Safe = strtoupper($isoA2Code);

    /* ==== MAKING THE API CALL ============================================= */

    // Initiate the cURL object.
    $ch = curl_init();
	
    // Insert the variable into url and set options.
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.api-ninjas.com/v1/city?country=$isoA2Safe",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "x-api-key: " . GETCAPITALCOORDS_KEY
        ],
    ]);

    // Execute and store the result.
    $result = curl_exec($ch);

    // Returns the error number and a readable message respectively.
    $cURL_ERROR_NUMBER = curl_errno($ch);
    $cURL_ERROR_MESSAGE = curl_error($ch);

    // Closing the cURL.
    curl_close($ch);

    /* ==== CHECKING FOR ERRORS AND EXTRACTING DATA (if no errors) ========== */
    
    // Decode the JSON into associative array check it.
    $decoded = json_decode($result, true);

    // Empty array ready for data.
    $infoArray = array();

    // Now checking the recieved JSON... Is there a problem with cURL?
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray= array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );

        // Is it an invalid JSON?
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMessage" => json_last_error_msg()
        );

        // The api call has worked, but has returned an error json.
        // Specific error codes can be found here: https://api-ninjas.com/error-codes
        // An example can be found here: https://api-ninjas.com/blog/api-exception-handling
    } elseif (isset($decoded['error'])) {
        $infoArray = array(
            "apiErrorMessage" => $decoded['error']['message'],
            "apiErrorDetails" => $decoded['error']['details']
        );

        // All good. Extracting info.
    } else {
        $infoArray = array(
            "capitalName" => $decoded[0]['name'],
            "capitalLat" => $decoded[0]['latitude'],
            "capitalLng" => $decoded[0]['longitude'],
        );
    }

    /* ==== READY TO SEND =================================================== */
    
    // Encode the json and send back to ajax.
    $finalResult = json_encode($infoArray, JSON_PRETTY_PRINT);
    echo $finalResult;
    
?>