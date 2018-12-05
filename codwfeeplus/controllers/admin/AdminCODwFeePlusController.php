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
if (!defined('_PS_VERSION_')) {
    exit;
}

include_once _PS_MODULE_DIR_ . 'codwfeeplus/CODwFP.php';

class AdminCODwFeePlusController extends ModuleAdminController
{

    private $_html = '';
    private $_errors = array();
    private $_msg = array();
    private $_display_mode = 'default';
    private $_orderList_parameters = array();
    private $_condForm_id = null;
    private $_condForm_getfrompost = false;
    private $_confForm_getfrompost = false;
    private $_test_totfee = 0;
    private $_test_result = '';
    private $_hide_testForm = true;
    private $_hide_helpForm = true;
    private $_testForm_hideable = true;
    private $_hide_configForm = true;
    private $_configform_hideable = false;
    private $_defCurrencySuffix = '';
    private $_checkupdate = false;
    private $_def_NA_icon = '<span class="codwfeeplus_list_notavail"><i class="icon-ban"></i></span>';
    private $_validateCondFormValues = array(
        array('name' => 'CODWFEEPLUS_ACTIVE', 'type' => 'Int', 'out' => 'active', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_CONDTYPE', 'type' => 'Int', 'out' => 'active', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_DESCRIPTION', 'type' => 'Text', 'out' => 'description', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_INTEGRATION', 'type' => 'Int', 'out' => 'integration', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_TAXRULE', 'type' => 'Int', 'out' => 'tax rule', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_FEETYPE', 'type' => 'Int', 'out' => 'fee type', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_FEE', 'type' => 'Price', 'out' => 'fee', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_PERCENTAGE', 'type' => 'Percentage', 'out' => 'fee percentage', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_FEE_PERCENT_INCLUDE_CARRIER', 'type' => 'Int', 'out' => 'percent include carrier fee', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_MIN', 'type' => 'Price', 'out' => 'minimum cart value', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_MAX', 'type' => 'Price', 'out' => 'maximum cart value', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_CARTVALUE_SIGN', 'type' => 'Int', 'out' => 'cart value comparison', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_CARTVALUE', 'type' => 'Int', 'out' => 'cart value', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_CARTVALUE_INCLUDE_CARRIER', 'type' => 'Int', 'out' => 'cart value include carrier', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_DELIVERY_ARRAY', 'type' => 'ArrayWithIds', 'out' => 'carriers', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_COUNTRIES_ARRAY', 'type' => 'ArrayWithIds', 'out' => 'countries', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_ZONES_ARRAY', 'type' => 'ArrayWithIds', 'out' => 'zones', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_GROUPS_ARRAY', 'type' => 'ArrayWithIds', 'out' => 'groups', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_MATCHALL_GROUPS', 'type' => 'Int', 'out' => 'match all groups', 'multilang' => 0, 'req' => 0),
        array('name' => 'cond_categoryBox', 'type' => 'ArrayWithIds', 'out' => 'categories', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_MATCHALL_CATEGORIES', 'type' => 'Int', 'out' => 'match all categories', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_MANUFACTURERS_ARRAY', 'type' => 'ArrayWithIdsWithZero', 'out' => 'manufacturers', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_MATCHALL_MANUFACTURERS', 'type' => 'Int', 'out' => 'match all manufacturers', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_SUPPLIERS_ARRAY', 'type' => 'ArrayWithIdsWithZero', 'out' => 'suppliers', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_MATCHALL_SUPPLIERS', 'type' => 'Int', 'out' => 'match all suppliers', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_ID', 'type' => 'IntOrEmpty', 'out' => 'id', 'multilang' => 0, 'req' => 0),
    );
    private $_validateTestFormValues = array(
        array('name' => 'tstfrm_cartvalue', 'type' => 'Price', 'out' => 'cart value', 'multilang' => 0, 'req' => 0),
        array('name' => 'tstfrm_carriervalue', 'type' => 'Price', 'out' => 'carrier fee', 'multilang' => 0, 'req' => 0),
        array('name' => 'tstfrm_country', 'type' => 'Int', 'out' => 'country', 'multilang' => 0, 'req' => 0),
        array('name' => 'tstfrm_carrier', 'type' => 'Int', 'out' => 'carrier', 'multilang' => 0, 'req' => 0),
        array('name' => 'tstfrm_group', 'type' => 'ArrayWithIds', 'out' => 'group', 'multilang' => 0, 'req' => 0),
        array('name' => 'test_categoryBox', 'type' => 'ArrayWithIds', 'out' => 'categories', 'multilang' => 0, 'req' => 0),
        array('name' => 'tstfrm_manufacturers', 'type' => 'ArrayWithIdsWithZero', 'out' => 'manufacturers', 'multilang' => 0, 'req' => 0),
        array('name' => 'tstfrm_suppliers', 'type' => 'ArrayWithIdsWithZero', 'out' => 'suppliers', 'multilang' => 0, 'req' => 0),
    );
    private $_validateConfigFormValues = array(
        array('name' => 'CODWFEEPLUS_AUTO_UPDATE', 'type' => 'Int', 'out' => 'auto update', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_INTEGRATION_WAY', 'type' => 'Int', 'out' => 'integration', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_PRODUCT_TITLE', 'type' => 'Text', 'out' => 'product name', 'multilang' => 1, 'req' => 1),
        array('name' => 'CODWFEEPLUS_PRODUCT_REFERENCE', 'type' => 'Text', 'out' => 'product reference', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_KEEPTRANSACTIONS', 'type' => 'Int', 'out' => 'store orders', 'multilang' => 0, 'req' => 0),
        array('name' => 'CODWFEEPLUS_BEHAVIOUR', 'type' => 'Int', 'out' => 'behavior', 'multilang' => 0, 'req' => 0),
    );

    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->_html = '';
        $this->display = 'view';
        $this->token = Tools::getAdminTokenLite('AdminCODwFeePlus');
        parent::__construct();
        $this->toolbar_title = $this->l('Cash on Delivery with Fee PLUS');
        $curr = Currency::getDefaultCurrency();
        $this->_defCurrencySuffix = $curr->sign;
        unset($curr);
    }

    public function initContent()
    {
        $this->show_toolbar = true;
        $this->meta_title = $this->l('COD with Fee Plus');
        parent::initContent();
    }

    public function bulkDeleteOrders($id_array)
    {
        if (!is_array($id_array)) {
            $id_array = array($id_array);
        }
        foreach ($id_array as $value) {
            $conds_db = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'codwfeeplus_transactions` WHERE `id_codwfeeplus_trans`=' . $value);
        }

