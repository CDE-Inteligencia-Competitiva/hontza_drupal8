<?php
namespace Drupal\despliegue\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\estrategia\Controller\EstrategiaController;
use Drupal\vigilancia\Controller\VigilanciaController;

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
      //->fields('despliegue', array('nid','vid','origen_uid','grupo_nid','grupo_seguimiento_nid','estrategia_nid','fecha_cumplimiento','no_control_date','importancia_despliegue'))
      ->fields('despliegue')    
      ->condition('despliegue.nid',$nid)
      ->condition('despliegue.vid',$vid)
      ->orderBy('despliegue.nid', 'DESC')
      //->limit($limit)
      ->execute();
    $rows=array();  
    while($row=$query->fetchObject()){
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

  public function despliegue_define_fecha_cumplimiento($node,$field='fecha_cumplimiento'){
    if(!empty($node)){
      $nid=$node->id();    
      $vid=$node->getRevisionId();
      $despliegue_row=$this->despliegue_get_despliegue_row($nid,$vid);

      if(isset($despliegue_row->$field) && !empty($despliegue_row->$field)){
        //return $estrategia_row->$field;
        $fecha=date("Y-m-d",$despliegue_row->$field);
        return $fecha;
      }
    }
    //else{
      $fecha=date("Y-m-d", strtotime("+6 months"));
      return $fecha;
    //}
    //return 0;
  }

  public function despliegue_update_fecha_cumplimiento($node,$field='fecha_cumplimiento'){
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
    $fecha_cumplimiento=$post['fecha_cumplimiento'];
    $fecha_cumplimiento = strtotime($fecha_cumplimiento);
    $despliegue_row=$this->despliegue_get_despliegue_row($nid,$vid);

    if( isset($post['estrategia_txek'])){
      $estrategia_check_array = array_keys($post['estrategia_txek']);
      $estrategia_nid = $estrategia_check_array[0];
      //echo print_r ($estrategia_check_array,1); exit();
    }
    
    if( isset($post['no_control_date'])){
      $no_control_date=$post['no_control_date'];
    }else{
      $no_control_date=0;
    }
     
    if(isset($despliegue_row->nid) && !empty($despliegue_row->nid)){

      //if que te permite crear reto sin control date
      if(isset($no_control_date) && !empty($no_control_date)){
        $fecha_cumplimiento=$despliegue_row->fecha_cumplimiento;
      } 

      $query = \Drupal::database()->update('despliegue');
      $query->fields([
        //'grupo_nid' => $grupo_nid,
        'grupo_seguimiento_nid' => $grupo_nid,
        'importancia_despliegue' => $importancia_despliegue,
        'fecha_cumplimiento' => $fecha_cumplimiento,
        'no_control_date' => $no_control_date, 
        'estrategia_nid' => $estrategia_nid,
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
        'fecha_cumplimiento',
        'no_control_date',  
      ]);
      $query->values([
        $nid,
        $vid,
        $uid,
        $grupo_nid,
        $grupo_nid,
        $importancia_despliegue,
        $estrategia_nid,
        $fecha_cumplimiento,  
        $no_control_date,      
      ]);
      $query->execute();
    }
  }

  function create_despliegue_guraso_fieldset($despliegue_nid,$estrategia_nid,$node_estrategia_nid,&$keys){
    $estrategia_controller= new EstrategiaController();
    $result=array(
    '#type'=>'fieldset',
    '#title'=>t('Select Challenge'),
    );

    $sel_estrategia_nid=$node_estrategia_nid;

    if(empty($despliegue_nid)){
        $sel_estrategia_nid=$estrategia_nid;
    }

    $rows=$estrategia_controller->estrategia_get_sel_estrategia_arbol_rows(0);
    
    $rows=$estrategia_controller->prepare_estrategia_arbol_by_pro($rows,1);
    
    if(count($rows)>0){
      $radio_button_list=0;      
      
      foreach($rows as $i=>$r){      
        $pro=$r['my_level'];

        //para radios
        $options [$r['nid']] = $r['title'];

        $result[$r['nid']] = array(
                //'#required' => TRUE,
                '#type' => 'checkbox',
                '#prefix' => '<div class=taxo'. ($pro-1) .'>',
                '#suffix' => '</div>',
                '#title' => $r['title'],
                 '#name' => 'estrategia_txek['.$r['nid'].']',
              );

        if(!empty($sel_estrategia_nid) && $r['nid']==$sel_estrategia_nid){
          $result[$r['nid']]['#attributes']=array('checked' => 'checked');
        }
      }
    }
    
    $keys=array_keys($result); 
    $keys=$estrategia_controller->get_numeric_values($keys);
    return $result;
  }

  public function despliegue_get_sel_despliegue_arbol_rows($my_list){
    $my_list=$this->despliegue_get_despliegue_arbol_rows(0);
    $arbol=array();

    if(count($my_list)>0){
    $kont=0;
      foreach($my_list as $i=>$node){
        $arbol[$kont]['title']=$node->label();
        $arbol[$kont]['nid']=$node->id();
        $arbol[$kont]['my_level']=1;
        $kont++;

        /*$despliegue_list=get_estrategia_despliegue_list($node->nid);
        if(count($despliegue_list)>0){
          foreach($despliegue_list as $k=>$despliegue){
            $arbol[$kont]['title']=$despliegue->title;
            $arbol[$kont]['nid']=$despliegue->nid;
            $arbol[$kont]['my_level']=2;                          
            $kont++;
           
            $decision_list=get_despliegue_decision_list($despliegue->nid);
            //print count($decision_list).'<BR>';
            if(count($decision_list)>0){
              foreach($decision_list as $a=>$decision){
                $arbol[$kont]['title']=$decision->title;
                $arbol[$kont]['nid']=$decision->nid;
                $arbol[$kont]['my_level']=3;
                $kont++;
                $informacion_list=get_decision_informacion_list($decision->nid);
                //print count($informacion_list).'<BR>';
                if(count($informacion_list)>0){
                  foreach($informacion_list as $b=>$informacion){
                    $arbol[$kont]['title']=$informacion->title;
                    $arbol[$kont]['nid']=$informacion->nid;
                    $arbol[$kont]['my_level']=4;
                    $kont++;
                  }
                }
              }
            }
          }
        }*/
      }
    }
   /* echo print_r($my_id_array,1);
    if(is_idea()){
      add_js_seleccionar_idea_estrategia($my_id_array);
    }*/
    return $arbol;
  } 

  function prepare_despliegue_arbol_by_pro($rows,$pro){
    $result=array();
    if(count($rows)>0){
      foreach($rows as $i=>$r){
        if($r['my_level']<=$pro){
           $result[]=$r;
        }
      }
    }
    return $result;
  }

  function despliegue_get_despliegue_arbol_rows($is_link=1){
    //$order_by=' ORDER BY e.peso ASC,n.sticky DESC, n.created ASC,n.nid ASC';
    
    //hontza5
    //$order_by=estrategia_inc_get_order_by($order_by);
    
    /*$sql='SELECT n.nid, n.sticky, n.created
    FROM {node} n
    LEFT JOIN {estrategia} e ON n.nid=e.nid
    WHERE '.implode(' AND ',$where).$order_by;*/

    $vigilancia=new VigilanciaController();  
    $my_grupo=$vigilancia->vigilancia_get_grupo_default_value();
    $gid=0;
    if(!empty($my_grupo)){
      $gid=$my_grupo->id();
    }

    $db = \Drupal::database();
    $query=$db->select('node', 'n');
    $query->leftJoin('node_field_data','node_field_data','n.vid=node_field_data.vid');
    $query->leftJoin('despliegue','e','node_field_data.vid=e.vid');
    $result=$query->fields('n', array('nid'))
    ->fields('node_field_data',array('sticky', 'created'))
    ->condition('e.grupo_nid',$gid)
    ->condition('e.grupo_seguimiento_nid',$gid)
    ->orderBy('e.peso', 'ASC')
    ->orderBy('node_field_data.sticky','DESC')
    ->orderBy('node_field_data.created','ASC')
    ->orderBy('n.nid','ASC')
    ->execute()
    ->fetchAll();
    $rows=array(); 
    //while($row=$pager_data->fetchObject()){
    foreach ($result as $row){
      $node=Node::load($row->nid);
      $my_list[]=$node;        
    }
    //echo print_r($my_list,1);exit();

    if($is_link){
      print 'entra';exit();
      $estrategia_desplegar=new EstrategiaDesplegarController();
      $rows=$estrategia_desplegar->estrategia_create_arbol($my_list);
      return $rows;
    }else{
      return $my_list;
    }  
  }

  function get_numeric_values($my_array){
    $result=array();
    if(count($my_array)>0){
      foreach($my_array as $i=>$v){
        if(is_numeric($v)){
          $result[]=$v;
        }
      }
    }
    return $result;
  }

}//class despliegue controller