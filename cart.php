<?php
session_start();
include "../4Feb/databaseconn.php";
include "isSessCoo.php";

$status = new Status();

/* =====================================
   USER CHECK (MODIFIED - no exit)
===================================== */

$userid = null;

if (isset($_SESSION['userid']) || isset($_COOKIE['userid'])) {
    $userid = ($status->cookieorsess() === "session")
        ? $_SESSION['userid']
        : $_COOKIE['userid'];
}

/* =====================================
   STEP 1: GET DATABASE CART (ONLY IF LOGGED IN)
===================================== */

$dbCart = [];
$cartId = null;

if ($userid) {

    $sql = "SELECT c.cart_id, ci.product_id, ci.quantity
            FROM cart c
            JOIN cart_items ci ON c.cart_id = ci.cart_id
            WHERE c.user_id = $userid";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $dbCart[$row['product_id']] = $row['quantity'];
        $cartId = $row['cart_id'];
    }
}

/* =====================================
   STEP 2: GET GUEST CART
===================================== */

$currentCart = [];

if (!$userid) {
    if (!empty($_SESSION['cart'])) {
        $currentCart = $_SESSION['cart'];
    } elseif (!empty($_COOKIE['cart'])) {
        $currentCart = json_decode($_COOKIE['cart'], true);
    }
}

/* =====================================
   STEP 3: HANDLE PLUS / MINUS / REMOVE
   (ONLY FOR LOGGED IN USER — SAME AS YOUR CODE)
===================================== */

