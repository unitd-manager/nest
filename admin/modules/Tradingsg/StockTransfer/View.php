<?
class CPL_Admin_Modules_Tradingsg_StockTransfer_View extends CP_Common_Lib_ModuleViewAbstract
{
   function getList($dataArray){
        $listObj = Zend_Registry::get('listObj');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $text = '';
        $rows = '';
        $readonly = '';
        $OrderItems = '';

        $rowCounter = 0;

        $SQLdeleteHistory ="
        DELETE FROM stock_transfer_history
        WHERE stock_transfer_id NOT IN (SELECT stock_transfer_id FROM stock_transfer)
        ";
        $resultdelhis = $db->sql_query($SQLdeleteHistory);
        $deletehistory = $db->sql_fetchrow($resultdelhis);

        //--------------------------------------------------------------------------//
        foreach ($dataArray as $row){

            $Sqlfrom ="
            select title as from_location
            FROM site  WHERE site_id='{$row['from_location']}'
            ";

            $resultfrom = $db->sql_query($Sqlfrom);
            $from = $db->sql_fetchrow($resultfrom);

            $stock_transfer_date = $fn->getCPDate($row['date'],"d-m-Y");

            $rows .= "
            {$listObj->getListRowHeader($row, $rowCounter)}
            {$listObj->getGoToDetailText($rowCounter, $stock_transfer_date)}
            {$listObj->getListDataCell($row['from_location'])}
            {$listObj->getListDataCell($row['to_location'])}
            {$listObj->getListDataCell($row['status'])}
            ";
            $rowCounter++ ;
        }

        $text = "
        {$listObj->getListHeader()}
        {$listObj->getListHeaderCell('Date', 'date')}
        {$listObj->getListHeaderCell('From Location', 'location_name')}
        {$listObj->getListHeaderCell('To Location', 'from_location')}
        {$listObj->getListHeaderCell('Status', 'status')}
        {$listObj->getListHeaderEnd()}
        {$rows}
        {$listObj->getListFooter()}
        ";

        return $text;
    }

    /**
     *
     */
    function getNew(){
        $formObj = Zend_Registry::get('formObj');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $expNoEdit  = array('isEditable' => 0);
        
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $siteRec = $fn->getRecordRowByID('site', 'site_id', $cpSiteIdSession);

        $newLocationUrl = 'index.php?_spAction=newLocation&lnkRoom=tradingsg_stockTransfer&showHTML=0';
        $newLocationUrl = "<a class='jqui-dialog-form' formId='portalForm' title='New Location' 
            w=600 h=560 href='' link='{$newLocationUrl}' callback='cpm.tradingsg.stockTransfer.afterNewLocation'>Add Location</a>";

        $sqlstocktrans = "
        SELECT value 
        FROM valuelist 
        WHERE key_text = 'stockLocation'
          AND value != 'Stock'
        ";
        $expVl = array('sqlType' => 'OneField', 'notesRight'  => $newLocationUrl);
        
        $fieldset = "
        {$formObj->getTBRow('Title', 'title')}
        {$formObj->getTBRow('From Location', 'from_location', 'Stock', $expNoEdit)}
        {$formObj->getDDRowBySQL('To Location', 'to_location', $sqlstocktrans, '', $expVl)}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Select Site', $fieldset)}
        ";

        return $text;
    }

    //==================================================================//
    function getRightPanel($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $db = Zend_Registry::get('db');
        $comment = getCPPluginObj('common_comment');
        $media = Zend_Registry::get('media');
        $text = '';

        $record_id = $fn->getIssetParam($row, 'stock_transfer_id');

        $text .="
        {$comment->getView(array(
             'roomName' => 'tradingsg_stockTransfer'
            ,'recordId' => $record_id
        ))}
        ";

        return $text;
    }

