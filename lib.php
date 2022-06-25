<?php
require_once('config.php');

$mysql_conn = new mysqli($mysqlHost, $mysqlUser, $mysqlPass);

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

/* select_db */
$mysql_conn->select_db($mysqlName);

$sql = "SET NAMES utf8";
$mysql_conn->query($sql);
$sql = "SET FOREIGN_KEY_CHECKS = 0";
$mysql_conn->query($sql);
$sql = "SET SESSION group_concat_max_len = 100000000";
$mysql_conn->query($sql);
$sql = "SET SESSION time_zone = '+7:00';";
$mysql_conn->query($sql);
$sql = "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY,',''));";
$mysql_conn->query($sql);

//$params = [
//    'name' => 'Vinamilk',
//    'barcode' => '1470695799272',
//]
function create_product($params = [], $mysql_conn) {
    $productId = createProduct($params, $mysql_conn);
    $params['product_id'] = $productId;

    $sku = getProductSku($productId, $mysql_conn);
    if (trim($params['barcode']) == '') {
        $params['barcode'] = $sku;
    }
    updateProductBarcode($params, $mysql_conn);

    createProductName($params, $mysql_conn);
    createProductUnitName($params, $mysql_conn);

    if ($params['seller'] != '') {
        $sellerId = isExistedSeller($params, $mysql_conn);

        if ($sellerId === null) {
            $sellerId = createSeller($params, $mysql_conn);
        }

        if ($sellerId !== null) {
            $params['seller_id'] = $sellerId;
            createSellerProduct($params, $mysql_conn);
        }
    }
}

function update_product($params = [], $mysql_conn) {
    $quote = 'quote';
    $sql = "
        UPDATE catalog_product p
        -- JOIN catalog_product_entity_varchar pn ON pn.product_id = p.id AND pn.attribute_code = 'name'
        -- JOIN catalog_product_entity_varchar pu ON pu.product_id = p.id AND pu.attribute_code = 'unit_name'
        SET
            p.list_price = {$quote($params['list_price'])}
            , p.unit_count = {$quote($params['unit_count'])}
            -- , pn.`value` = {$quote($params['name'])}
            -- , pu.`value` = {$quote($params['unit_name'])}
        WHERE
            p.id = {$quote($params['product_id'])}
    ";

    $rowCount = 0;
    if($resource = $mysql_conn->query($sql)) {
        $rowCount = $mysql_conn->affected_rows;
//        $resource->free();
    }

    $sql = "
        INSERT INTO catalog_product_entity_varchar (product_id, attribute_code, value, language_id)
        VALUES ({$quote($params['product_id'])}, 'name', {$quote($params['name'])}, 1)
        ON DUPLICATE KEY UPDATE
            value = VALUES(value)
    ";

    $rowCount = 0;
    if($resource = $mysql_conn->query($sql)) {
        $rowCount = $mysql_conn->affected_rows;
//        $resource->free();
    }

    $sql = "
        INSERT INTO catalog_attribute_listdata (attribute_code, value, language_id)
        VALUES ('unit_name', {$quote($params['unit_name'])}, 1)
        ON DUPLICATE KEY UPDATE
            value = VALUES(value)
    ";

    $rowCount = 0;
    if($resource = $mysql_conn->query($sql)) {
        $rowCount = $mysql_conn->affected_rows;
//        $resource->free();
    }

    $sql = "
        INSERT INTO catalog_product_entity_int (product_id, attribute_code, value)
        SELECT
            {$quote($params['product_id'])}
            , l.attribute_code
            , l.id
        FROM catalog_attribute_listdata l
        WHERE
            l.attribute_code = 'unit_name'
            AND l.value = {$quote($params['unit_name'])}
            AND l.language_id = 1
        ON DUPLICATE KEY UPDATE
            value = VALUES(value)
    ";

    $rowCount = 0;
    if($resource = $mysql_conn->query($sql)) {
        $rowCount = $mysql_conn->affected_rows;
//        $resource->free();
    }

    $rowCount = updateProductBarcode($params, $mysql_conn);

    return $rowCount;
}

