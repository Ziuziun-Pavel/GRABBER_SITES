<?php
$start = microtime(true);
header("Content-Type: text/html; charset=utf-8");
//header("Content-Type: text/html; charset=windows-1251");
require_once('simple_html_dom.php');
require_once('Array2XML.php');

define('URL','https://dverprom.by/katalog/');
define('VIEW','?limit=3');

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

function loadFile($src, $category) {
    $filename = basename($src);
    $dirPath = 'images/' . $category;
    $filePath = $dirPath . '/' . $filename;

    // Проверяем, существует ли папка
    if (!file_exists($dirPath)) {
        mkdir($dirPath, 0777, true); // Создаем папку, если она не существует
    }

    // Проверяем, существует ли файл
    if (file_exists($filePath)) {
        echo 'Файл уже существует: ' . $filePath . PHP_EOL;
        // Удаляем дублирующиеся файлы в папке
        removeDuplicateFiles($dirPath, $filename);
    }

    // Получаем содержимое изображения
    $imageContent = file_get_contents($src);

    // Сохраняем содержимое в файловой системе
    if ($imageContent !== false) {
        file_put_contents($filePath, $imageContent);
        echo 'Изображение успешно загружено и сохранено: ' . $filePath . PHP_EOL;
    } else {
        echo 'Не удалось загрузить изображение.' . PHP_EOL;
    }
}

function removeDuplicateFiles($dirPath, $filename) {
    $files = glob($dirPath . '/*'); // Получаем список файлов в папке

    foreach ($files as $file) {
        if (is_file($file) && basename($file) === $filename) {
            unlink($file); // Удаляем дублирующийся файл
            echo 'Удален дублирующийся файл: ' . $file . PHP_EOL;
        }
    }
}

function getCategoryName($link) {
    $pattern = '/\/katalog\/([^\/]+)/';
    preg_match($pattern, $link, $matches);

    return $matches[1];
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

        $prods[] = getProduct($link, $product->href);
    }
    return $prods;
}

function getProduct($catLink = '', $productLink = '') {
    $html = file_get_html($productLink);
    var_dump('pr: ' . $productLink);
    $array = explode('/', rtrim($productLink, '/'));

    $linkCategory = getCategoryName($catLink);
    $productCategory = getCategoryName($productLink);

    if ($linkCategory === $productCategory) {
        $params = array(
            'LINK' => $productLink,
            'ALIAS' => basename(end($array)),
            'TITLE' => $html->find('.catalogue__product-name', 0)->plaintext,
            'CODE' => $html->find('.product-page__vendor-code span', 0)->plaintext,
            'SHORT_DESCRIPTION' =>
                ($html->find('#product .short-descr.editor h1', 0) ? $html->find('#product .short-descr.editor h1', 0)->plaintext :
                    ($html->find('#product .short-descr.editor p', 0) ? $html->find('#product .short-descr.editor p', 0)->plaintext :
                        ($html->find('#product .short-descr.editor', 0) ? $html->find('#product .short-descr.editor', 0)->plaintext : ''))),
        );

        $description = '';
        $shortDescription = $html->find('.product-short-description');
        switch (true) {
            case ($shortDescription):
                foreach ($shortDescription as $txt) {
                    $text = $txt->innertext;
                    $description .= $text . '<br>';
                }
                break;
            case ($descriptionElement = $html->find('.editor')):
                foreach ($descriptionElement as $txt) {
                    $text = $txt->innertext;
                    $description .= $text . '<br>';
                }
                break;
            default:
                break;
        }

        $params['DESCRIPTION'] = $description;

        foreach ($html->find('.product-page__img-image ') as $c => $img) {
            $c++;
            $category = getCategoryName($productLink);
            $params['IMAGES'][] = 'images/' . $category . '/' . basename($img->src);
            loadFile($img->src, $category);
        }
    } else {
        $params = array(
            'LINK' => $productLink,
            'ALIAS' => end($array),
        );
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
