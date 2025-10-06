<?php
    $servername = getenv('DB_HOST');
    $username   = getenv('DB_USERNAME');
    $password   = getenv('DB_PASSWORD');
    $dbname     = getenv('DB_NAME');

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    date_default_timezone_set('America/New_York');
    error_reporting(0);