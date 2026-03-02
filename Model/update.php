<?php
require_once dirname(__DIR__) . "/Model/databaseconn.php";



 function updateData($eid)
 {
    global $conn;
   $sql = "select * from employee2 where id=".$eid;
   $result = $conn->query($sql);
   return  $result;

 }
  function updateData1($eid,$name,$email,$phone)
 {
   global $conn;
   $sql = "UPDATE employee2 
        SET name='".$name."', 
            email='".$email."', 
            phone='".$phone."' 
        WHERE id=".$eid;
        
  $result = $conn->query($sql);
   return  $result;
 }
  function removeTable($eid)
 {
   global $conn;
    $sql = "delete from employee2 where id=".$eid;
   $result = $conn->query($sql);
   return  $result;
 }
?>
