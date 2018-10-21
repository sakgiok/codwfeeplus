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

{if $help_ajax == true}
    <link rel="stylesheet" type="text/css" href="{$css_file}" />
{/if}
{if $help_ajax == false}
    <div class="bootstrap" id="codwfeeplushelpblock">
        <div class="panel">
            <div class="panel-heading" onclick="$('#codwfeeplus_help_panel').slideToggle();">
                <i class="icon-question"></i>
                {$help_title|escape:'htmlall':'UTF-8'}  <span style="text-transform: none;font-style: italic;">({$help_sub|escape:'htmlall':'UTF-8'})</span>
                {if $update.res == 'update'}
                    &nbsp;<span class="codwfeeplus_update_title_success">A NEW UPDATE IS FOUND!</span>
                {elseif $update.res == 'current'}
                    &nbsp;<span class="codwfeeplus_update_title_current">NO UPDATE FOUND!</span>
                {elseif $update.res == 'error'}
                    &nbsp;<span class="codwfeeplus_update_title_error">ERROR DURING UPDATE CHECK!</span>
                {/if}
            </div>
        {/if}
        <div id="codwfeeplus_help_panel"{if $help_ajax == false && $hide == true} style="display: none;"{/if}>
            <div class="codwfeeplus_update">
                {if $update.res != '' && $update.res != 'error'}
                    {if $update.res =='current'}
                        <div class="codwfeeplus_update_out_current">
                            <p>The module version is current.</p>
                        </div>
                    {elseif $update.res=='update'}
                        <div class="codwfeeplus_update_out_success">
                            <p>A new update is available: <a href="{$update.download_link}">{$update.download_link}</a> </p>
                        </div>
                    {/if}
                {elseif $update.res == 'error'}
                    <div class="codwfeeplus_update_out_error">
                        <p>Error during update check: {$update.out}</p>
                    </div>
                {/if}
                <div class="codwfeeplus_update_form">
                    <form action="{$href}" method="post">
                        <button type="submit" name="codwfeeplus_check_update">
                            <i class="icon-refresh"></i>
                            Update check
                        </button>
                    </form>
                </div>
            </div>
            <div class="codwfeeplus_help_title">
                <p>{$module_name|escape:'htmlall':'UTF-8'} - v{$module_version|escape:'htmlall':'UTF-8'}</p>
            </div>
            <div class="codwfeeplus_help_body">
                <p>This module allows you to use the Cash On Delivery (COD) payment method, adding a fee when the appropriate conditions are met.</p>
                <p>You can enter the conditions you need in the list below, which include the following parameters:</p>
                <ul>
                    <li>The shop.</li>
                    <li>The carrier the customer chose.</li>
                    <li>The country of the delivery address.</li>
                    <li>The zone of the country of the delivery address.</li>
                    <li>The product category that at least one of the products has.</li>
                    <li>The total cart value, either above or below a value.</li>
                    <li>The group the customer belongs to.</li>
                </ul>
                <p>Each time a condition is met, a fee is calculated according to its rules. The logic that the fee is calculated is:</p>
                <ul>
                    <li>If you have one or more shops selected in the condition, it checks if the selected shop is in the condition's selected shops.</li>
                    <li>If you have one or more carriers selected in the condition, it checks if the selected carrier is in the condition's selected carriers.</li>
                    <li>If you have one or more countries selected in the condition, it checks if the delivery country is in the condition's selected countries.</li>
                    <li>If you have one or more zones selected in the condition, it checks if the delivery country's zone is in the condition's selected zones.</li>
                    <li>If you have one or more groups selected in the condition, it checks if the customer's group is in the condition's selected groups.</li>
                    <li>If you have one or more categories selected in the condition, it checks if at least one of the products' categories matches the condition's selected categories.</li>
                    <li>If you have specified a cart value other than 0, it checks if the customer's cart value is below or above (depending on what you have specified) the stored cart value.</li>
                    <li>If all of the above is true, the fee is calculated, either no fee, fixed fee, percentage of the total cart value or fixed and percentage together.</li>
                    <li>If either of the min and max values are not 0 and the fee calculation method is either percentage or fixed and percentage, the calculated fee is clipped by these values.</li>
                </ul>
                <p>Activating the option "Store Orders" will keep a track of all the orders that are made with this payment option, including the steps made to calculate the fee. You can access the list by clicking on the View Orders button on the toolbar.</p>
                <p>The behavior option defines what the module will do if a condition is met. If the "Apply the highest in the list, matching fee" is selected, which is the default, the process will stop as soon as a condition is met (and the order the condition are tested is by their position - the highest one is first). If the "Add all matching fees" is selected, all the conditions will be tested and all the fees from the matching conditions will be added to the final fee.</p>
                <p>The Integration option defines how the resulting fee will be displayed to the client. The options are to display it as a product in the customer's order, to add it to the carrier's fee or to use whatever option is stored in the first condition that passes validation (Note that if the final fee is zero, the add to carrier's fee method is always used, which for zero fee doesn't have any consequences to the final order). If the product option is selected, you can define the product title and the product reference.</p>
                <p>If the final integration method is to add a COD product, the fee tax will be the one that is stored in the the first condition that passed validation. Note that the tax has no effect to the fee value, it just shows up in the invoice.</p>
                <p>Setting the Match All option to either groups or categories, you define that, during validation of a condition, all of the provided categories and/or groups must match the ones defined in the condition. If they are not enabled, if any of the provided groups/categories match at least one of the corresponding items provided by the condition, the condition will be validated, as far as the groups/categories are concerned.</p>
                <p>You can test the conditions, by setting the desired values of a cart in the form below and you will get the total fee and the conditions that met these values.</p>
                <p>In case you need to reset the COD product in the database, you can press the appropriate button. This action will delete the product already in the database (if it exists) and insert a new one. One case you might need to do this is, if you accidentally deleted the product from the product list.</p>
                <p>For any questions or recommendations you can contact me at sakgiok (at) gmail.com</p>
                <p>More details are available at module's site: <a href="{$update.info_link}" target="_blank">{$update.info_link}</a></p>
            </div>
            <div class="codwfeeplus_donate_body">
                <p>This is a completely free module that you can use without any limitations. In the case though, you want to buy me a beer, you can use the button below.</p>
                <div class="codwfeeplus_donate_form">
                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                        <input type="hidden" name="cmd" value="_s-xclick">
                        <input type="hidden" name="hosted_button_id" value="94VTWMDKGAFX4">
                        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                    </form>
                </div>
            </div>
        </div>
        {if $help_ajax == false}
        </div>
    </div>
{/if}