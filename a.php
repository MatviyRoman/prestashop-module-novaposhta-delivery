<?php

require('../../config/config.inc.php');
require_once('../../init.php');

require_once(dirname(__FILE__) . '/src/Delivery/NovaPoshtaApi2.php');
require_once(dirname(__FILE__) . '/src/Delivery/NovaPoshtaApi2Areas.php');

//'3347e67c4cf1bd22b38bdc4aaaa5a8a0',
//'b3aa7f2eba071bf3ca713b78be9a9da2',
//'3347e67c4cf1bd22b38bdc4aaaa5a8a0',
//'73a9f024b696cab746977890c797519f',

define('_MODULE_DB_PREFIX_', 'APARTNER_PRO_NP_');

$tableRegions = _MODULE_DB_PREFIX_ . 'regions';
$tableCities = _MODULE_DB_PREFIX_ . 'cities';
$tableWarehouses = _MODULE_DB_PREFIX_ . 'warehouses';

$tableRegionsExists = Db::getInstance()->executeS("SHOW TABLES LIKE '{$tableRegions}'");

$np_api_key = Configuration::get('PS_NP_API');
$np_api_key = '2879527eac969b6d40a8be0cd44463c2'; //! api key
$np = new \ApartnerPro\Delivery\NovaPoshtaApi2(
    $np_api_key,
    'ua',
    //! Language: ua (default) | en | ru
    FALSE,
    //! When there is an error in the query, throw an Exception: FALSE (default) | TRUE.
    'curl' //! The query mechanism used: curl (default) | file_get_contents.
);


