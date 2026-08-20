<?
class CPL_Admin_Modules_Tradingsg_Quote_Model extends CP_Admin_Modules_Tradingsg_Quote_Model
{
    function getSQL() {
        $fnsModDeliveryAddress = getCPFnObj('trading_deliveryAddressLink');

        $extraTableNames = '';
        $joinFlds = '';

        $SQL = "
      	SELECT DISTINCT q.quote_id
              ,q.title
              ,q.quote_code
              ,q.quote_date
              ,q.status
              ,q.priority
              ,q.follow_up_date
              ,q.creation_date
              ,q.modification_date
              ,q.currency
              ,q.note
              ,q.delivery_terms
              ,q.payment_terms
              ,q.created_by
              ,q.modified_by
              ,q.delivery_location
              ,q.quote_type
              ,q.delivery_date
              ,q.amount
              ,q.company_id
              ,q.contact_id
              ,q.staff_id
              ,q.enquiry_id
              ,q.flag
      	      ,co.company_name
              ,CONCAT_WS(' ', con.first_name, con.last_name) AS contact_name
              ,CONCAT_WS(' ', s.first_name, s.last_name) AS staff_name
      	FROM quote q
        LEFT JOIN company co ON (q.company_id = co.company_id)
      	LEFT JOIN contact con ON (q.contact_id = con.contact_id)
      	LEFT JOIN staff s ON (q.staff_id = s.staff_id)
      	LEFT JOIN quote_product qp ON (qp.quote_id = q.quote_id)
      	LEFT JOIN product p ON (qp.product_id = p.product_id)
      	LEFT JOIN product_group pg ON (pg.product_group_id = p.product_group_id)
      	LEFT JOIN product_group_staff pgs ON (pgs.product_group_id = p.product_group_id)
        ";

        return $SQL;
    }
    /**
     *
     */
    function getSearchProductTitle() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');

        $title = $fn->getReqParam('term', '', true);
        $extractor = explode(" **** ", $title);

        $productTitle = $extractor[0];

        $SQL = "
        SELECT p.title AS value
              ,p.title AS label
              ,CONCAT_WS(' **** ', p.title,p.part_number, p.price, p.unit, c.company_name,pg.title) AS label
        	  ,p.product_id AS id
        FROM product p
        LEFT JOIN product_company pc ON (pc.product_id = p.product_id)
        LEFT JOIN company c ON (c.company_id = pc.company_id)
        LEFT JOIN product_group pg ON (p.product_group_id = pg.product_group_id)
        WHERE p.title LIKE '%{$productTitle}%'
        AND p.published = 1
        ORDER BY p.title
        ";
        $result = $db->sql_query($SQL);

