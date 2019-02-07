/** Copyright 2018 Sakis Gkiokas
 *
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
$(document).ready(function () {

    if (codwfeeplus_is17) {
        if ($('body#cart').length) {
            $('a[data-link-action="delete-from-cart"][data-id-product="' + codwfeeplus_codproductid + '"]').trigger("click");
        }
    } else {
        if ($('body#order-opc').length || $('body#order').length) {
            $('a.cart_quantity_delete[id^="' + codwfeeplus_codproductid + '_"]').trigger("click");
        }
    }
});