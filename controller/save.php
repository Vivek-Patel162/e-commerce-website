 <?php
  
    require_once "../Model/insert.php";
    $errors = [];
    $name = $email  = $password = $phone = $success = "";

    if ($_SERVER['REQUEST_METHOD'] === "POST") {

        if (isset($_POST['submit'])) {

            $name = trim($_POST['ename'] ?? '');
            $email = trim($_POST['email'] ?? '');
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

            if (empty($phone)) {
                $errors['phone'] = "Phone number is required";
            } elseif (!preg_match("/^[6-9]\d{9}$/", $phone)) {
                $errors['phone'] = "Phone number must be exactly 10 digits and start with 6, 7, 8, or 9.";
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
                if (insertData($name, $email, $hashedPassword, $phone)) {
                    echo "Saved successfully!";
                } else {
                    echo "Error saving data.";
                }
            }
        }
    }
    ?>