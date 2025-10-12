<?php
    $servername = getenv('DB_HOST');
    $username   = getenv('DB_USERNAME');
    $password   = getenv('DB_PASSWORD');
    $dbname     = getenv('DB_DATABASE');
    $ssl_ca     = getenv('DB_SSL_CA');

    $conn = mysqli_init();

    mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
    mysqli_ssl_set($conn, NULL, NULL, $ssl_ca, NULL, NULL);

    if (!mysqli_real_connect($conn, $servername, $username, $password, $dbname, 3306, NULL, MYSQLI_CLIENT_SSL)) {
        die("Connection failed: " . mysqli_connect_error());
    }

    date_default_timezone_set('America/New_York');
    // error_reporting(0);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
