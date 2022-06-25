<title>🏡 Search - Shopping - local-test.com</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="stylesheet" href="style.css" />
<?php
require_once('lib.php');
$searchingProductName = isset($_REQUEST['searchingProductName']) && $_REQUEST['searchingProductName'] != '' ? $_REQUEST['searchingProductName'] : '';
$searchingProductBarcode = isset($_REQUEST['searchingProductBarcode']) && $_REQUEST['searchingProductBarcode'] != '' ? $_REQUEST['searchingProductBarcode'] : '';
?>
<form method="post">
    <label for="name">Tên sản phẩm</label>
    <input type="text" name="searchingProductName" id="searchingProductName" value="<?php echo $searchingProductName; ?>"/>
    <label for="barcode">Barcode</label>
    <input type="text" name="searchingProductBarcode" id="searchingProductBarcode" value="<?php echo $searchingProductBarcode; ?>"/>
    <input type="hidden" name="action" id="action" value="search_product"/>
    <input type="submit" name="submit" value="Search"/>
</form>
<div>
    <input type="button" id="searchingProductBarcodeScanButton" value="Scan barcode to search" />
</div>
<div id="searchingProductBarcodeScanVideoPanel" style="display:none">
    <video id="searchingProductBarcodeScanVideo" width="300" height="300" style="border: 1px solid white"></video>
</div>

