<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Transition Members", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['transition'])) {
        $update_query = "UPDATE members 
                        SET 
                            MemberStatus = CASE
                                WHEN NextMembership = 1 THEN 6
                                WHEN NextMembership = 2 THEN 5
                                WHEN NextMembership = 3 THEN 4
                            END,
                            NextMembership = NULL
                        WHERE MemberStatus IN (1, 2) AND NextMembership IN (1, 2, 3)";
    
        if ($conn->query($update_query)) {
            $_SESSION['successMessage'] = "<div class='message success'>Members successfully transitioned.</div>";
        } else {
            $_SESSION['errorMessage'] = "<div class='message error'>Failed to transition members.</div>";
        }
    
        header("Location: transition_members.php");
        exit();
    }

    $members_query = "SELECT LastName, FirstName, Suffix, EmailAddress, GradeLevel, MemberPhoto, NextMembership, MemberStatus 
                      FROM members ORDER BY LastName ASC, FirstName ASC";
    $members_result = $conn->query($members_query);

    $active = $pre_registered = $alumni = [];

    while ($row = $members_result->fetch_assoc()) {
        switch ($row['MemberStatus']) {
            case 1:
            case 2:
                $active[] = $row;
                break;
            case 6:
                $pre_registered[] = $row;
                break;
            case 5:
                $alumni[] = $row;
                break;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Transition Members</title>
    <?php include("../common/head.php"); ?>
    <script>
        $( function() {
            var icons = {
                header: "iconClosed",
                activeHeader: "iconOpen"
            };
            $( "#accordion" ).accordion({
                icons: icons,
                collapsible: true,
                heightStyle: "content"
            });
        } );
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
                <a href="../manage/index.php">Manage Membership</a>
            </li>
            <li>
                <span>Transition Members</span>
            </li>
        </ul>
        <h2>Transition Members</h2>
        <div class="message comment">Please find below the current membership status of your members. Utilize the tools provided to report and update membership statuses. This transition is intended for use exclusively during the start and end of the year process.</div>
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
        <form method="post">
            <div id="accordion" style="margin-bottom: 1rem;">
                <h3>Active Members</h3>
                <div>
                    <?php if (!empty($active)): ?>
                        <button type="submit" name="transition" style="display: flex; align-items: center; margin-bottom: 1rem;">Transition Members&nbsp;<i class="fa-solid fa-caret-right"></i></button>
                        <table class="members-table">
                            <thead>
                                <tr>
                                    <th align="left">Member Name</th>
                                    <th>Grade Level</th>
                                    <th align="left">Email Address</th>
                                    <th align="left">Next Membership</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active as $member): ?>
                                    <tr>
                                        <td>
                                            <div class="member-name">
                                                <?php if (!empty($member['MemberPhoto'])): ?>
                                                    <img src="../../MemberPhotos/<?= htmlspecialchars($member['MemberPhoto']) ?>" alt="Member photo" class="member-photo">
                                                <?php else: ?>
                                                    <img src="../images/noprofilepic.jpeg" alt="Member photo" class="member-photo">
                                                <?php endif; ?>
                                                <?= htmlspecialchars($member['LastName'] . ", " . $member['FirstName'] . " " . $member['Suffix']) ?>
                                            </div>
                                        </td>
                                        <td align="center"><?= htmlspecialchars($member['GradeLevel']) ?></td>
                                        <td><a href="mailto:<?= htmlspecialchars($member['EmailAddress']) ?>"><?= htmlspecialchars($member['EmailAddress']) ?></a></td>
                                        <td>
                                            <?php if ($member['NextMembership'] == 1): ?>
                                                Pre-Register
                                            <?php elseif ($member['NextMembership'] == 2): ?>
                                                Graduate
                                            <?php elseif ($member['NextMembership'] == 3): ?>
                                                Drop Immediately
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="message comment" style="margin-bottom: 0;">No current active members.</div>
                    <?php endif; ?>
                </div>
                <h3>Pre-Registered Members</h3>
                <div>
                    <?php if (!empty($pre_registered)): ?>
                        <table class="members-table">
                            <thead>
                                <tr>
                                    <th align="left">Member Name</th>
                                    <th>Grade Level</th>
                                    <th align="left">Email Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pre_registered as $member): ?>
                                    <tr>
                                        <td>
                                            <div class="member-name">
                                                <?php if (!empty($member['MemberPhoto'])): ?>
                                                    <img src="../../MemberPhotos/<?= htmlspecialchars($member['MemberPhoto']) ?>" alt="Member photo" class="member-photo">
                                                <?php else: ?>
                                                    <img src="../images/noprofilepic.jpeg" alt="Member photo" class="member-photo">
                                                <?php endif; ?>
                                                <?= htmlspecialchars($member['LastName'] . ", " . $member['FirstName'] . " " . $member['Suffix']) ?>
                                            </div>
                                        </td>
                                        <td align="center"><?= htmlspecialchars($member['GradeLevel']) ?></td>
                                        <td><a href="mailto:<?= htmlspecialchars($member['EmailAddress']) ?>"><?= htmlspecialchars($member['EmailAddress']) ?></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="message comment" style="margin-bottom: 0;">No current pre-registered members.</div>
                    <?php endif; ?>
                </div>
                <h3>Alumni Members</h3>
                <div>
                    <?php if (!empty($alumni)): ?>
                        <div class="member-columns">
                            <?php foreach ($alumni as $member): ?>
                                <div class="member-box">
                                    <?php if (!empty($member['MemberPhoto'])): ?>
                                        <img src="../../MemberPhotos/<?= htmlspecialchars($member['MemberPhoto']) ?>" alt="Member photo" class="member-photo-small">
                                    <?php else: ?>
                                        <img src="../images/noprofilepic.jpeg" alt="Member photo" class="member-photo-small">
                                    <?php endif; ?>
                                    <div class="member-name-only">
                                        <?= htmlspecialchars($member['FirstName'] . " " . $member['LastName'] . " " . $member['Suffix']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="message comment" style="margin-bottom: 0;">No alumni status members.</div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</body>
</html>