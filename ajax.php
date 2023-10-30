<?php
require('../../config/config.inc.php');
require_once('../../init.php');
if ($create_tables = Tools::getValue('create_tables')){

	//! modules/apartner_pro_np/ajax.php?create_tables=1


Db::getInstance()->Execute('CREATE TABLE `ps_np` (
	`description` text NOT NULL,
	`ref` varchar(64) NOT NULL,
	`id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;');


Db::getInstance()->Execute('CREATE TABLE `ps_np_ukr` (
	`description` text NOT NULL,
	`ref` varchar(64) NOT NULL,
	`id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;');

Db::getInstance()->Execute('CREATE TABLE `ps_np_warhouses_ukr` (
	`description` text NOT NULL,
	`phone` varchar(32) NOT NULL,
	`warehouse_type` varchar(64) NOT NULL,
	`warehouse_ref` varchar(64) NOT NULL,
	`city_ref` varchar(64) NOT NULL,
	`city_name` text NOT NULL,
	`reception` text NOT NULL,
	`total_max_weight` int(11) NOT NULL,
	`place_max_weight` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;');

Db::getInstance()->Execute('CREATE TABLE `ps_np_warhouses` (
	`description` text NOT NULL,
	`phone` varchar(32) NOT NULL,
	`warehouse_type` varchar(64) NOT NULL,
	`warehouse_ref` varchar(64) NOT NULL,
	`city_ref` varchar(64) NOT NULL,
	`city_name` text NOT NULL,
	`reception` text NOT NULL,
	`total_max_weight` int(11) NOT NULL,
	`place_max_weight` int(11) NOT NULL
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8;');

}

if($cron = Tools::getValue('cron')){
	//! /modules/apartner_pro_np/ajax.php?cron=1

	require_once(dirname(__FILE__) . '/src/Delivery/NovaPoshtaApi2.php');
	require_once(dirname(__FILE__) . '/src/Delivery/NovaPoshtaApi2Areas.php');
	/*$np = new \ApartnerPro\Delivery\NovaPoshtaApi2(
	//'3347e67c4cf1bd22b38bdc4aaaa5a8a0',
	'b3aa7f2eba071bf3ca713b78be9a9da2',
	'ru', // Язык возвращаемых данных: ru (default) | ua | en
	FALSE, // При ошибке в запросе выбрасывать Exception: FALSE (default) | TRUE
	'curl' // Используемый механизм запроса: curl (defalut) | file_get_content
	);*/
	$np_api_key = Configuration::get('PS_NP_API');
	$np_ua = new \ApartnerPro\Delivery\NovaPoshtaApi2(
	//'3347e67c4cf1bd22b38bdc4aaaa5a8a0',
	//'b3aa7f2eba071bf3ca713b78be9a9da2',
	//'3347e67c4cf1bd22b38bdc4aaaa5a8a0',
	//'73a9f024b696cab746977890c797519f',
	$np_api_key,
	'ua', // Язык возвращаемых данных: ru (default) | ua | en
	FALSE, // При ошибке в запросе выбрасывать Exception: FALSE (default) | TRUE
	'curl' // Используемый механизм запроса: curl (defalut) | file_get_content
	);
	//$city = $np->getCity(pSQL(/*$city_name*/''), '');
	//$cities = $np->getCities();
	$cities_ua = $np_ua->getCities();
	if($cities_ua['success'] == 1){
		$qnp = 'TRUNCATE `ps_np`';	
		Db::getInstance()->Execute($qnp);				
		$qnpukr = 'TRUNCATE `ps_np_ukr`';		
		Db::getInstance()->Execute($qnpukr);				
		foreach($cities_ua['data'] as $city){
			$sql2 = 'INSERT INTO `ps_np_ukr`
				(`description`, `ref`, `id`)
				values ("'.$city['Description'].'","'.$city['Ref'].'","'.$city['CityID'].'")';
			Db::getInstance()->Execute($sql2);	
			$sql = 'INSERT INTO `ps_np`
				(`description`, `ref`, `id`)
				values ("'.$city['DescriptionRu'].'","'.$city['Ref'].'","'.$city['CityID'].'")';
			Db::getInstance()->Execute($sql);				
		}
	}

	//$result = $np->getWarehouses(/*$city['data'][0]['Ref']*/'');
	$result_ua = $np_ua->getWarehouses(/*$city['data'][0]['Ref']*/'');
	echo 'cron';
	//print_r($result_ua);
	if($result_ua['success'] == 1){
		$q = 'TRUNCATE `ps_np_warhouses`';
		Db::getInstance()->Execute($q);
		$q2 = 'TRUNCATE `ps_np_warhouses_ukr`';
		Db::getInstance()->Execute($q2);

		foreach($result_ua as $res){

			if ($res && is_array($res))
			foreach($res as $rec){
				$time = '';
				$time .= 'Пн-Пт: '.$rec['Schedule']['Monday'] . '<br>Сб: ' . $rec['Schedule']['Saturday'] . '<br>Вс: ' . $rec['Schedule']['Sunday'];
				$sql2 = 'INSERT INTO `ps_np_warhouses_ukr`(`description`, `phone`, `warehouse_type`, `warehouse_ref`, `city_ref`, `city_name`, `reception`, `total_max_weight`, `place_max_weight`)
					values (
						"'.pSQL($rec['Number'] . ': ' . $rec["Description"]).'",
						"'.pSQL($rec["Phone"]).'",
						"'.pSQL($rec["TypeOfWarehouse"]).'",
						"'.pSQL($rec["Ref"]).'",
						"'.pSQL($rec["CityRef"]).'",
						"'.pSQL($rec["CityDescription"]).'",
						"'.$time.'",
						"'.pSQL($rec["TotalMaxWeightAllowed"]).'",
						"'.pSQL($rec["PlaceMaxWeightAllowed"]).'"
					)';
				Db::getInstance()->Execute($sql2);

				$sql = 'INSERT INTO `ps_np_warhouses`(`description`, `phone`, `warehouse_type`, `warehouse_ref`, `city_ref`, `city_name`, `reception`, `total_max_weight`, `place_max_weight`)
					values (
						"'.pSQL($rec['Number'] . ': ' . $rec["DescriptionRu"]).'",
						"'.pSQL($rec["Phone"]).'",
						"'.pSQL($rec["TypeOfWarehouse"]).'",
						"'.pSQL($rec["Ref"]).'",
						"'.pSQL($rec["CityRef"]).'",
						"'.pSQL($rec["CityDescriptionRu"]).'",
						"'.$time.'",
						"'.pSQL($rec["TotalMaxWeightAllowed"]).'",
						"'.pSQL($rec["PlaceMaxWeightAllowed"]).'"
					)';
				Db::getInstance()->Execute($sql);
			}
		}
	}
}

if($city_name = Tools::getValue('city')){

	$cartWeight = Context::getContext()->cart->getTotalWeight();
	//echo $cartWeight;
	//$sql = 'SELECT `ref` FROM `ps_np` WHERE description LIKE "%'.pSQL($city_name).'%"';	
	//$res = Db::getInstance()->ExecuteS($sql);
	//$sql2 = 'SELECT * FROM `ps_np_warhouses` WHERE `city_ref` = "'.$res[0]["ref"].'" AND (total_max_weight > '.$cartWeight.' OR total_max_weight = 0)';
	//$sql2 = 'SELECT * FROM `ps_np_warhouses` WHERE `city_ref` = "'.$res[0]["ref"].'"';
	$is_ua = checkUkraineSymbols($city_name);
	if($is_ua == false){
		//$sql2 = 'SELECT * FROM `ps_np_warhouses` WHERE `city_name` LIKE "%'.pSQL($city_name).'%"';
		$sql2 = 'SELECT * FROM `ps_np_warhouses` WHERE `city_name` = "'.pSQL($city_name).'"';
	}else{
		//$sql2 = 'SELECT * FROM `ps_np_warhouses_ukr` WHERE `city_name` LIKE "%'.pSQL($city_name).'%"';
		$sql2 = 'SELECT * FROM `ps_np_warhouses_ukr` WHERE `city_name` = "'.pSQL($city_name).'"';
	}
	$res2 = Db::getInstance()->ExecuteS($sql2);

	$reorder_warehouse = Tools::getValue('reorder_warehouse');

	$i = 0;
	$ret = array();
	foreach($res2 as $r2){
		print_r($r2);	
		//echo '<option value="'.$r2['warehouse_ref'].'" '. ($r2['total_max_weight'] > $cartWeight || $r2['total_max_weight'] == 0 ? "" : " disabled") .'>'.$r2['description'].'</option>';
		//$ret[] = $r2['description'];
		$ret2[] = $r2['description'];
		$description = explode(':', $r2['description']);
		// $ret[$i]['description'] = $description[1] . ' ' . $description[2];
		$ret[$i]['description'] = $r2['description'];
		$ret[$i]['ref'] = $r2['warehouse_ref'];
		$ret[$i]['reception'] = str_replace('<br>', '; ', $r2['reception']);
		$ret[$i]['selected'] = $reorder_warehouse != '' && $reorder_warehouse == $ret[$i]['description'] ? 1 : 0;
		$i++;
		//echo '<option value="'.$r2['warehouse_ref'].'">'.$r2['description'].'</option>';
	}

	echo json_encode($ret, JSON_UNESCAPED_UNICODE);
	exit;
	//print_r($res2);
//$city = $np->getCity(pSQL(/*$city_name*/''), '');
//$result = $np->getWarehouses(/*$city['data'][0]['Ref']*/'');
//print_r($result);

//$xml = 'https://dev2.sporthavka.com.ua/price.xml';
//$_array = simplexml_load_file($xml);

//print_r($_array->data);
//$k = 0;

//foreach($_array->data->item as $data_item){
	//++$k;
	/*$sql = 'INSERT INTO `ps_np`
		(`description`, `ref`, `id`)
		values ("'.$data_item->DescriptionRu.'","'.$data_item->Ref.'","'.$data_item->CityID.'")';*/
	//Db::getInstance()->Execute($sql);	
	//if($k == 1)
		//echo $data_item['DescriptionRu'];	
	//echo $k;
//}
//echo $k;	
}
if ($ref = Tools::getValue('ref')){
	$sql = 'SELECT `reception` FROM `ps_np_warhouses` WHERE warehouse_ref = "'.pSQL($ref).'"';
	$res = Db::getInstance()->ExecuteS($sql);
	echo $res[0]['reception'];
	//print_r($res);
}

if ($get_city = Tools::getValue('q')){
	$is_ua = checkUkraineSymbols($get_city);
	if($is_ua == false)	
		$sql = 'SELECT * FROM `ps_np` WHERE description LIKE "'.pSQL($get_city).'%"';
	else
		$sql = 'SELECT * FROM `ps_np_ukr` WHERE description LIKE "'.pSQL($get_city).'%"';
	$res = Db::getInstance()->ExecuteS($sql);
	//echo $res[0]['reception'];
	//print_r($res);
	foreach($res as $res_item){
		//print_r($res_item);
	}
	echo json_encode($res);
}

if ($get_city_list = Tools::getValue('city_list')){
	$sql = 'SELECT * FROM `ps_np`';
	$res = Db::getInstance()->ExecuteS($sql);
	//echo $res[0]['reception'];
	//print_r($res);
	foreach($res as $res_item){
		echo '<option value="'.$res_item['description'].'">'.$res_item['description'].'</option>';
	}
	echo json_encode($res);
}

	function checkUkraineSymbols1($str){
		if(stripos($str, 'ґ') !== false || stripos($str, 'є') !== false || stripos($str, 'і') !== false || stripos($str, 'ї') !== false)
			return true;
		else
			return false;
	}

	function checkUkraineSymbols($str)
	{
		// if(stripos($str, 'ґ') !== false || stripos($str, 'є') !== false || stripos($str, 'і') !== false || stripos($str, 'ї') !== false)
		// 	return true;
		// else
		// 	return false;

		if (Context::getContext()->language->id == 2) {
			return true;
		} else {
			return false;
		}
	}

/*if($_POST['warehouses']) {
	$wh = $np->getWarehouses($_POST['warehouses']);
	foreach ($wh['data'] as $warehouse) {
		echo '<option value="'.$warehouse['DescriptionRu'].'">'.$warehouse['DescriptionRu'].'</option>';
	}
} else {
	$cities = $np->getCities();	
	foreach ($cities['data'] as $city) {
		echo '<option data-ref="'. $city['Ref'] .'" value="'.$city['DescriptionRu'].'">'.$city['DescriptionRu'].'</option>';
	}
}*/
?>