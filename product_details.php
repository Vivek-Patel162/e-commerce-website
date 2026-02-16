<?php
session_start();
include "databaseconn.php";
if ((!isset($_COOKIE['userid']))&&(!isset($_SESSION['userid']))) {
    header("Location: login.php");
    exit;
}


if (!isset($_GET['id'])) {
    echo "Product not found";
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM products WHERE product_id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "Product not found";
    exit;
}

$product = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $product['product_name'] ?></title>
</head>
<body>

<h2><?= $product['product_name'] ?></h2>
<img src="<?= $product['image'] ?>" width="300">
<p>Price: ₹<?= $product['price'] ?></p>
<p>Description: <?= $product['description'] ?? 'No description' ?></p>

<a href="cart.php">Go to Cart</a>

</body>
</html>
