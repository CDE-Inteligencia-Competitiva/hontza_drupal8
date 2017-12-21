<?php
namespace Drupal\estrategia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Query\PagerSelectExtender;
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

class EstrategiaController extends ControllerBase {

  /**
   * Constructs a page with descriptive content.
   * Our router maps this method to the path 'examples/page-example'.
   */

  public function estrategia() {
    $build=array();
    $tempstore = \Drupal::service('user.private_tempstore')->get('grupo');
    $gid = $tempstore->get('grupo_select_gid');
    if(!empty($gid)){
      return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput('/group/'.$gid.'/estrategias')->toString());   
    }
    $build = array(
      '#markup'=>'Estrategia',
    );
    return $build;
  }
  
  public function estrategias_grupo(){
    $parameters = \Drupal::routeMatch()->getParameters();
    $gid=$parameters->get('group');
    $limit=10;
    $group=Group::load($gid);
    $group_type=$group->getGroupType();
    $group_type_id=$group_type->id();
    $db = \Drupal::database();
    $type=$group_type_id.'-group_node-estrategia';    
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
    $build['#markup']=$this->estrategias_menu().implode('',$rows);
    $build['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
    
    return $build; 
  }
  public function estrategia_on_entity_presave($entity){
    if(!empty($entity)){
      if($this->estrategia_is_entity_estrategia($entity)){
        $post=\Drupal::request()->request->all();
        /*echo print_r($post,1);
        exit();*/  
        /*if($this->estrategia_is_post_form_save($post,'node_estrategia_form')){
          $my_group_gid=$entity->get('field_my_group')->getValue();
          echo print_r($my_group_gid,1);exit();
        }*/
      }  
    }  
  }
  private function estrategia_is_post_form_save($post){
    $form_id_array=array('node_estrategia_form','node_estrategia_edit_form');
    if(isset($post['form_id']) && in_array($post['form_id'],$form_id_array)){
      return 1;
    }
    return 0;  
  }
  public function estrategia_is_entity_estrategia($entity,$type_id_in=''){
    $type_id=$entity->getEntityTypeId();
    if($type_id=='node'){ 
      $node_type=$entity->getType();
      $node_type_array=array('estrategia');
      if(in_array($node_type,$node_type_array)){
        return 1;
      }    
    }
    return 0;    
  }
  public function estrategia_on_entity_save($entity,$action){
    if(!empty($entity)){
      if($this->estrategia_is_entity_estrategia($entity)){
        $post=\Drupal::request()->request->all();
        if($this->estrategia_is_post_form_save($post)){
            $my_group_gid=$entity->get('field_my_group')->getValue();
            //echo print_r($my_group_gid,1);exit();
            $grupo_nid=0;
            if(isset($my_group_gid[0]['target_id']) && !empty($my_group_gid[0]['target_id'])){
              $grupo_nid=$my_group_gid[0]['target_id'];
              if($action=='insert'){          
                $my_group=Group::load($my_group_gid[0]['target_id']);
                $my_plugin_id='group_node:estrategia';
                $my_group->addContent($entity,$my_plugin_id);
              }
            }
            $this->estrategia_save($entity,$grupo_nid);   
        }
      }  
    }  
  }

  private function estrategias_menu(){
    return '';
  }
  
  private function estrategia_save($entity,$grupo_nid){
    $nid=$entity->id();
    //print 'nid='.$nid;exit();    
    //$node=Node::load($nid);
    $user = \Drupal::currentUser();
    $uid=$user->id();    
    $vid_array=$entity->get('vid')->getValue();
    //echo print_r($vid_array,1);exit();
    $vid=$vid_array[0]['value'];

    $post=\Drupal::request()->request->all();    
    $importancia_reto=$post['importancia_reto'];
    $facilidad_reto=$post['facilidad_reto'];
    $fecha_cumplimiento=$post['fecha_cumplimiento'];

    $estrategia_row=$this->estrategia_get_estrategia_row($nid,$vid);
    //echo print_r($importancia_reto,1);exit();   
    
    if(isset($estrategia_row->nid) && !empty($estrategia_row->nid)){
      $query = \Drupal::database()->update('estrategia');
      $query->fields([
        //'grupo_nid' => $grupo_nid,
        'grupo_seguimiento_nid' => $grupo_nid,
        'importancia_reto' => $importancia_reto,
        'facilidad_reto' => $facilidad_reto,
        'fecha_cumplimiento' => $fecha_cumplimiento,
      ]);
      $query->condition('nid',$nid);
      $query->condition('vid',$vid);
      $query->execute();
    }else{
      $query = \Drupal::database()->insert('estrategia');
      $query->fields([
        'nid',
        'vid',
        'origen_uid',
        'grupo_nid',
        'grupo_seguimiento_nid',
        'importancia_reto',
        'facilidad_reto',
        'fecha_cumplimiento',
      ]);
      $query->values([
        $nid,
        $vid,
        $uid,
        $grupo_nid,
        $grupo_nid,
        $importancia_reto,
        $facilidad_reto,
        $fecha_cumplimiento,
      ]);
      $query->execute();
    }
  }
  public function estrategia_get_estrategia_row($nid,$vid){
    $estrategia_array=$this->estrategia_get_estrategia_array($nid,$vid);
    if(!empty($estrategia_array)){
      return $estrategia_array[0];
    }
    return '';
  }  
  private function estrategia_get_estrategia_array($nid,$vid){
    $result=array();
    $db = \Drupal::database();
    $query=$db->select('estrategia', 'estrategia')
      ->fields('estrategia', array('nid','vid','origen_uid','grupo_nid','grupo_seguimiento_nid','importancia_reto','facilidad_reto','fecha_cumplimiento'))
      ->condition('estrategia.nid',$nid)
      ->condition('estrategia.vid',$vid)
      ->orderBy('estrategia.nid', 'DESC')
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
  public function estrategia_my_get_evaluacion_options($with_zero=0,$my_type=''){
        $result=array();
              if($with_zero){
                  $result[0]=0;
              }
        $num=5;
        if(empty($my_type)){
            for($i=1;$i<=$num;$i++){
                    $result[$i]=$i;
            }
        }else{
            $this->estrategia_inc_add_evaluacion_disabled_option($my_type,$result);
            switch($my_type){
                case 'eval_accesibilidad':
                    $result[1]='1='.t('Key Technology is owned by a competitor');
                    $result[2]='2='.t('Key Technology is difficult to access');
                    $result[3]='3='.t('Key Technology is accessible');
                    $result[4]='4='.t('Key Technology is very accessible');
                    $result[5]='5='.t('Key Technology is very cheap or free');
                    break;
                case 'eval_riesgo_complejidad':
                    $result[1]='1='.t('The project has many possibilities to fail');
                    $result[2]='2='.t('The project is quite complex');
                    $result[3]='3='.t('The risk of the project is normal');
                    $result[4]='4='.t('The project is easy to do');
                    $result[5]='5='.t('The project has no risk');
                    break;
                case 'eval_inversiones':
                    $result[1]='1='.t('The project requires a high investment');
                    $result[2]='2='.t('The project requires a considerable investment');
                    $result[3]='3='.t('The project requires a normal investment');
                    $result[4]='4='.t('The project requires a little investment');
                    $result[5]='5='.t('The project requires a minimum investment');
                    break;
                case 'eval_potencial_mercado':
                    $result[1]='1='.t('The project does not open any niche market');
                    $result[2]='2='.t('The project could open a niche market');
                    $result[3]='3='.t('The project is going to open a niche market');
                    $result[4]='4='.t('The project is going to open some niche markets');
                    $result[5]='5='.t('The project is going to open many niche market');
                    break;
                case 'eval_impacto_negocio':
                    $result[1]='1='.t('The project does not reinforce our strategy');
                    $result[2]='2='.t('The project reinforces a little to our strategy');
                    $result[3]='3='.t('The project reinforces our strategy');
                    $result[4]='4='.t('The project reinforces a lot our strategy');
                    $result[5]='5='.t('The project is fully aligned with our strategy');
                    break;
                case 'eval_rapidez_de_ejecucion':
                    $result[1]='1='.t('Very long execution delay');
                    $result[2]='2='.t('Long execution delay');
                    $result[3]='3='.t('Normal execution delay');
                    $result[4]='4='.t('Short execution delay');
                    $result[5]='5='.t('Very short execution delay');
                   break;
                case 'pond_accesibilidad':
                case 'pond_riesgo_complejidad':
                case 'pond_inversiones':
                case 'pond_potencial_mercado':
                case 'pond_impacto_negocio':
                case 'pond_rapidez_de_ejecucion':
                    $result[1]='1='.t('Very low relative importance');
                    $result[2]='2='.t('Low relative importance');
                    $result[3]='3='.t('Average relative importance');
                    $result[4]='4='.t('High relative importance');
                    $result[5]='5='.t('Very high relative importance');
                    break;
                case 'valor_reto':
                    $result[1]='1='.t('The most important challenge');
                    $result[2]='2='.t('Very important challenge');
                    $result[3]='3='.t('Important challenge');
                    $result[4]='4='.t('Secondary challenge');
                    $result[5]='5='.t('Marginal challenge');
                    break;
                case 'importancia_reto':
                    $result[1]='1='.t('Very low');
                    $result[2]='2='.t('Low');
                    $result[3]='3='.t('Normal');
                    $result[4]='4='.t('High');
                    $result[5]='5='.t('Very high');                    
                    break;
                case 'facilidad_reto':
                    $result[1]='1='.t('Very difficult');
                    $result[2]='2='.t('Difficult');
                    $result[3]='3='.t('Normal');
                    $result[4]='4='.t('Easy');
                    $result[5]='5='.t('Very easy');
                    break;
                case 'importancia_despliegue':
                    $result[1]='1='.t('Marginal Subchallenge');
                    $result[2]='2='.t('Secondary Subchallenge');
                    $result[3]='3='.t('Important Subchallenge');
                    $result[4]='4='.t('Very important Subchallenge');
                    $result[5]='5='.t('The most important Subchallenge');
                    break;
                case 'valor_decision':
                    $result[1]='1='.t('Marginal decision');
                    $result[2]='2='.t('Secondary decision');
                    $result[3]='3='.t('Important decision');
                    $result[4]='4='.t('Very important decision');
                    //$result[5]='5='.t('The most important decision to achieve the challenge or subchallenge');
                    $result[5]='5='.t('The most important decision');
                    break;
                case 'importancia':
                    $result[1]='1='.t('Marginal Key Question');
                    $result[2]='2='.t('Secondary Key Question');
                    $result[3]='3='.t('Important Key Question');
                    $result[4]='4='.t('Very important Key Question');
                    $result[5]='5='.t('The most important Key Question');
                    break;
                case 'accesibilidad':
                    $result[1]='1='.t('Information very difficult to get');
                    $result[2]='2='.t('Not very accessible information');
                    $result[3]='3='.t('Available information');
                    $result[4]='4='.t('Easily accessible information');
                    $result[5]='5='.t('Free disseminated information');
                    break;
                case 'evaluar_doc':
                    $result[1]='1='.t('No value');
                    $result[2]='2='.t('Little value');
                    $result[3]='3='.t('Normal value');
                    $result[4]='4='.t('High value');
                    $result[5]='5='.t('Very high value');
                    break;
                case 'eval_oportunidad':
                    $result[1]='1='.t('Marginal business opportunity');
                    $result[2]='2='.t('Small business opportunity');
                    $result[3]='3='.t('Good business opportunity');
                    $result[4]='4='.t('Pretty good business opportunity');
                    $result[5]='5='.t('Great Business Opportunity');
                    //echo print_r($result,1);
                    break;
                default:
                    break;
            }
        }
        //$result=proyecto_reverse_eval_options($result,$my_type);
    return $result;
  }
  private function estrategia_inc_add_evaluacion_disabled_option($my_type,&$result){
    if(in_array($my_type,array('importancia_despliegue','valor_decision'))){
        $result[0]='0='.t('Disabled');
    }
  }
  public function estrategia_add_despliegue(){
    print 'prueba';exit();
  }  
}//class EstrategiaController