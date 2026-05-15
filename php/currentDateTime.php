<?php

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    //ini_set('display_errors', 'On');
    //error_reporting(E_ALL);
    
    /* ==== GETTING READY =================================================== */

    // Receive variable sent from JavaScript AJAX.
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    // Making sure the variables are safe to use.
    $latSafe = null; 
    $lngSafe = null;

    // Function to double check if the numbers are numbers.
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

    // Insert the safe variables into the API URL address.
    $url = "https://timeapi.io/api/v1/time/current/coordinate?latitude=$latSafe&longitude=$lngSafe";


    /* ==== MAKING THE API CALL ============================================= */

    // Initiate the cURL object.
    $ch = curl_init();

    // Setting the cURL parameters.
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute and storing the cURL result.
    $result = curl_exec($ch);

    // A variable for response http codes, curl error numbers, and readable error message respectively.
    $responseHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cURL_ERROR_NUMBER = curl_errno($ch);
    $cURL_ERROR_MESSAGE = curl_error($ch);

    // Closing cURL. 
    curl_close($ch);

    /* ==== CHECKING FOR ERRORS WITH API CALL =============================== */

    // Empty array for data or errors
    $infoArray = array();

    // Decoding the json into ass. array to check for errors.
    $decoded = json_decode($result, true);

    // First did something go wrong with the curl?
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray = array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );

        // Second did the api return a valid json?
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMessage" => json_last_error_msg()
        );

        // Third the api has responded but says something went wrong.
        // An example can be found here: https://timeapi.io/swagger/index.html
    } elseif ($responseHttpCode !== 200) {
        $infoArray = array(
            "code" => $decoded['status'],
            "errorTitle" => $decoded['title'],
            "errors" => $decoded['errors']
        );

        // All good. Extracting info.
    } else {
        $infoArray = array(
            "day" => substr($decoded['date'], 8, 2),
            "month" => substr($decoded['date'], 5, 2),
            "year" => substr($decoded['date'], 0, 4),
            "dotw" => $decoded['day_of_week'],
            "time" => substr($decoded['time'], 0, 5)
        );
    }

    /* ==== READY TO SEND =================================================== */

    // Convert back into JSON and send.
    $result = json_encode($infoArray, JSON_PRETTY_PRINT);
    echo $result;
    
?>