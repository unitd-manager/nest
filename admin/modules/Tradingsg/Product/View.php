<?
class CPL_Admin_Modules_Tradingsg_Product_View extends CP_Admin_Modules_Tradingsg_Product_View
{

    //==================================================================//
    function getList($dataArray) {
        $listObj = Zend_Registry::get('listObj');
        $db = Zend_Registry::get('db');
        $pager = Zend_Registry::get('pager');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');
        $modulesArr = Zend_Registry::get('modulesArr');
        $mediaArray = Zend_Registry::get('mediaArray');

        $text = '';
        $rows = '';
        $rowCounter = 0;

        foreach ($dataArray as $row){
            $sortOrder = '';

            if($cpCfg['m.ecommerce.product.hasSortOrderFld']){
                $sortOrder = $listObj->getListSortOrderField($row, 'product_id');
            }

            $extaFlds = '';
            if (!$cpCfg['m.ecommerce.product.hasProductItem']){
                $extaFlds = "
                {$listObj->getListDataCell($row['qty_in_stock'])}
                ";
            }

            $productCodeTd = $listObj->getListDataCell($row['item_code'], 'center');
            if ($cpCfg['cp.serialKeyActive'] == "SULB-DHEO-0R6K-59CL" || $cpCfg['cp.serialKeyActive'] == "YODX0-9DT58-VCZ5W-A8XXB") {
                $productCodeTd = $listObj->getListDataCell($row['product_code'], 'center');
            }

            $rows .= "
            {$listObj->getListRowHeader($row, $rowCounter)}
            {$productCodeTd}
            {$listObj->getListDataCell($row['tag_no'], 'center')}
            {$listObj->getListDataCell($row['category_title'])}
            {$listObj->getListDataCell($row['sub_category_title'])}
            {$listObj->getGoToDetailText($rowCounter, $row['title'])}
            {$listObj->getListDataCell($row['part_number'])}
            {$listObj->getListDataCell($row['price'])}
            {$listObj->getListDataCell($row['unit'])}
            {$sortOrder}
            {$listObj->getListDataCell($row['company_records'])}
            {$listObj->getListDataCell($row['modified_by'] . ' ' . $row['modification_date'])}
            {$listObj->getListPublishedImage($row['published'], $row['product_id'])}
            {$listObj->getListRowEnd($row['product_id'])}
            ";
            $rowCounter++;
        }


        $sortOrder = '';
        if($cpCfg['m.ecommerce.product.hasSortOrderFld']){
            $sortOrder = $listObj->getListSortOrderImage('p');
        }

        $extaFlds = '';
        if (!$cpCfg['m.ecommerce.product.hasProductItem']){
            $extaFlds = "
            {$listObj->getListHeaderCell('Stock', 'p.qty_in_stock')}
            ";
        }

        $productCodeTh = $listObj->getListHeaderCell('Item Code', 'item_code' , 'headerCenter');
        if ($cpCfg['cp.serialKeyActive'] == "SULB-DHEO-0R6K-59CL" || $cpCfg['cp.serialKeyActive'] == "YODX0-9DT58-VCZ5W-A8XXB") {
            $productCodeTh = $listObj->getListHeaderCell('Product Code', 'product_code' , 'headerCenter');
        }

        $text = "
        {$listObj->getListHeader()}
        {$productCodeTh}
        {$listObj->getListHeaderCell('Tag No', 'p.tag_no', 'headerCenter')}
        {$listObj->getListHeaderCell('Category', 'c.title')}
        {$listObj->getListHeaderCell('Sub Category', 'sc.title')}
        {$listObj->getListHeaderCell('Product Name', 'p.title')}
        {$listObj->getListHeaderCell('Part Number', 'p.part_number')}
        {$listObj->getListHeaderCell('List Price', 'p.price')}
        {$listObj->getListHeaderCell('Unit', 'p.unit')}
        {$sortOrder}
        {$listObj->getListHeaderCell('Supplier', 'co.company_name')}
        {$listObj->getListHeaderCell('Updated By', 'p.modified_by')}
        {$listObj->getListHeaderCell('Published', 'p.published', 'headerCenter')}
        {$listObj->getListHeaderEnd()}
        {$rows}
        {$listObj->getListFooter()}
        ";

        return $text;
    }

