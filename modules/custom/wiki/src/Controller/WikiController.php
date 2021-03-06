<?php

namespace Drupal\wiki\Controller;

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
/*use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Entity\Feed;
use Drupal\Core\Render\Markup;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;
use Drupal\vigilancia\Controller\VigilanciaExtraController;*/

class WikiController extends ControllerBase {

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'examples/page-example'.
   */

  public function wiki_area_wiki() {
    $build=array();
    $tempstore = \Drupal::service('user.private_tempstore')->get('grupo');
    $gid = $tempstore->get('grupo_select_gid');
    if(!empty($gid)){
      return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput('/group/'.$gid.'/wikis')->toString());   
    }
    $build = array(
      '#markup'=>'Wiki',
    );
    return $build;
  }
  public function wikis_grupo(){
    $this->wiki_set_title('List of Wikis');

    $my_request = \Drupal::request();
    $search=$my_request->get('search');

    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $limit=10;
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-wiki';
    $pager_data=$db->select('node', 'node')
      ->extend(PagerSelectExtender::class);    
    $pager_data->leftJoin('group_content_field_data','group_content_field_data','node.nid=group_content_field_data.entity_id');
    $pager_data->leftJoin('node_field_data','node_field_data','group_content_field_data.entity_id=node_field_data.nid');
    $pager_data->leftJoin('node__body','node__body','group_content_field_data.entity_id=node__body.entity_id');
    //  ->fields('group_content_field_data', array('entity_id'))
    $pager_data->fields('node', array('nid'));
    $pager_data->condition('node.type','wiki');
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

  //  while($row=$pager_data->fetchObject()){
  //      $node=Node::load($row->entity_id);
    foreach($res as $row){
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
    $build['#markup']=$this->wikis_menu().implode('',$rows);
    if (empty($rows)){
      $build['#markup']=$this->t('No contents');
    }
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
    return $build;
  }
  public function wiki_on_entity_save($entity,$action){
    if(!empty($entity)){
      if($this->wiki_is_entity_wiki($entity)){
        $post=\Drupal::request()->request->all();
        if($this->wiki_is_post_form_save($post)){
            $my_group_gid=$entity->get('field_my_group')->getValue();
            //echo print_r($my_group_gid,1);exit();
            $grupo_nid=0;
            if(isset($my_group_gid[0]['target_id']) && !empty($my_group_gid[0]['target_id'])){
              $grupo_nid=$my_group_gid[0]['target_id'];
              if($action=='insert'){          
                $my_group=Group::load($my_group_gid[0]['target_id']);
                $my_plugin_id='group_node:wiki';
                $my_group->addContent($entity,$my_plugin_id);
              }
            }
            $this->wiki_save($entity,$grupo_nid,$post,$action);   
        }
      }  
    }  
  }
  private function wiki_is_post_form_save($post){
    $form_id_array=array('node_wiki_form','node_wiki_edit_form');
    if(isset($post['form_id']) && in_array($post['form_id'],$form_id_array)){
      return 1;
    }
    return 0;  
    $this->wiki_is_confirm_enlazar_wiki_pantalla(); 
  }
  public function wiki_is_entity_wiki($entity,$type_id_in=''){
    $type_id=$entity->getEntityTypeId();
    if($type_id=='node'){ 
      $node_type=$entity->getType();
      $node_type_array=array('wiki');
      if(in_array($node_type,$node_type_array)){
        return 1;
      }    
    }
    return 0;
  }
  private function wikis_menu(){
    return '';
  }
  private function wiki_set_title($title){
          $request = \Drupal::request();
          if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
            $route->setDefault('_title', $title);
          }
  }
  public function wiki_grupo_add_categorias_form_field(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id){
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
  public function wiki_on_entity_presave($entity) {
    $this->wiki_on_categorias_entity_presave($entity);
  }
  public function wiki_on_categorias_entity_presave($entity){
    if(!empty($entity)){
      $type_id=$entity->getEntityTypeId();
      if($this->wiki_is_entity_wiki($entity)){
        $post=\Drupal::request()->request->all();
          //$tipodeitemcategoria_tid_array=array();
           $tid_array=array();
          if($this->wiki_is_post_form_save($post)){
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

  public function enlazar_wiki(){
    /* $build[] = array(
      '#markup' => 'prueba'
       );
    return $build;*/
    $parameters = \Drupal::routeMatch()->getParameters();
    $node=$parameters->get('node');
  
    $noticia=Node::load($node);
    $entity_type='node';
    
    $view_mode='full';
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $view_build = $view_builder->view($noticia, $view_mode);
    $node_view = render($view_build);
           
    $build['#markup']=$node_view;
    return $build; 
  }

  public function wiki_enlazar_wiki_html(){

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
    $type=$group_type_id.'-group_node-wiki';    
    $pager_data=$db->select('node', 'node')
      ->extend(PagerSelectExtender::class);
    $pager_data->leftJoin('group_content_field_data','group_content_field_data','node.nid=group_content_field_data.entity_id');
    $pager_data->leftJoin('node_field_data','node_field_data','group_content_field_data.entity_id=node_field_data.nid');
    //$pager_data->leftJoin('node__body','node__body','group_content_field_data.entity_id=node__body.entity_id');
    //  ->fields('group_content_field_data', array('entity_id'))
    $pager_data->fields('node', array('nid'));
    $pager_data->fields('node_field_data', array('uid','created','title'));
    $pager_data->condition('node.type','wiki');
    $pager_data->condition('group_content_field_data.type',$type);
    $pager_data->condition('group_content_field_data.gid',$gid);
   // ->condition('group_content_field_data.search',$search)   
    $pager_data->orderBy('group_content_field_data.created', 'DESC');
    $pager_data->limit($limit);
    $res=$pager_data->execute(); 
    $rows=array();  
    $html=array(); 
    $kont=0;
    foreach($res as $r){
      //$node=Node::load($row->entity_id);
      $node=Node::load($r->nid);
      $wiki_nid=0;
      if (!empty($node)){
       $wiki_nid=$node->id();
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
        $rows[$kont][3]=$this->wiki_define_wiki_new_rows($item_nid);
        $kont++;  
      } 

      $rows[$kont]=array();      
      $rows[$kont][0]=array('data'=>$icono_activado.'&nbsp;'.$r->title);
      $my_user=User::load($r->uid);
      $rows[$kont][1]=$my_user->getAccountName();
      $rows[$kont][2]='';
      if(!empty($r->created)){
        $rows[$kont][2]=date('Y-m-d',$r->created);
      }
      $rows[$kont][3]=$this->wiki_node_enlazar_wiki_define_acciones($r,$item_nid,$is_activado,$gid);
      $kont++;    
    }
    
    $build['teaser'] = array(
      '#theme' => 'table',
      //'#cache' => ['disabled' => TRUE],
      //'#caption' => 'The table caption / Title',
      '#header' => $headers,
      '#rows' => $rows,
    );
    return $build;
  }

    public function wiki_node_enlazar_wiki_define_acciones($r,$item_nid,$is_activado,$gid){

    global $base_url;
    $path=$base_url.'/'.\Drupal::theme()->getActiveTheme()->getPath();
    $img_src=$path.'/images/icons/wiki32.png';
    //print $img_src;exit();
    $title_enlazar=t('');    
    $img='<img src="'.$img_src.'" alt="'.$title_enlazar.'" title="'.$title_enlazar.'">';    
    $route_string='confirm_enlazar_wiki';
    $title_enlazar=Markup::create($img);  
    $link=Link::createFromRoute($title_enlazar,$route_string,array('group'=>$gid,'node'=>$item_nid,'wiki_nid'=>$r->nid)); 
    $my_render=$link->toRenderable();
    $html[]=render($my_render);

    return implode('&nbsp;',$html);
  }

  public function confirm_enlazar_wiki(){
    $parameters = \Drupal::routeMatch()->getParameters();
    $item_nid=$parameters->get('node');
    $wiki_nid=$parameters->get('wiki_nid');
  
    $this->wiki_confirm_enlazar_wiki($item_nid,$wiki_nid);
    $url = Url::fromRoute('entity.node.canonical', ['node' => $wiki_nid])->toString();
         // $url='node/'.$r->nid.'/edit';

    $path=$url.'/edit';
    return new \Symfony\Component\HttpFoundation\RedirectResponse($path);
   
     $build[] = array(
      '#markup' => 'prueba'
       );
    return $build;
  }

  
  private function wiki_confirm_enlazar_wiki($item_nid,$wiki_nid){
    $item_nid_array=explode(',',$item_nid);
    //intelsat-2015
    //$item_nid_array=hontza_solr_funciones_get_node_id_array_by_arg($item_nid_array);
    $num=count($item_nid_array);    
    //if(count($item_nid_array)>1){
    if($num>1){    
        foreach($item_nid_array as $i=>$value){
            $this->delete_enlazar_wiki($wiki_nid,$value);
            $this->insert_enlazar_wiki($wiki_nid,$value);
        }
    }else{
        $this->delete_enlazar_wiki($wiki_nid,$item_nid);
        $this->insert_enlazar_wiki($wiki_nid,$item_nid);
    }    
    //
    //drupal_goto('node/'.$nid.'/enlazar_wiki');
    //drupal_goto('node/'.$wiki_nid);
    //drupal_goto('node/'.$wiki_nid.'/edit');
    //drupal_goto('comment/reply/'.$wiki_nid);
    //intelsat-2015
    //red_set_bulk_command_executed_message($num);
    //drupal_goto('comment/reply/'.$wiki_nid,array('item_nid'=>$item_nid));
    //exit();
  }
  private function delete_enlazar_wiki($wiki_nid,$item_nid){
    $query  = \Drupal::database()->delete('enlazar_wiki');
    $query ->condition('wiki_nid',$wiki_nid);
    $query ->condition('item_nid',$item_nid);
    $query ->execute(); 
  } 
  private function insert_enlazar_wiki($wiki_nid,$item_nid){
    $query  = \Drupal::database()->insert('enlazar_wiki');
    $query ->fields([
      'wiki_nid',
      'item_nid', 
     // 'enlazar_uid',
      'enlazar_created',
      ]);
    $query ->values([
      $wiki_nid,
      $item_nid,
  //    $user->uid,
      time()
      ]);
    $query ->execute(); 
  }

  public function wiki_is_enlazar_wiki_pantalla(){
    $wiki_block_controller = new WikiBlockController();
    $arg = $wiki_block_controller->wiki_arg();
    //echo print_r($arg,1); exit();
    if (isset($arg[5]) && !empty($arg[5]) && $arg[5]=='enlazar_wiki'){
      return 1;
    }
    return 0;
  }

  public function wiki_is_confirm_enlazar_wiki_pantalla(){
    
    $wiki_block_controller = new WikiBlockController();
    $arg = $wiki_block_controller->wiki_arg();
   //echo print_r($arg,2); exit();
    if (isset($arg[5]) && !empty($arg[5]) && $arg[5]=='confirm_enlazar_wiki'){
      return 1;
    }
    if (!empty($arg[3]) && $arg[3]=='edit'){
      return 1;
    }
    return 0;
  }

  public function wiki_define_wiki_new_rows($item_nid){
    global $base_url;
    $path=$base_url.'/'.\Drupal::theme()->getActiveTheme()->getPath();
    $img_src=$path.'/images/icons/add_left.png';
    //print $img_src;exit();
    $title_enlazar=t('Create new wiki');    
    $img='<img src="'.$img_src.'" alt="'.$title_enlazar.'">';    
    $title_enlazar=Markup::create($img);  
    $route_string='node.add';
    $link=Link::createFromRoute($title_enlazar,$route_string,array('origin_nid' => $item_nid,'node_type' =>'wiki')); 
    $my_render=$link->toRenderable();
    $html[]=render($my_render);

    return implode('&nbsp;',$html);

  }

  public function wiki_links_wiki_html(){

    $wiki_block_controller = new WikiBlockController();
    $arg = $wiki_block_controller->wiki_arg();
    //echo print_r($arg,2); exit();
    

    $vigilancia=new VigilanciaController();  
    $my_grupo=$vigilancia->vigilancia_get_grupo_default_value();
    $gid=0;
    if(!empty($my_grupo)){
      $gid=$my_grupo->id();
    }
  
    $limit=100;

    //intelsat-2017-debate
    $rows=array();
    
    $group=Group::load($gid);    
    
    //intelsat-2017-debate
    if(!empty($group)){
      $group_type=$group->getGroupType();
      $group_type_id=$group_type->id();
      $db = \Drupal::database();
      $type=$group_type_id.'-group_node-item';    
      $pager_data=$db->select('enlazar_wiki', 'enlazar_wiki')
        ->extend(PagerSelectExtender::class);
      $pager_data->leftJoin('group_content_field_data','group_content_field_data','enlazar_wiki.item_nid=group_content_field_data.entity_id');
      $pager_data->leftJoin('node_field_data','node_field_data','enlazar_wiki.item_nid=node_field_data.nid');
      //$pager_data->fields('node', array('nid'));
      $pager_data->fields('node_field_data', array('title'));
      $pager_data->fields('enlazar_wiki', array('id','enlazar_created','wiki_nid', 'item_nid'));
      $pager_data->condition('node_field_data.type','item');
      $pager_data->condition('enlazar_wiki.wiki_nid',$arg[2]);
      $pager_data->condition('group_content_field_data.type',$type);
      $pager_data->condition('group_content_field_data.gid',$gid);
      $pager_data->orderBy('enlazar_wiki.enlazar_created', 'ASCE');
      $pager_data->limit($limit);
      $res=$pager_data->execute();  

      //$rows=array();  
      $kont=0;
    
      foreach($res as $r){

        $node=Node::load($r->item_nid);
        $item_nid=0;
        if (!empty($node)){
         $item_nid=$node->id();
        }

        $rows[$kont]=array();   
        $rows[$kont][0]=$this->wiki_node_delete_enlazar_wiki_define_acciones($item_nid,$r->wiki_nid,$gid);
        $rows[$kont][1]='';
        if(!empty($r->enlazar_created)){
          $rows[$kont][1]=date('Y-m-d',$r->enlazar_created);
        }
        $rows[$kont][2]='&nbsp;'.$r->title;
        $kont++;    
        }
    }    

    $build = array(
      '#type' => 'table',
      '#rows' => $rows,
      '#prefix' => '<h3>'.t('Links').'</h3>',
      //'#suffix' => '<span class="suffix">Count</span></div>',
    );

    return $build;
  }

  public function wiki_node_delete_enlazar_wiki_define_acciones($item_nid,$wiki_nid,$gid){
    global $base_url;
    $path=$base_url.'/'.\Drupal::theme()->getActiveTheme()->getPath();
    $img_src=$path.'/images/icons/delete.png';
    //print $img_src;exit();
    $title_enlazar=t('Delete');    
    $img='<img src="'.$img_src.'" alt="'.$title_enlazar.'" title="'.$title_enlazar.'">';     
    $title_enlazar=Markup::create($img);  
    $route_string='unlink_wiki';
    $link=Link::createFromRoute($title_enlazar,$route_string,array('wiki_nid' => $wiki_nid,'group' =>$gid,'node' => $item_nid));//revisar
    $my_render=$link->toRenderable();
    $html[]=render($my_render);

    return implode('&nbsp;',$html);
  }

  function unlink_wiki(){

    $parameters = \Drupal::routeMatch()->getParameters();
    $item_nid=$parameters->get('node');
    $wiki_nid=$parameters->get('wiki_nid');

    $this->delete_enlazar_wiki($wiki_nid,$item_nid);
     
    $url = Url::fromRoute('entity.node.canonical', ['node' => $wiki_nid])->toString();
    return new \Symfony\Component\HttpFoundation\RedirectResponse($url);
   

    $build[] = array(
      '#markup' => 'prueba'
       );
    return $build;  
  }
  
   function wiki_solr_funciones_get_node_id_array_by_arg_string($node_id_array){
    if($node_id_array=='is_all_selected'){
    //revisar cuando sea necesario
        $result=$this->wiki_solr_funciones_get_result_node_id_array();
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
  
  function wiki_save($entity,$grupo_nid,$post,$action){
    //print 'debate_save'.$post['origin_nid']; exit();
    $wiki_nid=$entity->id();
    //print $debate_nid;exit();
    if($action=='insert'){
      $this->wiki_confirm_enlazar_wiki($post['origin_nid'],$wiki_nid); 
   /* $url = Url::fromRoute('entity.node.canonical', ['node' => $debate_nid])->toString();
    $url1 = $url.'#comment-form';
    return new \Symfony\Component\HttpFoundation\RedirectResponse($url1);*/  
    }
  }

  public function wiki_title_links_wiki_html(){
    $links_wiki_html= $this->wiki_links_wiki_html();
    //$links_wiki_html='<h3>'.t('Links').'</h3>'.render($links_wiki_html);
    $links_wiki_html=render($links_wiki_html);
    return $links_wiki_html;

  }

}//class WikiController