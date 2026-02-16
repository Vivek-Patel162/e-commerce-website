<?php
interface Availability
{
    public function iscookie(): bool;
    public function unsetCookie();
    public function unsetSession();
    public function setSession($email, $name, $userid);
    public function setCookie($email, $name, $lastid);
    public function cookieorsess();

}

class Status implements Availability
{
    public function iscookie(): bool
    { // explicitly add : bool here too
        return (isset($_COOKIE['active']) && $_COOKIE['active'] === "true");
    }
    public function unsetCookie()
    {
        setcookie("email", "", time() - 3600, "/");
        setcookie("user", "", time() - 3600, "/");
        setcookie("userid", "", time() - 3600, "/");
        setcookie("cart", "", time() - 3600, "/");
        setcookie("cart_count", "", time() - 3600, "/");
    }
    public function unsetSession()
    {
        unset($_SESSION['cart']);
        unset($_SESSION['cart_count']);
        session_unset();
        session_destroy();
    }

    public function setCookie($email, $name, $lastid)
    {
        setcookie("email", $email, time() + (60 * 10), "/");
        setcookie("user", $name, time() + (60 * 10), "/");
        setcookie("userid", $lastid, time() + (60 * 10), "/");
    }
    public function setSession($email,$name,$userid){
    $_SESSION['email'] = $email;
    $_SESSION['user'] = $name;
    $_SESSION['userid'] = $userid;
    }
    public function cookieorsess(){
    if(!isset($_COOKIE['userid']))
     {
      return "session";
   }else{
     return "cookie";
     }
    }
}