    /**
     *
     */
    function getUpdateProductCompany() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $company_id = 98;

        $SQLProduct = "SELECT product_id FROM product WHERE member_only = 1";
        $result = $db->sql_query($SQLProduct);

        while ($row = $db->sql_fetchrow($result)) {
            $fa1 = array();
            $fa1['item_code'] = $this->getUpdateProductCode();

            $whereCondition = "
            WHERE product_id = {$row['product_id']} AND item_code IS NULL
            ";
            $SQLProduct = $dbUtil->getUpdateSQLStringFromArray($fa1, 'product', $whereCondition);
            $db->sql_query($SQLProduct);

            $recCount = $fn->getRecordCount('product_company', "company_id = '{$company_id}' AND product_id = {$row['product_id']}");
            if (is_numeric ($company_id) && $recCount == 0) {
                $fa2 = array();
                $fa2['company_id'] = $company_id;
                $fa2['product_id']  = $row['product_id'];
                $fa2 = $fn->addCreationDetailsToFieldsArray($fa2, 'product_company');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa2, 'product_company');
                $result1 = $db->sql_query($SQL);
            }
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
    function getEdit($row) {
        $db = Zend_Registry::get('db');
        $ln = Zend_Registry::get('ln');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $am = Zend_Registry::get('am');
        $ln = Zend_Registry::get('ln');
        $formObj = Zend_Registry::get('formObj');
        $cpUtil = Zend_Registry::get('cpUtil');

        $formObj->mode = $tv['action'];
        $sqlUnit = $fn->getValueListSQL('productUnit', 'value');
        $expVl = array('sqlType' => 'OneField');
        $expNoEdit  = array('isEditable' => 0);
        $sqlDiscountType = array("%", "Value");
        $expVlDisc   = array('sqlType' => 'OneField', 'firstOptionLabel' => 'No Discount');

        $latest = '';
        if ($cpCfg['m.ecommerce.product.showLatest'] == 1) {
            $latest = $formObj->getYesNoRRow("Latest", "latest", $row['latest']);
        }

        $favourite = '';
        if ($cpCfg['m.ecommerce.product.showFavourite'] == 1) {
            $favourite = $formObj->getYesNoRRow("Favourite", "favourite", $row['favourite']);
        }

        $priceText = '';
        if ($cpCfg['m.ecommerce.product.isCountryBased'] == 0  ) {
            if  ($cpCfg['cp.restaurent'] == 0) {
            $priceText = $formObj->getTBRow('<b>List Price</b>', 'price', $row['price'],$expNoEdit);
            }else{
                $priceText = $formObj->getTBRow('<b>List Price</b>', 'price', $row['price']);
    
            }
    
        }
        $embedCode = '';
        if ($cpCfg['m.ecommerce.product.showEmbedCode'] == 1) {
            $embedCode = $formObj->getTARow('Embed Code', 'embed_code', $ln->gfv($row, 'embed_code', '0'));
        }

        $weight = '';
        if ($cpCfg['m.ecommerce.product.showWeight']) {
            $weight = $formObj->getTBRow("Shipping Weight in Grams", "weight_grams", $row['weight_grams']);
        }

        if ($_SESSION['userGroupType'] == 'Super Administrator' || $_SESSION['userGroupType'] == 'Administrator' ) {
            $sqlProductGroup = "
            SELECT product_group_id
                  ,title
            FROM product_group
            ";
        }
        else{
            $sqlProductGroup = "
            SELECT pg.product_group_id
                  ,pg.title
            FROM product_group pg
            LEFT JOIN product_group_staff pgs ON (pg.product_group_id = pgs.product_group_id)
            WHERE pg.product_group_id = pgs.product_group_id
            AND pgs.staff_id = {$_SESSION['staff_id']}
            ";
        }

        $sqlCategory = '';
        $modCat = getCPModuleObj('webBasic_category');
        $sqlCategory = $modCat->model->getCategorySQLByType('Product');
        $expCategory = array('detailValue' => $row['category_title']);

        $sqlSubCategory = '';
        if ($row['category_id'] != ''){
            $modSubCat = getCPModuleObj('webBasic_subCategory');
            $sqlSubCategory = $modSubCat->model->getSubCategorySQL($row['category_id']);
        }
        $expSubCategory = array('detailValue' => $row['sub_category_title']);

        $stockFld = '';
        if (!$cpCfg['m.ecommerce.product.hasProductItem']){
            $stockFld = "
            {$formObj->getTBRow('Quantity in Stock', 'qty_in_stock', $row['qty_in_stock'], $expNoEdit)}
            ";
        }

        $modSec = getCPModuleObj('webBasic_section');
        $sqlSection = $modSec->model->getSectionSQL();
        $expSection = array('detailValue' => $row['section_title']);

                             /*{$formObj->getDDRowBySQL('Section', 'section_id', $sqlSection, $row['section_id'], $expSection)}*/
		$validatedProduct =	"{$formObj->getTBRow('Product Name *', 'title', $ln->gfv($row, 'title', '0'))}
                             {$formObj->getDDRowBySQL('Category', 'category_id', $sqlCategory, $row['category_id'], $expCategory)}
                             {$formObj->getDDRowBySQL('Sub Category', 'sub_category_id', $sqlSubCategory, $row['sub_category_id'], $expSubCategory)}
							";

        $price_from_supplier =  '';

        if ($_SESSION['userGroupType'] == 'Super Administrator' || $_SESSION['userGroupType'] == 'Administrator' ) {
            $price_from_supplier = $formObj->getTBRow('Price from Supplier', 'price_from_supplier', $row['price_from_supplier']);
        }


        if ($cpCfg['cp.restaurent'] == 1){

        $productTypeArr = array(
            "Menu"
            ,"Items"
            
        );
    }else{
        $productTypeArr = array(
           "Purchasing and Selling"
            ,"Purchasing Product"
            ,"Selling Product"
        );
    }

        $SqlSupplier = "
        SELECT  s.supplier_id
               ,s.company_name
        FROM supplier s
        ";

        $hideDiscountFields = "";
        $hideDiscountAmount = "";
        $hideDiscountPercent = "";
        if($row['discount_type'] == ""){
            $hideDiscountFields = "hideDiscountFields";
        }
        else if($row['discount_type'] == "%"){
            $hideDiscountPercent = "showDiscountPercent";
        }
        else{
            $hideDiscountAmount = "showDiscountAmount";
        }

        $fielset1 = "
        {$formObj->getTBRow('Item Code', 'item_code', $row['item_code'], $expNoEdit)}
        {$validatedProduct}
        {$formObj->getDDRowBySQL('Supplier', 'supplier_id', $SqlSupplier, $row['supplier_id'])}
        {$formObj->getTBRow('Tag No', 'tag_no', $row['tag_no'])}
        {$stockFld}
        {$formObj->getTBRow('HSN', 'hsn', $row['hsn'])}
        {$priceText}
        {$formObj->getTBRow('GST%', 'gst', $row['gst'], $expNoEdit)}
        {$formObj->getDDRowBySQL('Unit', 'unit', $sqlUnit, $row['unit'], $expVl)}
        {$formObj->getDDRowByArr('Product Type', 'product_type', $productTypeArr, $row['product_type'])}
        {$formObj->getTBRow('Bar code', 'bar_code', $row['bar_code'])}
        {$formObj->getDateRow('From Date', 'discount_from_date', $row['discount_from_date'])}
        {$formObj->getDateRow('To Date', 'discount_to_date', $row['discount_to_date'])}
        {$formObj->getDDRowByArr('Discount Type', 'discount_type', $sqlDiscountType, $row['discount_type'], $expVlDisc)}
        {$formObj->getTBRow('MRP', 'mrp', $row['mrp'])}
        <div class='{$hideDiscountFields} hideDiscountPercent {$hideDiscountPercent}'>
            {$formObj->getTBRow('Discount Percentage', 'discount_percentage', $row['discount_percentage'])}
        </div>
        <div class='{$hideDiscountFields} hideDiscountAmount {$hideDiscountAmount}'>
            {$formObj->getTBRow('Discount Amount', 'discount_amount', $row['discount_amount'])}
        </div>
        {$weight}
        {$formObj->getTARow('Short Description', 'description_short', $ln->gfv($row, 'description_short', '0'))}
        {$embedCode}
        {$formObj->getYesNoRRow('Published', 'published', $row['published'])}
        {$latest}
        {$favourite}
        {$formObj->getYesNoRRow('General Quotation', 'general_quotation', $row['general_quotation'])}
        ";

        $fieldset2 = "
        {$formObj->getHTMLEditor('Description', 'description', $ln->gfv($row, 'description', '0'))}
        ";

        $fieldset3 = '';

        if($cpCfg['m.ecommerce.product.showDescription2']){
            $fieldset3 = "
            {$formObj->getFieldSetWrapped('Description 2',
             $formObj->getHTMLEditor('Description 2', 'description2', $ln->gfv($row, 'description2', '0'))
            )}
            ";
        }

        $text = "
        {$formObj->getFieldSetWrapped('Product Details', $fielset1)}
        {$formObj->getFieldSetWrapped('Description', $fieldset2)}
        {$fieldset3}
        {$formObj->getCreationModificationText($row)}
        ";
        return $text;
    }

