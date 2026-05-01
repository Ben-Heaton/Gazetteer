<?php

    // Setting header (instructions) information.
    header('Content-Type: application/json; charset=UTF-8');

    /* !!!! COMMENT OUT IF RELIABLE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
    
    /* ==== GETTING READY =================================================== */

    // Allows access to stored api keys.
    $configPath = realpath(__DIR__ . '/../config/config.php');
    if ($configPath === false) {
        echo("config.php file path issue");
        exit;
    }
    require($configPath);

    // Recieve the variable from ajax and make it safe.
    $countryCodeSafe = trim(strtolower($_POST['countryCode']));

    // Insert the variable into the url.
    $url = "https://newsdata.io/api/1/latest?apikey=" . NEWSDATA_KEY . "&country=$countryCodeSafe&language=en&prioritydomain=top&size=10";


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

    // Empty array for data / errors.
    $infoArray = array();

    // Decoding json into ass. array to check over.
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

        // Third the api has responded but says something went wrong.
        // More info found here: https://newsdata.io/documentation#http-response
    } elseif ($responseHttpCode !== 200) {
        $httpError = null;
        $errorMessage = null;

        if ($responseHttpCode === 400) {
            $httpError = "Parameter missing.";
            $errorMessage = "API request is malformed or contains invalid parameters.";

        } elseif ($responseHttpCode === 401) {
            $httpError = "Unauthorized.";
            $errorMessage = "API key is invalid or missing";

        } elseif ($responseHttpCode === 403) {
            $httpError = "CORS policy failed. IP/Domain restricted.";
            $errorMessage = "API request is trying to access a resource on a different domain or IP address, 
            and the server has not been configured to allow cross-origin resource sharing (CORS) from that domain or IP.";

        } elseif ($responseHttpCode === 409) {
            $httpError = "Parameter duplicate.";
            $errorMessage = "A parameter has been passed to the API with a duplicate value.";

        } elseif ($responseHttpCode === 415) {
            $httpError = "Unsupported type.";
            $errorMessage = "API is unable to process a request because the request is in a format that is not supported by the API.";

        } elseif ($responseHttpCode === 422) {
            $httpError = "Unprocessable entity.";
            $errorMessage = "API is unable to process a request due to a semantic error in the request, 
            typically indicating that the request is well-formed, but the server is unable to understand or fulfill it.";

        } elseif ($responseHttpCode === 429) {
           $httpError = "Too many requests.";
           $errorMessage = "You have exceeded the rate limit for your plan.";

        } elseif ($responseHttpCode === 500) {
            $httpError = "Internal server error.";
            $errorMessage = "Unexpected error on the server. Try again later";
        }

        $infoArray = array(
            "httpErrorCode" => $responseHttpCode,
            "httpError" => $httpError,
            "errorMessage" => $errorMessage
        );

        // The api responded with it's own error json
    } elseif ($decoded['status'] === 'error') {
        $infoArray = array(
            "apiErrorCode" => $decoded['results']['code'],
            "apiErrorMessage" => $decoded['results']['message']
        );

        // All good. Extracting info.
    } else {
        foreach ($decoded['results'] as $article) {
            array_push($infoArray, [
                "imageUrl" => $article['image_url'],
                "title" => $article['title'],
                "link" => $article['link'],
                "description" => $article['description']
            ]);
        }
    }

    /* ==== READY TO SEND =================================================== */
    $result = json_encode($infoArray, JSON_PRETTY_PRINT);
    echo $result;
    
?>