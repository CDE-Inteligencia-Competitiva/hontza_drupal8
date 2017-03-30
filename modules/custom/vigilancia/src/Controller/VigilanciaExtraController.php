<?php

namespace Drupal\vigilancia\Controller;

use Drupal\Core\Controller\ControllerBase;


class VigilanciaExtraController extends ControllerBase {
	 public function vigilancia_extra_get_duplicado_or_url($info_cut,$is_solo_url){
     	$or_url='';
     	
     	if(!empty($info_cut['link'])){
     		$or[]='node__field_rss_url.field_rss_url_value="'.$info_cut['link'].'"';
     		$or[]='node__field_rss_guid.field_rss_guid_value="'.$info_cut['link'].'"';
     	}
		
		if(!empty($info_cut['guid'])){
     		$or[]='node__field_rss_url.field_rss_url_value="'.$info_cut['guid'].'"';
     		$or[]='node__field_rss_guid.field_rss_guid_value="'.$info_cut['guid'].'"';
     	}
     	
     	if(!empty($info_cut['url'])){
     		$or[]='node__field_rss_url.field_rss_url_value="'.$info_cut['url'].'"';
     		$or[]='node__field_rss_guid.field_rss_guid_value="'.$info_cut['url'].'"';
     	}
     	       	
     	$or_url='('.implode(' OR ',$or).')';
     	if($is_solo_url){
        	$where[]=$or_url;
	    }/*else{
	        $where[]=$or_url.' AND n.title="%s"';
	    }*/	
     	return $or_url;
     }      
}//class	