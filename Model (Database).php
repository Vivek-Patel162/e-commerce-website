Model (Database)
/model/db.php
<?php

$conn = mysqli_connect("localhost", "root", "", "test_db");

if (!$conn) {
    die("DB Connection Failed");
}

/model/insert.php
<?php

require "db.php";

function insertContact($name, $email) {
    global $conn;

    $name = mysqli_real_escape_string($conn, $name);
    $email = mysqli_real_escape_string($conn, $email);

    $sql = "INSERT INTO contacts (name, email) VALUES ('$name', '$email')";
    return mysqli_query($conn, $sql);
}

4) View (Form UI)
/view/form.php
<!DOCTYPE html>
<html>
<head>
    <title>Simple Form</title>
</head>
<body>

<h2>Contact Form</h2>

<form method="POST" action="controller/save.php">
    Name:<br>
    <input type="text" name="name" required><br><br>

    Email:<br>
    <input type="email" name="email" required><br><br>

    <button type="submit">Save</button>
</form>

</body>
</html>

5) Controller (Handle Request)
/controller/save.php
<?php

require "../model/insert.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["name"];
    $email = $_POST["email"];

    if (insertContact($name, $email)) {
        echo "Saved successfully!";
    } else {
        echo "Error saving data.";
    }
}