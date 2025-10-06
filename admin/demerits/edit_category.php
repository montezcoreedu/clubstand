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

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $categoryName = trim($_POST['CategoryName']);

                $stmt = $conn->prepare("UPDATE demerit_categories SET CategoryName = ?, UpdatedAt = NOW() WHERE CategoryId = ?");
                $stmt->bind_param("si", $categoryName, $categoryId);
            
                if ($stmt->execute()) {
                    $_SESSION['successMessage'] = "<div class='message success'>Demerit category successfully updated!</div>";
                    header("Location: settings.php");
                    exit();
                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
                    header("Location: settings.php");
                    exit();
                }
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Demerit Category</title>
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
                <span>Edit Demerit Category</span>
            </li>
        </ul>
        <h2>Edit Demerit Category</h2>
        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td width="180"><b>Category Name:</b></td>
                        <td><input type="text" name="CategoryName" value="<?php echo $categoryData['CategoryName']; ?>" maxlength="100" required></td>
                    </tr>
                    <tr>
                        <td colspan="2"><button type="submit">Save changes</button></td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</body>
</html>