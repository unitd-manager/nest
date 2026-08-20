<?
class CPL_Admin_Widgets_Project_InvoiceSummary_View extends CP_Admin_Widgets_Project_InvoiceSummary_View
{

    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');

        $url = "index.php?_topRm=project&module=project_invoice";

        $text = "
        <h2><a href='{$url}'>Invoice Summary</a></h2>
        <div class='tableOuter'>
            <table class='thinList list'>
                <tr>
                    <th>Total outstanding invoices:</th>
                    <td>154,000</td>
                </tr>

                <tr>
                    <th>Total invoices due this month:</th>
                    <td>75,000</td>
                </tr>

                <tr>
                    <th>Total late invoices:</th>
                    <td>67,000</td>
                </tr>

                <tr>
                    <th>Total late invoice (> 90 days):</th>
                    <td>78,900</td>
                </tr>

                <tr>
                    <th>Total invoices raised this month:</th>
                    <td>97,000</td>
                </tr>

                <tr>
                    <th>Total invoices raised last month:</th>
                    <td>78,000</td>
                </tr>

                <tr>
                    <th>Total invoices paid this month:</th>
                    <td>70,000</td>
                </tr>

                <tr>
                    <th>Total invoices paid last month:</th>
                    <td>67,000</td>
                </tr>
            </table>
        </div>
        ";

        return $text;
    }
}