    /**
     *
     */

    function getRightPanel($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $media = Zend_Registry::get('media');

        $links = '';

        if ($cpCfg['m.ecommerce.product.hasRelatedProduct'] == 1){
            $links .= $displayLinkData->getLinkPortalMain('ecommerce_product', 'ecommerce_productLink', 'Related Products', $row);
        }

        if ($cpCfg['m.ecommerce.product.hasVoucherHistory']){
            $url       = "index.php?module=ecommerce_product&_spAction=generateBulkVouchers&id={$row['product_id']}&showHTML=0";
            $printLink = "index.php?module=ecommerce_product&_spAction=printVoucher&id={$row['product_id']}&showHTML=0";

            $links .= "
            <div class='floatbox'>
                <div class='float_right'>
                    <a href='{$url}' id='bulkAddVouchers'>Bulk Generate</a>
                </div>
                <div class='float_right'>
                    <a href='{$printLink}' id='printVoucher' target='_blank'>Print Voucher &nbsp;|</a>
                </div>
            </div>
            {$displayLinkData->getLinkPortalMain('ecommerce_product', 'ecommerce_productVoucherLink', 'Product Voucher Link', $row)}
            ";
        }

        if ($cpCfg['m.ecommerce.product.hasProductItem'] == 1){
            $links .= $displayLinkData->getLinkPortalMain('ecommerce_product', 'ecommerce_productItemLink', 'Product Item', $row);
        }

        if ($cpCfg['m.ecommerce.product.hasCountry'] == 1){
            //$links .= "
            //{$displayLinkData->getLinkPortalMain('ecommerce_product', 'country', 'Countries', $row)}
            //{$productItem}
            //";
        }

        if ($cpCfg['m.ecommerce.product.hasContentHistory'] == 1){
            //$links .= $displayLinkData->getLinkPortalMain('ecommerce_product', 'webBasic_contentHistoryLink', 'Content History', $row);
        }

        $text ="
        <div id='productPriceLinkPortal'>
            {$this->getProductPriceDetail($row['product_id'])}
        </div>
        {$media->getRightPanelMediaDisplay('Picture', 'tradingsg_product', 'picture', $row)}
        {$media->getRightPanelMediaDisplay('Related Picture', 'tradingsg_product', 'relatedPicture', $row)}
        {$links}
        ";
        return $text;
    }

