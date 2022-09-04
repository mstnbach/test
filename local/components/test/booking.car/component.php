<?
define("NEED_AUTH", true);
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Highloadblock\HighloadBlockTable;

/** @var array $arParams */
/** @var array $arResult */
global $USER;

Loader::IncludeModule("iblock");
Loader::includeModule("highloadblock");

$startData = date('Y-m-d H:i:s', strtotime($arParams['START']));
$endData = date('Y-m-d H:i:s', strtotime($arParams['END']));

$blockId = $arParams['IBLOCK_ID'];
$blockIdCar = $arParams['CAR_IBLOCK_ID'];

//Получаем id должности текущего пользователя
$dbUser = UserTable::getList([
    'select' => ['UF_*'],
    'filter' => ['ID' => $USER->GetID()]
]);
$employeePositionId = $dbUser->fetch();

//Получаем id категории комфорта из hl-блока по полученной должности
$arFilter = ['ID' => $employeePositionId];
$hlblockProperty = getHLBlockProperty($arParams['HLBLOCK_EMPLOYEE_POSITION_ID'], $arFilter);
$arCategoryComfortId = $hlblockProperty[0]['UF_CATEGORY_COMFORT'];

//Получаем xml_id категорий комфорта
$arFilter = ['ID' => $arCategoryComfortId];
$arSelect = ['UF_XML_ID'];
$hlblockProperty = getHLBlockProperty($arParams["HLBLOCK_CATEGORY_COMFORT_ID"], $arFilter);

$arCategoryXmlId = [];

foreach ($hlblockProperty as $value) {
    $arCategoryXmlId[] = $value['UF_XML_ID'];
}

//Получаем автомобили нужных категорий
$resAuto = CIBlockElement::GetList(
    ['NAME'],
    ["IBLOCK_ID" => $blockIdCar, 'PROPERTY_CATEGORY' => $arCategoryXmlId],
    false,
    false,
    ["ID", 'IBLOCK_ID', "NAME", 'PROPERTY_DRIVER', 'PROPERTY_MODEL', 'PROPERTY_CATEGORY']);

$arAuto = [];

while ($arFields = $resAuto->Fetch()) {

    //Проверяем автомобиль, на выбранное время
    $resBooking = CIBlockElement::GetList(
        [],
        ["IBLOCK_ID" => $blockId,
            'PROPERTY_CAR' => $arFields['ID'],
            [
                'LOGIC' => "OR",
                [
                    'LOGIC' => 'AND',
                    '>=PROPERTY_START' => $startData,
                    '<=PROPERTY_START' => $endData,
                    '>=PROPERTY_END' => $endData,
                ],
                [
                    'LOGIC' => 'AND',
                    '<=PROPERTY_START' => $startData,
                    '>=PROPERTY_END' => $startData,
                    '<=PROPERTY_END' => $endData,
                ],
                [
                    'LOGIC' => 'AND',
                    '<=PROPERTY_START' => $startData,
                    '>=PROPERTY_END' => $endData,
                ],
                [
                    'LOGIC' => 'AND',
                    '>=PROPERTY_START' => $startData,
                    '<=PROPERTY_END' => $endData,
                ],
            ]
        ],
        false,
        false,
        ["ID", "NAME", 'PROPERTY_CAR', 'PROPERTY_START', 'PROPERTY_END']
    );
    if ($resBooking->Fetch() == false)
        $arAuto[] = $arFields;
}
foreach ($arAuto as $value) {

    $rsUser = UserTable::getList([
        'select' => ['NAME', 'SECOND_NAME'],
        'filter' => ['ID' => $value['PROPERTY_DRIVER_VALUE']]
    ])->fetch();

    $driverName = trim($rsUser['NAME'] . ' ' . $rsUser['SECOND_NAME']);
    $arSelect = ['UF_NAME'];

    $arFilter = ['UF_XML_ID' => $value['PROPERTY_MODEL_VALUE']];
    $hlblockPropertyCarModel = getHLBlockProperty($arParams["HLBLOCK_CAR_MODEL_ID"] , $arFilter, $arSelect);

    $arFilter = ['UF_XML_ID' => $value['PROPERTY_CATEGORY_VALUE']];
    $hlblockPropertyCategory = getHLBlockProperty($arParams["HLBLOCK_CATEGORY_COMFORT_ID"] , $arFilter, $arSelect);

    $arResult[] = [
        'DRIVER_NAME' => $driverName,
        'CAR_MODEL' => $hlblockPropertyCarModel[0]['UF_NAME'],
        'CATEGORY_COMFORT' => $hlblockPropertyCategory[0]['UF_NAME'],
    ];

}

return $arResult;

function getHLBlockProperty($hlblockId, $arFilter, $arSelect = ['*'])
{

    $hlblock = HighloadBlockTable::getList([
        'select' => ['*'],
        'filter' => ['ID' => $hlblockId]
    ])->fetch();

    $entity = HighloadBlockTable::compileEntity($hlblock);
    $entityData = $entity->getDataClass();
    $entityIterator = $entityData::getList([
        'select' => $arSelect,
        'filter' => $arFilter
    ]);
    while($arr = $entityIterator->fetch()){
        $hlblockProperty[] = $arr;
    }

    unset($hlblock, $entityIterator, $entityData, $entity, $dbUser, $employeePositionId);

    return $hlblockProperty;

}

