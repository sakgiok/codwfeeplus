{** Copyright 2018 Sakis Gkiokas
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
 *}

{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" rel="nofollow" title="{l s='Go back to the Checkout' mod='codwfeeplus'}">{l s='Checkout' mod='codwfeeplus'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Cash on delivery (COD) payment' mod='codwfeeplus'}
    {/capture}

<h1 class="page-heading">
    {l s='Order summary' mod='codwfeeplus'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<form action="{$link->getModuleLink('codwfeeplus', 'validation', [], true)|escape:'html'}" method="post">
    <input type="hidden" name="confirm" value="1" />

    <div class="box">
        <h3 class="page-subheading">
            {l s='Cash on delivery (COD) payment' mod='codwfeeplus'}
        </h3>
        <div id="codwfeeplus_val_image" style="float:left; margin: 0px 15px 0 0px;">
            <img src="{$this_path}views/img/codwfeeplus.png" alt="{l s='Cash on delivery (COD) payment' mod='codwfeeplus'}"/>
        </div>
        <div id="codwfeeplus_val_text" style="display: inline-block;">
            <p>

                <strong class="dark">
                    {l s='You have chosen the Cash on Delivery method.' mod='codwfeeplus'}
                </strong>
            </p>
            <p>
                {l s='The total amount of your order is' mod='codwfeeplus'}
                <span id="amount" class="price">{convertPrice price=$total}</span>
                {if $use_taxes == 1}
                    {l s='(tax incl.)' mod='codwfeeplus'}
                {/if}
            </p>
            <div class="breakdown">
                <ul style="list-style: disc; padding: 0 0 0 2em;">
                    <li>{l s='Products\' value: ' mod='codwfeeplus'} <span id="products_value" class="price">{convertPrice price=$product_value}</span></li>
                        {if $wrappingfee !=0}
                        <li>{l s='Wrapping fee: ' mod='codwfeeplus'} <span id="wrapping_fee" class="price">{convertPrice price=$wrappingfee}</span></li>
                        {/if}
                    <li>{l s='Shipping fee: ' mod='codwfeeplus'} <span id="shipping_fee" class="price">{convertPrice price=$carrierfee}</span></li>
                    <li>{l s='COD fee: ' mod='codwfeeplus'} <span id="cod_fee" class="price">{convertPrice price=$codfee}</span></li>
                </ul>
            </div>
            <p>
                {l s='Please confirm your order by clicking \'I confirm my order\'' mod='codwfeeplus'}.
            </p>
        </div>
    </div>
    <p class="cart_navigation clearfix" id="cart_navigation">
        <a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
            <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='codwfeeplus'}
        </a>
        <button class="button btn btn-default button-medium" type="submit" name="btnSubmit">
            <span>{l s='I confirm my order' mod='codwfeeplus'}<i class="icon-chevron-right right"></i></span>
        </button>
    </p>
</form>
