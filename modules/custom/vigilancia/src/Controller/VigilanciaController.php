<?php

namespace Drupal\vigilancia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Link;
use Drupal\grupo\Controller\GrupoController;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Entity\Feed;
use Drupal\Core\Render\Markup;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;
use Drupal\vigilancia\Controller\VigilanciaExtraController;

class VigilanciaController extends ControllerBase {

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'examples/page-example'.
   */

  public function vigilancia() {
    $build=array();
    $tempstore = \Drupal::service('user.private_tempstore')->get('grupo');
    $gid = $tempstore->get('grupo_select_gid');
    if(!empty($gid)){
      return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput('/group/'.$gid.'/vigilancia')->toString());   
    }
    $build = array(
      '#markup'=>'Vigilancia',
    );
    return $build;
  }
  public function vigilancia_grupo(){
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $limit=10;








    /*
    $item_array=$this->vigilancia_get_item_array($gid,$limit);    
    $rows=array();
    if(!empty($item_array)){
      foreach ($item_array as $item) {
        $node=Node::load($item->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;
        //$rows[]=$view_build;
      }  
    }
    //$build['vigilancia'] = array(
      //'#rows' => $rows,
      //'#header' => array(t('NID'), t('Title')),
      //'#type' => 'teaser',
      //'#empty' => t('No content available.'),
    //);
    $build['#markup']=implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 1,
    );
    */
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-item';    
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
    $build['#markup']=$this->vigilancia_noticias_menu().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
    


    return $build; 
  }
  private function vigilancia_get_item_array($gid,$limit_in=0){
    $result=array();
    $limit='';
    if(!empty($limit_in)){
      $limit=' LIMIT 0,'.$limit_in;
    }
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-item';
    $res=$db->query('SELECT * FROM {group_content_field_data} WHERE group_content_field_data.type=:type AND group_content_field_data.gid=:gid ORDER BY group_content_field_data.created DESC'.$limit,array(':type'=>$type,':gid'=>$gid));
    //$res=$query->execute();
    while($row=$res->fetchObject()){
      $result[]=$row;
    }
    return $result;
  }
  public function vigilancia_import_rss(){
    $build['#markup']='Import RSS';
    return $build;
  }
  public function vigilancia_get_block_vigilancia_left_content(){
    $html=array();
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    if(!is_numeric($gid)){
      $tempstore = \Drupal::service('user.private_tempstore')->get('grupo');
      $gid=$tempstore->get('grupo_select_gid');
    }  
    if(is_numeric($gid)){
      /*$route_string='vigilancia_import_rss';    
      $link=Link::createFromRoute(t('Import RSS'),$route_string,array('group' =>$gid));*/
      $route_string='entity.feeds_feed.add_page';    
      $link=Link::createFromRoute(t('Import RSS'),$route_string);
      $my_render=$link->toRenderable();
      $html[]=render($my_render);
    }  
    $result=implode('<br>',$html);
    return $result;
  }
  public function vigilancia_get_current_username(){
    $result=array();
    $user = \Drupal::currentUser();
    $uid=$user->id();
    $result['#markup']='';
    if(empty($uid)){
      return '';
    }  
    $result['#markup']='<i>'.t('User').':</i> <b>'.$user->getUsername().'</b>';       
    return $result;
  }
  public function vigilancia_get_grupo_default_value(){
    $grupo=new GrupoController();
    $my_grupo=$grupo->grupo_get_current_grupo();
    //intelsat-2017-debate
    if(!empty($my_grupo)){
      $gid=$my_grupo->id();
      if(!empty($gid)){
        //$my_group->label();
        return $my_grupo;
      }
    }  
    return '';
  }
  public function print_form_fields($form){
    if(!empty($form)){
      foreach($form as $field=>$row){
        print 'field='.$field.'<br>';
      }
    }
  }
  public function vigilancia_get_block_vigilancia_canales_left_content(){
    $html=array();
    $grupo=new GrupoController();
    $my_grupo=$grupo->grupo_get_current_grupo();
    if(!empty($my_grupo)){
      $gid=$my_grupo->id();
      if(is_numeric($gid)){
        $route_string='vigilancia_grupo_canales';    
        $link=Link::createFromRoute(t('Channels'),$route_string,array('group' =>$gid));
        $my_render=$link->toRenderable();
        //intelsat
        $html[]='<ul class="clearfix menu">';          
        $html[]='<li class="menu-item">'.render($my_render).'</li>';
        //intelsat
        $grupo_canales_lista=$this->vigilancia_grupo_canales_lista();
        /*echo print_r($grupo_canales_lista,1);
        exit();*/
        if(!empty($grupo_canales_lista)){
          foreach($grupo_canales_lista as $i=>$row){
            //intelsat
            //$route_string='entity.feeds_feed.canonical';
            //$link=Link::createFromRoute($row->title, $route_string,array('feeds_feed'=>$row->fid));
            $route_string='vigilancia_grupo_canales_noticias';
            $link=Link::createFromRoute($row->title, $route_string,array('group'=>$gid,'canal_nid'=>$row->fid));
            $my_render=$link->toRenderable();
            //intelsat
            $html[]='<li class="menu-item">'.render($my_render).'</li>';
          }
        }
        $html[]='</ul>';
      }
    }  
    $result=implode('',$html);
    return $result;
  }
  public function vigilancia_grupo_canales(){
    $grupo_controller=new GrupoController();
    //$parameters = \Drupal::routeMatch()->getParameters();
    //$gid=$parameters->get('group');
    $group=$grupo_controller->grupo_get_current_grupo();
    $gid=$group->id();
    $limit=10;
    if (empty($_REQUEST['page'])) {
      $start = 0;
    }
    else {
      $start = $_REQUEST['page'] * $limit;
    }

    $db = \Drupal::database();
    $where=array();
    $where[]='1';
    $where[]='feeds_feed__field_my_group.field_my_group_target_id='.$gid;
    /*$pager_data = $db->select('feeds_feed', 'feeds_feed');
    $pager_data->lefJoin('feeds_feed__field_my_group','feeds_feed__field_my_group','feeds_feed.fid=feeds_feed__field_my_group.entity_id');*/
    $from=' FROM {feeds_feed} feeds_feed
    LEFT JOIN {feeds_feed__field_my_group} ON feeds_feed.fid=feeds_feed__field_my_group.entity_id
    WHERE '.implode(' AND ',$where).'
    GROUP BY feeds_feed.fid 
    ORDER BY feeds_feed.created DESC';
    $sql='SELECT feeds_feed.*,feeds_feed__field_my_group.field_my_group_target_id '.$from;
    $count_query='SELECT count(feeds_feed.fid) '.$from;
    //print $count_query;exit();
    $count_result = $db->query($count_query);
    $count_result->allowRowCount = TRUE;
    $total_count = $count_result->rowCount();
    $page = pager_default_initialize($total_count, $limit);
    $query_items = db_query_range($sql, $start, $limit);
    $query_items->allowRowCount = TRUE;

    $rows=array();
    if ($query_items->rowCount() == 0) {
      print t('No Results');
      exit();
    }
    else {
      foreach($query_items as $r){
        $row=array();
        $route_string='entity.feeds_feed.canonical';    
        $link=Link::createFromRoute($r->title,$route_string,array('feeds_feed'=>$r->fid));
        $my_render=$link->toRenderable();
        $row[0]=render($my_render);
        $my_user = \Drupal\user\Entity\User::load($r->uid);
        $row[1]=$my_user->getUsername();
        $gid=$r->field_my_group_target_id;
        $group_row=Group::load($gid);
        $row[2]=$group_row->label();
        $rows[]=$row;
      }
    }
    $build['vigilancia_grupo_canales'] = array(
      '#rows' => $rows,
      '#header' => array(t('Title'),t('User'),t('Group')),
      '#type' => 'table',
      '#empty' => t('No content available.'),
    );
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
    return $build;
  }

  //mireia2017
  public function vigilancia_grupo_canales_lista(){
    $grupo_controller=new GrupoController();
    //$parameters = \Drupal::routeMatch()->getParameters();
    //$gid=$parameters->get('group');
    $group=$grupo_controller->grupo_get_current_grupo();
    $gid=$group->id();
    /*$limit=10;
    if (empty($_REQUEST['page'])) {
      $start = 0;
    }
    else {
      $start = $_REQUEST['page'] * $limit;
    }*/

    //$db = \Drupal::database();
    $where=array();
    $where[]='1';
    $where[]='feeds_feed__field_my_group.field_my_group_target_id='.$gid;
    /*$pager_data = $db->select('feeds_feed', 'feeds_feed');
    $pager_data->lefJoin('feeds_feed__field_my_group','feeds_feed__field_my_group','feeds_feed.fid=feeds_feed__field_my_group.entity_id');*/
    $from=' FROM {feeds_feed} feeds_feed
    LEFT JOIN {feeds_feed__field_my_group} ON feeds_feed.fid=feeds_feed__field_my_group.entity_id
    WHERE '.implode(' AND ',$where).'
    GROUP BY feeds_feed.fid 
    ORDER BY feeds_feed.created DESC';
    $sql='SELECT feeds_feed.*,feeds_feed__field_my_group.field_my_group_target_id '.$from;
    //$count_query='SELECT count(feeds_feed.fid) '.$from;
    //print $count_query;exit();
    /*$count_result = $db->query($count_query);
    $count_result->allowRowCount = TRUE;
    $total_count = $count_result->rowCount();
    $page = pager_default_initialize($total_count, $limit);
    $query_items = db_query_range($sql, $start, $limit);
    $query_items->allowRowCount = TRUE;*/

    /*$rows=array();
    if ($query_items->rowCount() == 0) {
      print t('No Results');
      exit();
    }
    else {
      foreach($query_items as $r){
        $row=array();
        $route_string='entity.feeds_feed.canonical';    
        $link=Link::createFromRoute($r->title,$route_string,array('feeds_feed'=>$r->fid));
        $my_render=$link->toRenderable();
        $row[0]=render($my_render);
        $my_user = \Drupal\user\Entity\User::load($r->uid);
        $row[1]=$my_user->getUsername();
        $gid=$r->field_my_group_target_id;
        $group_row=Group::load($gid);
        $row[2]=$group_row->label();
        $rows[]=$row;
      }
    }
    $build['vigilancia_grupo_canales'] = array(
      '#rows' => $rows,
      '#header' => array(t('Title'),t('User'),t('Group')),
      '#type' => 'table',
      '#empty' => t('No content available.'),
    );
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
    return $build;*/
    $result=array();
    $res=db_query($sql);
    while($row=$res->fetchObject()){
      $result[]=$row;
    }
    return $result;
  }
  public function vigilancia_grupo_entity_access(\Drupal\Core\Entity\EntityInterface $entity, $operation, \Drupal\Core\Session\AccountInterface $account){
    $entity_type=$entity->getEntityType();
    $type=$entity_type->getProvider();
    $type_array=array('feeds');
    if(in_array($type,$type_array)){
      $my_entity=$entity->toArray();
      if(isset($my_entity['field_my_group']) && isset($my_entity['field_my_group'][0])){
        if(isset($my_entity['field_my_group'][0]['target_id']) && !empty($my_entity['field_my_group'][0]['target_id'])){
          $gid=$my_entity['field_my_group'][0]['target_id'];
          $grupo=new GrupoController();
          $gid_array=$grupo->grupo_get_user_gid_array();
          if(in_array($gid,$gid_array)){
            return TRUE;
          }
        }
      }
      return FALSE;
    }
  return TRUE;         
  }
  public function vigilancia_get_block_vigilancia_categorias_left_content(){
    $html=array();
    $grupo_controller=new GrupoController();
    $my_grupo=$grupo_controller->grupo_get_current_grupo();
    if(!empty($my_grupo)){
      $categorias_vid=$grupo_controller->grupo_get_categorias_vid($my_grupo);    
      $gid=$my_grupo->id();
      if(is_numeric($gid)){
        $route_string='vigilancia_grupo_categorias_create_edit';    
        $link=Link::createFromRoute(t('Create/Edit Categories'),$route_string,array('group' =>$gid));
        $my_render=$link->toRenderable();
        $html[]=render($my_render);
        $html[]='<br>';
        $categorias_array = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($categorias_vid);
        if(!empty($categorias_array)){
          $html[]='<ul>';
          foreach($categorias_array as $i=>$term){
             $route_string='vigilancia_grupo_categoria_noticias';    
            $link=Link::createFromRoute($term->name,$route_string,array('group' =>$gid, 'tid'=> $term->tid));
             $my_render=$link->toRenderable();
            $html[]='<li class="nivel'.$term->depth.'">'.render($my_render).'</li>';
            //$html[]='<li class="nivel'.$term->depth.'">'.$term->name.'</li>';
          }
          $html[]='</ul>';
        }
      }
    }  
    $result=implode('',$html);
    return $result;
  }
  public function vigilancia_grupo_categorias_edit(){
    $build=array();
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $grupo_controller=new GrupoController();
    $my_grupo=Group::load($gid);    
    $categorias_vid=$grupo_controller->grupo_get_categorias_vid($my_grupo);
    if(empty($categorias_vid)){
      $vid='categoria'.$my_grupo->id();
      $my_vocabulary=Vocabulary::load($vid);
      if(!empty($my_vocabulary)){
        $categorias_vid=$my_vocabulary->id();
      }else{
        if(empty($categorias_vid)){
          $vocabulary=Vocabulary::create(array(
            'vid'=>$vid,
            'name' => $my_grupo->label().' categories',
            'machine_name' => $vid,
            //'description' => '',
            //'weight' => 0,
          ));
          $vocabulary->save();
          $categorias_vid=$vocabulary->id();
        }
      }  
      $my_grupo->set('field_group_categories',$categorias_vid);
      $my_grupo->save();
    }
    //$buil['#markup']='';
    //return $build;
    //$url='/admin/structure/taxonomy/manage/'.$categorias_vid.'/overview';
    //return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput($url)->toString());
    $url='/group/'.$gid.'/vigilancia/categorias/manage/'.$categorias_vid.'/overview';
    return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput($url)->toString());                  
  }
  public function vigilancia_grupo_add_categorias_form_field(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id){
    $grupo_controller=new GrupoController();    
    $my_grupo=$grupo_controller->grupo_get_current_grupo();
    $entity=$form_state->getFormObject()->getEntity();
    $tid_array=$this->vigilancia_get_field_feed_categories_tid_array($entity);
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
              $pro=$this->vigilancia_get_profundidad($term->tid);
                        $key='my_cat_'.$term->tid;
                        $form[$fieldset][$key]= array(
                          //'#required' => TRUE,
                          '#type' => 'checkbox',
                          '#prefix' => '<div class=taxo'. $pro .'>',
                          '#suffix' => '</div>',
                          '#title' => $term->name
                        );
                        if(in_array($term->tid,$tid_array)){
                          $form[$fieldset][$key]['#attributes']=array('checked'=>'checked');
                        }
          }          
        }
      }
    }  
  }
 //mireia2017
   public function vigilancia_grupo_add_tipos_de_fuentes_form_field(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id){
    $grupo_controller=new GrupoController();    
    $my_grupo=$grupo_controller->grupo_get_current_grupo();
    $entity=$form_state->getFormObject()->getEntity();
    $tid_array=$this->vigilancia_get_field_canal_source_type_tid_array($entity);
    if(!empty($my_grupo)){
      $fuentes_vid=$grupo_controller->grupo_get_fuentes_vid($my_grupo);    
      $gid=$my_grupo->id();
      if(is_numeric($gid)){
        $fuentes_array = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($fuentes_vid);
        if(!empty($fuentes_array)){
          $fieldset='fuentes_fs';
          $form[$fieldset]=array();
          $form[$fieldset]['#type']='fieldset';
          $form[$fieldset]['#title']=t('Source Types');
          foreach($fuentes_array as $i=>$term){
              $pro=$this->vigilancia_get_profundidad($term->tid);
                        $key='my_tipo_de_fuente_'.$term->tid;
                        $form[$fieldset][$key]= array(
                          //'#required' => TRUE,
                          '#type' => 'checkbox',
                          '#prefix' => '<div class=taxo'. $pro .'>',
                          '#suffix' => '</div>',
                          '#title' => $term->name
                        );
                        if(in_array($term->tid,$tid_array)){
                          $form[$fieldset][$key]['#attributes']=array('checked'=>'checked');
                        }
          }          
        }
      }
    }  
  }

  public function vigilancia_get_profundidad($tid,$prof=0){
    $prof_valor=0;
    $term_hierarchy_row=$this->vigilancia_get_taxonomy_term_hierarchy_row($tid);
    $parent=0;
    if(isset($term_hierarchy_row->parent)){
      $parent=$term_hierarchy_row->parent;      
    }
    if ($parent!=0) {
      $num=$prof;
      $num=$num+1;    
      return $this->vigilancia_get_profundidad($parent,$num);
    }
    else{
      $val=$prof;
      //$prof=0;
      return $val;
    } 
  }
  private function vigilancia_get_taxonomy_term_hierarchy_row($tid){
    $db = \Drupal::database();
    $res=db_query('SELECT * FROM {taxonomy_term_hierarchy} WHERE tid=:tid',array(':tid'=>$tid));
    while($row=$res->fetchObject()){
      return $row;
    }  
    $my_result=new stdClass();
    return $my_result;
  }
  public function vigilancia_on_entity_insert($entity){
    if(!empty($entity)){
      $type_id=$entity->getEntityTypeId();
      if(in_array($type_id,array('feeds_feed'))){
        $this->vigilancia_on_entity_save($entity);
      }  
    }  
  }
  /*private function vigilancia_on_entity_save($feed){
    $post=\Drupal::request()->request->all();
    $categoria_tid_array=array();
    if(!empty($post)){
      foreach($post as $field=>$value){
        $konp='my_cat_';
        $pos=strpos($field,$konp);
        if($pos===FALSE){
          continue;
        }else if($pos>=0){
          $tid=str_replace($konp,'',$field);
          $categoria_tid_array[]=$tid;
        }  
      }      
    }
    $feed->set('field_feed_categories',$categoria_tid_array);
    $feed->save();
  }
  public function vigilancia_on_entity_update($entity){
    $post=\Drupal::request()->request->all();
    if(!empty($post) && isset($post['form_id'])){
      if(in_array($post['form_id'],array('feeds_feed_rss_standard_form'))){
          if(!empty($entity)){
            $type_id=$entity->getEntityTypeId();
            if(in_array($type_id,array('feeds_feed'))){
              $this->vigilancia_on_entity_save($entity);
            }  
          }
      }    
    }    
  }*/
  public function vigilancia_on_entity_presave($entity){
    if(!empty($entity)){
      $type_id=$entity->getEntityTypeId();
      if(in_array($type_id,array('feeds_feed'))){
        $post=\Drupal::request()->request->all();
        //intelsat-2017
        if($this->vigilancia_is_post_feeds_feed_rss_standard_form_save($post)){
          $categoria_tid_array=array();
          if(!empty($post)){
            foreach($post as $field=>$value){
              $konp='my_cat_';
              $pos=strpos($field,$konp);
              if($pos===FALSE){
                continue;
              }else if($pos>=0){
                $tid=str_replace($konp,'',$field);
                $categoria_tid_array[]=$tid;
              }  
            }      
          }
          $entity->set('field_feed_categories',$categoria_tid_array);
        }  
      }  
    }  
  }
  //mireia2017
    public function vigilancia_on_fuentes_entity_presave($entity){
    if(!empty($entity)){
      $type_id=$entity->getEntityTypeId();
      if(in_array($type_id,array('feeds_feed'))){
        $post=\Drupal::request()->request->all();
        //intelsat-2017
        if($this->vigilancia_is_post_feeds_feed_rss_standard_form_save($post)){
          $tipodefuente_tid_array=array();
          if(!empty($post)){
            foreach($post as $field=>$value){
              $konp='my_tipo_de_fuente_';
              $pos=strpos($field,$konp);
              if($pos===FALSE){
                continue;
              }else if($pos>=0){
                $tid=str_replace($konp,'',$field);
                $tipodefuente_tid_array[]=$tid;
              }  
            }      
          }
          $entity->set('field_canal_source_type',$tipodefuente_tid_array);
        }  
      }  
    }  
  }
  private function vigilancia_get_field_feed_categories_tid_array($entity){
    $result=array();
    $field_feed_categories=$entity->get('field_feed_categories')->getValue();
    if(!empty($field_feed_categories)){
      foreach($field_feed_categories as $i=>$row){
        if(isset($row['target_id']) && !empty($row['target_id'])){
          $result[]=$row['target_id'];
        }
      }
    }
    return $result;
  }
  //mireia2017
    private function vigilancia_get_field_canal_source_type_tid_array($entity){
    $result=array();
    $field_canal_source_type=$entity->get('field_canal_source_type')->getValue();
    if(!empty($field_canal_source_type)){
      foreach($field_canal_source_type as $i=>$row){
        if(isset($row['target_id']) && !empty($row['target_id'])){
          $result[]=$row['target_id'];
        }
      }
    }
    return $result;
  }
  public function vigilancia_is_item_duplicado(FeedInterface $feed, ItemInterface $item, StateInterface $state){
    $my_group_gid=$feed->get('field_my_group')->getValue();
    $gid='';
    if(!empty($my_group_gid) && isset($my_group_gid[0]) && isset($my_group_gid[0]['target_id'])){
      if(!empty(isset($my_group_gid[0]['target_id']))){
        $gid=$my_group_gid[0]['target_id'];
        $info_cut=$this->vigilancia_get_item_url_cut($item);
        $is_solo_url=1;
        $item_uniq_array=$this->vigilancia_get_guid_url_item_array($item,1,$feed,$info_cut,$is_solo_url,$gid);
        //mireia
        //echo 'item_uniq_array='.print_r($item_uniq_array,1);exit();
        
        if(count($item_uniq_array)>0){
            return 1;
        }
      }
    }
    return 0;
  }
  private function vigilancia_get_item_url_cut(ItemInterface $item){
    $result=array();
    $link='';
    $guid='';
    $url='';

    $result['link']='';
    $result['guid']='';
    $result['url']='';

    $link=$item->get('link');
    $guid=$item->get('guid');
    $url=$item->get('url');    
    
    if(!empty($link)){
        $result['link']=$link;
    }
    if(!empty($guid)){
        $result['guid']=$guid;
    }
    if(!empty($url)){
        $result['url']=$url;
    }
    return $result;
  }

  /*group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-item';    
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
    }*/
    private function vigilancia_get_guid_url_item_array(ItemInterface $item,$with_grupo=1,FeedInterface $feed=NULL,$info_cut='',$is_solo_url=1,$gid=0){
      $result=array();

      $db = \Drupal::database();
      $where=array();
      $where[]='1';
      $group=Group::load($gid);
      $group_type=$group->getGroupType();
      $group_type_id=$group_type->id();
      $type=$group_type_id.'-group_node-item';   
      $where[]='group_content_field_data.type="'.$type.'"';
      $where[]='group_content_field_data.gid='.$gid;
      //$where[]='node__field_rss_url.field_rss_url_value="'.$info_cut['url'].'"';
      $vigilancia_extra_controller=new VigilanciaExtraController();      
      $or_url=$vigilancia_extra_controller->vigilancia_extra_get_duplicado_or_url($info_cut,$is_solo_url);
      if(!empty($or_url)){
        $where[]=$or_url;
      }


      $sql='SELECT node__field_rss_url.* 
      FROM {group_content_field_data} group_content_field_data
      LEFT JOIN {node__field_rss_url} node__field_rss_url ON group_content_field_data.entity_id=node__field_rss_url.entity_id
      LEFT JOIN {node__field_rss_guid} node__field_rss_guid ON group_content_field_data.entity_id=node__field_rss_guid.entity_id
      WHERE '.implode(' AND ',$where).'
      ORDER BY group_content_field_data.created DESC';
      //print $sql;exit();
      $res=$db->query($sql);
      while($row=$res->fetchObject()){
        $result[]=$row;
      }
      /*print "zenbatnotizi=".count($result);
      exit();*/
      return $result;
    }
    public function vigilancia_grupo_categoria_noticias(){
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $tid=$parameters->get('tid');

    
    $term=Term::load($tid);
    $title=$term->label();
    $this->vigilancia_set_title('News in category: '.$title);
    
    $limit=10;
    /*4 hauek kenduta zeudeen print $gid;
    print $tid;
    exit();*/
     /*`node__field_item_canal_category_tid*/
     //nik kenduitut 4 lerro hauek
     /*$result=array();
      $db = \Drupal::database();
      $where=array();
      $where[]='1';


    
      */
      //mireia2017
      $group=Group::load($gid);
      $group_type=$group->getGroupType();
      $group_type_id=$group_type->id();
      $db = \Drupal::database();
      $type=$group_type_id.'-group_node-item'; 
      $pager_data=$db->select('node__field_item_canal_category_tid', 'node__field_item_canal_category_tid')
      ->extend(PagerSelectExtender::class);
       $result=$pager_data->leftJoin('node__field_my_group', 'node__field_my_group', 'node__field_item_canal_category_tid.entity_id=node__field_my_group.entity_id');
       $result=$pager_data->fields('node__field_item_canal_category_tid', array('entity_id'))
      ->condition('node__field_item_canal_category_tid.field_item_canal_category_tid_target_id',$tid)
      ->condition('node__field_my_group.field_my_group_target_id',$gid)
      ->orderBy('node__field_item_canal_category_tid.entity_id', 'DESC')
      ->limit($limit)
      ->execute()
      ->fetchAll();
    $rows=array(); 
     //while($row=$pager_data->fetchObject()){
    foreach ($result as $row){
        $node=Node::load($row->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;        
    }
    $build['#markup']=$this->vigilancia_noticias_menu2().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
     return $build;

      //3 hauek nik kenduitut
      /*
      $where[]='group_content_field_data.type="'.$type.'"';
      $where[]='group_content_field_data.gid='.$gid;
      $where[]='node__field_rss_url.field_rss_url_value="'.$info_cut['url'].'"';
      */

      //nik kenduitut 4 hauek
      /*
      $sql='SELECT node__field_item_canal_category_tid.* 
      FROM {node__field_item_canal_category_tid} node__field_item_canal_category_tid
      WHERE '.implode(' AND ',$where).'
      ORDER BY node__field_item_canal_category_tid.entity_id DESC';
      */

      //kenduta zeoon *print $sql;exit();*/
      //nik kendueet  $res=$db->query($sql);
      
      /*nik kenduitutt
      while($row=$res->fetchObject()){
        $result[]=$row;
        $node=node_load($row->entity_id);
        $node_view=node_view($node);
        echo print_r($node_view,1);
        exit();
      }
     honearteeee */

     // kenduta zeoonn return $result;
     /*nik kendueett

      $build = array(
      '#markup'=>implode('',$html),
    );
    honearteeee*/
   



    }
    

public function vigilancia_get_block_vigilancia_fuentes_left_content(){
    $html=array();
    $grupo_controller=new GrupoController();
    $my_grupo=$grupo_controller->grupo_get_current_grupo();
    if(!empty($my_grupo)){
      $fuentes_vid=$grupo_controller->grupo_get_fuentes_vid($my_grupo);    
      $gid=$my_grupo->id();
      if(is_numeric($gid)){
        $route_string='vigilancia_grupo_fuentes_create_edit';    
        $link=Link::createFromRoute(t('Create/Edit Source Types'),$route_string,array('group' =>$gid));
        $my_render=$link->toRenderable();
        $html[]=render($my_render);
        $html[]='<br>';
        $fuentes_array = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($fuentes_vid);
        if(!empty($fuentes_array)){
          $html[]='<ul>';
          foreach($fuentes_array as $i=>$term){
             $route_string='vigilancia_grupo_fuentes_noticias';    
            $link=Link::createFromRoute($term->name,$route_string,array('group' =>$gid, 'tid'=> $term->tid));
             $my_render=$link->toRenderable();
            $html[]='<li class="nivel'.$term->depth.'">'.render($my_render).'</li>';
            //$html[]='<li class="nivel'.$term->depth.'">'.$term->name.'</li>';
          }
          $html[]='</ul>';
        }
      }
    }  
    $result=implode('',$html);
    return $result;
  }  
  public function vigilancia_grupo_fuentes_edit(){
    $build=array();
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $grupo_controller=new GrupoController();
    $my_grupo=Group::load($gid);    
    $fuentes_vid=$grupo_controller->grupo_get_fuentes_vid($my_grupo);
    if(empty($fuentes_vid)){
      $vid='tipo_fuente'.$my_grupo->id();
      $my_vocabulary=Vocabulary::load($vid);
      if(!empty($my_vocabulary)){
        $fuentes_vid=$my_vocabulary->id();
      }else{
        if(empty($fuentes_vid)){
          $vocabulary=Vocabulary::create(array(
            'vid'=>$vid,
            'name' => $my_grupo->label().' source types',
            'machine_name' => $vid,
            //'description' => '',
            //'weight' => 0,
          ));
          $vocabulary->save();
          $fuentes_vid=$vocabulary->id();
        }
      }  
      $my_grupo->set('field_group_source_types',$fuentes_vid);
      $my_grupo->save();
    }
    //$buil['#markup']='';
    //return $build;
    //$url='/admin/structure/taxonomy/manage/'.$categorias_vid.'/overview';
    //return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput($url)->toString());
    $url='/group/'.$gid.'/vigilancia/fuentes/manage/'.$fuentes_vid.'/overview';
    return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput($url)->toString());                  
  }
   public function vigilancia_grupo_fuentes_noticias(){
     $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $tid=$parameters->get('tid');

    $term=Term::load($tid);
    $title=$term->label();
    $this->vigilancia_set_title('News in type of source: '.$title);
    $limit=10;

    //mireia2017
      $group=Group::load($gid);
      $group_type=$group->getGroupType();
      $group_type_id=$group_type->id();
      $db = \Drupal::database();
      $type=$group_type_id.'-group_node-item'; 
      $pager_data=$db->select('node__field_item_source_tid', 'node__field_item_source_tid')
      ->extend(PagerSelectExtender::class);
       $result=$pager_data->leftJoin('node__field_my_group', 'node__field_my_group', 'node__field_item_source_tid.entity_id=node__field_my_group.entity_id');
       $result=$pager_data->fields('node__field_item_source_tid', array('entity_id'))
      ->condition('node__field_item_source_tid.field_item_source_tid_target_id',$tid)
      ->condition('node__field_my_group.field_my_group_target_id',$gid)
      ->orderBy('node__field_item_source_tid.entity_id', 'DESC')
      ->limit($limit)
      ->execute()
      ->fetchAll();
    $rows=array(); 
    foreach ($result as $row){
        $node=Node::load($row->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;        
    }
    $build['#markup']=$this->vigilancia_noticias_fuentes_menu().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
     return $build;


    /*print $gid;
    print $tid;
    exit();
    */
    }

public function vigilancia_grupo_validadas(){
  return $this->my_vigilancia_grupo_validadas();
}
public function my_vigilancia_grupo_validadas($flag_id='leido_interesante'){
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $limit=10;
    
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-item';    
    $pager_data=$db->select('group_content_field_data', 'group_content_field_data')
      ->extend(PagerSelectExtender::class);
      $pager_data->leftJoin('flagging', 'flagging', 'group_content_field_data.entity_id = flagging.entity_id');
      $result=$pager_data->fields('group_content_field_data', array('entity_id'))
      ->condition('group_content_field_data.type',$type)
      ->condition('group_content_field_data.gid',$gid)
      ->condition('flagging.flag_id',$flag_id)
      ->orderBy('group_content_field_data.created', 'DESC')
      ->limit($limit)
      ->execute()
      ->fetchAll();
    $rows=array();  
    //while($row=$pager_data->fetchObject()){
    foreach ($result as $row){
    //while($row=$result->fetchObject()){
        $node=Node::load($row->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;        
    }
    $build['#markup']=$this->vigilancia_noticias_menu().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
    


    return $build; 
  }
  public function vigilancia_grupo_rechazadas(){
  return $this->my_vigilancia_grupo_validadas('leido_no_interesante');
}
//mireia2017
public function vigilancia_grupo_lo_mas_comentado(){
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $limit=10;
    
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-item';    
    $pager_data=$db->select('group_content_field_data', 'group_content_field_data')
      ->extend(PagerSelectExtender::class);
      $pager_data->leftJoin('comment_entity_statistics', 'comment_entity_statistics', 'group_content_field_data.entity_id = comment_entity_statistics.entity_id');
      $result=$pager_data->fields('group_content_field_data', array('entity_id'))
      ->condition('group_content_field_data.type',$type)
      ->condition('group_content_field_data.gid',$gid)
      ->condition('comment_entity_statistics.comment_count', 0, '>')
      //->condition('comment_entity_statistics.entity_id',$entity_id)
      ->orderBy('comment_entity_statistics.comment_count', 'DESC')
      ->limit($limit)
      ->execute()
      ->fetchAll();
    $rows=array();  
    //while($row=$pager_data->fetchObject()){
    foreach ($result as $row){
    //while($row=$result->fetchObject()){
        $node=Node::load($row->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;        
    }
    $build['#markup']=$this->vigilancia_noticias_menu().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
    

    return $build; 
  }
  //mireia2017
  public function vigilancia_grupo_categorias_node_item_edit_form_field(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id){
    $grupo_controller=new GrupoController();    
    $my_grupo=$grupo_controller->grupo_get_current_grupo();
    $entity=$form_state->getFormObject()->getEntity();
    $tid_array=$this->vigilancia_get_field_item_canal_category_tid_array($entity);
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
              $pro=$this->vigilancia_get_profundidad($term->tid);
                        $key='my_tipo_de_item_categoria_'.$term->tid;
                        $form[$fieldset][$key]= array(
                          //'#required' => TRUE,
                          '#type' => 'checkbox',
                          '#prefix' => '<div class=taxo'. $pro .'>',
                          '#suffix' => '</div>',
                          '#title' => $term->name
                        );
                        if(in_array($term->tid,$tid_array)){
                          $form[$fieldset][$key]['#attributes']=array('checked'=>'checked');
                        }
          }          
        }
      }
    }  
  }

  //mireia2017
  public function vigilancia_grupo_tipos_de_fuentes_node_item_edit_form_field(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id){
    $grupo_controller=new GrupoController();    
    $my_grupo=$grupo_controller->grupo_get_current_grupo();
    $entity=$form_state->getFormObject()->getEntity();
    $tid_array=$this->vigilancia_get_field_item_source_tid_array($entity);
    if(!empty($my_grupo)){
      $fuentes_vid=$grupo_controller->grupo_get_fuentes_vid($my_grupo);    
      $gid=$my_grupo->id();
      if(is_numeric($gid)){
        $fuentes_array = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($fuentes_vid);
        if(!empty($fuentes_array)){
          $fieldset='fuentes_fs';
          $form[$fieldset]=array();
          $form[$fieldset]['#type']='fieldset';
          $form[$fieldset]['#title']=t('Source Types');
          foreach($fuentes_array as $i=>$term){
              $pro=$this->vigilancia_get_profundidad($term->tid);
                        $key='my_tipo_de_item_fuente_'.$term->tid;
                        $form[$fieldset][$key]= array(
                          //'#required' => TRUE,
                          '#type' => 'checkbox',
                          '#prefix' => '<div class=taxo'. $pro .'>',
                          '#suffix' => '</div>',
                          '#title' => $term->name
                        );
                        if(in_array($term->tid,$tid_array)){
                          $form[$fieldset][$key]['#attributes']=array('checked'=>'checked');
                        }
          }          
        }
      }
    }  
  }
  //mireia2017
  public function vigilancia_get_field_item_canal_category_tid_array($entity){
    $result=array();
    $field_item_canal_category_tid=$entity->get('field_item_canal_category_tid')->getValue();
    if(!empty($field_item_canal_category_tid)){
      foreach($field_item_canal_category_tid as $i=>$row){
        if(isset($row['target_id']) && !empty($row['target_id'])){
          $result[]=$row['target_id'];
        }
      }
    }
    return $result;
  }
  //mireia2017
  private function vigilancia_get_field_item_source_tid_array($entity){
    $result=array();
    $field_item_source_tid=$entity->get('field_item_source_tid')->getValue();
    if(!empty($field_item_source_tid)){
      foreach($field_item_source_tid as $i=>$row){
        if(isset($row['target_id']) && !empty($row['target_id'])){
          $result[]=$row['target_id'];
        }
      }
    }
    return $result;
  }

  //mireia2017
  public function vigilancia_on_item_categorias_entity_presave($entity){
    if(!empty($entity)){
      $type_id=$entity->getEntityTypeId();
      //intelsat-2017
      //if(in_array($type_id,array('feeds_feed'))){
      if($this->vigilancia_is_entity_item($entity,$type_id)){
        $post=\Drupal::request()->request->all();
          $tipodeitemcategoria_tid_array=array();
          //intelsat-2017
          if($this->vigilancia_is_post_node_item_edit_form_save($post)){
            if(!empty($post)){            
              foreach($post as $field=>$value){
                $konp='my_tipo_de_item_categoria_';
                $pos=strpos($field,$konp);
                if($pos===FALSE){
                  continue;
                }else if($pos>=0){
                  $tid=str_replace($konp,'',$field);
                  $tipodeitemcategoria_tid_array[]=$tid;
                }  
              }
            }
            //intelsat-2017
              //$entity->set('field_item_source_tid',$tipodeitemcategoria_tid_array);
              $entity->set('field_item_canal_category_tid',$tipodeitemcategoria_tid_array);        
          }            
      }  
    }  
  }

  //mireia2017
  public function vigilancia_on_item_tipodefuente_entity_presave($entity){
    if(!empty($entity)){
      $type_id=$entity->getEntityTypeId();
      //intelsat-2017
      //if(in_array($type_id,array('feeds_feed'))){
      if($this->vigilancia_is_entity_item($entity,$type_id)){  
        $post=\Drupal::request()->request->all();
        $tipodeitemfuente_tid_array=array();
        //intelsat-2017
        if($this->vigilancia_is_post_node_item_edit_form_save($post)){
          if(!empty($post)){          
            foreach($post as $field=>$value){
              $konp='my_tipo_de_item_fuente_';
              $pos=strpos($field,$konp);
              if($pos===FALSE){
                continue;
              }else if($pos>=0){
                $tid=str_replace($konp,'',$field);
                $tipodeitemfuente_tid_array[]=$tid;
              }  
            }            
          }
          //intelsat-2017
            //$entity->set('field_item_canal_category_tid',$tipodeitemfuente_tid_array);
            $entity->set('field_item_source_tid',$tipodeitemfuente_tid_array);        
        }
      }  
    }  
  }
  //intelsat-2017
  private function vigilancia_is_entity_item($entity,$type_id_in=''){
    $type_id=$entity->getEntityTypeId();
    if($type_id=='node'){ 
      $node_type=$entity->getType();
      $node_type_array=array('item');
      if(in_array($node_type,$node_type_array)){
        return 1;
      }    
    }
    return 0;    
  }
  //intelsat-2017
  private function vigilancia_is_post_feeds_feed_rss_standard_form_save($post){    
    return $this->vigilancia_is_post_form_save($post,'feeds_feed_rss_standard_form');  
  }
  //intelsat-2017
  private function vigilancia_is_post_form_save($post,$form_id){
    if(isset($post['form_id']) && $post['form_id']==$form_id){
      return 1;
    }
    return 0;  
  }
  //intelsat-2017
  private function vigilancia_is_post_node_item_edit_form_save($post){
    return $this->vigilancia_is_post_form_save($post,'node_item_edit_form');  
  }

  private function vigilancia_noticias_menu(){


    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');

    $options=array();

    $route_string='vigilancia_grupo_validadas';
    $is_active_validadas=$this->vigilancia_is_active_validadas();
    $options_validadas=$this->vigilancia_get_menu_link_principal_options($options,$is_active_validadas);
    $span_validadas=$this->vigilancia_get_menu_span_active_link($is_active_validadas);
    $li_class_validadas=$this->vigilancia_get_li_class_principal($is_active_validadas);
    $title_validadas=Markup::create(t('Validated').$span_validadas);           
    $link_validadas=Link::createFromRoute($title_validadas,$route_string,array('group' =>$gid),$options_validadas);
    $my_render_validadas=$link_validadas->toRenderable();
    $my_render_validadas=render($my_render_validadas); 

    $route_string='vigilancia_grupo_rechazadas';
    $is_active_rechazadas=$this->vigilancia_is_active_rechazadas();
    $options_rechazadas=$this->vigilancia_get_menu_link_principal_options($options,$is_active_rechazadas);
    $span_rechazadas=$this->vigilancia_get_menu_span_active_link($is_active_rechazadas);
    $li_class_rechazadas=$this->vigilancia_get_li_class_principal($is_active_rechazadas);
    $title_rechazadas=Markup::create(t('Rejected').$span_rechazadas);           
    $link_rechazadas=Link::createFromRoute($title_rechazadas,$route_string,array('group' =>$gid),$options_rechazadas);
    $my_render_rechazadas=$link_rechazadas->toRenderable();
    $my_render_rechazadas=render($my_render_rechazadas); 

    $route_string='vigilancia_grupo_lo_mas_comentado';
    $is_active_lo_mas_comentado=$this->vigilancia_is_active_lo_mas_comentado();
    $options_lo_mas_comentado=$this->vigilancia_get_menu_link_principal_options($options,$is_active_lo_mas_comentado);
    $span_rechazadas=$this->vigilancia_get_menu_span_active_link($is_active_rechazadas);
    $li_class_lo_mas_comentado=$this->vigilancia_get_li_class_principal($is_active_lo_mas_comentado);
    $title_lo_mas_comentado=Markup::create(t('Most commented'));            
    $link_lo_mas_comentado=Link::createFromRoute($title_lo_mas_comentado,$route_string,array('group' =>$gid),$options_lo_mas_comentado);
    $my_render_lo_mas_comentado=$link_lo_mas_comentado->toRenderable();
    $my_render_lo_mas_comentado=render($my_render_lo_mas_comentado); 



    /*$html='<nav role="navigation" aria-labelledby="block-buho-main-menu-menu" id="block-buho-main-menu" class="contextual-region block block-menu navigation menu--main">
                
      <h2 class="visually-hidden" id="block-buho-main-menu-menu">Main navigation</h2>
      <div data-contextual-id="block:block=buho_main_menu:langcode=en|menu:menu=main:langcode=en"></div>

          <div class="content">
            <div class="menu-toggle-target menu-toggle-target-show" id="show-block-buho-main-menu"></div>
        <div class="menu-toggle-target" id="hide-block-buho-main-menu"></div>
        <a class="menu-toggle" href="#show-block-buho-main-menu">Show &mdash; Main navigation</a>
        <a class="menu-toggle menu-toggle--hide" href="#hide-block-buho-main-menu">Hide &mdash; Main navigation</a>


                  <ul class="clearfix menu">
                       

                <li class="menu-item">
                  '.$my_render_validadas.'
                  </li>


                    <li class="menu-item">
                  '.$my_render_rechazadas.'
                  </li>

                    <li class="menu-item">
                  '.$my_render_lo_mas_comentado.'
                  </li>

            </ul>


      </div>
    </nav>';*/

    $html=array();
    $html[]='<nav aria-label="Tabs" role="navigation" class="tabs">';
    $html[]='<h2 class="visually-hidden">Primary tabs</h2>';
    $html[]='<ul class="tabs primary">';
    $html[]='<li'.$li_class_validadas.'>'.$my_render_validadas.'</li>';
    $html[]='<li'.$li_class_rechazadas.'>'.$my_render_rechazadas.'</li>';
    $html[]='<li'.$li_class_lo_mas_comentado.'>'.$my_render_lo_mas_comentado.'</li>';
    $html[]='</ul>';
    $html[]='</nav>';
    $html=implode('',$html);

    return $html;
  }

