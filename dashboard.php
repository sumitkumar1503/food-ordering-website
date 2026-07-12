<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_login(['admin', 'kitchen', 'delivery', 'staff']);

$page = (string) ($_GET['page'] ?? 'overview');
$role = user()['role'];
$allowedPages = [
    'admin' => ['overview','orders','menu','customers','staff','coupons','reviews','reports','settings'],
    'kitchen' => ['overview','orders'],
    'delivery' => ['overview','orders'],
    'staff' => ['overview','orders','menu'],
];
if (!in_array($page, $allowedPages[$role], true)) {
    $page = 'overview';
}

$nav = [
    'overview' => ['bi-grid-1x2-fill','Overview'],
    'orders' => ['bi-receipt-cutoff','Orders'],
    'menu' => ['bi-egg-fried','Menu'],
    'customers' => ['bi-people','Customers'],
    'staff' => ['bi-person-badge','Team & roles'],
    'coupons' => ['bi-ticket-perforated','Coupons'],
    'reviews' => ['bi-chat-heart','Reviews'],
    'reports' => ['bi-bar-chart','Reports'],
    'settings' => ['bi-gear','Settings'],
];
$titles = [
    'overview' => $role === 'admin' ? 'Good evening, ' . explode(' ', user()['name'])[0] : ($role === 'kitchen' ? 'Kitchen command centre' : ($role === 'delivery' ? 'Ready to hit the road?' : 'Front desk overview')),
    'orders' => 'Order management', 'menu' => 'Menu management', 'customers' => 'Customer community',
    'staff' => 'Team & permissions', 'coupons' => 'Offers & coupons', 'reviews' => 'Customer reviews',
    'reports' => 'Business reports', 'settings' => 'Restaurant settings',
];

function dash_status(array $order): string
{
    [$label,$color,$icon] = status_meta($order['status']);
    return '<span class="dash-status ' . e($color) . '"><i class="bi ' . e($icon) . '"></i>' . e($label) . '</span>';
}

