<?
ini_set('max_execution_time', 300);
class CPL_Admin_Modules_Tradingsg_Order_View extends CP_Admin_Modules_Tradingsg_Order_View
{
    /**
     *
     */
    function getList($dataArray){
        $listObj = Zend_Registry::get('listObj');
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        $rows  = "";
        $rowCounter = 0;

        //--------------------------------------------------------------------------//
        foreach ($dataArray as $row){
            $creation_date = $fn->getCPDate($row['creation_date'], 'd-m-Y');
            $currency = strtoupper($row['currency']);

            $modObj = getCPModuleObj('tradingsg_pos');
            $order_amount = $modObj->view->getTotalAmount($row['order_id']);

            $printInvoice = "";
            $printBill = "";
            if($row['order_status'] == "Paid" || $row['order_status'] == "Due" || $row['order_status'] == "Partial Payment"){
                $urlPrintInvoice = "index.php?_topRm=finance&module=tradingsg_order&_spAction=printInvoiceRecord&order_id={$row['order_id']}&showHTML=0";
                $printInvoice = "
                <a href='{$urlPrintInvoice}' target='_blank'>
                    Print Invoice
                </a>
                ";

                $urlPrintBill = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=printInvoiceRecord&orderNo={$row['order_id']}&showHTML=0";
                $printBill = "
                <a href='{$urlPrintBill}' target='_blank'>
                    Print Bill
                </a>
                ";
            }
            $invoiceRec = $fn->getRecordByCondition('invoice', "order_id = '{$row['order_id']}'");
            $amount_paid = 0;
            $amount_due = 0;

            if($invoiceRec['invoice_id'] > 0) {
                $SQLRec="
                SELECT SUM(invHist.amount) AS amount_paid
                FROM invoice_receipt_history invHist
                LEFT JOIN receipt r ON (r.receipt_id = invHist.receipt_id)
                WHERE invHist.invoice_id =  {$invoiceRec['invoice_id']}
                AND r.receipt_status != 'Cancelled'
                ";
                $resultRec = $db->sql_query($SQLRec);
                $rowRec    = $db->sql_fetchrow($resultRec);
                $amount_paid = $rowRec['amount_paid'];
                $amount_due = $order_amount - $amount_paid;
            }

            if($row['order_id'] < 10){
                $orderNo = '0000' . $row['order_id'];
            }
            else if($row['order_id'] <= 99){
                $orderNo = '000' . $row['order_id'];
            }
            else if($row['order_id'] <= 999){
                $orderNo = '00' . $row['order_id'];
            }
            else if($row['order_id'] <= 9999){
                $orderNo = '0' . $row['order_id'];
            }
            else{
                $orderNo = $row['order_id'];
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

            $rests="";
            if ($cpCfg['cp.restaurent'] == 1){
              
                $rests =$row['table_value'];
            }

            $rows .= "
            {$listObj->getListRowHeader($row, $rowCounter)}
            {$listObj->getGoToDetailText($rowCounter, $orderNo)}
            {$listObj->getListDataCell($row['bill_number'])}
            {$listObj->getListDataCell($row['companyName'])}
            {$listObj->getListDataCell($rests)}
            {$listObj->getListDataCell($creation_date)}
            {$listObj->getListDataCell($row['record_type'])}
            {$listObj->getListDataCell($currency.'&nbsp;'.number_format(round($order_amount), 2), 'right')}
            {$listObj->getListDataCell(number_format(round($amount_paid), 2), 'right')}
            {$listObj->getListDataCell(number_format(round($amount_due), 2), 'right')}
            {$listObj->getListDataCell($row['order_status'])}
            {$listObj->getListDataCell($row['delivery_date'])}
            {$listObj->getListDataCell($printBill)}
            {$listObj->getListDataCell($printInvoice)}
            {$listObj->getListRowEnd($row['order_id'])}
            ";

            $rowCounter++;
        }
        $restss="";
        if ($cpCfg['cp.restaurent'] == 1){
          
            $restss =$listObj->getListHeaderCell('Table Name', 'o.table_value');
        }


        $text = "
        {$listObj->getListHeader()}
        {$listObj->getListHeaderCell('Order Id', 'o.order_id')}
        {$listObj->getListHeaderCell('POS Bill No', 'o.bill_number')}
        {$listObj->getListHeaderCell('Company Name', 'c.companyName')}
        {$restss}
        {$listObj->getListHeaderCell('Order Date', 'o.creation_date')}
        {$listObj->getListHeaderCell('Order Type', 'o.record_type')}
        {$listObj->getListHeaderCell('Amount', '', 'txtRight')}
        {$listObj->getListHeaderCell('Amount Paid', '', 'txtRight')}
        {$listObj->getListHeaderCell('Amount Due', '', 'txtRight')}
        {$listObj->getListHeaderCell('Status', 'o.order_status')}
        {$listObj->getListHeaderCell('Delivery Date', 'o.delivery_date')}
        {$listObj->getListHeaderCell('Print Bill', '')}
        {$listObj->getListHeaderCell('Print To PDF', '')}
        {$listObj->getListHeaderEnd()}
        {$rows}
        {$listObj->getListFooter()}
        ";

        return $text;
    }

    /**
     *
     */
    function getEdit($row) {
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $formObj = Zend_Registry::get('formObj');
        $dateUtil = Zend_Registry::get('dateUtil');
        $ln = Zend_Registry::get('ln');

        $formObj->mode = $tv['action'];

        $expStatus = array('sqlType' => 'OneField', 'isEditable' => 0);
        $expNoEdit = array('isEditable' => 0);

        $sqlCountry = getCPModelObj('common_geoCountry')->getCountryDDSQL();
        $expCountry = array('detailValue' => $row['shipping_address_country']);

        $creation_date = $dateUtil->formatDate($row['creation_date'], 'DD MM YYYY');

        $currency = strtoupper($row['currency']);

        $modObj = getCPModuleObj('tradingsg_pos');
        $order_amount = $modObj->view->getTotalAmount($row['order_id']);

        $discount = '';
        if ($cpCfg['m.ecommerce.order.hasDiscount']){
            $discount = $formObj->getTBRow('Discount', 'discount', $row['discount'], $expNoEdit);
        }

        $quote = "<a href='index.php?_topRm=order&module=tradingsg_quote&record_id={$row['quote_id']}&_action=edit'>{$row['quote_code']}</a>";

        if($row['order_id'] < 10){
            $orderNo = '0000' . $row['order_id'];
        }
        else if($row['order_id'] <= 99){
            $orderNo = '000' . $row['order_id'];
        }
        else if($row['order_id'] <= 999){
            $orderNo = '00' . $row['order_id'];
        }
        else if($row['order_id'] <= 9999){
            $orderNo = '0' . $row['order_id'];
        }
        else{
            $orderNo = $row['order_id'];
        }

        $loyalty_points = '';
        if($order_amount >= 1000){
            $loyalty_points = ($order_amount / 1000) * 10;
        }
        $loyalty_points = round($loyalty_points);

        $fielset1 = "
        {$formObj->getTBRow('Order Id', 'order_id', $orderNo, $expNoEdit)}
        {$formObj->getTBRow('PO No', 'memo', $row['memo'])}
        {$formObj->getDateRow('Order Date', 'order_date', $row['order_date'])}
        {$formObj->getTBRow('Amount', 'amount', $currency.'&nbsp;'. number_format($order_amount, 2), $expNoEdit)}
        {$discount}
        {$formObj->getDDRowByArr('Status', 'order_status', $cpCfg['m.ecommerce.order.statusArr'], $row['order_status'], $expStatus)}
        {$formObj->getTBRow('DC No', 'delivery_challan_no', $row['delivery_challan_no'])}
        {$formObj->getDateRow('Delivery Date', 'delivery_date', $row['delivery_date'])}
        {$formObj->getTARow('Terms', 'invoice_terms', $row['invoice_terms'])}
        {$formObj->getTARow('Notes', 'notes', $row['notes'])}
        {$formObj->getTBRow('Loyalty Points', 'loyalty_points', $loyalty_points)}
        {$formObj->getYesNoRRow('IGST', 'igst_show', $row['igst_show'])}
        ";

        //{$formObj->getTBRow('Country', 'company_country_name', $row['company_country_name'], $expNoEdit)}
        $fielset2 = "
        {$formObj->getTBRow('Company Name', 'shipping_first_name', $row['shipping_first_name'])}
        {$formObj->getTBRow('Address 1', 'shipping_address1', $row['shipping_address1'])}
        {$formObj->getTBRow('Address 2', 'shipping_address2', $row['shipping_address2'])}
        {$formObj->getTBRow('District/ Town', 'shipping_address_city', $row['shipping_address_city'])}
        {$formObj->getTBRow('State/ Zip', 'shipping_address_state', $row['shipping_address_state'])}
        {$formObj->getDDRowBySQL('Country', 'shipping_address_country', $sqlCountry, $row['shipping_address_country_code'], $expCountry)}
        {$formObj->getTBRow('Email Id', 'shipping_email', $row['shipping_email'])}
        {$formObj->getTBRow('Phone No', 'shipping_phone', $row['shipping_phone'])}
        {$formObj->getTBRow('GST No', 'shipping_gst_no', $row['shipping_gst_no'])}
        ";

        $fielset3 = "
        {$formObj->getTBRow('Company Name', 'cust_company_name', $row['cust_company_name'])}
        {$formObj->getTBRow('Address 1', 'cust_address1', $row['cust_address1'])}
        {$formObj->getTBRow('Address 2', 'cust_address2', $row['cust_address2'])}
        {$formObj->getTBRow('District/ Town', 'cust_address_city', $row['cust_address_city'])}
        {$formObj->getTBRow('State/ Zip', 'cust_address_state', $row['cust_address_state'])}
        {$formObj->getDDRowBySQL('Country', 'cust_address_country_code', $sqlCountry, $row['cust_address_country_code'], $expCountry)}
        {$formObj->getTBRow('Email Id', 'cust_email', $row['cust_email'])}
        {$formObj->getTBRow('Phone No', 'cust_phone', $row['cust_phone'])}
        {$formObj->getTBRow('GST No', 'cust_gst_no', $row['cust_gst_no'])}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Main Details', $fielset1)}
        {$formObj->getFieldSetWrapped('Delivery Address', $fielset2)}
        {$formObj->getFieldSetWrapped('Customer Details', $fielset3)}
        {$formObj->getCreationModificationText($row)}
        ";

        return $text;
    }

    /**
     *
     */
    function getPrintInvoiceRecordComputerSystems($order_id) {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot2.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

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

        //$pdf->setPrintFooter(false);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
        $pdf->AddPage();

        //$order_id = $fn->getReqParam('order_id');

        $subSqlForPercentSum = "
        SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2)) as discount_sum
        FROM order_item oi
        WHERE oi.order_id = {$order_id}
          AND oi.discount_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForPercentSum = "
            SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2))
            FROM order_item oi
            WHERE oi.order_id = {$order_id}
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
        WHERE oi.order_id = {$order_id}
          AND oi.discount_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlForValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForValueSum ="
            SELECT SUM(round(oi.discount_amount  * oi.qty,2))
            FROM order_item oi
            WHERE oi.order_id = {$order_id}
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
              ,o.shipping_charge
              ,i.vat AS invoice_vat
              ,i.invoice_date
              ,DATE_FORMAT(i.invoice_date, '%d-%m-%Y')AS invoice_creation_date
              ,i.invoice_code_vat
              ,i.invoice_code
              ,i.invoice_id
              ,o.cust_company_name
              ,o.company_id
              ,o.cust_phone
              ,o.cust_email
              ,o.cust_address1
              ,o.cust_address2
              ,o.cust_address_city
              ,o.cust_address_state
              ,o.cust_address_country_code
              ,o.cust_gst_no
              ,o.shipping_first_name
              ,o.shipping_phone
              ,o.shipping_email
              ,o.shipping_address1
              ,o.shipping_address2
              ,o.shipping_address_city
              ,o.shipping_address_state
              ,o.shipping_address_country_code
              ,o.shipping_gst_no
              ,o.invoice_terms
              ,o.memo
              ,o.igst_show
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
        WHERE o.order_id = '{$order_id}'
        ORDER BY p.title
        ";
        $result = $db->sql_query($SQL);
        $result2 = $db->sql_query($SQL);
        $company = $db->sql_fetchrow($result2);
        //============================================================================= //

        $pdf->SetFont('helvetica','', 8);

        $today = date("d-m-Y");

        if($company['order_id'] < 10){
            $orderNo = '0000' . $company['order_id'];
        }
        else if($company['order_id'] <= 99){
            $orderNo = '000' . $company['order_id'];
        }
        else if($company['order_id'] <= 999){
            $orderNo = '00' . $company['order_id'];
        }
        else if($company['order_id'] <= 9999){
            $orderNo = '0' . $company['order_id'];
        }
        else{
            $orderNo = $company['order_id'];
        }

        //$invoice_code = substr($company['invoice_code'], 2);

        if($company['invoice_code'] == ""){
            $invoice_code = 'INV - '.$company['invoice_id']; 
        }
        else{
            $invoice_code = $company['invoice_code'];
        }

        $tbl1 ='
        <table border="0" width="100%" cellpadding="3">
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:12px;"><span style="font-weight:bold;">Invoice No &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : </span>'.$invoice_code.'</td>
                <td style="border-right:1px solid #000000;border-top:1px solid #000000;font-size:12px;"><span style="font-weight:bold;">Po Code : </span>'.$company['memo'].'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;border-bottom:1px solid #000000;font-size:12px;"><span style="font-weight:bold;">Date of Invoice : </span>'.$company['invoice_creation_date'].'</td>
                <td style="border-right:1px solid #000000;border-bottom:1px solid #000000;font-size:12px;"></td>
            </tr>
        </table>
        ';

        $tbl2 ='
        <table border="0" width="100%" cellpadding="3">
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-siz11;font-weight:bold;"><i>Billed to :</i></td>
                <td style="border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Shipped to :</i></td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$company['cust_company_name'].'</td>
                <td style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$company['shipping_first_name'].'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$company['cust_address1'].'</td>
                <td style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$company['shipping_address1'].'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.$company['cust_address2'].''.$company['cust_address_city'].' '.$company['cust_address_state'].'</td>
                <td style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$company['shipping_address2'].''.$company['shipping_address_city'].' '.$company['shipping_address_state'].'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">Party Email Id : '.$company['cust_email'].'</td>
                <td style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">Party Email Id : '.$company['shipping_email'].'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">Party Mobile No : '.$company['cust_phone'].'</td>
                <td style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">Party Mobile No : '.$company['shipping_phone'].'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;font-size:11px;">GST IN / UIN :'.$company['cust_gst_no'].'</td>
                <td style="border-right:1px solid #000000;border-bottom:1px solid #000000;font-size:11px;font-weight:bold;">GST IN / UIN :'.$company['shipping_gst_no'].'</td>
            </tr>
        </table>
        ';

        if($company['gst_status'] == "ON"){

            if($company['igst_show'] == "1"){
                $tbl3 ='<table border="0" width="100%" cellpadding="3">
                            <thead>
                                <tr>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">REF NO</th>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">S.NO</th>
                                    <th width="31%" style="border:1px solid #000000;font-weight:bold;" align="left">PRODUCTS</th>
                                    <th width="10%" style="border:1px solid #000000;font-weight:bold;" align="center">HSN/SAC CODE</th>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">QTY</th>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">UNIT</th>
                                    <th width="9%"  style="border:1px solid #000000;font-weight:bold;" align="right">PRICE</th>
                                    <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="right">IGST Rate</th>
                                    <th width="10%" style="border:1px solid #000000;font-weight:bold;" align="center">IGST AMOUNT</th>
                                    <th width="12%" style="border:1px solid #000000;font-weight:bold;" align="right">AMOUNT</th>
                                </tr>
                            </thead>
                ';
            }else{
                $tbl3 ='<table border="0" width="100%" cellpadding="3">
                            <thead>
                                <tr>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">REF NO</th>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">S.NO</th>
                                    <th width="19%" style="border:1px solid #000000;font-weight:bold;" align="left">PRODUCTS</th>
                                    <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="center">HSN/SAC CODE</th>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">QTY</th>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">UNIT</th>
                                    <th width="9%"  style="border:1px solid #000000;font-weight:bold;" align="right">PRICE</th>
                                    <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="right">CGST Rate</th>
                                    <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="center">CGST AMOUNT</th>
                                    <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="right">SGST Rate</th>
                                    <th width="10%" style="border:1px solid #000000;font-weight:bold;" align="right">SGST AMOUNT</th>
                                    <th width="10%" style="border:1px solid #000000;font-weight:bold;" align="right">AMOUNT</th>
                                </tr>
                            </thead>
                ';
            }
        }
        //5
        else{
            $tbl3 ='<table border="0" width="100%" cellpadding="3">
                        <thead>
                            <tr>
                                <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">REF NO</th>
                                <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">S.NO</th>
                                <th width="31%" style="border:1px solid #000000;font-weight:bold;" align="left">PRODUCTS</th>
                                <th width="10%" style="border:1px solid #000000;font-weight:bold;" align="center">HSN/SAC CODE</th>
                                <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="center">QTY</th>
                                <th width="13%" style="border:1px solid #000000;font-weight:bold;" align="right">PRICE</th>
                                <th width="11%" style="border:1px solid #000000;font-weight:bold;" align="right">DISCOUNT</th>
                                <th width="17%" style="border:1px solid #000000;font-weight:bold;" align="right">AMOUNT</th>
                            </tr>
                        </thead>
            ';
        }

        $sub_total = 0;
        $count = 1;
        $total_qty = 0;
        $total_unit = 0;
        $discount = 0;
        $subTotal = 0;
        $discountValueTotal = 0;
        $Overallsubtotalwithoutdiscount = 0;
        $savedAmount = 0;
        $gstValue = 0;
        $gst_total_Amount = 0;
        $total_vat_Amount_total = 0;
        $total_vat_Sum_Half = 0;
        $total_vat_Sum = 0;
        $total_total_tax = 0;
        $total_tax = '';
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

            $SQLTax = "
            SELECT  gst
                    ,order_id
                    ,SUM((unit_price * qty) - ((unit_price * discount_percentage) /100 * qty)) AS qty_amount
            FROM `order_item` 
            WHERE order_item_id = '{$row['order_item_id']}'
            AND gst > 0
            ";
            $resultTax  = $db->sql_query($SQLTax);
            $rowTax     = $db->sql_fetchrow($resultTax);

            $totalVatSum = 0;

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
                $vat_Sum_Half   = $vat_Sum / 2;

                $totalVatSum += $vat_Sum;

                $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);

            /*if($row['gst_status'] == "ON"){
                $gstValue = $total * $row['gst'] / 100;
                $total    = $total + $gstValue;
            }*/

            if($row['gst_status'] == "ON"){
                $total = ($row['qty'] * $row['unit_price']) + $vat_Sum_Half + $vat_Sum_Half;
            }
            
            $subTotal += $total;
            $Overallsubtotalwithoutdiscount += $subtotalwithoutdiscount;
            $discount = $row['discount'];
            $discount_percentage_amount_sum = $row['discount_percentage_amount_sum'] + $discount;
            //$discount_percentage_amount_sum = $discount;
            $savedAmount = $discount_percentage_amount_sum;

            $total_qty  += $row['qty'];
            $total_unit  = $row['unit'];


            if($row['gst_status'] == "ON"){
                if($company['igst_show'] == "1"){
                    $tbl3 = $tbl3.'<tr>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['ref_no'].'</td>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$count.'</td>
                                        <td width="31%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="left">'.$row['item_title'].'</td>
                                        <td width="10%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['hsn_sac'].'</td>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['qty'].'</td>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['unit'].'</td>
                                        <td width="9%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($row['unit_price'], 2).'</td>
                                        <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$vatPercent.' %</td>
                                        <td width="10%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.number_format($vat_Sum, 2).'</td>
                                        <td width="12%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($total, 2).'</td>
                                    </tr>
                                    ';
                }

                else{
                    $tbl3 = $tbl3.'<tr>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['ref_no'].'</td>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$count.'</td>
                                        <td width="19%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="left">'.$row['item_title'].'</td>
                                        <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['hsn_sac'].'</td>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['qty'].'</td>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['unit'].'</td>
                                        <td width="9%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($row['unit_price'], 2).'</td>
                                        <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$vatPercentHalf.' %</td>
                                        <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.number_format($vat_Sum_Half, 2).'</td>
                                        <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$vatPercentHalf.' %</td>
                                        <td width="10%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($vat_Sum_Half, 2).'</td>
                                        <td width="10%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($total, 2).'</td>
                                    </tr>
                                    ';
                }
            }
            else{
                $tbl3 = $tbl3.'<tr>
                                    <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['ref_no'].'</td>
                                    <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$count.'</td>
                                    <td width="31%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="left">'.$row['item_title'].'</td>
                                    <td width="10%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['hsn_sac'].'</td>
                                    <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['qty'].'</td>
                                    <td width="13%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($row['unit_price'], 2).'</td>
                                    <td width="11%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($discountValue, 2).'</td>
                                    <td width="17%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($total, 2).'</td>
                                </tr>
                                ';
            }

            $count++;
        }

        $totalAmount     = $subTotal - $discount;
        $discountOverall = $discountValueTotal;
        $shipping_charge = $company['shipping_charge'];

        if($shipping_charge == ""){
            $shipping_charge = 0;
        }

        if($shipping_charge != "" && $shipping_charge > 0){
            $totalAmount = $totalAmount + $shipping_charge;
        }

        $roundOff    = round($totalAmount) - $totalAmount;
        $totalAmount = round($totalAmount);

        $sub_total_in_words = $fn->getConvertNumber($totalAmount .'.00');

        if($company['gst_status'] == "ON"){

            $tbl4 = '<table cellpadding="4" border="0" width="60%">';

            if($company['igst_show'] == "1"){
                $tbl4 = $tbl4.'
                    <tr>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Tax Rate</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Taxable</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">IGST</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Total Tax</td>
                    </tr>
                ';
            }else{
                $tbl4 = $tbl4.'
                    <tr>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Tax Rate</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Taxable</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">CGST</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">SGST</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Total Tax</td>
                    </tr>
                ';
            }

            $SQLTax = "
            SELECT  oi.gst
                    ,oi.order_id
                    ,p.hsn AS hsn_sac
                    ,SUM((oi.unit_price * oi.qty) - (((oi.unit_price * oi.discount_percentage) /100 ) * oi.qty)) AS qty_amount
            FROM `order_item` oi
            LEFT JOIN product p ON (p.product_id = oi.record_id)
            WHERE oi.order_id = '{$order_id}'
            AND oi.gst > 0
            GROUP BY oi.gst
            ORDER BY oi.gst ASC
            ";
            $resultTax  = $db->sql_query($SQLTax);

            $totalVatSum = 0;
            $counter = 1;
            while($rowTax     = $db->sql_fetchrow($resultTax)){

                $total_amount = $rowTax['qty_amount'];
                
                if($rowTax['gst'] == ''){
                    $vatPercent = '0.00';
                }
                else{
                    $vatPercent = $rowTax['gst'];
                }

                $vat_Sum  = ($total_amount * $rowTax['gst'])/100;

                //$vat_Amount_total = $total_amount + $vat_Sum;
                $vat_Amount_total = $total_amount;
                if($vat_Sum == 0){
                    $vat_Amount_total = 0;
                }

                $vatPercentHalf = $vatPercent / 2;
                $vat_Sum_Half   = $vat_Sum / 2;

                $totalVatSum += $vat_Sum;

                $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);
                $total_tax = $vat_Sum_Half + $vat_Sum_Half;
                if($company['igst_show'] == "1"){
                    $tbl4 = $tbl4.'
                    <tr>
                        <td align="right">'.$rowTax['gst'].' %</td>
                        <td align="right">'.number_format($vat_Amount_total, 2).'</td>
                        <td align="right">'.number_format($vat_Sum, 2).'</td>
                        <td align="right">'.number_format($total_tax, 2).'</td>
                    </tr>
                    ';
                }else{
                    $tbl4 = $tbl4.'
                    <tr>
                        <td align="right">'.$rowTax['gst'].' %</td>
                        <td align="right">'.number_format($vat_Amount_total, 2).'</td>
                        <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
                        <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
                        <td align="right">'.number_format($total_tax, 2).'</td>
                    </tr>
                    ';
                }

                $counter++;
            }   
        }

        $emptyRow = '';
        if($count <= 7){
            $countCheck = 7 - $count;
        }
        else{
            $countCheck = 0;
        }

        for($ic = 1; $ic <= $countCheck; $ic++){
            if($company['gst_status'] == "ON"){
                if($company['igst_show'] == "1"){
                    $emptyRow .= '
                    <tr>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    </tr>
                    ';
                }else{
                    $emptyRow .= '
                    <tr>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    </tr>
                    ';
                }
            }
            else{
                $emptyRow .= '
                    <tr>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    </tr>
                    ';
            }
        }

        if($company['gst_status'] == "ON"){

            if($company['igst_show'] == "1"){
                $shipping_charge_row = "";
                if($shipping_charge != "" && $shipping_charge > 0){
                    $shipping_charge_row = '
                    <tr>
                        <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="9">SHIPPING CHARGE</td>
                        <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($shipping_charge,2).'</td>
                    </tr>
                    ';
                }

                $tbl3 = $tbl3.'
                '.$emptyRow.'
                <tr>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-left:1px solid #000000;" colspan="9">SUB TOTAL</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-right:1px solid #000000;">'.number_format($subTotal,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="9">DISCOUNT</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($savedAmount,2).'</td>
                </tr>
                '.$shipping_charge_row.'
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="9">ROUND OFF</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($roundOff,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;font-weight:bold;font-size:11px;" colspan="9">TOTAL</td>
                    <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.number_format($totalAmount,2).'</td>
                </tr>
                </table>
                ';
            }
            else{
                $shipping_charge_row = "";
                if($shipping_charge != "" && $shipping_charge > 0){
                    $shipping_charge_row = '
                    <tr>
                        <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="11">SHIPPING CHARGE</td>
                        <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($shipping_charge,2).'</td>
                    </tr>
                    ';
                }

                $tbl3 = $tbl3.'
                '.$emptyRow.'
                <tr>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-left:1px solid #000000;" colspan="11">SUB TOTAL</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-right:1px solid #000000;">'.number_format($subTotal,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="11">DISCOUNT</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($savedAmount,2).'</td>
                </tr>
                '.$shipping_charge_row.'
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="11">ROUND OFF</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($roundOff,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;font-weight:bold;font-size:11px;" colspan="11">TOTAL</td>
                    <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.number_format($totalAmount,2).'</td>
                </tr>
                </table>
                ';
            }
        }

        else{

            $shipping_charge_row = "";
            if($shipping_charge != "" && $shipping_charge > 0){
                $shipping_charge_row = '
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="7">SHIPPING CHARGE</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($shipping_charge,2).'</td>
                </tr>
                ';    
            }

            $tbl3 = $tbl3.'
            '.$emptyRow.'
            <tr>
                <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-left:1px solid #000000;" colspan="7">SUB TOTAL</td>
                <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-right:1px solid #000000;">'.number_format($subTotal,2).'</td>
            </tr>
            <tr>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="7">DISCOUNT</td>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($savedAmount,2).'</td>
            </tr>
            '.$shipping_charge_row.'
            <tr>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="7">ROUND OFF</td>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($roundOff,2).'</td>
            </tr>
            <tr>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-top:1px solid #000000;border-bottom:1px solid #000000;font-weight:bold;font-size:11px;" colspan="7">TOTAL</td>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-top:1px solid #000000;border-bottom:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.number_format($totalAmount,2).'</td>
            </tr>
            </table>
            ';
        }

        if($company['gst_status'] == "ON"){

            $total_vat_Amount_total += $vat_Amount_total;
            $total_vat_Sum_Half += $vat_Sum_Half;
            $total_total_tax += $total_tax;
            $total_vat_Sum += $vat_Sum;

            if($company['igst_show'] == "1"){
                $tbl4 = $tbl4.'
                <tr>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">TOTAL</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Amount_total, 2).'</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Sum, 2).'</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_total_tax, 2).'</td>
                </tr>
                ';

            }else{
                $tbl4 = $tbl4.'
                <tr>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">TOTAL</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Amount_total, 2).'</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Sum_Half, 2).'</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Sum_Half, 2).'</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_total_tax, 2).'</td>
                </tr>
                ';
            }

                $tbl4 = $tbl4.'</table>';

        }

        $tbl5 = '<table cellpadding="4" border="1" width="100%">';

        $tbl5 = $tbl5.'
            <tr>
                <td rowspan="2">'.$company['invoice_terms'].'</td>
                <td style="font-weight:bold;">Receivers signature<br/></td>
            </tr>
            <tr>
                <td align="right" style="font-size:12px;font-weight:bold;">For '.$cpCfg['cp.companyName'].'<br/><br/><br/>Authorised signatory</td>
            </tr>
        </table>
        ';

        $tbl6 = '
        <table cellpadding="4" border="0">
            <tr>
                <td align="left" style="font-size:12px;font-weight:bold;"><b>'.strtoupper($sub_total_in_words).'</b></td>
            </tr>
            <tr>
                <td align="left" style="font-size:10px;"><b>Company PAN : </b><b>'.$cpCfg['cp.companyPanNo'].'</b></td>
            </tr>
            <tr>
                <td align="left" style="font-size:10px;font-weight:bold;"><b>Bank Details : </b><b>'.$cpCfg['cp.bankDetails'].'</b></td>
            </tr>
        </table>
        ';

        $pdf->writeHTML($tbl1, true, false, false, false, '');
        $pdf->writeHTML($tbl2, true, false, false, false, '');
        $pdf->writeHTML($tbl3, true, false, false, false, '');
        
        if($company['gst_status'] == "ON"){
            $pdf->writeHTML($tbl4, true, false, false, false, '');
        }

        $pdf->ln(-4);
        $pdf->writeHTML($tbl6, true, false, false, false, '');
        $pdf->writeHTML($tbl5, true, false, false, false, '');

        $download_title = $company['invoice_code'] . '-Invoice.pdf';
        $pdf->IncludeJS("print();");
        $pdf->Output($download_title, 'I');
    }

    /**
     *
     */
    function getPrintInvoiceRecordFinance($order_id) {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot3.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

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
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER,5);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 10);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        //$pdf->setPrintFooter(false);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
        $pdf->AddPage('L', 'A5');

        //$order_id = $fn->getReqParam('order_id');

        $subSqlForPercentSum = "
        SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2)) as discount_sum
        FROM order_item oi
        WHERE oi.order_id = {$order_id}
          AND oi.discount_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForPercentSum = "
            SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2))
            FROM order_item oi
            WHERE oi.order_id = {$order_id}
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
        WHERE oi.order_id = {$order_id}
          AND oi.discount_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlForValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForValueSum ="
            SELECT SUM(round(oi.discount_amount  * oi.qty,2))
            FROM order_item oi
            WHERE oi.order_id = {$order_id}
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
              ,p.mrp
              ,CONCAT_WS('::', p.carton_no, p.batch_no, p.model) code
              ,o.order_date
              ,o.discount
              ,o.order_id
              ,o.bill_number
              ,o.gst_status
              ,o.shipping_charge
              ,i.vat AS invoice_vat
              ,i.invoice_date
              ,DATE_FORMAT(i.invoice_date, '%d-%m-%Y')AS invoice_creation_date
              ,i.invoice_code_vat
              ,i.invoice_code
              ,i.invoice_id
              ,o.cust_company_name
              ,o.company_id
              ,o.cust_phone
              ,o.cust_email
              ,o.cust_address1
              ,o.cust_address2
              ,o.cust_address_city
              ,o.cust_address_state
              ,o.cust_address_country_code
              ,o.cust_gst_no
              ,o.shipping_first_name
              ,o.shipping_phone
              ,o.shipping_email
              ,o.shipping_address1
              ,o.shipping_address2
              ,o.shipping_address_city
              ,o.shipping_address_state
              ,o.shipping_address_country_code
              ,o.shipping_gst_no
              ,o.invoice_terms
              ,o.memo
              ,o.igst_show
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
        WHERE o.order_id = '{$order_id}'
        ORDER BY p.title
        ";
        $result = $db->sql_query($SQL);
        $result2 = $db->sql_query($SQL);
        $company = $db->sql_fetchrow($result2);
        //============================================================================= //

        $pdf->SetFont('helvetica','', 7);

        $today = date("d-m-Y");

        if($company['order_id'] < 10){
            $orderNo = '0000' . $company['order_id'];
        }
        else if($company['order_id'] <= 99){
            $orderNo = '000' . $company['order_id'];
        }
        else if($company['order_id'] <= 999){
            $orderNo = '00' . $company['order_id'];
        }
        else if($company['order_id'] <= 9999){
            $orderNo = '0' . $company['order_id'];
        }
        else{
            $orderNo = $company['order_id'];
        }

        //$invoice_code = substr($company['invoice_code'], 2);

        if($company['invoice_code'] == ""){
            $invoice_code = 'INV - '.$company['invoice_id']; 
        }
        else{
            $invoice_code = $company['invoice_code'];
        }
        $due_date = date('d-m-Y', strtotime($company['invoice_date']. ' + 15 days'));;

        $tbl1 ='
        <table border="0" width="100%" cellpadding="3">
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:10px;"><i>Billing Details :</i></td>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:10px;"><span style="">Invoice No &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : </span>'.$invoice_code.'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:10px;">'.$company['cust_company_name'].'</td>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:10px;"><span style="">Date : </span>'.$company['invoice_creation_date'].'</td>
            </tr>

            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:10px;">'.$company['cust_address1'].'</td>
                <td style="border-right:1px solid #000000;font-size:10px;">Vehicle No.</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:10px;">'.$company['cust_address2'].''.$company['cust_address_city'].' '.$company['cust_address_state'].'</td>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:10px;"><span style="">Due Date : </span>'.$due_date.'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:10px;">Mobile No : '.$company['cust_phone'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; GST IN / UIN :'.$company['cust_gst_no'].'</td>
                <td style="border-right:1px solid #000000;font-size:10px;"></td>
            </tr>
        </table>
        ';

        $tbl3 ='<table border="0" width="100%" cellpadding="3">
                    <thead>
                        <tr>
                            <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">S.NO</th>
                            <th width="19%" style="border:1px solid #000000;font-weight:bold;" align="left">NAME</th>
                            <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="center">HSN/SAC</th>
                            <th width="7%"  style="border:1px solid #000000;font-weight:bold;" align="center">MRP</th>
                            <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">QTY</th>
                            <th width="9%"  style="border:1px solid #000000;font-weight:bold;" align="right">PCS RATE</th>
                            <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="right">CGST %</th>
                            <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="right">CGST</th>
                            <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="right">SGST %</th>
                            <th width="8%" style="border:1px solid #000000;font-weight:bold;" align="right">SGST</th>
                            <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="right">DISC</th>
                            <th width="10%" style="border:1px solid #000000;font-weight:bold;" align="right">AMOUNT</th>
                        </tr>
                    </thead>
        ';

        $sub_total = 0;
        $count = 1;
        $total_qty = 0;
        $total_unit = 0;
        $discount = 0;
        $subTotal = 0;
        $total_gst = 0;
        $discountValueTotal = 0;
        $Overallsubtotalwithoutdiscount = 0;
        $savedAmount = 0;
        $gstValue = 0;
        $gst_total_Amount = 0;
        $total_vat_Amount_total = 0;
        $total_vat_Sum_Half = 0;
        $total_vat_Sum = 0;
        $total_total_tax = 0;
        $total_tax = '';
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

            $SQLTax = "
            SELECT  gst
                    ,order_id
                    ,SUM((unit_price * qty) - ((unit_price * discount_percentage) /100 * qty)) AS qty_amount
            FROM `order_item` 
            WHERE order_item_id = '{$row['order_item_id']}'
            AND gst > 0
            ";
            $resultTax  = $db->sql_query($SQLTax);
            $rowTax     = $db->sql_fetchrow($resultTax);

            $totalVatSum = 0;

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
                $vat_Sum_Half   = $vat_Sum / 2;

                $totalVatSum += $vat_Sum;

                $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);

            /*if($row['gst_status'] == "ON"){
                $gstValue = $total * $row['gst'] / 100;
                $total    = $total + $gstValue;
            }*/

            if($row['gst_status'] == "ON"){
                $total = ($row['qty'] * $row['unit_price']) + $vat_Sum_Half + $vat_Sum_Half - $discountValue;
            }
            
            $subTotal += $total;
            $Overallsubtotalwithoutdiscount += $subtotalwithoutdiscount;
            $discount = $row['discount'];
            $discount_percentage_amount_sum = $row['discount_percentage_amount_sum'] + $discount;
            //$discount_percentage_amount_sum = $discount;
            $savedAmount = $discount_percentage_amount_sum;

            $total_qty  += $row['qty'];
            $total_unit  = $row['unit'];
            $total_gst  += $vat_Sum_Half;
            $discountValueTotal += $discountValue;


            $tbl3 = $tbl3.'
            <tr>
                <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$count.'</td>
                <td width="19%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="left">'.$row['item_title'].'</td>
                <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['hsn_sac'].'</td>
                <td width="7%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['mrp'].'</td>
                <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['qty'].'</td>
                <td width="9%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($row['unit_price'], 2).'</td>
                <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$vatPercentHalf.' %</td>
                <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($vat_Sum_Half, 2).'</td>
                <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$vatPercentHalf.' %</td>
                <td width="8%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($vat_Sum_Half, 2).'</td>
                <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$discountValue.'</td>                
                <td width="10%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($total, 2).'</td>
            </tr>
            ';
            $count++;
        }

        $totalAmount     = $subTotal - $discount;
        $shipping_charge = $company['shipping_charge'];

        if($shipping_charge == ""){
            $shipping_charge = '';
        }

        if($shipping_charge != "" && $shipping_charge > 0){
            $totalAmount = $totalAmount + $shipping_charge;
        }

        $sub_total_in_words = $fn->getConvertNumber($totalAmount .'.00');

        $tbl4 = '<table cellpadding="3" border="0" width="60%">';

        $tbl4 = $tbl4.'
            <tr>
                <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Tax Rate</td>
                <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Taxable</td>
                <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">CGST</td>
                <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">SGST</td>
                <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Total Tax</td>
            </tr>
        ';

        $SQLTax = "
        SELECT  oi.gst
                ,oi.order_id
                ,p.hsn AS hsn_sac
                ,SUM((oi.unit_price * oi.qty) - (((oi.unit_price * oi.discount_percentage) /100 ) * oi.qty)) AS qty_amount
        FROM `order_item` oi
        LEFT JOIN product p ON (p.product_id = oi.record_id)
        WHERE oi.order_id = '{$order_id}'
        AND oi.gst > 0
        GROUP BY oi.gst
        ORDER BY oi.gst ASC
        ";
        $resultTax  = $db->sql_query($SQLTax);

        $totalVatSum = 0;
        $counter = 1;
        while($rowTax     = $db->sql_fetchrow($resultTax)){

            $total_amount = $rowTax['qty_amount'];
            
            if($rowTax['gst'] == ''){
                $vatPercent = '0.00';
            }
            else{
                $vatPercent = $rowTax['gst'];
            }

            $vat_Sum  = ($total_amount * $rowTax['gst'])/100;

            //$vat_Amount_total = $total_amount + $vat_Sum;
            $vat_Amount_total = $total_amount;
            if($vat_Sum == 0){
                $vat_Amount_total = 0;
            }

            $vatPercentHalf = $vatPercent / 2;
            $vat_Sum_Half   = $vat_Sum / 2;

            $totalVatSum += $vat_Sum;

            $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);
            $total_tax = $vat_Sum_Half + $vat_Sum_Half;
            $tbl4 = $tbl4.'
            <tr>
                <td align="right">'.$rowTax['gst'].' %</td>
                <td align="right">'.number_format($vat_Amount_total, 2).'</td>
                <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
                <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
                <td align="right">'.number_format($total_tax, 2).'</td>
            </tr>
            ';

            $counter++;
        }   

        $emptyRow = '';
        if($count <= 6){
            $countCheck = 6 - $count;
        }
        else{
            $countCheck = 0;
        }

        for($ic = 1; $ic <= $countCheck; $ic++){
            if($company['gst_status'] == "ON"){
                $emptyRow .= '
                <tr>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                </tr>
                ';
            }
            else{
                $emptyRow .= '
                    <tr>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    </tr>
                    ';
            }
        }

        $shipping_charge_row = "";
        if($shipping_charge != "" && $shipping_charge > 0){
            $shipping_charge_row = '
            <tr>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="11">SHIPPING CHARGE</td>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($shipping_charge,2).'</td>
            </tr>
            ';
        }

        $tbl3 = $tbl3.'
        '.$emptyRow.'
        <tr>
            <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;font-weight:bold;font-size:11px;" colspan="4">TOTAL</td>
            <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;border-right:1px solid #000000;font-weight:bold;">'.$total_qty.'</td>
            <td style="border-bottom:1px solid #000000;border-top:1px solid #000000;"></td>
            <td style="border-bottom:1px solid #000000;border-top:1px solid #000000;"></td>
            <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;border-right:1px solid #000000;font-weight:bold;">'.number_format($total_gst,2).'</td>
            <td style="border-bottom:1px solid #000000;border-top:1px solid #000000;"></td>
            <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;border-right:1px solid #000000;font-weight:bold;">'.number_format($total_gst,2).'</td>
            <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;border-right:1px solid #000000;font-weight:bold;">'.number_format($discountValueTotal,2).'</td>            
            <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;border-right:1px solid #000000;font-weight:bold;">'.number_format($totalAmount,2).'</td>
        </tr>
        </table>
        ';
        $total_vat_Amount_total += $vat_Amount_total;
        $total_vat_Sum_Half += $vat_Sum_Half;
        $total_total_tax += $total_tax;
        $total_vat_Sum += $vat_Sum;

        $tbl4 = $tbl4.'
        <tr>
            <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">TOTAL</td>
            <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Amount_total, 2).'</td>
            <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Sum_Half, 2).'</td>
            <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Sum_Half, 2).'</td>
            <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_total_tax, 2).'</td>
        </tr>
        </table>
        ';

        $tbl5 = '<table cellpadding="4" border="1" width="100%" nobr="true">';

        $tbl5 = $tbl5.'
            <tr>
                <td>'.$company['invoice_terms'].'</td>
                <td align="right" style="font-size:12px;font-weight:bold;">For '.$cpCfg['cp.companyName'].'<br/><br/><br/>Authorised signatory</td>
            </tr>
        </table>
        ';

        $tbl6 = '
        <table cellpadding="4" border="0">
            <tr>
                <td align="left" style="font-size:10px;font-weight:bold;"><b>Bank Details : </b><b>'.$cpCfg['cp.bankDetails'].'</b></td>
                <td align="center" style="font-size:10px;font-weight:bold;"><b>Buyers Signature</b></td>
                <td align="left" style="font-size:10px;">Delivery Charges : '.number_format($shipping_charge,2).'<br/>Round Off : <br/><b>NET AMOUNT : '.number_format($totalAmount,2).'</b></td>
            </tr>
        </table>
        ';

        $pdf->writeHTML($tbl1, true, false, false, false, '');
        $pdf->ln(-5);
        $pdf->writeHTML($tbl3, true, false, false, false, '');        
        $pdf->ln(-5);
        $pdf->writeHTML($tbl4, true, false, false, false, '');
        $pdf->ln(-5);
        $pdf->writeHTML($tbl6, true, false, false, false, '');
        $pdf->ln(-5);
        $pdf->writeHTML($tbl5, true, false, false, false, '');

        $download_title = $company['invoice_code'] . '-Invoice.pdf';
        $pdf->IncludeJS("print();");
        $pdf->Output($download_title, 'I');
    }


    /**
     *
     */
    function getPrintInvoiceRecord() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        $order_id = $fn->getReqParam('order_id');

        if ($cpCfg['cp.serialKeyActive'] == "SULB-DHEO-0R6K-59CL" || $cpCfg['cp.serialKeyActive'] == "YODX0-9DT58-VCZ5W-A8XXB") {
            $this->getPrintInvoiceRecordSurgicalShop($order_id);
        }

        else if($cpCfg['cp.companyType'] == "Computer Shop"){
            $this->getPrintInvoiceRecordComputerSystems($order_id);
        }

        else{

            ini_set('memory_limit', '512M');
            set_time_limit(50000);

            include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
            include_once(CP_LOCAL_PATH.'lib/headfoot.php');

            $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

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

            $pdf->setPrintFooter(false);

            // set some language-dependent strings (optional)
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
            }

            $subSqlForPercentSum = "
            SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2)) as discount_sum
            FROM order_item oi
            WHERE oi.order_id = {$order_id}
              AND oi.discount_type = '%'
            ";
            $resultSubSql = $db->sql_query($subSqlForPercentSum);
            $rowSql       = $db->sql_fetchrow($resultSubSql);
            if($rowSql['discount_sum'] > 0){
                $subSqlForPercentSum = "
                SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2))
                FROM order_item oi
                WHERE oi.order_id = {$order_id}
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
            WHERE oi.order_id = {$order_id}
              AND oi.discount_type = 'Value'
            ";
            $resultSubSql = $db->sql_query($subSqlForValueSum);
            $rowSql       = $db->sql_fetchrow($resultSubSql);
            if($rowSql['discount_sum'] > 0){
                $subSqlForValueSum ="
                SELECT SUM(round(oi.discount_amount  * oi.qty,2))
                FROM order_item oi
                WHERE oi.order_id = {$order_id}
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
                  ,o.shipping_address1
                  ,o.shipping_first_name
                  ,o.shipping_address2
                  ,o.shipping_address_city
                  ,o.shipping_address_state
                  ,o.shipping_gst_no
                  ,i.vat AS invoice_vat
                  ,i.invoice_date
                  ,DATE_FORMAT(o.order_date, '%d-%m-%Y')AS invoice_creation_date
                  ,i.invoice_code_vat
                  ,i.invoice_code
                  ,i.igst_cgst
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
            WHERE o.order_id = '{$order_id}'
            ORDER BY p.title
            ";
            $result = $db->sql_query($SQL);
            $result2 = $db->sql_query($SQL);
            $company = $db->sql_fetchrow($result2);
            //============================================================================= //

            $pdf->SetFont('Arial','B', 8);

            $today = date("d-m-Y");

            if($company['order_id'] < 10){
                $orderNo = '0000' . $company['order_id'];
            }
            else if($company['order_id'] <= 99){
                $orderNo = '000' . $company['order_id'];
            }
            else if($company['order_id'] <= 999){
                $orderNo = '00' . $company['order_id'];
            }
            else if($company['order_id'] <= 9999){
                $orderNo = '0' . $company['order_id'];
            }
            else{
                $orderNo = $company['order_id'];
            }

            $invoice_code = substr($company['invoice_code'], 2);

            $tbl1 = '
            <table border="0" width="100%">
                <tr>
                    <td width="35%">BILL NO : '.$orderNo.'</td>
                    <td align="center" width="30%" style="font-weight:bold;">BILL</td>
                    <td width="35%" align="right">'.$company['invoice_creation_date'].'</td>
                </tr>
            </table>
            ';

            $tblHead ='
            <table border="1" width="100%" cellpadding="5">
                <tr>
                    <td width="100%">BILLED TO :<br/>'.$company['shipping_first_name'].'<br/>'.$company['shipping_address1'].'<br/>'.$company['shipping_address2'].'<br/>'.$company['shipping_address_city'].'<br/>'.$company['shipping_address_state'].'<br/>GST No.: '.$company['shipping_gst_no'].'</td>
                </tr>
            </table>
            ';

            if($company['gst_status'] == "ON"){
                $tbl3 ='<table border="0" width="100%" cellpadding="3">
                            <thead>
                                <tr>
                                    <th width="6%"  style="border:1px solid #000000;" align="center">S.NO</th>
                                    <th width="32%" style="border:1px solid #000000;" align="left">PRODUCTS</th>
                                    <th width="10%" style="border:1px solid #000000;" align="center">ITEM CODE</th>
                                    <th width="6%"  style="border:1px solid #000000;" align="center">QTY</th>
                                    <th width="11%" style="border:1px solid #000000;" align="right">PRICE</th>
                                    <th width="10%" style="border:1px solid #000000;" align="right">DISCOUNT</th>
                                    <th width="12%" style="border:1px solid #000000;" align="right">GST</th>
                                    <th width="13%" style="border:1px solid #000000;" align="right">AMOUNT</th>
                                </tr>
                            </thead>
                ';
            }
            //5
            else{
                $tbl3 ='<table border="0" width="100%" cellpadding="3">
                            <thead>
                                <tr>
                                    <th width="6%"  style="border:1px solid #000000;" align="center">S.NO</th>
                                    <th width="35%" style="border:1px solid #000000;" align="left">PRODUCTS</th>
                                    <th width="10%" style="border:1px solid #000000;" align="center">ITEM CODE</th>
                                    <th width="8%"  style="border:1px solid #000000;" align="center">QTY</th>
                                    <th width="13%" style="border:1px solid #000000;" align="right">PRICE</th>
                                    <th width="11%" style="border:1px solid #000000;" align="right">DISCOUNT</th>
                                    <th width="17%" style="border:1px solid #000000;" align="right">AMOUNT</th>
                                </tr>
                            </thead>
                ';
            }

            $sub_total = 0;
            $count = 1;
            $total_qty = 0;
            $discount = 0;
            $subTotal = 0;
            $subTotall = 0;
            $discountValueTotal = 0;
            $Overallsubtotalwithoutdiscount = 0;
            $savedAmount = 0;
            $gstValue = 0;
            $gstValues = 0;
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

                if($row['gst_status'] == "ON"){
                    $gstValue = $total * $row['gst'] / 100;
                    $total    = $total + $gstValue;
                }
                
                 $grossAmount = $row['qty'] * $row['unit_price'];

                $taxableAmount = $grossAmount;
                
                
                
                $taxableAmount1 = $grossAmount - $discountValue;

                
            
                $gstValues += $gstValue;
                 $subTotall += $total;
                $subTotal += $taxableAmount;
                $Overallsubtotalwithoutdiscount += $subtotalwithoutdiscount;
                $discount = $row['discount'];
                $discount_percentage_amount_sum = $row['discount_percentage_amount_sum'] + $discount;
                //$discount_percentage_amount_sum = $discount;
                $savedAmount = $discount_percentage_amount_sum;

                $total_qty  += $row['qty'];


                if($row['gst_status'] == "ON"){
                    $tbl3 = $tbl3.'<tr>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$count.'</td>
                                        <td width="32%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="left">'.$row['item_title'].'</td>
                                        <td width="10%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['item_code'].'</td>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['qty'].'</td>
                                        <td width="11%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($row['unit_price'], 2).'</td>
                                        <td width="10%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($discountValue, 2).'</td>
                                        <td width="12%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">('.$row['gst'].'%)'.number_format($gstValue, 2).'</td>
                                        <td width="13%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($total, 2).'</td>
                                    </tr>
                                    ';
                }
                else{
                    $tbl3 = $tbl3.'<tr>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$count.'</td>
                                        <td width="35%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="left">'.$row['item_title'].'</td>
                                        <td width="10%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['item_code'].'</td>
                                        <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['qty'].'</td>
                                        <td width="13%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($row['unit_price'], 2).'</td>
                                        <td width="11%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($discountValue, 2).'</td>
                                        <td width="17%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($total, 2).'</td>
                                    </tr>
                                    ';
                }

                $count++;
            }

            $totalAmount     = $subTotall - $discount;
            $discountOverall = $discountValueTotal;

            $sub_total_in_words = $fn->getConvertNumber($totalAmount .'.00');

            if($company['gst_status'] == "ON"){

                $tbl4 = '<table cellpadding="4" border="0">';

                $tbl4 = $tbl4.'
                        <tr>
                            <td align="left" colspan="3">TAX SUMMARY:</td>
                        </tr>
                        <tr>
                            <td style="border:1px solid #000000;" align="left" >TAX TYPE</td>
                            <td style="border:1px solid #000000;" align="right">TAXABLE</td>
                            <td style="border:1px solid #000000;" align="right">TAX AMOUNT</td>
                        </tr>
                ';

                $SQLTax = "
                SELECT  gst
                        ,order_id
                        ,SUM((unit_price * qty) - ((unit_price * discount_percentage) /100 * qty)) AS qty_amount
                FROM `order_item` 
                WHERE order_id = '{$order_id}'
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

                    $vat_Amount_total = $total_amount;
                    if($vat_Sum == 0){
                        $vat_Amount_total = 0;
                    }

                    $vatPercentHalf = $vatPercent / 2;
                    $vat_Sum_Half   = $vat_Sum / 2;

                    $totalVatSum += $vat_Sum;

                    $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);

                    if(isset($company['igst_cgst']) && $company['igst_cgst'] == "IGST"){
                        // Show IGST only
                        $tbl4 = $tbl4.'
                        <tr>
                            <td style="border-left:1px solid #000000;" align="left">IGST '.$vatPercent.' %</td>
                            <td style="border-left:1px solid #000000;" align="right">'.number_format($vat_Amount_total, 2).'</td>
                            <td style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($vat_Sum, 2).'</td>
                        </tr>
                        ';
                    }
                    else{
                        // Show SGST and CGST
                        $tbl4 = $tbl4.'
                        <tr>
                            <td style="border-left:1px solid #000000;" align="left">SGST '.$vatPercentHalf.' %</td>
                            <td style="border-left:1px solid #000000;" align="right">'.number_format($vat_Amount_total, 2).'</td>
                            <td style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($vat_Sum_Half, 2).'</td>
                        </tr>
                        <tr>
                            <td align="left"  style="border-left:1px solid #000000;">CGST '.$vatPercentHalf.' %</td>
                            <td align="right" style="border-left:1px solid #000000;">'.number_format($vat_Amount_total, 2).'</td>
                            <td align="right" style="border-left:1px solid #000000;border-right:1px solid #000000;">'.number_format($vat_Sum_Half, 2).'</td>
                        </tr>
                        ';
                    }
                }   
            }

            if($company['gst_status'] == "ON"){
                $tbl3 = $tbl3.'
                <tr>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                </tr>
                <tr>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td align="right" style="border-left:1px solid #000000;">SUB TOTAL</td>
                    <td align="right" style="border-left:1px solid #000000;border-right:1px solid #000000;">'.number_format($subTotal,2).'</td>
                </tr>
                <tr>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td align="right" style="border-left:1px solid #000000;">DISCOUNT</td>
                    <td align="right" style="border-left:1px solid #000000;border-right:1px solid #000000;">'.number_format($savedAmount,2).'</td>
                </tr>
                   <tr>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td align="right" style="border-left:1px solid #000000;">GST</td>
                    <td align="right" style="border-left:1px solid #000000;border-right:1px solid #000000;">'.number_format($gstValues,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;border-top:1px solid #000000;font-weight:bold;font-size:11px;" colspan="7">TOTAL</td>
                    <td align="right" style="border-left:1px solid #000000;border-top:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.number_format($totalAmount,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;font-size:11px;" colspan="7">YOU HAVE SAVED</td>
                    <td align="right" style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.number_format($savedAmount,2).'</td>
                </tr>
                <tr>
                    <td colspan="8" align="right" style="border:1px solid #000000;">AMOUNT IN WORDS : <span style="font-size:11px;"><b>'.strtoupper($sub_total_in_words).'</b></span></td>
                </tr>
                </table>
                ';
            }

            else{
                $tbl3 = $tbl3.'
                <tr>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                </tr>
                <tr>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td align="right" style="border-left:1px solid #000000;">SUB TOTAL</td>
                    <td align="right" style="border-left:1px solid #000000;border-right:1px solid #000000;">'.number_format($subTotal,2).'</td>
                </tr>
                <tr>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    <td align="right" style="border-left:1px solid #000000;">DISCOUNT</td>
                    <td align="right" style="border-left:1px solid #000000;border-right:1px solid #000000;">'.number_format($savedAmount,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;border-top:1px solid #000000;font-weight:bold;font-size:11px;" colspan="6">TOTAL</td>
                    <td align="right" style="border-left:1px solid #000000;border-top:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.number_format($totalAmount,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;font-size:11px;" colspan="6">YOU HAVE SAVED</td>
                    <td align="right" style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.number_format($savedAmount,2).'</td>
                </tr>
                <tr>
                    <td colspan="7" align="right" style="border:1px solid #000000;">AMOUNT IN WORDS : <span style="font-size:11px;"><b>'.strtoupper($sub_total_in_words).'</b></span></td>
                </tr>
                </table>
                ';
            }

            if($company['gst_status'] == "ON"){

                $tbl4 = $tbl4.'
                <tr>
                    <td align="right" style="border:1px solid #000000;" colspan="2">TOTAL</td>
                    <td align="right" style="border:1px solid #000000;">'.number_format($totalVatSum, 2).'</td>
                </tr>
                ';

                $tbl4 = $tbl4.'</table>';
            }


            /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
            $pdf->AddPage();

            $pdf->writeHTML($tbl1, true, false, false, false, '');
            $pdf->writeHTML($tblHead, true, false, false, false, '');
            $pdf->writeHTML($tbl3, true, false, false, false, '');
            
            if($company['gst_status'] == "ON"){
                $pdf->writeHTML($tbl4, true, false, false, false, '');
            }

            $download_title = $company['invoice_code'] . '-Invoice.pdf';
            $pdf->IncludeJS("print();");
            $pdf->Output($download_title, 'I');
        }
    }

    /**
     *
     */
    function getRightPanel($row){
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $media = Zend_Registry::get('media');
        $cpCfg = Zend_Registry::get('cpCfg');
        $db = Zend_Registry::get('db');

        $printText = "";
        $actionButtons = "";
        $summaryAction = "";
        $captainCopy = "";

        $links ='';
        if ($cpCfg['m.ecommerce.order.showAttachment'] == 1){
            $links .= $media->getRightPanelMediaDisplay('Attachments', 'tradingsg_order', 'attachment', $row);
        }

        $printTextButton ='';

        if ($cpCfg['m.tradingsg.order.showReceiptButton']){
            $formActionReceipt = "index.php?module=tradingsg_order&_spAction=generateReceiptForm&order_id={$row['order_id']}&showHTML=0";

            $actionButtons .="
            <div class='float_right btn btn-info mb5'>
                <a href='{$formActionReceipt}' id='generateReceipt'>CREATE RECEIPT</a>
            </div>
            ";
        }

        if ($cpCfg['m.tradingsg.order.showInvoiceButton']){
            $formActionInvoice = "index.php?module=tradingsg_order&_spAction=generateInvoiceForm&order_id={$row['order_id']}&showHTML=0";
            $actionButtons .="
            <div class='float_right btn btn-success mb5'>
                <a href='{$formActionInvoice}' id='generateInvoice'>CREATE INVOICE</a>
            </div>
            ";
        }

        $actionButtons .="
        <div class='float_right btn btn-danger mb5'>
            <a id='cancelOrderEdit' order_id='{$row['order_id']}'>CANCEL ORDER</a>
        </div>
        ";

         $formActionCredit = "index.php?module=tradingsg_order&_spAction=generateCreditNoteForm&order_id={$row['order_id']}&showHTML=0";
            $actionButtons .="
            <div class='float_right btn btn-success mb5'>
                <a href='{$formActionCredit}' id='generateCredit'>CREATE CREDIT NOTE</a>
            </div>
            ";

         $formActionDebit = "index.php?module=tradingsg_order&_spAction=generateDebitNoteForm&order_id={$row['order_id']}&showHTML=0";
            $actionButtons .="
            <div class='float_right btn btn-success mb5'>
                <a href='{$formActionDebit}' id='generateDebit'>CREATE DEBIT NOTE</a>
            </div>
            ";

        $print ="
        <div class='floatbox actionBtnsDetail'>
	        <div class='orderbtnbackground floatbox mb10'>
            {$actionButtons}
	        </div>
        </div>
        ";

        if ($cpCfg['m.tradingsg.order.showInvoicePortalDisplay']){
            //$links .= $displayLinkData->getLinkPortalMain('tradingsg_order', 'tradingsg_invoiceLink', 'Invoices Linked', $row);
            $links .= $this->getInvoicePortalDisplay($row);
        }

        if ($cpCfg['m.tradingsg.order.showReceiptPortalDisplay']){
            //$links .= $displayLinkData->getLinkPortalMain('tradingsg_order', 'tradingsg_receiptLink', 'Receipt Linked', $row);
            $links .= $this->getReceiptPortalDisplay($row);
        }

            $summaryTableOrder = $this->getSummaryInOrder($row);

        $orderItem = '';
        if ($cpCfg['m.tradingsg.order.showOrderItemDisplay']){
            $orderItem = $displayLinkData->getLinkPortalMain('tradingsg_order', 'ecommerce_orderItemLink', 'Order Items', $row);
        }
        $links .= $this->getSalesReturnDisplay($row);
        $links .= $this->getCreditPortalDisplay($row);
        $links .= $this->getDebitPortalDisplay($row);

        $text = "
        {$print}
        {$summaryTableOrder}
        {$orderItem}
        {$links}
        ";

        return $text;
    }

    /**
     */
    function getInvoicePortalDisplay($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $formAction = '';

        $text = "
        <tr class=''>
        <td>
            <div id='' class='invoiceDisplay'>
                <h2>Invoice(s)</h2>
                <form id='orderItemPrint' class='' method='post' action='{$formAction}'>
                    <div id='invoicePortalOuter'>
                        {$this->getInvoicePortalDisplayDetail($row)}
                    </div>
                </form>
            </div>
        </td>
        </tr>
        ";

        return $text;
    }

    /**
    **/

    function getSummaryInOrder($row) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $rows  = "";

        $SQL = "
        SELECT o.*
              ,(SELECT SUM(round((oi.unit_price * oi.qty),2))
               FROM order_item oi
               WHERE oi.order_id = {$row['order_id']}
               ) AS order_amount
              ,(SELECT SUM(i.invoice_amount) FROM invoice i
                WHERE i.order_id = o.order_id
                AND i.status != 'Cancelled'
                ) AS invoice_amount 
              ,(SELECT SUM(r.amount)
                FROM receipt r
                WHERE o.order_id = r.order_id
                AND r.receipt_status != 'Cancelled'
                )AS receipt_amount  
        FROM `order`o
        WHERE o.order_id = {$row['order_id']}
        ";

        $result = $db->sql_query($SQL);
        $row  = $db->sql_fetchrow($result);

        $orderAmt   = number_format(round($row['order_amount']), 2);
        $invoiceAmt = number_format($row['invoice_amount'] ,2);
        $receiptAmt = number_format($row['receipt_amount'] ,2);

        $outstandingInvoiceAmt = number_format($row['invoice_amount'] - $row['receipt_amount'], 2);
        $overallBalanceAmt     = number_format($row['invoice_amount'] - $row['receipt_amount'], 2);

            $rows = "
            <table class='summaryAmountDetails'>
                <tr class= 'summaryTitle'>
                    <th>SUMMARY</th>
                    <th></th>
                </tr>
                <tr>
                    <td class='totalOrderAmountLabel'>TOTAL ORDER AMOUNT</td>
                    <td class='totalInvoiceAmountValue'>{$invoiceAmt}</td>
                </tr>
                <tr>
                    <td class='totalOrderAmountLabel'>AMOUNT PAID</td>
                    <td class='totalReciptAmountValue'>{$receiptAmt}</td>
                </tr>
                <tr>
                    <td class='totalOrderAmountLabel'>AMOUNT BALANCE</td>
                    <td class='totalOverallAmountValue'>{$overallBalanceAmt}</td>
                </tr>
            </table>
            ";      

        $text = "
        {$rows}
        ";

        return $text;

    }

  /**
     */
    function getCreditPortalDisplay($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $formAction = '';

        $text = "
        <tr class=''>
        <td>
            <div id='' class='invoiceDisplay'>
                <h2>Credit Note(s)</h2>
                <form id='orderItemPrint' class='' method='post' action='{$formAction}'>
                    <div id='invoicePortalOuter'>
                        {$this->getCreditPortalDisplayDetail($row)}
                    </div>
                </form>
            </div>
        </td>
        </tr>
        ";

        return $text;
    }

    
    function getCreditPortalDisplayDetail($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $rows  = "";
        $rowsPvt  = "";
        $links = "";
        $sqlAppend = "";

        $status = $fn->getReqParam('status');

        if ($status) {
            $sqlAppend .= "AND i.status = '{$status}'";
        }

        $_SESSION['selectedInvoiceIds'] = array();
        $exp = array('isEditable' => 1);

        $SQL = "
        SELECT i.*
            
            {$sqlAppend}
        FROM credit_note i
        WHERE i.order_id = {$row['order_id']}
          AND i.invoice_type = 'Client'
        ORDER BY i.credit_note_id
        ";

        $result   = $db->sql_query($SQL);
        $discount = '';
        $tdCheckBox = '';
        $checkBoxStatus = '';
        $count = 1;
        $invoice_code = '';
        $add_registration_fee = '';
        $invoice_hist_amount  = '';

        while ($rowInvoice = $db->sql_fetchrow($result)) {
            $gstvalue = '';
            $gsttaxvalue = '';
            $pfvalue = '';
            $frieghtValue = '';
            $total = '';
            $selectedValuePaid   = '';
            $selectedValueDue    = '';
            $selectedValueCancel = '';

            $urlPrint       = "index.php?_topRm=finance&module=tradingsg_order&_spAction=printCreditRecord&invoice_code={$rowInvoice['invoice_code']}&credit_note_id={$rowInvoice['credit_note_id']}&invoice_type=normal&footer_logo=yes&showHTML=0";
           
           
            if($rowInvoice['status'] != 'Cancelled'){
                $total += $rowInvoice['invoice_amount'];
            }


                $cancelInvoiceLink = '';
                if ($rowInvoice['status'] != 'Cancelled'){
                    $cancelInvoiceLink = "<a href='#' class='cancelCreditNote' invoice_code='{$rowInvoice['invoice_code']}'>Cancel Credit Note</a>";
                }

                $invoice_date = $fn->getCPDate($rowInvoice['invoice_date'], 'd-m-Y');
                $totalvalueRounded = number_format(round($total),2);

                $rows .= "
                <tr>
                    <td>{$rowInvoice['invoice_code']}</td>
                    <td>{$rowInvoice['status']}</td>
                    <td>{$invoice_date}</td>
                    <td align='right'>$totalvalueRounded</td>
                    <td><a href='{$urlPrint}' target='_blank'>Print Credit Note</a></td>
                    <td>{$cancelInvoiceLink}</td>
                </tr>
                ";
            }

            //$invoice_code = $rowInvoice['invoice_code'];
        //}

        $header ="
        <tr style='background-color:#EAEAE8;'>
        <th>Credit Code</th>
        <th>Status</th>
        <th>Credit Date</th>
        <th>Amount</th>
        <th>Print</th>
        <th>Cancel</th>
        </tr>
        ";

        $text = "
        <table class='thinlist'>
            {$header}
            {$rows}
            {$rowsPvt}
        </table>
        ";

        return $text;
    }

    /**
     *
     */
    function getPrintReceipt() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

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

        $receipt_code = $fn->getReqParam('receipt_code');
        $order_id = $fn->getReqParam('order_id');

        $SQL = "
        SELECT ini.*
              ,CONCAT_WS(' ',o.shipping_first_name) AS cust_name
              ,o.cust_address1
              ,o.cust_address2
              ,o.cust_address_po_code
              ,o.shipping_address1
              ,o.shipping_address_area
              ,o.shipping_address_city
              ,o.shipping_address_country_code
              ,o.shipping_address_po_code
              ,o.shipping_phone
              ,o.order_id
              ,i.discount
              ,i.creation_date
              ,i.invoice_id AS invoice_id_main
              ,i.invoice_code
              ,i.invoice_amount
              ,r.receipt_id
              ,r.amount AS receipt_amount
              ,r.receipt_code
              ,r.mode_of_payment
              ,r.remarks
              ,r.creation_date AS receipt_date
        FROM receipt r
        LEFT JOIN invoice_receipt_history irh ON (r.receipt_id = irh.receipt_id)
        LEFT JOIN invoice i ON (i.invoice_id = irh.invoice_id)
        LEFT JOIN invoice_item ini ON (i.invoice_id = ini.invoice_id)
        LEFT JOIN `order` o ON (o.order_id = i.order_id)
        LEFT JOIN company c ON (c.company_id = o.company_id)
        WHERE r.receipt_code = '{$receipt_code}'
        AND r.order_id = {$order_id}
        ";
        $result = $db->sql_query($SQL);
        $result2 = $db->sql_query($SQL);
        $company = $db->sql_fetchrow($result2);
        //============================================================================= //

        $pdf->SetFont('Courier','B',10);

        $today = date("d-m-Y");
        $receipt_date = $fn->getCPDate($company['receipt_date'], 'd-m-Y');

        $tbl1 = '
        <table border="0" width="100%" style="font-size:17px;">
            <tr>
                <td align="center" style="font-weight:bold;font-family:andalusb;">RECEIPT</td>
            </tr>
        </table>
        ';

        $address2 = '';
        if($company['cust_address2']) {
            $address2 = '
            <span>'.strtoupper($company['cust_address2']).'</span><br/>
            ';
        }

        $tbl2 ='<table border="0" width="100%" cellpadding="0" style="font-size:15px;">
                    <tr>
                        <td width="70%" style="line-height:20px;"><br/>
                            <span><b>NAME :</b> '.strtoupper($company['cust_name']).'</span><br/><br/>
                            <span><b>ADDRESS :</b><br/></span>
                            <span>'.strtoupper($company['shipping_address1']).'</span><br/>
                            <span>'.strtoupper($company['shipping_address_city']).', '.strtoupper($company['shipping_address_country_code']).' - '.$company['shipping_address_po_code'].'.</span>
                        </td>
                        <td width="30%" style="line-height:20px;"><br/>
                            <span>DATE : '.$receipt_date.'</span><br/>
                            <span>Code : '.$company['receipt_code'].'</span>
                        </td>
                    </tr>
                </table>
                ';


        $tbl3 ='<table border="1" width="100%" cellpadding="4" style="font-size:15px;">
                    <thead>
                        <tr>
                            <th width="10%">S.NO</th>
                            <th width="40%">DESCRIPTION</th>
                            <th width="10%" style="text-align:center;">QTY</th>
                            <th width="20%" style="text-align:right;">UNIT PRICE</th>
                            <th width="20%" style="text-align:right;">AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
        ';

        $count = 1;
        $discount = '';
        $Total_Amount = '';
        $SQLOrderItem = "
        SELECT item_title
               ,unit_price AS Amount
               ,qty
               ,(unit_price*qty) AS QTY_AMOUNT
        FROM order_item
        WHERE order_id = {$company['order_id']}
        ";
        $resultOrderItem = $db->sql_query($SQLOrderItem);
        $numRowsOrderItem = $db->sql_numrows($resultOrderItem);

        if($numRowsOrderItem > 0){
            $count = 1;
            $Sub_Total = '';
            while($rowOrderItem  = $db->sql_fetchrow($resultOrderItem)){
                $tbl3 = $tbl3.'<tr>
                                    <td width="10%">'.$count.'</td>
                                    <td width="40%">'.$rowOrderItem['item_title'].'</td>
                                    <td width="10%" style="text-align:center;">'.$rowOrderItem['qty'].'</td>
                                    <td width="20%" style="text-align:right;">'.$rowOrderItem['Amount'].'</td>
                                    <td width="20%" style="text-align:right;">'.$rowOrderItem['QTY_AMOUNT'].'</td>
                                </tr>';

                $Sub_Total += $rowOrderItem['Amount'];

                $count++;
            }

            $SQLDues = "
            SELECT i.invoice_code
                  ,i.invoice_amount
                  ,i.discount
                  ,irh.amount
            FROM receipt r
            LEFT JOIN (invoice_receipt_history irh) ON ( r.receipt_id = irh.receipt_id )
            LEFT JOIN (invoice i) ON ( i.invoice_id = irh.invoice_id )
            WHERE r.receipt_status != 'Cancelled'
            AND r.receipt_id = {$company['receipt_id']}
            AND r.order_id = {$company['order_id']}
            ";
            $resultDues  = $db->sql_query($SQLDues);
            $numRowsDues = $db->sql_numrows($resultDues);
            $invoice_amount = 0;
            $invoice_due_amount = 0;
            if($numRowsDues > 0){
                while ($rowDues = $db->sql_fetchrow($resultDues)) {
                    $invoice_amount += $rowDues['amount'];
                    $invoice_due_amount += $rowDues['invoice_amount'];
                }
            }

            $Total_Amount  = $invoice_amount;
            $Total_Amount_balance  = $invoice_due_amount;
            $balanceAmount = $Total_Amount_balance - $company['receipt_amount'];
            $Sub_Total     = number_format($Sub_Total, 2);
            $discount      = number_format($company['discount'], 2);
            $Total_Amount  = number_format($Total_Amount, 2);
            $ReceiptAmount = number_format($company['receipt_amount'], 2);
            $tbl3 = $tbl3.'<tr>
                                <td colspan="4" style="text-align:right;">SUB TOTAL</td>
                                <td style="text-align:right;">'.$Sub_Total.'</td>
                            </tr>
                            <tr>
                                <td colspan="4" style="text-align:right;">DISCOUNT</td>
                                <td style="text-align:right;">'.$discount.'</td>
                            </tr>
                            <tr>
                                <td colspan="4" style="text-align:right;">TOTAL AMOUNT</td>
                                <td style="text-align:right;">'.$Total_Amount.'</td>
                            </tr>
            ';

            $SQLPrevSum = "
            SELECT i.*
                ,(
                SELECT SUM(invHist.amount) AS prev_sum
                FROM invoice_receipt_history invHist
                LEFT JOIN receipt r ON (r.receipt_id = invHist.receipt_id)
                WHERE invHist.invoice_id =  i.invoice_id
                AND r.receipt_status != 'Cancelled'
                AND r.receipt_id < '{$company['receipt_id']}'
                ) as prev_inv_amount_group
            FROM invoice i
            LEFT JOIN `order` o ON (i.order_id = o.order_id)
            WHERE i.order_id = {$company['order_id']}
                AND i.status != 'Cancelled'
            ";
            $resultPrevSum  = $db->sql_query($SQLPrevSum);
            $numRowsPrevSum = $db->sql_numrows($resultPrevSum);
            $rowPrevSum     = $db->sql_fetchrow($resultPrevSum);
            $previous_paid_amount = 0;
            if($rowPrevSum['prev_inv_amount_group'] != ''){
                $previous_paid_amount = $rowPrevSum['prev_inv_amount_group'];
                $previous_paid_amount_formatted = number_format($previous_paid_amount, 2);

                $tbl3 = $tbl3.'<tr>
                                <td colspan="4" style="text-align:right;">AMOUNT PAID PREVIOUS</td>
                                <td style="text-align:right;">'.$previous_paid_amount_formatted.'</td>
                            </tr>
                ';
            }

            $balanceAmount = number_format($balanceAmount - $previous_paid_amount, 2);

            $tbl3 = $tbl3.'<tr bgColor="#BCFDFD">
                                <td colspan="4" style="text-align:right;">AMOUNT PAID NOW</td>
                                <td style="text-align:right;">'.$ReceiptAmount.'</td>
                            </tr>
                            <tr>
                                <td colspan="4" style="text-align:right;">BALANCE</td>
                                <td style="text-align:right;">'.$balanceAmount.'</td>
                            </tr>
            ';
        }

        $tbl3 = $tbl3.'</tbody></table>';

        $pdf->writeHTML($tbl1, true, false, false, false, '');
        $pdf->writeHTML($tbl2, true, false, false, false, '');
        $pdf->ln(4);
        $pdf->writeHTML($tbl3, true, false, false, false, '');
        $download_title = $company['receipt_code'] . '-Invoice.pdf';
        $pdf->Output($download_title, 'I');
    }

    /**
     *
     */
    function getReceiptPortalDisplay($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');

        $rows = "";
        $links= "";
        $sqlAppend = '';
        $exp = array('isEditable' => 1);

        $receiptRec = $fn->getRecordRowByID('receipt', 'order_id', $row['order_id']);

        $SQL = "
        SELECT DISTINCT r.receipt_id
              ,r.*
        FROM receipt r
        LEFT JOIN (invoice_receipt_history irh) ON (r.receipt_id = irh.receipt_id)
        WHERE r.order_id = {$row['order_id']}
              {$sqlAppend}
        ORDER BY r.receipt_id
        ";
        $result   = $db->sql_query($SQL);
        $numRows  = $db->sql_numrows($result);

        $total = '';
        $discount = '';
        $tdCheckBox = '';
        $count = 1;

        while ($rowReceipt = $db->sql_fetchrow($result)) {

            $urlPrint = "index.php?_topRm=finance&module=tradingsg_order&_spAction=printReceipt&receipt_code={$rowReceipt['receipt_code']}&order_id={$row['order_id']}&showHTML=0";

            $expMedia = array('condn' => " AND media_type = 'attachment' AND actual_file_name LIKE '%{$rowReceipt['receipt_code']}%'");
            $mediaRec = $fn->getRecordRowByID('media', 'record_id', $rowReceipt['receipt_id'], $expMedia);
            $mediaLink = "index.php?plugin=common_media&_spAction=saveMedia&room=pms_receipt&recordType=attachment&media_id={$mediaRec['media_id']}&showHTML=0";

            $receipt_date = $fn->getCPDate($rowReceipt['date'], 'd-m-Y');

            $cancelReceiptLink = '';
            if ($rowReceipt['receipt_status'] != 'Cancelled') {
                $cancelReceiptLink = "<a href='#' class='cancelReceipt' order_id='{$row['order_id']}' receipt_id='{$rowReceipt['receipt_id']}'>Cancel Receipt</a>";
            }
            if ($rowReceipt['receipt_status'] == 'Cancelled') {
                $cancelReceiptLink = "Cancelled";
            }

            $ReceiptAmount = number_format($rowReceipt['amount'], 2);

            $rows .= "
            <tr>
                <td>{$rowReceipt['receipt_code']}</td>
                <td>{$receipt_date}</td>
                <td>{$rowReceipt['mode_of_payment']}</td>
                <td align='right'>{$ReceiptAmount}</td>
                <td><a href='{$urlPrint}' target='_blank'>Print Receipt</a></td>
                <td>{$cancelReceiptLink}</td>
            </tr>
            ";
            if($rowReceipt['receipt_status'] == 'Paid'){
                $total += $rowReceipt['amount'];
            }
            $count++;
        }
        $total = "
            <tr style='background-color:#EAEAE8;text-align:center;font-weight:bold;'>
                <td colspan=7>Total : $total</td>
            </tr>
        ";

        $header ="
        <tr style='background-color:#EAEAE8;'>
        <th>Receipt Code</th>
        <th>Receipt Date</th>
        <th>Mode of Payment</th>
        <th>Receipt Amount</th>
        <th>Print</th>
        <th>Cancel</th>
        </tr>
        ";

        $formAction = "index.php?_topRm=finance&module=pms_order&_spAction=generateRefundForm&showHTML=0&order_id={$row['order_id']}&receipt_id={$receiptRec['receipt_id']}";

        $text = "
        <h2>Receipt(s)</h2>
        <tr class=''>
        <td>
            <div id='' class='linkPortalWrapper pms_company__pms_orderLink'>
                <form id='orderItemPrint' class='' method='post'
                action='{$formAction}'>
                <table class='thinlist'>
                    {$header}
                    {$rows}
                </table>
                <input type='hidden' name='order_id' value='{$row['order_id']}' />
                <input type='hidden' name='receipt_id' value='{$receiptRec['receipt_id']}' />
                </form>
            </div>
        </td>
        </tr>
        ";

        return $text;
    }


    /**
     */
    function getInvoicePortalDisplayDetail($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $rows  = "";
        $rowsPvt  = "";
        $links = "";
        $sqlAppend = "";

        $status = $fn->getReqParam('status');

        if ($status) {
            $sqlAppend .= "AND i.status = '{$status}'";
        }

        $_SESSION['selectedInvoiceIds'] = array();
        $exp = array('isEditable' => 1);

        $SQL = "
        SELECT i.*
            ,(
            SELECT GROUP_CONCAT(r.receipt_code ORDER BY r.receipt_code SEPARATOR ', ')
            FROM receipt r, invoice_receipt_history invrecpt
            WHERE r.receipt_id = invrecpt.receipt_id
            AND i.invoice_id = invrecpt.invoice_id
            ) AS receipt_codes_history
            {$sqlAppend}
        FROM invoice i
        WHERE i.order_id = {$row['order_id']}
          AND i.invoice_type = 'Client'
        ORDER BY i.invoice_id
        ";

        $result   = $db->sql_query($SQL);
        $discount = '';
        $tdCheckBox = '';
        $checkBoxStatus = '';
        $count = 1;
        $invoice_code = '';
        $add_registration_fee = '';
        $invoice_hist_amount  = '';
        $total = 0;

        while ($rowInvoice = $db->sql_fetchrow($result)) {
            $gstvalue = '';
            $gsttaxvalue = '';
            $pfvalue = '';
            $frieghtValue = '';
            $selectedValuePaid   = '';
            $selectedValueDue    = '';
            $selectedValueCancel = '';

            $urlPrint  = "index.php?_topRm=finance&module=tradingsg_order&_spAction=printInvoiceRecord&order_id={$rowInvoice['order_id']}&showHTML=0";
            $urlPrintDuplicate  = "index.php?_topRm=finance&module=tradingsg_order&_spAction=printInvoiceRecord&order_id={$rowInvoice['order_id']}&duplicate=1&showHTML=0";

            $printTriplicateInvoice = "";
            if ($cpCfg['cp.serialKeyActive'] == "SULB-DHEO-0R6K-59CL" || $cpCfg['cp.serialKeyActive'] == "YODX0-9DT58-VCZ5W-A8XXB") {
                $urlPrintTriplicate  = "index.php?_topRm=finance&module=tradingsg_order&_spAction=printInvoiceRecord&order_id={$rowInvoice['order_id']}&triplicate=1&showHTML=0";
                $printTriplicateInvoice = " / <a href='{$urlPrintTriplicate}' target='_blank'>Print Invoice Triplicate</a>";
            }
            
            $expMedia = array('condn' => " AND media_type = 'attachment' AND actual_file_name LIKE '%{$rowInvoice['invoice_code']}%'");
            $mediaRec = $fn->getRecordRowByID('media', 'record_id', $rowInvoice['invoice_id'], $expMedia);
            $mediaLink = "index.php?plugin=common_media&_spAction=saveMedia&room=tradingsg_invoice&recordType=attachment&media_id={$mediaRec['media_id']}&showHTML=0";

            if($rowInvoice['status'] != 'Cancelled'){
                $total += $rowInvoice['invoice_amount'];
            }

            //if($invoice_code == '' || $invoice_code != $rowInvoice['invoice_code']){

                /* Half way done. Need to do submit functioanlity. Move $editRow = ''; from below to this comment line */
                $editRow = '<td></td>';
                if ($rowInvoice['status'] == 'Due'
                 || $rowInvoice['status'] == ''
                 || $rowInvoice['status'] == 'Partial Payment'
                ) {
                    $editURL = "index.php?_topRm=finance&module=tradingsg_order&_spAction=editInvoiceForm&showHTML=0&invoice_id={$rowInvoice['invoice_id']}&order_id={$row['order_id']}";
                    $editRow = "<td><a href='{$editURL}' id='editInvoice'>Edit</a></td>";
                }

                $cancelInvoiceLink = '';
                $salesReturn = '';
                if ($rowInvoice['status'] != 'Cancelled'){
                    $cancelInvoiceLink = "<a href='#' class='cancelInvoice' invoice_code='{$rowInvoice['invoice_code']}' invoice_id='{$rowInvoice['invoice_id']}'>Cancel Invoice</a>";

                    $formActionSalesReturn = "index.php?module=tradingsg_order&_spAction=generateSalesReturnForm&invoice_id={$rowInvoice['invoice_id']}&order_id={$row['order_id']}&showHTML=0";

                    $salesReturn ="
                    <a href='{$formActionSalesReturn}' id='generateSalesReturn'>Sales Return</a>
                    ";
                }

                $invoice_date = $fn->getCPDate($rowInvoice['invoice_date'], 'd-m-Y');
                $totalvalueRounded = number_format($total, 2);

                if($rowInvoice['invoice_code'] == ""){
                    $invoice_code = 'INV - '.$rowInvoice['invoice_id']; 
                }
                else{
                    $invoice_code = $rowInvoice['invoice_code'];
                }

                $rows .= "
                <tr>
                    <td>{$invoice_code}</td>
                    <td>{$rowInvoice['status']}</td>
                    <td>{$invoice_date}</td>
                    <td align='right'>{$totalvalueRounded}</td>
                    <td><a href='{$urlPrint}' target='_blank'>Print Invoice</a> / <a href='{$urlPrintDuplicate}' target='_blank'>Print Invoice Duplicate</a>{$printTriplicateInvoice}</td>
                    <td>{$cancelInvoiceLink}</td>
                    {$editRow}
                    <td>{$salesReturn}</td>
                </tr>
                ";
            }

            //$invoice_code = $rowInvoice['invoice_code'];
        //}

        $header ="
        <tr style='background-color:#EAEAE8;'>
        <th>Invoice Code</th>
        <th>Status</th>
        <th>Invoice Date</th>
        <th class='txtRight'>Amount</th>
        <th>Print</th>
        <th>Cancel</th>
        <th>Edit</th>
        <th>Sales Return</th>
        </tr>
        ";

        $text = "
        <table class='thinlist'>
            {$header}
            {$rows}
            {$rowsPvt}
        </table>
        ";

        return $text;
    }

    /**
     *
     */

     function getCancelOrderNotes() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');

        $order_id = $fn->getReqParam('order_id');
        $formAction = "index.php?_topRm=pos&module=tradingsg_order&_spAction=CancelOrderNotesSubmit&showHTML=0";

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
    function getQuickSearch() {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $ln = Zend_Registry::get('ln');

        $creation_date1   = $fn->getReqParam('creation_date_1');
        $creation_date2   = $fn->getReqParam('creation_date_2');
        $order_status     = $fn->getReqParam('order_status');
        $shipment_status  = $fn->getReqParam('shipment_status');
        $gst_status       = $fn->getReqParam('gst_status');
        $shipping_address_country_code = $fn->getReqParam('shipping_address_country_code');

        $dirText = "";

        if ($cpCfg['cp.hasDirectoryMg'] == 1){
            $business_id = $fn->getReqParam('business_id');
            $business_contact_id = $fn->getReqParam('business_contact_id');

            $SQLBusiness = "
            SELECT b.business_id
                    ,b.business_name
            FROM business b
            ORDER BY b.business_name
            ";

            $SQLBusinessContact = "
            SELECT bc.business_contact_id
                    ,CONCAT_WS(' ', bc.first_name, bc.last_name) AS contact_name
            FROM business_contact bc
            ORDER BY contact_name
            ";

            $dirText = "
            <td class='fieldValue'>
                <select name='business_id'>
                    <option value=''>Business</option>
                    {$dbUtil->getDropDownFromSQLCols2($db, $SQLBusiness, $business_id)}
                </select>
            </td>

            <td class='fieldValue'>
                <select name='business_contact_id'>
                    <option value=''>Contact</option>
                    {$dbUtil->getDropDownFromSQLCols2($db, $SQLBusinessContact, $business_contact_id)}
                </select>
            </td>
            ";
        }

        $orgText = "";
        if ($cpCfg['m.ecommerce.order.showOrganization']) {
            $organization_id = $fn->getReqParam('organization_id');

            $SQLOrg = "
            SELECT o.organization_id
                  ,o.name
            FROM organization o
            ORDER BY o.name
            ";

            $orgText = "
            <td class='fieldValue'>
                <select name='organization_id'>
                    <option value=''>Organization</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $SQLOrg, $organization_id)}
                </select>
            </td>
            ";
        }

        $shipmentStatus = "";
        if ($cpCfg['m.ecommerce.order.showShipmentStatus']) {
            $shipmentStatus = "
            <td class='fieldValue'>
                <select name='shipment_status'>
                    <option value=''>Shipment Status</option>
                    {$cpUtil->getDropDown1($cpCfg['m.ecommerce.order.shipmentStatusArr'], $shipment_status)}
                </select>
            </td>
            ";
        }

        $orderGstStatus = "";
        if($cpCfg['showGstOnOff'] == 1){
            $gst_status_array = array('GST', 'Non GST');
            $orderGstStatus = "
            <td class='fieldValue'>
                <select name='gst_status'>
                    <option value=''>GST Status</option>
                    {$cpUtil->getDropDown1($gst_status_array, $gst_status)}
                </select>
            </td>
            ";
        }

        $text = "
        {$dirText}
        {$orgText}
        <td>
            {$formObj->getDateRangeRow('Creation Date:', 'creation_date', $creation_date1, $creation_date2)}
        </td>
        {$orderGstStatus}
        <td class='fieldValue'>
            <select name='order_status'>
                <option value=''>Status</option>
                {$cpUtil->getDropDown1($cpCfg['m.ecommerce.order.statusArr'], $order_status)}
            </select>
        </td>
        {$shipmentStatus}
        ";


        return $text;
    }

    /**
     *
     */
     function getGenerateSalesReturnForm() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        unset($_SESSION['selectedOrderItemIds']);

        $rows = '';

        $invoice_id = $fn->getReqParam('invoice_id');
        $order_id = $fn->getReqParam('order_id');
        $date     = $fn->getCurrentDate();
        $qty_balance = '';

        $sqlInvoiceItem = "
        SELECT ii.*
              ,p.carton_no
              ,o.record_type
        FROM invoice_item ii
        LEFT JOIN (product p) ON (p.product_id = ii.record_id)
        LEFT JOIN (`invoice` i) ON (i.invoice_id = ii.invoice_id)
        LEFT JOIN (`order` o) ON (o.order_id = i.order_id)
        WHERE ii.invoice_id = {$invoice_id}
        ";
        $resultInvoiceItem = $db->sql_query($sqlInvoiceItem);
        while ($rowII = $db->sql_fetchrow($resultInvoiceItem)) {
            $sqlQty = "
            SELECT SUM(srh.qty_return) AS qty_return
            FROM sales_return_history srh
            WHERE srh.invoice_id = {$invoice_id}
             AND srh.invoice_item_id = {$rowII['invoice_item_id']}
             AND srh.status IS NULL
            ";
            $resultQty = $db->sql_query($sqlQty);
            $rowQty = $db->sql_fetchrow($resultQty);

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

            $inputRow = '';
            $qtyRow = '';
            $qty_balance = $rowII['qty'] - $rowQty['qty_return'];
            if ($rowQty['qty_return'] != $rowII['qty']) {
                $pfx = $rowII['invoice_item_id'] . '_' ;
                $inputRow = "<input class='invoiceItemId' type='checkbox' name='invoiceItemId[]' value='{$rowII['invoice_item_id']}'>";
                $qtyRow = "<input type='text' value='{$qty_balance}' id='fld_qty' class='text w50' name='{$pfx}qty_return'>";
            }

            $rows .= "
            <tr invoiceRowItem[] = {$rowII['invoice_item_id']}>
                <td>
                    {$inputRow}
                </td>
                <td>{$rowII['item_title']}</td>
                <td class='sellingPrice txtRight'>{$product_Price}</td>
                <td class=''>{$rowII['qty']}</td>
                <td class=''>{$qtyRow}</td>
                <td class=''>{$rowQty['qty_return']}</td>
            </tr>
            ";
        }

        $formAction = "index.php?_topRm=finance&module=tradingsg_order&_spAction=generateSalesReturnFormSubmit&showHTML=0";

        $expNoEdit = array('isEditable' => 0);

        $text = "
        <form id='portalForm' class='yform columnar invoiceForm' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Amount', 'invoice_amount', '', $expNoEdit)}
            {$formObj->getDateRow('Date', 'sales_return_date', $date)}
            {$formObj->getTARow('Notes', 'notes')}
            {$formObj->getTBRow('Issued By', 'staff_id', $_SESSION['userFullName'], $expNoEdit)}
            <div class='button updateSalesReturnTotal'>
                <a href='#'>Update Total</a>
            </div>
            <div class=''>{$formObj->getTBRow('', "error_box", '', $expNoEdit)}</div>
            <table class='thinlist room-order-table'>
                <thead>
                    <th class='click-all-top'>
                        <a href='#' class='check-all'>
                            <img src='{$cpCfg['cp.commonImagesPathAlias']}icons/checkbox_checked.gif'>
                        </a>
                        <a href='#' class='uncheck-all'>
                            <img src='{$cpCfg['cp.commonImagesPathAlias']}icons/checkbox_unchecked.gif'>
                        </a>
                    </th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th class=''>Qty (Sales Return)</th>
                    <th>Qty Returned</th>
                </thead>

                <tbody>
                    {$rows}
                </tbody>
            </table>

            <input type='hidden' name='invoice_id' value='{$invoice_id}' />
            <input type='hidden' name='order_id' value='{$order_id}' />
        </form>
        ";

        return $text;
    }
    /**
     */
    function getSalesReturnDisplay($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $formAction = '';

        $text = "
        <tr class=''>
        <td>
            <div id='' class='invoiceDisplay'>
                <h2>Sales Return(s)</h2>
                <form id='orderItemPrint' class='' method='post' action='{$formAction}'>
                    <div id='invoicePortalOuter'>
                        {$this->getSalesReturnDisplayDetail($row)}
                    </div>
                </form>
            </div>
        </td>
        </tr>
        ";

        return $text;
    }

    /**
     */
    function getSalesReturnDisplayDetail($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $rows  = "";

        $_SESSION['selectedInvoiceIds'] = array();
        $exp = array('isEditable' => 1);

        $SQL = "
        SELECT srh.*
              ,i.invoice_code
              ,(SELECT SUM(srhh.price * srhh.qty_return) FROM sales_return_history srhh
                WHERE srhh.invoice_id = i.invoice_id
                AND srhh.order_id = {$row['order_id']}
                AND srhh.date = srh.date
                AND srhh.status IS NULL
                ) AS sales_return_amount
        FROM sales_return_history srh
        LEFT JOIN (invoice i) ON (i.invoice_id = srh.invoice_id)
        WHERE srh.order_id = {$row['order_id']}
          AND srh.status IS NULL
        ORDER BY i.invoice_id
        ";
        $result   = $db->sql_query($SQL);

        $invoice_code = '';
        $datechk = '';
        while ($rowInvoice = $db->sql_fetchrow($result)) {
            $total = '';

            $urlPrint  = "index.php?_topRm=finance&module=tradingsg_order&_spAction=printSalesReturn&invoice_code={$rowInvoice['invoice_code']}&date={$rowInvoice['date']}&sales_return_history_id={$rowInvoice['sales_return_history_id']}&showHTML=0";

            $date = $fn->getCPDate($rowInvoice['date'], 'd-m-Y');
            //$total += $rowInvoice['price'] * $rowInvoice['qty_return'];
            $total += $rowInvoice['sales_return_amount'];
            $totalvalueRounded = number_format(round($total),2);

            if($invoice_code != $rowInvoice['invoice_code'] || $datechk != $rowInvoice['date']){
                $srStatus = '';
                if($rowInvoice['status'] == 'Cancelled'){
                    $srStatus = '(' .$rowInvoice['status']. ')';
                }
                $rows .= "
                <tr>
                    <td>{$rowInvoice['invoice_code']} {$srStatus}</td>
                    <td>{$date}</td>
                    <td align='right'>$totalvalueRounded</td>
                    <td><a href='{$urlPrint}' target='_blank'>Print Sales Return</a></td>
                </tr>
                ";
                $invoice_code = $rowInvoice['invoice_code'];
                $datechk = $rowInvoice['date'];
            }
        }

        $header ="
        <tr style='background-color:#EAEAE8;'>
        <th>Invoice Code</th>
        <th>Sales Return Date</th>
        <th>Amount</th>
        <th>Print</th>
        </tr>
        ";

        $text = "
        <table class='thinlist'>
            {$header}
            {$rows}
        </table>
        ";

        return $text;
    }

    /**
     *
     */
    function getPrintInvoiceRecordSurgicalShop($order_id) {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfootSaiSurgical.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('USS');
        $pdf->SetSubject('Print Link');
        $pdf->SetTitle('Print Link');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        //$pdf->setPrintFooter(false);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER,10);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);


        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 6);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        //$pdf->setPrintFooter(false);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
        $pdf->AddPage('L');

        //$order_id = $fn->getReqParam('order_id');

        $subSqlForPercentSum = "
        SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2)) as discount_sum
        FROM order_item oi
        WHERE oi.order_id = {$order_id}
          AND oi.discount_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForPercentSum = "
            SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2))
            FROM order_item oi
            WHERE oi.order_id = {$order_id}
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
        WHERE oi.order_id = {$order_id}
          AND oi.discount_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlForValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForValueSum ="
            SELECT SUM(round(oi.discount_amount  * oi.qty,2))
            FROM order_item oi
            WHERE oi.order_id = {$order_id}
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
              ,p.pack_size
              ,p.product_code
              ,CONCAT_WS('::', p.carton_no, p.batch_no, p.model) code
              ,o.order_date
              ,o.discount
              ,o.order_id
              ,o.bill_number
              ,o.gst_status
              ,o.shipping_charge
              ,i.vat AS invoice_vat
              ,i.invoice_date
              ,DATE_FORMAT(i.invoice_date, '%d-%m-%Y')AS invoice_creation_date
              ,i.invoice_code_vat
              ,i.invoice_code
              ,i.invoice_id
              ,o.cust_company_name
              ,o.company_id
              ,o.cust_phone
              ,o.cust_email
              ,o.cust_address1
              ,o.cust_address2
              ,o.cust_address_city
              ,o.cust_address_state
              ,o.cust_address_country_code
              ,o.cust_gst_no
              ,o.shipping_first_name
              ,o.shipping_phone
              ,o.shipping_email
              ,o.shipping_address1
              ,o.shipping_address2
              ,o.shipping_address_city
              ,o.shipping_address_state
              ,o.shipping_address_country_code
              ,o.shipping_gst_no
              ,o.invoice_terms
              ,o.memo
              ,o.delivery_challan_no
              ,o.delivery_date
              ,o.igst_show
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
        WHERE o.order_id = '{$order_id}'
        ORDER BY p.tag_no
        ";
        $result = $db->sql_query($SQL);
        $result2 = $db->sql_query($SQL);
        $company = $db->sql_fetchrow($result2);
        //============================================================================= //

        $pdf->SetFont('helvetica','', 9);

        $today = date("d-m-Y");

        if($company['order_id'] < 10){
            $orderNo = '0000' . $company['order_id'];
        }
        else if($company['order_id'] <= 99){
            $orderNo = '000' . $company['order_id'];
        }
        else if($company['order_id'] <= 999){
            $orderNo = '00' . $company['order_id'];
        }
        else if($company['order_id'] <= 9999){
            $orderNo = '0' . $company['order_id'];
        }
        else{
            $orderNo = $company['order_id'];
        }

        if($company['invoice_code'] == ""){
            $invoice_code = 'INV - '.$company['invoice_id']; 
        }
        else{
            $invoice_code = $company['invoice_code'];
        }

        $tbl1 ='
        <table border="0" width="100%" cellpadding="4">
            <tr>
                <td width="48%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:10px;font-weight:bold;">Billed To : '.$company['cust_company_name'].'</td>
                <td width="4%"  style="border-right:1px solid #000000;font-weight:bold;"></td>
                <td width="26%" align="left" style="border-top:1px solid #000000;font-size:10px;font-weight:bold;">&nbsp;&nbsp;INVOICE NO : '.$invoice_code.'</td>
                <td width="22%" align="left" style="border-right:1px solid #000000;border-top:1px solid #000000;font-size:10px;font-weight:bold;">DATE : '.$company['invoice_creation_date'].'</td>
            </tr>
            <tr>
                <td width="48%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-bottom:1px solid #000000;font-size:10px;font-weight:bold;">'.$company['cust_address1'].' '.$company['cust_address2'].' '.$company['cust_address_city'].' '.$company['cust_address_state'].' <br/> GST No: '.$company['cust_gst_no'].'</td>
                <td width="4%"  style="border-right:1px solid #000000;font-size:10px;font-weight:bold;"></td>
                <td width="26%" style="font-size:10px;border-bottom:1px solid #000000;font-weight:bold;">&nbsp;&nbsp;D.C. NO. : '.$company['delivery_challan_no'].'<br><br>&nbsp;&nbsp;P.O NO. : '.$company['memo'].'</td>
                <td width="22%" style="border-right:1px solid #000000;border-bottom:1px solid #000000;font-size:10px;font-weight:bold;">DATE : '.$fn->getCPDate($company['delivery_date'], 'd-m-Y').'</td>
            </tr>
        </table>
        ';

        $tbl2 ='
        <table border="0" width="100%" cellpadding="3">
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:12px;font-weight:bold;"><i>Billed to :</i></td>
                <td style="border-right:1px solid #000000;border-top:1px solid #000000;font-size:12px;font-weight:bold;"><i>Shipped to :</i></td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:12px;font-weight:bold;">'.$company['cust_company_name'].'</td>
                <td style="border-right:1px solid #000000;font-size:12px;font-weight:bold;">'.$company['shipping_first_name'].'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:12px;font-weight:bold;">'.$company['cust_address1'].'</td>
                <td style="border-right:1px solid #000000;font-size:12px;font-weight:bold;">'.$company['shipping_address1'].'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:12px;">'.$company['cust_address2'].''.$company['cust_address_city'].' '.$company['cust_address_state'].'</td>
                <td style="border-right:1px solid #000000;font-size:12px;font-weight:bold;">'.$company['shipping_address2'].''.$company['shipping_address_city'].' '.$company['shipping_address_state'].'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:12px;">Party Email Id : '.$company['cust_email'].'</td>
                <td style="border-right:1px solid #000000;font-size:12px;font-weight:bold;">Party Email Id : '.$company['shipping_email'].'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:12px;">Party Mobile No : '.$company['cust_phone'].'</td>
                <td style="border-right:1px solid #000000;font-size:12px;font-weight:bold;">Party Mobile No : '.$company['shipping_phone'].'</td>
            </tr>
            <tr>
                <td style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;font-size:12px;">GST IN / UIN :'.$company['cust_gst_no'].'</td>
                <td style="border-right:1px solid #000000;border-bottom:1px solid #000000;font-size:12px;font-weight:bold;">GST IN / UIN :'.$company['shipping_gst_no'].'</td>
            </tr>
        </table>
        ';

        if($company['gst_status'] == "ON"){

            if($company['igst_show'] == "1"){
                $tbl3 ='<table border="0" nobr="true" width="100%" cellpadding="3">
                            <thead>
                                <tr>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">S.NO</th>
                                    <th width="7%"  style="border:1px solid #000000;font-weight:bold;" align="center">PRODUCT CODE</th>
                                    <th width="28%" style="border:1px solid #000000;font-weight:bold;" align="left">DESCRIPTION</th>
                                    <th width="6%"  style="border:1px solid #000000;font-weight:bold;" align="center">HSN CODE</th>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">PACK SIZE</th>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">QTY</th>
                                    <th width="7%"  style="border:1px solid #000000;font-weight:bold;" align="right">PRICE</th>
                                    <th width="6%"  style="border:1px solid #000000;font-weight:bold;" align="right">DISC.</th>
                                    <th width="7%"  style="border:1px solid #000000;font-weight:bold;" align="right">TAXABLE VALUE</th>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">IGST RATE</th>
                                    <th width="7%"  style="border:1px solid #000000;font-weight:bold;" align="right">IGST AMOUNT</th>
                                    <th width="12%" style="border:1px solid #000000;font-weight:bold;" align="right">AMOUNT</th>
                                </tr>
                            </thead>
                ';
            }else{
                $tbl3 ='<table border="0" nobr="true" width="100%" cellpadding="3">
                            <thead>
                                <tr>
                                    <th width="4%"  style="border:1px solid #000000;font-weight:bold;" align="center">S.NO</th>
                                    <th width="7%"  style="border:1px solid #000000;font-weight:bold;" align="center">PRODUCT CODE</th>
                                    <th width="23%" style="border:1px solid #000000;font-weight:bold;" align="left">DESCRIPTION</th>
                                    <th width="6%"  style="border:1px solid #000000;font-weight:bold;" align="center">HSN CODE</th>
                                    <th width="6%"  style="border:1px solid #000000;font-weight:bold;" align="center">PACK SIZE</th>
                                    <th width="4%"  style="border:1px solid #000000;font-weight:bold;" align="center">QTY</th>
                                    <th width="7%"  style="border:1px solid #000000;font-weight:bold;" align="right">PRICE</th>
                                    <th width="6%"  style="border:1px solid #000000;font-weight:bold;" align="right">DISC.</th>
                                    <th width="6%"  style="border:1px solid #000000;font-weight:bold;" align="right">TAXABLE VALUE</th>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">CGST RATE</th>
                                    <th width="6%"  style="border:1px solid #000000;font-weight:bold;" align="right">CGST AMOUNT</th>
                                    <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">SGST RATE</th>
                                    <th width="6%"  style="border:1px solid #000000;font-weight:bold;" align="right">SGST AMOUNT</th>
                                    <th width="9%" style="border:1px solid #000000;font-weight:bold;" align="right">AMOUNT</th>
                                </tr>
                            </thead>
                ';
            }
        }
        //5
        else{
            $tbl3 ='<table border="0" nobr="true" width="100%" cellpadding="3">
                        <thead>
                            <tr>
                                <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">S.NO</th>
                                <th width="7%"  style="border:1px solid #000000;font-weight:bold;" align="center">PRODUCT CODE</th>
                                <th width="33%" style="border:1px solid #000000;font-weight:bold;" align="left">PRODUCTS</th>
                                <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="center">HSN CODE</th>
                                <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="center">PACK SIZE</th>
                                <th width="6%"  style="border:1px solid #000000;font-weight:bold;" align="center">QTY</th>
                                <th width="10%" style="border:1px solid #000000;font-weight:bold;" align="right">PRICE</th>
                                <th width="8%"  style="border:1px solid #000000;font-weight:bold;" align="right">DISCOUNT</th>
                                <th width="15%" style="border:1px solid #000000;font-weight:bold;" align="right">AMOUNT</th>
                            </tr>
                        </thead>
            ';
        }

        $sub_total = 0;
        $count = 1;
        $total_qty = 0;
        $total_unit = 0;
        $discount = 0;
        $subTotal = 0;
        $discountValueTotal = 0;
        $Overallsubtotalwithoutdiscount = 0;
        $savedAmount = 0;
        $gstValue = 0;
        $gst_total_Amount = 0;
        $total_vat_Amount_total = 0;
        $total_vat_Sum_Half = 0;
        $total_vat_Sum = 0;
        $total_total_tax = 0;
        $total_tax = '';
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
            $totalPerQty = $row['qty'] * ($row['unit_price'] - $discount_value_for_one_qty);

            $SQLTax = "
            SELECT  gst
                    ,order_id
                    ,SUM((unit_price * qty) - ((unit_price * discount_percentage) /100 * qty)) AS qty_amount
            FROM `order_item` 
            WHERE order_item_id = '{$row['order_item_id']}'
            AND gst > 0
            ";
            $resultTax  = $db->sql_query($SQLTax);
            $rowTax     = $db->sql_fetchrow($resultTax);

            $totalVatSum = 0;

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
                $vat_Sum_Half   = $vat_Sum / 2;

                $totalVatSum += $vat_Sum;

                $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);

            /*if($row['gst_status'] == "ON"){
                $gstValue = $total * $row['gst'] / 100;
                $total    = $total + $gstValue;
            }*/

            if($row['gst_status'] == "ON"){
                $total = $totalPerQty + $vat_Sum_Half + $vat_Sum_Half;
            }else{
                $totalPerQty = 0;
                $total = $totalPerQty;
            }
            
            $subTotal += $total;
            $Overallsubtotalwithoutdiscount += $subtotalwithoutdiscount;
            $discount = $row['discount'];
            $discount_percentage_amount_sum = $row['discount_percentage_amount_sum'] + $discount;
            //$discount_percentage_amount_sum = $discount;
            $savedAmount = $discount_percentage_amount_sum;

            $total_qty  += $row['qty'];
            $total_unit  = $row['unit'];

            $row['qty'] = number_format($row['qty'], 0, '.', '');

            $discountPercentDis = number_format($discountValue, 2);
            if($row['discount_type'] == '%'){
                $discountPercentDis = number_format($row['discount_percentage'], 2).'%';
            }

            if($row['gst_status'] == "ON"){
                if($company['igst_show'] == "1"){
                    $tbl3 = $tbl3.'<tr>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$count.'</td>
                                        <td width="7%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['product_code'].'</td>
                                        <td width="28%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="left">'.$row['item_title'].'</td>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['hsn_sac'].'</td>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['pack_size'].' '.$row['unit'].'</td>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['qty'].'</td>
                                        <td width="7%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($row['unit_price'], 2).'</td>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$discountPercentDis.'</td>
                                        <td width="7%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($totalPerQty, 2).'</td>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$vatPercent.' %</td>
                                        <td width="7%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($vat_Sum, 2).'</td>
                                        <td width="12%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($total, 2).'</td>
                                    </tr>
                                    ';
                }

                else{
                    $tbl3 = $tbl3.'<tr>
                                        <td width="4%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$count.'</td>
                                        <td width="7%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['product_code'].'</td>
                                        <td width="23%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="left">'.$row['item_title'].'</td>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['hsn_sac'].'</td>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['pack_size'].' '.$row['unit'].'</td>
                                        <td width="4%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['qty'].'</td>
                                        <td width="7%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($row['unit_price'], 2).'</td>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$discountPercentDis.'</td>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($totalPerQty, 2).'</td>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$vatPercentHalf.' %</td>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($vat_Sum_Half, 2).'</td>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$vatPercentHalf.' %</td>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($vat_Sum_Half, 2).'</td>
                                        <td width="9%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($total, 2).'</td>
                                    </tr>
                                    ';
                }
            }
            else{
                $tbl3 = $tbl3.'<tr>
                                    <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$count.'</td>
                                    <td width="7%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['product_code'].'</td>
                                    <td width="33%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="left">'.$row['item_title'].'</td>
                                    <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['hsn_sac'].'</td>
                                    <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['pack_size'].' '.$row['unit'].'</td>
                                    <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['qty'].'</td>
                                    <td width="10%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($row['unit_price'], 2).'</td>
                                    <td width="8%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$discountPercentDis.'</td>
                                    <td width="15%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.number_format($total, 2).'</td>
                                </tr>
                                ';
            }

            $count++;
        }

        $totalAmount     = $subTotal - $discount;
        $discountOverall = $discountValueTotal;
        $shipping_charge = $company['shipping_charge'];

        if($shipping_charge == ""){
            $shipping_charge = 0;
        }

        if($shipping_charge != "" && $shipping_charge > 0){
            $totalAmount = $totalAmount + $shipping_charge;
        }

        $roundOff           = round($totalAmount) - $totalAmount;
        $totalAmount        = round($totalAmount);
        $sub_total_in_words = $fn->getConvertNumber($totalAmount .'.00');

        if($company['gst_status'] == "ON"){

            $tbl4 = '<table cellpadding="4" border="0" width="60%">';

            if($company['igst_show'] == "1"){
                $tbl4 = $tbl4.'
                    <tr>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Tax Rate</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Taxable</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">IGST</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Total Tax</td>
                    </tr>
                ';
            }else{
                $tbl4 = $tbl4.'
                    <tr>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Tax Rate</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Taxable</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">CGST</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">SGST</td>
                        <td style="border-bottom:1px solid #000000;font-weight:bold;" align="right">Total Tax</td>
                    </tr>
                ';
            }

            $SQLTax = "
            SELECT  oi.gst
                    ,oi.order_id
                    ,p.hsn AS hsn_sac
                    ,SUM((oi.unit_price * oi.qty) - (((oi.unit_price * oi.discount_percentage) /100 ) * oi.qty)) AS qty_amount
            FROM `order_item` oi
            LEFT JOIN product p ON (p.product_id = oi.record_id)
            WHERE oi.order_id = '{$order_id}'
            AND oi.gst > 0
            GROUP BY oi.gst
            ORDER BY oi.gst ASC
            ";
            $resultTax  = $db->sql_query($SQLTax);

            $totalVatSum = 0;
            $counter = 1;
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
                $vat_Sum_Half   = $vat_Sum / 2;

                $totalVatSum += $vat_Sum;

                $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);
                $total_tax = $vat_Sum_Half + $vat_Sum_Half;
                if($company['igst_show'] == "1"){
                    $tbl4 = $tbl4.'
                    <tr>
                        <td align="right">'.$rowTax['gst'].' %</td>
                        <td align="right">'.number_format($vat_Amount_total, 2).'</td>
                        <td align="right">'.number_format($vat_Sum, 2).'</td>
                        <td align="right">'.number_format($total_tax, 2).'</td>
                    </tr>
                    ';
                }else{
                    $tbl4 = $tbl4.'
                    <tr>
                        <td align="right">'.$rowTax['gst'].' %</td>
                        <td align="right">'.number_format($vat_Amount_total, 2).'</td>
                        <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
                        <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
                        <td align="right">'.number_format($total_tax, 2).'</td>
                    </tr>
                    ';
                }

                $counter++;
            }   
        }

        $emptyRow = '';
        if($company['gst_status'] == "ON"){
            if($company['igst_show'] == "1"){
                if($count <= 8){
                    $countCheck = 8 - $count;
                }
                else{
                    $countCheck = 0;
                }
            }else{
               if($count <= 8){
                    $countCheck = 8 - $count;
                }
                else{
                    $countCheck = 0;
                } 
            }
        }else{
            if($count <= 8){
                $countCheck = 8 - $count;
            }
            else{
                $countCheck = 0;
            }
        }

        for($ic = 1; $ic <= $countCheck; $ic++){
            if($company['gst_status'] == "ON"){
                if($company['igst_show'] == "1"){
                    $emptyRow .= '
                    <tr>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    </tr>
                    ';
                }else{
                    $emptyRow .= '
                    <tr>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    </tr>
                    ';
                }
            }
            else{
                $emptyRow .= '
                    <tr>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                        <td style="border-left:1px solid #000000;border-right:1px solid #000000;"></td>
                    </tr>
                    ';
            }
        }

        if($company['gst_status'] == "ON"){

            if($company['igst_show'] == "1"){
                $shipping_charge_row = "";
                if($shipping_charge != "" && $shipping_charge > 0){
                    $shipping_charge_row = '
                    <tr>
                        <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="11">SHIPPING CHARGE</td>
                        <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($shipping_charge,2).'</td>
                    </tr>
                    ';
                }

                $tbl3 = $tbl3.'
                '.$emptyRow.'
                <tr>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-left:1px solid #000000;" colspan="11">SUB TOTAL</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-right:1px solid #000000;">'.number_format($subTotal,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="11">DISCOUNT</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($savedAmount,2).'</td>
                </tr>
                '.$shipping_charge_row.'
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="11">IGST AMOUNT</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($totalVatSum,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="11">ROUND OFF</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($roundOff, 2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;font-weight:bold;font-size:11px;" colspan="11">TOTAL</td>
                    <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.number_format($totalAmount,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;" colspan="12">RUPEES: '.strtoupper($sub_total_in_words).'</td>
                </tr>
                </table>
                ';
            }
            else{
                $shipping_charge_row = "";
                if($shipping_charge != "" && $shipping_charge > 0){
                    $shipping_charge_row = '
                    <tr>
                        <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="13">SHIPPING CHARGE</td>
                        <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($shipping_charge,2).'</td>
                    </tr>
                    ';
                }

                $totalVatSumGst = $totalVatSum / 2 ;

                $tbl3 = $tbl3.'
                '.$emptyRow.'
                <tr>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-left:1px solid #000000;" colspan="13">SUB TOTAL</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-right:1px solid #000000;">'.number_format($subTotal,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="13">DISCOUNT</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($savedAmount,2).'</td>
                </tr>
                '.$shipping_charge_row.'
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="13">CGST AMOUNT</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($totalVatSumGst,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="13">SGST AMOUNT</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($totalVatSumGst,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="13">ROUND OFF</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($roundOff,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;font-weight:bold;font-size:11px;" colspan="13">TOTAL</td>
                    <td align="right" style="border-bottom:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-top:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.number_format($totalAmount,2).'</td>
                </tr>
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;" colspan="14">RUPEES: '.strtoupper($sub_total_in_words).'</td>
                </tr>
                </table>
                ';
            }
        }

        else{

            $shipping_charge_row = "";
            if($shipping_charge != "" && $shipping_charge > 0){
                $shipping_charge_row = '
                <tr>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="8">SHIPPING CHARGE</td>
                    <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($shipping_charge,2).'</td>
                </tr>
                ';    
            }

            $tbl3 = $tbl3.'
            '.$emptyRow.'
            <tr>
                <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-left:1px solid #000000;" colspan="8">SUB TOTAL</td>
                <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-left:1px solid #000000;border-right:1px solid #000000;">'.number_format($subTotal,2).'</td>
            </tr>
            <tr>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="8">DISCOUNT</td>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($savedAmount,2).'</td>
            </tr>
            '.$shipping_charge_row.'
            <tr>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;" colspan="8">ROUND OFF</td>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-right:1px solid #000000;">'.number_format($roundOff,2).'</td>
            </tr>
            <tr>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-top:1px solid #000000;border-bottom:1px solid #000000;font-weight:bold;font-size:11px;" colspan="8">TOTAL</td>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-top:1px solid #000000;border-bottom:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.number_format($totalAmount,2).'</td>
            </tr>
            <tr>
                <td align="right" style="border-left:1px solid #000000;font-weight:bold;border-top:1px solid #000000;border-bottom:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;" colspan="9">RUPEES: '.strtoupper($sub_total_in_words).'</td>
            </tr>
            </table>
            ';
        }

        if($company['gst_status'] == "ON"){

            $total_vat_Amount_total += $vat_Amount_total;
            $total_vat_Sum_Half += $vat_Sum_Half;
            $total_total_tax += $total_tax;
            $total_vat_Sum += $vat_Sum;

            if($company['igst_show'] == "1"){
                $tbl4 = $tbl4.'
                <tr>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">TOTAL</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Amount_total, 2).'</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Sum, 2).'</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_total_tax, 2).'</td>
                </tr>
                ';

            }else{
                $tbl4 = $tbl4.'
                <tr>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">TOTAL</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Amount_total, 2).'</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Sum_Half, 2).'</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_vat_Sum_Half, 2).'</td>
                    <td align="right" style="border-top:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">'.number_format($total_total_tax, 2).'</td>
                </tr>
                ';
            }

                $tbl4 = $tbl4.'</table>';

        }

        $tbl5 = '<table cellpadding="4" border="1" width="100%">';

        $tbl5 = $tbl5.'
            <tr>
                <td style="font-weight:bold;">THIS BILL DUES ON :</td>
                <td align="center" rowspan="2" style="font-size:12px;">For <b>'.$cpCfg['cp.companyName'].'</b><br/><br/><br/><br/><br/><b>Authorised Signatory</b></td>
            </tr>
            <tr>
                <td>'.$company['invoice_terms'].'</td>
            </tr>
        </table>
        ';

        $tbl6 = '
        <table cellpadding="4" border="0">
            <tr>
                <td align="left" style="font-size:10px;"><b>Company PAN : </b><b>'.$cpCfg['cp.companyPanNo'].'</b></td>
            </tr>
            <tr>
                <td align="left" style="font-size:10px;font-weight:bold;"><b>Bank Details : </b><b>'.$cpCfg['cp.bankDetails'].'</b></td>
            </tr>
        </table>
        ';

        $pdf->ln(-1);
        $pdf->writeHTML($tbl1, true, false, false, false, '');
        //$pdf->writeHTML($tbl2, true, false, false, false, '');
        $pdf->ln(-3);
        $pdf->writeHTML($tbl3, true, false, false, false, '');
        
        //if($company['gst_status'] == "ON"){
            //$pdf->writeHTML($tbl4, true, false, false, false, '');
        //}
        $pdf->ln(-2);
        $pdf->writeHTML($tbl6, true, false, false, false, '');
        $pdf->ln(-3);
        $pdf->writeHTML($tbl5, true, false, false, false, '');

        $download_title = $company['invoice_code'] . '-Invoice.pdf';
        $pdf->IncludeJS("print();");
        $pdf->Output($download_title, 'I');
    }

    /**
     *
    **/
    function getUpdateCostPriceOrderItem() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        //http://cubobillpro.localhost/admin/index.php?_topRm=finance&module=tradingsg_order&_spAction=UpdateCostPriceOrderItem&showHTML=0

        $SQLOrderItem = "
        SELECT oi.record_id
              ,oi.order_item_id
        FROM order_item oi
        LEFT JOIN `order` o ON (o.order_id = oi.order_id)
        ";
        $resultOrderItem = $db->sql_query($SQLOrderItem);
        $numRowsOrderItem = $db->sql_numrows($resultOrderItem);
        while($rowOrderItem  = $db->sql_fetchrow($resultOrderItem)){
            $SQLPOProduct = "
            SELECT cost_price
            FROM po_product
            WHERE product_id = '{$rowOrderItem['record_id']}'
            ORDER BY po_product_id DESC
            ";
            $resultPOProduct = $db->sql_query($SQLPOProduct);
            $rowPOProduct  = $db->sql_fetchrow($resultPOProduct);

            if($rowPOProduct['cost_price'] == "") {
                $rowPOProduct['cost_price'] = 0;
            }

            $SQLUpdateOrderItem = "
            UPDATE order_item SET cost_price = '{$rowPOProduct['cost_price']}'
            WHERE order_item_id = '{$rowOrderItem['order_item_id']}'
            ";
            $resultUpdateOrderItem = $db->sql_query($SQLUpdateOrderItem);
        }
    }

    /**
     *
     */
    function getPrintSalesReturn() {
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
        include_once(CP_LIBRARY_PATH.'lib_php/fpdf-extra/html2pdf.php');
        include_once(CP_LIBRARY_PATH.'lib_php/fpdf-extra/html_table1.php');
        include_once(CP_LIBRARY_PATH.'lib_php/fpdf-extra/mc_table.php');

        //$pdf = new MYPDF();
        $pdf = new PDF_MC_Table();
        $pdf->AddPage();
        $pdf->SetFont('Arial','',11);

        $invoiceHeading = '';

        $invoice_code = $fn->getReqParam('invoice_code');
        $date = $fn->getReqParam('date');
        $sales_return_history_id = $fn->getReqParam('sales_return_history_id');

        $SQLInvoice = "
        SELECT *
        FROM `invoice`
        WHERE invoice_code = '{$invoice_code}'
        ";
        $resultInvoice = $db->sql_query($SQLInvoice);
        $invoiceRec = $db->sql_fetchrow($resultInvoice);

        //TO CHECK IF THE SUM OF DISCOUNT TYPE(%) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForPercentSum = "
        SELECT SUM(round(((ini.cost_price * ini.discount_percentage )/100)* sr.qty_return,2)) as discount_sum
        FROM sales_return_history sr
        LEFT JOIN invoice_item ini ON (ini.invoice_item_id = sr.invoice_item_id)
        WHERE sr.invoice_id = {$invoiceRec['invoice_id']}
            AND ini.discount_type = '%'
            AND sr.status IS NULL
        ";
        $resultSubSql = $db->sql_query($subSqlForPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForPercentSum = "
            SELECT SUM(round(((ini.cost_price * ini.discount_percentage)/100)* sr.qty_return,2))
            FROM sales_return_history sr
            LEFT JOIN invoice_item ini ON (ini.invoice_item_id = sr.invoice_item_id)
            WHERE sr.invoice_id = {$invoiceRec['invoice_id']}
                AND ini.discount_type = '%'
                AND sr.status IS NULL
            ";
        }
        else{
            $subSqlForPercentSum = 0;
        }

        //TO CHECK IF THE SUM OF DISCOUNT TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForValueSum ="
        SELECT SUM(round(ini.discount_percentage  * sr.qty_return,2)) as discount_sum
        FROM sales_return_history sr
        LEFT JOIN invoice_item ini ON (ini.invoice_item_id = sr.invoice_item_id)
        WHERE sr.invoice_id = {$invoiceRec['invoice_id']}
            AND ini.discount_type = 'Value'
            AND sr.status IS NULL
        ";
        $resultSubSql = $db->sql_query($subSqlForValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForValueSum ="
            SELECT SUM(round(ini.discount_percentage  * sr.qty_return,2))
            FROM sales_return_history sr
            LEFT JOIN invoice_item ini ON (ini.invoice_item_id = sr.invoice_item_id)
            WHERE sr.invoice_id = {$invoiceRec['invoice_id']}
                AND ini.discount_type = 'Value'
                AND sr.status IS NULL
            ";
        }
        else{
            $subSqlForValueSum = 0;
        }

        $SQL = "
        SELECT sr.*
              ,ini.item_title AS product_title
              ,ini.discount_percentage
              ,ini.discount_type
              ,ini.vat
              ,ini.cost_price
              ,sr.qty_return AS qty
              ,p.title AS product_title1
              ,p.unit
              ,CONCAT_WS('::', p.carton_no, p.batch_no, p.model) code
              ,p.item_code
              ,p.part_number
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
              ,c.tin_no
              ,c.cst_no
              ,i.invoice_date
              ,q.delivery_date
              ,q.delivery_location
              ,ini.unit_price
              ,i.invoice_code
              ,i.invoice_code_vat
              ,i.invoice_code_vat_quote
              ,i.invoice_terms
              ,i.invoice_due_date
              ,i.notes
              ,i.cst
              ,i.cst_value
              ,i.vat_value
              ,i.vat AS invoice_vat
              ,i.frieght
              ,i.p_f
              ,o.record_type
              ,o.order_id
              ,o.shipping_address1
              ,o.shipping_first_name
              ,o.shipping_address2
              ,o.shipping_address_city
              ,o.shipping_address_state
               ,(SELECT gc.name FROM geo_country gc
                 WHERE gc.country_code = o.shipping_address_country)
                 AS shipping_address_country
              ,q.quote_code
              ,q.currency
              ,sr.qty_return * sr.price AS amount
              ,(ini.unit_price * sr.qty_return) AS Price_POS
              ,(SELECT
              ($subSqlForPercentSum)
               +
              ($subSqlForValueSum)) as discount_percentage_amount_sum
              ,(SELECT SUM(((inih.cost_price * inih.vat )/100)* inih.qty)
                FROM invoice_item inih
                WHERE inih.invoice_id = ini.invoice_id) AS vat_amount_sum
              ,(SELECT SUM(srh.qty_return * srh.price)
                FROM sales_return_history srh
                WHERE srh.invoice_id = sr.invoice_id
                  AND srh.date = sr.date
                  AND srh.status IS NULL) AS selling_price_sum
              ,(SELECT SUM(srh.qty_return * init.cost_price) FROM sales_return_history srh
                LEFT JOIN invoice_item init ON (init.invoice_item_id = srh  .invoice_item_id)
                WHERE srh.invoice_id = sr.invoice_id
                  AND srh.date = sr.date
                  AND srh.status IS NULL) AS sub_total
        FROM sales_return_history sr
        LEFT JOIN invoice_item ini ON (ini.invoice_item_id = sr.invoice_item_id)
        LEFT JOIN product p ON (p.product_id = ini.record_id)
        LEFT JOIN invoice i ON (i.invoice_id = sr.invoice_id)
        LEFT JOIN `order` o ON (o.order_id = sr.order_id)
        LEFT JOIN company c ON (c.company_id = o.company_id)
        LEFT JOIN quote q ON (q.quote_id = o.quote_id)
        LEFT JOIN product_group pg ON (p.product_group_id = pg.product_group_id)
        WHERE i.invoice_code = '{$invoice_code}'
        AND sr.date = '{$date}'
        ORDER BY ini.invoice_item_id, pg.sort_order ASC, p.title
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
        $lineItemNumber = 1;  // To increment the line item in receipt
        $printTaxName = '';
        $gsttaxvalue = '';
        $gstvalue = '';
        $totalvalue = '';
        $totalpf = '';
        $record_type = '';
        $discountValueTotal = 0;
        $total_discount_value_sum = 0;
        $total_vat_sum = 0;

        //============================================================================= //
        $pdf->SetFont('Arial','',11);
        //syed:multi text code to set width of each column and alignment
        $pdf->SetWidths(array(10, 40, 40, 10, 10, 22, 18, 15, 25));
        $pdf->SetAligns(array('L', 'L', 'L', 'R', 'L', 'R', 'R', 'R', 'R'));

        while ($row = $db->sql_fetchrow($result)) {
            $discount_value_for_one_qty = 0;
            $discountValue =0;

            if($row['record_type'] == 'POS'){
                $pdf->SetWidths(array(10, 45, 50, 10, 10, 22, 18, 25));
                $pdf->SetAligns(array('L', 'L', 'L', 'R', 'L', 'R', 'R', 'R', 'R'));
            }

            if($row['record_type'] == 'POS'){
                $amount = $row['Price_POS'];
            }else{
                $amount = $row['amount'];
            }

            if($row['discount_percentage'] > 0){
                if($row['discount_type'] == '%'){
                    $discount_value_for_one_qty  =  $row['cost_price'] * ($row['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
                else if($row['discount_type']  == 'Value'){
                    $discount_value_for_one_qty  =  $row['discount_percentage'];
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
                $discountValueTotal += $discountValue;

            }
            $total_discount_value_sum += $discountValue;
            $vat_for_one_qty = 0;
            $vatAmount =0;

            if($row['vat'] > 0){
                //$vat_for_one_qty  =  $row['cost_price'] * $row['vat']/100;
                $vat_for_one_qty  =  ($row['cost_price'] - $discount_value_for_one_qty) * $row['vat']/100;
                $vatAmount = $vat_for_one_qty;
            }
            $vatAmountTot = $vatAmount * $row['qty'];

            if ($count == 0){
                /* Logo of the institution */
                $pdf->Image('images/logo-print.gif',10,5,45);
                $pdf->SetXY(10,10);
                $pdf->SetFont('Courier','B',9);
                $pdf->Cell(50, 20, $cpCfg['cp.companyName']);
                $pdf->Ln(5);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf7']);
                $pdf->Ln(5);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf6']);
                $pdf->Ln(5);
                $pdf->Cell(50, 20, $cpCfg['printWebAddress']);

                $creationDate   = $fn->getCPDate($row['date'], 'd-m-Y');
                $invoiceDueDate = $fn->getCPDate($row['date'], 'd-m-Y');
                $currency = $row['currency'];

                $totalvalue = $row['sub_total'];

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
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf5']);
                $pdf->Ln(5);
                /*$pdf->SetXY(130,25);
                $pdf->Cell(50, 20, $cpCfg['printTelephoneAndFax']);
                $pdf->Ln(5);*/
                $pdf->SetXY(130,25);
                $pdf->Cell(50, 20, $cpCfg['printEmailAddress']);

                /* Header */
                $pdf->SetFont('Courier','BU',10);
                $pdf->SetXY(80, 45);
                $pdf->Cell(50, 20, $invoiceHeading . "Sales Return", 0, 0, 'C');
                $pdf->SetFont('Courier','B',9);
                $pdf->SetX(130);
                $pdf->Cell(31, 20, "DATE : " . $creationDate, 0, 0, 'L');
                $pdf->Ln(15);

                /* Company Details*/

                if ($row['shipping_address1'] != ''
                    || $row['shipping_address2'] != ''
                    || $row['shipping_address_city'] != ''
                    || $row['shipping_address_state'] != ''
                    || $row['shipping_address_country'] != '') {
                        //Delivery Address Fields in Order
                        $deliveryAddressFlat    = $row['shipping_address1'];
                        $deliveryAddressStreet  = $row['shipping_address2'];
                        $deliveryAddressTown    = $row['shipping_address_city'];
                        $deliveryAddressState   = $row['shipping_address_state'];
                        $deliveryAddressCountry = $row['shipping_address_country'];
                        $deliveryCompanyName    = $row['shipping_first_name'];
                } else {
                    //Delivery Address Fields in client
                    $deliveryAddressFlat    = $row['address_flat'];
                    $deliveryAddressStreet  = $row['address_street'];
                    $deliveryAddressTown    = $row['address_town'];
                    $deliveryAddressState   = $row['address_state'];
                    $deliveryAddressCountry = $row['address_country'];
                    $deliveryCompanyName    = $row['company_name'];
                }

                /* Company Details*/

                $date = $fn->getCPDate($row['delivery_date'], 'd-m-Y');

                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(95,8,"INVOICE TO",1,0, 'L', 1);
                $pdf->Cell(95,8,"DELIVERY TO",1,0, 'L', 1);
                $pdf->Ln();
                $pdf->SetFillColor(255,255,255);

                $pdf->Cell(95, 8, $row['company_name'],'LR', 0, 'L', 1);
                $pdf->Cell(95, 8, $deliveryCompanyName , 'LR', 0, 'L', 1);
                $pdf->Ln();
                $pdf->Cell(95, 5, $row['billing_address_flat'], 'LR', 0, 'L', 1);
                $pdf->Cell(95, 5, $deliveryAddressFlat, 'LR', 0, 'L', 1);
                $pdf->Ln();
                $pdf->Cell(95, 5, $row['billing_address_street'], 'LR', 0, 'L', 1);
                $pdf->Cell(95, 5, $deliveryAddressStreet, 'LR', 0, 'L', 1);
                $pdf->Ln();
                $pdf->Cell(95, 5, $row['billing_address_town'], 'LR', 0, 'L', 1);
                $pdf->Cell(95, 5, $deliveryAddressTown, 'LR', 0, 'L', 1);
                $pdf->Ln();
                $pdf->Cell(95, 5, $row['billing_address_country'] .' - '. $row['billing_address_state'], 'LR', 0, 'L', 1);
                $pdf->Cell(95, 5, $deliveryAddressCountry .' - '. $deliveryAddressState, 'LR', 0, 'L', 1);
                $pdf->Ln();
                $pdf->Cell(95, 8, 'TIN NO:' . $row['tin_no'], 'LR', 0, 'L', 1);
                $pdf->Cell(95, 8, 'TIN NO:' .$row['tin_no'], 'LR', 0, 'L', 1);
                $pdf->Ln(6);
                $pdf->Cell(95, 8, 'CST NO:' . $row['cst_no'], 'BLR', 0, 'L', 1);
                $pdf->Cell(95, 8, 'CST NO:' .$row['cst_no'], 'BLR', 0, 'L', 1);

                $pdf->Ln(10);

               if($row['record_type'] != 'POS'){

                   if($row['invoice_vat'] == 1){
                        $invoiceCode = 'INVQ -' . $row['invoice_code_vat_quote'];
                    } else {
                        $invoiceCode = $row['invoice_code'];
                    }
                }
                else{
                    $invoiceCode = 'INVT -' .$row['invoice_code_vat'];
                }


                /* Invoice Details*/
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(47.5,8,"INVOICE NO :",1,0, 'L', 1);
                $pdf->SetFillColor(255,255,255);
                $pdf->Cell(47.5, 8, $invoiceCode, 1, 0, 'L', 1);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(47.5,8,"Sales Return Date :",1,0, 'L', 1);
                $pdf->SetFillColor(255,255,255);
                $pdf->Cell(47.5, 8, $invoiceDueDate, 1, 0, 'L', 1);
                $pdf->Ln(12);

                /* List of order items header */
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(10,8,"S.NO",1,0, 'C', 1);

                if($row['record_type'] != 'POS'){
                    $pdf->Cell(40,8,"ITEM NAME",1,0, 'C', 1);
                    $pdf->Cell(40,8,"ITEM CODE",1,0, 'C', 1);
                }
                else{
                    $pdf->Cell(45,8,"ITEM NAME",1,0, 'C', 1);
                    $pdf->Cell(50,8,"ITEM CODE",1,0, 'C', 1);
                }

                $pdf->Cell(10,8,"QTY",1,0, 'C', 1);
                $pdf->Cell(10,8,"UOM",1,0, 'C', 1);
                $pdf->Cell(22,8,"UNIT PRICE",1,0, 'C', 1);
                $pdf->Cell(18,8,"DISCOUNT",1,0, 'C', 1);

                if ($row['record_type'] != 'POS'){

                    $pdf->Cell(15,8,"VAT",1,0, 'C', 1);
                    $pdf->Cell(25,8,"AMOUNT(" . $row['currency'] . ")",1,0, 'C', 1);
                    $pdf->Ln();
                }
                else{

                    $pdf->Cell(25,8,"AMOUNT",1,0, 'C', 1);
                    $pdf->Ln();
                }
            }

            //$total_discount_value_sum += $discount_value_for_one_qty;
            $total_vat_sum += $vatAmountTot;

            //===================================MAIN TABLE============================= //
            $discount_value_for_one_qty = number_format($discount_value_for_one_qty, 2);

            $pdf->SetFillColor(255,255,255);
            /*
            $pdf->Cell(10, 8, $lineItemNumber, 1, 0, 'C', 1);
            $pdf->Cell(65, 8, $row['product_title'], 1, 0, 'L', 1);
            $pdf->Cell(37, 8, $row['part_number'], 1, 0, 'L', 1);
            $pdf->Cell(13, 8, $row['qty'], 1, 0, 'R', 1);
            $pdf->Cell(13, 8, $row['unit'], 1, 0, 'R', 1);
            $pdf->Cell(26, 8, number_format($row['unit_price'],2), 1, 0, 'R', 1);
            $pdf->Cell(26, 8, number_format(round($row['amount']),2), 1, 0, 'R', 1);
            */

            if ($row['record_type'] != 'POS'){
                $pdf->Row(array($lineItemNumber, $row['product_title'] , $row['code'], $row['qty'], $row['unit'], number_format($row['cost_price'],2) , '- ' . $discount_value_for_one_qty, number_format($vatAmount, 2), number_format($amount,2) ));
            }
            else{
                $pdf->Row(array($lineItemNumber, $row['product_title'] , $row['code'], $row['qty'], $row['unit'], number_format($row['cost_price'],2) , '- ' . $discount_value_for_one_qty, number_format($amount,2) ));
            }

            //$pdf->Ln();

            $count++;
            $lineItemNumber++;
            $sub_total = $row['sub_total'];
            $notes = $row['notes'];
            $vat_value = $row['vat_value'];
            //$discount = $row['discount_percentage_amount_sum'];
            $discount  = $total_discount_value_sum;
            $record_type = $row['record_type'];

            $vat_amount_sum = $row['selling_price_sum'] - ($sub_total - $discount);
        }

            $totalvalueRounded = $totalvalue;

            $subtotalvalue = $totalvalue;
            if ($record_type != 'POS'){
                $totalvalue = $totalvalue + $total_vat_sum - $discount;
            }
            else{
                $totalvalue = $totalvalue - $discount;
            }
            $total_vat_sum = number_format(round($total_vat_sum),2);
            $vat_amount_sum = number_format(round($vat_amount_sum),2);
            $discount = number_format(round($discount),2);

            $pdf->Cell(165,8,"SUB TOTAL",1,0, 'R', 1);
            $pdf->Cell(25,8,number_format(round($subtotalvalue), 2),1,0, 'R', 1);
            $pdf->Ln();

            $pdf->Cell(165,8,"TOTAL DISCOUNT",1,0, 'R', 1);
            $pdf->Cell(25,8,'- ' . $discount,1,0, 'R', 1);
            $pdf->Ln();

            if($record_type != 'POS'){
                $pdf->Cell(165,8,"TOTAL VAT",1,0, 'R', 1);
                //$pdf->Cell(25,8,$vat_amount_sum,1,0, 'R', 1);
                $pdf->Cell(25,8,$total_vat_sum,1,0, 'R', 1);
                $pdf->Ln();
            }

            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(165, 8, 'TOTAL', 1, 0, 'R', 1);
            $pdf->Cell(25, 8, number_format(round($totalvalue), 2), 1, 0, 'R', 1);
            $pdf->Ln(10);

            $pdf->Cell(190, 8, $cpCfg['cp.invoiceVatInclusive'], 0, 0, 'L');
            $pdf->Ln(10);

            $pdf->Cell(150, 8, 'NOTE: ');
            $pdf->Ln(5);
            $pdf->drawTextBox($notes, 180, 55, 'L', 'T', 0);
            $pdf->Ln(15);

            $pdf->Cell(195,8, "(This is computer generated document, and does not require a signature)", 0, 0, 'L', 1);

            $pdf->Output();
    }

    /**
     *
     */
     function getGenerateCreditNoteForm() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        unset($_SESSION['selectedOrderItemIds']);

        $rows = '';

        $order_id = $fn->getReqParam('order_id');
        $date     = $fn->getCurrentDate();
        $due_date = date('Y-m-d', strtotime("+30 days"));
        $qty_balance = '';

        $sqlOrderItem = "
        SELECT * FROM order_item
        WHERE order_id = {$order_id}
        ";
        $resultOrderItem = $db->sql_query($sqlOrderItem);
        while ($rowOI = $db->sql_fetchrow($resultOrderItem)) {
            $sqlQty = "
            SELECT SUM(it.qty) AS qty_invoiced
            FROM credit_note_item it
            JOIN credit_note i ON (i.credit_note_id = it.credit_note_id)
            WHERE i.order_id = {$order_id}
             AND it.record_id = {$rowOI['record_id']}
             AND i.status != 'Cancelled'
            ";
            $resultQty = $db->sql_query($sqlQty);
            $rowQty = $db->sql_fetchrow($resultQty);

            $selling_price = $rowOI['unit_price'] * $rowOI['qty'];

            $qty_balance = $rowOI['qty'] - $rowQty['qty_invoiced'];

            $inputRow = '';
            $qtyRow = '';

            if ($rowQty['qty_invoiced'] != $rowOI['qty']) {
                $pfx = $rowOI['order_item_id'] . '_' ;
                $inputRow = "<input class='orderItemId' type='checkbox' name='orderItemId[]' value='{$rowOI['order_item_id']}'>";
                $qtyRow = "<input type='text' value='{$qty_balance}' id='fld_qty' class='text w50' name='{$pfx}qty'>";
            }

               $pfx1 = $rowOI['order_item_id'] . '_' ;
               
                $unitPriceRow = "<input type='text' value='{$rowOI['unit_price']}' id='fld_unit_price' class='text w50' name='{$pfx1}unit_price'>";


            $rows .= "
            <tr orderRowItem[] = {$rowOI['order_item_id']}>
                <td>
                    {$inputRow}
                </td>
                <td>{$rowOI['item_title']}</td>
                <td>{$rowOI['part_number']}</td>
                <td class='sellingPrice'>{$unitPriceRow}</td>
                <td class=''>{$rowOI['qty']}</td>
                <td class=''>{$qtyRow}</td>
                <td class='qtyBalance'>{$qty_balance}</td>
                <td class=''>{$rowQty['qty_invoiced']}</td>
            </tr>
            ";
        }

        $formAction = "index.php?_topRm=finance&module=tradingsg_order&_spAction=generateCreditFormSubmit&showHTML=0";

        $expNoEdit = array('isEditable' => 0);

        $icgstArr = array(
             "IGST"
            ,"CGST"
        );

        $orderRec = $fn->getRecordRowById('order', 'order_id', $order_id);

            //{$formObj->getTBRow('Add Frieght Cost', 'frieght_cost')}
        $text = "
        <form id='portalCreditForm' class='yform columnar creditNoteForm' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Amount', 'invoice_amount', '', $expNoEdit)}
            {$formObj->getDateRow('Date', 'invoice_date', $date)}
            {$formObj->getDateRow('Due Date', 'invoice_due_date', $due_date)}
            {$formObj->getTBRow('Customer Purchase Order No', 'cust_po_no')}
            {$formObj->getTARow('Terms', 'invoice_terms', $orderRec['invoice_terms'])}
            {$formObj->getTARow('Notes', 'notes', $orderRec['notes'])}
            {$formObj->getTBRow('Issued By', 'staff_id', $_SESSION['userFullName'], $expNoEdit)}
            {$formObj->getTBRow('Add Frieght Cost', 'frieght_cost')}
            {$formObj->getTBRow('Add P & F(%)', 'p_f')}
            <div class='button updateTotal'>
                <a href='#'>Update Total</a>
            </div>

            <table class='thinlist room-order-table'>
                <thead>
                    <th class='click-all-topping'>
                        <a href='#' class='check-all-col'>
                            <img src='{$cpCfg['cp.commonImagesPathAlias']}icons/checkbox_checked.gif'>
                        </a>
                        <a href='#' class='uncheck-all-col'>
                            <img src='{$cpCfg['cp.commonImagesPathAlias']}icons/checkbox_unchecked.gif'>
                        </a>
                    </th>
                    <th>Product Name</th>
                    <th>Part Number</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th class=''>Qty (Current Invoice)</th>
                    <th>Qty (Balance)</th>
                    <th>Qty (Invoiced)</th>
                </thead>

                <tbody>
                    {$rows}
                </tbody>
            </table>

            <input type='hidden' name='order_id' value='{$order_id}' />
            <input type='hidden' name='qty_balance' value='{$qty_balance}' />
        </form>
        ";

        //{$formObj->getTBRow('Add Frieght(%)', 'frieght')}

        return $text;
    }
    
	/**
     *
     */
    function getPrintCreditRecord() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $searchVar = Zend_Registry::get('searchVar');
        $media = Zend_Registry::get('media');
        $cpPaths = Zend_Registry::get('cpPaths');
        $dbUtil = Zend_Registry::get('dbUtil');
        $site_id  = $fn->getSessionParam('cp_site_id');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('USS');
        $pdf->SetSubject('Proforma Invoice');
        $pdf->SetTitle('Proforma Invoice');

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

        $invoiceHeading = '';

        $credit_note_id = $fn->getReqParam('credit_note_id');
        $invoice_code = $fn->getReqParam('invoice_code');
        $invoice_type = $fn->getReqParam('invoice_type');
        if($invoice_type == 'normal'){
            $invoiceHeading = 'ORIGINAL - ';
        }
        else if($invoice_type == 'transporter'){
            $invoiceHeading = 'TRANSPORTER - ';
        }
        else if($invoice_type == 'proforma'){
            $invoiceHeading = 'PROFORMA - ';
        }
        else if($invoice_type == 'extra'){
            $invoiceHeading = 'DUPLICATE - ';
        }

        $SQL = "
        SELECT ini.*
              ,ini.item_title AS product_title
              ,p.title AS product_title1
              ,p.unit
              ,p.item_code
              ,p.part_number
              ,p.description_short
              ,p.hsn
              ,c.company_name
              ,c.address_flat
              ,c.address_street
              ,c.address_town
              ,c.address_state
              ,c.address_po_code
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
              ,c.tin_no
              ,c.cst_no
              ,c.gst_no
              ,i.invoice_date
              ,q.delivery_date
              ,q.delivery_location
              ,q.gst_enabled
              ,q.show_discount_percentage
              ,q.currency
              ,q.payment_terms
              ,q.delivery_terms
              ,ini.unit_price
              ,i.invoice_code
              ,i.invoice_terms
              ,i.invoice_due_date
              ,i.notes
              ,i.cst
              ,i.vat
              ,i.cst_value
              ,i.vat_value
              ,i.frieght_cost
              ,i.cust_po_no
              ,i.p_f
              ,i.credit_note_id
              ,o.order_id
              ,o.notes
              ,o.shipping_address1
              ,o.shipping_first_name
              ,o.shipping_address2
              ,o.shipping_address_city
              ,o.shipping_address_state
              ,o.gst_status
              ,o.igst_show
               ,(SELECT gc.name FROM geo_country gc
                 WHERE gc.country_code = o.shipping_address_country)
                 AS shipping_address_country
              ,q.quote_code
              ,q.currency
              ,ini.qty * ini.unit_price AS amount
              ,(SELECT SUM(init.qty * init.cost_price) FROM credit_note_item init
               WHERE init.credit_note_id = ini.credit_note_id) AS sub_total
              ,(SELECT SUM(init.qty * init.unit_price) FROM credit_note_item init
               WHERE init.credit_note_id = ini.credit_note_id) AS total
              ,CONCAT_WS(' ', co.first_name, co.last_name) AS contact_name
              ,co.salutation
              ,(SELECT invoice_code FROM invoice inv WHERE inv.order_id = o.order_id AND inv.status != 'Cancelled') AS invoiceCode
              ,(SELECT invoice_date FROM invoice inv WHERE inv.order_id = o.order_id AND inv.status != 'Cancelled') AS creditDate
        FROM credit_note_item ini
        LEFT JOIN product p ON (p.product_id = ini.record_id)
        LEFT JOIN credit_note i ON (i.credit_note_id = ini.credit_note_id)
        LEFT JOIN `order` o ON (o.order_id = i.order_id)
        LEFT JOIN company c ON (c.company_id = o.company_id)
        LEFT JOIN quote q ON (q.quote_id = o.quote_id)
        LEFT JOIN contact co ON (co.contact_id = q.contact_id)
        LEFT JOIN product_group pg ON (p.product_group_id = pg.product_group_id)
        WHERE i.credit_note_id = '{$credit_note_id}'
          AND i.status != 'Cancelled'
        ORDER BY ini.credit_note_item_id, pg.sort_order ASC, p.title
        ";
        $result = $db->sql_query($SQL);
        $result2 = $db->sql_query($SQL);
        $Row = $db->sql_fetchrow($result2);

        $numRows  = $db->sql_numrows($result);
        //============================================================================= //

        $pdf->SetFont('helvetica','', 8);
        $today = date("d-m-Y");

        if($site_id == 1) {
            $tbl1 = '
            <table border="0" width="100%" style="font-size:17px;">
                <tr>
                    <td align="center" style="font-weight:bold; text-decoration: underline;">CREDIT NOTE</td>
                </tr>
            </table>
            ';

            $tblQuote ='
            <table border="0" width="100%" cellpadding="3">
            </table>
            ';

            $contact_name = '';
            if($Row['contact_name'] != ''){
                $contact_name = "Kind Attn: {$Row['salutation']}.{$Row['contact_name']}";
            }

            $addressFlat     = $Row['address_flat'];
            $addressStreet   = $Row['address_street'];
            $addressTown     = $Row['address_town'];
            $addressState    = $Row['address_state'];
            $addressCountry  = $Row['address_country'];

            $billingAddressFlat     = $Row['billing_address_flat'];
            $billingAddressStreet   = $Row['billing_address_street'];
            $billingAddressTown     = $Row['billing_address_town'];
            $billingAddressState    = $Row['billing_address_state'];
            $billingAddressCountry  = $Row['billing_address_country'];
            $invoiceDate   = $fn->getCPDate($Row['invoice_date'], 'd-m-Y');
                        $creditDate   = $fn->getCPDate($Row['creditDate'], 'd-m-Y');


            $tbl2 ='
            <table border="0" width="100%" cellpadding="3">
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Credit Note No : </i>'.$Row['invoice_code'].'</td>
                    <td width="50%" style="border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Reference Invoice: </i> '.$Row['invoiceCode'].' </td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Date Of Issue : </i>'.$invoiceDate.'</td>
                    <td width="50%" style="border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Date Of Invoice : </i> '.$creditDate.'</td>
                </tr>
                  <tr>
                    <td width="25%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>State : </i></td>
                    <td width="13%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Code : </i></td>
                    <td width="12%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;">'.$Row['address_po_code'].'</td>
                    <td width="50%" style="border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"></td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>From :</i></td>
                    <td width="50%" style="border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Billed to :</i></td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$cpCfg['cp.companyName'].'</td>
                    <td width="50%" style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$Row['company_name'].'</td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$cpCfg['cp.addressPdf5'].'</td>
                    <td width="50%" style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$Row['address_flat'].'</td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.$cpCfg['cp.panNoPdf'].'</td>
                    <td width="50%" style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$Row['address_street'].' '.$Row['address_town'].' '.$Row['address_state'].'</td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;"></td>
                    <td width="50%" style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">GST IN / UIN :'.$Row['gst_no'].'</td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-bottom:1px solid #000000;font-size:11px;"><span style="font-weight:bold;">State Code: 33</span></td>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-bottom:1px solid #000000;font-size:11px;"><span style="font-weight:bold;">State Code: </span>'.$Row['address_po_code'].'</td>
                </tr>
            </table>
            ';


            if($Row['gst_status'] == "ON"){
                $tbl3 ='
                <table border="1" nobr="true" width="100%" cellpadding="3" style="font-size:10px;">
                    <thead>
                        <tr>
                            <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">S.No</th>
                            <th width="35%" style="border:1px solid #000000;font-weight:bold;" align="left">Product Description</th>
                            <th width="10%"  style="border:1px solid #000000;font-weight:bold;" align="center">HSN Code</th>
                            <th width="6%"  style="border:1px solid #000000;font-weight:bold;" align="center">UOM</th>
                            <th width="7%"  style="border:1px solid #000000;font-weight:bold;" align="center">Qty</th>
                            <th width="12%"  style="border:1px solid #000000;font-weight:bold;" align="right">Price</th>
                            <th width="12%"  style="border:1px solid #000000;font-weight:bold;" align="right">Taxbl Val</th>
                            <th width="13%" style="border:1px solid #000000;font-weight:bold;" align="right">Total</th>
                        </tr>
                    </thead>
                ';
            }

            else {
                if($Row['show_discount_percentage'] == 1){
                    $tbl3 = '
                    <table border="1" width="100%" cellpadding="3" style="font-size:11px;">
                        <thead>
                            <tr>
                                <td width="6%"  style="font-weight:bold;" align="center">S.No</td>
                                <td width="26%" style="font-weight:bold;" align="left">Product Description</td>
                                <td width="15%" style="font-weight:bold;" align="center">HSN Code</td>
                                <td width="10%" style="font-weight:bold;" align="center">UOM</td>
                                <td width="8%"  style="font-weight:bold;" align="center">Qty</td>
                                <td width="10%" style="font-weight:bold;" align="right">Price</td>
                                <td width="10%" style="font-weight:bold;" align="right">Discount</td>
                                <td width="15%" style="font-weight:bold;" align="right">Total ('.$Row['currency'].')</td>
                            </tr>
                        </thead>
                        <tbody>
                    ';
                } else {
                    $tbl3 = '
                    <table border="1" width="100%" cellpadding="3" style="font-size:11px;">
                        <thead>
                            <tr>
                                <td width="6%"  style="font-weight:bold;" align="center">S.No</td>
                                <td width="29%" style="font-weight:bold;" align="left">Product Description</td>
                                <td width="15%" style="font-weight:bold;" align="center">HSN Code</td>
                                <td width="10%" style="font-weight:bold;" align="center">UOM</td>
                                <td width="10%" style="font-weight:bold;" align="center">Qty</td>
                                <td width="15%" style="font-weight:bold;" align="right">Price</td>
                                <td width="15%" style="font-weight:bold;" align="right">Total ('.$Row['currency'].')</td>
                            </tr>
                        </thead>
                        <tbody>
                    ';
                }
            }

            $count = 1;
            $overallTotal = 0;
            $vatSumTotal  = 0;
            while($row = $db->sql_fetchrow($result)){

                $discount_value_for_one_qty = 0;
                $discount_value_for_display = 0;
                if($row['discount_percentage'] > 0){
                    if($row['discount_type'] == '%'){
                        $discount_value_for_one_qty  =  $row['cost_price'] * ($row['discount_percentage']/100);
                        $discount_value_for_display  =  $row['discount_percentage'] . '%';
                    }
                    else if($row['discount_type']  == 'Value'){
                        $discount_value_for_one_qty  =  $row['discount_percentage'];
                        $discount_value_for_display  =  $row['discount_percentage'];
                    }
                }
 if($row['show_discount_percentage'] != 1){
                    $selling_price = $row['unit_price'];
                    $tsp = ($row['qty'] * $selling_price);

                    $SQLTax = "
                    SELECT  p.gst
                            ,SUM(ci.unit_price * ci.qty) AS qty_amount
                    FROM `credit_note_item` ci
                    LEFT JOIN `credit_note` cn ON (cn.credit_note_id = ci.credit_note_id)
                    LEFT JOIN `product` p ON (p.product_id = ci.record_id)
                    WHERE ci.credit_note_item_id = '{$row['credit_note_item_id']}'
                    AND p.gst > 0
                    AND cn.status != 'Cancelled'
                    ";
                    $resultTax  = $db->sql_query($SQLTax);
                    $rowTax     = $db->sql_fetchrow($resultTax);

                } else {
                    $selling_price = $row['unit_price'];
                    $tsp = ($row['qty'] * $selling_price);

                    $SQLTax = "
                    SELECT  p.gst
                            ,SUM(ci.unit_price * ci.qty) AS qty_amount
                     FROM `credit_note_item` ci
                     LEFT JOIN `credit_note` cn ON (cn.credit_note_id = ci.credit_note_id)
                    LEFT JOIN `product` p ON (p.product_id = ci.record_id)
                    WHERE ci.credit_note_item_id = '{$row['credit_note_item_id']}'
                    AND p.gst > 0
                    AND cn.status != 'Cancelled'
                    ";
                    $resultTax  = $db->sql_query($SQLTax);
                    $rowTax     = $db->sql_fetchrow($resultTax);
                }
                $titledesc = $row['item_title'];

                $titledescrip = $titledesc;
                $discount_value_for_one_qty = number_format($discount_value_for_one_qty, 2);

                $totalVatSum = 0;

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
                $vat_Sum_Half   = $vat_Sum / 2;

                $totalVatSum += $vat_Sum;

                $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);
                
                if($row['gst_status'] == "ON"){
                    $total = $tsp + $vat_Sum_Half + $vat_Sum_Half;
                } else {
                    $total = $tsp;
                }

                $tsp   = $tsp - $row['discount_amount'];
                $total = $total - $row['discount_amount'];
                $selling_price = number_format($selling_price,2);

                if($row['gst_status'] == "ON"){
                    $tbl3 = $tbl3.'<tr>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$count.'</td>
                                        <td width="35%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="left">'.$titledescrip.'</td>
                                        <td width="10%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['hsn'].'</td>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['unit'].'</td>
                                        <td width="7%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['qty'].'</td>
                                        <td width="12%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$selling_price.'</td>
                                        <td width="12%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$tsp.'</td>
                                        <td width="13%" style="border-left:1px solid #000000;border-right:1px solid #000000;"  align="right">'.number_format($total, 2).'</td>
                                    </tr>
                                    ';
                    $count++;
                } else {

                    if($row['show_discount_percentage'] == 1){    
                        $tbl3 = $tbl3.'<tr>
                                            <td width="6%"  align="center">'.$count.'</td>
                                            <td width="26%" align="left">'.$titledescrip.'</td>
                                            <td width="15%" align="center">'.$row['hsn'].'</td>
                                            <td width="10%" align="center">'.$row['unit'].'</td>
                                            <td width="8%"  align="center">'.$row['qty'].'</td>
                                            <td width="10%" align="right">'.$selling_price.'</td>
                                            <td width="10%" align="right">'.$discount_value_for_display.'</td>
                                            <td width="15%" align="right">'.number_format($tsp, 2).'</td>
                                        </tr>
                                       ';

                        $count++;
                    }else {
                        $tbl3 = $tbl3.'<tr>
                                            <td width="6%"  align="center">'.$count.'</td>
                                            <td width="29%" align="left">'.$titledescrip.'</td>
                                            <td width="15%" align="center">'.$row['hsn'].'</td>
                                            <td width="10%" align="center">'.$row['unit'].'</td>
                                            <td width="10%" align="center">'.$row['qty'].'</td>
                                            <td width="15%" align="right">'.$selling_price.'</td>
                                            <td width="15%" align="right">'.number_format($tsp, 2).'</td>
                                        </tr>
                                       ';

                        $count++;
                    }
                }

                $overallTotal += $total;
                $vatSumTotal  += $vat_Sum_Half;

                $total = $row['total'];
                $terms = $row['payment_terms'];
                $notes = $row['notes'];
                $delivery_terms = $row['delivery_terms'];
                $discount = 0;
                $sub_total = $total + $discount - $row['discount_amount'];
                $show_discount_percentage = $row['show_discount_percentage'];
            }
            $tbl4 = '';

            $totaldiscount = $sub_total - $discount;
            $discountPercent = $discount * 100 / $sub_total;
            $totaldiscount = number_format(round($totaldiscount), 2);
            $sub_total = number_format($sub_total,2);
            $discount = number_format($discount,2);
            $discountPercent = number_format($discountPercent,2);
            $displayDiscountPercent = '';

            if($Row['gst_status'] == "ON") {
                //$sub_total_in_words = $fn->getConvertNumber($overallTotal .'.00');
                $sub_total_in_words = $fn->getIndianCurrency($overallTotal .'.00');
                if($Row['igst_show'] == "1"){
                    $tbl3 = $tbl3.'
                                <tr>
                                    <td colspan="5" align="center" style="font-weight:bold;">Total Amount in Words</td>
                                    <td colspan="2" align="right" style="font-weight:bold;">Total Amount Before Tax</td>
                                    <td align="right">'.$totaldiscount.'</td>
                                </tr>
                                <tr>
                                    <td rowspan="5" colspan="5" align="center" style="font-weight:bold;font-size:12px;">'.strtoupper($sub_total_in_words).'</td>
                                    <td colspan="2" align="right" style="font-weight:bold;">Add: IGST 18.00%</td>
                                    <td align="right">'.number_format($totalVatSum, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold;">Total Amount After Tax</td>
                                    <td align="right">'.number_format($overallTotal, 2).'</td>
                                </tr>
                            </tbody>
                        </table>';
                } else {
                    $tbl3 = $tbl3.'
                                <tr>
                                    <td colspan="5" align="center" style="font-weight:bold;">Total Amount in Words</td>
                                    <td colspan="2" align="right" style="font-weight:bold;">Total Amount Before Tax</td>
                                    <td align="right">'.$totaldiscount.'</td>
                                </tr>
                                <tr>
                                    <td rowspan="5" colspan="5" align="center" style="font-weight:bold;font-size:12px;">'.strtoupper($sub_total_in_words).'</td>
                                    <td colspan="2" align="right" style="font-weight:bold;">Add: CGST 9.00%</td>
                                    <td align="right">'.number_format($vatSumTotal, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold;">Add: SGST 9.00%</td>
                                    <td align="right">'.number_format($vatSumTotal, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold;">Total Tax Amount</td>
                                    <td align="right">'.number_format($totalVatSum, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold;">Total Amount After Tax</td>
                                    <td align="right">'.number_format($overallTotal, 2).'</td>
                                </tr>
                            </tbody>
                        </table>';

                }
            } else {

                if($Row['show_discount_percentage'] == 1){ 
                    $tbl3 = $tbl3.'
                                    <tr>
                                        <td colspan="7" align="right" style="font-weight:bold;">TOTAL</td>
                                        <td align="right">'.$totaldiscount.'</td>
                                    </tr>
                                </tbody>
                            </table>';
                } else {
                    $tbl3 = $tbl3.'
                                    <tr>
                                        <td colspan="6" align="right" style="font-weight:bold;">TOTAL</td>
                                        <td align="right">'.$totaldiscount.'</td>
                                    </tr>
                                </tbody>
                            </table>';
                }
            }

            $tbl5 = '<table cellpadding="4" border="1" width="100%" nobr="true">';

            $tbl5 = $tbl5.'
                <tr>
                    <td width="50%" align="left" style="font-size:10px;font-weight:bold;"><b>Bank Details : </b><br/><b>'.$cpCfg['cp.bankDetails'].'</b></td>
                    <td width="20%" align="center"></td>
                    <td width="30%" align="right" rowspan="2" style="font-size:12px;font-weight:bold;">For '.$cpCfg['cp.companyName'].'<br/><br/><br/><br/><br/><br/>Authorised signatory</td>
                </tr>
                <tr>
                    <td width="50%" align=""><span style="font-weight:bold;font-size:11px;">Mode of Transport : </span><br/><br/>Vehicle No. : '.$notes.'</td>
                    <td width="20%" align="center" style="font-weight:bold;font-size:11px;vertical-align:bottom;"><br/><br/><br/><br/>Common Seal</td>
                </tr>
            </table>
            ';
        } else {
            $pdf->SetFont('calibri','', 8);
            $invoiceDate   = $fn->getCPDate($Row['invoice_date'], 'd-m-Y');
            $creditDate   = $fn->getCPDate($Row['creditDate'], 'd-m-Y');


            $tblQuote ='
            <table border="0" width="100%" cellpadding="3">
            </table>
            ';

            $contact_name = '';
            if($Row['contact_name'] != ''){
                $contact_name = "Kind Attn: {$Row['salutation']}.{$Row['contact_name']}";
            }

            $addressFlat     = $Row['address_flat'];
            $addressStreet   = $Row['address_street'];
            $addressTown     = $Row['address_town'];
            $addressState    = $Row['address_state'];
            $addressCountry  = $Row['address_country'];

            $billingAddressFlat     = $Row['billing_address_flat'];
            $billingAddressStreet   = $Row['billing_address_street'];
            $billingAddressTown     = $Row['billing_address_town'];
            $billingAddressState    = $Row['billing_address_state'];
            $billingAddressCountry  = $Row['billing_address_country'];

            $tbl1 = '
            <table border="0" width="100%" cellpadding="3">
                <tr>
                    <td width="27%" style="font-size:13px;font-weight:bold;color:#157ca7;"><strong>From :</strong><br/><font style="font-size:14px;font-weight:bold;color:#000000;">'.$cpCfg['cp.companyName'].'<br/></font><font style="font-size:11px;color:#000000;">'.$cpCfg['cp.addressPdf5'].'<br/>'.$cpCfg['cp.panNoPdf'].'<br/>State Code: 33</font></td>
                    <td width="43%" style="font-size:13px;font-weight:bold;color:#157ca7;"><strong>Billed to :</strong><br/><font style="font-size:14px;font-weight:bold;color:#000000;">'.$Row['company_name'].'<br/></font><font style="font-size:11px;color:#000000;">'.$Row['address_flat'].', '.$Row['address_street'].' '.$Row['address_town'].' '.$Row['address_state'].'<br/>GST IN / UIN :'.$Row['gst_no'].'<br/>State Code: '.$Row['address_po_code'].'</font></td>
                    <td width="30%" style="font-weight:bold;font-size:22px;">CREDIT NOTE <font style="font-size:12px;font-weight:bold;"><br/><br/><i>Code : </i>'.$Row['invoice_code'].'<br/><i>Date : </i>'.$invoiceDate.'<br/><i>Reference Invoice : </i>'.$Row['invoiceCode'].'<br/><i>Invoice Date : </i>'.$creditDate.'</font></td>
                </tr>
            </table>
            ';

            $tbl2 ='';

            if($Row['gst_status'] == "ON"){
                $tbl3 ='
                <table border="0" nobr="true" width="100%" cellpadding="4" style="font-size:11px;">
                    <thead>
                        <tr>
                            <th width="5%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">S.No</th>
                            <th width="35%" style="color:#fff;font-weight:bold; line-height:16px;" align="left" bgColor="#157ca7">Product Description</th>
                            <th width="10%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">HSN Code</th>
                            <th width="6%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">UOM</th>
                            <th width="7%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">Qty</th>
                            <th width="12%"  style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Price</th>
                            <th width="12%"  style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Taxbl Val</th>
                            <th width="13%" style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Total</th>
                        </tr>
                    </thead>
                ';
            }

            else {
                if($Row['show_discount_percentage'] == 1){
                    $tbl3 = '
                    <table border="0" width="100%" cellpadding="4" style="font-size:11px;">
                        <thead>
                            <tr>
                                <td width="6%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">S.No</td>
                                <td width="26%" style="color:#fff;font-weight:bold; line-height:16px;" align="left" bgColor="#157ca7">Product Description</td>
                                <td width="15%" style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">HSN Code</td>
                                <td width="10%" style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">UOM</td>
                                <td width="8%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">Qty</td>
                                <td width="10%" style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Price</td>
                                <td width="10%" style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Discount</td>
                                <td width="15%" style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Total ('.$Row['currency'].')</td>
                            </tr>
                        </thead>
                        <tbody>
                    ';
                } else {
                    $tbl3 = '
                    <table border="0" width="100%" cellpadding="4" style="font-size:11px;">
                        <thead>
                            <tr>
                                <td width="6%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">S.No</td>
                                <td width="29%" style="color:#fff;font-weight:bold; line-height:16px;" align="left" bgColor="#157ca7">Product Description</td>
                                <td width="15%" style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">HSN Code</td>
                                <td width="10%" style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">UOM</td>
                                <td width="10%" style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">Qty</td>
                                <td width="15%" style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Price</td>
                                <td width="15%" style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Total ('.$Row['currency'].')</td>
                            </tr>
                        </thead>
                        <tbody>
                    ';
                }
            }

            $tbl4 = '';

            $count = 1;
            $overallTotal = 0;
            $vatSumTotal  = 0;
            $total_vat_Amount_total = 0;
            $total_vat_Sum_Half = 0;
            $total_vat_Sum = 0;
            $total_total_tax = 0;
            $total_tax = 0;
            while($row = $db->sql_fetchrow($result)){

                $discount_value_for_one_qty = 0;
                $discount_value_for_display = 0;
                if($row['discount_percentage'] > 0){
                    if($row['discount_type'] == '%'){
                        $discount_value_for_one_qty  =  $row['cost_price'] * ($row['discount_percentage']/100);
                        $discount_value_for_display  =  $row['discount_percentage'] . '%';
                    }
                    else if($row['discount_type']  == 'Value'){
                        $discount_value_for_one_qty  =  $row['discount_percentage'];
                        $discount_value_for_display  =  $row['discount_percentage'];
                    }
                }

               if($row['show_discount_percentage'] != 1){
                    $selling_price = $row['unit_price'];
                    $tsp = ($row['qty'] * $selling_price);

                    $SQLTax = "
                    SELECT  p.gst
                            ,SUM(ci.unit_price * ci.qty) AS qty_amount
                    FROM `credit_note_item` ci
                    LEFT JOIN `credit_note` cn ON (cn.credit_note_id = ci.credit_note_id)
                    LEFT JOIN `product` p ON (p.product_id = ci.record_id)
                    WHERE ci.credit_note_item_id = '{$row['credit_note_item_id']}'
                    AND p.gst > 0
                    AND cn.status != 'Cancelled'
                    ";
                    $resultTax  = $db->sql_query($SQLTax);
                    $rowTax     = $db->sql_fetchrow($resultTax);

                } else {
                    $selling_price = $row['unit_price'];
                    $tsp = ($row['qty'] * $selling_price);

                    $SQLTax = "
                    SELECT  p.gst
                            ,SUM(ci.unit_price * ci.qty) AS qty_amount
                     FROM `credit_note_item` ci
                     LEFT JOIN `credit_note` cn ON (cn.credit_note_id = ci.credit_note_id)
                    LEFT JOIN `product` p ON (p.product_id = ci.record_id)
                    WHERE ci.credit_note_item_id = '{$row['credit_note_item_id']}'
                    AND p.gst > 0
                    AND cn.status != 'Cancelled'
                    ";
                    $resultTax  = $db->sql_query($SQLTax);
                    $rowTax     = $db->sql_fetchrow($resultTax);
                }

                $titledesc = $row['item_title'];

                $titledescrip = $titledesc;
                $discount_value_for_one_qty = number_format($discount_value_for_one_qty, 2);

                $totalVatSum = 0;

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
                $vat_Sum_Half   = $vat_Sum / 2;

                $totalVatSum += $vat_Sum;

                $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);
                
                if($row['gst_status'] == "ON"){
                    $total = $tsp + $vat_Sum_Half + $vat_Sum_Half;
                } else {
                    $total = $tsp;
                }

                $tsp   = $tsp - $row['discount_amount'];
                $total = $total - $row['discount_amount'];
                $selling_price = number_format($selling_price,2);

                if($row['gst_status'] == "ON"){
                    $tbl3 = $tbl3.'<tr>
                                        <td width="5%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$count.'</td>
                                        <td width="35%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="left">'.$titledescrip.'</td>
                                        <td width="10%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['hsn'].'</td>
                                        <td width="6%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['unit'].'</td>
                                        <td width="7%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['qty'].'</td>
                                        <td width="12%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="right">'.$selling_price.'</td>
                                        <td width="12%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="right">'.$tsp.'</td>
                                        <td width="13%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;"  align="right">'.number_format($total, 2).'</td>
                                    </tr>
                                    ';
                    $count++;
                } else {

                    if($row['show_discount_percentage'] == 1){    
                        $tbl3 = $tbl3.'<tr>
                                            <td width="6%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$count.'</td>
                                            <td width="26%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="left">'.$titledescrip.'</td>
                                            <td width="15%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['hsn'].'</td>
                                            <td width="10%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['unit'].'</td>
                                            <td width="8%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['qty'].'</td>
                                            <td width="10%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="right">'.$selling_price.'</td>
                                            <td width="10%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="right">'.$discount_value_for_display.'</td>
                                            <td width="15%" align="right" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;">'.number_format($tsp, 2).'</td>
                                        </tr>
                                       ';

                        $count++;
                    }else {
                        $tbl3 = $tbl3.'<tr>
                                            <td width="6%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$count.'</td>
                                            <td width="29%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="left">'.$titledescrip.'</td>
                                            <td width="15%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['hsn'].'</td>
                                            <td width="10%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['unit'].'</td>
                                            <td width="10%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['qty'].'</td>
                                            <td width="15%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="right">'.$selling_price.'</td>
                                            <td width="15%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="right">'.number_format($tsp, 2).'</td>
                                        </tr>
                                       ';

                        $count++;
                    }
                }

                $overallTotal += $total;
                $vatSumTotal  += $vat_Sum_Half;

                $total = $row['total'];
                $terms = $row['payment_terms'];
                $notes = $row['notes'];
                $delivery_terms = $row['delivery_terms'];
                $discount = 0;
                $sub_total = $total + $discount - $row['discount_amount'];
                $show_discount_percentage = $row['show_discount_percentage'];
            }

            $totaldiscount = $sub_total - $discount;
            $discountPercent = $discount * 100 / $sub_total;
            $Total_in_words = $fn->getIndianCurrency($totaldiscount .'.00');
            $totaldiscount = number_format(round($totaldiscount), 2);
            $sub_total = number_format($sub_total,2);
            $discount = number_format($discount,2);
            $discountPercent = number_format($discountPercent,2);
            $displayDiscountPercent = '';
            $emptyRow = '';

            for($ic = 1; $ic <= 6; $ic++){
                if($Row['gst_status'] == "ON"){
                    if($Row['igst_show'] == "1"){
                        $emptyRow .= '
                        <tr>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                        </tr>
                        ';
                    }else{
                        $emptyRow .= '
                        <tr>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                        </tr>
                        ';
                    }
                }
                else{
                    if($Row['show_discount_percentage'] == 1){ 
                        $emptyRow .= '
                            <tr>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            </tr>
                            ';
                    } else {
                        $emptyRow .= '
                            <tr>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            </tr>
                            ';                            
                    }
                }
            }

            if($Row['gst_status'] == "ON") {
                //$sub_total_in_words = $fn->getConvertNumber($overallTotal .'.00');
                $sub_total_in_words = $fn->getIndianCurrency($overallTotal .'.00');
                if($Row['igst_show'] == "1"){
                    $tbl3 = $tbl3.'
                                '.$emptyRow.'
                                <tr>
                                    <td colspan="5" align="center" style="font-weight:bold; line-height:16px;border-top:1px solid #aeafb1;">Total Amount in Words</td>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;border-top:1px solid #aeafb1;">Total Amount Before Tax</td>
                                    <td align="right" style="border-top:1px solid #aeafb1;">'.$totaldiscount.'</td>
                                </tr>
                                <tr>
                                    <td rowspan="5" colspan="5" align="center" style="font-weight:bold;font-size:12px; line-height:16px;">'.strtoupper($sub_total_in_words).'</td>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;">Add: IGST 18.00%</td>
                                    <td align="right">'.number_format($totalVatSum, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;">Total Amount After Tax</td>
                                    <td align="right">'.number_format($overallTotal, 2).'</td>
                                </tr>
                            </tbody>
                        </table>';
                } else {
                    $tbl3 = $tbl3.'
                                '.$emptyRow.'
                                <tr>
                                    <td colspan="5" align="center" style="font-weight:bold; line-height:16px;border-top:1px solid #aeafb1;">Total Amount in Words</td>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;border-top:1px solid #aeafb1;">Total Amount Before Tax</td>
                                    <td align="right" style="border-top:1px solid #aeafb1;">'.$totaldiscount.'</td>
                                </tr>
                                <tr>
                                    <td rowspan="5" colspan="5" align="center" style="font-weight:bold;font-size:12px; line-height:16px;">'.strtoupper($sub_total_in_words).'</td>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;">Add: CGST 9.00%</td>
                                    <td align="right">'.number_format($vatSumTotal, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;">Add: SGST 9.00%</td>
                                    <td align="right">'.number_format($vatSumTotal, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;">Total Tax Amount</td>
                                    <td align="right">'.number_format($totalVatSum, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold;color:#ffffff; line-height:16px;" bgColor="#157ca7">Total Amount After Tax</td>
                                    <td align="right" bgColor="#157ca7" style="font-weight:bold;color:#ffffff;">'.number_format($overallTotal, 2).'</td>
                                </tr>
                            </tbody>
                        </table>';

                }
            } else {

                if($Row['show_discount_percentage'] == 1){ 
                    $tbl3 = $tbl3.'
                                    '.$emptyRow.'
                                    <tr>
                                        <td colspan="7" align="right" style="font-weight:bold; line-height:16px;border-top:1px solid #aeafb1;">TOTAL</td>
                                        <td align="right" style="border-top:1px solid #aeafb1;">'.$totaldiscount.'</td>
                                    </tr>
                                </tbody>
                            </table>';
                } else {
                    $tbl3 = $tbl3.'
                                    '.$emptyRow.'
                                    <tr>
                                        <td colspan="5" style="border-top:1px solid #aeafb1;"></td>
                                        <td align="right" style="font-weight:bold; color:#ffffff; line-height:16px;border-top:1px solid #aeafb1;" bgColor="#157ca7">TOTAL</td>
                                        <td align="right" style="font-weight:bold; color:#ffffff; line-height:16px;border-top:1px solid #aeafb1;" bgColor="#157ca7">'.$totaldiscount.'</td>
                                    </tr>
                                    <br/>
                                    <br/>
                                    <tr>
                                        <td colspan="7" align="right" style="">('.strtoupper($Total_in_words).')</td>
                                    </tr>
                                </tbody>
                            </table>';
                }
            }

            if($Row['gst_status'] == "ON"){

                $tbl4 = '<table cellpadding="4" border="0" width="100%" style="font-size:11px;">';

                if($Row['igst_show'] == "1"){
                    $tbl4 = $tbl4.'
                        <br/>
                        <br/>
                        <tr>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="right">Tax Rate</td>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="right">Taxable</td>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="right">IGST</td>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="right">Total Tax</td>
                        </tr>
                    ';
                }else{
                    $tbl4 = $tbl4.'
                        <br/>
                        <br/>
                        <tr>
                            <td rowspan="2" style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Tax Rate</td>
                            <td rowspan="2" style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Taxable</td>
                            <td colspan="2" style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">CGST</td>
                            <td colspan="2" style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">SGST</td>
                            <td rowspan="2" style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Total Tax</td>
                        </tr>
                        <tr>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Rate</td>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Amount</td>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Rate</td>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Amount</td>
                        </tr>
                    ';
                }

                

                $SQLTax = "
                SELECT  p.gst
                        ,ci.credit_note_id
                        ,p.hsn AS hsn_sac
                        ,SUM(ci.unit_price * ci.qty) AS qty_amount
                FROM `credit_note_item` ci
                LEFT JOIN product p ON (p.product_id = ci.record_id)
                WHERE ci.credit_note_id = '{$Row['credit_note_id']}'
                AND p.gst > 0
                GROUP BY p.gst
                ORDER BY p.gst ASC
                ";
                $resultTax  = $db->sql_query($SQLTax);

                $totalVatSum = 0;
                $counter = 1;
                while($rowTax     = $db->sql_fetchrow($resultTax)){

                    $total_amount = $rowTax['qty_amount'];
                    
                    if($rowTax['gst'] == ''){
                        $vatPercent = '0.00';
                    }
                    else{
                        $vatPercent = $rowTax['gst'];
                    }

                    $vat_Sum  = ($total_amount * $rowTax['gst'])/100;

                    $gstRatePercent = $rowTax['gst'] / 2;

                    //$vat_Amount_total = $total_amount + $vat_Sum;
                    $vat_Amount_total = $total_amount;
                    if($vat_Sum == 0){
                        $vat_Amount_total = 0;
                    }

                    $vatPercentHalf = $vatPercent / 2;
                    $vat_Sum_Half   = $vat_Sum / 2;

                    $totalVatSum += $vat_Sum;

                    $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);
                    $total_tax = $vat_Sum_Half + $vat_Sum_Half;
                    if($Row['igst_show'] == "1"){
                        $tbl4 = $tbl4.'
                        <tr>
                            <td align="right">'.$rowTax['gst'].' %</td>
                            <td align="right">'.number_format($vat_Amount_total, 2).'</td>
                            <td align="right">'.number_format($vat_Sum, 2).'</td>
                            <td align="right">'.number_format($total_tax, 2).'</td>
                        </tr>
                        ';
                    }else{
                        $tbl4 = $tbl4.'
                        <tr>
                            <td align="right">'.$rowTax['gst'].' %</td>
                            <td align="right">'.number_format($vat_Amount_total, 2).'</td>
                            <td align="right">'.number_format($gstRatePercent, 0).'%</td>
                            <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
                            <td align="right">'.number_format($gstRatePercent, 0).'%</td>
                            <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
                            <td align="right">'.number_format($total_tax, 2).'</td>
                        </tr>
                        ';
                    }

                    $counter++;
                }   
            }

            if($Row['gst_status'] == "ON"){

                $total_vat_Amount_total += $vat_Amount_total;
                $total_vat_Sum_Half += $vat_Sum_Half;
                $total_total_tax += $total_tax;
                $total_vat_Sum += $vat_Sum;

                if($Row['igst_show'] == "1"){
                    $tbl4 = $tbl4.'
                    <tr>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">TOTAL</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_vat_Amount_total, 2).'</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_vat_Sum, 2).'</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_total_tax, 2).'</td>
                    </tr>
                    ';

                }else{
                    $tbl4 = $tbl4.'
                    <tr>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">TOTAL</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_vat_Amount_total, 2).'</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;"></td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_vat_Sum_Half, 2).'</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;"></td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_vat_Sum_Half, 2).'</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_total_tax, 2).'</td>
                    </tr>
                    ';
                }

                    $tbl4 = $tbl4.'</table>';

            }

            $tbl5 = '<table cellpadding="4" border="0" width="100%" nobr="true">';

            $tbl5 = $tbl5.'
                <tr>
                <br/>
                <br/>
                <br/>
                <br/>
                <br/>
                <br/>
                <br/>
                    <td width="48%" align="left" style="font-size:11px;font-weight:bold;color:#ffffff;" bgColor="#157ca7"><b>Bank Details : </b></td>
                    <td width="2%"></td>
                    <td width="20%" align="center" rowspan="4" style="font-weight:bold;font-size:11px;vertical-align:bottom;border:1px solid #e5e5e5;"><br/><br/><br/><br/><br/><br/><br/><br/>Common Seal</td>
                    <td width="30%" align="right" rowspan="4" style="font-size:12px;font-weight:bold;">For '.$cpCfg['cp.companyName'].'<br/><br/><br/><br/><br/><br/>Authorised signatory</td>
                </tr>
                <tr>
                    <td width="48%" align=""><span style="font-weight:bold;font-size:11px;color:#000000;"><b>'.$cpCfg['cp.bankDetails'].'</b></span></td>
                    <td width="2%"></td>
                </tr>
                <tr>
                <br/>
                    <td width="48%" align="" bgColor="#157ca7"><span style="font-weight:bold;font-size:11px; color:#ffffff;">Mode of Transport : </span></td>
                    <td width="2%"></td>
                </tr>
                <tr>
                    <td width="48%" align=""><font style="font-size:12px;">Vehicle No. : '.$notes.'</font></td>
                    <td width="2%"></td>
                </tr>
            </table>
            ';            
        }


        $pdf->ln(-5);
        $pdf->writeHTML($tbl1, true, false, false, false, '');
        $pdf->writeHTML($tblQuote, true, false, false, false, '');
        $pdf->writeHTML($tbl2, true, false, false, false, '');
        $pdf->writeHTML($tbl3, true, false, false, false, '');
        $pdf->writeHTML($tbl4, true, false, false, false, '');
        $pdf->writeHTML($tbl5, true, false, false, false, '');

        $pdf->Output();

    }

      /**
     */
    function getDebitPortalDisplay($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $formAction = '';

        $text = "
        <tr class=''>
        <td>
            <div id='' class='invoiceDisplay'>
                <h2>Debit Note(s)</h2>
                <form id='orderItemPrint' class='' method='post' action='{$formAction}'>
                    <div id='invoicePortalOuter'>
                        {$this->getDebitPortalDisplayDetail($row)}
                    </div>
                </form>
            </div>
        </td>
        </tr>
        ";

        return $text;
    }

    
    function getDebitPortalDisplayDetail($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $rows  = "";
        $rowsPvt  = "";
        $links = "";
        $sqlAppend = "";

        $status = $fn->getReqParam('status');

        if ($status) {
            $sqlAppend .= "AND i.status = '{$status}'";
        }

        $_SESSION['selectedInvoiceIds'] = array();
        $exp = array('isEditable' => 1);

        $SQL = "
        SELECT i.*
            
            {$sqlAppend}
        FROM debit_note i
        WHERE i.order_id = {$row['order_id']}
          AND i.invoice_type = 'Client'
        ORDER BY i.debit_note_id
        ";

        $result   = $db->sql_query($SQL);
        $discount = '';
        $tdCheckBox = '';
        $checkBoxStatus = '';
        $count = 1;
        $invoice_code = '';
        $add_registration_fee = '';
        $invoice_hist_amount  = '';

        while ($rowInvoice = $db->sql_fetchrow($result)) {
            $gstvalue = '';
            $gsttaxvalue = '';
            $pfvalue = '';
            $frieghtValue = '';
            $total = '';
            $selectedValuePaid   = '';
            $selectedValueDue    = '';
            $selectedValueCancel = '';

            $urlPrint       = "index.php?_topRm=finance&module=tradingsg_order&_spAction=printDebitRecord&invoice_code={$rowInvoice['invoice_code']}&debit_note_id={$rowInvoice['debit_note_id']}&invoice_type=normal&footer_logo=yes&showHTML=0";
           
           
            if($rowInvoice['status'] != 'Cancelled'){
                $total += $rowInvoice['invoice_amount'];
            }


                $cancelInvoiceLink = '';
                if ($rowInvoice['status'] != 'Cancelled'){
                    $cancelInvoiceLink = "<a href='#' class='cancelDebitNote' invoice_code='{$rowInvoice['invoice_code']}'>Cancel Debit Note</a>";
                }

                $invoice_date = $fn->getCPDate($rowInvoice['invoice_date'], 'd-m-Y');
                $totalvalueRounded = number_format(round($total),2);

                $rows .= "
                <tr>
                    <td>{$rowInvoice['invoice_code']}</td>
                    <td>{$rowInvoice['status']}</td>
                    <td>{$invoice_date}</td>
                    <td align='right'>$totalvalueRounded</td>
                    <td><a href='{$urlPrint}' target='_blank'>Print Debit Note</a></td>
                    <td>{$cancelInvoiceLink}</td>
                </tr>
                ";
            }

            //$invoice_code = $rowInvoice['invoice_code'];
        //}

        $header ="
        <tr style='background-color:#EAEAE8;'>
        <th>Debit Code</th>
        <th>Status</th>
        <th>Debit Date</th>
        <th>Amount</th>
        <th>Print</th>
        <th>Cancel</th>
        </tr>
        ";

        $text = "
        <table class='thinlist'>
            {$header}
            {$rows}
            {$rowsPvt}
        </table>
        ";

        return $text;
    }

     /**
     *
     */
     function getGenerateDebitNoteForm() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        unset($_SESSION['selectedOrderItemIds']);

        $rows = '';

        $order_id = $fn->getReqParam('order_id');
        $date     = $fn->getCurrentDate();
        $due_date = date('Y-m-d', strtotime("+30 days"));
        $qty_balance = '';

        $sqlOrderItem = "
        SELECT * FROM order_item
        WHERE order_id = {$order_id}
        ";
        $resultOrderItem = $db->sql_query($sqlOrderItem);
        while ($rowOI = $db->sql_fetchrow($resultOrderItem)) {
            $sqlQty = "
            SELECT SUM(it.qty) AS qty_invoiced
            FROM debit_note_item it
            JOIN debit_note i ON (i.debit_note_id = it.debit_note_id)
            WHERE i.order_id = {$order_id}
             AND it.record_id = {$rowOI['record_id']}
             AND i.status != 'Cancelled'
            ";
            $resultQty = $db->sql_query($sqlQty);
            $rowQty = $db->sql_fetchrow($resultQty);

            $selling_price = $rowOI['unit_price'] * $rowOI['qty'];

            $qty_balance = $rowOI['qty'] - $rowQty['qty_invoiced'];

            $inputRow = '';
            $qtyRow = '';

            if ($rowQty['qty_invoiced'] != $rowOI['qty']) {
                $pfx = $rowOI['order_item_id'] . '_' ;
                $inputRow = "<input class='orderItemId' type='checkbox' name='orderItemId[]' value='{$rowOI['order_item_id']}'>";
                $qtyRow = "<input type='text' value='{$qty_balance}' id='fld_qty' class='text w50' name='{$pfx}qty'>";
            }

               $pfx1 = $rowOI['order_item_id'] . '_' ;
               
                $unitPriceRow = "<input type='text' value='{$rowOI['unit_price']}' id='fld_unit_price' class='text w50' name='{$pfx1}unit_price'>";


            $rows .= "
            <tr orderRowItem[] = {$rowOI['order_item_id']}>
                <td>
                    {$inputRow}
                </td>
                <td>{$rowOI['item_title']}</td>
                <td>{$rowOI['part_number']}</td>
                <td class='sellingPrice'>{$unitPriceRow}</td>
                <td class=''>{$rowOI['qty']}</td>
                <td class=''>{$qtyRow}</td>
                <td class='qtyBalance'>{$qty_balance}</td>
                <td class=''>{$rowQty['qty_invoiced']}</td>
            </tr>
            ";
        }

        $formAction = "index.php?_topRm=finance&module=tradingsg_order&_spAction=generateDebitFormSubmit&showHTML=0";

        $expNoEdit = array('isEditable' => 0);

        $icgstArr = array(
             "IGST"
            ,"CGST"
        );

        $orderRec = $fn->getRecordRowById('order', 'order_id', $order_id);

            //{$formObj->getTBRow('Add Frieght Cost', 'frieght_cost')}
        $text = "
        <form id='portalDebitForm' class='yform columnar debitNoteForm' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Amount', 'invoice_amount', '', $expNoEdit)}
            {$formObj->getDateRow('Date', 'invoice_date', $date)}
            {$formObj->getDateRow('Due Date', 'invoice_due_date', $due_date)}
            {$formObj->getTBRow('Customer Purchase Order No', 'cust_po_no')}
            {$formObj->getTARow('Terms', 'invoice_terms', $orderRec['invoice_terms'])}
            {$formObj->getTARow('Notes', 'notes', $orderRec['notes'])}
            {$formObj->getTBRow('Issued By', 'staff_id', $_SESSION['userFullName'], $expNoEdit)}
            {$formObj->getTBRow('Add Frieght Cost', 'frieght_cost')}
            {$formObj->getTBRow('Add P & F(%)', 'p_f')}
            <div class='button updateTotal'>
                <a href='#'>Update Total</a>
            </div>

            <table class='thinlist room-order-table'>
                <thead>
                    <th class='click-all-debit'>
                        <a href='#' class='check-all-col-debit'>
                            <img src='{$cpCfg['cp.commonImagesPathAlias']}icons/checkbox_checked.gif'>
                        </a>
                        <a href='#' class='uncheck-all-col-debit'>
                            <img src='{$cpCfg['cp.commonImagesPathAlias']}icons/checkbox_unchecked.gif'>
                        </a>
                    </th>
                    <th>Product Name</th>
                    <th>Part Number</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th class=''>Qty (Current Invoice)</th>
                    <th>Qty (Balance)</th>
                    <th>Qty (Invoiced)</th>
                </thead>

                <tbody>
                    {$rows}
                </tbody>
            </table>

            <input type='hidden' name='order_id' value='{$order_id}' />
            <input type='hidden' name='qty_balance' value='{$qty_balance}' />
        </form>
        ";

        //{$formObj->getTBRow('Add Frieght(%)', 'frieght')}

        return $text;
    }

	/**
     *
     */
    function getPrintDebitRecord() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $searchVar = Zend_Registry::get('searchVar');
        $media = Zend_Registry::get('media');
        $cpPaths = Zend_Registry::get('cpPaths');
        $dbUtil = Zend_Registry::get('dbUtil');
        $site_id  = $fn->getSessionParam('cp_site_id');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('USS');
        $pdf->SetSubject('Proforma Invoice');
        $pdf->SetTitle('Proforma Invoice');

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

        $invoiceHeading = '';

        $debit_note_id = $fn->getReqParam('debit_note_id');
        $invoice_code = $fn->getReqParam('invoice_code');
        $invoice_type = $fn->getReqParam('invoice_type');
        if($invoice_type == 'normal'){
            $invoiceHeading = 'ORIGINAL - ';
        }
        else if($invoice_type == 'transporter'){
            $invoiceHeading = 'TRANSPORTER - ';
        }
        else if($invoice_type == 'proforma'){
            $invoiceHeading = 'PROFORMA - ';
        }
        else if($invoice_type == 'extra'){
            $invoiceHeading = 'DUPLICATE - ';
        }

        $SQL = "
        SELECT ini.*
              ,ini.item_title AS product_title
              ,p.title AS product_title1
              ,p.unit
              ,p.item_code
              ,p.part_number
              ,p.description_short
              ,p.hsn
              ,c.company_name
              ,c.address_flat
              ,c.address_street
              ,c.address_town
              ,c.address_state
              ,c.address_po_code
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
              ,c.tin_no
              ,c.cst_no
              ,c.gst_no
              ,i.invoice_date
              ,q.delivery_date
              ,q.delivery_location
              ,q.gst_enabled
              ,q.show_discount_percentage
              ,q.currency
              ,q.payment_terms
              ,q.delivery_terms
              ,ini.unit_price
              ,i.invoice_code
              ,i.invoice_terms
              ,i.invoice_due_date
              ,i.notes
              ,i.cst
              ,i.vat
              ,i.cst_value
              ,i.vat_value
              ,i.frieght_cost
              ,i.cust_po_no
              ,i.p_f
              ,i.debit_note_id
              ,o.order_id
              ,o.notes
              ,o.shipping_address1
              ,o.shipping_first_name
              ,o.shipping_address2
              ,o.shipping_address_city
              ,o.shipping_address_state
              ,o.gst_status
              ,o.igst_show
               ,(SELECT gc.name FROM geo_country gc
                 WHERE gc.country_code = o.shipping_address_country)
                 AS shipping_address_country
              ,q.quote_code
              ,q.currency
              ,ini.qty * ini.unit_price AS amount
              ,(SELECT SUM(init.qty * init.cost_price) FROM debit_note_item init
               WHERE init.debit_note_id = ini.debit_note_id) AS sub_total
              ,(SELECT SUM(init.qty * init.unit_price) FROM debit_note_item init
               WHERE init.debit_note_id = ini.debit_note_id) AS total
              ,CONCAT_WS(' ', co.first_name, co.last_name) AS contact_name
              ,co.salutation
              ,(SELECT invoice_code FROM invoice inv WHERE inv.order_id = o.order_id AND inv.status != 'Cancelled') AS invoiceCode
              ,(SELECT invoice_date FROM invoice inv WHERE inv.order_id = o.order_id AND inv.status != 'Cancelled') AS debitDate
        FROM debit_note_item ini
        LEFT JOIN product p ON (p.product_id = ini.record_id)
        LEFT JOIN debit_note i ON (i.debit_note_id = ini.debit_note_id)
        LEFT JOIN `order` o ON (o.order_id = i.order_id)
        LEFT JOIN company c ON (c.company_id = o.company_id)
        LEFT JOIN quote q ON (q.quote_id = o.quote_id)
        LEFT JOIN contact co ON (co.contact_id = q.contact_id)
        LEFT JOIN product_group pg ON (p.product_group_id = pg.product_group_id)
        WHERE i.debit_note_id = '{$debit_note_id}'
          AND i.status != 'Cancelled'
        ORDER BY ini.debit_note_item_id, pg.sort_order ASC, p.title
        ";
        $result = $db->sql_query($SQL);
        $result2 = $db->sql_query($SQL);
        $Row = $db->sql_fetchrow($result2);

        $numRows  = $db->sql_numrows($result);
        //============================================================================= //

        $pdf->SetFont('helvetica','', 8);
        $today = date("d-m-Y");

        if($site_id == 1) {
            $tbl1 = '
            <table border="0" width="100%" style="font-size:17px;">
                <tr>
                    <td align="center" style="font-weight:bold; text-decoration: underline;">DEBIT NOTE</td>
                </tr>
            </table>
            ';

            $tblQuote ='
            <table border="0" width="100%" cellpadding="3">
            </table>
            ';

            $contact_name = '';
            if($Row['contact_name'] != ''){
                $contact_name = "Kind Attn: {$Row['salutation']}.{$Row['contact_name']}";
            }

            $addressFlat     = $Row['address_flat'];
            $addressStreet   = $Row['address_street'];
            $addressTown     = $Row['address_town'];
            $addressState    = $Row['address_state'];
            $addressCountry  = $Row['address_country'];

            $billingAddressFlat     = $Row['billing_address_flat'];
            $billingAddressStreet   = $Row['billing_address_street'];
            $billingAddressTown     = $Row['billing_address_town'];
            $billingAddressState    = $Row['billing_address_state'];
            $billingAddressCountry  = $Row['billing_address_country'];
            $invoiceDate   = $fn->getCPDate($Row['invoice_date'], 'd-m-Y');
                        $creditDate   = $fn->getCPDate($Row['debitDate'], 'd-m-Y');


            $tbl2 ='
            <table border="0" width="100%" cellpadding="3">
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Debit Note No : </i>'.$Row['invoice_code'].'</td>
                    <td width="50%" style="border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Reference Invoice: </i> '.$Row['invoiceCode'].' </td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Date Of Issue : </i>'.$invoiceDate.'</td>
                    <td width="50%" style="border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Date Of Invoice : </i> '.$creditDate.'</td>
                </tr>
                  <tr>
                    <td width="25%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>State : </i></td>
                    <td width="13%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Code : </i></td>
                    <td width="12%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;">'.$Row['address_po_code'].'</td>
                    <td width="50%" style="border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"></td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>From :</i></td>
                    <td width="50%" style="border-right:1px solid #000000;border-top:1px solid #000000;font-size:11px;font-weight:bold;"><i>Billed to :</i></td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$cpCfg['cp.companyName'].'</td>
                    <td width="50%" style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$Row['company_name'].'</td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$cpCfg['cp.addressPdf5'].'</td>
                    <td width="50%" style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$Row['address_flat'].'</td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;">'.$cpCfg['cp.panNoPdf'].'</td>
                    <td width="50%" style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">'.$Row['address_street'].' '.$Row['address_town'].' '.$Row['address_state'].'</td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;font-size:11px;"></td>
                    <td width="50%" style="border-right:1px solid #000000;font-size:11px;font-weight:bold;">GST IN / UIN :'.$Row['gst_no'].'</td>
                </tr>
                <tr>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-bottom:1px solid #000000;font-size:11px;"><span style="font-weight:bold;">State Code: 33</span></td>
                    <td width="50%" style="border-left:1px solid #000000;border-right:1px solid #000000;border-bottom:1px solid #000000;font-size:11px;"><span style="font-weight:bold;">State Code: </span>'.$Row['address_po_code'].'</td>
                </tr>
            </table>
            ';


            if($Row['gst_status'] == "ON"){
                $tbl3 ='
                <table border="1" nobr="true" width="100%" cellpadding="3" style="font-size:10px;">
                    <thead>
                        <tr>
                            <th width="5%"  style="border:1px solid #000000;font-weight:bold;" align="center">S.No</th>
                            <th width="35%" style="border:1px solid #000000;font-weight:bold;" align="left">Product Description</th>
                            <th width="10%"  style="border:1px solid #000000;font-weight:bold;" align="center">HSN Code</th>
                            <th width="6%"  style="border:1px solid #000000;font-weight:bold;" align="center">UOM</th>
                            <th width="7%"  style="border:1px solid #000000;font-weight:bold;" align="center">Qty</th>
                            <th width="12%"  style="border:1px solid #000000;font-weight:bold;" align="right">Price</th>
                            <th width="12%"  style="border:1px solid #000000;font-weight:bold;" align="right">Taxbl Val</th>
                            <th width="13%" style="border:1px solid #000000;font-weight:bold;" align="right">Total</th>
                        </tr>
                    </thead>
                ';
            }

            else {
                if($Row['show_discount_percentage'] == 1){
                    $tbl3 = '
                    <table border="1" width="100%" cellpadding="3" style="font-size:11px;">
                        <thead>
                            <tr>
                                <td width="6%"  style="font-weight:bold;" align="center">S.No</td>
                                <td width="26%" style="font-weight:bold;" align="left">Product Description</td>
                                <td width="15%" style="font-weight:bold;" align="center">HSN Code</td>
                                <td width="10%" style="font-weight:bold;" align="center">UOM</td>
                                <td width="8%"  style="font-weight:bold;" align="center">Qty</td>
                                <td width="10%" style="font-weight:bold;" align="right">Price</td>
                                <td width="10%" style="font-weight:bold;" align="right">Discount</td>
                                <td width="15%" style="font-weight:bold;" align="right">Total ('.$Row['currency'].')</td>
                            </tr>
                        </thead>
                        <tbody>
                    ';
                } else {
                    $tbl3 = '
                    <table border="1" width="100%" cellpadding="3" style="font-size:11px;">
                        <thead>
                            <tr>
                                <td width="6%"  style="font-weight:bold;" align="center">S.No</td>
                                <td width="29%" style="font-weight:bold;" align="left">Product Description</td>
                                <td width="15%" style="font-weight:bold;" align="center">HSN Code</td>
                                <td width="10%" style="font-weight:bold;" align="center">UOM</td>
                                <td width="10%" style="font-weight:bold;" align="center">Qty</td>
                                <td width="15%" style="font-weight:bold;" align="right">Price</td>
                                <td width="15%" style="font-weight:bold;" align="right">Total ('.$Row['currency'].')</td>
                            </tr>
                        </thead>
                        <tbody>
                    ';
                }
            }

            $count = 1;
            $overallTotal = 0;
            $vatSumTotal  = 0;
            while($row = $db->sql_fetchrow($result)){

                $discount_value_for_one_qty = 0;
                $discount_value_for_display = 0;
                if($row['discount_percentage'] > 0){
                    if($row['discount_type'] == '%'){
                        $discount_value_for_one_qty  =  $row['cost_price'] * ($row['discount_percentage']/100);
                        $discount_value_for_display  =  $row['discount_percentage'] . '%';
                    }
                    else if($row['discount_type']  == 'Value'){
                        $discount_value_for_one_qty  =  $row['discount_percentage'];
                        $discount_value_for_display  =  $row['discount_percentage'];
                    }
                }
 if($row['show_discount_percentage'] != 1){
                    $selling_price = $row['unit_price'];
                    $tsp = ($row['qty'] * $selling_price);

                    $SQLTax = "
                    SELECT  p.gst
                            ,SUM(ci.unit_price * ci.qty) AS qty_amount
                    FROM `debit_note_item` ci
                    LEFT JOIN `debit_note` cn ON (cn.debit_note_id = ci.debit_note_id)
                    LEFT JOIN `product` p ON (p.product_id = ci.record_id)
                    WHERE ci.debit_note_item_id = '{$row['debit_note_item_id']}'
                    AND p.gst > 0
                    AND cn.status != 'Cancelled'
                    ";
                    $resultTax  = $db->sql_query($SQLTax);
                    $rowTax     = $db->sql_fetchrow($resultTax);

                } else {
                    $selling_price = $row['unit_price'];
                    $tsp = ($row['qty'] * $selling_price);

                    $SQLTax = "
                    SELECT  p.gst
                            ,SUM(ci.unit_price * ci.qty) AS qty_amount
                     FROM `debit_note_item` ci
                     LEFT JOIN `debit_note` cn ON (cn.debit_note_id = ci.debit_note_id)
                    LEFT JOIN `product` p ON (p.product_id = ci.record_id)
                    WHERE ci.debit_note_item_id = '{$row['debit_note_item_id']}'
                    AND p.gst > 0
                    AND cn.status != 'Cancelled'
                    ";
                    $resultTax  = $db->sql_query($SQLTax);
                    $rowTax     = $db->sql_fetchrow($resultTax);
                }
                $titledesc = $row['item_title'];

                $titledescrip = $titledesc;
                $discount_value_for_one_qty = number_format($discount_value_for_one_qty, 2);

                $totalVatSum = 0;

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
                $vat_Sum_Half   = $vat_Sum / 2;

                $totalVatSum += $vat_Sum;

                $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);
                
                if($row['gst_status'] == "ON"){
                    $total = $tsp + $vat_Sum_Half + $vat_Sum_Half;
                } else {
                    $total = $tsp;
                }

                $tsp   = $tsp - $row['discount_amount'];
                $total = $total - $row['discount_amount'];
                $selling_price = number_format($selling_price,2);

                if($row['gst_status'] == "ON"){
                    $tbl3 = $tbl3.'<tr>
                                        <td width="5%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$count.'</td>
                                        <td width="35%" style="border-left:1px solid #000000;border-right:1px solid #000000;" align="left">'.$titledescrip.'</td>
                                        <td width="10%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['hsn'].'</td>
                                        <td width="6%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['unit'].'</td>
                                        <td width="7%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="center">'.$row['qty'].'</td>
                                        <td width="12%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$selling_price.'</td>
                                        <td width="12%"  style="border-left:1px solid #000000;border-right:1px solid #000000;" align="right">'.$tsp.'</td>
                                        <td width="13%" style="border-left:1px solid #000000;border-right:1px solid #000000;"  align="right">'.number_format($total, 2).'</td>
                                    </tr>
                                    ';
                    $count++;
                } else {

                    if($row['show_discount_percentage'] == 1){    
                        $tbl3 = $tbl3.'<tr>
                                            <td width="6%"  align="center">'.$count.'</td>
                                            <td width="26%" align="left">'.$titledescrip.'</td>
                                            <td width="15%" align="center">'.$row['hsn'].'</td>
                                            <td width="10%" align="center">'.$row['unit'].'</td>
                                            <td width="8%"  align="center">'.$row['qty'].'</td>
                                            <td width="10%" align="right">'.$selling_price.'</td>
                                            <td width="10%" align="right">'.$discount_value_for_display.'</td>
                                            <td width="15%" align="right">'.number_format($tsp, 2).'</td>
                                        </tr>
                                       ';

                        $count++;
                    }else {
                        $tbl3 = $tbl3.'<tr>
                                            <td width="6%"  align="center">'.$count.'</td>
                                            <td width="29%" align="left">'.$titledescrip.'</td>
                                            <td width="15%" align="center">'.$row['hsn'].'</td>
                                            <td width="10%" align="center">'.$row['unit'].'</td>
                                            <td width="10%" align="center">'.$row['qty'].'</td>
                                            <td width="15%" align="right">'.$selling_price.'</td>
                                            <td width="15%" align="right">'.number_format($tsp, 2).'</td>
                                        </tr>
                                       ';

                        $count++;
                    }
                }

                $overallTotal += $total;
                $vatSumTotal  += $vat_Sum_Half;

                $total = $row['total'];
                $terms = $row['payment_terms'];
                $notes = $row['notes'];
                $delivery_terms = $row['delivery_terms'];
                $discount = 0;
                $sub_total = $total + $discount - $row['discount_amount'];
                $show_discount_percentage = $row['show_discount_percentage'];
            }
            $tbl4 = '';

            $totaldiscount = $sub_total - $discount;
            $discountPercent = $discount * 100 / $sub_total;
            $totaldiscount = number_format(round($totaldiscount), 2);
            $sub_total = number_format($sub_total,2);
            $discount = number_format($discount,2);
            $discountPercent = number_format($discountPercent,2);
            $displayDiscountPercent = '';

            if($Row['gst_status'] == "ON") {
                //$sub_total_in_words = $fn->getConvertNumber($overallTotal .'.00');
                $sub_total_in_words = $fn->getIndianCurrency($overallTotal .'.00');
                if($Row['igst_show'] == "1"){
                    $tbl3 = $tbl3.'
                                <tr>
                                    <td colspan="5" align="center" style="font-weight:bold;">Total Amount in Words</td>
                                    <td colspan="2" align="right" style="font-weight:bold;">Total Amount Before Tax</td>
                                    <td align="right">'.$totaldiscount.'</td>
                                </tr>
                                <tr>
                                    <td rowspan="5" colspan="5" align="center" style="font-weight:bold;font-size:12px;">'.strtoupper($sub_total_in_words).'</td>
                                    <td colspan="2" align="right" style="font-weight:bold;">Add: IGST 18.00%</td>
                                    <td align="right">'.number_format($totalVatSum, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold;">Total Amount After Tax</td>
                                    <td align="right">'.number_format($overallTotal, 2).'</td>
                                </tr>
                            </tbody>
                        </table>';
                } else {
                    $tbl3 = $tbl3.'
                                <tr>
                                    <td colspan="5" align="center" style="font-weight:bold;">Total Amount in Words</td>
                                    <td colspan="2" align="right" style="font-weight:bold;">Total Amount Before Tax</td>
                                    <td align="right">'.$totaldiscount.'</td>
                                </tr>
                                <tr>
                                    <td rowspan="5" colspan="5" align="center" style="font-weight:bold;font-size:12px;">'.strtoupper($sub_total_in_words).'</td>
                                    <td colspan="2" align="right" style="font-weight:bold;">Add: CGST 9.00%</td>
                                    <td align="right">'.number_format($vatSumTotal, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold;">Add: SGST 9.00%</td>
                                    <td align="right">'.number_format($vatSumTotal, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold;">Total Tax Amount</td>
                                    <td align="right">'.number_format($totalVatSum, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold;">Total Amount After Tax</td>
                                    <td align="right">'.number_format($overallTotal, 2).'</td>
                                </tr>
                            </tbody>
                        </table>';

                }
            } else {

                if($Row['show_discount_percentage'] == 1){ 
                    $tbl3 = $tbl3.'
                                    <tr>
                                        <td colspan="7" align="right" style="font-weight:bold;">TOTAL</td>
                                        <td align="right">'.$totaldiscount.'</td>
                                    </tr>
                                </tbody>
                            </table>';
                } else {
                    $tbl3 = $tbl3.'
                                    <tr>
                                        <td colspan="6" align="right" style="font-weight:bold;">TOTAL</td>
                                        <td align="right">'.$totaldiscount.'</td>
                                    </tr>
                                </tbody>
                            </table>';
                }
            }

            $tbl5 = '<table cellpadding="4" border="1" width="100%" nobr="true">';

            $tbl5 = $tbl5.'
                <tr>
                    <td width="50%" align="left" style="font-size:10px;font-weight:bold;"><b>Bank Details : </b><br/><b>'.$cpCfg['cp.bankDetails'].'</b></td>
                    <td width="20%" align="center"></td>
                    <td width="30%" align="right" rowspan="2" style="font-size:12px;font-weight:bold;">For '.$cpCfg['cp.companyName'].'<br/><br/><br/><br/><br/><br/>Authorised signatory</td>
                </tr>
                <tr>
                    <td width="50%" align=""><span style="font-weight:bold;font-size:11px;">Mode of Transport : </span><br/><br/>Vehicle No. : '.$notes.'</td>
                    <td width="20%" align="center" style="font-weight:bold;font-size:11px;vertical-align:bottom;"><br/><br/><br/><br/>Common Seal</td>
                </tr>
            </table>
            ';
        } else {
            $pdf->SetFont('calibri','', 8);
            $invoiceDate   = $fn->getCPDate($Row['invoice_date'], 'd-m-Y');
            $creditDate   = $fn->getCPDate($Row['debitDate'], 'd-m-Y');


            $tblQuote ='
            <table border="0" width="100%" cellpadding="3">
            </table>
            ';

            $contact_name = '';
            if($Row['contact_name'] != ''){
                $contact_name = "Kind Attn: {$Row['salutation']}.{$Row['contact_name']}";
            }

            $addressFlat     = $Row['address_flat'];
            $addressStreet   = $Row['address_street'];
            $addressTown     = $Row['address_town'];
            $addressState    = $Row['address_state'];
            $addressCountry  = $Row['address_country'];

            $billingAddressFlat     = $Row['billing_address_flat'];
            $billingAddressStreet   = $Row['billing_address_street'];
            $billingAddressTown     = $Row['billing_address_town'];
            $billingAddressState    = $Row['billing_address_state'];
            $billingAddressCountry  = $Row['billing_address_country'];

            $tbl1 = '
            <table border="0" width="100%" cellpadding="3">
                <tr>
                    <td width="27%" style="font-size:13px;font-weight:bold;color:#157ca7;"><strong>From :</strong><br/><font style="font-size:14px;font-weight:bold;color:#000000;">'.$cpCfg['cp.companyName'].'<br/></font><font style="font-size:11px;color:#000000;">'.$cpCfg['cp.addressPdf5'].'<br/>'.$cpCfg['cp.panNoPdf'].'<br/>State Code: 33</font></td>
                    <td width="43%" style="font-size:13px;font-weight:bold;color:#157ca7;"><strong>Billed to :</strong><br/><font style="font-size:14px;font-weight:bold;color:#000000;">'.$Row['company_name'].'<br/></font><font style="font-size:11px;color:#000000;">'.$Row['address_flat'].', '.$Row['address_street'].' '.$Row['address_town'].' '.$Row['address_state'].'<br/>GST IN / UIN :'.$Row['gst_no'].'<br/>State Code: '.$Row['address_po_code'].'</font></td>
                    <td width="30%" style="font-weight:bold;font-size:22px;">DEBIT NOTE <font style="font-size:12px;font-weight:bold;"><br/><br/><i>Code : </i>'.$Row['invoice_code'].'<br/><i>Date : </i>'.$invoiceDate.'<br/><i>Reference Invoice : </i>'.$Row['invoiceCode'].'<br/><i>Invoice Date : </i>'.$creditDate.'</font></td>
                </tr>
            </table>
            ';

            $tbl2 ='';

            if($Row['gst_status'] == "ON"){
                $tbl3 ='
                <table border="0" nobr="true" width="100%" cellpadding="4" style="font-size:11px;">
                    <thead>
                        <tr>
                            <th width="5%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">S.No</th>
                            <th width="35%" style="color:#fff;font-weight:bold; line-height:16px;" align="left" bgColor="#157ca7">Product Description</th>
                            <th width="10%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">HSN Code</th>
                            <th width="6%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">UOM</th>
                            <th width="7%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">Qty</th>
                            <th width="12%"  style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Price</th>
                            <th width="12%"  style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Taxbl Val</th>
                            <th width="13%" style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Total</th>
                        </tr>
                    </thead>
                ';
            }

            else {
                if($Row['show_discount_percentage'] == 1){
                    $tbl3 = '
                    <table border="0" width="100%" cellpadding="4" style="font-size:11px;">
                        <thead>
                            <tr>
                                <td width="6%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">S.No</td>
                                <td width="26%" style="color:#fff;font-weight:bold; line-height:16px;" align="left" bgColor="#157ca7">Product Description</td>
                                <td width="15%" style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">HSN Code</td>
                                <td width="10%" style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">UOM</td>
                                <td width="8%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">Qty</td>
                                <td width="10%" style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Price</td>
                                <td width="10%" style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Discount</td>
                                <td width="15%" style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Total ('.$Row['currency'].')</td>
                            </tr>
                        </thead>
                        <tbody>
                    ';
                } else {
                    $tbl3 = '
                    <table border="0" width="100%" cellpadding="4" style="font-size:11px;">
                        <thead>
                            <tr>
                                <td width="6%"  style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">S.No</td>
                                <td width="29%" style="color:#fff;font-weight:bold; line-height:16px;" align="left" bgColor="#157ca7">Product Description</td>
                                <td width="15%" style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">HSN Code</td>
                                <td width="10%" style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">UOM</td>
                                <td width="10%" style="color:#fff;font-weight:bold; line-height:16px;" align="center" bgColor="#157ca7">Qty</td>
                                <td width="15%" style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Price</td>
                                <td width="15%" style="color:#fff;font-weight:bold; line-height:16px;" align="right" bgColor="#157ca7">Total ('.$Row['currency'].')</td>
                            </tr>
                        </thead>
                        <tbody>
                    ';
                }
            }

            $tbl4 = '';

            $count = 1;
            $overallTotal = 0;
            $vatSumTotal  = 0;
            $total_vat_Amount_total = 0;
            $total_vat_Sum_Half = 0;
            $total_vat_Sum = 0;
            $total_total_tax = 0;
            $total_tax = 0;
            while($row = $db->sql_fetchrow($result)){

                $discount_value_for_one_qty = 0;
                $discount_value_for_display = 0;
                if($row['discount_percentage'] > 0){
                    if($row['discount_type'] == '%'){
                        $discount_value_for_one_qty  =  $row['cost_price'] * ($row['discount_percentage']/100);
                        $discount_value_for_display  =  $row['discount_percentage'] . '%';
                    }
                    else if($row['discount_type']  == 'Value'){
                        $discount_value_for_one_qty  =  $row['discount_percentage'];
                        $discount_value_for_display  =  $row['discount_percentage'];
                    }
                }

               if($row['show_discount_percentage'] != 1){
                    $selling_price = $row['unit_price'];
                    $tsp = ($row['qty'] * $selling_price);

                    $SQLTax = "
                    SELECT  p.gst
                            ,SUM(ci.unit_price * ci.qty) AS qty_amount
                    FROM `debit_note_item` ci
                    LEFT JOIN `debit_note` cn ON (cn.debit_note_id = ci.debit_note_id)
                    LEFT JOIN `product` p ON (p.product_id = ci.record_id)
                    WHERE ci.debit_note_item_id = '{$row['debit_note_item_id']}'
                    AND p.gst > 0
                    AND cn.status != 'Cancelled'
                    ";
                    $resultTax  = $db->sql_query($SQLTax);
                    $rowTax     = $db->sql_fetchrow($resultTax);

                } else {
                    $selling_price = $row['unit_price'];
                    $tsp = ($row['qty'] * $selling_price);

                    $SQLTax = "
                    SELECT  p.gst
                            ,SUM(ci.unit_price * ci.qty) AS qty_amount
                     FROM `debit_note_item` ci
                     LEFT JOIN `debit_note` cn ON (cn.debit_note_id = ci.debit_note_id)
                    LEFT JOIN `product` p ON (p.product_id = ci.record_id)
                    WHERE ci.debit_note_item_id = '{$row['debit_note_item_id']}'
                    AND p.gst > 0
                    AND cn.status != 'Cancelled'
                    ";
                    $resultTax  = $db->sql_query($SQLTax);
                    $rowTax     = $db->sql_fetchrow($resultTax);
                }

                $titledesc = $row['item_title'];

                $titledescrip = $titledesc;
                $discount_value_for_one_qty = number_format($discount_value_for_one_qty, 2);

                $totalVatSum = 0;

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
                $vat_Sum_Half   = $vat_Sum / 2;

                $totalVatSum += $vat_Sum;

                $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);
                
                if($row['gst_status'] == "ON"){
                    $total = $tsp + $vat_Sum_Half + $vat_Sum_Half;
                } else {
                    $total = $tsp;
                }

                $tsp   = $tsp - $row['discount_amount'];
                $total = $total - $row['discount_amount'];
                $selling_price = number_format($selling_price,2);

                if($row['gst_status'] == "ON"){
                    $tbl3 = $tbl3.'<tr>
                                        <td width="5%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$count.'</td>
                                        <td width="35%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="left">'.$titledescrip.'</td>
                                        <td width="10%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['hsn'].'</td>
                                        <td width="6%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['unit'].'</td>
                                        <td width="7%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['qty'].'</td>
                                        <td width="12%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="right">'.$selling_price.'</td>
                                        <td width="12%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="right">'.$tsp.'</td>
                                        <td width="13%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;"  align="right">'.number_format($total, 2).'</td>
                                    </tr>
                                    ';
                    $count++;
                } else {

                    if($row['show_discount_percentage'] == 1){    
                        $tbl3 = $tbl3.'<tr>
                                            <td width="6%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$count.'</td>
                                            <td width="26%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="left">'.$titledescrip.'</td>
                                            <td width="15%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['hsn'].'</td>
                                            <td width="10%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['unit'].'</td>
                                            <td width="8%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['qty'].'</td>
                                            <td width="10%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="right">'.$selling_price.'</td>
                                            <td width="10%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="right">'.$discount_value_for_display.'</td>
                                            <td width="15%" align="right" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;">'.number_format($tsp, 2).'</td>
                                        </tr>
                                       ';

                        $count++;
                    }else {
                        $tbl3 = $tbl3.'<tr>
                                            <td width="6%"  style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$count.'</td>
                                            <td width="29%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="left">'.$titledescrip.'</td>
                                            <td width="15%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['hsn'].'</td>
                                            <td width="10%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['unit'].'</td>
                                            <td width="10%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="center">'.$row['qty'].'</td>
                                            <td width="15%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="right">'.$selling_price.'</td>
                                            <td width="15%" style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;line-height:16px;" align="right">'.number_format($tsp, 2).'</td>
                                        </tr>
                                       ';

                        $count++;
                    }
                }

                $overallTotal += $total;
                $vatSumTotal  += $vat_Sum_Half;

                $total = $row['total'];
                $terms = $row['payment_terms'];
                $notes = $row['notes'];
                $delivery_terms = $row['delivery_terms'];
                $discount = 0;
                $sub_total = $total + $discount - $row['discount_amount'];
                $show_discount_percentage = $row['show_discount_percentage'];
            }

            $totaldiscount = $sub_total - $discount;
            $discountPercent = $discount * 100 / $sub_total;
            $Total_in_words = $fn->getIndianCurrency($totaldiscount .'.00');
            $totaldiscount = number_format(round($totaldiscount), 2);
            $sub_total = number_format($sub_total,2);
            $discount = number_format($discount,2);
            $discountPercent = number_format($discountPercent,2);
            $displayDiscountPercent = '';
            $emptyRow = '';

            for($ic = 1; $ic <= 6; $ic++){
                if($Row['gst_status'] == "ON"){
                    if($Row['igst_show'] == "1"){
                        $emptyRow .= '
                        <tr>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                        </tr>
                        ';
                    }else{
                        $emptyRow .= '
                        <tr>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                        </tr>
                        ';
                    }
                }
                else{
                    if($Row['show_discount_percentage'] == 1){ 
                        $emptyRow .= '
                            <tr>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            </tr>
                            ';
                    } else {
                        $emptyRow .= '
                            <tr>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                                <td style="border-left:1px solid #aeafb1;border-right:1px solid #aeafb1;"></td>
                            </tr>
                            ';                            
                    }
                }
            }

            if($Row['gst_status'] == "ON") {
                //$sub_total_in_words = $fn->getConvertNumber($overallTotal .'.00');
                $sub_total_in_words = $fn->getIndianCurrency($overallTotal .'.00');
                if($Row['igst_show'] == "1"){
                    $tbl3 = $tbl3.'
                                '.$emptyRow.'
                                <tr>
                                    <td colspan="5" align="center" style="font-weight:bold; line-height:16px;border-top:1px solid #aeafb1;">Total Amount in Words</td>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;border-top:1px solid #aeafb1;">Total Amount Before Tax</td>
                                    <td align="right" style="border-top:1px solid #aeafb1;">'.$totaldiscount.'</td>
                                </tr>
                                <tr>
                                    <td rowspan="5" colspan="5" align="center" style="font-weight:bold;font-size:12px; line-height:16px;">'.strtoupper($sub_total_in_words).'</td>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;">Add: IGST 18.00%</td>
                                    <td align="right">'.number_format($totalVatSum, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;">Total Amount After Tax</td>
                                    <td align="right">'.number_format($overallTotal, 2).'</td>
                                </tr>
                            </tbody>
                        </table>';
                } else {
                    $tbl3 = $tbl3.'
                                '.$emptyRow.'
                                <tr>
                                    <td colspan="5" align="center" style="font-weight:bold; line-height:16px;border-top:1px solid #aeafb1;">Total Amount in Words</td>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;border-top:1px solid #aeafb1;">Total Amount Before Tax</td>
                                    <td align="right" style="border-top:1px solid #aeafb1;">'.$totaldiscount.'</td>
                                </tr>
                                <tr>
                                    <td rowspan="5" colspan="5" align="center" style="font-weight:bold;font-size:12px; line-height:16px;">'.strtoupper($sub_total_in_words).'</td>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;">Add: CGST 9.00%</td>
                                    <td align="right">'.number_format($vatSumTotal, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;">Add: SGST 9.00%</td>
                                    <td align="right">'.number_format($vatSumTotal, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold; line-height:16px;">Total Tax Amount</td>
                                    <td align="right">'.number_format($totalVatSum, 2).'</td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right" style="font-weight:bold;color:#ffffff; line-height:16px;" bgColor="#157ca7">Total Amount After Tax</td>
                                    <td align="right" bgColor="#157ca7" style="font-weight:bold;color:#ffffff;">'.number_format($overallTotal, 2).'</td>
                                </tr>
                            </tbody>
                        </table>';

                }
            } else {

                if($Row['show_discount_percentage'] == 1){ 
                    $tbl3 = $tbl3.'
                                    '.$emptyRow.'
                                    <tr>
                                        <td colspan="7" align="right" style="font-weight:bold; line-height:16px;border-top:1px solid #aeafb1;">TOTAL</td>
                                        <td align="right" style="border-top:1px solid #aeafb1;">'.$totaldiscount.'</td>
                                    </tr>
                                </tbody>
                            </table>';
                } else {
                    $tbl3 = $tbl3.'
                                    '.$emptyRow.'
                                    <tr>
                                        <td colspan="5" style="border-top:1px solid #aeafb1;"></td>
                                        <td align="right" style="font-weight:bold; color:#ffffff; line-height:16px;border-top:1px solid #aeafb1;" bgColor="#157ca7">TOTAL</td>
                                        <td align="right" style="font-weight:bold; color:#ffffff; line-height:16px;border-top:1px solid #aeafb1;" bgColor="#157ca7">'.$totaldiscount.'</td>
                                    </tr>
                                    <br/>
                                    <br/>
                                    <tr>
                                        <td colspan="7" align="right" style="">('.strtoupper($Total_in_words).')</td>
                                    </tr>
                                </tbody>
                            </table>';
                }
            }

            if($Row['gst_status'] == "ON"){

                $tbl4 = '<table cellpadding="4" border="0" width="100%" style="font-size:11px;">';

                if($Row['igst_show'] == "1"){
                    $tbl4 = $tbl4.'
                        <br/>
                        <br/>
                        <tr>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="right">Tax Rate</td>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="right">Taxable</td>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="right">IGST</td>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="right">Total Tax</td>
                        </tr>
                    ';
                }else{
                    $tbl4 = $tbl4.'
                        <br/>
                        <br/>
                        <tr>
                            <td rowspan="2" style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Tax Rate</td>
                            <td rowspan="2" style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Taxable</td>
                            <td colspan="2" style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">CGST</td>
                            <td colspan="2" style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">SGST</td>
                            <td rowspan="2" style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Total Tax</td>
                        </tr>
                        <tr>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Rate</td>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Amount</td>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Rate</td>
                            <td style="font-weight:bold;color:#fff;" bgColor="#157ca7" align="center">Amount</td>
                        </tr>
                    ';
                }

                

                $SQLTax = "
                SELECT  p.gst
                        ,ci.debit_note_id
                        ,p.hsn AS hsn_sac
                        ,SUM(ci.unit_price * ci.qty) AS qty_amount
                FROM `debit_note_item` ci
                LEFT JOIN product p ON (p.product_id = ci.record_id)
                WHERE ci.debit_note_id = '{$Row['debit_note_id']}'
                AND p.gst > 0
                GROUP BY p.gst
                ORDER BY p.gst ASC
                ";
                $resultTax  = $db->sql_query($SQLTax);

                $totalVatSum = 0;
                $counter = 1;
                while($rowTax     = $db->sql_fetchrow($resultTax)){

                    $total_amount = $rowTax['qty_amount'];
                    
                    if($rowTax['gst'] == ''){
                        $vatPercent = '0.00';
                    }
                    else{
                        $vatPercent = $rowTax['gst'];
                    }

                    $vat_Sum  = ($total_amount * $rowTax['gst'])/100;

                    $gstRatePercent = $rowTax['gst'] / 2;

                    //$vat_Amount_total = $total_amount + $vat_Sum;
                    $vat_Amount_total = $total_amount;
                    if($vat_Sum == 0){
                        $vat_Amount_total = 0;
                    }

                    $vatPercentHalf = $vatPercent / 2;
                    $vat_Sum_Half   = $vat_Sum / 2;

                    $totalVatSum += $vat_Sum;

                    $vatPercentHalf = sprintf('%0.2f', $vatPercentHalf);
                    $total_tax = $vat_Sum_Half + $vat_Sum_Half;
                    if($Row['igst_show'] == "1"){
                        $tbl4 = $tbl4.'
                        <tr>
                            <td align="right">'.$rowTax['gst'].' %</td>
                            <td align="right">'.number_format($vat_Amount_total, 2).'</td>
                            <td align="right">'.number_format($vat_Sum, 2).'</td>
                            <td align="right">'.number_format($total_tax, 2).'</td>
                        </tr>
                        ';
                    }else{
                        $tbl4 = $tbl4.'
                        <tr>
                            <td align="right">'.$rowTax['gst'].' %</td>
                            <td align="right">'.number_format($vat_Amount_total, 2).'</td>
                            <td align="right">'.number_format($gstRatePercent, 0).'%</td>
                            <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
                            <td align="right">'.number_format($gstRatePercent, 0).'%</td>
                            <td align="right">'.number_format($vat_Sum_Half, 2).'</td>
                            <td align="right">'.number_format($total_tax, 2).'</td>
                        </tr>
                        ';
                    }

                    $counter++;
                }   
            }

            if($Row['gst_status'] == "ON"){

                $total_vat_Amount_total += $vat_Amount_total;
                $total_vat_Sum_Half += $vat_Sum_Half;
                $total_total_tax += $total_tax;
                $total_vat_Sum += $vat_Sum;

                if($Row['igst_show'] == "1"){
                    $tbl4 = $tbl4.'
                    <tr>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">TOTAL</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_vat_Amount_total, 2).'</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_vat_Sum, 2).'</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_total_tax, 2).'</td>
                    </tr>
                    ';

                }else{
                    $tbl4 = $tbl4.'
                    <tr>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">TOTAL</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_vat_Amount_total, 2).'</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;"></td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_vat_Sum_Half, 2).'</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;"></td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_vat_Sum_Half, 2).'</td>
                        <td align="right" style="border-top:1px solid #aeafb1;font-weight:bold;border-bottom:1px solid #aeafb1;">'.number_format($total_total_tax, 2).'</td>
                    </tr>
                    ';
                }

                    $tbl4 = $tbl4.'</table>';

            }

            $tbl5 = '<table cellpadding="4" border="0" width="100%" nobr="true">';

            $tbl5 = $tbl5.'
                <tr>
                <br/>
                <br/>
                <br/>
                <br/>
                <br/>
                <br/>
                <br/>
                    <td width="48%" align="left" style="font-size:11px;font-weight:bold;color:#ffffff;" bgColor="#157ca7"><b>Bank Details : </b></td>
                    <td width="2%"></td>
                    <td width="20%" align="center" rowspan="4" style="font-weight:bold;font-size:11px;vertical-align:bottom;border:1px solid #e5e5e5;"><br/><br/><br/><br/><br/><br/><br/><br/>Common Seal</td>
                    <td width="30%" align="right" rowspan="4" style="font-size:12px;font-weight:bold;">For '.$cpCfg['cp.companyName'].'<br/><br/><br/><br/><br/><br/>Authorised signatory</td>
                </tr>
                <tr>
                    <td width="48%" align=""><span style="font-weight:bold;font-size:11px;color:#000000;"><b>'.$cpCfg['cp.bankDetails'].'</b></span></td>
                    <td width="2%"></td>
                </tr>
                <tr>
                <br/>
                    <td width="48%" align="" bgColor="#157ca7"><span style="font-weight:bold;font-size:11px; color:#ffffff;">Mode of Transport : </span></td>
                    <td width="2%"></td>
                </tr>
                <tr>
                    <td width="48%" align=""><font style="font-size:12px;">Vehicle No. : '.$notes.'</font></td>
                    <td width="2%"></td>
                </tr>
            </table>
            ';            
        }


        $pdf->ln(-5);
        $pdf->writeHTML($tbl1, true, false, false, false, '');
        $pdf->writeHTML($tblQuote, true, false, false, false, '');
        $pdf->writeHTML($tbl2, true, false, false, false, '');
        $pdf->writeHTML($tbl3, true, false, false, false, '');
        $pdf->writeHTML($tbl4, true, false, false, false, '');
        $pdf->writeHTML($tbl5, true, false, false, false, '');

        $pdf->Output();

    }

}