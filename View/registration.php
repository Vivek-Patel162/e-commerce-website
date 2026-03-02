
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="registration.css">

</head>

<body>
   
    <div class="container">


        <h1>Registration</h1>
        <form  method="POST" action="../controller/save.php">


            <label for="nam">Enter name</label>
            <input type="text" id="nam" name="ename" >
            <span style="color:red">
                <?= $errors['ename'] ?? '' ?>
            </span><br><br>



            <label for="email">Enter Email</label>
            <input type="email" id="email" name="email">
            <span style="color:red">
                <?= $errors['email'] ?? '' ?>
            </span><br><br>


            <label for="phone">Enter Phone No.</label>
            <input type="number" id="phone" name="phone">
            <span style="color:red">
                <?= $errors['phone'] ?? '' ?>
            </span><br><br>


            <label for="password">Enter Password</label>
            <div class="pass-box">
                <input type="password" id="password" name="password">
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