        $dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        return $arr;
    }
    /**
     *
     */
     function getUpdateProductLineItems() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');

        $product_id = $fn->getReqParam('product_id');
        $rec_id = $fn->getReqParam('rec_id');
        $id = $tv['srcRoomId'];
        $selling_price_per = '';
        $discountValue     = '';
        $marginValue     = '';
        $cost_price_discount =  '';
        $cost_price_margin = '';
        $discount_type     = '';

        $arr = array('price' => 0, 'margin' => 0, 'title' => '', 'sellingPrice' => 0);

        $SQL    = "
        SELECT p.price
              ,p.part_number
              ,p.product_group_id
              ,p.item_code
              ,p.unit
              ,p.category_id
              ,p.price_from_supplier
              ,pg.margin
              ,pg.title
        FROM product p
        LEFT JOIN product_group pg ON (pg.product_group_id = p.product_group_id)
        WHERE p.product_id = '{$product_id}'
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        $quoteProductRec = $fn->getRecordRowByID('quote_product', 'quote_product_id', $rec_id);
        $quoteRec = $fn->getRecordRowByID('quote', 'quote_id', $quoteProductRec['quote_id']);
        $companyRec = $fn->getRecordRowByID('company', 'company_id', $quoteRec['company_id']);
        /*
        $quoteProductChk = $fn->getRecordByCondition('quote_product',
                                                     "product_id = {$product_id} AND
                                                     quote_id = {$quoteRec['quote_id']}");
         */

        //to validate if the product is already added
        $SQLCheck  = "
        SELECT product_id
        FROM quote_product
        WHERE product_id = {$product_id}
        AND quote_id = {$quoteRec['quote_id']}
        ";

        $resultCheck = $db->sql_query($SQLCheck);
        $numRows     = $db->sql_numrows($resultCheck);
        $arr['msg'] = '';
        if($numRows > 1){
            $arr['msg'] = "Please note the product is already added";
            return $cpUtil->getJsonFromArray($arr);
            exit();
        }
        //APPLIED FOR GENERAL TRADING -------------------------------------------------
        //for general trading we need to get discount from the company
        $discountMainRec = $fn->getRecordRowByID('discount', 'company_id', $quoteRec['company_id']);
        //TO CHECK IF MARGIN RECORD IS PRESENT IN DISCOUNT TABLE
        if($discountMainRec['company_id'] > 0){
            $discountValue = 0;
            //TO CHECK IF CATEGORY RECORD IS PRESENT IN DISCOUNT TABLE, IF YES FOLLOWING CODE WILL BE EXECUTED
            if($row['category_id'] != '' || $row['category_id']  != NULL || $row['category_id']  > 0){
                    $discountRecCat = $fn->getRecordByCondition('discount',
                                                     "product_group_id = {$row['product_group_id']} AND company_id = {$quoteRec['company_id']} AND category_id = {$row['category_id']}");

                //Discount %
                if ($discountRecCat['discount_percent'] > 0 || $discountRecCat['discount_percent'] != NULL){
                    $discountValue       = $discountRecCat['discount_percent'];
                    $cost_price_discount =  ($row['price'] *  $discountRecCat['discount_percent'])/100;
                }

                //Mark up % from Discount table
                if ($discountRecCat['margin'] > 0 || $discountRecCat['margin'] != NULL    || $discountRecCat['margin'] != ''){
                    $marginValue = $discountRecCat['margin'];
                    //$cost_price_margin =  ($row['price'] *  $discountRecCat['margin'])/100;
                    $cost_price_margin   =  $discountRecCat['margin'];
                    $arr['margin']       =  $discountRecCat['margin'];
                } else {
                    $marginValue = 0;
                }
            } else {
                 //IF NO CATEGORY RECORD IN DISCOUNT TABLE, FOLLOWING CODE WILL BE EXECUTED
                $discountRec = $fn->getRecordByCondition('discount',
                                                    "product_group_id = {$row['product_group_id']} AND company_id = {$quoteRec['company_id']} AND category_id IS NULL");
                if($discountRec['discount_percent'] > 0 || $discountRec['discount_percent'] != NULL){
                    $discountValue       = $discountRec['discount_percent'];
                    $cost_price_discount =  ($row['price'] *  $discountRec['discount_percent'])/100;
                }
                else{
                    $discountValue = 0;
                }
                //To Set Margin from Discount table
                if($discountRec['margin'] == NULL || $discountRec['margin'] == ''){
                    $arr['margin'] = 0;
                    $marginValue = 0;
                }
                else{
                    $marginValue = $discountRec['margin'];
                    //$cost_price_margin = ($row['price'] *  $discountRec['margin'])/100;
                    $cost_price_margin   =  $discountRec['margin'];
                    $arr['margin']       =  $discountRec['margin'];
                }
            }
        }
        else{
            //GET MARGIN VALUE FROM COMPANY TABLE
            if($companyRec['mark_up_percentage'] > 0){
                $marginValue = $companyRec['mark_up_percentage'];
                //$cost_price_margin =  ($row['price'] *  $companyRec['mark_up_percentage'])/100;
                $cost_price_margin =  $companyRec['mark_up_percentage'];
                $arr['margin'] =  $companyRec['mark_up_percentage'];
                $discountValue = 0;
                $cost_price_discount = 0;
            }
            else{
                $arr['margin'] = 0;
                $discountValue = 0;
                $cost_price_discount = 0;
                $marginValue = 0;
            }
        }

        $selling_price = $cost_price_margin + $row['price'];
        $discount_type  = 'Value';

        if($row['price'] == ''){
            $row['price'] = 0;
        }
        if($row['price_from_supplier'] == ''){
            $row['price_from_supplier'] = 0;
        }

        if($row['title'] == '' || $row['title'] == NULL){
            $row['title'] = '';
        }

        if($row['part_number'] == '' || $row['part_number'] == NULL){
            $row['part_number'] = '';
        }
        $SQLUpdate    = "
        UPDATE quote_product
        set cost_price = {$row['price']}
        ,product_id = {$product_id}
        ,discount_percentage = '{$discountValue}'
        ,mark_up = {$marginValue}
        ,discount_type = '{$discount_type}'
        ,qty = 1
        ,selling_price = {$selling_price}
        ,price_from_supplier = {$row['price_from_supplier']}
        WHERE quote_product_id = {$rec_id}
        ";
        $resultUpdate = $db->sql_query($SQLUpdate);
        $selling_price = number_format($selling_price,2);

        $arr['price'] = $row['price'];
        $arr['title'] = $row['title'];
        $arr['partNumber'] = $row['part_number'];
        $arr['sellingPrice'] = $selling_price;
        $arr['discount']   = $cost_price_discount .'('. $discountValue .'%)';
        $arr['itemCode']   = $row['item_code'];
        $arr['unit']       = $row['unit'];

        return $cpUtil->getJsonFromArray($arr);
    }

    /**
     *
     */
    function getUpdateSellingLineItems() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpUtil = Zend_Registry::get('cpUtil');

        $rec_id     = $fn->getReqParam('rec_id');
        $qty        = $fn->getReqParam('qty');
        $cost_price = $fn->getReqParam('costPrice');
        $discount_percentage     = $fn->getReqParam('discount_percentage');
        $discount_type  = $fn->getReqParam('discount_type');
        $mark_up     = $fn->getReqParam('mark_up');
        $mark_up_type  = $fn->getReqParam('mark_up_type');
        $part_number  = $fn->getReqParam('partNumber');

        $selling_price_per_discount = '';
        $discount_value =  '';
        $mark_up_value  =  '';
        $mark_up_value_for_one_qty  =  '';
        $discount_value_for_one_qty  = '';
        //$arr = array('sellingPrice' => 0, 'profit' => 0);
        //to update quantity in quote product
        if($qty > 0){
            $SQLUpdate    = "
            UPDATE quote_product
            set qty = {$qty}
            WHERE quote_product_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($cost_price > 0){
            $SQLUpdate    = "
            UPDATE quote_product
            set cost_price = {$cost_price}
            WHERE quote_product_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($discount_percentage > 0){
            $SQLUpdate    = "
            UPDATE quote_product
            set discount_percentage = {$discount_percentage}
            WHERE quote_product_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($discount_type != ''){
            $SQLUpdate    = "
            UPDATE quote_product
            set discount_type = '{$discount_type}'
            WHERE quote_product_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($mark_up > 0){
            $SQLUpdate    = "
            UPDATE quote_product
            set mark_up = {$mark_up}
            WHERE quote_product_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($mark_up_type != ''){
            $SQLUpdate    = "
            UPDATE quote_product
            set mark_up_type = '{$mark_up_type}'
            WHERE quote_product_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        $SQL    = "
        SELECT cost_price
              ,selling_price
              ,qty
              ,quote_id
              ,product_id
              ,discount_percentage
              ,discount_type
              ,mark_up
              ,mark_up_type
        FROM quote_product
        WHERE quote_product_id = {$rec_id}
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        //to update quantity in po_product
        $expPoRec = array('condn' => " AND product_id = {$row['product_id']}");
        $poRec    = $fn->getRecordRowByID('po_product', 'quote_id', $row['quote_id'], $expPoRec);
        //to update records in purchase order
        if($poRec['purchase_order_id'] != ''){
            if($qty > 0){
                $SQLUpdate    = "
                UPDATE po_product
                set qty = {$qty}
                WHERE po_product_id = {$poRec['po_product_id']}
                ";
                $resultUpdate = $db->sql_query($SQLUpdate);
            }
            if($cost_price > 0){
                $SQLUpdate    = "
                UPDATE po_product
                set price = {$cost_price}
                WHERE po_product_id = {$poRec['po_product_id']}
                ";
                $resultUpdate = $db->sql_query($SQLUpdate);
            }
        }
        if($row['qty'] < 1){
            $qty = 1;
        } else {
            $qty = $row['qty'];
        }

        if($row['discount_percentage'] > 0){
            if($row['discount_type'] == '%'){
                $discount_value              = $row['discount_percentage'];
                $discount_value_for_one_qty  =  $row['cost_price'] * ($row['discount_percentage']/100);
            }
            else if($row['discount_type']  == 'Value'){
                $discount_value              = $row['discount_percentage'];
                $discount_value_for_one_qty  =  $row['discount_percentage'];
            }
        }

        if($row['mark_up'] > 0){
            if($row['mark_up_type'] == '%'){
                $mark_up_value              = $row['mark_up'];
                $mark_up_value_for_one_qty  =  $row['cost_price'] * ($row['mark_up']/100);
            }
            else if($row['mark_up_type']  == 'Value'){
                $mark_up_value              = $row['mark_up'];
                $mark_up_value_for_one_qty  =  $row['mark_up'];
            }
        }

        $selling_price = $row['cost_price'] + $mark_up_value_for_one_qty - $discount_value_for_one_qty;

        $SQLUpdate    = "
        UPDATE quote_product
        set selling_price = {$selling_price}
        WHERE quote_product_id = {$rec_id}
        ";
        //selling prioe need not be updated here.
        $resultUpdate = $db->sql_query($SQLUpdate);

        if($row['qty'] == 0){
            $qty = 1;
        } else {
            $qty = $row['qty'];
        }

        $totalSellingPrice  = round($selling_price * $qty,2);
        $totalCostPrice     = round($row['cost_price'] * $qty,2);

        //TO CHECK IF THE SUM OF DISCOUNT TYPE(%) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForPercentSum = "
        SELECT SUM(round(((qp.cost_price * qp.discount_percentage )/100)* qp.qty,2)) as discount_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$row['quote_id']}
            AND qp.discount_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForPercentSum = "
            SELECT SUM(round(((qp.cost_price * qp.discount_percentage )/100)* qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$row['quote_id']}
                AND qp.discount_type = '%'
            ";
        }
        else{
            $subSqlForPercentSum = 0;
        }


        //TO CHECK IF THE SUM OF DISCOUNT TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForValueSum ="
        SELECT SUM(round(qp.discount_percentage  * qp.qty,2)) as discount_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$row['quote_id']}
            AND qp.discount_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlForValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForValueSum ="
            SELECT SUM(round(qp.discount_percentage  * qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$row['quote_id']}
                AND qp.discount_type = 'Value'
            ";
        }
        else{
            $subSqlForValueSum = 0;
        }

        //TO CHECK IF THE SUM OF MARK UP TYPE(%) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForMarkUpPercentSum = "
        SELECT SUM(round(((qp.cost_price * qp.mark_up )/100)* qp.qty,2)) as mark_up_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$row['quote_id']}
            AND qp.mark_up_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForMarkUpPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['mark_up_sum'] > 0){
            $subSqlForMarkUpPercentSum = "
            SELECT SUM(round(((qp.cost_price * qp.mark_up )/100)* qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$row['quote_id']}
                AND qp.mark_up_type = '%'
            ";
        }
        else{
            $subSqlForMarkUpPercentSum = 0;
        }


        //TO CHECK IF THE SUM OF MARK UP TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForMarkUpValueSum ="
        SELECT SUM(round(qp.mark_up * qp.qty,2)) as mark_up_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$row['quote_id']}
            AND qp.mark_up_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlForMarkUpValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['mark_up_sum'] > 0){
            $subSqlForMarkUpValueSum ="
            SELECT SUM(round(qp.mark_up * qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$row['quote_id']}
                AND qp.mark_up_type = 'Value'
            ";
        }
        else{
            $subSqlForMarkUpValueSum = 0;
        }

        $SQL ="SELECT

              (SELECT SUM(round(qp.cost_price * qp.qty,2))
               FROM quote_product qp WHERE qp.quote_id = {$row['quote_id']}
               )
               AS total_cost_price_sum

               ,(SELECT
              ($subSqlForPercentSum)
               +
              ($subSqlForValueSum)
               )
               as discount_percentage_amount_sum

               ,(SELECT
              ($subSqlForMarkUpPercentSum)
               +
              ($subSqlForMarkUpValueSum)
               )
               as mark_up_amount_sum

              ,(SELECT SUM(round((qp.selling_price * qp.qty),2))
              FROM quote_product qp WHERE qp.quote_id = {$row['quote_id']}
              )
              AS total_selling_price_sum
        ";
        $resultUpdate = $db->sql_query($SQL);
        $row          = $db->sql_fetchrow($resultUpdate);

        $arr['total_cost_price_sum']           = $row['total_cost_price_sum'];
        $arr['discount_percentage_amount_sum'] = $row['discount_percentage_amount_sum'];
        $arr['mark_up_amount_sum'] = $row['mark_up_amount_sum'];
        $arr['total_selling_price_sum']        = $row['total_selling_price_sum'];

        $arr['mark_up_value']    = round($mark_up_value,2);
        $arr['discount_value']    = round($discount_value,2);
        $arr['selling_price']     = $selling_price;
        $arr['totalCostPrice']    = $totalCostPrice;
        $arr['totalSellingPrice'] = $totalSellingPrice;

        return $cpUtil->getJsonFromArray($arr);
    }

    /**
     *
     */
    function getTradingsgQuoteTradingsgProductLinkSQL($id) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $product_group_id   = $fn->getReqParam('product_group_id');

        $whereSQL = '';
        $discount = '';
        $discountSum ='';

        if ($product_group_id != "") {
            $whereSQL .= " AND pg.product_group_id = '{$product_group_id}'";
        }

        //TO CHECK IF THE SUM OF DISCOUNT TYPE(%) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForPercentSum = "
        SELECT SUM(round(((qp.cost_price * qp.discount_percentage )/100)* qp.qty,2)) as discount_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$id}
            AND qp.discount_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForPercentSum = "
            SELECT SUM(round(((qp.cost_price * qp.discount_percentage )/100)* qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$id}
                AND qp.discount_type = '%'
            ";
        }
        else{
            $subSqlForPercentSum = 0;
        }


        //TO CHECK IF THE SUM OF DISCOUNT TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForValueSum ="
        SELECT SUM(round(qp.discount_percentage  * qp.qty,2)) as discount_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$id}
            AND qp.discount_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlForValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForValueSum ="
            SELECT SUM(round(qp.discount_percentage  * qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$id}
                AND qp.discount_type = 'Value'
            ";
        }
        else{
            $subSqlForValueSum = 0;
        }

        //TO CHECK IF THE SUM OF MARK UP TYPE(%) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForMarkUpPercentSum = "
        SELECT SUM(round(((qp.cost_price * qp.mark_up )/100)* qp.qty,2)) as mark_up_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$id}
            AND qp.mark_up_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForMarkUpPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['mark_up_sum'] > 0){
            $subSqlForMarkUpPercentSum = "
            SELECT SUM(round(((qp.cost_price * qp.mark_up )/100)* qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$id}
                AND qp.mark_up_type = '%'
            ";
        }
        else{
            $subSqlForMarkUpPercentSum = 0;
        }


        //TO CHECK IF THE SUM OF MARK UP TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForMarkUpValueSum ="
        SELECT SUM(round(qp.mark_up * qp.qty,2)) as mark_up_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$id}
            AND qp.mark_up_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlForMarkUpValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['mark_up_sum'] > 0){
            $subSqlForMarkUpValueSum ="
            SELECT SUM(round(qp.mark_up * qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$id}
                AND qp.mark_up_type = 'Value'
            ";
        }
        else{
            $subSqlForMarkUpValueSum = 0;
        }

        $add = "(CONCAT_WS('', '<a href=\'index.php?module=tradingsg_quote&_spAction=addNoteQp&showHTML=0&id=', qp.quote_product_id, '\' class=\'addNoteQp\'>Add Notes</a>'))";
        $view = "(CONCAT_WS('', '<a href=\'index.php?module=tradingsg_quote&_spAction=addNoteQp&showHTML=0&id=', qp.quote_product_id, '\' class=\'addNoteQp\'>View Notes</a>'))";

        $SQL = "
        SELECT qp.quote_product_id
              ,p.title AS product_title
              ,p.part_number
              ,substr(pg.title,1,4) AS pg_title
              ,qp.client_id
              ,qp.cost_price
              ,p.unit
              ,qp.qty

              ,round(qp.cost_price * qp.qty,2)
              as total_cost_price

              ,qp.discount_type
              ,qp.discount_percentage as discount_percentage_amount

              ,qp.mark_up_type
              ,qp.mark_up as mark_up_amount

              ,round(selling_price,2)
              as selling_price_amount

              ,round(
              (qp.selling_price  * qp.qty) ,2)
              as total_selling_price

              ,qp.remarks
              ,qp.quote_product_id AS qo_po_id
              ,(SELECT SUM(round(qp.cost_price * qp.qty,2))
               FROM quote_product qp WHERE qp.quote_id = {$id})
               AS total_cost_price_sum

               ,(SELECT
              ($subSqlForPercentSum)
               +
              ($subSqlForValueSum)
               )
               as discount_percentage_amount_sum

               ,(SELECT
              ($subSqlForMarkUpPercentSum)
               +
              ($subSqlForMarkUpValueSum)
               )
               as mark_up_amount_sum

              ,(SELECT SUM(round(
              (qp.selling_price * qp.qty),2))

              FROM quote_product qp WHERE qp.quote_id = {$id}) as total_selling_price_sum

              ,IF(qp.notes <> '', $view, $add)

        FROM quote_product qp
        LEFT JOIN quote q ON (q.quote_id = qp.quote_id)
        LEFT JOIN product p ON (p.product_id = qp.product_id)
        LEFT JOIN product_group pg ON (p.product_group_id = pg.product_group_id)
        ,(SELECT @row := 0) r
        WHERE qp.quote_id = {$id}
              {$whereSQL}
        ";

        return $SQL;
    }

    /**
     *
     */
    function getFields(){
        $ln = Zend_Registry::get('ln');
        $cpCfg = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');
        $fn = Zend_Registry::get('fn');

        $fa = array();

        $fa = $fn->addToFieldsArray($fa, 'quote_code');
        $fa = $fn->addToFieldsArray($fa, 'title');
        $fa = $fn->addToFieldsArray($fa, 'company_id_customer');
        $fa = $fn->addToFieldsArray($fa, 'contact_id');
        $fa = $fn->addToFieldsArray($fa, 'status');
        $fa = $fn->addToFieldsArray($fa, 'priority');
        $fa = $fn->addToFieldsArray($fa, 'quote_date');
        $fa = $fn->addToFieldsArray($fa, 'follow_up_date');
        $fa = $fn->addToFieldsArray($fa, 'currency');
        $fa = $fn->addToFieldsArray($fa, 'note');
        $fa = $fn->addToFieldsArray($fa, 'delivery_terms');
        $fa = $fn->addToFieldsArray($fa, 'payment_terms');
        $fa = $fn->addToFieldsArray($fa, 'creation_date');
        $fa = $fn->addToFieldsArray($fa, 'tax_percentage');
        $fa = $fn->addToFieldsArray($fa, 'shipping_method');
        $fa = $fn->addToFieldsArray($fa, 'company_id');
        $fa = $fn->addToFieldsArray($fa, 'delivery_date');
        $fa = $fn->addToFieldsArray($fa, 'delivery_location');
        $fa = $fn->addToFieldsArray($fa, 'quote_type');
        $fa = $fn->addToFieldsArray($fa, 'cst');
        $fa = $fn->addToFieldsArray($fa, 'vat');

        return $fa;
    }

    /**
     *
     */
    function getRaisePurchaseOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $quote_id  = $fn->getReqParam('id');
        $this->getUpdatePriceFromSupplier($quote_id);
        //print 'aaaaaa';
        $SQL = "
        SELECT qp.*
              ,p.title AS product_title
              ,p.unit
              ,q.quote_code
              ,q.quote_date
              ,c.company_name
        FROM quote_product qp
        LEFT JOIN product p ON (p.product_id = qp.product_id)
        LEFT JOIN quote q ON (q.quote_id = qp.quote_id)
        LEFT JOIN company c ON (c.company_id = qp.client_id)
        WHERE qp.quote_id = {$quote_id}
          AND qp.client_id != ''
          AND qp.product_id > 0
        GROUP BY c.company_name
        ";
        $result = $db->sql_query($SQL);
        while ($row = $db->sql_fetchrow($result)) {
            //To check if the po is already created or not, if not create a purchase order
            $purchaseOrderRec = $fn->getRecordByCondition('purchase_order',
                                                      "company_id_supplier = '{$row['client_id']}' AND quote_id = {$quote_id}");

            if(is_array($purchaseOrderRec)){
                $purchase_order_id = $purchaseOrderRec['purchase_order_id'];
            } else {
                //Getting max code to create po
                $fa = array();
                $fa['quote_id'] = $quote_id;
                //$fa['status'] = 'inprogress';
                $fa['company_id_supplier'] = $row['client_id'];
                $fa['creation_date'] = date('Y-m-d');
                $fa['po_code'] = $this->getUpdatePOCode();

                $SQLInsert = $dbUtil->getInsertSQLStringFromArray($fa, 'purchase_order');
                $resultInsert = $db->sql_query($SQLInsert);
                $purchase_order_id = $db->sql_nextid();
            }

            //This sql is used to get the values from quote_product. Below code will create the record in
            //po and po_product history table .If the product record already exist it will not create.
            $SQLSelect = "
            SELECT qp.*
                  ,p.title AS product_title
                  ,p.unit
                  ,p.product_id
                  ,q.quote_code
                  ,q.quote_date
                  ,c.company_name
            FROM quote_product qp
            LEFT JOIN product p ON (p.product_id = qp.product_id)
            LEFT JOIN quote q ON (q.quote_id = qp.quote_id)
            LEFT JOIN company c ON (c.company_id = qp.client_id)
            WHERE qp.quote_id = {$quote_id}
              AND qp.client_id = {$row['client_id']}
              AND qp.product_id > 0
              ORDER BY qp.quote_product_id
            ";
            $resultSelect = $db->sql_query($SQLSelect);

            while ($rowPP = $db->sql_fetchrow($resultSelect)) {
                if($rowPP['price_from_supplier'] != 0){
                    $price = $rowPP['price_from_supplier'];
                } else {
                    $price = $rowPP['cost_price'];
                }
                $fa1 = array();
                $fa1['product_id'] = $rowPP['product_id'];
                $fa1['price']      = $price;
                $fa1['qty']        = $rowPP['qty'];
                $fa1['quote_id']   = $quote_id;
                $fa1['status']     = 'print';
                $fa1['supplier_id']= $row['client_id'];
                $fa1['creation_date'] = date('Y-m-d');
                $fa1['purchase_order_id'] = $purchase_order_id;
                //Checking if the product exists in po product
                /* OPTION 1 */
                $poProductRec = $fn->getRecordByCondition('po_product',
                                                          "product_id = '{$rowPP['product_id']}' AND supplier_id = {$row['client_id']} AND quote_id = {$quote_id}");
                if(is_array($poProductRec)){
                    $whereCondition = "WHERE po_product_id = {$poProductRec['po_product_id']}";
                    $sqlPoUpdate = $dbUtil->getUpdateSQLStringFromArray($fa1, "po_product", $whereCondition);
                    $resultPoUpdate      = $db->sql_query($sqlPoUpdate);
                } else {
                    $SQLPo = $dbUtil->getInsertSQLStringFromArray($fa1, 'po_product');
                    $resultPo = $db->sql_query($SQLPo);
                }
                $deleteSql1 = "
                DELETE FROM po_product
                WHERE quote_id = {$quote_id}
                     AND product_id = {$rowPP['product_id']}
                     AND supplier_id != {$row['client_id']}
                ";
                $resultDelete = $db->sql_query($deleteSql1);

            }
            //DELETE THE PRODUCT RECORDS FROM po_product WHICH DO NOT EXIST IN quote_product

            $deleteSql = "
            DELETE FROM po_product
            WHERE quote_id = {$quote_id} AND product_id NOT IN
            (SELECT product_id FROM quote_product WHERE quote_id = {$quote_id} AND product_id > 0)
            ";
            $resultDelete = $db->sql_query($deleteSql);

            //DELETE THE PRODUCT RECORDS FROM po_product WHICH DO NOT EXIST IN quote_product

            $deleteSql = "
            DELETE FROM purchase_order
            WHERE quote_id = {$quote_id} AND company_id_supplier NOT IN
            (SELECT client_id FROM quote_product WHERE quote_id = {$quote_id} AND product_id > 0)
            ";
            $resultDelete = $db->sql_query($deleteSql);
        }
    }

    /**
     *
     */
    function getAdd(){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $validate = Zend_Registry::get('validate');

        if (!$this->getNewValidate()){
            return $validate->getErrorMessageXML();
        }

        if ($cpCfg['countryForCurrency'] == 'India'){
            $currency = 'INR';
        } else if ($cpCfg['countryForCurrency'] == 'Singapore'){
            $currency = 'SGD';
        } else {
            $currency = '';
        }

        $SQL = "SELECT max(quote_code) AS quote_code FROM quote";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

		$paymentTerms = nl2br("CST/VAT Applicable \nP & F - 3% \n50% against PO and 50% against Proforma Invoice prior to Dispatch \nOffer valid from 1 month from the Quote date", false);
		$sentence = str_replace('<br>', ' ', $paymentTerms);

        $fa = $this->getFields();
        $fa['quote_code']       = $this->getUpdateQuoteCode();
        $fa['staff_id']         = $_SESSION['staff_id'];
        $fa['status']           = 'New';
        $fa['priority']         = 'Medium';
        $fa['follow_up_date']   = date("Y-m-d", strtotime('+1 week'));
        $fa['currency']         = $currency;
        $fa['quote_date']       = date("Y-m-d");
        $fa['note']             = '';
        $fa['delivery_terms']   = '4 - 6 weeks from the receipt of PO';
        $fa['payment_terms']    = $sentence;

        $id = $fn->addRecord($fa);

        $fn->returnAfterNewSave($id);
    }

    /**
     *
     */
    function setSearchVar($linkRecType = '') {
        $tv = Zend_Registry::get('tv');
        $searchVar = Zend_Registry::get('searchVar');
        $fn = Zend_Registry::get('fn');
        $searchVar->mainTableAlias = 'q';

        $status 	   = $fn->getReqParam('status');
        $priority 	   = $fn->getReqParam('priority');
        $company_id    = $fn->getReqParam('company_id');
        $quoteDate1  = $fn->getReqParam('quoteDate1');
        $quoteDate2  = $fn->getReqParam('quoteDate2');

        if ($tv['record_id'] != '') {
            $searchVar->sqlSearchVar[] = "q.quote_id = {$tv['record_id']}";
        } else {


            if ($_SESSION['userGroupType'] == "User") {
                $searchVar->sqlSearchVar[] = "(pgs.staff_id = {$_SESSION['staff_id']})
               ";
            }

            if ($status != "") {
                $searchVar->sqlSearchVar[] = "q.status = '{$status}'";
            }

            if ($quoteDate1 != "" && $quoteDate1 != "From"
            && $quoteDate2 != "" && $quoteDate2 != "To" ) {
                $searchVar->sqlSearchVar[] = "(q.quote_date BETWEEN '{$quoteDate1}' AND '{$quoteDate2}')";
            }

            if ($quoteDate1 != "" && $quoteDate1 != "From" && $quoteDate2 == "To") {
                $searchVar->sqlSearchVar[] = "(q.quote_date >= '{$quoteDate1}')";
            }


            if ($quoteDate2 != "" && ($quoteDate1 == "From"
            || $quoteDate1 == "") && $quoteDate2 != "To") {
                $searchVar->sqlSearchVar[] = "(q.quote_date <= '{$quoteDate2}')";
            }

            if ($priority != "") {
                $searchVar->sqlSearchVar[] = "q.priority = '{$priority}'";
            }
            if ($company_id != "") {
                $searchVar->sqlSearchVar[] = "q.company_id = '{$company_id}'";
            }
            if ($tv['special_search'] == "Flagged") {
                $searchVar->sqlSearchVar[] = "q.flag = 1";
            }
            if ($tv['special_search'] == "Not-Flagged") {
                $searchVar->sqlSearchVar[] = "(q.flag != 1 OR q.flag IS null)";
            }
            if ($tv['keyword'] != "") {
                $searchVar->sqlSearchVar[] = "(
                       q.quote_code LIKE '%{$tv['keyword']}%'
                    OR co.company_name LIKE '%{$tv['keyword']}%'
                )";
            }
        }
        $searchVar->sortOrder = "q.creation_date DESC";

    }
    /**
     *
     */
    function getPrintExportAsPdf() {
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
        //$pdf = new PDF_HTML();
        $pdf = new PDF_MC_Table();

		$pdf->AddPage();
		$pdf->SetFont('Arial','',11);

        $quote_id = $fn->getReqParam('id');

        $subSqlForPercentSum = "
        SELECT SUM(round(((qp.cost_price * qp.discount_percentage )/100)* qp.qty,2)) as discount_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$quote_id}
            AND qp.discount_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForPercentSum = "
            SELECT SUM(round(((qp.cost_price * qp.discount_percentage )/100)* qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$quote_id}
                AND qp.discount_type = '%'
            ";
        }
        else{
            $subSqlForPercentSum = 0;
        }


        //TO CHECK IF THE SUM OF DISCOUNT TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForValueSum ="
        SELECT SUM(round(qp.discount_percentage  * qp.qty,2)) as discount_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$quote_id}
            AND qp.discount_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlForValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlForValueSum ="
            SELECT SUM(round(qp.discount_percentage  * qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$quote_id}
                AND qp.discount_type = 'Value'
            ";
        }
        else{
            $subSqlForValueSum = 0;
        }

        $SQL = "
        SELECT qp.*
              ,p.title AS product_title
              ,p.description_short
              ,p.unit
              ,p.item_code
              ,p.part_number
              ,q.quote_code
              ,q.payment_terms
              ,q.delivery_terms
              ,q.note
              ,q.currency
              ,q.quote_date
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
              ,c.customer_type
              ,qp.selling_price
              ,qp.cost_price
              ,qp.notes
              ,(SELECT
              ($subSqlForPercentSum)
               +
              ($subSqlForValueSum)) as discount_percentage_amount_sum
              ,(SELECT SUM(qph.qty * qph.cost_price) FROM  quote_product qph
               WHERE qph.quote_id = qp.quote_id) AS sub_total
              ,(SELECT SUM(qph.qty * qph.selling_price) FROM  quote_product qph
               WHERE qph.quote_id = qp.quote_id) AS total
              ,CONCAT_WS(' ', co.first_name, co.last_name) AS contact_name
        FROM quote_product qp
        LEFT JOIN product p ON (p.product_id = qp.product_id)
        LEFT JOIN quote q ON (q.quote_id = qp.quote_id)
        LEFT JOIN company c ON (c.company_id = q.company_id)
        LEFT JOIN contact co ON (q.contact_id = co.contact_id)
        WHERE q.quote_id = {$quote_id} AND qp.product_id > 0
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
		$totaltsp = '';
		$selling_price = '';

        //============================================================================= //
        $pdf->SetFont('Courier','',11);
        //syed:multi text code to set width of each column and alignment
        $pdf->SetWidths(array(10, 60, 35, 10, 12, 31, 31));
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
                $pdf->SetXY(90, 35);
                $pdf->Cell(21, 20, "QUOTATION", 0, 0, 'C');
                $pdf->Ln(15);

			    $quoteCode = $row['quote_code'];
				$formatedQC = explode("-", $quoteCode);

				$billingAddressFlat = '';
				$billingAddressStreet = '';
				$billingAddressTown = '';
				$billingAddressState = '';
				$billingAddressCountry = '';

				if ($row['billing_address_flat'] != ''
				 || $row['billing_address_street'] != ''
				 || $row['billing_address_town'] != ''
				 || $row['billing_address_state'] != ''
				 || $row['billing_address_country'] != '')
			    {
					$billingAddressFlat     = $row['billing_address_flat'];
					$billingAddressStreet   = $row['billing_address_street'];
					$billingAddressTown     = $row['billing_address_town'];
					$billingAddressState    = $row['billing_address_state'];
					$billingAddressCountry  = $row['billing_address_country'];
			    } else {
					$billingAddressFlat     = $row['address_flat'];
					$billingAddressStreet   = $row['address_street'];
					$billingAddressTown     = $row['address_town'];
					$billingAddressState    = $row['address_state'];
					$billingAddressCountry  = $row['address_country'];
				}

                $contact_name = '';
                if($row['contact_name'] != ''){
                    $contact_name = "Kind Attn: Mr {$row['contact_name']}";
                }

                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(95,8,"TO",1,0, 'L', 1);
                $pdf->Cell(95,8,"",1,0, 'L', 1);
                $pdf->Ln();
                $pdf->SetFillColor(255,255,255);
                $pdf->SetFont('Courier','B',11);
                $pdf->Cell(95, 8, $row['company_name'], 'LR', 0, 'L', 1);
                $pdf->SetFont('Courier','B',11);
                $pdf->Cell(95, 8, "QUOTE CODE : {$quoteCode}", 'LR', 0, 'L', 1);
                $pdf->Ln();
                $pdf->Cell(95, 12, "$contact_name", 'LR', 0, 'L', 1);
	            $pdf->Cell(95, 12, "DATE : {$fn->getCPDate($row['quote_date'], 'd-m-Y')}", 'LR', 0, 'L', 1);
                $pdf->Ln();
	            $pdf->Cell(95, 5, $billingAddressFlat, 'LR', 0, 'L', 1);
	            $pdf->Cell(95, 5, '', 'LR', 0, 'L', 1);
                $pdf->Ln();
	            $pdf->Cell(95, 5, $billingAddressStreet, 'LR', 0, 'L', 1);
	            $pdf->Cell(95, 5, '', 'LR', 0, 'L', 1);
                $pdf->Ln();
	            $pdf->Cell(95, 5, $billingAddressTown, 'LR', 0, 'L', 1);
	            $pdf->Cell(95, 5, '', 'LR', 0, 'L', 1);
                $pdf->Ln();
	            $pdf->Cell(95, 5, $billingAddressCountry . ' - ' . $billingAddressState, 'BLR', 0, 'L', 1);
	            $pdf->Cell(95, 5, '', 'BLR', 0, 'L', 1);
                $pdf->Ln();
                //$pdf->MultiCell(190,5,$row['company_name'] ."\n". $row['address_flat'] ."\n". $row['address_street'] ."\n". $row['address_town'] ."\n". $row['address_country']  ." - ". $row['address_state'],1,'L');
                $pdf->Ln(4);

                /* List of order items header */
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(10,8,"S.NO",1,0, 'C', 1);
                $pdf->Cell(60,8,"NAME OF THE ITEM",1,0, 'C', 1);
                $pdf->Cell(35,8,"PART No",1,0, 'C', 1);
                $pdf->Cell(10,8,"QTY",1,0, 'C', 1);
                $pdf->Cell(12,8,"UOM",1,0, 'C', 1);
                $pdf->Cell(31,8,"SP",1,0, 'C', 1);
                $pdf->Cell(31,8,"TOTAL(" . $row['currency'] . ")",1,0, 'C', 1);
                $pdf->Ln();
                $x=$pdf->GetX()+ 10;
                $y=$pdf->GetY();

                $height=10;
                $leftmargin=92;
            }

            //===================================MAIN TABLE============================= //

            if($row['mark_up_type'] == '%'){
                $selling_price = $row['cost_price'] + ($row['cost_price'] * ($row['mark_up']/100));
            }
            else if($row['mark_up_type'] == 'Value'){
                $selling_price = $row['cost_price']  + $row['mark_up'];
            } else {
				$selling_price = $row['cost_price'];
			}

			$tsp = $row['qty'] * $selling_price;
			$tsp = number_format($tsp,2);
			$titledesc = $row['product_title'];
			if($row['description_short'] != ''){
			    $titledesc = $titledesc . ' : ' . $row['description_short'];
			}
			if($row['notes'] != ''){
			    $titledesc = $titledesc . ' : ' . $row['notes'];
			}
			$titledescrip = $titledesc;

            $pdf->SetFont('Courier','B',11);
            $pdf->SetFillColor(255,255,255);
            //code to match values in the table for each column
            $pdf->Row(array($lineItemNumber, $titledescrip , $row['part_number'], $row['qty'], $row['unit'],number_format($selling_price,2), $tsp));

            $count++;
            $lineItemNumber++;

			$total = $row['total'];
            $discount = $row['discount_percentage_amount_sum'];
            $notes = $row['note'];
            $terms = $row['payment_terms'];
            $delivery_terms = $row['delivery_terms'];
			$sub_total = $total + $discount;
        }

			$totaldiscount = $sub_total - $discount;
			$discountPercent = $discount * 100 / $sub_total;
            $totaldiscount = number_format($totaldiscount,2);
            $sub_total = number_format($sub_total,2);
            $discount = number_format($discount,2);
            $discountPercent = number_format($discountPercent,2);


			if ($discount <= 0){
	            $pdf->SetFont('Courier','B',11);
	            $pdf->Cell(158,8,"TOTAL",1,0, 'R', 1);
	            $pdf->SetFont('Courier','B',11);
	            $pdf->Cell(31,8,$totaldiscount,1,0, 'R', 1);
				$pdf->Ln(12);
			} else {
	            $pdf->SetFont('Courier','B',11);
	            $pdf->Cell(158,8,"SUB-TOTAL",1,0, 'R', 1);
	            $pdf->SetFont('Courier','B',11);
	            $pdf->Cell(31,8,$sub_total,1,0, 'R', 1);
				$pdf->Ln();
	            $pdf->SetFont('Courier','B',11);
	            $pdf->Cell(158,8,"LESS : DISCOUNT (" . $discountPercent . "%)",1,0, 'R', 1);
	            $pdf->SetFont('Courier','B',11);
	            $pdf->Cell(31,8,$discount,1,0, 'R', 1);
				$pdf->Ln();
	            $pdf->SetFont('Courier','B',11);
	            $pdf->Cell(158,8,"TOTAL",1,0, 'R', 1);
	            $pdf->SetFont('Courier','B',11);
	            $pdf->Cell(31,8,$totaldiscount,1,0, 'R', 1);
				$pdf->Ln(12);
			}

            if($terms){
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(190,8,"Payment Terms", 1,0, 'L', 1);
                $pdf->Ln(10);
                $pdf->SetFillColor(255,255,255);
                $pdf->SetFont('Courier','B',11);
                //$pdf->Cell(190, 8, $terms, 1, 0, 'L', 1);
                $pdf->drawTextBox($terms, 180, 55, 'L', 'T', 0);
                $pdf->Ln(10);
            }

            if($delivery_terms){
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(190,8,"Delivery Terms",1,0, 'L', 1);
                $pdf->Ln(10);
                $pdf->drawTextBox($delivery_terms, 180, 55, 'L', 'T', 0);
                $pdf->Ln(10);
            }

            if($notes){
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(190, 8, 'NOTE: ', 1, 0, 'L', 1);
                $pdf->Ln(10);

                $pdf->SetFont('Courier','B',11);
                //$pdf->Cell(900, 8, $notes);
                $pdf->drawTextBox($notes, 180, 55, 'L', 'T', 0);
            }

            $pdf->Cell(55, 5, $cpCfg['printBestRegards']);
	        $pdf->SetX(10);
            $pdf->Cell(55, 16, $cpCfg['printEngexPower']);

            /* Creation of media record of the invoice */
            /*
	        $file_name = 'Refund_REF_' . date('Y-m-d') .'.pdf';
	        $outputPath = realpath($cpCfg['cp.mediaFolder']) . '/temp';

	        $outputFileName = $outputPath . '/' . $file_name;
            */
	        //$pdf->Output($outputFileName , "F");
			$pdf->Output();

    }

    /**
     *
     */
    function getUpdatePriceFromSupplier($quote_id) {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $SQL = "
        SELECT qp.*
        FROM quote_product qp
        WHERE qp.quote_id = {$quote_id}
        ";
        $result = $db->sql_query($SQL);
        while ($row = $db->sql_fetchrow($result)) {
            $mark_up_for_one_qty  ='';
            $discount_value_for_one_qty  ='';

            $productRec = $fn->getRecordByCondition('product',
                                                      "product_id = '{$row['product_id']}'");
            $UpdateSQL    = "UPDATE quote_product SET price_from_supplier = {$productRec['price_from_supplier']}
            WHERE quote_product_id = {$row['quote_product_id']}";
            $resultUpdate = $db->sql_query($UpdateSQL);
        }
    }
    /**
     *
     */
    function getRaiseInvoice() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');

        $quote_id  = $fn->getReqParam('id');
        $discountRecCat = '';

        $fa = array();
        $fa['quote_id'] = $quote_id;
        $fa['order_date'] = date('Y-m-d');
        $fa['creation_date'] = date('Y-m-d');

        $quoteRow = $fn->getRecordRowByID('quote', 'quote_id', $quote_id);
        $fa['company_id'] = $quoteRow['company_id'];

        $orderRec = $fn->getRecordByCondition('order', "quote_id = '{$quote_id}'");

        //check if the order record already exist or not
        if(is_array($orderRec)){
            $order_id = $orderRec['order_id'];
        } else {
            $SQLInsert = $dbUtil->getInsertSQLStringFromArray($fa, 'order');
            $resultInsert = $db->sql_query($SQLInsert);
            $order_id = $db->sql_nextid();
        }

        $SQLSelect = "
        SELECT qp.*
              ,p.title AS product_title
              ,p.part_number
              ,p.price_from_supplier
              ,q.quote_date
        FROM quote_product qp
        LEFT JOIN product p ON (p.product_id = qp.product_id)
        LEFT JOIN quote q ON (q.quote_id = qp.quote_id)
        WHERE q.quote_id = {$quote_id}
        AND qp.product_id > 0
        ORDER BY qp.quote_product_id
        ";
        $resultSelect = $db->sql_query($SQLSelect);

        while ($row = $db->sql_fetchrow($resultSelect)) {
            $fa1 = array();
            $fa1['record_id']           = $row['product_id'];
            $fa1['unit_price']          = $row['selling_price'];
            $fa1['cost_price']          = $row['cost_price'];
            $fa1['qty']                 = $row['qty'];
            $fa1['order_id']            = $order_id;
            $fa1['supplier_id']         = $row['client_id'];
            $fa1['item_title']          = $row['product_title'];
            $fa1['discount_percentage'] = $row['discount_percentage'];
            $fa1['mark_up']             = $row['mark_up'];
            $fa1['price_from_supplier'] = $row['price_from_supplier'];
            $fa1['part_number']         = $row['part_number'];

            $orderItemRec = $fn->getRecordByCondition('order_item',
                                                      "record_id = '{$row['product_id']}' AND order_id = {$order_id}");

            if(is_array($orderItemRec)){
                $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
                $sqlOiUpdate = $dbUtil->getUpdateSQLStringFromArray($fa1, "order_item", $whereCondition);
                $resultOiUpdate      = $db->sql_query($sqlOiUpdate);
            } else {
                $SQLOI = $dbUtil->getInsertSQLStringFromArray($fa1, 'order_item');
                $resultOI = $db->sql_query($SQLOI);
            }

            $productRow = $fn->getRecordRowByID('product', 'product_id', $row['product_id']);
            if($productRow['category_id']){
                $discountRecCat = $fn->getRecordByCondition('discount',
                                                         "product_group_id = {$productRow['product_group_id']} AND company_id = {$quoteRow['company_id']} AND category_id = {$productRow['category_id']}");
            }

            $discountRec = $fn->getRecordByCondition('discount',
                                                    "product_group_id = {$productRow['product_group_id']} AND company_id = {$quoteRow['company_id']} AND category_id IS NULL");

            $fa2 = array();
            if (is_array($discountRecCat)){
                $fa2['product_group_id']   = $discountRecCat['product_group_id'];
                $fa2['order_id']           = $order_id;
                $fa2['company_id']         = $discountRecCat['company_id'];
                $fa2['category_id']        = $discountRecCat['category_id'];
                $fa2['discount_percent']   = $discountRecCat['discount_percent'];
                $fa2['margin']             = $discountRecCat['margin'];
                $fa2['customer_type']      = $discountRecCat['customer_type'];

                $discountFinanceRec = $fn->getRecordByCondition('discount_finance',
                                                          "product_group_id = '{$productRow['product_group_id']}' AND order_id = {$order_id} AND category_id = {$productRow['category_id']}");

                if(is_array($discountFinanceRec)){
                } else {
                    $SQLDF = $dbUtil->getInsertSQLStringFromArray($fa2, 'discount_finance');
                    $resultDF = $db->sql_query($SQLDF);
                }
            } else {
                $fa2['product_group_id']   = $discountRec['product_group_id'];
                $fa2['order_id']           = $order_id;
                $fa2['company_id']         = $discountRec['company_id'];
                $fa2['discount_percent']   = $discountRec['discount_percent'];
                $fa2['margin']             = $discountRec['margin'];
                $fa2['customer_type']      = $discountRec['customer_type'];

                $discountFinanceRec = $fn->getRecordByCondition('discount_finance',
                                                          "product_group_id = '{$productRow['product_group_id']}' AND order_id = {$order_id} AND category_id IS NULL");

                if(is_array($discountFinanceRec)){
                } else {
                    $SQLDF = $dbUtil->getInsertSQLStringFromArray($fa2, 'discount_finance');
                    $resultDF = $db->sql_query($SQLDF);
                }
            }

        }
        //DELETE THE PRODUCT RECORDS FROM order_item WHICH DO NOT EXIST IN quote_product

        $deleteSql = "
        DELETE FROM order_item
        WHERE order_id = {$order_id} AND record_id NOT IN
        (SELECT product_id FROM quote_product
        WHERE quote_id = {$quote_id} AND product_id > 0)
        ";
        $resultDelete = $db->sql_query($deleteSql);
    }

    /**
     *
     */
    function getQuickAddSubmit() {
        checkLoggedIn();
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpUrl = Zend_Registry::get('cpUrl');
        $ln = Zend_Registry::get('ln');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $validate = Zend_Registry::get('validate');

        /*if (!$this->getQuickAddSubmitValidate()){
            return $validate->getErrorMessageXML();
        }*/

        $unit 				= $fn->getPostParam('unit');
        $part_number 		= $fn->getPostParam('part_number');
        $product_title 		= $fn->getPostParam('title');
        $product_group_id 	= $fn->getPostParam('product_group_id');
        $price 				= $fn->getPostParam('price');

        $fa = array();
        $fa['unit'] 			= $unit;
        $fa['title']         	= $product_title;
        $fa['part_number'] 		= $part_number;
	    $fa['product_group_id'] = $product_group_id;
        $fa['price'] 		    = $price;
        $fa['item_code']        = $this->getUpdateProductCode();
	    $fa['published']        = 1;

        $insert = $dbUtil->getInsertSQLStringFromArray($fa, 'product');
        $result = $db->sql_query($insert);
        $id     = $db->sql_nextid();

        return $validate->getSuccessMessageXML();

    }

    /**
     *
     */
    function getQuickAddSubmitValidate() {
        $ln = Zend_Registry::get('ln');
        $validate = Zend_Registry::get('validate');

        //==================================================================//
        $validate->resetErrorArray();
        $validate->validateData('part_number', $ln->gd('Please enter the part number'));

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getUpdateProductCode() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        /* Updation of Product Code */
        $nextProductItemCode = $fn->getSettingsValueByKey("nextProductItemCode");

        if($nextProductItemCode < 10){
            $ProCode = $fn->getSettingsValueByKey('productCodePrefix') . '000' . $nextProductItemCode;
        }
        else if($nextProductItemCode < 99){
            $ProCode = $fn->getSettingsValueByKey('productCodePrefix') . '00' . $nextProductItemCode;
        }
        else if($nextProductItemCode < 999){
            $ProCode = $fn->getSettingsValueByKey('productCodePrefix') . '0' . $nextProductItemCode;
        }
        else{
            $ProCode = $fn->getSettingsValueByKey('productCodePrefix') . $nextProductItemCode;
        }

        $SQL    = "UPDATE setting SET value = (value+1) WHERE key_text = 'nextProductItemCode'";
        $result = $db->sql_query($SQL);

        return $ProCode;
    }

    /**
     *
     */
    function getAddNoteFormSubmit() {
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $quote_product_id  = $fn->getPostParam('quote_product_id');
        $notes         = $fn->getPostParam('notes');

        if (!$this->getAddNoteFormValidate()){
            return $validate->getErrorMessageXML();
        }

        $fa = array();
        $fa['notes']     = $notes;

        $SQLUpdate    = "
        UPDATE quote_product
        set notes = '{$notes}'
        WHERE quote_product_id = {$quote_product_id}
        ";
        $resultUpdate = $db->sql_query($SQLUpdate);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getAddNoteFormValidate() {
        $ln = Zend_Registry::get('ln');
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }
}