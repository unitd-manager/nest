<?
class CPL_Admin_Modules_Tradingsg_PurchaseOrder_Model extends CP_Admin_Modules_Tradingsg_PurchaseOrder_Model
{
    /**
     *
     */
    function getFields(){
        $fn = Zend_Registry::get('fn');

        $fa = array();
        $fa = $fn->addToFieldsArray($fa, 'po_code');
        $fa = $fn->addToFieldsArray($fa, 'company_id_supplier');
        $fa = $fn->addToFieldsArray($fa, 'contact_id_supplier');
        $fa = $fn->addToFieldsArray($fa, 'payment_terms');
        $fa = $fn->addToFieldsArray($fa, 'status');
        $fa = $fn->addToFieldsArray($fa, 'notes');
        $fa = $fn->addToFieldsArray($fa, 'purchase_order_date');
        $fa = $fn->addToFieldsArray($fa, 'buy_currency');
        $fa = $fn->addToFieldsArray($fa, 'staff_id');
        $fa = $fn->addToFieldsArray($fa, 'delivery_address');
        $fa = $fn->addToFieldsArray($fa, 'consignee_name');
        $fa = $fn->addToFieldsArray($fa, 'consignee_address');
        $fa = $fn->addToFieldsArray($fa, 'consignee_phone');
        $fa = $fn->addToFieldsArray($fa, 'consignee_contact_person');
        $fa = $fn->addToFieldsArray($fa, 'deposit_paid');
        $fa = $fn->addToFieldsArray($fa, 'port_of_origin');
        $fa = $fn->addToFieldsArray($fa, 'deposit_note');
        $fa = $fn->addToFieldsArray($fa, 'shipment_no');
        $fa = $fn->addToFieldsArray($fa, 'required_delivery_date');
        $fa = $fn->addToFieldsArray($fa, 'priority');
        $fa = $fn->addToFieldsArray($fa, 'delivery_terms');
        $fa = $fn->addToFieldsArray($fa, 'follow_up_date');
        $fa = $fn->addToFieldsArray($fa, 'freight_cost');

        return $fa;
    }

    /**
     *
     */
    function getTradingsgPurchaseOrderTradingsgProductLinkSQL($id) {

        $SQL = "
        SELECT po.po_product_id
              ,p.title AS product_name
              ,p.part_number
              ,po.price
              ,po.qty
              ,po.qty_delivered
              ,po.status
        FROM po_product po
        JOIN product p ON (p.product_id = po.product_id)
        WHERE po.purchase_order_id = {$id}
        ";

        return $SQL;
    }

