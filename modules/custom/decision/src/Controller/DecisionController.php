<?php
namespace Drupal\decision\Controller;

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

class DecisionController extends ControllerBase {
  /**
   * Constructs a page with descriptive content.
   * Our router maps this method to the path 'examples/page-example'.
   */

  public function decision() {
    $build=array();
    $tempstore = \Drupal::service('user.private_tempstore')->get('grupo');
    $gid = $tempstore->get('grupo_select_gid');
    if(!empty($gid)){
      return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput('/group/'.$gid.'/decisions')->toString());   
    }
    $build = array(
      '#markup'=>'Decision',
    );
    return $build;
  }

  public function decisions_grupo(){
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $limit=10;
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-decision';    
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

    $build['#markup']=$this->decisions_menu().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );

    return $build; 
  }

  private function decisions_menu(){
    return '';
  }

  public function decision_get_decision_row($nid,$vid){
    $decision_array=$this->decision_get_decision_array($nid,$vid);
    if(!empty($decision_array)){
      return $decision_array[0];
    }
    return '';
  }  
  private function decision_get_decision_array($nid,$vid){
    $result=array();
    $db = \Drupal::database();
    $query=$db->select('decision', 'decision')
      ->fields('decision', array('nid','vid','origen_uid','grupo_nid','despliegue_nid','fecha_cumplimiento','no_control_date','valor_decision'))
      ->condition('decision.nid',$nid)
      ->condition('decision.vid',$vid)
      ->orderBy('decision.nid', 'DESC')
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

private function decision_is_post_form_save($post){
    $form_id_array=array('node_decision_form','node_decision_edit_form');
    if(isset($post['form_id']) && in_array($post['form_id'],$form_id_array)){
      return 1;
    }
    return 0;  
  }
  public function decision_is_entity_decision($entity,$type_id_in=''){
    $type_id=$entity->getEntityTypeId();
    if($type_id=='node'){ 
      $node_type=$entity->getType();
      $node_type_array=array('decision');
      if(in_array($node_type,$node_type_array)){
        return 1;
      }    
    }
    return 0;    
  }
  public function decision_on_entity_save($entity,$action){
    if(!empty($entity)){
      if($this->decision_is_entity_decision($entity)){
        $post=\Drupal::request()->request->all();
        if($this->decision_is_post_form_save($post)){
            $my_group_gid=$entity->get('field_my_group')->getValue();
            //echo print_r($my_group_gid,1);exit();
            $grupo_nid=0;
            if(isset($my_group_gid[0]['target_id']) && !empty($my_group_gid[0]['target_id'])){
              $grupo_nid=$my_group_gid[0]['target_id'];
              if($action=='insert'){          
                $my_group=Group::load($my_group_gid[0]['target_id']);
                $my_plugin_id='group_node:decision';
                $my_group->addContent($entity,$my_plugin_id);
              }
            }
            $this->decision_save($entity,$grupo_nid);   
        }
      }  
    }
}
    private function decision_save($entity,$grupo_nid){
    $nid=$entity->id();
    //print 'nid='.$nid;exit();    
    //$node=Node::load($nid);
    $user = \Drupal::currentUser();
    $uid=$user->id();    
    $vid_array=$entity->get('vid')->getValue();
    //echo print_r($vid_array,1);exit();
    $vid=$vid_array[0]['value'];

    $post=\Drupal::request()->request->all();    
    $valor_decision=$post['valor_decision'];
    $estrategia_nid=$post['estrategia_nid'];
        //echo print_r($importancia_reto,1);exit();

    $decision_row=$this->decision_get_decision_row($nid,$vid);
    
    if(isset($decision_row->nid) && !empty($decision_row->nid)){
      $query = \Drupal::database()->update('decision');
      $query->fields([
        //'grupo_nid' => $grupo_nid,
        'grupo_seguimiento_nid' => $grupo_nid,
        'valor_decision' => $valor_decision,
      ]);
      $query->condition('nid',$nid);
      $query->condition('vid',$vid);
      $query->execute();
    }else{
      $query = \Drupal::database()->insert('decision');
      $query->fields([
        'nid',
        'vid',
        'origen_uid',
        'grupo_nid',
        'despliegue_nid',
        'valor_decision',
      ]);
      $query->values([
        $nid,
        $vid,
        $origen_uid,
        $grupo_nid,
        $despliegue_nid,
        $valor_decision,
      ]);
      $query->execute();
    }
  }
}//class decision controller