<?
class CPL_Admin_Modules_Tradingin_Inventory_View extends CP_Admin_Modules_Tradingin_Inventory_View
{
    /**
     *
     */
    function getList($dataArray){
        $listObj = Zend_Registry::get('listObj');
        $db      = Zend_Registry::get('db');
        $fn      = Zend_Registry::get('fn');
        $cpCfg   = Zend_Registry::get('cpCfg');

        $count   = 0;
        $rows    = '';

        //TO CREATE INVENTORY RECORDS FROM PRODUCT RECORD
        $this->getCreateInventoryRecords();
         $stock = '';
        foreach ($dataArray as $row){

            /*$StockSql = "
            SELECT
                (SELECT SUM(qty) FROM po_product
                WHERE product_id = {$row['product_id']}) as product_qty_purchased
                ,(SELECT SUM(ordItem.qty) FROM order_item ordItem
                LEFT JOIN (`order` o) ON (o.order_id = ordItem.order_id)
                WHERE ordItem.record_id = {$row['product_id']}
                AND (o.order_status = 'Paid' || o.order_status = 'Due')
                ) as product_qty_sold_from_quote
            ";

            $resultStockSql = $db->sql_query($StockSql);
            $rowStockSql    = $db->sql_fetchrow($resultStockSql);*/

            //$stock = $rowStockSql['product_qty_purchased'] - $rowStockSql['product_qty_sold_from_quote'];
            $stockArray = $fn->getStockForProduct($row['product_id']);
            $stock      = $stockArray['OverallStock'];

            $weight       = '';
            
            $SQLUpdate = "
            update inventory set actual_stock = {$stock}
            WHERE inventory_id = {$row['inventory_id']}
            ";
            $result1 = $db->sql_query($SQLUpdate);


            $weightColumnValue = "";
            if($cpCfg['showWeightInPos'] == 1){
                $weightColumnValue = $listObj->getListDataCell($weight, 'center');
            }

            $productCodeTd = $listObj->getListDataCell($row['item_code'], 'center');
            if ($cpCfg['cp.serialKeyActive'] == "SULB-DHEO-0R6K-59CL" || $cpCfg['cp.serialKeyActive'] == "YODX0-9DT58-VCZ5W-A8XXB") {
                $productCodeTd = $listObj->getListDataCell($row['product_code'], 'center');
            }

            $rows .= "
            {$listObj->getListRowHeader($row, $count)}
            {$listObj->getListDataCell('STK-'.$row['inventory_code'])}
            {$listObj->getGoToDetailText($count, $row['product_name'])}
            {$productCodeTd}
            {$listObj->getListDataCell($row['tag_no'], 'center')}
            {$listObj->getListDataCell($row['company_name'])}
            {$listObj->getListDataCell($row['unit'])}
            {$listObj->getListDataCell($stock)}
            {$listObj->getListDataCell($row['minimum_order_level'])}
            {$listObj->getListRowEnd($row['inventory_id'])}
            ";

            $count++ ;
        }

        $weightColumn = "";
        if($cpCfg['showWeightInPos'] == 1){
            $weightColumn = $listObj->getListHeaderCell('weight', '', 'txtCenter');
        }

        $productCodeTh = $listObj->getListHeaderCell('Item Code', 'item_code' , 'headerCenter');
        if ($cpCfg['cp.serialKeyActive'] == "SULB-DHEO-0R6K-59CL" || $cpCfg['cp.serialKeyActive'] == "YODX0-9DT58-VCZ5W-A8XXB") {
            $productCodeTh = $listObj->getListHeaderCell('Product Code', 'product_code' , 'headerCenter');
        }

        $text = "
        {$listObj->getListHeader()}
        {$listObj->getListHeaderCell('Inventory Code', 'inventory_code')}
        {$listObj->getListHeaderCell('Name', 'product_name')}
        {$productCodeTh}
        {$listObj->getListHeaderCell('Tag No', 'p.tag_no', 'headerCenter')}
        {$listObj->getListHeaderCell('Supplier', 'company_name')}
        {$listObj->getListHeaderCell('UOM', 'i.unit')}
        {$listObj->getListHeaderCell('Stock', 'stock' )}
        {$listObj->getListHeaderCell('MOL', 'i.minimum_order_level' )}
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

        $text = "
        ";

        return $text;
    }

