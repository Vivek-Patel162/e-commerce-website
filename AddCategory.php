<?php
session_start();

if (!isset($_SESSION['email'])) {
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
    <link rel="stylesheet" href="AddCategory.css">

</head>

<body>
    <?php
    require "databaseconn.php";
    $editData = null;
    if (isset($_POST['edit'])) {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id=?");
        $stmt->bind_param("i", $_POST['edit_id']);
        $stmt->execute();
        $editData = $stmt->get_result()->fetch_assoc();
    } ?>
    <?php
    if ($editData) {
        $sql = "select category_id,category_name ,parent_id from categories where category_id!=" . $editData['category_id'];
    } else {
        $sql = "select category_id,category_name,parent_id from categories";
    }
    $result = $conn->query($sql);
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }


    function showCategoryMenu($categories, $parent_id = NULL)
    {
        echo "<ul class='cat-menu'>";

        foreach ($categories as $cat) {
            if ($cat['parent_id'] == $parent_id) {

                echo "<li>";
                echo "<span class='cat-item' data-id='{$cat['category_id']}'>
         {$cat['category_name']}
         </span>";

                // check if this category has children
                foreach ($categories as $child) {
                    if ($child['parent_id'] == $cat['category_id']) {
                        showCategoryMenu($categories, $cat['category_id']);
                        break;
                    }
                }

                echo "</li>";
            }
        }

        echo "</ul>";
    }

    ?>

    <form method="POST" action="category.php">
        <h1><?= isset($editData) ? 'Edit Category' : 'Add Categeory' ?></h1>

        <input type="hidden" name="id"
            value="<?= $editData['category_id'] ?? '' ?>">

        
          <input type="hidden" name="parent_id" id="parent_id" value="">
        Category Name:
        <input type="text" name="category"
            value="<?= htmlspecialchars($editData['category_name'] ?? '') ?>">

        Parent Category:

        <div class="category-dropdown">
            <span class="dropdown-title">Select Parent Category ▾</span>

            <div class="dropdown-content">
                <?php showCategoryMenu($categories); ?>
            </div>
        </div>

        <?php if (isset($editData)) { ?>
            <button name="update_record">Update</button>
        <?php } else { ?>
            <button name="add">Add</button>
        <?php } ?>
    </form>

</body>
<script src="AddCategory.js"></script>

</html>