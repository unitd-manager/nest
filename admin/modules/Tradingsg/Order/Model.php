<?
class CPL_Admin_Modules_Tradingsg_Order_Model extends CP_Admin_Modules_Tradingsg_Order_Model
{

    function getSQL() {
        $cpCfg = Zend_Registry::get('cpCfg');

        $orgName = '';
        $orgTable = '';
        if ($cpCfg['m.ecommerce.order.showOrganization']) {
            $orgName = ",org.name AS organization_name";
            $orgTable = "LEFT JOIN organization org ON (o.organization_id = org.organization_id)";
        }

        $sumTxt = '';
        if ($cpCfg['m.ecommerce.order.hasDiscount']){
            $sumTxt = "SUM(oi.unit_price * oi.qty) + o.shipping_charge - o.discount";
        } else {
            $sumTxt = "SUM(oi.unit_price * oi.qty) + o.shipping_charge";
        }

        $SQL = "
        SELECT o.*
              ,gc1.name AS cust_country_name
              ,gc2.name AS shipping_country_name
              ,c.company_name AS companyName
              ,c.website AS company_website
              ,c.fax AS company_fax
              ,c.phone AS company_phone
              ,c.address_flat AS company_address_flat
              ,c.address_street AS company_address_street
              ,c.address_town AS company_address_town
              ,c.address_state AS company_address_state
              ,gc3.name AS company_country_name
              ,(SELECT ($sumTxt)
               FROM order_item oi
               WHERE oi.order_id = o.order_id
               ) AS order_amount
              ,q.quote_code
              {$orgName}
        FROM `order` o
        LEFT JOIN geo_country gc1 ON (o.cust_address_country_code = gc1.country_code)
        LEFT JOIN geo_country gc2 ON (o.shipping_address_country_code = gc2.country_code)
        LEFT JOIN company c ON (o.company_id = c.company_id)
        LEFT JOIN geo_country gc3 ON (c.address_country = gc3.country_code)
        LEFT JOIN quote q ON o.quote_id = q.quote_id
        {$orgTable}
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $searchVar = Zend_Registry::get('searchVar');
        $searchVar->mainTableAlias = 'o';

        $business_id         = $fn->getReqParam('business_id');
        $organization_id     = $fn->getReqParam('organization_id');
        $business_contact_id = $fn->getReqParam('business_contact_id');
        $creation_date1      = $fn->getReqParam('creation_date_1');
        $creation_date2      = $fn->getReqParam('creation_date_2');
        $order_status        = $fn->getReqParam('order_status');
        $shipment_status     = $fn->getReqParam('shipment_status');
        $order_type          = $fn->getReqParam('order_type');
        $ok_to_ship          = $fn->getReqParam('ok_to_ship');
        $shipping_address_country_code = $fn->getReqParam('shipping_address_country_code');
        $order_id            = $fn->getReqParam('order_id');
        $gst_status          = $fn->getReqParam('gst_status');

        if ($order_id != "") {
            $searchVar->sqlSearchVar[] = "o.order_id = '{$order_id}'";
        } else if ($tv['record_id'] != '') {
            $searchVar->sqlSearchVar[] = "o.order_id = '{$tv['record_id']}'";
        } else {

            if ($business_id != '') {
                $searchVar->sqlSearchVar[] = "o.business_id = '{$business_id}'";
            }

            if ($organization_id != '') {
                $searchVar->sqlSearchVar[] = "org.organization_id = '{$organization_id}'";
            }

            if ($business_contact_id != '') {
                $searchVar->sqlSearchVar[] = "o.business_contact_id = '{$business_contact_id}'";
            }

            if ($creation_date1 != "" && $creation_date2 != "" ) {
                $searchVar->sqlSearchVar[] = "(o.creation_date BETWEEN '{$creation_date1} 00:00:00' AND '{$creation_date2} 23:59:59')";
            }

            if ($order_status != '') {
                $searchVar->sqlSearchVar[] = "o.order_status = '{$order_status}'";
            }
            else {
                $searchVar->sqlSearchVar[] = "o.order_status != 'Cancelled'";
            }

            if ($shipment_status != '') {
                $searchVar->sqlSearchVar[] = "o.shipment_status = '{$shipment_status}'";
            }

            if ($order_type != '') {
                $searchVar->sqlSearchVar[] = "o.order_type = '{$order_type}'";
            }

            if ($shipping_address_country_code != '') {
                $searchVar->sqlSearchVar[] = "o.shipping_address_country_code = '{$shipping_address_country_code}'";
            }

            if ($ok_to_ship != '') {
                $searchVar->sqlSearchVar[] = "o.ok_to_ship = '{$ok_to_ship}'";
            }

            if ($tv['keyword'] != "") {
                $searchVar->sqlSearchVar[] = "(
                    o.cust_first_name       LIKE '%{$tv['keyword']}%'  OR
                    c.company_name          LIKE '%{$tv['keyword']}%'  OR
                    o.order_id              LIKE '%{$tv['keyword']}%'  OR
                    o.cust_last_name        LIKE '%{$tv['keyword']}%'  OR
                    o.order_code            LIKE '%{$tv['keyword']}%'  OR
                    o.memo                  LIKE '%{$tv['keyword']}%'  OR
                    o.shipping_first_name   LIKE '%{$tv['keyword']}%'  OR
                    o.shipping_last_name    LIKE '%{$tv['keyword']}%'
                )";
            }

            if($gst_status != ""){
                if($gst_status == 'GST'){
                    $searchVar->sqlSearchVar[] = "o.gst_status = 'ON'";
                }
                else{
                    $searchVar->sqlSearchVar[] = "o.gst_status = 'OFF'";
                }
            }

            $searchVar->sortOrder = "o.creation_date DESC";
        }
    }

   /**
     *
     */
    function getFields() {
        $fn = Zend_Registry::get('fn');

        $fa = array();

        $fa = $fn->addToFieldsArray($fa, 'order_date');
        $fa = $fn->addToFieldsArray($fa, 'order_status');
        $fa = $fn->addToFieldsArray($fa, 'invoice_terms');
        $fa = $fn->addToFieldsArray($fa, 'notes');
        $fa = $fn->addToFieldsArray($fa, 'organization_id');
        $fa = $fn->addToFieldsArray($fa, 'ok_to_ship');

        $fa = $fn->addToFieldsArray($fa, 'cust_first_name');
        $fa = $fn->addToFieldsArray($fa, 'cust_last_name');
        $fa = $fn->addToFieldsArray($fa, 'cust_email');
        $fa = $fn->addToFieldsArray($fa, 'cust_phone');
        $fa = $fn->addToFieldsArray($fa, 'cust_address1');
        $fa = $fn->addToFieldsArray($fa, 'cust_address2');
        $fa = $fn->addToFieldsArray($fa, 'cust_address_city');
        $fa = $fn->addToFieldsArray($fa, 'cust_address_area');
        $fa = $fn->addToFieldsArray($fa, 'cust_address_state');
        $fa = $fn->addToFieldsArray($fa, 'cust_po_code');
        $fa = $fn->addToFieldsArray($fa, 'cust_country_code');

        $fa = $fn->addToFieldsArray($fa, 'shipping_first_name');
        $fa = $fn->addToFieldsArray($fa, 'shipping_last_name');
        $fa = $fn->addToFieldsArray($fa, 'shipping_email');
        $fa = $fn->addToFieldsArray($fa, 'shipping_phone');
        $fa = $fn->addToFieldsArray($fa, 'shipping_address1');
        $fa = $fn->addToFieldsArray($fa, 'shipping_address2');
        $fa = $fn->addToFieldsArray($fa, 'shipping_address_area');
        $fa = $fn->addToFieldsArray($fa, 'shipping_address_city');
        $fa = $fn->addToFieldsArray($fa, 'shipping_address_state');
        $fa = $fn->addToFieldsArray($fa, 'shipping_address_po_code');
        $fa = $fn->addToFieldsArray($fa, 'shipping_address_country_code');
        $fa = $fn->addToFieldsArray($fa, 'shipment_status');
        $fa = $fn->addToFieldsArray($fa, 'shipping_address_country');
        $fa = $fn->addToFieldsArray($fa, 'delivery_date');
        $fa = $fn->addToFieldsArray($fa, 'loyalty_points');
        $fa = $fn->addToFieldsArray($fa, 'memo');
        $fa = $fn->addToFieldsArray($fa, 'cust_company_name');
        $fa = $fn->addToFieldsArray($fa, 'cust_address1');
        $fa = $fn->addToFieldsArray($fa, 'cust_address2');
        $fa = $fn->addToFieldsArray($fa, 'cust_address_city');
        $fa = $fn->addToFieldsArray($fa, 'cust_address_state');
        $fa = $fn->addToFieldsArray($fa, 'cust_address_country_code');
        $fa = $fn->addToFieldsArray($fa, 'igst_show');
        $fa = $fn->addToFieldsArray($fa, 'order_date');
        $fa = $fn->addToFieldsArray($fa, 'delivery_challan_no');
        $fa = $fn->addToFieldsArray($fa, 'cust_gst_no');
        $fa = $fn->addToFieldsArray($fa, 'shipping_gst_no');
        //$fa = $fn->addToFieldsArray($fa, 'delivery_to_text');

        return $fa;
    }    

    /**
     *
     */
    function getTradingsgOrderEcommerceOrderItemLinkSQL($id) {
        $fn = Zend_Registry::get('fn');

        $invoice_id = $fn->getReqParam('invoice_id');
        $extraFields = "";

        if ($invoice_id != "") {
            $whereSQL = " AND i.invoice_id = '{$invoice_id}'";
        } else {
            $whereSQL = "";
        }

        return "
        SELECT b.order_item_id
              ,b.item_title
              ,b.part_number
              ,b.unit_price
              ,b.qty
              ,b.qty * b.unit_price
        FROM `order_item` b
        LEFT JOIN (invoice i) ON (b.invoice_id = i.invoice_id)
        WHERE b.order_id = {$id}
              {$whereSQL}
        ";

    }

    /**
     *
     */
    function getCancelOrderNotesSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getCancelOrderNotesValidate()){
            return $validate->getErrorMessageXML();
        }

        $order_id = $fn->getReqParam('order_id');
        $notes    = $fn->getPostParam('notes');

        $fa = array();
        $fa['notes'] = $notes;

        $whereCondition = "WHERE order_id = {$order_id}";
        $SQL = $dbUtil->getUpdateSQLStringFromArray($fa, 'order', $whereCondition);
        $db->sql_query($SQL);

        $this->getCancelOrderRecord();

        return $validate->getSuccessMessageXML();
    }


    /**
     *
     */
    function getCancelOrderNotesValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $validate->resetErrorArray();
        $validate->validateData('notes', 'Please enter notes');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getCancelOrderRecord(){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpUtil = Zend_Registry::get('cpUtil');

        $order_id     = $fn->getReqParam('order_id');

        $SQLUpdate ="
        UPDATE `order` SET order_status = 'Cancelled'
        WHERE order_id = '{$order_id}'
        ";
        $resultUpdate = $db->sql_query($SQLUpdate);

        $SQL = "
        SELECT invoice_id
        FROM `invoice`
        WHERE order_id = '{$order_id}'
        ";
        $result  = $db->sql_query($SQL);
        $row     = $db->sql_fetchrow($result);
        $numRows = $db->sql_numrows($result);

        if($numRows > 0){
            $SQLInvoice = "
            UPDATE invoice SET status = 'Cancelled'
            WHERE order_id = '{$order_id}'
            ";
            $resultInvoice = $db->sql_query($SQLInvoice);

            $SQLReceipt = "
            UPDATE receipt SET receipt_status = 'Cancelled'
            WHERE order_id = '{$order_id}'
            ";
            $resultReceipt = $db->sql_query($SQLReceipt);
        }

    }

    /**
     *
     */
    function getCancelInvoice() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $invoice_code = $fn->getReqParam('invoice_code');
        $invoice_id   = $fn->getReqParam('invoice_id');

        /* Finding of receipt record */
        $SQLIrh = "
        SELECT irh.*
        FROM invoice_receipt_history irh
        LEFT JOIN (receipt r) ON (irh.receipt_id = r.receipt_id)
        WHERE irh.invoice_id = {$invoice_id}
          AND r.receipt_status =  'Paid'
        ";
        $resultIrh = $db->sql_query($SQLIrh);
        $numRowsIrh = $db->sql_numrows($resultIrh);
        $rowIrh = $db->sql_fetchrow($resultIrh);

        if ($numRowsIrh == 0) {
            /* Updating of invoice record */
            $sqlInv = "
            UPDATE invoice
            SET status = 'Cancelled'
            WHERE invoice_id = '{$invoice_id}'
            ";
            $resultInv = $db->sql_query($sqlInv);
        } else {
            return 'Cannot cancel';
        }

        $invoiceRec = $fn->getRecordRowByID('invoice', 'invoice_id', $invoice_id);

        $SQLOrder = "
        UPDATE `order` SET order_status = 'Due'
        WHERE order_id = {$invoiceRec['order_id']}
        ";
        $result = $db->sql_query($SQLOrder);
    }

    /**
     *
     */
    function getCancelReceipt() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $receipt_id = $fn->getReqParam('receipt_id');
        $order_id   = $fn->getReqParam('order_id');

        /* Updating of invoice record */
        //$row = $fn->getRecordRowByID('receipt', 'receipt_code', '$receipt_code');

        //to update the status of invoice to Due for related receipts.
        /*$sqlInv = "
        UPDATE invoice
        SET status = 'Due'
        WHERE invoice_id IN
        (SELECT invoice_id
         FROM invoice_receipt_history
         WHERE receipt_id = {$receipt_id}
         )
        ";
        $resultInv = $db->sql_query($sqlInv);*/

        $sqlRec = "
        UPDATE receipt
        SET receipt_status = 'Cancelled'
        WHERE receipt_id = {$receipt_id}
        ";
        $resultRec = $db->sql_query($sqlRec);

        $modObj       = getCPModuleObj('tradingsg_pos');
        $order_amount = $modObj->view->getTotalAmount($order_id);
        
        $subSqlForPercentSum = "
        SELECT SUM(invHist.amount) AS total_invoice_amount
        FROM invoice_receipt_history invHist
        LEFT JOIN (receipt rec) ON (invHist.receipt_id = rec.receipt_id)
        WHERE rec.order_id =  '{$order_id}' 
        AND (rec.receipt_status = 'Paid' OR rec.receipt_status = 'Partial Payment')
        ";
        $resultSubSql = $db->sql_query($subSqlForPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);

        $total_invoice_amount = $rowSql['total_invoice_amount'];
        if($total_invoice_amount != '' || $total_invoice_amount > 0){
            if ($order_amount <= $total_invoice_amount){
                $orderStatus  = 'Paid';
            } else if ($order_amount > $total_invoice_amount){
                $orderStatus  = 'Partial Payment';
            } else{
                $orderStatus  = 'Due';
            }
        } else {
                $orderStatus  = 'Due';            
        }

        //FOR AUTO UPDATING OF ORDER STATUS WHEN A RECEIPT IS PAID
        $SQLUpdate = "UPDATE `order` SET order_status = '{$orderStatus}' WHERE order_id = {$order_id}";
        $resultUpdate = $db->sql_query($SQLUpdate);

        $sqlInv = "
        UPDATE invoice
        SET status = '{$orderStatus}'
        WHERE invoice_id IN
        (SELECT invoice_id
         FROM invoice_receipt_history
         WHERE receipt_id = {$receipt_id}
         )
        ";
        $resultInv = $db->sql_query($sqlInv); 

    }

    /**
     *
     */
    function getPopulateReceiptAmount() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $invoice_code = $fn->getReqParam('invoice_code');
        $invoice_id = $fn->getReqParam('invoice_id');
        $order_id = $fn->getReqParam('order_id');
        $checkedVal = $fn->getReqParam('checkedVal');

        if($checkedVal == 1){
            $_SESSION['selectedInvoiceIds'][] = $invoice_code;
        }
        else if($checkedVal == 0){
            $s = &$_SESSION['selectedInvoiceIds'];
            if(($key = array_search($invoice_code, $s)) !== false){
                unset($s[$key]);
            }
        }

        if(count($_SESSION['selectedInvoiceIds']) == 0){
            return 0;
        }

        $selectInvoiceIds = join(',', $_SESSION['selectedInvoiceIds']);
        $sessionExplode = explode(',', $selectInvoiceIds);

        $counter = 1;
        $count = count($sessionExplode);

        $invoice_code = '';
        foreach ($sessionExplode as $invoiceCode) {
            if ($count == $counter) {
                $invoice_code .= "'" . $invoiceCode . "'";
            } else {
                $invoice_code .= "'" . $invoiceCode . "',";
            }
            $counter++;
        }

        $SQLPaid = "
        SELECT SUM(invoice_amount) AS invoice_selected_sum
        FROM invoice
        WHERE invoice_code IN ({$invoice_code})
        AND order_id = '{$order_id}'
        AND status != 'Cancelled'
        ";
        $resultPaid = $db->sql_query($SQLPaid);
        $rowPaid    = $db->sql_fetchrow($resultPaid);

        $SQLPartialPayment = "
        SELECT SUM(irh.amount) AS invoice_partial_payment
        FROM invoice_receipt_history irh
        LEFT JOIN (invoice i) ON (irh.invoice_id = i.invoice_id)
        LEFT JOIN receipt r ON (r.receipt_id = irh.receipt_id)
        WHERE i.invoice_code IN ({$invoice_code})
          AND i.order_id = '{$order_id}'
          AND r.receipt_status != 'Cancelled'
        ";
        $resultPartialPayment = $db->sql_query($SQLPartialPayment);
        $rowPartialPayment    = $db->sql_fetchrow($resultPartialPayment);

        $SQLSH = "
        SELECT SUM(sh.qty_return * sh.price) as sales_return_amount
        FROM sales_return_history sh
        LEFT JOIN (invoice i) ON (i.invoice_id = sh.invoice_id)
        WHERE i.invoice_code IN ({$invoice_code})
          AND i.order_id = '{$order_id}'
        ";
        $resultSH = $db->sql_query($SQLSH);
        $rowSH    = $db->sql_fetchrow($resultSH);

        if ($rowPartialPayment['invoice_partial_payment'] == 0){
            return $rowPaid['invoice_selected_sum'] - $rowSH['sales_return_amount'];
        } else {
            return $rowPaid['invoice_selected_sum'] - $rowPartialPayment['invoice_partial_payment'] - $rowSH['sales_return_amount'];
        }

    }

    /**
     *
     */
    function getGenerateSalesReturnFormSubmit() {
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        if (!$this->getGenerateSalesReturnFormValidate()){
            return $validate->getErrorMessageXML();
        }

        $invoiceRowItem       = $fn->getPostParam('invoiceRowItem', array());
        $invoiceItemIds       = $fn->getPostParam('invoiceItemId', array());
        $sales_return_amount  = $fn->getPostParam('sales_return_amount');
        $sales_return_date    = $fn->getPostParam('sales_return_date');
        $notes                = $fn->getPostParam('notes');
        $invoice_id           = $fn->getReqParam('invoice_id');
        $order_id             = $fn->getReqParam('order_id');
        $order_id             = $fn->getReqParam('order_id');

        $count = count($invoiceItemIds);
        $recCount = 0;
        foreach ($invoiceItemIds as $key=>$value){
            $invoiceItemRec = $fn->getRecordRowByID('invoice_item', 'invoice_item_id', $value);
            $pfx  = $value . '_' ;
            $qty_return  = $fn->getPostParam("{$pfx}qty_return");

            $sqlInvoiceItem = "
            SELECT ii.*
                  ,p.carton_no
                  ,o.record_type
                  ,o.order_id
            FROM invoice_item ii
            LEFT JOIN (product p) ON (p.product_id = ii.record_id)
            LEFT JOIN (`invoice` i) ON (i.invoice_id = ii.invoice_id)
            LEFT JOIN (`order` o) ON (o.order_id = i.order_id)
            WHERE ii.invoice_id = {$invoice_id}
            AND ii.invoice_item_id = {$value}
            ";
            $resultInvoiceItem = $db->sql_query($sqlInvoiceItem);
            $rowII = $db->sql_fetchrow($resultInvoiceItem);

            if($rowII['record_type'] == 'POS'){
                $discount_value_for_one_qty = '';
                $discountValue = 0;
                $discountPrice = 0;

                if($rowII['discount_type'] == '%'){
                    $discount_value_for_one_qty  =  $rowII['unit_price'] * ($rowII['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty;
                    $discountPrice = $rowII['unit_price'] - $discountValue;
                }
                else if($rowII['discount_type']  == 'Value'){
                    $discount_value_for_one_qty  =  $rowII['discount_percentage'];
                    $discountValue = $discount_value_for_one_qty;
                    $discountPrice = $rowII['unit_price'] - $discountValue;
                }
                $product_Price = $discountPrice;
                $product_Price = $rowII['unit_price'];
            }
            else{
                $product_Price = $rowII['unit_price'];
            }

            if ($invoice_id > 0){
                $fa = array();
                $fa['invoice_id']   = $invoice_id;
                $fa['order_id']   = $order_id;
                $fa['invoice_item_id'] = $invoiceItemRec['invoice_item_id'];
                $fa['qty_return']   = $qty_return;
                $fa['date']   = $sales_return_date;
                $fa['notes']   = $notes;
                $fa['price']   = $product_Price;

                $sales_return_history_id = $fn->addRecord($fa, 'sales_return_history');
                //print_r ($fa);
                $recCount++;

                if($rowII['record_type'] == 'POS'){
                    $fa = array();
                    $fa1['receipt_status']   = 'Cancelled';
                    $whereCondition = "WHERE order_id = {$rowII['order_id']}";
                    $sqlOiUpdate = $dbUtil->getUpdateSQLStringFromArray($fa1, "receipt", $whereCondition);
                    $resultOiUpdate      = $db->sql_query($sqlOiUpdate);

                    $fa2['order_status']   = 'Due';
                    $whereCondition = "WHERE order_id = {$rowII['order_id']}";
                    $sqlOiUpdate = $dbUtil->getUpdateSQLStringFromArray($fa2, "order", $whereCondition);
                    $resultOiUpdate      = $db->sql_query($sqlOiUpdate);
                }
            }
        }

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getGenerateSalesReturnFormValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $invoiceItemIds  = $fn->getPostParam('invoiceItemId', array());
        $order_id        = $fn->getPostParam('order_id');

        $rowOrderRec = $fn->getRecordRowByID('order', 'order_id', $order_id);

        $validate->resetErrorArray();
        //$validate->validateData('qty_return', 'Please enter the qty');

        if(!empty($invoiceItemIds)){
        } else {
            $msg = 'Please check the Products below before adding Sales Return Qty.';
            $validate->validateData('error_box', $msg);
        }
        $item_title = '';
        $total_sales_return_sum = '';

        foreach ($invoiceItemIds as $key=>$value){
            $invoiceItemRec = $fn->getRecordRowByID('invoice_item', 'invoice_item_id', $value);

            $sqlQty = "
            SELECT SUM(srh.qty_return) AS qty_return
            FROM sales_return_history srh
            WHERE srh.invoice_id = {$invoiceItemRec['invoice_id']}
             AND srh.invoice_item_id = {$invoiceItemRec['invoice_item_id']}
             AND srh.status IS NULL
            ";
            $resultQty = $db->sql_query($sqlQty);
            $rowQty = $db->sql_fetchrow($resultQty);

            $pfx  = $value . '_' ;
            $qty_return  = $fn->getPostParam("{$pfx}qty_return");
            $qty_balance = $invoiceItemRec['qty'] - $rowQty['qty_return'];

            if($qty_balance < $qty_return){
                $item_title .= $invoiceItemRec['item_title'] . ', ';
                $itemTitle = rtrim($item_title,', ');
                $msg = 'Please make sure the qty entered for '. $itemTitle .' should not be greater than the actual qty.';
                $validate->validateData('error_box', $msg);
            } else if($qty_return == 0 || $qty_return == ''){
                $msg = 'Please enter the qty';
                $validate->validateData('error_box', $msg);
            }
            //to check the total sum of sales return
             $total_sales_return_sum += $invoiceItemRec['unit_price'] *  $qty_return;
        }

        //validating previous sum code starts here
        $invRec    = $fn->getRecordRowByID('invoice', 'invoice_id', $invoiceItemRec['invoice_id']);
        $invAmount = $invRec['invoice_amount'];

        $SQLPaid = "
        SELECT SUM(invHist.amount) AS prev_sum
        FROM invoice_receipt_history invHist
        LEFT JOIN (receipt rec) ON (invHist.receipt_id = rec.receipt_id)
        WHERE invHist.invoice_id =  {$invoiceItemRec['invoice_id']} and rec.receipt_status = 'Paid'
        ";
        $resultPaid = $db->sql_query($SQLPaid);
        $rowPaid    = $db->sql_fetchrow($resultPaid);
        if($total_sales_return_sum > ($invAmount - $rowPaid['prev_sum'])){
            $msg = 'The overall amount for the selected quantity must be less than the Total Invoice Amount['. $invAmount .'] - Previous Receipt Amounts['.$rowPaid['prev_sum'].']' .'<br>If you want to proceed as such please cancel the earlier receipts.';
            if ($rowOrderRec['record_type'] != 'POS') {
                $validate->validateData('error_box', $msg);
            }
        }
        //validating previous sum code ends here

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getGenerateCreditFormSubmit() {
        $fn       = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $db       = Zend_Registry::get('db');
        $dbUtil   = Zend_Registry::get('dbUtil');
        $cpCfg    = Zend_Registry::get('cpCfg');

        if (!$this->getGenerateInvoiceFormValidate()){
            return $validate->getErrorMessageXML();
        }

        $orderRowItem       = $fn->getPostParam('orderRowItem', array());
        $orderItemIds       = $fn->getPostParam('orderItemId', array());
        $invoice_amount     = $fn->getPostParam('invoice_amount');
        $invoice_date       = $fn->getPostParam('invoice_date');
        $invoice_due_date   = $fn->getPostParam('invoice_due_date');
        $invoice_terms      = $fn->getPostParam('invoice_terms');
        $notes              = $fn->getPostParam('notes');
        $order_id           = $fn->getReqParam('order_id');
        //$qty_arr          = $fn->getReqParam('qty', array());
        $qty_balance        = $fn->getReqParam('qty_balance');
        $cst                = $fn->getReqParam('cst');
        $sgst               = $fn->getReqParam('sgst');
        $igst_cgst          = $fn->getReqParam('igst_cgst');
        $vat                = $fn->getReqParam('vat');
        $cust_po_no         = $fn->getReqParam('cust_po_no');
        //$frieght          = $fn->getReqParam('frieght');
        $frieght_cost       = $fn->getReqParam('frieght_cost');
        $pf                 = $fn->getReqParam('p_f');
        $vat_value          = $fn->getReqParam('vat_value');
        $cst_value          = $fn->getReqParam('cst_value');
        $sgst_value         = $fn->getReqParam('sgst_value');
        $igst_cgst_value    = $fn->getReqParam('igst_cgst_value');
        $site_id            = $fn->getSessionParam('cp_site_id');
        //print_r ($orderItemIds);
        //print_r ($qty_arr);
        //exit();

        $SQL = "SELECT credit_note_id FROM credit_note ORDER BY credit_note_id DESC LIMIT 0,1 ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        $invoiceRec = $fn->getRecordByCondition('credit_note', "credit_note_id = '{$row['credit_note_id']}'");

        if ($invoiceRec['status'] == 'Cancelled'){
            $invoice_code = $fn->getSettingsValueByKey("nextCreditCode");
        } else {
            $appendSQLSiteInv = "";
            $invoice_code = $fn->getSettingsValueByKey("nextCreditCode");
            if ($cpCfg['cp.hasMultiUniqueSites']){
                $appendSQLSiteInv = " AND site_id = '{$site_id}'";
            }

            $SQLUpdate = "
            UPDATE setting SET value = (value + 1)
            WHERE key_text = 'nextCreditCode'
            {$appendSQLSiteInv}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        //$invoice_code = $fn->getSettingsValueByKey("nextInvoiceCode");

        if($invoice_code < 10){
            $invoice_code = '00' . $invoice_code;
        }
        else if($invoice_code < 99){
            $invoice_code = '0' . $invoice_code;
        }
        else{
            $invoice_code = $invoice_code;
        }

        $currentYear = date("y");
        $nextYear = $currentYear + 1;



        $fa = array();
        $fa['invoice_code']     = 'CRE/'.$invoice_code.'/'. $currentYear .' - ' .  $nextYear;
        $fa['invoice_amount']   = $invoice_amount;
        $fa['invoice_date']     = $invoice_date;
        $fa['invoice_due_date'] = $invoice_due_date;
        $fa['invoice_terms']    = $invoice_terms;
        $fa['notes']            = $notes;
        $fa['order_id']         = $order_id;
        $fa['status']           = 'Due';
        $fa['staff_id']         = $_SESSION['staff_id'];
        $fa['creation_date']    = date("Y-m-d H:i:s");
        $fa['created_by']       = $fn->getSessionParam('userName');
        $fa['invoice_type']     = 'Client';
        $fa['cust_po_no']       = $cust_po_no;
        $fa['cst']              = $cst;
        /*$fa['sgst']             = $sgst;
        $fa['igst_cgst']        = $igst_cgst;*/
        $fa['vat']              = $vat;
        //$fa['frieght']        = $frieght;
        $fa['frieght_cost']     = $frieght_cost;
        $fa['p_f']              = $pf;

        if($vat == 1){
            $fa['vat_value'] = $vat_value;
        }

        if($cst == 1){
            $fa['cst_value'] = $cst_value;
        }

        if($sgst == 1){
            $fa['sgst_value'] = $sgst_value;
        }

        if($igst_cgst != ''){
            $fa['igst_cgst_value'] = $igst_cgst_value;
        }

        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $fa['site_id'] = $site_id;
        }

        $insertInvoiceSQL   = $dbUtil->getInsertSQLStringFromArray($fa, 'credit_note');
        $resultSQL          = $db->sql_query($insertInvoiceSQL);
        $credit_note_id     = $db->sql_nextid();

        $count = count($orderItemIds);
        $recCount = 0;
        foreach ($orderItemIds as $key=> $value){
            $orderItemRec = $fn->getRecordRowByID('order_item', 'order_item_id', $value);
            $pfx  = $value . '_' ;
            $qty  = $fn->getPostParam("{$pfx}qty");
            $unit_price  = $fn->getPostParam("{$pfx}unit_price");


            if ($credit_note_id > 0){
                $fa = array();
                $fa['credit_note_id']   = $credit_note_id;
                $fa['record_id']    = $orderItemRec['record_id'];
                $fa['qty']          = $qty;
                $fa['unit_price']   = $unit_price;
                $fa['cost_price']   = $orderItemRec['cost_price'];
                $fa['item_title']   = $orderItemRec['item_title'];
                $fa['module']       = $orderItemRec['module'];
                $fa['supplier_id']  = $orderItemRec['supplier_id'];
                $fa['part_number']  = $orderItemRec['part_number'];
                $fa['order_item_id']= $value;
                $fa['discount_percentage']  = $orderItemRec['discount_percentage'];

                $credit_note_item_id = $fn->addRecord($fa, 'credit_note_item');
                //print_r ($fa);
                $recCount++;
            }
        }

        $sql ="
        SELECT SUM(it.qty * it.unit_price) As amount
        FROM credit_note_item it
        WHERE it.credit_note_id = {$credit_note_id}
        ";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);

        $pfVal = 0;
        if($pf != ''){
            $pfVal = $row['amount'] * $pf / 100;
        }

        $frieghtCost = 0;
        if($frieght_cost != ''){
            $frieghtCost = $frieght_cost;
        }

        if($cst == 0 && $vat == 1){
            $gstvalue = ($row['amount'] + $pfVal + $frieghtCost) * $vat_value / 100;
            $amount = $gstvalue + $row['amount'];
        } else if($cst == 1 && $vat == 0){
            $gstvalue = ($row['amount'] + $pfVal + $frieghtCost) * $cst_value / 100;
            $amount = $gstvalue + $row['amount'];
        } else if($sgst == 1){
            $gstvalue = ($row['amount'] + $pfVal + $frieghtCost) * $sgst_value / 100;
            $amount = $gstvalue + $row['amount'];
        } else {
            $amount = $row['amount'];
        }

        if($igst_cgst != ''){
            $icgstvalue = ($row['amount'] + $pfVal + $frieghtCost) * $igst_cgst_value / 100;
            $amount = $icgstvalue + $amount;
        }

        //$frieghtVal = 0;
        /*if($frieght != ''){
            $frieghtVal = $row['amount'] * $frieght / 100;
        }*/

        //$amount = $amount + $frieghtVal + $pfVal;
        $amount = $amount + $frieghtCost + $pfVal;
        $amount = round($amount);

        $fa2 = array();
        $fa2['invoice_amount']  = $amount;

        $whereCondition = "
        WHERE credit_note_id = {$credit_note_id}
        ";
        $SQLInvoice = $dbUtil->getUpdateSQLStringFromArray($fa2, 'credit_note', $whereCondition);
        $db->sql_query($SQLInvoice);

        //$this->getGenerateInvoiceForMedia($invoice_id);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getGenerateInvoiceFormValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $qty = $fn->getReqParam('qty');
        $qty_balance = $fn->getReqParam('qty_balance');

        $validate->resetErrorArray();
        //$validate->validateData('qty', 'Please enter the qty');

        /*if($qty_balance < $qty){
            $validate->errorArray['qty']['name'] = "qty";
            $validate->errorArray['qty']['msg']  = 'Please enter less qty';
        }*/

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

     /**
     *
     */
    function getCancelCreditNote() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $invoice_code = $fn->getReqParam('invoice_code');

            $sqlInv = "
            UPDATE credit_note
            SET status = 'Cancelled'
            WHERE invoice_code = '{$invoice_code}'
            ";
            $resultInv = $db->sql_query($sqlInv);

    }
    
    /**
     *
     */
    function getGenerateDebitFormSubmit() {
        $fn       = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $db       = Zend_Registry::get('db');
        $dbUtil   = Zend_Registry::get('dbUtil');
        $cpCfg    = Zend_Registry::get('cpCfg');

        if (!$this->getGenerateDebitFormValidate()){
            return $validate->getErrorMessageXML();
        }

        $orderRowItem       = $fn->getPostParam('orderRowItem', array());
        $orderItemIds       = $fn->getPostParam('orderItemId', array());
        $invoice_amount     = $fn->getPostParam('invoice_amount');
        $invoice_date       = $fn->getPostParam('invoice_date');
        $invoice_due_date   = $fn->getPostParam('invoice_due_date');
        $invoice_terms      = $fn->getPostParam('invoice_terms');
        $notes              = $fn->getPostParam('notes');
        $order_id           = $fn->getReqParam('order_id');
        //$qty_arr          = $fn->getReqParam('qty', array());
        $qty_balance        = $fn->getReqParam('qty_balance');
        $cst                = $fn->getReqParam('cst');
        $sgst               = $fn->getReqParam('sgst');
        $igst_cgst          = $fn->getReqParam('igst_cgst');
        $vat                = $fn->getReqParam('vat');
        $cust_po_no         = $fn->getReqParam('cust_po_no');
        //$frieght          = $fn->getReqParam('frieght');
        $frieght_cost       = $fn->getReqParam('frieght_cost');
        $pf                 = $fn->getReqParam('p_f');
        $vat_value          = $fn->getReqParam('vat_value');
        $cst_value          = $fn->getReqParam('cst_value');
        $sgst_value         = $fn->getReqParam('sgst_value');
        $igst_cgst_value    = $fn->getReqParam('igst_cgst_value');
        $site_id            = $fn->getSessionParam('cp_site_id');
        //print_r ($orderItemIds);
        //print_r ($qty_arr);
        //exit();

        $SQL = "SELECT debit_note_id FROM debit_note ORDER BY debit_note_id DESC LIMIT 0,1 ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        $invoiceRec = $fn->getRecordByCondition('debit_note', "debit_note_id = '{$row['debit_note_id']}'");

        if ($invoiceRec['status'] == 'Cancelled'){
            $invoice_code = $fn->getSettingsValueByKey("nextDebitCode");
        } else {
            $appendSQLSiteInv = "";
            $invoice_code = $fn->getSettingsValueByKey("nextDebitCode");
            if ($cpCfg['cp.hasMultiUniqueSites']){
                $appendSQLSiteInv = " AND site_id = '{$site_id}'";
            }

            $SQLUpdate = "
            UPDATE setting SET value = (value + 1)
            WHERE key_text = 'nextDebitCode'
            {$appendSQLSiteInv}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        //$invoice_code = $fn->getSettingsValueByKey("nextInvoiceCode");

        if($invoice_code < 10){
            $invoice_code = '00' . $invoice_code;
        }
        else if($invoice_code < 99){
            $invoice_code = '0' . $invoice_code;
        }
        else{
            $invoice_code = $invoice_code;
        }

        $currentYear = date("y");
        $nextYear = $currentYear + 1;

        $fa = array();
        $fa['invoice_code']     = 'DBN/'.$invoice_code.'/'. $currentYear .' - ' .  $nextYear;
        $fa['invoice_amount']   = $invoice_amount;
        $fa['invoice_date']     = $invoice_date;
        $fa['invoice_due_date'] = $invoice_due_date;
        $fa['invoice_terms']    = $invoice_terms;
        $fa['notes']            = $notes;
        $fa['order_id']         = $order_id;
        $fa['status']           = 'Due';
        $fa['staff_id']         = $_SESSION['staff_id'];
        $fa['creation_date']    = date("Y-m-d H:i:s");
        $fa['created_by']       = $fn->getSessionParam('userName');
        $fa['invoice_type']     = 'Client';
        $fa['cust_po_no']       = $cust_po_no;
        $fa['cst']              = $cst;
        /*$fa['sgst']             = $sgst;
        $fa['igst_cgst']        = $igst_cgst;*/
        $fa['vat']              = $vat;
        //$fa['frieght']        = $frieght;
        $fa['frieght_cost']     = $frieght_cost;
        $fa['p_f']              = $pf;

        if($vat == 1){
            $fa['vat_value'] = $vat_value;
        }

        if($cst == 1){
            $fa['cst_value'] = $cst_value;
        }

        if($sgst == 1){
            $fa['sgst_value'] = $sgst_value;
        }

        if($igst_cgst != ''){
            $fa['igst_cgst_value'] = $igst_cgst_value;
        }

        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $fa['site_id'] = $site_id;
        }

        $insertInvoiceSQL   = $dbUtil->getInsertSQLStringFromArray($fa, 'debit_note');
        $resultSQL          = $db->sql_query($insertInvoiceSQL);
        $debit_note_id         = $db->sql_nextid();

        $count = count($orderItemIds);
        $recCount = 0;
        foreach ($orderItemIds as $key=> $value){
            $orderItemRec = $fn->getRecordRowByID('order_item', 'order_item_id', $value);
            $pfx  = $value . '_' ;
            $qty  = $fn->getPostParam("{$pfx}qty");
            $unit_price  = $fn->getPostParam("{$pfx}unit_price");


            if ($debit_note_id > 0){
                $fa = array();
                $fa['debit_note_id']   = $debit_note_id;
                $fa['record_id']    = $orderItemRec['record_id'];
                $fa['qty']          = $qty;
                $fa['unit_price']   = $unit_price;
                $fa['cost_price']   = $orderItemRec['cost_price'];
                $fa['item_title']   = $orderItemRec['item_title'];
                $fa['module']       = $orderItemRec['module'];
                $fa['supplier_id']  = $orderItemRec['supplier_id'];
                $fa['part_number']  = $orderItemRec['part_number'];
                $fa['order_item_id']= $value;
                $fa['discount_percentage']  = $orderItemRec['discount_percentage'];

                $debit_note_item_id = $fn->addRecord($fa, 'debit_note_item');
                //print_r ($fa);
                $recCount++;
            }
        }

        $sql ="
        SELECT SUM(it.qty * it.unit_price) As amount
        FROM debit_note_item it
        WHERE it.debit_note_id = {$debit_note_id}
        ";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);

        $pfVal = 0;
        if($pf != ''){
            $pfVal = $row['amount'] * $pf / 100;
        }

        $frieghtCost = 0;
        if($frieght_cost != ''){
            $frieghtCost = $frieght_cost;
        }

        if($cst == 0 && $vat == 1){
            $gstvalue = ($row['amount'] + $pfVal + $frieghtCost) * $vat_value / 100;
            $amount = $gstvalue + $row['amount'];
        } else if($cst == 1 && $vat == 0){
            $gstvalue = ($row['amount'] + $pfVal + $frieghtCost) * $cst_value / 100;
            $amount = $gstvalue + $row['amount'];
        } else if($sgst == 1){
            $gstvalue = ($row['amount'] + $pfVal + $frieghtCost) * $sgst_value / 100;
            $amount = $gstvalue + $row['amount'];
        } else {
            $amount = $row['amount'];
        }

        if($igst_cgst != ''){
            $icgstvalue = ($row['amount'] + $pfVal + $frieghtCost) * $igst_cgst_value / 100;
            $amount = $icgstvalue + $amount;
        }

        //$frieghtVal = 0;
        /*if($frieght != ''){
            $frieghtVal = $row['amount'] * $frieght / 100;
        }*/

        //$amount = $amount + $frieghtVal + $pfVal;
        $amount = $amount + $frieghtCost + $pfVal;
        $amount = round($amount);

        $fa2 = array();
        $fa2['invoice_amount']  = $amount;

        $whereCondition = "
        WHERE debit_note_id = {$debit_note_id}
        ";
        $SQLInvoice = $dbUtil->getUpdateSQLStringFromArray($fa2, 'debit_note', $whereCondition);
        $db->sql_query($SQLInvoice);

        //$this->getGenerateInvoiceForMedia($invoice_id);

        return $validate->getSuccessMessageXML();
    }

     /**
     *
     */
    function getGenerateDebitFormValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $qty = $fn->getReqParam('qty');
        $qty_balance = $fn->getReqParam('qty_balance');

        $validate->resetErrorArray();
        //$validate->validateData('qty', 'Please enter the qty');

        /*if($qty_balance < $qty){
            $validate->errorArray['qty']['name'] = "qty";
            $validate->errorArray['qty']['msg']  = 'Please enter less qty';
        }*/

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

     /**
     *
     */
    function getCancelDebitNote() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $invoice_code = $fn->getReqParam('invoice_code');

            $sqlInv = "
            UPDATE debit_note
            SET status = 'Cancelled'
            WHERE invoice_code = '{$invoice_code}'
            ";
            $resultInv = $db->sql_query($sqlInv);
    }
}
