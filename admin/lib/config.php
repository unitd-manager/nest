<?
$cpCfg = array();
$cpCfg['cp.theme'] = 'MaterialTim';
$cpCfg['cp.jqVersion']   = '1.9.1';
$cpCfg['cp.jqUiVersion'] = '1.9.2';
$cpCfg['cp.tradingLoginText'] = 1;
$cpCfg['cp.hasAccessModule'] = true;
$cpCfg['cp.assetVersion'] = '100';
//$cpCfg['cp.multiLang'] = true;
$cpCfg['paymentReminder']    = 0;
$cpCfg['paymentReminder2']   = 0;

$cpCfg['cp.availableLanguages'] = array(
    // ONlY TWO LANGUAGES ARE ADDED.//
     'eng' => 'English'
    ,'tam' => 'Tamil'
);

$cpCfg['cp.hasAdminInterfaceLangs'] = true;
$cpCfg['cp.adminInterfaceLangs'] = array(
    'eng' => 'English',
    'tam' => 'Tamil',
);
$cpCfg['cp.topRooms'] = array(
    'order' => array(
        'title' => 'Trade'
       ,'modules' => array(
             'tradingsg_pos'
            //,'tradingsg_home'
            ,'common_dashboard'
            ,'tradingsg_quote'
            ,'tradingsg_order'
            ,'tradingsg_product'
            ,'tradingsg_company'
            ,'tradingsg_contact'
       )
       ,'default' => 'tradingsg_pos'
    )

    ,'inventory' => array(
        'title' => 'Inventory'
       ,'modules' => array(
             'tradingin_inventory'
            ,'tradingsg_purchaseOrder'
            ,'tradingsg_supplier'
            ,'tradingsg_stockTransfer'
            ,'tradingsg_expense'
            ,'tradingsg_expenseHead'
            ,'tradingsg_reports'

       )
       ,'default' => 'tradingin_inventory'
    )

    ,'admin' => array(
        'title' => 'Admin'
       ,'modules' => array(
             'core_userGroup'
            ,'core_staff'
            ,'tradingsg_staffAttendance'
            ,'webBasic_category'
            ,'webBasic_subCategory'
            ,'webBasic_content'
            ,'core_valuelist'
            ,'core_setting'
            ,'core_adminTranslation'
            ,'core_translation'
       )
       ,'default' => 'core_staff'
    )
);

$hiddenModules = array(
     'common_contactLink'
    ,'common_testRecipientLink'
    ,'common_interestLink'
    ,'ecommerce_orderItemLink'
    ,'tradingsg_companyLink'
    ,'tradingsg_productLink'
    ,'tradingsg_categoryLink'
    ,'tradingsg_purchaseOrderLink'
    ,'ecommerce_product'
    ,'tradingsg_contactLink'
    ,'tradingsg_quoteLink'
    ,'tradingsg_expenseLink'
    ,'tradingsg_discountLink'
    ,'tradingsg_batchHistoryLink'
    ,'tradingsg_productGroupLink'
 );

$tmpName = &$cpCfg['cp.topRooms'];
$cpCfg['cp.availableModules'] = array_merge(
      $tmpName['order']['modules']
    , $tmpName['inventory']['modules']
    , $tmpName['admin']['modules']
    , $hiddenModules);

$cpCfg['cp.availableModGroups'] = array(
     'core'
    ,'common'
    ,'webBasic'
    ,'ecommerce'
    ,'tradingsg'
    ,'tradingin'
    ,'project'
);

$cpCfg['cp.availableWidgets'] = array(
     'tradingsg_invoiceSummary'
    ,'tradingsg_enquiryFollowUp'
    ,'tradingsg_quoteFollowUp'
    ,'tradingsg_leadFollowUp'
    ,'tradingsg_leadByStaff'
    ,'tradingsg_salesByMonthChart'
    ,'tradingsg_salesByYearChart'
    ,'tradingsg_invoiceChartByMonth'
    ,'tradingsg_invoiceSummary'
    ,'tradingsg_enquiryByMonthChart'
    ,'tradingsg_quoteValueByMonthChart'
    ,'tradingsg_salesByMonth'
    ,'tradingsg_salesByYear'
    ,'tradingsg_invoiceByMonth'
    ,'tradingsg_invoiceByYear'
    ,'tradingsg_profitByMonth'
    ,'tradingsg_profitByYear'
    ,'tradingsg_quoteByMonth'
    ,'tradingsg_quoteByYear'
    ,'tradingsg_salesByClient'
    ,'tradingsg_invoiceByClient'
    ,'tradingsg_enquiryByMonth'
    ,'tradingsg_enquiryByYear'
    ,'tradingsg_enquiryByStaff'
    ,'tradingsg_enquiryActivityByStaff'
    ,'tradingsg_salesSummaryByProduct'
    ,'tradingsg_quoteByStaff'
    ,'tradingsg_detailInvoiceByMonth'
    ,'tradingsg_salesSummaryByProductGroup'
    ,'tradingsg_invoiceSummaryByProductGroup'
    ,'tradingsg_stockReport'
 	,'tradingsg_restaurent1'
    ,'tradingsg_restaurent2'
    ,'tradingsg_restaurent3'
    ,'tradingsg_restaurent4'
    ,'tradingsg_restaurent5'
    ,'tradingsg_restaurent6'
    ,'tradingsg_detailSummaryByClient'
    ,'tradingsg_quoteByStaffChart'
    ,'tradingsg_dailyCollectionReport'
    ,'tradingsg_salesSummaryByProduct'
    ,'tradingsg_priceTrackReport'
    ,'tradingsg_stockReport'
    ,'tradingsg_top10SellingProducts'
    ,'tradingsg_stockMOLProducts'
    ,'tradingsg_topSupplierOutstanding'
    ,'tradingsg_zeroTransactionProducts'
    ,'tradingsg_dailyCollectionChart'
    ,'tradingsg_supplierPaymentReport'
    ,'tradingsg_gSTREPORT'
    ,'tradingsg_invoiceByClient'
    ,'tradingsg_productDelivery'
    ,'tradingsg_invoicesForVat'
    ,'tradingsg_detailVatPercentForInvoice'
    ,'tradingsg_detailCollectionReport'
    ,'common_language'
    ,'common_adminTranslation'
    ,'tradingsg_topCustomerOutstanding'
    ,'tradingsg_stockTransferReport'
    ,'tradingsg_purchaseGstReport'
    ,'tradingsg_salesGstReport'
);

$cpCfg['cp.availablePlugins'] = array(
     'common_comment'
    ,'common_media'
    ,'common_login'
    ,'member_forgotPassword'
);

return $cpCfg;