        return;
    }

    public function initPageHeaderToolbar()
    {
        if ($this->module->active) {
            if ($this->_display_mode == 'cond_form') {
                $this->page_header_toolbar_title = $this->l('COD with Fee Plus') . ' - ' . $this->l('Condition Details');
                $this->page_header_toolbar_btn['cond_cancel'] = array(
                    'href' => $this->context->link->getAdminLink('AdminCODwFeePlus'),
                    'desc' => $this->l('Cancel'),
                    'icon' => 'process-icon-cancel',
                );
            } elseif ($this->_display_mode == 'view_log') {
                $this->page_header_toolbar_title = $this->l('COD with Fee Plus') . ' - ' . $this->l('Order List');
                $this->page_header_toolbar_btn['view_default'] = array(
                    'href' => $this->context->link->getAdminLink('AdminCODwFeePlus'),
                    'desc' => $this->l('Back'),
                    'icon' => 'process-icon-back',
                );
            } else { //default
                $this->page_header_toolbar_title = $this->l('COD with Fee Plus');

                if (!Shop::isFeatureActive() || Shop::getContext() == Shop::CONTEXT_SHOP) {
                    $this->page_header_toolbar_btn['add_condition'] = array(
                        'href' => $this->context->link->getAdminLink('AdminCODwFeePlus') . '&submitCODwFeePlus_add=1',
                        'desc' => $this->l('Add new condition'),
                        'icon' => 'process-icon-plus',
                    );
                    $this->page_header_toolbar_btn['reset_product'] = array(
                        'href' => $this->context->link->getAdminLink('AdminCODwFeePlus'),
                        'desc' => $this->l('Reset COD Product'),
                        'icon' => 'process-icon-refresh',
                    );
                    $this->page_header_toolbar_btn['view_trans_log'] = array(
                        'href' => $this->context->link->getAdminLink('AdminCODwFeePlus') . '&view_trans_log',
                        'desc' => $this->l('View order log'),
                        'icon' => 'process-icon-preview',
                    );
                }
            }
        }
        parent::initPageHeaderToolbar();
        $this->context->smarty->clearAssign('help_link');
//        $this->context->smarty->assign('help_link', 'index.php?controller=' . Tools::getValue('controller') . '?token=' . $this->token . '&ajax=1&action=OpenHelp');
    }

    private function renderMessages()
    {
        $ret = '';
        if (count($this->_errors)) {
            $err = '';
            foreach ($this->_errors as $error) {
                $err .= '<p>' . $error . '</p>';
            }
            $ret .= '<div class="alert alert-danger">' . $err . '</div>';
        }
        if (count($this->_msg)) {
            $msg = '';
            foreach ($this->_msg as $message) {
                $msg .= '<p>' . $message . '</p>';
            }
            $ret .= '<div class="alert alert-success">' . $msg . '</div>';
        }

        return $ret;
    }

    private function renderTestResult()
    {
        $ret = '';
        if ($this->_test_result != '') {
            $ret .= '<div id="codwfeeplus_test_result_panel" class="panel codwfeeplus_test_panel col-lg-12"><div class="panel-heading">'
                    . '<i class="icon-money"></i>       Total Cost <span class="badge">' . $this->_test_totfee . $this->_defCurrencySuffix . '</span></div>';
            $ret .= $this->_test_result . '</div>';
        }

        return $ret;
    }

    public function renderView()
    {
        $this->_html = '';
        $this->path = _PS_MODULE_DIR_ . $this->module->name . '/';
        $this->context->controller->addCSS($this->path . 'views/css/style-admin.css');
        $this->context->controller->addJS($this->path . 'views/js/admin.js');

        if (!$this->module->active) {
            $this->_html .= $this->renderModuleInactiveWarning('all');

            return $this->_html;
        }
        $render_normal = true;
        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            $render_normal = false;
        }

        if ($this->_display_mode == 'view_log') {
            $this->_html .= $this->renderMessages();
            if ($render_normal) {
                $this->_html .= $this->renderOrderList();
            } else {
                $this->_html .= $this->renderMultistoreInvalidSelection($this->_display_mode);
            }
        } elseif ($this->_display_mode == 'cond_form') {
            $this->_html .= $this->renderMessages();
            if ($render_normal) {
                $this->_html .= $this->renderFormConditions($this->_condForm_id, $this->_condForm_getfrompost);
            } else {
                $this->_html .= $this->renderMultistoreInvalidSelection($this->_display_mode);
            }
        } else {
            $this->_html .= $this->renderMessages();
            $this->_html .= $this->renderHelpForm(false, $this->_checkupdate, $this->_hide_helpForm);
            if ($render_normal) {
                $this->_html .= $this->renderConfigForm($this->_hide_configForm, $this->_confForm_getfrompost);
                $this->_html .= $this->renderConditionsList();
                $this->_html .= $this->renderTestForm($this->_hide_testForm);
                $this->_html .= $this->renderTestResult();
            } else {
                $this->_html .= $this->renderMultistoreInvalidSelection($this->_display_mode);
            }
        }

        return $this->_html;
    }

    public function postProcess()
    {
        $this->_errors = array();
        $this->_msg = array();
        $this->_condForm_id = null;
        $this->_condForm_getfrompost = false;
        $this->_confForm_getfrompost = false;
        $this->_display_mode = 'default';
        $this->_test_result = '';
        $this->_test_totfee = 0;
        $this->_hide_testForm = true;
        $this->_hide_configForm = true;
        $this->_hide_helpForm = true;

        if (Configuration::get('SG_CODWFEEPLUS_AUTO_UPDATE')) {
            $this->_checkupdate = true;
        } else {
            $this->_checkupdate = false;
        }

        if (Tools::isSubmit('view_trans_log')) {
            $this->fields_list = $this->getOrderList();
            $this->list_id = 'codwfeeplus_transactions';
            $this->_defaultOrderBy = 'codwfeeplus_datetime';
            $this->_defaultOrderWay = 'desc';
            $this->_pagination = array(20, 50, 100, 300, 1000);
            $this->_default_pagination = 50;

            $limit = null;
            if (isset($this->context->cookie->{$this->list_id . '_pagination'}) && $this->context->cookie->{$this->list_id . '_pagination'}) {
                $limit = $this->context->cookie->{$this->list_id . '_pagination'};
            } else {
                $limit = $this->_default_pagination;
            }

            $limit = (int) Tools::getValue($this->list_id . '_pagination', $limit);
            if (in_array($limit, $this->_pagination) && $limit != $this->_default_pagination) {
                $this->context->cookie->{$this->list_id . '_pagination'} = $limit;
            } else {
                unset($this->context->cookie->{$this->list_id . '_pagination'});
            }

            $start = 0;
            if ((int) Tools::getValue('submitFilter' . $this->list_id)) {
                $start = ((int) Tools::getValue('submitFilter' . $this->list_id) - 1) * $limit;
            } elseif (empty($start) && isset($this->context->cookie->{$this->list_id . '_start'}) && Tools::isSubmit('export' . $this->table)) {
                $start = $this->context->cookie->{$this->list_id . '_start'};
            }

// Either save or reset the offset in the cookie
            if ($start) {
                $this->context->cookie->{$this->list_id . '_start'} = $start;
            } elseif (isset($this->context->cookie->{$this->list_id . '_start'})) {
                unset($this->context->cookie->{$this->list_id . '_start'});
            }
            $this->processFilter();
            $this->_display_mode = 'view_log';
            if (Tools::isSubmit('submitResetcodwfeeplus_transactions')) {
                $this->processResetFilters();
            }
            $key = '';
            $way = '';
            if ($this->context->cookie->{'codwfeeplus' . $this->list_id . 'Orderby'} !== false) {
                $key = $this->context->cookie->{'codwfeeplus' . $this->list_id . 'Orderby'};
            } else {
                $key = $this->_defaultOrderBy;
            }
            if ($this->context->cookie->{'codwfeeplus' . $this->list_id . 'Orderway'} !== false) {
                $way = $this->context->cookie->{'codwfeeplus' . $this->list_id . 'Orderway'};
            } else {
                $way = $this->_defaultOrderWay;
            }
            $this->_orderList_parameters = array(
                'order' => array(
                    'key' => $key,
                    'way' => Tools::strtoupper($way),
                ),
                'pagination' => array(
                    'start' => $start,
                    'limit' => $limit,
                ),
            );
            if (Tools::isSubmit('deletecodwfeeplus_transactions') && Tools::isSubmit('id_codwfeeplus_trans')) {
                $ids = array(Tools::getValue('id_codwfeeplus_trans'));
                $this->bulkDeleteOrders($ids);
            }
            if (Tools::isSubmit('submitBulkdeletecodwfeeplus_transactions') && Tools::isSubmit('codwfeeplus_transactionsBox')) {
                $ids = Tools::getValue('codwfeeplus_transactionsBox');
                $this->bulkDeleteOrders($ids);
            }
            if (Tools::isSubmit('codwfeeplus_transactions_pagination') && Tools::isSubmit('submitFiltercodwfeeplus_transactions')) {
                if (Tools::getValue('submitFiltercodwfeeplus_transactions') > 0) {
                    $this->_orderList_parameters['pagination']['page'] = Tools::getValue('submitFiltercodwfeeplus_transactions');
                }
                $this->_orderList_parameters['pagination']['selected_pagination'] = Tools::getValue('codwfeeplus_transactions_pagination');
            }
            if (Tools::isSubmit('submitFilter') || $this->context->cookie->{'submitFilter'} !== false) {
                
            }
        } elseif (Tools::isSubmit('submitCODwFeePlusConfig')) {
            if ($this->_validate_conf()) {
                $this->_postproccess_conf();
                $this->_display_mode = 'default';
                $this->_hide_configForm = false;
                if (Configuration::get('SG_CODWFEEPLUS_AUTO_UPDATE')) {
                    $this->_checkupdate = true;
                } else {
                    $this->_checkupdate = false;
                }
            } else {
                $this->_display_mode = 'default';
                $this->_hide_configForm = false;
                $this->_confForm_getfrompost = true;
            }
        } elseif (Tools::isSubmit('submitCODwFeePlus_add')) {
            $this->_display_mode = 'cond_form';
            $this->_condForm_id = null;
        } elseif (Tools::isSubmit('updatecodwfeeplus_conditions') && Tools::isSubmit('id_codwfeeplus_cond')) {
            $this->_display_mode = 'cond_form';
            $this->_condForm_id = Tools::getValue('id_codwfeeplus_cond');
        } elseif (Tools::isSubmit('submitCODwFeePlusConditions')) {
            if ($this->_validate_cond()) {
                $this->_postproccess_cond();
                $this->_display_mode = 'default';
            } else {
                $this->_display_mode = 'cond_form';
                $this->_condForm_id = null;
                $this->_condForm_getfrompost = true;
            }
        } elseif (Tools::isSubmit('statuscodwfeeplus_conditions') && Tools::isSubmit('id_codwfeeplus_cond')) {
            $this->_postproccess_toggleactive(Tools::getValue('id_codwfeeplus_cond'));
            $this->_display_mode = 'default';
        } elseif (Tools::isSubmit('deletecodwfeeplus_conditions') && Tools::isSubmit('id_codwfeeplus_cond')) {
            $this->_postproccess_delete(Tools::getValue('id_codwfeeplus_cond'));
            $this->_display_mode = 'default';
        } elseif (Tools::isSubmit('submitCODwFeePlusTest') && Tools::isSubmit('tstfrm_country') && Tools::isSubmit('tstfrm_carrier') && Tools::isSubmit('tstfrm_cartvalue') && Tools::isSubmit('tstfrm_carriervalue')) {
            if ($this->_validate_testForm()) {
                $this->_postproccess_testForm();
                $this->_hide_testForm = false;
                $this->_display_mode = 'default';
            } else {
                $this->_hide_testForm = false;
                $this->_display_mode = 'default';
            }
        } elseif (Tools::isSubmit('submitBulkactivatecodwfeeplus_conditions') && Tools::isSubmit('codwfeeplus_conditionsBox')) {
            $this->bulkActivate();
            $this->_display_mode = 'default';
        } elseif (Tools::isSubmit('submitBulkdeactivatecodwfeeplus_conditions') && Tools::isSubmit('codwfeeplus_conditionsBox')) {
            $this->bulkDeActivate();
            $this->_display_mode = 'default';
        } elseif (Tools::isSubmit('submitBulkdeletecodwfeeplus_conditions') && Tools::isSubmit('codwfeeplus_conditionsBox')) {
            $this->bulkDelete();
            $this->_display_mode = 'default';
        } elseif (Tools::isSubmit('submitBulkduplicatecodwfeeplus_conditions') && Tools::isSubmit('codwfeeplus_conditionsBox')) {
            $this->bulkDuplicateCondition();
            $this->_display_mode = 'default';
        } elseif (Tools::isSubmit('submitBulkexportcodwfeeplus_conditions') && Tools::isSubmit('codwfeeplus_conditionsBox')) {
            $this->bulkExportCondition();
            $this->_display_mode = 'default';
        } elseif (Tools::isSubmit('action') && Tools::getValue('action') == 'updatePositions') {
            $this->_display_mode = 'default';
            $this->ajaxProcessUpdatePositions();
        } elseif (Tools::isSubmit('submitCODwFeePlus_reset_product')) {
            $this->resetProduct();
            $this->_display_mode = 'default';
        } elseif (Tools::isSubmit('submitCODwFeePlusActionCopyToShop') && Tools::isSubmit('CODwFeePlusActionCopyToShop_shopId') && Tools::isSubmit('CODwFeePlusActionCopyToShop_condId')) {
            $shop_id = Tools::getValue('CODwFeePlusActionCopyToShop_shopId');
            $cond_id = Tools::getValue('CODwFeePlusActionCopyToShop_condId');
            $this->copyConditionToShop($shop_id, $cond_id);
            $this->_display_mode = 'default';
        } elseif (Tools::isSubmit('submitCODwFeePlusActionDuplicatecond') && Tools::isSubmit('CODwFeePlusActionDuplicatecond_condId')) {
            $cond_id = Tools::getValue('CODwFeePlusActionDuplicatecond_condId');
            $this->duplicateCondition($cond_id);
            $this->_display_mode = 'default';
        } elseif (Tools::isSubmit('submitCODwFeePlusActionExportcond') && Tools::isSubmit('CODwFeePlusActionExportcond_condId')) {
            $cond_id = Tools::getValue('CODwFeePlusActionExportcond_condId');
            $this->exportCondition($cond_id);
            $this->_display_mode = 'default';
        } elseif (Tools::isSubmit('codwfeeplus_check_update')) {
            $this->_checkupdate = true;
            $this->_hide_helpForm = false;
            $this->_display_mode = 'default';
        }

        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $shop_list = Shop::getShops(true, null, true);
                foreach ($shop_list as $value) {
                    $key = 'submitBulkcopytoshop' . $value . 'codwfeeplus_conditions';
                    if (Tools::isSubmit($key) && Tools::isSubmit('codwfeeplus_conditionsBox')) {
                        $this->bulkCopyToShop($value);
                        $this->_display_mode = 'default';
                    }
                }
            }
        }
    }

    public function initProcess()
    {
        $this->_display_mode = 'default';
        if (Tools::isSubmit('view_trans_log')) {
            $this->_display_mode = 'view_log';
        } elseif (Tools::isSubmit('submitCODwFeePlus_add')) {
            $this->_display_mode = 'cond_form';
        } elseif (Tools::isSubmit('updatecodwfeeplus_conditions') && Tools::isSubmit('id_codwfeeplus_cond')) {
            $this->_display_mode = 'cond_form';
        } elseif (Tools::isSubmit('submitCODwFeePlusConditions')) {
            if (!$this->_validate_cond()) {
                $this->_display_mode = 'cond_form';
            }
        }

        parent::initProcess();
    }

    private function duplicateCondition($cond_id)
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;
        $cond = new CODwFP($cond_id);
        $cond->id_codwfeeplus_cond = null;
        $cond->codwfeeplus_position = $cond->getMaxPosition() + 1;
        $ret &= $cond->saveToDB();
        if ($ret) {
            $this->_msg[] = $this->l('Successful update.');
        } else {
            $this->_errors[] = $this->l('Failed to update.');
        }

        return $ret;
    }

    private function importValidateParams($param)
    {
        $ret = true;
        if (is_array($param) && count($param)) {
            $c = new CODwFP();
            $ret &= $c->validate($param);
            unset($c);
        } else {
            $ret &= false;
        }
        return $ret;
    }

    private function importFixParams($param)
    {
        $ret = null;
        if (is_array($param) && count($param)) {
            $ret = $param;
//Fix description
            $ret['codwfeeplus_desc'] = stripslashes(urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($ret['codwfeeplus_desc']))));
//countries
            if (isset($ret['codwfeeplus_countries'])) {
                $arr = CODwFP::stringToArray($ret['codwfeeplus_countries']);
                $arr2 = array();
                $countries = Country::getCountries($this->context->language->id, true);
                $countries_id = array_column($countries, 'id_country');
                foreach ($arr as $value) {
                    if (in_array($value, $countries_id)) {
                        $arr2[] = $value;
                    }
                }
                $ret['codwfeeplus_countries'] = CODwFP::arrayToString($arr2);
            }
//zones
            if (isset($ret['codwfeeplus_zones'])) {
                $arr = CODwFP::stringToArray($ret['codwfeeplus_zones']);
                $arr2 = array();
                $zones = Zone::getZones(true);
                $zones_id = array_column($zones, 'id_zone');
                foreach ($arr as $value) {
                    if (in_array($value, $zones_id)) {
                        $arr2[] = $value;
                    }
                }
                $ret['codwfeeplus_zones'] = CODwFP::arrayToString($arr2);
            }
//carriers
            if (isset($ret['codwfeeplus_carriers'])) {
                $arr = CODwFP::stringToArray($ret['codwfeeplus_carriers']);
                $arr2 = array();
                $carriers = Carrier::getCarriers($this->context->language->id, false, false, false, null, Carrier::ALL_CARRIERS);
                $carriers_id = array_column($carriers, 'id_carrier');
                foreach ($arr as $value) {
                    if (in_array($value, $carriers_id)) {
                        $arr2[] = $value;
                    }
                }
                $ret['codwfeeplus_carriers'] = CODwFP::arrayToString($arr2);
            }
//groups
            if (isset($ret['codwfeeplus_groups'])) {
                if (Group::isFeatureActive()) {
                    $arr = CODwFP::stringToArray($ret['codwfeeplus_groups']);
                    $arr2 = array();
                    $groups = Group::getGroups($this->context->language->id);
                    $groups_id = array_column($groups, 'id_group');
                    foreach ($arr as $value) {
                        if (in_array($value, $groups_id)) {
                            $arr2[] = $value;
                        }
                    }
                    $ret['codwfeeplus_groups'] = CODwFP::arrayToString($arr2);
                } else {
                    $ret['codwfeeplus_groups'] = '';
                }
            }
//categories
            if (isset($ret['codwfeeplus_categories'])) {
                $arr = CODwFP::stringToArray($ret['codwfeeplus_categories']);
                $arr2 = array();
                $categories = Category::getSimpleCategories($this->context->language->id);
                $categories_id = array_column($categories, 'id_category');
                foreach ($arr as $value) {
                    if (in_array($value, $categories_id)) {
                        $arr2[] = $value;
                    }
                }
                $ret['codwfeeplus_categories'] = CODwFP::arrayToString($arr2);
            }
//manufacturers
            if (isset($ret['codwfeeplus_manufacturers'])) {
                $arr = CODwFP::stringToArray($ret['codwfeeplus_manufacturers']);
                $arr2 = array();
                $manufacturers = Manufacturer::getManufacturers();
                $manufacturers_id = array_column($manufacturers, 'id_manufacturer');
                foreach ($arr as $value) {
                    if (in_array($value, $manufacturers_id)) {
                        $arr2[] = $value;
                    }
                }
                $ret['codwfeeplus_manufacturers'] = CODwFP::arrayToString($arr2);
            }
//suppliers
            if (isset($ret['codwfeeplus_suppliers'])) {
                $arr = CODwFP::stringToArray($ret['codwfeeplus_suppliers']);
                $arr2 = array();
                $suppliers = Supplier::getSuppliers();
                $suppliers_id = array_column($suppliers, 'id_supplier');
                foreach ($arr as $value) {
                    if (in_array($value, $suppliers_id)) {
                        $arr2[] = $value;
                    }
                }
                $ret['codwfeeplus_suppliers'] = CODwFP::arrayToString($arr2);
            }
        }
        return $ret;
    }

    private function importConditions($inarr)
    {
        $ret = true;
        $k = 0;
        if (is_array($inarr)) {
            if (isset($inarr['version']) && Tools::version_compare($this->module->version, $inarr['version'], '>=')) {
                if (isset($inarr['data']) && count($inarr['data'])) {
                    foreach ($inarr['data'] as $key => $value) {
                        if ($this->importValidateParams($value)) {
                            $value = $this->importFixParams($value);
                            $c = new CODwFP();
                            $c->loadFromArray($value);
                            $c->saveToDB();
                        } else {
                            $ret &= false;
                            $this->_errors[] = $this->l('Invalid import condition values #.') . $key;
                        }
                    }
                } else {
                    $ret &= false;
                    $this->_errors[] = $this->l('Invalid import condition data.');
                }
            } else {
                $ret &= false;
                $this->_errors[] = $this->l('Invalid import condition data.');
            }
        } else {
            $ret &= false;
            $this->_errors[] = $this->l('Invalid import condition data.');
        }
        return $ret;
    }

    private function exportCondition($in_arr)
    {
        if (!$in_arr) {
            $this->_errors[] = $this->l('Failed to export conditions.');
            return;
        }
        if (!is_array($in_arr)) {
            $in_arr = array($in_arr);
        }
        $out = array(
            'version' => $this->module->version,
            'count' => count($in_arr),
            'data' => array(),
        );
        foreach ($in_arr as $value) {
            $c = new CODwFP($value);
            $out['data'][] = $c->exportConditionArray();
            unset($c);
        }
        $out_enc = json_encode($out);
        $datetime = date('Ymd_His');
        header("Content-type: text/plain");
        header("Content-Disposition: attachment; filename=codwfeeplus_export_{$datetime}.json");
        echo $out_enc;
        exit();
    }

    public function bulkDuplicateCondition()
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;
        $id_array = Tools::getValue('codwfeeplus_conditionsBox');
        if (is_array($id_array)) {
            foreach ($id_array as $value) {
                $cond = new CODwFP($value);
                $cond->id_codwfeeplus_cond = null;
                $cond->codwfeeplus_position = $cond->getMaxPosition() + 1;
                $ret &= $cond->saveToDB();
            }
        }
        if ($ret) {
            $this->_msg[] = $this->l('Successful update.');
        } else {
            $this->_errors[] = $this->l('Failed to update.');
        }

        return $ret;
    }

    public function bulkExportCondition()
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;
        $id_array = Tools::getValue('codwfeeplus_conditionsBox');
        if (is_array($id_array)) {
            $ret &= $this->exportCondition($id_array);
        } else {
            $ret &= false;
        }
        if ($ret) {
            $this->_msg[] = $this->l('Successfully exported conditions.');
        } else {
            $this->_errors[] = $this->l('Failed to export conditions.');
        }
    }

    private function copyConditionToShop($shop_id, $cond_id)
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;
        $cond = new CODwFP($cond_id);
        $cond->id_codwfeeplus_cond = null;
        $cond->codwfeeplus_shop = $shop_id;
        $cond->codwfeeplus_position = $cond->getMaxPosition($shop_id) + 1;
        $ret &= $cond->saveToDB();
        if ($ret) {
            $this->_msg[] = $this->l('Successful update.');
        } else {
            $this->_errors[] = $this->l('Failed to update.');
        }

        return $ret;
    }

    public function bulkCopyToShop($shop_id)
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;
        $id_array = Tools::getValue('codwfeeplus_conditionsBox');
        if (is_array($id_array)) {
            foreach ($id_array as $value) {
                $cond = new CODwFP($value);
                $cond->id_codwfeeplus_cond = null;
                $cond->codwfeeplus_shop = $shop_id;
                $cond->codwfeeplus_position = $cond->getMaxPosition($shop_id) + 1;
                $ret &= $cond->saveToDB();
            }
        }
        if ($ret) {
            $this->_msg[] = $this->l('Successful update.');
        } else {
            $this->_errors[] = $this->l('Failed to update.');
        }

        return $ret;
    }

    public function bulkDelete()
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;
        $id_array = Tools::getValue('codwfeeplus_conditionsBox');
        if (is_array($id_array)) {
            foreach ($id_array as $value) {
                $ret &= Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'codwfeeplus_conditions` WHERE `id_codwfeeplus_cond`=' . $value);
            }
        }
        if ($ret) {
            $this->_msg[] = $this->l('Successful update.');
        } else {
            $this->_errors[] = $this->l('Failed to update.');
        }

        return $ret;
    }

    public function bulkDeActivate()
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;
        $id_array = Tools::getValue('codwfeeplus_conditionsBox');
        if (is_array($id_array)) {
            foreach ($id_array as $value) {
                $ret &= Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'codwfeeplus_conditions` '
                        . 'SET `codwfeeplus_active`=0 WHERE `id_codwfeeplus_cond`=' . $value);
            }
        }
        if ($ret) {
            $this->_msg[] = $this->l('Successful update.');
        } else {
            $this->_errors[] = $this->l('Failed to update.');
        }

        return $ret;
    }

    public function bulkActivate()
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;
        $id_array = Tools::getValue('codwfeeplus_conditionsBox');
        if (is_array($id_array)) {
            foreach ($id_array as $value) {
                $ret &= Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'codwfeeplus_conditions` '
                        . 'SET `codwfeeplus_active`=1 WHERE `id_codwfeeplus_cond`=' . $value);
            }
        }
        if ($ret) {
            $this->_msg[] = $this->l('Successful update.');
        } else {
            $this->_errors[] = $this->l('Failed to update.');
        }

        return $ret;
    }

