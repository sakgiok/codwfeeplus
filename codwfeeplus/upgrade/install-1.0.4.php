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
 * For any recomendations and/or suggestions please contact me
 * at sakgiok@gmail.com
 *
 *  @author    Sakis Gkiokas <sakgiok@gmail.com>
 *  @copyright 2018 Sakis Gkiokas
 *  @license   https://opensource.org/licenses/GPL-3.0  GNU General Public License version 3
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_4($object)
{
    $ret = true;
    $ret &= Configuration::updateValue('PS_CODWFEEPLUS_BEHAVIOUR', 0);
    $ret &= Configuration::updateValue('PS_CODWFEEPLUS_KEEPTRANSACTIONS', 1);
    $ret &= Db::getInstance()->execute('
			ALTER TABLE `' ._DB_PREFIX_.'codwfeeplus_conditions`
                            ADD `codwfeeplus_cartvalue` DECIMAL(13, 4) NOT NULL DEFAULT \'0\' AFTER `codwfeeplus_desc`,
                            ADD `codwfeeplus_cartvalue_sign` int(2) unsigned NOT NULL DEFAULT \'0\' AFTER `codwfeeplus_desc`
		');
    $ret &= Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `' ._DB_PREFIX_.'codwfeeplus_transactions` (
			  `id_codwfeeplus_trans` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `codwfeeplus_result` TEXT,
                          `codwfeeplus_datetime` DATETIME NOT NULL,
                          `codwfeeplus_customer_id` int(10) NOT NULL DEFAULT \'0\',
                          `codwfeeplus_order_id` int(10) NOT NULL DEFAULT \'0\',
                          `codwfeeplus_fee` DECIMAL(13, 4) NOT NULL DEFAULT \'0\',
                          `codwfeeplus_cart_total` DECIMAL(13, 4) NOT NULL DEFAULT \'0\',
			  PRIMARY KEY (`id_codwfeeplus_trans`)
			) ENGINE=' ._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
		');

    return $ret;
}
