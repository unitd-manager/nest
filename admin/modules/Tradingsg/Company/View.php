<?
class CPL_Admin_Modules_Tradingsg_Company_View extends CP_Common_Lib_ModuleViewAbstract
{
    /**
     *
     */
    function getList($dataArray){
        $listObj = Zend_Registry::get('listObj');

        $count   = 0;
        $rows    = '';

        foreach ($dataArray as $row){
            $email     = $row['email'];
            $website   = $row['website'];

            $rows .= "
            {$listObj->getListRowHeader($row, $count)}
            {$listObj->getGoToDetailText($count, $row['company_name'])}
            {$listObj->getListDataCell("<a href='{$website}'>{$website}</a>")}
            {$listObj->getListDataCell($row['phone'])}
            {$listObj->getListRowEnd($row['company_id'])}
            ";

            $count++ ;
        }

        $text = "
        {$listObj->getListHeader()}
        {$listObj->getListHeaderCell('Name', 'c.company_name')}
        {$listObj->getListHeaderCell('Website', 'a.website')}
        {$listObj->getListHeaderCell('Telephone', 'a.phone' )}
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

        $fielset1 = "
        {$formObj->getTBRow('Client Company Name', 'company_name')}
        {$formObj->getTBRow('Main Phone', 'phone')}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Key Details', $fielset1)}
        ";

        return $text;
    }

