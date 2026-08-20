<?
class CPL_Admin_Modules_Tradingsg_PurchaseOrder_View extends CP_Admin_Modules_Tradingsg_PurchaseOrder_View
{
    /**
     *
     */
    function getEdit($row){
        $formObj = Zend_Registry::get('formObj');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');

        $fnsModGrp = includeCPClass('ModGroup', 'Trading', 'Functions');

        $expNoEdit = array('isEditable' => 0);

        $expContact = array('detailValue' => $row['contact_name_supplier']);
        $modContact = getCPModuleObj('trading_contact');

        $expCompany = array('sqlType' => 'OneField');
        $expDeliveryTerms = $fnsModGrp->getTermsParamArr('trading_paymentTermsLink',
                                                        $row['company_id_supplier'],
                                                        'fld_delivery_terms'
                                                        );

        $expPaymentTerms = $fnsModGrp->getTermsParamArr('trading_paymentTermsLink',
                                                        $row['company_id_supplier'],
                                                        'fld_payment_terms'
                                                        );

        $expVl = array('sqlType' => 'OneField');
        $sqlPriority = $fn->getValuelistSql('quotePriority');
        $sqlCurrency = $fn->getValueListSQL('currency');

        $statusArr = $cpCfg['m.trading.purchaseOrder.statusArr'];
        if($row['status'] == 'confirmed'){ //if po confirmed, remove option 'new'
            unset($statusArr[array_search('new', $statusArr)]);
        }

        $modContact = getCPModuleObj('core_staff');
        $sqlSalesManager = $modContact->model->getStaffByGroupSQL();

        $expStaff   = array('detailValue' => $row['staff_name']);

        $quote = "<a href='index.php?_topRm={$tv['topRm']}&module=tradingsg_quote&record_id={$row['quote_id']}&_action=edit'>{$row['quote_code']}</a>";

        $fieldset1 = "
        {$formObj->getTBRow('PO Code', 'po_code', $row['po_code'], $expNoEdit)}
        {$formObj->getTBRow('Title', 'quote_id', $row['quote_title'], $expNoEdit)}
        {$formObj->getTBRow('Quote Code', 'quote_id', $quote, $expNoEdit)}
        {$formObj->getTBRow('Supplier', 'supplier_name', $row['supplier_name'], $expNoEdit)}
        {$formObj->getTBRow('Client Name', 'company_name', $row['company_name'], $expNoEdit)}
        {$formObj->getDDRowByArr('Status', 'status', $statusArr, $row['status'])}
        {$formObj->getDDRowBySQL('Priority', 'priority', $sqlPriority, $row['priority'], $expVl)}
        {$formObj->getDDRowBySQL('Staff Member Responsible', 'staff_id', $sqlSalesManager,
                                 $row['staff_id'], $expStaff)}
        {$formObj->getDateRow('Follow up Date', 'follow_up_date', $row['follow_up_date'])}
        {$formObj->getDDRowBySQL('Currency', 'buy_currency', $sqlCurrency, $row['buy_currency'], $expVl)}
        {$formObj->getTBRow('Freight Cost', 'freight_cost', $row['freight_cost'])}
        {$formObj->getTARow('Notes to Supplier', 'notes', $row['notes'])}
        {$formObj->getTARow('Delivery Terms', 'delivery_terms', $row['delivery_terms'], $expDeliveryTerms)}
        {$formObj->getTARow('Payment Terms', 'payment_terms', $row['payment_terms'], $expPaymentTerms)}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Purchase Order Header', $fieldset1)}
        {$formObj->getCreationModificationText($row)}
        ";

        return $text;
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
        include_once(CP_LIBRARY_PATH.'lib_php/fpdf-extra/mc_table.php');

        //$pdf = new MYPDF();
        $pdf = new PDF_MC_Table();

		$pdf->AddPage();
		$pdf->SetFont('Arial','',11);

        $supplier_id 	   = $fn->getReqParam('company_id');
        $delivery_terms    = $fn->getReqParam('delivery_terms');
        $notes    		   = $fn->getReqParam('notes');

        if ($supplier_id == ''){
			$pdf->SetFont('Courier','B',11);
            $pdf->SetXY(30,30);
            $pdf->Cell(50, 20, "Please select the company and print the Purchase Order PDF");
			$pdf->Output();
		}else {

		    $SQL = "
	        SELECT pop.*
	              ,p.title AS product_title
	              ,p.part_number
	              ,p.unit
	              ,p.item_code
	              ,supl.p_f
	              ,supl.cst
	              ,supl.vat
	              ,supl.company_name
				  ,supl.address_flat
				  ,supl.address_street
				  ,supl.address_town
				  ,supl.address_state
				  ,supl.address_country
	              ,supl.fax
	              ,supl.phone
	              ,supl.add_vat
	              ,supl.add_cst
	              ,supl.add_pf
	              ,supl.add_freight_cost
	              ,pop.creation_date
	              ,po.po_code
	              ,po.status
	              ,po.delivery_terms
	              ,po.notes
	              ,po.payment_terms
			      ,po.freight_cost
	              ,q.quote_code
	              ,q.delivery_date
	              ,q.delivery_location
	              ,q.company_id
	              ,(SELECT SUM(poph.qty) FROM  po_product poph
			        LEFT JOIN purchase_order poo ON (poo.purchase_order_id = poph.purchase_order_id)
	               WHERE poph.product_id = pop.product_id
	               AND poo.status = 'sent to supplier'
	               AND poph.status = 'print'
	               AND poo.company_id_supplier = {$supplier_id}
	               GROUP BY part_number) AS sum_qty
	        FROM po_product pop

	        LEFT JOIN product p ON (p.product_id = pop.product_id)
	        LEFT JOIN company supl ON (supl.company_id = pop.supplier_id)
	        LEFT JOIN quote q ON (q.quote_id = pop.quote_id)
	        LEFT JOIN purchase_order po ON (po.purchase_order_id = pop.purchase_order_id)
	        LEFT JOIN product_group pg ON (p.product_group_id = pg.product_group_id)
	        WHERE po.company_id_supplier = {$supplier_id}
	        AND po.status = 'sent to supplier'
	        AND pop.status = 'print'
	        ORDER BY pop.po_product_id
	         ";

	        $result = $db->sql_query($SQL);
	        $result1 = $db->sql_query($SQL);
	   //print $SQL;
	        $numRows  = $db->sql_numrows($result);

	        $today = date("d-m-Y");
            if ($numRows == 0){
                $pdf->SetFont('Courier','B',11);
                $pdf->SetXY(5,30);
                $pdf->drawTextBox('Note: Select the Supplier from the dropdown in the left side and please try again. Also please make the status of the Purchase order to Confirmed, only PO with status *** sent to supplier *** will be taken in to account.', 180, 55, 'L', 'T', 0);
                $pdf->Output();
                return;
            }

	        $count = 0;
	        $total = 0;
	        $discount_price = 0;
	        $rows = "";
	        $lineItemNumber = 1;  // To increment the line item in receipt
			$totalAmount = '';
			$company_names = '';
			$delivery_terms = '';
			$delivery_term = '';
			$notes = '';
			$note  = '';
			$subTotal = '';
			$pf = '';
			$freight_cost = '';
            $add_cst= '';
            $add_vat = '';
            $cst = '';
            $vat = '';
            $po_id = '';
            $payment_terms = '';
            $payment_term = '';
	        //============================================================================= //
	        $pdf->SetFont('Arial','',11);
	        $num = '';
            $pdf->SetWidths(array(10, 65, 35, 10, 10, 30, 30));
            $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'R', 'R'));