//mireia2017
public function vigilancia_grupo_categoria_noticias_validadas($flag_id='leido_interesante'){
  
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $tid=$parameters->get('tid');
    
    //$title=t('Validated');
    //$title='Validated';
    $term=Term::load($tid);
    $title=$term->label();
    $this->vigilancia_set_title('News in category: '.$title);

    $limit=10;

   
      $group=Group::load($gid);
      $group_type=$group->getGroupType();
      $group_type_id=$group_type->id();
      $db = \Drupal::database();
      $type=$group_type_id.'-group_node-item'; 
      $pager_data=$db->select('node__field_item_canal_category_tid', 'node__field_item_canal_category_tid')
      ->extend(PagerSelectExtender::class);
       $pager_data->leftJoin('node__field_my_group', 'node__field_my_group', 'node__field_item_canal_category_tid.entity_id=node__field_my_group.entity_id');
       //intelsat
       //OHARRA::::agian $type begiratzea komeni da horretarako hau deskomentatu, baina flagging taulako leftJoin bakarrarekin geratu
       /*$pager_data->leftJoin('group_content_field_data', 'group_content_field_data', 'node__field_item_canal_category_tid.entity_id=group_content_field_data.entity_id');
       $pager_data->leftJoin('flagging', 'flagging', 'group_content_field_data.entity_id = flagging.entity_id');*/
       $pager_data->leftJoin('flagging', 'flagging', 'node__field_item_canal_category_tid.entity_id = flagging.entity_id');
       //
       $result=$pager_data->fields('node__field_item_canal_category_tid', array('entity_id'))
      ->condition('node__field_item_canal_category_tid.field_item_canal_category_tid_target_id',$tid)
      ->condition('node__field_my_group.field_my_group_target_id',$gid)
      //intelsat
      //OHARRA::::agian $type begiratzea komeni da horretarako hau deskomentatu
      //->condition('group_content_field_data.type',$type)
      ->condition('flagging.flag_id',$flag_id)
      //
      ->orderBy('node__field_item_canal_category_tid.entity_id', 'DESC')
      ->limit($limit)
      ->execute()
      ->fetchAll();
    $rows=array(); 
    foreach ($result as $row){
        $node=Node::load($row->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;        
    }
    $build['#markup']=$this->vigilancia_noticias_menu2().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
     return $build;

    
}

  public function vigilancia_grupo_categoria_noticias_rechazadas(){
  return $this->vigilancia_grupo_categoria_noticias_validadas('leido_no_interesante');
}

  private function vigilancia_noticias_menu2(){


    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    //intelsat
    $tid=$parameters->get('tid');


$options=array();

$route_string='vigilancia_grupo_categoria_noticias_validadas';
$is_active_validadas=$this->vigilancia_is_active_categoria_validadas();    
$options_validadas=$this->vigilancia_get_menu_link_principal_options($options,$is_active_validadas);
$span_validadas=$this->vigilancia_get_menu_span_active_link($is_active_validadas);
$li_class_validadas=$this->vigilancia_get_li_class_principal($is_active_validadas);
$title_validadas=Markup::create(t('Validated').$span_validadas);                           
//intelsat           
//$link_categoria_noticias_validadas=Link::createFromRoute(t('Validated'),$route_string,array('group' =>$gid));
//$link_categoria_noticias_validadas=Link::createFromRoute(t('Validated'),$route_string,array('group' =>$gid,'tid'=>$tid));
$link_categoria_noticias_validadas=Link::createFromRoute($title_validadas,$route_string,array('group' =>$gid,'tid'=>$tid),$options_validadas);
$my_render_categoria_noticias_validadas=$link_categoria_noticias_validadas->toRenderable();
$my_render_categoria_noticias_validadas=render($my_render_categoria_noticias_validadas); 

$route_string='vigilancia_grupo_categoria_noticias_rechazadas';
$is_active_rechazadas=$this->vigilancia_is_active_categoria_rechazadas();    
$options_rechazadas=$this->vigilancia_get_menu_link_principal_options($options,$is_active_rechazadas);
$span_rechazadas=$this->vigilancia_get_menu_span_active_link($is_active_rechazadas);
$li_class_rechazadas=$this->vigilancia_get_li_class_principal($is_active_rechazadas);
$title_rechazadas=Markup::create(t('Rejected').$span_rechazadas);                           
//intelsat           
//$link_categoria_noticias_validadas=Link::createFromRoute(t('Validated'),$route_string,array('group' =>$gid));
//$link_categoria_noticias_rechazadas=Link::createFromRoute(t(),$route_string,array('group' =>$gid,'tid'=>$tid));
$link_categoria_noticias_rechazadas=Link::createFromRoute($title_rechazadas,$route_string,array('group' =>$gid,'tid'=>$tid),$options_rechazadas);
$my_render_categoria_noticias_rechazadas=$link_categoria_noticias_rechazadas->toRenderable();
$my_render_categoria_noticias_rechazadas=render($my_render_categoria_noticias_rechazadas); 

$route_string='vigilancia_grupo_categoria_noticias_lo_mas_comentado';
$is_active_lo_mas_comentado=$this->vigilancia_is_active_categoria_lo_mas_comentado();    
$options_lo_mas_comentado=$this->vigilancia_get_menu_link_principal_options($options,$is_active_lo_mas_comentado);
$span_lo_mas_comentado=$this->vigilancia_get_menu_span_active_link($is_active_lo_mas_comentado);
$li_class_lo_mas_comentado=$this->vigilancia_get_li_class_principal($is_active_lo_mas_comentado);
$title_lo_mas_comentado=Markup::create(t('Most commented').$span_lo_mas_comentado);                           
//mireia2017         
//$link_categoria_noticias_validadas=Link::createFromRoute(t('Validated'),$route_string,array('group' =>$gid));
//$link_categoria_noticias_validadas=Link::createFromRoute(t('Validated'),$route_string,array('group' =>$gid,'tid'=>$tid));
$link_categoria_noticias_lo_mas_comentado=Link::createFromRoute($title_lo_mas_comentado,$route_string,array('group' =>$gid,'tid'=>$tid),$options_lo_mas_comentado);
$my_render_categoria_noticias_lo_mas_comentado=$link_categoria_noticias_lo_mas_comentado->toRenderable();
$my_render_categoria_noticias_lo_mas_comentado=render($my_render_categoria_noticias_lo_mas_comentado); 





/*$html='<nav role="navigation" aria-labelledby="block-buho-main-menu-menu" id="block-buho-main-menu" class="contextual-region block block-menu navigation menu--main">
            
  <h2 class="visually-hidden" id="block-buho-main-menu-menu">Main navigation</h2>
  <div data-contextual-id="block:block=buho_main_menu:langcode=en|menu:menu=main:langcode=en"></div>

      <div class="content">
        <div class="menu-toggle-target menu-toggle-target-show" id="show-block-buho-main-menu"></div>
    <div class="menu-toggle-target" id="hide-block-buho-main-menu"></div>
    <a class="menu-toggle" href="#show-block-buho-main-menu">Show &mdash; Main navigation</a>
    <a class="menu-toggle menu-toggle--hide" href="#hide-block-buho-main-menu">Hide &mdash; Main navigation</a>


              <ul class="clearfix menu">
                   

            <li class="menu-item">
              '.$my_render_categoria_noticias_validadas.'
              </li>

             <li class="menu-item">
              '.$my_render_categoria_noticias_rechazadas.'
              </li>



        </ul>


  </div>
</nav>';*/

    $html=array();
    $html[]='<nav aria-label="Tabs" role="navigation" class="tabs">';
    $html[]='<h2 class="visually-hidden">Primary tabs</h2>';
    $html[]='<ul class="tabs primary">';
    $html[]='<li'.$li_class_validadas.'>'.$my_render_categoria_noticias_validadas.'</li>';
    $html[]='<li'.$li_class_rechazadas.'>'.$my_render_categoria_noticias_rechazadas.'</li>';
    $html[]='<li'.$li_class_lo_mas_comentado.'>'.$my_render_categoria_noticias_lo_mas_comentado.'</li>';
    $html[]='</ul>';
    $html[]='</nav>';
    $html=implode('',$html);


return $html;

}


private function vigilancia_set_title($title){
          $request = \Drupal::request();
          if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
            $route->setDefault('_title', $title);
          }
}
//mireia2017
public function vigilancia_grupo_canales_noticias(){
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $canal_nid=$parameters->get('canal_nid');
    /*print 'gid='.$gid.'<br>';
    print 'canal_nid='.$canal_nid;
    exit();*/
    $canal=Feed::load($canal_nid);
    $canal_title=$canal->label();
    $this->vigilancia_set_title($canal_title);
    $limit=10;
    
    $group=Group::load($gid);
      $group_type=$group->getGroupType();
      $group_type_id=$group_type->id();
      $db = \Drupal::database();
      $type=$group_type_id.'-group_node-item'; 
      $pager_data=$db->select('node__feeds_item', 'node__feeds_item')
      ->extend(PagerSelectExtender::class);
      $pager_data->leftJoin('node__field_my_group', 'node__field_my_group', 'node__feeds_item.entity_id=node__field_my_group.entity_id');
      $pager_data->leftJoin('group_content_field_data', 'group_content_field_data','node__feeds_item.entity_id=group_content_field_data.entity_id');
       
      $result=$pager_data->fields('node__feeds_item', array('entity_id'))
      ->condition('node__feeds_item.feeds_item_target_id',$canal_nid)
      ->condition('node__field_my_group.field_my_group_target_id',$gid)
      ->condition('group_content_field_data.type',$type)
      ->orderBy('node__feeds_item.feeds_item_imported', 'DESC')
      ->limit($limit)
      ->execute()
      ->fetchAll();


    $rows=array(); 
    foreach ($result as $row){
        $node=Node::load($row->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;        
    }
    
    //print count($rows);exit();

    $fieldset=$this->vigilancia_canal_menu_actions($canal);
    $build['my_fieldset']['#type']='fieldset';
    $build['my_fieldset']['#title']=t('Channel Operations');
    $build['my_fieldset']['#markup']=$fieldset;
    $build['my_fieldset']['#weight']=0;
    $markup=$this->vigilancia_noticias_canales_menu();
    $markup.=implode('',$rows);
    $build['my_markup']['#markup']=$markup;
    $build['my_markup']['#weight']=1;    
    //$build['#allowed_tags']=array('fieldset','legend');
    /*$build['#type']='inline_template';
    $build['#template']='{{ markup }}';
    $build['#context']['markup']=$markup;*/

    $build['my_markup']['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
     return $build;    
  }
  private function vigilancia_is_active_validadas($konp='validadas'){
    if($this->vigilancia_is_pantalla_principal()){
      $arg=$this->vigilancia_arg();
      if(isset($arg[4]) && !empty($arg[4])){
        if($arg[4]==$konp){
          return 1;
        }
      }  
    }
    return 0;
  }
  private function vigilancia_arg(){
    $path = \Drupal::request()->getpathInfo();
    $arg  = explode('/',$path);
    return $arg;
  }
  private function vigilancia_is_pantalla_principal(){
    $arg=$this->vigilancia_arg();
    if(isset($arg[1]) && !empty($arg[1])){
      if($arg[1]=='group'){
        if(isset($arg[2]) && !empty($arg[2])){
          if(is_numeric($arg[2])){
            if(isset($arg[3]) && !empty($arg[3])){
              if($arg[3]=='vigilancia'){
                return 1;
              }
            }
          }  
        }  
      }  
    }
    return 0;  
  }
  private function vigilancia_get_menu_span_active_link($is_active){
    $result='';
    if($is_active){
      $result='<span class="visually-hidden">(active tab)</span>';
    }
    return $result;      
  }
  private function vigilancia_is_active_rechazadas(){
    return $this->vigilancia_is_active_validadas('rechazadas');
  }
  private function vigilancia_get_li_class_principal($is_active){
    $result='';
    if($is_active){
      $result=' class="is-active"';
    }
    return $result; 
  }
  private function vigilancia_is_active_lo_mas_comentado(){
    return $this->vigilancia_is_active_validadas('lo-mas-comentado');
  }
  private function vigilancia_get_menu_link_principal_options($options_in,$is_active){
    $options=$options_in;
    if($is_active){
      if(!isset($options['attributes'])){
        $options['attributes']=array();
      }
      $options['attributes']['class']='is-active';
    }
    return $options;
  }
  private function vigilancia_is_active_categoria_validadas($konp='validadas'){
    if($this->vigilancia_is_pantalla_principal()){
      $arg=$this->vigilancia_arg();
      if(isset($arg[4]) && !empty($arg[4])){
        if($arg[4]=='categoria'){
          if(isset($arg[5]) && !empty($arg[5])){
            if(is_numeric($arg[5])){
              if(isset($arg[6]) && !empty($arg[6])){
                if($arg[6]=='noticias'){
                   if(isset($arg[7]) && !empty($arg[7])){
                      if($arg[7]==$konp){
                        return 1;
                      }  
                   }  
                }  
              }               
            }
          }  
        }
      }  
    }
    return 0;  
  }
  private function vigilancia_is_active_categoria_rechazadas(){
    return $this->vigilancia_is_active_categoria_validadas('rechazadas');
  }

  private function vigilancia_is_active_categoria_lo_mas_comentado(){
    return $this->vigilancia_is_active_categoria_validadas('lo-mas-comentado');
  }
  private function vigilancia_canal_menu_actions($canal){
    global $base_url;
    $html=array();
    //$feed_type=$canal->getType();
    //$feeds_feed_type=$feed_type->id();   
    //$html[]='<fieldset>';
    //$html[]='<legend>'.t('Channel Operations').'</legend>';
    $html[]='<div class="div_canal_action_main">';
    $html[]='<div class="div_canal_action">';
    //$route_string='entity.feeds_feed_type.edit_form';
    //$link=Link::createFromRoute(t('Edit channel'),$route_string,array('feeds_feed_type'=>$feeds_feed_type));
    //$url = Url::fromUri('feed/'.$canal->id().'/edit');
    //$link = Link::fromTextAndUrl(t('Link title'),$url);
    
    $path=$base_url.'/'.\Drupal::theme()->getActiveTheme()->getPath();
    $img_src=$path.'/images/edit_canal.png';
    //print $img_src;exit();
    $title_edit_canal=t('Edit channel');    
    $img='<img src="'.$img_src.'" alt="'.$title_edit_canal.'" title="'.$title_edit_canal.'">';    
    $route_string='entity.feeds_feed.edit_form';
    $title_edit_canal=Markup::create($img);  
    $link=Link::createFromRoute($title_edit_canal,$route_string,array('feeds_feed'=>$canal->id())); 
    $my_render=$link->toRenderable();
    $html[]=render($my_render);
    $html[]='</div>';

    $html[]='<div class="div_canal_action">';
    $img_src=$path.'/images/canal_view.png';
    $title_view_canal=t('Channel file');   
    $img='<img src="'.$img_src.'" alt="'.$title_view_canal.'" title="'.$title_view_canal.'">'; 
    $route_string='entity.feeds_feed.canonical';
    $title_view_canal=Markup::create($img); 
    $link=Link::createFromRoute($title_view_canal, $route_string,array('feeds_feed'=>$canal->id()));            
    $my_render=$link->toRenderable();
    $html[]=render($my_render);        
    $html[]='</div>';    
    
    $html[]='<div class="div_canal_action">';
    $img_src=$path.'/images/import_canal.png';
    $title_import_canal=t('Update channel');   
    $img='<img src="'.$img_src.'" alt="'.$title_import_canal.'" title="'.$title_import_canal.'">';     
    $route_string='entity.feeds_feed.import_form';
    $title_import_canal=Markup::create($img); 
    $link=Link::createFromRoute($title_import_canal,$route_string,array('feeds_feed'=>$canal->id()));            
    $my_render=$link->toRenderable();
    $html[]=render($my_render);        
    $html[]='</div>';
    $html[]='</div>'; 

    //$html[]='</fieldset>';    
    return implode('',$html);

  }
  private function vigilancia_noticias_canales_menu(){


    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $canal_nid=$parameters->get('canal_nid');

    $options=array();

    $route_string='vigilancia_grupo_canales_noticias_validadas';
    $is_active_validadas=$this->vigilancia_is_active_canales_validadas();
    $options_validadas=$this->vigilancia_get_menu_link_principal_options($options,$is_active_validadas);
    $span_validadas=$this->vigilancia_get_menu_span_active_link($is_active_validadas);
    $li_class_validadas=$this->vigilancia_get_li_class_principal($is_active_validadas);
    $title_validadas=Markup::create(t('Validated').$span_validadas);           
    $link_validadas=Link::createFromRoute($title_validadas,$route_string,array('group' =>$gid,'canal_nid'=>$canal_nid),$options_validadas);
    $my_render_validadas=$link_validadas->toRenderable();
    $my_render_validadas=render($my_render_validadas); 

    $route_string='vigilancia_grupo_canales_noticias_rechazadas';
    $is_active_rechazadas=$this->vigilancia_is_active_canales_rechazadas();
    $options_rechazadas=$this->vigilancia_get_menu_link_principal_options($options,$is_active_rechazadas);
    $span_rechazadas=$this->vigilancia_get_menu_span_active_link($is_active_rechazadas);
    $li_class_rechazadas=$this->vigilancia_get_li_class_principal($is_active_rechazadas);
    $title_rechazadas=Markup::create(t('Rejected').$span_rechazadas);           
    $link_rechazadas=Link::createFromRoute($title_rechazadas,$route_string,array('group' =>$gid,'canal_nid'=>$canal_nid),$options_rechazadas);
    $my_render_rechazadas=$link_rechazadas->toRenderable();
    $my_render_rechazadas=render($my_render_rechazadas); 

    $route_string='vigilancia_grupo_canales_noticias_lo_mas_comentado';
    $is_active_lo_mas_comentado=$this->vigilancia_is_active_canales_lo_mas_comentado();
    $options_lo_mas_comentado=$this->vigilancia_get_menu_link_principal_options($options,$is_active_lo_mas_comentado);
    $span_rechazadas=$this->vigilancia_get_menu_span_active_link($is_active_rechazadas);
    $li_class_lo_mas_comentado=$this->vigilancia_get_li_class_principal($is_active_lo_mas_comentado);
    $title_lo_mas_comentado=Markup::create(t('Most commented'));            
    $link_lo_mas_comentado=Link::createFromRoute($title_lo_mas_comentado,$route_string,array('group' =>$gid,'canal_nid'=>$canal_nid),$options_lo_mas_comentado);
    $my_render_lo_mas_comentado=$link_lo_mas_comentado->toRenderable();
    $my_render_lo_mas_comentado=render($my_render_lo_mas_comentado);

    //simulatzen 
    
    $html=array();
    $html[]='<nav aria-label="Tabs" role="navigation" class="tabs">';
    $html[]='<h2 class="visually-hidden">Primary tabs</h2>';
    $html[]='<ul class="tabs primary">';
    $html[]='<li'.$li_class_validadas.'>'.$my_render_validadas.'</li>';
    $html[]='<li'.$li_class_rechazadas.'>'.$my_render_rechazadas.'</li>';
    $html[]='<li'.$li_class_lo_mas_comentado.'>'.$my_render_lo_mas_comentado.'</li>';
    $html[]='</ul>';
    $html[]='</nav>';
    $html=implode('',$html);

    return $html;
  }
   //mieia2017
   public function vigilancia_grupo_canales_noticias_validadas($flag_id='leido_interesante'){
    return $this->my_vigilancia_grupo_canales_noticias_validadas('leido_interesante');
   } 
  private function my_vigilancia_grupo_canales_noticias_validadas($flag_id='leido_interesante'){
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $canal_nid=$parameters->get('canal_nid');
    /*print 'gid='.$gid.'<br>';
    print 'canal_nid='.$canal_nid;
    exit();*/
    $canal=Feed::load($canal_nid);
    $canal_title=$canal->label();
    $this->vigilancia_set_title($canal_title);
    $limit=10;
    
    $group=Group::load($gid);
      $group_type=$group->getGroupType();
      $group_type_id=$group_type->id();
      $db = \Drupal::database();
      $type=$group_type_id.'-group_node-item'; 
      $pager_data=$db->select('node__feeds_item', 'node__feeds_item')
      ->extend(PagerSelectExtender::class);
      $pager_data->leftJoin('node__field_my_group', 'node__field_my_group', 'node__feeds_item.entity_id=node__field_my_group.entity_id');
      $pager_data->leftJoin('group_content_field_data', 'group_content_field_data','node__feeds_item.entity_id=group_content_field_data.entity_id');
      //intelsat-berria-ultimas-ekin konparatuz
      $pager_data->leftJoin('flagging', 'flagging', 'group_content_field_data.entity_id = flagging.entity_id');
       

      $result=$pager_data->fields('node__feeds_item', array('entity_id'))
      ->condition('node__feeds_item.feeds_item_target_id',$canal_nid)
      ->condition('node__field_my_group.field_my_group_target_id',$gid)
      ->condition('group_content_field_data.type',$type)
       //intelsat-berria-ultimas-ekin konparatuz
      ->condition('flagging.flag_id',$flag_id)
      ->orderBy('node__feeds_item.feeds_item_imported', 'DESC')
      ->limit($limit)
      ->execute()
      ->fetchAll();


    $rows=array(); 
    foreach ($result as $row){
        $node=Node::load($row->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;        
    }
    
    //print count($rows);exit();

    $fieldset=$this->vigilancia_canal_menu_actions($canal);
    $build['my_fieldset']['#type']='fieldset';
    $build['my_fieldset']['#title']=t('Channel Operations');
    $build['my_fieldset']['#markup']=$fieldset;
    $build['my_fieldset']['#weight']=0;
    $markup=$this->vigilancia_noticias_canales_menu();
    $markup.=implode('',$rows);
    $build['my_markup']['#markup']=$markup;
    $build['my_markup']['#weight']=1;    
    //$build['#allowed_tags']=array('fieldset','legend');
    /*$build['#type']='inline_template';
    $build['#template']='{{ markup }}';
    $build['#context']['markup']=$markup;*/

    $build['my_markup']['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
     return $build;
  }

  //mireia2017

  public function vigilancia_grupo_canales_noticias_rechazadas($flag_id='leido_no_interesante'){
    return $this->my_vigilancia_grupo_canales_noticias_validadas('leido_no_interesante');
   } 
  
  //intelsat

  /*public function vigilancia_grupo_canales_noticias_rechazadas(){

    $build['#markup']='proba';

    return $build;

  }
  */

  //intelsat

  public function vigilancia_grupo_canales_noticias_lo_mas_comentado(){

    //$build['#markup']='proba';

    //return $build;

    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $canal_nid=$parameters->get('canal_nid');
    /*print 'gid='.$gid.'<br>';
    print 'canal_nid='.$canal_nid;
    exit();*/
    $canal=Feed::load($canal_nid);
    $canal_title=$canal->label();
    $this->vigilancia_set_title($canal_title);
    $limit=10;
    
    $group=Group::load($gid);
      $group_type=$group->getGroupType();
      $group_type_id=$group_type->id();
      $db = \Drupal::database();
      $type=$group_type_id.'-group_node-item'; 
      $pager_data=$db->select('node__feeds_item', 'node__feeds_item')
      ->extend(PagerSelectExtender::class);
      $pager_data->leftJoin('node__field_my_group', 'node__field_my_group', 'node__feeds_item.entity_id=node__field_my_group.entity_id');
      $pager_data->leftJoin('group_content_field_data', 'group_content_field_data','node__feeds_item.entity_id=group_content_field_data.entity_id');
      //intelsat-berria-ultimas-ekin konparatuz
      //$pager_data->leftJoin('flagging', 'flagging', 'group_content_field_data.entity_id = flagging.entity_id');
      $pager_data->leftJoin('comment_entity_statistics', 'comment_entity_statistics', 'node__feeds_item.entity_id = comment_entity_statistics.entity_id');

      $result=$pager_data->fields('node__feeds_item', array('entity_id'))
      ->condition('node__feeds_item.feeds_item_target_id',$canal_nid)
      ->condition('node__field_my_group.field_my_group_target_id',$gid)
      ->condition('group_content_field_data.type',$type)
       //intelsat-berria-ultimas-ekin konparatuz
      ->condition('comment_entity_statistics.comment_count', 0, '>')
      ->orderBy('comment_entity_statistics.comment_count', 'DESC')
      ->limit($limit)
      ->execute()
      ->fetchAll();


    $rows=array(); 
    foreach ($result as $row){
        $node=Node::load($row->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;        
    }
    
    //print count($rows);exit();

    $fieldset=$this->vigilancia_canal_menu_actions($canal);
    $build['my_fieldset']['#type']='fieldset';
    $build['my_fieldset']['#title']=t('Channel Operations');
    $build['my_fieldset']['#markup']=$fieldset;
    $build['my_fieldset']['#weight']=0;
    $markup=$this->vigilancia_noticias_canales_menu();
    $markup.=implode('',$rows);
    $build['my_markup']['#markup']=$markup;
    $build['my_markup']['#weight']=1;    
    //$build['#allowed_tags']=array('fieldset','legend');
    /*$build['#type']='inline_template';
    $build['#template']='{{ markup }}';
    $build['#context']['markup']=$markup;*/

    $build['my_markup']['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
     return $build;

  }
/*
  //intelsat

  public function vigilancia_grupo_categoria_noticias_lo_mas_comentado(){

    $build['#markup']='proba';

    return $build;

  }
  */
  private function vigilancia_is_active_canales_validadas($konp='validadas'){
    if($this->vigilancia_is_pantalla_principal()){
      $arg=$this->vigilancia_arg();
      if(isset($arg[4]) && !empty($arg[4]) && $arg[4]=='canales'){
          if(isset($arg[5]) && !empty($arg[5]) && is_numeric($arg[5])){
              if(isset($arg[6]) && !empty($arg[6]) && $arg[6]=='noticias'){
                  if(isset($arg[7]) && !empty($arg[7])&& $arg[7]==$konp){
                      return 1;
                  }
              }
          }
      }
    }                
    return 0;
  }
  private function vigilancia_is_active_canales_rechazadas(){
    return $this->vigilancia_is_active_canales_validadas('rechazadas');
  }
  private function vigilancia_is_active_canales_lo_mas_comentado(){
    return $this->vigilancia_is_active_canales_validadas('lo-mas-comentado');
  }
  //mireia2017
  public function vigilancia_grupo_categoria_noticias_lo_mas_comentado(){
  
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $tid=$parameters->get('tid');
    
    //$title=t('Validated');
    //$title='Validated';
    $term=Term::load($tid);
    $title=$term->label();
    $this->vigilancia_set_title('News in category: '.$title);

    $limit=10;

   
      $group=Group::load($gid);
      $group_type=$group->getGroupType();
      $group_type_id=$group_type->id();
      $db = \Drupal::database();
      $type=$group_type_id.'-group_node-item'; 
      $pager_data=$db->select('node__field_item_canal_category_tid', 'node__field_item_canal_category_tid')
      ->extend(PagerSelectExtender::class);
       $pager_data->leftJoin('node__field_my_group', 'node__field_my_group', 'node__field_item_canal_category_tid.entity_id=node__field_my_group.entity_id');
       //intelsat
       //OHARRA::::agian $type begiratzea komeni da horretarako hau deskomentatu, baina flagging taulako leftJoin bakarrarekin geratu
       $pager_data->leftJoin('group_content_field_data', 'group_content_field_data', 'node__field_item_canal_category_tid.entity_id=group_content_field_data.entity_id');
       //
       //intelsat-comment_entity_statistics
       $pager_data->leftJoin('comment_entity_statistics', 'comment_entity_statistics', 'node__field_item_canal_category_tid.entity_id = comment_entity_statistics.entity_id');
      
       $result=$pager_data->fields('node__field_item_canal_category_tid', array('entity_id'))
      ->condition('node__field_item_canal_category_tid.field_item_canal_category_tid_target_id',$tid)
      ->condition('node__field_my_group.field_my_group_target_id',$gid)
      //intelsat
      //OHARRA::::agian $type begiratzea komeni da horretarako hau deskomentatu
      ->condition('group_content_field_data.type',$type)
      //intelsat-comment_entity_statistics
      ->condition('comment_entity_statistics.comment_count', 0, '>')
      //
      ->orderBy('comment_entity_statistics.comment_count', 'DESC')
      ->limit($limit)
      ->execute()
      ->fetchAll();

     
    $rows=array(); 
    foreach ($result as $row){
        $node=Node::load($row->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;        
    }
    $build['#markup']=$this->vigilancia_noticias_menu2().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
     return $build;

    
}
private function vigilancia_noticias_fuentes_menu(){

    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $tid=$parameters->get('tid');


$options=array();

$route_string='vigilancia_grupo_fuentes_noticias_validadas';
$is_active_validadas=$this->vigilancia_is_active_fuentes_validadas();
$options_validadas=$this->vigilancia_get_menu_link_principal_options($options,$is_active_validadas);
$span_validadas=$this->vigilancia_get_menu_span_active_link($is_active_validadas);
$li_class_validadas=$this->vigilancia_get_li_class_principal($is_active_validadas);
$title_validadas=Markup::create(t('Validated').$span_validadas);                           

$link_fuentes_noticias_validadas=Link::createFromRoute($title_validadas,$route_string,array('group' =>$gid,'tid'=>$tid),$options_validadas);
$my_render_fuentes_noticias_validadas=$link_fuentes_noticias_validadas->toRenderable();
$my_render_fuentes_noticias_validadas=render($my_render_fuentes_noticias_validadas);

$route_string='vigilancia_grupo_fuentes_noticias_rechazadas';
$is_active_rechazadas=$this->vigilancia_is_active_fuentes_rechazadas();
$options_rechazadas=$this->vigilancia_get_menu_link_principal_options($options,$is_active_rechazadas);
$span_rechazadas=$this->vigilancia_get_menu_span_active_link($is_active_rechazadas);
$li_class_rechazadas=$this->vigilancia_get_li_class_principal($is_active_rechazadas);
$title_rechazadas=Markup::create(t('Rejected').$span_rechazadas);                           

$link_fuentes_noticias_rechazadas=Link::createFromRoute($title_rechazadas,$route_string,array('group' =>$gid,'tid'=>$tid),$options_rechazadas);
$my_render_fuentes_noticias_rechazadas=$link_fuentes_noticias_rechazadas->toRenderable();
$my_render_fuentes_noticias_rechazadas=render($my_render_fuentes_noticias_rechazadas); 


$route_string='vigilancia_grupo_fuentes_noticias_lo_mas_comentado';
$is_active_lo_mas_comentado=$this->vigilancia_is_active_fuentes_lo_mas_comentado();
$options_lo_mas_comentado=$this->vigilancia_get_menu_link_principal_options($options,$is_active_lo_mas_comentado);
$span_lo_mas_comentado=$this->vigilancia_get_menu_span_active_link($is_active_lo_mas_comentado);
$li_class_lo_mas_comentado=$this->vigilancia_get_li_class_principal($is_active_lo_mas_comentado);
$title_lo_mas_comentado=Markup::create(t('Most commented').$span_lo_mas_comentado);                           

$link_fuentes_noticias_lo_mas_comentado=Link::createFromRoute($title_lo_mas_comentado,$route_string,array('group' =>$gid,'tid'=>$tid),$options_lo_mas_comentado);
$my_render_fuentes_noticias_lo_mas_comentado=$link_fuentes_noticias_lo_mas_comentado->toRenderable();
$my_render_fuentes_noticias_lo_mas_comentado=render($my_render_fuentes_noticias_lo_mas_comentado); 


    $html=array();
    $html[]='<nav aria-label="Tabs" role="navigation" class="tabs">';
    $html[]='<h2 class="visually-hidden">Primary tabs</h2>';
    $html[]='<ul class="tabs primary">';
    $html[]='<li'.$li_class_validadas.'>'.$my_render_fuentes_noticias_validadas.'</li>';
    $html[]='<li'.$li_class_rechazadas.'>'.$my_render_fuentes_noticias_rechazadas.'</li>';
    $html[]='<li'.$li_class_lo_mas_comentado.'>'.$my_render_fuentes_noticias_lo_mas_comentado.'</li>';
    $html[]='</ul>';
    $html[]='</nav>';
    $html=implode('',$html);


return $html;

}

public function vigilancia_grupo_fuentes_noticias_validadas($flag_id='leido_interesante'){
    return $this->my_vigilancia_grupo_fuentes_noticias_validadas('leido_interesante');
   } 
private function my_vigilancia_grupo_fuentes_noticias_validadas($flag_id='leido_interesante'){
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $tid=$parameters->get('tid');

    $term=Term::load($tid);
    $title=$term->label();
    $this->vigilancia_set_title('News in type of source: '.$title);
    $limit=10;

    //mireia2017
      $group=Group::load($gid);
      $group_type=$group->getGroupType();
      $group_type_id=$group_type->id();
      $db = \Drupal::database();
      $type=$group_type_id.'-group_node-item'; 
      $pager_data=$db->select('node__field_item_source_tid', 'node__field_item_source_tid')
      ->extend(PagerSelectExtender::class);
       $result=$pager_data->leftJoin('node__field_my_group', 'node__field_my_group', 'node__field_item_source_tid.entity_id=node__field_my_group.entity_id');
       $pager_data->leftJoin('flagging', 'flagging', 'node__field_my_group.entity_id = flagging.entity_id');
       $result=$pager_data->fields('node__field_item_source_tid', array('entity_id'))
      ->condition('node__field_item_source_tid.field_item_source_tid_target_id',$tid)
      ->condition('node__field_my_group.field_my_group_target_id',$gid)
      ->condition('flagging.flag_id',$flag_id)
      ->orderBy('node__field_item_source_tid.entity_id', 'DESC')
      ->limit($limit)
      ->execute()
      ->fetchAll();
    $rows=array(); 
    foreach ($result as $row){
        $node=Node::load($row->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;        
    }
    $build['#markup']=$this->vigilancia_noticias_fuentes_menu().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
     return $build;

    }
  

public function vigilancia_grupo_fuentes_noticias_rechazadas($flag_id='leido_no_interesante'){
    return $this->my_vigilancia_grupo_fuentes_noticias_validadas('leido_no_interesante');
  }

public function vigilancia_grupo_fuentes_noticias_lo_mas_comentado(){
  $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $tid=$parameters->get('tid');

    $term=Term::load($tid);
    $title=$term->label();
    $this->vigilancia_set_title('News in type of source: '.$title);
    $limit=10;

    //mireia2017
      $group=Group::load($gid);
      $group_type=$group->getGroupType();
      $group_type_id=$group_type->id();
      $db = \Drupal::database();
      $type=$group_type_id.'-group_node-item'; 
      $pager_data=$db->select('node__field_item_source_tid', 'node__field_item_source_tid')
      ->extend(PagerSelectExtender::class);
       $result=$pager_data->leftJoin('node__field_my_group', 'node__field_my_group', 'node__field_item_source_tid.entity_id=node__field_my_group.entity_id');
      $result=$pager_data->leftJoin('comment_entity_statistics', 'comment_entity_statistics', 'node__field_item_source_tid.entity_id = comment_entity_statistics.entity_id');
       $result=$pager_data->fields('node__field_item_source_tid', array('entity_id'))
      ->condition('node__field_item_source_tid.field_item_source_tid_target_id',$tid)
      ->condition('node__field_my_group.field_my_group_target_id',$gid)
      ->condition('comment_entity_statistics.comment_count', 0, '>')
      ->orderBy('comment_entity_statistics.comment_count', 'DESC')
      ->orderBy('node__field_item_source_tid.entity_id', 'DESC')
      ->limit($limit)
      ->execute()
      ->fetchAll();
    $rows=array(); 
    foreach ($result as $row){
        $node=Node::load($row->entity_id);
        $entity_type='node';
        $view_mode='teaser';
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $view_build = $view_builder->view($node, $view_mode);
        $node_view = render($view_build);
        $rows[]=$node_view;        
    }
    $build['#markup']=$this->vigilancia_noticias_fuentes_menu().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
     return $build;


    /*print $gid;
    print $tid;
    exit();
    */
    }
private function vigilancia_is_active_fuentes_validadas($konp='validadas'){
   if($this->vigilancia_is_pantalla_principal()){
      $arg=$this->vigilancia_arg();
      if(isset($arg[4]) && !empty($arg[4]) && $arg[4]=='fuentes'){
          if(isset($arg[5]) && !empty($arg[5]) && is_numeric($arg[5])){
              if(isset($arg[6]) && !empty($arg[6]) && $arg[6]=='noticias'){
                  if(isset($arg[7]) && !empty($arg[7])&& $arg[7]==$konp){
                      return 1;
                  }
              }
          }
      }
    }                
    return 0;
  }
private function vigilancia_is_active_fuentes_rechazadas(){
 return $this->vigilancia_is_active_fuentes_validadas('rechazadas');
}
private function vigilancia_is_active_fuentes_lo_mas_comentado(){
  return $this->vigilancia_is_active_fuentes_validadas('lo-mas-comentado');
}
}//class VigilanciaController