    /**
     *
     */
    function getEdit($row){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $dbUtil = Zend_Registry::get('dbUtil');
        $formObj = Zend_Registry::get('formObj');
        $current_date = date('Y-m-d');
        $text = '';

        $text = "
        <div id='editDisplayLoad'>{$this->getEditDisplay($row['stock_transfer_id'], $row['from_location'])}</div>
        ";

        return $text;
    }

    /**
     *
     */
    function getEditDisplay($stock_transfer_id='', $site_id=''){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $dbUtil = Zend_Registry::get('dbUtil');
        $formObj = Zend_Registry::get('formObj');
        $current_date = date('Y-m-d');
        $text = '';
        $rows = '';
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        if($stock_transfer_id == ''){
            $stock_transfer_id = $fn->getReqParam('stock_transfer_id');
        }

        /*if($site_id == ''){
            $site_id = $fn->getReqParam('site_id');
        }*/


        $SQLStockTransfer = "
        SELECT st.*
        FROM stock_transfer st
        WHERE st.stock_transfer_id = {$stock_transfer_id}
        ORDER BY st.date DESC
        ";
        $resultStockTransfer = $db->sql_query($SQLStockTransfer);
        $row = $db->sql_fetchrow($resultStockTransfer);

        $stock_transfer_status_arr = array('Request', 'Delivered', 'On Hold', 'Cancelled');
        $stock_transfer_id         = $row['stock_transfer_id'];
        $stock_transfer_date       = $fn->getCPDate($row['date'],"d-m-Y");

        $OrderItems = $this->getOrderItems($stock_transfer_id);

        $reuqestFormPdf   = "index.php?module=tradingsg_stockTransfer&_spAction=printExportAsPdf&id={$row['stock_transfer_id']}&printType=request&showHTML=0";
        $deliveryOrderPdf = "index.php?module=tradingsg_stockTransfer&_spAction=printExportAsPdf&id={$row['stock_transfer_id']}&printType=delivery&showHTML=0";

        $editableFalse = '';
        $buttonChange  = '';

        $sqlstocktrans = "
        SELECT value 
        FROM valuelist 
        WHERE key_text = 'stockLocation'
          AND value != 'Stock'
        ";
        $expVl = array('sqlType' => 'OneField');

        if($row['lock_record'] == 1){
            $editableFalse = "disabled = '1'";

            $buttonChange .= "
            <a class='btn btn-info rollbackChanges' stock_transfer_id= '{$row['stock_transfer_id']}'>
                <span class='fa-refresh'></span>
                 Rollback Transaction
            </a>";

            $buttonChange .= "
            <a class='btn btn-danger deductFromStock' stock_transfer_id= '{$row['stock_transfer_id']}'>
                <span class='fa-check'></span>
                Deduct From Stock
            </a>";
        }else{
            $buttonChange = "
            <a class='btn btn-success completeTransaction' stock_transfer_id= '{$row['stock_transfer_id']}'>
                <span class='fa-lock'></span>
                Complete Transaction
            </a>";
        }

        if($row['status'] == 'Cancelled' || $row['status'] == 'Delivered'){
            $editableFalse = "disabled = '1'";
        }

        if($row['status'] == 'Cancelled'){
            $buttonChange = "<div class='CancelledButton btn-danger'>Cancelled</div>";
        }

        $expNoEdit = '';
        if($row['stock_deducted'] == 1){
            $expNoEdit = array('isEditable' => 0);

            $buttonChange = "<div class='DeliveredProducts btn-success'>Stock transferred successfully</div>";
        }


        $text = "
        <!--<div class='float_left btn btn-info mb10'>
             <a href='{$reuqestFormPdf}' target = 'blank' id='exportasPdfStockTransfer'><span class='fa-file-pdf-o'></span>Request Form</a>
        </div>
        <div class='float_left btn btn-info mb10'>
             <a href='{$deliveryOrderPdf}' target = 'blank' id='exportasPdfStockTransfer'><span class='fa-print'></span>Delivery Order</a>
        </div>-->
        <table class='list thinlist topTable'>
            <tr>
                <th>
                    {$formObj->getTBRow('Title', 'title', $row['title'])}
                </th>
                <th>
                    <div class='locationTitle'><label>From Location :</label>{$row['from_location']}
                    </div>
                </th>
                <th>
                    {$formObj->getDDRowBySQL('To Location', 'to_location', $sqlstocktrans, $row['to_location'], $expVl)}
                </th>
                <th>
                    {$formObj->getDateRow('Date', 'date', $row['date'])}
                </th>
                <th>
                    {$formObj->getDDRowByArr('Status', 'status', $stock_transfer_status_arr, $row['status'], $expNoEdit)}
                </th>
            </tr>
            <tr>
                <th>
                    {$formObj->getTBRow('Notes', 'notes', $row['notes'])}
                </th>
                <th colspan = '2'>
                    <div class='locationTitle'><label>Created By : </label>{$row['created_by']} {$row['creation_date']}
                    </div>
                </th>
                <th colspan = '2'>
                    <div class='locationTitle'><label>Modified By : </label>{$row['modified_by']} {$row['modification_date']}
                    </div>
                </th>
            </tr>
        </table>

        <div class='addProduct'>
            Search by Product : <input type='text' value='' id='fld_product_title' class='text' name='product_title' stock_transfer_id={$row['stock_transfer_id']} {$editableFalse}>
        </div>

        <input type='hidden' name='site_id' value={$cpSiteIdSession}>

        <div class = 'float_box'>
            <div class = 'float_left actionButtons'>
                {$buttonChange}
            </div>
        </div>

        <table class='list thinlist'>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Product Name</th>
                    <th>From Location Qty</th>
                    <th>Request Qty</th>
                    <th>Qty Delivered</th>
                    <th>Created By</th>
                    <th>Modified By</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody id='orderItems'>
                {$OrderItems}
            </tbody>
        </table>
        ";

        return $text;
    }

