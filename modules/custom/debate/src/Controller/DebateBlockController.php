<?php

namespace Drupal\debate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\grupo\Controller\GrupoController;
use Drupal\Core\Link;
//use Drupal\Core\Entity;
use Drupal\node\Entity\Node;
use Drupal\estrategia\Controller\EstrategiaBlockController;

class DebateBlockController extends ControllerBase {	 
      public function debate_arg(){
         $path = \Drupal::request()->getpathInfo();
         $arg  = explode('/',$path);
         return $arg;
      }
      public function debate_is_pantalla($pantalla,$with_grupo=0){
          $arg=$this->debate_arg();
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
      
      public function debate_get_visible_block($plugin_id){
        $result=array();
        $result['is_access']=0;
        $result['result']=1;
        $debate_block_array=array('debate_left');
        $estrategia_block_controller=new EstrategiaBlockController();        
        
        if($this->debate_is_tab_debate()){
          $result['is_access']=1;
          if(in_array($plugin_id,$debate_block_array)){
            $result['result']=1;
            return $result;
          }
          if($estrategia_block_controller->estrategia_not_block_array($plugin_id,'')){
            $result['result']=0;
            return $result;
          }   
          return $result;
        }else{
          if(in_array($plugin_id,$debate_block_array)){
            $result['is_access']=1;
            $result['result']=0;
            return $result;
          }
        }  
        //print $plugin_id.'<br>';
        return $result;
      }
      public function debate_is_tab_debate(){
          if($this->debate_is_pantalla('area-debate')){
               return 1;
          }
          if($this->debate_is_node('debate')){
               return 1;
          }
          if($this->debate_is_pantalla('debates',1)){
               return 1;
          }          
          return 0;     
      }
      public function debate_is_node($node_type){
        $arg=$this->debate_arg();
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
      public function debate_get_block_debate_left_content(){
        $html=array();
        $grupo_controller=new GrupoController();
        $my_grupo=$grupo_controller->grupo_get_current_grupo();
        if(!empty($my_grupo)){
          //$fuentes_vid=$grupo_controller->grupo_get_fuentes_vid($my_grupo);    
          $gid=$my_grupo->id();
          if(is_numeric($gid)){
            $route_string='node.add';
            //Url::fromRoute('node.add', ['node_type' => $type]    
            $link=Link::createFromRoute(t('Create Discussion'),$route_string,array('node_type' =>'debate'));
            $my_render=$link->toRenderable();
            $html[]='<ul class="clearfix menu">';          
            $html[]='<li class="menu-item">'.render($my_render).'</li>';
            $debates_route_string='debates_grupo';
            $debates_link=Link::createFromRoute(t('List of Discussions'),$debates_route_string,array('group' =>$gid));
            $my_render=$debates_link->toRenderable();
            $html[]='<li class="menu-item">'.render($my_render).'</li>';
            $html[]='</ul>';
            
          }
        }  
        $result=implode('',$html);
        return $result;
      }      
}//class	