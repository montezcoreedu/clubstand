<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Attendance", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }
    
    if (!isset($_GET['date'])) {
        $_SESSION['errorMessage'] = "<div class='message error'>Meeting date is required.</div>";
        header("Location: index.php");
        exit();
    }

    $MeetingDate = date('Y-m-d', strtotime($_GET['date']));

    $sql = "SELECT 
                m.MemberId, m.FirstName, m.LastName, m.Suffix, m.EmailAddress, m.MemberPhoto,
                a.Status 
            FROM attendance a
            INNER JOIN members m ON a.MemberId = m.MemberId AND a.MeetingDate = ?
            ORDER BY m.LastName ASC, m.FirstName ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $MeetingDate);
    $stmt->execute();
    $result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Attendance</title>
    <?php include("../common/head.php"); ?>
    <script>
        function showSelect(inputField, memberId) {
            const currentValue = inputField.value.trim();
            const select = document.createElement("select");

            select.innerHTML = `
                <option value="">Present</option>
                <option value="Absent" ${currentValue === "Absent" ? "selected" : ""}>Absent</option>
                <option value="Tardy" ${currentValue === "Tardy" ? "selected" : ""}>Tardy</option>
                <option value="Excused" ${currentValue === "Excused" ? "selected" : ""}>Excused</option>
            `;

            select.onchange = function() {
                inputField.value = this.value;
                inputField.style.background = this.value ? "#f8d7da" : "white";
                select.replaceWith(inputField);
            };

            inputField.replaceWith(select);
            select.focus();
            select.onblur = function() {
                inputField.value = select.value;
                inputField.style.background = select.value ? "#f8d7da" : "white";
                select.replaceWith(inputField);
            };
        }
    </script>
    <style>
        .attendance {
            width: 120px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="index.php">Attendance</a>
            </li>
            <li>
                <span>Edit Attendance for <?php echo date('F j, Y', strtotime($MeetingDate)); ?></span>
            </li>
        </ul>
        <h2>Edit Attendance for <?php echo date('F j, Y', strtotime($MeetingDate)); ?></h2>
        <form method="POST" action="submit_attendance.php">
            <input type="hidden" name="MeetingDate" value="<?php echo $MeetingDate; ?>">
            <table class="general-table">
                <thead>
                    <tr>
                        <th align="left" width="280">Member Name</th>
                        <th align="left" width="320">Email Address</th>
                        <th align="left">Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        while ($row = $result->fetch_assoc()) {
                            $attendanceStatus = ($row['Status'] === "Present") ? "" : $row['Status'];
                            $memberPhoto = !empty($row['MemberPhoto']) ? "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>" : "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";

                            echo "<tr>
                                <td><span class='member-name'>{$memberPhoto} {$row['LastName']}, {$row['FirstName']} {$row['Suffix']}</span></td>
                                <td><a href='mailto:{$row['EmailAddress']}'>{$row['EmailAddress']}</a></td>
                                <td>
                                    <input type='hidden' name='member_id[]' value='{$row['MemberId']}'>
                                    <input type='text' class='attendance' readonly 
                                        onclick='showSelect(this, {$row['MemberId']})'
                                        name='attendance_status[]' 
                                        value='{$attendanceStatus}'
                                    >
                                </td>
                            </tr>";
                        }
                    ?>
                </tbody>
            </table>
            <div style="margin: 1rem 0;">
                <button type="submit">Save changes</button>
            </div>
        </form>
    </div>
</body>
</html>