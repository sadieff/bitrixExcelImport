<?
IncludeModuleLangFile(__FILE__); // в menu.php точно так же можно использовать языковые файлы

// сформируем верхний пункт меню
$aMenu = array(
    "parent_menu" => "global_menu_services", // поместим в раздел "Сервис"
    "sort"        => 10,                    // вес пункта меню
    "url"         => "",  // ссылка на пункте меню
    "text"        => GetMessage("MODULE_MENU"),       // текст пункта меню
    "title"       => GetMessage("MODULE_MENU"), // текст всплывающей подсказки
    "icon"        => "form_menu_icon", // малая иконка
    "page_icon"   => "form_page_icon", // большая иконка
    "items_id"    => "",  // идентификатор ветви
    "items"       => array(
        array(
            "text" => GetMessage("MODULE_MENU_ADD"),
            "title" => GetMessage("MODULE_MENU_ADD"),
            "url"  => "sadieff.import.php?lang=".LANGUAGE_ID,
            "icon" => "form_menu_icon",
            "page_icon" => "form_page_icon",
            "more_url"  => array(),
        ),
    ),
);

return $aMenu;