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
class CODwFP
{

    public $id_codwfeeplus_cond = null;
    public $codwfeeplus_fee = 0;
    public $codwfeeplus_fee_min = 0;
    public $codwfeeplus_fee_max = 0;
    public $codwfeeplus_fee_percent = 0;
    public $codwfeeplus_fee_type = 0;
    public $codwfeeplus_active = 1;
    public $codwfeeplus_condtype = 0; //0 fee - 1 module activation
    public $codwfeeplus_position;
    public $codwfeeplus_countries = '';
    public $codwfeeplus_zones = '';
    public $codwfeeplus_carriers = '';
    public $codwfeeplus_categories = '';
    public $codwfeeplus_groups = '';
    public $codwfeeplus_manufacturers = '';
    public $codwfeeplus_suppliers = '';
    public $codwfeeplus_shop = 0;
    public $codwfeeplus_desc = '';
    public $codwfeeplus_cartvalue_sign = 0;
    public $codwfeeplus_cartvalue = 0;
    public $codwfeeplus_integration = 0;
    public $codwfeeplus_orderstate_id = 0;
    public $codwfeeplus_taxrule_id = 0;
    public $codwfeeplus_fee_percent_include_carrier = 0;
    public $codwfeeplus_cartvalue_include_carrier = 0;
    public $codwfeeplus_matchall_categories = 0;
    public $codwfeeplus_matchall_groups = 0;
    public $codwfeeplus_matchall_manufacturers = 0;
    public $codwfeeplus_matchall_suppliers = 0;
    public $validation_arr = array();

