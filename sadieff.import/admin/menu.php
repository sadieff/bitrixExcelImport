<?
IncludeModuleLangFile(__FILE__); // в menu.php точно так же можно использовать языковые файлы

// сформируем верхний пункт меню
$aMenu = array(
    "parent_menu" => "global_menu_services", // поместим в раздел "Сервис"
    "sort"        => 10,                    // вес пункта меню
    "url"         => "",  // ссылка на пункте меню
    "text"        => "Экспорт товаров",       // текст пункта меню
    "title"       => "Экспорт товаров", // текст всплывающей подсказки
    "icon"        => "form_menu_icon", // малая иконка
    "page_icon"   => "form_page_icon", // большая иконка
    "items_id"    => "",  // идентификатор ветви
    "items"       => array(
        array(
            "text" => "Импорт товаров из excel",
            "title" => "Импорт товаров из excel",
            "url"  => "sadieff.export.php",
            "icon" => "form_menu_icon",
            "page_icon" => "form_page_icon",
            "more_url"  => array(),
        ),
        array(
            "text" => "Перерасчет товаров на складах",
            "title" => "Перерасчет товаров на складах",
            "url"  => "sadieff.stockchecker.php",
            "icon" => "form_menu_icon",
            "page_icon" => "form_page_icon",
            "more_url"  => array(),
        ),
    ),
);

return $aMenu;