//HELP FORM

    public function renderHelpForm($ajax = false, $check_update = false, $hide = true)
    {
        $ret = '';
        $update_status = array(
            'res' => '',
            'cur_version' => '',
            'download_link' => '',
            'info_link' => Configuration::get('SG_CODWFEEPLUS_INFO_LINK'),
            'github_link' => Configuration::get('SG_CODWFEEPLUS_GITHUB_LINK'),
            'out' => '',
        );
        if ($check_update) {
            $ret = $this->module->getUpdateStatus();
            if (Tools::strpos($ret, 'error') === false) {
                $update_status['res'] = $this->module->_updatestatus['res'];
                $update_status['cur_version'] = $this->module->_updatestatus['cur_version'];
                $update_status['download_link'] = $this->module->_updatestatus['download_link'];
                $update_status['info_link'] = $this->module->_updatestatus['info_link'];
                $update_status['github_link'] = $this->module->_updatestatus['github_link'];
            } else {
                $update_status['res'] = 'error';
                if ($ret == 'error_res') {
                    $update_status['out'] = $this->l('Update site reported an error.');
                } elseif ($ret == 'error_resp') {
                    $update_status['out'] = $this->l('Invalid response from the update site.');
                } elseif ($ret == 'error_url') {
                    $update_status['out'] = $this->l('Update site could not be reached.');
                }
            }
        }
        $this->context->smarty->assign(array(
            'help_title' => $this->l('INFO'),
            'help_sub' => $this->l('click to toggle'),
            'module_name' => $this->module->displayName,
            'module_version' => $this->module->version,
            'help_ajax' => $ajax,
            'css_file' => _PS_MODULE_DIR_ . '/' . $this->module->name . '/views/css/style-admin.css',
            'update' => $update_status,
            'href' => $this->context->link->getAdminLink('AdminCODwFeePlus', true),
            'hide' => $hide,
        ));
        $lang_iso = Tools::strtolower(trim($this->context->language->iso_code));

        if (Tools::file_exists_cache(_PS_MODULE_DIR_ . '/' . $this->module->name . '/views/templates/admin/help_' . $lang_iso . '.tpl')) {
            $ret = $this->context->smarty->fetch(_PS_MODULE_DIR_ . '/' . $this->module->name . '/views/templates/admin/help_' . $lang_iso . '.tpl');
        } else {
            $ret = $this->context->smarty->fetch(_PS_MODULE_DIR_ . '/' . $this->module->name . '/views/templates/admin/help_en.tpl');
        }


        return $ret;
    }

