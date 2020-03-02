<?php
use Bitrix\Main\Localization\Loc;
use	Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

/* подключим языковые файлы */
Loc::loadMessages(__FILE__);

/* получим ID модуля */
$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);
Loader::includeModule($module_id);

/* массив с настройками */
$aTabs = array(
    array(
        "DIV" 	  => "edit",
        "TAB" 	  => Loc::getMessage("LITE_TASKS_OPTIONS_TAB_NAME"),
        "TITLE"   => Loc::getMessage("LITE_TASKS_OPTIONS_TAB_NAME"),
        "OPTIONS" => array(
            Loc::getMessage("LITE_TASKS_OPTIONS_TAB_COMMON"),
            array(
                "module_on",
                Loc::getMessage("LITE_TASKS_OPTIONS_MODULE_ON"),
                "Y",
                array("checkbox")
            ),
            array(
                "show_button",
                Loc::getMessage("LITE_TASKS_OPTIONS_SHOW_BUTTON"),
                "Y",
                array("checkbox")
            ),
            array(
                "show_popup",
                Loc::getMessage("LITE_TASKS_OPTIONS_SHOW_POPUP"),
                "Y",
                array("checkbox")
            ),
        )
    )
);

/* отрисовываем форму */
$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);
$tabControl->Begin(); ?>

<form action="<? echo($APPLICATION->GetCurPage()); ?>?mid=<? echo($module_id); ?>&lang=<? echo(LANG); ?>" method="post">

	<?
	foreach($aTabs as $aTab){

		if($aTab["OPTIONS"]){

			$tabControl->BeginNextTab();

			__AdmSettingsDrawList($module_id, $aTab["OPTIONS"]);
		}
	}

	$tabControl->Buttons();
	?>

	<input type="submit" name="apply" value="<? echo(Loc::GetMessage("LITE_TASKS_OPTIONS_INPUT_APPLY")); ?>" class="adm-btn-save" />
	<input type="submit" name="default" value="<? echo(Loc::GetMessage("LITE_TASKS_OPTIONS_INPUT_DEFAULT")); ?>" />

	<?
	echo(bitrix_sessid_post());
	?>

</form>

<?
$tabControl->End();

/* сохранение изменений */
if($request->isPost() && check_bitrix_sessid()){

    foreach($aTabs as $aTab){

        foreach($aTab["OPTIONS"] as $arOption){

            if(!is_array($arOption)){

                continue;
            }

            if($arOption["note"]){

                continue;
            }

            if($request["apply"]){

                $optionValue = $request->getPost($arOption[0]);

                if(
                    $arOption[0] == "module_on" ||
                    $arOption[0] == "show_button" ||
                    $arOption[0] == "show_popup"
                ){
                    if($optionValue == ""){
                        $optionValue = "N";
                    }
                }

                Option::set($module_id, $arOption[0], is_array($optionValue) ? implode(",", $optionValue) : $optionValue);
            }elseif($request["default"]){
                Option::set($module_id, $arOption[0], $arOption[2]);
            }
        }
    }

    LocalRedirect($APPLICATION->GetCurPage()."?mid=".$module_id."&lang=".LANG);
}