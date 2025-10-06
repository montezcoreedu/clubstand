<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Member Field Value</title>
    <?php include("../common/head.php"); ?>
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
                <span>Member Field Value</span>
            </li>
        </ul>
        <h2>Member Field Value</h2>
        <form method="post" id="massFieldChangeForm">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td width="220"><b>Field Name (Database):</b></td>
                        <td><input type="text" name="FieldName" id="FieldName" required style="width: 100%; max-width: 280px;" oninput="previewChange()"></td>
                    </tr>
                    <tr>
                        <td width="220"><b>New Field Value:</b></td>
                        <td><input type="text" name="NewFieldValue" id="NewFieldValue" required style="width: 100%; max-width: 280px;" oninput="previewChange()"></td>
                    </tr>
                    <tr>
                        <td width="220"><b>Overwrite Existing Data?</b></td>
                        <td><input type="checkbox" name="OverwriteData" id="OverwriteData" checked></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button type="submit">Submit</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <div id="previewMembers">
        </div>
    </div>
    <script>
        function previewChange() {
            const fieldName = document.getElementById("FieldName").value;
            const newValue = document.getElementById("NewFieldValue").value;
            
            if (fieldName && newValue) {
                fetch(`preview_members.php?FieldName=${fieldName}&NewFieldValue=${newValue}`)
                    .then(response => response.json())
                    .then(data => {
                        let previewHtml = "<h3>Preview of Affected Members</h3>";
                        if (data.members && data.members.length > 0) {
                            previewHtml += "<table class='general-table'><thead><th align='left'>Member Name</th><th align='left'>Current Value</th><th align='left'>New Value</th></thead>";
                            data.members.forEach(member => {
                                previewHtml += `<tr>
                                    <td>${member.LastName}, ${member.FirstName} ${member.Suffix}</td>
                                    <td>${member.CurrentValue}</td>
                                    <td>${newValue}</td>
                                </tr>`;
                            });
                            previewHtml += "</table>";
                        } else {
                            previewHtml += "<p>No members found for this field.</p>";
                        }
                        document.getElementById("previewMembers").innerHTML = previewHtml;
                    });
            } else {
                document.getElementById("previewMembers").innerHTML = "";
            }
        }

        document.getElementById("massFieldChangeForm").addEventListener("submit", function(event) {
            event.preventDefault();
            
            const fieldName = document.getElementById("FieldName").value;
            const newValue = document.getElementById("NewFieldValue").value;
            const overwriteData = document.getElementById("OverwriteData").checked ? 1 : 0;

            fetch("process_mass_field_change.php", {
                method: "POST",
                body: new URLSearchParams({
                    FieldName: fieldName,
                    NewFieldValue: newValue,
                    OverwriteData: overwriteData
                }),
            })
            .then(response => response.text())
            .then(data => alert(data));
        });
    </script>
</body>
</html>