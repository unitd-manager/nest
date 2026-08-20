<?
class CPL_Admin_Modules_Tradingsg_Enquiry_View extends CP_Admin_Modules_Tradingsg_Enquiry_View
{
    function getList($dataArray){
        $listObj = Zend_Registry::get('listObj');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');

        $rows  = "";
        $rowCounter = 0;
        $fnModCountry = includeCPClass('ModuleFns', 'common_country');

        $staff       = '';
        $clientType  = '';
        $country     = '';
        $enquiryType = '';
        $subject     = '';
        //--------------------------------------------------------------------------//
        foreach ($dataArray as $row){
            $staff       = $listObj->getListDataCell($row['staff_name_assigned']);
            $enquiryType = $listObj->getListDataCell($row['enquiry_type']);


            $rows .= "
            {$listObj->getListRowHeader($row, $rowCounter)}
            {$listObj->getGoToDetailText($rowCounter, $row['title'])}
            {$fnModCountry->getCountryValueCellInList($row)}
            {$listObj->getListDataCell($row['c_company_name'])}
            {$listObj->getListDataCell($row['email'])}
            {$listObj->getListDataCell($row['status'])}
            {$staff}
            {$subject}
            {$listObj->getListDataCell($row['creation_date'])}
            {$listObj->getListDataCell($row['enquiry_id'])}
            {$listObj->getListRowEnd($row['enquiry_id'])}
            ";
            $rowCounter++;
        }

        $staff       = '';
        $clientType  = '';
        $enquiryType = '';
        $enquiryType = '';

        $staff = $listObj->getListHeaderCell('Staff Assigned', 'staff_name_assigned');

        $enquiryType = $listObj->getListHeaderCell('Enquiry Type', 'enquiry_type');

        $text = "
        {$listObj->getListHeader()}
        {$listObj->getListHeaderCell('Title', 'e.title')}
        {$listObj->getListHeaderCell('Client Name', 'com.company_name')}
        {$listObj->getListHeaderCell($ln->gd('cp.header.lbl.email', 'Email'), 'e.email')}
        {$listObj->getListHeaderCell($ln->gd('m.webBasic.header.enquiry.lbl.enquiryStatus', 'Enquiry Status'), 'e.status')}
        {$staff}
        {$listObj->getListHeaderCell($ln->gd('cp.header.lbl.creationDate', 'Creation Date'), 'e.creation_date')}
        {$listObj->getListHeaderCell($ln->gd('cp.header.lbl.id', 'ID'), 'e.enquiry_id')}
        {$listObj->getListHeaderEnd()}
        {$rows}
        {$listObj->getListFooter()}
        ";

        return $text;
    }

    /**
     *
     */
    function getPrint($result){
        return $this->getList($result);
    }

    /**
     *
     */
    function getNew(){
        $formObj = Zend_Registry::get('formObj');
        $ln = Zend_Registry::get('ln');

        $fieldset = "
        {$formObj->getTBRow('Title', 'title')}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Key Details', $fieldset)}
        ";

        return $text;
    }

    /**
     *
     */
    function getEdit($row) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $formObj = Zend_Registry::get('formObj');
        $fn = Zend_Registry::get('fn');
        $ln = Zend_Registry::get('ln');

        $formObj->mode = $tv['action'];

        //$expNoEditStaffAllocation = array('isEditable' => 0);
        $expNoEdit     = array('isEditable' => 1);
        $expNoEditCode = array('isEditable' => 0);
        $sqlStatus     = $fn->getValueListSQL('enquiryStatus');
        $expVl         = array('sqlType' => 'OneField');

        $sqlCountry = getCPModelObj('common_geoCountry')->getCountryDDSQL();
        $expCountry = array('detailValue' => $row['country_name']);

        $staff = '';
        $clientType = '';
        $country = '';

        $sqlStaff = "
        SELECT staff_id
              ,CONCAT_WS(' ', s.first_name, s.last_name) AS staff_name
        FROM staff s
        ORDER BY staff_name
        ";

        $expStf = array('detailValue' => $row['staff_name'], 'isEditable' => 0);
        $expNoEditStaffAllocation = array('detailValue' => $row['staff_name_assigned'], 'isEditable' => 0);
        $staff = $formObj->getDDRowBySQL('Staff',  'staff_id', $sqlStaff, $row['staff_id'], $expStf);