function role_status_options(string $role, string $current): array
{
    if ($role === 'admin') {
        return ['pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled'];
    }
    $next = [
        'staff' => ['pending' => ['confirmed','cancelled'], 'confirmed' => ['cancelled']],
        'kitchen' => ['confirmed' => ['preparing'], 'preparing' => ['ready']],
        'delivery' => ['ready' => ['out_for_delivery'], 'out_for_delivery' => ['delivered']],
    ][$role][$current] ?? [];
    return array_merge([$current], $next);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(page_title($titles[$page])) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Lobster&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(base_url('assets/css/style.css?v=' . filemtime(__DIR__ . '/assets/css/style.css'))) ?>" rel="stylesheet">
</head>
<body class="dashboard-body">
<aside class="sidebar" id="sidebar">
    <a class="brand cafe-brand" href="dashboard.php"><img class="cafe-logo-mark" src="<?=e(base_url('assets/images/cafe-logo-mark.svg'))?>" alt=""><strong class="cafe-word">Cafe</strong></a>
    <div class="workspace-label">WORKSPACE</div>
    <nav><?php foreach ($nav as $key=>$item): if (!in_array($key,$allowedPages[$role],true)) continue; ?><a href="dashboard.php?page=<?= $key ?>" class="<?= $page===$key?'active':'' ?>"><i class="bi <?= $item[0] ?>"></i><span><?= $item[1] ?></span><?php if ($key==='orders'): ?><b><?= (int) query('SELECT COUNT(*) FROM orders WHERE status NOT IN ("delivered","cancelled")')->fetchColumn() ?></b><?php endif ?></a><?php endforeach ?></nav>
    <div class="sidebar-spacer"></div>
    <form method="post" action="actions.php" class="m-0 mb-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="logout"><button type="submit" class="store-link w-100 bg-transparent text-start" style="appearance:none;color:#ff8a6a;border-color:#4a302a;cursor:pointer"><i class="bi bi-box-arrow-left"></i><span>Sign out</span></button></form>
    <div class="sidebar-user"><span><?= e(strtoupper(substr(user()['name'],0,1))) ?></span><div><b><?= e(user()['name']) ?></b><small><?= e(ucfirst($role)) ?></small></div><i class="bi bi-three-dots-vertical"></i></div>
</aside>
<div class="dash-main">
    <header class="dash-header">
        <button class="sidebar-toggle" data-sidebar><i class="bi bi-list"></i></button>
        <div><h1><?= e($titles[$page]) ?><?php if ($page==='overview'): ?> <span>👋</span><?php endif ?></h1><p><?= $page==='overview' ? e(date('l, d F Y')) . ' · Here’s what’s happening at Cafe.' : 'Manage and monitor your restaurant from one place.' ?></p></div>
        <div class="dash-head-actions"><a class="head-icon" href="dashboard.php?page=orders" title="Search orders"><i class="bi bi-search"></i></a><a class="head-icon has-notice" href="dashboard.php?page=orders&status=pending" title="Pending orders"><i class="bi bi-bell"></i></a><?php if ($role==='admin'): ?><a href="dashboard.php?page=menu&add=1" class="dash-primary"><i class="bi bi-plus-lg"></i> Add menu item</a><?php endif ?></div>
    </header>
    <?php foreach (flashes() as $message): ?><div class="alert alert-<?= $message['type']==='error'?'danger':'success' ?> mx-4 d-flex align-items-center gap-2"><i class="bi bi-info-circle"></i><?= e($message['message']) ?></div><?php endforeach ?>
    <main class="dash-content">
<?php if ($page === 'overview' && $role === 'admin'):
    $today = query('SELECT COUNT(*) orders,COALESCE(SUM(total),0) revenue FROM orders WHERE DATE(created_at)=CURDATE() AND status!="cancelled"')->fetch();
    $totalCustomers = query('SELECT COUNT(*) FROM users WHERE role="customer"')->fetchColumn();
    $totalOrders = query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $totalMenuItems = (int)query('SELECT COUNT(*) FROM menu_items')->fetchColumn();
    $itemsPerPage = 5;
    $currentItemPage = max(1, min((int)ceil($totalMenuItems/$itemsPerPage), (int)($_GET['p']??1)));
    $itemOffset = ($currentItemPage-1)*$itemsPerPage;
    $overviewItems = query("SELECT m.*,c.name category_name FROM menu_items m JOIN categories c ON c.id=m.category_id ORDER BY m.id DESC LIMIT {$itemsPerPage} OFFSET {$itemOffset}")->fetchAll();
    $totalItemPages = max(1, (int)ceil($totalMenuItems/$itemsPerPage));
    $hours = preg_split('/\s*[–-]\s*/u', setting('opening_hours','10:00 AM – 11:30 PM'));
    $startTime = trim($hours[0]??'10:00 AM');
    $endTime = trim($hours[1]??'11:30 PM');
?>
<section class="reference-admin-stats">
    <a href="dashboard.php?page=orders" class="reference-stat yellow"><span><i class="bi bi-bag"></i></span><strong><?=number_format((int)$totalOrders)?></strong><b>Total Orders</b></a>
    <a href="dashboard.php?page=customers" class="reference-stat red"><span><i class="bi bi-people"></i></span><strong><?=number_format((int)$totalCustomers)?></strong><b>Total Customers</b></a>
    <a href="dashboard.php?page=settings" class="reference-stat purple"><span><i class="bi bi-bar-chart-fill"></i></span><strong>01</strong><b>Total Branches</b></a>
    <a href="dashboard.php?page=reports" class="reference-stat cyan"><span><i class="bi bi-currency-dollar"></i></span><strong><?=money($today['revenue'])?></strong><b>Today's Turn Over</b></a>
</section>

<section class="reference-item-panel">
    <div class="reference-table-wrap"><table class="reference-item-table">
        <thead><tr><th>Item Image</th><th>Item Name <i class="bi bi-chevron-expand"></i></th><th>Category Name <i class="bi bi-chevron-expand"></i></th><th>Start Day Time</th><th>End Day Time</th><th>Action <i class="bi bi-chevron-expand"></i></th></tr></thead>
        <tbody><?php foreach($overviewItems as $item):?><tr>
            <td><img src="<?=e(food_image($item['image']))?>" alt="<?=e($item['name'])?>"></td>
            <td><b><?=e($item['name'])?></b></td><td><?=e($item['category_name'])?></td><td><?=$startTime?></td><td><?=$endTime?></td>
            <td><div class="reference-actions"><a href="dashboard.php?page=menu&edit=<?=$item['id']?>" title="Edit"><i class="bi bi-pencil"></i></a><form method="post" action="actions.php" data-confirm="Archive this menu item?"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete_item"><input type="hidden" name="id" value="<?=$item['id']?>"><button type="submit" title="Archive"><i class="bi bi-trash3"></i></button></form></div></td>
        </tr><?php endforeach?></tbody>
    </table></div>
    <nav class="reference-pagination" aria-label="Menu item pages">
        <a class="<?=$currentItemPage<=1?'disabled':''?>" href="dashboard.php?page=overview&p=<?=max(1,$currentItemPage-1)?>"><i class="bi bi-chevron-left"></i></a>
        <?php for($number=1;$number<=$totalItemPages;$number++):?><a class="<?=$number===$currentItemPage?'active':''?>" href="dashboard.php?page=overview&p=<?=$number?>"><?=$number?></a><?php endfor?>
        <a class="<?=$currentItemPage>=$totalItemPages?'disabled':''?>" href="dashboard.php?page=overview&p=<?=min($totalItemPages,$currentItemPage+1)?>"><i class="bi bi-chevron-right"></i></a>
    </nav>
</section>

<?php elseif ($page === 'overview' && $role === 'kitchen'):
    $queue = query('SELECT * FROM orders WHERE status IN ("confirmed","preparing") ORDER BY FIELD(status,"preparing","confirmed"),created_at')->fetchAll();
    $kitchenAverage = (int) round((float) query('SELECT COALESCE(AVG(m.preparation_time),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN menu_items m ON m.id=oi.menu_item_id WHERE o.status IN ("confirmed","preparing")')->fetchColumn());
?>
<section class="role-banner kitchen-banner"><div><span><i class="bi bi-fire"></i></span><div><small>LIVE KITCHEN</small><h2><?= count($queue) ?> orders in your queue</h2><p>Average preparation time: <b><?=$kitchenAverage?> minutes</b></p></div></div><div class="kitchen-clock"><small>Current time</small><b data-clock><?= date('h:i A') ?></b></div></section>
<div class="queue-filters" data-queue-filters><button type="button" class="active" data-filter="all">All orders <b><?=count($queue)?></b></button><button type="button" data-filter="confirmed">New <b><?=count(array_filter($queue,fn($o)=>$o['status']==='confirmed'))?></b></button><button type="button" data-filter="preparing">Cooking <b><?=count(array_filter($queue,fn($o)=>$o['status']==='preparing'))?></b></button></div>
<section class="kitchen-grid"><?php foreach ($queue as $order): $items=query('SELECT * FROM order_items WHERE order_id=?',[$order['id']])->fetchAll(); ?><article class="kitchen-ticket <?= $order['status'] ?>"><div class="ticket-head"><div><span>#<?=e(substr($order['order_number'],-6))?></span><h3><?=e($order['customer_name'])?></h3></div><div><b><i class="bi bi-clock"></i> <?= max(1,(int)((time()-strtotime($order['created_at']))/60)) ?> min</b><small><?= e(ucfirst($order['order_type'])) ?></small></div></div><div class="ticket-items"><?php foreach ($items as $item): ?><div><b><?= $item['quantity'] ?>×</b><span><?=e($item['item_name'])?><?php if($item['instructions']):?><small><i class="bi bi-exclamation-circle"></i><?=e($item['instructions'])?></small><?php endif?></span></div><?php endforeach ?></div><form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="order_status"><input type="hidden" name="order_id" value="<?=$order['id']?>"><input type="hidden" name="back" value="dashboard.php"><input type="hidden" name="status" value="<?=$order['status']==='confirmed'?'preparing':'ready'?>"><button class="<?=$order['status']==='confirmed'?'start-cooking':'mark-ready'?>"><i class="bi <?=$order['status']==='confirmed'?'bi-fire':'bi-bag-check'?>"></i><?=$order['status']==='confirmed'?'Start cooking':'Mark as ready'?></button></form></article><?php endforeach ?></section>

<?php elseif ($page === 'overview' && $role === 'delivery'):
    $deliveries = query('SELECT * FROM orders WHERE status IN ("ready","out_for_delivery") AND (delivery_user_id IS NULL OR delivery_user_id=?) ORDER BY created_at', [user()['id']])->fetchAll();
    $riderToday = query('SELECT COUNT(*) delivered,COALESCE(AVG(TIMESTAMPDIFF(MINUTE,created_at,updated_at)),0) average_minutes FROM orders WHERE delivery_user_id=? AND status="delivered" AND DATE(updated_at)=CURDATE()', [user()['id']])->fetch();
    $riderEarnings = (int)$riderToday['delivered'] * 80;
?>
<section class="role-banner delivery-banner"><div><span><i class="bi bi-scooter"></i></span><div><small>DELIVERY HUB</small><h2><?= count($deliveries) ?> deliveries waiting</h2><p>You have completed <b><?=(int)$riderToday['delivered']?> deliveries</b> today.</p></div></div><div class="online-toggle"><i></i><span><b>Available</b><small>Receiving delivery requests</small></span></div></section>
<section class="delivery-stats"><article><span><i class="bi bi-box-seam"></i></span><div><b><?=(int)$riderToday['delivered']?></b><small>Delivered today</small></div></article><article><span><i class="bi bi-stopwatch"></i></span><div><b><?=round((float)$riderToday['average_minutes'])?> min</b><small>Average time</small></div></article><article><span><i class="bi bi-currency-rupee"></i></span><div><b><?=money($riderEarnings)?></b><small>Today's earnings</small></div></article><article><span><i class="bi bi-shield-check"></i></span><div><b><?=count($deliveries)?></b><small>Available jobs</small></div></article></section>
<div class="panel-head standalone"><div><h3>Active deliveries</h3><p>Pickup, navigate and delight the customer</p></div></div>
<section class="delivery-grid"><?php foreach($deliveries as $order):?><article class="delivery-card"><div class="delivery-card-head"><span><?=dash_status($order)?></span><b>#<?=e(substr($order['order_number'],-6))?></b></div><div class="route-line"><div><i class="bi bi-shop"></i></div><span></span><div><i class="bi bi-geo-alt-fill"></i></div></div><div class="route-details"><div><small>PICKUP FROM</small><b><?=e(setting('restaurant_name'))?></b><span><?=e(setting('restaurant_address'))?></span></div><div><small>DELIVER TO</small><b><?=e($order['customer_name'])?></b><span><?=e($order['delivery_address'])?></span></div></div><div class="delivery-contact"><a href="tel:<?=e($order['customer_phone'])?>"><i class="bi bi-telephone"></i> Call customer</a><a target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=<?=e(rawurlencode($order['delivery_address']))?>"><i class="bi bi-map"></i> Directions</a></div><form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="order_status"><input type="hidden" name="order_id" value="<?=$order['id']?>"><input type="hidden" name="back" value="dashboard.php"><input type="hidden" name="status" value="<?=$order['status']==='ready'?'out_for_delivery':'delivered'?>"><button class="dash-primary w-100 justify-content-center"><?=$order['status']==='ready'?'Start delivery':'Mark as delivered'?> <i class="bi bi-arrow-right"></i></button></form></article><?php endforeach ?></section>

<?php elseif ($page === 'orders' || ($page === 'overview' && $role === 'staff')):
    $orderSearch = trim((string) ($_GET['q'] ?? ''));
    $orderStatus = (string) ($_GET['status'] ?? '');
    $orderDate = (string) ($_GET['date'] ?? '');
    $orderWhere = ['1=1'];
    $orderParams = [];
    if ($orderSearch !== '') {
        $orderWhere[] = '(order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)';
        $needle = '%' . $orderSearch . '%';
        array_push($orderParams, $needle, $needle, $needle);
    }
    if (in_array($orderStatus, ['pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled'], true)) {
        $orderWhere[] = 'status=?';
        $orderParams[] = $orderStatus;
    }
    if ($orderDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $orderDate)) {
        $orderWhere[] = 'DATE(created_at)=?';
        $orderParams[] = $orderDate;
    }
    $orders = query('SELECT * FROM orders WHERE ' . implode(' AND ', $orderWhere) . ' ORDER BY FIELD(status,"pending","confirmed","preparing","ready","out_for_delivery","delivered","cancelled"),id DESC LIMIT 100', $orderParams)->fetchAll();
    $roleStatuses = [
        'admin' => ['pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled'],
        'staff' => ['pending','confirmed','cancelled'],
        'kitchen' => ['confirmed','preparing','ready'],
        'delivery' => ['ready','out_for_delivery','delivered'],
    ][$role];