    /**
     *
     */
    function getEdit($row){
        $formObj = Zend_Registry::get('formObj');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $formObj->mode = $tv['action'];

        $discountPercent = '';
        $cstNo = '';
        $tinNo = '';

        $sqlStatus   = $fn->getValueListSQL('companyStatus');
        $sqlSupplier = $fn->getValueListSQL('supplierType');
        $sqlIndustry = $fn->getValueListSQL('companyIndustry');
        $sqlSize     = $fn->getValueListSQL('companySize');
        $sqlSource   = $fn->getValueListSQL('companySource');

        $sqlCountry = getCPModelObj('common_geoCountry')->getCountryDDSQL();
        $expCountry = array('detailValue' => $row['country_name']);

        $expVl = array('sqlType' => 'OneField');

        $markUp = '';
        /*if ($cpCfg['m.tradingsg.company.hasMarkUpPercent']) {
            $markUp = $formObj->getTBRow('Mark Up(%)', 'mark_up_percentage', $row['mark_up_percentage']);
        }*/

        if ($cpCfg['m.tradingsg.company.hasCstNo']) {
            $cstNo = $formObj->getTBRow('Cst No', 'cst_no', $row['cst_no']);
        }

        if ($cpCfg['m.tradingsg.company.hasGstNo']) {
            $gstNo = $formObj->getTBRow('Gst No', 'gst_no', $row['gst_no']);
        }

        if ($cpCfg['m.tradingsg.company.hasTinNo']) {
            $tinNo = $formObj->getTBRow('Tin No', 'tin_no', $row['tin_no']);
        }

//        {$formObj->getDDRowBySQL('Customer Type', 'customer_type', $sqlCustomerType, $row['customer_type'], $expVl)}
        $current_date = date('Y-m-d');

        if($row['loyalty_point_linked'] == 1) {
            $row['loyalty_linked_date'] = $current_date;
        }

        $fieldset1 = "
        {$formObj->getTBRow('Name', 'company_name', $row['company_name'])}
        {$formObj->getTBRow('Website', 'website', $row['website'])}
        {$markUp}
        {$formObj->getTBRow('Main Phone', 'phone', $row['phone'])}
        {$formObj->getTBRow('Main Fax', 'fax', $row['fax'])}
        ";

        $fieldset2 = "
        {$formObj->getTBRow('Address1', 'address_flat', $row['address_flat'])}
        {$formObj->getTBRow('Address2', 'address_street', $row['address_street'])}
        {$formObj->getTBRow('District/ Town', 'address_town', $row['address_town'])}
        {$formObj->getTBRow('State/ Zip', 'address_state', $row['address_state'])}
        {$formObj->getDDRowBySQL('Country', 'address_country', $sqlCountry, $row['address_country'], $expCountry)}
        ";

		$fieldset3 = "
        {$formObj->getTBRow('Address1', 'billing_address_flat', $row['billing_address_flat'])}
        {$formObj->getTBRow('Address2', 'billing_address_street', $row['billing_address_street'])}
        {$formObj->getTBRow('District/ Town', 'billing_address_town', $row['billing_address_town'])}
        {$formObj->getTBRow('State/ Zip', 'billing_address_state', $row['billing_address_state'])}
        {$formObj->getDDRowBySQL('Country', 'billing_address_country', $sqlCountry, $row['billing_address_country'], $expCountry)}
		";

        $fieldset4 = "
        {$formObj->getDDRowBySQL('Status', 'status', $sqlStatus, $row['status'], $expVl)}
  		{$formObj->getDDRowBySQL('Supplier Type', 'supplier_type', $sqlSupplier, $row['supplier_type'], $expVl)}
	    {$formObj->getDDRowBySQL('Industry', 'industry', $sqlIndustry, $row['industry'], $expVl)}
        {$formObj->getDDRowBySQL('Company Size', 'company_size', $sqlSize, $row['company_size'], $expVl)}
        {$formObj->getDDRowBySQL('Company Source', 'source', $sqlSource, $row['source'], $expVl)}
        {$cstNo}
        {$tinNo}
        {$gstNo}
        {$formObj->getYesNoRRow('Loyalty point linked', 'loyalty_point_linked', $row['loyalty_point_linked'])}
        {$formObj->getDateRow('Loyalty Linked Date', 'loyalty_linked_date', $row['loyalty_linked_date'])}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Company Details', $fieldset1)}
        {$formObj->getFieldSetWrapped('Client Delivery Address', $fieldset2)}
        {$formObj->getFieldSetWrapped('Client Billing Address', $fieldset3)}
        {$formObj->getFieldSetWrapped('More Details', $fieldset4)}
        {$formObj->getCreationModificationText($row)}
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
    function getSearch(){
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');

        $sqlCategory = $fn->getValueListSQL('companyCategory');
        $sqlStatus   = $fn->getValueListSQL('companyStatus');
        $expVl = array('sqlType' => 'OneField');

        $spArray = array(
            "Flagged"
           ,"Not-Flagged"
        );

        $fielset = "
        {$formObj->getTBRow('Company Name', 'company_name')}
        {$formObj->getDDRowBySQL('Choose Category', 'category', $sqlCategory, 'Client', $expVl)}
        {$formObj->getDDRowBySQL('Status', 'status', $sqlStatus, 'Current', $expVl)}
        {$formObj->getDDRowByArr('Special Search', 'special_search', $spArray)}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Company Details', $fielset)}
        ";

        return $text;
    }

    /**
     *
     */
    function getRightPanel($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $media = Zend_Registry::get('media');

        $discountLink = '';
        //if($cpCfg['m.tradingsg.company.hadDiscountLink']){
            $discountLink = $displayLinkData->getLinkPortalMain('tradingsg_company', 'tradingsg_discountLink', 'Service Cost/Discount', $row);
        //}

        $record_id = $fn->getIssetParam($row, 'company_id');

        $text = "
        {$displayLinkData->getLinkPortalMain('tradingsg_company', 'tradingsg_contactLink', 'Contacts Linked', $row)}
        {$discountLink}
        {$this->getRelatedInvoicesPortal($row)}
        {$media->getRightPanelMediaDisplay('Picture', 'tradingsg_company', 'picture', $row)}
        {$media->getRightPanelMediaDisplay('Attachments', 'tradingsg_company', 'attachment', $row)}
        ";

        return $text;
    }

    /**
     *
     */
    function getProductKeyPortal($company_id=''){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        if($company_id == ''){
            $company_id = $fn->getReqParam('company_id');
        }

        $ProductKey = $this->getProductKeyLink($company_id);

        $recCount = $fn->getRecordCount('lead_history', "company_id = '{$company_id}'");

        $header ="
        <thead>
            <tr>
                <th>Product Key</th>
                <th>No Of Users</th>
                <th>Status</th>
                <th>Activated Date</th>
                <th>Linked By & Date</th>
            </tr>
        </thead>
        ";

        if($recCount == 0){
            $header = "<tr><td align='center'>No Records Linked<br/><br/></td></tr>";
        }

        $formActionProductKeyLink = "index.php?module=tradingsg_company&_spAction=AddProductKeyToCompany&company_id={$company_id}&showHTML=0";

        $add = "<div class='actBtns'>
                    <a class='AddProductKeyLinkButton'  href='{$formActionProductKeyLink}' company_id={$company_id}>Generate Key</a>
                </div>
                ";

        if($recCount > 0){
            $add = "";
        }

        $text = "
        <div class='linkPortalWrapper tradingsg_company_productKeyLink'>
            <div class='header' expanded='1'>
                <div class='floatbox'>
                    <div class='float_left'>Product Key Linked</div>
                    <div class='txtRight'>
                        <span class='count' id='AddProductKeyPortalCount'>({$recCount})</span>
                        <div class='toggle'></div>
                    </div>
                </div>
            </div>
            <div class='linkPortalDataWrapper'>
                <form>
                    <table class='ProductKeyList'>
                        {$header}
                        <tbody id='AddProductKeyPortal'>
                            {$ProductKey}
                        </tbody>
                    </table>
                    <input type='hidden' name='company_id' value='{$company_id}' />
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
    function getProductKeyLink($company_id = ''){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        if($company_id == ''){
            $company_id = $fn->getReqParam('company_id');
        }

        $rows  = "";

        $SQL="
        SELECT product_name 
              ,trial_key
              ,active_key
              ,status
              ,active_date
              ,no_of_users
              ,linked_date
              ,linked_by
        FROM lead_history
        WHERE company_id = '{$company_id}'
        ";
        $result   = $db->sql_query($SQL);
        $numRows = $db->sql_numrows($result);

        $count = 1;
        $qty_balance = '';
        while ($row = $db->sql_fetchrow($result)) {
            $rows .= "
            <tr>
                <td>{$row['product_name']}</td>
                <td>{$row['trial_key']}</td>
                <td>{$row['active_key']}</td>
                <td>{$row['no_of_users']}</td>
                <td>{$row['status']}</td>
                <td>{$row['active_date']}</td>
                <td><i>{$row['linked_by']} - {$row['linked_date']}</i></td>
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
    function getQuickSearch() {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $status   = $fn->getReqParam('status');

        $sqlStatus = $fn->getValueListSQL('companyStatus');

        $spArray = array(
            "Flagged"
           ,"Not-Flagged"
        );

        $text = "
        <td>
            <select name='status' >
                <option value=''>Status</option>
                {$dbUtil->getDropDownFromSQLCols1($db, $sqlStatus, $status)}
            </select>
        </td>
        <td>
            <select name='special_search'>
                <option value=''>Special Search</option
                {$cpUtil->getDropDown1($spArray, $tv['special_search'])}
           </select>
        </td>
        ";

        return $text;
    }

    /**
     *
     */
    function getAddProductKeyToCompany() {
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');

        $company_id = $fn->getReqParam('company_id');

        $formAction = "index.php?module=tradingsg_company&_spAction=AddProductKeyToCompanySubmit&showHTML=0";
        $expNoEdit = array('isEditable' => 0);

        $sqlLatestKey = "
        SELECT CASE 
               WHEN MAX(lead_history_id)
               THEN MAX(lead_history_id) + 1
               ELSE 1
               END AS lead_history_id
        FROM lead_history
        WHERE (status = 'Active' OR company_id IS NOT NULL)
        ";
        $resultLatestKey  = $db->sql_query($sqlLatestKey);
        $numRowsLatestKey = $db->sql_numrows($resultLatestKey);
        $rowLatestKey     = $db->sql_fetchrow($resultLatestKey);

        if($numRowsLatestKey == 0){
            $rowLatestKey['lead_history_id'] = 1;
        }

        $productKeyRec = $fn->getRecordByCondition('lead_history', "lead_history_id = '{$rowLatestKey['lead_history_id']}'");

        $text = "
        <form id='AddProductKeyLinkForm' class='AddProductKeyLinkForm yform columnar' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Product Key', 'radnom_no', $productKeyRec['radnom_no'], $expNoEdit)}
            {$formObj->getTBRow('No Of Users', 'no_of_users', '')}
            <input type='hidden' name='company_id' value='{$company_id}' />
            <input type='hidden' name='lead_history_id' value='{$rowLatestKey['lead_history_id']}' />
        </form>
        ";

        return $text;
    }
    /**
     *
     */
    function getRelatedInvoicesPortal($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $rows  = "";        

        $SQLInv = "
        SELECT i.*
              ,c.company_id
              ,o.loyalty_points
              ,(SELECT SUM(invHist.amount) AS prev_sum
                FROM invoice_receipt_history invHist
                LEFT JOIN receipt r ON (r.receipt_id = invHist.receipt_id)
                WHERE invHist.invoice_id =  i.invoice_id
                AND r.receipt_status != 'Cancelled'
                AND i.status != 'Cancelled'
                ) as Amount_Paid
        FROM invoice i
        LEFT JOIN (`order` o) ON (i.order_id   = o.order_id)
        LEFT JOIN (company c) ON (o.company_id = c.company_id)
        WHERE c.company_id = {$row['company_id']}
          AND i.status != 'Cancelled'
        ";

        $resultInv   = $db->sql_query($SQLInv);
        $recCount = $db->sql_numrows($resultInv);
        while ($rowInv = $db->sql_fetchrow($resultInv)) {

            $invoice_date = $fn->getCPDate($rowInv['invoice_date'], 'd-m-Y');

            $balance_Amount = $rowInv['invoice_amount'] - $rowInv['Amount_Paid'];
            $balance_Amount = number_format($balance_Amount, 2);
            $Amount_Paid = number_format($rowInv['Amount_Paid'], 2);
            $invoice_amount = number_format($rowInv['invoice_amount'], 2);

            $rows .= "
            <tr>
                <td>{$invoice_date}</td>
                <td>{$rowInv['invoice_code']}</td>
                <td>{$rowInv['loyalty_points']}</td>
                <td align='right'>{$invoice_amount}</td>
                <td align='right'>{$Amount_Paid}</td>
                <td align='right'>{$balance_Amount}</td>
            </tr>
            ";
        }

        $header ="
        <tr>
          <th>Date</th>
          <th>Invoice Code</th>
          <th>Loyalty Points</th>
          <th>Amount</th>
          <th>Paid</th>
          <th>Balance</th>
        </tr>
        ";

        if($recCount == 0){
            $header = "<tr><td align='center'>No Records Linked<br/><br/></td></tr>";
        }

        $SQLTotal = "
        SELECT  SUM(o.loyalty_points) AS total_points
        FROM `order` o
        LEFT JOIN (company c) ON (o.company_id = c.company_id)
        WHERE c.company_id = o.company_id
        ";
        $resultTotal = $db->sql_query($SQLTotal);
        $rowTotal    = $db->sql_fetchrow($resultTotal);

        $text = "
        <div class='linkPortalWrapper tradingsg_company_invoiceLink'>
          <div class='panel panel-primary'>
            <div class='panel-heading'>
              <div expanded='1'>
                  <div class='floatbox'>
                      <div class='float_left RightPanelHeading'>Related Invoices</div>
                      <div class='txtRight'>
                      Total Loyalty points: {$rowTotal['total_points']}
                          <span class='count' id='relatedinvoicesPortalCount'>({$recCount})</span>
                          <div class='toggle'></div>
                      </div>
                  </div>
              </div>
            </div>
            <div class='panel-body'>
              <div class='linkPortalDataWrapper'>
                  <form>
                      <table class='relatedinvoicesList'>
                          <thead>
                              {$header}
                          </thead>
                          <tbody id='relatedinvoicesPortal'>
                              {$rows}
                          </tbody>
                      </table>
                  </form>
              </div>
            </div>
          </div>
        </div>
        ";

        return $text;
    }
}