    /**
     *
     */
    function getEdit($row){
        $formObj = Zend_Registry::get('formObj');
        $cpCfg   = Zend_Registry::get('cpCfg');
        $tv      = Zend_Registry::get('tv');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $db = Zend_Registry::get('db');

        $formObj->mode = $tv['action'];
        $expNoEdit  = array('isEditable' => 0);

            /*,(SELECT SUM(ordItem.qty) FROM order_item ordItem
            LEFT JOIN (`order` o) ON (o.order_id = ordItem.order_id)
            WHERE ordItem.record_id = {$row['product_id']}
            AND (o.order_status = 'Paid' || o.order_status = 'Due')
            ) as product_qty_sold_from_quote*/
        $stockArray = $fn->getStockForProduct($row['product_id']);
        $stock      = $stockArray['OverallStock'];
        $purchasedQty  = $stockArray['PurchasedQty'];
        $soldQty      = $stockArray['SoldQty'];

        $creation_date     = $fn->getCPDate($row['creation_date'], 'd-m-Y H:i:s');
        $modification_date = $fn->getCPDate($row['modification_date'], 'd-m-Y H:i:s');

        $PurchasedWeight = '';
        $SoldWeight      = '';
        $StockWeight     = '';

        if($cpCfg['showWeightInPos'] == 1){

            if($PurchasedWeight != "" && $PurchasedWeight > 0){
                $PurchasedWeight = " | Weight ({$PurchasedWeight})";
            }
            else{
                $PurchasedWeight = "";
            }

            if($SoldWeight != "" && $SoldWeight > 0){
                $SoldWeight = " | Weight ({$SoldWeight})";
            }
            else{
                $SoldWeight = "";
            }

            if($StockWeight != "" && $StockWeight > 0){
                $StockWeight = " | Weight ({$StockWeight})";
            }
            else{
                $StockWeight = "";
            }

            $StockRows = "
            <tr>
                <td>{$purchasedQty}</td>
                <td>{$soldQty}</td>
                <td>{$stock}</td>
            </tr>
            ";
        }
        else{
            $StockRows = "
            <tr>
                <td>{$purchasedQty}</td>
                <td>{$soldQty}</td>
                <td>{$stock}</td>
            </tr>
            ";
        }

        $text = "
        <div class='linkPortalWrapper'>
            <div expanded='1' class='header'>
                <div class='floatbox'>
                    <div class='float_left'>Product Details</div>
                    <div class='toggle'></div>
                    <div class='float_right'>Creation : {$row['created_by']} on {$creation_date} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Modified : {$row['modified_by']} {$modification_date}</div>
                </div>
            </div>
            <div>
                <div class='linkPortalDataWrapper'>
                    <table class='thinlist'>
                        <tbody>
                            <tr>
                                <td width='25%'>{$formObj->getTBRow('Name', 'product_name', $row['product_name'], $expNoEdit)}</td>
                                <td width='25%'>{$formObj->getTBRow('Item Code', 'item_code', $row['item_code'], $expNoEdit)}</td>
                                <td width='25%'>{$formObj->getTBRow('UOM', 'unit', $row['unit'], $expNoEdit)}</td>
                                <td width='25%'>{$formObj->getTBRow('MOL', 'minimum_order_level', $row['minimum_order_level'])}</td>
                            </tr>
                            <tr>
                                <td width='25%'>{$formObj->getTARow('Notes', 'notes', $row['notes'])}</td>
                            </tr>
                        </tbody>
                    </table>
                    <table class='thinlist stockDetailsTable'>
                        <thead>
                            <tr>
                                <th>Total Purchased Qty</th>
                                <th>Total Sold Qty</th>
                                <th>Total Available Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$StockRows}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getPrintDetail($row){
        $db = Zend_Registry::get('db');
        return $this->getDetail($row);
    }