    /**
     *
     */
    function getQuickSearch() {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpUtil = Zend_Registry::get('cpUtil');
        $am = Zend_Registry::get('am');
        $fn = Zend_Registry::get('fn');

        $supplier_id     	 = $fn->getReqParam('supplier_id');
        $special_search      = $fn->getReqParam('special_search');
        $special_search  	 = $fn->getReqParam('special_search');
        $product_group_id	 = $fn->getReqParam('product_group_id');
        $general_quotation   = $fn->getReqParam('general_quotation');
        $subCatOptions  = '';
        $catOptions  = '';

        //$sqlProductGroup = $fn->getDDSql('tradingsg_productGroup');
        $sqlProductGroup = "
        SELECT a.product_group_id
              ,a.title
        FROM product_group a
        ORDER BY a.product_group_id
        ";

        $sqlSupplier = "
        SELECT c.supplier_id
        	  ,c.company_name
        FROM supplier c
        ORDER BY c.company_name
        ";


        $SQLCat = "
        SELECT a.category_id
              ,a.title
        FROM category a
        ORDER BY a.title
        ";
        $catOptions = $dbUtil->getDropDownFromSQLCols2($db, $SQLCat, $tv['category_id']);

        if ($tv['category_id'] != "") {
            $sqlCombo = "
            SELECT a.sub_category_id
                  ,a.title
            FROM sub_category a
            WHERE a.category_id = {$tv['category_id']}
            ORDER BY a.title
            ";
            $subCatOptions = $dbUtil->getDropDownFromSQLCols2($db, $sqlCombo, $tv['sub_category_id']);
        }

        $generalQuoteArray = array(
            "Yes"
           ,"No"
        );


        $text = "
        <td>
            <select name='supplier_id'>
                <option value=''>Supplier Name</option>
                {$dbUtil->getDropDownFromSQLCols2($db, $sqlSupplier, $supplier_id)}
            </select>
        </td>
        <td class='fieldValue'>
            <select name='category_id'>
                <option value=''>Category</option>
                {$catOptions}
            </select>
        </td>
        <td class='fieldValue'>
            <select name='sub_category_id'>
                <option value=''>Sub Category</option>
                {$subCatOptions}
            </select>
        </td>
        <td>
            <select class='w125' name='general_quotation'>
                <option value=''>Show GQ</option>
                {$cpUtil->getDropDown1($generalQuoteArray, $general_quotation)}
           </select>
        </td>
        <td class='fieldValue'>
            <select name='special_search'>
                <option value=''>Special Search</option>
                {$cpUtil->getDropDown1($cpCfg['m.ecommerce.product.btnPosArr'], $special_search)}
            </select>
        </td>
        </tr>
        <tr>
        <!--<td class='button' style='padding:8px'>
            <a class='quickAdd' href='#'>Quick Add</a>
        </td>-->
        ";


        return $text;
    }

