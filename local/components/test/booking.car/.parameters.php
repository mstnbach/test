<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Iblock\PropertyTable;
use Bitrix\Highloadblock\HighloadBlockTable;

/** @var array $arCurrentValues */

use Bitrix\Main\Loader;

Loader::IncludeModule("iblock");
Loader::includeModule("highloadblock");

//Получаем активные инфоблоки
$arIBlock = [];
$rsIBlock = CIBlock::GetList(["SORT" => "ASC"], ["ACTIVE" => "Y"]);
while ($arr = $rsIBlock->Fetch()) {
    $arIBlock[$arr["ID"]] = "[" . $arr["ID"] . "] " . $arr["NAME"];
}

//Получаем все HL-блоки
$arHLBlock = [];
$rsHLBlock = HighloadBlockTable::GetList([
    "select" => ['ID', 'NAME'],
    "order" => ["ID" => "ASC"],
]);
while ($arr = $rsHLBlock->Fetch()) {
    $arHLBlock[$arr["ID"]] = "[" . $arr["ID"] . "] " . $arr["NAME"];
}

$arComponentParameters = [
    "GROUPS" => [
        "CAR_IBLOCK" => array(
            "NAME" => GetMessage('CAR_IBLOCK')
        ),
    ],
    'PARAMETERS' => [
        "IBLOCK_ID" => [
            "PARENT" => "BASE",
            "NAME" => GetMessage("IBLOCK_ID"),
            "TYPE" => "LIST",
            "VALUES" => $arIBlock,
            "REFRESH" => "Y",
            "ADDITIONAL_VALUES" => "Y",
        ],
        "CAR_IBLOCK_ID" => [
            "PARENT" => "CAR_IBLOCK",
            "NAME" => GetMessage("CAR_IBLOCK_ID"),
            "TYPE" => "LIST",
            "VALUES" => $arIBlock,
            "REFRESH" => "Y",
            "ADDITIONAL_VALUES" => "Y",
        ],
        "HLBLOCK_CAR_MODEL_ID" => array(
            "PARENT" => "CAR_IBLOCK",
            "NAME" => GetMessage("HLBLOCK_CAR_MODEL"),
            "TYPE" => "LIST",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => $arHLBlock,
        ),
        "HLBLOCK_EMPLOYEE_POSITION_ID" => array(
            "PARENT" => "CAR_IBLOCK",
            "NAME" => GetMessage("HLBLOCK_EMPLOYEE_POSITION"),
            "TYPE" => "LIST",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => $arHLBlock,
        ),
        "HLBLOCK_CATEGORY_COMFORT_ID" => array(
            "PARENT" => "CAR_IBLOCK",
            "NAME" => GetMessage("HLBLOCK_CATEGORY_COMFORT"),
            "TYPE" => "LIST",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => $arHLBlock,
        ),
        "UF_CATEGORY_COMFORT_NAME" => array(
            "PARENT" => "CAR_IBLOCK",
            "NAME" => GetMessage("UF_CATEGORY_COMFORT_NAME"),
            "TYPE" => "TEXT",
            "ADDITIONAL_VALUES" => "Y",
        ),
    ],
];