    /**
     *
     */
    function getRightPanel($row){
        $fn = Zend_Registry::get('fn');
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $media = Zend_Registry::get('media');

        $text = "
        {$this->getPurchaseOrderDisplay($row)}
        {$this->getOrderDisplay($row)}
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
        $fn = Zend_Registry::get('fn');

        $supplier_id         = $fn->getReqParam('supplier_id');
        $category_id         = $fn->getReqParam('category_id');
        $minimum_order_level         = $fn->getReqParam('minimum_order_level');

        $spArray = array(
            "Flagged"
           ,"Not-Flagged"
        );

        $spArray1 = array(
            ""
           ,"MOL Products"
        );

        $sqlSupplier = "
        SELECT c.company_id
              ,c.company_name
        FROM company c
        WHERE c.category = 'Supplier'
        ORDER BY c.company_name
        ";

        $sqlCategory = "
        SELECT c.category_id
              ,c.title
        FROM category c
        ";

        $text = "
        <td>
            <select name='supplier_id'>
                <option value=''>Supplier Name</option>
                {$dbUtil->getDropDownFromSQLCols2($db, $sqlSupplier, $supplier_id)}
            </select>
        </td>
        <td>
            <select name='category_id'>
                <option value=''>Category</option>
                {$dbUtil->getDropDownFromSQLCols2($db, $sqlCategory, $category_id)}
            </select>
        </td>
        <td>
            <select name='special_search'>
                <option value=''>Special Search</option
                {$cpUtil->getDropDown1($spArray, $tv['special_search'])}
           </select>
        </td>
        <td>
            <select name='minimum_order_level'>
                <option value=''>Minimum Order Level</option
                {$cpUtil->getDropDown1($spArray1, $minimum_order_level)}
           </select>
        </td>
        ";

        return $text;
    }
    /**
     *
     */
    function getCreateInventoryRecords() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');

        $SQL = "
        SELECT p.product_id
        FROM product p
        WHERE p.product_id NOT IN(
            SELECT invent.product_id
            FROM inventory invent
        )
        ORDER BY p.product_id
       ";

       $SQL = "
        SELECT p.product_id
        FROM product p
        LEFT JOIN inventory inv ON inv.product_id = p.product_id
        WHERE inv.product_id IS NULL
       ";

        $result = $db->sql_query($SQL);

