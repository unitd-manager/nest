<?php
include_once(CP_LIBRARY_PATH.'lib_php/tcpdf-extra/headfoot.php');

// Extend the TCPDF class to create custom Header and Footer
class MYPDF_Local extends MYPDF{
	//Page header
	public function Header() {
		$cpCfg = Zend_Registry::get('cpCfg');
		$this->SetFont('Arial','B', 8);
		
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
			<td width="10%" style="border-bottom:1px solid #000000;">
				<img src="images/logo-print.png" width="" height=""/>
			</td>
			<td width="90%" style="border-bottom:1px solid #000000;">
				<p align="center" style="line-height:160%;font-size:14pt">'.$cpCfg['cp.companyName'].'<br/>
				<font style="font-size:8pt;">'.$cpCfg['cp.addressPdf1'].''.$cpCfg['cp.addressPdf2'].' '.$cpCfg['cp.addressPdf3'].'<br/>
				'.$cpCfg['cp.addressPdf5'].'
				<br/>'.$cpCfg['cp.addressPdf4'].' '.$cpCfg['printEmailAddress'].'</font></p></td>
		</tr>
		';

		$header = $header.'</table>';

		$this->writeHTML($header, true, false, false, false, '');
		$this->SetTopMargin(40);
	}

	public function Footer() {
		$this->SetFont('Courier','B',9);
      	
      	$footer='<table border="0" width="100%">';
		
		$footer= $footer.'
		<tr>
			<td></td>
			<td></td>
		</tr>
		<tr>
			<td width="78%">(This is computer generated document, and does not require a signature)</td>
			<td width="22%" align="right">Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages().'</td>
		</tr>';
		
		$footer=$footer.'</table>';
		
		$this->writeHTML($footer, true, false, false, false, '');
    }
}
?>