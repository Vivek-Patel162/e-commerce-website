<?php
session_start();
require_once "../4Feb/databaseconn.php";
include "isSessCoo.php";
$status=new Status();

$cart = [];

/* ===============================
   CHECK CART SOURCE
================================ */

//  LOGGED IN USER → DATABASE
if (isset($_SESSION['userid']) || isset($_COOKIE['userid'])) {

    $userid = $_SESSION['userid'] ?? $_COOKIE['userid'];

    $sqlCart = "
        SELECT ci.product_id, ci.quantity
        FROM cart c
        JOIN cart_items ci ON c.cart_id = ci.cart_id
        WHERE c.user_id = $userid
    ";

    $resultCart = $conn->query($sqlCart);

    if ($resultCart && $resultCart->num_rows > 0) {
        while ($row = $resultCart->fetch_assoc()) {
            $cart[$row['product_id']] = $row['quantity'];
        }
    }
}

//  GUEST SESSION
elseif (isset($_SESSION['cart'])) {
    $cart = $_SESSION['cart'];
}

// GUEST COOKIE
elseif (isset($_COOKIE['cart'])) {
    $cart = json_decode($_COOKIE['cart'], true);
}

//  EMPTY CART
if (empty($cart)) {
    echo "<h2>Your cart is empty</h2>";
    exit;
}

$product_ids = implode(",", array_keys($cart));



$sql = "SELECT * FROM products WHERE product_id IN ($product_ids)";
$result = $conn->query($sql);

if (isset($_COOKIE['email'])) {
    $email = $_COOKIE['email'];
} elseif (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
}

if (isset($email)) {

    $sql2 = "SELECT name,phone,user_id FROM users WHERE email='" . $email . "'";
    $result2 = $conn->query($sql2);

    if ($result2->num_rows > 0) {
        $row1 = $result2->fetch_assoc();
        $name = $row1['name'];
        $phone = $row1['phone'];
        $userid = $row1['user_id'];
    }
}


$errors = [];
$dataOrder = [];
$grandTotal = 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="checkout.css">
</head>

<body>
    <h1>Order Confirmation</h1>
    <form method="POST">
        Name:
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>">
        <span style="color:red">
            <?= $errors['name'] ?? '' ?>
        </span><br><br>
        Phone:
        <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>">
        <span style="color:red">
            <?= $errors['phone'] ?? '' ?>
        </span><br><br>



        Delivery Address:
        <input type="text" name="address">
        <span style="color:red">
            <?= $errors['address'] ?? '' ?>
        </span><br><br>

        Payment Method:
        <br>
        <select name="paymentMethod">
            <option value="Cash on Delivery">Cash on Delivery</option>
            <option value="Net Banking">Net Banking</option>
        </select>
        <br><br>


        <table cellpadding="10">
            <tr>
                <th>image</th>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>

            </tr>

            <?php


            while ($row = $result->fetch_assoc()) {
                $dataOrder[$row['product_id']][] = $row;
                $qty = $cart[$row['product_id']] ?? 0;

                $total = $row['price'] * $qty;

                $grandTotal += $total;
            ?>

                <tr>
                    <td>
                        <img src="/PracticePhp/4Feb/images/<?= $row['image'] ?>" alt="image" width="80">
                    <td><?= $row['product_name'] ?></td>
                    <td>₹<?= $row['price'] ?></td>

                    <td> <?= $qty ?></td>
                    <td>₹<?= $total ?></td>
                </tr>

            <?php } ?>

            <br><br>

            <tr>
                <td colspan="4"><strong>Grand Total</strong></td>
                <td><strong>₹<?= $grandTotal ?></strong></td>
            </tr>

        </table>
        <button type="submit" name="confirm">Confirm Order</button>
    </form>

    <?php
    if (isset($_POST['confirm'])) {

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $paymentmethod = trim($_POST['paymentMethod'] ?? '');



        if ($name === "") {
            $errors['name'] = "Name is required";
        } elseif (!preg_match("/^[a-zA-Z-' ]{3,}$/", $name)) {
            $errors['name'] = "Name should be at least 3 characters long and contain only letters";
        }

        if (empty($phone)) {
            $errors['phone'] = "Phone number is required";
        } elseif (!preg_match("/^[6-9]\d{9}$/", $phone)) {
            $errors['phone'] = "Phone number must be exactly 10 digits";
        }
        if ($address === "") {
            $errors['address'] = "Address is required";
        }

        $sql = "insert into orders(user_id,total_amount,payment_method)values($userid,$grandTotal,'$paymentmethod')";
        if ($conn->query($sql)) {

            $lastid = $conn->insert_id;


            $values = "";



             /* ===============================
        THIS BLOCK HERE
       DATABASE CART SUPPORT
    =============================== */

    if (isset($_SESSION['userid']) || isset($_COOKIE['userid'])) {

        $userid = $_SESSION['userid'] ?? $_COOKIE['userid'];

        $sqlCart = "
            SELECT ci.product_id, ci.quantity, p.price
            FROM cart c
            JOIN cart_items ci ON c.cart_id = ci.cart_id
            JOIN products p ON p.product_id = ci.product_id
            WHERE c.user_id = $userid
        ";

        $resultCart = $conn->query($sqlCart);

        while ($row = $resultCart->fetch_assoc()) {

            $pid = $row['product_id'];
            $qty = $row['quantity'];
            $price = $row['price'];
            $subtotal = $price * $qty;

            $values .= "($lastid,$pid,$qty,$price,$subtotal),";
        }

        $values = rtrim($values, ',');

        $sqlInsert = "INSERT INTO order_items (order_id, product_id, quantity, price, subtotal)
                      VALUES $values";

        if ($conn->query($sqlInsert)) {

           // Clear database cart
        $conn->query("DELETE ci FROM cart_items ci
                      JOIN cart c ON ci.cart_id = c.cart_id
                      WHERE c.user_id = $userid");

        header("Location: dashboard.php");
        exit;
        }
    }

    /* ===============================
       YOUR EXISTING COOKIE / SESSION
       CODE CONTINUES BELOW
    =============================== */



            if (isset($_COOKIE['cart'])) {

                $cart = json_decode($_COOKIE['cart'], true);
                $values = "";

                foreach ($cart as $pid => $qty) {
                    $price = $dataOrder[$pid][0]['price'];
                    $subtotal = $price * $qty;
                    $values .= "($lastid,$pid,$qty,$price,$subtotal),";
                }

                $values = rtrim($values, ',');

                $sql = "INSERT INTO order_items (order_id, product_id, quantity, price, subtotal)
            VALUES $values";

                if ($conn->query($sql)) {
                    setcookie("cart", "", time() - 3600, "/");
                    setcookie("cart_count", "", time() - 3600, "/");

                    header("Location: dashboard.php");
                    exit;
                }
            } elseif (isset($_SESSION['cart'])) {

                $values = "";

                foreach ($_SESSION['cart'] as $pid => $qty) {
                    $price = $dataOrder[$pid][0]['price'];
                    $subtotal = $price * $qty;
                    $values .= "($lastid,$pid,$qty,$price,$subtotal),";
                }

                $values = rtrim($values, ',');

                $sql = "INSERT INTO order_items (order_id, product_id, quantity, price, subtotal)
            VALUES $values";

                if ($conn->query($sql)) {
                   
                    unset($_SESSION['cart']);
                    unset($_SESSION['cart_count']);
                    header("Location: dashboard.php");
                    exit;
                }
            }
        }
        echo "something went wrong";
    }

    ?>
    <a href="cart.php">← Back to cart page</a>
</body>

</html>