    public function __construct($id_codwfp = null)
    {
        $this->validation_arr = array(
            'codwfeeplus_fee' => 'float',
            'codwfeeplus_fee_min' => 'float',
            'codwfeeplus_fee_max' => 'float',
            'codwfeeplus_fee_percent' => 'float',
            'codwfeeplus_fee_type' => 'int',
            'codwfeeplus_active' => 'int',
            'codwfeeplus_condtype' => 'int',
            'codwfeeplus_countries' => 'string',
            'codwfeeplus_zones' => 'string',
            'codwfeeplus_carriers' => 'string',
            'codwfeeplus_categories' => 'string',
            'codwfeeplus_groups' => 'string',
            'codwfeeplus_manufacturers' => 'string',
            'codwfeeplus_suppliers' => 'string',
            'codwfeeplus_desc' => 'string',
            'codwfeeplus_cartvalue_sign' => 'int',
            'codwfeeplus_cartvalue' => 'float',
            'codwfeeplus_integration' => 'int',
            'codwfeeplus_orderstate_id' => 'int',
            'codwfeeplus_taxrule_id' => 'int',
            'codwfeeplus_fee_percent_include_carrier' => 'int',
            'codwfeeplus_cartvalue_include_carrier' => 'int',
            'codwfeeplus_matchall_groups' => 'int',
            'codwfeeplus_matchall_categories' => 'int',
            'codwfeeplus_matchall_manufacturers' => 'int',
            'codwfeeplus_matchall_suppliers' => 'int',
        );
        if (!is_null($id_codwfp)) {
            $conds_db = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'codwfeeplus_conditions` '
                    . 'WHERE `id_codwfeeplus_cond`=' . (int) $id_codwfp);

            if ($conds_db) {
                $this->codwfeeplus_fee = (float) $conds_db[0]['codwfeeplus_fee'];
                $this->codwfeeplus_fee_min = (float) $conds_db[0]['codwfeeplus_fee_min'];
                $this->codwfeeplus_fee_max = (float) $conds_db[0]['codwfeeplus_fee_max'];
                $this->codwfeeplus_fee_percent = (float) $conds_db[0]['codwfeeplus_fee_percent'];
                $this->codwfeeplus_fee_type = (int) $conds_db[0]['codwfeeplus_fee_type'];
                $this->codwfeeplus_integration = (int) $conds_db[0]['codwfeeplus_integration'];
                $this->codwfeeplus_orderstate_id = (int) $conds_db[0]['codwfeeplus_orderstate_id'];
                $this->codwfeeplus_taxrule_id = (int) $conds_db[0]['codwfeeplus_taxrule_id'];
                $this->codwfeeplus_active = (int) $conds_db[0]['codwfeeplus_active'];
                $this->codwfeeplus_condtype = (int) $conds_db[0]['codwfeeplus_condtype'];
                $this->codwfeeplus_position = (int) $conds_db[0]['codwfeeplus_position'];
                $this->codwfeeplus_countries = $conds_db[0]['codwfeeplus_countries'];
                $this->codwfeeplus_zones = $conds_db[0]['codwfeeplus_zones'];
                $this->codwfeeplus_carriers = $conds_db[0]['codwfeeplus_carriers'];
                $this->codwfeeplus_categories = $conds_db[0]['codwfeeplus_categories'];
                $this->codwfeeplus_groups = $conds_db[0]['codwfeeplus_groups'];
                $this->codwfeeplus_manufacturers = $conds_db[0]['codwfeeplus_manufacturers'];
                $this->codwfeeplus_suppliers = $conds_db[0]['codwfeeplus_suppliers'];
                $this->codwfeeplus_shop = $conds_db[0]['codwfeeplus_shop'];
                $this->codwfeeplus_desc = $conds_db[0]['codwfeeplus_desc'];
                $this->id_codwfeeplus_cond = (int) $id_codwfp;
                $this->codwfeeplus_cartvalue_sign = (int) $conds_db[0]['codwfeeplus_cartvalue_sign'];
                $this->codwfeeplus_cartvalue = (float) $conds_db[0]['codwfeeplus_cartvalue'];
                $this->codwfeeplus_fee_percent_include_carrier = (int) $conds_db[0]['codwfeeplus_fee_percent_include_carrier'];
                $this->codwfeeplus_cartvalue_include_carrier = (int) $conds_db[0]['codwfeeplus_cartvalue_include_carrier'];
                $this->codwfeeplus_matchall_groups = (int) $conds_db[0]['codwfeeplus_matchall_groups'];
                $this->codwfeeplus_matchall_categories = (int) $conds_db[0]['codwfeeplus_matchall_categories'];
                $this->codwfeeplus_matchall_manufacturers = (int) $conds_db[0]['codwfeeplus_matchall_manufacturers'];
                $this->codwfeeplus_matchall_suppliers = (int) $conds_db[0]['codwfeeplus_matchall_suppliers'];
            }
        } else {
            $this->codwfeeplus_position = $this->getMaxPosition() + 1;
            if (Shop::isFeatureActive()) {
                if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                    $this->codwfeeplus_shop = Shop::getContextShopID();
                }
            }
        }
    }

    public function saveToDB()
    {
        $sql1 = 'INSERT INTO `' . _DB_PREFIX_ . 'codwfeeplus_conditions` (`codwfeeplus_active`,`codwfeeplus_condtype`,`codwfeeplus_fee`,`codwfeeplus_fee_min`,' .
                '`codwfeeplus_fee_max`,`codwfeeplus_fee_percent`,`codwfeeplus_fee_type`,`codwfeeplus_integration`,`codwfeeplus_taxrule_id`,'
                . '`codwfeeplus_orderstate_id`,`codwfeeplus_position`,' .
                '`codwfeeplus_countries`,`codwfeeplus_zones`,`codwfeeplus_manufacturers`,`codwfeeplus_matchall_manufacturers`,' .
                '`codwfeeplus_suppliers`,`codwfeeplus_matchall_suppliers`,' .
                '`codwfeeplus_carriers`,`codwfeeplus_categories`,`codwfeeplus_matchall_categories`,' .
                '`codwfeeplus_groups`,`codwfeeplus_matchall_groups`,`codwfeeplus_shop`,' .
                '`codwfeeplus_desc`,`codwfeeplus_cartvalue_sign`,`codwfeeplus_cartvalue`,' .
                '`codwfeeplus_fee_percent_include_carrier`,`codwfeeplus_cartvalue_include_carrier`)' .
                ' VALUES(' .
                (int) pSQL($this->codwfeeplus_active) . ',' .
                (int) pSQL($this->codwfeeplus_condtype) . ',' .
                (float) pSQL($this->codwfeeplus_fee) . ',' .
                (float) pSQL($this->codwfeeplus_fee_min) . ',' .
                (float) pSQL($this->codwfeeplus_fee_max) . ',' .
                (float) pSQL($this->codwfeeplus_fee_percent) . ',' .
                (int) pSQL($this->codwfeeplus_fee_type) . ',' .
                (int) pSQL($this->codwfeeplus_integration) . ',' .
                (int) pSQL($this->codwfeeplus_taxrule_id) . ',' .
                (int) pSQL($this->codwfeeplus_orderstate_id) . ',' .
                (int) pSQL($this->codwfeeplus_position) . ',' .
                '\'' . pSQL($this->codwfeeplus_countries) . '\',' .
                '\'' . pSQL($this->codwfeeplus_zones) . '\',' .
                '\'' . pSQL($this->codwfeeplus_manufacturers) . '\',' .
                (int) pSQL($this->codwfeeplus_matchall_manufacturers) . ',' .
                '\'' . pSQL($this->codwfeeplus_suppliers) . '\',' .
                (int) pSQL($this->codwfeeplus_matchall_suppliers) . ',' .
                '\'' . pSQL($this->codwfeeplus_carriers) . '\',' .
                '\'' . pSQL($this->codwfeeplus_categories) . '\',' .
                (int) pSQL($this->codwfeeplus_matchall_categories) . ',' .
                '\'' . pSQL($this->codwfeeplus_groups) . '\',' .
                (int) pSQL($this->codwfeeplus_matchall_groups) . ',' .
                '\'' . pSQL($this->codwfeeplus_shop) . '\',' .
                '\'' . pSQL($this->codwfeeplus_desc) . '\',' .
                (int) pSQL($this->codwfeeplus_cartvalue_sign) . ',' .
                (float) pSQL($this->codwfeeplus_cartvalue) . ',' .
                (int) pSQL($this->codwfeeplus_fee_percent_include_carrier) . ',' .
                (int) pSQL($this->codwfeeplus_cartvalue_include_carrier) .
                ')';

        $sql2 = 'UPDATE `' . _DB_PREFIX_ . 'codwfeeplus_conditions` SET `codwfeeplus_fee`=' . (float) pSQL($this->codwfeeplus_fee) .
                ',`codwfeeplus_fee_min`=' . (float) pSQL($this->codwfeeplus_fee_min) .
                ',`codwfeeplus_fee_max`=' . (float) pSQL($this->codwfeeplus_fee_max) .
                ',`codwfeeplus_fee_percent`=' . (float) pSQL($this->codwfeeplus_fee_percent) .
                ',`codwfeeplus_fee_type`=' . (int) pSQL($this->codwfeeplus_fee_type) .
                ',`codwfeeplus_integration`=' . (int) pSQL($this->codwfeeplus_integration) .
                ',`codwfeeplus_taxrule_id`=' . (int) pSQL($this->codwfeeplus_taxrule_id) .
                ',`codwfeeplus_orderstate_id`=' . (int) pSQL($this->codwfeeplus_orderstate_id) .
                ',`codwfeeplus_active`=' . (int) pSQL($this->codwfeeplus_active) .
                ',`codwfeeplus_condtype`=' . (int) pSQL($this->codwfeeplus_condtype) .
                ',`codwfeeplus_position`=' . (int) pSQL($this->codwfeeplus_position) .
                ',`codwfeeplus_countries`=\'' . pSQL($this->codwfeeplus_countries) . '\'' .
                ',`codwfeeplus_zones`=\'' . pSQL($this->codwfeeplus_zones) . '\'' .
                ',`codwfeeplus_manufacturers`=\'' . pSQL($this->codwfeeplus_manufacturers) . '\'' .
                ',`codwfeeplus_matchall_manufacturers`=' . (int) pSQL($this->codwfeeplus_matchall_manufacturers) .
                ',`codwfeeplus_suppliers`=\'' . pSQL($this->codwfeeplus_suppliers) . '\'' .
                ',`codwfeeplus_matchall_suppliers`=' . (int) pSQL($this->codwfeeplus_matchall_suppliers) .
                ',`codwfeeplus_carriers`=\'' . pSQL($this->codwfeeplus_carriers) . '\'' .
                ',`codwfeeplus_categories`=\'' . pSQL($this->codwfeeplus_categories) . '\'' .
                ',`codwfeeplus_matchall_categories`=' . (int) pSQL($this->codwfeeplus_matchall_categories) .
                ',`codwfeeplus_groups`=\'' . pSQL($this->codwfeeplus_groups) . '\'' .
                ',`codwfeeplus_matchall_groups`=' . (int) pSQL($this->codwfeeplus_matchall_groups) .
                ',`codwfeeplus_shop`=\'' . pSQL($this->codwfeeplus_shop) . '\'' .
                ',`codwfeeplus_desc`=\'' . pSQL($this->codwfeeplus_desc) . '\'' .
                ',`codwfeeplus_cartvalue_sign`=' . (int) pSQL($this->codwfeeplus_cartvalue_sign) .
                ',`codwfeeplus_cartvalue`=' . (float) pSQL($this->codwfeeplus_cartvalue) .
                ',`codwfeeplus_fee_percent_include_carrier`=' . (int) pSQL($this->codwfeeplus_fee_percent_include_carrier) .
                ',`codwfeeplus_cartvalue_include_carrier`=' . (int) pSQL($this->codwfeeplus_cartvalue_include_carrier) .
                ' WHERE `id_codwfeeplus_cond`=' . (int) pSQL($this->id_codwfeeplus_cond);

        $res = true;
        if (is_null($this->id_codwfeeplus_cond)) {
            $res &= Db::getInstance()->execute($sql1);
        } else {
            $res &= Db::getInstance()->execute($sql2);
        }

        return $res;
    }

    public function delete()
    {
        $res = true;
        if (!is_null($this->id_codwfeeplus_cond)) {
            $res &= Db::getInstance()->execute('
			DELETE FROM `' . _DB_PREFIX_ . 'codwfeeplus_conditions`
			WHERE `id_codwfeeplus_cond` = ' . (int) $this->id_codwfeeplus_cond
            );
        }
        $this->fixPositions();

        return $res;
    }

    public function fixPositions()
    {
        $res = true;
        $where_shop = '';
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $where_shop = ' WHERE `codwfeeplus_shop`=' . Shop::getContextShopID();
            }
        }
        $update_pairs = '';
        $conds_db = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'codwfeeplus_conditions`' . $where_shop . ' ORDER BY `codwfeeplus_position` ASC');
        $comma = '';
        $pos = 0;
        if ($conds_db) {
            foreach ($conds_db as $value) {
                $update_pairs .= $comma . '(' . (int) $value['id_codwfeeplus_cond'] . ', ' . $pos . ')';
                if ($comma === '') {
                    $comma = ',';
                }
                ++$pos;
            }

            $res &= Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'codwfeeplus_conditions` '
                    . '(`id_codwfeeplus_cond`, `codwfeeplus_position`) '
                    . 'VALUES '
                    . $update_pairs . ' '
                    . 'ON DUPLICATE KEY UPDATE '
                    . '`codwfeeplus_position` = VALUES(`codwfeeplus_position`)');
        }

        return $res;
    }

    public function getDeliveryArray()
    {
        return $this->stringToArray($this->codwfeeplus_carriers);
    }

    public function getCountriesArray()
    {
        return $this->stringToArray($this->codwfeeplus_countries);
    }

    public function getZonesArray()
    {
        return $this->stringToArray($this->codwfeeplus_zones);
    }

    public function getManufacturersArray()
    {
        return $this->stringToArray($this->codwfeeplus_manufacturers);
    }

    public function getSuppliersArray()
    {
        return $this->stringToArray($this->codwfeeplus_suppliers);
    }

    public function getCategoriesArray()
    {
        return $this->stringToArray($this->codwfeeplus_categories);
    }

    public function getGroupsArray()
    {
        return $this->stringToArray($this->codwfeeplus_groups);
    }

    public function setDeliveryArray($incarrier)
    {
        $this->codwfeeplus_carriers = $this->arrayToString($incarrier);
    }

    public function setCountriesArray($incountries)
    {
        $this->codwfeeplus_countries = $this->arrayToString($incountries);
    }

    public function setZonesArray($inzones)
    {
        $this->codwfeeplus_zones = $this->arrayToString($inzones);
    }

    public function setManufacturersArray($inmanufacturers)
    {
        $this->codwfeeplus_manufacturers = $this->arrayToString($inmanufacturers);
    }

    public function setSuppliersArray($insuppliers)
    {
        $this->codwfeeplus_suppliers = $this->arrayToString($insuppliers);
    }

    public function setCategoriesArray($incategories)
    {
        $this->codwfeeplus_categories = $this->arrayToString($incategories);
    }

    public function setGroupsArray($ingroups)
    {
        $this->codwfeeplus_groups = $this->arrayToString($ingroups);
    }

    public static function arrayToString($inarr)
    {
        return implode('|', $inarr);
    }

    public static function stringToArray($instr)
    {
        if ($instr === '') {
            return array();
        } else {
            return explode('|', $instr);
        }
    }

    public function getMaxPosition($shop_id = null)
    {
        $where_shop = '';
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $where_shop = ' WHERE `codwfeeplus_shop`=' . (($shop_id == null) ? Shop::getContextShopID() : $shop_id);
            }
        }
        if (!$res = Db::getInstance()->executeS('
            SELECT MAX(`codwfeeplus_position`) AS `max_position` FROM `' . _DB_PREFIX_ . 'codwfeeplus_conditions`' . $where_shop)) {
            return -1;
        }

        if (!isset($res[0]['max_position'])) {
            return -1;
        }

        return (int) $res[0]['max_position'];
    }

    public function getArrayCarrierFeeIncludedForList()
    {
        $ret = array(
            'codwfeeplus_fee_percent' => array(
                'carrierfeeincluded' => $this->codwfeeplus_fee_percent_include_carrier,
                'value' => $this->codwfeeplus_fee_percent,
                'type' => 'percent',
            ),
            'codwfeeplus_cartvalue' => array(
                'carrierfeeincluded' => $this->codwfeeplus_cartvalue_include_carrier,
                'value' => $this->codwfeeplus_cartvalue,
                'type' => 'price',
            ),
        );
        return $ret;
    }

    public function getArrayForList($lang_id)
    {
        $mod = Module::getInstanceByName('codwfeeplus');
        $ret = array(
            'countries' => array(
                'count' => 0,
                'title' => '',
                'contains_matchall' => 0,
                'matchall' => 0,
            ),
            'carriers' => array(
                'count' => 0,
                'title' => '',
                'contains_matchall' => 0,
                'matchall' => 0,
            ),
            'zones' => array(
                'count' => 0,
                'title' => '',
                'contains_matchall' => 0,
                'matchall' => 0,
            ),
            'manufacturers' => array(
                'count' => 0,
                'title' => '',
                'contains_matchall' => 1,
                'matchall' => 0,
            ),
            'suppliers' => array(
                'count' => 0,
                'title' => '',
                'contains_matchall' => 1,
                'matchall' => 0,
            ),
            'categories' => array(
                'count' => 0,
                'title' => '',
                'contains_matchall' => 1,
                'matchall' => 0,
            ),
            'groups' => array(
                'count' => 0,
                'title' => '',
                'contains_matchall' => 1,
                'matchall' => 0,
            ),
        );

        //countries
        if ($this->codwfeeplus_countries != '') {
            $countries_arr = $this->getCountriesArray();
            $ret['countries']['count'] = count($countries_arr);
            $i = 0;
            foreach ($countries_arr as $value) {
                $o = new Country($value);
                $ret['countries']['title'] .= ($i > 0 ? '<br/>' : '') . $o->name[(int) $lang_id];
                unset($o);
                ++$i;
            }
        }
        //carriers
        if ($this->codwfeeplus_carriers != '') {
            $carriers_arr = $this->getDeliveryArray();
            $ret['carriers']['count'] = count($carriers_arr);
            $i = 0;
            foreach ($carriers_arr as $value) {
                $o = new Carrier($value);
                $ret['carriers']['title'] .= ($i > 0 ? '<br/>' : '') . $o->name;
                unset($o);
                ++$i;
            }
        }
        //zones
        if ($this->codwfeeplus_zones != '') {
            $zones_arr = $this->getZonesArray();
            $ret['zones']['count'] = count($zones_arr);
            $i = 0;
            foreach ($zones_arr as $value) {
                $o = new Zone($value);
                $ret['zones']['title'] .= ($i > 0 ? '<br/>' : '') . $o->name;
                unset($o);
                ++$i;
            }
        }
        //manufacturers
        if ($this->codwfeeplus_manufacturers != '') {
            $manufacturers_arr = $this->getManufacturersArray();
            $ret['manufacturers']['count'] = count($manufacturers_arr);
            $i = 0;
            $ret['manufacturers']['matchall'] = $this->codwfeeplus_matchall_manufacturers;
            $empty_val_text = $mod->l('Empty manufacturer');
            if ($mod->is17) {
                $empty_val_text = $mod->l('Empty brand');
            }
            foreach ($manufacturers_arr as $value) {
                if ($value == '0') {
                    $ret['manufacturers']['title'] .= ($i > 0 ? '<br/>' : '') . $empty_val_text;
                } else {
                    $o = new Manufacturer($value);
                    $ret['manufacturers']['title'] .= ($i > 0 ? '<br/>' : '') . $o->name;
                    unset($o);
                }
                ++$i;
            }
        }
        //suppliers
        if ($this->codwfeeplus_suppliers != '') {
            $suppliers_arr = $this->getSuppliersArray();
            $ret['suppliers']['count'] = count($suppliers_arr);
            $i = 0;
            $ret['suppliers']['matchall'] = $this->codwfeeplus_matchall_suppliers;
            foreach ($suppliers_arr as $value) {
                if ($value == '0') {
                    $ret['suppliers']['title'] .= ($i > 0 ? '<br/>' : '') . $mod->l('Empty supplier');
                } else {
                    $o = new Supplier($value);
                    $ret['suppliers']['title'] .= ($i > 0 ? '<br/>' : '') . $o->name;
                    unset($o);
                }
                ++$i;
            }
        }
        //categories
        if ($this->codwfeeplus_categories != '') {
            $categories_arr = $this->getCategoriesArray();
            $ret['categories']['count'] = count($categories_arr);
            $i = 0;
            $ret['categories']['matchall'] = $this->codwfeeplus_matchall_categories;
            foreach ($categories_arr as $value) {
                $o = new Category($value);
                $ret['categories']['title'] .= ($i > 0 ? '<br/>' : '') . $o->name[(int) $lang_id];
                unset($o);
                ++$i;
            }
        }
        //groups
        if ($this->codwfeeplus_groups != '') {
            $groups_arr = $this->getGroupsArray();
            $ret['groups']['count'] = count($groups_arr);
            $i = 0;
            $ret['groups']['matchall'] = $this->codwfeeplus_matchall_groups;
            foreach ($groups_arr as $value) {
                $o = new Group($value);
                $ret['groups']['title'] .= ($i > 0 ? '<br/>' : '') . $o->name[(int) $lang_id];
                unset($o);
                ++$i;
            }
        }

        return $ret;
    }

    public function validateConditionValues()
    {
        $lang_id = (int) Configuration::get('PS_LANG_DEFAULT');
        $changed = false;
        if ($this->codwfeeplus_taxrule_id != 0) {
            $tax_array_raw = TaxRulesGroup::getTaxRulesGroups();
            if (array_search($this->codwfeeplus_taxrule_id, array_column($tax_array_raw, 'id_tax_rules_group')) === false) {
                $this->codwfeeplus_taxrule_id = 0;
                $changed = true;
            }
        }

        if ($this->codwfeeplus_orderstate_id != 0) {
            $os = OrderState::getOrderStates($lang_id);
            if (array_search($this->codwfeeplus_orderstate_id, array_column($os, 'id_order_state')) === false) {
                $this->codwfeeplus_orderstate_id = 0;
                $changed = true;
            }
        }

        if ($this->codwfeeplus_countries != '') {
            $countries = null;
            if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')) {
                $countries = Carrier::getDeliveredCountries($lang_id, true, true);
            } else {
                $countries = Country::getCountries($lang_id, true);
            }
            $countries_arr = $this->getCountriesArray();
            $new_arr = array();
            foreach ($countries_arr as $value) {
                if (array_search($value, array_column($countries, 'id_country')) === false) {
                    $changed = true;
                } else {
                    $new_arr[] = $value;
                }
            }
            $this->codwfeeplus_countries = $this->arrayToString($new_arr);
        }

        if ($this->codwfeeplus_zones != '') {
            $zones = Zone::getZones();
            $zones_arr = $this->getZonesArray();
            $new_arr = array();
            foreach ($zones_arr as $value) {
                if (array_search($value, array_column($zones, 'id_zone')) === false) {
                    $changed = true;
                } else {
                    $new_arr[] = $value;
                }
            }
            $this->codwfeeplus_zones = $this->arrayToString($new_arr);
        }

        if (Group::isFeatureActive()) {
            if ($this->codwfeeplus_groups != '') {
                $groups = Group::getGroups($lang_id);
                $groups_arr = $this->getGroupsArray();
                $new_arr = array();
                foreach ($groups_arr as $value) {
                    if (array_search($value, array_column($groups, 'id_group')) === false) {
                        $changed = true;
                    } else {
                        $new_arr[] = $value;
                    }
                }
                $this->codwfeeplus_groups = $this->arrayToString($new_arr);
            }
        }

        if ($this->codwfeeplus_categories != '') {
            $categories = Category::getCategories(false, true, false);
            $categories_arr = $this->getCategoriesArray();
            $new_arr = array();
            foreach ($categories_arr as $value) {
                if (array_search($value, array_column($categories, 'id_category')) === false) {
                    $changed = true;
                } else {
                    $new_arr[] = $value;
                }
            }
            $this->codwfeeplus_categories = $this->arrayToString($new_arr);
        }

        if ($this->codwfeeplus_manufacturers != '') {
            $manufacturers = Manufacturer::getManufacturers();
            $manufacturers_arr = $this->getManufacturersArray();
            $new_arr = array();
            foreach ($manufacturers_arr as $value) {
                if ($value != 0) {
                    if (array_search($value, array_column($manufacturers, 'id_manufacturer')) === false) {
                        $changed = true;
                    } else {
                        $new_arr[] = $value;
                    }
                } else {
                    $new_arr[] = $value;
                }
            }
            $this->codwfeeplus_manufacturers = $this->arrayToString($new_arr);
        }

        if ($this->codwfeeplus_suppliers != '') {
            $suppliers = Supplier::getSuppliers();
            $suppliers_arr = $this->getSuppliersArray();
            $new_arr = array();
            foreach ($suppliers_arr as $value) {
                if ($value != 0) {
                    if (array_search($value, array_column($suppliers, 'id_supplier')) === false) {
                        $changed = true;
                    } else {
                        $new_arr[] = $value;
                    }
                } else {
                    $new_arr[] = $value;
                }
            }
            $this->codwfeeplus_suppliers = $this->arrayToString($new_arr);
        }

        if ($changed) {
            $this->saveToDB();
        }

        return !$changed;
    }

    public function exportConditionArray()
    {
        $out = array(
            'codwfeeplus_fee' => $this->codwfeeplus_fee,
            'codwfeeplus_fee_min' => $this->codwfeeplus_fee_min,
            'codwfeeplus_fee_max' => $this->codwfeeplus_fee_max,
            'codwfeeplus_fee_percent' => $this->codwfeeplus_fee_percent,
            'codwfeeplus_fee_type' => $this->codwfeeplus_fee_type,
            'codwfeeplus_active' => $this->codwfeeplus_active,
            'codwfeeplus_condtype' => $this->codwfeeplus_condtype,
            'codwfeeplus_countries' => $this->codwfeeplus_countries,
            'codwfeeplus_zones' => $this->codwfeeplus_zones,
            'codwfeeplus_carriers' => $this->codwfeeplus_carriers,
            'codwfeeplus_categories' => $this->codwfeeplus_categories,
            'codwfeeplus_matchall_categories' => $this->codwfeeplus_matchall_categories,
            'codwfeeplus_groups' => $this->codwfeeplus_groups,
            'codwfeeplus_matchall_groups' => $this->codwfeeplus_matchall_groups,
            'codwfeeplus_manufacturers' => $this->codwfeeplus_manufacturers,
            'codwfeeplus_matchall_manufacturers' => $this->codwfeeplus_matchall_manufacturers,
            'codwfeeplus_suppliers' => $this->codwfeeplus_suppliers,
            'codwfeeplus_matchall_suppliers' => $this->codwfeeplus_matchall_suppliers,
            'codwfeeplus_desc' => $this->codwfeeplus_desc,
            'codwfeeplus_cartvalue_sign' => $this->codwfeeplus_cartvalue_sign,
            'codwfeeplus_cartvalue' => $this->codwfeeplus_cartvalue,
            'codwfeeplus_integration' => $this->codwfeeplus_integration,
            'codwfeeplus_taxrule_id' => $this->codwfeeplus_taxrule_id,
            'codwfeeplus_orderstate_id' => $this->codwfeeplus_orderstate_id,
            'codwfeeplus_fee_percent_include_carrier' => $this->codwfeeplus_fee_percent_include_carrier,
            'codwfeeplus_cartvalue_include_carrier' => $this->codwfeeplus_cartvalue_include_carrier
        );
        return $out;
    }

    public function validate($param)
    {
        $ret = true;
        foreach ($param as $key => $value) {
            if (isset($this->{$key})) {
                if ($this->validation_arr[$key] == 'float') {
                    $ret &= Validate::isFloat($value);
                } elseif ($this->validation_arr[$key] == 'int') {
                    $ret &= Validate::isInt($value);
                } elseif ($this->validation_arr[$key] == 'string') {
                    $ret &= Validate::isString($value);
                }
            } else {
                $ret &= false;
            }
        }
        return $ret;
    }

    public function loadFromArray($param)
    {
        foreach ($param as $key => $value) {
            if (isset($this->{$key})) {
                $this->{$key} = $value;
            }
        }
    }

}
