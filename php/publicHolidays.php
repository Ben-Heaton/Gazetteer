<?php

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
    
    /* ==== GETTING READY =================================================== */

    // Allows use of stored api keys.
    $configPath = realpath(__DIR__ . '/../config/config.php');
    if ($configPath === false) {
        echo("config.php file path issue");
        exit;
    }
    require($configPath);

    // Recieve the variables sent from JavaScript AJAX.
    $countryCode = $_POST['countryCode'];
    $year = $_POST['year'];

    // Make sure the varables are safe to use.
    $countryCodeSafe = trim(strtoupper($countryCode));
    $yearSafe = null;

    function checkIfNum($num) {
        global $yearSafe;
        if (is_numeric($num)) {
            $yearSafe = intval($num);
        } else {
            $invalidYear = "Invalid Year Integer";
            echo json_encode($invalidYear, JSON_PRETTY_PRINT);
            exit;
        }

    }

    checkIfNum($year);

    // Insert the now safe variables into the API URL address.
    $url = "https://calendarific.com/api/v2/holidays?&api_key=" . PUBLICHOLIDAYS_KEY . "&country=$countryCodeSafe&year=$yearSafe";


    /* ==== MAKING THE API CALL ============================================= */

    // Initiate the cURL object.
    $ch = curl_init();

    // Setting the cURL parameters.
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute and storing the cURL result.
    $result = curl_exec($ch);

    // Variables for response http codes, curl error numbers, and readable error message respectively.
    $responseHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cURL_ERROR_NUMBER = curl_errno($ch);
    $cURL_ERROR_MESSAGE = curl_error($ch);

    // Closing cURL. 
    curl_close($ch);


    /* ==== CHECKING FOR ERRORS WITH API CALL =============================== */

    // Empty array for data or errors
    $infoArray = array();

    // Decoding array for to check for errors.
    $decoded = json_decode($result, true);

    // Checking for errors, first was the cURL ok?
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray = array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );

        // Is the received json a valid one?
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMessage" => json_last_error_msg()
        );

        // The Calendarific api has detected an input error.
    } elseif ($responseHttpCode == 422) {
        $infoArray = array(
            "apiErrorCode" => $decoded['meta']['code'],
            "apiErrorDetail" => $decoded['meta']['error_detail']
        );

        // Did the api send back any other error?
        // More information can be found here: https://calendarific.com/api-documentation
    } elseif ($responseHttpCode !== 200) {
        $httpCode = null;

        if ($responseHttpCode == 401) {
            $httpCode = "Unauthorized Missing or incorrect API token in header.";
        } elseif ($responseHttpCode == 500) {
            $httpCode = "Internal Server Error.";
        } elseif ($responseHttpCode == 503) {
            $httpCode = "Service Unavailable (Planned service outage).";
        } elseif ($responseHttpCode == 429) {
            $httpCode = "Too many requests. API limits reached.";
        }
        
        $infoArray = array(
            "httpError" => $httpCode
        );

        // All good. Extracting info.
    } else {
        $holDates = [];
        $holNames = [];
        $holDescs = [];

        foreach ($decoded['response']['holidays'] as $holiday) {
            array_push($holDates, $holiday['date']['iso']);
            array_push($holNames, $holiday['name']);
            array_push($holDescs, $holiday['description']);  
        }

        $infoArray = array(
            "holDates" => $holDates,
            "holNames" => $holNames,
            "holDescs" => $holDescs
        );
    }

    /* ==== READY TO SEND =================================================== */

    // Encode and send back to ajax.
    $result = json_encode($infoArray, JSON_PRETTY_PRINT);
    echo $result;
    
?>