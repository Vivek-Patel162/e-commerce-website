<?php
session_start();
require_once "../4Feb/databaseconn.php";
include "isSessCoo.php";

/* ===============================
   CLASSES
=============================== */

class User
{
    private $conn;
    public $id;
    public $name;
    public $phone;
    public $email;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initUser();
    }

    private function initUser()
    {
        if (isset($_COOKIE['email'])) {
            $this->email = $_COOKIE['email'];
        } elseif (isset($_SESSION['email'])) {
            $this->email = $_SESSION['email'];
        }

        if ($this->email) {
            $sql = "SELECT user_id, name, phone FROM users WHERE email='" . $this->email . "'";
            $result = $this->conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $this->id = $row['user_id'];
                $this->name = $row['name'];
                $this->phone = $row['phone'];
            }
        }
    }
}

class Cart
{
    private $conn;
    private $userId;
    public $items = []; // product_id => quantity

    public function __construct($conn, $userId = null)
    {
        $this->conn = $conn;
        $this->userId = $userId;
        $this->loadCart();
    }

    private function loadCart()
    {
        // Logged-in user → database
        if ($this->userId) {
            $sql = "SELECT ci.product_id, ci.quantity
                    FROM cart c
                    JOIN cart_items ci ON c.cart_id = ci.cart_id
                    WHERE c.user_id = $this->userId";
            $result = $this->conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $this->items[$row['product_id']] = $row['quantity'];
                }
            }
        }
        // Guest session
        elseif (isset($_SESSION['cart'])) {
            $this->items = $_SESSION['cart'];
        }
        // Guest cookie
        elseif (isset($_COOKIE['cart'])) {
            $this->items = json_decode($_COOKIE['cart'], true);
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function getProducts()
    {
        if ($this->isEmpty()) return [];

        $product_ids = implode(",", array_keys($this->items));
        $sql = "SELECT * FROM products WHERE product_id IN ($product_ids)";
        $result = $this->conn->query($sql);
        $products = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[$row['product_id']] = $row;
            }
        }
        return $products;
    }

    public function clear()
    {
        if ($this->userId) {
            $this->conn->query("DELETE ci FROM cart_items ci
                                JOIN cart c ON ci.cart_id = c.cart_id
                                WHERE c.user_id = $this->userId");
        } elseif (isset($_SESSION['cart'])) {
            unset($_SESSION['cart'], $_SESSION['cart_count']);
        } elseif (isset($_COOKIE['cart'])) {
            setcookie("cart", "", time() - 3600, "/");
            setcookie("cart_count", "", time() - 3600, "/");
        }
    }
}

class Order
{
    private $conn;
    private $userId;
    private $cart;
    public $grandTotal = 0;

    public function __construct($conn, $userId, Cart $cart)
    {
        $this->conn = $conn;
        $this->userId = $userId;
        $this->cart = $cart;
    }

    public function placeOrder($paymentMethod)
    {
        $products = $this->cart->getProducts();
        if (empty($products)) return false;

        // Calculate grand total
        foreach ($products as $pid => $product) {
            $qty = $this->cart->items[$pid];
            $this->grandTotal += $product['price'] * $qty;
        }

        // Insert into orders table
        $sqlOrder = "INSERT INTO orders(user_id, total_amount, payment_method)
                     VALUES ($this->userId, $this->grandTotal, '$paymentMethod')";
        if ($this->conn->query($sqlOrder)) {
            $orderId = $this->conn->insert_id;

            // Prepare order_items insert
            $values = [];
            foreach ($products as $pid => $product) {
                $qty = $this->cart->items[$pid];
                $subtotal = $product['price'] * $qty;
                $values[] = "($orderId, $pid, $qty, {$product['price']}, $subtotal)";
            }

            $sqlItems = "INSERT INTO order_items(order_id, product_id, quantity, price, subtotal)
                         VALUES " . implode(",", $values);

            if ($this->conn->query($sqlItems)) {
                $this->cart->clear();
                return true;
            }
        }
        return false;
    }
}

/* ===============================
   PAGE LOGIC
=============================== */

$status = new Status();
$user = new User($conn);
$cart = new Cart($conn, $user->id);

if ($cart->isEmpty()) {
    echo "<h2>Your cart is empty</h2>";
    exit;
}

$products = $cart->getProducts();
$grandTotal = 0;
foreach ($products as $pid => $product) {
    $grandTotal += $product['price'] * $cart->items[$pid];
}

$errors = [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="checkout.css">
</head>

<body>
    <h1>Order Confirmation</h1>
    <form method="POST">
        Name:
        <input type="text" name="name" value="<?= htmlspecialchars($user->name) ?>">
        <span style="color:red"><?= $errors['name'] ?? '' ?></span><br><br>

        Phone:
        <input type="text" name="phone" value="<?= htmlspecialchars($user->phone) ?>">
        <span style="color:red"><?= $errors['phone'] ?? '' ?></span><br><br>

        Delivery Address:
        <input type="text" name="address">
        <span style="color:red"><?= $errors['address'] ?? '' ?></span><br><br>

        Payment Method:
        <select name="paymentMethod">
            <option value="Cash on Delivery">Cash on Delivery</option>
            <option value="Net Banking">Net Banking</option>
        </select><br><br>

        <table cellpadding="10">
            <tr>
                <th>Image</th>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
            </tr>
            <?php foreach ($products as $pid => $product): 
                $qty = $cart->items[$pid];
                $total = $product['price'] * $qty;
            ?>
            <tr>
                <td><img src="/PracticePhp/4Feb/images/<?= $product['image'] ?>" width="80"></td>
                <td><?= $product['product_name'] ?></td>
                <td>₹<?= $product['price'] ?></td>
                <td><?= $qty ?></td>
                <td>₹<?= $total ?></td>
            </tr>
            <?php endforeach; ?>
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
        $paymentMethod = trim($_POST['paymentMethod'] ?? '');

        if ($name === "") $errors['name'] = "Name is required";
        elseif (!preg_match("/^[a-zA-Z-' ]{3,}$/", $name)) $errors['name'] = "Name should be at least 3 letters";

        if ($phone === "") $errors['phone'] = "Phone is required";
        elseif (!preg_match("/^[6-9]\d{9}$/", $phone)) $errors['phone'] = "Phone must be 10 digits";

        if ($address === "") $errors['address'] = "Address is required";

        if (empty($errors)) {
            $order = new Order($conn, $user->id, $cart);
            if ($order->placeOrder($paymentMethod)) {
                header("Location: dashboard.php");
                exit;
            } else {
                echo "<p>Something went wrong placing the order.</p>";
            }
        }
    }
    ?>
    <a href="cart.php">← Back to cart page</a>
</body>
</html>