    /**
     *totalqty
     */
    function getOrderItems1($stock_transfer_id=''){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        
        $text = '';
        $rows = '';
        $totalquantity = '';

        if ($stock_transfer_id == ''){
            $stock_transfer_id = $fn->getReqParam('stock_transfer_id');

        }

        $SqlStockTransferCount = "
        SELECT stock_transfer_history_id
        FROM stock_transfer_history
        WHERE stock_transfer_id = '{$stock_transfer_id}'
        ";
        $resultStockTransferCount  = $db->sql_query($SqlStockTransferCount);
        $numRowsStockTransferCount = $db->sql_numrows($resultStockTransferCount);

        $StockSql = "
        SELECT p.title
              ,sh.qty
              ,sh.qty_requested
              ,SUM(po.qty) AS stock
              ,sh.stock_transfer_history_id
              ,sh.created_by
              ,sh.product_id
              ,sh.modified_by
              ,sh.creation_date
              ,sh.modification_date
              ,st.stock_transfer_id 
              ,st.from_location
              ,st.to_location
              ,st.status
              ,st.lock_record
        FROM `product` p
        LEFT JOIN stock_transfer_history sh ON (sh.product_id = p.product_id)
        LEFT JOIN stock_transfer st ON (st.stock_transfer_id=sh.stock_transfer_id)
        LEFT JOIN po_product po ON (po.product_id=sh.product_id)
        where p.published='1' 
        AND p.product_id= sh.product_id 
        AND sh.stock_transfer_id = {$stock_transfer_id}  
        GROUP BY po.product_id       
        ";
        $resultStockSql = $db->sql_query($StockSql);
        $rowCounter = 1;
        while ($rowz = $db->sql_fetchrow($resultStockSql)) {


            if ($rowz['from_location'] != ''){

                $SQLsitedetail="
                SELECT site_id
                   ,title 
                FROM site WHERE site_id = {$rowz['from_location']}
                ";
                $resultsitedetail = $db->sql_query($SQLsitedetail);

            }
            if ($rowz['to_location'] != ''){

                $SQLsitedetailto="
                SELECT site_id
                   ,title 
                FROM site WHERE site_id = {$rowz['to_location']}
                ";
                $resultsitedetailto = $db->sql_query($SQLsitedetailto);

            }
            
            while ($rowsitedetail = $db->sql_fetchrow($resultsitedetail)){


            $SQLStockTransfer = "
            SELECT  st.from_location 
                    ,st.to_location
                    ,sh.product_id
                    ,SUM(sh.qty) AS Transfer_qty
            FROM stock_transfer st
            LEFT JOIN stock_transfer_history sh ON (sh.stock_transfer_id = st.stock_transfer_id)
            WHERE sh.product_id = {$rowz['product_id']} AND st.from_location = {$rowsitedetail['site_id']}";

            $resultStockTransfer = $db->sql_query($SQLStockTransfer);
            $rowStockTransfer = $db->sql_fetchrow($resultStockTransfer);


            $SQLStockTransferto = "
            SELECT  st.from_location
                    ,st.to_location
                    ,sh.product_id
                    ,SUM(sh.qty) AS Transfer_qty_to
            FROM stock_transfer st
            LEFT JOIN stock_transfer_history sh ON (sh.stock_transfer_id = st.stock_transfer_id)
            WHERE sh.product_id = {$rowz['product_id']} AND st.to_location = {$rowsitedetail['site_id']}";

            $resultStockTransferto = $db->sql_query($SQLStockTransferto);
            $rowStockTransferto = $db->sql_fetchrow($resultStockTransferto);
            $SQLOthersite = "
            SELECT
                (SELECT SUM(qty) FROM po_product pp
                 LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                 WHERE pp.product_id = {$rowz['product_id']} AND po.site_id = {$rowsitedetail['site_id']}) as product_qty_purchased
                 
               ,(SELECT SUM(invItem.qty) FROM invoice_item invItem             
                LEFT JOIN (invoice inv) ON (inv.invoice_id = invItem.invoice_id AND inv.status != 'Cancelled' )
                LEFT JOIN (`order` o) ON (o.order_id = inv.order_id)
                WHERE record_id = {$rowz['product_id']}
                  AND o.site_id = {$rowsitedetail['site_id']}
                ) as product_qty_sold_from_quote
                
                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                WHERE ini.record_id = {$rowz['product_id']}
                AND inv.site_id = {$rowsitedetail['site_id']}
                ) as sales_return_qty

                ,(SELECT SUM(pp.damaged_qty) FROM po_product pp
                  LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                  WHERE pp.product_id = {$rowz['product_id']} AND po.site_id = {$rowsitedetail['site_id']}
                 ) as damaged_qty
            ";

                $SqlExpenseProduct = "
                SELECT SUM(ep.qty) AS qty
                FROM expense_product ep
                LEFT JOIN expense e ON (e.expense_id = ep.expense_id)
                WHERE ep.product_id = {$rowz['product_id']}
                AND ep.status = 'Added'
                AND e.site_id = {$rowsitedetail['site_id']}
                AND ep.stock_deducted = 1
                ";
                $resultExpenseProduct = $db->sql_query($SqlExpenseProduct);
                $rowExpenseProduct    = $db->sql_fetchrow($resultExpenseProduct);


        }

            $resultothersite = $db->sql_query($SQLOthersite);

            while ($rowothersite = $db->sql_fetchrow($resultothersite)){
                    if ($rowsitedetail['site_id'] == $cpSiteIdSession && $rowStockTransfer['from_location'] == $cpSiteIdSession){
                        $totalqty = $rowothersite['product_qty_purchased'] - $rowothersite['product_qty_sold_from_quote'] + $rowothersite['sales_return_qty'] - $rowothersite['damaged_qty']- $rowStockTransfer['Transfer_qty'] + $rowStockTransferto['Transfer_qty_to'] - $rowExpenseProduct['qty'];
                    }
                    else if ($rowsitedetail['site_id'] != $cpSiteIdSession && $rowStockTransfer['to_location'] == $cpSiteIdSession){
                        $totalqty = $rowothersite['product_qty_purchased'] - $rowothersite['product_qty_sold_from_quote'] + $rowothersite['sales_return_qty'] - $rowothersite['damaged_qty']- $rowStockTransfer['Transfer_qty'] + $rowStockTransferto['Transfer_qty_to'] - $rowExpenseProduct['qty'];
                    }
                    else {
                        $totalqty = $rowothersite['product_qty_purchased'] - $rowothersite['product_qty_sold_from_quote'] + $rowothersite['sales_return_qty'] - $rowothersite['damaged_qty']- $rowStockTransfer['Transfer_qty'] + $rowStockTransferto['Transfer_qty_to'] - $rowExpenseProduct['qty']; 
                    }

            }
        
        while ($rowsitedetailto = $db->sql_fetchrow($resultsitedetailto)){


            $SQLStockTransfer = "
            SELECT  st.from_location 
                    ,st.to_location
                    ,sh.product_id
                    ,SUM(sh.qty) AS Transfer_qty
            FROM stock_transfer st
            LEFT JOIN stock_transfer_history sh ON (sh.stock_transfer_id = st.stock_transfer_id)
            WHERE sh.product_id = {$rowz['product_id']} AND st.from_location = {$rowsitedetailto['site_id']}";

            $resultStockTransfer = $db->sql_query($SQLStockTransfer);
            $rowStockTransfer = $db->sql_fetchrow($resultStockTransfer);


            $SQLStockTransferto = "
            SELECT  st.from_location
                    ,st.to_location
                    ,sh.product_id
                    ,SUM(sh.qty) AS Transfer_qty_to
            FROM stock_transfer st
            LEFT JOIN stock_transfer_history sh ON (sh.stock_transfer_id = st.stock_transfer_id)
            WHERE sh.product_id = {$rowz['product_id']} AND st.to_location = {$rowsitedetailto['site_id']}";

            $resultStockTransferto = $db->sql_query($SQLStockTransferto);
            $rowStockTransferto = $db->sql_fetchrow($resultStockTransferto);
            $SQLOthersiteto = "
            SELECT
                (SELECT SUM(qty) FROM po_product pp
                 LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                 WHERE pp.product_id = {$rowz['product_id']}
                 AND po.site_id = {$rowsitedetailto['site_id']}
                 ) as product_qty_purchased

               ,(SELECT SUM(invItem.qty) FROM invoice_item invItem
                LEFT JOIN (invoice inv) ON (inv.invoice_id = invItem.invoice_id AND inv.status != 'Cancelled' )
                LEFT JOIN (`order` o) ON (o.order_id = inv.order_id)
                WHERE record_id = {$rowz['product_id']}
                  AND o.site_id = {$rowsitedetailto['site_id']}
                ) as product_qty_sold_from_quote

                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                WHERE ini.record_id = {$rowz['product_id']}
                AND inv.site_id = {$rowsitedetailto['site_id']}
                ) as sales_return_qty

                ,(SELECT SUM(pp.damaged_qty) FROM po_product pp
                  LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                  WHERE pp.product_id = {$rowz['product_id']} AND po.site_id = {$rowsitedetailto['site_id']}
                 ) as damaged_qty
            ";

            $SqlExpenseProduct = "
            SELECT SUM(ep.qty) AS qty
            FROM expense_product ep
            LEFT JOIN expense e ON (e.expense_id = ep.expense_id)
            WHERE ep.product_id = {$rowz['product_id']}
            AND ep.status = 'Added'
            AND e.site_id = {$rowsitedetailto['site_id']}
            AND ep.stock_deducted = 1
            ";
            $resultExpenseProduct = $db->sql_query($SqlExpenseProduct);
            $rowExpenseProduct    = $db->sql_fetchrow($resultExpenseProduct);

        }

