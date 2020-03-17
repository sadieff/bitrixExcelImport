<?php
ini_set('display_errors', 'On'); // сообщения с ошибками будут показываться
error_reporting(E_ALL); // E_ALL - отображаем ВСЕ ошибки
include_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
Loader::includeModule("highloadblock");

//$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/custom-admin.css");
$APPLICATION->AddHeadScript("/bitrix/js/sadieff.export/jquery-1.9.1.min.js");

if($REQUEST_METHOD == "POST" && $apply != ""){

    /* Загрузим каталог */
    if(!empty($_FILES['file']['name'])) {

        $uploadDir = $_SERVER["DOCUMENT_ROOT"] . '/local/exchange/';

        if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . 'catalog.xls')) {
            $result = true;
        } else {
            $result = false;
        }

    }
}

$aTabs = array(
    array(
        "DIV" => "edit1",
        "TAB" => "Загрузить файл",
        "ICON" => "",
        "TITLE" => "Загрузить exel файл",
    ),
    array(
        "DIV" => "edit2",
        "TAB" => "Журнал",
        "ICON" => "",
        "TITLE" => "Последние действия",
    ),
);

$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
    );

    $tabControl->Begin(); ?>

    <form action="<? echo($APPLICATION->GetCurPage()); ?>?lang=<? echo(LANG); ?>" method="post" enctype=multipart/form-data>

    <?
        foreach($aTabs as $aTab):
            $tabControl->BeginNextTab(); ?>

            <? if($aTab["DIV"] == "edit1"): ?>
                <tr>
                    <td><span class="required">*</span>Выберите файл:</td>
                    <td><input type="file" name="file"></td>
                </tr>
                <? if($REQUEST_METHOD == "POST" && $apply != ""): ?>
                <tr>
                    <td colspan="2" class="text-center">
                        <? if($result == true): ?>
                            <span class="alert-green">
                                Файл успешно загружен. Начало обработки.
                            </span>
                            <div class="loading">
                                <span class="result" id="exportResult"></span>
                            </div>

                        <? else: ?>
                            <span class="alert-red">
                                Файл не был загружен.
                            </span>
                        <? endif; ?>
                    </td>
                </tr>
                <? endif; ?>
            <? elseif($aTab["DIV"] == "edit2"): ?>
                <div id="loginfo"></div>
            <? endif; ?>

        <?
        endforeach;
    $tabControl->Buttons();
    ?>

        <input type="submit" name="apply" value="Сохранить и запустить" class="adm-btn-save" />
        <input type="submit" name="default" value="Отменить" />

    </form>

<? $tabControl->End();?>

    <? if($REQUEST_METHOD == "POST" && $apply != ""): ?>
        <script>

            startExport(1);
            log_exchange();

            function startExport(step, product_update = 0, product_add = 0, price_update = 0, price_add = 0, storage_update = 0, storage_add = 0, price_skip = 0){

                $.ajax({
                    url:'/local/exchange/handler.php',
                    data: {
                        step: step,
                        product_update: product_update,
                        product_add: product_add,
                        price_update: price_update,
                        price_add: price_add,
                        storage_update: storage_update,
                        storage_add: storage_add,
                        price_skip: price_skip
                    },
                    type:'post',
                    dataType: 'JSON',
                    success:function(data){
                        $('#exportResult').text(data.percent+'%').css('width', data.percent+'%');
                        var step = +data.step + 1;
                        if(data.step < data.total) startExport(step, +data.product_update, +data.product_add, +data.price_update, +data.price_add, +data.storage_update, +data.storage_add, +data.price_skip);
                            else log_exchange();
                    }
                });

            }

        </script>
    <? endif; ?>

    <script>
        log_exchange();
        function log_exchange() {
            $.ajax({
                url:'/local/exchange/exchange-log.txt?21',
                data: '',
                type:'post',
                dataType: 'html',
                success:function(data){
                    $('#loginfo').html(data);
                }
            });
        }
    </script>

    <style>
        .text-center {
            text-align: center;
        }
        .alert-green {
            color: green;
        }
        .alert-red {
            color: red;
        }
        .loading {
            border: 1px solid #c5c5c5;
            margin: 30px auto;
            width: 80%;
        }
        #exportResult {
            display: block;
            background: rgba(0, 128, 0, 0.75);
            color: #fff;
            line-height: 31px;
            height: 31px;
            margin: 1px;
            box-sizing: border-box;
            width: 0;
        }
        #loginfo b {
            display: inline-block;
            font-weight: 700;
            color: green;
            margin-left: 5px;
        }
        #loginfo div {
            margin-bottom: 5px;
        }
    </style>

<? require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php"); ?>