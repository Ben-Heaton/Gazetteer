<?php

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    //ini_set('display_errors', 'On');
    //error_reporting(E_ALL);
    
    /* ==== GETTING READY =================================================== */

    // Allows access to key from .env file.
    $configPath = realpath(__DIR__ . '/../config/config.php');
    if ($configPath === false) {
        echo("config.php file path issue");
        exit;
    }
    require($configPath);

    // receive variable sent from JavaScript AJAX.
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    // Making sure the numbers are numbers with function.
    $latSafe = null;
    $lngSafe = null;

    function checkIfNum($num1, $num2) {
        global $latSafe;
        global $lngSafe;
        if (is_numeric($num1) && is_numeric($num2)) {
            $latSafe = floatval($num1);
            $lngSafe = floatval($num2);
        } else {
            $invalidCoords = "Invalid Coordinates";
            echo json_encode($invalidCoords, JSON_PRETTY_PRINT);
            exit;
        }
    }

    checkIfNum($lat, $lng);

    // Insert the sent variables into the API URL address.
    $url = "https://api.openweathermap.org/data/2.5/weather?lat=$latSafe&lon=$lngSafe&appid=" . CURRENTWEATHER_KEY . "&units=metric";

   
    /* ==== MAKING THE API CALL ============================================= */

    // Initiate the cURL object.
    $ch = curl_init();

    // Setting the cURL parameters.
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute and storing the cURL result.
    $result = curl_exec($ch);

    // 1. returns an error number, 2. returns a human readable error message.
    $cURL_ERROR_NUMBER = curl_errno($ch);
    $cURL_ERROR_MESSAGE = curl_error($ch);

    // Closing cURL. 
    curl_close($ch);

    /* ==== CHECKING FOR ERRORS WITH API CALL =============================== */

    // Empty array for either result or error json.
    $infoArray = array();

    // Decode the (assumed) received json into an associate array to check over.
    $decoded = json_decode($result, true);

    // Error Checking, first did something go wrong with the curl call?
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray = array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );

        // Is the received json a valid json?
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMessage" => json_last_error_msg()
        );

        // Valid json received but api return an error json.
        // Info on errors found here: https://openweathermap.org/api/one-call-3?collection=one_call_api_3.0#errors
    } elseif ($decoded['cod'] !== 200) {
        $infoArray = array(
            "apiErrorNumber" => $decoded['cod'],
            "apiErrorMessage" => $decoded['message']
        );

        // All good. Extracting required info.
    } else {
        $infoArray = array(
            "main" => $decoded['weather'][0]['main'],
            "icon" => $decoded['weather'][0]['icon'],
            "temp" => $decoded['main']['temp']
        );
    }  

    /* ==== READY TO SEND =================================================== */

    // Convert back into JSON and send.
    $result = json_encode($infoArray, JSON_PRETTY_PRINT);
    echo $result;

?>