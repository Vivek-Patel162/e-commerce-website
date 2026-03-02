<?php
require_once __DIR__ . "/databaseconn.php";
// function getdata($email)
function getdata()
{
    global $conn;
    $sql = "select * from employee2";// where email='" ;//. $email . "'";
    $result = $conn->query($sql);
    return $result;
}

// function updatedata($eid,$name,$email,$phone)
// {
//     global $conn;
//  return  $conn->query("UPDATE employee2 SET name ='$name',email='$email',phone=$phone WHERE eid = $eid");
// }
function deletedata($eid){
    global $conn;
 return $conn->query("DELETE FROM employee2 WHERE id= $eid");
}
?>