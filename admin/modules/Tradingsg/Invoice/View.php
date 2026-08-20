<?
class CPL_Admin_Modules_Tradingsg_Invoice_View extends CP_Admin_Modules_Tradingsg_Invoice_View
{

    /**
     *
     */
    function getList($dataArray) {
        $listObj = Zend_Registry::get('listObj');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $currentDate  = date("Y-m-d");

        $count   = 0;
        $rows    = '';
        $rowCounter = 0;
        $hightlightDueTasks='';

        foreach ($dataArray as $row){

            if ($row['status'] =='Due' ||  $row['status'] == 'Partial Payment'){
                if ($row['invoice_due_date'] < $currentDate){
                     $hightlightDueTasks = $listObj->getListRowHeader($row, $rowCounter, 'projectList2');
                }
                else{
                    $hightlightDueTasks = $listObj->getListRowHeader($row, $rowCounter);
                }
            }
            else {
                    $hightlightDueTasks = $listObj->getListRowHeader($row, $rowCounter);
            }

            $urlInvoicePrint = "index.php?_topRm=finance&module=tradingsg_order&_spAction=printInvoiceRecord&invoice_code={$row['invoice_code']}&invoice_type=normal&footer_logo=yes&showHTML=0";
            $printInvoiceRecord = "<a target ='_blank' href='{$urlInvoicePrint}'>{$row['invoice_code']}</a>";

        $rows .="
            {$hightlightDueTasks}
            {$listObj->getListDataCell($printInvoiceRecord)}
            {$listObj->getListDataCell($row['company_name'])}
            {$listObj->getListDateCell($row['invoice_date'])}
            {$listObj->getListDataCell($row['status'])}
            {$listObj->getListDataCell($row['invoice_amount'] ,'right')}
            {$listObj->getListDataCell($row['order_id'], 'center')}
            {$listObj->getListRowEnd($row['invoice_id'])}
            ";

            $rowCounter++;;
        }

        $text = "
        {$listObj->getListHeader()}
        {$listObj->getListHeaderCell('Invoice Code', 'invoice_code')}
        {$listObj->getListHeaderCell('Company Name', 'c.company_name')}
        {$listObj->getListHeaderCell('Invoice Date', 'i.invoice_date')}
        {$listObj->getListHeaderCell('Status', 'i.status')}
        {$listObj->getListHeaderCell('Amount', 'i.invoice_amount', 'headerRight')}
        {$listObj->getListHeaderCell('Order Id', 'i.order_id', 'headerCenter')}
        {$listObj->getListHeaderEnd()}
        {$rows}
        {$listObj->getListFooter()}
        ";

        return $text;
    }

    /**
     *
     */
     function getGenerateInvoiceForm() {
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
            FROM invoice_item it
            JOIN invoice i ON (i.invoice_id = it.invoice_id)
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

            $rows .= "
            <tr orderRowItem[] = {$rowOI['order_item_id']}>
                <td>
                    {$inputRow}
                </td>
                <td>{$rowOI['item_title']}</td>
                <td>{$rowOI['part_number']}</td>
                <td class='sellingPrice'>{$rowOI['unit_price']}</td>
                <td class=''>{$rowOI['qty']}</td>
                <td class=''>{$qtyRow}</td>
                <td class='qtyBalance'>{$qty_balance}</td>
                <td class=''>{$rowQty['qty_invoiced']}</td>
            </tr>
            ";
        }

        $formAction = "index.php?_topRm=finance&module=tradingsg_order&_spAction=generateInvoiceFormSubmit&showHTML=0";

        $expNoEdit = array('isEditable' => 0);

        $icgstArr = array(
             "IGST"
            ,"CGST"
        );

        
          $invoiceIGST = isset($cpCfg['cp.invoiceIGST']) ? $cpCfg['cp.invoiceIGST'] : 0;
        $invoiceCGST = isset($cpCfg['cp.invoiceCGST']) ? $cpCfg['cp.invoiceCGST'] : 0;

