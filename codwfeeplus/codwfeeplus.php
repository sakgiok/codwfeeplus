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

//use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
include_once _PS_MODULE_DIR_ . 'codwfeeplus/CODwFP.php';

class codwfeeplus extends PaymentModule
{

    private $_html = '';
    private $_postErrors = array();
    public $_testoutput = '';
    public $_testoutput_check = '';
    public $_testoutput_applyfee = '';
    public $_cond_integration = '';
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
        $this->version = '1.0.7';
        $this->author = 'Sakis Gkiokas';
        $this->need_instance = 1;
        $this->controllers = array('validation');
        $this->is_eu_compatible = 1;
        $this->secure_key = Tools::encrypt($this->name);
        $this->currencies = true;
//        $this->currencies_mode = 'radio';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Cash on delivery with fee (COD) PLUS');
        $this->description = $this->l('Accept cash on delivery payments with extra fee and more options');
        $this->ps_versions_compliancy = array('min' => '1.6.0.4', 'max' => '1.7.99.99');
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
                $p = new Product((int) $cod_product_id);
                $p->delete();
                unset($p);
                $ret &= Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_ID', 0);
            }
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        $cod_product_id = Configuration::get('SG_CODWFEEPLUS_PRODUCT_ID');
        $p = new Product((int) $cod_product_id);
        $p->delete();
        unset($p);
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
			  `codwfeeplus_active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_fee_percent_include_carrier` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_cartvalue_include_carrier` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                          `codwfeeplus_position` int(10) unsigned NOT NULL,
                          `codwfeeplus_countries` TEXT,
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
        $this->context->controller->addCSS($this->_path . 'views/css/style-front.css');
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
        $integration = Configuration::get('SG_CODWFEEPLUS_INTEGRATION_WAY');
        $cond_integration = $this->_cond_integration;
        $integration_product = false;
        if ($integration == 0) {
            //By condition
            if ($cond_integration == 1 && $fee != 0) {
                $integration_product = true;
            }
        } elseif ($integration == 2 && $CODfee != 0) {
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

        $this->context->smarty->assign(array(
            'fee' => number_format($fee, 2, '.', ''),
        ));

        $payment_options = [$this->getPaymentOptionValue()];
        return $payment_options;
    }

    public function getPaymentOptionValue()
    {
        $pOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;
        $pOption->setCallToActionText($this->l('Pay with Cash on Delivery'))
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
        $integration = Configuration::get('SG_CODWFEEPLUS_INTEGRATION_WAY');
        $cond_integration = $this->_cond_integration;
        $integration_product = false;
        if ($integration == 0) {
            //By condition
            if ($cond_integration == 1 && $fee != 0) {
                $integration_product = true;
            }
        } elseif ($integration == 2 && $CODfee != 0) {
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
        $ret = array();
        foreach ($manufacturers_array as $value) {
            if ($value == '0') {
                $ret[] = $this->l('Empty manufacturer');
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
        $cartvalue = (float) $params['cart']->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $carriervalue = (float) $params['cart']->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $cat_array = $this->getCategoriesFromCart($params['cart']->id);
        $cust_group = array();
        if (Group::isFeatureActive()) {
            $cust_group = Customer::getGroupsStatic((int) $params['cart']->id_customer);
        }
        $manufacturers = $this->getManufacturersFromCart($params['cart']->id);
        $suppliers = $this->getSuppliersFromCart($params['cart']->id);

        return $this->getCost_common($id_carrier, $id_country, $id_zone, $cartvalue, $carriervalue, $cat_array, $cust_group, $manufacturers, $suppliers, $id_shop);
    }

    public function getCostFromCart($cart)
    {
        $id_shop = $cart->id_shop;
        $id_carrier = $cart->id_carrier;
        $address = Address::getCountryAndState($cart->id_address_delivery);
        $id_country = $address['id_country'] ? $address['id_country'] : Configuration::get('PS_COUNTRY_DEFAULT');
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

        return $this->getCost_common($id_carrier, $id_country, $id_zone, $cartvalue, $carriervalue, $cat_array, $cust_group, $manufacturers, $suppliers, $id_shop);
    }

    public function getCost_common($id_carrier, $id_country, $id_zone, $cartvalue, $carriervalue, $categories_array, $cust_group, $manufacturers_array, $suppliers_array, $id_shop)
    {
        $fee_arr = array();
        $ret = 0;
        $global_integration_way = Configuration::get('SG_CODWFEEPLUS_INTEGRATION_WAY');
        $where_shop = '';
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $where_shop = ' WHERE `codwfeeplus_shop`=' . Shop::getContextShopID();
            }
        }
        $conds_db = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'codwfeeplus_conditions`' . $where_shop . ' ORDER BY `codwfeeplus_position`');
        $curr = Currency::getDefaultCurrency();
        $c = $curr->suffix;
        unset($curr);
        $this->_testoutput = '<div class="codwfeeplus_testoutput">';
        $this->_testoutput .= '<div class="codwfeeplus_parameters">'
                . '<p>Integration way is <span class="codwfeeplus_bold_txt">' . $this->_integration_general_arr[$global_integration_way] . '</span>.</p>'
                . '<p>Started checking with these parameters:</p>'
                . '<ul>'
                . '<li>Shop: <span class="codwfeeplus_bold_txt">' . $this->getShopName($id_shop) . '</span></li>'
                . '<li>Cart Value: <span class="codwfeeplus_bold_price">' . Tools::displayPrice($cartvalue) . '</span></li>'
                . '<li>Carrier\'s fee Value: <span class="codwfeeplus_bold_price">' . Tools::displayPrice($carriervalue) . '</span></li>'
                . '<li>Carrier: <span class="codwfeeplus_bold_txt">' . $this->getCarrierName($id_carrier) . '</span></li>'
                . '<li>Country: <span class="codwfeeplus_bold_txt">' . $this->getCountryName($id_country) . '</span></li>'
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

        $this->_testoutput .= '<li>Manufacturers: ';
        $man_names = $this->getManufacturersNameArray($manufacturers_array);
        if (count($man_names)) {
            $this->_testoutput .= '<ul>';
            foreach ($man_names as $man_name) {
                $this->_testoutput .= '<li><span class="codwfeeplus_bold_txt">' . $man_name . '</span></li>';
            }
            $this->_testoutput .= '</ul>';
        } else {
            $this->_testoutput .= '<span class="codwfeeplus_bold_txt">No manufacturers defined.</span>';
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
                $cond_valid = $this->checkCondValid($cond, $id_carrier, $id_country, $id_zone, $categories_array, $cartvalue, $carriervalue, $cust_group, $manufacturers_array, $suppliers_array, $id_shop);
                $this->_testoutput .= '<div class="codwfeeplus_' . ($cond_valid ? 'cond_passed' : 'cond_failed') . '">'
                        . '<p>Checking condition with id# <span class="codwfeeplus_bold_txt">' . $cond['id_codwfeeplus_cond'] . '</span></p>';
                $this->_testoutput .= $this->_testoutput_check;
                if ($cond_valid) {
                    $this->_testoutput .= '<p>Condition passed validation. Calculating fee...</p>';
                    $fee = $this->getConditionFee($cond, $cartvalue, $carriervalue);
                    $this->_testoutput .= $this->_testoutput_applyfee;
                    $this->_testoutput .= '<p>Fee calculated from this condition: <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span></p>';
                    $fee_arr[] = array(
                        'fee' => $fee,
                        'id' => $cond['id_codwfeeplus_cond'],
                        'integration' => $cond['codwfeeplus_integration'],
                        'taxrule_id' => $cond['codwfeeplus_taxrule_id'],
                    );
                } else {
                    $this->_testoutput .= '<p>Condition did not pass validation.</p>';
                }

                $this->_testoutput .= '</div>';
            }
        } else {
            $this->_testoutput .= '<div class="codwfeeplus_cond_warning">'
                    . 'There are no conditions defined'
                    . '</div>';
        }

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

        if ($this->_cond_integration == 1) {
            $this->_testoutput .= '<div class="codwfeeplus_taxrule_calc">';
            $this->_cond_taxrule = $fee_arr[0]['taxrule_id'];
            $rule_name = $this->getTaxRuleNameFromID($this->_cond_taxrule);
            $this->_testoutput .= '<p>Tax for COD product is defined from the first successful condition (<span class="codwfeeplus_bold_txt">' . $rule_name . '</span> from condition with id <span class="codwfeeplus_bold_txt">' . $fee_arr[0]['id'] . '</span>).</p>';
            $this->_testoutput .= '</div>';
        }

        $this->_testoutput .= '</div>';
        $this->_testoutput .= '</div>';

        return Tools::ps_round((float) $ret, _PS_PRICE_COMPUTE_PRECISION_);
    }

    private function checkCondValid($cond, $id_carrier, $id_country, $id_zone, $categories_array, $cartvalue, $carriervalue, $cust_group, $manufacturers_array, $suppliers_array, $id_shop)
    {
        $this->_testoutput_check = '<div class="codwfeeplus_cond_check_steps"><ul>';
        $apply_cond = false;
        if ($cond['codwfeeplus_active']) {
            $apply_cond = true;

            if ($cond['codwfeeplus_countries'] !== '') {
                $v = $this->countriesArrToText($cond['codwfeeplus_countries']);
                $t = $this->checkListID($cond['codwfeeplus_countries'], $id_country);
                if ($t) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted country matched condition\'s countries (' . $v . ').</li>';
                } else {
                    $this->_testoutput_check .= '<li  class="codwfeeplus_cond_error">Submitted country didn\'t match condition\'s countries (' . $v . ').</li>';
                }
                $apply_cond &= $t;
            } else {
                $this->_testoutput_check .= '<li  class="codwfeeplus_cond_neutral">Condition doesn\'t have any countries defined.</li>';
            }

            if ($cond['codwfeeplus_carriers'] !== '') {
                $v = $this->carriersArrToText($cond['codwfeeplus_carriers']);
                $t = $this->checkListID($cond['codwfeeplus_carriers'], $id_carrier);
                if ($t) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted carrier matched condition\'s carriers (' . $v . ').</li>';
                } else {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_error">Submitted carrier didn\'t match condition\'s carriers (' . $v . ').</li>';
                }
                $apply_cond &= $t;
            } else {
                $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Condition doesn\'t have any carriers defined.</li>';
            }

            if ($cond['codwfeeplus_zones'] !== '') {
                $v = $this->zonesArrToText($cond['codwfeeplus_zones']);
                $t = $this->checkListID($cond['codwfeeplus_zones'], $id_zone);
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
                if ($cond['codwfeeplus_groups'] !== '') {
                    $v = $this->groupsArrToText($cond['codwfeeplus_groups']);
                    $t = $this->checkMultipleValuesListID($cond['codwfeeplus_groups'], $cust_group, $cond['codwfeeplus_matchall_groups']);
                    $multitext = $cond['codwfeeplus_matchall_groups'] ? '(All submitted groups should match)' : '(Any submitted group should match)';
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

            if ($cond['codwfeeplus_categories'] !== '') {
                $v = $this->categoriesArrToText($cond['codwfeeplus_categories']);
                $t = $this->checkMultipleValuesListID($cond['codwfeeplus_categories'], $categories_array, $cond['codwfeeplus_matchall_categories']);
                $multitext = $cond['codwfeeplus_matchall_categories'] ? '(All submitted categories should match)' : '(Any submitted category should match)';
                if ($t) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted categories matched condition\'s categories ' . $multitext . ' -> ' . $v . '.</li>';
                } else {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_error">Submitted categories didn\'t match condition\'s categories ' . $multitext . ' -> ' . $v . '.</li>';
                }
                $apply_cond &= $t;
            } else {
                $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Condition doesn\'t have any categories defined.</li>';
            }

            if ($cond['codwfeeplus_manufacturers'] !== '') {
                $v = $this->manufacturersArrToText($cond['codwfeeplus_manufacturers']);
                $t = $this->checkMultipleValuesListID($cond['codwfeeplus_manufacturers'], $manufacturers_array, $cond['codwfeeplus_matchall_manufacturers']);
                $multitext = $cond['codwfeeplus_matchall_manufacturers'] ? '(All submitted manufacturers should match)' : '(Any submitted manufacturer should match)';
                if ($t) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted manufacturers matched condition\'s groups ' . $multitext . ' -> ' . $v . '.</li>';
                } else {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_error">Submitted manufacturers didn\'t match condition\'s groups ' . $multitext . ' -> ' . $v . '.</li>';
                }
                $apply_cond &= $t;
            } else {
                $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Condition doesn\'t have any manufacturers defined.</li>';
            }

            if ($cond['codwfeeplus_suppliers'] !== '') {
                $v = $this->suppliersArrToText($cond['codwfeeplus_suppliers']);
                $t = $this->checkMultipleValuesListID($cond['codwfeeplus_suppliers'], $suppliers_array, $cond['codwfeeplus_matchall_suppliers']);
                $multitext = $cond['codwfeeplus_matchall_suppliers'] ? '(All submitted suppliers should match)' : '(Any submitted supplier should match)';
                if ($t) {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_success">Submitted suppliers matched condition\'s groups ' . $multitext . ' -> ' . $v . '.</li>';
                } else {
                    $this->_testoutput_check .= '<li class="codwfeeplus_cond_error">Submitted suppliers didn\'t match condition\'s groups ' . $multitext . ' -> ' . $v . '.</li>';
                }
                $apply_cond &= $t;
            } else {
                $this->_testoutput_check .= '<li class="codwfeeplus_cond_neutral">Condition doesn\'t have any suppliers defined.</li>';
            }

            if (((float) $cond['codwfeeplus_cartvalue']) != 0) {
                $t = true;
                $sign = '';
                $addCarrier = true;
                if (!$cond['codwfeeplus_cartvalue_include_carrier']) {
                    $addCarrier = false;
                }
                $tot = $cartvalue;
                if ($addCarrier) {
                    $tot += $carriervalue;
                }
                if ($cond['codwfeeplus_cartvalue_sign'] == 0) {  // >=
                    $sign = 'greater or equal';
                    $t = $this->firstGreaterorEqualtoSecond($tot, (float) $cond['codwfeeplus_cartvalue']);
                } else {
                    $sign = 'less or equal';
                    $t = $this->firstLesserorEqualtoSecond($tot, (float) $cond['codwfeeplus_cartvalue']);
                }
                $txt = 'Submitted cart value ';
                if ($addCarrier) {
                    $txt = 'Submitted cart value (including carrier\'s fee) ';
                }
                $cur = $this->context->currency;
                $def_cur = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
                $cond_value = (float) $cond['codwfeeplus_cartvalue'];
                $cond_value_txt = '<span class="codwfeeplus_bold_price">' . Tools::displayPrice($cond_value) . '</span>';
                if ($cur->id != Configuration::get('PS_CURRENCY_DEFAULT')) {
                    $old_cond_value = $cond_value;
                    $cond_value = Tools::convertPriceFull($cond_value, null, $cur);
                    $cond_value_txt = '(<span class="codwfeeplus_bold_price">' . Tools::displayPrice($old_cond_value, $def_cur) . '</span> =>)<span class="codwfeeplus_bold_price">' . Tools::displayPrice($cond_value) . '</span>';
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

    private function getConditionFee($cond, $cartvalue, $carriervalue)
    {
        /* @var $cur Currency */
        $cur = $this->context->currency;
        $def_cur = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        $this->_testoutput_applyfee = '<div class="codwfeeplus_fee_calc_steps"><ul>';
        $fee = 0;
        $addCarrier = true;
        if (!$cond['codwfeeplus_fee_percent_include_carrier']) {
            $addCarrier = false;
        }
        switch ($cond['codwfeeplus_fee_type']) {
            case 0: //no fee
                $fee = 0;
                $this->_testoutput_applyfee .= '<li>This condition doesn\'t have any fee</li>';
                break;
            case 1: //fix
                $fee = (float) $cond['codwfeeplus_fee'];
                if ($cur->id != Configuration::get('PS_CURRENCY_DEFAULT')) {
                    $oldfee = $fee;
                    $fee = Tools::convertPriceFull($fee, null, $cur);
                    $this->_testoutput_applyfee .= '<li>Converting fixed fee currency: From <span class="codwfeeplus_bold_price">' . Tools::displayPrice($oldfee, $def_cur) . '</span> to <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span></li>';
                }
                $this->_testoutput_applyfee .= '<li>Fixed fee of <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> applied</li>';
                break;
            case 2: //percentage
                $percent = (float) $cond['codwfeeplus_fee_percent'];
                $percent = $percent / 100;
                if ($addCarrier) {
                    $fee = ($cartvalue + $carriervalue ) * $percent;
                    $this->_testoutput_applyfee .= '<li>Percentage fee (including Carrier\'s fee): (<span class="codwfeeplus_bold_price">' . Tools::displayPrice($cartvalue) . '</span> + <span class="codwfeeplus_bold_price">' . Tools::displayPrice($carriervalue) . '</span>) * <span class="codwfeeplus_bold_price">' . ($percent * 100) . '%</span> = <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> calculated</li>';
                } else {
                    $fee = $cartvalue * $percent;
                    $this->_testoutput_applyfee .= '<li>Percentage fee: <span class="codwfeeplus_bold_price">' . Tools::displayPrice($cartvalue) . '</span> * <span class="codwfeeplus_bold_price">' . ($percent * 100) . '%</span> = <span class="codwfeeplus_bold_price">' . Tools::displayPrice($fee) . '</span> calculated</li>';
                }

                $minimalfee = (float) $cond['codwfeeplus_fee_min'];
                $maximalfee = (float) $cond['codwfeeplus_fee_max'];
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
                $percent = (float) $cond['codwfeeplus_fee_percent'];
                $percent = $percent / 100;
                $fixed = (float) $cond['codwfeeplus_fee'];
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


                $minimalfee = (float) $cond['codwfeeplus_fee_min'];
                $maximalfee = (float) $cond['codwfeeplus_fee_max'];
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
        $this->_testoutput_applyfee .= '<p>Contition\'s integration way is <span class="codwfeeplus_bold_txt">' . $this->_integration_condition_arr[$cond['codwfeeplus_integration']] . '</span>.</p>';

        $rule_name = $this->getTaxRuleNameFromID($cond['codwfeeplus_taxrule_id']);
        $this->_testoutput_applyfee .= '<p>Contition\'s tax for COD product is <span class="codwfeeplus_bold_txt">' . $rule_name . '</span>.</p>';

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
        $ret = '';
        $i = 0;
        $inArr = explode('|', $inliststr);
        foreach ($inArr as $value) {
            if ($value == '0') {
                $ret .= ($i > 0 ? ' ,' : '') . $this->l('Empty manufacturer');
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

    public function validateOrder_AddToCarrier($fee, $id_cart, $id_order_state, $amount_paid, $payment_method = 'Unknown', $message = null, $extra_vars = array(), $currency_special = null, $dont_touch_amount = false, $secure_key = false, Shop $shop = null)
    {
        if (self::DEBUG_MODE) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Function called', 1, null, 'Cart', (int) $id_cart, true);
        }
        if (!isset($this->context)) {
            $this->context = Context::getContext();
        }
        $this->context->cart = new Cart((int) $id_cart);
        $this->context->customer = new Customer((int) $this->context->cart->id_customer);
// The tax cart is loaded before the customer so re-cache the tax calculation method
        $this->context->cart->setTaxCalculationMethod();
        $this->context->language = new Language((int) $this->context->cart->id_lang);
        $this->context->shop = ($shop ? $shop : new Shop((int) $this->context->cart->id_shop));
        ShopUrl::resetMainDomainCache();
        $id_currency = $currency_special ? (int) $currency_special : (int) $this->context->cart->id_currency;
        $this->context->currency = new Currency((int) $id_currency, null, (int) $this->context->shop->id);
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
            $context_country = $this->context->country;
        }
        $order_status = new OrderState((int) $id_order_state, (int) $this->context->language->id);
        if (!Validate::isLoadedObject($order_status)) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Order Status cannot be loaded', 3, null, 'Cart', (int) $id_cart, true);
            throw new PrestaShopException('Can\'t load Order status');
        }
        if (!$this->active) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Module is not active', 3, null, 'Cart', (int) $id_cart, true);
            die(Tools::displayError());
        }
// Does order already exists ?
        if (Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists() == false) {
            if ($secure_key !== false && $secure_key != $this->context->cart->secure_key) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - Secure key does not match', 3, null, 'Cart', (int) $id_cart, true);
                die(Tools::displayError());
            }
// For each package, generate an order
            $delivery_option_list = $this->context->cart->getDeliveryOptionList();
            $package_list = $this->context->cart->getPackageList();
            $cart_delivery_option = $this->context->cart->getDeliveryOption();
// If some delivery options are not defined, or not valid, use the first valid option
            foreach ($delivery_option_list as $id_address => $package) {
                if (!isset($cart_delivery_option[$id_address]) || !array_key_exists($cart_delivery_option[$id_address], $package)) {
                    foreach ($package as $key => $val) {
                        $cart_delivery_option[$id_address] = $key;
                        break;
                    }
                }
            }
            $order_list = array();
            $order_detail_list = array();
            do {
                $reference = Order::generateReference();
            } while (Order::getByReference($reference)->count());
            $this->currentOrderReference = $reference;
            $order_creation_failed = false;
            $cart_total_paid = (float) Tools::ps_round((float) $this->context->cart->getOrderTotal(true, Cart::BOTH), 2);
            foreach ($cart_delivery_option as $id_address => $key_carriers) {
                foreach ($delivery_option_list[$id_address][$key_carriers]['carrier_list'] as $id_carrier => $data) {
                    foreach ($data['package_list'] as $id_package) {
                        // Rewrite the id_warehouse
                        $package_list[$id_address][$id_package]['id_warehouse'] = (int) $this->context->cart->getPackageIdWarehouse($package_list[$id_address][$id_package], (int) $id_carrier);
                        $package_list[$id_address][$id_package]['id_carrier'] = $id_carrier;
                    }
                }
            }
// Make sure CartRule caches are empty
            CartRule::cleanCache();
            $cart_rules = $this->context->cart->getCartRules();
            foreach ($cart_rules as $cart_rule) {
                if (($rule = new CartRule((int) $cart_rule['obj']->id)) && Validate::isLoadedObject($rule)) {
                    if ($error = $rule->checkValidity($this->context, true, true)) {
                        $this->context->cart->removeCartRule((int) $rule->id);
                        if (isset($this->context->cookie) && isset($this->context->cookie->id_customer) && $this->context->cookie->id_customer && !empty($rule->code)) {
                            if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                                Tools::redirect('index.php?controller=order-opc&submitAddDiscount=1&discount_name=' . urlencode($rule->code));
                            }
                            Tools::redirect('index.php?controller=order&submitAddDiscount=1&discount_name=' . urlencode($rule->code));
                        } else {
                            $rule_name = isset($rule->name[(int) $this->context->cart->id_lang]) ? $rule->name[(int) $this->context->cart->id_lang] : $rule->code;
                            $error = sprintf(Tools::displayError('CartRule ID %1s (%2s) used in this cart is not valid and has been withdrawn from cart'), (int) $rule->id, $rule_name);
                            PrestaShopLogger::addLog($error, 3, '0000002', 'Cart', (int) $this->context->cart->id);
                        }
                    }
                }
            }
            foreach ($package_list as $id_address => $packageByAddress) {
                foreach ($packageByAddress as $id_package => $package) {
                    /** @var Order $order */
                    $order = new Order();
                    $order->product_list = $package['product_list'];
                    if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
                        $address = new Address((int) $id_address);
                        $this->context->country = new Country((int) $address->id_country, (int) $this->context->cart->id_lang);
                        if (!$this->context->country->active) {
                            throw new PrestaShopException('The delivery address country is not active.');
                        }
                    }
                    $carrier = null;
                    if (!$this->context->cart->isVirtualCart() && isset($package['id_carrier'])) {
                        $carrier = new Carrier((int) $package['id_carrier'], (int) $this->context->cart->id_lang);
                        $order->id_carrier = (int) $carrier->id;
                        $id_carrier = (int) $carrier->id;
                    } else {
                        $order->id_carrier = 0;
                        $id_carrier = 0;
                    }

                    $order->id_customer = (int) $this->context->cart->id_customer;
                    $order->id_address_invoice = (int) $this->context->cart->id_address_invoice;
                    $order->id_address_delivery = (int) $id_address;
                    $order->id_currency = $this->context->currency->id;
                    $order->id_lang = (int) $this->context->cart->id_lang;
                    $order->id_cart = (int) $this->context->cart->id;
                    $order->reference = $reference;
                    $order->id_shop = (int) $this->context->shop->id;
                    $order->id_shop_group = (int) $this->context->shop->id_shop_group;
                    $order->secure_key = ($secure_key ? pSQL($secure_key) : pSQL($this->context->customer->secure_key));
                    $order->payment = $payment_method;
                    if (isset($this->name)) {
                        $order->module = $this->name;
                    }
                    $order->recyclable = $this->context->cart->recyclable;
                    $order->gift = (int) $this->context->cart->gift;
                    $order->gift_message = $this->context->cart->gift_message;
                    $order->mobile_theme = $this->context->cart->mobile_theme;
                    $order->conversion_rate = $this->context->currency->conversion_rate;
                    $amount_paid = !$dont_touch_amount ? Tools::ps_round((float) $amount_paid, 2) : $amount_paid;
                    $order->total_paid_real = 0;
                    $order->total_products = (float) $this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS, $order->product_list, $id_carrier);
                    $order->total_products_wt = (float) $this->context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS, $order->product_list, $id_carrier);
                    $order->total_discounts_tax_excl = (float) abs($this->context->cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS, $order->product_list, $id_carrier));
                    $order->total_discounts_tax_incl = (float) abs($this->context->cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS, $order->product_list, $id_carrier));
                    $order->total_discounts = $order->total_discounts_tax_incl;

