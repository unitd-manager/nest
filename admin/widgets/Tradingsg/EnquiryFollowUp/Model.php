<?
class CPL_Admin_Widgets_Tradingsg_EnquiryFollowUp_Model extends CP_Admin_Widgets_Tradingsg_EnquiryFollowUp_Model
{
    /**
     *
     */
    function getSQL(){

        $cpCfg = Zend_Registry::get('cpCfg');
        /* Old sql
        if ($_SESSION['userGroupType'] == "User") {
            $joinFlds .= "
            ,epg_hist.product_group_id as history_enquiry_product_group_id
            ,pgs_hist.product_group_id as history_staff_product_group_id
            ";
        }

        $SQL = "
        SELECT e.*
              ,com.company_name 
              ,com.phone 
              ,com.email 
              {$joinFlds}
        FROM enquiry_product_group epg_hist, product_group_staff pgs_hist, 
        `enquiry` e
        LEFT JOIN staff s ON (s.staff_id = e.staff_id)
        LEFT JOIN staff st ON (st.staff_id = e.staff_allocation)
        LEFT JOIN (company com) ON (e.company_id = com.company_id)
        LEFT JOIN (geo_country gc) ON (e.address_country = gc.country_code)
        ";
        */       
        $SQL = "
        SELECT DISTINCT e.enquiry_id
              ,e.email
              ,e.country
              ,e.status
              ,e.follow_up_date
              ,e.phone
              ,e.title
              ,e.enquiry_code
              ,e.staff_allocation
              ,e.flag
              ,e.creation_date
              ,e.modification_date
              ,com.company_name 
              ,com.email AS c_company_email
        FROM `enquiry` e
        LEFT JOIN staff s ON (s.staff_id = e.staff_id)
        LEFT JOIN staff st ON (st.staff_id = e.staff_allocation)
        LEFT JOIN (company com) ON (e.company_id = com.company_id)
        LEFT JOIN (enquiry_product_group epg_hist) ON (e.enquiry_id = epg_hist.enquiry_id)
        LEFT JOIN (product_group_staff pgs_hist) ON (pgs_hist.product_group_id = epg_hist.product_group_id)
            ";
        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $searchVar = $this->searchVar;

        /*if ($_SESSION['userGroupType'] == "User") {
            $searchVar->sqlSearchVar[] = "(e.enquiry_id = epg_hist.enquiry_id
            AND pgs_hist.staff_id = {$_SESSION['staff_id']}
            AND epg_hist.product_group_id  = pgs_hist.product_group_id)
            OR 
            (e.staff_allocation =  {$_SESSION['staff_id']})
           ";
        }*/

        if ($_SESSION['userGroupType'] == "User") {
          $searchVar->sqlSearchVar[] = " 
            (e.staff_id =  {$_SESSION['staff_id']})
            ";
        }

        $searchVar->sortOrder = 'e.follow_up_date DESC';

    }
}