    /**
     *
     */
    function getImportData(){
        $phpExcel = includeCPClass('Lib', 'PhpExcelImportWrapper');
        $db = Zend_Registry::get('db');

        /*
        STOCK - qty_in_stock - qty
        PRODUCT - title
        Item Code - item_code
        Category - category_id
        FC from China - fc_price
        Purchase Cost from BLOSSOMS - price
        Product Weight - product_weight
        VAT% - vat_percentage
        Price per KG - weight_per_kg
        Product Display Price - selling_price
        Add Shipping Cost - logistics
        Comission Calculation Price (Less VAT & Logistics) - agent_price
        TC Comsn % ( 5% - 20%) - commission
        */

        $fa = array(
              'title' => $phpExcel->getImportFldObj('PRODUCT')
             ,'purchase_order_date' => $phpExcel->getImportFldObj('Purchase Date')
             //,'item_code' => $phpExcel->getImportFldObj('ITEM CODE')
             //,'price'          => $phpExcel->getImportFldObj('COST')
             ,'hsn'          => $phpExcel->getImportFldObj('HSN CODE')
             ,'qty'          => $phpExcel->getImportFldObj('Qty')
             ,'product_weight' => $phpExcel->getImportFldObj('PR. WT')
             ,'weight_per_kg'  => $phpExcel->getImportFldObj('WT / KG')
             ,'gst' => $phpExcel->getImportFldObj('GST%')
             ,'logistics'      => $phpExcel->getImportFldObj('Logistics')
             ,'selling_price'  => $phpExcel->getImportFldObj('Display Price')
             ,'agent_price'    => $phpExcel->getImportFldObj('Product Price')
             ,'actual_price'    => $phpExcel->getImportFldObj('Actual Price')
             ,'commission'     => $phpExcel->getImportFldObj('TP Commission')
             //,'tp_commission'  => $phpExcel->getImportFldObj('TP Commission%')
             ,'ctp_commission' => $phpExcel->getImportFldObj('CTP Commission%')
             ,'description'   => $phpExcel->getImportFldObj('Description')
             ,'supplier'    => $phpExcel->getImportFldObj('Supplier')
             ,'category'    => $phpExcel->getImportFldObj('Category')
             ,'section'    => $phpExcel->getImportFldObj('Section')
             ,'no_of_pcs'    => $phpExcel->getImportFldObj('No. of Pcs')
             ,'return_days'    => $phpExcel->getImportFldObj('Return Days')
             ,'image_code'    => $phpExcel->getImportFldObj('Image Code')
             ,'tamil_description'    => $phpExcel->getImportFldObj('Tamil Description')
             ,'color_size_qty1'    => $phpExcel->getImportFldObj('CSMQ1')
             ,'color_size_qty2'    => $phpExcel->getImportFldObj('CSMQ2')
        );

        $fa['po_code']['defaultValue'] = $this->getUpdatePOCode();
        $fa['title']['refOnly'] = true;
        //$fa['item_code']['refOnly'] = true;
        //$fa['price']['refOnly'] = true;
        $fa['product_weight']['refOnly'] = true;
        $fa['weight_per_kg']['refOnly'] = true;
        $fa['gst']['refOnly'] = true;
        $fa['logistics']['refOnly'] = true;
        $fa['selling_price']['refOnly'] = true;
        $fa['agent_price']['refOnly'] = true;
        $fa['commission']['refOnly'] = true;
        $fa['category']['refOnly'] = true;
        $fa['section']['refOnly'] = true;
        $fa['qty']['refOnly'] = true;
        //$fa['tp_commission']['refOnly'] = true;
        $fa['ctp_commission']['refOnly'] = true;
        $fa['supplier']['refOnly'] = true;
        $fa['description']['refOnly'] = true;
        $fa['no_of_pcs']['refOnly'] = true;
        $fa['return_days']['refOnly'] = true;
        $fa['image_code']['refOnly'] = true;
        $fa['tamil_description']['refOnly'] = true;
        $fa['color_size_qty1']['refOnly'] = true;
        $fa['color_size_qty2']['refOnly'] = true;
        $fa['hsn']['refOnly'] = true;
        $fa['actual_price']['refOnly'] = true;

        /****************************************/
        $config = array(
             'module'              => 'vunited_purchaseOrder'
            ,'matchFieldArr'       => array('purchase_order_date')
            ,'fldsArr'             => $fa
            ,'callbackAfterInsert' => 'importDataRowCallback'
        );

        return $phpExcel->importData($config);
    }

    /**
     *
     */
    function importDataRowCallback($purchase_order_id, $fa) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $qty = $fa['qty'];
        //$price = $fa['price'];
        $category = $fa['category'];
        $section = $fa['section'];
        //$item_code = $fa['item_code'];
        $title = $fa['title'];
        $product_weight = $fa['product_weight'];
        $weight_per_kg = $fa['weight_per_kg'];
        $gst = $fa['gst'];
        $logistics = $fa['logistics'];
        $selling_price = $fa['selling_price'];
        $agent_price = $fa['agent_price'];
        $commission = $fa['commission'];
        //$tp_commission = $fa['tp_commission'];
        $ctp_commission = $fa['ctp_commission'];
        $supplier = $fa['supplier'];
        $description = $fa['description'];
        $no_of_pcs = $fa['no_of_pcs'];
        $return_days = $fa['return_days'];
        $image_code = $fa['image_code'];
        $tamil_description = $fa['tamil_description'];
        $color_size_qty1 = $fa['color_size_qty1'];
        $color_size_qty2 = $fa['color_size_qty2'];
        $hsn = $fa['hsn'];
        $actual_price = $fa['actual_price'];

        $sqlcount1 = "
        SELECT COUNT(*)
        FROM `section`
        WHERE title = '{$section}'
        ";
        $resultcount1 = $db->sql_query($sqlcount1);
        $secRecCount    = $db->sql_fetchrow($resultcount1);

