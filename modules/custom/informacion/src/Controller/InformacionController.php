<?php
namespace Drupal\informacion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\estrategia\Controller\EstrategiaController;
use Drupal\vigilancia\Controller\VigilanciaController;
use Drupal\decision\Controller\DecisionController;

class InformacionController extends ControllerBase {
  /**
   * Constructs a page with descriptive content.
   * Our router maps this method to the path 'examples/page-example'.
   */

  public function informacion() {
    $build=array();
    $tempstore = \Drupal::service('user.private_tempstore')->get('grupo');
    $gid = $tempstore->get('grupo_select_gid');
    if(!empty($gid)){
      return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput('/group/'.$gid.'/informaciones')->toString());   
    }
    $build = array(
      '#markup'=>'Informacion',
    );
    return $build;
  }

  public function informaciones_grupo(){
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $limit=10;
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-informacion';    
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

    $build['#markup']=$this->informaciones_menu().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );

    return $build; 
  }

  private function informaciones_menu(){
    return '';
  }

public function informacion_get_informacion_row($nid,$vid){
    $informacion_array=$this->informacion_get_informacion_array($nid,$vid);
    if(!empty($informacion_array)){
      return $informacion_array[0];
    }
    return '';
  }  
  private function informacion_get_informacion_array($nid,$vid){
    $result=array();
    $db = \Drupal::database();
    $query=$db->select('informacion', 'informacion')
      //->fields('informacion', array('nid','vid','origen_uid','grupo_nid','decision_nid','fecha_cumplimiento','no_control_date','importancia'))
      ->fields('informacion')
      ->condition('informacion.nid',$nid)
      ->condition('informacion.vid',$vid)
      ->orderBy('informacion.nid', 'DESC')
      //->limit($limit)
      ->execute();
    $rows=array();  
    while($row=$query->fetchObject()){
        $result[]=$row;       
    }
    return $result;
  } 

