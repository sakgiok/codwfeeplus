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

function upgrade_module_1_0_6($object)
{
    if (Shop::isFeatureActive()) {
        Shop::setContext(Shop::CONTEXT_ALL);
    }
    //old vars
    Configuration::deleteByName('PS_CODWFEEPLUS_BEHAVIOUR');
    Configuration::deleteByName('PS_CODWFEEPLUS_KEEPTRANSACTIONS');

    $ret = true;
    $title = array();
    $id_tab = (int) Tab::getIdFromClassName('AdminCODwFeePlus');
    $tab = new Tab((int) $id_tab);
    foreach (Language::getLanguages(true) as $lang) {
        if ($lang['iso_code'] == 'el') {
            $title[$lang['id_lang']] = 'Χρέωση Αντικαταβολής';
        } else {
            $title[$lang['id_lang']] = 'Cash on Delivery Fee';
        }
        $tab->name[$lang['id_lang']] = $object->getTranslation('COD with Fee Plus', $lang['iso_code'], $object->name);
    }
    $ret &= $tab->save();
    unset($tab);
    $ret &= Configuration::updateValue('SG_CODWFEEPLUS_AUTO_UPDATE', 0);
    $ret &= Configuration::updateValue('SG_CODWFEEPLUS_INFO_LINK', 'https://sakgiok.gr');
    $ret &= Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_TITLE', $title);
    $ret &= Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_REFERENCE', 'COD');
    $ret &= Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_ID', 0);
    $ret &= Configuration::updateValue('SG_CODWFEEPLUS_MATCHALL_CATEGORIES', 0); //0 match any, 1 match all
    $ret &= Configuration::updateValue('SG_CODWFEEPLUS_MATCHALL_GROUPS', 0); //0 match any, 1 match all
    $ret &= Configuration::updateValue('SG_CODWFEEPLUS_INTEGRATION_WAY', 0); //0 by condition - 1 Add to carrier's fee - 2 Add new product
    $ret &= Db::getInstance()->execute('
			ALTER TABLE `' ._DB_PREFIX_.'codwfeeplus_conditions`
                            ADD `codwfeeplus_groups` TEXT AFTER `codwfeeplus_categories`
		');
    $ret &= Db::getInstance()->execute('
			ALTER TABLE `' ._DB_PREFIX_.'codwfeeplus_conditions`
                            ADD `codwfeeplus_integration` int(2) unsigned NOT NULL DEFAULT \'0\' AFTER `codwfeeplus_fee_type`
		');
    $ret &= Db::getInstance()->execute('
			ALTER TABLE `' ._DB_PREFIX_.'codwfeeplus_conditions`
                            ADD `codwfeeplus_shops` TEXT AFTER `codwfeeplus_groups`
		');
    $ret &= Db::getInstance()->execute('
			UPDATE `' ._DB_PREFIX_.'codwfeeplus_conditions`
                            SET `codwfeeplus_groups` = \'\'
		');
    $ret &= Db::getInstance()->execute('
			UPDATE `' ._DB_PREFIX_.'codwfeeplus_conditions`
                            SET `codwfeeplus_integration` = 0
		');
    $ret &= Db::getInstance()->execute('
			UPDATE `' ._DB_PREFIX_.'codwfeeplus_conditions`
                            SET `codwfeeplus_shops` = \'\'
		');
    $ret &= Db::getInstance()->execute('
			ALTER TABLE `' ._DB_PREFIX_.'codwfeeplus_conditions`
                            ADD `codwfeeplus_taxrule_id` int(2) unsigned NOT NULL DEFAULT \'0\' AFTER `codwfeeplus_integration`
		');
    $ret &= Db::getInstance()->execute('
			UPDATE `' ._DB_PREFIX_.'codwfeeplus_conditions`
                            SET `codwfeeplus_taxrule_id` = 0
		');
    $ret &= $object->installCODProduct();

    return $ret;
}
