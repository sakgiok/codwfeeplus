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
                $subtotals = $cart->getSubtotals();
                $totals = $cart->getTotals();

                if ($cod_active) {
                    $taxconfiguration = new TaxConfiguration();
                    $include_taxes = $taxconfiguration->includeTaxes();
                    $cart_obj = $this->context->cart;
                    $address_id = 0;

                    $taxAddressType = Configuration::get('PS_TAX_ADDRESS_TYPE');
                    if (Validate::isLoadedObject($cart_obj) && !empty($taxAddressType)) {
                        $address_id = $cart_obj->$taxAddressType;
                    } else {
                        $address_id = $cart_obj->id_address_delivery;
                    }

                    $CODfee = $this->module->getCostFromCart($this->context->cart);

                    $CODfee_tax = $this->module->getCODFeeTax($cart_obj->id_carrier, $address_id);

                    $CODfee_notax = Tools::ps_round(((float) $CODfee) / (1.0 + $CODfee_tax), 9);
                    $CODfee_tax_amount = Tools::ps_round(((float) $CODfee_notax) * $CODfee_tax, 9);

                    $CODFee_final = $CODfee;
                    if (!$include_taxes) {
                        $CODFee_final = $CODfee_notax;
                    }

                    if (isset($subtotals['tax'])) {
                        $subtotals['tax']['amount'] += $CODfee_tax_amount;
                        $subtotals['tax']['value'] = Tools::displayPrice($subtotals['tax']['amount']);
                    }
                    $subtotals['cod'] = array(
                        'amount' => $CODFee_final,
                        'label' => $this->module->l('Cash on delivery fee', 'ajax'),
                        'type' => 'cod_fee',
                        'value' => Tools::displayPrice($CODFee_final),
                    );

                    if (isset($totals['total']['amount'])) {
                        $totals['total']['amount'] += $CODFee_final;
                        $totals['total']['value'] = Tools::displayPrice($totals['total']['amount']);
                    }
                    if (isset($totals['total_excluding_tax']['amount'])) {
                        $totals['total_excluding_tax']['amount'] += $CODfee_notax;
                        $totals['total_excluding_tax']['value'] = Tools::displayPrice($totals['total_excluding_tax']['amount']);
                    }
                    if (isset($totals['total_including_tax']['amount'])) {
                        $totals['total_including_tax']['amount'] += $CODfee;
                        $totals['total_including_tax']['value'] = Tools::displayPrice($totals['total_including_tax']['amount']);
                    }
                }
                $cart['subtotals'] = $subtotals;
                $cart['totals'] = $totals;

                ob_end_clean();
                header('Content-Type: application/json');
                $this->ajaxDie(json_encode(
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
