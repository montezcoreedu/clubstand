<?php
    include("../../dbconnection.php");

    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');

    $searchTerm = isset($_GET['query']) ? trim($_GET['query']) : '';

    if ($searchTerm) {
        $searchTerm = "%$searchTerm%";
        
        $stmt = $conn->prepare("
            (SELECT 'Member' AS type, MemberId AS id, CONCAT('../../MemberPhotos/', MemberPhoto) AS image, 'member-photo' as addClass, CONCAT(FirstName, ' ', LastName, ' ', Suffix) AS name, EmailAddress AS details 
            FROM members WHERE FirstName LIKE ? AND MemberStatus IN (1, 2) OR LastName LIKE ? AND MemberStatus IN (1, 2) OR EmailAddress LIKE ? AND MemberStatus IN (1, 2))
            UNION
            (SELECT 'Page' AS type, PageName AS id, CONCAT('../images/', PageImage, '.png') AS image, 'noImg' as addClass, PageName AS name, PageUrl AS details FROM pages WHERE PageName LIKE ?)
            UNION
            (SELECT 'Primary Contact' AS type, MemberId AS id, '../images/noprofilepic.png' AS image, 'member-photo' as addClass, PrimaryContactName AS name, CONCAT('Associated with:', ' ', FirstName, ' ', LastName) AS details 
            FROM members WHERE PrimaryContactName LIKE ? AND MemberStatus IN (1, 2) OR PrimaryContactEmail LIKE ? AND MemberStatus IN (1, 2))
            UNION
            (SELECT 'Secondary Contact' AS type, MemberId AS id, '../images/noprofilepic.png' AS image, 'member-photo' as addClass, SecondaryContactName AS name, CONCAT('Associated with:', ' ', FirstName, ' ', LastName) AS details 
            FROM members WHERE SecondaryContactName LIKE ? AND MemberStatus IN (1, 2) OR SecondaryContactEmail LIKE ? AND MemberStatus IN (1, 2))
            ORDER BY name asc
        ");

        $stmt->bind_param("ssssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();

        $results = [];
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }

        echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        exit;
    }
