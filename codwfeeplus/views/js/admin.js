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

    function jqSelector(str)
    {
        return str.replace(/([;&,\.\+\*\~':"\!\^#$%@\[\]\(\)=>\|])/g, '\\$1');
    }

    function codwfeeplus_prod_details_hide() {
        $('div.codwfeeplus_product_details input').css('background-color', '#ffebeb');
    }


    function codwfeeplus_prod_details_show() {
        $('div.codwfeeplus_product_details input').css('background-color', '#ebffeb');
    }


    if ($('#CODWFEEPLUS_INTEGRATION_WAY').val() == 1) {
        codwfeeplus_prod_details_hide();
    } else {
        codwfeeplus_prod_details_show();
    }


    $('#CODWFEEPLUS_INTEGRATION_WAY').change(function () {
        if ($('#CODWFEEPLUS_INTEGRATION_WAY').val() == 1) {
            codwfeeplus_prod_details_hide();
        } else {
            codwfeeplus_prod_details_show();
        }
    });

    function codwfeeplus_cond_feetype_select(a) {
        switch (a) {
            case '0':
                $('#CODWFEEPLUS_FEE').css('background-color', '#ffebeb');
                $('#CODWFEEPLUS_PERCENTAGE').css('background-color', '#ffebeb');
                $('#CODWFEEPLUS_MIN').css('background-color', '#ffebeb');
                $('#CODWFEEPLUS_MAX').css('background-color', '#ffebeb');
                break;

            case '1':
                $('#CODWFEEPLUS_FEE').css('background-color', '#ebffeb');
                $('#CODWFEEPLUS_PERCENTAGE').css('background-color', '#ffebeb');
                $('#CODWFEEPLUS_MIN').css('background-color', '#ffebeb');
                $('#CODWFEEPLUS_MAX').css('background-color', '#ffebeb');
                break;

            case '2':
                $('#CODWFEEPLUS_FEE').css('background-color', '#ffebeb');
                $('#CODWFEEPLUS_PERCENTAGE').css('background-color', '#ebffeb');
                $('#CODWFEEPLUS_MIN').css('background-color', '#ebffeb');
                $('#CODWFEEPLUS_MAX').css('background-color', '#ebffeb');
                break;

            case '3':
                $('#CODWFEEPLUS_FEE').css('background-color', '#ebffeb');
                $('#CODWFEEPLUS_PERCENTAGE').css('background-color', '#ebffeb');
                $('#CODWFEEPLUS_MIN').css('background-color', '#ebffeb');
                $('#CODWFEEPLUS_MAX').css('background-color', '#ebffeb');
                break;

            default:
                $('#CODWFEEPLUS_FEE').css('background-color', '#ebffeb');
                $('#CODWFEEPLUS_PERCENTAGE').css('background-color', '#ebffeb');
                $('#CODWFEEPLUS_MIN').css('background-color', '#ebffeb');
                $('#CODWFEEPLUS_MAX').css('background-color', '#ebffeb');
                break;
        }
    }

    codwfeeplus_cond_feetype_select($('#CODWFEEPLUS_FEETYPE').val());

    $('#CODWFEEPLUS_FEETYPE').change(function () {
        codwfeeplus_cond_feetype_select($('#CODWFEEPLUS_FEETYPE').val());
    });

//    if ($('#CODWFEEPLUS_INTEGRATION').val() == 1) {
//        $('#CODWFEEPLUS_TAXRULE').css('background-color', '#ebffeb');
//    } else {
//        $('#CODWFEEPLUS_TAXRULE').css('background-color', '#ffebeb');
//    }
//
//    $('#CODWFEEPLUS_INTEGRATION').change(function () {
//        if ($('#CODWFEEPLUS_INTEGRATION').val() == 1) {
//            $('#CODWFEEPLUS_TAXRULE').css('background-color', '#ebffeb');
//        } else {
//            $('#CODWFEEPLUS_TAXRULE').css('background-color', '#ffebeb');
//        }
//    });

    $("a#page-header-desc-codwfeeplus_conditions-reset_product").click(function (e) {
        e.stopPropagation();
        e.preventDefault();
        var href = this.href;
        var input = '<input type="hidden" name="submitCODwFeePlus_reset_product" value="1" />';
        $("body").append('<form action="' + href + '" method="post" id="codwfeeplus_poster">' + input + '</form>');
        $("#codwfeeplus_poster").submit();
    });

    $("a.action-copy-to-shop").click(function (e) {
        e.stopPropagation();
        e.preventDefault();
        var href = this.href;
        var parts = href.split('?');
        var url = parts[0] + '?';
        var params = parts[1].split('&');
        var pp, inputs = '';
        for (var i = 0, n = params.length; i < n; i++) {
            pp = params[i].split('=');
            if (pp[0] == 'submitCODwFeePlusActionCopyToShop' || pp[0] == 'CODwFeePlusActionCopyToShop_shopId' || pp[0] == 'CODwFeePlusActionCopyToShop_condId') {
                inputs += '<input type="hidden" name="' + pp[0] + '" value="' + pp[1] + '" />';
            } else {
                if (i > 0) {
                    url += '&';
                }
                url += pp[0] + '=' + pp[1];
            }
        }
        $("body").append('<form action="' + url + '" method="post" id="codwfeeplus_poster">' + inputs + '</form>');
        $("#codwfeeplus_poster").submit();
    });

    $("a.action-duplicate-cond").click(function (e) {
        e.stopPropagation();
        e.preventDefault();
        var href = this.href;
        var parts = href.split('?');
        var url = parts[0] + '?';
        var params = parts[1].split('&');
        var pp, inputs = '';
        for (var i = 0, n = params.length; i < n; i++) {
            pp = params[i].split('=');
            if (pp[0] == 'submitCODwFeePlusActionDuplicatecond' || pp[0] == 'CODwFeePlusActionDuplicatecond_condId') {
                inputs += '<input type="hidden" name="' + pp[0] + '" value="' + pp[1] + '" />';
            } else {
                if (i > 0) {
                    url += '&';
                }
                url += pp[0] + '=' + pp[1];
            }
        }
        $("body").append('<form action="' + url + '" method="post" id="codwfeeplus_poster">' + inputs + '</form>');
        $("#codwfeeplus_poster").submit();
    });

    $("a.action-export-cond").click(function (e) {
        e.stopPropagation();
        e.preventDefault();
        var href = this.href;
        var parts = href.split('?');
        var url = parts[0] + '?';
        var params = parts[1].split('&');
        var pp, inputs = '';
        for (var i = 0, n = params.length; i < n; i++) {
            pp = params[i].split('=');
            if (pp[0] == 'submitCODwFeePlusActionExportcond' || pp[0] == 'CODwFeePlusActionExportcond_condId') {
                inputs += '<input type="hidden" name="' + pp[0] + '" value="' + pp[1] + '" />';
            } else {
                if (i > 0) {
                    url += '&';
                }
                url += pp[0] + '=' + pp[1];
            }
        }
        $("body").append('<form action="' + url + '" method="post" id="codwfeeplus_poster">' + inputs + '</form>');
        $("#codwfeeplus_poster").submit();
    });

    $(".codwfeeplus_btn_selectall").click(function (e) {
        var id = $(this).data("rel");
        $("#" + jqSelector(id)+" option").prop('selected', true);
    });
    $(".codwfeeplus_btn_selectnone").click(function (e) {
        var id = $(this).data("rel");
        $("#" + jqSelector(id)+" option").prop('selected', false);
    });
});

$(window).load(function () {
    if ($("#codwfeeplus_test_result_panel").length) {
        var page_head_height = $("#content div.bootstrap div.page-head").height();
        var infos_height = $('#header_infos').height();
        var offset = $("#codwfeeplus_test_result_panel").offset().top;
        $('html, body').animate({
            scrollTop: offset - page_head_height - infos_height
        }, 1000);
    }
});