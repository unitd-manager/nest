<?php
include_once(CP_LIBRARY_PATH.'lib_php/tcpdf-extra/headfoot.php');

// Extend the TCPDF class to create custom Header and Footer
class MYPDF_Local extends MYPDF{
	//Page header
	public function Header() {
		$cpCfg = Zend_Registry::get('cpCfg');
		$fn    = Zend_Registry::get('fn');

		$duplicateCopy  = $fn->getReqParam('duplicate');
		$triplecateCopy = $fn->getReqParam('triplicate');

		if($duplicateCopy == 1){
			$copyText = $cpCfg['duplicateCopyBillText'];
		}
		else if($triplecateCopy == 1){
			$copyText = $cpCfg['triplicateBillCopyText'];
		}
		else{
			$copyText = $cpCfg['originalBillCopyText'];
		}

		$this->SetFont('helvetica','', 9);
		
		$header='<table border="0" cellpadding="4" width="100%">';

		$header = $header.'
		<tr>
			<td width="53%">
				<span align="right" style="font-size:9pt;font-weight:bold;background-color:#000000;color:#FFFFFF;border-radius:4px;"> INVOICE </span>
			</td>
			<td width="47%">
				<span align="right"><i>'.$copyText.'</i></span>
			</td>
		</tr>
		<tr>
			<td width="100%">
				<span align="center" style="font-size:14pt;font-weight:bold;">'.$cpCfg['cp.companyName'].'</span>
			</td>
		</tr>
		<tr>
			<td rowspan="2" width="45%" style="border-top:1px solid #000000;border-bottom:1px solid #000000;border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;">
				<span align="center">'.strtoupper($cpCfg['cp.addressPdf1']).' '.strtoupper($cpCfg['cp.addressPdf2']).'<br/>'.strtoupper($cpCfg['cp.addressPdf3']).'<br/>'.$cpCfg['printEmailAddress'].'</span>
			</td>
			<td rowspan="2" width="10%" align="center">
				<img src="images/SaiSurgicalLogo.png" width="40" height="35"/>
			</td>
			<td width="45%" align="center" style="border-top:1px solid #000000;border-bottom:1px solid #000000;border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;">'.$cpCfg['cp.addressPdf4'].'</td>
		</tr>
		<tr>
			<td width="45%" align="center" style="border-bottom:1px solid #000000;border-left:1px solid #000000;border-right:1px solid #000000;font-weight:bold;">'.$cpCfg['cp.addressPdf5'].'</td>
		</tr>
		';

		$header = $header.'</table>';
		
		$this->writeHTML($header, true, false, false, false, '');
		$this->SetTopMargin(36);
	}

	public function Footer() {
		$this->SetFont('helvetica','', 9);
      	
      	$footer ='<table border="0" width="100%">';

      	//if($total > 1){
			$footer = $footer.'
			<tr>
				<td width="100%" style="border-bottom:1px solid #000000;"></td>
			</tr>';
			
			$footer = $footer.'</table>';
      	//}

		if($this->getNumPages() == 1){
			$this->writeHTML($footer, true, false, false, false, '');
		}
    }
}
?>