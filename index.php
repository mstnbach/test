<?
define("NEED_AUTH", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Тестовое задание"); ?>
    <form style="display: flex; flex-flow: column; width: 300px; margin: 50px" action="/booking" method="get">
        <label for="start"> Дата/Время начала</label>
        <input id="start" name="start" type="datetime-local">
        <label for="end">Дата/Время окончания</label>
        <input id="end" name="end" type="datetime-local">
        <button type="submit">Получить свободные авто</button>
    </form>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>