<?php
require_once dirname(__DIR__) . "/controller/update.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="orders.css">

</head>
<body>
        <h2>Details</h2>
    <?php 
    function view($result){
    ?>

    <table >
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Action</th>
        </tr>

        <?php
        while ($row = $result->fetch_assoc()) {
                 
           
    ?>
            <tr>
                  <td><?= $row['name'] ?></td>
                <td><?= $row['email'] ?></td>
                <td>
                   <?= $row['phone'] ?>
                </td>
                   <td>

                        <form method="POST" action="../controller/update.php" style="display:inline;">
                            <input type="hidden" name="eid" value="<?=  $row['id'] ?>">
                            
                            <button type="update1" name="update1" >Update</button>
                        </form>

                        <form method="POST"    action="../controller/update.php" style="display:inline;">
                            <input type="hidden" name="eid" value="<?= $row['id'] ?>">
                           
                            <button type="remove" name="remove">Remove</button>
                        </form>

                    </td>
                
            </tr>

        <?php } ?>

    </table>
    <?php
    }?>
    
</body>
</html>