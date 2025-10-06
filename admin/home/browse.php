<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    $SQL = '';
    $member_count = 0;

    if (!empty($_GET['LastName'])) {
        $lastName = $_GET['LastName'];
        $SQL = "LastName LIKE '$lastName%' AND MemberStatus IN (1, 2)";
    }

    if (!empty($_GET['GradeLevel'])) {
        $grade = $_GET['GradeLevel'];
        $gradeLevels = [
            '9' => '9th Grade',
            '10' => '10th Grade',
            '11' => '11th Grade',
            '12' => '12th Grade'
        ];
        $gradeLabel = $gradeLevels[$grade] ?? 'Unknown';

        $SQL = "GradeLevel = '$grade' AND MemberStatus IN (1, 2)";
    }

    if (!empty($_GET['Gender'])) {
        $genderCode = $_GET['Gender'];
        $gender = ($genderCode === 'Female') ? 'Female' : 'Male';

        $SQL = "Gender = '$gender' AND MemberStatus IN (1, 2)";
    }

    if (!empty($_GET['Officers'])) {
        $SQL = "Position > '' AND MemberStatus IN (1, 2)";
    }

    if (!empty($_GET['Alumni'])) {
        $SQL = "MemberStatus = 5";
    }

    if (
        !empty($_GET['LastName']) || !empty($_GET['GradeLevel']) || 
        !empty($_GET['Gender']) || !empty($_GET['Officers']) || 
        !empty($_GET['Alumni'])
    ) {
        $members_query = "
            SELECT MemberId, FirstName, LastName, Suffix, Position, GradeLevel, EmailAddress, MembershipTier, MemberPhoto 
            FROM members 
            WHERE $SQL 
            ORDER BY LastName ASC, FirstName ASC
        ";
        $members_result = $conn->query($members_query);
        $member_count = mysqli_num_rows($members_result);

        if ($member_count === 1) {
            $direct_sql = "SELECT MemberId FROM members WHERE $SQL";
            $direct_query = $conn->query($direct_sql);
            $direct = mysqli_fetch_assoc($direct_query);
            $memberId = $direct['MemberId'];

            header("Location: ../members/lookup.php?id=$memberId");
            exit;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Browse Members</title>
    <?php include("../common/head.php"); ?>
    <script>
        function addSelected() {
            const selected = document.querySelectorAll('.member-checkbox:checked');
            if (selected.length === 0) {
                alert("Please select at least one member to add.");
                return;
            }

            if (confirm("Are you sure you want to add the selected members from Quick Select?")) {
                document.getElementById("addForm").submit();
            }
        }
    </script>
</head>
<body>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="index.php">Member Search</a>
            </li>
            <li>
                <span>Browse Members</span>
            </li>
        </ul>
        <h2>Browse Members</h2>
        <?php
            if (
                !empty($_GET['LastName']) || !empty($_GET['GradeLevel']) || 
                !empty($_GET['Gender']) || !empty($_GET['Officers']) || 
                !empty($_GET['Alumni'])
            ) {
                if (!empty($_GET['LastName'])) {
                    echo "<h3>Last Name Starts with {$lastName}</h3>";
                } elseif (!empty($_GET['GradeLevel'])) {
                    echo "<h3>{$gradeLabel} Members</h3>";
                } elseif (!empty($_GET['Gender'])) {
                    echo "<h3>{$genderCode} Members</h3>";
                } elseif (!empty($_GET['Officers'])) {
                    echo "<h3>Chapter Officers</h3>";
                } elseif (!empty($_GET['Alumni'])) {
                    echo "<h3>Alumni Members</h3>";
                }
            
                if ($members_result->num_rows) {
                    echo '<form id="addForm" action="../quickselect/add_selected.php" method="post">';
                        echo '<div style="margin-bottom: 1rem;">';
                            echo '<button type="submit">Make Quick Select</button>';
                        echo '</div>';
                        echo '<table class="members-table">';
                            echo '<thead>';
                                echo '<th align="center"><input type="checkbox" id="selectAll"></th>';
                                echo '<th align="left">Member Name</th>';
                                echo '<th align="left">Grade Level</th>';
                                echo '<th align="left">Membership</th>';
                                echo '<th align="left">Email Address</th>';
                                if (!empty($_GET['Officers'])) {
                                    echo '<th align="left">Position</th>';
                                }
                            echo '</thead>';
                            echo '<tbody>';
                                while ($row = $members_result->fetch_assoc()) {
                                    $memberPhoto = !empty($row['MemberPhoto']) 
                                        ? "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>" 
                                        : "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                        
                                    echo "<tr>";
                                        echo "<td align='center'>
                                                <input type='checkbox' class='member-checkbox' name='selected_ids[]' value='{$row['MemberId']}'>
                                            </td>";
                                        echo "<td>
                                                <a href='../members/lookup.php?id={$row['MemberId']}' class='member-name'>
                                                    {$memberPhoto} {$row['LastName']}, {$row['FirstName']} {$row['Suffix']}
                                                </a>
                                            </td>";
                                        echo "<td>{$row['GradeLevel']}</td>";
                                        echo "<td>{$row['MembershipTier']}</td>";
                                        echo "<td><a href='mailto:{$row['EmailAddress']}'>{$row['EmailAddress']}</a></td>";
                                        if (!empty($_GET['Officers'])) {
                                            echo "<td>{$row['Position']}</td>";
                                        }
                                    echo "</tr>";
                                }
                            echo '</tbody>';
                        echo '</table>';
                    echo '</form>';
                } else {
                    if (!empty($_GET['LastName'])) {
                        echo "<p>No members with last name starting with {$lastName}.</p>";
                    } elseif (!empty($_GET['GradeLevel'])) {
                        echo "<p>No members in the {$gradeLabel}.</p>";
                    } elseif (!empty($_GET['Gender'])) {
                        echo "<p>No members with the gender {$genderCode}.</p>";
                    } elseif (!empty($_GET['Officers'])) {
                        echo "<p>No chapter officers found.</p>";
                    } elseif (!empty($_GET['Probation'])) {
                        echo "<p>No members currently under probation.</p>";
                    } elseif (!empty($_GET['Alumni'])) {
                        echo "<p>No alumni members found.</p>";
                    }
                }
            } else {
                echo '<p>No browse selected.</p>';
            }
        ?>
    </div>
    <script>
        <?php if ($members_result->num_rows): ?>
        // Member Select
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
        <?php endif; ?>
    </script>
</body>
</html>