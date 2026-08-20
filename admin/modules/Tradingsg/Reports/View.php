<?
class CPL_Admin_Modules_Tradingsg_Reports_View extends CP_Admin_Modules_Tradingsg_Reports_View
{

    var $jssKeys = array('jqForm-3.15');

    /**
     *
     */
    function getList() {
        $listObj = Zend_Registry::get('listObj');
        $cpUtil  = Zend_Registry::get('cpUtil');

        $rowCounter = 0;
        $rows = "";

        $text = "
        <div class='floatbox'>
            <div class='float_left'>
                <a href='#' class='cpBack'>back</a>
            </div>
            <div class='float_right'>
                {$this->getReportsDropdown()}
            </div>
        </div>
        <div id='reportSearchPanel' class='ui-corner-all'>
        </div>
        <div id='reportContainer' class='ui-corner-all'>
        </div>
        <script type='text/javascript' src='https://www.google.com/jsapi'></script>
        <script type='text/javascript'>
            google.load('visualization', '1.0', {'packages':['corechart']});
        </script>
		";

        return $text;
    }

    /**
     *
     */
    function getReportsDropdown() {
        $listObj = Zend_Registry::get('listObj');
        $cpUtil  = Zend_Registry::get('cpUtil');
        $cpCfg  = Zend_Registry::get('cpCfg');

        $rowCounter = 0;

        $repArrSrc = $this->model->reportsArray;

        $repArr = array();
        foreach($repArrSrc AS $key => $val){
            $repArr[$key] = $val['title'];
        }

        $text = "
        <table class='search'>
        <tr>
            <td>
                <select name='report' class='report'>
                    <option value=''>Please Choose the Report</option>
                    <optgroup label='Sales Reports'>
                        <option value='dailyCollectionReport'>Daily Collection Report12</option>
                        <option value='detailCollectionReport'>Detail Collection Report</option>
                        <option value='invoiceByMonth'>Sales by Month</option>
                        <option value='invoiceByYear'>Sales by Year</option>
                        <option value='salesSummaryByProduct'>Sales Summary By Product</option>
                        <option value='detailSummaryByClient'>Detail Summary By Client</option>
                    </optgroup>
                    <optgroup label='Stock Reports'>
                        <option value='stockReport'>Stock Report</option>
                        <option value='priceTrackReport'>Price Track Report</option>
                        <option value='supplierPaymentReport'>Supplier Payment Report</option>
                        <option value='gstReport'>GST Report</option>
                        <option value='stockTransferReport'>Stock Transfer Report</option>
                    </optgroup>
                    <optgroup label='GST Reports'>
                        <option value='purchaseGstReport'>Purchase GST Report</option>
                        <option value='salesGstReport'>Sales GST Report</option>
                    </optgroup>
                </select>
            </td>
        </tr>
        </table>
        ";

        return $text;
    }