        //$country_val = ($row['address_country'] == '') ? 'SG' : $row['address_country'];
        if ($cpCfg['countryForCurrency'] == 'India'){
            $country_val = 'IN';
        } else if ($cpCfg['countryForCurrency'] == 'Singapore'){
            $country_val = 'SG';
        } else {
            $country_val = '';
        }

        $country = $formObj->getDDRowBySQL('Country', 'address_country', $sqlCountry, $country_val, $expCountry);

        $prefContactTime = "";
        $expCb = array('commaToArray' => true, 'sqlType' => 'OneField', 'disabled' => false);
        $sqlPrefCont = $fn->getValueListSQL('preferredContact');
        $sqlPrefTime = $fn->getValueListSQL('preferredTime');

        $prefContactTime = "
        {$formObj->getCheckBoxArrRowBySQL('Preferred Contact', 'preferred_contact[]', $sqlPrefCont, $row['preferred_contact'], $expCb)}
        {$formObj->getCheckBoxArrRowBySQL('Preferred Time', 'preferred_time[]', $sqlPrefTime, $row['preferred_time'], $expCb)}
        ";

        $fnModCountry = includeCPClass('ModuleFns', 'common_country');

        $expComp = array('displayText' => $row['c_company_name']);

        $sqlComp = "
        SELECT c.company_id
              ,c.company_name
        FROM company c
        WHERE c.category = 'Client'
        ORDER BY company_name
        ";
        $companyText = $fn->getRecordDetailLink('tradingsg_company', 'record_id',
                            $row['company_id'], $expComp);
        //$expCompDisp = array('detailValue' => $companyText, 'hideFirstOption' => 1);

        if ($row['status'] != ''){
        $status =  $formObj->getDDRowBySQL($ln->gd('m.webBasic.enquiry.lbl.enquiryStatus', 'Enquiry Status'), 'status', $sqlStatus, $row['status'], $expVl);
        } else {
        $status = $formObj->getDDRowBySQL($ln->gd('m.webBasic.enquiry.lbl.enquiryStatus', 'Enquiry Status'), 'status', $sqlStatus, 'In Progress', $expVl);
        }

        $follow_up_date = ($row['follow_up_date'] == '') ? date('Y-m-d', strtotime(' + 7 days')) : $row['follow_up_date'];

		$staffAllocation = '';
        if($_SESSION['userGroupType'] == 'User') {
			$staffAllocation = $formObj->getDDRowBySQL('Staff Assigned', 'staff_allocation', $sqlStaff, $row['staff_allocation'], $expNoEditStaffAllocation);
        } else {
			$staffAllocation = $formObj->getDDRowBySQL('Staff Assigned', 'staff_allocation', $sqlStaff, $row['staff_allocation']);

		}

        $fieldset1 = "
        {$formObj->getTBRow('Enquiry Code', 'enquiry_code', $row['enquiry_code'], $expNoEditCode)}
        {$formObj->getTBRow('Title', 'title', $row['title'])}
        {$formObj->getDDRowBySQL('Client Name', 'company_id', $sqlComp,$row['company_id'])}
        {$fnModCountry->getCountryDropDown($formObj->mode, $row)}
        {$formObj->getTBRow($ln->gd('cp.lbl.email', 'Email'), 'email', $row['email'], $expNoEdit)}
        {$formObj->getTBRow('Phone', 'phone', $row['phone'])}
        {$formObj->getTARow('Notes', 'comments', $row['comments'])}
        {$country}
        ";

        $fieldset2 = "
		{$staffAllocation}
        {$status}
        {$formObj->getDateRow($ln->gd('m.webBasic.enquiry.lbl.followUpDate', 'Follow up Date'), 'follow_up_date', $follow_up_date)}
        {$formObj->getTARow($ln->gd('m.webBasic.enquiry.lbl.adminCommnets', 'Admin Comments'), 'notes', $row['notes'])}
        {$staff}
        ";

        $text = "
        {$formObj->getFieldSetWrapped($ln->gd('m.webBasic.enquiry.lbl.enquiryDetails', 'Enquiry Details'), $fieldset1)}
        {$formObj->getFieldSetWrapped($ln->gd('m.webBasic.enquiry.lbl.followUpDate', 'Follow up'), $fieldset2)}
        {$formObj->getCreationModificationText2($row)}
        ";