//Adding cod fee
                    $feewithout = $fee;

// fee already contains tax
                    if ($order->carrier_tax_rate > 0 && $fee > 0) {
                        $feewithout = (float) Tools::ps_round($fee - (float) $fee / (100 + $order->carrier_tax_rate) * $order->carrier_tax_rate, 2);
                    }

                    $order->total_shipping_tax_excl = (float) $this->context->cart->getPackageShippingCost((int) $id_carrier, false, null, $order->product_list) + $feewithout;
                    $order->total_shipping_tax_incl = (float) $this->context->cart->getPackageShippingCost((int) $id_carrier, true, null, $order->product_list) + $fee;
                    $order->total_shipping = $order->total_shipping_tax_incl;
                    if (!is_null($carrier) && Validate::isLoadedObject($carrier)) {
                        $order->carrier_tax_rate = $carrier->getTaxesRate(new Address((int) $this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
                    }
                    $order->total_wrapping_tax_excl = (float) abs($this->context->cart->getOrderTotal(false, Cart::ONLY_WRAPPING, $order->product_list, $id_carrier));
                    $order->total_wrapping_tax_incl = (float) abs($this->context->cart->getOrderTotal(true, Cart::ONLY_WRAPPING, $order->product_list, $id_carrier));
                    $order->total_wrapping = $order->total_wrapping_tax_incl;
                    $order->total_paid_tax_excl = (float) Tools::ps_round((float) $this->context->cart->getOrderTotal(false, Cart::BOTH, $order->product_list, $id_carrier) + $feewithout, _PS_PRICE_COMPUTE_PRECISION_);
                    $order->total_paid_tax_incl = (float) Tools::ps_round((float) $this->context->cart->getOrderTotal(true, Cart::BOTH, $order->product_list, $id_carrier) + $fee, _PS_PRICE_COMPUTE_PRECISION_);
                    $order->total_paid = $order->total_paid_tax_incl;
                    $order->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
                    $order->round_type = Configuration::get('PS_ROUND_TYPE');
                    $order->invoice_date = '0000-00-00 00:00:00';
                    $order->delivery_date = '0000-00-00 00:00:00';
                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Order is about to be added', 1, null, 'Cart', (int) $id_cart, true);
                    }
// Creating order
                    $result = $order->add();
                    if (!$result) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Order cannot be created', 3, null, 'Cart', (int) $id_cart, true);
                        throw new PrestaShopException('Can\'t save Order');
                    }