        $resultothersiteto = $db->sql_query($SQLOthersiteto);

            while ($rowothersiteto = $db->sql_fetchrow($resultothersiteto)){

                //if ($rowothersite['product_qty_purchased']!=''){
                    if ($rowsitedetail['site_id'] == $cpSiteIdSession && $rowStockTransfer['from_location'] == $cpSiteIdSession){
                        $totalqtyto = $rowothersiteto['product_qty_purchased'] - $rowothersiteto['product_qty_sold_from_quote'] + $rowothersiteto['sales_return_qty'] - $rowothersiteto['damaged_qty']- $rowStockTransfer['Transfer_qty'] + $rowStockTransferto['Transfer_qty_to'] - $rowExpenseProduct['qty']; 
                    }
                    else if ($rowsitedetail['site_id'] != $cpSiteIdSession && $rowStockTransfer['to_location'] == $cpSiteIdSession){
                        $totalqtyto = $rowothersiteto['product_qty_purchased'] - $rowothersiteto['product_qty_sold_from_quote'] + $rowothersiteto['sales_return_qty'] - $rowothersiteto['damaged_qty']- $rowStockTransfer['Transfer_qty'] + $rowStockTransferto['Transfer_qty_to'] - $rowExpenseProduct['qty']; 
                    }
                    else {
                       $totalqtyto = $rowothersiteto['product_qty_purchased'] - $rowothersiteto['product_qty_sold_from_quote'] + $rowothersiteto['sales_return_qty'] - $rowothersiteto['damaged_qty']- $rowStockTransfer['Transfer_qty'] + $rowStockTransferto['Transfer_qty_to'] - $rowExpenseProduct['qty'];  
                        }

            }

