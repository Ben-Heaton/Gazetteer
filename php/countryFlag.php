<?php
    //@file_get_contents($url)
    // Setting header (instructions) information.
    header('Content-Type: image/png');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
    
    /* ==== GETTING READY =================================================== */

    // Recieve variable sent from JavaScript AJAX.
    $alphaTwoLower = $_POST['alphaTwoLower'];

    // Make sure the variable is in lower case
    $alphaTwoLowerSafe = strtolower($alphaTwoLower);

    // Insert sent variable into the API URL address.
    $url = "https://flagfeed.com/flags/$alphaTwoLowerSafe";

    /* ==== MAKING THE CALL ================================================= */
    
    // Inititate the curl object.
    $ch = curl_init();

    // Setting the curl parameters.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);

    // Execute and store the result.
    $result = curl_exec($ch);

    // A variable for the response http code.
    // curl_getinfo (retieves information about the last transfer), & CURLINFO_HTTP_CODE (Is an integer).
    $responseHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // close the curl.
    curl_close($ch);

    /* ==== CHECKING FOR ERRORS WITH CALL =================================== */

    // Empty array just in case of error.
    $errArr = array();

    // Decode the json if the response is a error json
    $decoded = json_decode($result, true);
    
    // Error responses/codes can be found here: https://flagfeed.com/api#errors
    if ($responseHttpCode === 400) {
        header('Content-Type: application/json');   // Response is now a json, so need to set it. Overwriting the header at the top.
        $errArr = array(
            "code" => "BAD_REQUEST",
            "error" => $decoded['error']
        );
        echo json_encode($errArr, JSON_PRETTY_PRINT);


    } elseif ($responseHttpCode === 404) {
        header('Content-Type: application/json');
        $errArr = array(
            "code" => "NOT_FOUND",
            "error" => $decoded['error'],
            "message" => $decoded['message']
        );
        echo json_encode($errArr, JSON_PRETTY_PRINT);


    } elseif ($responseHttpCode === 429) {
        header('Content-Type: application/json');
        $errArr = array(
            "code" => "RATE_LIMITED",
            "cause" => "Too many requests from this origin; back off and retry",
        );
        echo json_encode($errArr, JSON_PRETTY_PRINT);

        /* ==== READY TO SEND =============================================== */
    } else {
        echo $result;
    }
    
?>