// Amount paid by customer is not the right one -> Status = payment error
// We don't use the following condition to avoid the float precision issues : http://www.php.net/manual/en/language.types.float.php
// if ($order->total_paid != $order->total_paid_real)
// We use number_format in order to compare two string
/////////////////////////////////////////////////////////////
                    if ($order_status->logable && number_format($cart_total_paid + $fee, _PS_PRICE_COMPUTE_PRECISION_) != number_format($amount_paid, _PS_PRICE_COMPUTE_PRECISION_)) {
                        $id_order_state = Configuration::get('PS_OS_ERROR');
                    }
                    $order_list[] = $order;
                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - OrderDetail is about to be added', 1, null, 'Cart', (int) $id_cart, true);
                    }
// Insert new Order detail list using cart for the current order
                    $order_detail = new OrderDetail(null, null, $this->context);
                    $order_detail->createList($order, $this->context->cart, $id_order_state, $order->product_list, 0, true, $package_list[$id_address][$id_package]['id_warehouse']);
                    $order_detail_list[] = $order_detail;
                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - OrderCarrier is about to be added', 1, null, 'Cart', (int) $id_cart, true);
                    }
// Adding an entry in order_carrier table
                    if (!is_null($carrier)) {
                        $order_carrier = new OrderCarrier();
                        $order_carrier->id_order = (int) $order->id;
                        $order_carrier->id_carrier = (int) $id_carrier;
                        $order_carrier->weight = (float) $order->getTotalWeight();
                        $order_carrier->shipping_cost_tax_excl = (float) $order->total_shipping_tax_excl;
                        $order_carrier->shipping_cost_tax_incl = (float) $order->total_shipping_tax_incl;
                        $order_carrier->add();
                    }
                }
            }
