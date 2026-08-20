<?
class CPL_Admin_Modules_Tradingsg_Quote_View extends CP_Admin_Modules_Tradingsg_Quote_View
{
    /**
     *
     */
    function getPrintQuoteGeneralTrading() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $media = Zend_Registry::get('media');

        //-----------------------------------------------------------------//
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/tbs_class.php');
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/plugins/tbs_plugin_opentbs.php');
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/plugins/tbs_plugin_html.php');

        $TBS = new clsTinyButStrong;
        $TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);

        $quote_id  = $fn->getReqParam('id');
        $template = 'Quote-General-Trading.xlsx';
        $templatePath = $cpCfg['cp.localPath'].'lib/template/' . $template;
        $TBS->LoadTemplate($templatePath);
        $rnd_no = mt_rand();
        $file_name = 'Quote-Product_' . $quote_id . '_' . $rnd_no;
        $file_name = str_replace('.', '_' . date('Y-m-d') . '.', $file_name);

        $path = realpath($cpCfg['cp.mediaFolder']) . '\temp';
        $file_name_save = $path . '\\' . $file_name;
        $sourceFilePath = $file_name_save;
        $today =  date('d/m/Y');
		$gsttaxvalue = $cpCfg['amtForGSTCalc'] ;

        //TO CHECK IF THE SUM OF DISCOUNT TYPE(%) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
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
              ,p.unit
              ,p.item_code
              ,p.part_number
              ,q.quote_code
              ,q.quote_date
              ,q.payment_terms
              ,c.company_name
              ,c.address_flat
              ,c.address_street
              ,c.address_town
              ,c.address_state
              ,c.address_country
              ,c.customer_type
              ,(SELECT SUM(qph.qty * qph.selling_price) FROM  quote_product qph
               WHERE qph.quote_id = qp.quote_id) AS total
              ,(SELECT
               ($subSqlForPercentSum)
                +
               ($subSqlForValueSum)
               )
               as discount_percentage_amount_sum
        FROM quote_product qp
        LEFT JOIN product p ON (p.product_id = qp.product_id)
        LEFT JOIN quote q ON (q.quote_id = qp.quote_id)
        LEFT JOIN company c ON (c.company_id = q.company_id)
        WHERE q.quote_id = {$quote_id}
        ";
        $result = $db->sql_query($SQL);

        $serialNo       = 1;
        $arr            = array();
        $blkMain        = array();
        $blkProduct     = array();
        $blkQty         = array();
        $blkUom         = array();
        $blkPrice       = array();
        $blkSerialNo    = array();
        $blkAmount    = array();
        $selling_price = '';

        while ($row = $db->sql_fetchrow($result)) {
            //repeating rows of product values
            $arr1 = array('product_title' => $row['product_title']);
            $blkProduct[] = $arr1;

            $arr2 = array('qty' => $row['qty']);
            $blkQty[] = $arr2;

            $arr3 = array('serial_no' => $serialNo);
            $blkSerialNo[] = $arr3;

            $arr4 = array('unit' => $row['unit']);
            $blkUom[] = $arr4;

            if($row['mark_up_type'] == '%'){
                $selling_price = $row['cost_price'] + ($row['cost_price'] * ($row['mark_up']/100));
            }
            else if($row['mark_up_type'] == 'Value'){
                $selling_price = $row['cost_price']  + $row['mark_up'];
            } else {
                $selling_price = $row['cost_price'];
            }

            $arr5 = array('selling_price' => number_format($selling_price,2));
            $blkPrice[] = $arr5;

            $arr6 = array('amount' => number_format($selling_price * $row['qty'], 2));
            $blkAmount[] = $arr6;

            $arr7 = array('item_code' => $row['item_code']);
            $blkItemCode[] = $arr7;

            $arr8 = array('part_number' => $row['part_number']);
            $blkPartNumber[] = $arr8;

            $quote_date   = $fn->getCPDate($row['quote_date'], 'd-m-Y');

            //Header Part and Total/subtotal
            $arr['quote_code']   = $row['quote_code'];
            $arr['company_name'] = $row['company_name'];
            $arr['address_flat'] = $row['address_flat'];
            $arr['address_street'] = $row['address_street'];
            $arr['address_town'] = $row['address_town'];
            $arr['address_state'] = $row['address_state'];
            $arr['address_country'] = $row['address_country'];
            $arr['quote_date'] = $quote_date;
            $arr['sub_total'] = number_format($row['total'] + $row['discount_percentage_amount_sum'], 2);
            $arr['payment_terms'] = $row['payment_terms'];
            $arr['discount'] = $row['discount_percentage_amount_sum'];

            //$tax =  ($row['sub_total'] * $gsttaxvalue)/100;
            //$arr['tax'] =  number_format($tax, 2);
            $arr['total'] =  number_format($row['total'], 2);
            $blkMain[] = $arr;

            $serialNo++;
        }

        $TBS->MergeBlock('blkMain', $blkMain);
        $TBS->MergeBlock('blkProduct', $blkProduct);
        $TBS->MergeBlock('blkQty', $blkQty);
        $TBS->MergeBlock('blkSerialNo', $blkSerialNo);
        $TBS->MergeBlock('blkItemCode', $blkItemCode);
        $TBS->MergeBlock('blkUom', $blkUom);
        $TBS->MergeBlock('blkPrice', $blkPrice);
        $TBS->MergeBlock('blkAmount', $blkAmount);
        $TBS->MergeBlock('blkPartNumber', $blkPartNumber);
        $TBS->Show(OPENTBS_DOWNLOAD, $file_name);
    }

    /**
     *
     */
    function getPrintPurchaseOrder() {
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
        include_once(CP_LIBRARY_PATH.'lib_php/fpdf-extra/html_table1.php');

        $pdf = new MYPDF();

		$pdf->AddPage();
		$pdf->SetFont('Arial','',11);

        $purchase_order_id = $fn->getReqParam('id');

        $SQL = "
        SELECT pop.*
              ,p.title AS product_title
              ,p.unit
              ,p.item_code
              ,c.company_name
              ,c.fax
              ,c.phone
              ,pop.creation_date
              ,po.po_code
              ,q.quote_code
              ,q.delivery_date
              ,q.delivery_location
        FROM po_product pop
        LEFT JOIN product p ON (p.product_id = pop.product_id)
        LEFT JOIN company c ON (c.company_id = pop.supplier_id)
        LEFT JOIN quote q ON (q.quote_id = pop.quote_id)
        LEFT JOIN purchase_order po ON (po.purchase_order_id = pop.purchase_order_id)
        LEFT JOIN product_group pg ON (p.product_group_id = pg.product_group_id)
        WHERE pop.purchase_order_id = {$purchase_order_id}
        ORDER BY pg.sort_order ASC, p.title
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

        //============================================================================= //
        $pdf->SetFont('Arial','',11);
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
                $creationDate = $fn->getCPDate($row['creation_date'], 'd-m-Y');
                $deliveryDate = $fn->getCPDate($row['delivery_date'], 'd-m-Y');

                /* Company address */
                //Address to be got from settings
                /*
                $pdf->Cell(50, 20, '25 LORONG 39 GEYLANG SINGAPORE 387875');
                $pdf->Ln(5);
                $pdf->SetXY(140,5);

                $pdf->Cell(50, 20, 'TEL: +65 674 74 126 FAX: +65 674 84 322');
                $pdf->Ln(5);
                $pdf->SetXY(140,10);

                $pdf->Cell(50, 20, 'EMAIL: enquiry@novo-ship-supplies.com.sg');
                $pdf->Ln(5);
                $pdf->SetXY(140, 15);

                $pdf->Cell(50, 20, 'WEBSITE: www.novo-ship-supplies.com.sg');
                $pdf->Ln(5);
                $pdf->SetXY(140,20);
                $pdf->Cell( 50, 20, 'GST REG. NO: 201203469M');
                */
                $pdf->SetXY(140,0);
                $pdf->SetFont('Courier','B',11);
                $pdf->Cell(50, 20, $cpCfg['cp.companyName']);
                $pdf->Ln(5);
                $pdf->SetXY(140,5);
                $pdf->SetFont('Courier','',8);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf1']);
                $pdf->Ln(5);
                $pdf->SetXY(140,10);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf2']);
                $pdf->Ln(5);
                $pdf->SetXY(140,15);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf3']);
                $pdf->Ln(5);
                $pdf->SetXY(140, 20);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf4']);
                $pdf->Ln(5);
                $pdf->SetXY(140,25);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf5']);

                /* Header */
                $pdf->SetFont('Courier','BU',11);
                $pdf->SetXY(100, 35);
                $pdf->Cell(21, 20, "PURCHASE ORDER", 0, 0, 'C');
                $pdf->Ln(20);


                /* Company Details*/
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(100,8,"VENDOR",1,0, 'L', 1);
                $pdf->Cell(45,8,"TEL",1,0, 'L', 1);
                $pdf->Cell(45,8,"FAX",1,0, 'L', 1);
                $pdf->Ln();
                $pdf->SetFillColor(255,255,255);
	            $pdf->Cell(100, 8, $row['company_name'], 1, 0, 'L', 1);
	            $pdf->Cell(45, 8, $row['phone'], 1, 0, 'L', 1);
	            $pdf->Cell(45, 8, $row['fax'], 1, 0, 'L', 1);
                $pdf->Ln(20);

			    $quoteCode = $row['quote_code'];
				$formatedQC = explode("-", $quoteCode);

                /* Purchase Details*/
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(70,8,"QUOTE CODE",1,0, 'L', 1);
                $pdf->Cell(30,8,"PO DATE",1,0, 'L', 1);
                $pdf->Cell(30,8,"PO CODE",1,0, 'L', 1);
                $pdf->Cell(60,8,"DELIVERY DATE",1,0, 'L', 1);
                $pdf->Ln();
                $pdf->SetFillColor(255,255,255);
	            //$pdf->Cell(70, 8, $formatedQC[3], 1, 0, 'L', 1);
	            $pdf->Cell(70, 8, $quoteCode, 1, 0, 'L', 1);
	            $pdf->Cell(30, 8, $creationDate, 1, 0, 'L', 1);
	            $pdf->Cell(30, 8, $row['po_code'], 1, 0, 'L', 1);
	            $pdf->Cell(60, 8, $deliveryDate, 1, 0, 'L', 1);
                $pdf->Ln();
                $pdf->Cell(190,8,"LOCATION: {$row['delivery_location']}",1,0, 'L', 1);
                //$pdf->Cell(30,8,"TIME: AM(TBC)",1,0, 'L', 1);
                //$pdf->Cell(33,8,"DELIVERY DATE : ",'TBL',0, 'L', 1);
				//$pdf->Cell(27,8, $deliveryDate, 'TBR', 0, 'L', 1);
                $pdf->Ln(10);

				$pdf->Cell(30,15,"(Note : Please mention the exact Item Code for all the products.)",0,0, 'L', 1);
                $pdf->Ln(10);

                /* List of order items header */
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(25,8,"ITEM CODE",1,0, 'C', 1);
                $pdf->Cell(145,8,"NAME OF THE ITEM",1,0, 'C', 1);
                $pdf->Cell(10,8,"QTY",1,0, 'C', 1);
                $pdf->Cell(10,8,"UOM",1,0, 'C', 1);
                $pdf->Ln();
            }

            //===================================MAIN TABLE============================= //
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(25, 8, $row['item_code'], 1, 0, 'L', 1);
            //$pdf->Cell(145, 8, substr($row['product_title'], 0, 61), 1, 0, 'L', 1);
            $pdf->Cell(145, 8, $row['product_title'], 1, 0, 'L', 1);
            $pdf->Cell(10, 8, $row['qty'], 1, 0, 'R', 1);
            $pdf->Cell(10, 8, $row['unit'], 1, 0, 'R', 1);
            $pdf->Ln(25);

            $count++;
            $lineItemNumber++;
        }

	        /* Best Regards & Engex Power */
            $pdf->Cell(55, 5, $cpCfg['printBestRegards']);
	        $pdf->SetX(10);
            $pdf->Cell(55, 16, $cpCfg['printEngexPower']);

	        /* Creation of media record of the invoice */
	        $file_name = 'Refund_REF_' . date('Y-m-d') .'.pdf';
	        $outputPath = realpath($cpCfg['cp.mediaFolder']) . '/temp';

	        $outputFileName = $outputPath . '/' . $file_name;
	        //$pdf->Output($outputFileName , "F");
			$pdf->Output();

    }

    /**
     *
     */
    function getPrintPurchaseOrderWithPrice() {
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
        include_once(CP_LIBRARY_PATH.'lib_php/fpdf-extra/html_table1.php');

        $pdf = new MYPDF();

				$pdf->AddPage();
				$pdf->SetFont('Arial','',11);

        $purchase_order_id = $fn->getReqParam('id');

        $SQL = "
        SELECT pop.*
              ,p.title AS product_title
              ,p.unit
              ,p.item_code
              ,c.company_name
              ,c.fax
              ,c.phone
              ,pop.creation_date
              ,q.delivery_date
              ,q.delivery_location
              ,pop.price
              ,po.po_code
              ,q.quote_code
              ,q.currency
              ,pop.qty * pop.price AS amount
              ,(SELECT SUM(popp.qty * popp.price) FROM po_product popp
               WHERE popp.purchase_order_id = pop.purchase_order_id) AS sub_total
        FROM po_product pop
        LEFT JOIN product p ON (p.product_id = pop.product_id)
        LEFT JOIN company c ON (c.company_id = pop.supplier_id)
        LEFT JOIN quote q ON (q.quote_id = pop.quote_id)
        LEFT JOIN purchase_order po ON (po.purchase_order_id = pop.purchase_order_id)
        LEFT JOIN product_group pg ON (p.product_group_id = pg.product_group_id)
        WHERE pop.purchase_order_id = {$purchase_order_id}
        ORDER BY pg.sort_order ASC, p.title
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


        //============================================================================= //
        $pdf->SetFont('Arial','',11);
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
                $creationDate = $fn->getCPDate($row['creation_date'], 'd-m-Y');
                $deliveryDate = $fn->getCPDate($row['delivery_date'], 'd-m-Y');

				$printTaxName = $cpCfg['printTaxName'] ;
				$gsttaxvalue = $cpCfg['amtForGSTCalc'] ;
				$gstvalue = $row['sub_total'] * $gsttaxvalue / 100;
				$totalvalue = $gstvalue + $row['sub_total'];

                /* Company address */
                //Address to be got from settings
                /*
                $pdf->Cell(50, 20, '25 LORONG 39 GEYLANG SINGAPORE 387875');
                $pdf->Ln(5);
                $pdf->SetXY(140,5);
                $pdf->Cell(50, 20, 'TEL: +65 674 74 126 FAX: +65 674 84 322');
                $pdf->Ln(5);
                $pdf->SetXY(140,10);
                $pdf->Cell(50, 20, 'EMAIL: enquiry@novo-ship-supplies.com.sg');
                $pdf->Ln(5);
                $pdf->SetXY(140, 15);
                $pdf->Cell(50, 20, 'WEBSITE: www.novo-ship-supplies.com.sg');
                $pdf->Ln(5);
                $pdf->SetXY(140,20);
                $pdf->Cell( 50, 20, 'GST REG. NO: 201203469M');
                */
                $pdf->SetXY(140,0);
                $pdf->SetFont('Courier','B',11);
                $pdf->Cell(50, 20, $cpCfg['cp.companyName']);
                $pdf->Ln(5);
                $pdf->SetXY(140,5);
                $pdf->SetFont('Courier','',8);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf1']);
                $pdf->Ln(5);
                $pdf->SetXY(140,10);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf2']);
                $pdf->Ln(5);
                $pdf->SetXY(140,15);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf3']);
                $pdf->Ln(5);
                $pdf->SetXY(140, 20);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf4']);
                $pdf->Ln(5);
                $pdf->SetXY(140,25);
                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf5']);

                /* Header */
                $pdf->SetFont('Courier','BU',11);
                $pdf->SetXY(100, 35);
                $pdf->Cell(21, 20, "PURCHASE ORDER WITH PRICE", 0, 0, 'C');
                $pdf->Ln(20);


                /* Company Details*/
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(100,8,"VENDOR",1,0, 'L', 1);
                $pdf->Cell(45,8,"TEL",1,0, 'L', 1);
                $pdf->Cell(45,8,"FAX",1,0, 'L', 1);
                $pdf->Ln();
                $pdf->SetFillColor(255,255,255);
	            $pdf->Cell(100, 8, $row['company_name'], 1, 0, 'L', 1);
	            $pdf->Cell(45, 8, $row['phone'], 1, 0, 'L', 1);
	            $pdf->Cell(45, 8, $row['fax'], 1, 0, 'L', 1);
                $pdf->Ln(20);

				$currency = $row['currency'];
				$quoteCode = $row['quote_code'];
				$formatedQC = explode("-", $quoteCode);

                /* Purchase Details*/
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(70,8,"QUOTE CODE",1,0, 'L', 1);
                $pdf->Cell(30,8,"PO DATE",1,0, 'L', 1);
                $pdf->Cell(30,8,"PO CODE",1,0, 'L', 1);
                $pdf->Cell(60,8,"DELIVERY DATE",1,0, 'L', 1);
                $pdf->Ln();
                $pdf->SetFillColor(255,255,255);
	            //$pdf->Cell(70, 8, $formatedQC[3], 1, 0, 'L', 1);
	            $pdf->Cell(70, 8, $quoteCode, 1, 0, 'L', 1);
	            $pdf->Cell(30, 8, $creationDate, 1, 0, 'L', 1);
	            $pdf->Cell(30, 8, $row['po_code'], 1, 0, 'L', 1);
	            $pdf->Cell(60, 8, $deliveryDate, 1, 0, 'L', 1);
                $pdf->Ln();
                $pdf->Cell(190,8,"LOCATION: {$row['delivery_location']}",1,0, 'L', 1);
                //$pdf->Cell(30,8,"TIME: AM(TBC)",1,0, 'L', 1);
                //$pdf->Cell(33,8,"DELIVERY DATE : ",'TBL',0, 'L', 1);
				//$pdf->Cell(27,8, $deliveryDate, 'TBR', 0, 'L', 1);
                $pdf->Ln(10);


				$pdf->Cell(30,15,"(Note : Please mention the exact Item Code for all the products.)",0,0, 'L', 1);
                $pdf->Ln(10);

                /* List of order items header */
                $pdf->SetFont('Courier','B',11);
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(17,8,"ITEM NO",1,0, 'C', 1);
                $pdf->Cell(24,8,"ITEM CODE",1,0, 'C', 1);
                $pdf->Cell(79,8,"NAME OF THE ITEM",1,0, 'C', 1);
                $pdf->Cell(10,8,"QTY",1,0, 'C', 1);
                $pdf->Cell(10,8,"UOM",1,0, 'C', 1);
                $pdf->Cell(20,8,"UP",1,0, 'C', 1);
                $pdf->Cell(30,8,"AMOUNT(" . $row['currency'] . ")",1,0, 'C', 1);
                $pdf->Ln();
            }

            //===================================MAIN TABLE============================= //
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(17, 8, $lineItemNumber, 1, 0, 'C', 1);
            $pdf->Cell(24, 8, $row['item_code'], 1, 0, 'L', 1);
            //$pdf->Cell(91, 8, substr($row['product_title'], 0, 61), 1, 0, 'L', 1);
            $pdf->Cell(79, 8, $row['product_title'], 1, 0, 'L', 1);
            $pdf->Cell(10, 8, $row['qty'], 1, 0, 'R', 1);
            $pdf->Cell(10, 8, $row['unit'], 1, 0, 'R', 1);
            $pdf->Cell(20, 8, $row['price'], 1, 0, 'R', 1);
            $pdf->Cell(30, 8, $row['amount'], 1, 0, 'R', 1);
            $pdf->Ln();

            $count++;
            $lineItemNumber++;
            $sub_total = $row['sub_total'];
        }
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(160, 8, "SUB TOTAL {$currency}", 1, 0, 'R', 1);
            $pdf->Cell(30, 8, $sub_total, 1, 0, 'R', 1);
            $pdf->Ln();

            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(160, 8, "ADD: {$printTaxName} {$gsttaxvalue}%", 1, 0, 'R', 1);
            $pdf->Cell(30, 8, number_format($gstvalue, 2), 1, 0, 'R', 1);
            $pdf->Ln();

            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(160, 8, 'TOTAL', 1, 0, 'R', 1);
            $pdf->Cell(30, 8, number_format($totalvalue, 2), 1, 0, 'R', 1);
			$pdf->Ln(28);

	        /* Best Regards & Engex Power */
            $pdf->Cell(55, 5, $cpCfg['printBestRegards']);
	        $pdf->SetX(10);
            $pdf->Cell(55, 16, $cpCfg['printEngexPower']);

	        /* Creation of media record of the invoice */
	        $file_name = 'Refund_REF_' . date('Y-m-d') .'.pdf';
	        $outputPath = realpath($cpCfg['cp.mediaFolder']) . '/temp';

	        $outputFileName = $outputPath . '/' . $file_name;
	        //$pdf->Output($outputFileName , "F");
			$pdf->Output();

    }
     /**
     *
     */
    function getEdit($row){
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');
        $ln = Zend_Registry::get('ln');
        $am = Zend_Registry::get('am');
        $comment = getCPPluginObj('common_comment');

        $record_id = $fn->getIssetParam($row, 'quote_id');

        $fnsModGrp = includeCPClass('ModGroup', 'Trading', 'Functions');

        $formObj->mode = $tv['action'];
        $modContact = getCPModuleObj('trading_contact');

        $modContact = getCPModuleObj('core_staff');
        $sqlSalesManager = $modContact->model->getStaffByGroupSQL();

        $fnsModQuote = includeCPClass('ModuleFns', 'trading_quote');

        $sqlCurrency = $fn->getValueListSQL('currency');
        $sqlPriority = $fn->getValuelistSql('quotePriority');
        $sqlDeliveryLocation = $fn->getValuelistSql('deliveryLocation');
        $expVl      = array('sqlType' => 'OneField');
        $expNoEdit  = array('isEditable' => 0);
        $validatedClient = '';

        if ($row['staff_id'] == '') {
            $staff_name = $_SESSION['staff_id'];
        } else {
            $staff_name = $row['staff_id'];
        }

        $expStaff   = array('detailValue' => $row['staff_name'], 'isEditable' => 0);

        $sqlContact = '';
        if($row['company_id'] != '') {
            $sqlContact = "
            SELECT contact_id
                  ,CONCAT_WS(' ', first_name, last_name ) AS contact_name
            FROM contact
            WHERE company_id = {$row['company_id']}
            ";
        }
        $expContact = array('detailValue' => $row['contact_name']);

        $sqlCompany = "
        SELECT company_id, company_name FROM company
        WHERE category = 'Client'
        ORDER BY company_name
        ";
        $expComp = array('detailValue' => $row['company_name']);

		$validatedClient =	"{$formObj->getTBRow('Title', 'title', $row['title'])}
							 {$formObj->getDDRowBySQL('Client Name*', 'company_id', $sqlCompany, $row['company_id'], $expComp)}
							 {$formObj->getDDRowBySQL('Contact Person', 'contact_id', $sqlContact, $row['contact_id'], $expContact)}
			        		 {$formObj->getDDRowByArr('Quote Status', 'status', $cpCfg['m.trading.product.quoteProductStatusArr'], $row['status'])}
			        		 {$formObj->getDDRowBySQL('Priority', 'priority', $sqlPriority, $row['priority'], $expVl)}
			        		 {$formObj->getDDRowBySQL('Delivery Location', 'delivery_location', $sqlDeliveryLocation, $row['delivery_location'], $expVl)}
			        		 {$formObj->getDateRow('Delivery Date', 'delivery_date', $row['delivery_date'])}
			        		 {$formObj->getDateRow('Quote Date', 'quote_date', $row['quote_date'])}
			        		 {$formObj->getDateRow('Follow up Date', 'follow_up_date', $row['follow_up_date'])}
			        		 {$formObj->getDDRowBySQL('Currency', 'currency', $sqlCurrency, $row['currency'], $expVl)}
							";

        //$summaryDisplay = $this->getSummaryDisplayGeneralTrading($row);
        $summaryDisplay = '';

        $enquiryCode = '';
        if($row['enquiry_id'] != '') {
            $enquiryRecord = $fn->getRecordRowByID('enquiry', 'enquiry_id', $row['enquiry_id']);
            $enquiryCode = "{$formObj->getTBRow('Enquiry Code', 'enquiry_code', $enquiryRecord['enquiry_code'], $expNoEdit)}";
        }

        $fieldset1 = "
		{$formObj->getTBRow('Quotation Code', 'quote_code', $row['quote_code'], $expNoEdit)}
		{$enquiryCode}
		{$validatedClient}
   		{$formObj->getTARow('Payment Terms', 'payment_terms', $row['payment_terms'])}
   		{$formObj->getTARow('Delivery Terms', 'delivery_terms', $row['delivery_terms'])}
   		{$formObj->getTARow('Notes ', 'note', $row['note'])}
		{$formObj->getDDRowBySQL('Staff', 'staff_id', $sqlSalesManager, $staff_name, $expStaff)}
		{$formObj->getTBRow('Quote Type', 'quote_type', $row['quote_type'], $expNoEdit)}
		";

        $text = "
        <div class='summary'>
		    <div class='c50l'>
		    <div class='subcl'>
	        <div class='linkPortalWrapper'>
	            <div class='header'>
	                <div class='floatbox'>
	                    <div class='float_left' style='font-size:125%;'>Quote Header</div>
	                    <div class='toggle'> </div>
	                </div>
	            </div>
	            <div>
	                <div class='linkPortalDataWrapper'>
	                    {$formObj->getFieldSetWrapped('', $fieldset1)}
	                </div>
	            </div>
	        </div>
        	{$formObj->getCreationModificationText($row)}
	        </div>
	        </div>
	        <div class='c50r'>
    	        <div class='subcr'>
    	            {$summaryDisplay}
    	        </div>
	        </div>
        </div>

        ";

        return $text;
    }
    /**
     *
     */
    function getSummaryDisplayGeneralTrading($row){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        $companyRec = $fn->getRecordRowById('company', 'company_id', $row['company_id']);

        //TO CHECK IF THE SUM OF MARK UP TYPE(%) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForPercentSum = "
        SELECT SUM(round(((qp.cost_price * qp.mark_up)/100)* qp.qty,2)) as mark_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$row['quote_id']}
            AND qp.mark_up_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['mark_sum'] > 0){
            $subSqlForPercentSum = "
            SELECT SUM(round(((qp.cost_price * qp.mark_up)/100)* qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$row['quote_id']}
                AND qp.mark_up_type = '%'
            ";
        }
        else{
            $subSqlForPercentSum = 0;
        }

        //TO CHECK IF THE SUM OF MARK UP TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForValueSum ="
        SELECT SUM(round(qp.mark_up * qp.qty,2)) as mark_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$row['quote_id']}
            AND qp.mark_up_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlForValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['mark_sum'] > 0){
            $subSqlForValueSum ="
            SELECT SUM(round(qp.mark_up * qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$row['quote_id']}
                AND qp.mark_up_type = 'Value'
            ";
        }
        else{
            $subSqlForValueSum = 0;
        }

        //TO CHECK IF THE SUM OF DISCOUNT TYPE(%) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
        $subSqlForDiscPercentSum = "
        SELECT SUM(round(((qp.cost_price * qp.discount_percentage)/100)* qp.qty,2)) as discount_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$row['quote_id']}
            AND qp.discount_type = '%'
        ";
        $resultSubSql = $db->sql_query($subSqlForDiscPercentSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlDiscForPercentSum = "
            SELECT SUM(round(((qp.cost_price * qp.discount_percentage)/100)* qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$row['quote_id']}
                AND qp.discount_type = '%'
            ";
        }
        else{
            $subSqlDiscForPercentSum = 0;
        }

        $subSqlDiscForValueSum ="
        SELECT SUM(round(qp.discount_percentage * qp.qty,2)) as discount_sum
        FROM quote_product qp
        WHERE qp.quote_id = {$row['quote_id']}
            AND qp.discount_type = 'Value'
        ";
        $resultSubSql = $db->sql_query($subSqlDiscForValueSum);
        $rowSql       = $db->sql_fetchrow($resultSubSql);
        if($rowSql['discount_sum'] > 0){
            $subSqlDiscForValueSum ="
            SELECT SUM(round(qp.discount_percentage * qp.qty,2))
            FROM quote_product qp
            WHERE qp.quote_id = {$row['quote_id']}
                AND qp.discount_type = 'Value'
            ";
        }
        else{
            $subSqlDiscForValueSum = 0;
        }



        //discount value need not be subracted from selling price as below.
        $SQLQuoteProd = "
        SELECT (SUM(qp.selling_price * qp.qty)) AS total_selling_price
              ,(SUM(qp.cost_price * qty)) AS total_cost_price
              ,(count(qp.qty)) AS total_count
              ,(SELECT
              ($subSqlForPercentSum)
               +
              ($subSqlForValueSum)
               )
               as total_mark_up
              ,(SELECT
              ($subSqlDiscForPercentSum)
               +
              ($subSqlDiscForValueSum)
               )
               as total_discount
        FROM quote_product qp
        WHERE qp.quote_id = {$row['quote_id']}
        ";
        $resultQuoteProd = $db->sql_query($SQLQuoteProd);
        $rowQuoteProd    = $db->sql_fetchrow($resultQuoteProd);

        $total_selling_price = number_format($rowQuoteProd['total_selling_price'], 2);
        $total_cost_price    = number_format($rowQuoteProd['total_cost_price'], 2);

        /*
        $total_discount_percentage = 0;
        if ($rowQuoteProd['total_count']){
            $total_discount_percentage = $rowQuoteProd['total_discount_percentage'] / $rowQuoteProd['total_count'];
        }
        */
        //$total_discount_value = ($total_selling_price * $total_discount_percentage)/100;
        $total_discount_value = $rowQuoteProd['total_discount'];

        $SQLExpense = "
        SELECT (SUM(amount)) AS total_amount
        FROM expense
        WHERE quote_id = {$row['quote_id']}
        ";
        $resultExpense = $db->sql_query($SQLExpense);
        $rowExpense    = $db->sql_fetchrow($resultExpense);


        /* Total of Items before Customer Type Discount */
        /*
        $total_profit =   $rowQuoteProd['total_selling_price']
                        - $rowQuoteProd['total_cost_price'];
        $total_profit_format = number_format($total_profit, 2);
        */

        $total_discount_format = 0;

        $total_discount_format = number_format($total_discount_value, 2);

        //$total_profit = $total_profit - $total_discount_format;
        $total_profit = $rowQuoteProd['total_mark_up'];
        $total_profit_format = number_format($total_profit, 2);
	    $printTaxName = $cpCfg['printTaxName'] ;

        $text = "
        <div class='linkPortalWrapper'>
        <div class='header'>
            <div class='floatbox'>
                <div class='float_left'>Summary</div>
                <div class='toggle'> </div>
            </div>
        </div>

        <div>
            <div class='linkPortalDataWrapper'>
                <table class='thinlist mb10'>
                    <thead>
                        <th>Line Items</th>
                        <th class='txtRight'>Amount</th>
                    </thead>

                    <tr>
                        <td>Status</td>
                        <td class='txtRight'>{$row['status']}</td>
                    </tr>

                    <tr>
                        <td>Total Selling Price</td>
                        <td class='txtRight'>{$total_selling_price}</td>
                    </tr>

                    <tr>
                        <td>Total Cost Price</td>
                        <td class='txtRight'>{$total_cost_price}</td>
                    </tr>

                    <tr>
                        <td>Other Expense</td>
                        <td class='txtRight'>{$rowExpense['total_amount']}</td>
                    </tr>

                    <tr>
                        <td>Total Discount</td>
                        <td class='txtRight'>{$total_discount_format}</td>
                    </tr>

                    <tr>
                        <td>Total Profit</td>
                        <td class='txtRight'>{$total_profit_format}</td>
                    </tr>

                    <tr>
                        <td>Prepared By</td>
                        <td class='txtRight'></td>
                    </tr>
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
    function getRightPanel($row){
        $fn = Zend_Registry::get('fn');
        $media = Zend_Registry::get('media');
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $cpCfg = Zend_Registry::get('cpCfg');
        $db = Zend_Registry::get('db');
        $comment = getCPPluginObj('common_comment');

        if($cpCfg['m.tradingsg.quote.printQuoteGeneral']){
            $printText ="";
            $urlQuote = "index.php?module=tradingsg_quote&_spAction=printQuoteExcelGeneral&id={$row['quote_id']}&showHTML=0";
        }
        else{
            $printText ="";
            $urlQuote = "index.php?module=tradingsg_quote&_spAction=printQuoteExcel&id={$row['quote_id']}&showHTML=0";
        }
        $urlRaisePo = "index.php?module=tradingsg_quote&_spAction=raisePurchaseOrder&id={$row['quote_id']}&showHTML=0";

        if ($cpCfg['m.tradingsg.quote.printQuoteGeneralTrading'] == 1) {
            $urlQuoteGeneral = "index.php?module=tradingsg_quote&_spAction=printQuoteGeneralTrading&id={$row['quote_id']}&showHTML=0";
        } else {
            $urlQuoteGeneral = "index.php?module=tradingsg_quote&_spAction=printQuoteExcelBasic&id={$row['quote_id']}&showHTML=0";
        }

        $formActionCategory = "index.php?_topRm=order&module=tradingsg_quote&_spAction=updateMarkupByCategoryForm&quote_id={$row['quote_id']}&showHTML=0";
        $formActionGroup = "index.php?_topRm=order&module=tradingsg_quote&_spAction=updateMarkupByGroupForm&quote_id={$row['quote_id']}&showHTML=0";
        $formActionDiscGroup = "index.php?_topRm=order&module=tradingsg_quote&_spAction=updateDiscountByGroupForm&quote_id={$row['quote_id']}&showHTML=0";
        $formActionUpdateDiscount = "index.php?_topRm=order&module=tradingsg_quote&_spAction=updateDiscountForm&quote_id={$row['quote_id']}&showHTML=0";
        $bulkGenerateUrl  = "index.php?module=tradingsg_quote&_spAction=generateBulkProduct&id={$row['quote_id']}&showHTML=0";

        $updateDiscountByGroup = '';
        if ($cpCfg['m.tradingsg.quote.updateDiscountByGroup'] == 1) {
        	$updateDiscountByGroup = "
            <div class='button mb5'>
	            <a href='{$formActionDiscGroup}' id='updateDiscountByGroupForm'>Update Discount by Department</a>
            </div>
            ";
        }


        if ($cpCfg['m.tradingsg.quote.showExportExcellC2']) {
            $ExportToExcellC2 ="
            <div class='button mb5'>
                <a href='{$urlQuote}' id='print'>Export to Excel C2</a>
            </div>
            ";
        }

        if ($cpCfg['m.tradingsg.quote.printQuoteGeneralTrading'] == 1) {
            $ExportToExcellC1 ="
            <div class='button mb5'>
                <a href='{$urlQuoteGeneral}' id='print'>Export to Excel</a>
            </div>
            ";
        } else {
            $ExportToExcellC1 ="
            <div class='button mb5'>
                <a href='{$urlQuoteGeneral}' id='print'>Export to Excel C1</a>
            </div>
            ";
        }

        $deleteProductChecked ="
        <div class='button mb5'>
            <a href='#' id='deleteProductChecked' quote_id='{$row['quote_id']}'>Delete Checked Products</a>
        </div>
        ";

        $urlExportAsPdf = "index.php?module=tradingsg_quote&_spAction=printExportAsPdf&id={$row['quote_id']}&showHTML=0";

        if ($cpCfg['countryForCurrency'] == 'India'){
            $exportAsPdf ="
            <div class='button mb5'>
                <a href='{$urlExportAsPdf}' target='blank' id='exportasPdf'>Export as PDF</a>
            </div>
            ";
        }

        $sqlQuoteProduct = "
        SELECT qp.quote_product_id, qp.quote_id
        FROM quote_product qp WHERE qp.quote_id = {$row['quote_id']}
        ";
        $resulQp = $db->sql_query($sqlQuoteProduct);
        $rowQp = $db->sql_fetchrow($resulQp);

        $generalQuotation = '';
        if ($rowQp['quote_id'] == '') {
            $generalQuotation ="
            <div class='button mb5'>
                <a href='#' id='raiseGeneralQuotation' quote_id='{$row['quote_id']}'>Raise General Quotation</a>
            </div>
            ";
        }

        if ($row['quote_type'] == 'General Quotation') {
            $printText .="
            <div class='floatbox  btnbackground'>
    	        <div class='actionBtnsDetail actionbtnwidth '>
    	            {$generalQuotation}
    	            <div class='button mb5'>
    	                <a href='#' id='convertClientRequirement' quote_id='{$row['quote_id']}'>Convert to Client Requirement</a>
    	            </div>
    	        </div>
    	        <div class='actionBtnsDetail actionbtnwidth'>
    	            {$ExportToExcellC1}
    	            {$deleteProductChecked}
			        {$exportAsPdf}
    	        </div>
            </div>
            ";
        } else {
            $raisePo = '';
            if ($_SESSION['userGroupType'] != "User") {
                $raisePo = "
	            <div class='button mb5'>
	                <a href='#' id='raisePo' quote_id='{$row['quote_id']}'>Raise PO</a>
	            </div>
                ";
            }

            $printText .="
            <div class='floatbox  btnbackground'>
    	        <div class='actionBtnsDetail actionbtnwidth '>
                    {$raisePo}
    	            <div class='button mb5'>
    	                <a href='#' id='raiseInvoice' quote_id='{$row['quote_id']}'>Raise Invoice</a>
    	            </div>
    	        </div>
    	        <div class='actionBtnsDetail actionbtnwidth'>
                    <div class='button mb5'>
    	                <a href='{$formActionGroup}' id='updateMarkupByGroup'>Update Markup by Group</a>
    	            </div>
    	            <div class='button mb5'>
    	                <a href='{$formActionUpdateDiscount}' id='updateDiscountForm'>Update Discount</a>
    	            </div>
    	            {$updateDiscountByGroup}
    	        </div>
    	        <div class='actionBtnsDetail actionbtnwidth'>
    	            {$ExportToExcellC1}
	                {$deleteProductChecked}
			        {$exportAsPdf}
    	        </div>
            </div>
            ";
        }


	            /*<div class='button mb5'>
	                <a href='{$formActionCategory}' id='updateMarkupByCategory'>Update Markup by Category</a>
	            </div>
	            <div class='button mb5'>
	                <a href='#' id='deleteProducts' quote_id='{$row['quote_id']}'>Delete Products Linked</a>
	            </div>

	            */

        $SQL = "
        SELECT count(*) AS total
        FROM quote_product
        WHERE quote_id = {$row['quote_id']}
        ";
        $result = $db->sql_query($SQL);
        $rowCount = $db->sql_fetchrow($result);

        $SQLTotalCp = "
        SELECT SUM(round(qp.cost_price * qp.qty,2)) AS total_cost_price_sum
        FROM quote_product qp WHERE qp.quote_id = {$row['quote_id']}
        ";
        $resultTotalCp = $db->sql_query($SQLTotalCp);
        $rowTotalCp = $db->sql_fetchrow($resultTotalCp);

        $SQLMarkUp = "
        SELECT SUM(round(((qp.cost_price * qp.mark_up)/100)* qp.qty,2)) AS mark_up_amount_sum
        FROM quote_product qp WHERE qp.quote_id = {$row['quote_id']}
        ";
        $resultMarkUp = $db->sql_query($SQLMarkUp);
        $rowMarkUp = $db->sql_fetchrow($resultMarkUp);

        $SQLDiscount = "
        SELECT SUM(round(((qp.cost_price * qp.discount_percentage)/100) * qp.qty,2)) AS discount_percentage_amount_sum
        FROM quote_product qp WHERE qp.quote_id = {$row['quote_id']}
        ";
        $resultDiscount = $db->sql_query($SQLDiscount);
        $rowDiscount = $db->sql_fetchrow($resultDiscount);

        $SQLTotalSp = "
        SELECT SUM(format((qp.selling_price * qp.qty),2)) AS total_selling_price_sum
        FROM quote_product qp WHERE qp.quote_id = {$row['quote_id']}
        ";
        $resultTotalSp = $db->sql_query($SQLTotalSp);
        $rowTotalSp = $db->sql_fetchrow($resultTotalSp);

        $orderRec = $fn->getRecordByCondition('order', "quote_id = '{$row['quote_id']}'");

        $urlInvoice = "index.php?_topRm=finance&module=tradingsg_order&_action=edit&record_id={$orderRec['order_id']}";
        $invoiceLink = '';
        if ($orderRec['order_id'] != '') {
            $invoiceLink ="<a href='{$urlInvoice}'>Go To Invoice</a>";
        }

        $bulkAdd = "<a href='{$bulkGenerateUrl}' id='bulkAddProduct'>Generate Bulk Items</a>";

        $poLink = '';
        if ($_SESSION['userGroupType'] != "User") {
            $poLink = $displayLinkData->getLinkPortalMain('tradingsg_quote', 'tradingsg_purchaseOrderLink', 'Purchase Order Linked', $row);
        }

        $text = "
		{$comment->getView(array(
		     'roomName' => 'tradingsg_quote'
		    ,'recordId' => $row['quote_id']
		    ,'allowEdit' => false
		    ,'allowDelete' => false
		    ,'addReviewLbl' => 'Add Activity'
		    ,'heading' => 'Activities'
		))}
        <div class='subcolumns summary'>
            <div class='c50l'>
            <div class='subcl'>
                {$media->getRightPanelMediaDisplay("Attachments", "tradingsg_quote", "attachment", $row)}
            </div>
            </div>
            <div class='c50r'>
            <div class='subcr'>
                {$displayLinkData->getLinkPortalMain('tradingsg_quote', 'tradingsg_expenseLink', 'Expense Linked', $row)}
            </div>
            </div>
        </div>

        {$printText}
        {$displayLinkData->getLinkPortalMain('tradingsg_quote', 'tradingsg_productLink',
                                             'Products Linked - No. of items (' . $rowCount['total'] .')
                                             ' . $invoiceLink .' ' . $bulkAdd .'
                                              ', $row)}
        {$poLink}
        ";

          /*- Total Cost Price (' . $rowTotalCp['total_cost_price_sum'] .')
          - Total Mark Up (' . $rowMarkUp['mark_up_amount_sum'] .')
          - Total Discount (' . $rowDiscount['discount_percentage_amount_sum'] .')
          - Total Selling Price (' . $rowTotalSp['total_selling_price_sum'] .')*/
        return $text;
    }

    /**
     *
     */
    function getQuickSearch() {
        $cpUtil = Zend_Registry::get('cpUtil');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $status     	= $fn->getReqParam('status');
        $priority   	= $fn->getReqParam('priority');
        $company_id 	= $fn->getReqParam('company_id');
        $quoteDate1     = $fn->getReqParam('quoteDate1');
        $quoteDate2     = $fn->getReqParam('quoteDate2');
        $deliveryDate1  = $fn->getReqParam('deliveryDate1');
        $deliveryDate2  = $fn->getReqParam('deliveryDate2');
        $yearEnd = date('Y') + 10;

        $spArray = array(
            "Flagged"
           ,"Not-Flagged"
        );

       //$sqlCompany = $fn->getDDSql('tradingsg_company');

        $sqlCompany = "
        SELECT DISTINCT company_id, company_name FROM company
        WHERE category = 'Client'
        ORDER BY company_name
        ";

		if($quoteDate1 == ''){
			$quoteDate1 = 'From';
		}

		if($quoteDate2 == ''){
			$quoteDate2 = 'To';
		}

        $text = "
        <td>
            <select name='priority'>
                <option value=''>Priority</option>
                {$cpUtil->getDropDown1($cpCfg['m.trading.product.quoteProductPriorityArr'], $priority)}
            </select>
        </td>
        <td>
            <select name='company_id'>
                <option value=''>Client Name</option>
                {$dbUtil->getDropDownFromSQLCols2($db, $sqlCompany, $company_id)}
            </select>
        </td>

        <td class='dateRange'>
            Quote Date:
            <input type='text' allowEdit='1' name='quoteDate1' class='fld_date'
                   id='fld_quoteDate1' value='{$quoteDate1 }' yearEnd='{$yearEnd}' />
            <input type='text' allowEdit='1' name='quoteDate2' class='fld_date'
                   id='fld_quoteDate2' value='{$quoteDate2}' yearEnd='{$yearEnd}' />
        </td>

        <td class='dateRange'>
            Delivery Date:
            <input type='text' allowEdit='1' name='deliveryDate1' class='fld_date'
                   id='fld_deliveryDate1' value='{$deliveryDate1 }' yearEnd='{$yearEnd}' />
            <input type='text' allowEdit='1' name='deliveryDate2' class='fld_date'
                   id='fld_deliveryDate2' value='{$deliveryDate2}' yearEnd='{$yearEnd}' />
        </td>

        <td>
            <select name='status'>
                <option value=''>Status</option>
                {$cpUtil->getDropDown1($cpCfg['m.trading.product.quoteProductStatusArr'], $status)}
            </select>
        </td>
        <!--<td>
            <select class='w125' name='special_search'>
                <option value=''>Special Search</option>
                {$cpUtil->getDropDown1($spArray, $tv['special_search'])}
           </select>
        </td>-->
        ";
        /*</tr>
        <tr>
        <td class='button' style='padding:8px'>
            <a class='quickAdd' href='#'>Quick Add</a>
        </td>*/

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

        $formAction = "index.php?_topRm=admin&module=tradingsg_quote&_spAction=quickAddSubmit&showHTML=0";

        $product_group_id   = $fn->getReqParam('product_group_id');
        $unit   = $fn->getReqParam('unit');

        $sqlUnit = "
        SELECT v.value
              ,v.value
        FROM valuelist v
        WHERE v.key_text = 'productUnit'
        ORDER BY v.value
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

        $product = "<input type='text' value='' id='title' class='text' name='title'>";
        $productGroup = "
        <select name='product_group_id'>
            <option value=''>Please Select</option>
            {$dbUtil->getDropDownFromSQLCols2($db, $sqlProductGroup, $product_group_id)}
        </select>
        ";
        $partNumber = "<input type='text' value='' id='part_number' class='text' name='part_number'>";
        $listPrice = "<input type='text' value='' id='price' class='text' name='price'>";
        $uom = "
        <select name='unit'>
            <option value=''>Please Select</option>
            {$dbUtil->getDropDownFromSQLCols2($db, $sqlUnit, $unit)}
        </select>
        ";

        $newRow = "
        <a href='#' class='addRow button mb10'>Add Product</a>
        ";

        $add = "
            <input type='submit' class='submit' value='Save'>
        ";

        $delete = "
        <input type='button' class='removeProduct' value='Delete'>
        ";

        $rows = "
        <tr>
            <td>{$product}</td>
            <td>{$productGroup}</td>
            <td>{$partNumber}</td>
            <td>{$listPrice}</td>
            <td>{$uom}</td>
            <td>{$add}{$delete}</td>
        </tr>
        ";

        $header ="
        <tr>{$newRow}</tr>
        <tr style='background-color:#EAEAE8;'>
        <th>Product</th>
        <th>Product Group</th>
        <th>Part Number</th>
        <th>List Price</th>
        <th>UOM</th>
        <th>Actions</th>
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

        $product_group_id = $fn->getReqParam('product_group_id');
        $unit   = $fn->getReqParam('unit');

        $sqlUnit = "
        SELECT v.value
              ,v.value
        FROM valuelist v
        WHERE v.key_text = 'productUnit'
        ORDER BY v.value
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

        $product = "<input type='text' value='' id='title' class='text' name='title'>";
        $productGroup = "
        <select name='product_group_id'>
            <option value=''>Please Select</option>
            {$dbUtil->getDropDownFromSQLCols2($db, $sqlProductGroup, $product_group_id)}
        </select>
        ";
        $partNumber = "<input type='text' value='' id='part_number' class='text' name='part_number'>";
        $listPrice = "<input type='text' value='' id='price' class='text' name='price'>";
        $uom = "
        <select name='unit'>
            <option value=''>Please Select</option>
            {$dbUtil->getDropDownFromSQLCols2($db, $sqlUnit, $unit)}
        </select>
        ";

        $add = "<input type='submit' value='Save'>";

        $delete = "
        <input type='button' class='removeProduct' value='Delete'>
        ";

        $rows = "
        <tr>
            <td>{$product}</td>
            <td>{$productGroup}</td>
            <td>{$partNumber}</td>
            <td>{$listPrice}</td>
            <td>{$uom}</td>
            <td>{$add}{$delete}</td>
        </tr>
        ";

        return $rows;
    }

    /**
     *
     */
    function getAddNoteQp() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');

        $quote_product_id = $fn->getReqParam('id');

        $formAction = "index.php?_topRm=order&module=tradingsg_quote&_spAction=addNoteFormSubmit&showHTML=0";
        $quoteProductRec = $fn->getRecordRowByID('quote_product', 'quote_product_id', $quote_product_id);

        $text = "
        <form id='portalForm' class='yform columnar addNoteForm' method='post' action='{$formAction}'>
            {$formObj->getTARow('Notes', 'notes', $quoteProductRec['notes'])}
            <input type='hidden' name='quote_product_id' value='{$quote_product_id}' />
        </form>
        ";

        return $text;
    }
}