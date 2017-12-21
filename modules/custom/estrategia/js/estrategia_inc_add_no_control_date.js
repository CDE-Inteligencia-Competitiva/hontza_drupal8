jQuery(document).ready(function(){
   
    var is_txek_no_control_date=jQuery("#edit-no-control-date").prop("checked");
    //alert("is_txek="+is_txek);
    if(is_txek_no_control_date){
        jQuery("#edit-fecha-cumplimiento").attr("disabled","disabled");
    }  
    
    jQuery("#edit-no-control-date").change(function(){
        //var is_txek=jQuery(this).attr("checked");
        var is_txek=jQuery(this).prop("checked");
        //alert("is_txek="+is_txek);
        if(is_txek){
            //jQuery("#edit-fecha-cumplimiento").attr("readonly","readonly");
            jQuery("#edit-fecha-cumplimiento").attr("disabled","disabled");
        }else{
            //jQuery("#edit-fecha-cumplimiento").removeAttr("readonly");
            //jQuery("#edit-fecha-cumplimiento").removeClass("hasDatepicker");
            //jQuery("#edit-fecha-cumplimiento").removeClass("form-date");
            jQuery("#edit-fecha-cumplimiento").removeAttr("disabled");
        }
    });
    /*jQuery("#edit-fecha-cumplimiento").click(function(){
        var is_txek=jQuery("#edit-no-control-date").prop("checked");
        //alert("is_txek="+is_txek);
        if(is_txek){
            jQuery("#edit-fecha-cumplimiento").blur();
        }  
     });*/
});