        while ($row = $db->sql_fetchrow($result)) {

            $fa = array();

            $fa['product_id']     = $row['product_id'];
            $fa['creation_date']  = date('Y-m-d H:i:s');
            $fa['inventory_code']     = $this->getUpdateInventoryCode();;

            $inventory_id = $fn->addRecord($fa, 'inventory');
        }
    }

    /**
     *
     */
    function getUpdateInventoryCode() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        /* Updation of Purchase order Code */
        $inventoryCode = $fn->getSettingsValueByKey("inventoryCode");

        $SQL    = "UPDATE setting SET value = (value+1) WHERE key_text = 'inventoryCode'";
        $result = $db->sql_query($SQL);

        return $inventoryCode;
    }

    /**
     */
    function getOrderDisplay($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $formAction = '';

        $text = "
        <div class='linkPortalWrapper panel tradingin_inventory_orderLink'>
            <div class='panel panel-primary'>
                <div class='panel-heading'>
                    <div expanded='1'>
                        <div class='floatbox'>
                            <div class='float_left RightPanelHeading'>Orders Linked</div>
                            <div class='txtRight'>
                              <div class='toggle'></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <div class='linkPortalDataWrapper'>
                        <form id='orderItemPrint' class='' method='post' action='{$formAction}'>
                            <div id='invoicePortalOuter'>
                                {$this->getOrderDisplayDetail($row)}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getOrderDisplayDetail($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $rows  = "";
        $rowsPvt  = "";
        $links = "";
        $leftJoin  = "";
        $sqlAppend = "";

        $SQL = "
        SELECT DISTINCT o.order_id
              ,oi.order_item_id
              ,oi.item_title
              ,oi.unit_price
              ,oi.qty
              ,oi.qty * oi.unit_price
              ,o.order_date
              ,o.record_type
              ,com.company_name
        FROM `order_item` oi
        LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
        LEFT JOIN (`invoice` inv) ON (o.order_id = inv.order_id)
        LEFT JOIN company com ON com.company_id = o.company_id
        WHERE oi.record_id = {$row['product_id']}
        AND (o.order_status = 'Paid' || o.order_status = 'Due' || o.order_status = 'Partial Payment')
        AND (inv.status = 'Paid' || inv.status = 'Due' || inv.status = 'Partial Payment')
        ";

        $result   = $db->sql_query($SQL);
        $client = '';
        while ($rowOI = $db->sql_fetchrow($result)) {
            if($rowOI['record_type'] == 'POS'){
                $client = 'POS';
            }
            else{
                $client = $rowOI['company_name'];
            }

            $SQLINV = "
            SELECT DISTINCT i.invoice_id
                  ,it.unit_price
                  ,it.qty
                  ,i.invoice_date
                  ,i.invoice_code
            FROM `invoice_item` it
            LEFT JOIN (`invoice` i) ON (i.invoice_id = it.invoice_id)
            WHERE it.record_id = {$row['product_id']}
            AND i.order_id = {$rowOI['order_id']}
            AND (i.status = 'Paid' || i.status = 'Due' || i.status = 'Partial Payment')
            ";

            $resultINV   = $db->sql_query($SQLINV);
            $rowsInv = '';
            while ($rowINV = $db->sql_fetchrow($resultINV)) {
                $urlPrint = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=printInvoiceRecord&orderNo={$rowOI['order_id']}&showHTML=0";

                if($rowINV['invoice_code'] == ""){
                    $invoice_code = 'INV - '.$rowINV['invoice_id']; 
                }
                else{
                    $invoice_code = $rowINV['invoice_code'];
                }
                
                $rowsInv .="
                <tr>
                    <td>
                        <a href='{$urlPrint}' target='_blank'>
                            <u>{$invoice_code}</u>
                        </a>
                    </td>
                    <td>{$fn->getCPDate($rowINV['invoice_date'], 'd-m-Y')}</td>
                    <td>{$rowINV['qty']}</td>
                    <td class='txtRight'>{$rowINV['unit_price']}</td>
                </tr>
                ";
            }

            if($rowOI['order_id'] < 10){
                $orderNo = '0000' . $rowOI['order_id'];
            }
            else if($rowOI['order_id'] <= 99){
                $orderNo = '000' . $rowOI['order_id'];
            }
            else if($rowOI['order_id'] <= 999){
                $orderNo = '00' . $rowOI['order_id'];
            }
            else if($rowOI['order_id'] <= 9999){
                $orderNo = '0' . $rowOI['order_id'];
            }
            else{
                $orderNo = $rowOI['order_id'];
            }

            $OrderEditLink = "index.php?_topRm=order&module=tradingsg_order&_action=edit&order_id={$rowOI['order_id']}";

            $rows .= "
            <tr class='orderRightPanelTr'>
                <td width='10%'>
                    <a href='{$OrderEditLink}' target='_blank'>
                        <u>{$orderNo}</u>
                    </a>
                </td>
                <td width='15%'>{$fn->getCPDate($rowOI['order_date'], 'd-m-Y')}</td>
                <td width='30%' class='txtRight'>{$rowOI['unit_price']}</td>
                <td width='15%'>{$rowOI['qty']}</td>
                <td width='30%'>{$client}</td>
            </tr>
            <tr>
                <td></td>
                <td colspan='4'>
                    <table class='thinlist'>
                        <tr>
                            <th>Invoice Code</th>
                            <th>Date</th>
                            <th>PCS</th>
                            <th class='txtRight'>Amount</th>
                        </tr>
                        {$rowsInv}
                    </table>
                </td>
            </tr>
            ";
        }

        //style='background-color:#0F9191; color:#ffffff'

        $header ="
        <tr style='background-color:#EAEAE8;'>
            <th width='10%'>Order Id</th>
            <th width='15%'>Date</th>
            <th width='30%' class='txtRight'>Amount</th>
            <th width='15%'>PCS</th>
            <th width='30%'>Client</th>
        </tr>
        ";

        $text = "
        <table class='thinlist' width='100%'>
            {$header}
            {$rows}
        </table>
        ";

        return $text;
    }

    /**
     */
    function getPurchaseOrderDisplay($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $formAction = '';

        $text = "
        <div class='linkPortalWrapper panel tradingin_inventory_purchaseOrderLink'>
            <div class='panel panel-primary'>
                <div class='panel-heading'>
                    <div expanded='1'>
                        <div class='floatbox'>
                            <div class='float_left RightPanelHeading'>Purchase Orders Linked</div>
                            <div class='txtRight'>
                              <div class='toggle'></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <div class='linkPortalDataWrapper'>
                        <form id='poPrint' class='' method='post' action='{$formAction}'>
                            <div id='invoicePortalOuter'>
                                {$this->getPurchaseOrderDisplayDetail($row)}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     */
    function getPurchaseOrderDisplayDetail($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $rows  = "";
        $rowsPvt  = "";
        $links = "";
        $leftJoin  = "";
        $sqlAppend = "";
        $tdForSiteId = "";
        $thForSiteId = "";
        $leftjnAppend = "";

        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $sqlAppend = ",st.title as site_title";
            $leftjnAppend = "
            LEFT JOIN site st ON st.site_id = po.site_id";
        }

        $SQL = "
        SELECT pop.cost_price
              ,pop.qty
              ,com.company_name AS supplier_name
              ,po.po_code
              ,po.purchase_order_date
              ,po.purchase_order_id
              ,po.creation_date
              {$sqlAppend}
        FROM po_product pop
        LEFT JOIN purchase_order po ON po.purchase_order_id = pop.purchase_order_id
        LEFT JOIN supplier com ON pop.supplier_id = com.supplier_id
        {$leftjnAppend}
        WHERE pop.product_id = {$row['product_id']}
        ";

        $result   = $db->sql_query($SQL);

        while ($rowPo = $db->sql_fetchrow($result)) {
            if($cpCfg['cp.hasMultiUniqueSites']  == true){
                $tdForSiteId = "<td>{$rowPo['site_title']}</td>";
            }

            if($rowPo['purchase_order_date'] == '' || $rowPo['purchase_order_date'] == 0){
                $purchase_order_date = $fn->getCPDate($rowPo['creation_date'], 'd-m-Y');
            }
            else{
                $purchase_order_date = $fn->getCPDate($rowPo['purchase_order_date'], 'd-m-Y');
            }
            $po_code = "<a href='index.php?_topRm=order&module=tradingsg_purchaseOrder&record_id={$rowPo['purchase_order_id']}&_action=edit'><u>{$rowPo['po_code']}</u></a>";

            $rows .= "
            <tr>
                <td>{$po_code}</td>
                {$tdForSiteId}
                <td>{$purchase_order_date}</td>
                <td>{$rowPo['cost_price']}</td>
                <td>{$rowPo['qty']}</td>
                <td>{$rowPo['supplier_name']}</td>
            </tr>
            ";
        }

        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $thForSiteId = "<th>Location</th>";
        }
        $header ="
        <tr style='background-color:#EAEAE8;'>
        <th>PO Code</th>
        {$thForSiteId}
        <th>Date</th>
        <th>Amount</th>
        <th>Qty</th>
        <th>Supplier</th>
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

}