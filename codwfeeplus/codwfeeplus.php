<?php

/** Copyright 2018 Sakis Gkiokas
 * This file is part of codwfeeplus module for Prestashop.
 *
 * Codwfeeplus is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Codwfeeplus is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * For any recommendations and/or suggestions please contact me
 * at sakgiok@gmail.com
 *
 *  @author    Sakis Gkiokas <sakgiok@gmail.com>
 *  @copyright 2018 Sakis Gkiokas
 *  @license   https://opensource.org/licenses/GPL-3.0  GNU General Public License version 3
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
if (!defined('_PS_PRICE_COMPUTE_PRECISION_')) {
    define('_PS_PRICE_COMPUTE_PRECISION_', _PS_PRICE_DISPLAY_PRECISION_);
}
//use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
include_once _PS_MODULE_DIR_ . 'codwfeeplus/CODwFP.php';

class codwfeeplus extends PaymentModule
{

    private $_html = '';
    private $_postErrors = array();
    public $_testoutput = '';
    public $_testoutput_check = '';
    public $_testoutput_applyfee = '';
    public $_testoutput_method_active = true;
    public $_cond_integration = 0;
    public $_cond_orderstate = 0;
    public $_cond_taxrule = 0;
    public $_updatestatus = array(
        'res' => '',
        'cur_version' => '',
        'download_link' => '',
        'info_link' => '',
        'github_link' => '',
    );
    public $public_name = '';
    private $tab_name = '';
    private $_integration_general_arr = array();
    private $_integration_condition_arr = array();
    private $tmp_shop_context_type = Shop::CONTEXT_ALL;
    private $tmp_shop_context_id = 0;
    private $_newProductId = 0;
    public $is17 = false;

    public function __construct()
    {
        if (Tools::version_compare(_PS_VERSION_, '1.7.0', '>=')) {
            $this->is17 = true;
        }
        $this->name = 'codwfeeplus';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.9';
        $this->author = 'Sakis Gkiokas';
        $this->need_instance = 1;
        if ($this->is17) {
            $this->controllers = array('validation', 'ajax');
        } else {
            $this->controllers = array('validation');
        }

        $this->is_eu_compatible = 1;
        $this->secure_key = Tools::encrypt($this->name);
        $this->currencies = true;
//        $this->currencies_mode = 'radio';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Cash on delivery with fee (COD) PLUS');
        $this->description = $this->l('Accept cash on delivery payments with extra fee and more options');
        $this->ps_versions_compliancy = array('min' => '1.6.0.6', 'max' => '8.99.99');
        $this->public_name = $this->l('Cash on delivery');
        $this->tab_name = $this->l('COD with Fee Plus');
        $this->_integration_general_arr = array(
            0 => 'defined by first successful condition',
            1 => 'add to carrier\'s fee',
            2 => 'add a COD product to the order',
        );
        $this->_integration_condition_arr = array(
            0 => 'add to carrier\'s fee',
            1 => 'add a COD product to the order',
        );
        if (!$this->getProductStatus()) {
            $this->warning = $this->l('COD Product was not found.');
        }
    }

    public function installTab($parent, $class_name, $name)
    {
        // Create new admin tab
        $tab = new Tab();
        $tab->id_parent = (int) Tab::getIdFromClassName($parent);
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->getTranslation($name, $lang['iso_code']);
        }
        $tab->class_name = $class_name;
        $tab->module = $this->name;
        $tab->active = 1;

        return $tab->add();
    }

    public function uninstallTab($class_name)
    {
        // Retrieve Tab ID
        $id_tab = (int) Tab::getIdFromClassName($class_name);
// Load tab
        $tab = new Tab((int) $id_tab);
// Delete it
        return $tab->delete();
    }

    public function install()
    {
        $tab_parent = 'AdminParentModules';
        if ($this->is17) {
            $tab_parent = 'AdminParentPayment';
        }
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        if (!parent::install()
                or ! $this->createTables()
                or ! $this->installTab($tab_parent, 'AdminCODwFeePlus', 'COD with Fee Plus')
                or ! $this->registerHook('payment')
                or ! $this->registerHook('paymentReturn')
                or ! $this->registerHook('updateCarrier')
                or ! $this->registerHook('header')
                or ! $this->registerHook('PaymentOptions')
                or ! Configuration::updateValue('SG_CODWFEEPLUS_BEHAVIOUR', 0)
                or ! Configuration::updateValue('SG_CODWFEEPLUS_KEEPTRANSACTIONS', 1)
                or ! Configuration::updateValue('SG_CODWFEEPLUS_INTEGRATION_WAY', 0)
                or ! Configuration::updateValue('SG_CODWFEEPLUS_ORDERSTATE', Configuration::get('PS_OS_PREPARATION'))
                or ! Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_ID', 0)
                or ! Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_REFERENCE', 'COD')
                or ! Configuration::updateValue('SG_CODWFEEPLUS_AUTO_UPDATE', 0)
                or ! Configuration::updateValue('SG_CODWFEEPLUS_INFO_LINK', 'https://sakgiok.gr/programs/codwfeeplus/')
                or ! Configuration::updateValue('SG_CODWFEEPLUS_GITHUB_LINK', 'https://github.com/sakgiok/codwfeeplus')
                or ! Configuration::updateValue('SG_CODWFEEPLUS_LOGO_FILENAME_17', 'codwfeeplus_logo_17.png')
                or ! Configuration::updateValue('SG_CODWFEEPLUS_LOGO_ENABLED', 0)
                or ! $this->installMultiLangParameters()
                or ! $this->installCODProduct()
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        if (!$this->removeCODProduct()
                or ! $this->deleteTables()
                or ! Configuration::deleteByName('SG_CODWFEEPLUS_BEHAVIOUR')
                or ! Configuration::deleteByName('SG_CODWFEEPLUS_KEEPTRANSACTIONS')
                or ! Configuration::deleteByName('SG_CODWFEEPLUS_INTEGRATION_WAY')
                or ! Configuration::deleteByName('SG_CODWFEEPLUS_ORDERSTATE')
                or ! Configuration::deleteByName('SG_CODWFEEPLUS_PRODUCT_ID')
                or ! Configuration::deleteByName('SG_CODWFEEPLUS_PRODUCT_TITLE')
                or ! Configuration::deleteByName('SG_CODWFEEPLUS_PRODUCT_REFERENCE')
                or ! Configuration::deleteByName('SG_CODWFEEPLUS_AUTO_UPDATE')
                or ! Configuration::deleteByName('SG_CODWFEEPLUS_INFO_LINK')
                or ! Configuration::deleteByName('SG_CODWFEEPLUS_GITHUB_LINK')
                or ! Configuration::deleteByName('SG_CODWFEEPLUS_LOGO_FILENAME_17')
                or ! Configuration::deleteByName('SG_CODWFEEPLUS_LOGO_ENABLED')
                or ! $this->uninstallTab('AdminCODwFeePlus')
                or ! parent::uninstall()) {
            return false;
        }

        return true;
    }

    public function getContent()
    {
        $link = $this->context->link->getAdminLink('AdminCODwFeePlus', true);
        Tools::redirectAdmin($link);
    }

    public function storeContextShop()
    {
        if (Shop::isFeatureActive()) {
            $this->tmp_shop_context_type = Shop::getContext();
            if ($this->tmp_shop_context_type != Shop::CONTEXT_ALL) {
                if ($this->tmp_shop_context_type == Shop::CONTEXT_GROUP) {
                    $this->tmp_shop_context_id = Shop::getContextShopGroupID();
                } else {
                    $this->tmp_shop_context_id = Shop::getContextShopID();
                }
            }
        }
    }

    public function resetContextShop()
    {
        if (Shop::isFeatureActive()) {
            if ($this->tmp_shop_context_type != Shop::CONTEXT_ALL) {
                Shop::setContext($this->tmp_shop_context_type, $this->tmp_shop_context_id);
            } else {
                Shop::setContext($this->tmp_shop_context_type);
            }
        }
    }

    private function installCODProduct_loop()
    {
        $res = true;
        $p = new Product(null, false, Configuration::get('PS_LANG_DEFAULT'));
        $p->reference = Configuration::get('SG_CODWFEEPLUS_PRODUCT_REFERENCE');
        $p->name = 'cod product';
        $p->is_virtual = false;
        $p->indexed = 0;
        $p->id_category_default = Configuration::get('PS_HOME_CATEGORY');
        $p->link_rewrite = 'pr-codwfeeplus';
        $res &= $p->save();
        $res &= $p->addToCategories(array(Configuration::get('PS_HOME_CATEGORY')));
        $res &= Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_ID', $p->id);
        $this->_newProductId = $p->id;

        return $res;
    }

    public function installCODProduct()
    {
        $this->removeCODProduct();

        $this->storeContextShop();
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        $res = true;

        $res &= $this->installCODProduct_loop();
        if (Shop::isFeatureActive()) {
            $shop_list = Shop::getShops(true, null, true);
            foreach ($shop_list as $value) {
                Shop::setContext(Shop::CONTEXT_SHOP, $value);
                $res &= Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_ID', $this->_newProductId);
                $res &= $this->updateCODProduct(1.0);
            }
        } else {
            $res &= $this->updateCODProduct(1.0);
        }
        $this->resetContextShop();

        return $res;
    }

    public function addCODProductToCart($cart)
    {
        $cod_product_id = (int) Configuration::get('SG_CODWFEEPLUS_PRODUCT_ID');
        $cart->updateQty(1, $cod_product_id);
        $cart->update();
    }

    public function removeCODProductFromCart($cart)
    {
        $cod_product_id = (int) Configuration::get('SG_CODWFEEPLUS_PRODUCT_ID');
        $cod_product = $cart->getProducts(false, $cod_product_id);
        if (!empty($cod_product)) {
            $q = (int) $cod_product[0]['quantity'];
            $cart->updateQty($q, $cod_product_id, null, false, 'down');
            $cart->update();
        }
    }

    private function updateCODProduct_Loop($price = null, $id_tax_rules_group = 0)
    {
        $ret = true;
        if ($this->is17) {
            Configuration::loadConfiguration();
        }
        $p = new Product((int) Configuration::get('SG_CODWFEEPLUS_PRODUCT_ID'));
        $pname = array();
        $plink_rewrite = array();
        foreach (Language::getLanguages(true) as $lang) {
            $pname[$lang['id_lang']] = Configuration::get('SG_CODWFEEPLUS_PRODUCT_TITLE', (int) $lang['id_lang']);
            $plink_rewrite[$lang['id_lang']] = 'pr-codwfeeplus';
        }
        $p->name = $pname;
        $p->description = $pname;
        $p->description_short = $pname;
        $p->reference = Configuration::get('SG_CODWFEEPLUS_PRODUCT_REFERENCE');
        $p->active = false;
        $p->visibility = 'none';
        $p->minimal_quantity = 1;
        $p->available_for_order = true;
        $p->link_rewrite = $plink_rewrite;
        $p->id_tax_rules_group = $id_tax_rules_group;
        $p->redirect_type = '404';
        //Only for a specific customization of a module I made
        if (isset($p->skroutz_available)) {
            $p->skroutz_available = 0;
        }
        ///
        if ($price != null) {
            $tax = ((float) $p->getTaxesRate()) * 0.01;
            $price_notax = Tools::ps_round((float) $price / (1.0 + $tax), 9);
            $found = null;
            if ($this->context->currency->id != Configuration::get('PS_CURRENCY_DEFAULT')) {
                $sp_array = SpecificPrice::getByProductId($p->id);
                if ($sp_array && count($sp_array)) {
                    foreach ($sp_array as $value) {
                        if ($value['id_currency'] == $this->context->currency->id) {
                            $found = $value['id_specific_price'];
                        }
                    }
                }
                $s = new SpecificPrice($found);
                $s->price = $price_notax;
                $s->id_product = $p->id;
                $s->id_currency = $this->context->currency->id;
                $s->id_shop = 0;
                $s->id_country = 0;
                $s->id_customer = 0;
                $s->id_group = 0;
                $s->id_product_attribute = 0;
                $s->id_shop_group = 0;
                $s->from_quantity = 1;
                $s->reduction = 0;
                $s->reduction_type = 'amount';
                $s->from = 0;
                $s->to = 0;
                $s->save();
                unset($s);
            } else {
                $p->price = $price_notax;
            }
        }
        $p->quantity = 100;

//        if (Shop::isFeatureActive()) {
//            $p->id_shop_list = Shop::getShops(true, null, true);
//        }
        $ret &= $p->save();

        if (StockAvailable::getQuantityAvailableByProduct($p->id) < 100) {
            StockAvailable::setQuantity($p->id, 0, 100);
            StockAvailable::setProductOutOfStock($p->id, true);
        }

        return $ret;
    }

    public function updateCODProduct($price = null, $id_tax_rules_group = 0)
    {
        if (!((int) Configuration::get('SG_CODWFEEPLUS_PRODUCT_ID'))) {
            return false;
        }
        $ret = true;
        $ret &= $this->updateCODProduct_Loop($price, $id_tax_rules_group);

        return $ret;
    }

    public function removeCODProduct()
    {
        $this->storeContextShop();
        $ret = true;
        if (Shop::isFeatureActive()) {
            $shop_list = Shop::getShops(true, null, true);
            foreach ($shop_list as $value) {
                Shop::setContext(Shop::CONTEXT_SHOP, $value);
                $cod_product_id = Configuration::get('SG_CODWFEEPLUS_PRODUCT_ID');
                if ($cod_product_id != '0') {
                    $p = new Product((int) $cod_product_id);
                    $p->delete();
                    unset($p);
                }
                $ret &= Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_ID', 0);
            }
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        $cod_product_id = Configuration::get('SG_CODWFEEPLUS_PRODUCT_ID');
        if ($cod_product_id != '0') {
            $p = new Product((int) $cod_product_id);
            $p->delete();
            unset($p);
        }
        $ret &= Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_ID', 0);

        $this->resetContextShop();

        return $ret;
    }

    private function getProductStatus_Loop()
    {
        $p_exists = true;
        $p_id = Configuration::get('SG_CODWFEEPLUS_PRODUCT_ID');
        $p = new Product((int) $p_id);
        $p_exists &= $p->existsInDatabase($p_id, 'product');
        if ($p_exists) {
            foreach (Language::getLanguages(true) as $lang) {
                if (array_key_exists($lang['id_lang'], $p->name)) {
                    $p_exists &= ($p->name[$lang['id_lang']] == Configuration::get('SG_CODWFEEPLUS_PRODUCT_TITLE', (int) $lang['id_lang']));
                } else {
                    $p_exists &= false;
                }
            }
        }
//        $p_exists &= ($p->reference == Configuration::get('SG_CODWFEEPLUS_PRODUCT_REFERENCE'));
        return $p_exists;
    }

    public function getProductStatus()
    {
        if (!((int) Configuration::get('SG_CODWFEEPLUS_PRODUCT_ID'))) {
            return false;
        }

        $ret = true;
        $ret &= $this->getProductStatus_Loop();

        return $ret;
    }

    public function installMultiLangParameters()
    {
        $ret = true;
        $title = array();
        $desc = array();
        $desc_short = array();
        foreach (Language::getLanguages(true) as $lang) {
            if ($lang['iso_code'] == 'el') {
                $title[$lang['id_lang']] = 'Χρέωση Αντικαταβολής';
            } else {
                $title[$lang['id_lang']] = 'Cash on Delivery Fee';
            }
        }
        $ret &= Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_TITLE', $title);

        return $ret;
    }

    public function createTables()
    {
        /* Conditions */
        $ret = Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'codwfeeplus_conditions` (
			  `id_codwfeeplus_cond` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `codwfeeplus_fee` DECIMAL(13, 4) NOT NULL DEFAULT \'0\',
                          `codwfeeplus_fee_min` DECIMAL(13, 4) NOT NULL DEFAULT \'0\',
                          `codwfeeplus_fee_max` DECIMAL(13, 4) NOT NULL DEFAULT \'0\',
                          `codwfeeplus_fee_percent` DECIMAL(13, 4) NOT NULL DEFAULT \'0\',
                          `codwfeeplus_fee_type` int(2) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_integration` int(2) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_taxrule_id` int(2) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_orderstate_id` int(2) unsigned NOT NULL DEFAULT \'0\',
						  `codwfeeplus_active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_condtype` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_fee_percent_include_carrier` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_cartvalue_include_carrier` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_position` int(10) unsigned NOT NULL,
                          `codwfeeplus_countries` TEXT,
						  `codwfeeplus_states` TEXT,
                          `codwfeeplus_carriers` TEXT,
                          `codwfeeplus_zones` TEXT,
                          `codwfeeplus_manufacturers` TEXT,
                          `codwfeeplus_matchall_manufacturers` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_suppliers` TEXT,
                          `codwfeeplus_matchall_suppliers` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_categories` TEXT,
                          `codwfeeplus_matchall_categories` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_groups` TEXT,
                          `codwfeeplus_matchall_groups` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_desc` TEXT,
                          `codwfeeplus_cartvalue_sign` int(2) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_cartvalue` DECIMAL(13, 4) NOT NULL DEFAULT \'0\',
                          `codwfeeplus_shop` int(10) unsigned NOT NULL DEFAULT \'0\',
			  PRIMARY KEY (`id_codwfeeplus_cond`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
		');
        $ret &= Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'codwfeeplus_transactions` (
			  `id_codwfeeplus_trans` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `codwfeeplus_result` TEXT,
                          `codwfeeplus_datetime` DATETIME NOT NULL,
                          `codwfeeplus_customer_id` int(10) NOT NULL DEFAULT \'0\',
                          `codwfeeplus_order_id` int(10) NOT NULL DEFAULT \'0\',
                          `codwfeeplus_fee` DECIMAL(13, 4) NOT NULL DEFAULT \'0\',
                          `codwfeeplus_cart_total` DECIMAL(13, 4) NOT NULL DEFAULT \'0\',
                          `codwfeeplus_shop` int(10) unsigned NOT NULL DEFAULT \'0\',
			  PRIMARY KEY (`id_codwfeeplus_trans`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
		');

        return $ret;
    }

    /**
     * deletes tables.
     */
    public function deleteTables()
    {
        $ret = Db::getInstance()->execute('
			DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'codwfeeplus_conditions`;
		');
        $ret &= Db::getInstance()->execute('
			DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'codwfeeplus_transactions`;
		');

        return $ret;
    }

    public function addTransaction($customer_id, $order_id, $fee, $cart_total, $result)
    {
        if (!Configuration::get('SG_CODWFEEPLUS_KEEPTRANSACTIONS')) {
            return true;
        }
        $shop_id = 0;
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $shop_id = Shop::getContextShopID();
            }
        }

        $date = date('Y-m-d H:i:s');
        $ret = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'codwfeeplus_transactions`
            (`codwfeeplus_result`,`codwfeeplus_datetime`,`codwfeeplus_customer_id`,
            `codwfeeplus_order_id`,`codwfeeplus_fee`,`codwfeeplus_cart_total`,`codwfeeplus_shop`) VALUES('
                . '\'' . pSQL($result, true) . '\','
                . '\'' . $date . '\','
                . $customer_id . ','
                . $order_id . ','
                . $fee . ','
                . $cart_total . ','
                . $shop_id
                . ')'
        );

        return $ret;
    }

    public function hasProductDownload($cart)
    {
        foreach ($cart->getProducts() as $product) {
            $pd = ProductDownload::getIdFromIdProduct((int) ($product['id_product']));
            if ($pd and Validate::isUnsignedInt($pd)) {
                return true;
            }
        }

        return false;
    }

    public function hookUpdateCarrier($params)
    {
        $this->updateConditionsAfterCarrierUpdate($params['id_carrier'], $params['carrier']->id);

        return;
    }

    public function hookHeader($params)
    {
        if ($this->is17) {
            $this->context->controller->addCSS($this->_path . 'views/css/style-front_17.css');
            $this->context->controller->addJS($this->_path . 'views/js/front.js');
        } else {
            $this->context->controller->addCSS($this->_path . 'views/css/style-front.css');
        }
        $this->context->controller->addJS($this->_path . 'views/js/front-reorder.js');
        Media::addJsDef(array(
            'codwfeeplus_codproductreference' => Configuration::get('SG_CODWFEEPLUS_PRODUCT_REFERENCE'),
            'codwfeeplus_codproductid' => Configuration::get('SG_CODWFEEPLUS_PRODUCT_ID'),
            'codwfeeplus_is17' => $this->is17,
                )
        );
    }

    public function updateConditionsAfterCarrierUpdate($old_id, $new_id)
    {
        $conds_db = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'codwfeeplus_conditions`');
        foreach ($conds_db as $cond) {
            $cond_id = $cond['id_codwfeeplus_cond'];
            $C = new CODwFP($cond_id);
            $carriers_array = $C->getDeliveryArray();
            foreach ($carriers_array as &$value) {
                if ($value == $old_id) {
                    $value = $new_id;
                }
            }
            $C->setDeliveryArray($carriers_array);
            $C->saveToDB();
            unset($C);
        }

        return;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        $fee = $this->getCost($params);

        if (!$this->_testoutput_method_active) {
            return false;
        }
        $integration = Configuration::get('SG_CODWFEEPLUS_INTEGRATION_WAY');
        $cond_integration = $this->_cond_integration;
        $integration_product = false;
        if ($integration == 0) {
            //By condition
            if ($cond_integration == 1 && $fee != 0) {
                $integration_product = true;
            }
        } elseif ($integration == 2 && $fee != 0) {
            $integration_product = true;
        }

        $product_valid = $this->getProductStatus();

        if (!$product_valid && $integration_product) {
            return false;
        }

// Check if cart has product download
        if ($this->hasProductDownload($params['cart'])) {
            return false;
        }

        $link = new Link;
        $parameters = array("action" => "getCartSummary");
        $ajax_link = $link->getModuleLink($this->name, 'ajax', $parameters);

        $this->context->smarty->assign(array(
            'fee' => number_format($fee, 2, '.', ''),
            'fee_formatted' => Tools::displayPrice($fee),
            'ajax_link' => $ajax_link,
        ));

        $payment_options = [$this->getPaymentOptionValue()];
        return $payment_options;
    }

    public function getPaymentOptionValue()
    {
        $pOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;
        $pOption->setModuleName($this->name)
                ->setCallToActionText($this->l('Pay with Cash on Delivery'))
                ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                ->setAdditionalInformation($this->context->smarty->fetch('module:codwfeeplus/views/templates/hook/payment_infos.tpl'));
        if (Configuration::get('SG_CODWFEEPLUS_LOGO_ENABLED')) {
            $pOption->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . '' . $this->name . '/views/img/' . Configuration::get('SG_CODWFEEPLUS_LOGO_FILENAME_17')));
        }
        return $pOption;
    }

    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }

        $fee = $this->getCost($params);

        if (!$this->_testoutput_method_active) {
            return false;
        }

        $integration = Configuration::get('SG_CODWFEEPLUS_INTEGRATION_WAY');
        $cond_integration = $this->_cond_integration;
        $integration_product = false;
        if ($integration == 0) {
            //By condition
            if ($cond_integration == 1 && $fee != 0) {
                $integration_product = true;
            }
        } elseif ($integration == 2 && $fee != 0) {
            $integration_product = true;
        }

        $product_valid = $this->getProductStatus();

        if (!$product_valid && $integration_product) {
            return false;
        }

