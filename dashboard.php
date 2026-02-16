<?php
session_start();
/* ===============================
   COOKIE ACCEPT / REJECT
================================ */

if (isset($_POST['cookie_action'])) {

    if ($_POST['cookie_action'] === 'accept') {

        // Save consent
        setcookie("cookie_consent", "accepted", time() + 600, "/");
        setcookie("active", "true", time() + 600, "/");


        if (!empty($_SESSION['cart'])) {

            $sessionCart = $_SESSION['cart'];

            // If cookie cart already exists → merge
            $cookieCart = [];

            if (isset($_COOKIE['cart'])) {
                $decoded = json_decode($_COOKIE['cart'], true);
                if (is_array($decoded)) {
                    $cookieCart = $decoded;
                }
            }

            foreach ($sessionCart as $pid => $qty) {
                $cookieCart[$pid] = ($cookieCart[$pid] ?? 0) + $qty;
            }

            $cart_count = array_sum($cookieCart);

            setcookie("cart", json_encode($cookieCart), time() + 600, "/");
            setcookie("cart_count", $cart_count, time() + 600, "/");

            unset($_SESSION['cart']);
            unset($_SESSION['cart_count']);
        }
    }

    if ($_POST['cookie_action'] === 'reject') {

        setcookie("cookie_consent", "rejected", time() + 600, "/");
        setcookie("active", "false", time() + 600, "/");

        // Optional: clear cookie cart if rejecting
        setcookie("cart", "", time() - 3600, "/");
        setcookie("cart_count", "", time() - 3600, "/");
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

include "databaseconn.php";
include "isSessCoo.php";

$status = new Status();

/* ===============================
   LOGIN / LOGOUT
================================ */

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['login'])) {
    header("Location: login.php");
    exit;
}

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
   ADD TO CART
================================ */

if (isset($_POST['add_to_cart'])) {

    $product_id = intval($_POST['product_id']);

    // LOGGED IN USER → DATABASE CART
    if (isset($_SESSION['userid']) || isset($_COOKIE['userid'])) {

        $userid = $_SESSION['userid'] ?? $_COOKIE['userid'];

        // Check cart exists
        $checkCart = $conn->query("SELECT cart_id FROM cart WHERE user_id = $userid");

        if ($checkCart->num_rows > 0) {
            $cartRow = $checkCart->fetch_assoc();
            $cartId = $cartRow['cart_id'];
        } else {
            $conn->query("INSERT INTO cart (user_id) VALUES ($userid)");
            $cartId = $conn->insert_id;
        }

        // Check product exists in cart
        $checkProduct = $conn->query("
            SELECT quantity FROM cart_items
            WHERE cart_id = $cartId AND product_id = $product_id
        ");

        if ($checkProduct->num_rows > 0) {
            $conn->query("
                UPDATE cart_items
                SET quantity = quantity + 1
                WHERE cart_id = $cartId AND product_id = $product_id
            ");
        } else {
            $conn->query("
                INSERT INTO cart_items (cart_id, product_id, quantity)
                VALUES ($cartId, $product_id, 1)
            ");
        }
    }

    // GUEST USER → COOKIE / SESSION
    else {

        if ($status->isCookie()) {

            $cart = [];

            if (isset($_COOKIE['cart'])) {
                $decoded = json_decode($_COOKIE['cart'], true);
                if (is_array($decoded)) {
                    $cart = $decoded;
                }
            }

            $cart[$product_id] = ($cart[$product_id] ?? 0) + 1;
            $cart_count = array_sum($cart);

            setcookie("cart", json_encode($cart), time() + 600, "/");
            setcookie("cart_count", $cart_count, time() + 600, "/");
        } else {

            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            $_SESSION['cart'][$product_id] =
                ($_SESSION['cart'][$product_id] ?? 0) + 1;

            $_SESSION['cart_count'] =
                array_sum($_SESSION['cart']);
        }
    }

    header("Location: dashboard.php");
    exit;
}

/* ===============================
   GET CART COUNT (ICON)
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

/* ===============================
   GET PRODUCTS
================================ */

$sql = "SELECT * FROM products";
$result = $conn->query($sql);

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <nav class="navbar">

        <div class="nav-left">
            <h2>Dashboard</h2>
            <a href="product.php?category_id=1">All</a>
            <a href="product.php?category_id=3">Mobiles</a>
            <a href="product.php?category_id=4">Laptop</a>
            <a href="product.php?category_id=11">Cloth</a>
        </div>

        <div class="nav-center">
            <?php if (isset($_SESSION['user'])): ?>
                <p>Welcome, <b><?= $_SESSION['user'] ?></b></p>
            <?php elseif (isset($_COOKIE['user'])): ?>
                <p>Welcome, <b><?= $_COOKIE['user'] ?></b></p>
            <?php else: ?>
                <p>Welcome, User</p>
            <?php endif; ?>
        </div>

        <div class="nav-right">
            <a href="cart.php" class="cart-link">
                🛒 Cart <span class="cart-count"><?= $cartCount ?></span>
            </a>

            <a href="order.php">Orders</a>

            <?php if (isset($_SESSION['user']) || isset($_COOKIE['user'])): ?>
                <form method="POST">
                    <button name="logout" class="logout-btn">Logout</button>
                </form>
            <?php else: ?>
                <form method="POST">
                    <button name="login">Login</button>
                </form>
            <?php endif; ?>
        </div>

    </nav>

    <div class="product-container">

        <?php foreach ($products as $row): ?>

            <form method="POST">
                <div class="product-card">

                    <a href="product_details.php?id=<?= $row['product_id'] ?>">
                        <img src="/PracticePhp/4Feb/images/<?= $row['image'] ?>" width="80">
                        <h3><?= $row['product_name'] ?></h3>
                        <p>₹<?= $row['price'] ?></p>
                    </a>

                    <input type="hidden" name="product_id" value="<?= $row['product_id'] ?>">

                    <button type="submit" name="add_to_cart" class="add-to-cart">
                        Add to Cart
                    </button>

                </div>
            </form>

        <?php endforeach; ?>

    </div>

    <?php if (!isset($_COOKIE['cookie_consent'])): ?>
        <div class="cookie-popup">
            <p>Please accept or reject cookies to continue.</p>

            <form method="POST">
                <button type="submit" name="cookie_action" value="accept" class="accept-btn">
                    Accept
                </button>

                <button type="submit" name="cookie_action" value="reject" class="decline-btn">
                    Reject
                </button>

            </form>
        </div>
    <?php endif; ?>





</body>

</html>