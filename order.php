<?php
session_start();
require_once  "databaseconn.php";


if((!isset($_COOKIE['userid']))&&(!isset($_SESSION['userid'])))
{
   
header("Location: ".$_SERVER['HTTP_REFERER']);
exit();
}
$userid=0;
if(!isset($_COOKIE['userid']))
{
 $userid=$_SESSION['userid'];
}else{
    $userid=$_COOKIE['userid'];
}
$sql = "
SELECT 
    o.order_id,o.order_date,
    p.product_name,
    p.image,
    oi.quantity,
    oi.price,
    oi.subtotal
FROM orders o
JOIN order_items oi ON o.order_id = oi.order_id
JOIN products p ON oi.product_id = p.product_id
WHERE o.user_id = $userid
";


$result = $conn->query($sql);

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
        <h2>orders</h2>

    <table cellpadding="10">
        <tr>
            <th>image</th>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Total</th>
            <th>Date</th>

        </tr>

        <?php
        while ($row = $result->fetch_assoc()) {
                $qty = $row['quantity']; 
           
    ?>
            <tr>
                <td>
                    <img src="/PracticePhp/4Feb/images/<?= $row['image'] ?>" alt="image" width="80">
                <td><?= $row['product_name'] ?></td>
                <td>₹<?= $row['price'] ?></td>
                <td>
                    <?= $qty ?>
                </td>

                <td>
                   <?= $row['subtotal'] ?>
                </td>
                 <td>
                   <?= $row['order_date'] ?>
                </td>
                
            </tr>

        <?php } ?>

    </table>
     <a href="dashboard.php">← Continue Shopping</a>
</body>
</html>