        $orderRec = $fn->getRecordRowById('order', 'order_id', $order_id);

            //{$formObj->getTBRow('Add Frieght Cost', 'frieght_cost')}
        $text = "
         <script>
            var invoiceIGSTValue = {$invoiceIGST};
            var invoiceCGSTValue = {$invoiceCGST};
            
            function populateIGSTCGSTValue() {
                var selectedValue = $('select[name=\"igst_cgst\"]').val();
                var igstCgstDiv = $('input[name=\"igst_cgst_value\"]').closest('div');
                
                if(selectedValue === 'IGST') {
                    $('input[name=\"igst_cgst_value\"]').val(invoiceIGSTValue).trigger('change');
                    igstCgstDiv.show(); // Ensure div is visible
                } else if(selectedValue === 'CGST') {
                    $('input[name=\"igst_cgst_value\"]').val(invoiceCGSTValue).trigger('change');
                    igstCgstDiv.show(); // Ensure div is visible
                }
            }
            
            $(document).ready(function() {
                // Populate on page load
                populateIGSTCGSTValue();
                
                // Populate on change
                $('select[name=\"igst_cgst\"]').on('change', function() {
                    populateIGSTCGSTValue();
                });
                
                // Before form submission, ensure the value is set
                $('#portalForm').on('submit', function() {
                    populateIGSTCGSTValue();
                });
            });
        </script>
        <form id='portalForm' class='yform columnar invoiceForm' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Amount', 'invoice_amount', '', $expNoEdit)}
            {$formObj->getDateRow('Date', 'invoice_date', $date)}
            {$formObj->getDateRow('Due Date', 'invoice_due_date', $due_date)}
            {$formObj->getDDRowByArr('IGST/CGST', 'igst_cgst', $icgstArr)}
            <div class='icgst_value'>
                {$formObj->getTBRow('', 'igst_cgst_value', '')}
            </div>
            {$formObj->getTARow('Terms', 'invoice_terms', $orderRec['invoice_terms'])}
            {$formObj->getTARow('Notes', 'notes', $orderRec['notes'])}
            {$formObj->getTBRow('Issued By', 'staff_id', $_SESSION['userFullName'], $expNoEdit)}
            <div class='button updateTotal'>
                <a href='#'>Update Total</a>
            </div>

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
            <input type='hidden' name='gst_status' value='{$orderRec['gst_status']}' />
        </form>
        ";

        //{$formObj->getTBRow('Add Frieght(%)', 'frieght')}

