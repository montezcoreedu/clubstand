<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Assign Officers", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }
        
    $qs_query = "SELECT q.SelectId, m.MemberId, m.LastName, m.FirstName, m.Suffix, m.Position, m.EmailAddress, m.GradeLevel, m.MembershipTier, m.MemberPhoto 
        FROM quick_select q 
        INNER JOIN members m ON q.MemberId = m.MemberId 
        WHERE q.AdminId = $accountId 
        AND q.AddedOn >= NOW() - INTERVAL 1 DAY
        AND MemberStatus IN (1, 2)
        ORDER BY m.LastName asc, m.FirstName asc";
    $qs_result = $conn->query($qs_query);

    $positions = $conn->query("SELECT PositionName FROM officer_positions ORDER BY Sort asc");
    $roles = [];
    while ($row = $positions->fetch_assoc()) {
        $roles[] = $row['PositionName'];
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Assign Officer Positions</title>
    <?php include("../common/head.php"); ?>
    <style>
        form {
            display: flex;
            align-items: flex-start;
            flex-direction: row;
            justify-content: space-between;
        }

        #position-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 200px;
            margin-right: 2rem;
        }

        .position-box {
            display: inline-block;
            width: 180px;
            text-align: center;
            padding: 8px;
            margin-bottom: 10px;
            background: #f0f0f0;
            border: 2px dashed #ccc;
            cursor: move;
        }

        .droppable-area {
            min-height: 50px;
            padding: 10px;
            background: #f2fdf2;
            border: 2px dashed #5cb85c;
            border-radius: 6px;
            text-align: center;
        }

        .assigned {
            font-family: avenir-bold, sans-serif;
            background: #dff0d8;
        }
    </style>
    <script>
        $(function () {
            $(".position-box").draggable({
                helper: "clone"
            });

            $(".droppable-area").droppable({
                accept: ".position-box",
                drop: function (event, ui) {
                    const $draggable = ui.draggable;
                    const position = $draggable.text();
                    const $dropArea = $(this);

                    if ($dropArea.hasClass("assigned")) return;

                    if ($(`input[value="${position}"]`).length > 0) {
                        alert(`"${position}" has already been assigned!`);
                        return;
                    }

                    $dropArea
                        .html(position + `<input type="hidden" name="positions[${$dropArea.data("member-id")}]" value="${position}">`)
                        .addClass("assigned");

                    $draggable.remove();
                }
            });

            $("#reset-assignments").click(function () {
                $(".droppable-area").removeClass("assigned").html("");

                const roles = <?php echo json_encode($roles); ?>;
                const $positionContainer = $("#position-container");
                $positionContainer.html("");
                roles.forEach(role => {
                    const box = $(`<div class="position-box">${role}</div>`);
                    $positionContainer.append(box);
                });

                $(".position-box").draggable({
                    helper: "clone"
                });
            });
        });
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
                <span>Assign Officer Positions</span>
            </li>
        </ul>
        <h2>Assign Officer Positions</h2>
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
        <?php if ($qs_result->num_rows) : ?>
            <form method="post" action="save_positions.php">
                <div id="position-container">
                    <?php
                    $positions = $conn->query("SELECT PositionName FROM officer_positions ORDER BY Sort asc");
                    while ($row = $positions->fetch_assoc()) {
                        $pos = htmlspecialchars($row['PositionName']);
                        echo "<div class='position-box'>{$pos}</div>";
                    }
                    ?>
                </div>
                <div style="width: 100%;">
                    <table class="members-table">
                        <thead>
                            <tr>
                                <th align="left">Members Name</th>
                                <th align="left">Current Position</th>
                                <th align="left">New Position</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $qs_result->fetch_assoc()) : ?>
                                <?php
                                    $memberId = $row['MemberId'];
                                    $memberPhoto = !empty($row['MemberPhoto']) ?
                                        "<img src='../../MemberPhotos/{$row['MemberPhoto']}' class='member-photo'>" :
                                        "<img src='../images/noprofilepic.jpeg' class='member-photo'>";
                                    $currentPosition = !empty($row['Position']) ?
                                    "{$row['Position']}" : "General Member";
                                ?>
                                <tr>
                                    <td>
                                        <span class="member-name">
                                            <?= $memberPhoto ?> <?= $row['LastName'] ?>, <?= $row['FirstName'] ?> <?= $row['Suffix'] ?>
                                        </span>
                                    </td>
                                    <td><?= $currentPosition ?></td>
                                    <td class="droppable-area" data-member-id="<?= $memberId ?>"></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div style="margin: 1rem 0;">
                        <button type="submit" class="action-btn green">Save changes</button>
                        &nbsp;
                        <button type="button" id="reset-assignments">Reset Assignments</button>
                    </div>
                </div>
            </form>
        <?php else : ?>
            <div class="comment message">No members in quick select to perform action.</div>
        <?php endif; ?>
    </div>
</body>
</html>