<?php
require_once dirname(__DIR__)."/controller/update.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
<?php
function DataForUpdation($result){
       while ($row = $result->fetch_assoc()) {
                 
           
?>

 <h1>Update Detail</h1>
    <form method="POST">
        Name:
        <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>">
        <span style="color:red"><?= $errors['name'] ?? '' ?></span><br><br>

        Phone:
        <input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>">
        <span style="color:red"><?= $errors['phone'] ?? '' ?></span><br><br>

        Email:
        <input type="text" name="email" value="<?= htmlspecialchars($row['email']) ?>">
        <span style="color:red"><?= $errors['email'] ?? '' ?></span><br><br>

         <form method="POST" style="display:inline;">
                            <input type="hidden" name="eid" value="<?=  $row['id'] ?>">
                            <button type="update" name="update">Update</button>
                        </form>

<?php }
}
?>
</body>
</html>