<?php
session_start();
include "isSessCoo.php";
include "databaseconn.php";

class CartManager
{
    private $conn;
    private $status;
    private $userid = null;
    private $cartId = null;
    private $dbCart = [];
    private $currentCart = [];

    public function __construct($conn, $status)
    {
        $this->conn = $conn;
        $this->status = $status;
        $this->initUser();
        $this->loadDatabaseCart();
        $this->loadGuestCart();
    }

    /* ================= USER CHECK ================= */

    private function initUser()
    {
        if (isset($_SESSION['userid']) || isset($_COOKIE['userid'])) {
            $this->userid = ($this->status->cookieorsess() === "session")
                ? $_SESSION['userid']
                : $_COOKIE['userid'];
        }
    }

    public function getUserId()
    {
        return $this->userid;
    }

    /* ================= DATABASE CART ================= */

    private function loadDatabaseCart()
    {
        if ($this->userid) {

            $sql = "SELECT c.cart_id, ci.product_id, ci.quantity
                    FROM cart c
                    JOIN cart_items ci ON c.cart_id = ci.cart_id
                    WHERE c.user_id = $this->userid";

            $result = $this->conn->query($sql);

            while ($row = $result->fetch_assoc()) {
                $this->dbCart[$row['product_id']] = $row['quantity'];
                $this->cartId = $row['cart_id'];
            }
        }
    }

    /* ================= GUEST CART ================= */

    private function loadGuestCart()
    {
        if (!$this->userid) {
            if (!empty($_SESSION['cart'])) {
                $this->currentCart = $_SESSION['cart'];
            } elseif (!empty($_COOKIE['cart'])) {
                $this->currentCart = json_decode($_COOKIE['cart'], true);
            }
        }
    }

    /* ================= HANDLE ACTION ================= */

    public function handleActions()
    {
        if (!isset($_POST['action'], $_POST['product_id'])) return;

        $pid = $_POST['product_id'];

        // Logged-in user
        if ($this->userid) {

            if ($_POST['action'] === 'plus') {
                $this->conn->query("UPDATE cart_items
                                    SET quantity = quantity + 1
                                    WHERE cart_id = $this->cartId
                                    AND product_id = $pid");
            }

            if ($_POST['action'] === 'minus') {
                $this->conn->query("UPDATE cart_items
                                    SET quantity = quantity - 1
                                    WHERE cart_id = $this->cartId
                                    AND product_id = $pid");

                $this->conn->query("DELETE FROM cart_items
                                    WHERE cart_id = $this->cartId
                                    AND product_id = $pid
                                    AND quantity <= 0");
            }

            if ($_POST['action'] === 'remove') {
                $this->conn->query("DELETE FROM cart_items
                                    WHERE cart_id = $this->cartId
                                    AND product_id = $pid");
            }

            header("Location: cart.php");
            exit;
        }

        // Guest user
        else {

            if ($_POST['action'] === 'plus') {

                if (isset($_SESSION['cart'])) {
                    $_SESSION['cart_count']++;
                    $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + 1;
                } elseif (isset($_COOKIE['cart'])) {

                    $cart = json_decode($_COOKIE['cart'], true);
                    $cart[$pid] = ($cart[$pid] ?? 0) + 1;
                    $cart_count = $_COOKIE['cart_count'] + 1;

                    setcookie("cart_count", $cart_count, time() +3600, "/");
                    setcookie("cart", json_encode($cart), time() + 3600, "/");
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

                    $cart = json_decode($_COOKIE['cart'], true);

                    if (isset($cart[$pid])) {

                        $cart[$pid]--;

                        if ($cart[$pid] <= 0) {
                            unset($cart[$pid]);
                        }

                        $cart_count = $_COOKIE['cart_count'] - 1;
                        if ($cart_count < 0) $cart_count = 0;

                        setcookie("cart", json_encode($cart), time() +3600, "/");
                        setcookie("cart_count", $cart_count, time() +3600, "/");
                    }
                }
            }

            if ($_POST['action'] === 'remove') {

                if (isset($_SESSION['cart'])) {

                    $_SESSION['cart_count'] -= $_SESSION['cart'][$pid];
                    unset($_SESSION['cart'][$pid]);
                } elseif (isset($_COOKIE['cart'])) {

                    $cart = json_decode($_COOKIE['cart'], true);

                    if (isset($cart[$pid])) {

                        unset($cart[$pid]);
                        $cart_count = array_sum($cart);

                        setcookie("cart", json_encode($cart), time() + 3600, "/");
                        setcookie("cart_count", $cart_count, time() + 3600, "/");
                    }
                }
            }

            header("Location: cart.php");
            exit;
        }
    }

    /* ================= FETCH CART ================= */

    public function fetchCart()
    {
        if ($this->userid && $this->cartId) {

            $sql = "SELECT p.*, ci.quantity
                    FROM cart_items ci
                    JOIN products p ON ci.product_id = p.product_id
                    WHERE ci.cart_id = $this->cartId";

            return $this->conn->query($sql);
        } elseif (!$this->userid && !empty($this->currentCart)) {

            $ids = implode(",", array_keys($this->currentCart));
            $sql = "SELECT * FROM products WHERE product_id IN ($ids)";
            return $this->conn->query($sql);
        }

        return null;
    }

    public function getGuestCart()
    {
        return $this->currentCart;
    }
}

/* ============================
   PAGE EXECUTION
============================ */

$status = new Status();
$cartManager = new CartManager($conn, $status);

$cartManager->handleActions();

if (isset($_POST['checkout'])) {

    if ($cartManager->getUserId()) {
        header("Location: checkout.php");
    } else {
        header("Location: login.php");
    }
    exit;
}

$result = $cartManager->fetchCart();
$currentCart = $cartManager->getGuestCart();
$userid = $cartManager->getUserId();

$totalCount = 0;
$grandTotal = 0;
?>

  <?php
    if ($userid && $status->cookieorsess() === "session") {
        $_SESSION['cart_count'] = $totalCount;
    } elseif ($userid) {
        setcookie("cart_count", $totalCount, time() + 3600, "/");
    }
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

  

</body>

</html>