<?php
require_once dirname(__DIR__) . "/Model/getdata.php";
require_once dirname(__DIR__) . "/View/dataOf.php";
$errors = [];
$result = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        $email = trim($_POST['email'] ?? '');
        if ($email === "") {
            $errors['email'] = "Email is required";
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format";
        }

        if (empty($errors)) {
            $result = getdata($email);
            if (function_exists('view')) {
                view($result);
            }
            else {
                echo "function doesn't exists";
            }

        }
        else {
            echo "there is an error";
        }
    }
}
?>