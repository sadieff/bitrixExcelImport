<?php

/*
    Основной скрипт для обработки exel файлов.

    Итак, есть файл с 60к товарами.
    Загружался раньше он вот сюда: https://istk-deutz.ru/local/obmen/xls2.php

    Если товар есть - то остаток из файла добавляем в склад 7 дней.
    Если товара нет - создаем новый товар и добавляем его в техническую папку.

    И правильно я понимаю: "При обновлении склада "В пути" или "Под заказ 7 дней" у позиции, которые отсутствовали в импорте, остаток и цена становятся 0."
    Т.е. если раньше у товара было что то в складах "В пути" или "Под заказ 7 дней", но в Exel документе этого товара нет совсем, то них надо остаток на складах и цену ставить на 0.
    Это же касается и файла 1 который грузится вот сюда? https://istk-deutz.ru/local/obmen/xls.php

    При обновлении склада "В пути" или "Под заказ 7 дней" у позиции, которые отсутствовали в импорте, остаток и цена становятся 0.
*/

header("Content-type: application/json; charset=utf-8");

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
Loader::includeModule("iblock");
Cmodule::IncludeModule("catalog");
$blockElement = new CIBlockElement;

const STEP_COUNT = 400;
$curentStep = $_POST['step'];
$endRow = false;
$logEditName = 0;
$logProductUpdate = $_POST['product_update'];
$logProductAdd = $_POST['product_add'];
$logPriceUpdate = $_POST['price_update'];
$logPriceAdd = $_POST['price_add'];
$logStorageUpdate = $_POST['storage_update'];
$logStorageAdd = $_POST['storage_add'];
$logPriceSkip = $_POST['price_skip'];

if (!file_exists('catalog.xls')) {
    $output = array(
        "type" => "error",
        "text" => "Ошибка при открытии файла",
    );
    echo json_encode($output);
}

// подключим класс для работы с талицами
require_once 'classes/PHPExcel.php';

$excel = PHPExcel_IOFactory::createReaderForFile('catalog.xls');

$excelObj = $excel->load('catalog.xls');
$worksheet = $excelObj->getSheet(0);
$lastRow = $worksheet->getHighestRow();

$output = []; // массив с ответом для ajax
$mapArticul = []; // карта артикулов для выборки из API Битрикс

$start = (($curentStep - 1) * STEP_COUNT)  + 1;
$end = $curentStep * STEP_COUNT;

// соберем всю информацию в цикле в массив $product
$product = [];

for ($row = $start; $row <= $end; $row++) {

    if($row > $lastRow) $endRow = true;
    if($row > $lastRow || $row == 1) continue;

    // Получим артикул
    $articul = (string)$worksheet->getCell('A' . $row)->getValue();

    // Добавим нули в начало, если длина артикула меньше 8
    if(strlen($articul) < 8) {
        for ($art = strlen($articul); $art < 8; $art++) {
            $articul = '0'.$articul;
        }
    }

    // Получим имя.
    $name = $articul.' '.$worksheet->getCell('B' . $row)->getValue();

    // Если в колонке D пусто, возьмем из C
    //$name = $worksheet->getCell('D' . $row)->getValue();
    //if ($name == '' || $name == '-') $name = $worksheet->getCell('C' . $row)->getValue();

    // Получим количество
    $count = $worksheet->getCell('D' . $row)->getValue();

    // Получим стоимость, приводим к себестоимости через формулу: минус 30%, плюс 20%
    $price = $worksheet->getCell('C' . $row)->getValue();
    /*$price = $price - $price * 0.30;
    $price = $price + $price * 0.20;*/

    // Сформируем массив
    $mapArticul[] = $articul;
    $product[$articul] = array(
        "ROW" => $row,
        "ARTICUL" => $articul,
        "NAME" => $name,
        "PRICE" => round($price, 2),
        "COUNT" => $count,
    );

}
unset($excel);
unset($excelObj);
unset($worksheet);

// Теперь обработаем данные, которые получили из таблицы.

