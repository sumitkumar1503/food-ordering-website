<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_login('admin');

$where = ['1=1'];
$params = [];
$search = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$date = (string) ($_GET['date'] ?? '');

if ($search !== '') {
    $where[] = '(order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)';
    $needle = '%' . $search . '%';
    array_push($params, $needle, $needle, $needle);
}
if (in_array($status, ['pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled'], true)) {
    $where[] = 'status=?';
    $params[] = $status;
}
if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $where[] = 'DATE(created_at)=?';
    $params[] = $date;
}

$orders = query(
    'SELECT order_number,customer_name,customer_phone,order_type,subtotal,discount,delivery_fee,tax,total,payment_method,payment_status,status,delivery_address,created_at
     FROM orders WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC',
    $params
)->fetchAll();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="cafe-orders-' . date('Y-m-d-His') . '.csv"');
echo "\xEF\xBB\xBF";
$output = fopen('php://output', 'wb');
fputcsv($output, ['Order','Customer','Phone','Type','Subtotal','Discount','Delivery','Tax','Total','Payment','Payment status','Order status','Address','Created']);
foreach ($orders as $order) {
    fputcsv($output, array_values($order));
}
fclose($output);
exit;
