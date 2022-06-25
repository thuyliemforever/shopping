<title>Promotion - Shopping - local-test.com</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="stylesheet" href="style.css" />
<?php
require_once('lib.php');
$name = isset($_REQUEST['name']) && $_REQUEST['name'] != '' ? $_REQUEST['name'] : '';
$barcode = isset($_REQUEST['barcode']) && $_REQUEST['barcode'] != '' ? $_REQUEST['barcode'] : '';
?>
<form method="post">
    <label for="name">Tên sản phẩm</label>
    <input type="text" name="name" id="name" value="<?php echo $name; ?>"/>
    <label for="barcode">Barcode</label>
    <input type="text" name="barcode" id="barcode" value="<?php echo $barcode; ?>"/>
    <input type="hidden" name="action" id="action" value="search_promotion"/>
    <input type="submit" name="submit" value="Search"/>
</form>
<?php
if (isset($_REQUEST['submit'])) {
    $startTime = microtime(true);
    $logFile = 'auto_rule_gen_' . date('Y-m-d') . '.log';
//    $log = date('d-m-Y H:i:s');
//    file_put_contents($logFile, "\r\n========================================================\r\n" . $log . "\r\n", FILE_APPEND | LOCK_EX);

    $action = isset($_REQUEST['action']) && $_REQUEST['action'] != '' ? $_REQUEST['action'] : 'search_promotion';

    $params = [];
    $params['name'] = isset($_REQUEST['name']) && $_REQUEST['name'] != '' ? $_REQUEST['name'] : null;
    $params['barcode'] = isset($_REQUEST['barcode']) && $_REQUEST['barcode'] != '' ? $_REQUEST['barcode'] : null;
//    $params['product_id'] = isset($_REQUEST['product_id']) && $_REQUEST['product_id'] != '' ? $_REQUEST['product_id'] : null;
    $params['list_price'] = isset($_REQUEST['list_price']) && $_REQUEST['list_price'] != '' ? $_REQUEST['list_price'] : null;
    $params['seller'] = isset($_REQUEST['seller']) && $_REQUEST['seller'] != '' ? $_REQUEST['seller'] : null;
//    $params['seller_id'] = isset($_REQUEST['seller_id']) && $_REQUEST['seller_id'] != '' ? $_REQUEST['seller_id'] : null;
    $params['list_price'] = isset($_REQUEST['list_price']) && $_REQUEST['list_price'] != '' ? $_REQUEST['list_price'] : null;
    $params['current_price'] = isset($_REQUEST['current_price']) && $_REQUEST['current_price'] != '' ? $_REQUEST['current_price'] : null;
//    $params['special_from'] = isset($_REQUEST['special_from']) && $_REQUEST['special_from'] != '' ? $_REQUEST['special_from'] : null;
    $params['special_to'] = isset($_REQUEST['special_to']) && $_REQUEST['special_to'] != '' ? $_REQUEST['special_to'] : null;

    $results = search_promotion($params, $mysql_conn);

    $name = '';
    foreach ($results as $result) {
        if ($name != $result['name']) {
            $name = $result['name'];
            echo "$name";
        }

        $current_unit_price = '';
        if ($result['unit_count']) {
            $current_unit_price = intval($result['current_price'] / $result['unit_count']);
        }
        echo '<form method="post">';
        echo '<label for="current_price">Giá</label> ';
        echo "<input type=\"text\" name=\"current_price\" id=\"current_price\" value=\"{$result['current_price']}\" size='4' readonly/> ";
        echo '<label for="current_unit_price">Giá đơn vị</label> ';
        echo "<input type=\"text\" name=\"current_unit_price\" id=\"current_unit_price\" value=\"{$current_unit_price}\" size='4' readonly/> ";
        echo '<label for="seller">Seller</label> ';
        echo "<input type=\"text\" name=\"seller\" id=\"seller\" value=\"{$result['seller']}\" readonly class='w100px'/> ";
        echo '<label for="list_price">list_price</label> ';
        echo "<input type=\"text\" name=\"list_price\" id=\"list_price\" value=\"{$result['list_price']}\" size='4' readonly/> ";
        echo '<label for="special_to">special_to</label> ';
        echo "<input type=\"text\" name=\"special_to\" id=\"special_to\" value=\"{$result['special_to']}\" size='15' readonly class='w75px'/> ";
//        echo '<input type="submit" name="submit" value="Update"/>';
        echo '</form>';
    }

    $duration = intval((microtime(true) - $startTime) * 1000);
    $log = 'Hoàn tất trong ' . $duration . ' ms';
    echo $log . '<br/>';
//    file_put_contents($logFile, $log . "\r\n", FILE_APPEND | LOCK_EX);
}

