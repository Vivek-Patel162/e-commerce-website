<?php
session_start();
if ((!isset($_COOKIE['email']))&&(!isset($_SESSION['email']))) {
    header("Location: login.php");
    exit;
}

include "databaseconn.php";

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="category.css">
</head>

<body>
    <div>
        <h2>Categories</h1>
            <a class="logoutAdd" href="AddCategory.php">+ Add Category</a>
            <?php
            if (isset($_POST['update_record'])) {
                if (empty($_POST['category'])) {
                    $success = "Fields cannot be empty";
                    header('Location:AddCategory.php');

                    $conn->close();
                    exit;
                }
                $stm = $conn->prepare(
                    "UPDATE categories
                     SET categeory_name=?,parent_id=?
                     WHERE category_id=?"
                );

                $stm->bind_param(
                    "sii",
                    $_POST['category'],
                    $_POST['parent_id'],
                    $_POST['id']
                );

                $stm->execute();
                $editData = null;
                header("Location: Category.php?updated=1");
                exit;
            }



            if (isset($_POST['delete'])) {
                $stmt = $conn->prepare("DELETE FROM categories WHERE category_id=?");
                $stmt->bind_param("i", $_POST['edit_id']);
                $stmt->execute();
                header("Location: Categeory.php");
                exit;
            }
            ?>


            <?php
            if (isset($_POST['add'])) {
                $category = trim($_POST['category']) ?? '';
                $parentid = trim($_POST['parent_id']) ?? '';

                $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name= ?");
                $stmt->bind_param("s", $category);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    //             $stmt = $conn->prepare("
                    //  UPDATE categeory
                    //  SET product_id = CONCAT(product_id, ?, '')
                    //  WHERE categeory = ?
                    //  ");

                    //             $concat_value = "," . $productp;
                    //             $stmt->bind_param("ss", $concat_value, $name);

                    //             $stmt->execute();
                    echo "Category Already exists ";
                    // header("Location:AddCategory.php");
                    // exit;
                } else {
                    if (empty($parentid)) {
                        $sq2 = "INSERT INTO categories(category_name) VALUES ('$category')";
                    } else {
                        $sq2 = "INSERT INTO categories(category_name,parent_id) VALUES ('$category',$parentid)";
                    }

                    if ($conn->query($sq2) == TRUE) {
                        echo "Added categeory";
                    } else {
                        echo "there is an error";
                    }
                }
            }


            ?>
            <?php
            $sql = "
          SELECT * from categories";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {

                echo "<table>
<tr>
    <th>ID</th>
    <th>Categories</th>
    <th>Operation</th>
</tr>";



                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
            <td>{$row['category_id']}</td>
            <td><b>{$row['category_name']}</b></td>
            <td>
                <form method='post' action='AddCategory.php' style='display:inline'>
                    <input type='hidden' name='edit_id' value='{$row['category_id']}'>
                    <button name='edit'>Edit</button>
                </form>

                <form method='post' style='display:inline'>
                    <input type='hidden' name='edit_id' value='{$row['category_id']}'>
                    <button name='delete'>Delete</button>
                </form>
            </td>
        </tr>";
                }
                echo "</table>";
            }
            echo "<a href='dashboard.php'>← Back to Dashboard</a>";
            ?>
            <a class="logoutAdd" href="logout.php">logout</a>
    </div>
</body>

</html>