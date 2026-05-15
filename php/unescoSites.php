<?php

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    //ini_set('display_errors', 'On');
    //error_reporting(E_ALL);
    
    /* ==== GETTING READY =================================================== */

    // Recieve the variable from JavaScript AJAX and make it safe to use.
    $alphaTwoUpperSafe = trim(strtoupper($_POST['alphaTwoUpper']));

    // Insert sent variable into the API URL address.
    $url = "https://data.unesco.org/api/explore/v2.1/catalog/datasets/whc001/records?limit=99&refine=iso_codes%3A%22$alphaTwoUpperSafe%22";


    /* ==== MAKING THE API CALL ============================================= */

    // Initiate the cURL object.
    $ch = curl_init();

    // Setting the cURL parameters.
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute and storing the cURL result.
    $result = curl_exec($ch);

    // A variable for response http codes, curl error numbers, and readable error message respectively.
    $responseHttpCode= curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
    } elseif ($responseHttpCode !== 200) {
        $infoArray = array(
            "code" => $responseHttpCode,
            "error" => "Look up HTTP status error code"
        );

        // Forth the api responed but there are no results.
    } elseif (empty($decoded['results'])) {
        $infoArray = array(
            "error" => "The iso_a2 country code did not feed through to the api."
        );

        // All good. Extrating info.
    } else {
        foreach($decoded['results'] as $unescoSite) {
            array_push($infoArray, [
                "siteName" => $unescoSite['name_en'],
                "siteLat" => $unescoSite['coordinates']['lat'],
                "siteLng" => $unescoSite['coordinates']['lon']
            ]); 
        }
    }


    /* ==== READY TO SEND =================================================== */

    // Convert back into JSON and send back to ajax.
    $result = json_encode($infoArray, JSON_PRETTY_PRINT);
    echo $result;
    
?>