<?php
require 'vendor/autoload.php'; // Путь к файлу автозагрузки PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Создаем новый объект класса Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$catalog = include 'dverprom.db.php';

// Устанавливаем заголовки столбцов
$columnHeaders = [
    'Название категории',
    'Ссылка на категорию',
    'Алиас категории',
    'Подкатегория',
    'Ссылка на подкатегорию',
    'Алиас подкатегории',
    'Продукты',
    'Ссылка на продукт',
    'Алиас продукта',
    'Артикул продукта',
    'Краткое описание продукта',
    'Описание продукта',
    'Картинки продукта'
];

$columnIndex = 1;
foreach ($columnHeaders as $header) {
    $sheet->setCellValueByColumnAndRow($columnIndex, 1, $header);
    $columnIndex++;
}

// Заполняем данные из массива
$row = 2;
foreach ($catalog['CATEGORIES'] as $category) {
    $sheet->setCellValueByColumnAndRow(1, $row, $category['TITLE']);
    $sheet->setCellValueByColumnAndRow(2, $row, $category['LINK']);
    $sheet->setCellValueByColumnAndRow(3, $row, $category['ALIAS']);

    // Обрабатываем подкатегории
    foreach ($category['SUB_CATEGORIES'] as $subCategory) {
        $sheet->setCellValueByColumnAndRow(4, $row, $subCategory['TITLE']);
        $sheet->setCellValueByColumnAndRow(5, $row, $subCategory['LINK']);
        $sheet->setCellValueByColumnAndRow(6, $row, $subCategory['ALIAS']);

        // Обрабатываем продукты
        foreach ($subCategory['PRODUCTS'] as $product) {
            $sheet->setCellValueByColumnAndRow(7, $row, $product['TITLE']);
            $sheet->setCellValueByColumnAndRow(8, $row, $product['LINK']);
            $sheet->setCellValueByColumnAndRow(9, $row, $product['ALIAS']);

            if (isset($product['CODE'])) {
                $sheet->setCellValueByColumnAndRow(10, $row, $product['CODE']);
                $sheet->setCellValueByColumnAndRow(11, $row, $product['SHORT_DESCRIPTION']);
                $sheet->setCellValueByColumnAndRow(12, $row, $product['DESCRIPTION']);

                // Обрабатываем картинки продукта
                if (isset($product['IMAGES']) && is_array($product['IMAGES'])) {
                    $images = implode(', ', $product['IMAGES']);
                    $sheet->setCellValueByColumnAndRow(13, $row, $images);
                } else {
                    $sheet->setCellValueByColumnAndRow(13, $row, '');
                }
            } else {
                $sheet->setCellValueByColumnAndRow(10, $row, '-');
                $sheet->setCellValueByColumnAndRow(11, $row, '-');
                $sheet->setCellValueByColumnAndRow(12, $row,  '-');
                // Обрабатываем картинки продукта
                if (isset($product['IMAGES']) && is_array($product['IMAGES'])) {
                    $images = implode(', ', $product['IMAGES']);
                    $sheet->setCellValueByColumnAndRow(13, $row, $images);
                } else {
                    $sheet->setCellValueByColumnAndRow(13, $row, '');
                }
            }

            $row++;
        }
    }
}


// Устанавливаем автоширину столбцов
$sheet->getColumnDimension('A')->setAutoSize(true);
$sheet->getColumnDimension('B')->setAutoSize(true);
$sheet->getColumnDimension('C')->setAutoSize(true);
$sheet->getColumnDimension('D')->setAutoSize(true);
$sheet->getColumnDimension('E')->setAutoSize(true);
$sheet->getColumnDimension('F')->setAutoSize(true);
$sheet->getColumnDimension('G')->setAutoSize(true);
$sheet->getColumnDimension('H')->setAutoSize(true);
$sheet->getColumnDimension('I')->setAutoSize(true);
$sheet->getColumnDimension('J')->setAutoSize(true);
$sheet->getColumnDimension('K')->setAutoSize(true);
$sheet->getColumnDimension('L')->setAutoSize(true);
$sheet->getColumnDimension('M')->setAutoSize(true);

// Создаем объект класса Xlsx Writer и сохраняем файл
$writer = new Xlsx($spreadsheet);
$writer->save('catalog1.xlsx');
?>