?>
<form class="filter-bar" method="get" action="dashboard.php">
    <input type="hidden" name="page" value="orders">
    <div class="search-box"><i class="bi bi-search"></i><input name="q" value="<?=e($orderSearch)?>" placeholder="Search order or customer..."></div>
    <select name="status"><option value="">All statuses</option><?php foreach(['pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled'] as $status):?><option value="<?=$status?>" <?=$orderStatus===$status?'selected':''?>><?=ucwords(str_replace('_',' ',$status))?></option><?php endforeach?></select>
    <input type="date" name="date" value="<?=e($orderDate)?>">
    <button type="submit"><i class="bi bi-funnel"></i> Filter</button>
    <?php if($role==='admin'):?><a class="filter-export" href="export.php?<?=e(http_build_query(['q'=>$orderSearch,'status'=>$orderStatus,'date'=>$orderDate]))?>"><i class="bi bi-download"></i> Export CSV</a><?php endif?>
</form>
<section class="panel order-management"><div class="panel-head"><div><h3>All orders</h3><p><?=count($orders)?> orders shown · Filters and status updates are live</p></div><span class="live-badge"><i></i> LIVE</span></div><div class="table-responsive"><table class="dash-table"><thead><tr><th>Order</th><th>Customer</th><th>Type</th><th>Items</th><th>Payment</th><th>Total</th><th>Status</th><th>Update</th></tr></thead><tbody><?php foreach($orders as $order):?><tr><td><b>#<?=e(substr($order['order_number'],-6))?></b><small><?=date('d M · h:i A',strtotime($order['created_at']))?></small></td><td><b><?=e($order['customer_name'])?></b><small><?=e($order['customer_phone'])?></small></td><td><span class="order-type"><i class="bi <?=$order['order_type']==='delivery'?'bi-scooter':'bi-shop'?>"></i><?=e(ucfirst($order['order_type']))?></span></td><td><?= (int)query('SELECT SUM(quantity) FROM order_items WHERE order_id=?',[$order['id']])->fetchColumn()?> items</td><td><b><?=e(strtoupper($order['payment_method']))?></b><small class="text-<?=$order['payment_status']==='paid'?'success':'warning'?>"><?=e($order['payment_status'])?></small></td><td><b><?=money($order['total'])?></b></td><td><?=dash_status($order)?></td><td><?php if($role==='admin'||in_array($order['status'],$roleStatuses,true)):?><form method="post" action="actions.php" class="status-form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="order_status"><input type="hidden" name="order_id" value="<?=$order['id']?>"><input type="hidden" name="back" value="dashboard.php?page=orders"><select name="status"><?php foreach($roleStatuses as $s):?><option value="<?=$s?>" <?=$s===$order['status']?'selected':''?>><?=ucwords(str_replace('_',' ',$s))?></option><?php endforeach?></select><button type="submit"><i class="bi bi-check2"></i></button></form><?php else:?><small>No action for this role</small><?php endif?></td></tr><?php endforeach?></tbody></table></div></section>