    /**
     *
     */
    function getQuickAdd() {
        $formObj = Zend_Registry::get('formObj');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $formAction = "index.php?_topRm=admin&module=tradingsg_product&_spAction=quickAddSubmit&showHTML=0";

        $unit   = $fn->getReqParam('unit');
        $product_group_id   = $fn->getReqParam('product_group_id');
        $company_id   = $fn->getReqParam('company_id');

        $sqlUnit = "
        SELECT v.value
              ,v.value
        FROM valuelist v
        WHERE v.key_text = 'productUnit'
        ORDER BY v.value
        ";

        $sqlSupplier = "
        SELECT c.company_id
              ,c.company_name
        FROM company c
        WHERE c.category = 'Supplier'
        ORDER BY c.company_name
        ";

        if ($_SESSION['userGroupType'] == 'Super Administrator' || $_SESSION['userGroupType'] == 'Administrator' ) {
            $sqlProductGroup = "
            SELECT product_group_id
                  ,title AS product_group_title
            FROM product_group
            ";
        }
        else{
            $sqlProductGroup = "
            SELECT pg.product_group_id
                  ,pg.title AS product_group_title
            FROM product_group pg
            LEFT JOIN product_group_staff pgs ON (pg.product_group_id = pgs.product_group_id)
            WHERE pg.product_group_id = pgs.product_group_id
            AND pgs.staff_id = {$_SESSION['staff_id']}
            ";
        }

        $product = "<input type='text' value='' id='title' class='text' name='title[]'>";

        $productGroup = "
        <select name='product_group_id[]'>
            <option value=''>Please Select</option>
            {$dbUtil->getDropDownFromSQLCols2($db, $sqlProductGroup, $product_group_id)}
        </select>
        ";

        $partNumber = "<input type='text' value='' id='part_number' class='text' name='part_number[]'>";
        $hsn = "<input type='text' value='' id='price' class='text' name='hsn[]'>";
        $listPrice = "<input type='text' value='' id='price' class='text' name='price[]'>";
        $priceFromSupplier = "<input type='text' value='' id='price_from_supplier' class='text' name='price_from_supplier[]'>";

        $uom = "
        <select name='unit[]'>
            <option value=''>Please Select</option>
            {$dbUtil->getDropDownFromSQLCols2($db, $sqlUnit, $unit)}
        </select>
        ";

        $supplier = "
        <select name='company_id[]'>
            <option value=''>Please Select</option>
            {$dbUtil->getDropDownFromSQLCols2($db, $sqlSupplier, $company_id)}
        </select>
        ";

        $newRow = "
        <a href='#' class='addRow button mb10'>Add Product</a>
        ";

        $rows = "
        <tr>
            <td>{$product}</td>
            <td>{$partNumber}</td>
            <td>{$hsn}</td>
            <td>{$productGroup}</td>
            <td class='supplier'>{$supplier}</td>
            <td class='uom'>{$uom}</td>
            <td>{$priceFromSupplier}</td>
            <td>{$listPrice}</td>
        </tr>
        ";

        $header ="
        <tr>{$newRow}</tr>
        <tr style='background-color:#EAEAE8;'>
        <th>Product Title</th>
        <th>Part Number</th>
        <th>HSN</th>
        <th>Product Group</th>
        <th>Supplier</th>
        <th>UOM</th>
        <th>Price from Suppplier</th>
        <th>List Price</th>
        </tr>
        ";

        $text = "
        <form id='quickAddForm' class='' method='post' action='{$formAction}'>
            <table class='thinlist' id='productTable'>
                {$header}
                {$rows}
            </table>
        </form>
        ";

        return $text;
    }

