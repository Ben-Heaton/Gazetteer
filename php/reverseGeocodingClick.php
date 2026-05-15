<?php
    
    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    //ini_set('display_errors', 'On');
    //error_reporting(E_ALL);

    /* ==== GETTING READY =================================================== */
    
    /* realpath() returns canonicalized absolute pathname. It handles differences between windows and unix systems; hopefully solving pathing issues for me?
    __DIR__ is a magic constant. It means directory, ensures the correct path to the file specified is taken.
    require() is similar to include (evaluates the specified file) except require produces a fatal error if not working.
    Purpose: Runs the called file allowing any variables in the called file to be available within the current file, i.e. KEYS. */
    
    $configPath = realpath(__DIR__ . '/../config/config.php');
    // if realpath returns as false
    if ($configPath === false) {
        echo("config.php file path issue");
        exit;
    }
    require($configPath);

    // Receive the variables sent from JavaScript AJAX.
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
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
	
    // Insert sent variable into the API URL address.
    $url= "https://secure.geonames.org/countryCodeJSON?lat=$latSafe&lng=$lngSafe&username=" . GEONAMES_KEY;
	
	/* ==== MAKING THE API CALL ============================================= */

    // Initiate the cURL object.
    $ch = curl_init();

    // Setting the cURL parameters.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    
   // Executes and storing the cURL result.
    $result = curl_exec($ch);

    // Returns the last error number, and a human readable message respectively.
    $cURL_ERROR_NUMBER = curl_errno($ch);
    $cURL_ERROR_MESSAGE = curl_error($ch);

    // Closing cURL.
    curl_close($ch);

    /* ==== CHECKING FOR ERRORS WITH API CALL =============================== */

    // Decode the array to check it.
    $decoded = json_decode($result, true);
    
    // Empty array ready for eventual JSON encoding.
    $infoArray = array();

    // There's an error in the cURL API call.
    if ($cURL_ERROR_NUMBER !== 0) {
        $infoArray = array(
            "curlErrorNumber" => $cURL_ERROR_NUMBER,
            "curlErrorDescGeneral" => curl_strerror($cURL_ERROR_NUMBER),
            "curlErrorDescSpecific" => $cURL_ERROR_MESSAGE
        );

        // The call works but the response is not a valid JSON.
        // json_last_error() returns an int, so (if int !== No error has occrured).
    } elseif (json_last_error() !== JSON_ERROR_NONE) {
        $infoArray = array(
            "apiLastErrorNumber" => json_last_error(),
            "apiLastErrorDescription" => "Failure - Invalid JSON",
            "apiLastErrorMessage" => json_last_error_msg()  // Would return error string of above json_decode() call.
        );

        // The call works but the API returns an error JSON response.
        // Codes can be found here: https://www.geonames.org/export/webservice-exception.html
    } elseif (isset($decoded['status'])) {
        $infoArray = array(
            "apiErrorNumber" => $decoded['status']['value'],
            "apiErrorDescription" => $decoded['status']['message'],
        );

        // All fine. Extract info.
    } else {
        $infoArray = array(
            "languages" => $decoded['languages'],
            "countryCode" => $decoded['countryCode'],
            "countryName" => $decoded['countryName']
        );
    }

    /* ==== READY TO SEND =================================================== */
    
    // Encode the JSON to go back to AJAX
    $finalResult = json_encode($infoArray, JSON_PRETTY_PRINT);
    
    // Send JSON File.
    echo $finalResult;

?>