//TEST FORM

    public function renderTestForm($hide = true)
    {
        $test_field_values = $this->getTestFieldsValues();
        $carriers = Carrier::getCarriers($this->context->language->id, false, false, false, null, Carrier::ALL_CARRIERS);
        $carriers_list = array();
        foreach ($carriers as $value) {
            $carriers_list[] = array(
                'id_option' => (int) $value['id_carrier'],
                'name' => $value['name'],
            );
        }
        $groups_list = array();
        if (Group::isFeatureActive()) {
            $groups = Group::getGroups($this->context->language->id);

            foreach ($groups as $value) {
                $groups_list[] = array(
                    'id_option' => (int) $value['id_group'],
                    'name' => $value['name'],
                );
            }
            $groupsFileds = array(
                'type' => 'select',
                'multiple' => true,
                'label' => $this->l('Customer Group'),
                'name' => 'tstfrm_group[]',
                'options' => array(
                    'query' => $groups_list,
                    'id' => 'id_option',
                    'name' => 'name',
                ),
                'hint' => $this->l('The customer\'s group.'),
            );
        } else {
            $groupsFileds = array(
                'type' => 'html',
                'html_content' => '<p class="codwfeeplus_nogroup_error">' . $this->l('The group feature is not active on this shop.') . '</p>',
                'label' => $this->l('Customer Group'),
                'name' => 'tstfrm_group[]',
            );
        }

        $emptyManuf_text = $this->l('Empty manufacturer');
        $manuf_label = $this->l('Cart Manufacturers');
        $manuf_hint = $this->l('The manufacturers of the products in the cart.');
        if ($this->module->is17) {
            $emptyManuf_text = $this->l('Empty brand');
            $manuf_label = $this->l('Cart Brands');
            $manuf_hint = $this->l('The brands of the products in the cart.');
        }
        $manufacturers_list = array(
            0 => array(
                'id_option' => 0,
                'name' => $emptyManuf_text,
            )
        );
        $manufacturers = Manufacturer::getManufacturers();
        foreach ($manufacturers as $value) {
            $manufacturers_list[] = array(
                'id_option' => (int) $value['id_manufacturer'],
                'name' => $value['name'],
            );
        }

        $suppliers_list = array(
            0 => array(
                'id_option' => 0,
                'name' => $this->l('Empty supplier'),
            )
        );
        $suppliers = Supplier::getSuppliers();
        foreach ($suppliers as $value) {
            $suppliers_list[] = array(
                'id_option' => (int) $value['id_supplier'],
                'name' => $value['name'],
            );
        }

        $root_category = Category::getRootCategory()->id;

        $tree = new HelperTreeCategories('codwfeeplus_test_cat_tree');
        $tree->setUseCheckBox(true)
                ->setUseSearch(false)
                ->setIdTree('codwfeeplus_test_cat_tree')
                ->setFullTree(true)
                ->setChildrenOnly(true)
                ->setNoJS(true)
                ->setRootCategory($root_category)
                ->setInputName('test_categoryBox')
                ->setSelectedCategories($test_field_values['tstfrm_category']);
        $categoryTree = $tree->render();
//token changed because of the category tree... So change it back.
        $this->context->smarty->assign('token', $this->token);

        if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')) {
            $countries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        } else {
            $countries = Country::getCountries($this->context->language->id, true);
        }
        $country_list = array();
        foreach ($countries as $value) {
            $country_list[] = array(
                'id_option' => (int) $value['id_country'],
                'name' => $value['name'],
            );
        }
        $fields_form = array(
            'form' => array(
                'id_form' => 'codwfeeplus_testingform',
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Total Cart Value'),
                        'name' => 'tstfrm_cartvalue',
                        'suffix' => $this->_defCurrencySuffix,
                        'class' => 'fixed-width-sm',
                        'hint' => $this->l('The total value of the products in the cart.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Carrier Fee Value'),
                        'name' => 'tstfrm_carriervalue',
                        'suffix' => $this->_defCurrencySuffix,
                        'class' => 'fixed-width-sm',
                        'hint' => $this->l('The fee of the carrier.'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Delivery Country'),
                        'name' => 'tstfrm_country',
                        'options' => array(
                            'query' => $country_list,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $this->l('The country of the delivery address.'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Delivery Carrier'),
                        'name' => 'tstfrm_carrier',
                        'options' => array(
                            'query' => $carriers_list,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $this->l('The selected carrier to deliver the products.'),
                    ),
                    $groupsFileds,
                    array(
                        'type' => 'categories_select',
                        'label' => $this->l('Category List'),
                        'name' => 'tstfrm_category',
                        'category_tree' => $categoryTree,
                        'hint' => $this->l('The categories that the products belong to. Keep in mind that a producy might belong to more than one category.'),
                    ),
                    array(
                        'type' => 'select',
                        'multiple' => true,
                        'label' => $manuf_label,
                        'name' => 'tstfrm_manufacturers[]',
                        'options' => array(
                            'query' => $manufacturers_list,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $manuf_hint,
                    ),
                    array(
                        'type' => 'select',
                        'multiple' => true,
                        'label' => $this->l('Cart Suppliers'),
                        'name' => 'tstfrm_suppliers[]',
                        'options' => array(
                            'query' => $suppliers_list,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $this->l('The suppliers of the products in the cart.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Test'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = true;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->submit_action = 'submitCODwFeePlusTest';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminCODwFeePlus', false);
        $helper->token = Tools::getAdminTokenLite('AdminCODwFeePlus');
        $helper->tpl_vars = array(
            'fields_value' => $test_field_values,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        $js_hide = '';
        $span_hide = '';
        if ($this->_testForm_hideable) {
            $js_hide = '  onclick="$(\'#codwfeeplus_testingform\').slideToggle();"';
            $span_hide = '  <span style="text-transform: none;font-style: italic;">(' . $this->l('click to toggle') . ')</span>';
        }

        $ret = '';
        $ret .= '<div class="bootstrap" id="codwfeeplustestblock">'
                . '<div class="panel">'
                . '<div class="panel-heading"' . $js_hide . '>'
                . '<i class="icon-tasks"></i>'
                . '   ' . $this->l('Test conditions') . $span_hide
                . '</div>';

        $ret .= $helper->generateForm(array('form' => $fields_form));

        $ret .= '</div></div>';
        if ($hide && $this->_testForm_hideable) {
            $ret .= '<script type="text/javascript">$(\'#codwfeeplus_testingform\').hide();</script>';
        }

        return $ret;
    }

    protected function getTestFieldsValues()
    {
        $cat = array();
        if (Tools::isSubmit('test_categoryBox')) {
            $cat = (Tools::getValue('test_categoryBox'));
        }
        $ret = array(
            'tstfrm_carrier' => Tools::getValue('tstfrm_carrier', 0),
            'tstfrm_shop' => Tools::getValue('tstfrm_shop', 0),
            'tstfrm_group[]' => Tools::getValue('tstfrm_group', 0),
            'tstfrm_country' => Tools::getValue('tstfrm_country', 0),
            'tstfrm_cartvalue' => Tools::getValue('tstfrm_cartvalue', 0),
            'tstfrm_carriervalue' => Tools::getValue('tstfrm_carriervalue', 0),
            'tstfrm_category' => $cat,
            'tstfrm_manufacturers[]' => Tools::getValue('tstfrm_manufacturers', -1),
            'tstfrm_suppliers[]' => Tools::getValue('tstfrm_suppliers', -1),
        );

        return $ret;
    }

//CONFIG FORM

    public function renderConfigForm($hide = false, $getFromPost = false)
    {
        $test_field_values = $this->getConfigFieldsValues($getFromPost);
        $options = array(
            array(
                'id_option' => 0,
                'name' => $this->l('Apply the highest in the list, matching fee'),
            ),
            array(
                'id_option' => 1,
                'name' => $this->l('Add all matching fees'),
            ),
        );
        $options_integration = array(
            array(
                'id_option' => 0,
                'name' => $this->l('Defined by first successful condition'),
            ),
            array(
                'id_option' => 1,
                'name' => $this->l('Add to carrier\'s fee'),
            ),
            array(
                'id_option' => 2,
                'name' => $this->l('Add a COD product to the order'),
            ),
        );
        $fields_form = array(
            'form' => array(
                'id_form' => 'codwfeeplus_configform',
                'input' => array(
                    array(
                        'type' => 'html',
                        'name' => 'CODWFEEPLUS_PRODUCT_INFO',
                        'html_content' => $this->getProductStatus(),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Auto check for updates'),
                        'name' => 'CODWFEEPLUS_AUTO_UPDATE',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                        'hint' => $this->l('Toggle whether check for updates when this page is loaded.'),
                    ),
                    array(
                        'type' => 'html',
                        'html_content' => '<hr class="codwfeeplus_form_hr">',
                        'col' => '12',
                        'label' => '',
                        'name' => 'sep',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Integration global'),
                        'name' => 'CODWFEEPLUS_INTEGRATION_WAY',
                        'options' => array(
                            'query' => $options_integration,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $this->l('How to integrade the COD fee to the order.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('COD product title'),
                        'name' => 'CODWFEEPLUS_PRODUCT_TITLE',
                        'lang' => true,
                        'hint' => $this->l('The name of the COD product that will be displayed in the order.'),
                        'form_group_class' => 'codwfeeplus_product_details',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('COD product reference'),
                        'name' => 'CODWFEEPLUS_PRODUCT_REFERENCE',
                        'hint' => $this->l('The reference of the COD product that will be displayed in the order.'),
                        'class' => 'fixed-width-xxl',
                        'form_group_class' => 'codwfeeplus_product_details',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Store orders'),
                        'name' => 'CODWFEEPLUS_KEEPTRANSACTIONS',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'value' => 1,
                            ),
                            array(
                                'value' => 0,
                            ),
                        ),
                        'hint' => $this->l('Toggle whether to keep a log of orders done with this module.'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Behaviour'),
                        'name' => 'CODWFEEPLUS_BEHAVIOUR',
                        'options' => array(
                            'query' => $options,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $this->l('How to calculate the final fee.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
        if ($this->module->is17) {
            $fields_form['form']['input'][] = array(
                'type' => 'html',
                'html_content' => '<hr class="codwfeeplus_form_hr">',
                'col' => '12',
                'label' => '',
                'name' => 'sep',
            );
            $fields_form['form']['input'][] = array(
                'type' => 'switch',
                'label' => $this->l('Enable logo'),
                'name' => 'CODWFEEPLUS_LOGO_ENABLED',
                'is_bool' => true,
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    ),
                ),
                'hint' => $this->l('Toggle whether to enable the logo display on the payment page.'),
            );
            $fields_form['form']['input'][] = array(
                'type' => 'file',
                'label' => $this->l('Payment logo'),
                'name' => 'CODWFEEPLUS_LOGO_FILENAME_17',
                'hint' => $this->l('Select a logo for the payment page.'),
            );
            $fields_form['form']['input'][] = array(
                'type' => 'html',
                'name' => 'CODWFEEPLUS_LOGO_FILENAME_17_PREVIEW',
                'html_content' => '<div class="codwfeeplus_logo_preview"><img src="' . Media::getMediaPath(_PS_MODULE_DIR_ . $this->module->name . '/views/img/' . Configuration::get('SG_CODWFEEPLUS_LOGO_FILENAME_17')) . '"></img></div>',
            );
        }
        $fields_form['form']['input'][] = array(
            'type' => 'html',
            'html_content' => '<hr class="codwfeeplus_form_hr">',
            'col' => '12',
            'label' => '',
            'name' => 'sep',
        );
        $fields_form['form']['input'][] = array(
            'type' => 'file',
            'label' => $this->l('Import Conditions'),
            'name' => 'CODWFEEPLUS_CONDITIONS_IMPORT',
            'hint' => $this->l('Select a previously exported file, to import conditions'),
        );
        $helper = new HelperForm();
        $helper->show_toolbar = true;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->submit_action = 'submitCODwFeePlusConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminCODwFeePlus', false);
        $helper->token = Tools::getAdminTokenLite('AdminCODwFeePlus');
        $helper->tpl_vars = array(
            'fields_value' => $test_field_values,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        $js_hide = '';
        $span_hide = '';
        if ($this->_configform_hideable) {
            $js_hide = ' onclick="$(\'#codwfeeplus_configform\').slideToggle();"';
            $span_hide = '  <span style="text-transform: none;font-style: italic;">(' . $this->l('click to toggle') . ')</span>';
        }

        $ret = '';
        $ret .= '<div class="bootstrap" id="codwfeeplusconfigblock">'
                . '<div class="panel">'
                . '<div class="panel-heading"' . $js_hide . '>'
                . '<i class="icon-cogs"></i>'
                . '   ' . $this->l('Configuration') . $span_hide . '</div>';

        $ret .= $helper->generateForm(array('form' => $fields_form));

        $ret .= '</div></div>';
        if ($hide && $this->_configform_hideable) {
            $ret .= '<script type="text/javascript">$(\'#codwfeeplus_configform\').hide();</script>';
        }

        return $ret;
    }

    protected function getConfigFieldsValues($getfrompost = false)
    {
        $ret = array();
        if ($getfrompost) {
            $ret['CODWFEEPLUS_BEHAVIOUR'] = Tools::getValue('CODWFEEPLUS_BEHAVIOUR', 0);
            $ret['CODWFEEPLUS_KEEPTRANSACTIONS'] = Tools::getValue('CODWFEEPLUS_KEEPTRANSACTIONS', 1);
            $ret['CODWFEEPLUS_INTEGRATION_WAY'] = Tools::getValue('CODWFEEPLUS_INTEGRATION_WAY', 0);
            $ret['CODWFEEPLUS_PRODUCT_REFERENCE'] = Tools::getValue('CODWFEEPLUS_PRODUCT_REFERENCE', 'COD');
            $ret['CODWFEEPLUS_AUTO_UPDATE'] = Tools::getValue('CODWFEEPLUS_AUTO_UPDATE', 0);
            $ret['CODWFEEPLUS_LOGO_ENABLED'] = Tools::getValue('CODWFEEPLUS_LOGO_ENABLED', 0);
            foreach (Language::getLanguages(true) as $lang) {
                $ret['CODWFEEPLUS_PRODUCT_TITLE'][$lang['id_lang']] = Tools::getValue('CODWFEEPLUS_PRODUCT_TITLE_' . $lang['id_lang']);
            }
        } else {
            $ret['CODWFEEPLUS_BEHAVIOUR'] = Configuration::get('SG_CODWFEEPLUS_BEHAVIOUR');
            $ret['CODWFEEPLUS_KEEPTRANSACTIONS'] = Configuration::get('SG_CODWFEEPLUS_KEEPTRANSACTIONS');
            $ret['CODWFEEPLUS_INTEGRATION_WAY'] = Configuration::get('SG_CODWFEEPLUS_INTEGRATION_WAY');
            $ret['CODWFEEPLUS_PRODUCT_REFERENCE'] = Configuration::get('SG_CODWFEEPLUS_PRODUCT_REFERENCE');
            $ret['CODWFEEPLUS_AUTO_UPDATE'] = Configuration::get('SG_CODWFEEPLUS_AUTO_UPDATE');
            $ret['CODWFEEPLUS_LOGO_ENABLED'] = Configuration::get('SG_CODWFEEPLUS_LOGO_ENABLED');
            foreach (Language::getLanguages(true) as $lang) {
                $ret['CODWFEEPLUS_PRODUCT_TITLE'][$lang['id_lang']] = Configuration::get('SG_CODWFEEPLUS_PRODUCT_TITLE', $lang['id_lang']);
            }
        }

        return $ret;
    }

//LOG LIST
    protected function renderOrderList()
    {
        $helper = new HelperList();

        $helper->title = $this->l('Orders');
        $helper->controller_name = $this->controller_name;
        $helper->table = 'codwfeeplus_transactions';
        $helper->no_link = true;
        $helper->orderBy = $this->_orderList_parameters['order']['key'];
        $helper->orderWay = $this->_orderList_parameters['order']['way'];
        $helper->shopLinkType = '';
        $helper->identifier = 'id_codwfeeplus_trans';
        $helper->actions = array('preview', 'delete');

        $values = $this->getOrderListValues();
        $helper->listTotal = $this->_listTotal;
        $helper->_default_pagination = $this->_default_pagination;
        $helper->_pagination = $this->_pagination;
        $helper->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ),
        );
        $helper->tpl_vars = array('icon' => 'icon-list');

        $helper->token = Tools::getAdminTokenLite('AdminCODwFeePlus');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminCODwFeePlus', false) . '&view_trans_log';
        $helper->override_folder = '_configure/';

        return $helper->generateList($values, $this->getOrderList());
    }

    public function getOrderListCount()
    {
        $where_shop = '';
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $where_shop = ' WHERE `codwfeeplus_shop`=' . Shop::getContextShopID();
            }
        }
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'codwfeeplus_transactions`' . $where_shop;
        $ret = Db::getInstance()->executeS($sql);

        return $ret[0]['COUNT(*)'];
    }

    public function getOrderListValues()
    {
        $key = '';
        if ($this->_orderList_parameters['order']['key'] == 'codwfeeplus_customer') {
            $key = 'codwfeeplus_customer_name';
        } else {
            $key = $this->_orderList_parameters['order']['key'];
        }

        $sql = 'SELECT SQL_CALC_FOUND_ROWS a.*,
            CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `codwfeeplus_customer_name`
                FROM `' . _DB_PREFIX_ . 'codwfeeplus_transactions` a
                LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = a.`codwfeeplus_customer_id`)';
        $where_shop = '';
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $where_shop = '`codwfeeplus_shop`=' . Shop::getContextShopID();
            }
        }
        $where = $this->getOrderListFilter();
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($where_shop != '') {
            if ($where == '') {
                $where_shop = ' WHERE ' . $where_shop;
            } else {
                $where_shop = ' AND ' . $where_shop;
            }
        }

        $where = $where . $where_shop;
        $orderby = ' ORDER BY `' . $key . '` ' . $this->_orderList_parameters['order']['way'];
        $limit_num = (int) $this->_orderList_parameters['pagination']['limit'];
        $start = (int) $this->_orderList_parameters['pagination']['start'];
        $limit = ' LIMIT ' . $start . ',' . $limit_num;
        $all = $sql . $where . $orderby . $limit;
        $trans_db = Db::getInstance()->executeS($all);
        $this->_listTotal = Db::getInstance()->executeS('SELECT FOUND_ROWS()')[0]['FOUND_ROWS()'];
        $trans = array();
        foreach ($trans_db as $value) {
            $trans[] = array(
                'id_codwfeeplus_trans' => $value['id_codwfeeplus_trans'],
                'codwfeeplus_datetime' => $value['codwfeeplus_datetime'],
                'codwfeeplus_customer' => array('id' => $value['codwfeeplus_customer_id'], 'name' => $value['codwfeeplus_customer_name']),
                'codwfeeplus_order_id' => $value['codwfeeplus_order_id'],
                'codwfeeplus_fee' => $value['codwfeeplus_fee'],
                'codwfeeplus_cart_total' => $value['codwfeeplus_cart_total'],
                'codwfeeplus_result' => $value['codwfeeplus_result'],
                'id_myButton' => $value['id_codwfeeplus_trans'],
                'id_currency' => $this->getCurrencyIdFromOrder($value['codwfeeplus_order_id']),
            );
        }

        return $trans;
    }

    private function getCurrencyIdFromOrder($inOrderId)
    {
        $o = new Order($inOrderId);
        $ret = $o->id_currency;
        unset($o);
        return $ret;
    }

    private function getOrderListFilter()
    {
        if ($this->_filter) {
            $ret = '1 ' . $this->_filter;
        } else {
            $ret = '';
        }
        $search = '`codwfeeplus_customer`';
        $replace = 'CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`)';
        $ret = str_replace($search, $replace, $ret);

        return $ret;
    }

    public function getOrderList()
    {
        return array(
            'id_codwfeeplus_trans' => array('title' => $this->l('ID'), 'type' => 'int', 'align' => 'center', 'orderby' => true),
            'codwfeeplus_datetime' => array('title' => $this->l('Date'), 'type' => 'datetime', 'orderby' => true),
            'codwfeeplus_customer' => array('title' => $this->l('Customer'), 'callback' => 'callbackOrderListViewCustomer', 'align' => 'center', 'orderby' => true),
            'codwfeeplus_order_id' => array('title' => $this->l('Order ID'), 'type' => 'int', 'callback' => 'callbackOrderListViewOrder', 'align' => 'center', 'orderby' => true),
            'codwfeeplus_fee' => array('title' => $this->l('Fee'), 'type' => 'price', 'currency' => true, 'align' => 'center', 'orderby' => true),
            'codwfeeplus_cart_total' => array('title' => $this->l('Cart Total'), 'type' => 'price', 'currency' => true, 'align' => 'center', 'orderby' => true),
            'id_myButton' => array('title' => $this->l('Details'), 'align' => 'center', 'orderby' => false, 'search' => false, 'callback' => 'callbackOrderListViewDetails'),
        );
    }

    public function callbackOrderListViewCustomer($cust)
    {
        return '<span>' . $cust['name'] . '</span>
            <span style="float: right;">
                <span>
                    <a href="' . $this->context->link->getAdminLink('AdminCustomers', true) . '&id_customer=' . $cust['id'] . '&viewcustomer">
                        <i class="icon-search-plus"></i>
                    </a>
                </span>
            </span>';
    }

    public function callbackOrderListViewOrder($id)
    {
        return '<span>' . $id . '</span>
            <span style="float: right;">
                <span">
                    <a href="' . $this->context->link->getAdminLink('AdminOrders', true) . '&id_order=' . $id . '&vieworder">
                        <i class="icon-search-plus"></i>
                    </a>
                </span>
            </span>';
    }

    public function callbackOrderListViewDetails($id)
    {
        return '<span class="btn-group-action">
                <span class="btn-group">
                    <span class="btn btn-default" onclick="$(\'#codwfeeplus_result_' . $id . '\').slideToggle();">
                        <i class="icon-search-plus"></i>
                    </span>
                </span>
            </span>';
    }

    private function getCustomerName($cust_id)
    {
        $cust = new Customer($cust_id);

        return $cust->firstname . ' ' . $cust->lastname;
    }

    public function displayExportcondLink($token, $id, $name = null)
    {
        $this->override_folder = '';
        $tpl = $this->createTemplate('_configure/helpers/list/list_action_duplicatecond.tpl');

        $tpl->assign(array(
            'href' => $this->context->link->getAdminLink('AdminCODwFeePlus', true) . '&submitCODwFeePlusActionExportcond=1'
            . '&CODwFeePlusActionExportcond_condId=' . $id,
            'action' => $this->l('Export'),
            'class' => 'action-export-cond',
            'icon' => 'icon-share-square',
        ));

        return $tpl->fetch();
    }

    public function displayDuplicatecondLink($token, $id, $name = null)
    {
        $this->override_folder = '';
        $tpl = $this->createTemplate('_configure/helpers/list/list_action_duplicatecond.tpl');

        $tpl->assign(array(
            'href' => $this->context->link->getAdminLink('AdminCODwFeePlus', true) . '&submitCODwFeePlusActionDuplicatecond=1'
            . '&CODwFeePlusActionDuplicatecond_condId=' . $id,
            'action' => $this->l('Duplicate'),
            'class' => 'action-duplicate-cond',
            'icon' => 'icon-copy',
        ));

        return $tpl->fetch();
    }

    public function displayCopyCondToStoreLink($token, $id, $name = null)
    {
        $this->override_folder = '';
        $tpl = $this->createTemplate('_configure/helpers/list/list_action_copyCondToStore.tpl');
        $buttons = array();
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $current_shop = Shop::getContextShopID();
                $shop_list = Shop::getShops(true, null, true);
                foreach ($shop_list as $value) {
                    if ($value != $current_shop) {
                        $buttons[] = array(
                            'href' => $this->context->link->getAdminLink('AdminCODwFeePlus', true) . '&submitCODwFeePlusActionCopyToShop=1'
                            . '&CODwFeePlusActionCopyToShop_shopId=' . $value . '&CODwFeePlusActionCopyToShop_condId=' . $id,
                            'action' => $this->l('Copy to shop') . ' ' . $this->module->getShopName($value),
                            'class' => 'action-copy-to-shop',
                            'icon' => 'icon-paperclip',
                        );
                    }
                }
            }
        }
        $tpl->assign(array(
            'buttons' => $buttons,
        ));

        return $tpl->fetch();
    }

//LIST
    protected function renderConditionsList()
    {
        $helper = new HelperList();

        $helper->title = $this->l('Conditions');
        $helper->table = 'codwfeeplus_conditions';
        $helper->no_link = false;
        $helper->orderBy = 'position';
        $helper->orderWay = 'ASC';
        $helper->shopLinkType = '';
        $helper->identifier = 'id_codwfeeplus_cond';
        $helper->position_identifier = 'position';

        if (Shop::isFeatureActive() && $this->areThereOtherShops()) {
            $helper->actions = array('edit', 'duplicatecond', 'copyCondToStore', 'delete', 'exportcond');
        } else {
            $helper->actions = array('edit', 'duplicatecond', 'delete', 'exportcond');
        }
        $values = $this->getConditionListValues();
        $helper->listTotal = count($values);
        $helper->_default_pagination = 10000;
        $helper->_pagination = array(10000);
        $helper->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ),
            'activate' => array(
                'text' => $this->l('Activate selected'),
                'icon' => 'icon-certificate text-success',
            ),
            'deactivate' => array(
                'text' => $this->l('Deactivate selected'),
                'icon' => 'icon-certificate text-danger',
            ),
            'duplicate' => array(
                'text' => $this->l('Duplicate selected'),
                'icon' => 'icon-copy',
            ),
        );

        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $current_shop = Shop::getContextShopID();
                $helper->bulk_actions['divider1'] = array(
                    'text' => 'divider',
                );
                $shop_list = Shop::getShops(true, null, true);
                foreach ($shop_list as $value) {
                    if ($value != $current_shop) {
                        $helper->bulk_actions['copytoshop' . $value] = array(
                            'text' => $this->l('Copy to shop') . ' ' . $this->module->getShopName($value),
                            'icon' => 'icon-paperclip',
                        );
                    }
                }
            }
        }
        $helper->bulk_actions['divider2'] = array(
            'text' => 'divider',
        );
        $helper->bulk_actions['export'] = array(
            'text' => $this->l('Export selected'),
            'icon' => 'icon-share-square',
        );

        $helper->tpl_vars = array('show_filters' => false, 'icon' => 'icon-list');

        $helper->toolbar_btn['new'] = array(
            'href' => $this->context->link->getAdminLink('AdminCODwFeePlus', false)
            . '&submitCODwFeePlus_add=1&token=' . Tools::getAdminTokenLite('AdminCODwFeePlus'),
            'desc' => $this->l('Add new condition'),
        );

        $helper->token = Tools::getAdminTokenLite('AdminCODwFeePlus');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminCODwFeePlus', false);
        $helper->name_controller = Tools::getValue('controller');
        $helper->controller_name = $this->controller_name;
        $helper->bootstrap = $this->bootstrap;
        $helper->override_folder = '_configure/';

        return $helper->generateList($values, $this->getConditionList());
    }

    public function getConditionListValues()
    {
        $type_desc = array(
            0 => $this->l('No Fee'),
            1 => $this->l('Fixed Fee'),
            2 => $this->l('Percentage Fee'),
            3 => $this->l('Fixed and Percentage Fee'),
        );
        $cartvaluesign_arr = array(
            0 => '>=',
            1 => '<=',
        );
        $integration_desc = array(
            0 => $this->l('Carrier Fee'),
            1 => $this->l('Product'),
        );
        $where_shop = '';
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $where_shop = ' WHERE `codwfeeplus_shop`=' . Shop::getContextShopID();
            }
        }
        $conds_db = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'codwfeeplus_conditions`' . $where_shop . ' ORDER BY `codwfeeplus_position`');

        $conds = array();
        $tax_array = TaxRulesGroup::getAssociatedTaxRatesByIdCountry(Tools::getCountry());

        $tax_array[0] = '--';
        foreach ($conds_db as $value) {
            $tax_rule = $value['codwfeeplus_taxrule_id'];

            $c = new CODwFP($value['id_codwfeeplus_cond']);
            $condtype = $c->codwfeeplus_condtype;
            $val_array = $c->getArrayForList((int) $this->context->language->id);
            $val_array_includecarrierfee = $c->getArrayCarrierFeeIncludedForList();
            $val_id = array(
                'id' => $value['id_codwfeeplus_cond'],
                'condtype' => $condtype,
            );
            $val_feetype = array(
                'val' => $type_desc[$value['codwfeeplus_fee_type']],
                'condtype' => $condtype,
            );
            $val_integration = array(
                'val' => $integration_desc[$value['codwfeeplus_integration']],
                'condtype' => $condtype,
            );
            $val_tax = array(
                'val' => $tax_rule != 0 ? number_format($tax_array[$tax_rule], 2, '.', '') : $tax_array[$tax_rule],
                'condtype' => $condtype,
            );
            $val_fee = array(
                'val' => $value['codwfeeplus_fee'],
                'condtype' => $condtype,
            );
            $val_feemin = array(
                'val' => $value['codwfeeplus_fee_min'],
                'condtype' => $condtype,
            );
            $val_feemax = array(
                'val' => $value['codwfeeplus_fee_max'],
                'condtype' => $condtype,
            );
            $val_feepercent = array(
                'val' => $val_array_includecarrierfee['codwfeeplus_fee_percent'],
                'condtype' => $condtype,
            );
            $row_class = 'codwfeeplus_condlist_type_fee';
            if ($c->codwfeeplus_condtype == 1) {
                $row_class = 'codwfeeplus_condlist_type_paymethod';
            }
            unset($c);
            $tmp = array(
                'id_codwfeeplus_cond' => $value['id_codwfeeplus_cond'],
                'id_codwfeeplus_cond_array' => $val_id,
                'codwfeeplus_desc' => $value['codwfeeplus_desc'],
                'codwfeeplus_fee_type' => $val_feetype,
                'codwfeeplus_integration' => $val_integration,
                'codwfeeplus_tax' => $val_tax,
                'codwfeeplus_fee' => $val_fee,
                'codwfeeplus_fee_min' => $val_feemin,
                'codwfeeplus_fee_max' => $val_feemax,
                'codwfeeplus_fee_percent' => $val_feepercent,
                'codwfeeplus_cartvalue_sign' => $cartvaluesign_arr[$value['codwfeeplus_cartvalue_sign']],
                'codwfeeplus_cartvalue' => $val_array_includecarrierfee['codwfeeplus_cartvalue'],
                'codwfeeplus_countries' => $val_array['countries'],
                'codwfeeplus_carriers' => $val_array['carriers'],
                'codwfeeplus_zones' => $val_array['zones'],
                'codwfeeplus_manufacturers' => $val_array['manufacturers'],
                'codwfeeplus_suppliers' => $val_array['suppliers'],
                'codwfeeplus_categories' => $val_array['categories'],
                'codwfeeplus_active' => $value['codwfeeplus_active'],
                'position' => $value['codwfeeplus_position'],
                'class' => $row_class,
            );
            if (Group::isFeatureActive()) {
                $tmp['codwfeeplus_groups'] = $val_array['groups'];
            }
            $conds[] = $tmp;
        }

        return $conds;
    }

    public function callbackCondListTooltip_lists($param)
    {
        $ret = '';
        $tooltip = '';
        $text = '';
        if ($param['count'] == 0) {
            $text = '--';
        } else {
            $text = $param['count'];
        }

        if ($param['contains_matchall']) {
            if ($param['matchall']) {
                $tooltip = $this->l('[Match All]') . '<br />' . $param['title'];
                $text = '[' . $text . ']';
            } else {
                $tooltip = $this->l('[Match Any]') . '<br />' . $param['title'];
            }
        } else {
            $tooltip = $param['title'];
        }
        if ($param['count'] == 0) {
            $ret = '--';
        } else {
            $ret = '<span class="label-tooltip codwfeeplus_condlist_tooltip" '
                    . 'data-toggle="tooltip" data-html="true" title="" data-original-title="' . $tooltip . '">'
                    . $text
                    . '</span>';
        }

        return $ret;
    }

    public function callbackCondListTooltip_idandtype($param)
    {
        $ret = '';
        $tooltip = $this->l('This condition is used to calculate fee.');
        $text = $param['id'];

        if ($param['condtype'] == 1) {
            $tooltip = $this->l('This condition is used to define if module will be active.');
        }
        $ret = '<span class="label-tooltip codwfeeplus_condlist_tooltip" '
                . 'data-toggle="tooltip" data-html="true" title="" data-original-title="' . $tooltip . '">'
                . $text
                . '</span>';

        return $ret;
    }

    public function callbackCondListTooltip_feepercent($param)
    {
        $ret = '';
        $val = '';
        $decimal = 2;
        $tooltip_text_no = $this->l('Carrier\'s fee is not included to the calculation.');
        $tooltip_text_yes = $this->l('Carrier\'s fee is included to the calculation.');
        $tooltip_text = '';
        $mark = '';

        if ($param['val']['carrierfeeincluded'] == 0) {
            $tooltip_text = $tooltip_text_no;
        } else {
            $tooltip_text = $tooltip_text_yes;
            $mark = '<span class="codwfeeplus_condlist_mark">*</span>';
        }

        $val = number_format(Tools::ps_round((float) $param['val']['value'], $decimal), 2, '.', '') . ' %';
        if ($param['condtype'] == 0) {
            $ret = '<span class="label-tooltip codwfeeplus_condlist_tooltip" '
                    . 'data-toggle="tooltip" data-html="true" title="" data-original-title="' . $tooltip_text . '">'
                    . $val . $mark
                    . '</span>';
        } else {
            $ret = $this->_def_NA_icon;
        }

        return $ret;
    }

    public function callbackCondListTooltip_cartvalue($param)
    {
        $ret = '';
        $val = '';
        $decimal = 2;
        $tooltip_text_no = $this->l('Carrier\'s fee is not included to the calculation.');
        $tooltip_text_yes = $this->l('Carrier\'s fee is included to the calculation.');
        $tooltip_text = '';
        $mark = '';

        if ($param['carrierfeeincluded'] == 0) {
            $tooltip_text = $tooltip_text_no;
        } else {
            $tooltip_text = $tooltip_text_yes;
            $mark = '<span class="codwfeeplus_condlist_mark">*</span>';
        }

        if ($param['type'] == 'percent') {
            $val = number_format(Tools::ps_round((float) $param['value'], $decimal), 2, '.', '') . ' %';
        } elseif ($param['type'] == 'price') {
            $val = Tools::displayPrice($param['value']);
        }

        $ret = '<span class="label-tooltip codwfeeplus_condlist_tooltip" '
                . 'data-toggle="tooltip" data-html="true" title="" data-original-title="' . $tooltip_text . '">'
                . $val . $mark
                . '</span>';

        return $ret;
    }

    public function callbackCondListTooltip_integration($param)
    {
        $ret = '';
        $text = '';
        if ($param['condtype'] == 0) {
            $text = $param['val'];
        } else {
            $text = $this->_def_NA_icon;
        }
        $ret = $text;
        return $ret;
    }

    public function callbackCondListTooltip_tax($param)
    {
        $ret = '';
        $text = '';
        $decimal = 2;
        if ($param['condtype'] == 0) {
            if ($param['val'] == '--') {
                $text = $param['val'];
            } else {
                $text = number_format(Tools::ps_round((float) $param['val'], $decimal), 2, '.', '') . ' %';
            }
        } else {
            $text = $this->_def_NA_icon;
        }
        $ret = $text;
        return $ret;
    }

    public function callbackCondListTooltip_feetype($param)
    {
        $ret = '';
        $text = '';
        if ($param['condtype'] == 0) {
            $text = $param['val'];
        } else {
            $text = $this->_def_NA_icon;
        }
        $ret = $text;
        return $ret;
    }

    public function callbackCondListTooltip_fee($param)
    {
        $ret = '';
        $text = '';
        if ($param['condtype'] == 0) {
            $text = Tools::displayPrice($param['val']);
        } else {
            $text = $this->_def_NA_icon;
        }
        $ret = $text;
        return $ret;
    }

    public function getConditionList()
    {
        $ret1 = array(
            'id_codwfeeplus_cond' => array('title' => $this->l('ID'), 'class' => 'codwfeeplus_list_cell_hidden', 'type' => 'text', 'align' => 'center', 'orderby' => false),
            'id_codwfeeplus_cond_array' => array('title' => $this->l('ID'), 'callback' => 'callbackCondListTooltip_idandtype', 'type' => 'text', 'align' => 'center', 'orderby' => false),
            'codwfeeplus_desc' => array('title' => $this->l('Condition description'), 'type' => 'text', 'orderby' => false),
            'codwfeeplus_integration' => array('title' => $this->l('Integration list'), 'type' => 'text', 'callback' => 'callbackCondListTooltip_integration', 'align' => 'center', 'orderby' => false),
            'codwfeeplus_tax' => array('title' => $this->l('Product Tax'), 'type' => 'text', 'callback' => 'callbackCondListTooltip_tax', 'align' => 'center', 'orderby' => false, 'class' => 'codwfeeplus_nowrap'),
            'codwfeeplus_fee_type' => array('title' => $this->l('Type'), 'type' => 'text', 'callback' => 'callbackCondListTooltip_feetype', 'align' => 'center', 'orderby' => false),
            'codwfeeplus_fee' => array('title' => $this->l('Fee'), 'type' => 'text', 'callback' => 'callbackCondListTooltip_fee', 'align' => 'center', 'orderby' => false, 'class' => 'codwfeeplus_nowrap'),
            'codwfeeplus_fee_percent' => array('title' => $this->l('Percent'), 'type' => 'text', 'callback' => 'callbackCondListTooltip_feepercent', 'align' => 'center', 'orderby' => false, 'class' => 'codwfeeplus_nowrap'),
            'codwfeeplus_fee_min' => array('title' => $this->l('Min Fee'), 'type' => 'text', 'callback' => 'callbackCondListTooltip_fee', 'align' => 'center', 'orderby' => false, 'class' => 'codwfeeplus_nowrap'),
            'codwfeeplus_fee_max' => array('title' => $this->l('Max Fee'), 'type' => 'text', 'callback' => 'callbackCondListTooltip_fee', 'align' => 'center', 'orderby' => false, 'class' => 'codwfeeplus_nowrap'),
            'codwfeeplus_cartvalue_sign' => array('title' => $this->l('Cart Value condition'), 'type' => 'text', 'align' => 'center', 'orderby' => false, 'class' => 'codwfeeplus_nowrap'),
            'codwfeeplus_cartvalue' => array('title' => $this->l('Cart Value'), 'type' => 'text', 'callback' => 'callbackCondListTooltip_cartvalue', 'align' => 'center', 'orderby' => false, 'class' => 'codwfeeplus_nowrap'),
            'codwfeeplus_carriers' => array('title' => $this->l('Carriers'), 'callback' => 'callbackCondListTooltip_lists', 'type' => 'text', 'align' => 'center', 'orderby' => false),
            'codwfeeplus_countries' => array('title' => $this->l('Countries'), 'callback' => 'callbackCondListTooltip_lists', 'type' => 'text', 'align' => 'center', 'orderby' => false),
            'codwfeeplus_zones' => array('title' => $this->l('Zones'), 'callback' => 'callbackCondListTooltip_lists', 'type' => 'text', 'align' => 'center', 'orderby' => false),
        );

        if (Group::isFeatureActive()) {
            $ret1['codwfeeplus_groups'] = array('title' => $this->l('Groups'), 'callback' => 'callbackCondListTooltip_lists', 'type' => 'text', 'align' => 'center', 'orderby' => false);
        }
        $manuf_label = $this->l('Manufacturers');
        if ($this->module->is17) {
            $manuf_label = $this->l('Brands');
        }
        $ret2 = array(
            'codwfeeplus_categories' => array('title' => $this->l('Categories'), 'callback' => 'callbackCondListTooltip_lists', 'type' => 'text', 'align' => 'center', 'orderby' => false),
            'codwfeeplus_manufacturers' => array('title' => $manuf_label, 'callback' => 'callbackCondListTooltip_lists', 'type' => 'text', 'align' => 'center', 'orderby' => false),
            'codwfeeplus_suppliers' => array('title' => $this->l('Suppliers'), 'callback' => 'callbackCondListTooltip_lists', 'type' => 'text', 'align' => 'center', 'orderby' => false),
            'codwfeeplus_active' => array('title' => $this->l('Active'), 'active' => 'status', 'type' => 'bool', 'align' => 'center', 'orderby' => false),
            'position' => array('title' => $this->l('Position'), 'position' => 'true', 'align' => 'center', 'orderby' => true),
        );

        $ret3 = array_merge($ret1, $ret2);
        return $ret3;
    }

