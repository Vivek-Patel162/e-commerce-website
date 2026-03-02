<?php
require_once dirname(__DIR__) ."/View/update.php";
require_once dirname(__DIR__) ."/Model/update.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update1']))
    {
        $eid=trim($_POST['eid'] ?? '');
       
        $result=updateData($eid);
      
        DataForUpdation($result);

    }

    if(isset($_POST['update']))
    {

      

            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
        



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
        $eid=trim($_POST['eid'] ?? '');
        $result=updateData1($eid,$name,$email,$phone);
        if($result)
        {
            header("Location:../View/dataOf.php");
        }

    }
    
    if(isset($_POST['remove']))
    {
         $eid=trim($_POST['eid'] ?? '');
         $result=removeTable($eid);
         
        if($result)
        {
            echo "successfully deleted";
        }
    }
    
}

    ?>