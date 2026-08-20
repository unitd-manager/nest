<?
class CPL_Admin_Modules_Tradingsg_Pos_View extends CP_Common_Lib_ModuleViewAbstract
{
    /**
     *
     */
    function getList($dataArray){
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $tv      = Zend_Registry::get('tv');
        $ln      = Zend_Registry::get('ln');
        $cpUtil  = Zend_Registry::get('cpUtil');
        $cpCfg   = Zend_Registry::get('cpCfg');
        $formObj = Zend_Registry::get('formObj');

        $text = '';
        $rows = '';
        $TableVal="";

        $readonly = '';
        $OrderItems = '';
        $modeOfPayment = '';
        $overallNetTotal = '';
        $mopArray = array(
            "Cash"
           ,"Credit Card"
        );

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        /*<input type='submit' class='button ml10' value='Add'>*/
        $rowOrder = $fn->getRecordRowByID('order', 'order_id', $session_order_id);

        $site_id        = $fn->getSessionParam('cp_site_id');

        if($session_order_id < 10){
            $orderId = '0000' . $session_order_id;
        }
        else if($session_order_id <= 99){
            $orderId = '000' . $session_order_id;
        }
        else if($session_order_id <= 999){
            $orderId = '00' . $session_order_id;
        }
        else if($session_order_id <= 9999){
            $orderId = '0' . $session_order_id;
        }
        else{
            $orderId = $session_order_id;
        }

        if($session_order_id == ''){
            $readonly = 'readonly';
            $css = "style='background-color:grey;'";
            $buttonCss = "allButtonsHide";
            $showOrderId = '';
            $showBillNo = '';
            $netTotal = '';
            $netTotalFormatted = '';
            $gstButton = '';
            $collectionAmt = '';
            $gstOrderItemColumn = '';
            $orderDate = '';
            $weightColumn = '';
            $packSizeColumn = '';
            $NoOfItemsCount = '';
        }

        if($session_order_id != ''){
            $OrderItems = $this->getOrderItems();
            $css = '';
            $buttonCss   = 'allButtonsShow';
            $showOrderId = "ORD NO: {$orderId}";
            $bill_number = $fn->getSettingsValueByKey("nextBillNumber");
            
            if($bill_number < 10){
                $billNo = '0000' . $bill_number;
            }
            else if($bill_number <= 99){
                $billNo = '000' . $bill_number;
            }
            else if($bill_number <= 999){
                $billNo = '00' . $bill_number;
            }
            else if($bill_number <= 9999){
                $billNo = '0' . $bill_number;
            }
            else{
                $billNo = $bill_number;
            }

            

        $SQLMode1 = "
        SELECT table_value
        FROM `order`
        WHERE order_id = '{$session_order_id}'
        ";
        $resultMode1 = $db->sql_query($SQLMode1);
        $recValue = $db->sql_fetchrow($resultMode1);


        $sqlTableVal = $fn->getValueListSQL('tableValue');


        $expVl = array('sqlType' => 'OneField', 'firstOptionLabel' => 'Select Table');
        if ($cpCfg['cp.restaurent'] == 1){
            $TableVal   = $formObj->getDDRowBySQL('Table', 'table_value', $sqlTableVal, $recValue['table_value'], $expVl);
        }


            $showBillNo  = "BILL NO: {$rowOrder['bill_number']}";

            $SQLMode = "
            SELECT mode_of_payment
            FROM `order`
            WHERE order_id = '{$session_order_id}'
            ";
            $resultMode = $db->sql_query($SQLMode);
            $recMode = $db->sql_fetchrow($resultMode);

            $modOfPaymeny = "Cash";
            if($recMode['mode_of_payment'] != ""){
                $modOfPaymeny = $recMode['mode_of_payment'];
            }

            $modeOfPayment ="
            Mode Of Payment
            <select id='fld_mode_of_payment' name='mode_of_payment'>
                <option value=''>Please Select</option>
                {$cpUtil->getDropDown1($mopArray, $modOfPaymeny)}
            </select>
            ";

            $netTotal = $this->getTotalAmount($session_order_id);
            $netTotalFormatted = number_format(round($netTotal), 2);

            $onButtonClass = "btn-default";
            if($rowOrder['gst_status'] == "ON"){
                $onButtonClass = "btn-success active";
            }

            $offButtonClass = "btn-default";
            if($rowOrder['gst_status'] == "OFF"){
                $offButtonClass = "btn-success active";
            }

            $gstOrderItemColumn = "displayNone";
            if($rowOrder['gst_status'] == "ON"){
                $gstOrderItemColumn = "";
            }

            $weightColumn = "displayNone";
            if($cpCfg['showWeightInPos'] == 1){
                $weightColumn = "";
            }

            $packSizeColumn = "displayNone";
            if($cpCfg['showPackSizeInPos'] == 1){
                $packSizeColumn = "";
            }

            $gstButton = "";
            if($cpCfg['showGstOnOff'] == 1){
                $gstButton = "
                <div class='float_left gstOnOff'>
                    <div class='float_left gstToggleText'>
                    </div>
                    <div class='btn-group btn-toggle'> 
                        <button class='btn {$onButtonClass}'>ON</button>
                        <button class='btn {$offButtonClass}'>OFF</button>
                        <input name='gst_selected' value='ON' type='hidden'/>
                    </div>
                </div>
                ";
            }

            $collectionAmt = "";
            if($cpCfg['cp.showCollectionForTheDay'] == 1){
                $currentDate = date('Y-m-d');

                $SQLCollection = "
                SELECT SUM(invoice_amount) AS total_amount
                FROM `invoice`
                WHERE order_id != '{$session_order_id}'
                AND status != 'Cancelled'
                AND invoice_date = '{$currentDate}'
                ";
                $resultCollection = $db->sql_query($SQLCollection);
                $recCollection    = $db->sql_fetchrow($resultCollection);

                $totalCollection = $recCollection['total_amount'];

                if($totalCollection == ""){
                    $totalCollection = 0; 
                }

                $totalCollection = number_format($totalCollection, 2);

                $collectionAmt = "
                <div class='float_left OrderNoOnTop'>
                    COL AMT: {$totalCollection}
                </div>
                ";
            }

            $orderDate = "
            <div class='orderDatePOSHeader float_left'>
                {$formObj->getDateRow('', 'order_date', $rowOrder['order_date'])}
            </div>
            ";

            $NoOfItemsCount = $fn->getRecordCount('order_item', "order_id = '{$session_order_id}'");
        }

        $SQLInvoice = "
        SELECT *
        FROM `invoice`
        WHERE order_id = '{$session_order_id}'
        ";
        $resultInvoice = $db->sql_query($SQLInvoice);
        $rec = $db->sql_fetchrow($resultInvoice);

        $invoice_code = $rec['invoice_code'];

            // CHECK PENDING ORDER //
        $checkPendingOrder = '' ;
        $printThermalPrinter = '';
        $thermalPrinter = '';

        $disableBtn = '';
        if($NoOfItemsCount == 0){
            $disableBtn = "disabled";
        }

        if ($session_order_id !=''){
            $checkPendingOrder ="<input type='button' id='changeStatusPending' class='btn btn-warning txt_16px' value='Change To Pending' {$disableBtn}>
                                 <input type='button' id='checkPendingOrder' class='btn btn-success txt_16px' value='Check Pending Order'>";
        } else {
            $checkPendingOrder ="<input type='button' id='checkPendingOrder' class='btn btn-success txt_16px' value='Check Pending Order'>" ;
        }

        //$url = "index.php?_topRm=pos&module={$tv['module']}&_spAction=printBill&invoice_code={$invoice_code}&order_id={$session_order_id}&showHTML=0";
        //$returnUrl = "<a href='{$url}' target=_blank ></a>";
                    //<input type='button' id='checkPendingOrder' class='button mb10' value='Check Pending Order'>
        $formUpdateDiscount        = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=applyDiscount&discountApplied={$rowOrder['discount']}&showHTML=0";
        $formAddClient             = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=addClient&showHTML=0";
        $formUpdateShippingCharges = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=applyShippingCharges&shippingChargeApplied={$rowOrder['shipping_charge']}&showHTML=0";
        $formAddDicountType        = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=addDefaultDiscountType&showHTML=0";

        //if(strpos($_SERVER['HTTP_HOST'], 'localhost') || strpos($_SERVER['HTTP_HOST'], 'testpilotweb')){
            //<a href='PrintExcel.php' id='thermalPrinter'>Thermal Print</a>
            $printThermalPrinter ="
            <div class='button float_right thermalPrintHide'>
                <a onclick=\"javascript:jsWebClientPrint.print('orderId={$session_order_id}');\"
                id='thermalPrinter'>Thermal Print</a>
            </div>
            ";

            $thermalPrinter ="
            <div class='btn btn-info float_right'>
                <a href='#' id='thermalPrinterPrint'><span class='printBillIcon'></span>Print</a>
            </div>
            ";
        //}

        /*<th>Discount Type</th>
          <th>Discount Value</th>*/
        $customerInfo ='';
        $removeClient = '';

        if($rowOrder['cust_company_name'] != ''){

        $loyaltypoint = '';

        $SQLloyal = "
        SELECT loyalty_point_linked
        FROM `company` c
        WHERE company_name = '{$rowOrder['cust_company_name']}'
        ";
        $resultloyal = $db->sql_query($SQLloyal);
        $rowloyal = $db->sql_fetchrow($resultloyal);

        if($rowloyal['loyalty_point_linked'] != ''){
            $loyaltypoint ="Loyalty point linked";
        }else {
            $loyaltypoint ="
                <div class='btn btn-info'>
                    <a cust_company_name='{$rowOrder['cust_company_name']}' href='#' class='loyaltypoint'>Link Loyalty Point</a>
                </div>
            ";
        }
            if($cpCfg['cp.restaurent'] == 0){
            $customerInfo ="
            <div class='mt10'>
                <div>Company Name: {$rowOrder['cust_company_name']}</div>
                <div>Mobile: {$rowOrder['cust_phone']}</div>
                <div>Email : {$rowOrder['cust_email']}</div>
                <div>Address: {$rowOrder['cust_address1']} ,{$rowOrder['cust_address2']} ,{$rowOrder['cust_address_city']} {$rowOrder['cust_address_state']}</div>
                {$loyaltypoint}
            </div>
            ";

            $removeClient = "
            <div class='btn btn-info float_left mt10'>
                <a href='javascript:void(0);' id='removeClient'><span class='removeClientIcon'></span>Remove Client</a>
            </div>
            ";
            }
        }

        $fnMod = includeCPClass('ModuleFns', 'core_adminTranslation');
        $arr = $fnMod->getAdminTranslationGroupArray();
        $viewAllOrderLink = "index.php?module=tradingsg_order";
        $formSalesName  = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=saleByName&order_id='{$session_order_id}'&showHTML=0";
        
        if($cpCfg['cp.companyType'] == "Computer Shop"){
            $startOrderBtn = "<input type='button' id='newOrderNormal' class='buttonpos btn btn-success' value='Start Order'>";
        }
        else{
            $startOrderBtn = "
            <div class='btn btn-success'>
                <a href='{$formSalesName}' id='newOrder'>
                    Start Order
                </a>
            </div>
            ";
            $startOrderBtn = "<input type='button' id='newOrderNormal' class='buttonpos btn btn-success' value='Start Order'>";
        }
        
        $product_code="";
        if ($cpCfg['cp.serialKeyActive'] == "SULB-DHEO-0R6K-59CL" || $cpCfg['cp.serialKeyActive'] == "YODX0-9DT58-VCZ5W-A8XXB" || $cpCfg['cp.restaurent'] == 0) {
            $product_code = "<th width='8%'>{$ln->gd('cp.label.productCode','Product Code')}</th>";
        }

        if ($cpCfg['cp.restaurent'] == 0){
            $product_code = "            <th width='6%' class='txtCenter'>{$ln->gd('cp.label.ref_no','Ref No')}</th>
<th width='8%'>{$ln->gd('cp.label.tagNo','Tag No')}</th>
 <th width='6%'  class='txtCenter'>{$ln->gd('cp.label.unit','UOM')}</th>
                            <th width='6%'  class='txtCenter {$packSizeColumn}'>{$ln->gd('cp.label.packSize','Pack Size')}</th>";

                           
        }
        $otherField="";
        $clientHide="";
        if ($cpCfg['cp.restaurent'] == 0){
            $otherField = "
        <th width='8%'> 
            <a class='discountTypeHeader' href='{$formAddDicountType}'>
                {$ln->gd('cp.label.discountType','Discount Type')}</th>
            </a>
        <th width='8%'  class='txtRight discountAmountHeader'>{$ln->gd('cp.label.discount', 'Discount')}</th>
        ";
            $clientHide="<div class='btn btn-info float_left'>
                        <a href='{$formAddClient}' id='addClient'><span class='addClientIcon'></span>Add Client</a>
                    </div>
                    <input type='text' value='' id='fld_customer_name' placeholder='Choose Client' class='text' name='customer_name' {$readonly} {$css}>";
                           
        }
        
        //<th width='6%' class='txtCenter {$weightColumn}'>{$ln->gd('cp.label.wt','Weight')}</th>
        $text = "
        <div class='panel panel-info'>
            <div class='panel-heading floatbox'>
                {$orderDate}
                <div class='float_left modeOfPaymentDropdown'>
                    {$modeOfPayment}
                </div>
                <div class='float_left modeOfPaymentDropdown'>
                    {$TableVal}
                    {$clientHide}
                </div>
                {$gstButton}
                {$collectionAmt}
                <div class='float_left viewAllOrderBtn ml20'>
                    <a target='_blank' href='{$viewAllOrderLink}' id='viewAllOrder' class='btn btn-primary'>
                        View All Order
                    </a>
                </div>
                <div class='float_left'>
                    {$startOrderBtn}
                    <input type='button' id='cancelOrder' class='buttonpos2 btn btn-primary' value='End Order'>
                </div>
                <div class='float_left'>
                    <a order_id='{$session_order_id}' id='cancelOrderPOS' class='btn btn-danger ml20'>
                        Cancel Order
                    </a>
                </div>
            </div>
            <div class='panel-body'>
                <table class='addProductTablePOS' width='100%'>
                    <tr>
                        <td width='33%' data-label='Product'>
                            <div class='floatbox'>
                                <div class='addProduct'>
                                    <input type='text' value='' id='fld_product_title' class='text' name='product_title' placeholder='Please Type Product Name / Item Code' {$readonly} {$css}>
                                </div>
                             </div>
                        </td>
                        <td width='15%' align='center' data-label='Check Pending Order'>
                            {$checkPendingOrder}
                        </td>
                        <td width='15%' align='center' data-label='Bill No / Order No'>
                            <div class='OrderNoOnTop'>
                                {$showBillNo}
                            </div>
                            <div class='OrderNoOnTop'>
                                {$showOrderId}
                            </div>
                        </td>
                        <td width='6%' align='center' data-label='No.of Products'>
                            <b>No.of Products:</b>
                            <div class='OrderNoOnTop NoOfProductsOnTop'>
                                {$NoOfItemsCount}
                            </div>
                        </td>
                        <td width='31%' data-label='Total Amount'>
                         " . ($_SESSION['userGroupName'] == 'Waiter' ? "

                            <div class='TotalAmountDisplayTop float_right'>
                             
                            </div>
                               " : "
                                <div class='TotalAmountDisplayTop float_right'>
                                {$netTotalFormatted}
                                </div>
                               
                                 ") . "
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class='panel panel-info'>
            <div class='panel-body'>
                <table class='list thinlist'>
                    <thead>
                    " . ($_SESSION['userGroupName'] == 'Waiter' ? "
                    <tr class='txt_16px'>
                        <th width='4%'>{$ln->gd('cp.label.serialNo','S.No')}</th>
                        <th width='29%'>{$ln->gd('cp.label.itemName', 'Product Name')}</th>
                        <th width='6%' class='txtCenter'>{$ln->gd('cp.label.qty','Qty')}</th>
                        <th width='3%'>{$ln->gd('cp.label.delete','Delete')}</th>
                    </tr>
                    " : "
                    <tr class='txt_16px'>
                        <th width='4%'>{$ln->gd('cp.label.serialNo','S.No')}</th>
                        <th width='29%'>{$ln->gd('cp.label.itemName', 'Product Name')}</th>
                        {$product_code}
                        <th width='6%' class='txtCenter'>{$ln->gd('cp.label.qty','Qty')}</th>
                        <th width='9%' class='txtRight'>{$ln->gd('cp.label.unitPrice','Unit Price')}</th>
                        <th width='9%' class=''>{$ln->gd('cp.label.unitPrice','History')}</th>
                        {$otherField}
                        <th width='8%' class='txtRight GstColumnHeader {$gstOrderItemColumn}'>{$ln->gd('cp.label.gstPercentage','GST(%)')}</th>
                        <th width='11%' class='txtRight'>{$ln->gd('cp.label.total','Total')}</th>
                        <th width='3%'>{$ln->gd('cp.label.delete','Delete')}</th>
                    </tr>
                    ") . "
                    </thead>
                    <tbody id='orderItems'>
                        {$OrderItems}
                    </tbody>
                </table>
                " . ($_SESSION['userGroupName'] != 'Waiter' ? "
                <div class='floatbox mt20 {$buttonCss}'>
                    {$thermalPrinter}
                    <div class='btn btn-info float_right'>
                        <a href='{$formUpdateDiscount}' id='applyDiscount'><span class='applyDiscountIcon'></span>
                            Apply Discount
                        </a>
                    </div>
        
                    <div class='btn btn-info float_right'>
                        <a href='{$formUpdateShippingCharges}' id='applyShippingCharge'><span class='applyShippingChargeIcon'></span>
                            Shipping Charges
                        </a>
                    </div>
        
                    {$printThermalPrinter}
        
                    
                </div>
                <div class='floatbox' id='customerDetailsDisplay'>
                    {$customerInfo}
                    {$removeClient}
                </div>
                  " : "
                  <div></div>
                ") . "
            </div>
        </div>
        <style>
            @media screen and (max-width: 600px) {
                .addProductTablePOS, .addProductTablePOS thead, .addProductTablePOS tbody, .addProductTablePOS th, .addProductTablePOS td, .addProductTablePOS tr {
                    display: block;
                    width: 100%;
                }
    
                .addProductTablePOS tr {
                    margin-bottom: 15px;
                }
    
                .addProductTablePOS td {
                    text-align: right;
                    padding-left: 0%;
                    position: relative;
                }
    
                .addProductTablePOS td::before {
                    content: attr(data-label);
                    position: absolute;
                    left: 10px;
                    width: 45%;
                    padding-right: 10px;
                    text-align: left;
                    white-space: nowrap;
                }
            }
    
            @media screen and (min-width: 601px) and (max-width: 1024px) {
                .addProductTablePOS, .addProductTablePOS thead, .addProductTablePOS tbody, .addProductTablePOS th, .addProductTablePOS td, .addProductTablePOS tr {
                    display: block;
                    width: 100%;
                }
    
                .addProductTablePOS tr {
                    margin-bottom: 15px;
                }
    
                .addProductTablePOS td {
                    text-align: right;
                    padding-left: 0%;
                    position: relative;
                }
    
                .addProductTablePOS td::before {
                    content: attr(data-label);
                    position: absolute;
                    left: 10px;
                    width: 45%;
                    padding-right: 10px;
                    text-align: left;
                    white-space: nowrap;
                }
            }
        </style>
    ";
    
    return $text;
}
    /**
     *
     */
    function getOrderItems(){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $formObj = Zend_Registry::get('formObj');
        $cpCfg = Zend_Registry::get('cpCfg');
        $expNoEdit  = array('isEditable' => 0);
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : false;

        $text = '';
        $qtytotal = '';
        $rows = '';
        $subTotal = 0;
        $netTotal = 0;
        $discount = '';
        $discount_percentage_amount_sum = '';
        $discountValue = '';
        $Overallsubtotalwithoutdiscount = 0;

        //New Changes
        //$sqlDiscountVal = $fn->getValueListSQL('discountValue');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $sqlDiscountVal = "SELECT CONCAT_WS(' ',value,'%') FROM valuelist
                                    WHERE key_text='discountValue'
                                    ORDER BY value ASC";
        $sqlDiscountType = array("%", "Value");
        $expVl      = array('sqlType' => 'OneField','firstOptionLabel' => 'No Discount');

        //TO CHECK IF THE SUM OF DISCOUNT TYPE(%) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForPercentSum = "
        SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2)) as discount_sum
        FROM order_item oi
        WHERE oi.order_id = {$session_order_id}
          AND oi.discount_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForPercentSum = "
            SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2))
            FROM order_item oi
            WHERE oi.order_id = {$session_order_id}
              AND oi.discount_type = '%'
            ";
        }
        else{
            $subSqlForPercentSum = 0;
        }


        //TO CHECK IF THE SUM OF DISCOUNT TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForValueSum ="
        SELECT SUM(round(oi.discount_amount  * oi.qty,2)) as discount_sum
        FROM order_item oi
        WHERE oi.order_id = {$session_order_id}
          AND oi.discount_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlForValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForValueSum ="
            SELECT SUM(round(oi.discount_amount  * oi.qty,2))
            FROM order_item oi
            WHERE oi.order_id = {$session_order_id}
              AND oi.discount_type = 'Value'
            ";
        }
        else{
            $subSqlForValueSum = 0;
        }

        $SQL    = "
        SELECT oi.*
              ,o.discount
              ,o.gst_status
              ,CONCAT_WS('::', p.carton_no, p.batch_no, p.model) as code
              ,p.unit
              ,p.pack_size
              ,p.product_code
              ,(SELECT
              ($subSqlForPercentSum)
               +
              ($subSqlForValueSum)) as discount_percentage_amount_sum
              ,o.shipping_charge
        FROM order_item oi
        LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
        LEFT JOIN (product p) ON (p.product_id = oi.record_id)
        WHERE oi.order_id = {$session_order_id}
        ORDER BY oi.order_item_id DESC
        ";
        $result = $db->sql_query($SQL);
        $count           = 1;
        $gstValue        = 0;
        $numRows = $db->sql_numrows($result);
        while ($row = $db->sql_fetchrow($result)) {
            $discount_value_for_one_qty = '';
            $discountValue = 0;
            $discount_percentage = '';
            $discount_percentage_type =0;
            if($row['discount_percentage'] > 0 || $row['discount_amount'] > 0){
                if($row['discount_type'] == '%'){
                    $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                    $discount_percentage_type = $discountValue;
                    $discount_percentage = $row['discount_percentage'];
                }
                else if($row['discount_type']  == 'Value'){
                    $discount_value_for_one_qty  =  $row['discount_amount'];
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                    $discount_percentage = $row['discount_amount'];
                    $discount_percentage_type = $row['discount_amount'];
                }
                $discountValue = number_format($discountValue, 2);
            }
            //$discount_percentage = number_format($discount_percentage,2);
            if($row['weight'] > 0){
                $subtotalwithoutdiscount = (($row['weight'] * $row['unit_price']) * ($row['gst'] / 100));
                $subtotalwithoutdiscount = $row['weight'] * $row['unit_price'] + $subtotalwithoutdiscount;
                $total = $row['weight'] * ($row['unit_price'] - $discount_value_for_one_qty);                
            } else {
                $subtotalwithoutdiscount = (($row['qty'] * $row['unit_price']) * ($row['gst'] / 100));
                $subtotalwithoutdiscount = $row['qty'] * $row['unit_price'] + $subtotalwithoutdiscount;
                $total = $row['qty'] * ($row['unit_price'] - $discount_value_for_one_qty);
            }
            
            if($row['gst_status'] == "ON"){
                $gstValue = $total * $row['gst'] / 100;
                $total    = $total + $gstValue;
            }
            
            $subTotal += $total;
            $Overallsubtotalwithoutdiscount += $subtotalwithoutdiscount;
            $discount = $row['discount'];
            $netTotal = $subTotal - $discount;
            $discount_percentage_amount_sum = $row['discount_percentage_amount_sum'] + $discount;

            $StockSql = "
            SELECT
                (SELECT SUM(qty) FROM po_product
                WHERE product_id = {$row['record_id']}) as product_qty_purchased
                ,(SELECT SUM(oi.qty) FROM order_item oi
                LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
                WHERE record_id = {$row['record_id']}
                  AND o.order_status = 'Paid' || o.order_status = 'Partial Payment'
                  AND o.link_stock = 1
                ) as product_qty_sold
                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                WHERE ini.record_id = {$row['record_id']}
                  AND srh.status IS NULL
                ) as sales_return_qty
                ,(SELECT SUM(damage_qty) FROM po_product
                WHERE product_id = {$row['record_id']}
                ) as damaged_qty
            ";
            $resultStockSql = $db->sql_query($StockSql);
            $rowStockSql    = $db->sql_fetchrow($resultStockSql);

            $stock = $rowStockSql['product_qty_purchased'] - $rowStockSql['product_qty_sold'] + $rowStockSql['sales_return_qty'] - $rowStockSql['damaged_qty'];
            $expDiscountType = array('sqlType' => 'OneField');

            $subtotalwithoutdiscount = number_format($subtotalwithoutdiscount, 2);

            $discount_percentage_type = number_format($discount_percentage_type, 2);

            $total    = number_format($total, 2);

            $gstOrderItemColumn = "displayNone";
            if($row['gst_status'] == "ON"){
                $gstValue = number_format($gstValue, 2);
                $gstOrderItemColumn = "";
            }

            $weightColumn = "displayNone";
            if($cpCfg['showWeightInPos'] == 1){
                $weightColumn = "";
            }

            $packSizeColumn = "displayNone";
            if($cpCfg['showPackSizeInPos'] == 1){
                $packSizeColumn = "";
            }
            $product_code="";
            if ($cpCfg['cp.serialKeyActive'] == "SULB-DHEO-0R6K-59CL" || $cpCfg['cp.serialKeyActive'] == "YODX0-9DT58-VCZ5W-A8XXB" || $cpCfg['cp.restaurent'] == 0) {
                $product_code = $row['product_code'];
            }
           
            if ($cpCfg['cp.restaurent'] == 0){
                $product_code ="<td class='txtCenter'><input type='text' value='{$row['ref_no']}' id='fld_ref_no' class='text w50 txtRight txt_16px' name='ref_no' order_item_id='{$row['order_item_id']}'></td>
                <td> {$row['tag_no']}</td>
                <td class='txtCenter'>{$row['unit']}</td>
            <td class='{$packSizeColumn}'>{$row['pack_size']}</td>";

            }
            $otherfield="";
            if ($cpCfg['cp.restaurent'] == 0){
                $otherfield ="
            <td class='w100' order_item_id='{$row['order_item_id']}'>
                {$formObj->getDDRowByArr('', 'discount_type', $sqlDiscountType, $row['discount_type'], $expVl)}
            </td>
            <td class='w100'><input type='text' value='{$discount_percentage}' id='fld_discount_percentage' class='text w100 txtRight' name='discount_percentage' order_item_id='{$row['order_item_id']}'></td>
          ";

            }

            $rowOrder = $fn->getRecordRowByID('order', 'order_id', $session_order_id);
            $unitPriceDetails = "{$row['unit_price']}";
            if ($cpCfg['cp.posUnitPriceEditable'] == 1){
                $unitPriceDetails = "<input type='text' value='{$row['unit_price']}' id='fld_unit_price' class='text {$row['order_item_id']} w75 txtRight txt_16px' name='unit_price' order_item_id='{$row['order_item_id']}'>";
            }

            $history = '';
            if($rowOrder['company_id'] > 0){
                $SQLInvoice = "
                SELECT oi.order_item_id
                FROM `order_item` oi
                LEFT JOIN `order` o ON (o.order_id = oi.order_id)
                WHERE o.company_id = '{$rowOrder['company_id']}'
                  AND oi.order_id != '{$session_order_id}'
                  AND oi.record_id = '{$row['record_id']}'
                ORDER BY oi.order_id DESC
                ";
                $resultInvoice = $db->sql_query($SQLInvoice);
                $numRowsOrder  = $db->sql_numrows($resultInvoice);
                $invoiceRec = $db->sql_fetchrow($resultInvoice);
                if($numRowsOrder > 0) {
                    $history = "<a order_item_id='{$invoiceRec['order_item_id']}' href='#' id='clientHistory'>History</a>";                    
                    $unitPriceDetails = "<input type='text' value='{$row['unit_price']}' id='fld_unit_price' class='text {$row['order_item_id']} w75 txtRight txt_16px' name='unit_price' order_item_id='{$row['order_item_id']}'>";
                }
            }

            //<td class='txtCenter {$weightColumn}'><input type='text' value='{$row['weight']}' id='fld_weight' class='text w50 txtRight txt_16px' name='weight' order_item_id='{$row['order_item_id']}'></td>
            $rows .= "
              " . ($_SESSION['userGroupName'] == 'Waiter' ? "
                <tr class='txt_16px'>
            <tr class ='{$row['order_item_id']} txt_16px'>
            <td class='txtRight'>{$count}</td>
            <td class='w25p'>{$row['item_title']}</td>
           
            
            <td class='txtCenter'><input type='text' value='{$row['qty']}' id='fld_qty' class='text w50 txtRight txt_16px' name='qty' order_item_id='{$row['order_item_id']}' stock='{$stock}'></td>
            
            <td><a href='#' class='deleteItem' order_item_id='{$row['order_item_id']}'>Delete</a></td>
            </tr>
             " : "
            <tr class='txt_16px'>
            <tr class ='{$row['order_item_id']} txt_16px'>
            <td class='txtRight'>{$count}</td>
            <td class='w25p'>{$row['item_title']}</td>
            {$product_code}
            
            <td class='txtCenter'><input type='text' value='{$row['qty']}' id='fld_qty' class='text w50 txtRight txt_16px' name='qty' order_item_id='{$row['order_item_id']}' stock='{$stock}'></td>
             <td class='unitPrice txtRight'>{$unitPriceDetails}</td>
             <td class='unitPrice'>{$history}</td>
            {$otherfield}
              <td class='txtRight GstColumnValue {$gstOrderItemColumn}'>{$gstValue}({$row['gst']}%)</td>
            <td class='txtRight'>{$total}</td>
            <td><a href='#' class='deleteItem' order_item_id='{$row['order_item_id']}'>Delete</a></td>
            </tr>
                ") . "
            ";

            $qtytotal += $row['qty'];
            $count++;
            
        }

        $gst_status      = $rowOrder['gst_status'];
        $shipping_charge = $rowOrder['shipping_charge'];

        if($shipping_charge == ""){
            $shipping_charge = 0;
        }

        $colspan = "10";
        $colspanDis = "4";
        $totalQtyColspan = "5";
        if($gst_status == "ON"){
            $colspan = "11";
            $colspanDis = "5";
        }

        if($cpCfg['showWeightInPos'] == 1){
            $colspan = "10";
            $colspanDis = "4";
        }

        

        if($cpCfg['showPackSizeInPos'] == 1){
            $colspan = "11";
            $colspanDis = "4";
            $totalQtyColspan = "6";
        }

        
        if ($cpCfg['cp.restaurent'] == 1){
            $colspan = "5";
            $colspanDis = "2";
            $totalQtyColspan = "2";
        }

        if($gst_status == "ON" && $cpCfg['showWeightInPos'] == 1){
            $colspan = "11";
            $colspanDis = "5";
        }

        if($gst_status == "ON" && $cpCfg['showWeightInPos'] == 1 && $cpCfg['showPackSizeInPos'] == 1){
            $colspan = "12";
            $colspanDis = "5";
        }
        
        $shipping_charge_row = "";
        if($shipping_charge != "" && $shipping_charge > 0){
            $shippingChargeFormatted = number_format($shipping_charge, 2);
            
            $removeShippingCharge = "
            <a class='removeShippingCharge' href='#'  order_id='{$session_order_id}'>
                <img src='/cmspilotv30/CP/admin/images/icons/btn_remove.png'>
            </a>
            ";

            $shipping_charge_row = "
            <tr>
                <td colspan='{$colspan}' class='totalDiscount totalFontSize'>Shipping Charges</td>
                <td id = 'fld_shipping_charge_amount' class='txtRight totalDiscount totalFontSize'>{$shippingChargeFormatted}</td>
                <td class='txtCenter'>{$removeShippingCharge}</td>
            </tr>
            ";

            $netTotal = $netTotal + $shipping_charge;
        }

        $TotalOVerall   = number_format($netTotal, 2);
        $roundOffAmount = round($netTotal) - $netTotal;
        $netTotal = round($netTotal);

        $overallSubTotal = number_format($subTotal, 2);
        $overallNetTotal = number_format($netTotal, 2);
        $roundOffAmount  = number_format($roundOffAmount, 2);
        $Overallsubtotalwithoutdiscount = number_format($Overallsubtotalwithoutdiscount, 2);
        
        if($discount_percentage_amount_sum == ""){
            $discount_percentage_amount_sum = 0;
        }
        
        $discount_percentage_amount_sum = number_format($discount_percentage_amount_sum, 2);

        
        $text = "
        {$rows}
    ";
    
    if ($_SESSION['userGroupName'] != "Waiter") {
        $text .= "
        <tr>
            <td colspan='{$totalQtyColspan}' class='totalQty'>Total Qty</td>
            <td class='totalQty'>{$qtytotal}</td>
            <td colspan='{$colspanDis}' class='totalDiscount totalFontSize'>Total Discount</td>
            <td id='fld_totalDiscount_amount' class='totalDiscount totalFontSize'>{$discount_percentage_amount_sum}</td>
            <td></td>
        </tr>
        {$shipping_charge_row}
        <tr>
            <td colspan='{$colspan}' class='totalFontSize'>Total Amount</td>
            <td id='fld_net_amount' class='txtRight totalFontSize'>{$TotalOVerall}</td>
            <td></td>
        </tr>
        <tr>
            <td colspan='{$colspan}' class='totalFontSize'>Round Off</td>
            <td id='fld_roundOff_amount' class='txtRight totalFontSize'>{$roundOffAmount}</td>
            <td></td>
        </tr>
        <tr>
            <td colspan='{$colspan}' class='netTotal'>Net Total</td>
            <td id='fld_netTotal_amount' class='txtRight netTotal'>{$overallNetTotal}</td>
            <td></td>
        </tr>
        <tr class='txt_20px'>
            <td colspan='{$colspan}' class='txtRight'>Amount Paid</td>
            <td class='txtRight amountGiven'>
                <input type='text' value='' id='fld_amount_given' class='text w150 txtRight txt_20px' name='amount_given' total='{$netTotal}'>
            </td>
            <td></td>
        </tr>
        <tr class='balanceRow'>
            <td colspan='{$colspan}' class='netTotal'>Change</td>
            <td class='netTotal balance'></td>
            <td></td>
        </tr>
        <input type='hidden' id='fld_subtotal_amount' name='subtotal_amount' value='{$Overallsubtotalwithoutdiscount}'>
        <input type='hidden' id='fld_qty_total' name='qty_total' value='{$qtytotal}'>
        <input type='hidden' id='fld_products_total' name='fld_products_total' value='{$numRows}'>
        <input type='hidden' id='fld_current_order_id' name='fld_current_order_id' value='{$session_order_id}'>
        ";
    }

            /*<td class='txtRight totalDiscount'>
                <input type='text' value='{$discount}' id='fld_discount' class='text w50 txtRight' name='discount' order_id='{$session_order_id}'>
            </td>*/

        return $text;
    }

    /**
     *
     */

     function getApplyDiscount() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');

        $discountApplied = $fn->getReqParam('discountApplied');
        $formAction = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=applyDiscountSubmit&showHTML=0";

        $text = "
        <form id='portalPOSApplyDiscountForm' class='yform columnar' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Discount Amount', 'discount_value', '')}
            <input type='hidden' name='discountApplied' value='{$discountApplied}'/>
        </form>
        ";
        return $text;

    }

    /**
     *
     */

     function getAddClient() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');

        $formAction = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=addClientSubmit&showHTML=0";
        $sqlCountry = getCPModelObj('common_geoCountry')->getCountryDDSQL();
        //$this->model->getUpdateOrderLineItems2();
        $text = "
        <form id='portalForm' class='yform columnar' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Company Name', 'company_name')}
            {$formObj->getTBRow('Mobile', 'mobile')}
            {$formObj->getTBRow('Email', 'email')}
            {$formObj->getTBRow('GST No', 'gst_no')}
            {$formObj->getTBRow('Address1', 'address_flat')}
            {$formObj->getTBRow('Address2', 'address_street')}
            {$formObj->getTBRow('District/ Town', 'address_town')}
            {$formObj->getTBRow('State/ Zip', 'address_state')}
            {$formObj->getDDRowBySQL('Country', 'address_country', $sqlCountry)}
        </form>
        ";
        return $text;

    }


    /**
     *
     */
    function getPrintBillPdf($printOnly = '') {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $searchVar = Zend_Registry::get('searchVar');
        $media = Zend_Registry::get('media');
        $cpPaths = Zend_Registry::get('cpPaths');
        $dbUtil = Zend_Registry::get('dbUtil');

        ini_set('memory_limit', '512M');

        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/fpdf/fpdf.php');
        include_once(CP_LIBRARY_PATH.'lib_php/fpdf-extra/html_table1.php');

        $pdf = new MYPDF();

        $pdf->AddPage();
        $pdf->SetFont('Arial','',11);

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $invoice_code = $fn->getReqParam('invoice_code');
        //$order_id = $fn->getReqParam('order_id');
        $orderNo = $fn->getReqParam('orderNo');
        $discountValueTotal = 0;

        if($printOnly == '') {
            $printOnly = $fn->getReqParam('printOnly');
        }

        if ($printOnly != 1 ) {
            $SQL    = "
            UPDATE `order`
            set order_status = 'Paid'
            WHERE order_id = {$session_order_id}
            ";
            $result = $db->sql_query($SQL);
        }

        if ($orderNo == '' ) {
            if($session_order_id < 10){
                $orderId = '0000' . $session_order_id;
            }
            else if($session_order_id <= 99){
                $orderId = '000' . $session_order_id;
            }
            else if($session_order_id <= 999){
                $orderId = '00' . $session_order_id;
            }
            else if($session_order_id <= 9999){
                $orderId = '0' . $session_order_id;
            }
            else{
                $orderId = $session_order_id;
            }
        } else {
            if($orderNo < 10){
                $orderId = '0000' . $orderNo;
            }
            else if($orderNo <= 99){
                $orderId = '000' . $orderNo;
            }
            else if($orderNo <= 999){
                $orderId = '00' . $orderNo;
            }
            else if($orderNo <= 9999){
                $orderId = '0' . $orderNo;
            }
            else{
                $orderId = $orderNo;
            }
        }


        $SQL = "
        SELECT oi.*
              ,p.title AS product_title
              ,p.unit
              ,p.item_code
              ,CONCAT_WS('::', p.part_number, p.model) code
              ,o.order_date
              ,o.order_id
              ,i.vat AS invoice_vat
              ,i.invoice_code_vat
              ,i.invoice_code
              ,oi.qty * oi.unit_price AS amount
              ,(SELECT SUM(oit.qty * oit.unit_price) FROM order_item oit
               WHERE oit.order_id = oi.order_id) AS sub_total
        FROM order_item oi
        LEFT JOIN product p ON (p.product_id = oi.record_id)
        LEFT JOIN `order` o ON (o.order_id = oi.order_id)
        LEFT JOIN invoice i ON (i.order_id = o.order_id)
        WHERE o.order_id = '{$orderId}'
        ORDER BY p.title
        ";
        $result = $db->sql_query($SQL);

        $numRows  = $db->sql_numrows($result);

        $today = date('d-m-Y');
        if ($numRows == 0){
            $pdf->SetXY(30,30);
            $pdf->Cell(50, 20, "Please set the values for your Order and print the PDF");
            $pdf->Output();
            return;
        }

        $count = 0;
        $total = 0;
        $discount_price = 0;
        $rows = "";
        $discountValue =0;
        $lineItemNumber = 1;  // To increment the line item in receipt

        //============================================================================= //
        $pdf->SetFont('Arial','',10);
        while ($row = $db->sql_fetchrow($result)) {
            $discount_value_for_one_qty = 0;
            $discountValue =0;
            if($row['discount_percentage'] > 0){
                if($row['discount_type'] == '%'){
                    $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
                else if($row['discount_type']  == 'Value'){
                    $discount_value_for_one_qty  =  $row['discount_percentage'];
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
                $discountValueTotal += $discountValue;
            }

            if ($count == 0){
                /* Logo of the institution */
                $pdf->Image('images/logo-print.gif',10,5,45);
                $pdf->SetFont('Courier','B',9);
                $pdf->Cell(50, 20, $cpCfg['cp.companyName']);
                $pdf->Ln(5);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf7']);
                $pdf->Ln(5);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf6']);
                $pdf->Ln(5);
                $pdf->Cell(50, 20, $cpCfg['printWebAddress']);

                $creationDate   = $fn->getCPDate($row['order_date'], 'd-m-Y');

                /* Company address */
                //Address to be got from settings
                $pdf->SetXY(130,0);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf1']);
                $pdf->Ln(5);
                $pdf->SetXY(130,5);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf2']);
                $pdf->Ln(5);
                $pdf->SetXY(130,10);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf3']);
                $pdf->Ln(5);
                $pdf->SetXY(130, 15);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf4']);
                $pdf->Ln(5);
                $pdf->SetXY(130,20);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf5'  ]);
                $pdf->Ln(5);
                /*$pdf->SetXY(130,25);
                $pdf->Cell(50, 20, $cpCfg['printTelephoneAndFax']);
                $pdf->Ln(5);*/
                $pdf->SetXY(130,25);
                $pdf->Cell(50, 20, $cpCfg['printEmailAddress']);

                /* Header */
                $pdf->SetFont('Courier','BU',9);
                $pdf->SetXY(80, 45);
                $pdf->Cell(50, 20, "BILL", 0, 0, 'C');
                $pdf->SetFont('Courier','B',9);
                $pdf->SetX(130);
                $pdf->Cell(31, 20, "DATE : " . $creationDate, 0, 0, 'L');
                $pdf->Ln(20);

                if($row['invoice_vat'] == 1){
                    $invoiceCode = 'INVT -' . $row['invoice_code_vat'];
                } else {
                    $invoiceCode = $row['invoice_code'];
                }

                /* Invoice Details*/
                $pdf->SetFont('Courier','B',9);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(30,8,"BILL NO :",1,0, 'L', 1);
                $pdf->SetFillColor(255,255,255);
                $pdf->Cell(66, 8, $invoiceCode, 1, 0, 'L', 1);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(30,8,"ORD NO :",1,0, 'L', 1);
                $pdf->SetFillColor(255,255,255);
                $pdf->Cell(66, 8, $orderId, 1, 0, 'L', 1);
                $pdf->Ln(12);

                /* List of order items header */
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(10,8,"S.NO",1,0, 'C', 1);
                $pdf->Cell(48,8,"NAME OF THE ITEM",1,0, 'C', 1);
                $pdf->Cell(57,8,"ITEM CODE",1,0, 'C', 1);
                $pdf->Cell(10,8,"UOM",1,0, 'C', 1);
                $pdf->Cell(10,8,"QTY",1,0, 'C', 1);
                $pdf->Cell(20,8,"UNIT PRICE",1,0, 'C', 1);
                $pdf->Cell(18,8,"DISCOUNT",1,0, 'C', 1);
                $pdf->Cell(22,8,"AMOUNT" ,1,0, 'C', 1);
                $pdf->Ln();
            }

            //===================================MAIN TABLE============================= //
            $amount = $row['amount'] - $discountValue;
            $discount_value_for_one_qty = number_format($discount_value_for_one_qty, 2);

            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(10, 8, $lineItemNumber, 1, 0, 'C', 1);
            $pdf->Cell(48, 8, $row['product_title'], 1, 0, 'L', 1);
            $pdf->Cell(57, 8, $row['code'], 1, 0, 'L', 1);
            $pdf->Cell(10, 8, $row['unit'], 1, 0, 'R', 1);
            $pdf->Cell(10, 8, $row['qty'], 1, 0, 'R', 1);
            $pdf->Cell(20, 8, $row['unit_price'], 1, 0, 'R', 1);
            $pdf->Cell(18, 8, '- ' . $discount_value_for_one_qty, 1, 0, 'R', 1);
            $pdf->Cell(22, 8, number_format($amount, 2), 1, 0, 'R', 1);
            $pdf->Ln();

            $count++;
            $lineItemNumber++;
            $sub_total = $row['sub_total'];
            $discount = '';
            $total = $row['sub_total'] - $discountValueTotal;
        }
            /*$pdf->SetFillColor(255,255,255);
            $pdf->Cell(168, 8, "SUB TOTAL", 1, 0, 'R', 1);
            $pdf->Cell(25, 8, $sub_total, 1, 0, 'R', 1);
            $pdf->Ln();*/

            $printTaxName = $cpCfg['printTaxName'] ;
            $discountValueTotal = number_format($discountValueTotal, 2);

            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(173, 8, "Total Discount", 1, 0, 'R', 1);
            $pdf->Cell(22, 8, '- ' . $discountValueTotal, 1, 0, 'R', 1);
            $pdf->Ln();

            //$totalvalueRounded = round($totalvalue);
            //$totalvalueRounded = $totalvalue;
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(173, 8, 'TOTAL', 1, 0, 'R', 1);
            $pdf->Cell(22, 8, number_format($total, 2), 1, 0, 'R', 1);
            $pdf->Ln(10);
            $pdf->Cell(190, 8, $cpCfg['cp.invoiceVatInclusive'], 0, 0, 'L');
            $pdf->Ln(10);

            /* Creation of media record of the invoice */
            //$pdf->Output($outputFileName , "F");
            $pdf->Output();
            //$pdf->Output($invoiceCode.'.PDF', 'D');

    }


    /**
     *
     */
    function getPrintBillForPrinter($printOnly = '') {
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $media = Zend_Registry::get('media');

        //-----------------------------------------------------------------//
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/tbs_class.php');
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/plugins/tbs_plugin_opentbs.php');
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/plugins/tbs_plugin_html.php');

        $TBS = new clsTinyButStrong;
        $TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $template = 'Pos-Invoice.xlsx';
        $templatePath = $cpCfg['cp.localPath'].'lib/template/' . $template;
        $TBS->LoadTemplate($templatePath);
        $rnd_no = mt_rand();
        $file_name = 'Pos-Invoice_' . $session_order_id . '_' . $rnd_no;
        $file_name = $session_order_id . '.xlsx';
        //$file_name = str_replace('.', '_' . date('Y-m-d') . '.', $file_name);

        //if ($cpCfg['local']['site'] == 'local') {
            $path = realpath($cpCfg['cp.mediaFolder']) . '\temp\invoicePrint';
            $file_name_save = $path . '\\' . $file_name;
        //} else {
        //    $path = realpath($cpCfg['cp.mediaFolder']) . '/temp/invoicePrint';
        //    $file_name_save = $path . '//' . $file_name;
       // }
        $sourceFilePath = $file_name_save;

        $invoice_code = $fn->getReqParam('invoice_code');
        //$order_id = $fn->getReqParam('order_id');
        $orderNo = $fn->getReqParam('orderNo');
        $discountValueTotal = 0;
        $total_qty = 0;

        if($printOnly == '') {
            $printOnly = $fn->getReqParam('printOnly');
        }

        if ($printOnly != 1 ) {
            $SQL    = "
            UPDATE `order`
            set order_status = 'Paid'
            WHERE order_id = {$session_order_id}
            ";
            $result = $db->sql_query($SQL);
        }

        if ($orderNo == '' ) {
            if($session_order_id < 10){
                $orderId = '0000' . $session_order_id;
            }
            else if($session_order_id <= 99){
                $orderId = '000' . $session_order_id;
            }
            else if($session_order_id <= 999){
                $orderId = '00' . $session_order_id;
            }
            else if($session_order_id <= 9999){
                $orderId = '0' . $session_order_id;
            }
            else{
                $orderId = $session_order_id;
            }
        } else {
            if($orderNo < 10){
                $orderId = '0000' . $orderNo;
            }
            else if($orderNo <= 99){
                $orderId = '000' . $orderNo;
            }
            else if($orderNo <= 999){
                $orderId = '00' . $orderNo;
            }
            else if($orderNo <= 9999){
                $orderId = '0' . $orderNo;
            }
            else{
                $orderId = $orderNo;
            }
        }


        $SQL = "
        SELECT oi.*
              ,p.title AS product_title
              ,p.unit
              ,p.item_code
              ,p.batch_no
              ,p.carton_no
              ,CONCAT_WS('::', p.part_number, p.model) code
              ,o.order_date
              ,o.order_id
              ,i.vat AS invoice_vat
              ,i.invoice_code_vat
              ,i.invoice_code
              ,oi.qty * oi.unit_price AS amount
              ,(SELECT SUM(oit.qty * oit.unit_price) FROM order_item oit
               WHERE oit.order_id = oi.order_id) AS sub_total
        FROM order_item oi
        LEFT JOIN product p ON (p.product_id = oi.record_id)
        LEFT JOIN `order` o ON (o.order_id = oi.order_id)
        LEFT JOIN invoice i ON (i.order_id = o.order_id)
        WHERE o.order_id = '{$orderId}'
        ORDER BY p.title
        ";
        $result = $db->sql_query($SQL);

        $numRows  = $db->sql_numrows($result);

        $today = date("Y-m-d");

        global $TeamList;
        $TeamList    = array();

        $serialNo       = 1;
        $arr            = array();
        $blkMain        = array();
        /*$blkProduct     = array();
        $blkQty         = array();
        $blkUom         = array();
        $blkPrice       = array();
        $blkSerialNo    = array();
        $blkAmount    = array();*/
        $count = -1;
        while ($row = $db->sql_fetchrow($result)) {
            $discount_value_for_one_qty = 0;
            $discountValue =0;
            $count++;
            if($row['discount_percentage'] > 0){
                if($row['discount_type'] == '%'){
                    $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
                else if($row['discount_type']  == 'Value'){
                    $discount_value_for_one_qty  =  $row['discount_percentage'];
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
                $discountValueTotal += $discountValue;
            }

            //repeating rows of product values
            /*$arr1 = array('product_title' => $row['product_title']);
            $blkProduct[] = $arr1;

            $arr2 = array('qty' => $row['qty']);
            $blkQty[] = $arr2;

            $arr3 = array('serial_no' => $serialNo);
            $blkSerialNo[] = $arr3;

            $arr4 = array('unit_price' => number_format($row['unit_price'],2));
            $blkPrice[] = $arr4;

            $arr5 = array('amount' => number_format($row['amount'], 2));
            $blkAmount[] = $arr5;

            $arr6 = array('item_code' => $row['carton_no']);
            $blkCode[] = $arr6;

            $total_qty += $row['qty'];
            $sub_total = $row['sub_total'];
            $invoice_code = $row['invoice_code'];

            $serialNo++;*/

            $TeamList[$count] = array(
                'product_title'    => $row['product_title'],
                'qty'              => $row['qty'],
                'carton_no'        => $row['item_code'] . ':' . $row['carton_no'],
                'unit_price'       => number_format($row['unit_price'],2),
                'amount'           => number_format($row['amount'], 2),
                'qty'              =>$row['qty'],
                'serial_no'        => $serialNo
                );

            $TeamList[$count]['matches'][] = array(
                'unit_discount'    => $discount_value_for_one_qty,
                'discount_allqty'  => $discountValue
            );

            $total_qty    += $row['qty'];
            $sub_total    = $row['sub_total'];
            $invoice_code = $row['invoice_code'];
            /*$company_id = $row['company_id'];
            $company_name = $row['company_name'];
            $mobile = $row['mobile'];*/

            $serialNo++;

        }
        //Header Part and Total/subtotal
        /*$arr['sub_total'] = number_format($sub_total, 2);
        $arr['discount'] = number_format($discountValueTotal, 2);
        $arr['total_qty'] = $total_qty;
        $arr['invoice_code'] = $invoice_code;
        $arr['date'] = $fn->getCPDate($today, 'd-m-Y');
        $arr['total'] =  number_format($sub_total - $discountValueTotal, 2);
        $blkMain[] = $arr;*/

        $arr['sub_total']     = number_format($sub_total, 2);
        $arr['discount']      = number_format($discountValueTotal, 2);
        $arr['total_qty']     = $total_qty;
        $arr['invoice_code']  = $invoice_code;
        $arr['date']          = $fn->getCPDate($today, 'd-m-Y');
        $arr['total']         =  number_format($sub_total - $discountValueTotal, 2);
        $arr['items']         = $numRows;

        /*$arr['company_id']    = $company_id;
        $arr['company_name']  = $company_name;
        $arr['mobile']        = $mobile;*/
        $blkMain[]            = $arr;

        /*$TBS->MergeBlock('blkMain', $blkMain);
        $TBS->MergeBlock('blkProduct', $blkProduct);
        $TBS->MergeBlock('blkQty', $blkQty);
        $TBS->MergeBlock('blkSerialNo', $blkSerialNo);
        $TBS->MergeBlock('blkPrice', $blkPrice);
        $TBS->MergeBlock('blkAmount', $blkAmount);
        $TBS->MergeBlock('blkCode', $blkCode);*/
        //$TBS->Show(OPENTBS_DOWNLOAD, $file_name);

        $TBS->MergeBlock('blkMain', $blkMain);
        $TBS->MergeBlock('mb', $TeamList);
        $TBS->MergeBlock('mb','array','TeamList');
        $TBS->MergeBlock('sb','array','TeamList[%p1%][matches]');

        $TBS->Show(OPENTBS_FILE, $sourceFilePath);
        echo "<script>window.close();</script>";

        //return $this->getPrintbillcondition($printOnly);
        //$this->model->getCreateNewOrder();
    }


    /**
     *
     */
    function getPrintBillActual($printOnly = '') {
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $media = Zend_Registry::get('media');

        //-----------------------------------------------------------------//
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/tbs_class.php');
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/plugins/tbs_plugin_opentbs.php');
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/plugins/tbs_plugin_html.php');

        $TBS = new clsTinyButStrong;
        $TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $template = 'Pos-Invoice.xlsx';
        $templatePath = $cpCfg['cp.localPath'].'lib/template/' . $template;
        $TBS->LoadTemplate($templatePath);
        $rnd_no = mt_rand();
        $file_name = 'Pos-Invoice_' . $session_order_id . '_' . $rnd_no;
        //$file_name = $session_order_id . '.xlsx';
        $file_name = str_replace('.', '_' . date('Y-m-d') . '.', $file_name);

        $path = realpath($cpCfg['cp.mediaFolder']) . '\temp\invoicePrint';
        $file_name_save = $path . '\\' . $file_name;
        $sourceFilePath = $file_name_save;

        $invoice_code = $fn->getReqParam('invoice_code');
        //$order_id = $fn->getReqParam('order_id');
        $orderNo = $fn->getReqParam('orderNo');
        $discountValueTotal = 0;
        $total_qty = 0;

        if($printOnly == '') {
            $printOnly = $fn->getReqParam('printOnly');
        }

        if ($printOnly != 1 ) {
            $SQL    = "
            UPDATE `order`
            set order_status = 'Paid'
            WHERE order_id = {$session_order_id}
            ";
            $result = $db->sql_query($SQL);
        }

        if ($orderNo == '' ) {
            if($session_order_id < 10){
                $orderId = '0000' . $session_order_id;
            }
            else if($session_order_id <= 99){
                $orderId = '000' . $session_order_id;
            }
            else if($session_order_id <= 999){
                $orderId = '00' . $session_order_id;
            }
            else if($session_order_id <= 9999){
                $orderId = '0' . $session_order_id;
            }
            else{
                $orderId = $session_order_id;
            }
        } else {
            if($orderNo < 10){
                $orderId = '0000' . $orderNo;
            }
            else if($orderNo <= 99){
                $orderId = '000' . $orderNo;
            }
            else if($orderNo <= 999){
                $orderId = '00' . $orderNo;
            }
            else if($orderNo <= 9999){
                $orderId = '0' . $orderNo;
            }
            else{
                $orderId = $orderNo;
            }
        }


        $SQL = "
        SELECT oi.*
              ,p.title AS product_title
              ,p.unit
              ,p.item_code
              ,p.batch_no
              ,p.carton_no
              ,CONCAT_WS('::', p.carton_no, p.batch_no, p.model) code
              ,o.order_date
              ,o.order_id
              ,i.vat AS invoice_vat
              ,i.invoice_code_vat
              ,i.invoice_code
              ,oi.qty * oi.unit_price AS amount
              ,(SELECT SUM(oit.qty * oit.unit_price) FROM order_item oit
               WHERE oit.order_id = oi.order_id) AS sub_total
        FROM order_item oi
        LEFT JOIN product p ON (p.product_id = oi.record_id)
        LEFT JOIN `order` o ON (o.order_id = oi.order_id)
        LEFT JOIN invoice i ON (i.order_id = o.order_id)
        WHERE o.order_id = '{$orderId}'
        ORDER BY p.title
        ";
        $result = $db->sql_query($SQL);

        $numRows  = $db->sql_numrows($result);

        $today = date("Y-m-d");

        $serialNo       = 1;
        $arr            = array();
        $blkMain        = array();
        $blkProduct     = array();
        $blkQty         = array();
        $blkUom         = array();
        $blkPrice       = array();
        $blkSerialNo    = array();
        $blkAmount    = array();

        while ($row = $db->sql_fetchrow($result)) {
            $discount_value_for_one_qty = 0;
            $discountValue =0;
            if($row['discount_percentage'] > 0){
                if($row['discount_type'] == '%'){
                    $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
                else if($row['discount_type']  == 'Value'){
                    $discount_value_for_one_qty  =  $row['discount_percentage'];
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
                $discountValueTotal += $discountValue;
            }

            //repeating rows of product values
            $arr1 = array('product_title' => $row['product_title']);
            $blkProduct[] = $arr1;

            $arr2 = array('qty' => $row['qty']);
            $blkQty[] = $arr2;

            $arr3 = array('serial_no' => $serialNo);
            $blkSerialNo[] = $arr3;

            $arr4 = array('unit_price' => number_format($row['unit_price'],2));
            $blkPrice[] = $arr4;

            $arr5 = array('amount' => number_format($row['amount'], 2));
            $blkAmount[] = $arr5;

            $arr6 = array('item_code' => $row['carton_no']);
            $blkCode[] = $arr6;

            $total_qty += $row['qty'];
            $sub_total = $row['sub_total'];
            $invoice_code = $row['invoice_code'];

            $serialNo++;
        }
        //Header Part and Total/subtotal
        $arr['sub_total'] = number_format($sub_total, 2);
        $arr['discount'] = number_format($discountValueTotal, 2);
        $arr['total_qty'] = $total_qty;
        $arr['invoice_code'] = $invoice_code;
        $arr['date'] = $fn->getCPDate($today, 'd-m-Y');
        $arr['total'] =  number_format($sub_total - $discountValueTotal, 2);
        $blkMain[] = $arr;

        $TBS->MergeBlock('blkMain', $blkMain);
        $TBS->MergeBlock('blkProduct', $blkProduct);
        $TBS->MergeBlock('blkQty', $blkQty);
        $TBS->MergeBlock('blkSerialNo', $blkSerialNo);
        $TBS->MergeBlock('blkPrice', $blkPrice);
        $TBS->MergeBlock('blkAmount', $blkAmount);
        $TBS->MergeBlock('blkCode', $blkCode);
        $TBS->Show(OPENTBS_DOWNLOAD, $file_name);

        return $this->getPrintbillcondition($printOnly);
        //$this->model->getCreateNewOrder();
    }

    /**
     *
     */
    function getPrintBill($printOnly = '') {
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $media = Zend_Registry::get('media');

        //-----------------------------------------------------------------//
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/tbs_class.php');
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/plugins/tbs_plugin_opentbs.php');
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/plugins/tbs_plugin_html.php');

        $TBS = new clsTinyButStrong;
        $TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $template = 'Pos-Invoice.xlsx';
        $templatePath = $cpCfg['cp.localPath'].'lib/template/' . $template;
        $TBS->LoadTemplate($templatePath);
        $rnd_no = mt_rand();
        $file_name = 'Pos-Invoice_' . $session_order_id . '_' . $rnd_no;
        //$file_name = $session_order_id . '.xlsx';
        $file_name = str_replace('.', '_' . date('Y-m-d') . '.', $file_name);

        $path = realpath($cpCfg['cp.mediaFolder']) . '\temp\invoicePrint';
        $file_name_save = $path . '\\' . $file_name;
        $sourceFilePath = $file_name_save;

        $invoice_code = $fn->getReqParam('invoice_code');
        //$order_id = $fn->getReqParam('order_id');
        $orderNo = $fn->getReqParam('orderNo');
        $discountValueTotal = 0;
        $total_qty = 0;

        if($printOnly == '') {
            $printOnly = $fn->getReqParam('printOnly');
        }

        if ($printOnly != 1 ) {
            $SQL    = "
            UPDATE `order`
            set order_status = 'Paid'
            WHERE order_id = {$session_order_id}
            ";
            $result = $db->sql_query($SQL);
        }

        if ($orderNo == '' ) {
            if($session_order_id < 10){
                $orderId = '0000' . $session_order_id;
            }
            else if($session_order_id <= 99){
                $orderId = '000' . $session_order_id;
            }
            else if($session_order_id <= 999){
                $orderId = '00' . $session_order_id;
            }
            else if($session_order_id <= 9999){
                $orderId = '0' . $session_order_id;
            }
            else{
                $orderId = $session_order_id;
            }
        } else {
            if($orderNo < 10){
                $orderId = '0000' . $orderNo;
            }
            else if($orderNo <= 99){
                $orderId = '000' . $orderNo;
            }
            else if($orderNo <= 999){
                $orderId = '00' . $orderNo;
            }
            else if($orderNo <= 9999){
                $orderId = '0' . $orderNo;
            }
            else{
                $orderId = $orderNo;
            }
        }


        $SQL = "
        SELECT oi.*
              ,p.title AS product_title
              ,p.unit
              ,p.item_code
              ,p.batch_no
              ,p.carton_no
              ,CONCAT_WS('::', p.carton_no, p.batch_no, p.model) code
              ,o.order_date
              ,o.order_id
              ,i.vat AS invoice_vat
              ,i.invoice_code_vat
              ,i.invoice_code
              ,oi.qty * oi.unit_price AS amount
              ,(SELECT SUM(oit.qty * oit.unit_price) FROM order_item oit
               WHERE oit.order_id = oi.order_id) AS sub_total
        FROM order_item oi
        LEFT JOIN product p ON (p.product_id = oi.record_id)
        LEFT JOIN `order` o ON (o.order_id = oi.order_id)
        LEFT JOIN invoice i ON (i.order_id = o.order_id)
        WHERE o.order_id = '{$orderId}'
        ORDER BY p.title
        ";
        $result = $db->sql_query($SQL);

        $numRows  = $db->sql_numrows($result);

        $today = date("Y-m-d");

        global $TeamList;
        $TeamList    = array();

        $serialNo       = 1;
        $arr            = array();
        $blkMain        = array();

        $count = -1;
        while ($row = $db->sql_fetchrow($result)) {
            $count++;
            $discount_value_for_one_qty = 0;
            $discountValue =0;
            if($row['discount_percentage'] > 0){
                if($row['discount_type'] == '%'){
                    $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
                else if($row['discount_type']  == 'Value'){
                    $discount_value_for_one_qty  =  $row['discount_percentage'];
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
                $discountValueTotal += $discountValue;
            }

            $TeamList[$count] = array(
                'product_title'    => $row['product_title'],
                'qty'              => $row['qty'],
                'carton_no'        => $row['carton_no'],
                'unit_price'       => number_format($row['unit_price'],2),
                'amount'           => number_format($row['amount'], 2),
                'qty'              =>$row['qty'],
                'serial_no'        => $serialNo
                );

            $TeamList[$count]['matches'][] = array(
                'unit_discount'    => $discount_value_for_one_qty,
                'discount_allqty'  => $discountValue
            );

            $total_qty    += $row['qty'];
            $sub_total    = $row['sub_total'];
            $invoice_code = $row['invoice_code'];

            $serialNo++;
        }
        //Total/subtotal
        $arr['sub_total']     = number_format($sub_total, 2);
        $arr['discount']      = number_format($discountValueTotal, 2);
        $arr['total_qty']     = $total_qty;
        $arr['invoice_code']  = $invoice_code;
        $arr['date']          = $fn->getCPDate($today, 'd-m-Y');
        $arr['total']         =  number_format($sub_total - $discountValueTotal, 2);
        $arr['items']         = $numRows;
        $blkMain[]            = $arr;

        $TBS->MergeBlock('blkMain', $blkMain);
        $TBS->MergeBlock('mb', $TeamList);
        $TBS->MergeBlock('mb','array','TeamList');
        $TBS->MergeBlock('sb','array','TeamList[%p1%][matches]');

        $TBS->Show(OPENTBS_DOWNLOAD, $file_name);

        return $this->getPrintbillcondition($printOnly);
        //$this->model->getCreateNewOrder();
    }


    /**
     *
     */
    function getPrintbillcondition($printOnly=''){
         $fn = Zend_Registry::get('fn');
         $db = Zend_Registry::get('db');
         $cpCfg = Zend_Registry::get('cpCfg');
         if ($printOnly != 1 ) {
            $_SESSION['order_id'] = '';
            return $this->model->getCreateNewOrder();
         }
     }


    /**
     *
     */
    function getPrintbillconditionForPrinter($printOnly=''){
         $fn = Zend_Registry::get('fn');
         $db = Zend_Registry::get('db');
         $cpCfg = Zend_Registry::get('cpCfg');
         if ($printOnly != 1 ) {
            $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
            $session_order_id = $session_order_id - 1;
            $file_name = $session_order_id. '.xlsx';

            if ($cpCfg['local']['site'] == 'local') {
                $path = realpath($cpCfg['cp.mediaFolder']) . '\temp\invoicePrint';
                $file_name_delete = $path . '\\' . $file_name;
            } else {
                $path = realpath($cpCfg['cp.mediaFolder']) . '/temp/invoicePrint';
                $file_name_delete = $path . '//' . $file_name;
            }
            unlink($file_name_delete);
            $_SESSION['order_id'] = '';
            echo "<script>window.close();</script>";
            return $this->model->getCreateNewOrder();
         }
     }


    /********************************* PROCESS ************************************
    ACTION: IN POS MODULE - WHEN YOU CLICK 'GENERATE/UPDATE BILL' BUTTON
    STEP 1: SQL,LOOP THE RECORDS OF ORDER ITEM TO FIND THE DISCOUNT AMOUNT, INVOICE AMOUNT
    STEP 2: GENERATE NEXT INVOICE CODE - GENERATE INVOICE CODE VAT - UPDATE/INSERT INVOICE RECORDS IN INVOICE TABLE
    STEP 3: LOOP THE RECORDS OF ORDER ITEM AND UPDATE/INSERT INVOICE ITEM RECORDS IN INVOICE ITEM TABLE
    STEP 4: UPDATE/INSERT RECEIPT RECORDS IN RECEIPT TABLE - UPDATE/INSERT RECEIPT HISTORY RECORDS IN INVOICE
    RECEIPT HISTORY TABLE  - UPDATE ORDER STATUS TO PAID FOR ORDER TABLE
    ******************************* END PROCESS **********************************/
    function getGenerateBill() {
        $cpCfg     = Zend_Registry::get('cpCfg');
        $fn        = Zend_Registry::get('fn');
        $tv        = Zend_Registry::get('tv');
        $fn        = Zend_Registry::get('fn');
        $db        = Zend_Registry::get('db');
        $searchVar = Zend_Registry::get('searchVar');
        $media     = Zend_Registry::get('media');
        $cpPaths   = Zend_Registry::get('cpPaths');
        $dbUtil    = Zend_Registry::get('dbUtil');
        $cpUtil    = Zend_Registry::get('cpUtil');

        $mode_of_payment = $fn->getReqParam('mode_of_payment');
        $bill_number     = $fn->getSettingsValueByKey("nextBillNumber");
        $receipt_amount  = $fn->getReqParam('receipt_amount');
        $gst_selected    = $fn->getReqParam('gst_selected');
        $order_date      = $fn->getReqParam('order_date');

        $session_order_id   = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $discountValueTotal = '';
        $invoice_amount     = '';
        $discount = 0;

        $invoice_amount = $this->getTotalAmount($session_order_id);
        $invoice_amount = round($invoice_amount);
        $receipt_amount = round($receipt_amount);
        /********************************** STEP 1 ENDS HERE ****************************/

        /********************************** STEP 2 **************************************/
        /*$SQLUpdate = "UPDATE setting SET value = (value+1) WHERE key_text = 'nextInvoiceCode'";
        $resultUpdate = $db->sql_query($SQLUpdate);
        $invoice_code = $fn->getSettingsValueByKey("nextInvoiceCode");*/
        if($cpCfg['cp.companyType'] == "Aachi Dealer"){
            $SQLUpdate = "UPDATE setting SET value = (value+1) WHERE key_text = 'nextInvoiceCode'";
            $resultUpdate = $db->sql_query($SQLUpdate);
            $invoice_code = $fn->getSettingsValueByKey("nextInvoiceCode");
            $invoice_code = "GLO - ".$invoice_code;
        } else{
            if($gst_selected == 'ON'){
                $SQLInvoiceCode = "
                SELECT MAX(CONVERT(REPLACE(invoice_code, 'INV - ', ''), UNSIGNED INTEGER)) AS invoice_code
                FROM invoice
                WHERE status != 'Cancelled'
                AND gst_status = 'ON'
                ";
                $resultInvoiceCode = $db->sql_query($SQLInvoiceCode);
                $rowInvoiceCode    = $db->sql_fetchrow($resultInvoiceCode);
                $invoice_code      = $rowInvoiceCode['invoice_code'] + 1;

                if($invoice_code == ""){
                    $invoice_code = "INV - 1000";
                }
                else{
                    $invoice_code = "INV - ".$invoice_code;
                }
            }
            else{
                $invoice_code = "";
            }
        }

        $date     = $order_date;
        $SQLInvoice = "
        SELECT *
        FROM `invoice`
        WHERE order_id = '{$session_order_id}'
        ";
        $resultInvoice = $db->sql_query($SQLInvoice);
        $invoiceRec = $db->sql_fetchrow($resultInvoice);

        $fa = array();
        $fa['invoice_amount']  = $invoice_amount;
        $fa['invoice_date']    = $date;
        $fa['order_id']        = $session_order_id;
        $fa['discount']        = $discount;
        $fa['gst_status']      = $gst_selected;
        $fa['staff_id']        = $_SESSION['staff_id'];
        $fa['creation_date']   = date("Y-m-d H:i:s");
        $fa['created_by']      = $fn->getSessionParam('userName');
        $fa['vat']             = 1;
        $fa['mode_of_payment'] = $mode_of_payment;

        /*$SQLICV = "
        SELECT max(invoice_code_vat) AS invoice_code_vat
        FROM `invoice`
        WHERE vat = 1
        ";
        $resultICV = $db->sql_query($SQLICV);
        $rowICV = $db->sql_fetchrow($resultICV);
        if ($rowICV['invoice_code_vat'] == '' || $rowICV['invoice_code_vat'] == 0){
            $invoice_code_vat = 1;
        } else {
            $invoice_code_vat = $rowICV['invoice_code_vat'] + 1;
        }*/

        if ($invoice_amount <= $receipt_amount){
            $fa['status']  = 'Paid';
        } else if ($invoice_amount > $receipt_amount){
            $fa['status']  = 'Partial Payment';
        } else{
            $fa['status']  = 'Due';
        }

        if (is_array($invoiceRec)) {
            $whereCondition = "WHERE order_id = {$session_order_id}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa, "invoice", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
            $invoice_id     = $invoiceRec['invoice_id'];
        } else {
            $fa['invoice_code'] = $invoice_code;
            //$fa['status']       = 'Paid';
            $fa['invoice_type'] = 'Client';
            //$fa['invoice_code_vat'] = $invoice_code_vat;

            $insertInvoiceSQL   = $dbUtil->getInsertSQLStringFromArray($fa, 'invoice');
            $resultSQL          = $db->sql_query($insertInvoiceSQL);
            $invoice_id         = $db->sql_nextid();
        }

        if($invoice_code == ''){
            $SQLUpdate    = "
            UPDATE `invoice`
            SET invoice_code = 'INV - {$invoice_id}'
            WHERE invoice_id = {$invoice_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        $invoiceCode = $invoice_code;

        /********************************** STEP 2 ENDS HERE ****************************/

        /********************************** STEP 3 **************************************/
        $SQL = "
        SELECT *
        FROM order_item
        WHERE order_id = '{$session_order_id}'
        ";
        $result = $db->sql_query($SQL);
        while ($row = $db->sql_fetchrow($result)) {
            $fa = array();
            $fa['invoice_id']          = $invoice_id;
            $fa['record_id']           = $row['record_id'];
            $fa['qty']                 = $row['qty'];
            $fa['unit_price']          = $row['unit_price'];
            $fa['cost_price']          = $row['cost_price'];
            $fa['item_title']          = $row['item_title'];
            $fa['item_code']           = $row['item_code'];
            $fa['model']               = $row['model'];
            $fa['order_item_id']       = $row['order_item_id'];
            $fa['vat']                 = $row['vat'];
            $fa['gst']                 = $row['gst'];
            $fa['discount_type']       = $row['discount_type'];
            $fa['discount_percentage'] = $row['discount_percentage'];
            $fa['ref_no']              = $row['ref_no'];
            $fa['tag_no']              = $row['tag_no'];

            if($cpCfg['cp.hasMultiUniqueSites'] == 1){
                $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
                $appendSQL = "AND site_id = '{$cpSiteIdSession}'";
            }

            $SQLInvoiceItem = "
            SELECT *
            FROM `invoice_item`
            WHERE invoice_id = '{$invoice_id}'
              AND record_id = '{$row['record_id']}'
            ";
            $resultInvoiceItem = $db->sql_query($SQLInvoiceItem);
            $invoiceItemRec = $db->sql_fetchrow($resultInvoiceItem);

            if(is_array($invoiceItemRec)){
                $whereCondition = "WHERE invoice_item_id = {$invoiceItemRec['invoice_item_id']}";
                $sqlOiUpdate = $dbUtil->getUpdateSQLStringFromArray($fa, "invoice_item", $whereCondition);
                $resultOiUpdate      = $db->sql_query($sqlOiUpdate);
            } else {
                $invoice_item_id = $fn->addRecord($fa, 'invoice_item');
            }
        }
        /********************************** STEP 3 ENDS HERE ****************************/

        /********************************** STEP 4 **************************************/
        $orderStatus = "Due";
        if($receipt_amount > 0){
            $SQLUpdate = "UPDATE setting SET value = (value+1) WHERE key_text = 'nextReceiptCode'";
            $resultUpdate = $db->sql_query($SQLUpdate);
            $receipt_code = $fn->getSettingsValueByKey("nextReceiptCode");

            $SQLReceipt = "
            SELECT *
            FROM `receipt`
            WHERE order_id = '{$session_order_id}'
            ";
            $resultReceipt = $db->sql_query($SQLReceipt);
            $receiptRec = $db->sql_fetchrow($resultReceipt);

            $fa = array();
            $recpInvAmount = 0;
            if ($invoice_amount <= $receipt_amount){
                $recpInvAmount = $invoice_amount;
                $orderStatus   = 'Paid';
                //$fa['receipt_status']  = 'Paid';
            } else if ($invoice_amount > $receipt_amount){
                $recpInvAmount = $receipt_amount;
                $orderStatus   = 'Partial Payment';
                //$fa['receipt_status']  = 'Partial Payment';
            }

            $fa['amount']         = $recpInvAmount;
            $fa['order_id']       = $session_order_id;
            $fa['date']           = $date;
            $fa['receipt_status'] = 'Paid';
            $fa['creation_date']  = date("Y-m-d H:i:s");
            $fa['created_by']     = $fn->getSessionParam('userName');

            if (is_array($receiptRec)) {
                $whereCondition = "WHERE order_id = {$session_order_id}";
                $sqlOiUpdate = $dbUtil->getUpdateSQLStringFromArray($fa, "receipt", $whereCondition);
                $resultOiUpdate      = $db->sql_query($sqlOiUpdate);
                $receipt_id         = $receiptRec['receipt_id'];
            } else {
                $fa['receipt_code']   = 'RCPT - ' . $receipt_code;

                $insertReceiptSQL   = $dbUtil->getInsertSQLStringFromArray($fa, 'receipt');
                $resultSQL          = $db->sql_query($insertReceiptSQL);
                $receipt_id         = $db->sql_nextid();
            }

            $SQLReceiptHis = "
            SELECT *
            FROM `invoice_receipt_history`
            WHERE invoice_id = '{$invoice_id}'
              AND receipt_id = '{$receipt_id}'
            ";
            $resultReceiptHis = $db->sql_query($SQLReceiptHis);
            $receiptHisRec = $db->sql_fetchrow($resultReceiptHis);

            $fa = array();
            $fa['receipt_id']    = $receipt_id;
            $fa['invoice_id']    = $invoice_id;
            $fa['amount']        = $recpInvAmount;
            $fa['creation_date'] = date("Y-m-d H:i:s");

            if(is_array($receiptHisRec)){
                $whereCondition = "WHERE invoice_receipt_history_id = {$receiptHisRec['invoice_receipt_history_id']}";
                $sqlOiUpdate = $dbUtil->getUpdateSQLStringFromArray($fa, "invoice_receipt_history", $whereCondition);
                $resultOiUpdate      = $db->sql_query($sqlOiUpdate);
            } else {
                $histId = $fn->addRecord($fa, 'invoice_receipt_history');
            }
            
        }

        $order_time = date("H:i:s");
        $order_date = date("Y-m-d");
        
        $SQL    = "
        UPDATE `order`
        SET order_status = '{$orderStatus}'
           ,order_time   = '{$order_time}'
           ,order_date   = '{$order_date}'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        return $session_order_id;

        /********************************** STEP 4 ENDS HERE ****************************/
    }

    /**
     *
     */
    function getPrintBillFromInvoiceOld($printOnly = '') {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $searchVar = Zend_Registry::get('searchVar');
        $media = Zend_Registry::get('media');
        $cpPaths = Zend_Registry::get('cpPaths');
        $dbUtil = Zend_Registry::get('dbUtil');

        ini_set('memory_limit', '512M');

        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/fpdf/fpdf.php');
        include_once(CP_LIBRARY_PATH.'lib_php/fpdf-extra/html_table1.php');

        $pdf = new MYPDF();

		$pdf->AddPage();
		$pdf->SetFont('Arial','',11);

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $invoice_code = $fn->getReqParam('invoice_code');
        //$order_id = $fn->getReqParam('order_id');
        $orderNo = $fn->getReqParam('orderNo');

        if($printOnly == '') {
            $printOnly = $fn->getReqParam('printOnly');
        }

        if ($printOnly != 1 ) {
            $SQL    = "
            UPDATE `order`
            set order_status = 'Paid'
            WHERE order_id = {$session_order_id}
            ";
            $result = $db->sql_query($SQL);
        }

        $SQL = "
        SELECT ini.*
              ,p.title AS product_title
              ,p.unit
              ,p.item_code
              ,c.company_name
              ,c.address_flat
              ,c.address_street
              ,c.address_town
              ,c.address_state
              , (SELECT gc.name FROM geo_country gc
                 WHERE gc.country_code = c.address_country)
                AS address_country
              ,c.billing_address_flat
              ,c.billing_address_street
              ,c.billing_address_town
              ,c.billing_address_state
              , (SELECT gc.name FROM geo_country gc
                 WHERE gc.country_code = c.billing_address_country)
                AS billing_address_country
              ,c.fax
              ,c.phone
              ,i.invoice_date
              ,q.delivery_date
              ,q.delivery_location
              ,ini.unit_price
              ,i.invoice_code
              ,i.invoice_terms
              ,i.invoice_due_date
              ,i.notes
              ,i.discount
              ,q.quote_code
              ,q.currency
              ,oi.discount_type
              ,oi.discount_percentage
              ,ini.qty * ini.unit_price AS amount
              ,(SELECT SUM(init.qty * init.unit_price) FROM invoice_item init
               WHERE init.invoice_id = ini.invoice_id) AS sub_total
        FROM invoice_item ini
        LEFT JOIN product p ON (p.product_id = ini.record_id)
        LEFT JOIN invoice i ON (i.invoice_id = ini.invoice_id)
        LEFT JOIN `order` o ON (o.order_id = i.order_id)
        LEFT JOIN order_item oi ON (oi.order_item_id = ini.order_item_id)
        LEFT JOIN company c ON (c.company_id = o.company_id)
        LEFT JOIN quote q ON (q.quote_id = o.quote_id)
        WHERE i.invoice_code = '{$invoice_code}'
        ORDER BY p.title
        ";
        $result = $db->sql_query($SQL);

        $numRows  = $db->sql_numrows($result);

        $today = date("Y-m-d");
		if ($numRows == 0){
            $pdf->SetXY(30,30);
            $pdf->Cell(50, 20, "Please set the values for your Order and print the PDF");
			$pdf->Output();
			return;
		}

        $count = 0;
        $total = 0;
        $discount_price = 0;
        $rows = "";
        $discountValue ='';
        $lineItemNumber = 1;  // To increment the line item in receipt

        if ($orderNo == '' ) {
            if($session_order_id < 10){
                $orderId = '0000' . $session_order_id;
            }
            else if($session_order_id <= 99){
                $orderId = '000' . $session_order_id;
            }
            else if($session_order_id <= 999){
                $orderId = '00' . $session_order_id;
            }
            else if($session_order_id <= 9999){
                $orderId = '0' . $session_order_id;
            }
            else{
                $orderId = $session_order_id;
            }
        } else {
            if($orderNo < 10){
                $orderId = '0000' . $orderNo;
            }
            else if($orderNo <= 99){
                $orderId = '000' . $orderNo;
            }
            else if($orderNo <= 999){
                $orderId = '00' . $orderNo;
            }
            else if($orderNo <= 9999){
                $orderId = '0' . $orderNo;
            }
            else{
                $orderId = $orderNo;
            }
        }


        //============================================================================= //
        $pdf->SetFont('Arial','',11);
        while ($row = $db->sql_fetchrow($result)) {
            $discount_value_for_one_qty = '';
            if($row['discount_percentage'] > 0){
                if($row['discount_type'] == '%'){
                    $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
                else if($row['discount_type']  == 'Value'){
                    $discount_value_for_one_qty  =  $row['discount_percentage'];
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
            }

            if ($count == 0){
                /* Logo of the institution */
                $pdf->Image('images/logo-print.gif',10,5,45);
                $pdf->SetFont('Courier','B',11);
                $pdf->Cell(50, 20, $cpCfg['cp.companyName']);
                $pdf->Ln(5);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf7']);
                $pdf->Ln(5);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf6']);
                $pdf->Ln(5);
                $pdf->Cell(50, 20, $cpCfg['printWebAddress']);

                $creationDate   = $fn->getCPDate($row['invoice_date'], 'd-m-Y');

                /* Company address */
                //Address to be got from settings
                $pdf->SetFont('Courier','B',11);
                $pdf->SetXY(130,0);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf1']);
                $pdf->Ln(5);
                $pdf->SetXY(130,5);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf2']);
                $pdf->Ln(5);
                $pdf->SetXY(130,10);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf3']);
                $pdf->Ln(5);
                $pdf->SetXY(130, 15);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf4']);
                $pdf->Ln(5);
                $pdf->SetXY(130,20);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf5'  ]);
                $pdf->Ln(5);
                $pdf->SetXY(130,25);
                $pdf->Cell(50, 20, $cpCfg['printTelephoneAndFax']);
                $pdf->Ln(5);
                $pdf->SetXY(130,30);
                $pdf->Cell(50, 20, $cpCfg['printEmailAddress']);

                /* Header */
                $pdf->SetFont('Courier','BU',11);
                $pdf->SetXY(80, 45);
                $pdf->Cell(50, 20, "BILL", 0, 0, 'C');
                $pdf->SetFont('Courier','B',11);
                $pdf->SetX(130);
                $pdf->Cell(31, 20, "DATE : " . $creationDate, 0, 0, 'L');
                $pdf->Ln(20);

                /* Invoice Details*/
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(30,8,"BILL NO :",1,0, 'L', 1);
                $pdf->SetFillColor(255,255,255);
	            $pdf->Cell(66, 8, $row['invoice_code'], 1, 0, 'L', 1);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(30,8,"ORD NO :",1,0, 'L', 1);
                $pdf->SetFillColor(255,255,255);
                $pdf->Cell(66, 8, $orderId, 1, 0, 'L', 1);
                $pdf->Ln(12);

                /* List of order items header */
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(15,8,"S.NO",1,0, 'C', 1);
                $pdf->Cell(75,8,"NAME OF THE ITEM",1,0, 'C', 1);
                $pdf->Cell(15,8,"QTY",1,0, 'C', 1);
                $pdf->Cell(15,8,"UOM",1,0, 'C', 1);
                $pdf->Cell(24,8,"UNIT PRICE",1,0, 'C', 1);
                $pdf->Cell(24,8,"DISCOUNT",1,0, 'C', 1);
                $pdf->Cell(25,8,"AMOUNT" ,1,0, 'C', 1);
                $pdf->Ln();
            }

            //===================================MAIN TABLE============================= //
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(15, 8, $lineItemNumber, 1, 0, 'C', 1);
            $pdf->Cell(75, 8, $row['product_title'], 1, 0, 'L', 1);
            $pdf->Cell(15, 8, $row['qty'], 1, 0, 'R', 1);
            $pdf->Cell(15, 8, $row['unit'], 1, 0, 'R', 1);
            $pdf->Cell(24, 8, $row['unit_price'], 1, 0, 'R', 1);
            $pdf->Cell(24, 8, number_format($discountValue, 2), 1, 0, 'R', 1);
            $pdf->Cell(25, 8, $row['amount'], 1, 0, 'R', 1);
            $pdf->Ln();

            $count++;
            $lineItemNumber++;
            $sub_total = $row['sub_total'];
            $discount = $row['discount'];
            $total = $row['sub_total'] - $row['discount'];
        }
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(168, 8, "SUB TOTAL", 1, 0, 'R', 1);
            $pdf->Cell(25, 8, $sub_total, 1, 0, 'R', 1);
            $pdf->Ln();

	        $printTaxName = $cpCfg['printTaxName'] ;

            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(168, 8, "Discount", 1, 0, 'R', 1);
            $pdf->Cell(25, 8, $discount, 1, 0, 'R', 1);
            $pdf->Ln();

            //$totalvalueRounded = round($totalvalue);
            //$totalvalueRounded = $totalvalue;
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(168, 8, 'TOTAL', 1, 0, 'R', 1);
            $pdf->Cell(25, 8, number_format($total, 2), 1, 0, 'R', 1);
			$pdf->Ln(20);

	        /* Creation of media record of the invoice */
	        $file_name = 'Refund_REF_' . date('Y-m-d') .'.pdf';
	        $outputPath = realpath($cpCfg['cp.mediaFolder']) . '/temp';

	        $outputFileName = $outputPath . '/' . $file_name;
	        //$pdf->Output($outputFileName , "F");
			$pdf->Output();

    }

    /**
     *
     */
    function getProductPrice(){

		$productDisplay = $this->getProductPriceDisplay();

		$text = "
		<div class='checkProductPrice'>
			Product Name / Model No / Item Code/ Carton No: <input type='text' value='' id='fld_product_title' class='text' name='product_title'>
			(Please enter words related to the label)
		</div>
		<table class='list thinlist'>
			<thead>
				<tr>
					<th>Item Code</th>
					<th>Item Name</th>
					<th>Model No</th>
					<th>Carton No</th>
					<th>Batch No</th>
					<th>List Price</th>
					<th>Unit</th>
					<th>Available Stock Quantity</th>
					<th>FC Ref Code</th>
				</tr>
			</thead>
			<tbody id='productDisplay'>
				{$productDisplay}
			</tbody>
		</table>
		";

        return $text;
    }

    /**
     *
     */
    function getCheckPendingOrderDetails(){
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        $appendSqlSite ='';
        if($cpCfg['cp.hasMultiUniqueSites'] == 1){
            $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
            $appendSqlSite = "AND o.site_id = {$cpSiteIdSession}";
        }

        $today     = date("Y-m-d");
        $yesterday = date("Y-m-d", strtotime("yesterday"));

        $SQL = "
        SELECT o.*
              ,(SELECT SUM((oi.qty)*(oi.unit_price))
               FROM order_item oi
               WHERE o.order_id = oi.order_id
              ) AS order_amount
        FROM `order` o
        WHERE o.order_status IN ('Pending', 'New')
           {$appendSqlSite}
           AND o.order_date IN('{$today}', '{$yesterday}')
       ORDER BY order_id DESC
        ";

        $result  = $db->sql_query($SQL);

        $rows = '';

            while ($row = $db->sql_fetchrow($result)) {

                if($row['order_id'] < 10){
                    $orderId = '0000' . $row['order_id'];
                }
                else if($row['order_id'] <= 99){
                    $orderId = '000' . $row['order_id'];
                }
                else if($row['order_id'] <= 999){
                    $orderId = '00' . $row['order_id'];
                }
                else if($row['order_id'] <= 9999){
                    $orderId = '0' . $row['order_id'];
                }
                else{
                    $orderId = $row['order_id'];
                }

                $bill_number = $row['bill_number'];
            
                if($bill_number < 10){
                    $billNo = '0000' . $bill_number;
                }
                else if($bill_number <= 99){
                    $billNo = '000' . $bill_number;
                }
                else if($bill_number <= 999){
                    $billNo = '00' . $bill_number;
                }
                else if($bill_number <= 9999){
                    $billNo = '0' . $bill_number;
                }
                else{
                    $billNo = $bill_number;
                }

                $orderAmount = $this->getTotalAmount($row['order_id']);
                $orderAmount = number_format($orderAmount, 2);

                if ($cpCfg['cp.restaurent'] == 0){
                    $rest ="<td>{$row['cust_company_name']}</td>";
                }else{
                    $rest ="<td>{$row['table_value']}</td>";
                }

                $rows .= "
                    <tr order_id={$row['order_id']}>
                        <td>{$fn->getCPDate($row['order_date'], 'd-m-Y')}</td>
                        {$rest}
                        <td><a href='#'  class='pendingOrderID'><u>{$orderId}</u></a></td>
                        <td>{$billNo}</td>
                        <td align='right'>{$orderAmount}</td>
                        <td>{$row['order_status']}</td>
                        <td>{$row['created_by']}</td>
                    </tr>
                ";
            }


            if ($cpCfg['cp.restaurent'] == 0){
                $rests ="<th>Customer Name</th>";
            }else{
                $rests ="<th>Table Name</th>";
            }

        $text = "
        <table class='list thinlist'>
            <thead>
                <tr>
                    <th>Date</th>
                    {$rests}
                    <th>Order Code</th>
                    <th>Bill No</th>
                    <th class='txtRight'>Amount</th>
                    <th>Status</th>
                    <th>Billed By</th>
                </tr>
            </thead>
            <h1>*Please click the order code</h1>
            {$rows}
        </table>
        ";

        return $text;
    }

    /**
     *
     */
    function getOrderStatusToPending() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $SQL    = "
        UPDATE `order`
        set order_status = 'Pending'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $_SESSION['order_id'] = '';
    }

    /**
     *
     */
   function getInsertOldOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_id = $fn->getReqParam('order_id');

        $_SESSION['order_id'] = $order_id;
    }

    /**
     *
     */
    function getProductPriceDisplay(){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $product_id = $fn->getReqParam('product_id');

        $SQL    = "
        SELECT p.*
        FROM product p
        WHERE p.product_id = '{$product_id}'
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        $StockSql = "
        SELECT
            (SELECT SUM(qty) FROM po_product
            WHERE product_id = '{$product_id}'
            ) as product_qty_purchased
            ,(SELECT SUM(damage_qty) FROM po_product
            WHERE product_id = '{$product_id}'
            ) as damage_qty
            ,(SELECT SUM(oi.qty) FROM order_item oi
            LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
            WHERE record_id = '{$product_id}'
              AND o.order_status = 'Paid'
            ) as product_qty_sold
        ";
        $resultStockSql = $db->sql_query($StockSql);
        $rowStockSql    = $db->sql_fetchrow($resultStockSql);

        $stock = $rowStockSql['product_qty_purchased'] - $rowStockSql['product_qty_sold'] - $rowStockSql['damage_qty'];

		$rows = "
		<tr>
			<td>{$row['item_code']}</td>
			<td class='w25p'>{$row['title']}</td>
			<td>{$row['model']}</td>
			<td>{$row['carton_no']}</td>
			<td>{$row['batch_no']}</td>
			<td class='unitPrice txtRight'>{$row['price']}</td>
			<td>{$row['unit']}</td>
			<td>{$stock}</td>
			<td>{$row['fc_price_code']}</td>
		</tr>
		";

		$text = "
		{$rows}
		";

        return $text;
    }
        
    /**
     *
     */
    function getPrintInvoiceRecord() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');
        $receipt_amount = $fn->getReqParam('receipt_amount');
        $change = $fn->getReqParam('change');


        if($cpCfg['cp.demoVersionStatus'] == 1){
            $this->getDemoPrintBill();
        }

        else{

            if ($cpCfg['cp.serialKeyActive'] == "SULB-DHEO-0R6K-59CL" || $cpCfg['cp.serialKeyActive'] == "YODX0-9DT58-VCZ5W-A8XXB") {
                $order_id = $fn->getReqParam('orderNo');
                $modObj = getCPModuleObj('tradingsg_order');
                $modObj->view->getPrintInvoiceRecordSurgicalShop($order_id);
            }
            
            else if($cpCfg['cp.companyType'] == "Computer Shop"){
                $order_id = $fn->getReqParam('orderNo');
                $modObj = getCPModuleObj('tradingsg_order');
                $modObj->view->getPrintInvoiceRecordComputerSystems($order_id);
            }
            else if($cpCfg['cp.companyType'] == "Aachi Dealer"){
                $order_id = $fn->getReqParam('orderNo');
                $modObj = getCPModuleObj('tradingsg_order');
                $modObj->view->getPrintInvoiceRecordFinance($order_id);
            }

            else
            {
                ini_set('memory_limit', '512M');
                set_time_limit(50000);

                include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
                include_once(CP_LOCAL_PATH.'lib/headfootBillPrint.php');

                //$pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                //$pdf = new MYPDF_Local('L', 'px', array('261.250', '110.48'), true, 'UTF-8', false);
                $orderNo = $fn->getReqParam('orderNo');
                $discountValueTotal = 0;

                $subSqlForPercentSum = "
                SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2)) as discount_sum
                FROM order_item oi
                WHERE oi.order_id = {$orderNo}
                  AND oi.discount_type = '%'
                ";
                $resultSubSql = $db->sql_query($subSqlForPercentSum);
                $rowSql       = $db->sql_fetchrow($resultSubSql);
                if($rowSql['discount_sum'] > 0){
                    $subSqlForPercentSum = "
                    SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2))
                    FROM order_item oi
                    WHERE oi.order_id = {$orderNo}
                      AND oi.discount_type = '%'
                    ";
                }
                else{
                    $subSqlForPercentSum = 0;
                }


                //TO CHECK IF THE SUM OF DISCOUNT TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
                $subSqlForValueSum ="
                SELECT SUM(round(oi.discount_amount  * oi.qty,2)) as discount_sum
                FROM order_item oi
                WHERE oi.order_id = {$orderNo}
                  AND oi.discount_type = 'Value'
                ";
                $resultSubSql = $db->sql_query($subSqlForValueSum);
                $rowSql       = $db->sql_fetchrow($resultSubSql);
                if($rowSql['discount_sum'] > 0){
                    $subSqlForValueSum ="
                    SELECT SUM(round(oi.discount_amount  * oi.qty,2))
                    FROM order_item oi
                    WHERE oi.order_id = {$orderNo}
                      AND oi.discount_type = 'Value'
                    ";
                }
                else{
                    $subSqlForValueSum = 0;
                }

                $SQL = "
                SELECT oi.*
                      ,p.title AS product_title
                      ,p.unit
                      ,p.item_code
                      ,p.batch_no
                      ,p.hsn AS hsn_sac
                      ,p.carton_no
                      ,CONCAT_WS('::', p.carton_no, p.batch_no, p.model) code
                      ,o.order_date
                      ,o.discount
                      ,o.gst_status
                      ,o.order_id
                      ,o.bill_number
                      ,o.shipping_charge
                      ,o.cust_phone
                      ,o.cust_address_city
                      ,o.cust_address1
                      ,o.cust_address2
                      ,o.cust_company_name
                      ,o.bill_by
                      ,DATE_FORMAT(o.creation_date, '%d-%m-%Y %h:%i:%s %p')AS invoice_creation
                      ,i.vat AS invoice_vat
                      ,i.invoice_date
                      ,DATE_FORMAT(o.order_date, '%d-%m-%Y')AS invoice_creation_date
                      ,i.invoice_code_vat
                      ,i.invoice_code
                      ,oi.qty * oi.unit_price AS amount
                      ,(SELECT SUM(oit.qty * oit.unit_price) FROM order_item oit
                       WHERE oit.order_id = oi.order_id) AS sub_total
                      ,(SELECT
                       ($subSqlForPercentSum)
                        +
                       ($subSqlForValueSum)) as discount_percentage_amount_sum
                FROM order_item oi
                LEFT JOIN product p ON (p.product_id = oi.record_id)
                LEFT JOIN `order` o ON (o.order_id = oi.order_id)
                LEFT JOIN invoice i ON (i.order_id = o.order_id)
                WHERE o.order_id = '{$orderNo}'
                ORDER BY p.title
                ";
                $result  = $db->sql_query($SQL);
                $numRows = $db->sql_numrows($result);
                $result2 = $db->sql_query($SQL);
                $company = $db->sql_fetchrow($result2);
                //============================================================================= //

                $today = date("d-m-Y");

                if($company['bill_number'] < 10){
                    $billNo = '0000' . $company['bill_number'];
                }
                else if($company['bill_number'] <= 99){
                    $billNo = '000' . $company['bill_number'];
                }
                else if($company['bill_number'] <= 999){
                    $billNo = '00' . $company['bill_number'];
                }
                else if($company['bill_number'] <= 9999){
                    $billNo = '0' . $company['bill_number'];
                }
                else{
                    $billNo = $company['bill_number'];
                }

                if($company['order_id'] < 10){
                    $orderIdHead = '0000' . $company['order_id'];
                }
                else if($company['order_id'] <= 99){
                    $orderIdHead = '000' . $company['order_id'];
                }
                else if($company['order_id'] <= 999){
                    $orderIdHead = '00' . $company['order_id'];
                }
                else if($company['order_id'] <= 9999){
                    $orderIdHead = '0' . $company['order_id'];
                }
                else{
                    $orderIdHead = $company['order_id'];
                }

                $SQLStaff = "
                SELECT user_name
                FROM staff
                WHERE staff_id = '{$_SESSION['staff_id']}'
                ";
                $resultStaff = $db->sql_query($SQLStaff);
                $rowStaff    = $db->sql_fetchrow($resultStaff);

                $header= '
                <table border="0" width="100%" style="border-bottom:1px solid black;">


                <tr>
                    <td align="left" width="20%"><br/><br/><img src="images/logo-print.png" width="" height=""/></td>
                    <td align="center" width="80%"><span style="font-size:12px">'.$cpCfg['cp.companyName'].'</span><br/>
                        '.$cpCfg['cp.addressPdf1'].'<br/>
                        '.$cpCfg['cp.addressPdf2'].'
                        '.$cpCfg['cp.addressPdf3'].'<br/>
                        '.$cpCfg['cp.addressPdf5'].'<br/>
                        '.$cpCfg['cp.mobileNumInBill'].'
                    </td>
                </tr>
                </table>
                ';

                $tbl1 ='
                <table border="0" width="100%" cellpadding="2">
                    <tr>
                        <th align="left" width="13%">BNo:</th>
                        <th align="left" width="19%">'.$company['bill_number'].'</th>
                        <th align="right" width="6%"></th>
                        <th align="left" width="62%">'.$company['invoice_creation'].'</th>
                    </tr>
                    <tr>
                        <th align="left" width="23%">Bill By:</th>
                        <th align="left" width="33%">'.$company['bill_by'].'</th>
                        <th align="right" width="24%">ORD NO:</th>
                        <th align="left" width="20%">'.$orderIdHead.'</th>
                    </tr>
                </table>
                ';


                $tbl2 ='<table border="0" width="100%" cellpadding="2">
                            <tr>
                                <th width="8%"  align="left"   style="border-top:1px dashed black;">#</th>
                                <th width="68%" align="left"   style="border-top:1px dashed black;">Product Name</th>
                                <th width="24%" align="left"   style="border-top:1px dashed black;">HSN-SAC</th>
                            </tr>
                            <tr>
                                <th width="14%" align="center" style="border-bottom:1px dashed black;">Qty</th>
                                <th width="23%" align="right"  style="border-bottom:1px dashed black;">Price</th>
                                <th width="19%" align="right"  style="border-bottom:1px dashed black;">Disc.</th>
                                <th width="15%" align="right"  style="border-bottom:1px dashed black;">Gst</th>
                                <th width="28%" align="right"  style="border-bottom:1px dashed black;">Value</th>
                            </tr>
                ';

                $subTotal = 0;
                $discountValueTotal = 0;
                $Overallsubtotalwithoutdiscount = 0;
                $savedAmount = 0;
                $total_qty = 0;
                $serialNo = 0;
                $overall_vat_Sum = 0;

                if($company['gst_status'] == "ON"){
                    $heightPage = 390;
                }else{
                    $heightPage = 310;
                }

                $discount = 0;
                $sub_total = 0;
                while ($row = $db->sql_fetchrow($result)) {
                    $serialNo++;

                    $discount_value_for_one_qty = '';
                    $discountValue = 0;
                    $discount_percentage = '';
                    $discount_percentage_type =0;
                    if($row['discount_percentage'] > 0 || $row['discount_amount'] > 0){
                        if($row['discount_type'] == '%'){
                            $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                            $discountValue = $discount_value_for_one_qty * $row['qty'];
                            $discount_percentage_type = $discountValue;
                            $discount_percentage = '';
                        }
                        else if($row['discount_type']  == 'Value'){
                            $discount_value_for_one_qty  =  $row['discount_amount'];
                            $discountValue = $discount_value_for_one_qty * $row['qty'];
                            $discount_percentage = $row['discount_amount'];
                            $discount_percentage_type = $row['discount_amount'];
                        }
                        
                    }

                    if($discountValue == ""){
                        $discountValue = 0;
                    }
                    
                    if($row['weight'] > 0){
                        $subtotalwithoutdiscount = $row['weight'] * $row['unit_price'];
                        $total = $row['weight'] * ($row['unit_price'] - $discount_value_for_one_qty);
                        $qty = $row['weight'];
                        $unit_price = $total;
                    } else {
                        $subtotalwithoutdiscount = $row['qty'] * $row['unit_price'];
                        $total = $row['qty'] * ($row['unit_price'] - $discount_value_for_one_qty);
                        $qty = $row['qty'];
                        $unit_price = $row['unit_price'];
                    }

                    $gstValue = 0;
                    
                    if($row['gst_status'] == "ON"){
                        $gstValue = $total * $row['gst'] / 100;
                        $total    = $total + $gstValue;
                    }
                    
                    $subTotal += $total;
                    $Overallsubtotalwithoutdiscount += $subtotalwithoutdiscount;
                    $discount = $row['discount'];
                    $discount_percentage_amount_sum = $row['discount_percentage_amount_sum'] + $discount;
                    //$discount_percentage_amount_sum = $discount;
                    $savedAmount = $discount_percentage_amount_sum;

                    $discountValueTotal += $row['discounted_amount'];
                    $tbl2 = $tbl2.'
                            <tr>
                                <td width="8%"  align="left">'.$serialNo.'</td>
                                <td width="68%" align="left">['.$row['product_title'].']</td>
                                <td width="24%" align="left">'.$row['hsn_sac'].'</td>
                            </tr>
                            <tr>
                                <td width="14%" align="center">'.$qty.'</td>
                                <td width="23%" align="right">'.number_format($unit_price,2).'</td>
                                <td width="19%" align="right">'.number_format($discountValue, 2).'</td>
                                <th width="15%" align="right">'.number_format($gstValue, 2).'</th>
                                <td width="28%" align="right">'.number_format($total, 2).'</td>
                            </tr>
                    ';

                    $total_qty += $row['qty'];
                    //$sub_total = $row['sub_total'];
                    $discount  = $row['discount'];

                    $overall_vat_Sum  += ($row['total_amount'] * $row['gst'])/100;

                    $heightPage = $heightPage + 40;
                }

                $tbl2 = $tbl2.'</table>';

                $tbl3 = '<table>';

                $serialNoTotal = $serialNo;

                $totalAmount     = $subTotal - $discount;
                $discountOverall = $discount + $discountValueTotal;

                $tbl4 = '<table cellpadding="4" border="0">';

                $tbl4 = $tbl4.'
                        <tr>
                            <td align="left" colspan="3"><font style="font-size: 11px;">Tax Summary:</font></td>
                        </tr>
                        <tr>
                            <td align="left"  style="border-top:1px dashed black;border-bottom:1px dashed black;">Tax Type</td>
                            <td align="right" style="border-top:1px dashed black;border-bottom:1px dashed black;">Taxable</td>
                            <td align="right" style="border-top:1px dashed black;border-bottom:1px dashed black;">Tax Amt</td>
                        </tr>
                ';

                $vatSumDisplayRow = '
                <tr>
                    <td colspan="2" style="border-top:1px dashed black;"></td>
                </tr>
                ';

                if($company['gst_status'] == "ON"){

                    $SQLTax = "
                    SELECT  gst
                            ,order_id
                            ,SUM((unit_price * qty) - ((unit_price * discount_percentage) /100 * qty) - discount_amount) AS qty_amount
                    FROM `order_item` 
                    WHERE order_id = '{$orderNo}'
                    AND gst > 0
                    GROUP BY gst
                    ORDER BY gst ASC
                    ";
                    $resultTax  = $db->sql_query($SQLTax);

                    $totalVatSum = 0;
                    while($rowTax     = $db->sql_fetchrow($resultTax)){

                        $total_amount = $rowTax['qty_amount'];
                        
                        if($rowTax['gst'] == ''){
                            $vatPercent = '0.00';
                        }
                        else{
                            $vatPercent = $rowTax['gst'];
                        }

                        $vat_Sum  = ($total_amount * $rowTax['gst'])/100;

                        $vat_Amount_total = $total_amount + $vat_Sum;
                        if($vat_Sum == 0){
                            $vat_Amount_total = 0;
                        }

                        $vatPercentHalf = $vatPercent / 2;
                        $vat_Sum_Half = $vat_Sum / 2;

                        $totalVatSum += $vat_Sum;

                        $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);

                        $tbl4 = $tbl4.'
                        <tr>
                            <td align="left">SGST '.$vatPercentHalf.' %</td>
                            <td align="right">'.number_format($vat_Amount_total, 2).'</td>
                            <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
                        </tr>
                        <tr>
                            <td align="left">CGST '.$vatPercentHalf.' %</td>
                            <td align="right">'.number_format($vat_Amount_total, 2).'</td>
                            <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
                        </tr>
                        ';

                        $heightPage = $heightPage + 20;
                    }

                    $totalVatSum = number_format($totalVatSum, 2);

                    $vatSumDisplayRow = '
                    <tr>
                        <td align="right"><font style="font-size: 11px;">Tax Amount</font></td>
                        <td align="right"><font style="font-size: 11px;">'.$totalVatSum.'</font></td>
                    </tr>
                    ';
                }

                $shipping_charge = $company['shipping_charge'];

                if($shipping_charge == ""){
                    $shipping_charge = 0;
                }

                $shipping_charge_row = "";
                if($shipping_charge != "" && $shipping_charge > 0){
                    $shipping_charge_row = '
                    <tr>
                        <td align="right"><font style="font-size: 11px;">Shipping Charges</font></td>
                        <td align="right"><font style="font-size: 11px;">'.number_format($shipping_charge,2).'</font></td>
                    </tr>
                    ';
                    
                    $totalAmount = $totalAmount + $shipping_charge;
                }

                $totalNetAmount = $totalAmount;
                $roundOffAmount = round($totalAmount) - $totalAmount;
                $totalAmount    = round($totalAmount);

                $tbl3 = $tbl3.'
                        <tr>
                            <td align="right" style="border-top:1px dashed black;"><font style="font-size: 11px;">Sub Total</font></td>
                            <td align="right" style="border-top:1px dashed black;"><font style="font-size: 11px;">'.number_format($subTotal,2).'</font></td>
                        </tr>
                        <tr>
                            <td align="right"><font style="font-size: 11px;">Discount</font></td>
                            <td align="right"><font style="font-size: 11px;">-'.number_format($savedAmount,2).'</font></td>
                        </tr>
                        '.$shipping_charge_row.'
                        <tr>
                            <td align="right"><font style="font-size: 11px;">Total Amount</font></td>
                            <td align="right"><font style="font-size: 11px;">'.number_format($totalNetAmount,2).'</font></td>
                        </tr>
                        <tr>
                            <td align="right"><font style="font-size: 11px;">Round Off</font></td>
                            <td align="right"><font style="font-size: 11px;">'.number_format($roundOffAmount,2).'</font></td>
                        </tr>
                        <tr>
                            <td align="right" style="border-top:1px dashed black;border-bottom:1px dashed black;line-height:20px;"><font style="font-size:14px;">Total</font></td>
                            <td align="right" style="border-top:1px dashed black;border-bottom:1px dashed black;line-height:20px;"><font style="font-size:14px;">'.number_format($totalAmount, 2).'</font></td>
                        </tr>
                        <tr>
                            <td align="right"><font style="font-size: 11px;">Total Items</font></td>
                            <td align="right"><font style="font-size: 11px;">'.$serialNoTotal.'</font></td>
                        </tr>
                        <tr>
                            <td align="right"><font style="font-size: 11px;">Total Qty</font></td>
                            <td align="right"><font style="font-size: 11px;">'.$total_qty.'</font></td>
                        </tr>
                        '.$vatSumDisplayRow.'
                        <tr>
                            <td align="right"><font style="font-size: 11px;">Cash Received</font></td>
                            <td align="right"><font style="font-size: 11px;">'.$receipt_amount.'</font></td>
                        </tr>
                        <tr>
                            <td align="right" style="border-bottom:1px dashed black;"><font style="font-size: 11px;">Balance</font></td>
                            <td align="right" style="border-bottom:1px dashed black;"><font style="font-size: 11px;">'.$change.'</font></td>
                        </tr>
                ';
                
                $tbl3 = $tbl3.'</table>';

                if($company['gst_status'] == "ON"){

                    $tbl4 = $tbl4.'
                    <tr>
                        <td align="left"  style="border-top:1px dashed black;border-bottom:1px dashed black;" >TOTAL</td>
                        <td align="right" style="border-top:1px dashed black;border-bottom:1px dashed black;" colspan="2">'.$totalVatSum.'</td>
                    </tr>
                    ';

                    $tbl4 = $tbl4.'</table>';
                }

                $customerDetails = '';

                if($cpCfg['cp.showCustomerDetailsInFooter'] == 1){
                    $customerDetails='Cust Name: '.$company['cust_company_name'].'<br>Mobile: '.$company['cust_phone'].'<br>Address: '.$company['cust_address1'].', '.$company['cust_address2'].', '.$company['cust_address_city'];
                }

                $tbl5 = '
                <table cellpadding="4">
                    <tr>
                        <td align="" width="100%">'.$customerDetails.'</td>
                    </tr>
                    <tr>
                        <td align="center" width="100%">THANK YOU VISIT AGAIN!<br>'.$cpCfg['cp.posBillFooterText'].'</td>
                    </tr>
                </table>
                ';

                if($heightPage >= '780'){
                    $heightPage = '790';
                }

                $pdf = new MYPDF_Local('P', 'px', array('212.250', $heightPage), true, 'UTF-8', false);                
                //$fontname = $pdf->addTTFfont('E:/Projects/cmsPilot/v3.0/library/lib_php/tcpdf/fonts/akshar.ttf', 'TrueTypeUnicode', '', 32);
                
                // set document information
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetAuthor('USS');
                $pdf->SetSubject('Print Link');
                $pdf->SetTitle('Print Link');
                $pdf->setPrintHeader(false);

                // set default header data
                $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 04', PDF_HEADER_STRING);
                // set header and footer fonts
                $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
                $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

                // set default monospaced font
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

                // set margins
                $pdf->SetMargins(PDF_MARGIN_LEFT, 0, PDF_MARGIN_RIGHT);
                $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
                $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
                $pdf->setPrintFooter(false);

                // set auto page breaks
                $pdf->SetAutoPageBreak(TRUE, 1);

                // set image scale factor
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

                // set some language-dependent strings (optional)
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }

                /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
                $pdf->AddPage();
                $pdf->SetFont('Courier','B',8);

                $pdf->writeHTML($header, true, false, false, false, '');
                $pdf->ln(-5);
                $pdf->writeHTML($tbl1, true, false, false, false, '');
                $pdf->ln(-10);
                $pdf->SetFont('Courier','B',8);
                $pdf->writeHTML($tbl2, true, false, false, false, '');
                $pdf->SetFont('Courier','B',8);
                $pdf->writeHTML($tbl3, true, false, false, false, '');

                if($company['gst_status'] == "ON"){
                    //$pdf->writeHTML($tbl4, true, false, false, false, '');
                }

                $pdf->writeHTML($tbl5, true, false, false, false, '');
                $download_title = $company['order_id'] . '-Invoice.pdf';
                $pdf->IncludeJS("print();");
                //$pdf->Output('lib/template/temp/'.$download_title, 'F');
                $pdf->Output($download_title, 'I');
                //return $this->getPrintbillcondition($printOnly);
            }
        }
    }

    /**
     *
     */
    function getDemoPrintBill() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfootBillPrintDemo.php');

        $today = date("d-m-Y");
        $invoice_date = date("d-m-Y h:i a");

        if(10001 < 10){
            $billNo = '0000' . 10001;
        }
        else if(10001 <= 99){
            $billNo = '000' . 10001;
        }
        else if(10001 <= 999){
            $billNo = '00' . 10001;
        }
        else if(10001 <= 9999){
            $billNo = '0' . 10001;
        }
        else{
            $billNo = 10001;
        }

        if(5 < 10){
            $orderIdHead = '0000' . 5;
        }
        else if(5 <= 99){
            $orderIdHead = '000' . 5;
        }
        else if(5 <= 999){
            $orderIdHead = '00' . 5;
        }
        else if(5 <= 9999){
            $orderIdHead = '0' . 5;
        }
        else{
            $orderIdHead = 5;
        }

        $tbl1 ='
        <table border="0" width="100%" cellpadding="2">
            <tr>
                <th align="left" width="13%">BNo:</th>
                <th align="left" width="19%">'.$billNo.'</th>
                <th align="right" width="15%">Date:</th>
                <th align="left" width="53%">'.$invoice_date.'</th>
            </tr>
            <tr>
                <th align="left" width="23%">Bill By:</th>
                <th align="left" width="33%">Staff Name</th>
                <th align="right" width="24%">ORD NO:</th>
                <th align="left" width="20%">'.$orderIdHead.'</th>
            </tr>
        </table>
        ';

        $tbl2 ='<table border="0" width="100%" cellpadding="2">
                    <tr>
                        <th width="6%"  align="left"   style="border-top:1px dashed black;">#</th>
                        <th width="70%" align="left"   style="border-top:1px dashed black;">Product Name</th>
                        <th width="24%" align="left"   style="border-top:1px dashed black;">HSN-SAC</th>
                    </tr>
                    <tr>
                        <th width="15%" align="center" style="border-bottom:1px dashed black;">Qty</th>
                        <th width="35%" align="right"  style="border-bottom:1px dashed black;">Price</th>
                        <th width="50%" align="right"  style="border-bottom:1px dashed black;">Total Amt</th>
                    </tr>
        ';

        $total_qty = 0;
        $serialNo = 2;
        $overall_vat_Sum = 0;
        $heightPage = 350;
        $discount = 0;

        $tbl2 = $tbl2.'
                <tr>
                    <td width="6%"  align="left">1</td>
                    <td width="70%" align="left">Sample Product 1</td>
                    <td width="24%" align="left">12025124</td>
                </tr>
                <tr>
                    <td width="15%" align="center">2</td>
                    <td width="35%" align="right">'.number_format(525,2).'</td>
                    <td width="50%" align="right">'.number_format(1050, 2).'</td>
                </tr>
        ';

        $tbl2 = $tbl2.'
                <tr>
                    <td width="6%"  align="left">2</td>
                    <td width="70%" align="left">Sample Product 2</td>
                    <td width="24%" align="left">120263647</td>
                </tr>
                <tr>
                    <td width="15%" align="center">2</td>
                    <td width="35%" align="right">'.number_format(560,2).'</td>
                    <td width="50%" align="right">'.number_format(1120, 2).'</td>
                </tr>
        ';

        $total_qty    = 4;
        $sub_total    = 2170;
        $discount     = 50;

        $overall_vat_Sum  += (2120 * 10)/100;

        $heightPage = $heightPage + 40;

        $tbl2 = $tbl2.'</table>';

        $tbl3 = '<table>';

        $serialNoTotal = $serialNo;

        $totalAmount = $sub_total - $discount;
        $discountOverall = $discount;

        $tbl3 = $tbl3.'
                <tr>
                    <td align="right" style="border-top:1px dashed black;"><font style="font-size: 11px;">Sub Total</font></td>
                    <td align="right" style="border-top:1px dashed black;"><font style="font-size: 11px;">'.number_format($sub_total,2).'</font></td>
                </tr>
                <tr>
                    <td align="right"><font style="font-size: 11px;">Discount</font></td>
                    <td align="right"><font style="font-size: 11px;">'.number_format($discountOverall,2).'</font></td>
                </tr>
                <tr>
                    <td align="right" style="border-top:1px dashed black;border-bottom:1px dashed black;line-height:20px;"><font style="font-size:14px;">Total</font></td>
                    <td align="right" style="border-top:1px dashed black;border-bottom:1px dashed black;line-height:20px;"><font style="font-size:14px;">'.number_format($totalAmount, 2).'</font></td>
                </tr>
                <tr>
                    <td align="right"><font style="font-size: 11px;">Total Items</font></td>
                    <td align="right"><font style="font-size: 11px;">'.$serialNoTotal.'</font></td>
                </tr>
                <tr>
                    <td align="right"><font style="font-size: 11px;">Total Qty</font></td>
                    <td align="right"><font style="font-size: 11px;">'.$total_qty.'</font></td>
                </tr>
                <tr>
                    <td align="right" style="border-bottom:1px dashed black;"><font style="font-size: 11px;">Tax Amount</font></td>
                    <td align="right" style="border-bottom:1px dashed black;"><font style="font-size: 11px;">'.number_format($overall_vat_Sum, 2).'</font></td>
                </tr>
        ';
        
        $tbl3 = $tbl3.'</table>';

        $tbl4 = '<table cellpadding="4" border="0">';

        $tbl4 = $tbl4.'
                <tr>
                    <td align="left" colspan="3"><font style="font-size: 11px;">Tax Summary:</font></td>
                </tr>
                <tr>
                    <td align="left"  style="border-top:1px dashed black;border-bottom:1px dashed black;">Tax Type</td>
                    <td align="right" style="border-top:1px dashed black;border-bottom:1px dashed black;">Taxable</td>
                    <td align="right" style="border-top:1px dashed black;border-bottom:1px dashed black;">Tax Amt</td>
                </tr>
        ';


        $vatPercent = 10;

        $vat_Sum    = (2120 * 10)/100;

        $vat_Amount_total = 2120 - $vat_Sum;
        if($vat_Sum == 0){
            $vat_Amount_total = 0;
        }

        $vatPercentHalf = $vatPercent / 2;
        $vat_Sum_Half = $vat_Sum / 2;

        $totalVatSum = $vat_Sum;

        $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);

        $tbl4 = $tbl4.'
        <tr>
            <td align="left">SGST '.$vatPercentHalf.' %</td>
            <td align="right">'.number_format($vat_Amount_total, 2).'</td>
            <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
        </tr>
        <tr>
            <td align="left">CGST '.$vatPercentHalf.' %</td>
            <td align="right">'.number_format($vat_Amount_total, 2).'</td>
            <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
        </tr>
        ';

        $heightPage = $heightPage + 10;

        $tbl4 = $tbl4.'
        <tr>
            <td align="left"  style="border-top:1px dashed black;border-bottom:1px dashed black;" >TOTAL</td>
            <td align="right" style="border-top:1px dashed black;border-bottom:1px dashed black;" colspan="2">'.number_format($totalVatSum, 2).'</td>
        </tr>
        <tr>
            <td align="center" width="100%">THANK YOU VISIT AGAIN !</td>
        </tr>
        ';

        $tbl4 = $tbl4.'</table>';

        $pdf = new MYPDF_Local('P', 'px', array('212.250', $heightPage), true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('USS');
        $pdf->SetSubject('Print Link');
        $pdf->SetTitle('Print Link');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 04', PDF_HEADER_STRING);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER,10);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
        $pdf->AddPage();
        $pdf->SetFont('Courier','B',8);

        $pdf->ln(10);
        $pdf->writeHTML($tbl1, true, false, false, false, '');
        $pdf->ln(-10);
        $pdf->writeHTML($tbl2, true, false, false, false, '');
        $pdf->writeHTML($tbl3, true, false, false, false, '');
        $pdf->writeHTML($tbl4, true, false, false, false, '');
        $download_title = '10001' . '-Invoice.pdf';
        $pdf->IncludeJS("print();");
        $pdf->Output($download_title, 'I');
    }

    /**
     *
     */
     function getCreditCard() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');

        $formAction = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=creditCardSubmit&showHTML=0";

        $text = "
        <form id='portalForm' class='yform columnar' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Card Number', 'credit_card_no', '')}
        </form>
        ";
        return $text;

    }

    /**
     *
     */
     function getSaleByName() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $order_id = $fn->getReqParam('order_id');

        $formAction = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=saleByNameSubmit&showHTML=0";

        $text = "
        <form id='portalSaleNameForm' class='yform columnar' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Name', 'name', '')}
            <input type='hidden' name='order_id' value='{$order_id}'/>
        </form>
        ";
        return $text;

    }

    /**
     *
     */
    function getTotalAmount($session_order_id = ''){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        if($session_order_id == ""){
            $session_order_id = $fn->getReqParam('session_order_id');
        }

        $subSqlForPercentSum = "
        SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2)) as discount_sum
        FROM order_item oi
        WHERE oi.order_id = {$session_order_id}
          AND oi.discount_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForPercentSum = "
            SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2))
            FROM order_item oi
            WHERE oi.order_id = {$session_order_id}
              AND oi.discount_type = '%'
            ";
        }
        else{
            $subSqlForPercentSum = 0;
        }


        //TO CHECK IF THE SUM OF DISCOUNT TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForValueSum ="
        SELECT SUM(round(oi.discount_amount  * oi.qty,2)) as discount_sum
        FROM order_item oi
        WHERE oi.order_id = {$session_order_id}
          AND oi.discount_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlForValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForValueSum ="
            SELECT SUM(round(oi.discount_amount  * oi.qty,2))
            FROM order_item oi
            WHERE oi.order_id = {$session_order_id}
              AND oi.discount_type = 'Value'
            ";
        }
        else{
            $subSqlForValueSum = 0;
        }

        $SQL = "
        SELECT oi.*
              ,p.title AS product_title
              ,p.unit
              ,p.item_code
              ,p.batch_no
              ,p.hsn AS hsn_sac
              ,p.carton_no
              ,CONCAT_WS('::', p.carton_no, p.batch_no, p.model) code
              ,o.order_date
              ,o.discount
              ,o.order_id
              ,o.bill_number
              ,o.gst_status
              ,i.vat AS invoice_vat
              ,i.invoice_date
              ,DATE_FORMAT(o.creation_date, '%d-%m-%Y')AS invoice_creation_date
              ,i.invoice_code_vat
              ,i.invoice_code
              ,oi.qty * oi.unit_price AS amount
              ,(SELECT SUM(oit.qty * oit.unit_price) FROM order_item oit
               WHERE oit.order_id = oi.order_id) AS sub_total
              ,(SELECT
               ($subSqlForPercentSum)
                +
               ($subSqlForValueSum)) as discount_percentage_amount_sum
        FROM order_item oi
        LEFT JOIN product p ON (p.product_id = oi.record_id)
        LEFT JOIN `order` o ON (o.order_id = oi.order_id)
        LEFT JOIN invoice i ON (i.order_id = o.order_id)
        WHERE o.order_id = '{$session_order_id}'
        ORDER BY p.title
        ";
        $result = $db->sql_query($SQL);

        $sub_total = 0;
        $total_qty = 0;
        $discount = 0;
        $subTotal = 0;
        $discountValueTotal = 0;
        $Overallsubtotalwithoutdiscount = 0;

        while ($row = $db->sql_fetchrow($result)) {

            $discount_value_for_one_qty = '';
            $discountValue = 0;
            $discount_percentage = '';
            $discount_percentage_type =0;
            if($row['discount_percentage'] > 0 || $row['discount_amount'] > 0){
                if($row['discount_type'] == '%'){
                    $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                    $discount_percentage_type = $discountValue;
                    $discount_percentage = '';
                }
                else if($row['discount_type']  == 'Value'){
                    $discount_value_for_one_qty  =  $row['discount_amount'];
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                    $discount_percentage = $row['discount_amount'];
                    $discount_percentage_type = $row['discount_amount'];
                }
            }

            $subtotalwithoutdiscount = $row['qty'] * $row['unit_price'];
            $total = $row['qty'] * ($row['unit_price'] - $discount_value_for_one_qty);
            $gstValue = $total * $row['gst'] / 100;
            
            if($row['gst_status'] == "ON"){
                $total    = $total + $gstValue;
            }

            $subTotal += $total;
            $Overallsubtotalwithoutdiscount += $subtotalwithoutdiscount;
            $row['gst_value'] = $total * $row['gst'] / 100;
            $discount = $row['discount'];
            $discount_percentage_amount_sum = $row['discount_percentage_amount_sum'] + $discount;
            $savedAmount = $discount_percentage_amount_sum;

        }

        $rowOrder = $fn->getRecordRowByID('order', 'order_id', $session_order_id);
        $shipping_charge = $rowOrder['shipping_charge'];

        if($shipping_charge == ""){
            $shipping_charge = 0;
        }

        if($shipping_charge != "" && $shipping_charge > 0){
            $subTotal = $subTotal + $shipping_charge;
        }

        $totalAmount = $subTotal - $discount;
        
        return $totalAmount;
    }

    function getGenerateKeyString() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg  = Zend_Registry::get('cpCfg');

        //http://cubobillpro.localhost/admin/index.php?module=tradingsg_pos&_spAction=generateKeyString&showHTML=0

        $k = 1000;
        for ($m = 0; $m <= $k; $m++) {
            $tokens = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $segment_chars = 4;
            $num_segments = 4;
            $key_string = '';

            $segment_chars2 = 5;
            $num_segments2 = 4;
            $key_string2 = '';
         
            for ($i = 0; $i < $num_segments; $i++) {
         
                $segment  = '';
                $segment2 = ''; 
         
                for ($j = 0; $j < $segment_chars; $j++) {
                        $segment .= $tokens[rand(0, 35)];
                }

                for ($s = 0; $s < $segment_chars2; $s++) {
                        $segment2 .= $tokens[rand(0, 35)];
                }
                
                $key_string  .= $segment;
                $key_string2 .= $segment2;
         
                if ($i < ($num_segments - 1)) {
                        $key_string .= '-';
                }

                if ($i < ($num_segments - 1)) {
                        $key_string2 .= '-';
                }
            }

            $fa = array();
            $fa['weekly_key']  = $key_string;
            $fa['radnom_no']   = $key_string2;
            $fa['status']      = "Unused";
            $fa['no_of_days']  = "14";
            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'lead_history');
            $result = $db->sql_query($SQL);
        }
     
        return $key_string;
    }

    /**
     *
     */

     function getCancelOrderNotes() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');

        $order_id = $fn->getReqParam('order_id');
        $formAction = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=CancelOrderNotesSubmit&showHTML=0";

        $text = "
        <form id='portalCancelOrderNotesForm' class='yform columnar' method='post' action='{$formAction}'>
            {$formObj->getTARow('Notes', 'notes', '')}
            <input type='hidden' name='order_id' value='{$order_id}'/>
        </form>
        ";
        return $text;

    }

    /**
     *
     */
     function getApplyShippingCharges() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');

        $shippingChargeApplied = $fn->getReqParam('shippingChargeApplied');
        $formAction = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=applyShippingChargesSubmit&showHTML=0";

        $text = "
        <form id='portalPOSApplyShippingChargesForm' class='yform columnar' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Shipping Charge', 'shipping_charge', $shippingChargeApplied)}
        </form>
        ";
        return $text;

    }

    /**
     *
     */
     function getAddDefaultDiscountType() {
        $fn      = Zend_Registry::get('fn');
        $cpCfg   = Zend_Registry::get('cpCfg');
        $formObj = Zend_Registry::get('formObj');

        $discount_type = $cpCfg['cp.posDefaultDiscountType'];
        $formAction    = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=addDefaultDiscountTypeSubmit&showHTML=0";
        
        $sqlDiscountType = array("%", "Value");
        $expVl           = array('sqlType' => 'OneField', 'firstOptionLabel' => 'No Discount');

        $text = "
        <form id='portalPOSDiscountTypeDefaultForm' class='yform columnar' method='post' action='{$formAction}'>
            {$formObj->getDDRowByArr('Discount Type', 'discount_type_default', $sqlDiscountType, $discount_type, $expVl)}
        </form>
        ";

        return $text;
    }

    /**
     *
     */
    function getProfitAmount($session_order_id = ''){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        if($session_order_id == ""){
            $session_order_id = $fn->getReqParam('session_order_id');
        }

        $subSqlForPercentSum = "
        SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2)) as discount_sum
        FROM order_item oi
        WHERE oi.order_id = {$session_order_id}
          AND oi.discount_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForPercentSum = "
            SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2))
            FROM order_item oi
            WHERE oi.order_id = {$session_order_id}
              AND oi.discount_type = '%'
            ";
        }
        else{
            $subSqlForPercentSum = 0;
        }


        //TO CHECK IF THE SUM OF DISCOUNT TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForValueSum ="
        SELECT SUM(round(oi.discount_amount  * oi.qty,2)) as discount_sum
        FROM order_item oi
        WHERE oi.order_id = {$session_order_id}
          AND oi.discount_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlForValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForValueSum ="
            SELECT SUM(round(oi.discount_amount  * oi.qty,2))
            FROM order_item oi
            WHERE oi.order_id = {$session_order_id}
              AND oi.discount_type = 'Value'
            ";
        }
        else{
            $subSqlForValueSum = 0;
        }

        $SQL = "
        SELECT oi.*
              ,p.title AS product_title
              ,p.unit
              ,p.item_code
              ,p.batch_no
              ,p.hsn AS hsn_sac
              ,p.carton_no
              ,CONCAT_WS('::', p.carton_no, p.batch_no, p.model) code
              ,o.order_date
              ,o.discount
              ,o.order_id
              ,o.bill_number
              ,o.gst_status
              ,i.vat AS invoice_vat
              ,i.invoice_date
              ,DATE_FORMAT(o.creation_date, '%d-%m-%Y')AS invoice_creation_date
              ,i.invoice_code_vat
              ,i.invoice_code
              ,oi.qty * oi.unit_price AS amount
              ,(SELECT SUM(oit.qty * oit.unit_price) FROM order_item oit
               WHERE oit.order_id = oi.order_id) AS sub_total
              ,(SELECT
               ($subSqlForPercentSum)
                +
               ($subSqlForValueSum)) as discount_percentage_amount_sum
        FROM order_item oi
        LEFT JOIN product p ON (p.product_id = oi.record_id)
        LEFT JOIN `order` o ON (o.order_id = oi.order_id)
        LEFT JOIN invoice i ON (i.order_id = o.order_id)
        WHERE o.order_id = '{$session_order_id}'
        ORDER BY p.title
        ";
        $result = $db->sql_query($SQL);

        $sub_total = 0;
        $total_qty = 0;
        $discount = 0;
        $subTotal = 0;
        $discountValueTotal = 0;
        $Overallsubtotalwithoutdiscount = 0;

        while ($row = $db->sql_fetchrow($result)) {

            $discount_value_for_one_qty = '';
            $discountValue = 0;
            $discount_percentage = '';
            $discount_percentage_type =0;
            if($row['discount_percentage'] > 0 || $row['discount_amount'] > 0){
                if($row['discount_type'] == '%'){
                    $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                    $discount_percentage_type = $discountValue;
                    $discount_percentage = '';
                }
                else if($row['discount_type']  == 'Value'){
                    $discount_value_for_one_qty  =  $row['discount_amount'];
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                    $discount_percentage = $row['discount_amount'];
                    $discount_percentage_type = $row['discount_amount'];
                }
            }

            $subtotalwithoutdiscount = $row['qty'] * $row['unit_price'];
            $total = $row['qty'] * ($row['unit_price'] - $discount_value_for_one_qty);
            $total = $total - ($row['qty'] * $row['cost_price']);

            $subTotal += $total;
            $discount = $row['discount'];

        }

        $totalAmount = $subTotal - $discount;
        
        return $totalAmount;
    }

    /**
     *
     */
    function getShowClientHistory(){
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        $order_item_id = $fn->getReqParam('order_item_id');

        $SQL = "
        SELECT oi.*
              ,o.order_date
        FROM `order_item` oi
        LEFT JOIN `order` o ON (o.order_id = oi.order_id)
        WHERE oi.order_item_id = '{$order_item_id}'
        ";

        $result  = $db->sql_query($SQL);

        $rows = '';

        while ($row = $db->sql_fetchrow($result)) {
            $rows .= "
                <tr>
                    <td>{$fn->getCPDate($row['order_date'], 'd-m-Y')}</td>
                    <td>{$row['order_id']}</td>
                    <td>{$row['item_title']}</td>
                    <td>{$row['qty']}</td>
                    <td class='txtRight'>{$row['cost_price']}</td>
                    <td class='txtRight'>{$row['unit_price']}</td>
                </tr>
            ";
        }

        $text = "
        <table class='list thinlist'>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Order Code</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th class='txtRight'>Cost Price</th>
                    <th class='txtRight'>Selling Price</th>
                </tr>
            </thead>
            {$rows}
        </table>
        ";

        return $text;
    }
}