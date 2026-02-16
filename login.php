<?php
session_start();
include "isSessCoo.php";
$status = new Status();

$errors = [];
if (!isset($_SESSION['cart_count'])) {
    $_SESSION['cart_count'] = 0;
}

require "databaseconn.php";

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['login'])) {

    $uemail = trim($_POST['uemail'] ?? '');
    $upassword = trim($_POST['upassword'] ?? '');

    if ($uemail === "") {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($uemail, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    if ($upassword == "") {
        $errors['password'] = "Password is required";
    } elseif (!preg_match(
        "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/",
        $upassword
    )) {
        $errors['password'] = "Password must be at least 8 characters and include uppercase, lowercase, number & special character";
    }

    if (empty($errors)) {

        $sql = "select * from users where email='" . $uemail . "'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {

            $row = $result->fetch_assoc();

            if (password_verify($upassword, $row['password'])) {

                //cookie check is it active or not
                if ($status->iscookie()) {
                    $status->setCookie($row['email'], $row['name'], $row['user_id']);
                } else {
                    $status->setSession($row['email'], $row['name'], $row['user_id']);
                }

                /* =====================================
                   MERGE GUEST CART INTO DATABASE
                ===================================== */

                $user_id = $row['user_id'];
                $guestCart = [];

                if (!empty($_SESSION['cart'])) {
                    $guestCart = $_SESSION['cart'];
                } elseif (!empty($_COOKIE['cart'])) {
                    $guestCart = json_decode($_COOKIE['cart'], true);
                }

                if (!empty($guestCart)) {

                    $checkCart = $conn->query("SELECT cart_id FROM cart WHERE user_id = $user_id");

                    if ($checkCart->num_rows > 0) {
                        $cartRow = $checkCart->fetch_assoc();
                        $cartId = $cartRow['cart_id'];
                    } else {
                        $conn->query("INSERT INTO cart (user_id) VALUES ($user_id)");
                        $cartId = $conn->insert_id;
                    }

                    foreach ($guestCart as $productId => $quantity) {

                        $checkItem = $conn->query("SELECT quantity FROM cart_items 
                                                   WHERE cart_id = $cartId 
                                                   AND product_id = $productId");

                        if ($checkItem->num_rows > 0) {

                            $conn->query("UPDATE cart_items 
                                          SET quantity = quantity + $quantity
                                          WHERE cart_id = $cartId 
                                          AND product_id = $productId");

                        } else {

                            $conn->query("INSERT INTO cart_items (cart_id, product_id, quantity)
                                          VALUES ($cartId, $productId, $quantity)");
                        }
                    }

                    // Clear guest cart after merge
                    unset($_SESSION['cart']);
                    setcookie("cart", "", time() - 3600, "/");
                }

                /* ===================================== */

                if (($_SESSION['cart_count']) > 0 || ($_COOKIE['cart_count']) > 0) {
                    header("Location:cart.php");
                    exit;
                }

                header("Location: dashboard.php");
                exit;

            } else {
                $errors['password'] = "Invalid password";
            }

        } else {
            $errors['email'] = "Invalid email";
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <div class="wrapper">
        <div class="box">
            <h2>Login</h2>
            <form action="" method="POST">
                <label for="email">Email</label>
                <input type="email" name="uemail" value="<?= htmlspecialchars($uemail ?? '') ?>">
                <span class="error"><?= $errors['email'] ?? '' ?></span><br><br>

                <label for="password">Password</label>
                <div class="pass-box">
                    <input type="password" id="password" name="upassword">
                    <span onclick="showHide()">👁</span>
                </div>
                <span class="error"><?= $errors['password'] ?? '' ?></span><br><br>

                <button type="submit" name="login">Login</button>
                Don't have an account?
                <a href="registration.php">Register</a>
            </form>
        </div>
    </div>

    <script>
        function showHide() {
            const input = document.getElementById("password");
            if (!input) return;
            input.type = (input.type === "password") ? "text" : "password";
        }
    </script>
</body>

</html>