//! install data
if (Tools::getValue('install') == 1) {

    if (empty($tableRegionsExists)) {
        //! Table doesn't exist, create it
        Db::getInstance()->Execute('CREATE TABLE `' . $tableRegions . '` (
            `id` int(11) NOT NULL,
            `ref` varchar(64) NOT NULL,
            `AreasCenter` varchar(36) NOT NULL,
            `descriptionUA` text NOT NULL,
            `descriptionRu` text NOT NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
    }

    //! Regions
    if (count($np->getAreas()['errorCodes']) === false && $np->getAreas()['success'] !== 1) {
        die('An error occurred while getting the regions.');
    }

    $regions = $np->getAreas()['data'];

    foreach ($regions as $key => $data) {
        $ref = pSQL($data['Ref']);
        $areasCenter = pSQL($data['AreasCenter']);
        $description = pSQL($data['Description']);
        $descriptionRu = pSQL($data['DescriptionRu']);

        //! Check if a record with the same Ref exists
        $sql = 'SELECT `ref` FROM `' . $tableRegions . '` WHERE `ref` = "' . $ref . '"';
        $existingRecord = Db::getInstance()->getValue($sql);

        if (!$existingRecord) {
            //! Record doesn't exist, insert a new one
            $sql = 'INSERT INTO `' . $tableRegions . '`
            (`id`, `ref`, `AreasCenter`, `descriptionUA`, `descriptionRu`)
            VALUES ("' . ++$key . '", "' . $ref . '", "' . $areasCenter . '", "' . $description . '", "' . $descriptionRu . '")';
            Db::getInstance()->Execute($sql);
        } else {
            //! Record already exists, handle it as needed (e.g., update or skip)
        }
    }

    //! cities
    $tableCitiesExists = Db::getInstance()->executeS("SHOW TABLES LIKE '{$tableCities}'");

    if (empty($tableCitiesExists)) {
        Db::getInstance()->Execute('CREATE TABLE `' . $tableCities . '` (
            `descriptionUA` text NOT NULL,
            `descriptionRu` text NOT NULL,
            `regionUA` text NOT NULL,
            `regionRu` text NOT NULL,
            `typeUA` text NOT NULL,
            `typeRu` text NOT NULL,
            `area` varchar(64) NOT NULL,
            `ref` varchar(64) NOT NULL,
            `cityID` int(11) NOT NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
    }

    $cities = $np->getCities();

    if ($cities['success'] == 1) {
        if (Tools::getValue('update') == 1) {
            //! TRUNCATE and update table
            $qnp = 'TRUNCATE `' . $tableCities . '`';
            Db::getInstance()->Execute($qnp);
        }

        foreach ($cities['data'] as $city) {
            //! Check if a record with the same Ref exists

            $descriptionUA = pSQL($city['Description']);
            $descriptionRu = pSQL($city['DescriptionRu']);
            $regionUA = pSQL($city['AreaDescription']);
            $regionRu = pSQL($city['AreaDescriptionRu']);
            $typeUA = pSQL($city['SettlementTypeDescription']);
            $typeRu = pSQL($city['SettlementTypeDescriptionRu']);
            $area = pSQL($city['Area']);
            $ref = pSQL($city['Ref']);
            $cityID = pSQL($city['CityID']);

            $sql = 'SELECT `cityID` FROM `' . $tableCities . '` WHERE `cityID` = "' . $cityID . '"';
            $existingRecord = Db::getInstance()->getValue($sql);

            if (!$existingRecord) {
                //! Record doesn't exist, insert a new one

                $sql = 'INSERT INTO `' . $tableCities . '`
                (
                    `descriptionUA`,
                    `descriptionRu`,
                    `regionUA`,
                    `regionRu`,
                    `typeUA`,
                    `typeRu`,
                    `area`,
                    `ref`,
                    `cityID`
                )
                values (
                    "' . $descriptionUA . '",
                    "' . $descriptionRu . '",
                    "' . $regionUA . '",
                    "' . $regionRu . '",
                    "' . $typeUA . '",
                    "' . $typeRu . '",
                    "' . $area . '",
                    "' . $ref . '",
                    "' . $cityID . '"
                )';
                Db::getInstance()->Execute($sql);
            }
        }
    }

    //! warehouses

    $warehouses = $np->getWarehouses( /*$city['data'][0]['Ref']*/'');

    if ($warehouses['success'] == 1) {

        $tableWarehousesExists = Db::getInstance()->executeS("SHOW TABLES LIKE '{$tableWarehouses}'");
        if (empty($tableWarehousesExists)) {
            //! Table doesn't exist, create it
            Db::getInstance()->Execute('CREATE TABLE `' . $tableWarehouses . '` (
                    `descriptionUA` text NOT NULL,
                    `descriptionRu` text NOT NULL,
                    `phone` varchar(32) NOT NULL,
                    `warehouse_type` varchar(64) NOT NULL,
                    `warehouse_ref` varchar(64) NOT NULL,
                    `city_ref` varchar(64) NOT NULL,
                    `city_name_UA` text NOT NULL,
                    `city_name_Ru` text NOT NULL,
                    `receptionUA` text NOT NULL,
                    `receptionRu` text NOT NULL,
                    `total_max_weight` int(11) NOT NULL,
                    `place_max_weight` int(11) NOT NULL
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
        }

        //! TRUNCATE and update table
        if (Tools::getValue('update') == 1) {
            $qnp = 'TRUNCATE `' . $tableWarehouses . '`';
            Db::getInstance()->Execute($qnp);
        }

        foreach ($warehouses as $res) {
            if ($res && is_array($res)) {
                foreach ($res as $rec) {
                    $timeUA = 'Пн-Пт: ' . $rec['Schedule']['Monday'] . '<br>Сб: ' . $rec['Schedule']['Saturday'] . '<br>Нд: ' . $rec['Schedule']['Sunday'];
                    $timeRu = 'Пн-Пт: ' . $rec['Schedule']['Monday'] . '<br>Вс: ' . $rec['Schedule']['Saturday'] . '<br>Вс: ' . $rec['Schedule']['Sunday'];

                    $sql = 'INSERT INTO `' . $tableWarehouses . '`(
                            `descriptionUA`,
                            `descriptionRu`,
                            `phone`,
                            `warehouse_type`,
                            `warehouse_ref`,
                            `city_ref`,
                            `city_name_UA`,
                            `city_name_Ru`,
                            `receptionUA`,
                            `receptionRu`,
                            `total_max_weight`,
                            `place_max_weight`)
                        values (
                            "' . pSQL($rec['Number'] . ': ' . $rec["Description"]) . '",
                            "' . pSQL($rec['Number'] . ': ' . $rec["DescriptionRu"]) . '",
                            "' . pSQL($rec["Phone"]) . '",
                            "' . pSQL($rec["TypeOfWarehouse"]) . '",
                            "' . pSQL($rec["Ref"]) . '",
                            "' . pSQL($rec["CityRef"]) . '",
                            "' . pSQL($rec["CityDescription"]) . '",
                            "' . pSQL($rec["CityDescriptionRu"]) . '",
                            "' . pSQL($timeUA) . '",
                            "' . pSQL($timeRu) . '",
                            "' . pSQL($rec["TotalMaxWeightAllowed"]) . '",
                            "' . pSQL($rec["PlaceMaxWeightAllowed"]) . '"
                        )';
                    Db::getInstance()->Execute($sql);
                }
            }
        }
    }
}

//! Search cities from region
if (Tools::getValue('citiesByRegion') == 1) {
    $sql = "SELECT descriptionUA, descriptionRu, regionUA, regionRu, cityID FROM {$tableCities} WHERE `area` = '71508134-9b87-11de-822f-000c2965ae0e'";

    $citiesByRegionDB = Db::getInstance()->ExecuteS($sql);

    dd($citiesByRegionDB);
}

function dd1($text)
{
    echo '<pre>';
    print_r($text);
    echo '</pre>';
}