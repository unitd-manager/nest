<?
$cpCfg = array();

//------------ MASTER --------------//
$cpCfg['cp.captureAutoLogin'] = true;
$cpCfg['p.common.login.adminHasPaypalSubscription'] = true;
$cpCfg['m.ecommerce.order.hasDiscount'] = 1;
$cpCfg['cp.posUnitPriceEditable'] = 1;
//------------ COURSE --------------//
$cpCfg['m.webBasic.section.hasBanner'] = 1;
$cpCfg['cp.footerCompanyLink']  = "<a href='http://www.usoftsolutions.com' target='_blank'>USS</a>";
$cpCfg['m.tradingsg.quote.markUpDiscountfromCompany'] = true;
$cpCfg['m.tradingsg.quote.markUpBySellingPrice'] = true;
$cpCfg['m.webBasic.category.displayTradingmassProductGroup'] = 1;
$cpCfg['m.tradingsg.quote.displayTradingmassClientName'] = true;
$cpCfg['m.tradingsg.quote.displayTradingmassClientNameValidate'] = true;
$cpCfg['m.tradingsg.quote.showExportExcellC2'] = true;
$cpCfg['m.tradingsg.quote.displayNoDiscountInQuote'] = true;
$cpCfg['m.tradingsg.quote.printQuoteGeneralTrading'] = true;
$cpCfg['m.tradingsg.discountLink.showDiscount'] = false;
$cpCfg['m.core.staff.showUserGroup'] = 1;
$cpCfg['cp.cuboLoginText'] = true;
/************** ORDER *************/
$cpCfg['m.tradingsg.order.showCaptainCopyButton'] = false;


/************** PRODUCT *************/
$cpCfg['m.tradingsg.product.displayTradingmassProductName'] = true;
$cpCfg['m.tradingsg.product.displayTradingmassProductNameValidate'] = true;

/************** COMPANY *************/
$cpCfg['m.tradingsg.company.hasMarkUpPercent'] = true;
$cpCfg['m.tradingsg.company.hasCstNo'] = true;
$cpCfg['m.tradingsg.company.hasTinNo'] = true;
$cpCfg['m.tradingsg.company.hasDiscountPercent'] = false;
$cpCfg['m.tradingsg.company.hasGstNo'] = true;

/************** SUPPLIER *************/
$cpCfg['m.tradingsg.supplier.hasDiscountPercent'] = true;
$cpCfg['m.tradingsg.supplier.hasCstNo'] = true;
$cpCfg['m.tradingsg.supplier.hasTinNo'] = true;

/************** PO *************/
$cpCfg['m.tradingsg.purchaseOrder.showInvoiceButton'] = false;

/************** CALL REGISTRY *************/
$cpCfg['m.tradingsg.callRegistry.hasCandidate'] = true;
$cpCfg['m.tradingsg.callRegistry.companyFromProjectModuleForCrm'] = false;
$cpCfg['m.tradingsg.callRegistry.hasNoOfCandidate'] = true;

/************** Enquiry *************/
$cpCfg['m.tradingsg.enquiry.hasEnquiryProductGroupLink'] 	= true;

/************** REPORTS *************/
/* To include price from supplier field */
$cpCfg['m.tradingsg.reports.hasPriceFromSupplierInProfit'] = 1;

$cpCfg['m.webBasic.section.recordTypeArr'] = array (
     'Content'
    ,'Home'
    ,'Site Search'
    ,'Contact Us'
    ,'============='
    ,'Product'
    ,'Login'
    ,'Register'
    ,'My Profile'
    ,'Basket'
    ,'============='
);

$cpCfg['m.webBasic.category.recordTypeArr'] = array(
     'Content'
    ,'Enquiry Form'
    ,'My Profile'
    ,'============='
    ,'View Basket'
    ,'Shipping Details'
    ,'Confirm Order'
    ,'============='
    ,'Order Success'
    ,'Order Fail'
    ,'Order Cancel'
    ,'============='
);

$cpCfg['m.core.valuelist.recordTypeArr']     = array(
     'contactTitle' => 'Salutation'
    ,'callRegistryStatus'   => 'Leads Status'
    ,'callRegistryCategory' => 'Leads Category'
    ,'callRegistryReffer'  => 'Leads Reffer'
    ,'callRegistryIndustry' => 'Leads Industry'
    ,'enquiryStatus'    => 'Enquiry Status'
    ,'enquiryClientType'=> 'Enquiry Client Type'
    ,'deliveryTerms' => 'Delivery Terms'
    ,'paymentTerms' => 'Payment Terms'
    ,'quotePriority' => 'Priority'
    ,'partyType' => 'Party Type'
    ,'invoiceType' => 'Invoice Type'
    ,'collection' => 'Collection'
    ,'productUnit' => 'UOM'
    ,'hardware' => 'Hardware'
    ,'currency' => 'Currency'
    ,'contactTitle' => 'Contact Title'
    ,'customerType' => 'Customer Type'
    ,'companyCategory' => 'Company Category'
    ,'contactTitle' => 'Salutation'
    ,'paymentType' => 'Payment Type'
    ,'invoiceStatus' => 'Invoice Status'
    ,'deliveryLocation' => 'Delivery Location'
    ,'batchImportStatus' => 'Batch Import Status'
    ,'cuboSaleProducts' => 'Cubo Sale Products'
    ,'loyaltyPoint'     => '1000'
    ,'stockLocation'     => 'Stock Location'
    ,'tableValue'     => 'Table Value'
);

$cpCfg['m.trading.product.quoteProductStatusArr'] = array (
     'New'
    ,'PO Raised'
    ,'Invoiced to Customer'
    ,'On Hold'
    ,'Cancelled'
);

return $cpCfg;