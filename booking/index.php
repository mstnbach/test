<?
define("NEED_AUTH", true);
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include.php");

$startData = $_GET['start'];
$endData = $_GET['end'];

$arResult = $APPLICATION->IncludeComponent(
    "test:booking.car",
    "",
    [
        "IBLOCK_ID" => "2",
        "CAR_IBLOCK_ID" => "1",
        "CAR_PROPERTY_CATEGORY" => "4",
        "CAR_PROPERTY_DRIVER" => "3",
        "CAR_PROPERTY_MODEL" => "1",
        "HLBLOCK_EMPLOYEE_POSITION_ID" => "2",
        "HLBLOCK_CAR_MODEL_ID" => "5",
        "HLBLOCK_CATEGORY_COMFORT_ID" => "3",
        "START" => $startData,
        "END" => $endData,
    ]
);
echo '<pre>';
var_dump($arResult);