// The country can only change if the address used for the calculation is the delivery address, and if multi-shipping is activated
            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
                $this->context->country = $context_country;
            }
            if (!$this->context->country->active) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - Country is not active', 3, null, 'Cart', (int) $id_cart, true);
                throw new PrestaShopException('The order address country is not active.');
            }
            if (self::DEBUG_MODE) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - Payment is about to be added', 1, null, 'Cart', (int) $id_cart, true);
            }
// Register Payment only if the order status validate the order
            if ($order_status->logable) {
                // $order is the last order loop in the foreach
// The method addOrderPayment of the class Order make a create a paymentOrder
// linked to the order reference and not to the order id
                if (isset($extra_vars['transaction_id'])) {
                    $transaction_id = $extra_vars['transaction_id'];
                } else {
                    $transaction_id = null;
                }
                if (!isset($order) || !Validate::isLoadedObject($order) || !$order->addOrderPayment($amount_paid, null, $transaction_id)) {
                    PrestaShopLogger::addLog('PaymentModule::validateOrder - Cannot save Order Payment', 3, null, 'Cart', (int) $id_cart, true);
                    throw new PrestaShopException('Can\'t save Order Payment');
                }
            }
// Next !
            $only_one_gift = false;
            $cart_rule_used = array();
            $products = $this->context->cart->getProducts();

