<?php

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    //ini_set('display_errors', 'On');
    //error_reporting(E_ALL);
    
    /* ==== GETTING READY =================================================== */

    // Allows access to stored api keys.
    $configPath = realpath(__DIR__ . '/../config/config.php');
    if ($configPath === false) {
        echo("config.php file path issue");
        exit;
    }
    require($configPath);

    // Recieve variables from ajax and make sure they are safe to use.
    $ccLatSafe = floatval($_POST['ccLat']);
    $ccLngSafe = floatval($_POST['ccLng']);
    $pcLatSafe = floatval($_POST['pcLat']);
    $pcLngSafe = floatval($_POST['pcLng']);

    // Insert variables into url.
    $url = "https://distance-calculator8.p.rapidapi.com/calc?startLatitude=$ccLatSafe&startLongitude=$ccLngSafe&endLatitude=$pcLatSafe&endLongitude=$pcLngSafe";


    /* ==== MAKING THE API CALL ============================================= */

    // Initiate the curl object.
    $ch = curl_init();

    // Setting option and now keys like CALCULATEDISTANCE_KEY are now avalable, which can be concatenated to where required.
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "x-rapidapi-host: distance-calculator8.p.rapidapi.com",
            "x-rapidapi-key: " . CALCULATEDISTANCE_KEY
        ],
    ]);

    // Execute and store the curl result.
    $result = curl_exec($ch);
    
    // A variable for response http codes, curl error numbers, and readable error message respectively.
    $responseHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cURL_ERROR_NUMBER = curl_errno($ch);
    $cURL_ERROR_MESSAGE = curl_error($ch);

    // Clsoing the curl.
    curl_close($ch);


    /* ==== CHECKING FOR ERRORS WITH API CALL =============================== */

    // Empty array for data / errors.
    $infoArray = array();

    // Decoding json into ass. array to check over.
    $decoded = json_decode($result, true);

    // Error checking, first is the curl ok?
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray = array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );

        // did the api return a valid json?
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMessage" => json_last_error_msg()
        );

        // Third the api has responded but says something went wrong.
        // Rapidapi isn't very clear but seems to use normal code: https://docs.rapidapi.com/docs/response-headers
    } elseif ($responseHttpCode !== 200) {
        $infoArray = array(
            "code" => $responseHttpCode,
            "error" => "Something went wrong on the api end. Look up http 'code'."
        );

        // All good. Extrating info.
    } else {
        $infoArray = array(
            "kilometers" => $decoded['body']['distance']['kilometers'],
            "miles" => $decoded['body']['distance']['miles']
        );
    }


    /* ==== READY TO SEND =================================================== */
    $result = json_encode($infoArray, JSON_PRETTY_PRINT);
    echo $result;

?>