    /**
     *
     */
    function getAddProductRecord() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $unit   = $fn->getReqParam('unit');
        $product_group_id = $fn->getReqParam('product_group_id');
        $company_id = $fn->getReqParam('company_id');

        $sqlUnit = "
        SELECT v.value
              ,v.value
        FROM valuelist v
        WHERE v.key_text = 'productUnit'
        ORDER BY v.value
        ";

        $sqlSupplier = "
        SELECT c.company_id
              ,c.company_name
        FROM company c
        WHERE c.category = 'Supplier'
        ORDER BY c.company_name
        ";

        if ($_SESSION['userGroupType'] == 'Super Administrator' || $_SESSION['userGroupType'] == 'Administrator' ) {
            $sqlProductGroup = "
            SELECT product_group_id
                  ,title AS product_group_title
            FROM product_group
            ";
        }
        else{
            $sqlProductGroup = "
            SELECT pg.product_group_id
                  ,pg.title AS product_group_title
            FROM product_group pg
            LEFT JOIN product_group_staff pgs ON (pg.product_group_id = pgs.product_group_id)
            WHERE pg.product_group_id = pgs.product_group_id
            AND pgs.staff_id = {$_SESSION['staff_id']}
            ";
        }

        $product = "<input type='text' value='' id='title' class='text' name='title[]'>";

