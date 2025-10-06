<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Returning Members Form", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $query = "SELECT MemberId, LastName, FirstName, Suffix, GradeLevel, EmailAddress, LockAccess, RegistrationCompleted, MemberPhoto FROM members WHERE MemberStatus = 6 ORDER BY LastName asc, FirstName asc";
    $result = $conn->query($query);
    $members = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Returning Registration Form</title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="../home/index.php">Member Search</a>
            </li>
            <li>
                <a href="../manage/index.php">Manage Membership</a>
            </li>
            <li>
                <span>Returning Registration Form</span>
            </li>
        </ul>
        <h2>Returning Registration Form</h2>
        <?php
            if (isset($_SESSION['successMessage'])) {
                echo $_SESSION['successMessage'];
                unset($_SESSION['successMessage']);
            }

            if (isset($_SESSION['errorMessage'])) {
                echo $_SESSION['errorMessage'];
                unset($_SESSION['errorMessage']);
            }
        ?>
        <div style="margin: 1rem 0;">
            <div class="toggle-container">
                <label class="toggle-switch">
                    <input type="checkbox" id="toggleButton">
                    <span class="slider"></span>
                </label>
                <span id="toggleText" class="toggle-text closed">Closed Registration</span>
            </div>
        </div>
        <?php
            if (!empty($members)) {
                $memberCount = count($members);
                echo "<p>Remaining Members to Register: $memberCount</p>";

                echo '<form id="addForm" action="../quickselect/add_selected.php" method="post">';
                    echo '<div style="margin-bottom: 1.5rem;">';
                        echo '<button type="submit">Make Quick Select</button>';
                    echo '</div>';
                    echo '<table class="members-table" style="margin-bottom: 2rem;">';
                        echo '<thead>';
                            echo '<th><input type="checkbox" id="selectAll"></th>';
                            echo '<th align="left">Member Name</th>';
                            echo '<th align="left">Grade Level</th>';
                            echo '<th align="left">Email Address</th>';
                            echo '<th>Portal Access</th>';
                        echo '</thead>';
                        echo '<tbody>';
                        foreach ($members as $row) {
                            $memberPhoto = !empty($row['MemberPhoto']) 
                            ? "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>" 
                            : "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";

                            if ($row['LockAccess'] == 2 && $row['RegistrationCompleted'] == 1) {
                                $portalAccess = "<img src='../images/icon-check.svg' alt='Portal access icon' title='Has portal access'>";
                            } else {
                                $portalAccess = "<img src='../images/icon-caution.svg' alt='Portal access icon' title='Does\n't have portal access'>";
                            }
                        
                            echo "<tr>
                                <td align='center'>
                                    <input type='checkbox' class='member-checkbox' name='selected_ids[]' value='{$row['MemberId']}'>
                                </td>
                                <td>
                                    <a href='../members/membership.php?id={$row['MemberId']}' target='_blank' class='member-name'>{$memberPhoto} {$row['LastName']}, {$row['FirstName']} {$row['Suffix']}</a>
                                </td>
                                <td>{$row['GradeLevel']}</td>
                                <td><a href='mailto:{$row['EmailAddress']}'>{$row['EmailAddress']}</a></td>
                                <td align='center'>{$portalAccess}</td>
                            </tr>";
                        }
                        echo '</tbody>';
                    echo '</table>';
                echo '</form>';
            } else {
                echo '<p>No members currently in pre-register status.</p>';
            }
        ?>
    </div>
    <script>
        const toggleButton = document.getElementById('toggleButton');
        const toggleText = document.getElementById('toggleText');

        function fetchStatus() {
            fetch('get_registration_status.php')
            .then(response => response.json())
            .then(data => updateToggle(data.status));
        }

        function updateToggle(status) {
            if (status == 1) {
                toggleButton.checked = true;
                toggleText.textContent = 'Open Registration';
                toggleText.classList.remove('closed');
                toggleText.classList.add('open');
            } else {
                toggleButton.checked = false;
                toggleText.textContent = 'Closed Registration';
                toggleText.classList.remove('open');
                toggleText.classList.add('closed');
            }
        }

        toggleButton.addEventListener('change', () => {
            fetch('toggle_registration.php')
                .then(response => response.json())
                .then(data => updateToggle(data.status));
        });

        fetchStatus();
    </script>
</body>
</html>