            $editableFalse = '';
            $expNoEdit     = '';
            $deleteLink = "<a href='#' class='deleteItem btn btn-danger' stock_transfer_history_id='{$rowz['stock_transfer_history_id']}' stock_transfer_id= '{$rowz['stock_transfer_id']}'>Delete</a>";
            
            if($rowz['status'] == 'Cancelled'){
                $editableFalse  = "disabled = '1'";
                $deleteLink     = "";
                $expNoEdit      = array('isEditable' => 0);
            }

            if($rowz['status'] == 'Delivered'){
                $deleteLink     = "";
                $editableFalse  = "disabled = '1'";
            }

            $editableFalseRequest = '';
            if($rowz['lock_record'] == 1){
                $editableFalseRequest  = "disabled = '1'";
            }

            $editableFalseDelivered = "disabled = '1'";
            if($cpSiteIdSession == 1){
                $editableFalseDelivered = "";
            }

            if($rowz['lock_record'] == 1 && $rowz['status'] == 'Delivered'){
                $editableFalse  = "disabled = '1'";
            }
        
            $rows .= "
            <tr>
            <td>
                {$rowCounter}
                <input  type='hidden' class='stockTransfer_product_count' name='stockTransfer_product_count' value='{$numRowsStockTransferCount}'/>
            </td>
            <td class='w25p'>{$rowz['title']}</td>
            <td>{$totalqty}</td>
            <td class='w100'>
                <input type='text' value='{$rowz['qty_requested']}' id='fld_Request_qty' class='text w100' name='request_qty' stock_transfer_history_id='{$rowz['stock_transfer_history_id']}' stock_transfer_id= '{$rowz['stock_transfer_id']}' stock='{$totalqty}' {$editableFalse} {$editableFalseRequest}>
            </td>
            <td class='w100'>
                <input type='text' value='{$rowz['qty']}' id='fld_qty' class='text w100' name='qty' stock_transfer_history_id='{$rowz['stock_transfer_history_id']}' stock_transfer_id= '{$rowz['stock_transfer_id']}' stock='{$totalqty}' {$editableFalse} {$editableFalseDelivered}>
            </td>
            <td>{$totalqtyto}</td>
            <td>{$rowz['created_by']}  {$rowz['creation_date']}</td>
            <td>{$rowz['modified_by']}  {$rowz['modification_date']} </td>
            <td>{$deleteLink}</td>
            </tr>
            ";
            $rowCounter++ ;
            
        
        }
        $text = "
        {$rows}
        ";
        return $text;
        
    }

    /**
     *totalqty
     */
    function getOrderItems($stock_transfer_id=''){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        
        $text = '';
        $rows = '';
        $totalquantity = '';

        if ($stock_transfer_id == ''){
            $stock_transfer_id = $fn->getReqParam('stock_transfer_id');

        }

        $SqlStockTransferCount = "
        SELECT stock_transfer_history_id
        FROM stock_transfer_history
        WHERE stock_transfer_id = '{$stock_transfer_id}'
        ";
        $resultStockTransferCount  = $db->sql_query($SqlStockTransferCount);
        $numRowsStockTransferCount = $db->sql_numrows($resultStockTransferCount);

        $StockSql = "
        SELECT p.title
              ,sh.qty
              ,sh.qty_requested
              ,sh.stock_transfer_history_id
              ,sh.created_by
              ,sh.product_id
              ,sh.modified_by
              ,sh.creation_date
              ,sh.modification_date
              ,st.stock_transfer_id 
              ,st.from_location
              ,st.to_location
              ,st.status
              ,st.lock_record
        FROM `product` p
        LEFT JOIN stock_transfer_history sh ON (sh.product_id = p.product_id)
        LEFT JOIN stock_transfer st ON (st.stock_transfer_id=sh.stock_transfer_id)
        where p.published='1' 
        AND p.product_id= sh.product_id 
        AND sh.stock_transfer_id = {$stock_transfer_id}  
        ";
        $resultStockSql = $db->sql_query($StockSql);
        $rowCounter = 1;
        while ($rowz = $db->sql_fetchrow($resultStockSql)) {
            $StockSql = "
            SELECT
                (SELECT SUM(qty) FROM po_product pp
                 LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                 WHERE pp.product_id = {$rowz['product_id']}) as product_qty_purchased

               ,(SELECT SUM(damage_qty) FROM po_product pp
                 LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                 WHERE pp.product_id = {$rowz['product_id']}) as damage_qty

               ,(SELECT SUM(ordItm.qty) FROM order_item ordItm
                LEFT JOIN (`order` o) ON (o.order_id = ordItm.order_id)
                WHERE ordItm.record_id = {$rowz['product_id']}
                AND o.order_status != 'Cancelled'
                ) as product_qty_sold

                ,(SELECT SUM(sth.qty) FROM stock_transfer_history sth
                WHERE sth.product_id = {$rowz['product_id']}
                ) as product_qty_sold_from_stock

                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                WHERE ini.record_id = {$rowz['product_id']}
                  AND srh.status = 'Approved'
                ) as sales_return_qty

                ,(SELECT SUM(pp.product_weight) FROM po_product pp
                LEFT JOIN purchase_order po ON (po.purchase_order_id = pp.purchase_order_id)
                WHERE pp.product_id = {$rowz['product_id']}
                ) AS PurchasedGrams

                ,(SELECT SUM(oi.weight) FROM order_item oi
                  LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
                  WHERE oi.record_id = {$rowz['product_id']}
                    AND (o.order_status = 'Paid' OR o.order_status = 'Partial Payment')
                ) AS SoldGrams
            ";
            $resultStockSql = $db->sql_query($StockSql);
            $rowStockSql    = $db->sql_fetchrow($resultStockSql);
            
            $stock    = $rowStockSql['product_qty_purchased'] - $rowStockSql['product_qty_sold_from_stock'] - $rowStockSql['product_qty_sold'] + $rowStockSql['sales_return_qty'] - $rowStockSql['damage_qty'];
            $totalqty = $stock + $rowz['qty'];

            $editableFalse = '';
            $expNoEdit     = '';
            $deleteLink = "<a href='#' class='deleteItem btn btn-danger' stock_transfer_history_id='{$rowz['stock_transfer_history_id']}' stock_transfer_id= '{$rowz['stock_transfer_id']}'>Delete</a>";
            
            if($rowz['status'] == 'Cancelled'){
                $editableFalse  = "disabled = '1'";
                $deleteLink     = "";
                $expNoEdit      = array('isEditable' => 0);
            }

            if($rowz['status'] == 'Delivered'){
                $deleteLink     = "";
                $editableFalse  = "disabled = '1'";
            }

            $editableFalseRequest = '';
            if($rowz['lock_record'] == 1){
                $editableFalseRequest  = "disabled = '1'";
            }

            $editableFalseDelivered = "disabled = '1'";
            if($cpSiteIdSession == 1){
                $editableFalseDelivered = "";
            }

            if($rowz['lock_record'] == 1 && $rowz['status'] == 'Delivered'){
                $editableFalse  = "disabled = '1'";

                $totalqty   = $stock;
            }

            $rows .= "
            <tr>
            <td>
                {$rowCounter}
                <input  type='hidden' class='stockTransfer_product_count' name='stockTransfer_product_count' value='{$numRowsStockTransferCount}'/>
            </td>
            <td class='w25p'>{$rowz['title']}</td>
            <td>{$totalqty}</td>
            <td class='w100'>
                <input type='text' value='{$rowz['qty_requested']}' id='fld_Request_qty' class='text w100' name='request_qty' stock_transfer_history_id='{$rowz['stock_transfer_history_id']}' stock_transfer_id= '{$rowz['stock_transfer_id']}' stock='{$totalqty}' {$editableFalse} {$editableFalseRequest}>
            </td>
            <td class='w100'>
                <input type='text' value='{$rowz['qty']}' id='fld_qty' class='text w100' name='qty' stock_transfer_history_id='{$rowz['stock_transfer_history_id']}' stock_transfer_id= '{$rowz['stock_transfer_id']}' stock='{$totalqty}' {$editableFalse} {$editableFalseDelivered}>
            </td>
            <td>{$rowz['created_by']}  {$rowz['creation_date']}</td>
            <td>{$rowz['modified_by']}  {$rowz['modification_date']} </td>
            <td>{$deleteLink}</td>
            </tr>
            ";
            $rowCounter++ ;
            
        
        }
        $text = "
        {$rows}
        ";
        return $text;
        
    }

    /**
     *
     */
    function getNewLocation(){
        $formObj = Zend_Registry::get('formObj');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');

        $formAction = "index.php?_spAction=addLocation&lnkRoom=tradingsg_stockTransfer&showHTML=0";
        
        $text = "
        <form id='portalForm' class='yform columnar' method='post' action='{$formAction}'>
            <fieldset>
                {$formObj->getTBRow('Value', 'value')}
            </fieldset>
            
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

        $date1   = $fn->getReqParam('date_1');
        $date2   = $fn->getReqParam('date_2');
        $sqlstockLocation       = $fn->getValueListSQL('stockLocation');       
        $to_location         = $fn->getReqParam('to_location');

        $text = "
        <td>
            {$formObj->getDateRangeRow('Date:', 'date', $date1, $date2)}
        </td>
        <td>
            <select name='to_location'>
                <option value=''>To Location</option>
                {$dbUtil->getDropDownFromSQLCols1($db, $sqlstockLocation, $to_location)}
            </select>
        </td>    
        ";


        return $text;
    }
}