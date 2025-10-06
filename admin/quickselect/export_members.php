<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Export Members</title>
    <?php include("../common/head.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const tbody = document.querySelector("#fieldsBody");

            Sortable.create(tbody, {
                animation: 150,
                onEnd: () => {
                    renumberRows();
                }
            });
        });

        function addRow() {
            const tbody = document.getElementById("fieldsBody");
            const rowCount = tbody.rows.length;

            const newRow = document.createElement("tr");
            newRow.innerHTML = `
                <td class="row-number" align="center">${rowCount + 1}.</td>
                <td><input type="text" name="ColumnName[]" required style="width: 100%; max-width: 280px;"></td>
                <td><input type="text" name="FieldName[]" style="width: 100%; max-width: 280px;"></td>
                <td align="center"><button type="button" onclick="removeRow(this)"><i class="fa-solid fa-minus"></i></button></td>
            `;
            tbody.appendChild(newRow);
            renumberRows();
        }

        function removeRow(button) {
            const row = button.closest("tr");
            row.remove();
            renumberRows();
        }

        function renumberRows() {
            const numbers = document.querySelectorAll(".row-number");
            numbers.forEach((cell, index) => {
                cell.textContent = `${index + 1}.`;
            });
        }
    </script>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="../home/">Member Search</a>
            </li>
            <li>
                <a href="../quickselect/">Quick Select</a>
            </li>
            <li>
                <span>Export Members</span>
            </li>
        </ul>
        <h2>Export Members</h2>
        <form action="export_preview.php" method="get">
            <table id="fieldsTable" class="form-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th align="left">Column Name (Label)</th>
                        <th align="left">Field Name (Database)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="fieldsBody">
                    <tr>
                        <td class="row-number" align="center">1.</td>
                        <td><input type="text" name="ColumnName[]" required style="width: 100%; max-width: 280px;"></td>
                        <td><input type="text" name="FieldName[]" style="width: 100%; max-width: 280px;"></td>
                        <td></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">
                            <button type="button" onclick="addRow()">Add Column</button>
                            <button type="submit">Preview Export</button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </form>
    </div>
</body>
</html>