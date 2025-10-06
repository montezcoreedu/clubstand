<?php
    if (!empty($_GET['id'])) {
        $checkurl_sql = "SELECT MemberId FROM members WHERE MemberId = ".$_GET['id']."";
        $checkurl_query = $conn->query($checkurl_sql);
        $check_url = mysqli_num_rows($checkurl_query);
    }
    