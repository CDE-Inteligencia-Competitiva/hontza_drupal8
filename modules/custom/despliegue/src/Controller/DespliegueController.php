<?php
namespace Drupal\despliegue\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Query\PagerSelectExtender;
//use Drupal\estrategia\Controller
/*use Drupal\Core\Link;
use Drupal\grupo\Controller\GrupoController;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Entity\Feed;
use Drupal\Core\Render\Markup;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;
use Drupal\vigilancia\Controller\VigilanciaExtraController;*/

class DespliegueController extends ControllerBase {
  /**
   * Constructs a page with descriptive content.
   * Our router maps this method to the path 'examples/page-example'.
   */

  public function despliegue() {
    $build=array();
    $tempstore = \Drupal::service('user.private_tempstore')->get('grupo');
    $gid = $tempstore->get('grupo_select_gid');
    if(!empty($gid)){
      return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput('/group/'.$gid.'/despliegues')->toString());   
    }
    $build = array(
      '#markup'=>'Despliegue',
    );
    return $build;
  }

  public function despliegues_grupo(){
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $limit=10;
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-despliegue';    
    $pager_data=$db->select('group_content_field_data', 'group_content_field_data')
      ->extend(PagerSelectExtender::class)
      ->fields('group_content_field_data', array('entity_id'))
      ->condition('group_content_field_data.type',$type)
      ->condition('group_content_field_data.gid',$gid)
      ->orderBy('group_content_field_data.created', 'DESC')
      ->limit($limit)
      ->execute();
    $rows=array();  
    
    while($row=$pager_data->fetchObject()){
        $node=Node::load($row->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;       
    }

    $build['#markup']=$this->despliegues_menu().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );

    return $build; 
  }

  private function despliegues_menu(){
    return '';
  }

  public function despliegue_get_despliegue_row($nid,$vid){
    $despliegue_array=$this->despliegue_get_despliegue_array($nid,$vid);
    if(!empty($despliegue_array)){
      return $despliegue_array[0];
    }
    return '';
  }  
  private function despliegue_get_despliegue_array($nid,$vid){
    $result=array();
    $db = \Drupal::database();
    $query=$db->select('despliegue', 'despliegue')
      ->fields('despliegue', array('nid','vid','origen_uid','grupo_nid','grupo_seguimiento_nid','estrategia_nid','fecha_cumplimiento','no_control_date','importancia_despliegue'))
      ->condition('despliegue.nid',$nid)
      ->condition('despliegue.vid',$vid)
      ->orderBy('despliegue.nid', 'DESC')
      //->limit($limit)
      ->execute();
    $rows=array();  
    while($row=$query->fetchObject()){
        /*echo print_r($row,1);
        exit();*/
        $result[]=$row;       
    }
    return $result;
  } 

private function despliegue_is_post_form_save($post){
    $form_id_array=array('node_despliegue_form','node_despliegue_edit_form');
    if(isset($post['form_id']) && in_array($post['form_id'],$form_id_array)){
      return 1;
    }
    return 0;  
  }
  public function despliegue_is_entity_despliegue($entity,$type_id_in=''){
    $type_id=$entity->getEntityTypeId();
    if($type_id=='node'){ 
      $node_type=$entity->getType();
      $node_type_array=array('despliegue');
      if(in_array($node_type,$node_type_array)){
        return 1;
      }    
    }
    return 0;    
  }
  public function despliegue_on_entity_save($entity,$action){
    if(!empty($entity)){
      if($this->despliegue_is_entity_despliegue($entity)){
        $post=\Drupal::request()->request->all();
        if($this->despliegue_is_post_form_save($post)){
            $my_group_gid=$entity->get('field_my_group')->getValue();
            //echo print_r($my_group_gid,1);exit();
            $grupo_nid=0;
            if(isset($my_group_gid[0]['target_id']) && !empty($my_group_gid[0]['target_id'])){
              $grupo_nid=$my_group_gid[0]['target_id'];
              if($action=='insert'){          
                $my_group=Group::load($my_group_gid[0]['target_id']);
                $my_plugin_id='group_node:despliegue';
                $my_group->addContent($entity,$my_plugin_id);
              }
            }
            $this->despliegue_save($entity,$grupo_nid);   
        }
      }  
    }
}
    private function despliegue_save($entity,$grupo_nid){
    $nid=$entity->id();
    //print 'nid='.$nid;exit();    
    //$node=Node::load($nid);
    $user = \Drupal::currentUser();
    $uid=$user->id();    
    $vid_array=$entity->get('vid')->getValue();
    //echo print_r($vid_array,1);exit();
    $vid=$vid_array[0]['value'];

    $post=\Drupal::request()->request->all();    
    $importancia_despliegue=$post['importancia_despliegue'];
    $estrategia_nid=$post['estrategia_nid'];
        //echo print_r($importancia_reto,1);exit();

    $despliegue_row=$this->despliegue_get_despliegue_row($nid,$vid);
    
    if(isset($despliegue_row->nid) && !empty($despliegue_row->nid)){
      $query = \Drupal::database()->update('despliegue');
      $query->fields([
        //'grupo_nid' => $grupo_nid,
        'grupo_seguimiento_nid' => $grupo_nid,
        'importancia_despliegue' => $importancia_despliegue,
      ]);
      $query->condition('nid',$nid);
      $query->condition('vid',$vid);
      $query->execute();
    }else{
      $query = \Drupal::database()->insert('despliegue');
      $query->fields([
        'nid',
        'vid',
        'origen_uid',
        'grupo_nid',
        'grupo_seguimiento_nid',
        'importancia_despliegue',
        'estrategia_nid',
      ]);
      $query->values([
        $nid,
        $vid,
        $uid,
        $grupo_nid,
        $grupo_nid,
        $importancia_despliegue,
        $estrategia_nid,
      ]);
      $query->execute();
    }
  }
}//class despliegue controller