//$params = [
//    'name' => 'Vinamilk',
//    'barcode' => '1470695799272',
//]
function search_product($searchingProductName = '', $searchingProductBarcode = '', $mysql_conn) {
    $quote = 'quote';

    $where = '';
    if ($searchingProductName != '') {
        $where .= "WHERE \r\n\t pn.value LIKE {$quote("%{$searchingProductName}%")}";

        if ($searchingProductBarcode != '') {
            $where .= "\r\n\t OR pb.barcode = {$quote($searchingProductBarcode)}";
        }
    } else {
        if ($searchingProductBarcode != '') {
            $where .= "WHERE \r\n\t pb.barcode = {$quote($searchingProductBarcode)}";
        }
    }

    $sql = "
        SELECT
            pn.value name
            , IF(
                ps.special_price IS NOT NULL
                , IF(
                    ps.special_to IS NOT NULL
                    , IF(
                        NOW() >= IFNULL(ps.special_from, '0000-00-00 00:00:00') AND NOW() <= ps.special_to
                        , ps.special_price
                        , ps.price
                    )
                    , IF(
                        NOW() >= IFNULL(ps.special_from, '0000-00-00 00:00:00')
                        , ps.special_price
                        , ps.price
                    )
                )
                , ps.price
            ) current_price
            , s.name seller
            , ps.product_id
            , ps.seller_id
            , p.list_price
            , ps.price
            , ps.special_price
            , ps.special_from
            , ps.special_to
            , p.sku
            , p.unit_count
            , pu.value unit_name
            , (SELECT GROUP_CONCAT(barcode) FROM catalog_product_barcode WHERE product_id = p.id) barcode
        FROM catalog_product p
        LEFT JOIN catalog_product_barcode pb ON pb.product_id = p.id
        JOIN catalog_product_entity_varchar pn ON pn.product_id = p.id AND pn.attribute_code = 'name' AND pn.language_id = 1
        LEFT JOIN catalog_product_entity_int puo ON puo.product_id = p.id AND puo.attribute_code = 'unit_name'
        LEFT JOIN catalog_attribute_listdata pu ON pu.attribute_code = puo.attribute_code AND pu.id = puo.value AND pu.language_id = 1
        JOIN catalog_product_seller ps ON ps.product_id = p.id
        JOIN seller s ON s.id = ps.seller_id
        $where
        GROUP BY ps.id
        ORDER BY pn.value COLLATE utf8_unicode_ci, current_price, s.name COLLATE utf8_unicode_ci
    ";

    $results = [];
    if($resource = $mysql_conn->query($sql)) {
        $results = $resource->fetch_all(MYSQLI_ASSOC);

        $resource->free();
    }

    return $results;
}

//$params = [
//    'product_id' => 'product_id',
//    'seller_id' => 'seller_id',
//]
function update_seller($params = [], $mysql_conn) {
    $quote = 'quote';
    $sql = "
        UPDATE catalog_product_seller ps
        SET
            ps.price = {$quote($params['price'])}
            , ps.special_price = {$quote($params['special_price'])}
            , ps.special_from = {$quote($params['special_from'])}
            , ps.special_to = {$quote($params['special_to'])}
        WHERE
            ps.product_id = {$quote($params['product_id'])}
            AND ps.seller_id = {$quote($params['seller_id'])}
    ";

    $rowCount = 0;
    if($resource = $mysql_conn->query($sql)) {
        $rowCount = $mysql_conn->affected_rows;
//        $resource->free();
    }

    return $rowCount;
}

function search_promotion($params = [], $mysql_conn) {
    $quote = 'quote';
    $sql = "
        SELECT
            pn.value name
            , s.name seller
            , p.list_price
            , ps.special_price current_price
            , DATE(ps.special_to) special_to
            , p.unit_count
        FROM catalog_product p
        LEFT JOIN catalog_product_barcode pb ON pb.product_id = p.id
        JOIN catalog_product_entity_varchar pn ON pn.product_id = p.id AND pn.attribute_code = 'name'
        JOIN catalog_product_seller ps ON ps.product_id = p.id
        JOIN seller s ON s.id = ps.seller_id
        WHERE
            ps.special_price IS NOT NULL
            AND (
                (
                    ps.special_to IS NOT NULL
                    AND NOW() >= IFNULL(ps.special_from, '0000-00-00 00:00:00') 
                    AND NOW() <= ps.special_to
                )
                OR (
                    ps.special_to IS NULL
                    AND NOW() >= IFNULL(ps.special_from, '0000-00-00 00:00:00')
                )
            )
            AND (
                pb.barcode = {$quote($params['barcode'])}
                OR pn.value LIKE {$quote("%{$params['name']}%")}
            )
        ORDER BY s.name COLLATE utf8_unicode_ci, pn.value COLLATE utf8_unicode_ci
    ";

    $results = [];
    if($resource = $mysql_conn->query($sql)) {
        $results = $resource->fetch_all(MYSQLI_ASSOC);

        $resource->free();
    }

    return $results;
}