        return $text;
    }

    /**
     *
     */
    function getRightPanel($row){

        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $media = Zend_Registry::get('media');
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $comment = getCPPluginObj('common_comment');
        $actionButtons = '';

        $record_id = $fn->getIssetParam($row, 'enquiry_id');

        $actionButtons .="
        <div class='floatbox actionBtnsDetail'>
            <div class='float_right btn btn-primary mb5 mr5'>
                <a href='#' id='raiseQuote' enquiry_id='{$row['enquiry_id']}'>Raise Quote</a>
            </div>
        </div>
        ";

        $enquiryProductGroupLink = "";
        if ($cpCfg['m.tradingsg.enquiry.hasEnquiryProductGroupLink'] == 1 && $_SESSION['userGroupType'] != "User") {
	        $enquiryProductGroupLink .= $displayLinkData->getLinkPortalMain('tradingsg_enquiry', 'tradingsg_productGroupLink', 'Product Group Linked', $row);
		}

        $text = "
        {$actionButtons}
		{$comment->getView(array(
		     'roomName' => 'tradingsg_enquiry'
		    ,'recordId' => $record_id
		    ,'allowEdit' => false
		    ,'allowDelete' => false
		    ,'addReviewLbl' => 'Add Activity'
		    ,'heading' => 'Activities'
		))}
        {$displayLinkData->getLinkPortalMain('tradingsg_enquiry', 'tradingsg_quoteLink', 'Quote Linked', $row)}
		{$enquiryProductGroupLink}
        ";

        return $text;
    }

    /**
     *
     */
    function getQuickSearch() {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $ln = Zend_Registry::get('ln');

        $status         = $fn->getReqParam('status');
        $creation_date1 = $fn->getReqParam('creation_date1');
        $creation_date2 = $fn->getReqParam('creation_date2');
        $sqlStatus      = $fn->getValueListSQL('enquiryStatus');

        $clientType = '';
        $staff      = '';

        if ($cpCfg['m.webBasic.enquiry.showStaff'] == 1){
            $sqlStaff = "
            SELECT s.staff_id
                  ,CONCAT_WS(' ', s.first_name, s.last_name) AS staff_name
            FROM staff s
            ORDER BY staff_name
            ";
            $staff_id = $fn->getReqParam('staff_id');

            $staff = "
            <td>
                <select name='staff_id'>
                    <option value=''>Staff</option>
                    {$dbUtil->getDropDownFromSQLCols2($db, $sqlStaff, $staff_id)}
                </select>
            </td>
            ";
        }

        if ($cpCfg['m.webBasic.enquiry.showClientType'] == 1){
            $sqlType = $fn->getValueListSQL('enquiryClientType');
            $client_type = $fn->getReqParam('client_type');

            $clientType = "
            <td>
                <select name='client_type'>
                    <option value=''>Client Type</option>
                    {$dbUtil->getDropDownFromSQLCols1($db, $sqlType, $client_type)}
                </select>
            </td>
            ";
        }

        $fnModCountry = includeCPClass('ModuleFns', 'common_country');
        $yearEnd = date('Y') + 10;
        $text = "
        <td class='dateRange'>
            {$ln->gd('cp.lbl.creationDate', 'Creation Date: ')}
            <input type='text' allowEdit='1' name='creation_date1' class='fld_date'
                   id='fld_creation_date1' value='{$creation_date1}' yearEnd='{$yearEnd}' />
            <input type='text' allowEdit='1' name='creation_date2' class='fld_date'
                   id='fld_creation_date2' value='{$creation_date2}' yearEnd='{$yearEnd}' />
        </td>
        {$clientType}
        {$staff}
        <td>
            <select name='status'>
                <option value=''>{$ln->gd('m.webBasic.enquiry.lbl.status', 'Status')}</option>
                {$dbUtil->getDropDownFromSQLCols1($db, $sqlStatus, $status)}
            </select>
        </td>
        {$fnModCountry->getCountryDropDown('search')}
        ";


        return $text;
    }
    function getEditValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();

        //$validate->validateData('title', 'Please enter the title');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }
}