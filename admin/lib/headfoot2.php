<?php
include_once(CP_LIBRARY_PATH.'lib_php/tcpdf-extra/headfoot.php');

// Extend the TCPDF class to create custom Header and Footer
class MYPDF_Local extends MYPDF{
	//Page header
	public function Header() {
		$cpCfg = Zend_Registry::get('cpCfg');
		$fn    = Zend_Registry::get('fn');

		$duplicateCopy = $fn->getReqParam('duplicate');

		if($duplicateCopy == 1){
			$copyText = $cpCfg['duplicateCopyBillText'];
		}
		else{
			$copyText = $cpCfg['originalBillCopyText'];
		}

		$this->SetFont('helvetica','', 10);
		
		$header='<table border="0" width="100%">';

		/*$header= $header.'
		<tr>
			<td width="75%" style="border-bottom:1px solid #000000;">
				<img src="images/logo-print.png" width="" height=""/>
			</td>
			<td width="25%" style="border-bottom:1px solid #000000;"><p style="line-height:160%;font-size:14pt">'.$cpCfg['cp.companyName'].'<br/><font style="font-size:8pt;">'.$cpCfg['cp.addressPdf1'].'<br/>'.$cpCfg['cp.addressPdf2'].' '.$cpCfg['cp.addressPdf3'].'<br/>'.$cpCfg['cp.addressPdf4'].'<br/>'.$cpCfg['cp.addressPdf5'].'</font></p></td>
		</tr>
		';*/

		$header= $header.'
		<tr>
			<td width="20%" style="border-bottom:1px solid #000000;">
				<img src="images/logo-print.png" width="" height=""/>
			</td>
			<td width="60%" style="border-bottom:1px solid #000000;">
				<span align="center" style="font-size:11pt;font-weight:bold;"><u>TAX INVOICE</u></span><br/>
				<span align="center" style="font-size:15pt;font-weight:bold;">'.$cpCfg['cp.companyName'].'</span><br/>
				<span align="center">
				'.$cpCfg['cp.addressPdf1'].''.$cpCfg['cp.addressPdf2'].' '.$cpCfg['cp.addressPdf3'].'<br/>
				<span align="center" style="font-size:10pt;font-weight:bold;">'.$cpCfg['cp.addressPdf5'].'</span><br/>
				<span align="center" style="font-size:10pt;font-weight:bold;">'.$cpCfg['cp.addressPdf4'].' '.$cpCfg['printEmailAddress'].'</span>
				</span><br/>
			</td>
			<td width="20%" align="center" style="border-bottom:1px solid #000000;font-style:italic;">
				'.$copyText.'
			</td>
		</tr>
		';

		$header = $header.'</table>';

		$this->writeHTML($header, true, false, false, false, '');
		$this->SetTopMargin(38);
	}

	public function Footer() {
		$this->SetFont('helvetica','', 9);
      	
      	$footer='<table border="0" width="100%">';
		
		$footer= $footer.'
		<tr>
			<td width="78%">(This is computer generated document, and does not require a signature)</td>
			<td width="22%" align="right">Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages().'</td>
		</tr>';
		
		$footer = $footer.'</table>';
		
		$this->writeHTML($footer, true, false, false, false, '');
    }
}
?>