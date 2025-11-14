<?php
/**
 * Order Model
 * Handles all order-related database operations
 */

class Order {
    private $conn;
    private $table_name = "orders";

    // Order properties
    public $id;
    public $user_id;
    public $total;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all orders with user information
     */
    public function getAllOrdersWithUsers($status = '', $date_from = '', $date_to = '') {
        $query = "SELECT 
                o.id,
                o.total,
                o.status,
                o.created_at,
                u.user_id,
                u.full_name,
                u.email,
                u.username
              FROM orders o 
              JOIN users u ON o.user_id = u.user_id 
              WHERE 1=1";
    
        $params = [];
    
        if ($status) {
            $query .= " AND o.status = :status";
            $params[':status'] = $status;
        }
    
        if ($date_from) {
            $query .= " AND DATE(o.created_at) >= :date_from";
            $params[':date_from'] = $date_from;
        }
    
        if ($date_to) {
            $query .= " AND DATE(o.created_at) <= :date_to";
            $params[':date_to'] = $date_to;
        }
    
        $query .= " ORDER BY o.created_at DESC";
    
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get order by ID with user details
     */
    public function getOrderById($order_id) {
        try {
            $query = "SELECT o.*, u.full_name, u.email, u.username 
                      FROM " . $this->table_name . " o 
                      LEFT JOIN users u ON o.user_id = u.user_id 
                      WHERE o.id = :order_id 
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return null;
        } catch (PDOException $e) {
            error_log("Error fetching order by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get orders by user ID
     */
    public function getOrdersByUserId($user_id) {
        try {
            $query = "SELECT 
                    id,
                    user_id, 
                    total, 
                    status, 
                    created_at
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            // Add order_id field for compatibility with existing code
            foreach ($orders as &$order) {
                $order['order_id'] = 'SR-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
            }
        
            return $orders;
        } catch (PDOException $e) {
            error_log("Error fetching user orders: " . $e->getMessage());
            return [];
        }
    }

    /**
        * Get user orders (alias for getOrdersByUserId for compatibility)
    */
    public function getUserOrders($user_id) {
        return $this->getOrdersByUserId($user_id);
    }

    /**
    * Get order by ID (alias for consistency)
    */
    public function findById($order_id) {
        return $this->getOrderById($order_id);
    }

    /**
    * Get all orders (alias for getAllOrdersWithUsers with no filters)
    */
    public function getAllOrders() {    
        return $this->getAllOrdersWithUsers();
    }

    /**
     * Get order items with product details
     */
    public function getOrderItems($order_id) {
        try {
            $query = "SELECT oi.*, p.name as product_name, p.image 
                      FROM order_items oi 
                      LEFT JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = :order_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching order items: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new order
     */
    public function create() {
        try {
            $this->conn->beginTransaction();

            // Insert order
            $query = "INSERT INTO " . $this->table_name . " 
                      (user_id, total, status, created_at) 
                      VALUES 
                      (:user_id, :total, :status, NOW())";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
            $stmt->bindParam(':total', $this->total);
            $stmt->bindParam(':status', $this->status);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                $this->conn->commit();
                return true;
            }

            $this->conn->rollBack();
            return false;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error creating order: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add item to order
     */
    public function addOrderItem($product_id, $quantity, $unit_price) {
        try {
            $query = "INSERT INTO order_items 
                      (order_id, product_id, quantity, unit_price) 
                      VALUES 
                      (:order_id, :product_id, :quantity, :unit_price)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $this->id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':unit_price', $unit_price);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding order item: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($order_id, $status) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET status = :status 
                      WHERE id = :order_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating order status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get orders count
     */
    public function getOrdersCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error getting orders count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders($limit = 10) {
        $query = "SELECT 
                o.id,
                o.total,
                o.status,
                o.created_at,
                u.full_name
              FROM orders o 
              JOIN users u ON o.user_id = u.user_id 
              ORDER BY o.created_at DESC 
              LIMIT :limit";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue() {
        $query = "SELECT SUM(total) as revenue FROM orders WHERE status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['revenue'] ?? 0;
    }

    /**
     * Get orders by status
     */
    public function getOrdersByStatus($status) {
        try {
            $query = "SELECT o.*, u.full_name, u.email 
                      FROM " . $this->table_name . " o 
                      LEFT JOIN users u ON o.user_id = u.user_id 
                      WHERE o.status = :status 
                      ORDER BY o.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching orders by status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete order (and associated items via cascade)
     */
    public function delete() {
        try {
            $query = "DELETE FROM " . $this->table_name . " 
                      WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting order: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if order belongs to user
     */
    public function belongsToUser($order_id, $user_id) {
        try {
            $query = "SELECT id FROM " . $this->table_name . " 
                      WHERE id = :order_id AND user_id = :user_id 
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking order ownership: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get monthly sales data for charts
     */
    public function getMonthlySales($year = null) {
        try {
            if ($year === null) {
                $year = date('Y');
            }

            $query = "SELECT 
                        MONTH(created_at) as month, 
                        COUNT(*) as order_count,
                        SUM(total) as revenue
                      FROM " . $this->table_name . " 
                      WHERE YEAR(created_at) = :year AND status = 'completed'
                      GROUP BY MONTH(created_at) 
                      ORDER BY month ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching monthly sales: " . $e->getMessage());
            return [];
        }
    }

}
?>