private function informacion_is_post_form_save($post){
    $form_id_array=array('node_informacion_form','node_informacion_edit_form');
    if(isset($post['form_id']) && in_array($post['form_id'],$form_id_array)){
      return 1;
    }
    return 0;  
  }

  public function informacion_is_entity_informacion($entity,$type_id_in=''){
    $type_id=$entity->getEntityTypeId();
    if($type_id=='node'){ 
      $node_type=$entity->getType();
      $node_type_array=array('informacion');
      if(in_array($node_type,$node_type_array)){
        return 1;
      }    
    }
    return 0;    
  }

  public function informacion_on_entity_save($entity,$action){
    if(!empty($entity)){
      if($this->informacion_is_entity_informacion($entity)){
        $post=\Drupal::request()->request->all();
        if($this->informacion_is_post_form_save($post)){
            $my_group_gid=$entity->get('field_my_group')->getValue();
            //echo print_r($my_group_gid,1);exit();
            $grupo_nid=0;
            if(isset($my_group_gid[0]['target_id']) && !empty($my_group_gid[0]['target_id'])){
              $grupo_nid=$my_group_gid[0]['target_id'];
              if($action=='insert'){          
                $my_group=Group::load($my_group_gid[0]['target_id']);
                $my_plugin_id='group_node:informacion';
                $my_group->addContent($entity,$my_plugin_id);
              }
            }
            $this->informacion_save($entity,$grupo_nid);   
        }
      }  
    }
  }

  public function informacion_define_fecha_cumplimiento($node,$field='fecha_cumplimiento'){
    if(!empty($node)){
      $nid=$node->id();    
      $vid=$node->getRevisionId();
      $informacion_row=$this->informacion_get_informacion_row($nid,$vid);
      //echo print_r($estrategia_row,1);exit();
      if(isset($informacion_row->$field) && !empty($informacion_row->$field)){
        //return $estrategia_row->$field;
        $fecha=date("Y-m-d",$informacion_row->$field);
        return $fecha;
      }
    }else{
      $fecha=date("Y-m-d", strtotime("+6 months"));
      return $fecha;
    }
    return 0;
  }

  public function informacion_update_fecha_cumplimiento($node,$field='fecha_cumplimiento'){
    if(!empty($node)){
      $nid=$node->id();    
      $vid=$node->getRevisionId();
      $estrategia_row=$this->despliegue_get_despliegue_row($nid,$vid);
    
      if(isset($despliegue_row->$field) && !empty($despliegue_row->$field)){
       
        $fecha=date("Y-m-d",$despliegue_row->$field);
        return $fecha;
      }
    }else{
      $fecha=date("Y-m-d", strtotime("+6 months"));
      return $fecha;
    }
    return 0;
  }

  private function informacion_save($entity,$grupo_nid){
    $nid=$entity->id();
    //print 'nid='.$nid;exit();    
    //$node=Node::load($nid);
    $user = \Drupal::currentUser();
    $uid=$user->id();    
    $vid_array=$entity->get('vid')->getValue();
    //echo print_r($vid_array,1);exit();
    $vid=$vid_array[0]['value'];

    $post=\Drupal::request()->request->all();    
    $importancia=$post['importancia'];
    $decision_nid=$post['decision_nid'];
    $fecha_cumplimiento=$post['fecha_cumplimiento'];
    $fecha_cumplimiento = strtotime($fecha_cumplimiento);
    $informacion_row=$this->informacion_get_informacion_row($nid,$vid);
    
    if( isset($post['decision_txek'])){
      $decision_check_array = array_keys($post['decision_txek']);
      $decision_nid = $decision_check_array[0];
      //echo print_r ($decision_check_array,1); exit();
    }

        if( isset($_POST['no_control_date'])){
      $no_control_date=$post['no_control_date'];
    }else{
      $no_control_date=0;
    }

    if(isset($informacion_row->nid) && !empty($informacion_row->nid)){

      //if que te permite crear reto sin control date
      if(isset($no_control_date) && !empty($no_control_date)){
        $fecha_cumplimiento=$informacion_row->fecha_cumplimiento;
      }

      $query = \Drupal::database()->update('informacion');
      $query->fields([
        'grupo_nid' => $grupo_nid,
        //'grupo_seguimiento_nid' => $grupo_nid,
        'importancia' => $importancia,
        'fecha_cumplimiento' => $fecha_cumplimiento,        
        'no_control_date' => $no_control_date,
        'decision_nid' => $decision_nid,
      ]);
      $query->condition('nid',$nid);
      $query->condition('vid',$vid);
      $query->execute();
    }else{
      $query = \Drupal::database()->insert('informacion');
      $query->fields([
        'nid',
        'vid',
        'origen_uid',
        'grupo_nid',
        'decision_nid',
        'fecha_cumplimiento',
        'no_control_date',
        'importancia',
      ]);
      $query->values([
        $nid,
        $vid,
        $uid,
        $grupo_nid,
        $decision_nid,
        $fecha_cumplimiento,
        $no_control_date,
        $importancia,
      ]);
      $query->execute();
    }
  }

  function create_informacion_guraso_fieldset($informacion_nid,$decision_nid,$node_decision_nid,&$keys){
    $decision_controller= new DecisionController();
    $result=array(
    '#type'=>'fieldset',
    '#title'=>t('Select Decision'),
    );

    $sel_decision_nid=$node_decision_nid;

    if(empty($informacion_nid)){
        $sel_decision_nid=$decision_nid;
    }

    $rows=$decision_controller->decision_get_sel_decision_arbol_rows(0);
    
    $rows=$decision_controller->prepare_decision_arbol_by_pro($rows,1);

    if(count($rows)>0){
      $radio_button_list=0; 
      
      foreach($rows as $i=>$r){
        $pro=$r['my_level'];

        $result[$r['nid']] = array(
          //'#required' => TRUE,
          '#type' => 'checkbox',
          '#prefix' => '<div class=taxo'. ($pro-1) .'>',
          '#suffix' => '</div>',
          '#title' => $r['title'],
          '#name' => 'decision_txek['.$r['nid'].']',
        );
        
        if(!empty($sel_decision_nid) && $r['nid']==$sel_decision_nid){
          $result[$r['nid']]['#attributes']=array('checked' => 'checked');
        }
      }
    }

    $keys=array_keys($result);
    $keys=$decision_controller->get_numeric_values($keys);
    return $result;
  }  
}//class informacion controller
