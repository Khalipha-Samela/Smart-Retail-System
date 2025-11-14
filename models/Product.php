<?php
// models/Product.php
class Product {
    private $conn;
    private $table_name = "products";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all products - returns PDOStatement for use with fetch()
     */
    public function getAllProducts() {
        $query = "SELECT 
                    p.id as product_id,  // ALIAS id to product_id
                    p.name,
                    p.description, 
                    p.price,
                    p.stock_quantity,
                    p.rating,
                    c.id as category_id,  // ALIAS id to category_id
                    c.name as category_name  // ALIAS name to category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id";
    
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Search products - returns PDOStatement
     */
    public function searchProducts($keywords, $category_id = null) {
        $query = "SELECT 
                p.id as product_id,
                p.name,
                p.description,
                p.price,
                p.stock_quantity,
                p.rating,
                p.image,
                c.name as category_name
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE (p.name LIKE :keywords OR p.description LIKE :keywords)";
    
        if ($category_id) {
            $query .= " AND p.category_id = :category_id";
        }
    
        $query .= " ORDER BY p.name";
    
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%$keywords%";
        $stmt->bindParam(':keywords', $searchTerm);
    
        if ($category_id) {
            $stmt->bindParam(':category_id', $category_id);
        }
    
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
    * Get product by ID
    */
    public function getProductById($product_id) {
        try {
            $query = "SELECT * FROM products WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return null;
        } catch (PDOException $e) {
            error_log("Error fetching product by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get products by category - returns PDOStatement
     */
    public function getProductsByCategory($category_id) {
        $query = "SELECT 
                p.id as product_id,
                p.name,
                p.description,
                p.price,
                p.stock_quantity,
                p.rating,
                p.image,
                c.name as category_name
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.category_id = :category_id
              ORDER BY p.name";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total number of products
     * @return int
     */
    public function getProductsCount() {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting products count: " . $e->getMessage());
            return 0;
        }
    }

    /**
    * Get all products with category information for admin dashboard
    * @return array
    */
    public function getAllProductsWithCategory() {
        $query = "SELECT 
                p.id as product_id,
                p.name,
                p.description,
                p.price,
                p.stock_quantity,
                p.rating,
                p.image,
                p.created_at,
                c.id as category_id,
                c.name as category_name
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              ORDER BY p.created_at DESC";
    
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get current stock quantity for a product
     */
    public function reduceStock($product_id, $quantity) {
        $query = "UPDATE products 
              SET stock_quantity = stock_quantity - :qty 
              WHERE product_id = :id AND stock_quantity >= :qty";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':qty', $quantity);
        $stmt->bindParam(':id', $product_id);

        return $stmt->execute();
    }

    /**
     * Get product icon based on name or category
     */
    public static function getProductIcon($productName) {
        $name = strtolower($productName);
    
        if (strpos($name, 'phone') !== false) return 'fas fa-mobile-alt';
        if (strpos($name, 'laptop') !== false || strpos($name, 'computer') !== false) return 'fas fa-laptop';
        if (strpos($name, 'headphone') !== false) return 'fas fa-headphones';
        if (strpos($name, 'watch') !== false) return 'fas fa-clock';
        if (strpos($name, 'camera') !== false) return 'fas fa-camera';
        if (strpos($name, 'game') !== false) return 'fas fa-gamepad';
        if (strpos($name, 'shoe') !== false) return 'fas fa-shoe-prints';
        if (strpos($name, 'bag') !== false || strpos($name, 'backpack') !== false) return 'fas fa-briefcase';
        if (strpos($name, 'speaker') !== false) return 'fas fa-volume-up';
        if (strpos($name, 'mouse') !== false) return 'fas fa-mouse';
        if (strpos($name, 'hub') !== false || strpos($name, 'plug') !== false) return 'fas fa-plug';
        if (strpos($name, 'shirt') !== false || strpos($name, 'clothing') !== false) return 'fas fa-tshirt';
        if (strpos($name, 'book') !== false) return 'fas fa-book';
        if (strpos($name, 'sport') !== false) return 'fas fa-football-ball';
        if (strpos($name, 'garden') !== false) return 'fas fa-leaf';
    
        return 'fas fa-box'; // Default icon
    }
}
?>