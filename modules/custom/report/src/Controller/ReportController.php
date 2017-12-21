<?php

namespace Drupal\report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Query\PagerSelectExtender;
//use Drupal\Core\Link;
use Drupal\grupo\Controller\GrupoController;
use Drupal\vigilancia\Controller\VigilanciaController;
/*use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Entity\Feed;
use Drupal\Core\Render\Markup;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;
use Drupal\vigilancia\Controller\VigilanciaExtraController;*/

class ReportController extends ControllerBase {

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'examples/page-example'.
   */

  public function report_area_report() {
    $build=array();
    $tempstore = \Drupal::service('user.private_tempstore')->get('grupo');
    $gid = $tempstore->get('grupo_select_gid');
    if(!empty($gid)){
      return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput('/group/'.$gid.'/reports')->toString());   
    }
    $build = array(
      '#markup'=>'Report',
    );
    return $build;
  }
  
  public function reports_grupo(){
    $this->report_set_title('List of Reports');

    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $limit=10;
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-report';    
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

     //$build['vigilancia'] = array(
      //'#rows' => $rows,
      //'#header' => array(t('NID'), t('Title')),
      //'#type' => 'teaser',
      //'#empty' => t('No content available.'),
    //);
    $build['#markup']=$this->reports_menu().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
    return $build;
  }
    public function report_on_entity_save($entity,$action){
    if(!empty($entity)){
      if($this->report_is_entity_report($entity)){
        $post=\Drupal::request()->request->all();
        if($this->report_is_post_form_save($post)){
            $my_group_gid=$entity->get('field_my_group')->getValue();
            //echo print_r($my_group_gid,1);exit();
            $grupo_nid=0;
            if(isset($my_group_gid[0]['target_id']) && !empty($my_group_gid[0]['target_id'])){
              $grupo_nid=$my_group_gid[0]['target_id'];
              if($action=='insert'){          
                $my_group=Group::load($my_group_gid[0]['target_id']);
                $my_plugin_id='group_node:report';
                $my_group->addContent($entity,$my_plugin_id);
              }
            }
            //$this->report_save($entity,$grupo_nid);   
        }
      }  
    }  
  }
  private function report_is_post_form_save($post){
    $form_id_array=array('node_report_form','node_report_edit_form');
    if(isset($post['form_id']) && in_array($post['form_id'],$form_id_array)){
      return 1;
    }
    return 0;  
  }
  public function report_is_entity_report($entity,$type_id_in=''){
    $type_id=$entity->getEntityTypeId();
    if($type_id=='node'){ 
      $node_type=$entity->getType();
      $node_type_array=array('report');
      if(in_array($node_type,$node_type_array)){
        return 1;
      }    
    }
    return 0;
  }
  private function reports_menu(){
    return '';
  }
  private function report_set_title($title){
          $request = \Drupal::request();
          if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
            $route->setDefault('_title', $title);
          }
  }
  public function report_grupo_add_categorias_form_field(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id){
    $vigilancia=new VigilanciaController();
    $grupo_controller=new GrupoController();    
    $my_grupo=$grupo_controller->grupo_get_current_grupo();
    $entity=$form_state->getFormObject()->getEntity();
    $tid_array=array();
    if(!empty($entity)){
      $tid_array=$vigilancia->vigilancia_get_field_item_canal_category_tid_array($entity);
    }    
    if(!empty($my_grupo)){
      $categorias_vid=$grupo_controller->grupo_get_categorias_vid($my_grupo);    
      $gid=$my_grupo->id();
      if(is_numeric($gid)){
        $categorias_array = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($categorias_vid);
        if(!empty($categorias_array)){
          $fieldset='categorias_fs';
          $form[$fieldset]=array();
          $form[$fieldset]['#type']='fieldset';
          $form[$fieldset]['#title']=t('Categories');
          foreach($categorias_array as $i=>$term){
              $pro=$vigilancia->vigilancia_get_profundidad($term->tid);
                        $key='my_tipo_de_item_categoria_'.$term->tid;
                        $form[$fieldset][$key]= array(
                          //'#required' => TRUE,
                          '#type' => 'checkbox',
                          '#prefix' => '<div class=taxo'. $pro .'>',
                          '#suffix' => '</div>',
                          '#title' => $term->name,
                          '#name'=>'my_tipo_de_item_categoria['.$term->tid.']',
                        );
                        if(in_array($term->tid,$tid_array)){
                          $form[$fieldset][$key]['#attributes']=array('checked'=>'checked');
                        }
          }          
        }
      }
    }  
  }
  public function report_on_entity_presave($entity) {
    $this->report_on_categorias_entity_presave($entity);
  }
  public function report_on_categorias_entity_presave($entity){
    if(!empty($entity)){
      $type_id=$entity->getEntityTypeId();
      if($this->report_is_entity_report($entity)){
        $post=\Drupal::request()->request->all();
       //   $tipodeitemcategoria_tid_array=array();
          $tid_array=array();
          if($this->report_is_post_form_save($post)){
            if(!empty($post)){            
              /*foreach($post as $field=>$value){
                $konp='my_tipo_de_item_categoria_';
                $pos=strpos($field,$konp);
                if($pos===FALSE){
                  continue;
                }else if($pos>=0){
                  $tid=str_replace($konp,'',$field);
                  $tipodeitemcategoria_tid_array[]=$tid;
                }  
              }*/
              /*echo print_r($post,1); 
              exit;*/
              if(isset($post['my_tipo_de_item_categoria']) && !empty($post['my_tipo_de_item_categoria'])){
                foreach($post['my_tipo_de_item_categoria'] as $tid=>$value){
                  if(!empty($value)){
                    $tid_array[]=$tid;
                  }
                }
              }
            }
            $entity->set('field_item_canal_category_tid',$tid_array);        
          }            
      }  
    }  
  }
}//class ReportController