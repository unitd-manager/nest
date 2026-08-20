<?
class CPL_Admin_Modules_Tradingsg_Pos_Model extends CP_Admin_Modules_Tradingsg_Pos_Model
{
   /**
     *
     */
    function getSearchProductTitle() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $title = $fn->getReqParam('term', '', true);
        $extractor = explode(" **** ", $title);

        $productTitle = $extractor[0];

        $SupplierNameAlias = "Supplier: ";
        //,CONCAT_WS(' | | ', p.item_code, pg.title, p.title, p.price, CONCAT('{$SupplierNameAlias}', c.company_name)) AS label
        $productTypeCondition = '';
        if ($cpCfg['cp.restaurent'] == 1) {
            $productTypeCondition = "AND p.product_type = 'Menu'";
        }else{
            $productTypeCondition = "AND (p.product_type != 'Purchasing Product' OR p.product_type IS NULL)";
        }

        $productTypeConditionss = '';
        if ($cpCfg['cp.restaurent'] == 1) {
            $productTypeConditionss = "AND product_type = 'Menu'";
        }else{
            $productTypeConditionss = "AND (product_type != 'Purchasing Product' OR product_type IS NULL)";
        }
         //HAVING stock > 0   this condition if stock is zero  is not show product
        $SQL = "
        SELECT p.title AS value
              ,p.title AS label
              ,CONCAT_WS(' ', p.title, CONCAT('('
              ,if(
                    (SELECT SUM(qty) FROM po_product
                    WHERE product_id = p.product_id)

                    ,(SELECT SUM(qty) FROM po_product
                      WHERE product_id = p.product_id)
                    ,''
                )
                -
                if(
                    (SELECT SUM(damage_qty) FROM po_product
                    WHERE product_id = p.product_id)

                    ,(SELECT SUM(damage_qty) FROM po_product
                      WHERE product_id = p.product_id)
                    ,''
                )
                -
                if(
                    (SELECT SUM(sth.qty) FROM stock_transfer_history sth
                    WHERE sth.product_id = p.product_id)

                    ,(SELECT SUM(sth.qty) FROM stock_transfer_history sth
                    WHERE sth.product_id = p.product_id)
                    ,''
                )
                +
                if(
                    (SELECT SUM(srh.qty_return) FROM sales_return_history srh
                    LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                    LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                    WHERE ini.record_id = p.product_id
                      AND srh.status IS NULL)

                    ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                    LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                    LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                    WHERE ini.record_id = p.product_id
                      AND srh.status IS NULL)
                    ,''
                )
                -
                if(
                    (SELECT SUM(invItem.qty) FROM invoice_item invItem
                    LEFT JOIN (invoice inv) ON (inv.invoice_id = invItem.invoice_id AND inv.status != 'Cancelled' )
                    LEFT JOIN (`order` o) ON (o.order_id = inv.order_id)
                    WHERE record_id = p.product_id
                    )

                    ,(SELECT SUM(invItem.qty) FROM invoice_item invItem
                    LEFT JOIN (invoice inv) ON (inv.invoice_id = invItem.invoice_id AND inv.status != 'Cancelled' )
                    LEFT JOIN (`order` o) ON (o.order_id = inv.order_id)
                    WHERE record_id = p.product_id
                    )
                    ,''
                ), ')')) AS label
              ,p.product_id AS id
              ,if(
                    (SELECT SUM(qty) FROM po_product
                    WHERE product_id = p.product_id)

                    ,(SELECT SUM(qty) FROM po_product
                      WHERE product_id = p.product_id)
                    ,''
                )
                -
                if(
                    (SELECT SUM(damage_qty) FROM po_product
                    WHERE product_id = p.product_id)

                    ,(SELECT SUM(damage_qty) FROM po_product
                      WHERE product_id = p.product_id)
                    ,''
                )
                -
                if(
                    (SELECT SUM(sth.qty) FROM stock_transfer_history sth
                    WHERE sth.product_id = p.product_id)

                    ,(SELECT SUM(sth.qty) FROM stock_transfer_history sth
                    WHERE sth.product_id = p.product_id)
                    ,''
                )
                +
                if(
                    (SELECT SUM(srh.qty_return) FROM sales_return_history srh
                    LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                    LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                    WHERE ini.record_id = p.product_id
                      AND srh.status IS NULL)

                    ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                    LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                    LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                    WHERE ini.record_id = p.product_id
                      AND srh.status IS NULL)
                    ,''
                )
                -
                if(
                    (SELECT SUM(invItem.qty) FROM invoice_item invItem
                    LEFT JOIN (invoice inv) ON (inv.invoice_id = invItem.invoice_id AND inv.status != 'Cancelled' )
                    LEFT JOIN (`order` o) ON (o.order_id = inv.order_id)
                    WHERE record_id = p.product_id
                    )

                    ,(SELECT SUM(invItem.qty) FROM invoice_item invItem
                    LEFT JOIN (invoice inv) ON (inv.invoice_id = invItem.invoice_id AND inv.status != 'Cancelled' )
                    LEFT JOIN (`order` o) ON (o.order_id = inv.order_id)
                    WHERE record_id = p.product_id
                    )
                    ,''
                ) AS stock
        FROM product p
        LEFT JOIN product_company pc ON (pc.product_id = p.product_id)
        LEFT JOIN company c ON (c.company_id = pc.company_id)
        LEFT JOIN product_group pg ON (p.product_group_id = pg.product_group_id)
        WHERE (p.tag_no LIKE '{$productTitle}'
        OR p.bar_code LIKE '{$productTitle}'
        OR p.title LIKE '%{$productTitle}%')
        AND p.published = 1
        
        $productTypeCondition
       
        ORDER BY p.title ASC
        ";
        $result = $db->sql_query($SQL);

        if ($cpCfg['cp.serialKeyActive'] == "SULB-DHEO-0R6K-59CL" || $cpCfg['cp.serialKeyActive'] == "YODX0-9DT58-VCZ5W-A8XXB") {
            $SQL = "
            select * from (
                SELECT p.title AS value
                      ,CONCAT_WS(' | | ', p.tag_no, p.title, FORMAT(p.price, 2)) AS label
                      ,p.product_id AS id
                      ,p.tag_no
                      ,p.product_code
                      ,p.product_type
                      ,p.published
                      ,
                        if(
                            (SELECT SUM(qty) FROM po_product
                            WHERE product_id = p.product_id)

                            ,(SELECT SUM(qty) FROM po_product
                              WHERE product_id = p.product_id)
                            ,''
                        )
                        -
                        if(
                            (SELECT SUM(damage_qty) FROM po_product
                            WHERE product_id = p.product_id)

                            ,(SELECT SUM(damage_qty) FROM po_product
                              WHERE product_id = p.product_id)
                            ,''
                        )
                        -
                        if(
                            (SELECT SUM(sth.qty) FROM stock_transfer_history sth
                            WHERE sth.product_id = p.product_id)

                            ,(SELECT SUM(sth.qty) FROM stock_transfer_history sth
                            WHERE sth.product_id = p.product_id)
                            ,''
                        )
                        +
                        if(
                            (SELECT SUM(srh.qty_return) FROM sales_return_history srh
                            LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                            LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                            WHERE ini.record_id = p.product_id
                              AND srh.status IS NULL)

                            ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                            LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                            LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                            WHERE ini.record_id = p.product_id
                              AND srh.status IS NULL)
                            ,''
                        )
                        -
                        if(
                            (SELECT SUM(invItem.qty) FROM invoice_item invItem
                            LEFT JOIN (invoice inv) ON (inv.invoice_id = invItem.invoice_id AND inv.status != 'Cancelled' )
                            LEFT JOIN (`order` o) ON (o.order_id = inv.order_id)
                            WHERE record_id = p.product_id
                            )

                            ,(SELECT SUM(invItem.qty) FROM invoice_item invItem
                            LEFT JOIN (invoice inv) ON (inv.invoice_id = invItem.invoice_id AND inv.status != 'Cancelled' )
                            LEFT JOIN (`order` o) ON (o.order_id = inv.order_id)
                            WHERE record_id = p.product_id
                            )
                            ,''
                        ) AS stock
                FROM product p
                LEFT JOIN product_company pc ON (pc.product_id = p.product_id)
                LEFT JOIN company c ON (c.company_id = pc.company_id)
                LEFT JOIN product_group pg ON (p.product_group_id = pg.product_group_id)
            ) tmp

            WHERE (tag_no LIKE '%{$productTitle}%' OR product_code LIKE '%{$productTitle}%')
            AND published = 1
            
            $productTypeConditionss
            AND stock > 0
            ORDER BY tag_no
            ";
            $result = $db->sql_query($SQL);
        }

        $dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        return $arr;
    }

    /**
     *
     */
     function getUpdateOrderLineItems() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $tv     = Zend_Registry::get('tv');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');

        $product_id = $fn->getReqParam('product_id');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $SQLOrder = "
        SELECT order_date
        FROM `order`
        WHERE order_id = '{$session_order_id}'
        ";
        $resultOrder = $db->sql_query($SQLOrder);
        $rowOrder = $db->sql_fetchrow($resultOrder);

        $SQL    = "
        SELECT p.title
              ,p.item_code
              ,p.model
              ,p.part_number
              ,p.price
              ,p.gst
              ,p.vat
              ,p.discount_type
              ,p.discount_percentage
              ,p.discount_amount
              ,p.tag_no
              ,p.discount_from_date
              ,p.discount_to_date
              ,pop.cost_price
        FROM product p
        LEFT JOIN (po_product pop) ON (pop.product_id = p.product_id)
        WHERE p.product_id = '{$product_id}'
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        $qty   = 1;
        if ($cpCfg['cp.serialKeyActive'] == "SULB-DHEO-0R6K-59CL" || $cpCfg['cp.serialKeyActive'] == "YODX0-9DT58-VCZ5W-A8XXB") {
            $StockSql = "
            SELECT
                (SELECT SUM(qty) FROM po_product
                WHERE product_id = {$product_id}) as product_qty_purchased
                ,(SELECT SUM(oi.qty) FROM order_item oi
                LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
                WHERE record_id = {$product_id}
                  AND o.order_status != 'Cancelled'
                  AND o.link_stock = 1
                ) as product_qty_sold
                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                WHERE ini.record_id = {$product_id}
                  AND srh.status IS NULL
                ) as sales_return_qty
                ,(SELECT SUM(damage_qty) FROM po_product
                WHERE product_id = {$product_id}
                ) as damaged_qty
            ";
            $resultStockSql = $db->sql_query($StockSql);
            $rowStockSql    = $db->sql_fetchrow($resultStockSql);
            $stock = $rowStockSql['product_qty_purchased'] - $rowStockSql['product_qty_sold'] + $rowStockSql['sales_return_qty'] - $rowStockSql['damaged_qty'];
            if($stock < 0){
                $stock = 0;
            }

            $qty   = $stock;
        }

        if($row['gst'] == ''){
            $row['gst'] = 0;
        }

        $gst = $row['gst'] * $row['price'] / 100;

        if($row['vat'] == ''){
            $row['vat'] = 0;
        }

        $vat = $row['vat'] * $row['price'] / 100;

        if($row['discount_percentage'] == ''){
            $row['discount_percentage'] = 0;
        }

        if($row['discount_amount'] == ''){
            $row['discount_amount'] = 0;
        }


        // This if condition used to check the product discount date range and update the discount on order item
        if($rowOrder['order_date'] >= $row['discount_from_date'] && $rowOrder['order_date'] <= $row['discount_to_date']){
        }
        else{
            $row['discount_amount']     = 0;
            $row['discount_percentage'] = 0;
            $row['discount_type']       = "";
        }

        $discount_value_for_one_qty = 0;
        $discountValue = 0;
        if($row['discount_type'] == '%'){
            if($row['discount_percentage'] > 0){
                $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                $discountValue = $discount_value_for_one_qty * $row['qty'];
            }
        }
        else if($row['discount_type']  == 'Value'){
            if($row['discount_amount'] > 0){
                $discount_value_for_one_qty  =  $row['discount_amount'];
                $discountValue = $discount_value_for_one_qty * $row['qty'];
            }
        }

        $totalAmount = $row['price'] - $discountValue;

        if($row['discount_type'] == ""){
            $row['discount_type'] = $cpCfg['cp.posDefaultDiscountType'];
        }

        if($row['cost_price'] == ""){
            $row['cost_price'] = 0;
        }

        $fa = array();
        $fa['order_id']            = $session_order_id;
        $fa['record_id']           = $product_id;
        $fa['item_title']          = $row['title'];
        $fa['item_code']           = $row['item_code'];
        $fa['model']               = $row['model'];
        $fa['unit_price']          = $row['price'];
        $fa['cost_price']          = $row['cost_price'];
        $fa['ref_code']            = $row['part_number'];
        $fa['discount_type']       = $row['discount_type'];
        $fa['discount_percentage'] = $row['discount_percentage'];
        $fa['discount_amount']     = $row['discount_amount'];
        $fa['qty']                 = $qty;
        $fa['vat']                 = $row['vat'];
        $fa['gst']                 = $row['gst'];
        $fa['tag_no']              = $row['tag_no'];
        $fa['discounted_amount']   = $discountValue;
        $fa['total_amount']        = $totalAmount;

        $SQLOrderItem = "
        SELECT *
        FROM `order_item`
        WHERE order_id = '{$session_order_id}'
          AND record_id = {$product_id}
        ";
        $resultOrderItem = $db->sql_query($SQLOrderItem);
        $rec = $db->sql_fetchrow($resultOrderItem);

        if($rec['order_item_id'] != ''){
            $SQLUpdate = "UPDATE order_item SET qty = ({$rec['qty']} + 1)
                          WHERE order_id = '{$session_order_id}' AND record_id = {$product_id}";
            $resultUpdate = $db->sql_query($SQLUpdate);
        } else {
            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order_item');
            $db->sql_query($SQL);
            $order_item_id = $db->sql_nextid();
        }
    }


    /**
     *
     */
    function getCreateNewOrder() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg  = Zend_Registry::get('cpCfg');

        $staff_id       = $fn->getSessionParam('staff_id');
        $checkLastOrder = $fn->getReqParam('checkLastOrder');
        
        $SQLOrder = "
        SELECT order_id
        FROM `order`
        WHERE order_status = 'New'
        AND record_type = 'POS'
        AND staff_id = {$staff_id}
        ORDER BY order_id DESC
        LIMIT 1
        ";
        $resultOrder  = $db->sql_query($SQLOrder);
        $numRowsOrder = $db->sql_numrows($resultOrder);
        $rowOrder     = $db->sql_fetchrow($resultOrder);
        
        if($checkLastOrder == 1 && $numRowsOrder > 0) {
            $_SESSION['order_id'] = $rowOrder['order_id'];
        } else {
            if ($cpCfg['showGstInBill'] == 1) {
                $gst_status = 'OFF';
            } else {
                $gst_status = 'ON';            
            }

            $SQLOrder = "
            SELECT MAX(CONVERT(bill_number, UNSIGNED INTEGER)) AS bill_number
            FROM `order`
            WHERE order_status != 'Cancelled'
            ";
            $resultOrder   = $db->sql_query($SQLOrder);
            $recOrder      = $db->sql_fetchrow($resultOrder);
            $bill_number   = $recOrder['bill_number'] + 1;

            $fa = array();
            $fa['order_status']    = 'New';
            $fa['record_type']     = 'POS';
            $fa['order_date']      = date('Y-m-d');
            $fa['name_of_company'] = 'POS';
            $fa['gst_status']      = $gst_status;
            $fa['bill_number']     = $bill_number;
            $fa['vat']             = 1;
            $fa['link_stock']      = 1;
            $fa['staff_id']        = $staff_id;
            $fa['invoice_terms']   = $cpCfg['invoiceTermsForPrint'];
            $fa['bill_by']         = $cpCfg['billname'];
            
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $cpSiteIdSession   = $fn->getSessionParam('cp_site_id');
                $fa['site_id']     = $cpSiteIdSession;
            }
            $fa = $fn->addCreationDetailsToFieldsArray($fa, 'order');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order');
            $db->sql_query($SQL);
            $order_id = $db->sql_nextid();

            $_SESSION['order_id'] = $order_id;
        }

    }

    /**
     *
     */
    function getApplyDiscountSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getApplyDiscountValidate()){
            return $validate->getErrorMessageXML();
        }

        $discount_value= $fn->getPostParam('discount_value');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET discount = '{$discount_value}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);


        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getApplyDiscountValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();
        $validate->validateData('discount_value', 'Please enter discount amount');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $discount_value = $fn->getPostParam('discount_value');
        
        $OrderTotalAmount = $this->view->getTotalAmount($session_order_id);
        
        $discountApplied = $fn->getReqParam('discountApplied');
        if($discountApplied != ""){
            $OrderTotalAmount = $OrderTotalAmount + $discountApplied;
        }

        $OrderTotalAmountFormatted = number_format($OrderTotalAmount, 2);

        if($discount_value != "" && $discount_value > 0){
            if($OrderTotalAmount == 0){
                $validate->errorArray['discount_value']['name'] = "discount_value";
                $validate->errorArray['discount_value']['msg']  = "Please select some items before apply discount";
            }
        }

        if($OrderTotalAmount > 0){
            if($discount_value >= $OrderTotalAmount){
                $validate->errorArray['discount_value']['name'] = "discount_value";
                $validate->errorArray['discount_value']['msg']  = "Please enter discount amount as lesser than order amount: {$OrderTotalAmountFormatted}";
            }
        }

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getAddClientSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getAddClientValidate()){
            return $validate->getErrorMessageXML();
        }

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $company_name    = $fn->getReqParam('company_name');
        $mobile          = $fn->getReqParam('mobile');
        $email           = $fn->getReqParam('email');
        $address_flat    = $fn->getReqParam('address_flat');
        $address_street  = $fn->getReqParam('address_street');
        $address_town    = $fn->getReqParam('address_town');
        $address_state   = $fn->getReqParam('address_state');
        $address_country = $fn->getReqParam('address_country');
        $gst_no          = $fn->getReqParam('gst_no');
        $address_po_code = $fn->getReqParam('address_po_code');

        $fa = array();
        $fa['company_name']    = $company_name;
        $fa['mobile']          = $mobile;
        $fa['email']           = $email;
        $fa['address_flat']    = $address_flat;
        $fa['address_street']  = $address_street;
        $fa['address_town']    = $address_town;
        $fa['address_state']   = $address_state;
        $fa['address_country'] = $address_country;
        $fa['address_po_code'] = $address_po_code;
        $fa['gst_no']          = $gst_no;
        $fa['category']        = 'Client';
        $id = $fn->addRecord($fa, 'company');

        $fa1 = array();
        $fa1['cust_company_name']         = $company_name;
        $fa1['company_id']                = $id;
        $fa1['cust_phone']                = $mobile;
        $fa1['cust_email']                = $email;
        $fa1['cust_address1']             = $address_flat;
        $fa1['cust_address2']             = $address_street;
        $fa1['cust_address_city']         = $address_town;
        $fa1['cust_address_state']        = $address_state;
        $fa1['cust_address_country_code'] = $address_country;
        $fa1['cust_gst_no']               = $gst_no;

        $fa1['shipping_first_name']           = $company_name;
        $fa1['shipping_phone']                = $mobile;
        $fa1['shipping_email']                = $email;
        $fa1['shipping_address1']             = $address_flat;
        $fa1['shipping_address2']             = $address_street;
        $fa1['shipping_address_city']         = $address_town;
        $fa1['shipping_address_state']        = $address_state;
        $fa1['shipping_address_country_code'] = $address_country;
        $fa1['shipping_gst_no']               = $gst_no;

        $whereCondition = "WHERE order_id = {$session_order_id}";
        $SQL = $dbUtil->getUpdateSQLStringFromArray($fa1, 'order', $whereCondition);
        $db->sql_query($SQL);

        return $validate->getSuccessMessageXML();
    }


    /**
     *
     */
    function getAddClientValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $validate->resetErrorArray();
        $company_name= $fn->getReqParam('company_name');

        $validate->validateData('company_name', 'Please enter the company name');

        if($company_name != ''){
            $SQL = "
            SELECT c.*
            FROM company c
            WHERE c.company_name = '{$company_name}'
            ";
            $result = $db->sql_query($SQL);
            $numRows  = $db->sql_numrows($result);
            if($numRows > 0){
                $validate->errorArray['company_name']['name'] = "company_name";
                $validate->errorArray['company_name']['msg']  = "Company name already exist";
            }
        }

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
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

        $this->getCancelOrderCurrent();

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
    function getSearchCustomerDetails() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $title = $fn->getReqParam('term', '', true);
        $extractor = explode(" **** ", $title);

        $companyDetail = $extractor[0];

        $SQL = "
        SELECT c.company_name AS value
              ,c.company_name AS label
              ,c.company_id AS id
              ,c.company_name
        FROM company c
        WHERE (c.company_id LIKE '%{$companyDetail}%'
        OR c.company_name LIKE '%{$companyDetail}%'
        OR c.mobile LIKE '%{$companyDetail}%'
        OR c.email LIKE '%{$companyDetail}%')
        ORDER BY c.company_name
        ";
        $result = $db->sql_query($SQL);

        $dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        return $arr;
    }

    /**
     *
     */
    function getDisplayCustomerDetails() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $company_id= $fn->getReqParam('company_id');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $loyaltypoint = '';

        $SQL = "
        SELECT c.*
        FROM company c
        WHERE c.company_id = {$company_id}
        ";
        $result = $db->sql_query($SQL);
        $row    = $db->sql_fetchrow($result);

        if($row['loyalty_point_linked'] != ''){
            $loyaltypoint ="Loyalty point linked";
        }else {
            $loyaltypoint ="
                <div class='btn btn-info'>
                    <a company_name ='{$row['company_name']}' href='#' class='loyaltypoint'>Link Loyalty Point</a>
                </div>
            ";
        }

        $text = "
        <div class='mt10'>
            <div>Company Name: {$row['company_name']}</div>
            <div>Mobile: {$row['mobile']}</div>
            <div>Email : {$row['email']}</div>
            <div>Address: {$row['address_flat']} ,{$row['address_street']} ,{$row['address_town']} {$row['address_state']}</div>
            {$loyaltypoint}
        </div>

        <div class='btn btn-info float_left mt10'>
            <a href='javascript:void(0);' id='removeClient'><span class='removeClientIcon'></span>Remove Client</a>
        </div>
        ";

        $fa1 = array();
        $fa1['cust_company_name']             = $row['company_name'];
        $fa1['company_id']                    = $row['company_id'];
        $fa1['cust_phone']                    = $row['mobile'];
        $fa1['cust_email']                    = $row['email'];
        $fa1['cust_address1']                 = $row['address_flat'];
        $fa1['cust_address2']                 = $row['address_street'];
        $fa1['cust_address_city']             = $row['address_town'];
        $fa1['cust_address_state']            = $row['address_state'];
        $fa1['cust_address_country_code']     = $row['address_country'];
        $fa1['cust_gst_no']                   = $row['gst_no'];

        $fa1['shipping_first_name']           = $row['company_name'];
        $fa1['shipping_phone']                = $row['mobile'];
        $fa1['shipping_email']                = $row['email'];
        $fa1['shipping_address1']             = $row['address_flat'];
        $fa1['shipping_address2']             = $row['address_street'];
        $fa1['shipping_address_city']         = $row['address_town'];
        $fa1['shipping_address_state']        = $row['address_state'];
        $fa1['shipping_address_country_code'] = $row['address_country'];
        $fa1['shipping_gst_no']               = $row['gst_no'];

        $whereCondition = "WHERE order_id = {$session_order_id}";
        $SQL = $dbUtil->getUpdateSQLStringFromArray($fa1, 'order', $whereCondition);
        $db->sql_query($SQL);

        return $text;
    }

    /**
     *
     */
    function getRemoveClient() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $fa1 = array();
        $fa1['cust_company_name'] = '';
        $fa1['company_id'] = '';
        $fa1['cust_phone'] = '';
        $fa1['cust_email'] = '';
        $fa1['cust_address1'] = '';
        $fa1['cust_address2'] = '';
        $fa1['cust_address_city'] = '';
        $fa1['cust_address_state'] = '';
        $fa1['cust_address_country_code'] = '';

        $whereCondition = "WHERE order_id = {$session_order_id}";
        $SQL = $dbUtil->getUpdateSQLStringFromArray($fa1, 'order', $whereCondition);
        $db->sql_query($SQL);

        return $text;
    }

    /**
     *
     */
    function getUpdateQtyOrderItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id = $fn->getReqParam('order_item_id');
        $qty = $fn->getReqParam('qty');

        $SQL    = "
        UPDATE order_item
        set qty = {$qty}
        WHERE order_item_id = {$order_item_id}
        ";
        $result = $db->sql_query($SQL);

    }

    /**
     *
     */
    function getUpdateWeightOrderItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id = $fn->getReqParam('order_item_id');
        $weight = $fn->getReqParam('weight');
        if($weight == ''){
            $weight = 0;
        }

        $SQL    = "
        UPDATE order_item
        set weight = {$weight}, qty = 0
        WHERE order_item_id = {$order_item_id}
        ";
        $result = $db->sql_query($SQL);

    }

    /**
     *
     */
    function getUpdatediscountType() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id       = $fn->getReqParam('order_item_id');
        $discount_type       = $fn->getReqParam('discount_type');
        $discount_percentage = $fn->getReqParam('discount_percentage');

        $order_item_percentage = 0;

        if ($discount_type !=''){
            $order_item_percentage = $discount_type;
            //$discount_type ='%';
        }

        if($discount_type == "%"){
            $SQL    = "
            UPDATE order_item
            set discount_percentage = '{$discount_percentage}', discount_type ='%', discount_amount = '0.00'
            WHERE order_item_id = {$order_item_id}
            ";
            $result = $db->sql_query($SQL);
        }else if($discount_type == "Value"){
            $SQL    = "
            UPDATE order_item
            set discount_amount = '{$discount_percentage}', discount_type ='Value', discount_percentage = '0.00'
            WHERE order_item_id = {$order_item_id}
            ";
            $result = $db->sql_query($SQL);
        }else{
            $SQL    = "
            UPDATE order_item
            set discount_amount = '0.00', discount_type ='', discount_percentage = '0.00'
            WHERE order_item_id = {$order_item_id}
            ";
            $result = $db->sql_query($SQL);
        }

    }

    /**
     *
     */
    function getUpdateDiscountPercentOrderItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id = $fn->getReqParam('order_item_id');
        $discount_percentage = $fn->getReqParam('discount_percentage');
        $discount_type = $fn->getReqParam('discount_type');

        if($discount_type == "%"){
            $SQL    = "
            UPDATE order_item
            set discount_percentage = '{$discount_percentage}', discount_type ='%', discount_amount = '0.00'
            WHERE order_item_id = {$order_item_id}
            ";
            $result = $db->sql_query($SQL);
        }else{
            $SQL    = "
            UPDATE order_item
            set discount_amount = '{$discount_percentage}', discount_type ='Value', discount_percentage = '0.00'
            WHERE order_item_id = {$order_item_id}
            ";
            $result = $db->sql_query($SQL);
        }

    }

    /**
     *
     */
    function getUpdatePiecesOrderItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id = $fn->getReqParam('order_item_id');
        $pieces = $fn->getReqParam('pieces');

        $SQL    = "
        UPDATE order_item
        set pieces = '{$pieces}'
        WHERE order_item_id = {$order_item_id}
        ";
        $result = $db->sql_query($SQL);

    }

    /**
     *
     */
    function getUpdateDiscountOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_id = $fn->getReqParam('order_id');
        $discount = $fn->getReqParam('discount');

        $SQL    = "
        UPDATE `order`
        set discount = {$discount}
        WHERE order_id = {$order_id}
        ";
        $result = $db->sql_query($SQL);

    }

    /**
     *
     */
    function getUpdateBalance() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $amount_given = $fn->getReqParam('amount_given');
        $netTotal = $fn->getReqParam('netTotal');

        $balance = $amount_given - $netTotal;
        $balance = number_format($balance, 2);
        return $balance;

    }

    /**
     *
     */
    function getCancelOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $SQL    = "
        UPDATE `order`
        set order_status = 'Cancelled'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $SQL    = "
        UPDATE `invoice`
        set status = 'Cancelled'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $SQL    = "
        UPDATE `receipt`
        set receipt_status = 'Cancelled'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $_SESSION['order_id'] = '';

    }

    /**
     *
     */
    function getCancelOrderCurrent() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $SQL    = "
        UPDATE `order`
        set order_status = 'Cancelled'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $SQL    = "
        UPDATE `invoice`
        set status = 'Cancelled'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $SQL    = "
        UPDATE `receipt`
        set receipt_status = 'Cancelled'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $this->getCreateNewOrder();

    }

    /**
     *
     */
    function getCloseOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $_SESSION['order_id'] = '';

    }

    /**
     *
     */
    function getDeleteItem(){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $formObj = Zend_Registry::get('formObj');
        $cpCfg = Zend_Registry::get('cpCfg');

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : false;
        $order_item_id    = $fn->getReqParam('order_item_id');

        if($session_order_id){
            $deleteSQL    = "
            DELETE FROM order_item
            WHERE order_id = {$session_order_id}
            AND order_item_id = {$order_item_id}
            ";
            $result = $db->sql_query($deleteSQL);
        }
        return;
    }

    /**
     *
     */
    function getCreditCardSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getCreditCardValidate()){
            return $validate->getErrorMessageXML();
        }

        $credit_card_no = $fn->getPostParam('credit_card_no');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET credit_card_no = '{$credit_card_no}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getCreditCardValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();
        $validate->validateData('credit_card_no', 'Please enter the card number');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getSaleByNameSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getSaleByNameValidate()){
            return $validate->getErrorMessageXML();
        }

        $name = $fn->getPostParam('name');

        $sqlUpdate = "
        UPDATE `setting` SET value = '{$name}' WHERE key_text = 'billname'
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getSaleByNameValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();
        $validate->validateData('name', 'Please enter the name');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getModeOfPaymentUpdate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $mode_of_payment = $fn->getReqParam('mode_of_payment');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET mode_of_payment = '{$mode_of_payment}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);
    }

    function getTableValueUpdate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $table_value = $fn->getReqParam('table_value');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET table_value = '{$table_value}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);
    }

    /**
     *
     */
    function getGSTStatusUpdate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $gst_status = $fn->getReqParam('gst_status');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET gst_status = '{$gst_status}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);
    }

    /**
     *
     */
    function getLoyaltyUpdate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $cust_company_name = $fn->getReqParam('cust_company_name');

        $sqlUpdate = "
        UPDATE `company` SET loyalty_point_linked = '1'
        WHERE company_name = '{$cust_company_name}'
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);
    }

    function getApplyShippingChargesSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getApplyShippingChargesValidate()){
            return $validate->getErrorMessageXML();
        }

        $shipping_charge  = $fn->getPostParam('shipping_charge');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET shipping_charge = '{$shipping_charge}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);


        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getApplyShippingChargesValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();
        $validate->validateData('shipping_charge', 'Please enter shipping charge amount');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $shipping_charge = $fn->getPostParam('shipping_charge');
        
        $OrderTotalAmount = $this->view->getTotalAmount($session_order_id);
        
        if($shipping_charge != ""){
            $OrderTotalAmount = $OrderTotalAmount;
        }

        $OrderTotalAmountFormatted = number_format($OrderTotalAmount, 2);

        if($shipping_charge != "" && $shipping_charge > 0){
            if($OrderTotalAmount == 0){
                $validate->errorArray['shipping_charge']['name'] = "shipping_charge";
                $validate->errorArray['shipping_charge']['msg']  = "Please select some items before apply shipping charge";
            }
        }

        if($OrderTotalAmount > 0){
            if($shipping_charge >= $OrderTotalAmount){
                $validate->errorArray['shipping_charge']['name'] = "shipping_charge";
                $validate->errorArray['shipping_charge']['msg']  = "Please enter shipping charge amount as lesser than order amount: {$OrderTotalAmountFormatted}";
            }
        }

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getRemoveShippingChargeOrder() {
        $fn    = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        
        $order_id  = $fn->getReqParam('order_id');
        
        $SQLOrder = "
        UPDATE `order`
        SET shipping_charge = '0.00'
        WHERE order_id = {$order_id}
        ";
        $resultOrder = $db->sql_query($SQLOrder);
    }

    /**
     *
     */
    function getUpdateOrderDate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $dateChanged = $fn->getReqParam('dateChanged');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET order_date = '{$dateChanged}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);
    }

    /**
     *
     */
    function getUpdateRefNoOrderItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id = $fn->getReqParam('order_item_id');
        $ref_no        = $fn->getReqParam('ref_no');

        $SQL    = "
        UPDATE order_item
        SET ref_no = {$ref_no}
        WHERE order_item_id = {$order_item_id}
        ";
        $result = $db->sql_query($SQL);
    }

    /**
     *
     */
    function getAddDefaultDiscountTypeSubmit(){
        $validate = Zend_Registry::get('validate');
        $fn       = Zend_Registry::get('fn');
        $dbUtil   = Zend_Registry::get('dbUtil');
        $db       = Zend_Registry::get('db');

        if (!$this->getAddDefaultDiscountTypeValidate()){
            return $validate->getErrorMessageXML();
        }

        $discount_type    = $fn->getPostParam('discount_type_default');

        $sqlUpdate = "
        UPDATE `setting` SET value = '{$discount_type}'
        WHERE key_text = 'cp.posDefaultDiscountType'
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getAddDefaultDiscountTypeValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();
        $validate->validateData('discount_type_default', 'Please select discount_type');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getUpdateUnitPriceOrderItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id  = $fn->getReqParam('order_item_id');
        $unit_price     = $fn->getReqParam('unit_price');

        $SQL    = "
        UPDATE order_item
        SET unit_price = '{$unit_price}'
        WHERE order_item_id = {$order_item_id}
        ";
        $result = $db->sql_query($SQL);

    }
}