    /**
     *
     */
    function getSearch() {
        $formObj = Zend_Registry::get('formObj');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $pager = Zend_Registry::get('pager');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $reportsArray = $this->model->reportsArray;

        $report = $fn->getReqParam('report');
        $url = "";

        $year         	= $fn->getReqParam('year');
        $month        	= $fn->getReqParam('month');
        $sort_order   	= $fn->getReqParam('sort_order');
        $search_by    	= $fn->getReqParam('search_by');
        $active_start 	= $fn->getReqParam('active_start');
        $active_end   	= $fn->getReqParam('active_end');
        $course_id    	= $fn->getReqParam('course_id');
        $subject_id   	= $fn->getReqParam('subject_id');
        $batch_id     	= $fn->getReqParam('batch_id');
        $status       	= $fn->getReqParam('status');
        $staff_id     	= $fn->getReqParam('staff_id');
        $teacher_id   	= $fn->getReqParam('teacher_id');
        $product_id   	= $fn->getReqParam('product_id');
        $company_id   	= $fn->getReqParam('company_id');
        $sortOrder      = $fn->getReqParam('sortOrder');
        $supplier_id    = $fn->getReqParam('supplier_id');
        $section_id     = $fn->getReqParam('section_id');
        $record_type    = $fn->getReqParam('record_type');
        $gst_status     = $fn->getReqParam('gst_status');
        $order_status   = $fn->getReqParam('order_status');
        $start_date   	= $fn->getReqParam('start_date');
        $end_date 	  	= $fn->getReqParam('end_date');

        /*if ($start_date == '') {
            $start_date = date('Y-m-d', mktime (0,0,0,date("m")-6, date("d"), date("Y")));
        }

        if ($end_date == '') {
            $end_date = date('Y-m-d');
        }*/

        $searchFldsArr = $reportsArray[$report]['searchFlds'];

        $typeArr = array (
                'Quote'
               );

        if ($year == '') {
            $year = date('Y');
        }

        $arr = array (
                '01' => 'January'
               ,'02' => 'February'
               ,'03' => 'March'
               ,'04' => 'April'
               ,'05' => 'May'
               ,'06' => 'June'
               ,'07' => 'July'
               ,'08' => 'August'
               ,'09' => 'September'
               ,'10' => 'October'
               ,'11' => 'November'
               ,'12' => 'December'
               );

        if ($month == '') {
            $month = date('m');
        }

        $rows = '';
        foreach($searchFldsArr AS $searchFld){

            if ($report == 'summaryPurchaseSales' ||
                $report == 'summaryPurchase' ||
                $report == 'summarySales')
            {
                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";

                if ($report == 'summaryPurchaseSales') {
                    $sqlCompany = "
                    SELECT  c.company_id
                    	   ,c.company_name
                    FROM company c
                    WHERE c.category = 'Client'
                    AND c.company_name != ''
                    ORDER BY c.company_name ASC
                    ";

                    $rows .= "
                    <td>
                        <select name='company_id' class='clientFilter'>
                            <option value=''>Select Client</option>
                            {$dbUtil->getDropDownFromSQLCols2($db, $sqlCompany, $company_id)}
                        </select>
                    </td>
                    ";
                } else if ($report == 'summaryPurchase') {
                    $sqlYear = "
                    SELECT DISTINCT DATE_FORMAT(o.order_date, '%Y') AS order_year
                    FROM `order` o
                    ";

                    $sqlCompany = "
                    SELECT  c.company_id
                    	   ,c.company_name
                    FROM company c
                    WHERE c.category = 'Supplier'
                    AND c.company_name != ''
                    ORDER BY c.company_name ASC
                    ";

                    $rows .= "
                    <td>
                        <select name='year' class='year'>
                            <option value=''>Select Year</option>
                            {$dbUtil->getDropDownFromSQLCols1($db, $sqlYear, $year)}
                        </select>
                    </td>

                    <td>
                        <select name='month' class='ml10 mr10 month'>
                            {$cpUtil->getDropDownFromArr($arr, $month)}
                        </select>
                    </td>

                    <td>
                        <select name='company_id' class='supplierFilter'>
                            <option value=''>Select Supplier</option>
                            {$dbUtil->getDropDownFromSQLCols2($db, $sqlCompany, $company_id)}
                        </select>
                    </td>
                    ";
                } else if ($report == 'summarySales') {
                    $sqlYear = "
                    SELECT DISTINCT DATE_FORMAT(o.order_date, '%Y') AS order_year
                    FROM `order` o
                    ";

                    $sqlCompany = "
                    SELECT  c.company_id
                    	   ,c.company_name
                    FROM company c
                    WHERE c.category = 'Client'
                    AND c.company_name != ''
                    ORDER BY c.company_name ASC
                    ";

                    $rows .= "
                    <td>
                        <select name='year' class='year'>
                            <option value=''>Select Year</option>
                            {$dbUtil->getDropDownFromSQLCols1($db, $sqlYear, $year)}
                        </select>
                    </td>

                    <td>
                        <select name='month' class='ml10 mr10 month'>
                            {$cpUtil->getDropDownFromArr($arr, $month)}
                        </select>
                    </td>

                    <td>
                        <select name='company_id' class='clientFilter'>
                            <option value=''>Select Client</option>
                            {$dbUtil->getDropDownFromSQLCols2($db, $sqlCompany, $company_id)}
                        </select>
                    </td>
                    ";
                }
            }

            if ($report == 'profitByMonth' || $report == 'profitByYear') {
                $rows .= "<input type='hidden' name='price_from_supplier' value='{$cpCfg['m.tradingsg.reports.hasPriceFromSupplierInProfit']}'>";
                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";
            }else if ($report == 'salesByYear' || $report == 'salesByMonth'){

                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";
            }else if ($report == 'invoiceByMonth'){
                $sqlYear = "
                SELECT DISTINCT DATE_FORMAT(i.invoice_date, '%Y') AS invoice_year
                FROM invoice i
                LEFT JOIN (`order` o) ON (o.order_id = i.order_id)
                ";

                $rows .= "
                <td>
                    <select name='year'>
                        <option value=''>Select Year</option>
                        {$dbUtil->getDropDownFromSQLCols1($db, $sqlYear,'')}
                    </select>
                </td>
                ";

            }elseif ($report == 'detailInvoiceByMonth' ) {
                $sqlYear = "
                SELECT DISTINCT DATE_FORMAT(q.quote_date, '%Y') AS quote_year
                FROM quote q
                ";

                $rows .= "
                <td>
                    <select name='year' class='year'>
                        {$dbUtil->getDropDownFromSQLCols1($db, $sqlYear, $year)}
                    </select>
                </td>

                <td>
                    <select name='month' class='ml10 mr10 month'>
                    <option value=''>All Months</option>
                        {$cpUtil->getDropDownFromArr($arr, $month)}
                    </select>
                </td>
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";

            }
            else if ($report == 'quoteByMonth' || $report == 'enquiryByMonth') {
                $sqlYear = "
                SELECT DISTINCT DATE_FORMAT(q.quote_date, '%Y') AS quote_year
                FROM quote q
                ";

                $rows .= "
                <td>
                    <select name='year' class='year'>
                        <option value=''>Select Year</option>
                        {$dbUtil->getDropDownFromSQLCols1($db, $sqlYear, $year)}
                    </select>
                </td>

                <td>
                    <select name='month' class='ml10 mr10 month'>
                        {$cpUtil->getDropDownFromArr($arr, $month)}
                    </select>
                </td>
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";
            } else if ($report == 'salesByClient') {
                $arrOrder = array ('Client Name Ascending'
                                  ,'Client Name Descending');

                $rows .= "
                <td>
                    <select name='sortOrder' class='ml10 mr10'>
                        {$cpUtil->getDropDownFromArr($arrOrder, $sortOrder)}
                    </select>
                </td>
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";
            } /*else if ($report == 'invoiceByYear'){

                $sqlYear = "
                SELECT DISTINCT DATE_FORMAT(i.invoice_date, '%Y') AS invoice_year
                FROM `invoice` i
                ";

                $rows .= "
                <td>
                    <select name='year'>
                        <option value=''>Select Year</option>
                        {$dbUtil->getDropDownFromSQLCols1($db, $sqlYear, '')}
                    </select>
                </td>
                ";

            }*/

            if($report == 'purchaseOrderReport') {
                $previous_year = date('Y') - 1;
                $next_year = date('Y') + 1;
                $sqlYear = array(
                      $previous_year
                     ,date('Y')
                     ,$next_year
                );
                $month = '';
                $year = '';

                $sqlCompany = "
                SELECT  c.company_id
                       ,c.company_name
                FROM company c
                WHERE c.category = 'Supplier'
                AND c.company_name != ''
                ORDER BY c.company_name ASC
                ";

                $rows .= "
                <td>
                    <select name='year' class='year'>
                        <option value=''>Choose Year</option>
                        {$cpUtil->getDropDown1($sqlYear, $year, 0)}
                    </select>
                </td>
                ";

                $rows .= "
                <td>
                    <select name='month' class='ml10 mr10 month'>
                        <option value=''>Choose Month</option>
                        {$cpUtil->getDropDownFromArr($arr, $month)}
                    </select>
                </td>
                ";

                $rows .= "
                <td class='dateRange'>
                    From Date:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    To Date:
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                <td>
                    <select name='company_id' class='supplierFilter'>
                        <option value=''>Select Supplier</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlCompany, $company_id)}
                    </select>
                </td>
                ";

            }

