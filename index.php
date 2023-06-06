<?php
$start = microtime(true);
header("Content-Type: text/html; charset=utf-8");
//header("Content-Type: text/html; charset=windows-1251");
require_once('simple_html_dom.php');
require_once('Array2XML.php');

define('URL','https://dverprom.by/katalog/');
define('VIEW','?limit=2');

$i = 0;
$html = file_get_html(URL);

$catalog = array();

foreach ($html->find('.nav-submenu__item') as $ins ) {

    $a = $ins->first_child('a');
    $href = $a->href;
    $array = explode('/', rtrim($href, '/'));
    $alias = end($array);

    $category = array(
        'TITLE' => $a->plaintext,
        'LINK' => $href,
        'ALIAS' => $alias,
    );

    $obj = $a->parent()->last_child()->outertext;
    $obj = str_get_html($obj);

    $list = $ins->find('ul li');

    if (!empty($list)) {
        var_dump('1');
        $category['SUB_CATEGORIES'] = getSubCategories($obj->find('ul li'));
    } else {
        var_dump('2');
        $catalog['CATEGORIES'][$i]['PRODUCTS'] = getProducts($a->href);
    }

    $catalog['CATEGORIES'][$i] = $category;
    $i++;
}


function createDir($arr = array()){
    $path = '';
    foreach ($arr as $folder) {
        $path .= $folder.'/';
        @mkdir($path);
    }
    return (file_exists("https://" . $path));
}

function loadFile($path = '', $link = '', $ico = false) {
    $link = substr($link, 1);
    $link = substr($link, 0, -1);
    $link = explode('/', $link);
    $name = array_pop($link);
    $name .= ($ico) ? '_icon.' : '.';
    $exp = explode('.', $path);
    $exp = end($exp);
    if (createDir($link)) {
        $link = implode('/', $link);
        $link .= '/';
        $link = $link . $name . $exp;
        set_time_limit(30);
        $content = file_get_contents($path);
        file_put_contents($link, $content);
        return $link;
    }
}


function getUTF8($str = '') {
    set_time_limit(5);
    return mb_convert_encoding($str, "UTF-8", "Windows-1251");
}

function getSubCategories($obj) {
    set_time_limit(30);
    $subCats = array();
    foreach ($obj as $li) {
        $link = $li->first_child()->href;
        if ($link) {
            $array = explode('/', rtrim($link, '/'));
            $alias_sub = end($array);
            $category = array(
                'TITLE' => $li->plaintext,
                'LINK' => $link,
                'ALIAS' => $alias_sub,
                'PRODUCTS' => getProducts($link)
            );
            $subCats[] = $category;
        }
    }
    return $subCats;
}


function getProducts($link = '') {
    $html = file_get_html($link.VIEW);
    var_dump($link);
    $prods = array();
    foreach ($html->find('#mainContainer ul li .products-list__caption a') as $product) {
        set_time_limit(30);
        $prods[] = getProduct($product->href);
    }
    return $prods;
}

function getProduct($link = '') {
    $html = file_get_html($link);
    var_dump('pr: ' . $link);
    $array = explode('/', rtrim($link, '/'));
    $params = array(
        'LINK' => $link,
        'ALIAS' => end($array),
        'TITLE' => $html->find('.catalogue__product-name', 0)->plaintext,
        'CODE' => $html->find('.product-page__vendor-code span', 0)->plaintext,
        'SHORT_DESCRIPTION' =>
            ($html->find('#product .short-descr.editor h1', 0) ? $html->find('#product .short-descr.editor h1', 0)->plaintext :
                ($html->find('#product .short-descr.editor p span', 0) ? $html->find('#product .short-descr.editor p span', 0)->plaintext :
                    ($html->find('#product .short-descr.editor li', 0) ? $html->find('#product .short-descr.editor li', 0)->plaintext : ''))),
            );

    $description = '';
    $shortDescription = $html->find('.product-short-description');
    if ($shortDescription) {
        foreach ($shortDescription as $txt) {
            $text = $txt->innertext;
            $description .= $text . '<br>';
        }
    } else {
        $descriptionElement = $html->find('.editor', 0);
        if ($descriptionElement) {
            $description = $descriptionElement->innertext;
        } else {
            // Добавьте другие проверки и условия, если есть дополнительные варианты описания товара
            $customDescriptionElement = $html->find('.tabs-content__inner .editor', 0);
            if ($customDescriptionElement) {
                $description = $customDescriptionElement->innertext;
            }
        }
    }

    $params['DESCRIPTION'] = $description;

    $imageCounter = 1;
    foreach ($html->find('.product-page__img-image ') as $img) {
        $params['IMAGES'][] = $img->src;
        $imageCounter++;
    }

    return $params;
}

$content = "<?php\n\n";
$content .= '$catalog = ' . var_export($catalog, true) . ";\n\n";
$content .= "return \$catalog;";
file_put_contents('dverprom.db.php', $content);


print '<pre>';
print_r($catalog);
print '</pre>';
echo 'Время выполнения скрипта: '.(microtime(true) - $start).' сек.';