<?php elseif ($page === 'menu'):
    $items=query('SELECT m.*,c.name category_name FROM menu_items m JOIN categories c ON c.id=m.category_id ORDER BY m.id DESC')->fetchAll();
    $categories=query('SELECT * FROM categories WHERE status=1 ORDER BY sort_order')->fetchAll();
    $editItem = isset($_GET['edit']) && $role==='admin' ? query('SELECT * FROM menu_items WHERE id=?', [(int)$_GET['edit']])->fetch() : null;
    $showItemEditor = $role==='admin' && (isset($_GET['add']) || $editItem);
?>
<?php if($showItemEditor):?>
<section class="panel editor-panel">
    <div class="panel-head"><div><h3><?=$editItem?'Edit dish':'Add a new dish'?></h3><p>Changes are saved directly to the customer menu</p></div><a href="dashboard.php?page=menu" class="btn-close"></a></div>
    <form method="post" action="actions.php" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_item"><input type="hidden" name="id" value="<?=(int)($editItem['id']??0)?>">
        <div class="col-md-8"><label>Dish name</label><input name="name" class="form-control" required value="<?=e($editItem['name']??'')?>" placeholder="e.g. Tandoori Malai Tikka"></div>
        <div class="col-md-4"><label>Category</label><select name="category_id" class="form-select"><?php foreach($categories as $c):?><option value="<?=$c['id']?>" <?=($editItem['category_id']??0)==$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach?></select></div>
        <div class="col-12"><label>Description</label><textarea name="description" class="form-control" rows="3" required placeholder="Describe flavours and ingredients..."><?=e($editItem['description']??'')?></textarea></div>
        <div class="col-md-3"><label>Price (₹)</label><input name="price" type="number" min="1" step=".01" class="form-control" required value="<?=e($editItem['price']??'')?>"></div>
        <div class="col-md-3"><label>Compare price (₹)</label><input name="compare_price" type="number" min="0" step=".01" class="form-control" value="<?=e($editItem['compare_price']??'')?>"></div>
        <div class="col-md-3"><label>Stock</label><input name="stock" type="number" min="0" class="form-control" value="<?=e($editItem['stock']??50)?>"></div>
        <div class="col-md-3"><label>Preparation (minutes)</label><input name="preparation_time" type="number" min="5" class="form-control" value="<?=e($editItem['preparation_time']??20)?>"></div>
        <div class="col-md-4"><label>Spice level</label><select name="spice_level" class="form-select"><?php foreach([0=>'Mild',1=>'Medium',2=>'Hot',3=>'Extra hot'] as $value=>$label):?><option value="<?=$value?>" <?=($editItem['spice_level']??1)===$value?'selected':''?>><?=$label?></option><?php endforeach?></select></div>
        <div class="col-md-8 check-row">
            <label><input type="checkbox" name="is_veg" <?=!isset($editItem)||$editItem['is_veg']?'checked':''?>> Vegetarian</label>
            <label><input type="checkbox" name="is_featured" <?=($editItem['is_featured']??0)?'checked':''?>> Featured</label>
            <label><input type="checkbox" name="is_bestseller" <?=($editItem['is_bestseller']??0)?'checked':''?>> Bestseller</label>
            <label><input type="checkbox" name="status" <?=!isset($editItem)||$editItem['status']?'checked':''?>> Available</label>
        </div>
        <div class="col-md-6"><label>Food image URL (optional when uploading)</label><input name="image" type="url" class="form-control" value="<?=e($editItem['image']??'')?>" placeholder="https://images.unsplash.com/..."></div>
        <div class="col-md-6"><label>Upload food image (JPG, PNG, WebP or GIF; max 2 MB)</label><input name="image_upload" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control"></div>
        <?php if($editItem):?><div class="col-12 current-image"><img src="<?=e(food_image($editItem['image']))?>" alt=""> Current image</div><?php endif?>
        <div class="col-12"><button type="submit" class="dash-primary"><i class="bi bi-check2"></i> <?=$editItem?'Update dish':'Save menu item'?></button></div>
    </form>
