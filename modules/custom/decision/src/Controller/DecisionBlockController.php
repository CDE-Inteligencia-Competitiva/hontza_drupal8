<?php
namespace Drupal\decision\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\grupo\Controller\GrupoController;
use Drupal\Core\Link;
//use Drupal\Core\Entity;
use Drupal\node\Entity\Node;

class DecisionBlockController extends ControllerBase {
	 public function decision_get_visible_groups_block(){
          if($this->decision_is_tab_decision()){     
               return 0;
          }
          return 1;
      }
      public function decision_arg(){
         $path = \Drupal::request()->getpathInfo();
         $arg  = explode('/',$path);
         return $arg;
      }
      public function decision_is_pantalla($pantalla,$with_grupo=0){
          $arg=$this->decision_arg();
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
      public function decision_is_tab_decision(){
          if($this->decision_is_pantalla('decision')){
               return 1;
          }
          if($this->decision_is_node('decision')){
               return 1;
          }
          if($this->decision_is_pantalla('decisions',1)){
               return 1;
          }
          return 0;     
      }
      public function decision_get_visible_vigilancia_fuentes_left_block(){
          return $this->decision_get_visible_groups_block();
      }
      public function decision_get_visible_vigilancia_categorias_left_block(){
          return $this->decision_get_visible_groups_block();
      }
      public function decision_get_visible_vigilancia_canales_left_block(){
          return $this->decision_get_visible_groups_block();
      }
      public function decision_get_visible_vigilancia_left_block(){
          return $this->decision_get_visible_groups_block();
      }
      public function decision_get_visible_search_form_block_block(){
          return $this->decision_get_visible_groups_block();
      }
      public function decision_get_visible_system_menu_block_tools_block(){
          return $this->decision_get_visible_groups_block();
      }
      public function decision_get_visible_block($plugin_id){
        $result=array();
        $result['is_access']=0;
        $result['result']=1;
        $no_decision_block_array=$this->decision_define_no_decision_block_array();
        $decision_block_array=$this->decision_define_decision_block_array();
        $home_block_array=array('search_form_block');
        $grupo_block_array=array('system_menu_block:side-nav');
        $vigilancia_block_array=array('vigilancia_fuentes_left','vigilancia_categorias_left',
     'vigilancia_canales_left','vigilancia_left','search_form_block');

        if($this->decision_is_tab_decision()){     
          $result['is_access']=1;               
          if(in_array($plugin_id,$decision_block_array)){
               $result['result']=1;
               return $result;
          }
          if(in_array($plugin_id,$no_decision_block_array)){
            $result['result']=0;
          }          
          return $result;
        }else if($this->decision_is_tab_home()){
          $result['is_access']=1;
          if(in_array($plugin_id,$home_block_array)){
            $result['result']=1;
            return $result;
          }  
          if($this->decision_not_block_array($plugin_id,array_merge($no_decision_block_array,$decision_block_array))){
            $result['result']=0;
            return $result;
          }
          return $result; 
        }else if($this->decision_is_tab_grupo()){
          $result['is_access']=1;
          if(in_array($plugin_id,$grupo_block_array)){
            $result['result']=1;
            return $result;
          }
          if($this->decision_not_block_array($plugin_id,array_merge($no_decision_block_array,$decision_block_array))){
            $result['result']=0;
            return $result;
          } 
          return $result;
        }else if($this->decision_is_tab_vigilancia()){
          $result['is_access']=1;
          if(in_array($plugin_id,$vigilancia_block_array)){
            $result['result']=1;
            return $result;
          }
          if($this->decision_not_block_array($plugin_id,array_merge($no_decision_block_array,$decision_block_array))){
            $result['result']=0;
            return $result;
          }   
          return $result;
        }  
        //print $plugin_id.'<br>';
        return $result;
      }
      public function decision_get_block_decision_left_content(){
        $html=array();
        $grupo_controller=new GrupoController();
        $my_grupo=$grupo_controller->grupo_get_current_grupo();
        if(!empty($my_grupo)){
          //$fuentes_vid=$grupo_controller->grupo_get_fuentes_vid($my_grupo);    
          $gid=$my_grupo->id();
          if(is_numeric($gid)){
            $route_string='node.add';
            //Url::fromRoute('node.add', ['node_type' => $type]    
            $link=Link::createFromRoute(t('Add Decision'),$route_string,array('node_type' =>'decision'));
            $my_render=$link->toRenderable();
            $html[]='<ul class="clearfix menu">';          
            $html[]='<li class="menu-item">'.render($my_render).'</li>';
            $decisions_route_string='decisions_grupo';
            $decisions_link=Link::createFromRoute(t('List of Decisions'),$decisions_route_string,array('group' =>$gid));
            $my_render=$decisions_link->toRenderable();
            $html[]='<li class="menu-item">'.render($my_render).'</li>';
            $html[]='</ul>';
            
          }
        }  
        $result=implode('',$html);
        return $result;
      }
      public function decision_is_node($node_type){
        $arg=$this->decision_arg();
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
      public function decision_get_route_nid(){
        if($this->decision_is_node('decision')){
          $arg=$this->decision_arg();
          $nid=$arg[2];
          if(is_numeric($nid)){
            return $nid;
          }
        }
        return '';
      }
      private function decision_is_tab_home(){
        $arg=$this->decision_arg();
        if(!isset($arg[1]) || empty($arg[1])){
          return 1;
        }
        return 0;
      }
      private function decision_is_tab_grupo(){
        if($this->decision_is_pantalla('grupo')){
                 return 1;
        }
        return 0;
      }    
      public function decision_not_block_array($plugin_id,$block_array_in){
        $block_array=array();
        if(empty($block_array_in)){
          $no_decision_block_array=$this->decision_define_no_decision_block_array();
          $decision_block_array=$this->decision_define_decision_block_array();
          $block_array=array_merge($no_decision_block_array,$decision_block_array);
        }else{
          $block_array=$block_array_in;
        }
        if(in_array($plugin_id,$block_array)){
          return 1;      
        }
        return 0;
      }
      private function decision_is_tab_vigilancia(){
        if($this->decision_is_pantalla('vigilancia')){
                 return 1;
        }
        if($this->decision_is_pantalla('vigilancia',1)){
                 return 1;
        }
        if($this->decision_is_pantalla('feed')){
                 return 1;
        }
        if($this->decision_is_node('item')){
          return 1;
        }
      }
      private function decision_define_no_decision_block_array(){
        $no_decision_block_array=array('system_menu_block:side-nav','vigilancia_fuentes_left','vigilancia_categorias_left',
       'vigilancia_canales_left','vigilancia_left','search_form_block','system_menu_block:tools');
        return $no_decision_block_array;
      }
      private function decision_define_decision_block_array(){
        $decision_block_array=array('decision_left');
        return $decision_block_array;
      }
}//class	