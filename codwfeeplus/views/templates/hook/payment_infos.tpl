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
<p id="codwfeeplus_payment_infos" data-ajaxurl="{$ajax_link}">
    {l s='Pay with cash on delivery (COD)' mod='codwfeeplus'}
    {if $fee >0 }:&nbsp;<span id="codwfeeplus_fee" class="price">+{$fee_formatted}</span>{/if}
    &nbsp;<span>({l s='You pay for the merchandise upon delivery' mod='codwfeeplus'})</span>
</p>