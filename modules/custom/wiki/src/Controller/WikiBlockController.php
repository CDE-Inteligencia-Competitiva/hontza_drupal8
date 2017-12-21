<?php

namespace Drupal\wiki\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\grupo\Controller\GrupoController;
use Drupal\Core\Link;
//use Drupal\Core\Entity;
use Drupal\node\Entity\Node;
use Drupal\estrategia\Controller\EstrategiaBlockController;

class WikiBlockController extends ControllerBase {	 
      public function wiki_arg(){
         $path = \Drupal::request()->getpathInfo();
         $arg  = explode('/',$path);
         return $arg;
      }
      public function wiki_is_pantalla($pantalla,$with_grupo=0){
          $arg=$this->wiki_arg();
          if($with_grupo){
            if($arg[1]=='group'){
              if(isset($arg[2]) && is_numeric($arg[2])){
                if(isset($arg[3]) && $arg[3]==$pantalla){
                  return 1;
                }  
              }  
            }  
          }else{
            if(isset($arg[1]) && $arg[1]==$pantalla){
                 return 1;
            }
          }  
          return 0;
      }
      
      public function wiki_get_visible_block($plugin_id){
        $result=array();
        $result['is_access']=0;
        $result['result']=1;
        $debate_block_array=array('wiki_left');
        $estrategia_block_controller=new EstrategiaBlockController();        
        
        if($this->wiki_is_tab_wiki()){
          $result['is_access']=1;
          if(in_array($plugin_id,$wiki_block_array)){
            $result['result']=1;
            return $result;
          }
          if($estrategia_block_controller->estrategia_not_block_array($plugin_id,'')){
            $result['result']=0;
            return $result;
          }   
          return $result;
        }else{
          if(in_array($plugin_id,$wiki_block_array)){
            $result['is_access']=1;
            $result['result']=0;
            return $result;
          }
        }  
        //print $plugin_id.'<br>';
        return $result;
      }
      public function wiki_is_tab_wiki(){
          if($this->wiki_is_pantalla('area-trabajo')){
               return 1;
          }
          if($this->wiki_is_node('wiki')){
               return 1;
          }
          if($this->wiki_is_pantalla('wikis',1)){
               return 1;
          }          
          return 0;     
      }
      public function wiki_is_node($node_type){
        $arg=$this->wiki_arg();
        if(isset($arg[1]) && $arg[1]=='node'){
           if(isset($arg[2]) && $arg[2]=='add'){
              if(isset($arg[3]) && $arg[3]==$node_type){
                return 1;
              }  
           }else if(isset($arg[2]) && is_numeric($arg[2])){
              $nid=$arg[2];
              //$node=Entity::load($nid);
              $node=Node::load($nid);            
              $my_node_type=$node->getType();
              if($my_node_type==$node_type){
                return 1;
              }
           }
        }
        return 0;
      }
      public function wiki_get_block_wiki_left_content(){
        $html=array();
        $grupo_controller=new GrupoController();
        $my_grupo=$grupo_controller->grupo_get_current_grupo();
        if(!empty($my_grupo)){
          //$fuentes_vid=$grupo_controller->grupo_get_fuentes_vid($my_grupo);    
          $gid=$my_grupo->id();
          if(is_numeric($gid)){
            $route_string='node.add';
            //Url::fromRoute('node.add', ['node_type' => $type]    
            $link=Link::createFromRoute(t('Create Wiki Document'),$route_string,array('node_type' =>'wiki'));
            $my_render=$link->toRenderable();
            $html[]='<ul class="clearfix menu">';          
            $html[]='<li class="menu-item">'.render($my_render).'</li>';
            $wikis_route_string='wikis_grupo';
            $wikis_link=Link::createFromRoute(t('List of Wikis'),$wikis_route_string,array('group' =>$gid));
            $my_render=$wikis_link->toRenderable();
            $html[]='<li class="menu-item">'.render($my_render).'</li>';
            $html[]='</ul>';
            
          }
        }  
        $result=implode('',$html);
        return $result;
      } 

      public function wiki_get_route_item_nid(){
        if($this->wiki_is_node('item')){
          $arg=$this->wiki_arg();
          $nid=$arg[2];
          if(is_numeric($nid)){
            return $nid;
          }
        }
        return '';
      }     
}//class	