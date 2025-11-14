<?php
/**
 * Category Model
 * Handles all category-related database operations
 */

class Category {
    private $conn;
    private $table_name = "categories";

    // Category properties - UPDATED to match your SQL schema
    public $id;  // Changed from category_id
    public $name;
    public $description;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all categories - UPDATED to match your SQL schema
     */
    public function getAllCategories() {
        try {
            $query = "SELECT 
                        id,           -- Changed from category_id
                        name, 
                        description,
                        created_at
                      FROM " . $this->table_name . " 
                      ORDER BY name ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            // Return array instead of PDOStatement
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            throw new Exception("Unable to fetch categories: " . $e->getMessage());
        }
    }

    /**
     * Get category by ID - UPDATED to match your SQL schema
     */
    public function getCategoryById($id) {
        try {
            $query = "SELECT 
                        id,           -- Changed from category_id
                        name, 
                        description,
                        created_at
                      FROM " . $this->table_name . " 
                      WHERE id = :id 
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row;
            }

            return null;
        } catch (PDOException $e) {
            error_log("Error fetching category by ID: " . $e->getMessage());
            throw new Exception("Unable to fetch category");
        }
    }

    /**
     * Get categories with product count - UPDATED to match your SQL schema
     */
    public function getCategoriesWithProductCount() {
        $query = "SELECT 
                c.id,
                c.name,
                c.description,
                COUNT(p.id) as product_count
              FROM categories c 
              LEFT JOIN products p ON c.id = p.category_id 
              GROUP BY c.id, c.name, c.description
              ORDER BY c.name";
    
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new category - UPDATED to match your SQL schema
     */
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                      (name, description) 
                      VALUES 
                      (:name, :description)";

            $stmt = $this->conn->prepare($query);

            // Sanitize input
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->description = htmlspecialchars(strip_tags($this->description));

            // Bind parameters
            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':description', $this->description);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Error creating category: " . $e->getMessage());
            throw new Exception("Unable to create category");
        }
    }

    /**
     * Update a category
     */
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET 
                        name = :name,
                        description = :description
                      WHERE 
                        id = :id";

            $stmt = $this->conn->prepare($query);

            // Sanitize input
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->description = htmlspecialchars(strip_tags($this->description));

            // Bind parameters
            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating category: " . $e->getMessage());
            throw new Exception("Unable to update category");
        }
    }

    /**
     * Delete a category - UPDATED to match your SQL schema
     */
    public function delete() {
        try {
            // First check if category has products
            $product_count = $this->getProductCount($this->id);
            if ($product_count > 0) {
                throw new Exception("Cannot delete category with existing products");
            }

            $query = "DELETE FROM " . $this->table_name . " 
                      WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting category: " . $e->getMessage());
            throw new Exception("Unable to delete category");
        }
    }

    /**
     * Get product count for a category - UPDATED to match your SQL schema
     */
    private function getProductCount($category_id) {
        try {
            $query = "SELECT COUNT(*) as product_count 
                      FROM products 
                      WHERE category_id = :category_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['product_count'];
        } catch (PDOException $e) {
            error_log("Error getting product count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Search categories by name - UPDATED to match your SQL schema
     */
    public function search($keywords) {
        try {
            $query = "SELECT 
                        id,           -- Changed from category_id
                        name, 
                        description,
                        created_at
                      FROM " . $this->table_name . " 
                      WHERE name LIKE :keywords OR description LIKE :keywords
                      ORDER BY name ASC";

            $stmt = $this->conn->prepare($query);
            $search_term = "%{$keywords}%";
            $stmt->bindParam(':keywords', $search_term);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error searching categories: " . $e->getMessage());
            throw new Exception("Unable to search categories");
        }
    }

    /**
     * Get categories 
     */
    public function getCategoriesCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Database error in getCategoriesCount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if categories table exists
     */
    public function checkTableExists() {
        try {
            $query = "SHOW TABLES LIKE '" . $this->table_name . "'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log("Error checking table existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if category name already exists
     */
    public function categoryExists($name, $exclude_id = null) {
        try {
            $query = "SELECT id 
                      FROM " . $this->table_name . " 
                      WHERE name = :name";

            if ($exclude_id) {
                $query .= " AND id != :exclude_id";
            }

            $query .= " LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $name);

            if ($exclude_id) {
                $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
            }

            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking category existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get category icon based on category name
     */
    public static function getCategoryIcon($category_name) {
        $icons = [
            'electronics' => 'fas fa-laptop',
            'fashion' => 'fas fa-tshirt',
            'home' => 'fas fa-home',
            'sports' => 'fas fa-football-ball',
            'books' => 'fas fa-book',
            'garden' => 'fas fa-seedling'
        ];

        $category_lower = strtolower($category_name);
        
        foreach ($icons as $key => $icon) {
            if (strpos($category_lower, $key) !== false) {
                return $icon;
            }
        }

        // Default icon
        return 'fas fa-tag';
    }

    /**
     * Get all categories for dropdown - Simple version
     */
    public function getCategoriesForDropdown() {
        $query = "SELECT id, name FROM categories ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>