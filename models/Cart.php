<?php
/**
 * Cart Model
 * Handles cart-related database operations and session management
 */

class Cart {
    private $conn;
    private $table_name = "cart_items";

    // Cart properties
    public $item_id;
    public $user_id;
    public $product_id;
    public $quantity;
    public $unit_price;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Add item to cart (database version)
     */
    public function addToCart($user_id, $product_id, $quantity = 1) {
        // First, get product price
        $product_query = "SELECT price FROM products WHERE id = ?";
        $product_stmt = $this->conn->prepare($product_query);
        $product_stmt->bindParam(1, $product_id);
        $product_stmt->execute();
        
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            return false; // Product not found
        }
        
        $unit_price = $product['price'];

        // Check if item already exists in cart
        $query = "SELECT item_id, quantity FROM " . $this->table_name . " 
                  WHERE user_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $product_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Update existing item quantity
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $row['quantity'] + $quantity;
            
            $query = "UPDATE " . $this->table_name . " 
                      SET quantity = ?, unit_price = ?
                      WHERE item_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $new_quantity);
            $stmt->bindParam(2, $unit_price);
            $stmt->bindParam(3, $row['item_id']);
        } else {
            // Insert new item
            $query = "INSERT INTO " . $this->table_name . " 
                      (user_id, product_id, quantity, unit_price) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $product_id);
            $stmt->bindParam(3, $quantity);
            $stmt->bindParam(4, $unit_price);
        }

        return $stmt->execute();
    }

    /**
     * Get user's cart items from database
     */
    public function getCartItems($user_id) {
        $query = "SELECT 
                    ci.item_id, ci.product_id, ci.quantity, ci.unit_price,
                    p.name, p.description, p.image
                  FROM " . $this->table_name . " ci
                  INNER JOIN products p ON ci.product_id = p.id
                  WHERE ci.user_id = ?
                  ORDER BY ci.item_id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity($cart_item_id, $quantity, $user_id) {
        if ($quantity <= 0) {
            return $this->removeFromCart($cart_item_id, $user_id);
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET quantity = ?
                  WHERE item_id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $quantity);
        $stmt->bindParam(2, $cart_item_id);
        $stmt->bindParam(3, $user_id);

        return $stmt->execute();
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($cart_item_id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE item_id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $cart_item_id);
        $stmt->bindParam(2, $user_id);

        return $stmt->execute();
    }

    /**
     * Clear user's cart
     */
    public function clearCart($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);

        return $stmt->execute();
    }

    /**
     * Get cart total for user
     */
    public function getCartTotal($user_id) {
        $query = "SELECT SUM(ci.quantity * ci.unit_price) as total
                  FROM " . $this->table_name . " ci
                  WHERE ci.user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    /**
     * Get cart item count for user
     */
    public function getCartCount($user_id) {
        $query = "SELECT SUM(quantity) as total_items 
                  FROM " . $this->table_name . " 
                  WHERE user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_items'] ?? 0;
    }

    /**
     * Check if product is already in cart
     */
    public function isInCart($user_id, $product_id) {
        $query = "SELECT item_id FROM " . $this->table_name . " 
                  WHERE user_id = ? AND product_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $product_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Session-based cart methods (for guest users)
     */

    /**
     * Add item to session cart
     */
    public static function addToSessionCart($product_id, $product_data, $quantity = 1) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $cart = $_SESSION['cart'];
        $item_key = self::findItemInSessionCart($product_id, $cart);

        if ($item_key !== false) {
            // Update existing item
            $cart[$item_key]['quantity'] += $quantity;
        } else {
            // Add new item
            $cart_item = [
                'id' => $product_id,
                'product_id' => $product_id,
                'name' => $product_data['name'],
                'price' => $product_data['price'],
                'quantity' => $quantity,
                'icon' => $product_data['icon'] ?? Product::getProductIcon($product_data['name']),
                'image' => $product_data['image'] ?? '',
                'description' => $product_data['description'] ?? ''
            ];
            $cart[] = $cart_item;
        }

        $_SESSION['cart'] = $cart;
        return true;
    }

    /**
     * Get session cart items
     */
    public static function getSessionCart() {
        return $_SESSION['cart'] ?? [];
    }

    /**
     * Update session cart item quantity
     */
    public static function updateSessionCartQuantity($product_id, $quantity) {
        if (!isset($_SESSION['cart'])) {
            return false;
        }

        $cart = $_SESSION['cart'];
        $item_key = self::findItemInSessionCart($product_id, $cart);

        if ($item_key !== false) {
            if ($quantity <= 0) {
                unset($cart[$item_key]);
            } else {
                $cart[$item_key]['quantity'] = $quantity;
            }
            
            $_SESSION['cart'] = array_values($cart); // Reindex array
            return true;
        }

        return false;
    }

    /**
     * Remove item from session cart
     */
    public static function removeFromSessionCart($product_id) {
        if (!isset($_SESSION['cart'])) {
            return false;
        }

        $cart = $_SESSION['cart'];
        $item_key = self::findItemInSessionCart($product_id, $cart);

        if ($item_key !== false) {
            unset($cart[$item_key]);
            $_SESSION['cart'] = array_values($cart); // Reindex array
            return true;
        }

        return false;
    }

    /**
     * Clear session cart
     */
    public static function clearSessionCart() {
        $_SESSION['cart'] = [];
        return true;
    }

    /**
     * Get session cart total
     */
    public static function getSessionCartTotal() {
        if (!isset($_SESSION['cart'])) {
            return 0;
        }

        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }

    /**
     * Get session cart count
     */
    public static function getSessionCartCount() {
        if (!isset($_SESSION['cart'])) {
            return 0;
        }

        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }

        return $count;
    }

    /**
     * Helper method to find item in session cart
     */
    private static function findItemInSessionCart($product_id, $cart) {
        foreach ($cart as $key => $item) {
            if ($item['product_id'] == $product_id) {
                return $key;
            }
        }
        return false;
    }

    /**
     * Sync session cart to database when user logs in
     */
    public function syncSessionToDatabase($user_id) {
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return true;
        }

        try {
            $this->conn->beginTransaction();

            // Clear existing cart items for user
            $this->clearCart($user_id);

            // Add session cart items to database
            foreach ($_SESSION['cart'] as $item) {
                $this->addToCart($user_id, $item['product_id'], $item['quantity']);
            }

            // Clear session cart
            self::clearSessionCart();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error syncing cart to database: " . $e->getMessage());
            return false;
        }
    }
}
?>