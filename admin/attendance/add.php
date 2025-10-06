<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Attendance", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $query = "SELECT MemberId, LastName, FirstName, Suffix, EmailAddress, MemberPhoto FROM members WHERE MemberStatus IN (1, 2) ORDER BY LastName asc, FirstName asc";
    $result = $conn->query($query);

    $excuseQuery = "SELECT MemberId, MeetingDate, Reason FROM excuse_requests";
    $excuseResult = $conn->query($excuseQuery);
    $excuses = [];
    while ($excuse = $excuseResult->fetch_assoc()) {
        $memberId = $excuse['MemberId'];
        $date = date('m/d/Y', strtotime($excuse['MeetingDate']));
        $reason = $excuse['Reason'];
        $excuses[$date][$memberId] = $reason;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Attendance</title>
    <?php include("../common/head.php"); ?>
    <style>
        .attendance {
            width: 120px;
            cursor: pointer;
        }

        .excuse-icon {
            margin-left: 6px;
        }
    </style>
    <script>
        const excuses = <?= json_encode($excuses) ?>;

        $(function () {
            $("#MeetingDate").datepicker({
                dateFormat: 'mm/dd/yy',
                onSelect: applyExcuses
            });
            $('#MeetingDate').datepicker('setDate', 'today');
            setTimeout(applyExcuses, 200);

            function applyExcuses() {
                const rawDate = $("#MeetingDate").val();
                const dateObj = new Date(rawDate);
                const selectedDate = (dateObj.getMonth() + 1).toString().padStart(2, '0') + "/" +
                                    dateObj.getDate().toString().padStart(2, '0') + "/" +
                                    dateObj.getFullYear();

                $("tr").each(function () {
                    const memberId = $(this).find("input[name='member_id[]']").val();
                    const attendanceField = $(this).find("input[name='attendance_status[]']");
                    const excuseIcon = $(this).find(".excuse-icon");

                    if (!memberId) return;

                    if (excuses[selectedDate] && excuses[selectedDate][memberId]) {
                        attendanceField.val("Excused").css("background", "#d4edda");
                        const reason = excuses[selectedDate][memberId];
                        if (excuseIcon.length === 0) {
                            $(this).find("td:last-child").append(`<span class='excuse-icon' title="${reason}"><img src='../images/dot.gif' class='icon14 page-attach-icon' alt='Excused icon'></span>`);
                        } else {
                            excuseIcon.attr("title", reason);
                        }
                    } else {
                        if (attendanceField.val() === "Excused") {
                            attendanceField.val("").css("background", "white");
                        }
                        excuseIcon.remove();
                    }
                });
            }
        });

        function showSelect(inputField, memberId) {
            const currentValue = inputField.value.trim();
            const select = document.createElement("select");

            select.innerHTML = `
                <option value="">Present</option>
                <option value="Absent" ${currentValue === "Absent" ? "selected" : ""}>Absent</option>
                <option value="Tardy" ${currentValue === "Tardy" ? "selected" : ""}>Tardy</option>
                <option value="Excused" ${currentValue === "Excused" ? "selected" : ""}>Excused</option>
            `;

            select.onchange = function () {
                inputField.value = this.value;
                inputField.style.background = this.value ? "#f8d7da" : "white";
                select.replaceWith(inputField);
            };

            inputField.replaceWith(select);
            select.focus();

            select.onblur = function () {
                inputField.value = select.value;
                inputField.style.background = select.value ? "#f8d7da" : "white";
                select.replaceWith(inputField);
            };
        }
    </script>
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
                <span>Add Attendance</span>
            </li>
        </ul>
        <h2>Add Attendance</h2>
        <form method="POST" action="submit_attendance.php">
            <table class="form-table" style="margin-bottom: 1rem;">
                <tbody>
                    <tr>
                        <td width="280"><b>Meeting Date:</b></td>
                        <td><input type="text" id="MeetingDate" name="MeetingDate" required></td>
                    </tr>
                    <tr>
                        <td width="280"><b>Send Demerits & Notifications:</b></td>
                        <td><input type="checkbox" name="SendNotifications" checked></td>
                    </tr>
                </tbody>
            </table>
            <table class="general-table">
                <thead>
                    <tr>
                        <th align="left" width="300">Member Name</th>
                        <th align="left" width="380">Email Address</th>
                        <th align="left">Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        while ($row = $result->fetch_assoc()) {
                            $memberPhoto = !empty($row['MemberPhoto']) 
                                ? "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>" 
                                : "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";

                            echo "<tr>
                                <td><span class='member-name'>{$memberPhoto} {$row['LastName']}, {$row['FirstName']} {$row['Suffix']}</span></td>
                                <td><a href='mailto:{$row['EmailAddress']}'>{$row['EmailAddress']}</a></td>
                                <td>
                                    <input type='hidden' name='member_id[]' value='{$row['MemberId']}'>
                                    <input type='text' class='attendance' readonly 
                                        onclick='showSelect(this, {$row['MemberId']})'
                                        name='attendance_status[]'
                                    >
                                </td>
                            </tr>";
                        }
                    ?>
                </tbody>
            </table>
            <div style="margin: 1rem 0;">
                <button type="submit">Submit</button>
            </div>
        </form>
    </div>
</body>
</html>