            if ($report == 'leadByStaff'){

                $sqlStaff = "
                SELECT s.staff_id
                      ,CONCAT_WS(' ', s.first_name, s.last_name) AS staff_name
                FROM staff s
                ORDER BY staff_name
                ";

                $rows .= "
                <td>
                    <select name='staff_id' class='leadStaffFilter'>
                        <option value=''>Select Staff</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlStaff, $staff_id)}
                    </select>
                </td>
                ";

                $rows .= "
                <td>
                    <select name='month' class='ml10 mr10 month'>
                        {$cpUtil->getDropDownFromArr($arr, $month)}
                    </select>
                </td>
                ";

                $sqlYear = "
                SELECT DISTINCT DATE_FORMAT(c.contact_date, '%Y') AS contact_date
                FROM call_registry c
                ";
                $rows .= "
                <td>
                    <select name='year' class='leadStaffYearFilter'>
                        <option value=''>Select Year</option>
                        {$dbUtil->getDropDownFromSQLCols1($db, $sqlYear, $year)}
                    </select>
                </td>
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";
			}

            if ($report == 'enquiryByStaff'){

                $sqlStaff = "
                SELECT s.staff_id
                      ,CONCAT_WS(' ', s.first_name, s.last_name) AS staff_name
                FROM staff s
                ORDER BY staff_name
                ";

                $rows .= "
                <td>
                    <select name='staff_id' class='enquiryByStaffFilter'>
                        <option value=''>Select Staff</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlStaff, $staff_id)}
                    </select>
                </td>
                ";

                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";

                $sqlYear = "
                SELECT DISTINCT DATE_FORMAT(e.follow_up_date, '%Y') AS follow_up_date
		        FROM `enquiry` e
                ";
			}

            if ($report == 'quoteByStaff'){

                $sqlStaff = "
                SELECT s.staff_id
                      ,CONCAT_WS(' ', s.first_name, s.last_name) AS staff_name
                FROM staff s
                ORDER BY staff_name
                ";

                $rows .= "
                <td>
                    <select name='staff_id' class='enquiryByStaffFilter'>
                        <option value=''>Select Staff</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlStaff, $staff_id)}
                    </select>
                </td>
                ";

                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";

                $sqlYear = "
                SELECT DISTINCT DATE_FORMAT(q.quote_date, '%Y') AS quote_date
		        FROM quote q
                ";
			}

            if ($report == 'enquiryActivityByStaff'){

                $sqlStaff = "
                SELECT s.staff_id
                      ,CONCAT_WS(' ', s.first_name, s.last_name) AS staff_name
                FROM staff s
                ORDER BY staff_name
                ";

                $rows .= "
                <td>
                    <select name='staff_id' class='enquiryActivityByStaffFilter'>
                        <option value=''>Select Staff</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlStaff, $staff_id)}
                    </select>
                </td>
                ";

                $sqlYear = "
                SELECT DISTINCT DATE_FORMAT(c.comment_date, '%Y') AS comment_date
                FROM comment c
                ";

                /*$sqlYear = "
                SELECT DISTINCT DATE_FORMAT(e.follow_up_date, '%Y') AS follow_up_date
		        FROM `enquiry` e
                ";*/
                $rows .= "
                <td>
                    <select name='year' class='enquiryActivityByStaffYearFilter'>
                        <option value=''>Select Year</option>
                        {$dbUtil->getDropDownFromSQLCols1($db, $sqlYear, $year)}
                    </select>
                </td>
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";
		    }

            /*if ($report == 'gstReport') {
                    $sqlYear = "
                    SELECT DISTINCT DATE_FORMAT(o.order_date, '%Y') AS order_year
                    FROM `order` o
                    ";

                    $sqlCompany = "
                    SELECT  c.company_id
                           ,c.company_name
                    FROM company c
                    WHERE c.category = 'Supplier'
                    AND c.company_name != ''
                    ORDER BY c.company_name ASC
                    ";

                    $rows .= "

                    <td class='dateRange'>
                        Date Range:
                        <input type='text' allowEdit='1' name='start_date' class='fld_date'
                        id='fld_start_date' value='{$start_date}' />
                        <input type='text' allowEdit='1' name='end_date' class='fld_date'
                        id='fld_end_date' value='{$end_date}' />
                    </td>
                    <td>
                        <select name='year' class='year'>
                            <option value=''>Select Year</option>
                            {$dbUtil->getDropDownFromSQLCols1($db, $sqlYear, $year)}
                        </select>
                    </td>

                    <td>
                        <select name='month' class='ml10 mr10 month'>
                            {$cpUtil->getDropDownFromArr($arr, $month)}
                        </select>
                    </td>
                    ";
            }*/

            if ($report == 'salesSummaryByProduct') {
                /*$sqlProduct = "
                SELECT p.product_id
                      ,p.title
                FROM product p
                ORDER BY p.title
                ";*/

                $sqlProduct = "
                SELECT p.product_id
                      ,p.title
                FROM order_item oi
                LEFT JOIN (`order` o) ON (oi.order_id = o.order_id)
                LEFT JOIN (`invoice` i) ON (o.order_id = i.order_id)
                LEFT JOIN product p ON (oi.record_id = p.product_id)
                WHERE o.order_status != 'Cancelled'
                ";
                $sqlSupplier = "
                SELECT s.supplier_id
                      ,s.company_name
                FROM supplier s
                ORDER BY s.supplier_id
                ";

                $current_date = date('Y-m-d');
                $current_date = date('Y-m-d');

                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$current_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$current_date}' />
                </td>
                <td>
                    <select name='product_id' class='salesSummaryByProductFilter'>
                        <option value=''>Select Product</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlProduct, $product_id)}
                    </select>
                </td>
                ";
                $rows .= "
                <td class='fieldValue'>
                    <select name='supplier_id'>
                        <option value=''>Supplier</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlSupplier, $supplier_id)}
                    </select>
                </td>                
                ";
            }

            if ($report == 'salesSummaryByProductGroup') {
                $sqlProduct = "
                SELECT p.product_group_id
                      ,p.title
                FROM product_group p
                ORDER BY p.title
                ";

                $current_date = date('Y-m-d');
                $current_date = date('Y-m-d');

                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$current_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$current_date}' />
                </td>
                <td>
                    <select name='product_group_id' class='salesSummaryByProductFilter'>
                        <option value=''>Select Product Group</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlProduct, $product_id)}
                    </select>
                </td>
                ";
            }

            if ($report == 'invoiceSummaryByProductGroup') {
                $sqlProduct = "
                SELECT p.product_group_id
                      ,p.title
                FROM product_group p
                ORDER BY p.title
                ";

                $current_date = date('Y-m-d');
                $current_date = date('Y-m-d');

                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$current_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$current_date}' />
                </td>
                <td>
                    <select name='product_group_id' class='invoiceSummaryByProductFilter'>
                        <option value=''>Select Product Group</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlProduct, $product_id)}
                    </select>
                </td>
                ";
            }

            if($report == 'detailSummaryByClient') {

                $sqlCompany ="
                SELECT c.company_id
                      ,c.company_name
                FROM company c
                WHERE category = 'Client'
                AND company_name != ''
                ORDER BY company_name ASC
                ";

                $orderGstStatus = "";
                if($cpCfg['showGstOnOff'] == 1){
                    $gst_status_array = array('GST', 'Non GST');
                    $orderGstStatus = "
                    <td class='fieldValue'>
                        <select name='gst_status'>
                            <option value=''>GST Status</option>
                            {$cpUtil->getDropDown1($gst_status_array, $gst_status)}
                        </select>
                    </td>
                    ";
                }

                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                {$orderGstStatus}
                <td>
                    <select name='company_id' class='invoiceByClientFilter'>
                        <option value=''>Select Client</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlCompany, $company_id)}
                    </select>
                </td>
                ";
            }

            if($report == 'stockReport') {

                /*$sqlSupplier = "
                SELECT c.company_id
                      ,c.company_name
                FROM company c
                WHERE c.category = 'Supplier'
                ORDER BY c.company_name
                ";

                $rows .= "
                 <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                <td>
                    <select name='company_id' class='stockReportFilter'>
                        <option value=''>Select Client</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlSupplier, $company_id)}
                    </select>
                </td>
                ";*/
                $rows .= "
                 <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";
            }

            if ($report == 'dailyCollectionReport'){

                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                <td class='fieldValue'>
                    <select name='record_type'>
                        <option value=''>POS</option>
                        {$cpUtil->getDropDown1($typeArr, $record_type)}
                    </select>
                </td>
                ";
            }


            if($report == 'priceTrackReport') {

                $sqlSection = "
                SELECT s.section_id
                      ,s.title
                FROM section s
                WHERE s.section_type = 'Product'
                ORDER BY s.section_id
                ";

                $sqlSupplier = "
                SELECT s.supplier_id
                      ,s.company_name
                FROM supplier s
                ORDER BY s.supplier_id
                ";

                
                $rows .= "
                <td class='fieldValue'>
                    <select name='section_id'>
                        <option value=''>Category</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlSection, $section_id)}
                    </select>
                </td>                
                ";

                $rows .= "
                <td class='fieldValue'>
                    <select name='supplier_id'>
                        <option value=''>Supplier</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlSupplier, $supplier_id)}
                    </select>
                </td>                
                ";

                $rows .= "
                <td class='dateRange'>
                    From Date:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    To Date:
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";
            }

            if($report == 'supplierPaymentReport') {

                $rows .= "
                 <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";
            }

            if ($report == 'gstReport'){

                $rows .= "
                <td class='dateRange'>
                    From Date:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    To Date:
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";

            }

            if ($report == 'invoiceByClient') {

                $sqlCompany = "
                SELECT  c.company_id
                       ,c.company_name
                FROM  company c
                WHERE c.category = 'Client'
                AND c.company_name != ''
                ORDER BY company_name
                ";

                $rows .= "
                <td>
                    <select name='company_id' class='invoiceByClientFilter'>
                        <option value=''>Select Client</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlCompany, $company_id)}
                    </select>
                </td>
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";
            }

            if ($report == 'vatInvoice' || $report == 'invoicesForVat') {

                $current_date = date('Y-m-d');
                $current_date = date('Y-m-d');

                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                ";
            }

            if ($report == 'detailVatPercentForInvoice') {

                $typeArr = array (
                'Quote'
               );
                
                $vatPercentTypeArr = array (
                        'POS'
                       );

                $current_date = date('Y-m-d');
                $current_date = date('Y-m-d');

                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                <td class='fieldValue'>
                    <select name='record_type'>
                        <option value=''>Quote</option>
                        {$cpUtil->getDropDown1($vatPercentTypeArr, $record_type)}
                    </select>
                </td>
                ";
            }

            if ($report == 'detailCollectionReport') {
                $rows .= "
                <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                <td class='fieldValue'>
                    <select name='order_status'>
                        <option value=''>Status</option>
                        {$cpUtil->getDropDown1($cpCfg['m.ecommerce.order.statusArr'], $order_status)}
                    </select>
                </td>
                ";
            }

            if($report == 'stockTransferReport') {
                $to_location  = $fn->getReqParam('to_location');
                $sqlstockLocation = $fn->getValueListSQL('stockLocation');       

                $rows .= "
                 <td class='dateRange'>
                    Date Range:
                    <input type='text' allowEdit='1' name='start_date' class='fld_date'
                    id='fld_start_date' value='{$start_date}' />
                    <input type='text' allowEdit='1' name='end_date' class='fld_date'
                    id='fld_end_date' value='{$end_date}' />
                </td>
                <td>
                    <select name='to_location'>
                        <option value=''>To Location</option>
                        {$dbUtil->getDropDownFromSQLCols1($db, $sqlstockLocation, $to_location)}
                    </select>
                </td>    
                ";
            }

        }

        if ($report == 'purchaseGstReport') {

            $sqlYear = "
            SELECT DISTINCT DATE_FORMAT(po.purchase_order_date, '%Y') AS Year
            FROM  purchase_order po 
            WHERE po.purchase_order_date IS NOT NULL
            ";

            $sqlSupplier = "
            SELECT  c.supplier_id 
                   ,c.company_name
            FROM supplier c
            WHERE c.company_name != ''
            ORDER BY c.company_name ASC
            ";

            $rows .= "
            <td>
                <select name='year' class='leadStaffYearFilter'>
                    <option value=''>Choose Year</option>
                    {$dbUtil->getDropDownFromSQLCols1($db, $sqlYear, $year)}
                </select>
            </td>
            ";

            $rows .= "
            <td>
                <select name='month' class='ml10 mr10 month'>
                    <option value=''>Choose Month</option>
                    {$cpUtil->getDropDownFromArr($arr, $month)}
                </select>
            </td>
            ";

            $rows .= "
            <td>
                <select name='supplier_id' class='supplierFilter'>
                    <option value=''>Select Supplier</option>
                    {$dbUtil->getDropDownFromSQLCols2($db, $sqlSupplier, $supplier_id)}
                </select>
            </td>
            ";

        }

        if ($report == 'salesGstReport') {
            $sqlYear = "
            SELECT DISTINCT DATE_FORMAT(i.invoice_date, '%Y') AS Year
            FROM  invoice i
            ";
            
            $rows .= "
            <td>
                <select name='year' class='leadStaffYearFilter'>
                    <option value=''>Choose Year</option>
                    {$dbUtil->getDropDownFromSQLCols1($db, $sqlYear, $year)}
                </select>
            </td>
            ";

            $rows .= "
            <td>
                <select name='month' class='ml10 mr10 month'>
                    <option value=''>Choose Month</option>
                    {$cpUtil->getDropDownFromArr($arr, $month)}
                </select>
            </td>
            ";

        }

        $hiddenDateField = "<input type=text class=hiddenDateDisplay name=hidden_date_display>";
        $text = "
        <form id='reportSearch'>
        <table class='search'>
            <tr>
                <td class='resetLink'><a href='javascript:void(0);' onClick=\"javascript:$('#reportSearch').clearForm();\">reset</a></td>
                {$rows}
                <td>
                    <input type='hidden' name='report' value='{$report}'>
                    <input type='hidden' id='reportName' value='{$report}'>
                    <input type='submit' value='GO' class='button'>
                </td>
            </tr>
        </table>
        </form>
        <script>
            $(function() {
                $('#reportSearchPanel table.search td.dateRange input.fld_date').each(function(){
                    var inputname = $(this).attr('name');
                    var image = $(this).closest('ui-datepicker-trigger');
                    var dateValue = $(this).val();
                    $(this).addClass('MainDateField');
                    $(this).before('{$hiddenDateField}');
                });

                // Call the function on each input
                $('.hiddenDateDisplay[data-onload]').each(function() {
                    var dateCheck = $(this).attr('data-onload');
                    
                    if(dateCheck != '') {
                        var date      = dateCheck.replace(/-/g, '/');
                        var newdate   = new Date(date);
                        var dd = ('0' + newdate.getDate()).slice(-2);
                        var mm = ('0' + (newdate.getMonth() + 1)).slice(-2)
                        var y  = newdate.getFullYear();
             
                        var endDate = dd + '-'+ mm + '-' + y;
                    }else {
                        var endDate = '';
                    }

                    $(this).val(endDate);
                });
            });
        </script>
        ";

        return $text;
    }

    function getDisplayReport($text){
        $fn = Zend_Registry::get('fn');
        $pager = Zend_Registry::get('pager');
        $report = $fn->getReqParam('report');

        $searchQueryString = $pager->removeQueryString(array("_spAction"));
        $exportLink = "{$searchQueryString}&_spAction=exportData&report={$report}&showHTML=0";

        $text = "
        <div>
            <a href='{$exportLink}' class='exportLink button'>
                <u1>Export to Excel</u1>
            </a>
            {$text}
        </div>
        ";

        return $text;

        $json = array();
        $json['html'] = $text;

        return json_encode($json);
    }


    /**
     *
     */
    function getMonthFilterValues() {
        return "
        <option value=''>Month Filter</option>
        <option value='01'>January</option>
        <option value='02'>February</option>
        <option value='03'>March</option>
        <option value='04'>April</option>
        <option value='05'>May</option>
        <option value='06'>June</option>
        <option value='07'>July</option>
        <option value='08'>August</option>
        <option value='09'>September</option>
        <option value='10'>October</option>
        <option value='11'>November</option>
        <option value='12'>December</option>
        ";
    }

}