function quote($value = null) {
    if (is_array($value)) {
        foreach ($value as &$val) {
            $val = quote($val);
        }
        return implode(', ', $value);
    }

    if (is_int($value)) {
        return $value;
    } elseif (is_float($value)) {
        return sprintf('%F', $value);
    } elseif (is_null($value)) {
        return 'null';
    }

    return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
}

/**
 * Quotes a value and places into a piece of text at a placeholder.
 *
 * The placeholder is a question-mark; all placeholders will be replaced
 * with the quoted value.   For example:
 *
 * <code>
 * $text = "WHERE date < ?";
 * $date = "2005-01-02";
 * $safe = $sql->quoteInto($text, $date);
 * // $safe = "WHERE date < '2005-01-02'"
 * </code>
 *
 * @param string  $text  The text with a placeholder.
 * @param mixed   $value The value to quote.
 * @param integer $count OPTIONAL count of placeholders to replace
 * @return string An SQL-safe quoted value placed into the original text.
 */
function quoteInto($text, $value, $count = null)
{
    if ($count === null) {
        return str_replace('?', quote($value), $text);
    } else {
        while ($count > 0) {
            if (strpos($text, '?') !== false) {
                $text = substr_replace($text, $this->quote($value), strpos($text, '?'), 1);
            }
            --$count;
        }
        return $text;
    }
}

function create_seller_product($params = [], $mysql_conn) {
    $sellerProductId = 0;

    if ($params['seller'] != '') {
        $sellerId = isExistedSeller($params, $mysql_conn);

        if ($sellerId === null) {
            $sellerId = createSeller($params, $mysql_conn);
        }

        if ($sellerId !== null) {
            $params['seller_id'] = $sellerId;
            $sellerProductId = createSellerProduct($params, $mysql_conn);
        }
    }

    return $sellerProductId;
}

function createSellerProduct($params = [], $mysql_conn) {
    $sql = "
        INSERT INTO catalog_product_seller (product_id, seller_id, status, visibility, price, special_price, special_from, special_to)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $mysql_conn->prepare($sql);
    $status = 1;
    $visibility = 1;
    $stmt->bind_param('iiiiiiss', $params['product_id'], $params['seller_id'], $status, $visibility, $params['price'], $params['special_price'], $params['special_from'], $params['special_to']);
    $stmt->execute();

    $sellerProductId = $stmt->insert_id;
    $stmt->close();

    return $sellerProductId;
}

function createProductName($params = [], $mysql_conn) {
    $quote = 'quote';
    $sql = "
        INSERT INTO catalog_product_entity_varchar (product_id, attribute_code, value, language_id)
        VALUES ({$quote($params['product_id'])}, 'name', {$quote($params['name'])}, 1)
        ON DUPLICATE KEY UPDATE
            value = VALUES(value)
    ";

    $rowCount = null;
    if($resource = $mysql_conn->query($sql)) {
        $rowCount = $mysql_conn->affected_rows;
//        $resource->free();
    }

    return $rowCount;
}

function createProductUnitName($params = [], $mysql_conn) {
    $quote = 'quote';
    $sql = "
        INSERT INTO catalog_attribute_listdata (attribute_code, value)
        VALUES ('unit_name', {$quote($params['unit_name'])})
        ON DUPLICATE KEY UPDATE
            value = VALUES(value)
    ";

    $rowCount = 0;
    if($resource = $mysql_conn->query($sql)) {
        $rowCount = $mysql_conn->affected_rows;
//        $resource->free();
    }

    $sql = "
        INSERT INTO catalog_product_entity_int (product_id, attribute_code, value)
        SELECT
            {$quote($params['product_id'])}
            , l.attribute_code
            , l.id
        FROM catalog_attribute_listdata l
        WHERE
            l.attribute_code = 'unit_name'
            AND l.value = {$quote($params['unit_name'])}
            AND l.language_id = 1
        ON DUPLICATE KEY UPDATE
            value = VALUES(value)
    ";

    $rowCount = 0;
    if($resource = $mysql_conn->query($sql)) {
        $rowCount = $mysql_conn->affected_rows;
//        $resource->free();
    }

    return $rowCount;
}