        $productGroup = "
        <select name='product_group_id[]'>
            <option value=''>Please Select</option>
            {$dbUtil->getDropDownFromSQLCols2($db, $sqlProductGroup, $product_group_id)}
        </select>
        ";

        $partNumber = "<input type='text' value='' id='part_number' class='text' name='part_number[]'>";
        $listPrice = "<input type='text' value='' id='price' class='text' name='price[]'>";
        $priceFromSupplier = "<input type='text' value='' id='price_from_supplier' class='text' name='price_from_supplier[]'>";

        $uom = "
        <select name='unit[]'>
            <option value=''>Please Select</option>
            {$dbUtil->getDropDownFromSQLCols2($db, $sqlUnit, $unit)}
        </select>
        ";

        $supplier = "
        <select name='company_id[]'>
            <option value=''>Please Select</option>
            {$dbUtil->getDropDownFromSQLCols2($db, $sqlSupplier, $company_id)}
        </select>
        ";

        $rows = "
        <tr>
            <td>{$product}</td>
            <td>{$partNumber}</td>
            <td>{$productGroup}</td>
            <td class='supplier'>{$supplier}</td>
            <td class='uom'>{$uom}</td>
            <td>{$priceFromSupplier}</td>
            <td>{$listPrice}</td>
        </tr>
        ";

