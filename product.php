<?php
session_start();
include "databaseconn.php";
include "TopNav.php";

class ProductPage
{
    private $conn;
    private $status;
    private $categoryId;

    public function __construct($conn, $status)
    {
        $this->conn = $conn;
        $this->status = $status;
        $this->categoryId = $_GET['category_id'] ?? null;
    }

    /* ===============================
       ADD TO CART
    ================================ */

    public function handleAddToCart()
    {
        if (!isset($_POST['add_to_cart'])) return;

        $product_id = intval($_POST['product_id']);

        // LOGGED IN USER → DATABASE
        if (isset($_SESSION['userid']) || isset($_COOKIE['userid'])) {

            $userid = $_SESSION['userid'] ?? $_COOKIE['userid'];

            $checkCart = $this->conn->query(
                "SELECT cart_id FROM cart WHERE user_id = $userid"
            );

            if ($checkCart->num_rows > 0) {
                $cartRow = $checkCart->fetch_assoc();
                $cartId = $cartRow['cart_id'];
            } else {
                $this->conn->query(
                    "INSERT INTO cart (user_id) VALUES ($userid)"
                );
                $cartId = $this->conn->insert_id;
            }

            $checkProduct = $this->conn->query("
                SELECT quantity FROM cart_items
                WHERE cart_id = $cartId AND product_id = $product_id
            ");

            if ($checkProduct->num_rows > 0) {
                $this->conn->query("
                    UPDATE cart_items
                    SET quantity = quantity + 1
                    WHERE cart_id = $cartId AND product_id = $product_id
                ");
            } else {
                $this->conn->query("
                    INSERT INTO cart_items (cart_id, product_id, quantity)
                    VALUES ($cartId, $product_id, 1)
                ");
            }
        }

        // GUEST USER → COOKIE / SESSION
        else {

            if ($this->status->isCookie()) {

                $cart = [];

                if (isset($_COOKIE['cart'])) {
                    $decoded = json_decode($_COOKIE['cart'], true);
                    if (is_array($decoded)) {
                        $cart = $decoded;
                    }
                }

                $cart[$product_id] = ($cart[$product_id] ?? 0) + 1;
                $cart_count = array_sum($cart);

                setcookie("cart", json_encode($cart), time() +3600, "/");
                setcookie("cart_count", $cart_count, time() +3600, "/");

            } else {

                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }

                $_SESSION['cart'][$product_id] =
                    ($_SESSION['cart'][$product_id] ?? 0) + 1;

                $_SESSION['cart_count'] =
                    array_sum($_SESSION['cart']);
            }
        }

        header("Location: product.php?category_id=" . $this->categoryId);
        exit;
    }

    /* ===============================
       FETCH PRODUCTS
    ================================ */

    public function getProducts()
    {
        $sql = "WITH RECURSIVE category_tree AS (
                    SELECT category_id
                    FROM categories
                    WHERE category_id = {$this->categoryId}

                    UNION ALL

                    SELECT c.category_id
                    FROM categories c
                    JOIN category_tree ct ON c.parent_id = ct.category_id
                )
                SELECT *
                FROM products
                WHERE category_id IN (
                    SELECT category_id FROM category_tree
                );";

        $result = $this->conn->query($sql);

        $products = [];

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }

        return $products;
    }
}

/* ===============================
   EXECUTION
================================ */

$status = new Status();   // assuming already exists in project
$page = new ProductPage($conn, $status);

$page->handleAddToCart();
$products = $page->getProducts();

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="product.css">
</head>


<body>

    <?php
    $sql = "WITH RECURSIVE category_tree AS (
    SELECT category_id
    FROM categories
    WHERE category_id =" . $_GET['category_id'] . "

    UNION ALL

    SELECT c.category_id
    FROM categories c
    JOIN category_tree ct ON c.parent_id = ct.category_id
)
SELECT *
FROM products
WHERE category_id IN (SELECT category_id FROM category_tree);
";


    $result = $conn->query($sql);
    $products = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    } ?>


    <?php
    echo "<div class='product-container'>";

    foreach ($products as $row) {
        echo "<form method='POST'>
    <div class='product-card'>
       <a href='product_details.php?id={$row['product_id']}' style='text-decoration:none;color:inherit;'>
            <img src='/PracticePhp/4Feb/images/{$row['image']}' alt='image' width='80'>            
          <h3>{$row['product_name']}</h3>
            <p class='price'>₹{$row['price']}</p>
        </a>
         <input type='hidden' name='product_id' value='{$row['product_id']}'>
        
        <button  type='submit' class='add-to-cart' name='add_to_cart'
                data-id='{$row['product_id']}'>
            Add to Cart
        </button>
    </div>
    </form>
    ";
    }

    echo "</div>";

    ?>

</body>

</html>