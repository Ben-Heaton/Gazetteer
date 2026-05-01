<?php
    // 1. Specifying a path to the .env file, realpath help solve differences between windows and unix pathing.
    // 2. '__DIR__' is a magic constant. It means directory, helps ensure the correct path to the file specified is taken. In this case the directory config.php is in.
    // 3. If the path fails (realpath returns false), say there's an issue, otherwise...
    // 4. ...Read the file specified, which returns the contents as an associative array.
    // 'INI_SCANNER_RAW' setting means when parsing it treats all values in the file specified as raw strings, good for API keys.
    
    $envFilePath = realpath(__DIR__ . '/../.env');
    
    // if realpath returns as false
    if ($envFilePath === false) {
        echo("Path to .env file issue");
        exit;
    }
    $env = parse_ini_file($envFilePath, false, INI_SCANNER_RAW);
    
    // Creating global constants for $env. Now allows the API Keys to be accessable anywhere.
    define('CALCULATEDISTANCE_KEY', $env['CALCULATEDISTANCE_KEY']);
    define('COUNTRYPHOTOS_KEY', $env['COUNTRYPHOTOS_KEY']);
    define('CURRENTEXCHANGERATE_KEY', $env['CURRENTEXCHANGERATE_KEY']);
    define('CURRENTWEATHER_KEY', $env['CURRENTWEATHER_KEY']);
    define('INTERNATIONALAIRPORTS_KEY', $env['INTERNATIONALAIRPORTS_KEY']);
    define('MAJORCITIES_KEY', $env['MAJORCITIES_KEY']);
    define('PUBLICHOLIDAYS_KEY', $env['PUBLICHOLIDAYS_KEY']);
    define('WEATHERFORECAST_KEY', $env['WEATHERFORECAST_KEY']);
    define('GETCAPITALCOORDS_KEY', $env['GETCAPITALCOORDS_KEY']);
    define('GEONAMES_KEY', $env['GEONAMES_KEY']);
    define('NEWSDATA_KEY', $env['NEWSDATA_KEY']);
?>