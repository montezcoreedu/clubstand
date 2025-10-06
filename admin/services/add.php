<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Community Services", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    // Members Select DB
    $members_sql = "SELECT MemberId, FirstName, LastName, Suffix FROM members WHERE MemberStatus IN (1, 2) ORDER BY LastName asc, FirstName asc";
    $members_query = $conn->query($members_sql);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $serviceDate = date('Y-m-d', strtotime($_POST['ServiceDate']));
        $serviceName = $_POST['ServiceName'];
        $serviceType = $_POST['ServiceType'];
        $members = $_POST['Members'];
        $sameHours = isset($_POST['SameServiceHours']) ? $_POST['SameServiceHours'] : null;
        $individualHours = isset($_POST['ServiceHours']) ? $_POST['ServiceHours'] : null;
    
        $stmt = $conn->prepare("INSERT INTO communityservices (ServiceName, ServiceDate, ServiceType) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $serviceName, $serviceDate, $serviceType);
        
        if ($stmt->execute()) {
            $serviceId = $stmt->insert_id;

            $stmt = $conn->prepare("INSERT INTO memberservicehours (MemberId, ServiceId, ServiceHours) VALUES (?, ?, ?)");

            foreach ($members as $memberId) {
                $hours = $sameHours ? $sameHours : $individualHours[$memberId];
                $stmt->bind_param("iid", $memberId, $serviceId, $hours);
                $stmt->execute();
            }

            $_SESSION['successMessage'] = "<div class='message success'>Community service hours successfully recorded!</div>";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
            header("Location: index.php");
            exit();
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Community Service</title>
    <?php include("../common/head.php"); ?>
    <style>
        #selectMembers {
            width: 100%;
        }
    </style>
    <script>
        $( function() {
            $( "#ServiceDate" ).datepicker({
                dateFormat: 'm/d/yy'
            });
            $('#ServiceDate').datepicker('setDate', 'today');
        } );
    </script>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="index.php">Community Services</a>
            </li>
            <li>
                <span>Add Community Service</span>
            </li>
        </ul>
        <h2>Add Community Service</h2>
        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td width="240" valign="top"><b>Assign to Member:</b></td>
                        <td>
                            <select name="Members[]" id="selectMembers" multiple required onchange="updateMemberHours()">
                                <?php
                                    while ($member = mysqli_fetch_assoc($members_query)) {
                                        echo '<option value="'.$member['MemberId'].'">';
                                        echo $member['LastName'] . ', ' . $member['FirstName'];
                                        if ($member['Suffix']) {
                                            echo ' ' . $member['Suffix'];
                                        }
                                        echo '</option>';
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="240"><b>Date:</b></td>
                        <td><input type="text" id="ServiceDate" name="ServiceDate" required></td>
                    </tr>
                    <tr>
                        <td width="240"><b>Service Name:</b></td>
                        <td><input type="text" name="ServiceName" maxlength="100" required></td>
                    </tr>
                    <tr>
                        <td width="240"><b>Service Type:</b></td>
                        <td>
                            <select name="ServiceType" required>
                                <option value=""></option>
                                <option value="Community Outreach">Community Outreach</option>
                                <option value="Donation">Donation</option>
                                <option value="Environmental Work">Environmental Work</option>
                                <option value="Fundraising">Fundraising</option>
                                <option value="Sports & Recreation">Sports & Recreation</option>
                                <option value="Volunteer Work">Volunteer Work</option>
                                <option value="Other">Other</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="240"><b>Same Hours for Everyone?</b></td>
                        <td>
                            <input type="checkbox" id="sameHoursCheckbox" onchange="toggleHourInput()">
                        </td>
                    </tr>
                    <tr id="sameHoursRow" style="display: none;">
                        <td width="240"><b>Credit Hours (Everyone):</b></td>
                        <td><input type="number" name="SameServiceHours" min="0.5" step="0.5"></td>
                    </tr>
                    <tr id="individualHoursRow">
                        <td width="240" valign="top"><b>Member-Specific Hours:</b></td>
                        <td>
                            <table id="memberHoursContainer"></table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><button type="submit">Submit</button></td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $("#selectMembers").select2({});

        function updateMemberHours() {
            var select = document.getElementById("selectMembers");
            var container = document.getElementById("memberHoursContainer");
            container.innerHTML = "";

            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].selected) {
                    var memberId = select.options[i].value;
                    var memberName = select.options[i].text;

                    var tr = document.createElement("tr");
                    tr.innerHTML = `
                                    <td width="180">${memberName}</td>
                                    <td><input type="number" name="ServiceHours[${memberId}]" min="0.5" step="0.5" required>&nbsp;&nbsp;&nbsp;&nbsp;hours</td>
                                    `;
                    container.appendChild(tr);
                }
            }
        }

        function toggleHourInput() {
            var sameHoursCheckbox = document.getElementById("sameHoursCheckbox");
            var sameHoursRow = document.getElementById("sameHoursRow");
            var individualHoursRow = document.getElementById("individualHoursRow");

            if (sameHoursCheckbox.checked) {
                sameHoursRow.style.display = "";
                individualHoursRow.style.display = "none";
                document.getElementById("memberHoursContainer").innerHTML = "";
            } else {
                sameHoursRow.style.display = "none";
                individualHoursRow.style.display = "";
                updateMemberHours();
            }
        }
    </script>
</body>
</html>