			$po_code = array();
			$Checkno = 1;
	        while ($row = $db->sql_fetchrow($result1)) {
				$poCode = $row['po_code'];
				list($code, $number) = explode("-", $poCode);
				$pokey = array_search($number, $po_code);
				if ($pokey != true) {
					$po_code[$number] = $number;
					if($Checkno > 1){
						$number = substr($number,1);
					}
					$num .= $number;
			    }
			    $Checkno++;

	        }
            $po_code = "PO-{$num}";

	        while ($row = $db->sql_fetchrow($result)) {
	            if ($count == 0){
	                /* Logo of the institution */
	                $pdf->Image('images/logo-print.gif',10,5,45);
	                $pdf->SetXY(10,10);
	                $pdf->SetFont('Courier','B',11);
	                //$pdf->Cell(50, 20, "Authorized Distributor of");
	                //$pdf->SetXY(10,25);
	                //$pdf->Image('images/parker.jpg',10,28, 25);
	                //$pdf->Image('images/gse.png',42,25, 25);
	                $creationDate = $fn->getCPDate($row['creation_date'], 'd-m-Y');
	                $deliveryDate = $fn->getCPDate($row['delivery_date'], 'd-m-Y');

	                $pdf->SetXY(130,0);
	                $pdf->SetFont('Courier','B',11);
	                $pdf->Cell(50, 20, $cpCfg['cp.companyName']);
	                $pdf->Ln(5);
	                $pdf->SetXY(130,5);
	                $pdf->SetFont('Courier','B',11);
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
	                $pdf->Cell(50, 20, $cpCfg['cp.addressPdf7']);
	                $pdf->Ln(5);
	                $pdf->SetXY(130,30);
	                $pdf->Cell(50, 20, $cpCfg['cp.addressGSTNo']);
	                $pdf->Ln(5);
	                $pdf->SetXY(130,35);
	                $pdf->Cell(50, 20, $cpCfg['printEmailAddress']);

	                /* Header */
	                $pdf->SetFont('Courier','BU',11);
	                $pdf->SetXY(100, 40);
	                $pdf->Cell(21, 20, "PURCHASE ORDER", 0, 0, 'C');
	                $pdf->Ln(25);

					$pdf->SetXY(155, 55);
	                $pdf->SetFont('Courier','B',11);
					$pdf->Cell(20, 8, "DATE : {$today}", 0, 0, 'L');
					$pdf->Ln(10);

		            $pdf->SetXY(10, 55);
		            $pdf->Cell(21, 10, "PO CODE:", 0, 0, 'L');
		            $pdf->Cell(21, 10, $po_code, 0, 0, 'L');
					$pdf->Ln(10);

	                /* Company Details*/
					$billingAddressFlat     = $row['address_flat'];
					$billingAddressStreet   = $row['address_street'];
					$billingAddressTown     = $row['address_town'];
					$billingAddressState    = $row['address_state'];
					$billingAddressCountry  = $row['address_country'];

	                $pdf->SetFont('Courier','B',11);
	                $pdf->SetFillColor(254,203,156);
	                $pdf->Cell(190,8,"PURCHASE ORDER TO",1,0, 'L', 1);
	                $pdf->Ln();
	                $pdf->SetFillColor(255,255,255);
		            $pdf->Cell(190, 8, $row['company_name'], 'LR', 0, 'L', 1);
	                $pdf->Ln();
		            $pdf->Cell(190, 5, $billingAddressFlat, 'LR', 0, 'L', 1);
	                $pdf->Ln();
		            $pdf->Cell(190, 5, $billingAddressStreet, 'LR', 0, 'L', 1);
	                $pdf->Ln();
		            $pdf->Cell(190, 5, $billingAddressTown, 'LR', 0, 'L', 1);
	                $pdf->Ln();
		            $pdf->Cell(190, 5, $billingAddressCountry . ' - ' . $billingAddressState, 'BLR', 0, 'L', 1);
	                $pdf->Ln(10);

				    $quoteCode = $row['quote_code'];
					$formatedQC = explode("-", $quoteCode);

                    /* Company Details*/
                    $date = $fn->getCPDate($row['delivery_date'], 'd-m-Y');
                    $pdf->SetFont('Courier','B',11);
                    $pdf->SetFillColor(254,203,156);
                    $pdf->Cell(95,8,"INVOICE TO",1,0, 'L', 1);
                    $pdf->Cell(95,8,"DELIVERY TO",1,0, 'L', 1);
                    $pdf->Ln();
                    $pdf->SetFillColor(255,255,255);
                    $pdf->Cell(95, 8, $cpCfg['cp.companyName'], 'LR', 0, 'L', 1);
                    $pdf->Cell(95, 8, $cpCfg['cp.companyName'], 'LR', 0, 'L', 1);
                    $pdf->Ln();
	                $pdf->Cell(95, 5, $cpCfg['cp.addressPdf1'], 'LR', 0, 'L', 1);
	                $pdf->Cell(95, 5, $cpCfg['cp.addressPdf1'], 'LR', 0, 'L', 1);
                    $pdf->Ln();
	                $pdf->Cell(95, 5, $cpCfg['cp.addressPdf2'], 'LR', 0, 'L', 1);
	                $pdf->Cell(95, 5, $cpCfg['cp.addressPdf2'], 'LR', 0, 'L', 1);
                    $pdf->Ln();
	                $pdf->Cell(95, 5, $cpCfg['cp.addressPdf3'], 'LR', 0, 'L', 1);
	                $pdf->Cell(95, 5, $cpCfg['cp.addressPdf3'], 'LR', 0, 'L', 1);
                    $pdf->Ln();
	                $pdf->Cell(95, 5, $cpCfg['cp.addressPdf4'], 'LRB', 0, 'L', 1);
	                $pdf->Cell(95, 5, $cpCfg['cp.addressPdf4'], 'LRB', 0, 'L', 1);
                    $pdf->Ln(10);


                    /* List of order items header */
                    $pdf->SetFont('Courier','B',11);
                    $pdf->SetFillColor(254,203,156);
                    $pdf->Cell(10,8,"S.NO",1,0, 'C', 1);
                    $pdf->Cell(65,8,"NAME OF THE ITEM",1,0, 'C', 1);
                    $pdf->Cell(35,8,"PART-NUMBER",1,0, 'C', 1);
                    $pdf->Cell(10,8,"UOM",1,0, 'C', 1);
                    $pdf->Cell(10,8,"QTY",1,0, 'C', 1);
                    $pdf->Cell(30,8,"PRICE",1,0, 'C', 1);
                    $pdf->Cell(30,8,"TOTAL",1,0, 'C', 1);
                    $pdf->Ln();

					$fa = array();
					$po_code = array();
                    $poIds = array();

                    if($row['add_cst'] == 1 && $row['add_vat'] == 0){
                        $add_cst = $row['cst'];
                    }
                    if($row['add_vat'] == 1 && $row['vat'] > 0){
                        $add_vat = $row['vat'];
                    }
	            }

	            //===================================MAIN TABLE============================= //
			    $companyRec  	= $fn->getRecordRowByID('company', 'company_id', $row['company_id']);
				$company_name 	= $companyRec['company_name'];

				$poIdkey = array_search($row['purchase_order_id'], $poIds);
				if ($poIdkey != true) {
					$poIds[$row['purchase_order_id']] = $row['purchase_order_id'];

    				if($row['delivery_terms']){
                        $delivery_terms = $row['delivery_terms'];
                        $delivery_term .= $delivery_terms . "\n"  ;
                    }

    				if($row['notes']){
                        $notes = $row['notes'];
                        $note .= $notes . "\n";
                    }

    				if($row['payment_terms']){
                        $payment_term = $row['payment_terms'];
                        $payment_terms .= $payment_term . "\n";
                    }
			    }

            	$p_f = $row['p_f'];
            	$freight_cost += $row['freight_cost'];
            	$add_pf = $row['add_pf'];

				$poCode = $row['po_code'];
				list($code, $number) = explode("-", $poCode);
				$pokey = array_search($number, $po_code);
				if ($pokey != true) {
					$po_code[$number] = $number;
				    $num .= "-" . $number;
			    }

				//$company_names .= $company_name . ",";

				$key = array_search($row['product_id'], $fa);

				if ($key != true) {
					$fa[$row['product_id']] = $row['product_id'];
					//print_r($fa[$row['product_title']]);
					$total = $row['sum_qty'] * $row['price'];
					$totalDis = number_format($total,2);
					$subTotal += $total;

					$totalAmount += $total;
                    //$product_title = substr($row['product_title'], 0, 15);
                    $product_title = $row['product_title'];
                    $price = number_format($row['price'], 2);

                    $pdf->Row(array($lineItemNumber, $product_title , $row['part_number'], $row['unit'], $row['sum_qty'],$price, $totalDis));

                    /*
                    $pdf->SetFillColor(255,255,255);
                    $pdf->Cell(10, 8, $lineItemNumber, 1, 0, 'L', 1);
                    $pdf->Cell(70, 8, $product_title, 1, 0, 'L', 1);
                    $pdf->Cell(35, 8, $row['part_number'], 1, 0, 'R', 1);
                    $pdf->Cell(10, 8, $row['unit'], 1, 0, 'R', 1);
                    $pdf->Cell(10, 8, $row['sum_qty'], 1, 0, 'R', 1);
                    $pdf->Cell(25, 8, $price, 1, 0, 'R', 1);
                    $pdf->Cell(30, 8, $totalDis, 1, 0, 'R', 1);
                    $pdf->Ln();
                    */
				}

	            $count++;
	            $lineItemNumber++;
                $pfPercent = $row['p_f'];

	        }
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(160, 8, "SUB TOTAL", 1, 0, 'R', 1);
            $pdf->Cell(30, 8, number_format($subTotal,2), 1, 0, 'R', 1);
            $pdf->Ln();
			$totalAmountDis = number_format($totalAmount,0);

