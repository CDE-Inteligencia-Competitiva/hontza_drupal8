<?php

namespace Drupal\grupo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
//use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GrupoController extends ControllerBase {

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'examples/page-example'.
   */

  public function mis_grupos() {
    $build=array();
    $html=array();
    //$group_array=$this->grupo_get_group_array();
    $group_array=$this->grupo_get_user_groups_array();    
    if(!empty($group_array)){
      $html[]='<ul>';
      foreach($group_array as $i=>$group){
        //$route_string=''entity.group.canonical';
        $route_string='grupo_select';
        $link=Link::createFromRoute($group->label,$route_string,array('group' =>$group->gid));
        $my_render=$link->toRenderable();
        $html[]='<li>'.render($my_render).'</li>';
      }
      $html[]='</ul>';
    }

    $build = array(
      '#markup'=>implode('',$html),
    );
    return $build;
  }
  private function grupo_get_group_array(){
    $result=array();
    $db = \Drupal::database();

    $res = $db->select('groups','groups')->fields('groups')->execute();
    while($row=$res->fetchObject()){
      $result[]=$row;
    }
    return $result;
  }
  public function grupo_get_user_groups_array(){    
      $result=array();
      $db = \Drupal::database();
      $user = \Drupal::currentUser();
      $uid=$user->id();
      $query = $db->select('groups_field_data', 'groups_field_data');
      $query->fields('groups_field_data', array('label'));
      $query->fields('group_content_field_data', array('gid','label'));      
      $query->leftJoin('group_content_field_data', 'group_content_field_data', 'groups_field_data.id = group_content_field_data.gid');
      $query->condition('group_content_field_data.type','%'.db_like('group_membership').'%', 'LIKE');
      $query->condition('group_content_field_data.entity_id',$uid);
      $query->distinct();
      $res=$query->execute();
      while($row=$res->fetchObject()){
        $result[]=$row;
      }
      return $result;
  }
  public function grupo_select() {
      $parameters = \Drupal::routeMatch()->getParameters();
      $gid=$parameters->get('group');
      $tempstore = \Drupal::service('user.private_tempstore')->get('grupo');
      $tempstore->set('grupo_select_gid', $gid);
      return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput('/group/'.$gid)->toString());           
  }
  public function grupo_get_grupo_selected_title(){
    $result=array();

    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    if(!is_numeric($gid)){
      $tempstore = \Drupal::service('user.private_tempstore')->get('grupo');
      $gid=$tempstore->get('grupo_select_gid');
    }
    $grupo_title='';  
    if(is_numeric($gid)){
      $my_group=Group::load($gid);
      $grupo_title='<i>'.t('Group').': </i><b>'.$my_group->label().'</b>';      
    }  
    $result['#markup']='';
    if(empty($grupo_title)){
      return '';
    }
    $result['#markup']=$grupo_title;  
    return $result;
  }
  public function grupo_get_current_grupo(){
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    if(!is_numeric($gid)){
      $tempstore = \Drupal::service('user.private_tempstore')->get('grupo');
      $gid=$tempstore->get('grupo_select_gid');
    }
    $grupo_title='';  
    if(is_numeric($gid)){
      $my_group=Group::load($gid);
      return $my_group;
    }
    return '';    
  }
  public function grupo_get_user_gid_array(){
    $result=array();
    $groups_array=$this->grupo_get_user_groups_array();
    if(!empty($groups_array)){
      foreach($groups_array as $i=>$group){
        $result[]=$group->gid;
      }
    }
    return $result;    
  }
  public function grupo_get_categorias_vid($my_grupo){
    $my_grupo_row=$my_grupo->toArray();
    if(isset($my_grupo_row['field_group_categories'][0]['value'])){
      return $my_grupo_row['field_group_categories'][0]['value'];
    }
    return '';
  }
  public function grupo_user_in_group($account,$members){
    $account_uid=$account->id();      
    if(!empty($members)){
      foreach($members as $i=>$row){
        $my_user=$row->getUser();
        $uid=$my_user->id();
        if($account_uid==$uid){
          return 1;
        }
      }
    }
    return 0;
  }
  public function grupo_user_in_group_access($route,$account){
    $my_grupo=$this->grupo_get_current_grupo();
    if(!empty($my_grupo)){
      $members=$my_grupo->getMembers();
      if(!$this->grupo_user_in_group($account,$members)){
        return 0;    
      }
    }  
    return 1;
  }   


  public function grupo_get_fuentes_vid($my_grupo){
    $my_grupo_row=$my_grupo->toArray();
    if(isset($my_grupo_row['field_group_source_types'][0]['value'])){
      return $my_grupo_row['field_group_source_types'][0]['value'];
    }
    return '';
  }
}