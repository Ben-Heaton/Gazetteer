<?php

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    //ini_set('display_errors', 'On');
    //error_reporting(E_ALL);

    // Getting access to my api key/
    $configPath = realpath(__DIR__ . '/../config/config.php');
    if ($configPath === false) {
        echo("config.php file path issue");
        exit;
    }
    require($configPath);


    /* ==== API CALL 1 ==== GETTING THE NAMES OF THE CURRENCIES ========================================================================================================= */
    // For the first api call I want to get all currency names and thier respective codes.
    // E.g. "British Pound Sterling" and "GBP" (for comparisons later).

    /* ---- GETTING READY ---- */

    // API url and inserted key.
    $url_1 = "https://api.currencyapi.com/v3/currencies?apikey=" . CURRENTEXCHANGERATE_KEY;


    /* ---- MAKING THE API CALL ---- */

    // Initiate the cURL object.
    $ch_1 = curl_init();

    // Setting the cURL parameters.
    curl_setopt($ch_1, CURLOPT_URL, $url_1);
    curl_setopt($ch_1, CURLOPT_RETURNTRANSFER, true);

    // Execute and storing the cURL result.
    $result_1 = curl_exec($ch_1);

    // A variable for response http codes, curl error numbers, and readable error message respectively.
    $responseHttpCode_alpha = curl_getinfo($ch_1, CURLINFO_HTTP_CODE);
    $cURL_ERROR_NUMBER = curl_errno($ch_1);
    $cURL_ERROR_MESSAGE = curl_error($ch_1);

    // Closing cURL. 
    curl_close($ch_1);

    /* ---- CHECKING FOR ERRORS WITH API CALL ---- */

    // Empty array for data or errors
    $infoArray_1 = array();

    // Decoding the json into ass. array to check for errors.
    $decoded_1 = json_decode($result_1, true);

    // First did something go wrong with the curl?
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray_1 = array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );
        $errorEncountered = json_encode($infoArray_1, JSON_PRETTY_PRINT);
        echo $errorEncountered;
        exit;

        // Second did the api return a valid json?
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray_1 = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMessage" => json_last_error_msg()
        );
        $errorEncountered = json_encode($infoArray_1, JSON_PRETTY_PRINT);
        echo $errorEncountered;
        exit;


        // Third the api has responded but says something went wrong.
        // More info here: https://currencyapi.com/docs/status-codes
    } elseif ($responseHttpCode_alpha !== 200) {
        $specificError = null;

        if ($responseHttpCode_alpha === 403) {
            $specificError = "You are not allowed to use this endpoint.";

        } elseif ($responseHttpCode_alpha === 404) {
            $specificError = "The requested endpoint does not exist.";

        } elseif ($responseHttpCode_alpha === 422) {
            $specificError = "Validation Error.";

        } elseif ($responseHttpCode_alpha === 429) {
            $specificError = "Monthly rate limit hit.";

        } elseif ($responseHttpCode_alpha === 500) {
            $specificError = "Internal server error.";
        }

        $infoArray_1 = array(
            "code" => $responseHttpCode_alpha,
            "error" => $specificError
        );
        $errorEncountered = json_encode($infoArray_1, JSON_PRETTY_PRINT);
        echo $errorEncountered;
        exit;

        // All good. Extrating info. REMEMBER ALL THE VALUE ARE AGAINST 1 USD!
    } else {
        foreach($decoded_1['data'] as $currencies) {
            array_push($infoArray_1, [
                "currencyCode" => $currencies['code'],
                "currencyName" => $currencies['name']
            ]); 
        }
    }


    /* ==== API CALL 2 ==== GETTING THE VALUES OF THE CURRENCIES ======================================================================================================== */
    // For the second api call I want the latest currency exchange rate values.
    
    /* ---- GETTING READY ---- */

    // API url and inserted key.
    $url_2 = "https://api.currencyapi.com/v3/latest?apikey=" . CURRENTEXCHANGERATE_KEY;


    /* ---- MAKING THE API CALL ---- */

    // Initiate the cURL object.
    $ch_2 = curl_init();

    // Setting the cURL parameters.
    curl_setopt($ch_2, CURLOPT_URL, $url_2);
    curl_setopt($ch_2, CURLOPT_RETURNTRANSFER, true);

    // Execute and storing the cURL result.
    $result_2 = curl_exec($ch_2);

    // A variable for response http codes, curl error numbers, and readable error message respectively.
    $responseHttpCode_beta = curl_getinfo($ch_2, CURLINFO_HTTP_CODE);
    $cURL_ERROR_NUMBER = curl_errno($ch_2);
    $cURL_ERROR_MESSAGE = curl_error($ch_2);

    // Closing cURL. 
    curl_close($ch_2);

    /* ---- CHECKING FOR ERRORS WITH API CALL ---- */

    // Empty array for data or errors
    $infoArray_2 = array();

    // Decoding the json into ass. array to check for errors.
    $decoded_2 = json_decode($result_2, true);

    // First did something go wrong with the curl?
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray_2 = array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );
        $errorEncountered = json_encode($infoArray_2, JSON_PRETTY_PRINT);
        echo $errorEncountered;
        exit;

        // Second did the api return a valid json?
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray_2 = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMessage" => json_last_error_msg()
        );
        $errorEncountered = json_encode($infoArray_2, JSON_PRETTY_PRINT);
        echo $errorEncountered;
        exit;

        // Third the api has responded but says something went wrong.
        // More info here: https://currencyapi.com/docs/status-codes
    } elseif ($responseHttpCode_beta !== 200) {
        $specificError = null;

        if ($responseHttpCode_beta === 403) {
            $specificError = "You are not allowed to use this endpoint.";

        } elseif ($responseHttpCode_beta === 404) {
            $specificError = "The requested endpoint does not exist.";

        } elseif ($responseHttpCode_beta === 422) {
            $specificError = "Validation Error.";

        } elseif ($responseHttpCode_beta === 429) {
            $specificError = "Monthly rate limit hit.";

        } elseif ($responseHttpCode_beta === 500) {
            $specificError = "Internal server error.";
        }

        $infoArray_2 = array(
            "code" => $responseHttpCode_beta,
            "error" => $specificError
        );

        $errorEncountered = json_encode($infoArray_2, JSON_PRETTY_PRINT);
        echo $errorEncountered;
        exit;

        // All good. Extrating info. REMEMBER ALL THE VALUE ARE AGAINST 1 USD!
    } else {
        foreach($decoded_2['data'] as $currencyRate) {
            array_push($infoArray_2, [
                "currencyCode" => $currencyRate['code'],
                "currencyValue" => $currencyRate['value']
            ]);
        }
    }

    /* ==== COMAPRE & COMBINE INFO  ===================================================================================================================================== */
    // Now I need to merge the two list together based on the "currencyCode" to ultimatly make a json of {[currencyName => x, currencyCode => x, currencyValue => x], etc}.
    // The info in infoArray_1 is currencyCode & currencyName
    // The info in infoArray_2 is = currencyCode & currencyValue

    // Returns an array of values representing a single column from the input array, in this case the 'currencyCode'.
    // E.g: ['GBP' => ['currencyCode' => 'GBP', 'currencyName' => '...'], ...]
    $indexed_1 = array_column($infoArray_1, null, 'currencyCode');
    $indexed_2 = array_column($infoArray_2, null, 'currencyCode');

    // Iterates over every entry in $indexed_1 and merges it with the matching entry from $indexed_2.
    // using ?? [] as a safe fallback if a code exists in one array but not the other.
    $infoArrayFinal = array_values(
        array_map(
            fn($item) => array_merge($item, $indexed_2[$item['currencyCode']] ?? []),
            $indexed_1
        )
    );

    // Sort the array alphabetically by the currency name.
    usort($infoArrayFinal, fn($a, $b) => strcmp($a['currencyName'], $b['currencyName']));
    
    /* ==== SEND BACK TO AJAX =========================================================================================================================================== */
    $finalResult = json_encode($infoArrayFinal, JSON_PRETTY_PRINT);
    echo $finalResult;
    
?>