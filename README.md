# Импорт товаров из Excel.

**Задача:**

Импортировать товары из excel файла как товары в 1С Битрикс. Количество товаров ~ 60тыс. Разбить выполнение на шаги.
Так же у клиента есть несколько складов, но используется версия 1С Битрикс - малый бизнесс. Было решено сделать склады в виде свойств у товаров.

**Решение:**

Написан небольшой модуль: excel разбивается на строки и разбиение на шаги выполняется через ajax запросы. При каждом запросе к обработчику, возвращается текущий шаг и общее количество шагов.

Добавлен скрипт, осуществляющий перерасчет количества на складах свойствах у товаров в поле "Доступное количество". 