//FORM
    public function renderFormConditions($in_cond_id = null, $getfrompost = false)
    {
//        $this->addJS(_PS_BO_ALL_THEMES_DIR_ . 'default/js/tree.js');
        $carriers = Carrier::getCarriers($this->context->language->id, false, false, false, null, Carrier::ALL_CARRIERS);
        $carriers_list = array();
        foreach ($carriers as $value) {
            $carriers_list[] = array(
                'id_option' => (int) $value['id_carrier'],
                'name' => $value['name'],
            );
        }

        if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')) {
            $countries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        } else {
            $countries = Country::getCountries($this->context->language->id, true);
        }
        $country_list = array();
        foreach ($countries as $value) {
            $country_list[] = array(
                'id_option' => (int) $value['id_country'],
                'name' => $value['name'],
            );
        }

        $zones = Zone::getZones();
        $zones_list = array();
        foreach ($zones as $value) {
            $zones_list[] = array(
                'id_option' => (int) $value['id_zone'],
                'name' => $value['name'],
            );
        }

        $emptyManuf_text = $this->l('Empty manufacturer');
        $manuf_label = $this->l('Cart Manufacturers');
        $manuf_desc = $this->l('Select one or more manufacturers to compare to the cart values');
        $manuf_hint = $this->l('The manufacturers of the products in the cart.');
        $manuf_matchall_label = $this->l('Match all manufacturers');
        $manuf_matchall_hint = $this->l('Toggle whether to match all the manufacturers of the cart, to the manufacturers selected in a condition. If disabled, even if only one manufacturer matches the condition manufacturers, this step of validation will be passed.');
        if ($this->module->is17) {
            $emptyManuf_text = $this->l('Empty brand');
            $manuf_label = $this->l('Cart Brands');
            $manuf_desc = $this->l('Select one or more brands to compare to the cart values');
            $manuf_hint = $this->l('The brands of the products in the cart.');
            $manuf_matchall_label = $this->l('Match all brands');
            $manuf_matchall_hint = $this->l('Toggle whether to match all the brands of the cart, to the brands selected in a condition. If disabled, even if only one brand matches the condition brands, this step of validation will be passed.');
        }

        $manufacturers = ManufacturerCore::getManufacturers();
        $manufacturers_list = array(
            0 => array(
                'id_option' => 0,
                'name' => $emptyManuf_text,
            )
        );
        foreach ($manufacturers as $value) {
            $manufacturers_list[] = array(
                'id_option' => (int) $value['id_manufacturer'],
                'name' => $value['name'],
            );
        }

        $suppliers = SupplierCore::getSuppliers();
        $suppliers_list = array(
            0 => array(
                'id_option' => 0,
                'name' => $this->l('Empty supplier'),
            )
        );
        foreach ($suppliers as $value) {
            $suppliers_list[] = array(
                'id_option' => (int) $value['id_supplier'],
                'name' => $value['name'],
            );
        }

        $taxrules = TaxRulesGroup::getTaxRulesGroups();
        $options_taxrule = array(
            array(
                'id_option' => 0,
                'name' => $this->l('No Tax'),
            ),
        );
        foreach ($taxrules as $value) {
            $options_taxrule[] = array(
                'id_option' => (int) $value['id_tax_rules_group'],
                'name' => $value['name'],
            );
        }

        $options = array(
            array(
                'id_option' => 0,
                'name' => $this->l('No Fee'),
            ),
            array(
                'id_option' => 1,
                'name' => $this->l('Fixed Fee'),
            ),
            array(
                'id_option' => 2,
                'name' => $this->l('Percentage Fee'),
            ),
            array(
                'id_option' => 3,
                'name' => $this->l('Fixed and Percentage Fee'),
            ),
        );

        $options_cartvalue_sign = array(
            array(
                'id_option' => 0,
                'name' => $this->l('Apply when >='),
            ),
            array(
                'id_option' => 1,
                'name' => $this->l('Apply when <='),
            ),
        );

        $options_integration = array(
            array(
                'id_option' => 0,
                'name' => $this->l('Add to carrier\'s fee'),
            ),
            array(
                'id_option' => 1,
                'name' => $this->l('Add a COD product to the order'),
            ),
        );

        $fieldValues = $this->getFieldsValuesConditions($in_cond_id, $getfrompost);

        $category = Category::getRootCategory()->id;
        $tree = new HelperTreeCategories('codwfeeplus_cat_tree');
        $tree->setUseCheckBox(true)
                ->setUseSearch(false)
                ->setIdTree('codwfeeplus_cond_cat_tree')
                ->setFullTree(true)
                ->setChildrenOnly(true)
                ->setNoJS(true)
                ->setRootCategory($category)
                ->setInputName('cond_categoryBox')
                ->setSelectedCategories($fieldValues['categories']);
        $categoryTree = $tree->render();