</section>
<?php endif?>
<section class="menu-stats"><article><span><i class="bi bi-egg-fried"></i></span><div><b><?=count($items)?></b><small>Total dishes</small></div></article><article><span><i class="bi bi-check-circle"></i></span><div><b><?=count(array_filter($items,fn($i)=>$i['status']))?></b><small>Available</small></div></article><article><span><i class="bi bi-exclamation-triangle"></i></span><div><b><?=count(array_filter($items,fn($i)=>$i['stock']<20))?></b><small>Low stock</small></div></article><article><span><i class="bi bi-star"></i></span><div><b><?=count(array_filter($items,fn($i)=>$i['is_featured']))?></b><small>Featured</small></div></article></section>
<section class="panel"><div class="panel-head"><div><h3>Your menu</h3><p>Manage pricing, stock and availability</p></div><?php if($role==='admin'):?><a href="dashboard.php?page=menu&add=1" class="dash-primary"><i class="bi bi-plus-lg"></i> Add dish</a><?php endif?></div><div class="menu-admin-grid"><?php foreach($items as $item):?><article class="<?=$item['status']?'':'item-disabled'?>"><div class="menu-admin-image"><img src="<?=e(food_image($item['image']))?>"><span class="veg-dot <?=$item['is_veg']?'':'non-veg'?>"><i></i></span><?php if($role==='admin'):?><div class="dropdown item-actions"><button type="button" data-bs-toggle="dropdown" aria-label="Menu actions"><i class="bi bi-three-dots"></i></button><div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="dashboard.php?page=menu&edit=<?=$item['id']?>"><i class="bi bi-pencil"></i> Edit dish</a><form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle_item"><input type="hidden" name="id" value="<?=$item['id']?>"><button class="dropdown-item" type="submit"><i class="bi bi-toggle-<?=$item['status']?'on':'off'?>"></i> <?=$item['status']?'Mark unavailable':'Make available'?></button></form><form method="post" action="actions.php" data-confirm="Archive this dish?"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete_item"><input type="hidden" name="id" value="<?=$item['id']?>"><button class="dropdown-item text-danger" type="submit"><i class="bi bi-archive"></i> Archive</button></form></div></div><?php endif?></div><div><small><?=e($item['category_name'])?> · <?=$item['status']?'Available':'Unavailable'?></small><h3><?=e($item['name'])?></h3><div class="rating"><i class="bi bi-star-fill"></i><?=e($item['rating'])?> <span>· <?=$item['preparation_time']?> min</span></div><div class="menu-admin-bottom"><b><?=money($item['price'])?></b><span class="<?=$item['stock']<20?'low':''?>"><?=$item['stock']?> in stock</span></div></div></article><?php endforeach?></div></section>

<?php elseif ($page === 'customers'):
    $customerSearch = trim((string)($_GET['q']??''));
    $customerParams = [];
    $customerWhere = 'u.role="customer"';
    if($customerSearch!==''){$customerWhere.=' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';$customerNeedle='%'.$customerSearch.'%';$customerParams=[$customerNeedle,$customerNeedle,$customerNeedle];}
    $customers=query('SELECT u.*,COUNT(o.id) total_orders,COALESCE(SUM(o.total),0) spent,MAX(o.created_at) last_order FROM users u LEFT JOIN orders o ON o.user_id=u.id WHERE '.$customerWhere.' GROUP BY u.id ORDER BY spent DESC',$customerParams)->fetchAll();
?>
<section class="customer-highlight"><div><span><i class="bi bi-people"></i></span><div><small>YOUR COMMUNITY</small><h2><?=number_format(count($customers))?> registered foodies</h2><p><?=count(array_filter($customers,fn($customer)=>strtotime($customer['created_at'])>=strtotime('-7 days')))?> new customers joined this week.</p></div></div><div class="community-avatars"><?php foreach(array_slice($customers,0,5) as $customer):?><span class="customer-avatar"><?=e(strtoupper(substr($customer['name'],0,1)))?></span><?php endforeach?><b><?=count($customers)?></b></div></section>
<section class="panel"><div class="panel-head"><div><h3>Customer directory</h3><p>Know and reward your regulars</p></div><form class="search-box" method="get"><input type="hidden" name="page" value="customers"><i class="bi bi-search"></i><input name="q" value="<?=e($customerSearch)?>" placeholder="Search customers..."></form></div><div class="table-responsive"><table class="dash-table"><thead><tr><th>Customer</th><th>Contact</th><th>Orders</th><th>Total spent</th><th>Last order</th><th>Segment</th></tr></thead><tbody><?php foreach($customers as $c):?><tr><td><span class="customer-avatar"><?=e(strtoupper(substr($c['name'],0,1)))?></span><b><?=e($c['name'])?></b></td><td><?=e($c['email'])?><small><?=e($c['phone'])?></small></td><td><b><?=$c['total_orders']?></b></td><td><b><?=money($c['spent'])?></b></td><td><?= $c['last_order']?date('d M Y',strtotime($c['last_order'])):'—'?></td><td><span class="segment <?=$c['spent']>500?'vip':''?>"><?=$c['spent']>500?'VIP foodie':'New customer'?></span></td></tr><?php endforeach?></tbody></table><?php if(!$customers):?><div class="empty-state"><p>No customers match your search.</p></div><?php endif?></div></section>

