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

$aTabs = array(
    array(
        "DIV" => "edit1",
        "TAB" => "Перерасчет",
        "ICON" => "",
        "TITLE" => "Перерасчет складов",
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
                    <td colspan="2">
                        Данный модуль запустит скрипт перерасчета количества товаров на складах для всех товаров. Будет перерасчитаны склады-свойства и новое полученное значение запишется в поле "доступное количество".
                        Для запуска нажмите на кнопку "Запустить перерасчет" и дождитесь полного выполнения.
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="text-center">
                        <div class="loading" style="display: none">
                            <span class="result" id="exportResult"></span>
                        </div>
                    </td>
                </tr>

            <? elseif($aTab["DIV"] == "edit2"): ?>
                <div id="loginfo"></div>
            <? endif; ?>

        <?
        endforeach;
    $tabControl->Buttons();
    ?>

        <input type="submit" name="stockchecker" value="Запустить перерасчет" class="adm-btn-save" />
        <input type="submit" name="default" value="Отменить" />

    </form>

<? $tabControl->End();?>


    <script>

        $('input[name=stockchecker]').on('click', function(e){
            e.stopPropagation();
            e.preventDefault();
            $('.loading').css('display', 'block');
            startChecker(1, 0, 0);
        });

        function startChecker(step, product_update, total_step){

            $.ajax({
                url:'/local/exchange/stockchecker.php',
                data: {
                    step: step,
                    product_update: product_update,
                    total_step: total_step
                },
                type:'post',
                dataType: 'JSON',
                success:function(data){
                    $('#exportResult').text(data.percent+'%').css('width', data.percent+'%');
                    var step = +data.step + 1;
                    if(data.step < data.total) startChecker(+step, +data.product_update, +data.total);
                        else log();
                }
            });

        }

    </script>

    <script>
        log_exchange();
        function log_exchange() {
            $.ajax({
                url:'/local/exchange/stockchecker-log.txt?21',
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