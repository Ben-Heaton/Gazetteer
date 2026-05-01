<?php

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
    
    /* ==== GETTING READY =================================================== */

    // Recieve variable sent from JavaScript AJAX.
    $countryCode = $_POST['countryCode'];

    // Making sure the variable is in uppercase and strips whitespace.
    $countryCodeSafe = trim(strtoupper($countryCode));

    // Insert sent variable into the API URL address.
    $url = "https://vantilburger.com/CountryFactsAPI/fact/random.php?cc=$countryCodeSafe";

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

    // Decoding received json for errors
    $decoded = json_decode($result, true);

    // Checking for errors, something wrong with the curl?
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray = array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );

        // curl ok, is the the response a valid json?
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMesage" => json_last_error_msg()
        );

        // json is valid but is the json correct for the country?
    } elseif ($decoded['countryid'] !== $countryCodeSafe) {
        $infoArray = array(
            "error" => "The json country iso_a2 code does not match the input iso_a2 code."
        );

        // all ok, extracting info.
    } else {
        $infoArray = array(
            "funFact" => $decoded['description']
        );
    }


    /* ==== READY TO SEND =================================================== */
    
    // Encode json.
    $result = json_encode($infoArray, JSON_PRETTY_PRINT);
        
    // Send back to ajax.
    echo $result;
    
?>