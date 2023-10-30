<?php
/**
* 2007-2023 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2023 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Apartner_pro_np extends CarrierModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'apartner_pro_np';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'APARTNER.PRO';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Nova Poshta');
        $this->description = $this->l('Adds Delivery by Nova Poshta');
        $this->secure_key = Tools::encrypt($this->name);

        //$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->confirmUninstall = $this->l('Are you sure you want to delete this module?');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $carrier = $this->addCarrier();
        $this->addZones($carrier);
        $this->addGroups($carrier);
        $this->addRanges($carrier);
        Configuration::updateValue('APARTNER_PRO_NP_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('updateCarrier') &&
            $this->registerHook('displayAdminOrderContentShip') &&
            $this->registerHook('displayAdminOrderTabShip') &&
            $this->registerHook('displayShoppingCart') &&
            $this->registerHook('displayAfterCarrier') &&
            $this->registerHook('displayBeforeCarrier') &&
            $this->registerHook('displayCarrierExtraContent') &&
            $this->registerHook('actionCarrierProcess') &&
            $this->registerHook('actionCarrierUpdate') &&
            $this->registerHook('displayNpBlock');

            //$this->registerHook('displayHeader');
            //$this->registerHook('displayNpBlockCarrier');
    }

    public function uninstall()
    {
        Configuration::deleteByName('APARTNER_PRO_NP_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    public function hookDisplayNpBlock($params){
		//if(Context::getContext()->isMobile() != 1){
			//return false;
		//}

		$cart = Context::getContext()->cart;
        $address_delivery = new Address($cart->id_address_delivery);

		//print_r($address_delivery);
        $this->smarty->assign(array(
            'np_warehouse_selected' => $address_delivery->np_warehouse
        ));
		return $this->display(__FILE__, 'views/templates/hook/np_select.tpl');
	}

    public function hookDisplayNpBlockCarrier($params){

		//if(Context::getContext()->isMobile() != 1){
			//return false;
		//}

		$cart = Context::getContext()->cart;
        $address_delivery = new Address($cart->id_address_delivery);

        $this->smarty->assign(array(
            'np_warehouse_selected' => $address_delivery->np_warehouse,
			'mobile_np_text' => $this->l('выберите отделение')
        ));

		return $this->display(__FILE__, 'views/templates/hook/np_carrier.tpl');
	}

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitApartner_pro_npModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitApartner_pro_npModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'APARTNER_PRO_NP_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'APARTNER_PRO_NP_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'APARTNER_PRO_NP_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'APARTNER_PRO_NP_LIVE_MODE' => Configuration::get('APARTNER_PRO_NP_LIVE_MODE', true),
            'APARTNER_PRO_NP_ACCOUNT_EMAIL' => Configuration::get('APARTNER_PRO_NP_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'APARTNER_PRO_NP_ACCOUNT_PASSWORD' => Configuration::get('APARTNER_PRO_NP_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        if (Context::getContext()->customer->logged == true)
        {
            $id_address_delivery = Context::getContext()->cart->id_address_delivery;
            $address = new Address($id_address_delivery);

            /**
             * Send the details through the API
             * Return the price sent by the API
             */
            return 0;
        }

        return $shipping_cost;
    }

    public function getOrderShippingCostExternal($params)
    {
        return true;
    }

    protected function addCarrier()
    {
        $carrier = new Carrier();

        $carrier->name = $this->l('Nova Poshta');
        $carrier->is_module = true;
        $carrier->active = 1;
        $carrier->range_behavior = 1;
        $carrier->need_range = 1;
        $carrier->shipping_external = true;
        $carrier->range_behavior = 0;
        $carrier->external_module_name = $this->name;
        $carrier->shipping_method = 2;

        foreach (Language::getLanguages() as $lang)
            $carrier->delay[$lang['id_lang']] = $this->l('Nova Poshta delivery');

        if ($carrier->add() == true)
        {
            //@copy(dirname(__FILE__).'/views/img/carrier_image.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg');
            @copy(dirname(__FILE__).'/views/img/logo.png', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg');
            Configuration::updateValue('APARTNER_PRO_NP_CARRIER_ID', (int)$carrier->id);
            return $carrier;
        }

        return false;
    }

    protected function addGroups($carrier)
    {
        $groups_ids = array();
        $groups = Group::getGroups(Context::getContext()->language->id);
        foreach ($groups as $group)
            $groups_ids[] = $group['id_group'];

        $carrier->setGroups($groups_ids);
    }

    protected function addRanges($carrier)
    {
        $range_price = new RangePrice();
        $range_price->id_carrier = $carrier->id;
        $range_price->delimiter1 = '0';
        $range_price->delimiter2 = '10000';
        $range_price->add();

        $range_weight = new RangeWeight();
        $range_weight->id_carrier = $carrier->id;
        $range_weight->delimiter1 = '0';
        $range_weight->delimiter2 = '10000';
        $range_weight->add();
    }

    protected function addZones($carrier)
    {
        $zones = Zone::getZones();

        foreach ($zones as $zone)
            $carrier->addZone($zone['id_zone']);
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'views/js/front.js');
        $this->context->controller->addCSS($this->_path.'views/css/front.css');

        //ps1.6
        $this->context->controller->addCSS(($this->_path).'/views/assets/css/front.css', 'all');
        $this->context->controller->addJS(($this->_path).'views/assets/js/jquery.richAutocomplete.js');
    }

    public function hookUpdateCarrier($params)
    {
        /**
         * Not needed since 1.5
         * You can identify the carrier by the id_reference
        */

        return 'hookUpdateCarrier';

        return $this->display(__FILE__, 'views/templates/hook/np_select.tpl');
    }

    public function hookDisplayAdminOrderContentShip()
    {
        /* Place your code here. */

        return 'hookDisplayAdminOrderContentShip';

        return $this->display(__FILE__, 'views/templates/hook/np_select.tpl');
    }

    public function hookDisplayAdminOrderTabShip()
    {
        /* Place your code here. */

        return 'hookDisplayAdminOrderTabShip';

        return $this->display(__FILE__, 'views/templates/hook/np_select.tpl');
    }

    public function hookDisplayShoppingCart()
    {
        /* Place your code here. */

        return 'hookDisplayShoppingCart';

        return $this->display(__FILE__, 'views/templates/hook/np_select.tpl');
    }

    public function hookDisplayHeader($params){
        return 'hookDisplayHeader';
        $this->context->controller->addCSS(($this->_path).'/views/assets/css/front.css', 'all');
        //$this->context->controller->addJS(($this->_path).'/views/assets/js/jquery.richAutocomplete.js');
        $this->context->controller->addJS(($this->_path).'/views/assets/js/front.js');
    }

    public function hookDisplayBeforeCarrier($params) {
        //var_dump($params);

        return 'hookDisplayBeforeCarrier';

        return $this->display(__FILE__, 'views/templates/hook/np_select.tpl');
    }

    public function hookDisplayAfterCarrier($params) {
        //var_dump($params);

        echo Configuration::get('APARTNER_PRO_NP_CARRIER_ID');

        return 'hookDisplayAfterCarrier';

        return `<form class="clearfix" id="configuration_form" data-url-update="{url entity='order' params=['ajax' => 1, 'action' => 'selectDeliveryOption']}" method="post">
        <div class="panel" id="fieldset_0">
           <div class="form-wrapper">
              <div class="form-group">
                 <label for="myextrafield_id">{l s='Carrier branch additional Address or Name(option): '}</label>
                                 <input type="text" id="my_extrafield_1" name="my_extrafield_1" size="50" maxlength="120" value="" />
                 <label for="my_extrafield_2">{l s='Carrier branch additional Address or Name(option): '}</label>
                                 <input type="text" id="my_extrafield_2" name="my_extrafield_2" size="50" maxlength="120" value="" />
                                 <input type="submit" value="Submit">
              </div>
           </div>
        </div>
     </form>`;

    }

    //!
    private function getNovaPoshtaRegions()
    {
        require_once(dirname(__FILE__) . '/src/Delivery/NovaPoshtaApi2.php');
	    require_once(dirname(__FILE__) . '/src/Delivery/NovaPoshtaApi2Areas.php');

        //$np_api_key = Configuration::get('PS_NP_API');
        $np_api_key = '5f1a0500521765f0bc01c22a48e671a3';

        // URL API Нової Пошти для отримання списку областей
        $apiUrl = 'https://api.novaposhta.ua/v2.0/json/Address/getAreas';

        // Параметри запиту
        $data = array(
            'apiKey' => '5f1a0500521765f0bc01c22a48e671a3', // Підставте свій API ключ Нової Пошти
        );

        // Виконуємо HTTP-запит
        $response = $this->makeNovaPoshtaAPIRequest($apiUrl, $data);

        dd($response);

        // Обробка відповіді
        if ($response) {
            $regions = array();
            $data = json_decode($response, true);

            if (!empty($data['data'])) {
                foreach ($data['data'] as $region) {
                    $regions[] = $region['DescriptionRu'];
                }
            }

            return $regions;
        }

        return array(); // Повернення порожнього масиву у разі невдалого запиту
    }

    private function makeNovaPoshtaAPIRequest($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    //!

    public function hookDisplayCarrierExtraContent($params) {

        require_once(dirname(__FILE__) . '/src/Delivery/NovaPoshtaApi2.php');
        require_once(dirname(__FILE__) . '/src/Delivery/NovaPoshtaApi2Areas.php');

        //'3347e67c4cf1bd22b38bdc4aaaa5a8a0',
        //'b3aa7f2eba071bf3ca713b78be9a9da2',
        //'3347e67c4cf1bd22b38bdc4aaaa5a8a0',
        //'73a9f024b696cab746977890c797519f',

        $np_api_key = Configuration::get('PS_NP_API');
        $np_api_key = '2879527eac969b6d40a8be0cd44463c2';
        $np_ua = new \ApartnerPro\Delivery\NovaPoshtaApi2(
        $np_api_key,
        'ua', // Язык возвращаемых данных: ru (default) | ua | en
        FALSE, // При ошибке в запросе выбрасывать Exception: FALSE (default) | TRUE
        'curl' // Используемый механизм запроса: curl (defalut) | file_get_content
        );

        define('_MODULE_DB_PREFIX_', 'APARTNER_PRO_NP_');

        $tableNP = _MODULE_DB_PREFIX_ . 'np';
        $tableNPExists = Db::getInstance()->executeS("SHOW TABLES LIKE '{$tableNP}'");
        if (empty($tableNPExists)) {
            Db::getInstance()->Execute('CREATE TABLE `'.$tableNP.'` (
                `description` text NOT NULL,
                `ref` varchar(64) NOT NULL,
                `id` int(11) NOT NULL
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
        }

        //d($np_ua->getAreas());


        //exit;

        // Отримайте дані для відображення в корзині та обранні області, місто і відділення Нової Пошти
        //$regions = $this->getNovaPoshtaRegions(); // Отримайте список областей
        //$cities = $this->getNovaPoshtaCities();     // Отримайте список міст
        //$deliveryPoints = $this->getNovaPoshtaDeliveryPoints(); // Отримайте список відділень

       // dd($regions);

        //$this->context->smarty->assign(array(
            //'regions' => $regions,
            //'cities' => $cities,
            //'deliveryPoints' => $deliveryPoints,
        //));

        // Далі відобразіть ці дані у вигляді вибору відправлення в корзині
        // Використайте Smarty для відображення списку областей, міст і відділень у вашому шаблоні

        return $this->display(__FILE__, 'views/templates/hook/extraCarrier.tpl');

        //dd($params['carrier']);

        // Отримайте значення змінних, які вам потрібно вставити в JavaScript
        $npWarehouseSelected = 'some_value';
        $mobileNpText = 'another_value';

        // Створіть JavaScript-змінну
        $jsData = array(
            'npWarehouseSelected' => $npWarehouseSelected,
            'mobileNpText' => $mobileNpText
        );

        // Перетворіть масив в JSON
        $jsDataJson = json_encode($jsData);

        // Передайте JavaScript-змінну до шаблону
        $this->context->smarty->assign('jsData', $jsDataJson);


        if($params['carrier']["external_module_name"] === 'apartner_pro_np') {
            if(_PS_VERSION_ > 8) {

                if(Context::getContext()->isMobile() != 1){
                    return $this->display(__FILE__, 'views/templates/hook/ps8/np_select.tpl');
                }

                return $this->display(__FILE__, 'views/templates/hook/ps8/np_carrier.tpl');
            } else if(_PS_VERSION_ < '1.6') {

                if(Context::getContext()->isMobile() != 1){
                    return $this->display(__FILE__, 'views/templates/hook/ps16/np_select.tpl');
                }

                return $this->display(__FILE__, 'views/templates/hook/ps16/np_carrier.tpl');
            }
        }

        return `<div class="row carrier-extra-content">
        <input type="hidden" id="id_cart" name="id_cart" value="133">
<div class="col-sm-12">
<div class="">
<div class="col-xs-12 mb-2">
<div class="mb-2">
<label for="exampleFormControlInput1" class="form-label">Вкажіть регіон</label>
<select class="form-select form-control form-control-select" name="getAreas" id="getAreas"><option value="АРК" data-ref="71508128-9b87-11de-822f-000c2965ae0e">АРК</option><option value="Вінницька" data-ref="71508129-9b87-11de-822f-000c2965ae0e">Вінницька</option><option value="Волинська" data-ref="7150812a-9b87-11de-822f-000c2965ae0e">Волинська</option><option value="Дніпропетровська" data-ref="7150812b-9b87-11de-822f-000c2965ae0e">Дніпропетровська</option><option value="Донецька" data-ref="7150812c-9b87-11de-822f-000c2965ae0e">Донецька</option><option value="Житомирська" data-ref="7150812d-9b87-11de-822f-000c2965ae0e">Житомирська</option><option value="Закарпатська" data-ref="7150812e-9b87-11de-822f-000c2965ae0e">Закарпатська</option><option value="Запорізька" data-ref="7150812f-9b87-11de-822f-000c2965ae0e">Запорізька</option><option value="Івано-Франківська" data-ref="71508130-9b87-11de-822f-000c2965ae0e">Івано-Франківська</option><option value="Київська" data-ref="71508131-9b87-11de-822f-000c2965ae0e">Київська</option><option value="Кіровоградська" data-ref="71508132-9b87-11de-822f-000c2965ae0e">Кіровоградська</option><option value="Луганська" data-ref="71508133-9b87-11de-822f-000c2965ae0e">Луганська</option><option value="Львівська" data-ref="71508134-9b87-11de-822f-000c2965ae0e">Львівська</option><option value="Миколаївська" data-ref="71508135-9b87-11de-822f-000c2965ae0e">Миколаївська</option><option value="Одеська" data-ref="71508136-9b87-11de-822f-000c2965ae0e">Одеська</option><option value="Полтавська" data-ref="71508137-9b87-11de-822f-000c2965ae0e">Полтавська</option><option value="Рівненська" data-ref="71508138-9b87-11de-822f-000c2965ae0e">Рівненська</option><option value="Сумська" data-ref="71508139-9b87-11de-822f-000c2965ae0e">Сумська</option><option value="Тернопільська" data-ref="7150813a-9b87-11de-822f-000c2965ae0e">Тернопільська</option><option value="Харківська" data-ref="7150813b-9b87-11de-822f-000c2965ae0e">Харківська</option><option value="Херсонська" data-ref="7150813c-9b87-11de-822f-000c2965ae0e">Херсонська</option><option value="Хмельницька" data-ref="7150813d-9b87-11de-822f-000c2965ae0e">Хмельницька</option><option value="Черкаська" data-ref="7150813e-9b87-11de-822f-000c2965ae0e">Черкаська</option><option value="Чернівецька" data-ref="7150813f-9b87-11de-822f-000c2965ae0e">Чернівецька</option><option value="Чернігівська" data-ref="71508140-9b87-11de-822f-000c2965ae0e">Чернігівська</option></select>
</div>
<div class="mb-2">
<label for="exampleFormControlInput1" class="form-label">Вкажіть місто</label>
<select class="form-select form-control form-control-select" name="getCities" id="getCities" disabled=""><option selected="">Виберіть регіон</option></select>
</div>
<div class="mb-2">
<label for="exampleFormControlInput1" class="form-label">Вкажіть відділення або поштомат</label>
<select class="form-select form-control form-control-select" name="getWarehouses" id="getWarehouses" disabled=""><option selected="">Виберіть регіон</option></select>
</div>
</div>
</div>
</div>
<script>
$(document).ready(function () {
$.ajax({
url: "../modules/skyneuron_novaposhta/views/php/file.php",
method: "POST",
data: {
"getAreas": "1"
},
success: function (data) {
$("#getCities").prop('disabled', true);
$("#getWarehouses").prop('disabled', true);
$("#getCities").append("<option selected>Виберіть регіон</option>");
$("#getWarehouses").append("<option selected>Виберіть регіон</option>");
$("#getAreas").html(data);
}
});
});

$(document).ready(function () {
$('#getAreas').change(function () {
var getAreas = $(this).find(':selected').data("ref");

$("#getCities").append("<option selected>Завантаження...</option>");
$("#getWarehouses").append("<option selected>Завантаження...</option>");

$.ajax({
url: "../modules/skyneuron_novaposhta/views/php/file.php",
type: "POST",
data: {
"getCities": "1",
"getAreas": getAreas
},
success: function (data) {
$("#getCities").prop('disabled', false);
$("#getCities").html(data);
var firstOption = $("#getCities").find("option:first");
var dataRefValue = firstOption.attr("data-ref");
if (data == "") {
$("#getCities").find('option').remove();
$("#getWarehouses").find('option').remove();
$("#getCities").prop('disabled', true);
$("#getCities").append("<option selected>Пусто</option>");
$("#getWarehouses").prop('disabled', true);
$("#getWarehouses").append("<option selected>Пусто</option>");
} else {
$("#getCities").prop('disabled', false);
$.ajax({
url: "/modules/skyneuron_novaposhta/views/php/file.php",
type: "POST",
data: {
"getWarehouses": "1",
"getCities": dataRefValue
},
success: function (data) {
$("#getWarehouses").prop('disabled', false);
if (data == "") {
$("#getWarehouses").find('option').remove();
$("#getWarehouses").html('<option selected>Немає відділень або поштоматів.</option>');
$("#getWarehouses").prop("disabled", true);
$("#btn_place_order").prop("disabled", true);
} else {
$("#getWarehouses").html(data);
$("#btn_place_order").prop("disabled", false);
var getAreas = $("#getAreas option:selected").text();
var getCities = $("#getCities option:selected").text();
var getWarehouses = $("#getWarehouses option:selected").text();
$.ajax({
  url: "../modules/skyneuron_novaposhta/views/php/db.php",
  type: "POST",
  data: {
     "id_cart": $("#id_cart").val(),
     "getAreas": getAreas,
     "getCities": getCities,
     "getWarehouses": getWarehouses
  },
  success: function (data) {},
  error: function (request, error) {

  }
});
}
},
error: function (request, error) {

}
});
}
},
error: function (request, error) {

}
});
});
});

$(document).ready(function () {
$('#getCities').change(function () {
var getCities = $(this).find(':selected').data("ref");

$.ajax({
url: "../modules/skyneuron_novaposhta/views/php/file.php",
type: "POST",
data: {
"getWarehouses": "1",
"getCities": getCities
},
success: function (data) {
$("#getWarehouses").prop('disabled', false);
if (data == "") {
$("#getWarehouses").find('option').remove();
$("#getWarehouses").html('<option selected>Немає відділень або поштоматів.</option>');
$("#getWarehouses").prop("disabled", true);
$("#btn_place_order").prop("disabled", true);
} else {
$("#getWarehouses").html(data);
$("#btn_place_order").prop("disabled", false);
var getAreas = $("#getAreas option:selected").text();
var getCities = $("#getCities option:selected").text();
var getWarehouses = $("#getWarehouses option:selected").text();

$.ajax({
url: "../modules/skyneuron_novaposhta/views/php/db.php",
type: "POST",
data: {
"id_cart": $("#id_cart").val(),
"getAreas": getAreas,
"getCities": getCities,
"getWarehouses": getWarehouses
},
success: function (data) {},
error: function (request, error) {

}
});
}
},
error: function (request, error) {}
});
});
});
$(document).ready(function () {
$('#getWarehouses').change(function(){
var getAreas = $("#getAreas option:selected").text();
var getCities = $("#getCities option:selected").text();
var getWarehouses = $("#getWarehouses option:selected").text();

$.ajax({
url: "../modules/skyneuron_novaposhta/views/php/db.php",
type : "POST",
data : {
"id_cart": $("#id_cart").val(),
"getAreas": getAreas,
"getCities": getCities,
"getWarehouses": getWarehouses
},
success : function(data) {

},
error : function(request,error)
{

}
});
});
});
</script>
<script>
$(document).ready(function(){
if ($("#delivery_option_10").is(":checked")) {
$("#btn_place_order").prop("disabled", true);
} else {
$("#btn_place_order").prop("disabled", false);
}
});
</script>
    </div>`;

        return 'displayCarrierExtraContent';
    }


    public function hookActionCarrierProcess($params) {
        //var_dump($params);
        //exit;
    }

    public function hookActionCarrierUpdate($params) {
        var_dump($params);
        exit;
    }


    public static function validateNpCity($city)
	{
		// $is_ua = Tools::checkUkraineSymbols($city);
		// if ($is_ua == false)
		// 	$sql = 'SELECT `ref` FROM `ps_np` WHERE description = "' . pSQL($city) . '"';
		// else
		// 	$sql = 'SELECT `ref` FROM `ps_np_ukr` WHERE description = "' . pSQL($city) . '"';

		$sql = 'SELECT `ref` FROM `ps_np_ukr` WHERE description = "' . pSQL($city) . '"';
		$sql .= ' UNION ';
		$sql .= 'SELECT `ref` FROM `ps_np` WHERE description = "' . pSQL($city) . '"';

		if ($res = Db::getInstance()->ExecuteS($sql))
			return true;
		else
			return false;
	}

	public static function checkUkraineSymbols($str)
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
}