//token changed because of the category tree... So change it back.
        $this->context->smarty->assign('token', $this->token);

        $title = '';
        if ($in_cond_id == null) {
            $title = $this->l('New Condition');
        } else {
            $title = $this->l('Condition Details') . ' - ID: ' . $in_cond_id;
        }
        $groups_list = array();
        if (Group::isFeatureActive()) {
            $groups = Group::getGroups($this->context->language->id);
            foreach ($groups as $value) {
                $groups_list[] = array(
                    'id_option' => (int) $value['id_group'],
                    'name' => $value['name'],
                );
            }
        }

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $title,
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable this condition'),
                        'name' => 'CODWFEEPLUS_ACTIVE',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'value' => 1,
                            ),
                            array(
                                'value' => 0,
                            ),
                        ),
                        'hint' => $this->l('Toggle condition activation.'),
                    ),
                    array(
                        'type' => 'switch_custom',
                        'label' => $this->l('Type of condition'),
                        'name' => 'CODWFEEPLUS_CONDTYPE',
                        'cols' => 6,
                        'desc' => $this->l('Select if the validation of the condition defines the fee or if it disables the payment method.'),
                        'values' => array(
                            array(
                                'value' => 0,
                                'label' => $this->l('FEE CALCULATION'),
                                'id' => 'CODWFEEPLUS_CONDTYPE_0',
                            ),
                            array(
                                'value' => 1,
                                'label' => $this->l('MODULE ACTIVATION'),
                                'id' => 'CODWFEEPLUS_CONDTYPE_1',
                            ),
                        ),
                        'hint' => $this->l('When the condition validates, If the FEE CALCULATION is selected, the condition will calculate the fee. If MODULE ACTIVATION is selected and the condition is validated, the payment method will NOT be available in the front office.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Description'),
                        'name' => 'CODWFEEPLUS_DESCRIPTION',
                        'hint' => $this->l('Give a description to identify your condition.'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Integration form'),
                        'name' => 'CODWFEEPLUS_INTEGRATION',
                        'options' => array(
                            'query' => $options_integration,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $this->l('If the condition is validated, how the fee will be integrated to the order?'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('COD Product tax rule'),
                        'name' => 'CODWFEEPLUS_TAXRULE',
                        'options' => array(
                            'query' => $options_taxrule,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $this->l('If the condition is validated, and a COD product is used, what is the tax that it should contain?'),
                    ),
                    array(
                        'type' => 'html',
                        'html_content' => '<hr class="codwfeeplus_form_hr">',
                        'col' => '12',
                        'label' => '',
                        'name' => 'sep',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Fee Type'),
                        'name' => 'CODWFEEPLUS_FEETYPE',
                        'options' => array(
                            'query' => $options,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $this->l('If the condition is validated, what kind of fee should be applied?'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Fixed Fee'),
                        'name' => 'CODWFEEPLUS_FEE',
                        'suffix' => $this->_defCurrencySuffix,
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l(''),
                        'hint' => $this->l('This amount will be added to fee if fixed or percentage + fixed fee method is selected.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Percentage Fee'),
                        'name' => 'CODWFEEPLUS_PERCENTAGE',
                        'suffix' => '%',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l(''),
                        'hint' => $this->l('This is the percentage of the total cart value that will be added as fee, if percentage or percentage + fixed fee method is selected.'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Include carrier\'s fee in percentage'),
                        'name' => 'CODWFEEPLUS_FEE_PERCENT_INCLUDE_CARRIER',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'value' => 1,
                            ),
                            array(
                                'value' => 0,
                            ),
                        ),
                        'hint' => $this->l('When the percentage fee is calculated, include carrier\'s fee or just the products\' value?'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Minimum Fee'),
                        'name' => 'CODWFEEPLUS_MIN',
                        'suffix' => $this->_defCurrencySuffix,
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Set to 0 to disable'),
                        'hint' => $this->l('For percentage or percentage + fixed fee methods, if the calculated fee is below this value, this value will be applied.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Maximum Fee'),
                        'name' => 'CODWFEEPLUS_MAX',
                        'suffix' => $this->_defCurrencySuffix,
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Set to 0 to disable'),
                        'hint' => $this->l('For percentage or percentage + fixed fee methods, if the calculated fee is above this value, this value will be applied.'),
                    ),
                    array(
                        'type' => 'html',
                        'html_content' => '<hr class="codwfeeplus_form_hr">',
                        'col' => '12',
                        'label' => '',
                        'name' => 'sep',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Apply when cart total value is:'),
                        'name' => 'CODWFEEPLUS_CARTVALUE_SIGN',
                        'options' => array(
                            'query' => $options_cartvalue_sign,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $this->l('Select either greater or equal, or lesser or equal, for comparing cart value condition to customer\'s cart value.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Total cart value'),
                        'name' => 'CODWFEEPLUS_CARTVALUE',
                        'suffix' => $this->_defCurrencySuffix,
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Set to 0 to disable'),
                        'hint' => $this->l('The value to check against customer\'s total cart value.'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Include carrier\'s fee in total cart value'),
                        'name' => 'CODWFEEPLUS_CARTVALUE_INCLUDE_CARRIER',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'value' => 1,
                            ),
                            array(
                                'value' => 0,
                            ),
                        ),
                        'hint' => $this->l('When the total cart value is checked, include carrier\'s fee or just the products\' value?'),
                    ),
                    array(
                        'type' => 'html',
                        'html_content' => '<hr class="codwfeeplus_form_hr">',
                        'col' => '12',
                        'label' => '',
                        'name' => 'sep',
                    ),
                    array(
                        'type' => 'select',
                        'multiple' => true,
                        'label' => $this->l('Carriers List'),
                        'name' => 'CODWFEEPLUS_DELIVERY_ARRAY[]',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Select one or more carriers to compare to the cart values'),
                        'options' => array(
                            'query' => $carriers_list,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $this->l('Select (using the Control key) none, one or more Carriers from the list.'),
                    ),
                    array(
                        'type' => 'html',
                        'html_content' => '<hr class="codwfeeplus_form_hr">',
                        'col' => '12',
                        'label' => '',
                        'name' => 'sep',
                    ),
                    array(
                        'type' => 'select',
                        'multiple' => true,
                        'label' => $this->l('Countries List'),
                        'name' => 'CODWFEEPLUS_COUNTRIES_ARRAY[]',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Select one or more countries to compare to the cart values'),
                        'options' => array(
                            'query' => $country_list,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $this->l('Select (using the Control key) none, one or more Countries from the list.'),
                    ),
                    array(
                        'type' => 'html',
                        'html_content' => '<hr class="codwfeeplus_form_hr">',
                        'col' => '12',
                        'label' => '',
                        'name' => 'sep',
                    ),
                    array(
                        'type' => 'select',
                        'multiple' => true,
                        'label' => $this->l('Zone List'),
                        'name' => 'CODWFEEPLUS_ZONES_ARRAY[]',
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('Select one or more zones to compare to the cart values'),
                        'options' => array(
                            'query' => $zones_list,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'hint' => $this->l('Select (using the Control key) none, one or more Zones from the list.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );

        if (Group::isFeatureActive()) {
            $fields_form['form']['input'][] = array(
                'type' => 'html',
                'html_content' => '<hr class="codwfeeplus_form_hr">',
                'col' => '12',
                'label' => '',
                'name' => 'sep',
            );
            $fields_form['form']['input'][] = array(
                'type' => 'select',
                'multiple' => true,
                'label' => $this->l('Groups List'),
                'name' => 'CODWFEEPLUS_GROUPS_ARRAY[]',
                'class' => 'fixed-width-lg',
                'desc' => $this->l('Select one or more customer groups to compare to the cart values'),
                'options' => array(
                    'query' => $groups_list,
                    'id' => 'id_option',
                    'name' => 'name',
                ),
                'hint' => $this->l('Select (using the Control key) none, one or more Groups from the list.'),
            );
            $fields_form['form']['input'][] = array(
                'type' => 'switch',
                'label' => $this->l('Match all groups'),
                'name' => 'CODWFEEPLUS_MATCHALL_GROUPS',
                'is_bool' => true,
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    ),
                ),
                'hint' => $this->l('Toggle whether to match all the groups a customer belongs to, to the groups selected in a condition. If disabled, even if only one group matches the condition groups, this step of validation will be passed.'),
            );
        } else {
            $fields_form['form']['input'][] = array(
                'type' => 'html',
                'html_content' => '<hr class="codwfeeplus_form_hr">',
                'col' => '12',
                'label' => '',
                'name' => 'sep',
            );
            $fields_form['form']['input'][] = array(
                'type' => 'html',
                'html_content' => '<p class="codwfeeplus_nogroup_error">' . $this->l('The group feature is not active on this shop.') . '</p>',
                'label' => $this->l('Groups List'),
                'name' => 'CODWFEEPLUS_GROUPS_ARRAY',
                'class' => 'fixed-width-lg',
            );
        }

        $fields_form['form']['input'][] = array(
            'type' => 'html',
            'html_content' => '<hr class="codwfeeplus_form_hr">',
            'col' => '12',
            'label' => '',
            'name' => 'sep',
        );
        $fields_form['form']['input'][] = array(
            'type' => 'categories_select',
            'label' => $this->l('Category List'),
            'desc' => $this->l('Select one or more categories to apply this Fee.'),
            'name' => 'CODWFEEPLUS_CATEGORIES_ARRAY',
            'category_tree' => $categoryTree,
            'hint' => $this->l('Check the categories that at least one of the product of the cart, belongs to.'),
        );
        $fields_form['form']['input'][] = array(
            'type' => 'switch',
            'label' => $this->l('Match all categories'),
            'name' => 'CODWFEEPLUS_MATCHALL_CATEGORIES',
            'is_bool' => true,
            'values' => array(
                array(
                    'value' => 1,
                ),
                array(
                    'value' => 0,
                ),
            ),
            'hint' => $this->l('Toggle whether to match all the categories from the cart to the categories selected in a condition. If disabled, even if only one category matches the condition categories, this step of validation will be passed.'),
        );
        $fields_form['form']['input'][] = array(
            'type' => 'html',
            'html_content' => '<hr class="codwfeeplus_form_hr">',
            'col' => '12',
            'label' => '',
            'name' => 'sep',
        );

        $fields_form['form']['input'][] = array(
            'type' => 'select',
            'multiple' => true,
            'label' => $manuf_label,
            'name' => 'CODWFEEPLUS_MANUFACTURERS_ARRAY[]',
            'class' => 'fixed-width-lg',
            'desc' => $manuf_desc,
            'options' => array(
                'query' => $manufacturers_list,
                'id' => 'id_option',
                'name' => 'name',
            ),
            'hint' => $manuf_hint,
        );
        $fields_form['form']['input'][] = array(
            'type' => 'switch',
            'label' => $manuf_matchall_label,
            'name' => 'CODWFEEPLUS_MATCHALL_MANUFACTURERS',
            'is_bool' => true,
            'values' => array(
                array(
                    'value' => 1,
                ),
                array(
                    'value' => 0,
                ),
            ),
            'hint' => $manuf_matchall_hint,
        );
        $fields_form['form']['input'][] = array(
            'type' => 'html',
            'html_content' => '<hr class="codwfeeplus_form_hr">',
            'col' => '12',
            'label' => '',
            'name' => 'sep',
        );
        $fields_form['form']['input'][] = array(
            'type' => 'select',
            'multiple' => true,
            'label' => $this->l('Suppliers List'),
            'name' => 'CODWFEEPLUS_SUPPLIERS_ARRAY[]',
            'class' => 'fixed-width-lg',
            'desc' => $this->l('Select one or more suppliers to compare to the cart values'),
            'options' => array(
                'query' => $suppliers_list,
                'id' => 'id_option',
                'name' => 'name',
            ),
            'hint' => $this->l('Select (using the Control key) none, one or more suppliers from the list.'),
        );
        $fields_form['form']['input'][] = array(
            'type' => 'switch',
            'label' => $this->l('Match all suppliers'),
            'name' => 'CODWFEEPLUS_MATCHALL_SUPPLIERS',
            'is_bool' => true,
            'values' => array(
                array(
                    'value' => 1,
                ),
                array(
                    'value' => 0,
                ),
            ),
            'hint' => $this->l('Toggle whether to match all the suppliers of the cart, to the suppliers selected in a condition. If disabled, even if only one supplier matches the condition suppliers, this step of validation will be passed.'),
        );
        $fields_form['form']['input'][] = array(
            'type' => 'hidden',
            'name' => 'CODWFEEPLUS_ID',
        );

        $helper = new HelperForm();
        $helper->show_toolbar = true;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->show_cancel_button = true;
        $helper->back_url = $this->context->link->getAdminLink('AdminCODwFeePlus', true);
        $helper->identifier = 'id_codwfeeplus_cond';
        $helper->submit_action = 'submitCODwFeePlusConditions';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminCODwFeePlus', false);
        $helper->token = Tools::getAdminTokenLite('AdminCODwFeePlus');
        $helper->override_folder = '_configure/';
        $helper->tpl_vars = array(
            'fields_value' => $fieldValues['form'],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array('form' => $fields_form));
    }

    protected function getFieldsValuesConditions($in_cond_id = null, $getfrompost = false)
    {
        $ret = array();
        if (!$getfrompost) {
            $cond = new CODwFP($in_cond_id);
            $ret['form'] = array(
                'CODWFEEPLUS_ACTIVE' => $cond->codwfeeplus_active,
                'CODWFEEPLUS_CONDTYPE' => $cond->codwfeeplus_condtype,
                'CODWFEEPLUS_ID' => $cond->id_codwfeeplus_cond,
                'CODWFEEPLUS_FEETYPE' => $cond->codwfeeplus_fee_type,
                'CODWFEEPLUS_FEE' => $cond->codwfeeplus_fee,
                'CODWFEEPLUS_PERCENTAGE' => $cond->codwfeeplus_fee_percent,
                'CODWFEEPLUS_MIN' => $cond->codwfeeplus_fee_min,
                'CODWFEEPLUS_MAX' => $cond->codwfeeplus_fee_max,
                'CODWFEEPLUS_DELIVERY_ARRAY[]' => $cond->getDeliveryArray(),
                'CODWFEEPLUS_COUNTRIES_ARRAY[]' => $cond->getCountriesArray(),
                'CODWFEEPLUS_ZONES_ARRAY[]' => $cond->getZonesArray(),
                'CODWFEEPLUS_GROUPS_ARRAY[]' => $cond->getGroupsArray(),
                'CODWFEEPLUS_MANUFACTURERS_ARRAY[]' => $cond->getManufacturersArray(),
                'CODWFEEPLUS_SUPPLIERS_ARRAY[]' => $cond->getSuppliersArray(),
                'CODWFEEPLUS_MATCHALL_GROUPS' => $cond->codwfeeplus_matchall_groups,
                'CODWFEEPLUS_MATCHALL_CATEGORIES' => $cond->codwfeeplus_matchall_categories,
                'CODWFEEPLUS_MATCHALL_MANUFACTURERS' => $cond->codwfeeplus_matchall_manufacturers,
                'CODWFEEPLUS_MATCHALL_SUPPLIERS' => $cond->codwfeeplus_matchall_suppliers,
                'CODWFEEPLUS_DESCRIPTION' => $cond->codwfeeplus_desc,
                'CODWFEEPLUS_CARTVALUE_SIGN' => $cond->codwfeeplus_cartvalue_sign,
                'CODWFEEPLUS_CARTVALUE' => $cond->codwfeeplus_cartvalue,
                'CODWFEEPLUS_INTEGRATION' => $cond->codwfeeplus_integration,
                'CODWFEEPLUS_TAXRULE' => $cond->codwfeeplus_taxrule_id,
                'CODWFEEPLUS_FEE_PERCENT_INCLUDE_CARRIER' => $cond->codwfeeplus_fee_percent_include_carrier,
                'CODWFEEPLUS_CARTVALUE_INCLUDE_CARRIER' => $cond->codwfeeplus_cartvalue_include_carrier,
            );
            $ret['categories'] = $cond->getCategoriesArray();
            unset($cond);
        } else {
            $ret['form'] = array(
                'CODWFEEPLUS_ACTIVE' => Tools::getValue('CODWFEEPLUS_ACTIVE'),
                'CODWFEEPLUS_CONDTYPE' => Tools::getValue('CODWFEEPLUS_CONDTYPE'),
                'CODWFEEPLUS_ID' => Tools::getValue('CODWFEEPLUS_ID'),
                'CODWFEEPLUS_FEETYPE' => Tools::getValue('CODWFEEPLUS_FEETYPE'),
                'CODWFEEPLUS_FEE' => Tools::getValue('CODWFEEPLUS_FEE'),
                'CODWFEEPLUS_PERCENTAGE' => Tools::getValue('CODWFEEPLUS_PERCENTAGE'),
                'CODWFEEPLUS_MIN' => Tools::getValue('CODWFEEPLUS_MIN'),
                'CODWFEEPLUS_MAX' => Tools::getValue('CODWFEEPLUS_MAX'),
                'CODWFEEPLUS_DELIVERY_ARRAY[]' => $this->_notArrayToEmptyArray(Tools::getValue('CODWFEEPLUS_DELIVERY_ARRAY')),
                'CODWFEEPLUS_COUNTRIES_ARRAY[]' => $this->_notArrayToEmptyArray(Tools::getValue('CODWFEEPLUS_COUNTRIES_ARRAY')),
                'CODWFEEPLUS_ZONES_ARRAY[]' => $this->_notArrayToEmptyArray(Tools::getValue('CODWFEEPLUS_ZONES_ARRAY')),
                'CODWFEEPLUS_GROUPS_ARRAY[]' => $this->_notArrayToEmptyArray(Tools::getValue('CODWFEEPLUS_GROUPS_ARRAY')),
                'CODWFEEPLUS_MANUFACTURERS_ARRAY[]' => $this->_notArrayToEmptyArray(Tools::getValue('CODWFEEPLUS_MANUFACTURERS_ARRAY')),
                'CODWFEEPLUS_SUPPLIERS_ARRAY[]' => $this->_notArrayToEmptyArray(Tools::getValue('CODWFEEPLUS_SUPPLIERS_ARRAY')),
                'CODWFEEPLUS_MATCHALL_GROUPS' => Tools::getValue('CODWFEEPLUS_MATCHALL_GROUPS'),
                'CODWFEEPLUS_MATCHALL_CATEGORIES' => Tools::getValue('CODWFEEPLUS_MATCHALL_CATEGORIES'),
                'CODWFEEPLUS_MATCHALL_MANUFACTURERS' => Tools::getValue('CODWFEEPLUS_MATCHALL_MANUFACTURERS'),
                'CODWFEEPLUS_MATCHALL_SUPPLIERS' => Tools::getValue('CODWFEEPLUS_MATCHALL_SUPPLIERS'),
                'CODWFEEPLUS_DESCRIPTION' => Tools::getValue('CODWFEEPLUS_DESCRIPTION'),
                'CODWFEEPLUS_CARTVALUE_SIGN' => Tools::getValue('CODWFEEPLUS_CARTVALUE_SIGN'),
                'CODWFEEPLUS_CARTVALUE' => Tools::getValue('CODWFEEPLUS_CARTVALUE'),
                'CODWFEEPLUS_INTEGRATION' => Tools::getValue('CODWFEEPLUS_INTEGRATION'),
                'CODWFEEPLUS_TAXRULE' => Tools::getValue('CODWFEEPLUS_TAXRULE'),
                'CODWFEEPLUS_FEE_PERCENT_INCLUDE_CARRIER' => Tools::getValue('CODWFEEPLUS_FEE_PERCENT_INCLUDE_CARRIER'),
                'CODWFEEPLUS_CARTVALUE_INCLUDE_CARRIER' => Tools::getValue('CODWFEEPLUS_CARTVALUE_INCLUDE_CARRIER'),
            );
            $ret['categories'] = $this->_notArrayToEmptyArray(Tools::getValue('cond_categoryBox'));
        }

        return $ret;
    }

//postproccess

    private function _postproccess_testForm()
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;

        $cat = (Tools::getValue('test_categoryBox'));
        if (!$cat) {
            $cat = array();
        }
        $groups = (Tools::getValue('tstfrm_group'));
        if (!$groups) {
            $groups = array();
        }
        $manufacturers = (Tools::getValue('tstfrm_manufacturers'));
        if (!$manufacturers) {
            $manufacturers = array();
        }
        $suppliers = (Tools::getValue('tstfrm_suppliers'));
        if (!$suppliers) {
            $suppliers = array();
        }
        $id_zone = Country::getIdZone(Tools::getValue('tstfrm_country'));
        $id_shop = $this->context->shop->id;
        $this->_test_totfee = $this->module->getCost_common(Tools::getValue('tstfrm_carrier'), Tools::getValue('tstfrm_country'), $id_zone, Tools::getValue('tstfrm_cartvalue'), Tools::getValue('tstfrm_carriervalue'), $cat, $groups, $manufacturers, $suppliers, $id_shop);
        $this->_test_result .= $this->module->_testoutput;

        $ret &= ($this->_test_result != '');

        if ($ret) {
            if ($this->module->_testoutput_method_active) {
                $this->_msg[] = $this->l('Successful test. Total fee:') . ' ' . $this->_test_totfee . trim($this->_defCurrencySuffix);
            } else {
                $this->_msg[] = $this->l('Successful test. Payment method will be unavailable');
            }
        } else {
            $this->_errors[] = $this->l('Failed to test.');
        }

        return $ret;
    }

    private function _postproccess_delete($in_id)
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;
        $cond = new CODwFP($in_id);
        $ret &= $cond->delete();
        unset($cond);
        if ($ret) {
            $this->_msg[] = $this->l('Successful update.');
        } else {
            $this->_errors[] = $this->l('Failed to update.');
        }

        return $ret;
    }

    private function _postproccess_toggleactive($in_id)
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;
        $cond = new CODwFP($in_id);
        $cond->codwfeeplus_active = !$cond->codwfeeplus_active;
        $ret &= $cond->saveToDB();
        unset($cond);
        if ($ret) {
            $this->_msg[] = $this->l('Successful update.');
        } else {
            $this->_errors[] = $this->l('Failed to update.');
        }

        return $ret;
    }

    private function _postproccess_cond()
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;

        $id_cond = Tools::getValue('CODWFEEPLUS_ID', '');
        if ($id_cond === '') {
            $id_cond = null;
        }
        $cond = new CODwFP($id_cond);
        $cond->codwfeeplus_fee_type = Tools::getValue('CODWFEEPLUS_FEETYPE', 0);
        $cond->codwfeeplus_integration = Tools::getValue('CODWFEEPLUS_INTEGRATION', 0);
        $cond->codwfeeplus_taxrule_id = Tools::getValue('CODWFEEPLUS_TAXRULE', 0);
        $cond->codwfeeplus_active = Tools::getValue('CODWFEEPLUS_ACTIVE', 0);
        $cond->codwfeeplus_condtype = Tools::getValue('CODWFEEPLUS_CONDTYPE', 0);
        $cond->codwfeeplus_fee = Tools::getValue('CODWFEEPLUS_FEE', 0);
        $cond->codwfeeplus_fee_percent = Tools::getValue('CODWFEEPLUS_PERCENTAGE', 0);
        $cond->codwfeeplus_fee_min = Tools::getValue('CODWFEEPLUS_MIN', 0);
        $cond->codwfeeplus_fee_max = Tools::getValue('CODWFEEPLUS_MAX', 0);
        $cond->setDeliveryArray(Tools::getValue('CODWFEEPLUS_DELIVERY_ARRAY', array()));
        $cond->setCountriesArray(Tools::getValue('CODWFEEPLUS_COUNTRIES_ARRAY', array()));
        $cond->setZonesArray(Tools::getValue('CODWFEEPLUS_ZONES_ARRAY', array()));
        $cond->setManufacturersArray(Tools::getValue('CODWFEEPLUS_MANUFACTURERS_ARRAY', array()));
        $cond->setSuppliersArray(Tools::getValue('CODWFEEPLUS_SUPPLIERS_ARRAY', array()));
        $cond->setCategoriesArray(Tools::getValue('cond_categoryBox', array()));
        $cond->codwfeeplus_matchall_categories = Tools::getValue('CODWFEEPLUS_MATCHALL_CATEGORIES', 0);
        $cond->codwfeeplus_matchall_manufacturers = Tools::getValue('CODWFEEPLUS_MATCHALL_MANUFACTURERS', 0);
        $cond->codwfeeplus_matchall_suppliers = Tools::getValue('CODWFEEPLUS_MATCHALL_SUPPLIERS', 0);
        if (Group::isFeatureActive()) {
            $cond->setGroupsArray(Tools::getValue('CODWFEEPLUS_GROUPS_ARRAY', array()));
            $cond->codwfeeplus_matchall_groups = Tools::getValue('CODWFEEPLUS_MATCHALL_GROUPS', 0);
        }
        $cond->codwfeeplus_desc = Tools::getValue('CODWFEEPLUS_DESCRIPTION', '');
        $cond->codwfeeplus_cartvalue_sign = Tools::getValue('CODWFEEPLUS_CARTVALUE_SIGN', '');
        $cond->codwfeeplus_cartvalue = Tools::getValue('CODWFEEPLUS_CARTVALUE', '');
        $cond->codwfeeplus_cartvalue_include_carrier = Tools::getValue('CODWFEEPLUS_CARTVALUE_INCLUDE_CARRIER', '');
        $cond->codwfeeplus_fee_percent_include_carrier = Tools::getValue('CODWFEEPLUS_FEE_PERCENT_INCLUDE_CARRIER', '');
        $ret &= $cond->saveToDB();
        unset($cond);
        if ($ret) {
            $this->_msg[] = $this->l('Successful update.');
        } else {
            $this->_errors[] = $this->l('Failed to update.');
        }

        return $ret;
    }

    private function _postproccess_conf()
    {
        $this->_msg = array();
        $this->_errors = array();
        $title = array();
        $ret = true;
        $ret &= Configuration::updateValue('SG_CODWFEEPLUS_BEHAVIOUR', Tools::getValue('CODWFEEPLUS_BEHAVIOUR', 0));
        $ret &= Configuration::updateValue('SG_CODWFEEPLUS_KEEPTRANSACTIONS', Tools::getValue('CODWFEEPLUS_KEEPTRANSACTIONS', 1));
        $ret &= Configuration::updateValue('SG_CODWFEEPLUS_INTEGRATION_WAY', Tools::getValue('CODWFEEPLUS_INTEGRATION_WAY', 0));
        $ret &= Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_REFERENCE', Tools::getValue('CODWFEEPLUS_PRODUCT_REFERENCE', 'COD'));
        $ret &= Configuration::updateValue('SG_CODWFEEPLUS_AUTO_UPDATE', Tools::getValue('CODWFEEPLUS_AUTO_UPDATE', 0));
        if ($this->module->is17) {
            $ret &= Configuration::updateValue('SG_CODWFEEPLUS_LOGO_ENABLED', Tools::getValue('CODWFEEPLUS_LOGO_ENABLED', 0));
        }
        foreach (Language::getLanguages(true) as $lang) {
            $title[$lang['id_lang']] = Tools::getValue('CODWFEEPLUS_PRODUCT_TITLE_' . $lang['id_lang']);
        }
        $ret &= Configuration::updateValue('SG_CODWFEEPLUS_PRODUCT_TITLE', $title);
        $ret &= $this->module->updateCODProduct();

        if ($ret) {
            $this->_msg[] = $this->l('Successful update.');
        } else {
            $this->_errors[] = $this->l('Failed to update.');
        }

        $ret2 = true;

        if (isset($_FILES['CODWFEEPLUS_CONDITIONS_IMPORT']['tmp_name']) && !empty($_FILES['CODWFEEPLUS_CONDITIONS_IMPORT']['tmp_name'])) {
            if ($_FILES['CODWFEEPLUS_CONDITIONS_IMPORT']['type'] == 'application/json') {
                $length = filesize($_FILES['CODWFEEPLUS_CONDITIONS_IMPORT']['tmp_name']);
                $f = fopen($_FILES['CODWFEEPLUS_CONDITIONS_IMPORT']['tmp_name'], 'r');
                if ($f && $length > 0) {
                    $contents = fread($f, $length);
                    fclose($f);
                    $inarr = json_decode($contents, true);
                    $ret2 &= $this->importConditions($inarr);
                } else {
                    $ret2 &= false;
                }
                if ($ret2) {
                    $this->_msg[] = $this->l('Successfully imported conditions.');
                } else {
                    $this->_errors[] = $this->l('Failed to import conditions.');
                }
            } else {
                $ret2 = false;
                $this->_errors[] = $this->l('Invalid condition import file.');
            }
        }

        $ret3 = true;

        if (isset($_FILES['CODWFEEPLUS_LOGO_FILENAME_17']['tmp_name']) && !empty($_FILES['CODWFEEPLUS_LOGO_FILENAME_17']['tmp_name'])) {
            $ret3 &= ImageManager::isRealImage($_FILES['CODWFEEPLUS_LOGO_FILENAME_17']['tmp_name'], $_FILES['CODWFEEPLUS_LOGO_FILENAME_17']['type']);
            if ($ret3) {
                $final = _PS_MODULE_DIR_ . $this->module->name . '/views/img/' . $_FILES['CODWFEEPLUS_LOGO_FILENAME_17']['name'];
                $ret3 &= move_uploaded_file($_FILES['CODWFEEPLUS_LOGO_FILENAME_17']['tmp_name'], $final);
                $ret3 &= Configuration::updateValue('SG_CODWFEEPLUS_LOGO_FILENAME_17', $_FILES['CODWFEEPLUS_LOGO_FILENAME_17']['name']);
                if ($ret3) {
                    $this->_msg[] = $this->l('Successfully uploaded logo image.');
                } else {
                    $this->_errors[] = $this->l('Failed to upload logo image.');
                }
            } else {
                $this->_errors[] = $this->l('Not a valid image file.');
            }
        }

        return $ret & $ret2 & $ret3;
    }

//Validation

    private function _validate_testForm()
    {
        return $this->validateFormData($this->_validateTestFormValues);
    }

    private function _validate_conf()
    {
        return $this->validateFormData($this->_validateConfigFormValues);
    }

    private function valInt($inVal)
    {
        return Validate::isInt($inVal);
    }

    private function valIntOrEmpty($inVal)
    {
        if ($inVal == '') {
            return true;
        } else {
            return Validate::isInt($inVal);
        }
    }

    private function valText($inVal)
    {
        return Validate::isCleanHtml($inVal);
    }

    private function valPrice($inVal)
    {
        return Validate::isPrice($inVal);
    }

    private function valPercentage($inVal)
    {
        return Validate::isPercentage($inVal);
    }

    private function valArrayWithIds($inVal)
    {
        return Validate::isArrayWithIds($inVal);
    }

    private function valArrayWithIdsWithZero($inVal)
    {
        return $this->isArrayWithIdsWithZero($inVal);
    }

    private function validateFormData($param)
    {
        $this->_errors = array();
        $ret = true;



        foreach ($param as $value) {

            if ($value['multilang']) {
                $conf_title = array();
                foreach (Language::getLanguages(true) as $lang) {
                    $conf_title[$lang['id_lang']] = Tools::getValue($value['name'] . '_' . $lang['id_lang']);
                    if ($conf_title[$lang['id_lang']] !== false) {
                        if ($value['req'] && ($conf_title[$lang['id_lang']] == '' || $conf_title[$lang['id_lang']] == null)) {
                            $this->_errors[] = sprintf('Empty value for language %s: %s', $lang['name'], $value['out']);
                            $ret &= false;
                        }
                        $func = 'val' . $value['type'];
                        if (!$this->$func($conf_title[$lang['id_lang']])) {
                            $this->_errors[] = sprintf($this->l('Invalid product title for %s language.'), $lang['name']);
                            $ret &= false;
                        }
                    }
                }
            } else {
                $val = Tools::getValue($value['name']);
                if ($val !== false) {
                    if ($value['req'] && ($val == '' || $val == null)) {
                        $this->_errors[] = sprintf('Empty value: %s', $value['out']);
                        $ret &= false;
                    }
                    $func = 'val' . $value['type'];
                    if (!$this->$func($val)) {
                        $this->_errors[] = sprintf('Invalid value: %s', $value['out']);
                        $ret &= false;
                    }
                }
            }
        }
        return $ret;
    }

    private function _validate_cond()
    {
        return $this->validateFormData($this->_validateCondFormValues);
    }

    private function isArrayWithIdsWithZero($ids)
    {
        if (count($ids)) {
            foreach ($ids as $id) {
                if (!Validate::isUnsignedInt($id)) {
                    return false;
                }
            }
        }
        return true;
    }

//Positions

    public function ajaxProcessUpdatePositions()
    {
        $where_shop = '';
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $where_shop = ' AND `codwfeeplus_shop`=' . Shop::getContextShopID();
            }
        }
        $id = (int) Tools::getValue('id');
        $positions = Tools::getValue('codwfeeplus_cond');
        $way = (bool) Tools::getValue('way');

        if (is_array($positions) and $id) {
            foreach ($positions as $position => $value) {
                $pos = explode('_', $value);

                if (isset($pos[2]) && (int) $pos[2] === $id) {
                    $position = (int) $position;

                    if ($cond = Db::getInstance()->executeS(
                            'SELECT `id_codwfeeplus_cond`,
                            `codwfeeplus_position`
                        FROM `' . _DB_PREFIX_ . 'codwfeeplus_conditions`
                        WHERE `id_codwfeeplus_cond` = ' . $id . '
                        LIMIT 1'
                            )) {
                        Db::getInstance()->execute(
                                'UPDATE `' . _DB_PREFIX_ . 'codwfeeplus_conditions`
                        SET `codwfeeplus_position` = `codwfeeplus_position` ' .
                                ($way ? '- 1' : '+ 1') . '
                        WHERE `codwfeeplus_position`' . (
                                $way ?
                                        ' > ' . (int) $cond[0]['codwfeeplus_position'] .
                                        ' AND `codwfeeplus_position` <= ' . $position :
                                        ' < ' . (int) $cond[0]['codwfeeplus_position'] .
                                        ' AND `codwfeeplus_position` >= ' . $position
                                ) . $where_shop . ';
                        UPDATE `' . _DB_PREFIX_ . 'codwfeeplus_conditions`
                        SET `codwfeeplus_position` = ' . $position . '
                        WHERE `id_codwfeeplus_cond` = ' . $id
                        );
                    }

                    break;
                }
            }
        }
    }

