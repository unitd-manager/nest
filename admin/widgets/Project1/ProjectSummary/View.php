<?
class CPL_Admin_Widgets_Project_ProjectSummary_View extends CP_Admin_Widgets_Project_ProjectSummary_View
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $url = "index.php?_topRm=project&module=project_project";
        $target = $fn->getSettingsValueByKey('monthlySalesTarget');

        /*
        $totSalesThisMonth = $this->model->getTotalSalesThisMonth();
        $targetPercThisMonth = round(($totSalesThisMonth/$target) * 100);
                
        $totSalesLastMonth = $this->model->getTotalSalesLastMonth();
        $targetPercLastMonth = round(($totSalesLastMonth/$target) * 100);
        
        $monthNum = date('n');
        $totSalesThisYear = $this->model->getTotalSalesThisYear();
        $targetPercThisYear = round(($totSalesThisYear/($target*$monthNum)) * 100);

        $wdInvSummary = getCPWidgetObj('project_invoiceSummary');
        $totalStillToBill = preg_replace ('/[^\d\s]/', '', $this->model->getTotalValueOfStillToBill());
        $totalOutstanding = $totalStillToBill +  preg_replace ('/[^\d\s]/', '', $wdInvSummary->model->getTotalOutstandingInvoices());
        */

        $text = "
        <h2><a href='{$url}'>Projects & Sales Summary</a></h2>
        <div class='tableOuter'>
            <table class='thinList list'>
                <tr>
                    <th>Total value of sales this month:</th>
                    <td>{$this->model->getCurPfx()}" . number_format(7500) ."&nbsp;&nbsp;&nbsp;% of target: 150,000%</td>
                </tr>

                <tr>
                    <th>Total value of sales last month:</th>
                    <td>{$this->model->getCurPfx()}" . number_format(6200) ."&nbsp;&nbsp;&nbsp;% of target: 280,000%</td>
                </tr>

                <tr>
                    <th>Total value of sales this year:</th>
                    <td>{$this->model->getCurPfx()}" . number_format(8600) ."&nbsp;&nbsp;&nbsp;% of target: 240,000%</td>
                </tr>

                <tr>
                    <th>Total value of projects WIP:</th>
                    <td>9,000</td>
                </tr>

                <tr>
                    <th>Total value of Still to Bill:</th>
                    <td>15,000</td>
                </tr>

                <tr>
                    <th>Total value of Projects Still to Bill + Outstanding Invoices:</th>
                    <td>12,000</td>
                </tr>
            </table>
        </div>
        ";

        return $text;
    }
}