if ($userid && isset($_POST['action'], $_POST['product_id'])) {

    $pid = $_POST['product_id'];

    if ($_POST['action'] === 'plus') {
        $conn->query("UPDATE cart_items
                      SET quantity = quantity + 1
                      WHERE cart_id = $cartId
                      AND product_id = $pid");
    }

    if ($_POST['action'] === 'minus') {
        $conn->query("UPDATE cart_items
                      SET quantity = quantity - 1
                      WHERE cart_id = $cartId
                      AND product_id = $pid");

        $conn->query("DELETE FROM cart_items
                      WHERE cart_id = $cartId
                      AND product_id = $pid
                      AND quantity <= 0");
    }

    if ($_POST['action'] === 'remove') {
        $conn->query("DELETE FROM cart_items
                      WHERE cart_id = $cartId
                      AND product_id = $pid");
    }

    header("Location: cart.php");
    exit;
}

/*----------------------------------------
for the user as a guest
-----------------------------------------*/


if (!$userid && isset($_POST['action'], $_POST['product_id'])) {

    $pid = $_POST['product_id'];

    if ($_POST['action'] === 'plus') {


        if (isset($_SESSION['cart'])) {
            $_SESSION['cart_count']++;
            $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + 1;
        } else {
            if (isset($_COOKIE['cart'])) {
                $cart = json_decode($_COOKIE['cart'], true);
                $cart[$pid] = ($cart[$pid] ?? 0) + 1;
                $cart_count = $_COOKIE['cart_count'] + 1;
                setcookie("cart_count", $cart_count, time() + (60 * 10), "/");
                setcookie("cart", json_encode($cart), time() + (60 * 10), "/");
            }
        }
    }

    if ($_POST['action'] === 'minus') {

        if (isset($_SESSION['cart'])) {


            $_SESSION['cart'][$pid]--;
            $_SESSION['cart_count']--;
            if ($_SESSION['cart'][$pid] <= 0) {
                unset($_SESSION['cart'][$pid]);
            }
        } elseif (isset($_COOKIE['cart'])) {


            $cart = isset($_COOKIE['cart'])
                ? json_decode($_COOKIE['cart'], true)
                : [];

            // Step 2: Decrease quantity if exists
            if (isset($cart[$pid])) {

                $cart[$pid]--;

                // Step 3: Remove item if quantity <= 0
                if ($cart[$pid] <= 0) {
                    unset($cart[$pid]);
                }

                // Step 4: Update cart_count
                $cart_count = isset($_COOKIE['cart_count'])
                    ? $_COOKIE['cart_count'] - 1
                    : 0;

                if ($cart_count < 0) {
                    $cart_count = 0;
                }

                // Step 5: Save updated values
                setcookie("cart", json_encode($cart), time() + 60 * 10, "/");
                setcookie("cart_count", $cart_count, time() + 60 * 10, "/");
            }
        }
    }
    if ($_POST['action'] === 'remove') {


        if (isset($_SESSION['cart'])) {
           
            $_SESSION['cart_count'] -= $_SESSION['cart'][$pid];
            unset($_SESSION['cart'][$pid]);
        } else {
           
            if (isset($_COOKIE['cart'])) {

                $cart = json_decode($_COOKIE['cart'], true);

                if (isset($cart[$pid])) {

                    unset($cart[$pid]);

                    $cart_count = array_sum($cart);

                    setcookie("cart", json_encode($cart), time() + 60 * 10, "/");
                    setcookie("cart_count", $cart_count, time() + 60 * 10, "/");
                }
            }
        }
    }
    header("Location: cart.php");
    exit;
}


/* =====================================
   STEP 4: HANDLE CHECKOUT REDIRECT
===================================== */

if (isset($_POST['checkout'])) {

    if ($userid) {
        header("Location: checkout.php");
    } else {
        header("Location: login.php");
    }

    exit;
}


/* =====================================
   STEP 4: FETCH CART FOR DISPLAY
===================================== */

if ($userid && $cartId) {

    $sql = "SELECT p.*, ci.quantity
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.product_id
            WHERE ci.cart_id = $cartId";

    $result = $conn->query($sql);

} elseif (!$userid && !empty($currentCart)) {

    $ids = implode(",", array_keys($currentCart));
    $sql = "SELECT * FROM products WHERE product_id IN ($ids)";
    $result = $conn->query($sql);

} else {
    $result = null;
}

$totalCount = 0;
$grandTotal = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Cart</title>
    <link rel="stylesheet" href="cart.css">
</head>
<body>

<h2>My Cart</h2>

<?php if (!$result || $result->num_rows == 0): ?>

    <h3>Your cart is empty</h3>

<?php else: ?>

<table cellpadding="10">
<tr>
    <th>Image</th>
    <th>Product</th>
    <th>Price</th>
    <th>Quantity</th>
    <th>Action</th>
    <th>Total</th>
</tr>

<?php while ($row = $result->fetch_assoc()):

    $pid = $row['product_id'];

    if ($userid) {
        $qty = $row['quantity'];
    } else {
        $qty = $currentCart[$pid] ?? 0;
    }

    $total = $row['price'] * $qty;

    $grandTotal += $total;
    $totalCount += $qty;
?>

<tr>
    <td><img src="/PracticePhp/4Feb/images/<?= $row['image'] ?>" width="80"></td>
    <td><?= $row['product_name'] ?></td>
    <td>₹<?= $row['price'] ?></td>

    <td>
       
            <form method="POST" style="display:inline;">
                <input type="hidden" name="product_id" value="<?= $pid ?>">
                <input type="hidden" name="action" value="minus">
                <button>-</button>
            </form>
      

        <?= $qty ?>

       
            <form method="POST" style="display:inline;">
                <input type="hidden" name="product_id" value="<?= $pid ?>">
                <input type="hidden" name="action" value="plus">
                <button>+</button>
            </form>
        
    </td>

    <td>
        
            <form method="POST">
                <input type="hidden" name="product_id" value="<?= $pid ?>">
                <input type="hidden" name="action" value="remove">
                <button>Remove</button>
            </form>
      
    </td>
    

    

    <td>₹<?= $total ?></td>
</tr>

<?php endwhile; ?>

<tr>
    <td colspan="5"><strong>Grand Total</strong></td>
    <td><strong>₹<?= $grandTotal ?></strong></td>
</tr>

</table>

<?php endif; ?>

<div class="cart-actions">
    <a href="dashboard.php">← Continue Shopping</a>

    <?php if ($result && $result->num_rows > 0): ?>
        <form method="POST" style="display:inline;">
            <button type="submit" name="checkout">Checkout</button>
        </form>
    <?php endif; ?>
</div>

<?php
if ($userid && $status->cookieorsess() === "session") {
    $_SESSION['cart_count'] = $totalCount;
} elseif ($userid) {
    setcookie("cart_count", $totalCount, time() + 600, "/");
}
?>

</body>
</html>