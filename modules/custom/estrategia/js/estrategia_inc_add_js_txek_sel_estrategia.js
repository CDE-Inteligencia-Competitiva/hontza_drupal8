jQuery(document).ready(function(){
   
      
//alert('HA entrado');

//var idea_keys = new Array();
var idea_keys = drupalSettings.estrategia.estrategia_txek.idea_keys;
oportunidad_idea_txek();
                                    //
                                    function oportunidad_idea_txek(){
                                      var i;
                                      var v="";
                                      var my_key="";
                                      for(i in idea_keys){
                                        v=idea_keys[i];
                                        my_key="edit-"+v;
                                        //alert(my_key);
                                        jQuery("#"+my_key).change(function(){
                                            var is_txek=jQuery(this).prop("checked");
                                            //alert("is_txek="+is_txek);
                                            if(is_txek){
                                                set_beste_oportunidad_idea_txek(jQuery(this).attr("id"),false);
                                            }
                                        });
                                      }
                                      //
                                      function set_beste_oportunidad_idea_txek(my_key,modua){
                                            var konp=my_key.replace("edit-","");
                                            for(i in idea_keys){
                                                v=idea_keys[i];
                                                if(v!=konp){
                                                    my_key="edit-"+v;
                                                    jQuery("#"+my_key).attr("checked",modua);
                                                }
                                            }
                                      }
                                    }
                                    //


});