			$p_f = '';
			if($add_pf == 1){
                $p_f = $subTotal * ($pfPercent / 100);
				$pdf->Cell(160, 8, "ADD P&F: {$pfPercent}%" , 1, 0, 'R', 1);
				$pdf->Cell(30, 8, number_format($p_f, 2), 1, 0, 'R', 1);
				$pdf->Ln();
            }

			if($freight_cost > 0){
                $pdf->Cell(160, 8, "ADD FREIGHT COST: " , 1, 0, 'R', 1);
                $pdf->Cell(30, 8, number_format($freight_cost, 2), 1, 0, 'R', 1);
                $pdf->Ln();
            }
            $subTotal = $subTotal + $p_f + $freight_cost;

            if($add_cst){
                $cst = $subTotal * ($add_cst/ 100);
                $pdf->Cell(160, 8, "ADD CST: {$add_cst}%" , 1, 0, 'R', 1);
                $pdf->Cell(30, 8, number_format($cst, 2), 1, 0, 'R', 1);
                $pdf->Ln();
            }
            if($add_vat){
                $vat = $subTotal * ($add_vat/ 100);
                $pdf->Cell(160, 8, "ADD VAT: {$add_vat}%" , 1, 0, 'R', 1);
                $pdf->Cell(30, 8, number_format($vat, 2), 1, 0, 'R', 1);
                $pdf->Ln();
            }

