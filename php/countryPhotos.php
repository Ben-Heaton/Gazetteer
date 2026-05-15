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

    // Recieve the variable sent from AJAX. urlencode() replaces any spaces with a '+' instead.
    $countryNameSafe = urlencode($_POST['countryName']);

    // Insert the variable into the API URL address.
    $url = "https://api.pexels.com/v1/search?query=$countryNameSafe&per_page=20";


    /* ==== MAKING THE API CALL ============================================= */

    // Initiate the cURL object.
    $ch = curl_init();

    // Setting the cURL parameters.
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: " . COUNTRYPHOTOS_KEY]);

    // Execute and storing the cURL result.
    $result = curl_exec($ch);

    // A variable for response http codes, curl error numbers, and readable error message respectively.
    $responseHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cURL_ERROR_NUMBER = curl_errno($ch);
    $cURL_ERROR_MESSAGE = curl_error($ch);

    // Closing cURL. 
    curl_close($ch);


    /* ==== CHECKING FOR ERRORS WITH API CALL =============================== */

    // Empty array for data or errors.
    $infoArray = array();

    // Decoding result into ass. array to check over for errors.
    $decoded = json_decode($result, true);

    // Is the curl ok?
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray = array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );

        // Did the api return a valid json?
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMessage" => json_last_error_msg()
        );

        // The api works but has returned an error.
        // More info can be found here: https://help.pexels.com/hc/en-us/articles/900006376303-What-does-this-HTTP-response-code-mean
    } elseif ($responseHttpCode != 200) {
        $httpError = null;

        if ($responseHttpCode == 401) {
            $httpError = "You have not provided the proper Authorization header.";

        } elseif ($responseHttpCode == 403) {
            $httpError = "You do not have access to the resource you are requesting.";

        } elseif ($responseHttpCode == 429) {
            $httpError = "You have exceeded your allocated rate limit.";

            // Condition is like so because response can be any 500 number.
        } elseif ($responseHttpCode >= 500 && $responseHttpCode <= 599) {
            $httpError = "Server Error.";
        }

        $infoArray = array(
            "apiError" => $httpError
        );

        // All good Extracting data.
    } else {
        foreach($decoded['photos'] as $photo) {
            array_push($infoArray, [
                "photoMedium" => $photo['src']['medium'],
                "photoAlt" => $photo['alt'],
                "photoUrl" => $photo['url'],
                "photographerUrl" => $photo['photographer_url'],
                "photographerName" => $photo['photographer']
            ]);
        }

    }


    /* ==== READY TO SEND =================================================== */
    
    // Convert back into JSON and send.
    $result = json_encode($infoArray, JSON_PRETTY_PRINT);
    echo $result;
    
?>