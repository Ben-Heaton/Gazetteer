<?php

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
    
    
    /* ==== GETTING READY =================================================== */

    // Recieve variable sent from JavaScript AJAX.
    $country = $_POST['country'];

    // Make sure the variable is safe to pass through.
    $countrySafe = trim($country);

    // Insert sent variable into the API URL address.
    // Query should return with up to 15 results. The number varies to to wikipedia end.
    $url = "https://en.wikipedia.org/w/api.php?action=query&generator=search&gsrsearch=$countrySafe&gsrnamespace=0&gsrlimit=15&prop=pageimages|pageterms&piprop=thumbnail&pithumbsize=300&format=json";

    
    /* ==== MAKING THE API CALL ============================================= */
    
    // Initiate the cURL object.
    $ch = curl_init();

    // Setting the cURL parameters.
    curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'User-Agent: project1/1.0 (benheaton@live.co.uk)'
	));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute and storing the cURL result.
    $result = curl_exec($ch);

    // Returns an error number, returns a human readable error message respectively.
    $cURL_ERROR_NUMBER = curl_errno($ch);
    $cURL_ERROR_MESSAGE = curl_error($ch);

    // Closing cURL. 
    curl_close($ch);
 

    /* ==== CHECKING FOR ERRORS WITH API CALL =============================== */

    // Empty array for data or errors.
    $infoArray = array();

    // Decode the returned json in an ass. array.
    $decoded = json_decode($result, true);

    // Checking for errors, first did the cURL work?
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray = array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );

        // Second, the cURl was ok but is the response a valid json?
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMessage" => json_last_error_msg()
        );

        // Third, the json is valid but did the api return an error json?
        // Further info: https://www.mediawiki.org/wiki/API:Errors_and_warnings
    } elseif (isset($decoded['error'])) {
        $infoArray = array(
            "apiErrorCode" => $decoded['error']['code'],
            "apiErrorInfo" => $decoded['error']['info']
        );

        // All good. Extract infomation.
    } else {
        foreach ($decoded['query']['pages'] as $page) {
            if (isset($page['thumbnail']) == true) {
                array_push($infoArray, [
                    "title" => $page['title'],
                    "thumbnail" => $page['thumbnail']['source'],
                    "description" => $page['terms']['description'][0]
                ]);
            }
            if (count($infoArray) === 10) {
                break;
            }
        }
    }  

    /* ==== READY TO SEND =================================================== */

    // Convert back into JSON and send.
    $result = json_encode($infoArray, JSON_PRETTY_PRINT);
    echo $result;

?>