            $totalAmount = $subTotal + $vat + $cst;

			//$totalAmountDis = number_format($totalAmount,2);

            $pdf->SetFont('Courier','B',11);
            $pdf->Cell(160,8,"TOTAL",1,0, 'R', 1);
            $pdf->SetFont('Courier','B',11);
            $pdf->Cell(30,8, number_format($totalAmount, 2),1,0, 'R', 1);
            $pdf->Ln(10);

            /*$xaxis = $pdf->GetX();
            $yaxis = $pdf->GetY();
            $po_code = "PO{$num}";
            $pdf->SetXY(10, 55);
            $pdf->Cell(21, 10, "PO CODE:", 0, 0, 'L');
            $pdf->Cell(21, 10, $po_code, 0, 0, 'L');
            $pdf->Ln(10);
            $pdf->SetXY($xaxis, $yaxis);*/

			$pdf->Ln(5);

            //$pdf->SetXY(130,15);
            if($payment_terms){
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(195,8, "Payment Terms :", 0, 0, 'L', 1);
                $pdf->Ln(12);
                $pdf->SetFillColor(255,255,255);
                $pdf->drawTextBox($payment_terms, 170, 32, 'L', 'T', 0);
                $pdf->Ln();
                $pdf->Ln(5);
            }

            if($delivery_term){
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(195,8, "Delivery Terms :", 0, 0, 'L', 1);
                $pdf->Ln(12);
                $pdf->SetFillColor(255,255,255);
                $pdf->drawTextBox($delivery_term, 170, 32, 'L', 'T', 0);
                $pdf->Ln();
                $pdf->Ln(5);
            }

