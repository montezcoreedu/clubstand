<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Assign Officers", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT PositionId, PositionName, Sort FROM officer_positions ORDER BY Sort asc");
    $stmt->execute();
    $result = $stmt->get_result();
    $positions = [];
    while ($row = $result->fetch_assoc()) {
        $positions[] = $row;
    }
    $stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Officer Positions</title>
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
                <span>Manage Officer Positions</span>
            </li>
        </ul>
        <h2>Manage Officer Positions</h2>
        <table id="positionTable">
            <colgroup>
                <col style="width: 5%;">
                <col style="width: 85%;">
                <col style="width: 10%;">
            </colgroup>
            <tbody>
                <?php foreach ($positions as $i => $position): ?>
                    <tr data-id="<?php echo $position['PositionId']; ?>">
                        <td align="center">
                            <i class="fa-solid fa-bars" style="cursor: grab;"></i>
                            &nbsp;
                            <b><?php echo $i + 1; ?>.</b>
                        </td>
                        <td>
                            <input type="text" value="<?php echo htmlspecialchars($position['PositionName']); ?>" style="width: 30%;" onblur="savePosition(this)">
                        </td>
                        <td align="center">
                            <a href="javascript:void(0)" onclick="deletePosition(this)">
                                <i class="fa-solid fa-trash" style="color: rgb(144, 26, 7);"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3">
                        <a href="javascript:void(0)" onclick="addPosition()">
                            <i class="fa-solid fa-plus"></i> Add
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <script>
        $(function() {
            $("#positionTable tbody").sortable({
                items: "tr:not(:last-child)",
                placeholder: "ui-state-highlight",
                update: function(event, ui) {
                let order = [];
                $("#positionTable tbody tr:not(:last-child)").each(function(index) {
                    const id = $(this).data("id");
                    if (id) {
                    order.push({ id: id, sort: index + 1 });
                    }
                    $(this).find("td:first b").text((index + 1) + ".");
                });

                $.ajax({
                    url: "save_officers.php",
                    method: "POST",
                    data: { reorder: 1, order: JSON.stringify(order) },
                    success: function(res) {
                    try {
                        const data = JSON.parse(res);
                        if (!data.success) {
                        alert("Reorder failed: " + (data.error || "Unknown error"));
                        }
                    } catch (e) {
                        console.error("Invalid JSON:", res);
                    }
                    }
                });
                }
            }).disableSelection();
        });

        function addPosition() {
            fetch("save_officers.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `PositionName= `
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const table = document.querySelector("#positionTable tbody");
                    const rowCount = table.querySelectorAll("tr").length - 1;
                    const newRow = document.createElement("tr");
                    newRow.dataset.id = data.PositionId;
                    newRow.innerHTML = `
                        <td align="center"><b>${rowCount + 1}.</b></td>
                        <td><input type="text" value="" style="width: 30%;" onblur="savePosition(this)"></td>
                    `;
                    table.insertBefore(newRow, table.lastElementChild);
                }
            });
        }

        function savePosition(input) {
            const row = input.closest("tr");
            const positionId = row.dataset.id;
            const name = input.value;

            fetch("save_officers.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `PositionId=${positionId}&PositionName=${encodeURIComponent(name)}`
            })
        }

        function deletePosition(el) {
            const row = el.closest("tr");
            const positionId = row.dataset.id;

            if (!confirm("Delete this officer position?")) return;

            fetch("save_officers.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `delete=1&PositionId=${positionId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    row.remove();
                    renumberRows();
                    alert("Officer position successfully removed.");
                } else {
                    alert("Failed to delete. Please try again.");
                }
            });
        }
    </script>
</body>
</html>