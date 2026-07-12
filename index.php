<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$page = (string) ($_GET['page'] ?? 'home');
$search = trim((string) ($_GET['q'] ?? ''));
$categoryId = (int) ($_GET['category'] ?? 0);

$customerOnlyPages = ['customer-dashboard', 'checkout', 'orders', 'favorites', 'order-success', 'track'];
if (in_array($page, $customerOnlyPages, true)) {
    if (!logged_in()) {
        require_login();
    }
    if (!has_role('customer')) {
        flash('error', 'That page is available to customer accounts only.');
        redirect('dashboard.php');
    }
}

function storefront_header(string $title = ''): void
{
    global $page;
    $unread = logged_in() ? (int) query('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [user()['id']])->fetchColumn() : 0;
    $customerLayout = has_role('customer') && in_array($page, ['customer-dashboard','menu','item','cart','checkout','orders','favorites','order-success','track'], true);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f4511e">
        <title><?= e(page_title($title)) ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Lobster&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="<?= e(base_url('assets/css/style.css?v=' . filemtime(__DIR__ . '/assets/css/style.css'))) ?>" rel="stylesheet">
    </head>
    <body class="<?= $customerLayout?'customer-area-page ':'' ?><?= $page==='customer-dashboard'?'customer-dashboard-page ':'' ?><?= $page==='home'?'public-delivery-home':'' ?>">
    <div class="announcement"><i class="bi bi-lightning-charge-fill"></i> Free delivery above ₹499 <span></span> Use <b>WELCOME100</b> for ₹100 off</div>
    <nav class="navbar navbar-expand-lg sticky-top glass-nav">
        <div class="container">
            <a class="navbar-brand brand cafe-brand" href="<?= e(base_url('index.php')) ?>"><img class="cafe-logo-mark" src="<?=e(base_url('assets/images/cafe-logo-mark.svg'))?>" alt=""><strong class="cafe-word">Cafe</strong></a>
            <button class="navbar-toggler border-0 shadow-none" data-bs-toggle="collapse" data-bs-target="#mainNav"><i class="bi bi-list fs-2"></i></button>
            <div class="collapse navbar-collapse" id="mainNav">
                <form class="nav-search mx-lg-auto my-3 my-lg-0" action="index.php">
                    <input type="hidden" name="page" value="menu">
                    <i class="bi bi-search"></i>
                    <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search biryani, pizza, desserts...">
                </form>
                <ul class="navbar-nav align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link <?= $page === 'menu' ? 'active' : '' ?>" href="<?= e(base_url('index.php?page=menu')) ?>">Explore Menu</a></li>
                    <?php if (logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-icon" data-bs-toggle="dropdown" href="#"><i class="bi bi-bell"></i><?php if ($unread): ?><b><?= $unread ?></b><?php endif ?></a>
                            <div class="dropdown-menu dropdown-menu-end notification-menu p-2">
                                <?php foreach (query('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 4', [user()['id']])->fetchAll() as $note): ?>
                                    <div class="notification-item"><span><i class="bi <?= e($note['icon']) ?>"></i></span><div><strong><?= e($note['title']) ?></strong><small><?= e($note['message']) ?></small></div></div>
                                <?php endforeach ?>
                                <?php if($unread):?><form method="post" action="actions.php" class="p-2"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="read_notifications"><button type="submit" class="btn btn-sm btn-light w-100">Mark all as read</button></form><?php endif?>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="profile-chip" data-bs-toggle="dropdown" href="#"><span><?= e(strtoupper(substr(user()['name'], 0, 1))) ?></span><div><?= e(explode(' ', user()['name'])[0]) ?><small><?= e(ucfirst(user()['role'])) ?></small></div><i class="bi bi-chevron-down"></i></a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (user()['role'] !== 'customer'): ?><li><a class="dropdown-item" href="<?= e(base_url('dashboard.php')) ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li><?php endif ?>
                                <?php if(user()['role']==='customer'):?><li><a class="dropdown-item" href="<?=e(base_url('index.php?page=customer-dashboard'))?>"><i class="bi bi-grid"></i> Dashboard</a></li><li><a class="dropdown-item" href="<?= e(base_url('index.php?page=orders')) ?>"><i class="bi bi-bag"></i> My orders</a></li><li><a class="dropdown-item" href="<?=e(base_url('index.php?page=favorites'))?>"><i class="bi bi-heart"></i> Favorites</a></li><?php endif?>
                                <li><hr class="dropdown-divider"></li>
                                <li><form method="post" action="<?= e(base_url('actions.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="logout"><button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Sign out</button></form></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-outline-dark rounded-pill px-4" href="<?= e(base_url('index.php?page=login')) ?>">Sign in</a></li>
                    <?php endif ?>
                    <li class="nav-item"><a class="cart-button" href="<?= e(base_url('index.php?page=cart')) ?>"><i class="bi bi-bag-heart"></i><span>Cart</span><b><?= cart_count() ?></b></a></li>
                </ul>
            </div>
        </div>
    </nav>
    <?php foreach (flashes() as $message): ?>
        <div class="toast-flash alert alert-<?= $message['type'] === 'error' ? 'danger' : 'success' ?> shadow"><i class="bi bi-<?= $message['type'] === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i> <?= e($message['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endforeach ?>
    <?php if($customerLayout):?>
    <aside class="customer-dashboard-sidebar persistent-customer-sidebar">
        <a class="brand cafe-brand" href="index.php?page=customer-dashboard"><img class="cafe-logo-mark" src="<?=e(base_url('assets/images/cafe-logo-mark.svg'))?>" alt=""><strong class="cafe-word">Cafe</strong></a>
        <div class="customer-sidebar-profile"><span><?=e(strtoupper(substr(user()['name'],0,1)))?></span><div><b><?=e(user()['name'])?></b><small>Customer account</small></div></div>
        <nav>
            <a class="<?=$page==='customer-dashboard'?'active':''?>" href="index.php?page=customer-dashboard"><i class="bi bi-grid-fill"></i><span>Dashboard</span></a>
            <a class="<?=in_array($page,['menu','item'],true)?'active':''?>" href="index.php?page=menu"><i class="bi bi-egg-fried"></i><span>Explore Menu</span></a>
            <a class="<?=in_array($page,['cart','checkout'],true)?'active':''?>" href="index.php?page=cart"><i class="bi bi-bag"></i><span>My Cart</span><b><?=cart_count()?></b></a>
            <a class="<?=in_array($page,['orders','track','order-success'],true)?'active':''?>" href="index.php?page=orders"><i class="bi bi-receipt"></i><span>My Orders</span></a>
            <a class="<?=$page==='favorites'?'active':''?>" href="index.php?page=favorites"><i class="bi bi-heart"></i><span>Favorites</span></a>
        </nav>
        <div class="customer-sidebar-spacer"></div>
        <div class="customer-sidebar-offer"><i class="bi bi-gift"></i><b>Save 20% today</b><small>Use code FEAST20</small></div>
        <form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="logout"><button type="submit"><i class="bi bi-box-arrow-left"></i> Sign out</button></form>
    </aside>
    <?php endif?>
    <main>
    <?php
}

function storefront_footer(): void
{
    ?>
    </main>
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5"><a class="brand cafe-brand text-white" href="index.php"><img class="cafe-logo-mark" src="<?=e(base_url('assets/images/cafe-logo-mark.svg'))?>" alt=""><strong class="cafe-word">Cafe</strong></a><p class="mt-3">Big flavours, honest ingredients and happiness delivered to your doorstep.</p><div class="socials"><a target="_blank" rel="noopener" href="https://www.instagram.com/" aria-label="Instagram"><i class="bi bi-instagram"></i></a><a target="_blank" rel="noopener" href="https://www.youtube.com/" aria-label="YouTube"><i class="bi bi-youtube"></i></a><a target="_blank" rel="noopener" href="https://www.facebook.com/" aria-label="Facebook"><i class="bi bi-facebook"></i></a><a target="_blank" rel="noopener" href="https://x.com/" aria-label="X"><i class="bi bi-twitter-x"></i></a></div></div>
                <div class="col-6 col-lg-2"><h6>Discover</h6><a href="index.php?page=menu">Our menu</a><a href="index.php?page=menu&sort=rating">Best sellers</a><a href="index.php?page=cart">Apply offers</a></div>
                <div class="col-6 col-lg-2"><h6>Help</h6><a href="tel:<?=e(setting('restaurant_phone'))?>">Contact us</a><a href="mailto:<?=e(setting('restaurant_email','hello@cafe.test'))?>?subject=Cafe%20help">Email support</a><a target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=<?=e(rawurlencode(setting('restaurant_address')))?>">Delivery area</a></div>
                <div class="col-lg-3"><h6>We are open</h6><p><i class="bi bi-clock"></i> <?= e(setting('opening_hours')) ?></p><p><i class="bi bi-telephone"></i> <?= e(setting('restaurant_phone')) ?></p></div>
            </div>
            <hr><div class="footer-bottom"><span>© <?= date('Y') ?> Cafe</span><span>Made with <i class="bi bi-heart-fill text-danger"></i> for food lovers</span></div>
        </div>
    </footer>
    <nav class="mobile-nav">
        <a href="index.php" class="<?= ($_GET['page'] ?? 'home') === 'home' ? 'active' : '' ?>"><i class="bi bi-house"></i><span>Home</span></a>
        <a href="index.php?page=menu"><i class="bi bi-grid"></i><span>Menu</span></a>
        <a href="index.php?page=cart" class="mobile-cart"><i class="bi bi-bag"></i><b><?= cart_count() ?></b><span>Cart</span></a>
        <a href="index.php?page=orders"><i class="bi bi-receipt"></i><span>Orders</span></a>
        <a href="<?=logged_in()?(user()['role']==='customer'?'index.php?page=orders':'dashboard.php'):'index.php?page=login'?>"><i class="bi bi-person"></i><span>Account</span></a>
    </nav>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(base_url('assets/js/app.js?v=' . filemtime(__DIR__ . '/assets/js/app.js'))) ?>"></script>
    </body></html>
    <?php
}

function food_card(array $item): void
{
    $isFavorite = logged_in() && has_role('customer')
        ? (bool) query('SELECT user_id FROM favorites WHERE user_id=? AND menu_item_id=?', [user()['id'], $item['id']])->fetch()
        : false;
    ?>
    <article class="food-card">
        <div class="food-image">
            <img src="<?= e(food_image($item['image'])) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
            <span class="veg-dot <?= $item['is_veg'] ? '' : 'non-veg' ?>"><i></i></span>
            <form method="post" action="actions.php" class="favorite-form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle_favorite"><input type="hidden" name="item_id" value="<?=(int)$item['id']?>"><input type="hidden" name="back" value="<?=e('index.php?' . ($_SERVER['QUERY_STRING'] ?? ''))?>"><button class="heart <?=$isFavorite?'active':''?>" type="submit" aria-label="<?=$isFavorite?'Remove from':'Add to'?> favorites"><i class="bi bi-heart<?=$isFavorite?'-fill':''?>"></i></button></form>
            <?php if ($item['is_bestseller']): ?><span class="bestseller"><i class="bi bi-award-fill"></i> Bestseller</span><?php endif ?>
        </div>
        <div class="food-body">
            <div class="food-meta"><span><i class="bi bi-star-fill"></i> <?= e($item['rating']) ?></span><span><i class="bi bi-clock"></i> <?= (int) $item['preparation_time'] ?> min</span></div>
            <h3><a href="index.php?page=item&id=<?= (int) $item['id'] ?>"><?= e($item['name']) ?></a></h3>
            <p><?= e($item['description']) ?></p>
            <div class="food-bottom"><div class="price"><?= money($item['price']) ?><?php if ($item['compare_price']): ?><del><?= money($item['compare_price']) ?></del><?php endif ?></div>
                <form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add_cart"><input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="back" value="<?= e('index.php?' . ($_SERVER['QUERY_STRING'] ?? '')) ?>"><button class="add-btn"><i class="bi bi-plus-lg"></i> Add</button></form>
            </div>
        </div>
    </article>
    <?php
}

storefront_header(match ($page) {
    'menu' => 'Explore our menu', 'cart' => 'Your cart', 'checkout' => 'Checkout',
    'orders' => 'My orders', 'login' => 'Welcome back', 'register' => 'Create account',
    'favorites' => 'My favorites', 'customer-dashboard' => 'Customer dashboard',
    default => '',
});

if ($page === 'home'):
    $categories = query('SELECT * FROM categories WHERE status=1 ORDER BY sort_order LIMIT 4')->fetchAll();
    $featured = query('SELECT * FROM menu_items WHERE status=1 ORDER BY is_bestseller DESC,rating DESC LIMIT 4')->fetchAll();
?>
<header class="delivery-home-nav">
    <a class="delivery-logo" href="index.php"><span><i class="bi bi-dice-5-fill"></i></span><b>Food<br>Delivery</b></a>
    <nav><a class="active" href="index.php">Home</a><a href="index.php?page=menu">Online Order</a><a href="index.php?page=menu">Menu</a><a href="#delivery-categories">Reservation</a><a href="mailto:<?=e(setting('restaurant_email','hello@cafe.test'))?>">Contact</a></nav>
    <div class="delivery-nav-icons"><a href="index.php?page=menu"><i class="bi bi-search"></i></a><a href="<?=logged_in()?(has_role('customer')?'index.php?page=customer-dashboard':'dashboard.php'):'index.php?page=login'?>"><i class="bi bi-person-fill"></i></a><a href="index.php?page=cart"><i class="bi bi-bag-fill"></i><em><?=cart_count()?></em></a><a class="delivery-phone" href="tel:<?=e(setting('restaurant_phone'))?>"><i class="bi bi-telephone-fill"></i> +1 123 456 7890</a></div>
</header>
<section class="delivery-hero">
    <div class="delivery-hero-copy">
        <div class="delivery-slider-dots"><i></i><i class="active"></i><i></i><i></i><i></i><span class="bi bi-chevron-down"></span></div>
        <h1>We Deliver The<br>Taste Of Life</h1>
        <p>Get It Delivered Right To Your Door!</p>
        <form action="index.php" method="get" role="search"><input type="hidden" name="page" value="menu"><input type="search" name="q" required minlength="2" autocomplete="off" placeholder="Enter Your Food Name..." aria-label="Search food"><button type="submit">Find Food</button></form>
    </div>
    <div class="delivery-hero-art"><img src="<?=e(base_url('assets/images/food-delivery-scooter.png'))?>" alt="Food delivery scooter"><div class="delivery-socials"><a href="#"><i class="bi bi-facebook"></i></a><a href="#"><i class="bi bi-twitter"></i></a><a href="#"><i class="bi bi-linkedin"></i></a><a href="#"><i class="bi bi-youtube"></i></a><a href="#"><i class="bi bi-instagram"></i></a></div></div>
</section>
<section class="delivery-category-section" id="delivery-categories">
    <div class="delivery-section-title"><h2>Browse Food Category</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor<br>incididunt ut labore et dolore magna aliqua.</p></div>
    <div class="delivery-food-grid">
        <?php foreach($featured as $index=>$item):?>
        <article class="delivery-food-card">
            <div class="delivery-food-photo"><?php if($item['is_bestseller']):?><span>Recommended</span><?php endif?><img src="<?=e(food_image($item['image']))?>" alt="<?=e($item['name'])?>"></div>
            <div class="delivery-card-stars">★★★★★</div><h3><?=e($item['name'])?></h3><b><?=money($item['price'])?></b>
            <form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="add_cart"><input type="hidden" name="item_id" value="<?=$item['id']?>"><input type="hidden" name="back" value="index.php"><button class="<?=$index%2?'yellow':''?>">Add To Cart</button></form>
        </article>
        <?php endforeach?>
    </div>
</section>

<?php elseif ($page === 'customer-dashboard'):
    $dashboardCategories = query('SELECT * FROM categories WHERE status=1 ORDER BY sort_order LIMIT 6')->fetchAll();
    $dashboardPopular = query('SELECT * FROM menu_items WHERE status=1 ORDER BY is_bestseller DESC,rating DESC LIMIT 3')->fetchAll();
    $dashboardOrders = query('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 3', [user()['id']])->fetchAll();
?>
<section class="replica-dashboard">
    <div class="container">
        <div class="replica-voucher">
            <div class="replica-voucher-copy">
                <span>LIMITED TIME OFFER</span>
                <h1>Get Discount Voucher<br>Up To 20%</h1>
                <p>Enjoy more of your favourite flavours for less. Use code <b>FEAST20</b> on your next order.</p>
                <a href="index.php?page=menu">Order now <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="replica-voucher-art"><i class="circle one"></i><i class="circle two"></i><img src="https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=700&q=90" alt="Discount feast"></div>
        </div>

        <div class="replica-section-head"><h2>Category</h2><a href="index.php?page=menu">View all <i class="bi bi-chevron-right"></i></a></div>
        <div class="replica-categories">
            <?php foreach($dashboardCategories as $category):?>
                <a href="index.php?page=menu&category=<?=$category['id']?>" class="replica-category">
                    <span><i class="bi <?=e($category['icon'])?>"></i></span>
                    <b><?=e($category['name'])?></b>
                </a>
            <?php endforeach?>
        </div>

        <div class="replica-section-head"><h2>Popular Dishes</h2><a href="index.php?page=menu">View all <i class="bi bi-chevron-right"></i></a></div>
        <div class="replica-popular">
            <?php foreach($dashboardPopular as $item):
                $discountPercent = $item['compare_price'] ? max(0, (int)round((1-(float)$item['price']/(float)$item['compare_price'])*100)) : 0;
                $favorite = (bool)query('SELECT user_id FROM favorites WHERE user_id=? AND menu_item_id=?',[user()['id'],$item['id']])->fetch();
            ?>
            <article class="replica-food-card">
                <div class="replica-food-image">
                    <?php if($discountPercent):?><span class="replica-discount"><?=$discountPercent?>% OFF</span><?php endif?>
                    <form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle_favorite"><input type="hidden" name="item_id" value="<?=$item['id']?>"><input type="hidden" name="back" value="index.php?page=customer-dashboard"><button type="submit" aria-label="Favorite"><i class="bi bi-heart<?=$favorite?'-fill':''?>"></i></button></form>
                    <a href="index.php?page=item&id=<?=$item['id']?>"><img src="<?=e(food_image($item['image']))?>" alt="<?=e($item['name'])?>"></a>
                </div>
                <div class="replica-food-body">
                    <div class="replica-stars"><?=str_repeat('★',5)?><small><?=e($item['rating'])?></small></div>
                    <h3><?=e($item['name'])?></h3>
                    <div><strong><?=money($item['price'])?></strong><form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="add_cart"><input type="hidden" name="item_id" value="<?=$item['id']?>"><input type="hidden" name="back" value="index.php?page=customer-dashboard"><button type="submit" aria-label="Add to cart"><i class="bi bi-plus-lg"></i></button></form></div>
                </div>
            </article>
            <?php endforeach?>
        </div>

        <div class="replica-section-head recent"><h2>Recent Order</h2><a href="index.php?page=orders">View all <i class="bi bi-chevron-right"></i></a></div>
        <div class="replica-orders">
            <?php foreach($dashboardOrders as $order): [$statusLabel,$statusColor]=status_meta($order['status']);?>
                <a href="index.php?page=track&id=<?=$order['id']?>" class="replica-order">
                    <span><i class="bi bi-bag-check"></i></span><div><b>Order #<?=e(substr($order['order_number'],-6))?></b><small><?=date('d M Y · h:i A',strtotime($order['created_at']))?></small></div><strong><?=money($order['total'])?></strong><em class="<?=$statusColor?>"><?=$statusLabel?></em><i class="bi bi-chevron-right"></i>
                </a>
            <?php endforeach?>
            <?php if(!$dashboardOrders):?><div class="replica-no-orders">No recent orders yet. <a href="index.php?page=menu">Order your first meal</a></div><?php endif?>
        </div>
    </div>
</section>

<?php elseif ($page === 'menu'):
    $params = [];
    $where = ['m.status=1'];
    if ($categoryId) { $where[] = 'm.category_id=?'; $params[] = $categoryId; }
    if ($search !== '') { $where[] = '(m.name LIKE ? OR m.description LIKE ? OR c.name LIKE ?)'; $needle = "%{$search}%"; array_push($params, $needle, $needle, $needle); }
    if (isset($_GET['veg'])) $where[] = 'm.is_veg=1';
    $sort = (string) ($_GET['sort'] ?? 'popular');
    $orderBy = match ($sort) {
        'price-low' => 'm.price ASC',
        'price-high' => 'm.price DESC',
        'rating' => 'm.rating DESC',
        'newest' => 'm.id DESC',
        default => 'm.is_bestseller DESC,m.rating DESC',
    };
    $items = query('SELECT m.* FROM menu_items m JOIN categories c ON c.id=m.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $orderBy, $params)->fetchAll();
    $categories = query('SELECT * FROM categories WHERE status=1 ORDER BY sort_order')->fetchAll();
?>
<section class="page-hero menu-hero"><div class="container"><span class="kicker">EXPLORE THE MENU</span><h1>Made fresh. Made memorable.</h1><p>Discover bold flavours and comforting classics, prepared just for you.</p></div></section>
<section class="menu-area"><div class="container">
    <div class="menu-toolbar">
        <div class="category-pills"><a href="index.php?page=menu" class="<?= !$categoryId ? 'active' : '' ?>"><i class="bi bi-grid"></i> All</a><?php foreach ($categories as $category): ?><a href="index.php?page=menu&category=<?= $category['id'] ?>" class="<?= $categoryId === (int) $category['id'] ? 'active' : '' ?>"><i class="bi <?= e($category['icon']) ?>"></i> <?= e($category['name']) ?></a><?php endforeach ?></div>
        <a class="filter-pill" href="index.php?page=menu&veg=1"><span class="veg-dot"><i></i></span> Veg only</a>
    </div>
    <div class="results-head"><div><h2><?= $search ? 'Results for “' . e($search) . '”' : ($categoryId ? e(array_column($categories, 'name', 'id')[$categoryId] ?? 'Menu') : 'All delicious dishes') ?></h2><small><?= count($items) ?> dishes available</small></div><form method="get" action="index.php"><input type="hidden" name="page" value="menu"><?php if($categoryId):?><input type="hidden" name="category" value="<?=$categoryId?>"><?php endif?><?php if($search!==''):?><input type="hidden" name="q" value="<?=e($search)?>"><?php endif?><?php if(isset($_GET['veg'])):?><input type="hidden" name="veg" value="1"><?php endif?><select class="sort-btn" name="sort" onchange="this.form.submit()"><option value="popular" <?=$sort==='popular'?'selected':''?>>Sort: Popular</option><option value="rating" <?=$sort==='rating'?'selected':''?>>Highest rated</option><option value="price-low" <?=$sort==='price-low'?'selected':''?>>Price: Low to high</option><option value="price-high" <?=$sort==='price-high'?'selected':''?>>Price: High to low</option><option value="newest" <?=$sort==='newest'?'selected':''?>>Newest first</option></select></form></div>
    <?php if ($items): ?><div class="food-grid"><?php foreach ($items as $item) food_card($item); ?></div><?php else: ?><div class="empty-state"><span><i class="bi bi-search"></i></span><h3>No dishes found</h3><p>Try another search or explore all our delicious categories.</p><a class="btn-primary-custom" href="index.php?page=menu">View all dishes</a></div><?php endif ?>
</div></section>

<?php elseif ($page === 'item'):
    $item = query('SELECT m.*,c.name category_name FROM menu_items m JOIN categories c ON c.id=m.category_id WHERE m.id=? AND m.status=1', [(int) ($_GET['id'] ?? 0)])->fetch();
    if (!$item):
        echo '<div class="empty-state"><h2>Dish not found</h2></div>';
    else:
        $reviews = query('SELECT r.*,u.name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.menu_item_id=? AND r.status=1 ORDER BY r.id DESC', [$item['id']])->fetchAll();
?>
<section class="detail-section"><div class="container"><div class="detail-grid">
    <div class="detail-image"><img src="<?= e(food_image($item['image'])) ?>"><?php if($item['is_featured']):?><span class="detail-tag"><i class="bi bi-stars"></i> Chef recommended</span><?php endif?></div>
    <div class="detail-copy"><nav><a href="index.php">Home</a><i class="bi bi-chevron-right"></i><a href="index.php?page=menu">Menu</a><i class="bi bi-chevron-right"></i><span><?= e($item['category_name']) ?></span></nav><span class="veg-label <?= !$item['is_veg'] ? 'non-veg-label' : '' ?>"><i></i> <?= $item['is_veg'] ? 'VEGETARIAN' : 'NON-VEGETARIAN' ?></span><h1><?= e($item['name']) ?></h1><div class="detail-rating"><b><i class="bi bi-star-fill"></i> <?= e($item['rating']) ?></b><span><?= count($reviews) ?> verified ratings</span><span><i class="bi bi-clock"></i> <?= $item['preparation_time'] ?>–<?= $item['preparation_time'] + 10 ?> min</span></div><p><?= e($item['description']) ?></p>
        <div class="detail-notes"><div><span><i class="bi bi-fire"></i></span><b>Spice level</b><small><?= str_repeat('🌶', (int) $item['spice_level']) ?: 'Mild' ?></small></div><div><span><i class="bi bi-patch-check"></i></span><b>Freshly made</b><small>No preservatives</small></div></div>
        <form method="post" action="actions.php" class="detail-add"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add_cart"><input type="hidden" name="item_id" value="<?= $item['id'] ?>"><input type="hidden" name="back" value="index.php?page=item&id=<?= $item['id'] ?>"><div class="detail-price"><?= money($item['price']) ?><?php if($item['compare_price']):?><del><?= money($item['compare_price']) ?></del><?php endif?></div><div class="quantity-picker"><button type="button" data-qty="-1">−</button><input name="quantity" value="1" readonly><button type="button" data-qty="1">+</button></div><button class="btn-primary-custom flex-grow-1"><i class="bi bi-bag-plus"></i> Add to cart</button></form>
    </div>
</div>
<div class="review-section"><div class="section-heading"><div><span class="kicker">REAL FOOD. REAL LOVE.</span><h2>What foodies are saying</h2></div></div><?php if($reviews):?><div class="review-grid"><?php foreach ($reviews as $review): ?><article><div class="review-head"><span><?= e(strtoupper(substr($review['name'], 0, 1))) ?></span><div><b><?= e($review['name']) ?></b><small>Verified order</small></div><em><?= str_repeat('★', (int) $review['rating']) ?></em></div><p>“<?= e($review['comment']) ?>”</p><?php if($review['admin_reply']):?><div class="customer-admin-reply"><b>Cafe replied:</b> <?=e($review['admin_reply'])?></div><?php endif?></article><?php endforeach ?></div><?php else:?><div class="empty-state"><p>No verified reviews yet. Be the first to review this dish after delivery.</p></div><?php endif?></div>
</div></section>
<?php endif ?>

<?php elseif ($page === 'cart'):
    $totals = cart_totals();
?>
<section class="page-hero compact"><div class="container"><span class="kicker">ALMOST THERE</span><h1>Your delicious cart</h1><p>Review your favourites before checkout.</p></div></section>
<section class="cart-section"><div class="container">
<?php if (!cart()): ?><div class="empty-state"><span><i class="bi bi-bag-x"></i></span><h2>Your cart is hungry</h2><p>Add something delicious and come back here.</p><a class="btn-primary-custom" href="index.php?page=menu">Explore the menu <i class="bi bi-arrow-right"></i></a></div>
<?php else: ?><div class="cart-grid" data-cart data-tax-rate="<?=e(setting('tax_rate','5'))?>" data-delivery-fee="<?=e(setting('delivery_fee','49'))?>" data-free-delivery="<?=e(setting('free_delivery_threshold','499'))?>" data-discount="<?=e($totals['discount'])?>"><div class="cart-items"><div class="cart-title"><h2>Your items <span data-cart-count><?= cart_count() ?></span></h2><a href="index.php?page=menu"><i class="bi bi-plus"></i> Add more</a></div><form id="cartForm" method="post" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update_cart">
<?php foreach (cart() as $item): ?><article class="cart-item" data-cart-item data-unit-price="<?=e($item['price'])?>"><img src="<?= e(food_image($item['image'])) ?>"><div class="cart-item-copy"><div><h3><?= e($item['name']) ?></h3><span class="veg-dot"><i></i></span></div><input class="instruction" maxlength="255" name="instructions[<?= $item['id'] ?>]" value="<?= e($item['instructions'] ?? '') ?>" placeholder="+ Add cooking instructions"><b><?= money($item['price']) ?> each</b></div><div class="cart-item-actions"><button type="submit" class="remove-link" form="remove-<?= $item['id'] ?>" aria-label="Remove item"><i class="bi bi-trash3"></i></button><div class="quantity-picker"><button type="button" data-qty="-1">−</button><input name="quantity[<?= $item['id'] ?>]" value="<?= (int) $item['quantity'] ?>" readonly><button type="button" data-qty="1">+</button></div><strong data-line-total><?= money($item['price'] * $item['quantity']) ?></strong></div></article><?php endforeach ?>
<button type="submit" class="btn btn-outline-dark rounded-pill px-4 mt-3">Save cart changes</button><small class="cart-save-hint">Totals update instantly; save to keep quantities for checkout.</small></form>
<?php foreach (cart() as $item): ?><form id="remove-<?= $item['id'] ?>" method="post" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="remove_cart"><input type="hidden" name="item_id" value="<?= $item['id'] ?>"></form><?php endforeach ?>
<div class="delivery-promise"><span><i class="bi bi-scooter"></i></span><div><b>Delivery in 30–40 minutes</b><small>Your food is prepared fresh after order confirmation.</small></div></div></div>
<aside class="bill-card"><h3>Bill summary</h3><form class="coupon-form" method="post" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="apply_coupon"><i class="bi bi-ticket-perforated"></i><input name="code" placeholder="Enter coupon code" value="<?= e($_SESSION['coupon'] ?? '') ?>"><button>Apply</button></form><?php if(!empty($_SESSION['coupon'])):?><form class="coupon-hint" method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="remove_coupon"><span><b><?=e($_SESSION['coupon'])?></b> applied</span><button type="submit">REMOVE</button></form><?php else:?><div class="coupon-hint"><span><b>WELCOME100</b> saves ₹100</span><button data-copy="WELCOME100">COPY</button></div><?php endif?>
<div class="bill-lines"><p><span>Item total</span><b data-cart-subtotal><?= money($totals['subtotal']) ?></b></p><p><span>Delivery fee</span><b data-cart-delivery class="<?= $totals['delivery'] ? '' : 'text-success' ?>"><?= $totals['delivery'] ? money($totals['delivery']) : 'FREE' ?></b></p><?php if ($totals['discount']): ?><p class="text-success"><span>Coupon discount</span><b>−<?= money($totals['discount']) ?></b></p><?php endif ?><p><span>Taxes & charges</span><b data-cart-tax><?= money($totals['tax']) ?></b></p></div><div class="grand-total"><span><b>To pay</b><small>Including all taxes</small></span><strong data-cart-total><?= money($totals['total']) ?></strong></div><button type="submit" form="cartForm" name="next" value="checkout" class="btn-primary-custom w-100 justify-content-center">Save & proceed to checkout <i class="bi bi-arrow-right"></i></button><small class="secure-note"><i class="bi bi-shield-lock"></i> Safe and secure checkout</small></aside></div><?php endif ?>
</div></section>

<?php elseif ($page === 'checkout'):
    require_login('customer');
    if (!cart()) redirect('index.php?page=menu');
    $totals = cart_totals();
    $address = query('SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC LIMIT 1', [user()['id']])->fetch();
?>
<section class="page-hero compact"><div class="container"><span class="kicker">SECURE CHECKOUT</span><h1>Complete your order</h1></div></section>
<section class="checkout-section"><div class="container"><form method="post" action="actions.php" class="checkout-grid"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="place_order">
<div class="checkout-main"><div class="checkout-card"><div class="step-title"><span>1</span><div><h3>How would you like it?</h3><small>Choose delivery or pickup</small></div></div><div class="type-options"><label><input type="radio" name="order_type" value="delivery" checked><span><i class="bi bi-scooter"></i><b>Delivery</b><small>30–40 minutes</small></span></label><label><input type="radio" name="order_type" value="pickup"><span><i class="bi bi-shop"></i><b>Self pickup</b><small>Ready in 20 minutes</small></span></label></div></div>
<div class="checkout-card"><div class="step-title"><span>2</span><div><h3>Contact & delivery details</h3><small>So we know where to find you</small></div></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Full name</label><input class="form-control" name="name" value="<?= e(user()['name']) ?>" required></div><div class="col-md-6"><label class="form-label">Phone number</label><input class="form-control" name="phone" value="<?= e(user()['phone']) ?>" required></div><div class="col-12"><label class="form-label">Delivery address</label><textarea class="form-control" name="address" rows="3" required maxlength="300"><?= e($address ? $address['address_line'] . ', ' . $address['city'] . ' - ' . $address['pincode'] : '') ?></textarea></div><div class="col-12"><label class="form-label">Order note <span class="text-muted">(optional)</span></label><input class="form-control" name="notes" maxlength="500" placeholder="Less spicy, call on arrival..."></div></div></div>
<div class="checkout-card"><div class="step-title"><span>3</span><div><h3>Payment method</h3><small>All transactions are secure</small></div></div><div class="payment-options"><label><input type="radio" name="payment_method" value="upi" checked><span><i class="bi bi-phone"></i><b>UPI</b><small>Demo instant payment</small></span></label><label><input type="radio" name="payment_method" value="card"><span><i class="bi bi-credit-card"></i><b>Card</b><small>Demo Visa / Mastercard</small></span></label><label><input type="radio" name="payment_method" value="cod"><span><i class="bi bi-cash-coin"></i><b>Cash on delivery</b><small>Pay at your doorstep</small></span></label></div></div></div>
<aside class="bill-card order-preview" data-checkout-summary data-delivery="<?=e($totals['delivery'])?>" data-total="<?=e($totals['total'])?>"><h3>Your order</h3><?php foreach (cart() as $item): ?><div class="preview-item"><img src="<?= e(food_image($item['image'])) ?>"><div><b><?= e($item['name']) ?></b><small>Qty: <?= $item['quantity'] ?></small></div><strong><?= money($item['price'] * $item['quantity']) ?></strong></div><?php endforeach ?><div class="bill-lines"><p><span>Subtotal</span><b><?= money($totals['subtotal']) ?></b></p><?php if($totals['discount']):?><p class="text-success"><span>Coupon discount</span><b>−<?=money($totals['discount'])?></b></p><?php endif?><p><span>Delivery</span><b data-checkout-delivery><?= $totals['delivery'] ? money($totals['delivery']) : 'FREE' ?></b></p><p><span>Tax</span><b><?= money($totals['tax']) ?></b></p></div><div class="grand-total"><span><b>Total</b></span><strong data-checkout-total><?= money($totals['total']) ?></strong></div><button class="btn-primary-custom w-100 justify-content-center">Place order <i class="bi bi-arrow-right"></i></button><small class="secure-note"><i class="bi bi-shield-check"></i> By placing this order you agree to our terms.</small></aside>
</form></div></section>

<?php elseif ($page === 'login' || $page === 'register'): ?>
<section class="auth-section"><div class="auth-visual"><div class="auth-overlay"><a class="brand cafe-brand text-white" href="index.php"><img class="cafe-logo-mark" src="<?=e(base_url('assets/images/cafe-logo-mark.svg'))?>" alt=""><strong class="cafe-word">Cafe</strong></a><div><span class="kicker text-warning">FOOD THAT FEELS LIKE HOME</span><h2>Good food.<br>Great mood.</h2><p>Join thousands of food lovers enjoying fresh meals, fast delivery and delicious rewards.</p></div><div class="auth-testimonial"><div class="stars">★★★★★</div><p>“Everything from ordering to delivery was smooth—and the food was incredible!”</p><span>— Neha, Bengaluru</span></div></div></div>
<div class="auth-form-wrap"><div class="auth-form">
<?php if ($page === 'login'): ?><span class="kicker">WELCOME BACK</span><h1>Hungry? Let's fix that.</h1><p>Sign in to continue your delicious journey.</p><form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="login"><label>Email address</label><div class="input-icon"><i class="bi bi-envelope"></i><input type="email" name="email" autocomplete="email" required></div><label>Password</label><div class="input-icon"><i class="bi bi-lock"></i><input type="password" name="password" autocomplete="current-password" required><button type="button" data-password><i class="bi bi-eye"></i></button></div><div class="form-options"><span><i class="bi bi-shield-check"></i> Secure session</span><a href="mailto:<?=e(setting('restaurant_email','hello@cafe.test'))?>?subject=Password%20reset%20request">Forgot password?</a></div><button class="btn-primary-custom w-100 justify-content-center">Sign in <i class="bi bi-arrow-right"></i></button></form><p class="auth-switch">New to Cafe? <a href="index.php?page=register">Create an account</a></p>
<?php else: ?><span class="kicker">JOIN THE TABLE</span><h1>Create your account</h1><p>Great food and member-only rewards are waiting.</p><form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="register"><label>Full name</label><div class="input-icon"><i class="bi bi-person"></i><input name="name" required></div><label>Email address</label><div class="input-icon"><i class="bi bi-envelope"></i><input type="email" name="email" required></div><label>Phone number</label><div class="input-icon"><i class="bi bi-telephone"></i><input name="phone" required></div><label>Password</label><div class="input-icon"><i class="bi bi-lock"></i><input type="password" name="password" required minlength="6"></div><button class="btn-primary-custom w-100 justify-content-center mt-4">Create account <i class="bi bi-arrow-right"></i></button></form><p class="auth-switch">Already a member? <a href="index.php?page=login">Sign in</a></p><?php endif ?>
</div></div></section>

<?php elseif ($page === 'favorites'):
    require_login('customer');
    $favoriteItems = query('SELECT m.* FROM favorites f JOIN menu_items m ON m.id=f.menu_item_id WHERE f.user_id=? AND m.status=1 ORDER BY f.created_at DESC', [user()['id']])->fetchAll();
?>
<section class="page-hero compact"><div class="container"><span class="kicker">SAVED FOR LATER</span><h1>Your favorite flavours</h1><p>All the dishes you love, ready for one-click ordering.</p></div></section>
<section class="menu-area"><div class="container"><?php if($favoriteItems):?><div class="food-grid"><?php foreach($favoriteItems as $item) food_card($item);?></div><?php else:?><div class="empty-state"><span><i class="bi bi-heart"></i></span><h2>No favorites yet</h2><p>Tap the heart on any dish to save it here.</p><a class="btn-primary-custom" href="index.php?page=menu">Explore dishes</a></div><?php endif?></div></section>

<?php elseif ($page === 'orders'):
    require_login();
    if (user()['role'] !== 'customer') redirect('dashboard.php?page=orders');
    $orders = query('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC', [user()['id']])->fetchAll();
?>
<section class="page-hero compact"><div class="container"><span class="kicker">YOUR FOOD JOURNEY</span><h1>My orders</h1><p>Track current orders or revisit your old favourites.</p></div></section>
<section class="orders-section"><div class="container"><?php if (!$orders): ?><div class="empty-state"><span><i class="bi bi-receipt"></i></span><h2>No orders yet</h2><a class="btn-primary-custom" href="index.php?page=menu">Place your first order</a></div><?php else: ?><div class="order-list">
<?php foreach ($orders as $order):
    [$label,$color,$icon]=status_meta($order['status']);
    $orderItems=query('SELECT * FROM order_items WHERE order_id=?',[$order['id']])->fetchAll();
?>
<article class="order-card"><div class="order-card-head"><div><small>ORDER #<?= e($order['order_number']) ?></small><h3><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></h3></div><span class="status-badge <?= $color ?>"><i class="bi <?= $icon ?>"></i> <?= $label ?></span></div><div class="order-card-body"><div class="order-dishes"><?php foreach ($orderItems as $item): ?><p><span><?= $item['quantity'] ?>×</span> <?= e($item['item_name']) ?><b><?= money($item['item_price'] * $item['quantity']) ?></b></p><?php endforeach ?></div><div class="order-total"><small>Total amount</small><b><?= money($order['total']) ?></b><span><?= e(strtoupper($order['payment_method'])) ?> · <?= e($order['payment_status']) ?></span></div></div>
<?php if (!in_array($order['status'],['delivered','cancelled'],true)): ?><div class="track-progress"><?php $steps=['pending'=>'Received','confirmed'=>'Confirmed','preparing'=>'Preparing','ready'=>'Ready','out_for_delivery'=>'On the way','delivered'=>'Delivered']; $position=array_search($order['status'],array_keys($steps),true); foreach ($steps as $key=>$step): ?><div class="<?= array_search($key,array_keys($steps),true) <= $position ? 'done' : '' ?>"><i class="bi bi-check"></i><span><?= $step ?></span></div><?php endforeach ?></div><?php endif ?>
<?php if($order['status']==='delivered'):?><div class="order-reviews"><?php foreach($orderItems as $item): if(!$item['menu_item_id']) continue; $existingReview=query('SELECT * FROM reviews WHERE order_id=? AND menu_item_id=? AND user_id=?',[$order['id'],$item['menu_item_id'],user()['id']])->fetch(); ?><div class="order-review-row"><b><?=e($item['item_name'])?></b><?php if($existingReview):?><span class="reviewed-stars"><?=str_repeat('★',(int)$existingReview['rating'])?></span><small><?=e($existingReview['comment'])?></small><?php if($existingReview['admin_reply']):?><div class="customer-admin-reply"><b>Cafe replied:</b> <?=e($existingReview['admin_reply'])?></div><?php endif?><?php else:?><button type="button" class="btn btn-sm btn-outline-dark rounded-pill" data-toggle-target="#customer-review-<?=$order['id']?>-<?=$item['menu_item_id']?>">Write review</button><form id="customer-review-<?=$order['id']?>-<?=$item['menu_item_id']?>" class="customer-review-form d-none" method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="submit_review"><input type="hidden" name="order_id" value="<?=$order['id']?>"><input type="hidden" name="item_id" value="<?=$item['menu_item_id']?>"><select name="rating" class="form-select"><option value="5">★★★★★ Excellent</option><option value="4">★★★★ Good</option><option value="3">★★★ Average</option><option value="2">★★ Poor</option><option value="1">★ Bad</option></select><textarea name="comment" class="form-control" required minlength="5" maxlength="500" placeholder="Tell us about the food..."></textarea><button class="btn-primary-custom" type="submit">Submit review</button></form><?php endif?></div><?php endforeach?></div><?php endif?>
<div class="order-card-foot"><span><i class="bi bi-geo-alt"></i> <?= e($order['order_type'] === 'pickup' ? 'Self pickup' : $order['delivery_address']) ?></span><div><?php if(!in_array($order['status'],['delivered','cancelled'],true)):?><a href="index.php?page=track&id=<?= $order['id'] ?>" class="btn btn-outline-dark rounded-pill">Track order</a><?php else:?><form method="post" action="actions.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="reorder"><input type="hidden" name="order_id" value="<?=$order['id']?>"><button class="btn btn-outline-dark rounded-pill" type="submit">Order again</button></form><?php endif?></div></div></article>
<?php endforeach ?></div><?php endif ?></div></section>

<?php elseif ($page === 'order-success'):
    require_login('customer');
    $order = query('SELECT * FROM orders WHERE id=? AND user_id=?', [(int)($_GET['id']??0),user()['id']])->fetch();
    if (!$order) redirect('index.php');
?>
<section class="success-section"><div class="success-card"><div class="success-check"><i class="bi bi-check-lg"></i><span></span><span></span></div><span class="kicker">ORDER CONFIRMED</span><h1>Yay! Food is on the way.</h1><p>We've received your order and our kitchen is getting ready to work its magic.</p><div class="success-order"><span>Order number<b>#<?= e($order['order_number']) ?></b></span><span>Estimated time<b><?= $order['estimated_minutes'] ?>–<?= $order['estimated_minutes']+10 ?> min</b></span><span><?=$order['payment_status']==='paid'?'Amount paid':'Amount due'?><b><?= money($order['total']) ?></b></span></div><div class="success-actions"><a class="btn-primary-custom" href="index.php?page=track&id=<?= $order['id'] ?>">Track my order <i class="bi bi-arrow-right"></i></a><a href="index.php?page=menu" class="btn btn-outline-dark rounded-pill px-4">Back to menu</a></div><small><i class="bi bi-bell"></i> We'll notify you whenever your order moves to the next step.</small></div></section>

<?php elseif ($page === 'track'):
    require_login('customer');
    $order = query('SELECT * FROM orders WHERE id=? AND user_id=?', [(int)($_GET['id']??0),user()['id']])->fetch();
    if (!$order) redirect('index.php?page=orders');
    $rider = $order['delivery_user_id'] ? query('SELECT name,phone FROM users WHERE id=?', [$order['delivery_user_id']])->fetch() : query('SELECT name,phone FROM users WHERE role="delivery" AND status="active" LIMIT 1')->fetch();
    $steps=['pending'=>['Order received','Waiting for restaurant confirmation.'],'confirmed'=>['Order confirmed','We have received your order.'],'preparing'=>['Cooking your food','Our chefs are creating something delicious.'],'ready'=>['Packed and ready','Your order is packed with care.'],'out_for_delivery'=>['Out for delivery','Our rider is heading your way.'],'delivered'=>['Delivered','Enjoy your Cafe meal!']];
    $position=array_search($order['status'],array_keys($steps),true); if ($position===false) $position=-1;
?>
<section class="tracking-section"><div class="container"><div class="track-header"><div><span class="kicker">LIVE ORDER TRACKING</span><h1>Your feast is in motion</h1><p>Order #<?= e($order['order_number']) ?></p></div><div class="eta"><small>Estimated arrival</small><b><?= date('h:i A',strtotime($order['created_at'].' +'.$order['estimated_minutes'].' minutes')) ?></b><span><?= $order['estimated_minutes'] ?>–<?= $order['estimated_minutes']+10 ?> min</span></div></div><div class="tracking-grid"><div class="track-map"><div class="fake-map"><i class="road r1"></i><i class="road r2"></i><i class="road r3"></i><span class="restaurant-pin"><i class="bi bi-shop"></i></span><span class="home-pin"><i class="bi bi-house-heart"></i></span><div class="rider"><i class="bi bi-scooter"></i></div></div><div class="rider-card"><span><?=e(strtoupper(substr($rider['name']??'Rider',0,2)))?></span><div><b><?=e($rider['name']??'Delivery partner')?></b><small>Your delivery partner · <i class="bi bi-star-fill"></i> 4.9</small></div><a href="tel:<?=e($rider['phone']??setting('restaurant_phone'))?>" title="Call rider"><i class="bi bi-telephone"></i></a><a href="https://wa.me/<?=e(preg_replace('/\D+/','',$rider['phone']??setting('restaurant_phone')))?>" target="_blank" rel="noopener" title="Message rider"><i class="bi bi-chat-dots"></i></a></div></div><div class="track-timeline"><h3>Order status</h3><?php foreach ($steps as $key=>$step): $i=array_search($key,array_keys($steps),true); ?><div class="timeline-step <?= $i <= $position ? 'done' : '' ?> <?= $i === $position ? 'current' : '' ?>"><span><i class="bi <?= $i <= $position ? 'bi-check-lg' : 'bi-circle' ?>"></i></span><div><b><?= $step[0] ?></b><small><?= $step[1] ?></small><?php if ($i===$position): ?><em>Happening now</em><?php endif ?></div></div><?php endforeach ?><div class="help-box"><i class="bi bi-headset"></i><div><b>Need help with your order?</b><small>Our support team is here for you.</small></div><a href="tel:<?= e(setting('restaurant_phone')) ?>">Call us</a></div></div></div></div></section>

<?php else: ?>
<div class="empty-state"><span><i class="bi bi-compass"></i></span><h1>Page not found</h1><a class="btn-primary-custom" href="index.php">Take me home</a></div>
<?php endif;

storefront_footer();