function getProductBarcode($params = [], $mysql_conn) {
    $quote = 'quote';
    $sql = "
        SELECT
            b.barcode
        FROM catalog_product_barcode b
        WHERE
            b.product_id = {$quote($params['product_id'])}
    ";

    $barcodes = [];
    if($resource = $mysql_conn->query($sql)) {
        while ($row = $resource->fetch_assoc()) {
            $barcodes[$row['barcode']] = $row['barcode'];
        }

        $resource->free();
    }

    return $barcodes;
}

function updateProductBarcode($params = [], $mysql_conn) {
    $quote = 'quote';

    $barcodes = getProductBarcode($params, $mysql_conn);

    $params['barcode'] = str_replace(' ', '', $params['barcode']);
    $newBarcodes = explode(',', $params['barcode']);
    if (count($newBarcodes)) {
        foreach ($newBarcodes as $newBarcode) {
            $barcodes[$newBarcode] = $newBarcode;
        }
    }

    $sql = "
        DELETE FROM catalog_product_barcode
        WHERE
            barcode IN ({$quote($barcodes)})
    ";

    $rowCount = 0;
    if($resource = $mysql_conn->query($sql)) {
        $rowCount = $mysql_conn->affected_rows;
//        $resource->free();
    }

    $barcodeInsertValues = [];
    if (count($barcodes)) {
        foreach ($barcodes as $barcode) {
            $barcodeInsertValues[] = "{$quote($barcode)}, {$quote($params['product_id'])}";
        }
    }
    $barcodeInsertValues = implode('), (', $barcodeInsertValues);
    $sql = "
        INSERT INTO catalog_product_barcode (barcode, product_id)
		VALUES ($barcodeInsertValues)
    ";

    $rowCount = 0;
    if($resource = $mysql_conn->query($sql)) {
        $rowCount = $mysql_conn->affected_rows;
//        $resource->free();
    }

    return $rowCount;
}

function createProduct($params = [], $mysql_conn) {
    $params['sku'] = isset($params['barcode']) && $params['barcode'] != '' && isExistedSku($params['barcode'], $mysql_conn) == 0 ? $params['barcode'] : generateSku($mysql_conn);
    $type = 'physical';
    $status = 1;
    $visibility = 1;

    $quote = 'quote';
    $sql = "
        INSERT INTO catalog_product (sku, type, status, visibility, list_price, unit_count)
		VALUES ({$quote($params['sku'])}, {$quote($type)}, {$quote($status)}, {$quote($visibility)}, {$quote($params['list_price'])}, {$quote($params['unit_count'])})
    ";

    $productId = 0;
    if($resource = $mysql_conn->query($sql)) {
        $productId = $mysql_conn->insert_id;
//        $resource->free();
    }

    return $productId;
}

function createSeller($params = [], $mysql_conn) {
    $sql = "
        INSERT INTO seller (name, slug, logo, status)
		VALUES ('{$params['seller']}', null, null, 1)
    ";

    $sellerId = 0;
    if($resource = $mysql_conn->query($sql)) {
        $sellerId = $mysql_conn->insert_id;
//        $resource->free();
    }

    return $sellerId;
}

function generateSku($mysql_conn) {
    $sku = random_int(1000000000000, 9999999999999);
    if (isExistedSku($sku, $mysql_conn) > 0) {
        return generateSku($mysql_conn);
    }

    return $sku;
}

function isExistedSeller($params = [], $mysql_conn) {
    $sql = "
        SELECT
            s.id
        FROM seller s
        WHERE
            s.name = '{$params['seller']}'
    ";

    $sellerId = 0;
    if($resource = $mysql_conn->query($sql)) {
        $row = $resource->fetch_assoc();
        $sellerId = $row['id'];
        $resource->free();
    }

    return $sellerId;
}

function isExistedSku($sku, $mysql_conn) {
    $quote = 'quote';
    $sql = "
        SELECT
            p.id
        FROM catalog_product p
        WHERE
            p.sku = {$quote($sku)}
    ";

    $productId = 0;
    if($resource = $mysql_conn->query($sql)) {
        while ($row = $resource->fetch_assoc()) {
            $productId = $row['id'];
        }

        $resource->free();
    }

    return $productId;
}

function getProductSku($productId, $mysql_conn) {
    $quote = 'quote';
    $sql = "
        SELECT
            p.sku
        FROM catalog_product p
        WHERE
            p.id = {$quote($productId)}
    ";

    $sku = '';
    if($resource = $mysql_conn->query($sql)) {
        while ($row = $resource->fetch_assoc()) {
            $sku = $row['sku'];
        }

        $resource->free();
    }

    return $sku;
}