// Check if cart has product download
        if ($this->hasProductDownload($params['cart'])) {
            return false;
        }

        $this->smarty->assign(array(
            'fee' => number_format($fee, 2, '.', ''),
        ));

        return $this->display(__FILE__, 'payment.tpl');
    }

    private function getCategoriesFromCart($cart_id)
    {
        $ret = array();
        $cart = new Cart($cart_id);
        $products = $cart->getProducts(true);
        foreach ($products as $product) {
            $cat = Product::getProductCategoriesFull($product['id_product']);
            foreach ($cat as $value) {
                if (!in_array($value['id_category'], $ret)) {
                    $ret[] = $value['id_category'];
                }
            }
        }

        return $ret;
    }

    private function getManufacturersFromCart($cart_id)
    {
        $ret = array();
        $cart = new Cart($cart_id);
        $products = $cart->getProducts(true);
        foreach ($products as $product) {
            if (!in_array($product['id_manufacturer'], $ret)) {
                $ret[] = $product['id_manufacturer'];
            }
        }

        return $ret;
    }

    private function getSuppliersFromCart($cart_id)
    {
        $ret = array();
        $cart = new Cart($cart_id);
        $products = $cart->getProducts(true);
        foreach ($products as $product) {
            if (!in_array($product['id_supplier'], $ret)) {
                $ret[] = $product['id_supplier'];
            }
        }

        return $ret;
    }

    private function getCarrierName($id_carrier)
    {
        $car = new Carrier($id_carrier);

        return $car->name;
    }

    public function getShopName($id_shop)
    {
        $shop = new Shop($id_shop);

        return $shop->name;
    }

    private function getCountryName($id_country)
    {
        $country = new Country($id_country);

        return $country->name[(int) Configuration::get('PS_LANG_DEFAULT')];
    }

    private function getStateName($id_state)
    {
        $ret = 'No state defined in cart';
        if ($id_state > 0) {
            $state = new State($id_state);
            $ret = $state->name;
        }
        return $ret;
    }

    private function getZoneName($id_zone)
    {
        $zone = new Zone($id_zone);

        return $zone->name;
    }

    private function getCategoriesNameArray($categories_array)
    {
        $ret = array();
        foreach ($categories_array as $value) {
            $cat = new Category($value);
            $ret[] = $cat->name[(int) Configuration::get('PS_LANG_DEFAULT')];
            unset($cat);
        }

        return $ret;
    }

    private function getGroupNameArray($groups_array)
    {
        $ret = array();
        foreach ($groups_array as $value) {
            $g = new Group($value);
            $ret[] = $g->name[(int) Configuration::get('PS_LANG_DEFAULT')];
            unset($g);
        }

        return $ret;
    }

    private function getManufacturersNameArray($manufacturers_array)
    {
        $manuf_empty_label = $this->l('Empty manufacturer');
        if ($this->is17) {
            $manuf_empty_label = $this->l('Empty brand');
        }
        $ret = array();
        foreach ($manufacturers_array as $value) {
            if ($value == '0') {
                $ret[] = $manuf_empty_label;
            } else {
                $g = new Manufacturer($value);
                $ret[] = $g->name;
                unset($g);
            }
        }

        return $ret;
    }

    private function getSuppliersNameArray($suppliers_array)
    {
        $ret = array();
        foreach ($suppliers_array as $value) {
            if ($value == '0') {
                $ret[] = $this->l('Empty supplier');
            } else {
                $g = new Supplier($value);
                $ret[] = $g->name;
                unset($g);
            }
        }

        return $ret;
    }

    public function getCost($params)
    {
        $id_shop = $params['cart']->id_shop;
        $id_carrier = $params['cart']->id_carrier;
        $address = Address::getCountryAndState($params['cart']->id_address_delivery);
        $id_country = $address['id_country'] ? $address['id_country'] : Configuration::get('PS_COUNTRY_DEFAULT');
        $id_zone = Address::getZoneById($params['cart']->id_address_delivery);
        $id_state = Address::getCountryAndState($params['cart']->id_address_delivery)['id_state'];
        $cartvalue = (float) $params['cart']->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $carriervalue = (float) $params['cart']->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $cat_array = $this->getCategoriesFromCart($params['cart']->id);
        $cust_group = array();
        if (Group::isFeatureActive()) {
            $cust_group = Customer::getGroupsStatic((int) $params['cart']->id_customer);
        }
        $manufacturers = $this->getManufacturersFromCart($params['cart']->id);
        $suppliers = $this->getSuppliersFromCart($params['cart']->id);

        return $this->getCost_common($id_carrier, $id_country, $id_state, $id_zone, $cartvalue, $carriervalue, $cat_array, $cust_group, $manufacturers, $suppliers, $id_shop);
    }

    public function getCostFromCart($cart)
    {
        $id_shop = $cart->id_shop;
        $id_carrier = $cart->id_carrier;
        $address = Address::getCountryAndState($cart->id_address_delivery);
        $id_country = $address['id_country'] ? $address['id_country'] : Configuration::get('PS_COUNTRY_DEFAULT');
        $id_state = Address::getCountryAndState($cart->id_address_delivery)['id_state'];
        $id_zone = Address::getZoneById($cart->id_address_delivery);
        $cartvalue = (float) $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $carriervalue = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $cat_array = $this->getCategoriesFromCart($cart->id);
        $cust_group = array();
        if (Group::isFeatureActive()) {
            $cust_group = Customer::getGroupsStatic((int) $cart->id_customer);
        }
        $manufacturers = $this->getManufacturersFromCart($cart->id);
        $suppliers = $this->getSuppliersFromCart($cart->id);

        return $this->getCost_common($id_carrier, $id_country, $id_state, $id_zone, $cartvalue, $carriervalue, $cat_array, $cust_group, $manufacturers, $suppliers, $id_shop);
    }

    public function getCost_common($id_carrier, $id_country, $id_state, $id_zone, $cartvalue, $carriervalue, $categories_array, $cust_group, $manufacturers_array, $suppliers_array, $id_shop)
    {
        $fee_arr = array();
        $ret = 0;
        $global_integration_way = Configuration::get('SG_CODWFEEPLUS_INTEGRATION_WAY');
        $global_orderstate = $this->checkOrderState(Configuration::get('SG_CODWFEEPLUS_ORDERSTATE'));
        $where_shop = '';
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $where_shop = ' WHERE `codwfeeplus_shop`=' . Shop::getContextShopID();
            }
        }
        $this->_testoutput_method_active = true;
        $conds_db = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'codwfeeplus_conditions`' . $where_shop . ' ORDER BY `codwfeeplus_position`');
        $curr = Currency::getDefaultCurrency();
        $c = $curr->suffix;
        unset($curr);
        $this->_testoutput = '<div class="codwfeeplus_testoutput">';
        $this->_testoutput .= '<span style="display: none;">Output from COD with fee PLUS module for Prestasop&COPY; by Sakis Gkiokas, version: ' . $this->version . '</span>';
        $this->_testoutput .= '<div class="codwfeeplus_parameters">'
                . '<p>Integration way is <span class="codwfeeplus_bold_txt">' . $this->_integration_general_arr[$global_integration_way] . '</span>.</p>'
                . '<p>Default order status is <span class="codwfeeplus_bold_txt">' . $this->_getOrderstateText($global_orderstate) . '</span>.</p>'
                . '<p>Started checking with these parameters:</p>'
                . '<ul>'
                . '<li>Shop: <span class="codwfeeplus_bold_txt">' . $this->getShopName($id_shop) . '</span></li>'
                . '<li>Cart Value: <span class="codwfeeplus_bold_price">' . Tools::displayPrice($cartvalue) . '</span></li>'
                . '<li>Carrier\'s fee Value: <span class="codwfeeplus_bold_price">' . Tools::displayPrice($carriervalue) . '</span></li>'
                . '<li>Carrier: <span class="codwfeeplus_bold_txt">' . $this->getCarrierName($id_carrier) . '</span></li>'
                . '<li>Country: <span class="codwfeeplus_bold_txt">' . $this->getCountryName($id_country) . '</span></li>'
                . '<li>State: <span class="codwfeeplus_bold_txt">' . $this->getStateName($id_state) . '</span></li>'
                . '<li>Zone: <span class="codwfeeplus_bold_txt">' . $this->getZoneName($id_zone) . '</span></li>';

        $this->_testoutput .= '<li>Groups: ';
        $group_names = $this->getGroupNameArray($cust_group);
        if (count($group_names)) {
            $this->_testoutput .= '<ul>';
            foreach ($group_names as $group_name) {
                $this->_testoutput .= '<li><span class="codwfeeplus_bold_txt">' . $group_name . '</span></li>';
            }
            $this->_testoutput .= '</ul>';
        } else {
            if (Group::isFeatureActive()) {
                $this->_testoutput .= '<span class="codwfeeplus_bold_txt">No groups defined.</span>';
            } else {
                $this->_testoutput .= '<span class="codwfeeplus_bold_txt">Group feature is not active on this shop.</span>';
            }
        }
        $this->_testoutput .= '</li>';

        $this->_testoutput .= '<li>Categories: ';
        $cat_names = $this->getCategoriesNameArray($categories_array);
        if (count($cat_names)) {
            $this->_testoutput .= '<ul>';
            foreach ($cat_names as $cat_name) {
                $this->_testoutput .= '<li><span class="codwfeeplus_bold_txt">' . $cat_name . '</span></li>';
            }
            $this->_testoutput .= '</ul>';
        } else {
            $this->_testoutput .= '<span class="codwfeeplus_bold_txt">No categories defined.</span>';
        }
        $this->_testoutput .= '</li>';

        if ($this->is17) {
            $this->_testoutput .= '<li>Brands: ';
        } else {
            $this->_testoutput .= '<li>Manufacturers: ';
        }
        $man_names = $this->getManufacturersNameArray($manufacturers_array);
        if (count($man_names)) {
            $this->_testoutput .= '<ul>';
            foreach ($man_names as $man_name) {
                $this->_testoutput .= '<li><span class="codwfeeplus_bold_txt">' . $man_name . '</span></li>';
            }
            $this->_testoutput .= '</ul>';
        } else {
            if ($this->is17) {
                $this->_testoutput .= '<span class="codwfeeplus_bold_txt">No brands defined.</span>';
            } else {
                $this->_testoutput .= '<span class="codwfeeplus_bold_txt">No manufacturers defined.</span>';
            }
        }
        $this->_testoutput .= '</li>';

        $this->_testoutput .= '<li>Suppliers: ';
        $sup_names = $this->getSuppliersNameArray($suppliers_array);
        if (count($sup_names)) {
            $this->_testoutput .= '<ul>';
            foreach ($sup_names as $sup_name) {
                $this->_testoutput .= '<li><span class="codwfeeplus_bold_txt">' . $sup_name . '</span></li>';
            }
            $this->_testoutput .= '</ul>';
        } else {
            $this->_testoutput .= '<span class="codwfeeplus_bold_txt">No suppliers defined.</span>';
        }
        $this->_testoutput .= '</li>';

        $this->_testoutput .= '</ul></div>';

        if ($conds_db) {
            foreach ($conds_db as $cond) {
                $c = new CODwFP($cond['id_codwfeeplus_cond']);
                $c->validateConditionValues();
                $cond_type_fee = true;
                $cond_type_txt = 'Type: <span class="codwfeeplus_bold_txt">FEE CALCULATION</span>';
                if ($c->codwfeeplus_condtype != 0) {
                    $cond_type_fee = false;
                    $cond_type_txt = 'Type: <span class="codwfeeplus_bold_txt">PAYMENT METHOD DEACTIVATION</span>';
                }
                $cond_valid = $this->checkCondValid($c, $id_carrier, $id_country, $id_state, $id_zone, $categories_array, $cartvalue, $carriervalue, $cust_group, $manufacturers_array, $suppliers_array, $id_shop);
                $this->_testoutput .= '<div class="codwfeeplus_' . ($cond_valid ? 'cond_passed' : 'cond_failed') . '">'
                        . '<p>Checking condition with id# <span class="codwfeeplus_bold_txt">' . $c->id_codwfeeplus_cond . '</span>. ' . $cond_type_txt . '</p>';
                $this->_testoutput .= $this->_testoutput_check;
                if ($cond_valid) {
                    if ($cond_type_fee) {
                        $this->_testoutput .= '<p>Condition passed validation. Calculating fee...</p>';
                        $fee = $this->getConditionFee($c, $cartvalue, $carriervalue);
                        $this->_testoutput .= $this->_testoutput_applyfee;
                        $this->_testoutput .= '<p>Fee calculated from this condition: <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span></p>';
                        $fee_arr[] = array(
                            'fee' => $fee,
                            'id' => $c->id_codwfeeplus_cond,
                            'integration' => $c->codwfeeplus_integration,
                            'taxrule_id' => $c->codwfeeplus_taxrule_id,
                            'orderstate' => $c->codwfeeplus_orderstate_id,
                        );
                    } else {
                        $this->_testoutput .= '<p class="codwfeeplus_output_alert">Condition passed validation. Payment method will be unavailable...</p>';
                        $this->_testoutput_method_active = false;
                    }
                } else {
                    if ($cond_type_fee) {
                        $this->_testoutput .= '<p>Condition did not pass validation.</p>';
                    } else {
                        $this->_testoutput .= '<p>Condition did not pass validation. Payment method will be available...</p>';
                    }
                }

                $this->_testoutput .= '</div>';
                unset($c);
            }
        } else {
            $this->_testoutput .= '<div class="codwfeeplus_cond_warning">'
                    . 'There are no conditions defined'
                    . '</div>';
        }

        if ($this->_testoutput_method_active) {
            $this->_testoutput .= '<div class="codwfeeplus_fee_calc"><p>Calculating final fee to apply...</p>';
            if (Configuration::get('SG_CODWFEEPLUS_BEHAVIOUR') == 0) { //First fee in the list
                $this->_testoutput .= '<div><p>Applying the first condition that succeeded validation.</p>';
                if (count($fee_arr)) {
                    $ret = $fee_arr[0]['fee'];
                    $this->_testoutput .= '<p>Condition with id <span class="codwfeeplus_bold_txt">' . $fee_arr[0]['id'] . '</span> is used with fee <span class="codwfeeplus_bold_price">' . Tools::displayPrice($ret) . '</span>.</p>';
                } else {
                    $this->_testoutput .= '<p>No condition passed validation so fee is <span class="codwfeeplus_bold_price">' . Tools::displayPrice('0') . '</span>.</p>';
                }
                $this->_testoutput .= '</div>';
            } else {
                $this->_testoutput .= '<div><p>Adding all the fees returned from conditions that succeeded validation.</p>';
                if (count($fee_arr)) {
                    $this->_testoutput .= '<ul>';
                    foreach ($fee_arr as $value) {
                        $ret += $value['fee'];
                        $this->_testoutput .= '<li>Added resulting fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($value['fee']) . '</span> from condition with id <span class="codwfeeplus_bold_txt">' . $value['id'] . '</span>.</li>';
                    }
                    $this->_testoutput .= '</ul>';
                    $this->_testoutput .= '<p>Final fee is <span class="codwfeeplus_bold_price">' . Tools::displayPrice($ret) . '</span>.</p>';
                } else {
                    $this->_testoutput .= '<p>No condition passed validation so fee is <span class="codwfeeplus_bold_price">' . Tools::displayPrice('0') . '</span>.</p>';
                }
                $this->_testoutput .= '</div>';
            }

            $this->_testoutput .= '<div class="codwfeeplus_integration_calc">';
            if ($ret == 0) {
                $this->_cond_integration = 0;
                $this->_testoutput .= '<p>Calculated fee value is <span class="codwfeeplus_bold_price">' . Tools::displayPrice('0') . '</span> so integration way is not applied.</p>';
            } else {
                if ($global_integration_way != 0) { //not defined by condition
                    $this->_cond_integration = $global_integration_way - 1;
                    $this->_testoutput .= '<p>Integration way is defined globally as <span class="codwfeeplus_bold_txt">' . $this->_integration_general_arr[$global_integration_way] . '</span>.</p>';
                } else {
                    if (count($fee_arr)) {
                        $this->_cond_integration = $fee_arr[0]['integration'];
                        $this->_testoutput .= '<p>Integration way is defined from the first successful condition (<span class="codwfeeplus_bold_txt">' . $this->_integration_condition_arr[$fee_arr[0]['integration']] . '</span> from condition with id <span class="codwfeeplus_bold_txt">' . $fee_arr[0]['id'] . '</span>).</p>';
                    } else {
                        $this->_cond_integration = 0;
                        $this->_testoutput .= '<p>No conditions passed validation so integration way is not applied.</p>';
                    }
                }
            }
            $this->_testoutput .= '</div>';

            $this->_testoutput .= '<div class="codwfeeplus_orderstate_calc">';

            if (count($fee_arr)) {
                if ($fee_arr[0]['orderstate'] == 0) {
                    $this->_cond_orderstate = $global_orderstate;
                    $this->_testoutput .= '<p>Order status is defined from the first successful condition (with id <span class="codwfeeplus_bold_txt">' . $fee_arr[0]['id'] . '</span>) which is set to default value (<span class="codwfeeplus_bold_txt">' . $this->_getOrderstateText($this->_cond_orderstate) . '</span>).</p>';
                } else {
                    $this->_cond_orderstate = $fee_arr[0]['orderstate'];
                    $this->_testoutput .= '<p>Order status is defined from the first successful condition (<span class="codwfeeplus_bold_txt">' . $this->_getOrderstateText($fee_arr[0]['orderstate']) . '</span> from condition with id <span class="codwfeeplus_bold_txt">' . $fee_arr[0]['id'] . '</span>).</p>';
                }
            } else {
                $this->_cond_orderstate = $global_orderstate;
                $this->_testoutput .= '<p>No conditions passed validation so order status is the default (<span class="codwfeeplus_bold_txt">' . $this->_getOrderstateText($this->_cond_orderstate) . '</span>).</p>';
            }

            $this->_testoutput .= '</div>';

            if ($this->_cond_integration == 1) {
                $this->_testoutput .= '<div class="codwfeeplus_taxrule_calc">';
                $this->_cond_taxrule = $fee_arr[0]['taxrule_id'];
                $rule_name = $this->getTaxRuleNameFromID($this->_cond_taxrule);
                $this->_testoutput .= '<p>Tax for COD product is defined from the first successful condition (<span class="codwfeeplus_bold_txt">' . $rule_name . '</span> from condition with id <span class="codwfeeplus_bold_txt">' . $fee_arr[0]['id'] . '</span>).</p>';
                $this->_testoutput .= '</div>';
            }

            $this->_testoutput .= '</div>';
            $this->_testoutput .= '</div>';
        } else {
            $this->_testoutput .= '<div class="codwfeeplus_fee_calc">';
            $this->_testoutput .= '<p class="codwfeeplus_output_alert">Payment method will be unavailable...</p>';
            $this->_testoutput .= '</div>';
        }

        return Tools::ps_round((float) $ret, _PS_PRICE_COMPUTE_PRECISION_);
    }

    /**
     * Gets the final tax once a carrier and address exists
     *
     * @param int $carrier_id
     * @param int $address_id
     * @return float
     */
    public function getCODFeeTax($carrier_id, $address_id)
    {
        $car = new Carrier($carrier_id);
        $address = Address::initialize((int) $address_id);
        $carrier_tax = ((float) $car->getTaxesRate($address)) * 0.01;
        $product_tax = 0;
        $CODfee_tax = 0;
        if ($this->_cond_taxrule != 0) {
            $p = new Product();
            $p->id_tax_rules_group = $this->_cond_taxrule;
            $product_tax = ((float) $p->getTaxesRate($address)) * 0.01;
            unset($p);
        }

        if ($this->_cond_integration == 0) {
            $CODfee_tax = $carrier_tax;
        } else {
            $CODfee_tax = $product_tax;
        }

        return $CODfee_tax;
    }

    private function checkCondValid(CODwFP $c, $id_carrier, $id_country, $id_state, $id_zone, $categories_array, $cartvalue, $carriervalue, $cust_group, $manufacturers_array, $suppliers_array, $id_shop)
    {
        $this->_testoutput_check = '<div class="codwfeeplus_cond_check_steps"><ul>';
        $apply_cond = false;
        if ($c->codwfeeplus_active) {
            $apply_cond = true;

            if ($c->codwfeeplus_countries !== '') {
                $v = $this->countriesArrToText($c->codwfeeplus_countries);
                $t = $this->checkListID($c->codwfeeplus_countries, $id_country);
                if ($t) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted country matched condition\'s countries (' . $v . ').</li>';
                } else {
                    $this->_testoutput_check .= '<li  class="codwfeeplus_cond_error">Submitted country didn\'t match condition\'s countries (' . $v . ').</li>';
                }
                $apply_cond &= $t;
            } else {
                $this->_testoutput_check .= '<li  class="codwfeeplus_cond_neutral">Condition doesn\'t have any countries defined.</li>';
            }

            if ($c->codwfeeplus_states !== '') {
                $v = $this->statesArrToText($c->codwfeeplus_states);
                $t = $this->checkListID($c->codwfeeplus_states, $id_state);
                if ($t) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted state matched condition\'s states (' . $v . ').</li>';
                } else {
                    $this->_testoutput_check .= '<li  class="codwfeeplus_cond_error">Submitted state didn\'t match condition\'s states (' . $v . ').</li>';
                }
                $apply_cond &= $t;
            } else {
                $this->_testoutput_check .= '<li  class="codwfeeplus_cond_neutral">Condition doesn\'t have any states defined.</li>';
            }

            if ($c->codwfeeplus_carriers !== '') {
                $v = $this->carriersArrToText($c->codwfeeplus_carriers);
                $t = $this->checkListID($c->codwfeeplus_carriers, $id_carrier);
                if ($t) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted carrier matched condition\'s carriers (' . $v . ').</li>';
                } else {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_error">Submitted carrier didn\'t match condition\'s carriers (' . $v . ').</li>';
                }
                $apply_cond &= $t;
            } else {
                $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Condition doesn\'t have any carriers defined.</li>';
            }

            if ($c->codwfeeplus_zones !== '') {
                $v = $this->zonesArrToText($c->codwfeeplus_zones);
                $t = $this->checkListID($c->codwfeeplus_zones, $id_zone);
                if ($t) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted zone matched condition\'s zones (' . $v . ').</li>';
                } else {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_error">Submitted zone didn\'t match condition\'s zones (' . $v . ').</li>';
                }
                $apply_cond &= $t;
            } else {
                $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Condition doesn\'t have any zones defined.</li>';
            }

            if (Group::isFeatureActive()) {
                if ($c->codwfeeplus_groups !== '') {
                    $v = $this->groupsArrToText($c->codwfeeplus_groups);
                    $t = $this->checkMultipleValuesListID($c->codwfeeplus_groups, $cust_group, $c->codwfeeplus_matchall_groups);
                    $multitext = $c->codwfeeplus_matchall_groups ? '(All submitted groups should match)' : '(Any submitted group should match)';
                    if ($t) {
                        $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted customer groups matched condition\'s groups ' . $multitext . ' -> ' . $v . '.</li>';
                    } else {
                        $this->_testoutput_check .= '<li class="codwfeeplus_cond_error">Submitted customer groups didn\'t match condition\'s groups ' . $multitext . ' -> ' . $v . '.</li>';
                    }
                    $apply_cond &= $t;
                } else {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Condition doesn\'t have any groups defined.</li>';
                }
            } else {
                $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Group feature is not active on this shop.</li>';
            }

            if ($c->codwfeeplus_categories !== '') {
                $v = $this->categoriesArrToText($c->codwfeeplus_categories);
                $t = $this->checkMultipleValuesListID($c->codwfeeplus_categories, $categories_array, $c->codwfeeplus_matchall_categories);
                $multitext = $c->codwfeeplus_matchall_categories ? '(All submitted categories should match)' : '(Any submitted category should match)';
                if ($t) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted categories matched condition\'s categories ' . $multitext . ' -> ' . $v . '.</li>';
                } else {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_error">Submitted categories didn\'t match condition\'s categories ' . $multitext . ' -> ' . $v . '.</li>';
                }
                $apply_cond &= $t;
            } else {
                $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Condition doesn\'t have any categories defined.</li>';
            }

            if ($c->codwfeeplus_manufacturers !== '') {
                $v = $this->manufacturersArrToText($c->codwfeeplus_manufacturers);
                $t = $this->checkMultipleValuesListID($c->codwfeeplus_manufacturers, $manufacturers_array, $c->codwfeeplus_matchall_manufacturers);
                if ($this->is17) {
                    $multitext = $c->codwfeeplus_matchall_manufacturers ? '(All submitted brands should match)' : '(Any submitted brand should match)';
                } else {
                    $multitext = $c->codwfeeplus_matchall_manufacturers ? '(All submitted manufacturers should match)' : '(Any submitted manufacturer should match)';
                }
                if ($t) {
                    if ($this->is17) {
                        $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted brands matched condition\'s groups ' . $multitext . ' -> ' . $v . '.</li>';
                    } else {
                        $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted manufacturers matched condition\'s groups ' . $multitext . ' -> ' . $v . '.</li>';
                    }
                } else {
                    if ($this->is17) {
                        $this->_testoutput_check .= '<li class="codwfeeplus_cond_error">Submitted brands didn\'t match condition\'s groups ' . $multitext . ' -> ' . $v . '.</li>';
                    } else {
                        $this->_testoutput_check .= '<li class="codwfeeplus_cond_error">Submitted manufacturers didn\'t match condition\'s groups ' . $multitext . ' -> ' . $v . '.</li>';
                    }
                }
                $apply_cond &= $t;
            } else {
                if ($this->is17) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Condition doesn\'t have any brands defined.</li>';
                } else {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Condition doesn\'t have any manufacturers defined.</li>';
                }
            }

            if ($c->codwfeeplus_suppliers !== '') {
                $v = $this->suppliersArrToText($c->codwfeeplus_suppliers);
                $t = $this->checkMultipleValuesListID($c->codwfeeplus_suppliers, $suppliers_array, $c->codwfeeplus_matchall_suppliers);
                $multitext = $c->codwfeeplus_matchall_suppliers ? '(All submitted suppliers should match)' : '(Any submitted supplier should match)';
                if ($t) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted suppliers matched condition\'s groups ' . $multitext . ' -> ' . $v . '.</li>';
                } else {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_error">Submitted suppliers didn\'t match condition\'s groups ' . $multitext . ' -> ' . $v . '.</li>';
                }
                $apply_cond &= $t;
            } else {
                $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Condition doesn\'t have any suppliers defined.</li>';
            }

            if (((float) $c->codwfeeplus_cartvalue) != 0) {
                $t = true;
                $sign = '';
                $addCarrier = true;
                if (!$c->codwfeeplus_cartvalue_include_carrier) {
                    $addCarrier = false;
                }
                $tot = $cartvalue;
                if ($addCarrier) {
                    $tot += $carriervalue;
                }
		$cur = $this->context->currency;
                $def_cur = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
                $cond_value = (float) $c->codwfeeplus_cartvalue;
                $cond_value_txt = '<span class="codwfeeplus_bold_price">' . Tools::displayPrice($cond_value) . '</span>';
                if ($cur->id != Configuration::get('PS_CURRENCY_DEFAULT')) {
                    $old_cond_value = $cond_value;
                    $cond_value = Tools::convertPriceFull($cond_value, null, $cur);
                    $cond_value_txt = '(<span class="codwfeeplus_bold_price">' . Tools::displayPrice($old_cond_value, $def_cur) . '</span> =>)<span class="codwfeeplus_bold_price">' . Tools::displayPrice($cond_value) . '</span>';
                }
                if ($c->codwfeeplus_cartvalue_sign == 0) {  // >=
                    $sign = 'greater or equal';
                    $t = $this->firstGreaterorEqualtoSecond($tot, (float) $cond_value);
                } else {
                    $sign = 'less or equal';
                    $t = $this->firstLesserorEqualtoSecond($tot, (float) $cond_value);
                }
                $txt = 'Submitted cart value ';
                if ($addCarrier) {
                    $txt = 'Submitted cart value (including carrier\'s fee) ';
                }
                
                if ($t) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">' . $txt . 'matched condition\'s cart value (<span class="codwfeeplus_bold_txt">' . $sign . '</span> than ' . $cond_value_txt . ').</li>';
                } else {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_error">' . $txt . 'didn\'t match condition\'s cart value (<span class="codwfeeplus_bold_txt">' . $sign . '</span> than ' . $cond_value_txt . ').</li>';
                }
                $apply_cond &= $t;
            } else {
                $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Condition doesn\'t have any cart value rule defined.</li>';
            }
        } else {
            $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">The condition is not active.</li>';
        }
        $this->_testoutput_check .= '</ul></div>';

        return $apply_cond;
    }

    private function getConditionFee(CODwFP $c, $cartvalue, $carriervalue)
    {
        /* @var $cur Currency */
        $cur = $this->context->currency;
        $def_cur = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        $this->_testoutput_applyfee = '<div class="codwfeeplus_fee_calc_steps"><ul>';
        $fee = 0;
        $addCarrier = true;
        if (!$c->codwfeeplus_fee_percent_include_carrier) {
            $addCarrier = false;
        }
        switch ($c->codwfeeplus_fee_type) {
            case 0: //no fee
                $fee = 0;
                $this->_testoutput_applyfee .= '<li>This condition doesn\'t have any fee</li>';
                break;
            case 1: //fix
                $fee = (float) $c->codwfeeplus_fee;
                if ($cur->id != Configuration::get('PS_CURRENCY_DEFAULT')) {
                    $oldfee = $fee;
                    $fee = Tools::convertPriceFull($fee, null, $cur);
                    $this->_testoutput_applyfee .= '<li>Converting fixed fee currency: From <span class="codwfeeplus_bold_price">' . Tools::displayPrice($oldfee, $def_cur) . '</span> to <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span></li>';
                }
                $this->_testoutput_applyfee .= '<li>Fixed fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> applied</li>';
                break;
            case 2: //percentage
                $percent = (float) $c->codwfeeplus_fee_percent;
                $percent = $percent / 100;
                if ($addCarrier) {
                    $fee = ($cartvalue + $carriervalue ) * $percent;
                    $this->_testoutput_applyfee .= '<li>Percentage fee (including Carrier\'s fee): (<span class="codwfeeplus_bold_price">' . Tools::displayPrice($cartvalue) . '</span> + <span class="codwfeeplus_bold_price">' . Tools::displayPrice($carriervalue) . '</span>) * <span class="codwfeeplus_bold_price">' . ($percent * 100) . '%</span> = <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> calculated</li>';
                } else {
                    $fee = $cartvalue * $percent;
                    $this->_testoutput_applyfee .= '<li>Percentage fee: <span class="codwfeeplus_bold_price">' . Tools::displayPrice($cartvalue) . '</span> * <span class="codwfeeplus_bold_price">' . ($percent * 100) . '%</span> = <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> calculated</li>';
                }

                $minimalfee = (float) $c->codwfeeplus_fee_min;
                $maximalfee = (float) $c->codwfeeplus_fee_max;
                $minexists = false;
                $maxexists = false;
                $minapplied = false;
                $maxapplied = false;
                if ($minimalfee != 0) {
                    if ($cur->id != Configuration::get('PS_CURRENCY_DEFAULT')) {
                        $old_minfee = $minimalfee;
                        $minimalfee = Tools::convertPriceFull($minimalfee, null, $cur);
                        $this->_testoutput_applyfee .= '<li>Converting minimal fee currency: From <span class="codwfeeplus_bold_price">' . Tools::displayPrice($old_minfee, $def_cur) . '</span> to <span class="codwfeeplus_bold_price">' . Tools::displayPrice($minimalfee) . '</span></li>';
                    }
                    $this->_testoutput_applyfee .= '<li>There is a minimum fee condition of value <span class="codwfeeplus_bold_price">' . Tools::displayPrice($minimalfee) . '</span></li>';
                    $minexists = true;
                } else {
                    $this->_testoutput_applyfee .= '<li>There is no minimum fee condition defined.</li>';
                }
                if ($maximalfee != 0) {
                    if ($cur->id != Configuration::get('PS_CURRENCY_DEFAULT')) {
                        $old_maxfee = $maximalfee;
                        $maximalfee = Tools::convertPriceFull($maximalfee, null, $cur);
                        $this->_testoutput_applyfee .= '<li>Converting maximum fee currency: From <span class="codwfeeplus_bold_price">' . Tools::displayPrice($old_maxfee, $def_cur) . '</span> to <span class="codwfeeplus_bold_price">' . Tools::displayPrice($maximalfee) . '</span></li>';
                    }
                    $this->_testoutput_applyfee .= '<li>There is a maximum fee condition of value <span class="codwfeeplus_bold_price">' . Tools::displayPrice($maximalfee) . '</span></li>';
                    $maxexists = true;
                } else {
                    $this->_testoutput_applyfee .= '<li>There is no maximum fee condition defined.</li>';
                }

                if (($fee < $minimalfee) & ($minimalfee != 0)) {
                    $this->_testoutput_applyfee .= '<li>Calculated fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> is less than minimum fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($minimalfee) . '</span>, so final fee is <span class="codwfeeplus_bold_price">' . Tools::displayPrice($minimalfee) . '</span>.</li>';
                    $fee = $minimalfee;
                    $minapplied = true;
                } elseif (($fee > $maximalfee) & ($maximalfee != 0)) {
                    $this->_testoutput_applyfee .= '<li>Calculated fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> is greater than maximum fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($maximalfee) . '</span>, so final fee is <span class="codwfeeplus_bold_price">' . Tools::displayPrice($maximalfee) . '</span>.</li>';
                    $fee = $maximalfee;
                    $maxapplied = true;
                }

                if ($minexists && !$minapplied) {
                    $this->_testoutput_applyfee .= '<li>Calculated fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> is greater than minimum fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($minimalfee) . '</span>, so it stays the same.</li>';
                }

                if ($maxexists && !$maxapplied) {
                    $this->_testoutput_applyfee .= '<li>Calculated fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> is less than maximum fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($maximalfee) . '</span>, so it stays the same.</li>';
                }

                break;
            case 3: //fix+percentage
                $percent = (float) $c->codwfeeplus_fee_percent;
                $percent = $percent / 100;
                $fixed = (float) $c->codwfeeplus_fee;
                if ($cur->id != Configuration::get('PS_CURRENCY_DEFAULT')) {
                    $oldfee = $fixed;
                    $fixed = Tools::convertPriceFull($fixed, null, $cur);
                    $this->_testoutput_applyfee .= '<li>Converting fixed fee currency: From <span class="codwfeeplus_bold_price">' . Tools::displayPrice($oldfee, $def_cur) . '</span> to <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fixed) . '</span></li>';
                }
                if ($addCarrier) {
                    $fee = ($cartvalue + $carriervalue ) * $percent + $fixed;
                    $this->_testoutput_applyfee .= '<li>Percentage (including Carrier\'s fee) and fixed fee: (<span class="codwfeeplus_bold_price">' . Tools::displayPrice($cartvalue) . '</span> + <span class="codwfeeplus_bold_price">' . Tools::displayPrice($carriervalue) . '</span>) * <span class="codwfeeplus_bold_price">' . ($percent * 100) . '%</span> + <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fixed) . '</span> = <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> calculated</li>';
                } else {
                    $fee = $cartvalue * $percent + $fixed;
                    $this->_testoutput_applyfee .= '<li>Percentage and fixed fee: <span class="codwfeeplus_bold_price">' . Tools::displayPrice($cartvalue) . '</span> * <span class="codwfeeplus_bold_price">' . ($percent * 100) . '%</span> + <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fixed) . '</span> = <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> calculated</li>';
                }


                $minimalfee = (float) $c->codwfeeplus_fee_min;
                $maximalfee = (float) $c->codwfeeplus_fee_max;
                $minexists = false;
                $maxexists = false;
                $minapplied = false;
                $maxapplied = false;
                if ($minimalfee != 0) {
                    if ($cur->id != Configuration::get('PS_CURRENCY_DEFAULT')) {
                        $old_minfee = $minimalfee;
                        $minimalfee = Tools::convertPriceFull($minimalfee, null, $cur);
                        $this->_testoutput_applyfee .= '<li>Converting minimal fee currency: From <span class="codwfeeplus_bold_price">' . Tools::displayPrice($old_minfee, $def_cur) . '</span> to <span class="codwfeeplus_bold_price">' . Tools::displayPrice($minimalfee) . '</span></li>';
                    }
                    $this->_testoutput_applyfee .= '<li>There is a minimum fee condition of value <span class="codwfeeplus_bold_price">' . Tools::displayPrice($minimalfee) . '</span></li>';
                    $minexists = true;
                } else {
                    $this->_testoutput_applyfee .= '<li>There is no minimum fee condition defined.</li>';
                }
                if ($maximalfee != 0) {
                    if ($cur->id != Configuration::get('PS_CURRENCY_DEFAULT')) {
                        $old_maxfee = $maximalfee;
                        $maximalfee = Tools::convertPriceFull($maximalfee, null, $cur);
                        $this->_testoutput_applyfee .= '<li>Converting maximum fee currency: From <span class="codwfeeplus_bold_price">' . Tools::displayPrice($old_maxfee, $def_cur) . '</span> to <span class="codwfeeplus_bold_price">' . Tools::displayPrice($maximalfee) . '</span></li>';
                    }
                    $this->_testoutput_applyfee .= '<li>There is a maximum fee condition of value <span class="codwfeeplus_bold_price">' . Tools::displayPrice($maximalfee) . '</span></li>';
                    $maxexists = true;
                } else {
                    $this->_testoutput_applyfee .= '<li>There is no maximum fee condition defined.</li>';
                }

                if (($fee < $minimalfee) & ($minimalfee != 0)) {
                    $this->_testoutput_applyfee .= '<li>Calculated fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> is less than minimum fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($minimalfee) . '</span>, so final fee is <span class="codwfeeplus_bold_price">' . Tools::displayPrice($minimalfee) . '</span>.</li>';
                    $fee = $minimalfee;
                    $minapplied = true;
                } elseif (($fee > $maximalfee) & ($maximalfee != 0)) {
                    $this->_testoutput_applyfee .= '<li>Calculated fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> is greater than maximum fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($maximalfee) . '</span>, so final fee is <span class="codwfeeplus_bold_price">' . Tools::displayPrice($maximalfee) . '</span>.</li>';
                    $fee = $maximalfee;
                    $maxapplied = true;
                }

                if ($minexists && !$minapplied) {
                    $this->_testoutput_applyfee .= '<li>Calculated fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> is greater than minimum fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($minimalfee) . '</span>, so it stays the same.</li>';
                }

                if ($maxexists && !$maxapplied) {
                    $this->_testoutput_applyfee .= '<li>Calculated fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> is less than maximum fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($maximalfee) . '</span>, so it stays the same.</li>';
                }
                break;
            default:
                break;
        }

        $this->_testoutput_applyfee .= '</ul>';
        $this->_testoutput_applyfee .= '<p>Contition\'s integration way is <span class="codwfeeplus_bold_txt">' . $this->_integration_condition_arr[$c->codwfeeplus_integration] . '</span>.</p>';

        $rule_name = $this->getTaxRuleNameFromID($c->codwfeeplus_taxrule_id);
        $this->_testoutput_applyfee .= '<p>Contition\'s tax for COD product is <span class="codwfeeplus_bold_txt">' . $rule_name . '</span>.</p>';
        $this->_testoutput_applyfee .= '<p>Contition\'s order status is <span class="codwfeeplus_bold_txt">' . $this->_getOrderstateText($c->codwfeeplus_orderstate_id) . '</span>.</p>';

        $this->_testoutput_applyfee .= '</div>';

        return $fee;
    }

    private function countriesArrToText($inliststr)
    {
        $ret = '';
        $i = 0;
        $inArr = explode('|', $inliststr);
        foreach ($inArr as $value) {
            $c = new Country($value);
            $ret .= ($i > 0 ? ' ,' : '') . $c->name[(int) Configuration::get('PS_LANG_DEFAULT')];
            unset($c);
            ++$i;
        }

        return $ret;
    }

    private function statesArrToText($inliststr)
    {
        $ret = '';
        $i = 0;
        $inArr = explode('|', $inliststr);
        foreach ($inArr as $value) {
            $c = new State($value);
            $ret .= ($i > 0 ? ' ,' : '') . $c->name;
            unset($c);
            ++$i;
        }

        return $ret;
    }

    private function carriersArrToText($inliststr)
    {
        $ret = '';
        $i = 0;
        $inArr = explode('|', $inliststr);
        foreach ($inArr as $value) {
            $c = new Carrier($value);
            $ret .= ($i > 0 ? ' ,' : '') . $c->name;
            unset($c);
            ++$i;
        }

        return $ret;
    }

    private function zonesArrToText($inliststr)
    {
        $ret = '';
        $i = 0;
        $inArr = explode('|', $inliststr);
        foreach ($inArr as $value) {
            $c = new Zone($value);
            $ret .= ($i > 0 ? ' ,' : '') . $c->name;
            unset($c);
            ++$i;
        }

        return $ret;
    }

    private function manufacturersArrToText($inliststr)
    {
        $manuf_empty_label = $this->l('Empty manufacturer');
        if ($this->is17) {
            $manuf_empty_label = $this->l('Empty brand');
        }
        $ret = '';
        $i = 0;
        $inArr = explode('|', $inliststr);
        foreach ($inArr as $value) {
            if ($value == '0') {
                $ret .= ($i > 0 ? ' ,' : '') . $manuf_empty_label;
            } else {
                $c = new Manufacturer($value);
                $ret .= ($i > 0 ? ' ,' : '') . $c->name;
                unset($c);
            }
            ++$i;
        }

        return $ret;
    }

    private function suppliersArrToText($inliststr)
    {
        $ret = '';
        $i = 0;
        $inArr = explode('|', $inliststr);
        foreach ($inArr as $value) {
            if ($value == '0') {
                $ret .= ($i > 0 ? ' ,' : '') . $this->l('Empty supplier');
            } else {
                $c = new Supplier($value);
                $ret .= ($i > 0 ? ' ,' : '') . $c->name;
                unset($c);
            }
            ++$i;
        }

        return $ret;
    }

    private function shopsArrToText($inliststr)
    {
        $ret = '';
        $i = 0;
        $inArr = explode('|', $inliststr);
        foreach ($inArr as $value) {
            $c = new Shop($value);
            $ret .= ($i > 0 ? ' ,' : '') . $c->name;
            unset($c);
            ++$i;
        }

        return $ret;
    }

    private function groupsArrToText($inliststr)
    {
        $ret = '';
        $i = 0;
        $inArr = explode('|', $inliststr);
        foreach ($inArr as $value) {
            $c = new Group($value);
            $ret .= ($i > 0 ? ' ,' : '') . $c->name[(int) Configuration::get('PS_LANG_DEFAULT')];
            unset($c);
            ++$i;
        }

        return $ret;
    }

    private function categoriesArrToText($inliststr)
    {
        $ret = '';
        $i = 0;
        $inArr = explode('|', $inliststr);
        foreach ($inArr as $value) {
            $c = new Category($value);
            $ret .= ($i > 0 ? ' ,' : '') . $c->name[(int) Configuration::get('PS_LANG_DEFAULT')];
            unset($c);
            ++$i;
        }

        return $ret;
    }

    private function firstGreaterorEqualtoSecond($first, $second)
    {
        $ret = false;
        if ($first >= $second) {
            $ret = true;
        }

        return $ret;
    }

    private function firstLesserorEqualtoSecond($first, $second)
    {
        $ret = false;
        if ($first <= $second) {
            $ret = true;
        }

        return $ret;
    }

    private function checkMultipleValuesListID($inliststr, $inid_arr, $match_all)
    {
        $res_cond = false;
        if (count($inid_arr) > 0) {
            $res_cond = $match_all;
            foreach ($inid_arr as $value) {
                if ($match_all) {
                    $res_cond &= $this->checkListID($inliststr, $value);
                } else {
                    $res_cond |= $this->checkListID($inliststr, $value);
                }
            }
        }

        return $res_cond;
    }

    private function checkListID($inliststr, $inid)
    {
        $res = true;
        $arr = explode('|', $inliststr);
        $res &= in_array($inid, $arr);

        return $res;
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->is17) {
            return $this->display(__FILE__, 'confirmation.tpl');
        } else {
            $this->smarty->assign(
                    array(
                        'shop_name' => $this->context->shop->name,
            ));
            return $this->fetch('module:codwfeeplus/views/templates/hook/confirmation_17.tpl');
        }
    }

    public function getTranslation($string, $lang_iso, $js = false, $name = null, $source = null)
    {
        $ret = '';
        $_MODULES = null;
        $_MODULE = null;
        if ($name == null) {
            $name = $this->name;
        }
        if ($source == null) {
            $source = $this->name;
        }

        $files_by_priority = array(
            // Translations in theme
            _PS_THEME_DIR_ . 'modules/' . $name . '/translations/' . $lang_iso . '.php',
            _PS_THEME_DIR_ . 'modules/' . $name . '/' . $lang_iso . '.php',
            // PrestaShop 1.5 translations
            _PS_MODULE_DIR_ . $name . '/translations/' . $lang_iso . '.php',
        );
        foreach ($files_by_priority as $file) {
            if (file_exists($file)) {
                include $file;
                $_MODULES = !empty($_MODULES) ? $_MODULES + $_MODULE : $_MODULE; //we use "+" instead of array_merge() because array merge erase existing values.
            }
        }

        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = md5($string);

        if ($_MODULES == null) {
            return str_replace('"', '&quot;', $string);
        }

        $current_key = Tools::strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $source) . '_' . $key;
        $default_key = Tools::strtolower('<{' . $name . '}prestashop>' . $source) . '_' . $key;

        if ('controller' == Tools::substr($source, -10, 10)) {
            $file = Tools::substr($source, 0, -10);
            $current_key_file = Tools::strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $file) . '_' . $key;
            $default_key_file = Tools::strtolower('<{' . $name . '}prestashop>' . $file) . '_' . $key;
        }

        if (isset($current_key_file) && !empty($_MODULES[$current_key_file])) {
            $ret = Tools::stripslashes($_MODULES[$current_key_file]);
        } elseif (isset($default_key_file) && !empty($_MODULES[$default_key_file])) {
            $ret = Tools::stripslashes($_MODULES[$default_key_file]);
        } elseif (!empty($_MODULES[$current_key])) {
            $ret = Tools::stripslashes($_MODULES[$current_key]);
        } elseif (!empty($_MODULES[$default_key])) {
            $ret = Tools::stripslashes($_MODULES[$default_key]);
        } else {
            $ret = Tools::stripslashes($string);
        }

        if ($js) {
            $ret = addslashes($ret);
        } else {
            $ret = htmlspecialchars($ret, ENT_COMPAT, 'UTF-8');
        }

        return $ret;
    }

    public function getTaxRuleNameFromID($taxrule_id)
    {
        if ($taxrule_id == 0) {
            return 'No Tax';
        }
        $taxrules = TaxRulesGroup::getTaxRulesGroups();
        foreach ($taxrules as $value) {
            if ($value['id_tax_rules_group'] == $taxrule_id) {
                return $value['name'];
            }
        }
    }

    public function getUpdateStatus()
    {
        $ret = '';

        $version_arr = explode('.', $this->version);
        $Maj = (int) $version_arr[0];
        $Min = (int) $version_arr[1];
        $Rev = (int) $version_arr[2];

        $P = base64_encode(_PS_BASE_URL_ . __PS_BASE_URI__);
        $base_url = 'http://programs.sakgiok.gr/';
        $url = $base_url . $this->name . '/version.php?Maj=' . $Maj . '&Min=' . $Min . '&Rev=' . $Rev . '&P=' . $P;

        $response = Tools::file_get_contents($url);
        if ($response) {
            $arr = json_decode($response, true);
            if (isset($arr['res'])) {
                if ($arr['res'] == 'update') {
                    $this->_updatestatus['res'] = $arr['res'];
                    $this->_updatestatus['cur_version'] = $arr['cur_version'];
                    $this->_updatestatus['download_link'] = $arr['download_link'];
                    $this->_updatestatus['info_link'] = $arr['info_link'];
                    $this->_updatestatus['github_link'] = $arr['github_link'];
                    $ret = 'update';
                    $this->updateValueAllShops('SG_CODWFEEPLUS_INFO_LINK', $this->_updatestatus['info_link']);
                    $this->updateValueAllShops('SG_CODWFEEPLUS_GITHUB_LINK', $this->_updatestatus['github_link']);
                } elseif ($arr['res'] == 'current') {
                    $this->_updatestatus['res'] = $arr['res'];
                    $this->_updatestatus['cur_version'] = $arr['cur_version'];
                    $this->_updatestatus['download_link'] = $arr['download_link'];
                    $this->_updatestatus['info_link'] = $arr['info_link'];
                    $this->_updatestatus['github_link'] = $arr['github_link'];
                    $this->updateValueAllShops('SG_CODWFEEPLUS_INFO_LINK', $this->_updatestatus['info_link']);
                    $this->updateValueAllShops('SG_CODWFEEPLUS_GITHUB_LINK', $this->_updatestatus['github_link']);
                    $ret = 'current';
                } else {
                    $ret = 'error_res';
                }
            } else {
                $ret = 'error_resp';
            }
        } else {
            $ret = 'error_url';
        }

        return $ret;
    }

    public function updateValueAllShops($key, $value)
    {
        $this->storeContextShop();
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        $res = true;

        if (Shop::isFeatureActive()) {
            $shop_list = Shop::getShops(true, null, true);
            foreach ($shop_list as $shop) {
                Shop::setContext(Shop::CONTEXT_SHOP, $shop);
                $res &= Configuration::updateValue($key, $value);
            }
        } else {
            $res &= Configuration::updateValue($key, $value);
        }
        $this->resetContextShop();
    }

    private function _getOrderstateText($orderstate_id)
    {
        $ret = '';
        if ($orderstate_id == 0) {
            $ret = 'Default value';
        } else {
            $os = OrderState::getOrderStates(Configuration::get('PS_LANG_DEFAULT'));

            foreach ($os as $value) {
                if ($value['id_order_state'] == $orderstate_id) {
                    $ret = $value['id_order_state'] . ' - ' . $value['name'];
                }
            }
        }
        return $ret;
    }

    //validate functions

    public function runCorrect_validateOrder($fee, $id_cart, $id_order_state, $amount_paid, $payment_method = 'Unknown', $message = null, $extra_vars = array(), $currency_special = null, $dont_touch_amount = false, $secure_key = false, Shop $shop = null)
    {
        if ($this->is17) {
            if (Tools::version_compare(_PS_VERSION_, '1.7.0.2', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/17002.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.7.0.6', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/17006.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.7.1.2', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/17102.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.7.2.0', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/17200.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.7.2.2', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/17202.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.7.3.0', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/17300.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.7.5.2', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/17500.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.7.6.0', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/17600.php';
            } else {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/17600.php';
            }
        } else {
            if (Tools::version_compare(_PS_VERSION_, '1.6.0.6', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/16006.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.6.0.9', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/16009.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.6.0.10', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/16010.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.6.0.11', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/16011.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.6.0.14', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/16014.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.6.1.1', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/16101.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.6.1.4', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/16104.php';
            } elseif (Tools::version_compare(_PS_VERSION_, '1.6.1.24', '<=')) {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/16123.php';
            } else {
                include _PS_MODULE_DIR_ . 'codwfeeplus/validate_functions/16123.php';
            }
        }
    }

    public function checkOrderState($in_order_state)
    {
        $ret = $in_order_state;
        $lang_id = (int) Configuration::get('PS_LANG_DEFAULT');
        $os = OrderState::getOrderStates($lang_id);
        if (array_search($in_order_state, array_column($os, 'id_order_state')) === false) {
            $ret = Configuration::get('PS_OS_PREPARATION');
        }
        return $ret;
    }

}
