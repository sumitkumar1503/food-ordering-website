<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
verify_csrf();

$action = (string) ($_POST['action'] ?? '');
$back = (string) ($_POST['back'] ?? 'index.php');
if (!str_starts_with($back, 'index.php') && !str_starts_with($back, 'dashboard.php')) {
    $back = 'index.php';
}

try {
    switch ($action) {
        case 'login':
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $account = query('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1', [$email])->fetch();
            if (!$account || !password_verify((string) ($_POST['password'] ?? ''), $account['password'])) {
                flash('error', 'Incorrect email or password.');
                redirect('index.php?page=login');
            }
            unset($account['password']);
            session_regenerate_id(true);
            $_SESSION['user'] = $account;
            query('INSERT INTO activity_logs (user_id,action,ip_address) VALUES (?,?,?)', [
                $account['id'], 'Signed in', $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            flash('success', 'Welcome back, ' . $account['name'] . '!');
            redirect($account['role'] === 'customer' ? 'index.php' : 'dashboard.php');

        case 'register':
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone = preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($phone) < 10 || strlen($password) < 6) {
                throw new RuntimeException('Enter valid details. Password must have at least 6 characters.');
            }
            if (query('SELECT id FROM users WHERE email = ?', [$email])->fetch()) {
                throw new RuntimeException('An account with this email already exists.');
            }
            query('INSERT INTO users (name,email,password,phone) VALUES (?,?,?,?)', [
                $name, $email, password_hash($password, PASSWORD_DEFAULT), $phone,
            ]);
            flash('success', 'Account created. You can now sign in.');
            redirect('index.php?page=login');

        case 'logout':
            unset($_SESSION['user']);
            session_regenerate_id(true);
            flash('success', 'You have been signed out.');
            redirect('index.php');

        case 'add_cart':
            $id = (int) ($_POST['item_id'] ?? 0);
            $item = query('SELECT id,name,price,image FROM menu_items WHERE id = ? AND status = 1 AND stock > 0', [$id])->fetch();
            if (!$item) {
                throw new RuntimeException('This item is currently unavailable.');
            }
            $quantity = max(1, min(10, (int) ($_POST['quantity'] ?? 1)));
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['quantity'] = min(10, $_SESSION['cart'][$id]['quantity'] + $quantity);
            } else {
                $_SESSION['cart'][$id] = $item + ['quantity' => $quantity, 'instructions' => ''];
            }
            flash('success', $item['name'] . ' added to your cart.');
            redirect($back);

        case 'update_cart':
            foreach ((array) ($_POST['quantity'] ?? []) as $id => $quantity) {
                $quantity = (int) $quantity;
                if ($quantity <= 0) {
                    unset($_SESSION['cart'][(int) $id]);
                } elseif (isset($_SESSION['cart'][(int) $id])) {
                    $_SESSION['cart'][(int) $id]['quantity'] = min(10, $quantity);
                    $_SESSION['cart'][(int) $id]['instructions'] = trim((string) ($_POST['instructions'][$id] ?? ''));
                }
            }
            flash('success', 'Cart updated.');
            redirect(($_POST['next'] ?? '') === 'checkout' ? 'index.php?page=checkout' : 'index.php?page=cart');

        case 'remove_cart':
            unset($_SESSION['cart'][(int) ($_POST['item_id'] ?? 0)]);
            flash('success', 'Item removed from cart.');
            redirect('index.php?page=cart');

        case 'apply_coupon':
            $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
            $coupon = query('SELECT * FROM coupons WHERE code = ? AND status = 1 AND valid_until >= CURDATE() AND used_count < usage_limit', [$code])->fetch();
            $subtotal = cart_totals()['subtotal'];
            if (!$coupon || $subtotal < (float) $coupon['minimum_order']) {
                throw new RuntimeException('Coupon is invalid or the minimum order value is not met.');
            }
            $discount = $coupon['type'] === 'percent' ? $subtotal * ((float) $coupon['value'] / 100) : (float) $coupon['value'];
            if ($coupon['max_discount'] !== null) {
                $discount = min($discount, (float) $coupon['max_discount']);
            }
            $_SESSION['discount'] = round($discount, 2);
            $_SESSION['coupon'] = $code;
            flash('success', 'Coupon applied! You saved ' . money($discount) . '.');
            redirect('index.php?page=cart');

        case 'remove_coupon':
            unset($_SESSION['coupon'], $_SESSION['discount']);
            flash('success', 'Coupon removed from your cart.');
            redirect('index.php?page=cart');

        case 'place_order':
            require_login('customer');
            if (!cart()) {
                throw new RuntimeException('Your cart is empty.');
            }
            $name = trim((string) ($_POST['name'] ?? ''));
            $phone = preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? ''));
            $address = trim((string) ($_POST['address'] ?? ''));
            $orderType = in_array($_POST['order_type'] ?? '', ['delivery', 'pickup'], true) ? $_POST['order_type'] : 'delivery';
            $payment = in_array($_POST['payment_method'] ?? '', ['cod', 'card', 'upi'], true) ? $_POST['payment_method'] : 'cod';
            if (mb_strlen($name) < 2 || strlen($phone) < 10 || ($orderType === 'delivery' && mb_strlen($address) < 8)) {
                throw new RuntimeException('Please complete your contact and delivery details.');
            }
            foreach (cart() as $itemId => $cartItem) {
                $currentItem = query('SELECT id,name,price,image,stock,status FROM menu_items WHERE id=?', [$itemId])->fetch();
                if (!$currentItem || !$currentItem['status'] || (int) $currentItem['stock'] < (int) $cartItem['quantity']) {
                    throw new RuntimeException(($cartItem['name'] ?? 'An item') . ' is unavailable in the requested quantity.');
                }
                $_SESSION['cart'][$itemId] = array_merge($_SESSION['cart'][$itemId], [
                    'name' => $currentItem['name'], 'price' => $currentItem['price'], 'image' => $currentItem['image'],
                ]);
            }
            $totals = cart_totals();
            if ($totals['subtotal'] < (float) setting('minimum_order', '149')) {
                throw new RuntimeException('Minimum order value is ' . money(setting('minimum_order', '149')) . '.');
            }
            $number = order_number();
            db()->beginTransaction();
            query('INSERT INTO orders (order_number,user_id,customer_name,customer_phone,delivery_address,order_type,subtotal,discount,delivery_fee,tax,total,coupon_code,payment_method,payment_status,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $number, user()['id'], $name, $phone, $orderType === 'pickup' ? setting('restaurant_address') : $address,
                $orderType, $totals['subtotal'], $totals['discount'], $orderType === 'pickup' ? 0 : $totals['delivery'],
                $totals['tax'], $orderType === 'pickup' ? $totals['total'] - $totals['delivery'] : $totals['total'],
                $_SESSION['coupon'] ?? null, $payment, $payment === 'cod' ? 'pending' : 'paid', trim((string) ($_POST['notes'] ?? '')),
            ]);
            $orderId = (int) db()->lastInsertId();
            foreach (cart() as $item) {
                query('INSERT INTO order_items (order_id,menu_item_id,item_name,item_price,quantity,instructions) VALUES (?,?,?,?,?,?)', [
                    $orderId, $item['id'], $item['name'], $item['price'], $item['quantity'], $item['instructions'] ?? null,
                ]);
                $stockUpdate = query('UPDATE menu_items SET stock = stock - ? WHERE id = ? AND stock >= ?', [$item['quantity'], $item['id'], $item['quantity']]);
                if ($stockUpdate->rowCount() !== 1) {
                    throw new RuntimeException($item['name'] . ' just sold out. Please update your cart.');
                }
            }
            if (!empty($_SESSION['coupon'])) {
                query('UPDATE coupons SET used_count = used_count + 1 WHERE code = ?', [$_SESSION['coupon']]);
            }
            query('INSERT INTO notifications (user_id,title,message,icon) VALUES (?,?,?,?)', [
                user()['id'], 'Order received', "We received order {$number} and will confirm it shortly.", 'bi-bag-check',
            ]);
            db()->commit();
            $_SESSION['cart'] = [];
            unset($_SESSION['discount'], $_SESSION['coupon']);
            redirect('index.php?page=order-success&id=' . $orderId);

        case 'submit_review':
            require_login('customer');
            $orderId = (int) ($_POST['order_id'] ?? 0);
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
            $comment = trim((string) ($_POST['comment'] ?? ''));
            $valid = query('SELECT oi.id FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.id=? AND o.user_id=? AND o.status="delivered" AND oi.menu_item_id=?', [$orderId, user()['id'], $itemId])->fetch();
            if (!$valid || mb_strlen($comment) < 5) {
                throw new RuntimeException('Only delivered items can be reviewed.');
            }
            if (query('SELECT id FROM reviews WHERE user_id=? AND order_id=? AND menu_item_id=?', [user()['id'], $orderId, $itemId])->fetch()) {
                throw new RuntimeException('You have already reviewed this item.');
            }
            query('INSERT INTO reviews (user_id,menu_item_id,order_id,rating,comment) VALUES (?,?,?,?,?)', [user()['id'], $itemId, $orderId, $rating, $comment]);
            query('UPDATE menu_items SET rating=(SELECT AVG(rating) FROM reviews WHERE menu_item_id=? AND status=1) WHERE id=?', [$itemId, $itemId]);
            flash('success', 'Thank you for your review!');
            redirect('index.php?page=orders');

        case 'toggle_favorite':
            require_login('customer');
            $itemId = (int) ($_POST['item_id'] ?? 0);
            if (!query('SELECT id FROM menu_items WHERE id=? AND status=1', [$itemId])->fetch()) {
                throw new RuntimeException('That dish is unavailable.');
            }
            $favorite = query('SELECT user_id FROM favorites WHERE user_id=? AND menu_item_id=?', [user()['id'], $itemId])->fetch();
            if ($favorite) {
                query('DELETE FROM favorites WHERE user_id=? AND menu_item_id=?', [user()['id'], $itemId]);
                flash('success', 'Removed from your favorites.');
            } else {
                query('INSERT INTO favorites (user_id,menu_item_id) VALUES (?,?)', [user()['id'], $itemId]);
                flash('success', 'Saved to your favorites.');
            }
            redirect($back);

        case 'reorder':
            require_login('customer');
            $orderId = (int) ($_POST['order_id'] ?? 0);
            $orderItems = query(
                'SELECT oi.menu_item_id,oi.quantity,oi.instructions,m.name,m.price,m.image,m.stock,m.status
                 FROM order_items oi JOIN orders o ON o.id=oi.order_id
                 LEFT JOIN menu_items m ON m.id=oi.menu_item_id
                 WHERE o.id=? AND o.user_id=?',
                [$orderId, user()['id']]
            )->fetchAll();
            if (!$orderItems) {
                throw new RuntimeException('That order could not be reordered.');
            }
            $_SESSION['cart'] = [];
            $skipped = 0;
            foreach ($orderItems as $item) {
                if (!$item['menu_item_id'] || !$item['status'] || (int) $item['stock'] < 1) {
                    $skipped++;
                    continue;
                }
                $quantity = min(10, (int) $item['quantity'], (int) $item['stock']);
                $_SESSION['cart'][(int) $item['menu_item_id']] = [
                    'id' => (int) $item['menu_item_id'], 'name' => $item['name'], 'price' => $item['price'],
                    'image' => $item['image'], 'quantity' => $quantity, 'instructions' => $item['instructions'] ?? '',
                ];
            }
            unset($_SESSION['coupon'], $_SESSION['discount']);
            if (!cart()) {
                throw new RuntimeException('The dishes from that order are currently unavailable.');
            }
            flash('success', $skipped ? 'Available dishes were added; unavailable items were skipped.' : 'Your previous order is ready in the cart.');
            redirect('index.php?page=cart');

        case 'read_notifications':
            require_login();
            query('UPDATE notifications SET is_read=1 WHERE user_id=?', [user()['id']]);
            flash('success', 'Notifications marked as read.');
            redirect(user()['role'] === 'customer' ? 'index.php' : 'dashboard.php');

        case 'order_status':
            require_login(['admin', 'kitchen', 'delivery', 'staff']);
            $orderId = (int) ($_POST['order_id'] ?? 0);
            $status = (string) ($_POST['status'] ?? '');
            $allowed = [
                'admin' => ['pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled'],
                'staff' => ['confirmed','cancelled'],
                'kitchen' => ['confirmed','preparing','ready'],
                'delivery' => ['ready','out_for_delivery','delivered'],
            ];
            if (!in_array($status, $allowed[user()['role']] ?? [], true)) {
                throw new RuntimeException('That status change is not permitted.');
            }
            $order = query('SELECT user_id,order_number,status,delivery_user_id FROM orders WHERE id=?', [$orderId])->fetch();
            if (!$order) {
                throw new RuntimeException('Order not found.');
            }
            $role = user()['role'];
            $transitions = [
                'staff' => ['pending' => ['confirmed','cancelled'], 'confirmed' => ['cancelled']],
                'kitchen' => ['confirmed' => ['preparing'], 'preparing' => ['ready']],
                'delivery' => ['ready' => ['out_for_delivery'], 'out_for_delivery' => ['delivered']],
            ];
            if ($role !== 'admin' && !in_array($status, $transitions[$role][$order['status']] ?? [], true)) {
                throw new RuntimeException('That order cannot move from ' . str_replace('_', ' ', $order['status']) . ' to ' . str_replace('_', ' ', $status) . '.');
            }
            if ($role === 'delivery') {
                if ($order['delivery_user_id'] && (int) $order['delivery_user_id'] !== (int) user()['id']) {
                    throw new RuntimeException('This delivery is assigned to another rider.');
                }
                query('UPDATE orders SET status=?,delivery_user_id=?,payment_status=IF(?="delivered" AND payment_method="cod","paid",payment_status) WHERE id=?', [$status,user()['id'],$status,$orderId]);
            } else {
                query('UPDATE orders SET status=?,payment_status=IF(?="delivered" AND payment_method="cod","paid",payment_status) WHERE id=?', [$status,$status,$orderId]);
            }
            if ($order && $order['user_id']) {
                query('INSERT INTO notifications (user_id,title,message,icon) VALUES (?,?,?,?)', [
                    $order['user_id'], 'Order update', 'Order ' . $order['order_number'] . ' is now ' . str_replace('_', ' ', $status) . '.', 'bi-arrow-repeat',
                ]);
            }
            flash('success', 'Order status updated.');
            redirect($back);

        case 'save_item':
            require_login('admin');
            $id = (int) ($_POST['id'] ?? 0);
            $current = $id ? query('SELECT * FROM menu_items WHERE id=?', [$id])->fetch() : null;
            if ($id && !$current) {
                throw new RuntimeException('Menu item not found.');
            }
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $price = (float) ($_POST['price'] ?? 0);
            $imageUrl = trim((string) ($_POST['image'] ?? ''));
            if ($name === '' || $description === '' || $price <= 0) {
                throw new RuntimeException('Name, description and a valid price are required.');
            }
            $image = store_uploaded_image($_FILES['image_upload'] ?? [], $imageUrl ?: (string) ($current['image'] ?? ''));
            if ($image === '') {
                throw new RuntimeException('Provide an image URL or upload a food image.');
            }
            $values = [
                (int) ($_POST['category_id'] ?? 0), $name, $description, $price,
                ($_POST['compare_price'] ?? '') !== '' ? (float) $_POST['compare_price'] : null,
                $image, isset($_POST['is_veg']) ? 1 : 0, isset($_POST['is_featured']) ? 1 : 0,
                isset($_POST['is_bestseller']) ? 1 : 0, max(0, min(3, (int) ($_POST['spice_level'] ?? 1))),
                max(5, (int) ($_POST['preparation_time'] ?? 20)), max(0, (int) ($_POST['stock'] ?? 0)),
                isset($_POST['status']) ? 1 : 0,
            ];
            if ($id) {
                query('UPDATE menu_items SET category_id=?,name=?,description=?,price=?,compare_price=?,image=?,is_veg=?,is_featured=?,is_bestseller=?,spice_level=?,preparation_time=?,stock=?,status=? WHERE id=?', [...$values, $id]);
            } else {
                $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
                query('INSERT INTO menu_items (category_id,name,description,price,compare_price,image,is_veg,is_featured,is_bestseller,spice_level,preparation_time,stock,status,slug) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [...$values, $slug]);
            }
            flash('success', 'Menu item saved.');
            redirect('dashboard.php?page=menu');

        case 'toggle_item':
            require_login('admin');
            $id = (int) ($_POST['id'] ?? 0);
            query('UPDATE menu_items SET status=IF(status=1,0,1) WHERE id=?', [$id]);
            flash('success', 'Menu availability updated.');
            redirect('dashboard.php?page=menu');

        case 'delete_item':
            require_login('admin');
            query('UPDATE menu_items SET status=0, stock=0 WHERE id=?', [(int) ($_POST['id'] ?? 0)]);
            flash('success', 'Dish archived. Existing orders remain unchanged.');
            redirect('dashboard.php?page=menu');

        case 'save_staff':
            require_login('admin');
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone = preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? ''));
            $role = (string) ($_POST['role'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($phone) < 10 || !in_array($role, ['admin','kitchen','delivery','staff'], true)) {
                throw new RuntimeException('Enter valid staff details and select a role.');
            }
            $duplicate = query('SELECT id FROM users WHERE email=? AND id!=?', [$email, $id])->fetch();
            if ($duplicate) {
                throw new RuntimeException('That email is already in use.');
            }
            if ($id) {
                if ($password !== '' && strlen($password) < 6) {
                    throw new RuntimeException('New password must contain at least 6 characters.');
                }
                if ($password !== '') {
                    query('UPDATE users SET name=?,email=?,phone=?,role=?,password=? WHERE id=? AND role!="customer"', [$name,$email,$phone,$role,password_hash($password,PASSWORD_DEFAULT),$id]);
                } else {
                    query('UPDATE users SET name=?,email=?,phone=?,role=? WHERE id=? AND role!="customer"', [$name,$email,$phone,$role,$id]);
                }
                flash('success', 'Team member updated.');
            } else {
                if (strlen($password) < 6) {
                    throw new RuntimeException('Set a temporary password with at least 6 characters.');
                }
                query('INSERT INTO users (name,email,password,phone,role) VALUES (?,?,?,?,?)', [$name,$email,password_hash($password,PASSWORD_DEFAULT),$phone,$role]);
                flash('success', 'Team member invited. Share their email and temporary password securely.');
            }
            redirect('dashboard.php?page=staff');

        case 'toggle_staff':
            require_login('admin');
            $id = (int) ($_POST['id'] ?? 0);
            if ($id === (int) user()['id']) {
                throw new RuntimeException('You cannot deactivate your own account.');
            }
            query('UPDATE users SET status=IF(status="active","inactive","active") WHERE id=? AND role!="customer"', [$id]);
            flash('success', 'Team member status updated.');
            redirect('dashboard.php?page=staff');

        case 'save_coupon':
            require_login('admin');
            $id = (int) ($_POST['id'] ?? 0);
            $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
            $type = in_array($_POST['type'] ?? '', ['fixed','percent'], true) ? $_POST['type'] : 'fixed';
            $value = (float) ($_POST['value'] ?? 0);
            $minimum = max(0, (float) ($_POST['minimum_order'] ?? 0));
            $maximum = ($_POST['max_discount'] ?? '') !== '' ? max(0, (float) $_POST['max_discount']) : null;
            $validUntil = (string) ($_POST['valid_until'] ?? '');
            $limit = max(1, (int) ($_POST['usage_limit'] ?? 100));
            if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $code) || $value <= 0 || ($type === 'percent' && $value > 100) || strtotime($validUntil) === false) {
                throw new RuntimeException('Enter a valid coupon code, value and expiry date.');
            }
            if (query('SELECT id FROM coupons WHERE code=? AND id!=?', [$code,$id])->fetch()) {
                throw new RuntimeException('That coupon code already exists.');
            }
            if ($id) {
                query('UPDATE coupons SET code=?,type=?,value=?,minimum_order=?,max_discount=?,valid_until=?,usage_limit=? WHERE id=?', [$code,$type,$value,$minimum,$maximum,$validUntil,$limit,$id]);
            } else {
                query('INSERT INTO coupons (code,type,value,minimum_order,max_discount,valid_until,usage_limit) VALUES (?,?,?,?,?,?,?)', [$code,$type,$value,$minimum,$maximum,$validUntil,$limit]);
            }
            flash('success', 'Coupon saved and ready to use.');
            redirect('dashboard.php?page=coupons');

        case 'toggle_coupon':
            require_login('admin');
            query('UPDATE coupons SET status=IF(status=1,0,1) WHERE id=?', [(int) ($_POST['id'] ?? 0)]);
            flash('success', 'Coupon status updated.');
            redirect('dashboard.php?page=coupons');

        case 'delete_coupon':
            require_login('admin');
            query('DELETE FROM coupons WHERE id=?', [(int) ($_POST['id'] ?? 0)]);
            flash('success', 'Coupon deleted.');
            redirect('dashboard.php?page=coupons');

        case 'reply_review':
            require_login('admin');
            $id = (int) ($_POST['id'] ?? 0);
            $reply = trim((string) ($_POST['reply'] ?? ''));
            if (mb_strlen($reply) < 3 || mb_strlen($reply) > 500) {
                throw new RuntimeException('Reply must be between 3 and 500 characters.');
            }
            query('UPDATE reviews SET admin_reply=?,admin_replied_at=NOW() WHERE id=?', [$reply,$id]);
            flash('success', 'Your reply is now visible to the customer.');
            redirect('dashboard.php?page=reviews');

        case 'mark_review_helpful':
            require_login(['admin','customer','staff','kitchen','delivery']);
            $id = (int) ($_POST['id'] ?? 0);
            $marked = query('SELECT user_id FROM review_helpful WHERE user_id=? AND review_id=?', [user()['id'],$id])->fetch();
            if ($marked) {
                query('DELETE FROM review_helpful WHERE user_id=? AND review_id=?', [user()['id'],$id]);
                query('UPDATE reviews SET helpful_count=GREATEST(0,helpful_count-1) WHERE id=?', [$id]);
            } else {
                query('INSERT INTO review_helpful (user_id,review_id) VALUES (?,?)', [user()['id'],$id]);
                query('UPDATE reviews SET helpful_count=helpful_count+1 WHERE id=?', [$id]);
            }
            flash('success', 'Helpful vote updated.');
            redirect($back);

        case 'toggle_review':
            require_login('admin');
            query('UPDATE reviews SET status=IF(status=1,0,1) WHERE id=?', [(int) ($_POST['id'] ?? 0)]);
            flash('success', 'Review visibility updated.');
            redirect('dashboard.php?page=reviews');

        case 'save_settings':
            require_login('admin');
            $allowedSettings = ['restaurant_name','restaurant_phone','restaurant_email','restaurant_address','opening_hours','delivery_fee','tax_rate','minimum_order','free_delivery_threshold'];
            foreach ($allowedSettings as $key) {
                $value = trim((string) ($_POST[$key] ?? ''));
                if ($value === '') {
                    throw new RuntimeException('Complete every restaurant setting before saving.');
                }
                if (in_array($key, ['delivery_fee','tax_rate','minimum_order','free_delivery_threshold'], true) && (!is_numeric($value) || (float) $value < 0)) {
                    throw new RuntimeException('Fees, tax and order values must be valid positive numbers.');
                }
                if ($key === 'tax_rate' && (float) $value > 100) {
                    throw new RuntimeException('Tax rate cannot exceed 100%.');
                }
                query('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)', [$key,$value]);
            }
            flash('success', 'Restaurant settings saved.');
            redirect('dashboard.php?page=settings');

        default:
            throw new RuntimeException('Unknown action.');
    }
} catch (Throwable $exception) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'Something went wrong. Please try again.');
    redirect($back);
}
