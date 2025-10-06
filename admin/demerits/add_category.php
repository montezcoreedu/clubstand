<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/permissions.php");

    if (!in_array("Demerits", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $categoryName = trim($_POST['CategoryName']);

        $stmt = $conn->prepare("INSERT INTO demerit_categories (CategoryName, CreatedAt) VALUES (?, NOW())");
        $stmt->bind_param("s", $categoryName);
    
        if ($stmt->execute()) {
            $_SESSION['successMessage'] = "<div class='message success'>Demerit category successfully created!</div>";
            header("Location: settings.php");
            exit();
        } else {
            $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
            header("Location: settings.php");
            exit();
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Demerit Category</title>
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
                <span>Add Demerit Category</span>
            </li>
        </ul>
        <h2>Add Demerit Category</h2>
        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td width="180"><b>Category Name:</b></td>
                        <td><input type="text" name="CategoryName" maxlength="100" required></td>
                    </tr>
                    <tr>
                        <td colspan="2"><button type="submit">Submit</button></td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</body>
</html>