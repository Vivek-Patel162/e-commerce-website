<?php
session_start();
require "databaseconn.php";
include "isSessCoo.php";
$status = new Status();

if (!isset($_SESSION['cart_count'])) {
    $_SESSION['cart_count'] = 0;
} else {

    if (!isset($_COOKIE['cart_count'])) {
        setcookie('cart_count', 0, time() + 60 * 10, "/");
        $_COOKIE['cart_count'] = 0; // So you can use it immediately
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="registration.css">

</head>

<body>
    <?php


    $errors = [];
    $name = $email  = $password = $phone = $success = "";

    if ($_SERVER['REQUEST_METHOD'] === "POST") {

        if (isset($_POST['submit'])) {

            $name = trim($_POST['ename'] ?? '');
            $email = trim($_POST['email'] ?? '');
            // $city = trim($_POST['city'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = trim($_POST['password'] ?? '');



            if ($name === "") {
                $errors['ename'] = "Name is required";
            } elseif (!preg_match("/^[a-zA-Z-' ]{3,}$/", $name)) {
                $errors['ename'] = "Name should be at least 3 characters long and contain only letters";
            }

            if ($email === "") {
                $errors['email'] = "Email is required";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Invalid email format";
            }

            // if (empty($city)) {
            //     $errors['city'] = "City is required";
            // } elseif (!preg_match("/^[a-zA-Z\s]+$/", $city)) {
            //     $errors['city'] = "City must contain only letters and spaces";
            // }


            if (empty($phone)) {
                $errors['phone'] = "Phone number is required";
            } elseif (!preg_match("/^[6-9]\d{9}$/", $phone)) {
                $errors['phone'] = "Phone number must be exactly 10 digits";
            }
            if ($password == "") {
                $errors['password'] = "Password is required";
            } elseif (!preg_match(
                "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/",
                $password
            )) {
                $errors['password'] = "Password must be at least 8 characters and include uppercase, lowercase, number & special character";
            }
            // echo $password;
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            if (empty($errors)) {
                $sql = "select * from users where email='" . $email . "'";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    $success = "data already present for this email";
                } else {
                    $sql = "INSERT INTO users(name,email,phone,password)
                    VALUES ('$name','$email',$phone,'$hashedPassword')";
                    // -- VALUES ($name,$email,$city,$phone,$hashedPassword)";

                    if ($conn->query($sql)) {
                        $lastid = $conn->insert_id;

                        //cookie check active or not

                        if (isset($_COOKIE['active'])&&($_COOKIE['active']=="true")) {


                            $status->setCookie($email, $name, $lastid);
                        } else {
                       $status->setSession($email, $name, $lastid);
                        }
                        if (($_SESSION['cart_count']) > 0 || ($_COOKIE['cart_count']) > 0) {
                            header("Location:cart.php");
                            exit;
                        }
                        header("Location: dashboard.php");

                        exit;
                    }
                }
            }
        }
    }
    ?>
    <div class="container">


        <h1>Registration</h1>
        <form action="" method="POST">
            <label for="nam">Enter name</label>
            <input type="text" id="nam" name="ename" value="<?= htmlspecialchars($name)  ?>">
            <span style="color:red">
                <?= $errors['ename'] ?? '' ?>
            </span><br><br>



            <label for="email">Enter Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email)  ?>">
            <span style="color:red">
                <?= $errors['email'] ?? '' ?>
            </span><br><br>



            <!-- <label for="city">Enter City</label>
            <input type="text" id="city" name="city" value="<?= htmlspecialchars($city)  ?>">
            <span style="color:red">
               
            </span><br><br> -->



            <label for="phone">Enter Phone No.</label>
            <input type="number" id="phone" name="phone" value="<?= htmlspecialchars($phone)  ?>">
            <span style="color:red">
            </span><br><br>



            <label for="password">Enter Password</label>
            <div class="pass-box">
                <input type="password" id="password" name="password" value="<?= htmlspecialchars($password)  ?>">
                <span onclick="showHide()">👁</span>
            </div>
            <span style="color:red">
                <?= $errors['password'] ?? '' ?>
            </span><br>

            <br>
            <button type="submit" name="submit">Register</button>
            <a href="login.php">Login</a>
            <span style="color:green">
                <?= $success ?? '' ?>
            </span><br><br>
        </form>
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
<?php



$conn->close();
?>