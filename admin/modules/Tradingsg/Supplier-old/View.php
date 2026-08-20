<?
class CPL_Admin_Modules_Tradingsg_Supplier_View extends CP_Admin_Modules_Tradingsg_Supplier_View
{
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
        $sqlGroupName = $fn->getValueListSQL('companyGroupName');
        $sqlCustomerType = $fn->getValueListSQL('customerType');

        $sqlCountry = getCPModelObj('common_geoCountry')->getCountryDDSQL();
        $expCountry = array('detailValue' => $row['country_name']);

        $expVl = array('sqlType' => 'OneField');

        if ($cpCfg['m.tradingsg.company.hasDiscountPercent']) {
            $discountPercent = $formObj->getTBRow('Discount Percent', 'discount_percent', $row['discount_percent']);
        }

        if ($cpCfg['m.tradingsg.company.hasCstNo']) {
            $cstNo = $formObj->getTBRow('Cst No', 'cst_no', $row['cst_no']);
        }

        if ($cpCfg['m.tradingsg.company.hasTinNo']) {
            $tinNo = $formObj->getTBRow('Tin No', 'tin_no', $row['tin_no']);
        }

        if ($cpCfg['m.tradingsg.company.hasGstNo']) {
            $gstNo = $formObj->getTBRow('Gst No', 'gst_no', $row['gst_no']);
        }

        //{$formObj->getDDRowBySQL('Customer Type', 'customer_type', $sqlCustomerType, $row['customer_type'], $expVl)}
        //{$formObj->getYesNoRRow('Add FREIGHT COST', 'add_freight_cost', $row['add_freight_cost'])}

        $fieldset1 = "
        {$formObj->getTBRow('Name', 'company_name', $row['company_name'])}
        {$formObj->getTBRow('Website', 'website', $row['website'])}
        {$formObj->getTBRow('Main Phone', 'phone', $row['phone'])}
        {$formObj->getTBRow('Main Fax', 'fax', $row['fax'])}
        {$formObj->getYesNoRRow('Add VAT', 'add_vat', $row['add_vat'])}
        {$formObj->getYesNoRRow('Add CST', 'add_cst', $row['add_cst'])}
        {$formObj->getYesNoRRow('Add P&F', 'add_pf', $row['add_pf'])}
        {$formObj->getTBRow('VAT(%)', 'vat', $row['vat'])}
        {$formObj->getTBRow('CST (%)', 'cst', $row['cst'])}
        {$formObj->getTBRow('P&F (%)', 'p_f', $row['p_f'])}
        ";


        $fieldset2 = "
        {$formObj->getTBRow('Office Address', 'address_flat', $row['address_flat'])}
        {$formObj->getTBRow('Street Address', 'address_street', $row['address_street'])}
        {$formObj->getTBRow('District/ Town', 'address_town', $row['address_town'])}
        {$formObj->getTBRow('State/ Zip', 'address_state', $row['address_state'])}
        {$formObj->getDDRowBySQL('Country', 'address_country', $sqlCountry, $row['address_country'], $expCountry)}
        ";

        $fieldset3 = "
        {$formObj->getDDRowBySQL('Status', 'status', $sqlStatus, $row['status'], $expVl)}
  		{$formObj->getDDRowBySQL('Supplier Type', 'supplier_type', $sqlSupplier, $row['supplier_type'], $expVl)}
	    {$formObj->getDDRowBySQL('Industry', 'industry', $sqlIndustry, $row['industry'], $expVl)}
        {$formObj->getDDRowBySQL('Company Size', 'company_size', $sqlSize, $row['company_size'], $expVl)}
        {$formObj->getDDRowBySQL('Company Source', 'source', $sqlSource, $row['source'], $expVl)}
        {$discountPercent}
        {$cstNo}
        {$tinNo}
        {$gstNo}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Company Details', $fieldset1)}
        {$formObj->getFieldSetWrapped('Address', $fieldset2)}
        {$formObj->getFieldSetWrapped('More Details', $fieldset3)}
        {$formObj->getCreationModificationText($row)}
        ";

        return $text;
    }

}