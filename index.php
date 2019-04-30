<?php

// permission to download all files
define("DOWNLOAD", 0);


// data to connect to DB
$host = '127.0.0.1';
$db   = 'parser_db';
$user = 'root';
$pass = '';
$charset = 'utf8';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO($dsn, $user, $pass, $opt) or die("Could not connect to db");

// Creating an object with data from an XML file
$products = simplexml_load_file('products.xml') or die ("Error: Cannot create object");

//$countIx = 0;

// XML file parsing
    foreach ($products->item as $item)
    {
        $product = array(
            'prod_name' => strval($item->prod_name),
            'prod_id' => strval($item->prod_id),
            'prod_price' => strval($item->prod_price),
            'prod_tax_id' => strval($item->prod_tax_id),
            'taxpercent' => strval($item->taxpercent),
            'prod_amount' => strval($item->prod_amount),
            'prod_symbol' => strval($item->prod_symbol),
            'prod_weight' => strval($item->prod_weight),
            'prd_name' => strval($item->prd_name),
            'prod_ean' => strval($item->prod_ean),
            'prod_desc' => strval($item->prod_desc),
            'prod_link' => strval($item->prod_link),
            'prod_price_base' => strval($item->prod_price_base),
            'prod_price_net_base' => strval($item->prod_price_net_base),
            'prod_price_net' => strval($item->prod_price_net),
            'cat_path' => strval($item->cat_path),
            'prod_img' => implode(", ", (array)$item->prod_img->img),
        );

//        $countIx++;


// Check whether there is a database with such prod_id, and getting an array with pictures
        $sql = 'SELECT prod_img FROM products WHERE prod_id=:prod_id';
        $statement = $pdo->prepare($sql);
        $statement->execute([':prod_id' => $product['prod_id']]);
        $product_img = $statement->fetchColumn();

        $keys = array_keys($product);
        $allowed = implode(", ", $keys);
        $values = ":" . implode(", :", $keys);

        if ( !$product_img ) {
            $sql = "INSERT INTO products ($allowed) VALUES ($values)";
            $stmt= $pdo->prepare($sql);
            $stmt->execute($product);
        }
// If the constant is true, files will start downloading.
        if (DOWNLOAD)
        {
            $prod_id = $product['prod_id'];
            $images = explode(", ", $product_img);


            foreach ($images as $image){
                if (!is_dir("images/" . $prod_id . "/")) {
                    $path = mkdir("images/" . $prod_id . "/", 0700, true);
                    header("refresh: 0;"); exit;
                }else{
                    $path = "images/" . $prod_id . "/";
                }

                $link = curl_init($image);
                $file_name = pathinfo($image);
                $fp = fopen("$path" . $file_name['basename'], 'ab');
                curl_setopt($link, CURLOPT_FILE, $fp);
                curl_setopt($link, CURLOPT_HEADER, 0);
                curl_exec($link);
                curl_close($link);
                fclose($fp);
            }
        }
    }