<?php
if (isset($_REQUEST['submit'])) {
    $startTime = microtime(true);
    $logFile = 'auto_rule_gen_' . date('Y-m-d') . '.log';
//    $log = date('d-m-Y H:i:s');
//    file_put_contents($logFile, "\r\n========================================================\r\n" . $log . "\r\n", FILE_APPEND | LOCK_EX);

    $action = isset($_REQUEST['action']) && $_REQUEST['action'] != '' ? $_REQUEST['action'] : 'search_product';

    $params = [];
    $params['searchingProductName'] = isset($_REQUEST['searchingProductName']) && $_REQUEST['searchingProductName'] != '' ? $_REQUEST['searchingProductName'] : '';
    $params['searchingProductBarcode'] = isset($_REQUEST['searchingProductBarcode']) && $_REQUEST['searchingProductBarcode'] != '' ? $_REQUEST['searchingProductBarcode'] : '';
    $params['name'] = isset($_REQUEST['name']) && $_REQUEST['name'] != '' ? $_REQUEST['name'] : null;
    $params['barcode'] = isset($_REQUEST['barcode']) && $_REQUEST['barcode'] != '' ? $_REQUEST['barcode'] : null;
    $params['product_id'] = isset($_REQUEST['product_id']) && $_REQUEST['product_id'] != '' ? $_REQUEST['product_id'] : null;
    $params['list_price'] = isset($_REQUEST['list_price']) && $_REQUEST['list_price'] != '' ? $_REQUEST['list_price'] : null;
    $params['seller'] = isset($_REQUEST['seller']) && $_REQUEST['seller'] != '' ? $_REQUEST['seller'] : null;
    $params['seller_id'] = isset($_REQUEST['seller_id']) && $_REQUEST['seller_id'] != '' ? $_REQUEST['seller_id'] : null;
    $params['price'] = isset($_REQUEST['price']) && $_REQUEST['price'] != '' ? $_REQUEST['price'] : null;
    $params['special_price'] = isset($_REQUEST['special_price']) && $_REQUEST['special_price'] != '' ? $_REQUEST['special_price'] : null;
    $params['special_from'] = isset($_REQUEST['special_from']) && $_REQUEST['special_from'] != '' ? $_REQUEST['special_from'] : null;
    $params['special_to'] = isset($_REQUEST['special_to']) && $_REQUEST['special_to'] != '' ? $_REQUEST['special_to'] : null;
    $params['unit_name'] = isset($_REQUEST['unit_name']) && $_REQUEST['unit_name'] != '' ? $_REQUEST['unit_name'] : '';
    $params['unit_count'] = isset($_REQUEST['unit_count']) && $_REQUEST['unit_count'] != '' ? $_REQUEST['unit_count'] : 1;

    switch ($action) {
        case 'create_product':
            create_product($params, $mysql_conn);
            break;
        case 'update_product':
            update_product($params, $mysql_conn);
            break;
        case 'create_seller_product':
            $result = create_seller_product($params, $mysql_conn);
            break;
        case 'update_seller':
            $result = update_seller($params, $mysql_conn);
            break;
    }

    $results = search_product($params['searchingProductName'], $params['searchingProductBarcode'], $mysql_conn);

    $name = '';
    foreach ($results as $result) {
        if ($name != $result['name']) {
            if ($name != '') {
                echo '<form method="post">';
                echo 'Thêm mới ';
                echo '<label for="seller">Seller</label> ';
                echo "<input type=\"text\" name=\"seller\" id=\"seller\" value=\"\" class='w100px'/> ";
                echo '<label for="price">price</label> ';
                echo "<input type=\"text\" name=\"price\" id=\"price\" value=\"\" size='4'/> ";
                echo '<label for="special_price">special_price</label> ';
                echo "<input type=\"text\" name=\"special_price\" id=\"special_price\" value=\"\" size='4'/> ";
                echo '<label for="special_to">special_to</label> ';
                echo "<input type=\"text\" name=\"special_to\" id=\"special_to\" value=\"\" class='w75px'/> ";
                echo "<input type=\"hidden\" name=\"product_id\" id=\"product_id\" value=\"{$lastProductId}\"/>";
                echo "<input type=\"hidden\" name=\"name\" id=\"name\" value=\"{$lastName}\"/>";
                echo "<input type=\"hidden\" name=\"searchingProductName\" id=\"searchingProductName\" value=\"{$params['searchingProductName']}\"/>";
                echo "<input type=\"hidden\" name=\"searchingProductBarcode\" id=\"searchingProductBarcode\" value=\"{$params['searchingProductBarcode']}\"/>";
                echo "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"create_seller_product\"/>";
                echo '<input type="submit" name="submit" value="Create Seller"/>';
                echo '</form>';
            }

            $name = $result['name'];
            echo "$name";
            echo '<form method="post">';
//            echo '<label for="sku">SKU</label> ';
//            echo "<input type=\"text\" name=\"sku\" id=\"sku\" value=\"{$result['sku']}\" size='11' readonly/> ";
//            echo '<label for="list_price">list_price</label> ';
//            echo "<input type=\"text\" name=\"list_price\" id=\"list_price\" value=\"{$result['list_price']}\" size='4' readonly/> ";
            echo '<label for="barcode">Barcode</label> ';
            echo "<input type=\"text\" name=\"barcode\" id=\"barcode\" value=\"{$result['barcode']}\" size='11'/> ";
            echo '<label for="name">Tên</label> ';
            echo "<input type=\"text\" name=\"name\" id=\"name\" value=\"{$result['name']}\"/> ";
            echo '<label for="unit_count">Unit count</label> ';
            echo "<input type=\"text\" name=\"unit_count\" id=\"unit_count\" value=\"{$result['unit_count']}\" class='w35px'/> ";
            echo '<label for="unit_name">Unit name</label> ';
            echo "<input type=\"text\" name=\"unit_name\" id=\"unit_name\" value=\"{$result['unit_name']}\" class='w40px'/> ";
            echo "<input type=\"hidden\" name=\"product_id\" id=\"product_id\" value=\"{$result['product_id']}\"/>";
            echo "<input type=\"hidden\" name=\"searchingProductName\" id=\"searchingProductName\" value=\"{$params['searchingProductName']}\"/>";
            echo "<input type=\"hidden\" name=\"searchingProductBarcode\" id=\"searchingProductBarcode\" value=\"{$params['searchingProductBarcode']}\"/>";
            echo "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"update_product\"/>";
            echo '<input type="submit" name="submit" value="Update"/>';
            echo '</form>';
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
        echo '<label for="price">price</label> ';
        echo "<input type=\"text\" name=\"price\" id=\"price\" value=\"{$result['price']}\" size='4'/> ";
        echo '<label for="special_price">special_price</label> ';
        echo "<input type=\"text\" name=\"special_price\" id=\"special_price\" value=\"{$result['special_price']}\" size='4'/> ";
        echo '<label for="special_to">special_to</label> ';
        echo "<input type=\"text\" name=\"special_to\" id=\"special_to\" value=\"{$result['special_to']}\" class='w75px'/> ";
        echo "<input type=\"hidden\" name=\"product_id\" id=\"product_id\" value=\"{$result['product_id']}\"/>";
        echo "<input type=\"hidden\" name=\"seller_id\" id=\"seller_id\" value=\"{$result['seller_id']}\"/>";
        echo "<input type=\"hidden\" name=\"name\" id=\"name\" value=\"{$params['name']}\"/>";
        echo "<input type=\"hidden\" name=\"barcode\" id=\"barcode\" value=\"{$params['barcode']}\"/>";
        echo "<input type=\"hidden\" name=\"searchingProductName\" id=\"searchingProductName\" value=\"{$params['searchingProductName']}\"/>";
        echo "<input type=\"hidden\" name=\"searchingProductBarcode\" id=\"searchingProductBarcode\" value=\"{$params['searchingProductBarcode']}\"/>";
        echo "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"update_seller\"/>";
        echo '<input type="submit" name="submit" value="Update"/>';
        echo '</form>';

        $lastProductId = $result['product_id'];
        $lastName = $result['name'];
    }

    if (count($results)) {
        echo '<form method="post">';
        echo 'Thêm mới ';
        echo '<label for="seller">Seller</label> ';
        echo "<input type=\"text\" name=\"seller\" id=\"seller\" value=\"\" class='w100px'/> ";
        echo '<label for="price">price</label> ';
        echo "<input type=\"text\" name=\"price\" id=\"price\" value=\"\" size='4'/> ";
        echo '<label for="special_price">special_price</label> ';
        echo "<input type=\"text\" name=\"special_price\" id=\"special_price\" value=\"\" size='4'/> ";
        echo '<label for="special_to">special_to</label> ';
        echo "<input type=\"text\" name=\"special_to\" id=\"special_to\" value=\"\" class='w75px'/> ";
        echo "<input type=\"hidden\" name=\"product_id\" id=\"product_id\" value=\"{$lastProductId}\"/>";
        echo "<input type=\"hidden\" name=\"name\" id=\"name\" value=\"{$lastName}\"/>";
        echo "<input type=\"hidden\" name=\"searchingProductName\" id=\"searchingProductName\" value=\"{$params['searchingProductName']}\"/>";
        echo "<input type=\"hidden\" name=\"searchingProductBarcode\" id=\"searchingProductBarcode\" value=\"{$params['searchingProductBarcode']}\"/>";
        echo "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"create_seller_product\"/>";
        echo '<input type="submit" name="submit" value="Create Seller"/>';
        echo '</form>';
    }

//    $params['name'] = isset($result['name']) && $result['name'] != '' ? $result['name'] : null;
//    $params['barcode'] = isset($result['barcode']) && $result['barcode'] != '' ? $result['barcode'] : null;
//    $params['seller'] = isset($result['seller']) && $result['seller'] != '' ? $result['seller'] : null;
//    $params['list_price'] = isset($result['list_price']) && $result['list_price'] != '' ? $result['list_price'] : null;
//    $params['price'] = isset($result['price']) && $result['price'] != '' ? $result['price'] : null;
//    $params['special_price'] = isset($result['special_price']) && $result['special_price'] != '' ? $result['special_price'] : null;
//    $params['special_from'] = isset($result['special_from']) && $result['special_from'] != '' ? $result['special_from'] : null;
//    $params['special_to'] = isset($result['special_to']) && $result['special_to'] != '' ? $result['special_to'] : null;

    echo 'Thêm mới sản phẩm: 🏡<br/>';
    echo '<form method="post">';
    echo '<label for="name">Tên sản phẩm</label> ';
    echo "<input type=\"text\" name=\"name\" id=\"name\" value=\"\"/> ";
    echo '<label for="seller">Seller</label> ';
    echo "<input type=\"text\" name=\"seller\" id=\"seller\" value=\"\" class='w100px'/> ";
//    echo '<label for="list_price">list_price</label> ';
//    echo "<input type=\"text\" name=\"list_price\" id=\"list_price\" value=\"\" size='4'/> ";
    echo '<label for="price">price</label> ';
    echo "<input type=\"text\" name=\"price\" id=\"price\" value=\"\" size='4'/> ";
    echo '<label for="special_price">special_price</label> ';
    echo "<input type=\"text\" name=\"special_price\" id=\"special_price\" value=\"\" size='4'/> ";
    echo '<label for="special_to">special_to</label> ';
    echo "<input type=\"text\" name=\"special_to\" id=\"special_to\" value=\"\" class='w75px'/> ";
    echo '<label for="barcode-new">Barcode</label> ';
    echo "<input type=\"text\" name=\"barcode\" id=\"barcodeCreate\" value=\"\" size='11'/> ";
    echo '<label for="unit_name">Unit name</label> ';
    echo "<input type=\"text\" name=\"unit_name\" id=\"unit_name\" value=\"\" class='w40px'/> ";
    echo '<label for="unit_count">Unit count</label> ';
    echo "<input type=\"text\" name=\"unit_count\" id=\"unit_count\" value=\"\" class='w35px'/> ";
    echo "<input type=\"hidden\" name=\"searchingProductName\" id=\"searchingProductName\" value=\"{$params['searchingProductName']}\"/>";
    echo "<input type=\"hidden\" name=\"searchingProductBarcode\" id=\"searchingProductBarcode\" value=\"{$params['searchingProductBarcode']}\"/>";
    echo "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"create_product\"/>";
    echo '<input type="submit" name="submit" value="Create"/>';
    echo '</form>';

    $duration = intval((microtime(true) - $startTime) * 1000);
    $log = 'Hoàn tất trong ' . $duration . ' ms';
    echo $log . '<br/>';
//    file_put_contents($logFile, $log . "\r\n", FILE_APPEND | LOCK_EX);
}
?>
<div>
    <input type="button" id="barcodeCreateButton" value="Scan barcode to create" />
</div>
<div id="barcodeCreateVideoPanel" style="display:none">
    <video id="barcodeCreateVideo" width="300" height="300" style="border: 1px solid white"></video>
</div>

<div id="sourceSelectPanel" style="display:none">
    <label for="sourceSelect">Change video source:</label>
    <select id="sourceSelect" style="max-width:400px">
    </select>
</div>

<script type="text/javascript" src="https://unpkg.com/@zxing/library@latest"></script>
<script type="text/javascript">
    window.addEventListener('load', function () {
        let selectedDeviceId;
        const codeReader = new ZXing.BrowserMultiFormatReader();
        const searchingProductBarcodeScanVideoPanel = document.getElementById('searchingProductBarcodeScanVideoPanel');
        const barcodeCreateVideoPanel = document.getElementById('barcodeCreateVideoPanel');
        console.log('ZXing code reader initialized');
        codeReader.getVideoInputDevices()
            .then((videoInputDevices) => {
            const sourceSelect = document.getElementById('sourceSelect');
            selectedDeviceId = videoInputDevices[videoInputDevices.length - 1].deviceId;
            if (videoInputDevices.length >= 1) {
                videoInputDevices.forEach((element) => {
                    const sourceOption = document.createElement('option');
                    sourceOption.text = element.label;
                    sourceOption.value = element.deviceId;
                    sourceSelect.appendChild(sourceOption);
                })

                sourceSelect.onchange = () => {
                    selectedDeviceId = sourceSelect.value;
                };

                const sourceSelectPanel = document.getElementById('sourceSelectPanel');
                sourceSelectPanel.style.display = 'block';
            }

//            codeReader.decodeFromVideoDevice(selectedDeviceId, 'video', (result, err) => {
//                if (result) {
//                    console.log(result);
//                    document.getElementById('barcode-new').value = result.text;
//                }
//                if (err && !(err instanceof ZXing.NotFoundException)) {
//                    console.error(err);
//                    document.getElementById('barcode-new').value = err;
//                }
//            })

//            console.log(`Started continous decode from camera with id ${selectedDeviceId}`);

//            document.getElementById('resetButton').addEventListener('click', () => {
//                document.getElementById('barcode-new').value = '';
//                codeReader.reset();
//                codeReader.decodeFromVideoDevice(selectedDeviceId, 'video', (result, err) => {
//                    if (result) {
//                        console.log(result);
//                        document.getElementById('barcode-new').value = result.text;
//                    }
//                    if (err && !(err instanceof ZXing.NotFoundException)) {
//                        console.error(err);
//                        document.getElementById('barcode-new').value = err;
//                    }
//                })
//                console.log('Reset.');
//            })

            document.getElementById('searchingProductBarcodeScanButton').addEventListener('click', () => {
                searchingProductBarcodeScanVideoPanel.style.display = 'block';
                codeReader.decodeFromVideoDevice(selectedDeviceId, 'searchingProductBarcodeScanVideo', (result, err) => {
                    if (result) {
                        console.log(result);
                        document.getElementById('searchingProductBarcode').value = result.text;
                        codeReader.reset();
                        searchingProductBarcodeScanVideoPanel.style.display = 'none';
                    }
                    if (err && !(err instanceof ZXing.NotFoundException)) {
                        console.error(err);
                        document.getElementById('searchingProductBarcode').value = err;
                    }
                })
            })

            document.getElementById('barcodeCreateButton').addEventListener('click', () => {
                barcodeCreateVideoPanel.style.display = 'block';
                codeReader.decodeFromVideoDevice(selectedDeviceId, 'barcodeCreateVideo', (result, err) => {
                    if (result) {
                        console.log(result);
                        document.getElementById('barcodeCreate').value = result.text;
                        codeReader.reset();
                        barcodeCreateVideoPanel.style.display = 'none';
                    }
                    if (err && !(err instanceof ZXing.NotFoundException)) {
                        console.error(err);
                        document.getElementById('barcodeCreate').value = err;
                    }
                })
            })

            document.getElementById('startButton').addEventListener('click', () => {
                codeReader.decodeFromVideoDevice(selectedDeviceId, 'video', (result, err) => {
                    if (result) {
                        console.log(result);
                        document.getElementById('barcode-new').value = result.text;
                        codeReader.reset();
                    }
                    if (err && !(err instanceof ZXing.NotFoundException)) {
                        console.error(err);
                        document.getElementById('barcode-new').value = err;
                    }
                })
            })

            sourceSelect.onchange = () => {
                selectedDeviceId = sourceSelect.value;
                codeReader.reset();
            };

//            sourceSelect.onchange = () => {
//                selectedDeviceId = sourceSelect.value;
//                document.getElementById('barcode-new').value = '';
//                codeReader.reset();
//                codeReader.decodeFromVideoDevice(selectedDeviceId, 'video', (result, err) => {
//                    if (result) {
//                        console.log(result);
//                        document.getElementById('barcode-new').value = result.text;
//                    }
//                    if (err && !(err instanceof ZXing.NotFoundException)) {
//                        console.error(err);
//                        document.getElementById('barcode-new').value = err;
//                    }
//                })
//                console.log('Reset.');
//            };
        })
        .catch((err) => {
            console.error(err)
        })
    })
</script>