//Various

    private function _notArrayToEmptyArray($inval)
    {
        $ret = array();
        if (is_array($inval)) {
            $ret = $inval;
        }

        return $ret;
    }

    private function getProductStatus()
    {
        $ret = '<div class="codwfeeplus_productstatus">';

        $p_exists = $this->module->getProductStatus();
        $pid = (int) Configuration::get('SG_CODWFEEPLUS_PRODUCT_ID');

        if ($p_exists) {
            $ret .= '<div class="codwfeeplus_productstatus_true">'
                    . '<p>' . $this->l('COD product found in database. Product ID:') . ' '
                    . '<a href="' . $this->context->link->getAdminLink('AdminProducts', true) . '&id_product=' . $pid . '&updateproduct">' . $pid . '</a></p>'
                    . '</div>';
        } else {
            $ret .= '<div class="codwfeeplus_productstatus_false">'
                    . '<p>' . $this->l('COD product was not found in database. Use the button to recreate it.') . ' (Product ID: ' . $pid . ')</p>'
                    . '</div>';
        }
        $ret .= '</div>';

        return $ret;
    }

    private function resetProduct()
    {
        $this->_msg = array();
        $this->_errors = array();
        $ret = true;

        $ret &= $this->module->installCODProduct();

        if ($ret) {
            $this->_msg[] = $this->l('Successful resetted COD Product');
        } else {
            $this->_errors[] = $this->l('Failed to reset COD Product.');
        }

        return $ret;
    }

    private function renderMultistoreInvalidSelection($display_mode)
    {
        $name = $this->l('Message');
        $icon = 'icon-envelope';
        $content = '<div class="alert alert-warning">'
                . '<p>' . $this->l('This content is only avaliable when a store is selected in a multistore installation.') . '</p>'
                . '</div>';
        $ret = '';
        $ret .= '<div class="panel col-lg-12">'
                . '<div class="panel-heading">'
                . '<i class="' . $icon . '"></i>'
                . '   ' . $name
                . '</div>';
        $ret .= $content;
        $ret .= '</div>';

        return $ret;
    }

    private function renderModuleInactiveWarning($display_mode)
    {
        $name = $this->l('Message');
        $icon = 'icon-envelope';
        $content = '<div class="alert alert-danger">'
                . '<p>' . $this->l('The module is not active. Please activate it.') . '</p>'
                . '</div>';
        $ret = '';
        $ret .= '<div class="panel col-lg-12">'
                . '<div class="panel-heading">'
                . '<i class="' . $icon . '"></i>'
                . '   ' . $name
                . '</div>';
        $ret .= $content;
        $ret .= '</div>';

        return $ret;
    }

    private function areThereOtherShops()
    {
        $ret = false;
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $current_shop = Shop::getContextShopID();
                $shop_list = Shop::getShops(true, null, true);
                foreach ($shop_list as $value) {
                    if ($value != $current_shop) {
                        $ret |= true;
                    }
                }
            }
        }

        return $ret;
    }

}
