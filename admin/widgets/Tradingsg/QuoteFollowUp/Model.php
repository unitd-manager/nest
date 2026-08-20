<?
class CPL_Admin_Widgets_Tradingsg_QuoteFollowUp_Model extends CP_Admin_Widgets_Tradingsg_QuoteFollowUp_Model
{
    /**
     *
     */
    function getSQL(){
        $SQL = "
      	SELECT DISTINCT q.quote_id
              ,q.title
              ,q.quote_date
              ,q.follow_up_date
              ,q.amount
              ,q.company_id
              ,q.contact_id
              ,q.staff_id
              ,q.enquiry_id
              ,q.flag
      	      ,co.company_name
      	      ,co.email
      	      ,co.phone
      	FROM quote q
        LEFT JOIN company co ON (q.company_id = co.company_id)
      	LEFT JOIN contact con ON (q.contact_id = con.contact_id)
      	LEFT JOIN staff s ON (q.staff_id = s.staff_id)
      	LEFT JOIN quote_product qp ON (qp.quote_id = q.quote_id)
      	LEFT JOIN product p ON (qp.product_id = p.product_id)
      	LEFT JOIN product_group pg ON (pg.product_group_id = p.product_group_id)
      	LEFT JOIN product_group_staff pgs ON (pgs.product_group_id = p.product_group_id)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $fn = Zend_Registry::get('fn');

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'q';
        //to show only related quoteas matching to the product group for the staff with usergroup 'user'.
        $searchVar->sqlSearchVar[] = "q.status IN('New','Current','Sent to Customer')";

        if ($_SESSION['userGroupType'] == "User") {
            $searchVar->sqlSearchVar[] = "  
            (q.staff_id = {$_SESSION['staff_id']})
            ";
        }
        
        $searchVar->sortOrder = 'q.follow_up_date DESC';

    }
}