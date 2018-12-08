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
class CODwFeePlusValidationModuleFrontController extends ModuleFrontController
{

    public $ssl = true;
    public $display_column_left = false;

    public function postProcess()
    {
        if (!$this->module->is17) {
            if (!Tools::getValue('confirm')) {
                return;
            }
        }
        $cart = $this->context->cart;
        // $CODfee = $this->getCostValidated($this->context->cart);
        $CODfee = $this->module->getCostFromCart($cart);
        $testoutput = $this->module->_testoutput;
        $cond_integration = $this->module->_cond_integration;
        $taxrule = $this->module->_cond_taxrule;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'codwfeeplus') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer((int) $cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $total_original = $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $integration = Configuration::get('SG_CODWFEEPLUS_INTEGRATION_WAY');
        if ($integration == 0) {
            //By condition
            if ($cond_integration == 1 && $CODfee != 0) {
                $this->validate_addProduct($CODfee, $cart, $customer, $taxrule);
            } else {
                $this->validate_addToCarrier($CODfee, $cart, $customer);
            }
        } elseif ($integration == 2 && $CODfee != 0) {
            $this->validate_addProduct($CODfee, $cart, $customer, $taxrule);
        } else {
            $this->validate_addToCarrier($CODfee, $cart, $customer);
        }

        $this->module->addTransaction($customer->id, (int) $this->module->currentOrder, $CODfee, $total_original, $testoutput);
        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
    }

    private function validate_addProduct($CODfee, $cart, $customer, $taxrule)
    {
        $currency = $this->context->currency;
        $this->module->updateCODProduct((float) $CODfee, $taxrule);
        $this->module->removeCODProductFromCart($cart);
        $this->module->addCODProductToCart($cart);
        $total = $cart->getOrderTotal(true, Cart::BOTH);
        $package_list = $cart->getPackageList(true); //Flush the cache
        $this->module->validateOrder((int) $cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $this->module->public_name, null, array(), (int) $currency->id, false, $customer->secure_key);
    }

    private function validate_addToCarrier($CODfee, $cart, $customer)
    {
        $currency = $this->context->currency;
        $total = ($cart->getOrderTotal(true, Cart::BOTH) + $CODfee);
        $this->module->validateOrder_AddToCarrier($CODfee, (int) $cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $this->module->public_name, null, array(), (int) $currency->id, false, $customer->secure_key);
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        if (!$this->module->is17) {
            parent::initContent();
            $CODfee = $this->module->getCostFromCart($this->context->cart);
            $CODfee_tax_percent = $this->module->getCODFeeTax($this->context->cart->id_carrier, $this->context->cart->id_address_delivery);
            $CODfee_notax = Tools::ps_round(((float) $CODfee) / (1.0 + $CODfee_tax_percent), 9);
            $CODfee_tax_amount = Tools::ps_round(((float) $CODfee_notax) * $CODfee_tax_percent, 9);
            $this->context->smarty->assign(array(
                'total' => ($this->context->cart->getOrderTotal(true, Cart::BOTH) + $CODfee),
                'codfee' => $CODfee,
                'codfee_tax_amount' => $CODfee_tax_amount,
                'codfee_notax' => $CODfee_notax,
                'product_value' => ($this->context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS)),
                'carrierfee' => $this->context->cart->getOrderTotal(true, Cart::ONLY_SHIPPING),
                'wrappingfee' => $this->context->cart->getOrderTotal(true, Cart::ONLY_WRAPPING),
                'this_path' => $this->module->getPathUri(),
            ));

            $this->setTemplate('validation.tpl');
        }
    }

}