$requestElements  = $blockElement::GetList(
    array("SORT" => "ASC"),
    array("IBLOCK_ID" => 25, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y", "PROPERTY_CML2_ARTICLE" => $mapArticul),
    false,
    false,
    array(
        "ID",
        "NAME",
        "IBLOCK_ID",
        "CATALOG_GROUP_1",
        "PROPERTY_CML2_ARTICLE",
        "PROPERTY_NAME_CORRECT",
        "PROPERTY_PRICE_FIXED",
        "PROPERTY_SKLAD_7",
    )
);
$arrElements = [];
while ($element = $requestElements -> GetNextElement()) {

    $item = $element->GetFields();

    $articul = $item["PROPERTY_CML2_ARTICLE_VALUE"];

    // проверим, надо ли изменить имя
    $excelProductName = $product[$articul]["NAME"];
    if ($item["PROPERTY_NAME_CORRECT_VALUE"] == "Нет" && $item["NAME"] != $excelProductName) {

        $arLoadProductArray = Array(
            "NAME" => $product[$articul]["NAME"],
        );
        $el = new CIBlockElement;
        $res = $el->Update($item['ID'], $arLoadProductArray);

        $logEditName++;

    }

    // расчитаем цену.  проверим, менять цену или нет
    $price = $product[$articul]["PRICE"];
    if($item["CATALOG_PRICE_ID_1"] != $price && $item["PROPERTY_PRICE_FIXED_VALUE"] != 1) {

        $arFields = Array(
            "PRODUCT_ID" => $item["ID"],
            "CATALOG_GROUP_ID" => 1, // Базовая цена
            "PRICE" => $price,
            "CURRENCY" => "RUB",
        );

        // получим код ценового предложения
        $requestPrice = CPrice::GetList(array(), array("PRODUCT_ID" => $item["ID"], "CATALOG_GROUP_ID" => 1));
        if ($price = $requestPrice->Fetch()) {
            CPrice::Update($price["ID"], $arFields);
            $logPriceUpdate++;
        }
        else {
            CPrice::Add($arFields);
            $logPriceAdd++;
        }

    } else {
        $logPriceSkip++;
    }

    // Добавим количество на складах

    $storageID = false;
    $storageCount = $product[$articul]["COUNT"];

    // получим значения из других складов, чтобы их приплюсовать в общую сумму остатка


    if($storageCount != $item["PROPERTY_SKLAD_7_VALUE"] || $item["CATALOG_QUANTITY"] < $storageCount) {

        if(!empty($item["PROPERTY_SKLAD_7"])) {
            $fullQuantity = $item["CATALOG_QUANTITY"] - $item["PROPERTY_SKLAD_7"];
            if($fullQuantity > 0) $storageCount = $fullQuantity;
        }

        // запишем в свойство товара
        $blockElement->SetPropertyValuesEx($item["ID"], 25, array("SKLAD_7" => $product[$articul]["COUNT"]));

        $requestStorage = CCatalogStoreProduct::GetList(array(), array("PRODUCT_ID" => $item["ID"], "STORE_ID" => 1));
        if ($arrStorage = $requestStorage->Fetch()) $storageID = $arrStorage["ID"];

        $arFieldsStorage = Array(
            "PRODUCT_ID" => $item["ID"],
            "STORE_ID" => 1,
            "AMOUNT" => $storageCount,
        );
        if ($storageID) {
            CCatalogStoreProduct::Update($storageID, $arFieldsStorage);
            CCatalogProduct::add(array("ID" => $item["ID"], "QUANTITY" => $storageCount));
            $logStorageUpdate++;
        } else {
            CCatalogStoreProduct::Add($arFieldsStorage);
            CCatalogProduct::add(array("ID" => $item["ID"], "QUANTITY" => $storageCount));
            $logStorageAdd++;
        }

    }

    // удалить найденные артикулы из массива
    unset($product[$articul]);
    $logProductUpdate++;

}

// оставшиеся товары добавить как товары

foreach($product as $item) {

    $arLoadProductArray = Array(
        "ACTIVE" => "Y",
        "IBLOCK_ID" => 25,
        "IBLOCK_SECTION_ID" => 180,
        "NAME" => $item["NAME"],
        "CODE" => strtolower(imTranslite($item["NAME"])),
        "PROPERTY_VALUES" => array(
            "CML2_ARTICLE" => $item["ARTICUL"],
            "SKLAD_7"  => $item["COUNT"],
            "NAME_CORRECT" => "Да"
        )
    );

    $idElement = $blockElement->Add($arLoadProductArray);

    if($idElement === false) {
        continue;
    }

    /* Добавляем параметры товара к элементу каталога */
    $arproduct = array(
        "ID" => $idElement,
        "VAT_INCLUDED" => "Y"
    );
    CCatalogProduct::Add($arproduct);


    $arFields = Array(
        "PRODUCT_ID" => $idElement,
        "CATALOG_GROUP_ID" => 1, // Базовая цена
        "PRICE" => $item["PRICE"],
        "CURRENCY" => "RUB",
    );

    // получим код ценового предложения
    $requestPrice = CPrice::GetList(array(), array("PRODUCT_ID" => $idElement, "CATALOG_GROUP_ID" => 1));
    if ($price = $requestPrice->Fetch()) {
        CPrice::Update($price["ID"], $arFields);
        $mapIdElements[$item["ARTICUL"]]["UPDATE"] = $price["ID"];
    }
    else {
        CPrice::Add($arFields);
    }

    // Добавим количество на складах

    $storageID = false;
    $storageCount = $item["COUNT"];
    $requestStorage = CCatalogStoreProduct::GetList( array(), array( "PRODUCT_ID" => $idElement, "STORE_ID" => 1 ) );
    if ($arrStorage = $requestStorage->Fetch()) $storageID = $arrStorage["ID"];

    $arFieldsStorage = Array(
        "PRODUCT_ID" => $idElement,
        "STORE_ID" => 1,
        "AMOUNT" => $storageCount,
    );
    $mapIdElements[$item["ARTICUL"]] = $arFieldsStorage;
    if ( $storageID ) {
        CCatalogStoreProduct::Update($storageID, $arFieldsStorage);
        CCatalogProduct::add(array("ID" => $idElement, "QUANTITY" => $storageCount));
    }
    else {
        CCatalogStoreProduct::Add($arFieldsStorage);
        CCatalogProduct::add(array("ID" => $idElement, "QUANTITY" => $storageCount));
    }

    $logProductAdd++;
}

// Сформируем ответ для ajax
$totalStep = ceil($lastRow / STEP_COUNT);
$percent = ($curentStep / $totalStep) * 100;

$output = array(
    "type" => "success",
    "step" => $curentStep,
    "total" => $totalStep,
    "percent" => round($percent, 2),
    "product_update" => $logProductUpdate,
    "product_add" => $logProductAdd,
    "edit_name" => $logEditName,
    "price_update" => $logPriceUpdate,
    "price_add" => $logPriceAdd,
    "storage_update" => $logStorageUpdate,
    "storage_add" => $logStorageAdd,
    "price_skip" => $logPriceSkip,
);

$endRow = true;
if($endRow === true) {
    $content = '<div>Товары, существовавшие до импорта: <b>'.$logProductUpdate.'</b></div>';
    $content .= '<div>Добавленно новых товаров: <b>'.$logProductAdd.'</b></div>';
    $content .= '<div>Обновлено цен: <b>'.$logPriceUpdate.'</b></div>';
    $content .= '<div>Добавлено цен: <b>'.$logPriceAdd.'</b></div>';
    $content .= '<div>Пропущено цен (зафиксированы): <b>'.$logPriceSkip.'</b></div>';
    $content .= '<div>Обновлено количество: <b>'.$logPriceUpdate.'</b></div>';
    $content .= '<div>Добавлено количество: <b>'.$logStorageAdd.'</b></div>';

    file_put_contents('exchange-log.txt',  $content);
}

echo json_encode($output);