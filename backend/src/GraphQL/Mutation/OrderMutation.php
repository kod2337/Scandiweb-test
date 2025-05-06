<?php

namespace App\GraphQL\Mutation;

use PDO;
use App\Model\Product;

class OrderMutation
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Place an order
     * 
     * @param array $args
     * @return array
     */
    public function placeOrder(array $args): array
    {
        $orderInput = $args['order'] ?? null;
        
        if (!$orderInput || !isset($orderInput['items']) || empty($orderInput['items'])) {
            return [
                'success' => false,
                'message' => 'Invalid order input',
                'order' => null
            ];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Create a new order
            $stmt = $this->db->prepare("
                INSERT INTO orders (total, currency, status)
                VALUES (0, :currency, 'pending')
            ");
            $stmt->bindValue(':currency', $orderInput['currency'] ?? 'USD', PDO::PARAM_STR);
            $stmt->execute();
            
            $orderId = $this->db->lastInsertId();
            $total = 0;
            
            // Process order items
            foreach ($orderInput['items'] as $item) {
                $productId = $item['productId'];
                $quantity = $item['quantity'];
                
                // Get product price
                $productModel = new Product($this->db);
                $product = $productModel->getProductById($productId);
                
                if (!$product) {
                    continue; // Skip invalid products
                }
                
                $prices = $productModel->getProductPrices($productId);
                $price = $prices[0]['amount'] ?? 0; // Default to first price
                
                // Store attributes as JSON
                $attributesJson = json_encode($item['attributes'] ?? []);
                
                // Insert order item
                $stmt = $this->db->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, unit_price, attributes)
                    VALUES (:orderId, :productId, :quantity, :unitPrice, :attributes)
                ");
                $stmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
                $stmt->bindParam(':productId', $productId, PDO::PARAM_STR);
                $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->bindParam(':unitPrice', $price, PDO::PARAM_STR);
                $stmt->bindParam(':attributes', $attributesJson, PDO::PARAM_STR);
                $stmt->execute();
                
                // Update total
                $total += $price * $quantity;
            }
            
            // Update order with correct total
            $stmt = $this->db->prepare("
                UPDATE orders SET total = :total WHERE id = :orderId
            ");
            $stmt->bindParam(':total', $total, PDO::PARAM_STR);
            $stmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->db->commit();
            
            // Return the order data
            return [
                'success' => true,
                'message' => 'Order placed successfully',
                'order' => $this->getOrderData($orderId)
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to place order: ' . $e->getMessage(),
                'order' => null
            ];
        }
    }

    /**
     * Get order data by ID
     * 
     * @param int $orderId
     * @return array
     */
    private function getOrderData(int $orderId): array
    {
        // Get order details
        $stmt = $this->db->prepare("
            SELECT * FROM orders WHERE id = :orderId
        ");
        $stmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return [];
        }
        
        // Get order items
        $stmt = $this->db->prepare("
            SELECT * FROM order_items WHERE order_id = :orderId
        ");
        $stmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format order items
        $productModel = new Product($this->db);
        $formattedItems = [];
        
        foreach ($items as $item) {
            $product = $productModel->getProductById($item['product_id']);
            
            if (!$product) {
                continue;
            }
            
            $attributes = json_decode($item['attributes'], true) ?? [];
            
            $formattedItems[] = [
                'id' => $item['id'],
                'product' => [
                    'id' => $product['id'],
                    'name' => $product['name']
                ],
                'quantity' => (int) $item['quantity'],
                'unitPrice' => (float) $item['unit_price'],
                'attributes' => $attributes
            ];
        }
        
        return [
            'id' => $order['id'],
            'total' => (float) $order['total'],
            'currency' => $order['currency'],
            'status' => $order['status'],
            'created_at' => $order['created_at'],
            'items' => $formattedItems
        ];
    }
} 