<?php elseif ($page === 'staff'):
    $staff=query('SELECT * FROM users WHERE role!="customer" ORDER BY role,name')->fetchAll();
    $editStaff = isset($_GET['edit']) ? query('SELECT * FROM users WHERE id=? AND role!="customer"', [(int)$_GET['edit']])->fetch() : null;
?>
<?php if(isset($_GET['add']) || $editStaff):?>
<section class="panel editor-panel"><div class="panel-head"><div><h3><?=$editStaff?'Edit team member':'Invite team member'?></h3><p>Assign role-based access and sign-in credentials</p></div><a href="dashboard.php?page=staff" class="btn-close"></a></div>
<form method="post" action="actions.php" class="row g-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_staff"><input type="hidden" name="id" value="<?=(int)($editStaff['id']??0)?>">
<div class="col-md-6"><label>Full name</label><input class="form-control" name="name" required value="<?=e($editStaff['name']??'')?>"></div><div class="col-md-6"><label>Role</label><select class="form-select" name="role"><?php foreach(['admin'=>'Administrator','kitchen'=>'Kitchen staff','delivery'=>'Delivery staff','staff'=>'Cashier / staff'] as $value=>$label):?><option value="<?=$value?>" <?=($editStaff['role']??'staff')===$value?'selected':''?>><?=$label?></option><?php endforeach?></select></div>
<div class="col-md-6"><label>Email</label><input class="form-control" type="email" name="email" required value="<?=e($editStaff['email']??'')?>"></div><div class="col-md-6"><label>Phone</label><input class="form-control" name="phone" required value="<?=e($editStaff['phone']??'')?>"></div>
<div class="col-md-6"><label><?=$editStaff?'New password (leave blank to keep current)':'Temporary password'?></label><input class="form-control" type="password" name="password" <?=$editStaff?'':'required'?> minlength="6"></div>
<div class="col-12"><button class="dash-primary" type="submit"><i class="bi bi-send-check"></i> <?=$editStaff?'Save staff changes':'Create invitation'?></button></div></form></section>
<?php endif?>
<section class="staff-grid"><?php foreach($staff as $member):?><article class="staff-card"><div class="staff-cover"></div><div class="staff-avatar"><?=e(strtoupper(substr($member['name'],0,1)))?><i class="<?=$member['status']==='active'?'':'offline'?>"></i></div><div class="dropdown staff-actions"><button type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button><div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="dashboard.php?page=staff&edit=<?=$member['id']?>"><i class="bi bi-pencil"></i> Edit details</a><?php if((int)$member['id']!==(int)user()['id']):?><form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle_staff"><input type="hidden" name="id" value="<?=$member['id']?>"><button type="submit" class="dropdown-item"><i class="bi bi-person-<?=$member['status']==='active'?'dash':'check'?>"></i> <?=$member['status']==='active'?'Deactivate':'Activate'?></button></form><?php endif?></div></div><h3><?=e($member['name'])?></h3><span><?=e(ucfirst($member['role']))?></span><div><p><i class="bi bi-envelope"></i><?=e($member['email'])?></p><p><i class="bi bi-telephone"></i><?=e($member['phone'])?></p></div><footer><span class="<?=$member['status']==='active'?'':'text-muted'?>"><i class="bi bi-circle-fill"></i> <?=e(ucfirst($member['status']))?></span><a href="dashboard.php?page=staff&edit=<?=$member['id']?>">Edit profile</a></footer></article><?php endforeach?><a href="dashboard.php?page=staff&add=1" class="add-staff"><span><i class="bi bi-person-plus"></i></span><h3>Invite team member</h3><p>Add staff and assign a role.</p><b class="dash-primary">Send invitation</b></a></section>

<?php elseif ($page === 'coupons'):
    $coupons=query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll();
    $editCoupon = isset($_GET['edit']) ? query('SELECT * FROM coupons WHERE id=?', [(int)$_GET['edit']])->fetch() : null;
