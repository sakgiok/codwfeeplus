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
                <p>&copy;2018 Sakis Gkiokas. This module is completely free under the <a href="https://opensource.org/licenses/GPL-3.0" target="_blank">GNU General Public License version 3</a>.</p>
                <p>More Details: <a href="{$update.info_link}" target="_blank">{$update.info_link}</a></p>
                <p>Github repository: <a href="{$update.github_link}" target="_blank">{$update.github_link}</a></p>
            </div>
            <div class="codwfeeplus_donate_body">
                <p>This is a completely free module that you can use without any limitations, as described in it's license. In the case though, you want to buy me a beer, you can use the button below.</p>
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