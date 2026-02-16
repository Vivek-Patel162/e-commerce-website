<?php
session_start();
include "isSessCoo.php";
$status=new Status();
$status->unsetCookie();
$status->unsetSession();
    header("Location: dashboard.php");
    exit;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>

</body>

</html>