        return $rows;
    }

    /**
     *
     */
    function getProductPriceDetail($product_id=''){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        if($product_id == ''){
            $product_id = $fn->getReqParam('product_id');
        }

        $Product = $this->getProductPriceDetailList($product_id);

        $recCount = $fn->getRecordCount('product_price', "product_id = '{$product_id}'");

        $header ="
        <thead>
            <tr>
                <th>Date</th>
                <th>Price</th>
                <th>Product Weight(kg)</th>
                <th>GST %</th>
            </tr>
        </thead>
        ";

        $formActionProductPrice = "index.php?module=tradingsg_product&_spAction=AddProductPrice&product_id={$product_id}&showHTML=0";

        $add = '';
        if($cpCfg['cp.mrpProducts'] == 1){
            $add = "<div class='actBtns'>
                        <a id='AddProductPrice' href='{$formActionProductPrice}' product_id={$product_id}>Add</a>
                    </div>";
        }

        $text = "
        <div class='linkPortalWrapper tradingsg_product_productPriceLink'>
            <div class='header' expanded='1'>
                <div class='floatbox'>
                    <div class='float_left'>Product Price Linked</div>
                    <div class='txtRight'>
                        <span class='count' id='AddProductPricePortalCount'>({$fn->getRecordCount('product_price', "product_id = '{$product_id}'")})</span>
                        <div class='toggle'></div>
                    </div>
                </div>
            </div>
            <div class='linkPortalDataWrapper'>
                <form>
                    <table class='productPricelist'>
                        {$header}
                        <tbody id='AddProductPricePortal'>
                            {$Product}
                        </tbody>
                    </table>
                    <input type='hidden' name='product_id' value='{$product_id}' />
                </form>
            </div>
            {$add}
        </div>
        ";

        return $text;

    }

    /**
     *
     */
    function getProductPriceDetailList($product_id = ''){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        if($product_id == ''){
            $product_id = $fn->getReqParam('product_id');
        }

        $rows  = "";

        $SQL="
        SELECT pp.price
              ,pp.agent_commission
              ,pp.created_by
              ,pp.product_weight
              ,pp.weight_per_kg
              ,pp.gst
              ,pp.creation_date
              ,pp.modified_by
              ,pp.modification_date
              ,pp.product_price_id
              ,pp.product_id
        FROM product_price pp
        WHERE product_id = '{$product_id}'
        ORDER BY pp.creation_date DESC
        ";
        $result   = $db->sql_query($SQL);
        $numRows = $db->sql_numrows($result);

        if($numRows == 0){
            $SQL="
            SELECT p.*
                  ,p.gst
                  ,p.product_weight
            FROM product p
            WHERE product_id = '{$product_id}'
            ";
            $result   = $db->sql_query($SQL);
        }

        $count = 1;
        $qty_balance = '';
        while ($row = $db->sql_fetchrow($result)) {
            $creation = $fn->getCPDate($row['creation_date'], 'd-m-Y');
            $rows .= "
            <tr>
                <td>{$creation}</td>
                <td align='right'>{$row['price']}</td>
                <td align='right'>{$row['product_weight']}</td>
                <td>{$row['gst']}</td>
            </tr>
            ";
            $count++;
        }

        $text="{$rows}";

        return $text;
    }

    /**
     *
     */
    function getAddProductPrice() {
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');

        $product_id = $fn->getReqParam('product_id');

        $productRec = $fn->getRecordRowByID('product', 'product_id', $product_id);

        $formAction = "index.php?_topRm=inventory&module=tradingsg_product&_spAction=AddProductPriceSubmit&showHTML=0";
        $expNoEdit = array('isEditable' => 0);

        if ($_SESSION['userGroupName'] != "Supplier") {
            $text = "
            <form id='AddProductPriceForm' class='AddProductPriceForm yform columnar' method='post' action='{$formAction}'>
                {$formObj->getTBRow('Price', 'price', $productRec['price'])}
                {$formObj->getTBRow('Product Weight(kg)', 'product_weight', $productRec['product_weight'])}
                {$formObj->getTBRow('GST %', 'gst', $productRec['gst'])}
                <input type='hidden' name='product_id' value='{$product_id}' />
            </form>
            ";
        } else{
            $text = "
            <form id='AddProductPriceForm' class='AddProductPriceForm yform columnar' method='post' action='{$formAction}'>
                {$formObj->getTBRow('Price', 'price', $productRec['price'])}
                {$formObj->getTBRow('Product Weight(kg)', 'product_weight', $productRec['product_weight'])}
                {$formObj->getTBRow('GST %', 'gst', $productRec['gst'])}
                <input type='hidden' name='product_id' value='{$product_id}' />
            </form>
            ";
        }
            //{$formObj->getTBRow('TP Commission(%)', 'tp_commission', $productRec['tp_commission'])}

        return $text;
    }

    /**
     *
     */

    /**
     *
     */
    function getUpdateStockHistoryFromPo() {
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $db = Zend_Registry::get('db');

        //http://cubobillpro.localhost/admin/index.php?_topRm=order&module=tradingsg_product&_spAction=updateStockHistoryFromPo&showHTML=0

        $SQL="
        SELECT p.*
        FROM po_product p
        LEFT JOIN purchase_order po ON (po.purchase_order_id = p.purchase_order_id)
        WHERE po.status != 'Cancelled'
        ";
        $result   = $db->sql_query($SQL);
        while ($row = $db->sql_fetchrow($result)) {
            if($row['damage_qty'] == ''){
                $row['damage_qty'] = 0;
            }
            $fa = array();
            $fa['po_product_id'] = $row['po_product_id'];
            $fa['product_id']  = $row['product_id'];
            $fa['purchase_order_id']  = $row['purchase_order_id'];
            $fa['qty']  = $row['qty'];
            $fa['damage_qty']  = $row['damage_qty'];
            $fa['creation_date']  = $row['creation_date'];

            $SQLInsert = $dbUtil->getInsertSQLStringFromArray($fa, 'stock_history');
            $resultInsert = $db->sql_query($SQLInsert);
        }

    }
}