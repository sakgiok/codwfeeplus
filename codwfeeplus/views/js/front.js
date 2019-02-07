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

    function replace_cart_summary(cod_active) {
        if (!$("#codwfeeplus_payment_infos").length) {
            return;
        }
        var cart_summary = $("#js-checkout-summary");
        var cart_table = $("#order-items");
        var _url = $("#codwfeeplus_payment_infos").attr("data-ajaxurl");
        var datas = {cod_active: cod_active ? 1 : 0};
        $.ajax({
            type: 'POST',
            url: _url,
            data: datas,
            success: function (data) {
                if (typeof data !== "undefined") {
                    if (cart_summary.length) {
                        cart_summary.replaceWith(data.preview);
                    }
                    if (cart_table.length) {
                        cart_table.replaceWith(data.table_preview);
                    }
                }
            },
            error: function (data) {
            }

        })
    }
    $('input[data-module-name="codwfeeplus"]').on("change", null, function () {
        replace_cart_summary(true);
    });

//    $('input[type=radio][name=payment-option]').each(function (index, value) {
//        var lang = $(this).attr('data-lang');
//        label_arr[lang] = $(this).attr('value');
//    });
//
    $('input[type=radio][name=payment-option]').on("change", null, function () {
        if ($('input[data-module-name="codwfeeplus"]').length) {
            if ($(this).attr('data-module-name') != 'codwfeeplus') {
                replace_cart_summary(false);
            }
        }
    });
//    $('input[type="radio"]').on('deselect', function () {
//        alert("DEselected");
//    });
});