        return $text;
    }

    /**
     *
     */
     function getEditInvoiceForm() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        unset($_SESSION['selectedOrderItemIds']);

        $rows = '';
        $qty_balance ='';
        $order_id = $fn->getReqParam('order_id');
        $invoice_id = $fn->getReqParam('invoice_id');
        $date     = $fn->getCurrentDate();

        $invoiceRec = $fn->getRecordRowById('invoice', 'invoice_id', $invoice_id);

        $sqlInvoiceItem = "
        SELECT * FROM invoice_item
        WHERE invoice_id = {$invoice_id}
        ";
        $resultInvoiceItem = $db->sql_query($sqlInvoiceItem);
        while ($rowII = $db->sql_fetchrow($resultInvoiceItem)) {
            $sqlQty = "
            SELECT SUM(it.qty) AS qty_invoiced
            FROM invoice_item it
            JOIN invoice i ON (i.invoice_id = it.invoice_id)
            WHERE (i.order_id = {$order_id} AND i.status != 'Cancelled')
              AND it.record_id = {$rowII['record_id']}
            ";
            $resultQty = $db->sql_query($sqlQty);
            $rowQty = $db->sql_fetchrow($resultQty);

            $sqlOrderItem = "
            SELECT * FROM order_item
            WHERE order_id = {$order_id}
              AND record_id = {$rowII['record_id']}
            ";
            $resultOrdertem = $db->sql_query($sqlOrderItem);
            $rowOI = $db->sql_fetchrow($resultOrdertem);

            $selling_price = $rowII['unit_price'] * $rowII['qty'];

            $qty_balance = $rowOI['qty'] - $rowQty['qty_invoiced'];
            $qty_invoiced = $rowQty['qty_invoiced'] - $rowII['qty'];

            $inputRow = '';
            //if ($rowQty['qty_invoiced'] != $rowII['qty']) {
                $inputRow = "<input class='invoiceItemId' type='checkbox' name='invoiceItemId[]' value='{$rowII['invoice_item_id']}'>";
            //}

            $rows .= "
            <tr>
                <td>
                    {$inputRow}
                </td>
                <td>{$rowII['item_title']}</td>
                <td class='sellingPrice'>{$rowII['unit_price']}</td>
                <td class=''>{$rowOI['qty']}</td>
                <td class=''><input type='text' value='{$rowII['qty']}' id='fld_qty' class='text w50' name='qty[]'></td>
                <td class='qtyBalance'>{$qty_balance}</td>
                <td class=''>{$qty_invoiced}</td>
            </tr>
            ";
        }

        $formAction = "index.php?_topRm=finance&module=tradingsg_order&_spAction=editInvoiceFormSubmit&showHTML=0";

        $expNoEdit = array('isEditable' => 0);

        $classCst = '';
        $classVat = '';
        $classGst = '';
        $classICGst = '';
        if($invoiceRec['cst_value'] == 0) {
            $classCst = "cstValue";
        }

        if($invoiceRec['vat_value'] == 0) {
            $classVat = "vatValue";
        }

        if($invoiceRec['sgst_value'] == 0) {
            $classGst = "gstValue";
        }

        if($invoiceRec['igst_cgst_value'] == 0) {
            $classICGst = "icgstValue";
        }

        $icgstArr = array(
             "IGST"
            ,"CGST"
        );

        $invoiceIGST = isset($cpCfg['cp.invoiceIGST']) ? $cpCfg['cp.invoiceIGST'] : 0;
        $invoiceCGST = isset($cpCfg['cp.invoiceCGST']) ? $cpCfg['cp.invoiceCGST'] : 0;

        $text = "
        <script>
            var invoiceIGSTValue = {$invoiceIGST};
            var invoiceCGSTValue = {$invoiceCGST};
            
            function populateIGSTCGSTValue() {
                var selectedValue = $('select[name=\"igst_cgst\"]').val();
                var igstCgstDiv = $('input[name=\"igst_cgst_value\"]').closest('div');
                
                if(selectedValue === 'IGST') {
                    $('input[name=\"igst_cgst_value\"]').val(invoiceIGSTValue).trigger('change');
                    igstCgstDiv.show(); // Ensure div is visible
                } else if(selectedValue === 'CGST') {
                    $('input[name=\"igst_cgst_value\"]').val(invoiceCGSTValue).trigger('change');
                    igstCgstDiv.show(); // Ensure div is visible
                }
            }
            
            $(document).ready(function() {
                // Populate on page load
                populateIGSTCGSTValue();
                
                // Populate on change
                $('select[name=\"igst_cgst\"]').on('change', function() {
                    populateIGSTCGSTValue();
                });
                
                // Before form submission, ensure the value is set
                $('#portalForm').on('submit', function() {
                    populateIGSTCGSTValue();
                });
            });
        </script>
        <form id='portalForm' class='yform columnar invoiceForm' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Amount', 'invoice_amount', $invoiceRec['invoice_amount'], $expNoEdit)}
            {$formObj->getDateRow('Date', 'invoice_date', $invoiceRec['invoice_date'])}
            {$formObj->getDateRow('Due Date', 'invoice_due_date', $invoiceRec['invoice_due_date'])}
            {$formObj->getTBRow('Customer Purchase Order No', 'cust_po_no', $invoiceRec['cust_po_no'])}
            {$formObj->getTARow('Terms', 'invoice_terms', $invoiceRec['invoice_terms'])}
            {$formObj->getTARow('Notes', 'notes', $invoiceRec['notes'])}
            {$formObj->getTBRow('Issued By', 'staff_id', $_SESSION['userFullName'], $expNoEdit)}
	        {$formObj->getYesNoRRow('Add CST', 'cst', $invoiceRec['cst'])}
	        <div class='cst_value {$classCst}'>
	            {$formObj->getTBRow('', 'cst_value', $invoiceRec['cst_value'])}
            </div>
	        {$formObj->getYesNoRRow('Add VAT', 'vat', $invoiceRec['vat'])}
	        <div class='vat_value {$classVat}'>
                {$formObj->getTBRow('', 'vat_value', $invoiceRec['vat_value'])}
            </div>
            {$formObj->getDDRowByArr('IGST/CGST', 'igst_cgst', $icgstArr, $invoiceRec['igst_cgst'])}
            <div class='icgst_value {$classICGst}'>
                {$formObj->getTBRow('', 'igst_cgst_value', $invoiceRec['igst_cgst_value'])}
            </div>
            {$formObj->getYesNoRRow('Add SGST', 'sgst', $invoiceRec['sgst'])}
            <div class='gst_value {$classGst}'>
                {$formObj->getTBRow('', 'sgst_value', $invoiceRec['sgst_value'])}
            </div>
            {$formObj->getTBRow('Add Frieght Cost', 'frieght_cost', $invoiceRec['frieght_cost'])}
            {$formObj->getTBRow('Add P & F', 'p_f', $invoiceRec['p_f'])}
            <div class='button updateTotal'>
                <a href='#'>Update Total</a>
            </div>

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
                    <th class=''>Qty (Current Invoice)</th>
                    <th>Qty (Balance)</th>
                    <th>Qty (Invoiced)</th>
                </thead>

                <tbody>
                    {$rows}
                </tbody>
            </table>

            <input type='hidden' name='order_id' value='{$order_id}' />
            <input type='hidden' name='invoice_id' value='{$invoice_id}' />
            <input type='hidden' name='qty_balance' value='{$qty_balance}' />
        </form>
        ";
        //{$formObj->getTBRow('Add Frieght (%)', 'frieght', $invoiceRec['frieght'])}

        return $text;
    }

    /**
     *
     */
    function getPrintInvoice() {
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
        $invoice_type = $fn->getReqParam('invoice_type');
        if($invoice_type == 'normal'){
            $invoiceHeading = '';
        }
        else if($invoice_type == 'transporter'){
            $invoiceHeading = 'TRANSPORTER - ';
        }
        else if($invoice_type == 'proforma'){
            $invoiceHeading = 'PROFORMA - ';
        }
        else if($invoice_type == 'extra'){
            $invoiceHeading = 'EXTRA - ';
        }


        $SQL = "
        SELECT ini.*
              ,ini.item_title AS product_title
              ,p.title AS product_title1
              ,p.unit
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
              ,i.invoice_terms
              ,i.invoice_due_date
              ,i.notes
              ,i.cst
              ,i.vat
              ,i.cst_value
              ,i.vat_value
              ,i.frieght
              ,i.frieght_cost
              ,i.p_f
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
              ,ini.qty * ini.unit_price AS amount
              ,(SELECT SUM(init.qty * init.unit_price) FROM invoice_item init
               WHERE init.invoice_id = ini.invoice_id) AS sub_total
        FROM invoice_item ini
        LEFT JOIN product p ON (p.product_id = ini.record_id)
        LEFT JOIN invoice i ON (i.invoice_id = ini.invoice_id)
        LEFT JOIN `order` o ON (o.order_id = i.order_id)
        LEFT JOIN company c ON (c.company_id = o.company_id)
        LEFT JOIN quote q ON (q.quote_id = o.quote_id)
        LEFT JOIN product_group pg ON (p.product_group_id = pg.product_group_id)
        WHERE i.invoice_code = '{$invoice_code}'
        ORDER BY pg.sort_order ASC, p.title
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
        //============================================================================= //
        $pdf->SetFont('Arial','',11);
        //syed:multi text code to set width of each column and alignment
        $pdf->SetWidths(array(10, 65, 35, 11, 13, 26, 30));
        $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'R', 'R'));

        while ($row = $db->sql_fetchrow($result)) {
            if ($count == 0){
                /* Logo of the institution */
                $pdf->Image('images/logo-print.gif',10,5,45);
                $pdf->SetXY(10,10);
                $pdf->SetFont('Courier','B',11);
                $pdf->Cell(50, 20, 'Authorized Distributor of:');
                $pdf->SetXY(10,25);
                $pdf->Image('images/parker.jpg',10,28, 25);
                //$pdf->Image('images/gse.png',42,25, 25);
                $creationDate   = $fn->getCPDate($row['invoice_date'], 'd-m-Y');
                $invoiceDueDate = $fn->getCPDate($row['invoice_due_date'], 'd-m-Y');
                $deliveryDate   = $fn->getCPDate($row['delivery_date'], 'd-m-Y');
                $currency = $row['currency'];

                $gsttaxvalue = $cpCfg['amtForGSTCalc'] ;
                $gstvalue = $row['sub_total'] * $gsttaxvalue / 100;
                //$totalvalue = $gstvalue + $row['sub_total'];
                $totalvalue += $row['sub_total'];

                /* Company address */
                //Address to be got from settings
                $pdf->SetXY(130,0);
                $pdf->SetFont('Courier','B',11);
                $pdf->Cell(50, 20, $cpCfg['cp.companyName']);
                $pdf->Ln(5);
                $pdf->SetXY(130,5);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf1']);
                $pdf->Ln(5);
                $pdf->SetXY(130,10);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf2']);
                $pdf->Ln(5);
                $pdf->SetXY(130,15);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf3']);
                $pdf->Ln(5);
                $pdf->SetXY(130, 20);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf4']);
                $pdf->Ln(5);
                $pdf->SetXY(130,25);
                $pdf->Cell(50, 20, $cpCfg['printEmailAddress']);
                $pdf->Ln(5);
                $pdf->SetXY(130,30);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf7']);
                $pdf->Ln(5);
                $pdf->SetXY(130,35);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf6']);

                /* Header */
                $pdf->SetFont('Courier','BU',11);
                $pdf->SetXY(80, 45);
                $pdf->Cell(50, 20, $invoiceHeading . " INVOICE", 0, 0, 'C');
                $pdf->SetFont('Courier','B',11);
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

                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(95,8,"INVOICE TO",1,0, 'L', 1);
                $pdf->Cell(95,8,"DELIVERY TO",1,0, 'L', 1);
                $pdf->Ln();
                $pdf->SetFillColor(255,255,255);
                $pdf->SetFont('Courier','B',10.5);

                $pdf->SetFont('Courier','B',10);
                $pdf->Cell(95, 8, $row['company_name'],'LR', 0, 'L', 1);
                $pdf->Cell(95, 8, $deliveryCompanyName , 'LR', 0, 'L', 1);
                $pdf->Ln();
                $pdf->SetFont('Courier','B',10);
                $pdf->Cell(95, 5, $row['billing_address_flat'], 'LR', 0, 'L', 1);
                $pdf->Cell(95, 5, $deliveryAddressFlat, 'LR', 0, 'L', 1);
                $pdf->Ln();
                $pdf->SetFont('Courier','B',10);
                $pdf->Cell(95, 5, $row['billing_address_street'], 'LR', 0, 'L', 1);
                $pdf->Cell(95, 5, $deliveryAddressStreet, 'LR', 0, 'L', 1);
                $pdf->Ln();
                $pdf->Cell(95, 5, $row['billing_address_town'], 'LR', 0, 'L', 1);
                $pdf->Cell(95, 5, $deliveryAddressTown, 'LR', 0, 'L', 1);
                $pdf->Ln();
                $pdf->SetFont('Courier','B',10);
                $pdf->Cell(95, 5, $row['billing_address_country'] .' - '. $row['billing_address_state'], 'LR', 0, 'L', 1);
                $pdf->SetFont('Courier','B',10);
                $pdf->Cell(95, 5, $deliveryAddressCountry .' - '. $deliveryAddressState, 'LR', 0, 'L', 1);
                $pdf->Ln();
                $pdf->Cell(95, 8, 'TIN NO:' . $row['tin_no'], 'LR', 0, 'L', 1);
                $pdf->Cell(95, 8, 'TIN NO:' .$row['tin_no'], 'LR', 0, 'L', 1);
                $pdf->Ln(6);
                $pdf->Cell(95, 8, 'CST NO:' . $row['cst_no'], 'BLR', 0, 'L', 1);
                $pdf->Cell(95, 8, 'CST NO:' .$row['cst_no'], 'BLR', 0, 'L', 1);

                $pdf->Ln(10);

                /* Invoice Details*/
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(47.5,8,"INVOICE NO :",1,0, 'L', 1);
                $pdf->SetFillColor(255,255,255);
                $pdf->Cell(47.5, 8, $row['invoice_code'], 1, 0, 'L', 1);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(47.5,8,"DUE DATE :",1,0, 'L', 1);
                $pdf->SetFillColor(255,255,255);
                $pdf->Cell(47.5, 8, $invoiceDueDate, 1, 0, 'L', 1);
                $pdf->Ln(12);

                $terms = $row['invoice_terms'];
                $bank = $cpCfg['cp.bankDetails'];

                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(95,8,"TERMS",1,0, 'L', 1);
                $pdf->Cell(95,8,"BANK DETAILS",1,0, 'L', 1);
                $pdf->SetFillColor(255,255,255);
                $pdf->SetXY(10,132);
                $pdf->drawTextBox($terms, 95, 32, 'L', 'C', 1);
                $pdf->SetXY(105,132);
                $pdf->drawTextBox($bank, 95, 32, 'L', 'C', 'BLR');
                $pdf->Ln(20);

                /* List of order items header */
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(10,8,"S.NO",1,0, 'C', 1);
                $pdf->Cell(65,8,"NAME OF THE ITEM",1,0, 'C', 1);
                $pdf->Cell(35,8,"PART NUMBER",1,0, 'C', 1);
                $pdf->Cell(11,8,"QTY",1,0, 'C', 1);
                $pdf->Cell(13,8,"UOM",1,0, 'C', 1);
                $pdf->Cell(26,8,"UP",1,0, 'C', 1);
                $pdf->Cell(30,8,"AMOUNT(" . $row['currency'] . ")",1,0, 'C', 1);
                $pdf->Ln();
            }

            //===================================MAIN TABLE============================= //
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
            $pdf->Row(array($lineItemNumber, $row['product_title'] , $row['part_number'], $row['qty'], $row['unit'], number_format($row['unit_price'],2) , number_format($row['amount'],2) ));


            //$pdf->Ln();

            $count++;
            $lineItemNumber++;
            $sub_total = $row['sub_total'];
            $notes = $row['notes'];
            $frieght = $row['frieght'];
            $frieght_cost = $row['frieght_cost'];
            $pf = $row['p_f'];
            $vat = $row['vat'];
            $cst = $row['cst'];
            $vat_value = $row['vat_value'];
            $cst_value = $row['cst_value'];

        }
        $pdf->SetFillColor(255,255,255);
        $pdf->Cell(160, 8, "SUB TOTAL", 1, 0, 'R', 1);
        $pdf->Cell(30, 8, number_format(round($sub_total),2), 1, 0, 'R', 1);
        $pdf->Ln();

        $totalvalueRounded = round($totalvalue);
        $totalFrieght = $sub_total * $frieght / 100;
        $totalFrieghtCost = $sub_total + $frieght_cost;

        if($frieght > 0 ){
            $totalvalueRounded = $totalvalueRounded + round($totalFrieght);
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(160, 8, "ADD FRIEGHT : {$frieght}%", 1, 0, 'R', 1);
            $pdf->Cell(30, 8, number_format(round($totalFrieght), 2), 1, 0, 'R', 1);
            $pdf->Ln();
        }

        if($frieght_cost > 0 ){
            $totalvalueRounded = $totalvalueRounded + round($totalFrieghtCost);
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(160, 8, "ADD FRIEGHT COST:", 1, 0, 'R', 1);
            $pdf->Cell(30, 8, number_format(round($totalFrieghtCost), 2), 1, 0, 'R', 1);
            $pdf->Ln();
        }

        if($pf > 0 ){
            $totalpf = $sub_total * $pf / 100;
            $totalvalueRounded = $totalvalueRounded + round($totalpf);
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(160, 8, "ADD P&F: {$pf}%", 1, 0, 'R', 1);
            $pdf->Cell(30, 8, number_format(round($totalpf), 2), 1, 0, 'R', 1);
            $pdf->Ln();
        }

        if($vat == 1){
            $printTaxName = $cpCfg['printTaxName'] ;
            $gsttaxvalue = $vat_value;
            $gstvalue = ($sub_total + $totalpf) * $gsttaxvalue / 100;
            //$totalvalue = $gstvalue + round($sub_total);
        } else if($cst == 1 && $vat == 0){
            $printTaxName = $cpCfg['printCstText'] ;
            $gsttaxvalue = $cst_value;
            $gstvalue = ($sub_total + $totalpf) * $gsttaxvalue / 100;
            //$totalvalue = $gstvalue + round($sub_total) ;
        }

        $pdf->SetFillColor(255,255,255);
        $pdf->Cell(160, 8, "ADD: {$printTaxName} {$gsttaxvalue}%", 1, 0, 'R', 1);
        $pdf->Cell(30, 8, number_format(round($gstvalue), 2), 1, 0, 'R', 1);
        $pdf->Ln();

        $totalvalue = $totalvalue +  round($totalpf) + round($totalFrieght) + round($gstvalue);

        $pdf->SetFillColor(255,255,255);
        $pdf->Cell(160, 8, 'TOTAL', 1, 0, 'R', 1);
        $pdf->Cell(30, 8, number_format(round($totalvalue), 2), 1, 0, 'R', 1);
        $pdf->Ln(20);

        $pdf->SetFont('Courier','B',11);
        $pdf->Cell(150, 8, 'NOTE: ');
        $pdf->Ln(5);
        $pdf->drawTextBox($notes, 180, 55, 'L', 'T', 0);
        $pdf->Ln(15);

        /* Best Regards & Engex Power */
        $pdf->Cell(55, 5, $cpCfg['printBestRegards']);
        $pdf->SetX(10);
        $pdf->Cell(55, 16, $cpCfg['printEngexPower']);

        $pdf->Output();

    }

    /**
     *
     */
    function getQuickSearch() {
        $cpUtil = Zend_Registry::get('cpUtil');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $company_id = $fn->getReqParam('company_id');

        $sqlCompany = "
        SELECT company_id, company_name FROM company
        WHERE category = 'Supplier'
        ORDER BY company_name
        ";

        //$sqlCompany = $fn->getDDSql('tradingsg_company');

        $text = "
        <td>
            <select name='company_id'>
                <option value=''>Company Name</option>
                {$dbUtil->getDropDownFromSQLCols2($db, $sqlCompany, $company_id)}
            </select>
        </td>
        ";

        return $text;
    }
}