			//$note = substr($note, 0, -3);
            if($note){
                $pdf->SetFillColor(254,203,156);
                $pdf->Cell(195,8, "Notes :", 0, 0, 'L', 1);
                $pdf->Ln(12);
                $pdf->SetFillColor(255,255,255);
                $pdf->drawTextBox($note, 170, 32, 'L', 'T', 0);
            }

            $pdf->Ln(5);
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
	}
	/**
	 *
	 */
	function getPrintPurchaseOrderTCPDF() {
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

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        //include_once(CP_LIBRARY_PATH.'lib_php/tcpdf-extra/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot.php');

        //$pdf = new MYPDF2();
        // create new PDF document
        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Engex Power');
        $pdf->SetTitle('Purchase Order');
        $pdf->SetSubject('Purchase Order');
        //$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 04', PDF_HEADER_STRING);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER,10);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------QUOTE QUERY START

        $pdf->SetFont('Courier','B',10);
        $pdf->AddPage();

        $supplier_id 	   = $fn->getReqParam('company_id');
        $delivery_terms    = $fn->getReqParam('delivery_terms');
        $notes    		   = $fn->getReqParam('notes');

        $SQL = "
	        SELECT pop.*
	              ,p.title AS product_title
	              ,p.part_number
	              ,p.unit
	              ,p.item_code
	              ,supl.p_f
	              ,supl.cst
	              ,supl.vat
	              ,supl.company_name
				  ,supl.address_flat
				  ,supl.address_street
				  ,supl.address_town
				  ,supl.address_state
				  ,supl.address_country
	              ,supl.fax
	              ,supl.phone
	              ,supl.add_vat
	              ,supl.add_cst
	              ,supl.add_pf
	              ,supl.add_freight_cost
	              ,pop.creation_date
	              ,po.po_code
	              ,po.status
	              ,po.delivery_terms
	              ,po.notes
	              ,po.payment_terms
			      ,po.freight_cost
	              ,q.quote_code
	              ,q.delivery_date
	              ,q.delivery_location
	              ,q.company_id
	              ,(SELECT SUM(poph.qty) FROM  po_product poph
			        LEFT JOIN purchase_order poo ON (poo.purchase_order_id = poph.purchase_order_id)
	               WHERE poph.product_id = pop.product_id
	               AND poo.status = 'sent to supplier'
	               AND poph.status = 'print'
	               AND poo.company_id_supplier = {$supplier_id}
	               GROUP BY part_number) AS sum_qty
	        FROM po_product pop

	        LEFT JOIN product p ON (p.product_id = pop.product_id)
	        LEFT JOIN company supl ON (supl.company_id = pop.supplier_id)
	        LEFT JOIN quote q ON (q.quote_id = pop.quote_id)
	        LEFT JOIN purchase_order po ON (po.purchase_order_id = pop.purchase_order_id)
	        LEFT JOIN product_group pg ON (p.product_group_id = pg.product_group_id)
	        WHERE po.company_id_supplier = {$supplier_id}
	        AND po.status = 'sent to supplier'
	        AND pop.status = 'print'
	        ORDER BY pop.po_product_id
	         ";

	        $result = $db->sql_query($SQL);
	        $result1 = $db->sql_query($SQL);
	        $numRows  = $db->sql_numrows($result);

	        $today = date("d-m-Y");

	        $count = 0;
	        $total = 0;
	        $discount_price = 0;
	        $rows = "";
	        $lineItemNumber = 1;  // To increment the line item in receipt
			$totalAmount = '';
			$company_names = '';
			$delivery_terms = '';
			$delivery_term = '';
			$notes = '';
			$note  = '';
			$subTotal = '';
			$pf = '';
			$freight_cost = '';
            $add_cst= '';
            $add_vat = '';
            $cst = '';
            $vat = '';
            $po_id = '';
            $payment_terms = '';
            $payment_term = '';


        /*$creationDate   = $fn->getCPDate($row['invoice_date'], 'd-m-Y');
        $address = '<table border="0" width="100%" cellpadding="3">
                      <tr>
                            <td width="12%" height="20">Bill To:</td>
                            <td width="33%" style="border-bottom:1pt solid black;"> '.$companyName.'</td>
                            <td width="25%"></td>
                            <td width="30%">TAX INVOICE</td>
                     </tr>
                     <tr>
                            <td width="45%" height="20" style="border-bottom:1pt solid black;">'.$billingAddressFlat.'</td>
                            <td width="25%"></td>
                            <td width="30%">No. <font style="color:red;">'.$row['invoice_code'].'</font></td>
                     </tr>
                     <tr>
                            <td width="45%" height="20" style="border-bottom:1pt solid black;">'.$billingAddressStreet.'</td>
                            <td width="25%"></td>
                            <td width="8%">Terms:</td>
                            <td width="22%" style="border-bottom:1pt solid black;"></td>
                     </tr>
                     <tr>
                            <td width="45%" height="20" style="border-bottom:1pt solid black;">'.$billingAddressTown.'</td>
                            <td width="25%"></td>
                            <td width="8%">Date:</td>
                            <td width="22%" style="border-bottom:1pt solid black;">'.$creationDate.'</td>
                     </tr>
                     <tr>
                            <td height="20" style="border-bottom:1pt solid black;">'.$billingAddressCountry . ' - ' . $billingAddressState.'</td>
                     </tr>
                </table>';


        $orderItem ='<table border="1" cellpadding="5" width="100%">';

        $orderItem = $orderItem.'
                    <thead>
                    <tr bgcolor="#FDCA9C">
                        <th width="10%" height="30" align="center">QTY</th>
                        <th align="center" width="40%">DESCRIPTION</th>
                        <th width="10%" align="center">UOM</th>
                        <th width="20%"align="center">UNIT PRICE</th>
                        <th width="20%" align="center">AMOUNT ('.$row['currency'].') </th>
                    </tr>
                    </thead>';

        while ($rowz = $db->sql_fetchrow($result2)) {
            $orderItem = $orderItem.'<tr nobr="true">
                                        <td width="10%" height="30" align="center">'.$rowz['qty'].'</td>
                                        <td align="left" width="40%">'.$rowz['product_title'].'</td>
                                        <td width="10%" align="center">'.$rowz['unit'].'</td>
                                        <td width="20%" align="right">'.$rowz['unit_price'].'</td>
                                        <td width="20%"  align="right">'.$rowz['amount'].'</td>
                                    </tr>';
        }
        $sub_total = $row['sub_total'];
        $notes = $row['notes'];
        $printTaxName = $cpCfg['printTaxName'] ;
        $gsttaxvalue = $cpCfg['amtForGSTCalc'] ;
        $gstvalue = $row['sub_total'] * $gsttaxvalue / 100;
        $totalvalue = $gstvalue + $row['sub_total'];

        $orderItem = $orderItem.'<tr>
                                      <td colspan="4" align="right">SUB TOTAL '.$row['currency'].'</td>
                                      <td align="right">'.$row['sub_total'].'</td>
                                  </tr>
                                  <tr>
                                      <td colspan="4" align="right">ADD: '.$printTaxName.' '.$gsttaxvalue.'</td>
                                      <td align="right">'.number_format($gstvalue, 2).'</td>
                                  </tr>
                                  <tr>
                                      <td colspan="4" align="right">TOTAL</td>
                                      <td align="right">'.number_format($totalvalue, 2).'</td>
                                  </tr>';

        $orderItem = $orderItem.'</table>';

        $notesItem = '<table border="0" width="100%">
                        <tr>
                            <td>'.$cpCfg['cp.addInformationPdf'].' "'.$cpCfg['cp.companyName'].'".</td>
                        </tr>
                      </table>';
        $signBox  = '<table border="0" width="100%">
                        <tr>
                            <td width="38%" height="80" style="border-bottom:1pt solid black;"></td>
                            <td width="20%"></td>
                            <td width="42%" style="border-bottom:1pt solid black;" align="top">for '.$cpCfg['cp.companyName'].'</td>
                        </tr>
                        <tr>
                            <td width="38%">Company Stamp & Signature</td>
                            <td width="20%"></td>
                            <td width="42%"></td>
                        </tr>
                    </table>';
        //<td width="42%">for '.$cpCfg['cp.companyName'].'</td>

        $pdf->writeHTML($address, true, false, false, false, '');
        $pdf->writeHTML($orderItem, true, false, false, false, '');
        $pdf->writeHTML($notesItem, true, false, false, false, '');
        $pdf->ln(4);
        $pdf->writeHTML($signBox, true, false, false, false, '');*/
        $pdf->Output('purchase_order.pdf', 'I');

    }

}