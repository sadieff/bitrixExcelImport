<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;

Loc::loadMessages(__FILE__);

class sadieff_import extends CModule{

    /* информация о модуле */
    public function __construct(){

        if(file_exists(__DIR__."/version.php")){

            $arModuleVersion = array();

            include_once(__DIR__."/version.php");

            $this->MODULE_ID 		   = str_replace("_", ".", get_class($this));
            $this->MODULE_VERSION 	   = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
            $this->MODULE_NAME 		   = Loc::getMessage("MODULE_NAME");
            $this->MODULE_DESCRIPTION  = Loc::getMessage("MODULE_DESCRIPTION");
            $this->PARTNER_NAME 	   = Loc::getMessage("MODULE_PARTNER_NAME");
            $this->PARTNER_URI  	   = Loc::getMessage("MODULE_PARTNER_URI");
        }

        return false;
    }

    /* установка модуля */
    public function DoInstall(){

        global $APPLICATION;

        if(CheckVersion(ModuleManager::getVersion("main"), "14.00.00")){

            $this->InstallFiles();
            $this->InstallDB();

            ModuleManager::registerModule($this->MODULE_ID);

            $this->InstallEvents();
        }else{

            $APPLICATION->ThrowException(
                Loc::getMessage("MODULE_INSTALL_ERROR_VERSION")
            );
        }

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("MODULE_INSTALL_TITLE")." \"".Loc::getMessage("MODULE_NAME")."\"",
            __DIR__."/step.php"
        );

        return false;
    }

    /* скопировать стили и скрипты в систему */
    public function InstallFiles(){

        /*CopyDirFiles(
            __DIR__."/assets/scripts",
            Application::getDocumentRoot()."/bitrix/js/".$this->MODULE_ID."/",
            true,
            true
        );

        CopyDirFiles(
            __DIR__."/assets/styles",
            Application::getDocumentRoot()."/bitrix/css/".$this->MODULE_ID."/",
            true,
            true
        );*/

        CopyDirFiles(
            __DIR__."/assets/admin/sadieff.export.php",
            Application::getDocumentRoot()."/bitrix/admin/sadieff.import.php",
            true,
            true
        );

        return false;
    }

    /* выполняем запросы к базе */
    public function InstallDB(){

        return false;
    }

    /* регистрируем события */
    public function InstallEvents(){

        EventManager::getInstance()->registerEventHandler(
            "main",
            "OnBeforeEndBufferContent",
            $this->MODULE_ID,
            "Falbar\ToTop\Main",
            "appendScriptsToPage"
        );

        return false;
    }

    /* удаление модуля */
    public function DoUninstall(){

        global $APPLICATION;

        $this->UnInstallFiles();
        $this->UnInstallDB();
        $this->UnInstallEvents();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("MODULE_UNINSTALL_TITLE")." \"".Loc::getMessage("MODULE_NAME")."\"",
            __DIR__."/unstep.php"
        );

        return false;
    }

    /* удаляем все файлы приложения */
    public function UnInstallFiles(){

        Directory::deleteDirectory(
            Application::getDocumentRoot()."/bitrix/js/".$this->MODULE_ID
        );

        Directory::deleteDirectory(
            Application::getDocumentRoot()."/bitrix/css/".$this->MODULE_ID
        );

        /*File::deleteFile(
            Application::getDocumentRoot()."/bitrix/admin/task_add.php"
        );

        File::deleteFile(
            Application::getDocumentRoot()."/bitrix/admin/task_list.php"
        );*/

        return false;
    }

    /* удаляем настройки из системы */
    public function UnInstallDB(){

        Option::delete($this->MODULE_ID);

        return false;
    }

    /* удаляем повешанные события */
    public function UnInstallEvents(){

        EventManager::getInstance()->unRegisterEventHandler(
            "main",
            "OnBeforeEndBufferContent",
            $this->MODULE_ID,
            "Falbar\ToTop\Main",
            "appendScriptsToPage"
        );

        return false;
    }

}