// Make sure CartRule caches are empty
            CartRule::cleanCache();
            foreach ($order_detail_list as $key => $order_detail) {
                /** @var OrderDetail $order_detail */
                $order = $order_list[$key];
                if (!$order_creation_failed && isset($order->id)) {
                    if (!$secure_key) {
                        $message .= '<br />' . Tools::displayError('Warning: the secure key is empty, check your payment account before validation');
                    }
// Optional message to attach to this order
                    if (isset($message) & !empty($message)) {
                        $msg = new Message();
                        $message = strip_tags($message, '<br>');
                        if (Validate::isCleanHtml($message)) {
                            if (self::DEBUG_MODE) {
                                PrestaShopLogger::addLog('PaymentModule::validateOrder - Message is about to be added', 1, null, 'Cart', (int) $id_cart, true);
                            }
                            $msg->message = $message;
                            $msg->id_cart = (int) $id_cart;
                            $msg->id_customer = (int) ($order->id_customer);
                            $msg->id_order = (int) $order->id;
                            $msg->private = 1;
                            $msg->add();
                        }
                    }
// Insert new Order detail list using cart for the current order
//$orderDetail = new OrderDetail(null, null, $this->context);
//$orderDetail->createList($order, $this->context->cart, $id_order_state);
// Construct order detail table for the email
                    $products_list = '';
                    $virtual_product = true;
                    $product_var_tpl_list = array();
                    foreach ($order->product_list as $product) {
                        $price = Product::getPriceStatic((int) $product['id_product'], false, ($product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null), 6, null, false, true, $product['cart_quantity'], false, (int) $order->id_customer, (int) $order->id_cart, (int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
                        $price_wt = Product::getPriceStatic((int) $product['id_product'], true, ($product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null), 2, null, false, true, $product['cart_quantity'], false, (int) $order->id_customer, (int) $order->id_cart, (int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
                        $product_price = Product::getTaxCalculationMethod() == PS_TAX_EXC ? Tools::ps_round($price, 2) : $price_wt;
                        $product_var_tpl = array(
                            'reference' => $product['reference'],
                            'name' => $product['name'] . (isset($product['attributes']) ? ' - ' . $product['attributes'] : ''),
                            'unit_price' => Tools::displayPrice($product_price, $this->context->currency, false),
                            'price' => Tools::displayPrice($product_price * $product['quantity'], $this->context->currency, false),
                            'quantity' => $product['quantity'],
                            'customization' => array(),
                        );
                        $customized_datas = Product::getAllCustomizedDatas((int) $order->id_cart);
                        if (isset($customized_datas[$product['id_product']][$product['id_product_attribute']])) {
                            $product_var_tpl['customization'] = array();
                            foreach ($customized_datas[$product['id_product']][$product['id_product_attribute']][$order->id_address_delivery] as $customization) {
                                $customization_text = '';
                                if (isset($customization['datas'][Product::CUSTOMIZE_TEXTFIELD])) {
                                    foreach ($customization['datas'][Product::CUSTOMIZE_TEXTFIELD] as $text) {
                                        $customization_text .= $text['name'] . ': ' . $text['value'] . '<br />';
                                    }
                                }
                                if (isset($customization['datas'][Product::CUSTOMIZE_FILE])) {
                                    $customization_text .= sprintf(Tools::displayError('%d image(s)'), count($customization['datas'][Product::CUSTOMIZE_FILE])) . '<br />';
                                }
                                $customization_quantity = (int) $product['customization_quantity'];
                                $product_var_tpl['customization'][] = array(
                                    'customization_text' => $customization_text,
                                    'customization_quantity' => $customization_quantity,
                                    'quantity' => Tools::displayPrice($customization_quantity * $product_price, $this->context->currency, false),
                                );
                            }
                        }
                        $product_var_tpl_list[] = $product_var_tpl;
// Check if is not a virutal product for the displaying of shipping
                        if (!$product['is_virtual']) {
                            $virtual_product &= false;
                        }
                    } // end foreach ($products)
                    $product_list_txt = '';
                    $product_list_html = '';
                    if (count($product_var_tpl_list) > 0) {
                        $product_list_txt = $this->getEmailTemplateContent('order_conf_product_list.txt', Mail::TYPE_TEXT, $product_var_tpl_list);
                        $product_list_html = $this->getEmailTemplateContent('order_conf_product_list.tpl', Mail::TYPE_HTML, $product_var_tpl_list);
                    }
                    $cart_rules_list = array();
                    $total_reduction_value_ti = 0;
                    $total_reduction_value_tex = 0;
                    foreach ($cart_rules as $cart_rule) {
                        $package = array('id_carrier' => $order->id_carrier, 'id_address' => $order->id_address_delivery, 'products' => $order->product_list);
                        $values = array(
                            'tax_incl' => $cart_rule['obj']->getContextualValue(true, $this->context, CartRule::FILTER_ACTION_ALL_NOCAP, $package),
                            'tax_excl' => $cart_rule['obj']->getContextualValue(false, $this->context, CartRule::FILTER_ACTION_ALL_NOCAP, $package),
                        );
// If the reduction is not applicable to this order, then continue with the next one
                        if (!$values['tax_excl']) {
                            continue;
                        }
// IF
//	This is not multi-shipping
//	The value of the voucher is greater than the total of the order
//	Partial use is allowed
//	This is an "amount" reduction, not a reduction in % or a gift
// THEN
//	The voucher is cloned with a new value corresponding to the remainder
                        if (count($order_list) == 1 && $values['tax_incl'] > ($order->total_products_wt - $total_reduction_value_ti) && $cart_rule['obj']->partial_use == 1 && $cart_rule['obj']->reduction_amount > 0) {
                            // Create a new voucher from the original
                            $voucher = new CartRule((int) $cart_rule['obj']->id); // We need to instantiate the CartRule without lang parameter to allow saving it
                            unset($voucher->id);
// Set a new voucher code
                            $voucher->code = empty($voucher->code) ? Tools::substr(md5($order->id . '-' . $order->id_customer . '-' . $cart_rule['obj']->id), 0, 16) : $voucher->code . '-2';
                            if (preg_match('/\-([0-9]{1,2})\-([0-9]{1,2})$/', $voucher->code, $matches) && $matches[1] == $matches[2]) {
                                $voucher->code = preg_replace('/' . $matches[0] . '$/', '-' . ((int) ($matches[1]) + 1), $voucher->code);
                            }
// Set the new voucher value
                            if ($voucher->reduction_tax) {
                                $voucher->reduction_amount = ($total_reduction_value_ti + $values['tax_incl']) - $order->total_products_wt;
// Add total shipping amout only if reduction amount > total shipping
                                if ($voucher->free_shipping == 1 && $voucher->reduction_amount >= $order->total_shipping_tax_incl) {
                                    $voucher->reduction_amount -= $order->total_shipping_tax_incl;
                                }
                            } else {
                                $voucher->reduction_amount = ($total_reduction_value_tex + $values['tax_excl']) - $order->total_products;
// Add total shipping amout only if reduction amount > total shipping
                                if ($voucher->free_shipping == 1 && $voucher->reduction_amount >= $order->total_shipping_tax_excl) {
                                    $voucher->reduction_amount -= $order->total_shipping_tax_excl;
                                }
                            }
                            if ($voucher->reduction_amount <= 0) {
                                continue;
                            }
                            if ($this->context->customer->isGuest()) {
                                $voucher->id_customer = 0;
                            } else {
                                $voucher->id_customer = $order->id_customer;
                            }
                            $voucher->quantity = 1;
                            $voucher->reduction_currency = $order->id_currency;
                            $voucher->quantity_per_user = 1;
                            $voucher->free_shipping = 0;
                            if ($voucher->add()) {
                                // If the voucher has conditions, they are now copied to the new voucher
                                CartRule::copyConditions($cart_rule['obj']->id, $voucher->id);
                                $params = array(
                                    '{voucher_amount}' => Tools::displayPrice($voucher->reduction_amount, $this->context->currency, false),
                                    '{voucher_num}' => $voucher->code,
                                    '{firstname}' => $this->context->customer->firstname,
                                    '{lastname}' => $this->context->customer->lastname,
                                    '{id_order}' => $order->reference,
                                    '{order_name}' => $order->getUniqReference(),
                                );
                                Mail::Send(
                                        (int) $order->id_lang, 'voucher', sprintf(Mail::l('New voucher for your order %s', (int) $order->id_lang), $order->reference), $params, $this->context->customer->email, $this->context->customer->firstname . ' ' . $this->context->customer->lastname, null, null, null, null, _PS_MAIL_DIR_, false, (int) $order->id_shop
                                );
                            }
                            $values['tax_incl'] = $order->total_products_wt - $total_reduction_value_ti;
                            $values['tax_excl'] = $order->total_products - $total_reduction_value_tex;
                        }
                        $total_reduction_value_ti += $values['tax_incl'];
                        $total_reduction_value_tex += $values['tax_excl'];
                        $order->addCartRule($cart_rule['obj']->id, $cart_rule['obj']->name, $values, 0, $cart_rule['obj']->free_shipping);
                        if ($id_order_state != Configuration::get('PS_OS_ERROR') && $id_order_state != Configuration::get('PS_OS_CANCELED') && !in_array($cart_rule['obj']->id, $cart_rule_used)) {
                            $cart_rule_used[] = $cart_rule['obj']->id;
// Create a new instance of Cart Rule without id_lang, in order to update its quantity
                            $cart_rule_to_update = new CartRule((int) $cart_rule['obj']->id);
                            $cart_rule_to_update->quantity = max(0, $cart_rule_to_update->quantity - 1);
                            $cart_rule_to_update->update();
                        }
                        $cart_rules_list[] = array(
                            'voucher_name' => $cart_rule['obj']->name,
                            'voucher_reduction' => ($values['tax_incl'] != 0.00 ? '-' : '') . Tools::displayPrice($values['tax_incl'], $this->context->currency, false),
                        );
                    }
                    $cart_rules_list_txt = '';
                    $cart_rules_list_html = '';
                    if (count($cart_rules_list) > 0) {
                        $cart_rules_list_txt = $this->getEmailTemplateContent('order_conf_cart_rules.txt', Mail::TYPE_TEXT, $cart_rules_list);
                        $cart_rules_list_html = $this->getEmailTemplateContent('order_conf_cart_rules.tpl', Mail::TYPE_HTML, $cart_rules_list);
                    }
// Specify order id for message
                    $old_message = Message::getMessageByCartId((int) $this->context->cart->id);
                    if ($old_message && !$old_message['private']) {
                        $update_message = new Message((int) $old_message['id_message']);
                        $update_message->id_order = (int) $order->id;
                        $update_message->update();
// Add this message in the customer thread
                        $customer_thread = new CustomerThread();
                        $customer_thread->id_contact = 0;
                        $customer_thread->id_customer = (int) $order->id_customer;
                        $customer_thread->id_shop = (int) $this->context->shop->id;
                        $customer_thread->id_order = (int) $order->id;
                        $customer_thread->id_lang = (int) $this->context->language->id;
                        $customer_thread->email = $this->context->customer->email;
                        $customer_thread->status = 'open';
                        $customer_thread->token = Tools::passwdGen(12);
                        $customer_thread->add();
                        $customer_message = new CustomerMessage();
                        $customer_message->id_customer_thread = $customer_thread->id;
                        $customer_message->id_employee = 0;
                        $customer_message->message = $update_message->message;
                        $customer_message->private = 0;
                        if (!$customer_message->add()) {
                            $this->errors[] = Tools::displayError('An error occurred while saving message');
                        }
                    }
                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Hook validateOrder is about to be called', 1, null, 'Cart', (int) $id_cart, true);
                    }
// Hook validate order
                    Hook::exec('actionValidateOrder', array(
                        'cart' => $this->context->cart,
                        'order' => $order,
                        'customer' => $this->context->customer,
                        'currency' => $this->context->currency,
                        'orderStatus' => $order_status,
                    ));
                    foreach ($this->context->cart->getProducts() as $product) {
                        if ($order_status->logable) {
                            ProductSale::addProductSale((int) $product['id_product'], (int) $product['cart_quantity']);
                        }
                    }
                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Order Status is about to be added', 1, null, 'Cart', (int) $id_cart, true);
                    }
// Set the order status
                    $new_history = new OrderHistory();
                    $new_history->id_order = (int) $order->id;
                    $new_history->changeIdOrderState((int) $id_order_state, $order, true);
                    $new_history->addWithemail(true, $extra_vars);
// Switch to back order if needed
                    if (Configuration::get('PS_STOCK_MANAGEMENT') && ($order_detail->getStockState() || $order_detail->product_quantity_in_stock <= 0)) {
                        $history = new OrderHistory();
                        $history->id_order = (int) $order->id;
                        $history->changeIdOrderState(Configuration::get($order->valid ? 'PS_OS_OUTOFSTOCK_PAID' : 'PS_OS_OUTOFSTOCK_UNPAID'), $order, true);
                        $history->addWithemail();
                    }
                    unset($order_detail);
// Order is reloaded because the status just changed
                    $order = new Order((int) $order->id);
// Send an e-mail to customer (one order = one email)
                    if ($id_order_state != Configuration::get('PS_OS_ERROR') && $id_order_state != Configuration::get('PS_OS_CANCELED') && $this->context->customer->id) {
                        $invoice = new Address((int) $order->id_address_invoice);
                        $delivery = new Address((int) $order->id_address_delivery);
                        $delivery_state = $delivery->id_state ? new State((int) $delivery->id_state) : false;
                        $invoice_state = $invoice->id_state ? new State((int) $invoice->id_state) : false;
                        $data = array(
                            '{firstname}' => $this->context->customer->firstname,
                            '{lastname}' => $this->context->customer->lastname,
                            '{email}' => $this->context->customer->email,
                            '{delivery_block_txt}' => $this->_getFormatedAddress($delivery, "\n"),
                            '{invoice_block_txt}' => $this->_getFormatedAddress($invoice, "\n"),
                            '{delivery_block_html}' => $this->_getFormatedAddress($delivery, '<br />', array(
                                'firstname' => '<span style="font-weight:bold;">%s</span>',
                                'lastname' => '<span style="font-weight:bold;">%s</span>',
                            )),
                            '{invoice_block_html}' => $this->_getFormatedAddress($invoice, '<br />', array(
                                'firstname' => '<span style="font-weight:bold;">%s</span>',
                                'lastname' => '<span style="font-weight:bold;">%s</span>',
                            )),
                            '{delivery_company}' => $delivery->company,
                            '{delivery_firstname}' => $delivery->firstname,
                            '{delivery_lastname}' => $delivery->lastname,
                            '{delivery_address1}' => $delivery->address1,
                            '{delivery_address2}' => $delivery->address2,
                            '{delivery_city}' => $delivery->city,
                            '{delivery_postal_code}' => $delivery->postcode,
                            '{delivery_country}' => $delivery->country,
                            '{delivery_state}' => $delivery->id_state ? $delivery_state->name : '',
                            '{delivery_phone}' => ($delivery->phone) ? $delivery->phone : $delivery->phone_mobile,
                            '{delivery_other}' => $delivery->other,
                            '{invoice_company}' => $invoice->company,
                            '{invoice_vat_number}' => $invoice->vat_number,
                            '{invoice_firstname}' => $invoice->firstname,
                            '{invoice_lastname}' => $invoice->lastname,
                            '{invoice_address2}' => $invoice->address2,
                            '{invoice_address1}' => $invoice->address1,
                            '{invoice_city}' => $invoice->city,
                            '{invoice_postal_code}' => $invoice->postcode,
                            '{invoice_country}' => $invoice->country,
                            '{invoice_state}' => $invoice->id_state ? $invoice_state->name : '',
                            '{invoice_phone}' => ($invoice->phone) ? $invoice->phone : $invoice->phone_mobile,
                            '{invoice_other}' => $invoice->other,
                            '{order_name}' => $order->getUniqReference(),
                            '{date}' => Tools::displayDate(date('Y-m-d H:i:s'), null, 1),
                            '{carrier}' => ($virtual_product || !isset($carrier->name)) ? Tools::displayError('No carrier') : $carrier->name,
                            '{payment}' => Tools::substr($order->payment, 0, 32),
                            '{products}' => $product_list_html,
                            '{products_txt}' => $product_list_txt,
                            '{discounts}' => $cart_rules_list_html,
                            '{discounts_txt}' => $cart_rules_list_txt,
                            '{total_paid}' => Tools::displayPrice($order->total_paid, $this->context->currency, false),
                            '{total_products}' => Tools::displayPrice(Product::getTaxCalculationMethod() == PS_TAX_EXC ? $order->total_products : $order->total_products_wt, $this->context->currency, false),
                            '{total_discounts}' => Tools::displayPrice($order->total_discounts, $this->context->currency, false),
                            '{total_shipping}' => Tools::displayPrice($order->total_shipping, $this->context->currency, false),
                            '{total_wrapping}' => Tools::displayPrice($order->total_wrapping, $this->context->currency, false),
                            '{total_tax_paid}' => Tools::displayPrice(($order->total_products_wt - $order->total_products) + ($order->total_shipping_tax_incl - $order->total_shipping_tax_excl), $this->context->currency, false),);
                        if (is_array($extra_vars)) {
                            $data = array_merge($data, $extra_vars);
                        }
// Join PDF invoice
                        $file_attachement = null;
                        if ((int) Configuration::get('PS_INVOICE') && $order_status->invoice && $order->invoice_number) {
                            $order_invoice_list = $order->getInvoicesCollection();
                            Hook::exec('actionPDFInvoiceRender', array('order_invoice_list' => $order_invoice_list));
                            $pdf = new PDF($order_invoice_list, PDF::TEMPLATE_INVOICE, $this->context->smarty);
                            $file_attachement['content'] = $pdf->render(false);
                            $file_attachement['name'] = Configuration::get('PS_INVOICE_PREFIX', (int) $order->id_lang, null, $order->id_shop) . sprintf('%06d', $order->invoice_number) . '.pdf';
                            $file_attachement['mime'] = 'application/pdf';
                        } else {
                            $file_attachement = null;
                        }
                        if (self::DEBUG_MODE) {
                            PrestaShopLogger::addLog('PaymentModule::validateOrder - Mail is about to be sent', 1, null, 'Cart', (int) $id_cart, true);
                        }
                        if (Validate::isEmail($this->context->customer->email)) {
                            Mail::Send(
                                    (int) $order->id_lang, 'order_conf', Mail::l('Order confirmation', (int) $order->id_lang), $data, $this->context->customer->email, $this->context->customer->firstname . ' ' . $this->context->customer->lastname, null, null, $file_attachement, null, _PS_MAIL_DIR_, false, (int) $order->id_shop
                            );
                        }
                    }
// updates stock in shops
                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                        $product_list = $order->getProducts();
                        foreach ($product_list as $product) {
                            // if the available quantities depends on the physical stock
                            if (StockAvailable::dependsOnStock($product['product_id'])) {
                                // synchronizes
                                StockAvailable::synchronize($product['product_id'], $order->id_shop);
                            }
                        }
                    }
                    $order->updateOrderDetailTax();
                } else {
                    $error = Tools::displayError('Order creation failed');
                    PrestaShopLogger::addLog($error, 4, '0000002', 'Cart', (int) $order->id_cart);
                    die($error);
                }
            } // End foreach $order_detail_list
// Use the last order as currentOrder
            if (isset($order) && $order->id) {
                $this->currentOrder = (int) $order->id;
            }
            if (self::DEBUG_MODE) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - End of validateOrder', 1, null, 'Cart', (int) $id_cart, true);
            }

            return true;
        } else {
            $error = Tools::displayError('Cart cannot be loaded or an order has already been placed using this cart');
            PrestaShopLogger::addLog($error, 4, '0000001', 'Cart', (int) $this->context->cart->id);
            die($error);
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

}
