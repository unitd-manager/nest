<?php
include_once(CP_LIBRARY_PATH.'lib_php/tcpdf-extra/headfoot.php');

// Extend the TCPDF class to create custom Header and Footer
class MYPDF_Local extends MYPDF{
	//Page header
	public function Header() {
		$cpCfg = Zend_Registry::get('cpCfg');
		$fn    = Zend_Registry::get('fn');

		$this->SetFont('helvetica','', 8);
		
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
			<td width="100%">
				<span align="center" style="font-size:13pt;font-weight:bold;">'.$cpCfg['cp.companyName'].'</span><br/>
				<span align="center" style="font-size:10pt;font-weight:bold;">INVOICE</span><br/>
				<span align="center">
				'.$cpCfg['cp.addressPdf1'].''.$cpCfg['cp.addressPdf2'].' '.$cpCfg['cp.addressPdf3'].'<br/>
				<span align="center">'.$cpCfg['cp.addressPdf5'].' / </span>
				<span align="center">'.$cpCfg['cp.addressPdf4'].' / '.$cpCfg['printEmailAddress'].'</span>
				</span><br/>
			</td>
		</tr>
		';

		$header = $header.'</table>';

		$this->writeHTML($header, true, false, false, false, '');
		$this->SetTopMargin(22);
	}

	public function Footer() {
		$this->SetFont('helvetica','', 8);
      	
      	$footer='<table border="0" width="100%">';
		
		$footer= $footer.'
		<tr>
			<td width="100%" align="center">Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages().'</td>
		</tr>';
		
		$footer = $footer.'</table>';
		
		$this->writeHTML($footer, true, false, false, false, '');
    }
}
?>