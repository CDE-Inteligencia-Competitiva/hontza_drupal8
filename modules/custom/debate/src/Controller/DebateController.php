<?php

namespace Drupal\debate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Link;
use Drupal\grupo\Controller\GrupoController;
use Drupal\vigilancia\Controller\VigilanciaController;
use Drupal\user\Entity\User;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
/*
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Entity\Feed;
use Drupal\taxonomy\Entity\Term;
use Drupal\vigilancia\Controller\VigilanciaExtraController;*/

class DebateController extends ControllerBase {

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'examples/page-example'.
   */

  public function debate_area_debate() {
    $build=array();
    $tempstore = \Drupal::service('user.private_tempstore')->get('grupo');
    $gid = $tempstore->get('grupo_select_gid');
   
    if(!empty($gid)){
      return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput('/group/'.$gid.'/debates')->toString());   
    }/* elseif (!empty($search)) {
      return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput('/group/'.$gid.'/debates'.$search)->toString());   
    }*/
    $build = array(
      '#markup'=>'Debate',
    );
    return $build;
  }
  public function debates_grupo(){
    $this->debate_set_title('List of Discussions');

    $my_request = \Drupal::request();
    $search=$my_request->get('search');
    //print $search;exit();
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
  
    $limit=10;
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-debate';    
    $pager_data=$db->select('node', 'node')
      ->extend(PagerSelectExtender::class);
    $pager_data->leftJoin('group_content_field_data','group_content_field_data','node.nid=group_content_field_data.entity_id');
    $pager_data->leftJoin('node_field_data','node_field_data','group_content_field_data.entity_id=node_field_data.nid');
    $pager_data->leftJoin('node__body','node__body','group_content_field_data.entity_id=node__body.entity_id');
    //  ->fields('group_content_field_data', array('entity_id'))
    $pager_data->fields('node', array('nid'));
    $pager_data->condition('node.type','debate');
    $pager_data->condition('group_content_field_data.type',$type);
    $pager_data->condition('group_content_field_data.gid',$gid);
   // ->condition('group_content_field_data.search',$search)
      if(!empty($search)){
      $or=$pager_data->orConditionGroup()
        ->condition('node_field_data.title','%'.$pager_data->escapeLike($search).'%', 'LIKE')
        ->condition('node__body.body_value','%'.$pager_data->escapeLike($search).'%', 'LIKE');
      $pager_data->condition($or);
      }     
    $pager_data->orderBy('group_content_field_data.created', 'DESC');
    $pager_data->limit($limit);
    $res=$pager_data->execute(); 
    $rows=array();  
      //while($row=$pager_data->fetchObject()){
      foreach($res as $row){
        //$node=Node::load($row->entity_id);
        $node=Node::load($row->nid);
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

    $build['#markup']=implode('',$rows);
    if (empty($rows)){
      $build['#markup']=$this->t('No contents');
    }
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
    return $build;
  }
  public function debate_on_entity_save($entity,$action){
    if(!empty($entity)){
      if($this->debate_is_entity_debate($entity)){
        $post=\Drupal::request()->request->all();
        if($this->debate_is_post_form_save($post)){
            $my_group_gid=$entity->get('field_my_group')->getValue();
            //echo print_r($my_group_gid,1);exit();
            $grupo_nid=0;
            if(isset($my_group_gid[0]['target_id']) && !empty($my_group_gid[0]['target_id'])){
              $grupo_nid=$my_group_gid[0]['target_id'];
              if($action=='insert'){          
                $my_group=Group::load($my_group_gid[0]['target_id']);
                $my_plugin_id='group_node:debate';
                $my_group->addContent($entity,$my_plugin_id);
              }
            }
            $this->debate_save($entity,$grupo_nid,$post,$action);   
        }
      }  
    }  
  }
  private function debate_is_post_form_save($post){
    $form_id_array=array('node_debate_form','node_debate_edit_form');
    if(isset($post['form_id']) && in_array($post['form_id'],$form_id_array)){
      return 1;
    }
    return 0;  
  }
  public function debate_is_entity_debate($entity,$type_id_in=''){
    $type_id=$entity->getEntityTypeId();
    if($type_id=='node'){ 
      $node_type=$entity->getType();
      $node_type_array=array('debate');
      if(in_array($node_type,$node_type_array)){
        return 1;
      }    
    }
    return 0;
  }
  private function debates_menu(){
    return '';
  }
  private function debate_set_title($title){
          $request = \Drupal::request();
          if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
            $route->setDefault('_title', $title);
          }
  }
  public function debate_grupo_add_categorias_form_field(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id){
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
  //revisar
  public function debate_grupo_add_tags_form_field(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id){
    /*$vigilancia=new VigilanciaController();
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
    }*/  
  }

  public function debate_on_entity_presave($entity) {
    $this->debate_on_categorias_entity_presave($entity);
  }
  public function debate_on_categorias_entity_presave($entity){
    if(!empty($entity)){
      $type_id=$entity->getEntityTypeId();
      if($this->debate_is_entity_debate($entity)){
        $post=\Drupal::request()->request->all();
          //$tipodeitemcategoria_tid_array=array();
          $tid_array=array();
          if($this->debate_is_post_form_save($post)){
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
  public function enlazar_debate(){
    //print 'prueba';exit();   
  
    $parameters = \Drupal::routeMatch()->getParameters();
    $node=$parameters->get('node');
  
    $noticia=Node::load($node);
    $entity_type='node';
    
    //$view_mode='teaser';

    $view_mode='full';
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $view_build = $view_builder->view($noticia, $view_mode);
    $node_view = render($view_build);
    
    $build['#markup']=$node_view;
    return $build; 
  } 

  public function debate_enlazar_debate_html(){
    
    $parameters = \Drupal::routeMatch()->getParameters();
    $item_nid=$parameters->get('node');
    
    $vigilancia=new VigilanciaController();  
    $my_grupo=$vigilancia->vigilancia_get_grupo_default_value();
    $gid=0;
    if(!empty($my_grupo)){
      $gid=$my_grupo->id();
    }

 //hontza5  $headers=array();
 //   $headers[0]=array('data'=>t('Title'),'field'=>'node_title');
 //   $headers[1]=array('data'=>t('Creator'),'field'=>'username');
 //   $headers[2]=array('data'=>t('Creation date'),'field'=>'node_created');
 //   $headers[3]=t('Actions');

    $headers=array();
    $headers[0]=t('Title');
    $headers[1]=t('Creator');
    $headers[2]=t('Creation date');
    $headers[3]=t('Actions');

    $limit=100;
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-debate';    
    $pager_data=$db->select('node', 'node')
      ->extend(PagerSelectExtender::class);
    $pager_data->leftJoin('group_content_field_data','group_content_field_data','node.nid=group_content_field_data.entity_id');
    $pager_data->leftJoin('node_field_data','node_field_data','group_content_field_data.entity_id=node_field_data.nid');
    //$pager_data->leftJoin('node__body','node__body','group_content_field_data.entity_id=node__body.entity_id');
    //  ->fields('group_content_field_data', array('entity_id'))
    $pager_data->fields('node', array('nid'));
    $pager_data->fields('node_field_data', array('uid','created','title'));
    $pager_data->condition('node.type','debate');
    $pager_data->condition('group_content_field_data.type',$type);
    $pager_data->condition('group_content_field_data.gid',$gid);
   // ->condition('group_content_field_data.search',$search)   
    $pager_data->orderBy('group_content_field_data.created', 'DESC');
    $pager_data->limit($limit);
    $res=$pager_data->execute(); 
    $rows=array();  
 
    $kont=0;
    foreach($res as $r){
      //$node=Node::load($row->entity_id);
      $node=Node::load($r->nid);
      $debate_nid=0;
      if (!empty($node)){
       $debate_nid=$node->id();
      }
  
      $is_activado=0;
      //$icono_activado=hontza_enlazar_debate_asociado_icono_activado($r,$nid,0,$is_activado);      
      //intelsat-2015
      $icono_activado='';
      /*if(hontza_solr_funciones_is_pantalla_bookmark_multiple_mode()){
         if($is_activado){
             continue;
         } 
      }*/
      //
      $rows[$kont]=array();   
      if ($kont == 0){
        $rows[$kont][0]='';
        $rows[$kont][1]='';
        $rows[$kont][2]='';
        $rows[$kont][3]=$this->debate_define_debate_new_rows($item_nid);
        $kont++;  
      } 
      $rows[$kont][0]=$icono_activado.'&nbsp;'.$r->title;
      $my_user=User::load($r->uid);
      $rows[$kont][1]=$my_user->getAccountName();
      $rows[$kont][2]='';
      if(!empty($r->created)){
        $rows[$kont][2]=date('Y-m-d',$r->created);
      }
      $rows[$kont][3]=$this->debate_node_enlazar_debate_define_acciones($r,$item_nid,$is_activado,$gid);
      $kont++;    
    }

    $build = array(
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
    );

    return $build;
  }

  public function confirm_enlazar_debate(){

    $parameters = \Drupal::routeMatch()->getParameters();
    $item_nid=$parameters->get('node');
    $debate_nid=$parameters->get('debate_nid');
  
    $this->debate_confirm_enlazar_debate($item_nid,$debate_nid);

    #comment-form
    $url = Url::fromRoute('entity.node.canonical', ['node' => $debate_nid])->toString();
    $url1 = $url.'#comment-form';
    return new \Symfony\Component\HttpFoundation\RedirectResponse($url1);
    
    //return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput('/comment/reply/'.$debate_nid.'/item_nid')->toString()); 
    
    $build[] = array(
      '#markup' => 'prueba'
       );
    return $build;
  }
  public function debate_node_enlazar_debate_define_acciones($r,$item_nid,$is_activado,$gid){

    global $base_url;
    $path=$base_url.'/'.\Drupal::theme()->getActiveTheme()->getPath();
    $img_src=$path.'/images/icons/debatir.png';
    //print $img_src;exit();
    $title_enlazar=t('');    
    $img='<img src="'.$img_src.'" alt="'.$title_enlazar.'" title="'.$title_enlazar.'">';    
    $route_string='confirm_enlazar_debate';
    $title_enlazar=Markup::create($img);  
    $link=Link::createFromRoute($title_enlazar,$route_string,array('group'=>$gid,'node'=>$item_nid,'debate_nid'=>$r->nid)); 
    $my_render=$link->toRenderable();
    $html[]=render($my_render);

    return implode('&nbsp;',$html);
  }
 
  private function debate_confirm_enlazar_debate($item_nid,$debate_nid){
    $item_nid_array=explode(',',$item_nid);
    //intelsat-2015
    //$item_nid_array=hontza_solr_funciones_get_node_id_array_by_arg($item_nid_array);
    $num=count($item_nid_array);    
    //if(count($item_nid_array)>1){
    if($num>1){    
        foreach($item_nid_array as $i=>$value){
            $this->delete_enlazar_debate($debate_nid,$value);
            $this->insert_enlazar_debate($debate_nid,$value);
        }
    }else{
        $this->delete_enlazar_debate($debate_nid,$item_nid);
        $this->insert_enlazar_debate($debate_nid,$item_nid);
    }    
    //
    //drupal_goto('node/'.$nid.'/enlazar_debate');
    //drupal_goto('node/'.$debate_nid);
    //drupal_goto('node/'.$debate_nid.'/edit');
    //drupal_goto('comment/reply/'.$debate_nid);
    //intelsat-2015
    //red_set_bulk_command_executed_message($num);
    //drupal_goto('comment/reply/'.$debate_nid,array('item_nid'=>$item_nid));
    //return new \Symfony\Component\HttpFoundation\RedirectResponse('/node/'.$item_nid);
   // exit();
  }
  private function delete_enlazar_debate($debate_nid,$item_nid){
    $query = \Drupal::database()->delete('enlazar_debate');
    $query->condition('debate_nid',$debate_nid);
    $query->condition('item_nid',$item_nid);
    $query->execute(); 
  } 
  private function insert_enlazar_debate($debate_nid,$item_nid){
    $query = \Drupal::database()->insert('enlazar_debate');
    $query->fields([
      'debate_nid',
      'item_nid', 
     // 'enlazar_uid',
      'enlazar_created',
      ]);
    $query->values([
      $debate_nid,
      $item_nid,
  //    $user->uid,
      time()
      ]);
    $query->execute(); 
  }
  public function debate_is_enlazar_debate_pantalla(){
    $debate_block_controller = new DebateBlockController();
    $arg = $debate_block_controller->debate_arg();
    //echo print_r($arg,1); exit();
    if (isset($arg[5]) && !empty($arg[5]) && $arg[5]=='enlazar_debate'){
      return 1;
    }
    return 0;
  }

  public function debate_is_confirm_enlazar_debate_pantalla(){
    
    $debate_block_controller = new DebateBlockController();
    $arg = $debate_block_controller->debate_arg();
   //echo print_r($arg,1); exit();
   // if (isset($arg[2]) && !empty($arg[2]) && is_numeric($arg[2])){ 
    if (isset($arg[5]) && !empty($arg[5]) && $arg[5]=='confirm_enlazar_debate'){
      return 1;
    }
    return 0;
  }


  public function debate_define_debate_new_rows($item_nid){
    global $base_url;
    $path=$base_url.'/'.\Drupal::theme()->getActiveTheme()->getPath();
    $img_src=$path.'/images/icons/add_left.png';
    //print $img_src;exit();
    $title_enlazar=t('Create new discussion');    
    $img='<img src="'.$img_src.'" alt="'.$title_enlazar.'" title="'.$title_enlazar.'">';     
    $route_string='node.add';
    $title_enlazar=Markup::create($img);  
    //revisar
    //$link=Link::createFromRoute($title_enlazar,$route_string,array('node' => $item_nid,'node_type' =>'debate'));  
    $link=Link::createFromRoute($title_enlazar,$route_string,array('origin_nid' => $item_nid,'node_type' =>'debate'));  
    $my_render=$link->toRenderable();
    $html[]=render($my_render);

    return implode('&nbsp;',$html);

  }

  public function debate_links_debate_html(){

    $debate_block_controller = new DebateBlockController();
    $arg = $debate_block_controller->debate_arg();
    //echo print_r($arg,2); exit();


    $vigilancia=new VigilanciaController();  
    $my_grupo=$vigilancia->vigilancia_get_grupo_default_value();
    $gid=0;
    if(!empty($my_grupo)){
      $gid=$my_grupo->id();
    }
   
    $limit=100;
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-item';    
    $pager_data=$db->select('enlazar_debate', 'enlazar_debate')
      ->extend(PagerSelectExtender::class);
    $pager_data->leftJoin('group_content_field_data','group_content_field_data','enlazar_debate.item_nid=group_content_field_data.entity_id');
    $pager_data->leftJoin('node_field_data','node_field_data','enlazar_debate.item_nid=node_field_data.nid');
    //$pager_data->leftJoin('enlazar_debate','enlazar_debate','node_field_data.nid=enlazar_debate.id');
    //  ->fields('group_content_field_data', array('entity_id'))
    //$pager_data->fields('node', array('nid'));
    $pager_data->fields('node_field_data', array('title'));
    $pager_data->fields('enlazar_debate', array('id','enlazar_created','debate_nid','item_nid'));
    $pager_data->condition('node_field_data.type','item');
    $pager_data->condition('enlazar_debate.debate_nid',$arg[2]);
    $pager_data->condition('group_content_field_data.type',$type);
    $pager_data->condition('group_content_field_data.gid',$gid);
    $pager_data->orderBy('enlazar_debate.enlazar_created', 'ASCE');
    $pager_data->limit($limit);
    $res=$pager_data->execute();  

    /*
      $query = \Drupal::database()->select('enlazar_debate', 'ed');
      $query->fields('ed', ['id','enlazar_created','debate_nid']);
      $query->fields('nfd', ['title']);
      //$query->addField('ufd', 'name');
      $query->leftJoin('node_field_data', 'nfd','ed.item_nid = nfd.nid');
      $query->condition('nfd.type', 'item');
      $res = $query->execute();
      //->fetchAllAssoc('nid');
    */
    $rows=array();  
    $kont=0;
  
    foreach($res as $r){
      //echo print_r($r,1); exit();
      $node=Node::load($r->item_nid);
      $item_nid=0;
      if (!empty($node)){
       $item_nid=$node->id();
      }

      $rows[$kont]=array();   
      $rows[$kont][0]=$this->debate_node_delete_enlazar_debate_define_acciones($item_nid,$r->debate_nid,$gid);
      $rows[$kont][1]='';
      if(!empty($r->enlazar_created)){
         $rows[$kont][1]=date('Y-m-d',$r->enlazar_created);
      }
      $rows[$kont][2]='&nbsp;'.$r->title;
      $kont++;    
    }

    $build = array(
      '#type' => 'table',
      //'#title'=> t('Links');
      '#rows' => $rows,
      '#prefix' => '<h3>'.t('Links').'</h3>',
      //'#suffix' => '<span class="suffix">Count</span></div>',
    );
    
    return $build;  

  }

  public function debate_node_delete_enlazar_debate_define_acciones($item_nid, $debate_nid,$gid){
    global $base_url;
    $path=$base_url.'/'.\Drupal::theme()->getActiveTheme()->getPath();
    $img_src=$path.'/images/icons/delete.png';
    //print $img_src;exit();
    $title_enlazar=t('Delete');    
    $img='<img src="'.$img_src.'" alt="'.$title_enlazar.'" title="'.$title_enlazar.'">';     
    $title_enlazar=Markup::create($img);  
    $route_string='unlink_debate';
    $link=Link::createFromRoute($title_enlazar,$route_string,array('debate_nid' => $debate_nid,'group' =>$gid,'node' => $item_nid));//revisar
    $my_render=$link->toRenderable();
    $html[]=render($my_render);

    return implode('&nbsp;',$html);
  }

  function debate_solr_funciones_get_node_id_array_by_arg_string($node_id_array){
    if($node_id_array=='is_all_selected'){
    //revisar cuando sea necesario
        $result=$this->debate_solr_funciones_get_result_node_id_array();
        return implode(',',$result);
    }
    return $node_id_array;
  }
  
  function debate_solr_funciones_get_result_node_id_array(){
    $result=array();
   /* if(isset($_SESSION['my_results_solr']) && !empty($_SESSION['my_results_solr'])){
        foreach($_SESSION['my_results_solr'] as $i=>$row){
            $result[]=$row['node']->entity_id;
        }
    }else{
        return hontza_solr_search_get_result_node_id_array();
    }*/
    return $result;
  }
  function debate_save($entity,$grupo_nid,$post,$action){
    //print 'debate_save'.$post['origin_nid']; exit();
    $debate_nid=$entity->id();
    //print $debate_nid;exit();
    if($action=='insert'){
      $this->debate_confirm_enlazar_debate($post['origin_nid'],$debate_nid); 
   /* $url = Url::fromRoute('entity.node.canonical', ['node' => $debate_nid])->toString();
    $url1 = $url.'#comment-form';
    return new \Symfony\Component\HttpFoundation\RedirectResponse($url1);*/  
    }
  }

  function unlink_debate(){
    $parameters = \Drupal::routeMatch()->getParameters();
    $item_nid=$parameters->get('node');
    $debate_nid=$parameters->get('debate_nid');

    $this->delete_enlazar_debate($debate_nid,$item_nid);
     
    $url = Url::fromRoute('entity.node.canonical', ['node' => $debate_nid])->toString();
    $url1 = $url.'#comment-form';
    return new \Symfony\Component\HttpFoundation\RedirectResponse($url1);

    $build[] = array(
      '#markup' => 'prueba'
       );
    return $build;  
  }
  public function debate_title_links_debate_html(){
    $links_debate_html= $this->debate_links_debate_html();
  //$links_debate_html='<h3>'.t('Links').'</h3>'.render($links_debate_html);
    $links_debate_html=render($links_debate_html);
    return $links_debate_html;

  }
  
}//class DebateController