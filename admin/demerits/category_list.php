<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/permissions.php");

    if (!in_array("Demerits", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['id']) ) {
        $categoryId = isset($_GET['id']) ? $_GET['id'] : 0;

        if (!empty($categoryId)) {
            $stmt = $conn->prepare("SELECT * FROM demerit_categories WHERE CategoryId = ?");
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $categoryResult = $stmt->get_result();
            $categoryData = $categoryResult->fetch_assoc();
            $stmt->close();

            $descriptions = [];
            if ($categoryId > 0) {
                $stmt = $conn->prepare("SELECT DescriptionId, Description FROM demerit_descriptions WHERE CategoryId = ? ORDER BY Description asc");
                $stmt->bind_param("i", $categoryId);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $descriptions[] = $row;
                }
                $stmt->close();
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $categoryData['CategoryName']; ?> - Category List</title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="index.php#settings">Demerits</a>
            </li>
            <li>
                <span><?php echo $categoryData['CategoryName']; ?></span>
            </li>
        </ul>
        <h2><?php echo $categoryData['CategoryName']; ?></h2>
        <table id="descTable">
            <colgroup>
                <col style="width: 5%;">
                <col style="width: 85%;">
                <col style="width: 10%;">
            </colgroup>
            <tbody>
                <?php foreach ($descriptions as $i => $desc): ?>
                <tr data-id="<?php echo $desc['DescriptionId']; ?>">
                    <td align="center"><b><?php echo $i + 1; ?>.</b></td>
                    <td>
                        <input type="text" value="<?php echo htmlspecialchars($desc['Description']); ?>" style="width: 30%;" onblur="saveDescription(this)">
                    </td>
                    <td align="center">
                        <a href="javascript:void(0)" onclick="deleteDescription(this)">
                            <i class="fa-solid fa-trash" style="color: rgb(144, 26, 7);"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3">
                        <a href="javascript:void(0)" onclick="addDescription(<?= $categoryId ?>)">
                            <i class="fa-solid fa-plus"></i> Add
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <script>
        function addDescription(categoryId) {
            fetch("save_description.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `CategoryId=${categoryId}&Description=`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const table = document.querySelector("#descTable tbody");
                    const rowCount = table.querySelectorAll("tr").length - 1;
                    const newRow = document.createElement("tr");
                    newRow.dataset.id = data.DescriptionId;
                    newRow.innerHTML = `
                        <td align="center"><b>${rowCount + 1}.</b></td>
                        <td><input type="text" value="" style="width: 30%;" onblur="saveDescription(this)"></td>
                    `;
                    table.insertBefore(newRow, table.lastElementChild);
                }
            });
        }

        function saveDescription(input) {
            const row = input.closest("tr");
            const descId = row.dataset.id;
            const description = input.value;

            fetch("save_description.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `DescriptionId=${descId}&Description=${encodeURIComponent(description)}`
            })
        }

        function deleteDescription(el) {
            const row = el.closest("tr");
            const descId = row.dataset.id;

            if (!confirm("Delete this description?")) return;

            fetch("save_description.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `delete=1&DescriptionId=${descId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    row.remove();
                    renumberRows();
                    alert("Demerit description successfully removed.");
                } else {
                    alert("Failed to delete. Please try again.");
                }
            });
        }

        function renumberRows() {
            const rows = document.querySelectorAll("#descTable tbody tr[data-id]");
            rows.forEach((row, i) => {
                row.querySelector("td:first-child b").textContent = (i + 1) + ".";
            });
        }
    </script>
</body>
</html>