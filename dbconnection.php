<?php
    $servername = getenv('DB_HOST');
    $username   = getenv('DB_USERNAME');
    $password   = getenv('DB_PASSWORD');
    $dbname     = getenv('DB_DATABASE');
    $ssl_ca     = getenv('DB_SSL_CA');

    $conn = mysqli_init();

    mysqli_real_connect(
        $conn,
        $servername,
        $username,
        $password,
        $dbname,
        3306,
        NULL,
        MYSQLI_CLIENT_SSL,
        $ssl_ca
    );

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    date_default_timezone_set('America/New_York');
    error_reporting(0);
