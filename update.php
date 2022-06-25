<title>Update - Shopping - local-test.com</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<?php
require_once('lib.php');
$name = isset($_REQUEST['name']) && $_REQUEST['name'] != '' ? $_REQUEST['name'] : 'Vinamilk';
$barcode = isset($_REQUEST['barcode']) && $_REQUEST['barcode'] != '' ? $_REQUEST['barcode'] : '1470695799272';
?>
<form method="post">
    <label for="name">Tên sản phẩm</label>
    <input type="text" name="name" id="name" value="<?php echo $name; ?>"/>
    <label for="barcode">Barcode</label>
    <input type="text" name="barcode" id="barcode" value="<?php echo $barcode; ?>"/>
    <input type="submit" name="submit" value="Submit"/>
</form>
<?php
if (isset($_REQUEST['submit'])) {
    $startTime = microtime(true);
    $logFile = 'auto_rule_gen_' . date('Y-m-d') . '.log';
//    $log = date('d-m-Y H:i:s');
//    file_put_contents($logFile, "\r\n========================================================\r\n" . $log . "\r\n", FILE_APPEND | LOCK_EX);

    $params = [];
    $params['name'] = isset($_REQUEST['name']) && $_REQUEST['name'] != '' ? $_REQUEST['name'] : 'Vinamilk';
    $params['barcode'] = isset($_REQUEST['barcode']) && $_REQUEST['barcode'] != '' ? $_REQUEST['barcode'] : '1470695799272';
    $results = search($params, $mysql_conn);

    $i = 0;
    $name = '';
    foreach ($results as $result) {
        if ($name != $result['name']) {
            $name = $result['name'];
            echo "$name<br/>";
        }

        echo '<form method="post" action="update.php">';
        echo '<label for="price">Giá</label> ';
        echo "<input type=\"text\" name=\"price\" id=\"price\" value=\"{$result['price']}\"/> ";
        echo '<label for="seller">Seller</label> ';
        echo "<input type=\"text\" name=\"seller\" id=\"seller\" value=\"{$result['seller']}\"/> ";
        echo "<input type=\"hidden\" name=\"seller_id\" id=\"seller_id\" value=\"{$result['seller_id']}\"/>";
        echo '<input type="submit" name="submit" value="Submit"/>';
        echo '</form>';

        $i++;
    }

    $duration = intval((microtime(true) - $startTime) * 1000);
    $log = 'Hoàn tất trong ' . $duration . ' ms';
    echo $log . '<br/>';
//    file_put_contents($logFile, $log . "\r\n", FILE_APPEND | LOCK_EX);
}

















//$a = 31;
//var_dump($a);
//$b = "5";
//var_dump($b);
//if (isset($b) && $b) {
//    $a = $b;
//}
//if ($a < 1) {
//	$a = 30;
//	echo '$a < 1';
//}
//var_dump($a);
//$a = intval($a);
//var_dump($a);








// $a = [8, 5, 9, 3];
// $b = [2, 7, 4];
// $c = 3;

// $s = array_search($c, $a, 1);
// if ($s !== false) {
    // echo $b[$s];
// }
