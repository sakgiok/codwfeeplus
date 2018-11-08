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
class CODwFeePlusAjaxModuleFrontController extends ModuleFrontController
{

    public function postProcess()
    {
        if (!$this->module->is17) {
            return;
        }

        if (Tools::isSubmit('action')) {
            if (Tools::getValue('action') == 'getCartSummary') {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    $this->assignGeneralPurposeVariables();
                }
                $cod_active = Tools::getValue('cod_active');
                $cart = $this->cart_presenter->present(
                        $this->context->cart
                );

                if ($cod_active) {
                    $CODfee = $this->module->getCostFromCart($this->context->cart);
                    $CODfee_notax = $CODfee;
                    $total_cart = $this->context->cart->getOrderTotal(true, Cart::BOTH);
                    $total_cart_notax = $this->context->cart->getOrderTotal(false, Cart::BOTH);
                    $taxrule = $this->module->_cond_taxrule;
                    if ($taxrule != 0) {
                        $p = new Product();
                        $p->id_tax_rules_group = $taxrule;
                        $tax = ((float) $p->getTaxesRate()) * 0.01;
                        $CODfee_notax = Tools::ps_round((float) $CODfee / (1.0 + $tax), 9);
                        unset($p);
                    }

                    $cart['subtotals']['cod'] = array(
                        'amount' => $CODfee,
                        'label' => $this->module->l('Cash on delivery fee', 'ajax'),
                        'type' => 'cod_fee',
                        'value' => Tools::displayPrice($CODfee),
                    );
                    $cart['totals']['total']['amount'] = $total_cart + $CODfee;
                    $cart['totals']['total']['value'] = Tools::displayPrice($total_cart + $CODfee);
                    $cart['totals']['total_excluding_tax']['amount'] = $total_cart_notax + $CODfee_notax;
                    $cart['totals']['total_excluding_tax']['value'] = Tools::displayPrice($total_cart_notax + $CODfee_notax);
                    $cart['totals']['total_including_tax']['amount'] = $total_cart + $CODfee;
                    $cart['totals']['total_including_tax']['value'] = Tools::displayPrice($total_cart + $CODfee);
                }

                ob_end_clean();
                header('Content-Type: application/json');
                $this->ajaxDie(Tools::jsonEncode(
                                array(
                                    'preview' => $this->render('checkout/_partials/cart-summary', array(
                                        'cart' => $cart,
                                        'static_token' => Tools::getToken(false),
                                    )),
                                    'table_preview' => $this->render('checkout/_partials/order-final-summary-table.tpl', array(
                                        'products' => $cart['products'],
                                        'products_count' => $cart['products_count'],
                                        'subtotals' => $cart['subtotals'],
                                        'totals' => $cart['totals'],
                                        'labels' => $cart['labels'],
                                        'add_product_link' => true,
                                    )),
                )));
            }
        }
    }

}
