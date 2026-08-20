Util.createCPObject('cpt.angle');

cpt.angle = {
	init: function(){

        window.onload = getStartedContent();
        function getStartedContent() {
            var activation_expiry_status = $("input[name='activation_expiry_status']").val();
            
            if(activation_expiry_status == "Expired"){
                var urlUnPublish = 'index.php?plugin=common_login&_spAction=UnpublishLoginForExpired&showHTML=0';
                $.get(urlUnPublish, {}, function(json){
                    var urlLogout = 'index.php?plugin=common_login&_spAction=logout';
                    document.location = urlLogout;
                }); 
            }

            //var popupStaffSession = $('#getStaffPopupOnloadSession').val();

            /*if(popupStaffSession == ''){
                $('#staffNameEntermodal').modal('show');
                $('.chooseLocationByUserSubmit').live('click', function (e){
                    e.preventDefault();
                    var url = 'index.php?widget=common_multiUniqueSite&_spAction=changeSite&showHTML=0';
                    var cp_site_id = $('select[name=chooseLocationByUserDropdown]').val();
                    $.get(url, {cp_site_id: cp_site_id}, function(){
                        cpt.angle.resetSessionForLocation();
                    })
                });
                
            }*/

            $('.v-list table.cpSearch .type-text.ym-fbox-text.dateRange input.fld_date').each(function(){
                var inputname = $(this).attr('name');
                var dateValue = $(this).val();
                $(this).addClass('MainDateField');
                $(this).after("<input type='text' class='hiddenDateDisplay' name='hidden_date_display' data-onload='"+dateValue+"'>");
            });

            $('.v-list table.cpSearch td.dateRange input.fld_date').each(function(){
                var inputname = $(this).attr('name');
                var dateValue = $(this).val();
                $(this).addClass('MainDateField');
                $(this).after("<input type='text' class='hiddenDateDisplay' name='hidden_date_display' data-onload='"+dateValue+"'>");
            });

            $('.v-list table.cpSearch .type-text.ym-fbox-text.dateRange .hiddenDateDisplay[data-onload]').each(function() {
                var dateCheck = $(this).attr('data-onload');
                
                if(dateCheck != '') {
                    var date      = dateCheck.replace(/-/g, '/');
                    var newdate   = new Date(date);
                    var dd = ('0' + newdate.getDate()).slice(-2);
                    var mm = ('0' + (newdate.getMonth() + 1)).slice(-2)
                    var y  = newdate.getFullYear();
         
                    var endDate = dd + '-'+ mm + '-' + y;
                }else {
                    var endDate = '';
                }

                $(this).val(endDate);
            });

            $('.v-list table.cpSearch td.dateRange .hiddenDateDisplay[data-onload]').each(function() {
                var dateCheck = $(this).attr('data-onload');
                
                if(dateCheck != '') {
                    var date      = dateCheck.replace(/-/g, '/');
                    var newdate   = new Date(date);
                    var dd = ('0' + newdate.getDate()).slice(-2);
                    var mm = ('0' + (newdate.getMonth() + 1)).slice(-2)
                    var y  = newdate.getFullYear();
         
                    var endDate = dd + '-'+ mm + '-' + y;
                }else {
                    var endDate = '';
                }

                $(this).val(endDate);
            });

        }

        function blink(selector){
            $(selector).fadeOut('slow', function(){
                $(this).fadeIn('slow', function(){
                    blink(this);
                });
            });
        }
            
        blink('.demoVersionTextHeading');

        $("a.checkProductPrice").livequery('click', function (e){
            Util.showProgressInd();
            var url = 'index.php?module=tradingsg_pos&_spAction=productPrice&showHTML=0';
            var exp = {
                url: url
            };
            Util.openDialogForLink('Check Product Price',  900, 400, 0, exp);
        });

        $(".checkProductPrice input[name='product_title']")
        .livequery(cpt.angle.checkProductPrice);

        $('.leftNav .hlist ul li.first').livequery('click', function(){
            var parent = $(this).closest('li');
            parent.next('ul.displayNone').slideToggle();
        });

    	//show hide description in Help Content - TRADE SMART (USS Product)
        $('.contentTitle').livequery('click', function(){
            //$('.contentDescription').css('display','none');
            var parent = $(this).closest('.helpContentTask');
            $('.contentDescription', parent).slideToggle();
            var parent = $(this).closest('.startedContentTask');
            $('.contentDescription', parent).slideToggle();
        });

		// Adding help button pop window in the content list  - TRADE SMART (USS Product)
		$("a.helpContentTask").livequery('click', function (e){
		    var module_name = $(this).attr('module_name');
		    var url = 'index.php?module=webBasic_content&_spAction=helpContentTask&module_name=' + module_name + '&showHTML=0';
		    var exp = {
		        url: url
		    };
		    Util.openDialogForLink('Help Content',  1000, 500, 0, exp);
		});

    	//show hide description in GET STARTED Content - TRADE SMART (USS Product)
    	$('.contentTitle').livequery('click', function(){
    		var parent = $(this).closest('.getStartedContentTask');
    	    $('.contentDescription', parent).slideToggle();
    	});

		// Adding GET STARTED button pop window in the content list  - TRADE SMART (USS Product)
		$("a.getStartedContentTask").livequery('click', function (e){
		    var module_name = $(this).attr('module_name');
		    var url = 'index.php?module=webBasic_content&_spAction=startedContentTask&module_name=' + module_name + '&showHTML=0';
		    var exp = {
		        url: url
		    };
		    Util.openDialogForLink('Get Started',  1000, 500, 0, exp);
		});

    	$("#nav .hlist ul li a span").addClass('inner');
    	$("#nav .hlist ul li a").blend();

        $("ul.homeTop li").livequery('click', function(){
            $(this).children("ul.sub").slideToggle();
        });

        $("ul.homeTop font a").livequery('click', function(){
            $(this).children("ul.sub").slideToggle();
        });

        $(".leftnavShowHide").livequery('click', function(){
            $('#col1').slideToggle('fast', function() {
                $('.leftnavShowHide').toggleClass('leftnavShowHideicon', $('#col1').is(':hidden'));
            });

            $('#col3').addClass('fullleftlist');

        });

    	$('.contentScroller, .m-common_dashboard .widget div.tableOuter').addClass('scroll-pane');

    	if ($('.tplLogin').length > 0){
    	    var toSubtract = $('#header').outerHeight(true) + $('#footer').outerHeight(true);
    	    var mainPanelHt = $(window).height() - toSubtract - 20;
    	    $('#col3_content').css({'height' : mainPanelHt + 'px', overflow: 'auto', 'overflow-x': 'hidden'});
    	    $("#col3_content #loginOuter").cp_center();
    	}

    	$("table.search td select").change(function() {
    	    $('#searchTop').submit();
    	});

        $('form#portalForm_InstallationForm').livequery(function() {
            Util.setUpAjaxFormGeneral('portalForm_InstallationForm', function(json){
                Util.showProgressInd();
                //var url = "index.php?plugin=common_login&_spAction=ResetDBForInstallation&showHTML=0";
                alert('Successfully Activated! \n Click Ok to continue with login');
                var printUrl = "index.php?plugin=common_login&_spAction=PrintLoginDetails&showHTML=0";
                window.open(printUrl,'_blank');
                window.location.reload(true);
            });
        });

        $("#portalForm_InstallationForm input[name='product_key']").livequery('keyup', function() {
            var product_key = $(this).val();
            var radioValue = $("input[name='version_type']:checked").val();
            product_key = product_key.replace(/[\W\s\._\-]+/g, '');
            
            var split = 4;
            var chunk = [];

            for (var i = 0, len = product_key.length; i < len; i += split) {
                split = ( i >= 4 && i <= 16 ) ? 4 : 4;
                chunk.push(product_key.substr(i, split));
            }

            $(this).val(chunk.join("-").toUpperCase());
        });

        $(".activateToFullversion").livequery('click', function (e){
            var title = "Activate";
            e.preventDefault();

            var expObj = {
                validate: true
                ,callbackOnSuccess: function(){
                    Util.closeAllDialogs();
                    alert("Successfully Activated!!")
                    window.location.reload(true);
                }
            }
            
            Util.openFormInDialog.call(this, 'ProductKeyActivateForm', title, 589, 253, expObj);
        });

        $("#ProductKeyActivateForm input[name='active_key']").livequery('keyup', function() {
            var product_key = $(this).val();
            var radioValue = $("input[name='version_type']:checked").val();
            product_key = product_key.replace(/[\W\s\._\-]+/g, '');
            
            var split = 5;
            var chunk = [];

            for (var i = 0, len = product_key.length; i < len; i += split) {
                split = ( i >= 5 && i <= 16 ) ? 5 : 5;
                chunk.push(product_key.substr(i, split));
            }

            $(this).val(chunk.join("-").toUpperCase());
        });

        $(".SyncFilesFromServToLoc").livequery('click', function(){
            Util.showProgressInd();

            var urlFilesSync = "index.php?plugin=common_login&_spAction=SyncFilesFromServerToLocal&showHTML=0";
            $.get(urlFilesSync, {}, function(html){
                var urlDBSync    = "index.php?plugin=common_login&_spAction=SQLLogLatestFromFile&showHTML=0";
                $.get(urlDBSync, {}, function(html){
                    Util.hideProgressInd();

                    if(html == ""){
                        Util.alert("Files Are Updated Successfully!");
                    }else{
                        Util.alert("Problem Please Check Connection Or Contact Administrator!");
                    }

                    window.location.reload(true);
                });
            });
        });

        $(".registeredFromDemoVersion").livequery('click', function(){
            Util.showProgressInd();

            msg = "Do you like to register?";
            if (!confirm(msg)){
                return false;
            }else{
                var url = "index.php?plugin=common_login&_spAction=ChangeToCustomerVersion&showHTML=0";
                $.get(url, {}, function(html){
                    window.location.reload(true);
                });
            }
        });

        $(".rollBackToDemoVersion").livequery('click', function(){
            Util.showProgressInd();

            msg = "Do you like to rollback for demo version?";
            if (!confirm(msg)){
                return false;
            }else{
                var url = "index.php?plugin=common_login&_spAction=ChangeToDemoVersion&showHTML=0";
                $.get(url, {}, function(html){
                    window.location.reload(true);
                });
            }
        });

        $(".UpdateSQLLogOnTableLocal").livequery('click', function (e){
            var title = "SQL Log";
            e.preventDefault();

            var expObj = {
                validate: true
                ,callbackOnSuccess: function(){
                    Util.closeAllDialogs();
                    alert("Submitted Successfully!!")
                    window.location.reload(true);
                }
            }
            
            Util.openFormInDialog.call(this, 'SqlLogStoreForm', title, 589, 320, expObj);
        });

        $('.v-edit .hasDatepicker, .v-new .hasDatepicker').livequery('change', function(e){
            var parent    = $(this).closest(".type-text.ym-fbox-text");
            var dateCheck = $(this).val();

            if(dateCheck != "") {
                var date      = dateCheck.replace(/-/g, "/");
                var newdate   = new Date(date);
                var dd = ("0" + newdate.getDate()).slice(-2);
                var mm = ("0" + (newdate.getMonth() + 1)).slice(-2)
                var y  = newdate.getFullYear();
     
                var endDate = dd + '-'+ mm + '-' + y;
            }else {
                var endDate = "";
            }

            $('.hiddenDateDisplay', parent).val(endDate);
        });

        $('.v-list table.cpSearch .type-text.ym-fbox-text .hasDatepicker').livequery('change', function(e){
            var dateCheck = $(this).val();

            if(dateCheck != "") {
                var date      = dateCheck.replace(/-/g, "/");
                var newdate   = new Date(date);
                var dd = ("0" + newdate.getDate()).slice(-2);
                var mm = ("0" + (newdate.getMonth() + 1)).slice(-2)
                var y  = newdate.getFullYear();
     
                var endDate = dd + '-'+ mm + '-' + y;
            }else {
                var endDate = "";
            }

            $(this).next("input.hiddenDateDisplay").val(endDate);
        });

        $('.v-list table.cpSearch td.dateRange .hasDatepicker').livequery('change', function(e){
            var dateCheck = $(this).val();

            if(dateCheck != "") {
                var date      = dateCheck.replace(/-/g, "/");
                var newdate   = new Date(date);
                var dd = ("0" + newdate.getDate()).slice(-2);
                var mm = ("0" + (newdate.getMonth() + 1)).slice(-2)
                var y  = newdate.getFullYear();
     
                var endDate = dd + '-'+ mm + '-' + y;
            }else {
                var endDate = "";
            }

            $(this).next("input.hiddenDateDisplay").val(endDate);
        });

        $('#reportSearchPanel table.search td.dateRange .hasDatepicker').livequery('change', function(e){
            var dateCheck = $(this).val();

            if(dateCheck != "") {
                var date      = dateCheck.replace(/-/g, "/");
                var newdate   = new Date(date);
                var dd = ("0" + newdate.getDate()).slice(-2);
                var mm = ("0" + (newdate.getMonth() + 1)).slice(-2)
                var y  = newdate.getFullYear();
     
                var endDate = dd + '-'+ mm + '-' + y;
            }else {
                var endDate = "";
            }

            $(this).prev("input.hiddenDateDisplay").val(endDate);
        });
 	},

    checkProductPrice: function() {
        var titleObj = this;
        $(titleObj).autocomplete({
             source : 'index.php?module=tradingsg_pos&_spAction=searchProductTitle&showHTML=0'
            ,minLength : 2
            ,select: function(event, ui) {
                var selectedObj = ui.item;
                var product_id = selectedObj.id
                //alert (product_id);
                $(this).after("<input type='hidden' name='product_id' value=" + product_id + ">");

                //--------------------------------------------
                Util.showProgressInd();
                var url = 'index.php?module=tradingsg_pos&_spAction=productPriceDisplay&showHTML=0';
                $.get(url, {product_id: product_id}, function(html){
                    //cpm.tradingsg.pos.reloadOrderItems();
                    $(".checkProductPrice input[name='product_title']").val('');
                    $('#productDisplay').html(html);
                    Util.hideProgressInd();
                });
            }
        });
    }

}

function DropDown(el) {
                this.dd = el;
                this.placeholder = this.dd.children('span');
                this.opts = this.dd.find('ul.dropdown > li');
                this.val = '';
                this.index = -1;
                this.initEvents();
            }
            DropDown.prototype = {
                initEvents : function() {
                    var obj = this;

                    obj.dd.livequery('click', function(event){
                        $(this).toggleClass('active');
                        return false;
                    });

                    obj.opts.livequery('click',function(){
                        var opt = $(this);
                        obj.val = opt.text();
                        obj.index = opt.index();
                        obj.placeholder.text(obj.val);
                    });
                },
                getValue : function() {
                    return this.val;
                },
                getIndex : function() {
                    return this.index;
                }
            }

            $(function() {

                var dd = new DropDown( $('#dd') );

                $(document).click(function() {
                    // all dropdowns
                    $('.wrapper-dropdown-3').removeClass('active');
                });

            });