        if ($secRecCount == 0 && $section != '') {
            $fa1 = array();
            $fa1['title'] = $section;
            $fa1['published'] = 1;
            $fa1['section_type'] = 'Product';

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa1, 'section');
            $result = $db->sql_query($SQL);
            $section_id  = $db->sql_nextid();
        } else {
            $sqlsec = "
            SELECT section_id
                   ,title FROM section
                   WHERE title = '{$section}'
            ";
            $resultsec = $db->sql_query($sqlsec);
            $secRec    = $db->sql_fetchrow($resultsec);

            $section_id  = $secRec['section_id'];
        }

        $sqlcount3 = "
        SELECT COUNT(*)
        FROM `category`
        WHERE title = '{$category}'
        ";
        $resultcount3 = $db->sql_query($sqlcount3);
        $catRecCount    = $db->sql_fetchrow($resultcount3);

        if ($catRecCount == 0 && $category != '') {
            $fa1 = array();
            $fa1['title'] = $category;
            $fa1['published'] = 1;
            $fa1['section_id'] = $section_id;
            $fa1['category_type'] = 'Product';

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa1, 'category');
            $result = $db->sql_query($SQL);
            $category_id  = $db->sql_nextid();
        } else {
            $sqlcat = "
            SELECT category_id
                   ,title FROM category
                   WHERE title = '{$category}'
            ";
            $resultcat = $db->sql_query($sqlcat);
            $catRec    = $db->sql_fetchrow($resultcat);

            $category_id  = $catRec['category_id'];
        }

        $sqlcount2 = "
        SELECT COUNT(*) AS supplier_count
        FROM `supplier`
        WHERE company_name = '{$supplier}'
        ";
        $resultcount2 = $db->sql_query($sqlcount2);
        $supRecCount    = $db->sql_fetchrow($resultcount2);

        if ($supRecCount['supplier_count'] == 0 && $supplier != '') {
            $fa1 = array();
            $fa1['company_name'] = $supplier;

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa1, 'supplier');
            $result = $db->sql_query($SQL);
            $supplier_id  = $db->sql_nextid();
        } else {
            $sqlsup = "
            SELECT supplier_id
                   ,company_name FROM supplier
                   WHERE company_name = '{$supplier}'
            ";
            $resultsup = $db->sql_query($sqlsup);
            $supRec    = $db->sql_fetchrow($resultsup);

            $supplier_id  = $supRec['supplier_id'];
        }

        $fa2 = array();
        $fa2['qty_in_stock'] = $qty;
        $fa2['no_of_pcs'] = $no_of_pcs;
        $fa2['title']  = $title;
        $fa2['price']  = $selling_price;
        $fa2['section_id']  = $section_id;
        $fa2['category_id']  = $category_id;
        $fa2['supplier_id']  = $supplier_id;
        $fa2['product_code'] = $image_code;
        $fa2['gst']  = $gst;
        $fa2['published']  = 1;
        $fa2['logistics']  = $logistics;
        $fa2['agent_price']  = $agent_price;
        $fa2['commission']  = $commission;
        $fa2['product_weight']  = $product_weight;
        $fa2['weight_per_kg']  = $weight_per_kg;
        $fa2['description']  = $description;
        //$fa2['tp_commission']  = $tp_commission;
        $fa2['ctp_commission']  = $ctp_commission;
        $fa2['return_days']  = $return_days;
        $fa2['image_code']  = $image_code;
        $fa2['tamil_description']  = $tamil_description;
        $fa2['hsn']  = $hsn;
        $fa2['actual_price']  = $actual_price;
        if($color_size_qty1 != ''){
            $fa2['color']  = 1;
            $fa2['product_size']  = 1;
            $fa2['model']  = 1;
        }
        $fa2 = $fn->addCreationDetailsToFieldsArray($fa2, 'product');

        $SQL = $dbUtil->getInsertSQLStringFromArray($fa2, 'product');
        $result = $db->sql_query($SQL);
        $product_id  = $db->sql_nextid();

        if($color_size_qty1 != ''){
            $this->getCreateColorAndSizeRecords($color_size_qty1, $product_id);
        }
        if($color_size_qty2 != ''){
            $this->getCreateColorAndSizeRecords($color_size_qty2, $product_id);
        }

        $SQLpcs = "
        SELECT *
        FROM product_color_by_size
        WHERE product_id = {$product_id}
        ";
        $resultpcs = $db->sql_query($SQLpcs);
        $numRowspcs = $db->sql_numrows($resultpcs);

        $SQLpc = "
        SELECT *
        FROM product_color
        WHERE product_id = {$product_id}
        ";
        $resultpc = $db->sql_query($SQLpc);
        $numRowspc = $db->sql_numrows($resultpc);

        $SQLps = "
        SELECT *
        FROM product_size
        WHERE product_id = {$product_id}
        ";
        $resultps = $db->sql_query($SQLps);
        $numRowsps = $db->sql_numrows($resultps);

        $SQLpm = "
        SELECT *
        FROM product_model
        WHERE product_id = {$product_id}
        ";
        $resultpm = $db->sql_query($SQLpm);
        $numRowspm = $db->sql_numrows($resultpm);

        while ($rowpm = $db->sql_fetchrow($resultpm)) {
            $fa3 = array();
            $fa3['product_id'] = $product_id;
            $fa3['purchase_order_id']  = $purchase_order_id;
            $fa3['qty']  = $rowpm['qty'];
            $fa3['qty_requested']  = $rowpm['qty'];
            //$fa3['price']  = $price;
            $fa3['product_weight']  = $product_weight;
            $fa3['weight_per_kg']  = $weight_per_kg;
            $fa3['gst']  = $gst;
            $fa3['logistics']  = $logistics;
            $fa3['selling_price']  = $selling_price;
            $fa3['agent_price']  = $agent_price;
            $fa3['commission']  = $commission;
            $fa3['color_size_code']  = $rowpm['code'];
            $fa3 = $fn->addCreationDetailsToFieldsArray($fa3, 'po_product');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa3, 'po_product');
            $result = $db->sql_query($SQL);
        }

        if($numRowspcs > 0){
            while ($rowpcs = $db->sql_fetchrow($resultpcs)) {
                $fa3 = array();
                $fa3['product_id'] = $product_id;
                $fa3['purchase_order_id']  = $purchase_order_id;
                $fa3['qty']  = $rowpcs['qty'];
                $fa3['qty_requested']  = $rowpcs['qty'];
                //$fa3['price']  = $price;
                $fa3['product_weight']  = $product_weight;
                $fa3['weight_per_kg']  = $weight_per_kg;
                $fa3['gst']  = $gst;
                $fa3['logistics']  = $logistics;
                $fa3['selling_price']  = $selling_price;
                $fa3['agent_price']  = $agent_price;
                $fa3['commission']  = $commission;
                $fa3['color_size_code']  = $rowpcs['code'];
                $fa3 = $fn->addCreationDetailsToFieldsArray($fa3, 'po_product');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa3, 'po_product');
                $result = $db->sql_query($SQL);
            }
        } else if($numRowspc > 0 || $numRowsps > 0){
            while ($rowpc = $db->sql_fetchrow($resultpc)) {
                $fa3 = array();
                $fa3['product_id'] = $product_id;
                $fa3['purchase_order_id']  = $purchase_order_id;
                $fa3['qty']  = $rowpc['qty'];
                $fa3['qty_requested']  = $rowpc['qty'];
                //$fa3['price']  = $price;
                $fa3['product_weight']  = $product_weight;
                $fa3['weight_per_kg']  = $weight_per_kg;
                $fa3['gst']  = $gst;
                $fa3['logistics']  = $logistics;
                $fa3['selling_price']  = $selling_price;
                $fa3['agent_price']  = $agent_price;
                $fa3['commission']  = $commission;
                $fa3['color_size_code']  = $rowpc['code'];
                $fa3 = $fn->addCreationDetailsToFieldsArray($fa3, 'po_product');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa3, 'po_product');
                $result = $db->sql_query($SQL);
            }

            while ($rowps = $db->sql_fetchrow($resultps)) {
                $fa3 = array();
                $fa3['product_id'] = $product_id;
                $fa3['purchase_order_id']  = $purchase_order_id;
                $fa3['qty']  = $rowps['qty'];
                $fa3['qty_requested']  = $rowps['qty'];
                //$fa3['price']  = $price;
                $fa3['product_weight']  = $product_weight;
                $fa3['weight_per_kg']  = $weight_per_kg;
                $fa3['gst']  = $gst;
                $fa3['logistics']  = $logistics;
                $fa3['selling_price']  = $selling_price;
                $fa3['agent_price']  = $agent_price;
                $fa3['commission']  = $commission;
                $fa3['color_size_code']  = $rowps['code'];
                $fa3 = $fn->addCreationDetailsToFieldsArray($fa3, 'po_product');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa3, 'po_product');
                $result = $db->sql_query($SQL);
            }

        } else {
            $fa3 = array();
            $fa3['product_id'] = $product_id;
            $fa3['purchase_order_id']  = $purchase_order_id;
            $fa3['qty']  = $qty;
            $fa3['qty_requested']  = $qty;
            //$fa3['price']  = $price;
            $fa3['product_weight']  = $product_weight;
            $fa3['weight_per_kg']  = $weight_per_kg;
            $fa3['gst']  = $gst;
            $fa3['logistics']  = $logistics;
            $fa3['selling_price']  = $selling_price;
            $fa3['agent_price']  = $agent_price;
            $fa3['commission']  = $commission;
            $fa3 = $fn->addCreationDetailsToFieldsArray($fa3, 'po_product');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa3, 'po_product');
            $result = $db->sql_query($SQL);
        }

            /*$invRec = $fn->getRecordByCondition('inventory', "product_id = '{$product_id}'");
            $fa4 = array();
            $fa4['product_id'] = $product_id;
            $fa4['actual_stock'] = $invRec['actual_stock'] + $qty;
            $fa4 = $fn->addCreationDetailsToFieldsArray($fa4, 'inventory');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa4, 'inventory');
            $result = $db->sql_query($SQL);*/
    }

    /**
     *
     */
    function getCreateColorAndSizeRecords($color_size_qty, $product_id) {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $csq_arr = explode('-', $color_size_qty);
        $color = $csq_arr[0];
        $size = $csq_arr[1];
        $model = $csq_arr[2];
        $csqty = $csq_arr[3];

        $colorVal = ltrim($color,"C");
        $sizeVal = ltrim($size,"S");
        $modelVal = ltrim($model,"M");
        $csqtyVal = ltrim($csqty,"Q");

        if($colorVal != ''){
            $fa = array();
            $fa['product_id'] = $product_id;
            $fa['color'] = $colorVal;
            $fa['qty']  = $csqtyVal;
            $fa['code']  = $this->getUpdateColorCode();
            $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_color');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_color');
            $result = $db->sql_query($SQL);
            $product_color_id = $db->sql_nextid();
        }

        if($sizeVal != ''){
            $fa = array();
            $fa['product_id'] = $product_id;
            $fa['size'] = $sizeVal;
            $fa['qty']  = $csqtyVal;
            $fa['code']  = $this->getUpdateSizeCode();
            $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_size');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_size');
            $result = $db->sql_query($SQL);
            $product_size_id = $db->sql_nextid();
        }

        if($modelVal != ''){
            $fa = array();
            $fa['product_id'] = $product_id;
            $fa['model'] = $modelVal;
            $fa['qty']  = $csqtyVal;
            $fa['code']  = $this->getUpdateModelCode();
            $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_model');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_model');
            $result = $db->sql_query($SQL);
            $product_model_id = $db->sql_nextid();
        }

        if($colorVal != '' && $sizeVal != ''){
            $fa = array();
            $fa['product_id'] = $product_id;
            $fa['product_color_id'] = $product_color_id;
            $fa['product_size_id']  = $product_size_id;
            $fa['qty']  = $csqtyVal;
            $fa['code']  = $this->getUpdateColorSizeCode();
            $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_color_by_size');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_color_by_size');
            $result = $db->sql_query($SQL);
        }
    }

    /**
     *
     */
    function getUpdateColorCode() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $SQL = "
        SELECT code
        FROM product_color
        ORDER BY product_color_id DESC
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);
        $count = 1;

        $color_code = ltrim($row['code'],"C");
        $colorCode = 'C'.$color_code + 1;

        return $colorCode;
    }

    /**
     *
     */
    function getUpdateSizeCode() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $SQL = "
        SELECT code
        FROM product_size
        ORDER BY product_size_id DESC
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);
        $count = 1;

        $size_code = ltrim($row['code'],"S");
        $sizeCode = 'S'.$size_code + 1;

        return $sizeCode;
    }

    /**
     *
     */
    function getUpdateModelCode() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $SQL = "
        SELECT code
        FROM product_model
        ORDER BY product_model_id DESC
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);
        $count = 1;

        $model_code = ltrim($row['code'],"M");
        $modelCode = 'M'.$model_code + 1;

        return $modelCode;
    }

    /**
     *
     */
    function getUpdateColorSizeCode() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $SQL = "
        SELECT code
        FROM product_color_by_size
        ORDER BY product_color_by_size_id DESC
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);
        $count = 1;

        $size_code = ltrim($row['code'],"CS");
        $sizeCode = 'CS'.$size_code + 1;

        return $sizeCode;
    }

    /**
     *
     */
    function getUpdatePOCode() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        /* Updation of Purchase order Code */
        $poCode = $fn->getSettingsValueByKey("nextPurchaseOrderCode");

        $POCode = $fn->getSettingsValueByKey('poCodePrefix') . $poCode;

        $SQL    = "UPDATE setting SET value = (value+1) WHERE key_text = 'nextPurchaseOrderCode'";
        $result = $db->sql_query($SQL);

        return $POCode;
    }
    /**
     *
     */
    function getUpdateProductCode() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        /* Updation of Product Code */
        $nextProductItemCode = $fn->getSettingsValueByKey("nextProductCode");
        $ProCode = $nextProductItemCode;

        //To update Product code
        $SQLUpdate = "UPDATE setting SET value = (value+1) WHERE key_text = 'nextProductCode'";
        $resultUpdate = $db->sql_query($SQLUpdate);


        return $ProCode;
    }
}