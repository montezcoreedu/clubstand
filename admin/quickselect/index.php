<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    $adminId = $_SESSION['account_id'];

    $qs_query = "SELECT q.SelectId, m.MemberId, m.LastName, m.FirstName, m.Suffix, m.EmailAddress, m.GradeLevel, m.MembershipTier, m.MemberPhoto 
        FROM quick_select q 
        INNER JOIN members m ON q.MemberId = m.MemberId 
        WHERE q.AdminId = $adminId 
        AND q.AddedOn >= NOW() - INTERVAL 1 DAY 
        ORDER BY m.LastName asc, m.FirstName asc";
    $qs_result = $conn->query($qs_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Quick Select</title>
    <?php include("../common/head.php"); ?>
    <style>
        .actions-dropdown {
            display: inline-flex;
            position: relative;
            margin-bottom: 1rem;
        }

        .action-btn, .dropdown-toggle {
            padding: 4px 8px;
        }

        .action-btn {
            border-right: none;
            border-radius: 4px 0 0 4px;
        }

        .dropdown-toggle {
            margin-left: 1px;
            border-radius: 0 4px 4px 0;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 220px;
            margin-top: 4px;
            background-color: rgb(255, 255, 255);
            border: 1px solid rgb(208, 208, 208);
            border-radius: 4px;
            box-shadow: 0px 4px 8px 0px rgba(208, 208, 208, 0.4);
            z-index: 1;
        }

        .dropdown-menu li {
            list-style: none;
        }

        .dropdown-menu li a {
            display: block;
            width: 100%;
            color: rgb(48, 48, 48);
            padding: 8px 10px;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
        }

        .dropdown-menu li a:hover {
            background-color: #f0f0f0;
        }
    </style>
    <script>
        function removeSelected() {
            const selected = document.querySelectorAll('.member-checkbox:checked');
            if (selected.length === 0) {
                alert("Please select at least one member to remove.");
                return;
            }

            if (confirm("Are you sure you want to remove the selected members from Quick Select?")) {
                document.getElementById("removeForm").submit();
            }
        }
    </script>
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
                <span>Quick Select</span>
            </li>
        </ul>
        <h2>Quick Select</h2>
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
        <div>
            <div class="actions-dropdown">
                <button class="action-btn">Group Functions</button>
                <button class="dropdown-toggle" onclick="toggleDropdown()"><i class="fa-solid fa-caret-down"></i></button>
                <ul class="dropdown-menu" id="dropdownMenu">
                    <li>
                        <a href="export_members.php">Export Members</a>
                    </li>
                    <li>
                        <a href="generate_registration.php">Generate Registration Keys</a>
                    </li>
                    <li>
                        <a href="changefieldvalue.php">Member Field Value</a>
                    </li>
                    <li>
                        <a href="printreports.php">Print Reports</a>
                    </li>
                    <li>
                        <a onclick="removeSelected()">Remove from Quick Select</a>
                    </li>
                </ul>
            </div>
        </div>
        <?php
            if ($qs_result->num_rows) {
                $memberCount = $qs_result->num_rows;
                echo "<p>Members in Quick Select ($memberCount)</p>";

                echo '<form id="removeForm" method="post" action="remove_selected.php">';
                    echo '<table class="members-table">';
                        echo '<thead>';
                            echo '<th><input type="checkbox" id="selectAll"></th>';
                            echo '<th align="left">Member Name</th>';
                            echo '<th align="left">Grade Level</th>';
                            echo '<th align="left">Membership</th>';
                            echo '<th align="left">Email Address</th>';
                        echo '</thead>';
                        echo '<tbody>';
                        while ($row = $qs_result->fetch_assoc()) {
                            $selectId = $row['SelectId'];
                            $memberId = $row['MemberId'];
                        
                            $memberPhoto = !empty($row['MemberPhoto']) 
                                ? "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>" 
                                : "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                        
                            echo "<tr>
                                <td align='center'>
                                    <input type='checkbox' class='member-checkbox' name='selected_ids[]' value='{$selectId}'>
                                </td>
                                <td>
                                    <a href='../members/lookup.php?id={$memberId}' class='member-name'>{$memberPhoto} {$row['LastName']}, {$row['FirstName']} {$row['Suffix']}</a>
                                </td>
                                <td>{$row['GradeLevel']}</td>
                                <td>{$row['MembershipTier']}</td>
                                <td><a href='mailto:{$row['EmailAddress']}'>{$row['EmailAddress']}</a></td>
                            </tr>";
                        }
                        echo '</tbody>';
                    echo '</table>';
                echo '</form>';
            } else {
                echo '<p>No members in quick select currently.</p>';
            }
        ?>
    </div>
    <script>
        function toggleDropdown() {
            const menu = document.getElementById("dropdownMenu");
            menu.style.display = (menu.style.display === "block") ? "none" : "block";
        }

        document.addEventListener("click", function (event) {
            const dropdown = document.querySelector(".actions-dropdown");
            if (!dropdown.contains(event.target)) {
                document.getElementById("dropdownMenu").style.display = "none";
            }
        });

        const selectAll = document.getElementById("selectAll");
        const checkboxes = document.querySelectorAll(".member-checkbox");

        selectAll.addEventListener("change", () => {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });

        checkboxes.forEach(cb => {
            cb.addEventListener("change", () => {
                const allChecked = [...checkboxes].every(cb => cb.checked);
                const noneChecked = [...checkboxes].every(cb => !cb.checked);

                if (allChecked) {
                    selectAll.checked = true;
                    selectAll.indeterminate = false;
                } else if (noneChecked) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                } else {
                    selectAll.indeterminate = true;
                }
            });
        });
    </script>
</body>
</html>