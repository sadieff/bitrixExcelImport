<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
header("Content-type: application/json; charset=utf-8");

CModule::IncludeModule("iblock");
Cmodule::IncludeModule("catalog");

$logUpdate = $_POST['product_update'];
$curentStep = $_POST['step'];
$totalStep = $_POST['total_step'];
$iblock_id = 25;
const STEP_COUNT = 300;
$arrStocks = array(
    "SKLAD1",
    "SKLAD2",
    "SKLAD3",
    "SKLAD4",
    //"SKLAD5",
    //"SKLAD6",
    "SKLAD7",
    "SKLAD8",
    //"SKLAD9",
    "SKLAD10",
    "SKLAD10",
    "SKLAD_VPUTI",
    "SKLAD_7",
);
if(empty($totalStep) || $totalStep == 0) {
    $totalElement = CIBlockElement::GetList(
        array("SORT" => "ASC"),
        array("IBLOCK_ID" => $iblock_id),
        false,
        false,
        array("ID")
    );
    $totalStep = ceil(intval($totalElement->SelectedRowsCount()) / STEP_COUNT);
}

$requestElements = CIBlockElement::GetList(
    array("SORT" => "ASC"),
    array("IBLOCK_ID" => $iblock_id, "ID" => "35809"),
    false,
    array(
        "iNumPage" => $curentStep,
        "nPageSize" => STEP_COUNT,
    ),
    array("ID","IBLOCK_ID","CATALOG_QUANTITY","PROPERTY_*")
);
while ($element = $requestElements -> GetNextElement()){

    $item = $element->GetFields();
    $prop["PROPERTIES"] = $element->GetProperties();
    $arResult = array_merge($item, $prop);
    $count = 0;
    foreach ($arrStocks as $StItem){
        $count = $count + $arResult["PROPERTIES"][$StItem]["VALUE"];
    }

    if($count != $item["CATALOG_QUANTITY"]){
        $logUpdate++;
        /* Добавим количество в товар */
        $storageID = false;
        $requestStorage = CCatalogStoreProduct::GetList( array(), array( "PRODUCT_ID" => $arResult["ID"], "STORE_ID" => 1 ) );
        if ($arrStorage = $requestStorage->Fetch()) $storageID = $arrStorage["ID"];

        $arFieldsStorage = Array(
            "PRODUCT_ID" => $arResult["ID"],
            "STORE_ID" => 1,
            "AMOUNT" => $count,
        );
        if ( $storageID ) {
            CCatalogStoreProduct::Update($storageID, $arFieldsStorage);
            CCatalogProduct::add(array("ID" => $arResult["ID"], "QUANTITY" => $count));
        }
        else {
            CCatalogStoreProduct::Add($arFieldsStorage);
            CCatalogProduct::add(array("ID" => $arResult["ID"], "QUANTITY" => $count));
        }

    }

}

$content = '<div>Обновлено количество: <b>'.$logUpdate.'</b></div>';
$percent = ($curentStep / $totalStep) * 100;
file_put_contents('stockchecker-log.txt',  $content);

$output = array(
    "type" => "success",
    "step" => $curentStep,
    "total" => intval($totalStep),
    "percent" => round($percent, 2),
    "product_update" => $logUpdate,
);

echo json_encode($output);