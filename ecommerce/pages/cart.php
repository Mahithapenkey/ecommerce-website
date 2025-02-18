<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        $new_quantity = $cart_item['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$new_quantity, $user_id, $product_id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }
}

// Handle Update Quantity
if (isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity > 0) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $user_id, $product_id]);
    } else {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
    }
    header("Location: cart.php");
    exit();
}

// Handle Remove from Cart
if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    header("Location: cart.php");
    exit();
}

// Fetch user's cart items with product details
$stmt = $conn->prepare("
    SELECT cart.*, products.name, products.price, products.image 
    FROM cart 
    JOIN products ON cart.product_id = products.id 
    WHERE cart.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_price = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .cart-container {
            max-width: 600px;
            margin: auto;
            margin-top: 50px;
        }
        .cart-card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
            padding: 20px;
            background: white;
        }
        .btn-custom {
            width: 100%;
            margin-top: 10px;
        }
        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="cart-container">
        <div class="cart-card text-center">
            <h2 class="mb-4">Your Cart</h2>
            
            <?php if (empty($cart_items)): ?>
                <p class="text-muted">Your cart is empty.</p>
            <?php else: ?>
                <?php foreach ($cart_items as $item): 
                    $item_total = $item['price'] * $item['quantity'];
                    $total_price += $item_total;
                ?>
                <div class="cart-item">
                    <img src="../images/<?= htmlspecialchars($item['image']); ?>" alt="<?= htmlspecialchars($item['name']); ?>" class="item-image">
                    <div>
                        <div><strong><?= htmlspecialchars($item['name']); ?></strong></div>
                        <div>$<?= number_format($item['price'], 2); ?> x <?= $item['quantity']; ?></div>
                    </div>
                    <div>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="product_id" value="<?= $item['product_id']; ?>">
                            <input type="number" name="quantity" value="<?= $item['quantity']; ?>" min="1">
                            <button type="submit" name="update_quantity" class="btn btn-sm btn-primary">Update</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="product_id" value="<?= $item['product_id']; ?>">
                            <button type="submit" name="remove_from_cart" class="btn btn-sm btn-danger">Remove</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <p><strong>Total Price:</strong> $<?= number_format($total_price, 2); ?></p>
            <?php endif; ?>
            
            <a href="../index.php" class="btn btn-success btn-custom">Back to Shop</a>
            <a href="checkout.php" class="btn btn-success btn-custom">Proceed to Checkout</a>
        </div>
    </div>
</body>
</html>