?>
<?php if(isset($_GET['add']) || $editCoupon):?>
<section class="panel editor-panel"><div class="panel-head"><div><h3><?=$editCoupon?'Edit coupon':'Create a new offer'?></h3><p>The coupon becomes available at customer checkout immediately</p></div><a href="dashboard.php?page=coupons" class="btn-close"></a></div>
<form method="post" action="actions.php" class="row g-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_coupon"><input type="hidden" name="id" value="<?=(int)($editCoupon['id']??0)?>">
<div class="col-md-4"><label>Coupon code</label><input class="form-control text-uppercase" name="code" required value="<?=e($editCoupon['code']??'')?>" placeholder="FEAST20"></div><div class="col-md-4"><label>Discount type</label><select class="form-select" name="type"><option value="fixed" <?=($editCoupon['type']??'')==='fixed'?'selected':''?>>Fixed amount</option><option value="percent" <?=($editCoupon['type']??'percent')==='percent'?'selected':''?>>Percentage</option></select></div><div class="col-md-4"><label>Discount value</label><input class="form-control" type="number" min="1" step=".01" name="value" required value="<?=e($editCoupon['value']??'')?>"></div>
<div class="col-md-3"><label>Minimum order</label><input class="form-control" type="number" min="0" step=".01" name="minimum_order" value="<?=e($editCoupon['minimum_order']??0)?>"></div><div class="col-md-3"><label>Maximum discount</label><input class="form-control" type="number" min="0" step=".01" name="max_discount" value="<?=e($editCoupon['max_discount']??'')?>"></div><div class="col-md-3"><label>Valid until</label><input class="form-control" type="date" name="valid_until" required value="<?=e($editCoupon['valid_until']??date('Y-m-d',strtotime('+30 days')))?>"></div><div class="col-md-3"><label>Usage limit</label><input class="form-control" type="number" min="1" name="usage_limit" value="<?=e($editCoupon['usage_limit']??100)?>"></div>
<div class="col-12"><button class="dash-primary" type="submit"><i class="bi bi-ticket-perforated"></i> <?=$editCoupon?'Update coupon':'Create coupon'?></button></div></form></section>
<?php endif?>
<section class="coupon-admin-grid"><?php foreach($coupons as $coupon):?><article class="admin-coupon <?=$coupon['status']?'':'item-disabled'?>"><div class="coupon-side"><i class="bi bi-ticket-perforated"></i><span><?=e($coupon['code'])?></span></div><div class="coupon-content"><span class="dash-status <?=$coupon['status']?'success':'danger'?>"><i class="bi bi-<?=$coupon['status']?'check-circle':'pause-circle'?>"></i> <?=$coupon['status']?'Active':'Paused'?></span><small>SAVE</small><h2><?=$coupon['type']==='percent'?(int)$coupon['value'].'%':money($coupon['value'])?> OFF</h2><p>Minimum order <?=money($coupon['minimum_order'])?></p><div><span><i class="bi bi-calendar3"></i> Until <?=date('d M Y',strtotime($coupon['valid_until']))?></span><span><i class="bi bi-people"></i> <?=$coupon['used_count']?> / <?=$coupon['usage_limit']?> used</span></div><progress value="<?=$coupon['used_count']?>" max="<?=$coupon['usage_limit']?>"></progress></div><div class="dropdown coupon-actions"><button type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button><div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="dashboard.php?page=coupons&edit=<?=$coupon['id']?>"><i class="bi bi-pencil"></i> Edit coupon</a><form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle_coupon"><input type="hidden" name="id" value="<?=$coupon['id']?>"><button class="dropdown-item" type="submit"><i class="bi bi-pause-circle"></i> <?=$coupon['status']?'Pause':'Activate'?></button></form><form method="post" action="actions.php" data-confirm="Delete this coupon permanently?"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete_coupon"><input type="hidden" name="id" value="<?=$coupon['id']?>"><button class="dropdown-item text-danger" type="submit"><i class="bi bi-trash3"></i> Delete</button></form></div></div></article><?php endforeach?><a href="dashboard.php?page=coupons&add=1" class="new-coupon"><span><i class="bi bi-plus-lg"></i></span><h3>Create a new offer</h3><p>Delight customers with an irresistible deal.</p><b class="dash-primary">Create coupon</b></a></section>

<?php elseif ($page === 'reviews'):
    $reviews=query('SELECT r.*,u.name,m.name item_name,m.image FROM reviews r JOIN users u ON u.id=r.user_id JOIN menu_items m ON m.id=r.menu_item_id ORDER BY r.id DESC')->fetchAll();
    $reviewCount = count($reviews);
    $averageRating = $reviewCount ? array_sum(array_column($reviews,'rating')) / $reviewCount : 0;
    $positiveCount = count(array_filter($reviews, fn($review) => $review['rating'] >= 4));
    $ratingDistribution = [];
    foreach ([5,4,3,2,1] as $star) {
        $ratingDistribution[$star] = $reviewCount ? (int) round(count(array_filter($reviews, fn($review) => (int)$review['rating'] === $star)) * 100 / $reviewCount) : 0;
    }
?>
<section class="review-summary panel"><div><b><?=number_format($averageRating,1)?></b><span><?=str_repeat('★',(int)round($averageRating))?></span><small>Based on <?=$reviewCount?> reviews</small></div><div class="rating-bars"><?php foreach($ratingDistribution as $star=>$width):?><p><span><?=$star?> <i class="bi bi-star-fill"></i></span><i><b style="width:<?=$width?>%"></b></i><em><?=$width?>%</em></p><?php endforeach?></div><div><strong><?=$reviewCount?(int)round($positiveCount*100/$reviewCount):0?>%</strong><span>Positive reviews</span><small>Calculated from live data</small></div></section>
<section class="reviews-admin"><?php foreach($reviews as $review):?><article class="panel <?=$review['status']?'':'item-disabled'?>"><div class="reviewer"><span><?=e(strtoupper(substr($review['name'],0,1)))?></span><div><b><?=e($review['name'])?></b><small>Verified customer · <?=date('d M Y',strtotime($review['created_at']))?> · <?=$review['status']?'Visible':'Hidden'?></small></div><em><?=str_repeat('★',(int)$review['rating'])?></em></div><p>“<?=e($review['comment'])?>”</p><div class="review-dish"><img src="<?=e(food_image($review['image']))?>"><span>Review for <b><?=e($review['item_name'])?></b></span></div><?php if($review['admin_reply']):?><div class="admin-reply"><b><i class="bi bi-shop"></i> Cafe replied</b><p><?=e($review['admin_reply'])?></p></div><?php endif?><form id="reply-<?=$review['id']?>" class="review-reply-form <?=$review['admin_reply']?'':'d-none'?>" method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="reply_review"><input type="hidden" name="id" value="<?=$review['id']?>"><textarea class="form-control" name="reply" required maxlength="500" placeholder="Write a thoughtful public reply..."><?=e($review['admin_reply']??'')?></textarea><button class="dash-primary" type="submit">Publish reply</button></form><footer><button type="button" data-toggle-target="#reply-<?=$review['id']?>"><i class="bi bi-reply"></i> <?=$review['admin_reply']?'Edit reply':'Reply'?></button><form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="mark_review_helpful"><input type="hidden" name="id" value="<?=$review['id']?>"><input type="hidden" name="back" value="dashboard.php?page=reviews"><button type="submit"><i class="bi bi-hand-thumbs-up"></i> Helpful (<?=$review['helpful_count']?>)</button></form><form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle_review"><input type="hidden" name="id" value="<?=$review['id']?>"><button type="submit"><i class="bi bi-eye<?=$review['status']?'-slash':''?>"></i> <?=$review['status']?'Hide':'Show'?></button></form></footer></article><?php endforeach?></section>

