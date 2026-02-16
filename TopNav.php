<?php
require "databaseconn.php";
include "isSessCoo.php";

$status = new Status();

/* ===============================
   LOGIN REDIRECT
================================ */
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['login'])) {
    header("Location:login.php");
    exit;
}

/* ===============================
   VIEW CART
================================ */
if (isset($_GET['action']) && $_GET['action'] === 'view_cart') {

    if (isset($_COOKIE['cookie_consent'])) {
        header("Location: cart.php");
        exit;
    } else {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

/* ===============================
   LOGOUT
================================ */
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['logout'])) {

    $status->unsetCookie();
    $status->unsetSession();

    // Clear cookie consent
    setcookie("cookie_consent", "", time() - 3600, "/");
    setcookie("active", "", time() - 3600, "/");
    setcookie("cart", "", time() - 3600, "/");
    setcookie("cart_count", "", time() - 3600, "/");

    header("Location: dashboard.php");
    exit;
}


/* ===============================
   GET CART COUNT (SAME AS DASHBOARD)
================================ */

$cartCount = 0;

if (isset($_SESSION['userid']) || isset($_COOKIE['userid'])) {

    $userid = $_SESSION['userid'] ?? $_COOKIE['userid'];

    $countQuery = $conn->query("
        SELECT SUM(ci.quantity) as total
        FROM cart c
        JOIN cart_items ci ON c.cart_id = ci.cart_id
        WHERE c.user_id = $userid
    ");

    if ($countQuery && $countQuery->num_rows > 0) {
        $row = $countQuery->fetch_assoc();
        $cartCount = $row['total'] ?? 0;
    }

} else {

    $cartCount =
        $_SESSION['cart_count'] ??
        ($_COOKIE['cart_count'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
        <h2 class="logo">Dashboard</h2>

        <a href="dashboard.php">All</a>
        <a href="product.php?category_id=3">Mobiles</a>
        <a href="product.php?category_id=4">Laptop</a>
        <a href="product.php?category_id=11">cloth</a>
    </div>

    <div class="nav-center">
        <?php if(isset($_COOKIE['user'])) { ?>
            <p>Welcome, <b><?= $_COOKIE['user'] ?></b></p>
        <?php } elseif(isset($_SESSION['user'])) { ?>
            <p>Welcome, <b><?= $_SESSION['user'] ?></b></p>
        <?php } else { ?> 
            <p>Welcome,User <b></b></p>
        <?php } ?>
    </div>

    <div class="nav-right">
        <a href="?action=view_cart" class="cart-link">
            🛒 Cart <span class="cart-count"><?= $cartCount ?></span>
        </a>

        <?php if ((isset($_SESSION['user']))||(isset($_COOKIE['user']))) { ?>
            <form method="POST" class="logout-form">
                <button name="logout" class="logout-btn">Logout</button>
            </form>
        <?php } else { ?>
            <form method="POST" class="logout-form">
                <button name="login" class="logout-btn">Login</button>
            </form>
        <?php } ?>
    </div>
</nav>

</body>
</html>
