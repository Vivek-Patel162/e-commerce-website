<?php
require_once "databaseconn.php";
function insertData($name, $email, $hashedPassword, $phone):bool
{  
    global $conn;
    $sql = "select * from employee2 where email='" .$email."'";
    $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return false;
        } else {

            $sql = "INSERT INTO employee2(name,email,phone,password)
                    VALUES ('$name','$email',$phone,'$hashedPassword')";
            // -- VALUES ($name,$email,$city,$phone,$hashedPassword)";

            if ($result = $conn->query($sql)) {;
                return true;
            }else {
                   return false;
                }


            }
        
    return false;
}
?>