<?php elseif ($page === 'reports'):
    $report = query('SELECT COALESCE(SUM(total),0) revenue,COUNT(*) orders,COALESCE(AVG(total),0) average_order FROM orders WHERE status!="cancelled"')->fetch();
    $customerStats = query('SELECT COUNT(*) customers,SUM(order_count>1) repeat_customers FROM (SELECT user_id,COUNT(*) order_count FROM orders WHERE user_id IS NOT NULL AND status!="cancelled" GROUP BY user_id) customer_orders')->fetch();
    $repeatRate = $customerStats['customers'] ? ((int)$customerStats['repeat_customers'] * 100 / (int)$customerStats['customers']) : 0;
    $monthly = query('SELECT DATE_FORMAT(created_at,"%b") month,COALESCE(SUM(total),0) revenue FROM orders WHERE status!="cancelled" AND created_at>=DATE_SUB(CURDATE(),INTERVAL 11 MONTH) GROUP BY YEAR(created_at),MONTH(created_at) ORDER BY YEAR(created_at),MONTH(created_at)')->fetchAll();
    $maxRevenue = max(1, ...array_map(fn($row)=>(float)$row['revenue'],$monthly));
?>
<section class="report-cards"><article><span><i class="bi bi-graph-up-arrow"></i></span><div><small>GROSS REVENUE</small><h2><?=money($report['revenue'])?></h2><b><?=$report['orders']?> completed/active orders</b></div></article><article><span><i class="bi bi-basket2"></i></span><div><small>AVERAGE ORDER VALUE</small><h2><?=money($report['average_order'])?></h2><b>Calculated from live orders</b></div></article><article><span><i class="bi bi-arrow-repeat"></i></span><div><small>REPEAT RATE</small><h2><?=number_format($repeatRate,1)?>%</h2><b><?=$customerStats['repeat_customers']??0?> repeat customers</b></div></article></section>
<section class="panel large-chart"><div class="panel-head"><div><h3>Sales performance</h3><p>Revenue from the last 12 months in your database</p></div><a href="export.php"><i class="bi bi-download"></i> Export orders</a></div><div class="report-bar-chart"><?php if(!$monthly):?><div class="empty-state"><p>No sales data yet.</p></div><?php else: foreach($monthly as $month):?><div><b><?=money($month['revenue'])?></b><i style="height:<?=max(4,(float)$month['revenue']/$maxRevenue*100)?>%"></i><span><?=e($month['month'])?></span></div><?php endforeach; endif?></div></section>

<?php elseif ($page === 'settings'): ?>
<form method="post" action="actions.php" class="settings-grid"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_settings">
<nav class="settings-nav panel"><a href="#restaurant" class="active"><i class="bi bi-shop"></i> Restaurant profile</a><a href="#hours"><i class="bi bi-clock"></i> Opening hours</a><a href="#delivery"><i class="bi bi-scooter"></i> Delivery settings</a><a href="#billing"><i class="bi bi-receipt"></i> Tax & billing</a><button class="dash-primary" type="submit"><i class="bi bi-check2"></i> Save all settings</button></nav>
<div class="settings-sections">
<section class="panel settings-form" id="restaurant"><div class="panel-head"><div><h3>Restaurant profile</h3><p>This information appears across the customer storefront</p></div></div><div class="restaurant-logo"><span><img src="<?=e(base_url('assets/images/cafe-logo-mark.svg'))?>" alt=""></span><div><b>Cafe brand</b><small>The storefront logo uses the Cafe cloche identity.</small></div></div><div class="row g-3"><div class="col-md-6"><label>Restaurant name</label><input class="form-control" name="restaurant_name" required value="<?=e(setting('restaurant_name'))?>"></div><div class="col-md-6"><label>Contact number</label><input class="form-control" name="restaurant_phone" required value="<?=e(setting('restaurant_phone'))?>"></div><div class="col-md-6"><label>Contact email</label><input class="form-control" type="email" name="restaurant_email" required value="<?=e(setting('restaurant_email','hello@cafe.test'))?>"></div><div class="col-12"><label>Restaurant address</label><textarea class="form-control" name="restaurant_address" required><?=e(setting('restaurant_address'))?></textarea></div></div></section>
<section class="panel settings-form" id="hours"><div class="panel-head"><div><h3>Opening hours</h3><p>Shown in the footer and pickup checkout</p></div></div><label>Public opening-hours text</label><input class="form-control" name="opening_hours" required value="<?=e(setting('opening_hours'))?>" placeholder="10:00 AM – 11:30 PM"></section>
<section class="panel settings-form" id="delivery"><div class="panel-head"><div><h3>Delivery rules</h3><p>These values update customer cart totals automatically</p></div></div><div class="row g-3"><div class="col-md-6"><label>Delivery fee (₹)</label><input class="form-control" type="number" min="0" step=".01" name="delivery_fee" required value="<?=e(setting('delivery_fee'))?>"></div><div class="col-md-6"><label>Free delivery above (₹)</label><input class="form-control" type="number" min="0" step=".01" name="free_delivery_threshold" required value="<?=e(setting('free_delivery_threshold','499'))?>"></div><div class="col-md-6"><label>Minimum order (₹)</label><input class="form-control" type="number" min="0" step=".01" name="minimum_order" required value="<?=e(setting('minimum_order'))?>"></div></div></section>
<section class="panel settings-form" id="billing"><div class="panel-head"><div><h3>Tax & billing</h3><p>Tax is recalculated from the discounted subtotal</p></div></div><div class="row"><div class="col-md-6"><label>Tax rate (%)</label><input class="form-control" type="number" min="0" max="100" step=".01" name="tax_rate" required value="<?=e(setting('tax_rate'))?>"></div></div><button class="dash-primary mt-4" type="submit"><i class="bi bi-check2-circle"></i> Save all changes</button></section>
</div></form>
<?php endif ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?=e(base_url('assets/js/app.js?v='.filemtime(__DIR__.'/assets/js/app.js')))?>"></script>
</body></html>
