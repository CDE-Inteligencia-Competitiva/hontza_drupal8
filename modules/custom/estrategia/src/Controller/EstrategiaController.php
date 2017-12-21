<?php
namespace Drupal\estrategia\Controller;

use Drupal\Core\Access\AccessResult;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\vigilancia\Controller\VigilanciaController;
use Drupal\estrategia\Controller\EstrategiaDesplegarController;
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

  public function estrategias_desplegar(){
    //print 'prueba desplegar';exit();
    
    $estrategia_desplegar_controller= new EstrategiaDesplegarController();
    $rows=$estrategia_desplegar_controller->estrategia_get_estrategia_arbol_rows();

    //$rows=$this->estrategia_get_estrategia_arbol_rows();

    $header=array();
    $header[0]=t('Title');
    //$header[1]=t('Status');     
    //$header[2]=t('Action');     
       
    //$rows=$this->estrategia_get_estrategia_arbol_rows(1); 
    //echo print_r ($rows,1); exit();
    
    $build=array('#type' => 'table',
      '#header' => $header,
      '#rows' => $rows);
    return $build; 
  } 

  public function estrategias_importar(){
    //print 'prueba importar';exit();

    $this->estrategia_importar_form();
  } 

  public function estrategias_preguntas_clave(){
    //print 'prueba preguntas clave';exit();

        $estrategia_desplegar_controller= new EstrategiaDesplegarController();
        $rows=$estrategia_desplegar_controller->estrategia_get_estrategia_arbol_rows();

    $header=array();
    $header[0]=t('Challenge');
    $header[1]=t('Value');
    $header[2]=t('SubChallenge');
    $header[3]=t('Value');
    $header[4]=t('Decision');
    $header[5]=t('Value');
    $header[6]=t('Key Question');
    $header[7]=t('Importance');
    $header[8]=t('Accessibility');
    $header[9]=t('Score');
    $header[10]=t('Ranking');
       
    //$rows=$this->estrategia_get_estrategia_arbol_rows(1); 
    //echo print_r ($rows,1); exit();
    
    $build=array('#type' => 'table',
      '#header' => $header,
      '#rows' => $rows);
    return $build; 
  } 

  public function estrategias_tabla_preguntas_canales(){
    //print 'prueba tabla preguntas canales';exit();

    
    //drupal_set_title(t('Table Questions - Channels'));
    $this->boletin_report_no_group_selected_denied();
    $this->hontza_grupo_shared_active_tabs_access();
    $output='';
    //$output.=estrategia_resumen_preguntas_clave_canales_html();
    //$output.=estrategia_resumen_preguntas_clave_canales_html(1);
    $output.=$this->estrategia_create_menu_resumen_preguntas_clave_canales();
    $output.=$this->estrategia_create_menu_resumen_preguntas_clave_canales_para_ordenar();
    $output.='<div>'.$this->estrategia_resumen_preguntas_clave_canales_volver_link().'&nbsp;|&nbsp;';
    //$output.=l(t('Download csv'),'download_resumen_preguntas_clave_canales').'&nbsp;|&nbsp;';
    //$output.=l(t('Print'),'imprimir_resumen_preguntas_clave_canales',array('attributes'=>array('target'=>'_blank'))).'&nbsp;|&nbsp;';
    //$output.=l(t('Fullscreen'),'imprimir_resumen_preguntas_clave_canales/fullscreen',array('attributes'=>array('target'=>'_blank'))).'&nbsp;';
    $output.='</div>';
    $output.=$this->estrategia_resumen_preguntas_clave_canales_mensaje_de_los_navegadores();
    $output.=$this->estrategia_resumen_preguntas_clave_canales_html(2);
    return $output;    
    
  }

  public function estrategias_descargar(){
    //print 'prueba descargar';exit(); 

    $data_csv_array=$this->estrategia_create_resumen_preguntas_clave_canales_data_csv_array();
    $this->estrategia_call_download_resumen_preguntas_clave_canales_csv($data_csv_array);
    $mycsv_download=$this->estrategia_tabla_csv_download();
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

  public function estrategia_define_fecha_cumplimiento($node,$field='fecha_cumplimiento'){
    if(!empty($node)){
      $nid=$node->id();    
      $vid=$node->getRevisionId();
      $estrategia_row=$this->estrategia_get_estrategia_row($nid,$vid);
      //echo print_r($estrategia_row,1);exit();
      if(isset($estrategia_row->$field) && !empty($estrategia_row->$field)){
        //return $estrategia_row->$field;
        $fecha=date("Y-m-d",$estrategia_row->$field);
        return $fecha;
      }
    }
    //else{
      $fecha=date("Y-m-d", strtotime("+6 months"));
      /*$result=$this->estrategia_create_fecha_array($fecha,1);
      return $result;*/
      return $fecha;
    //}
    //return 0;
  }

  public function estrategia_update_fecha_cumplimiento($node,$field='fecha_cumplimiento'){
    if(!empty($node)){
      $nid=$node->id();    
      $vid=$node->getRevisionId();
      $estrategia_row=$this->estrategia_get_estrategia_row($nid,$vid);
      //echo print_r($estrategia_row,1);exit();

      if(isset($estrategia_row->$field) && !empty($estrategia_row->$field)){
        //return $estrategia_row->$field;
        $fecha=date("Y-m-d",$estrategia_row->$field);
        return $fecha;
      }
    }else{
      $fecha=date("Y-m-d", strtotime("+6 months"));
      /*$result=$this->estrategia_create_fecha_array($fecha,1);
      return $result;*/
      return $fecha;
    }
    return 0;
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
    $fecha_cumplimiento = strtotime($fecha_cumplimiento);
    $estrategia_row=$this->estrategia_get_estrategia_row($nid,$vid);
    
/*/...codigo nuevo    
    $fecha_cumplimiento=0;
    if(isset($post['fecha_cumplimiento']) && !empty($post['fecha_cumplimiento'])){
      $fecha_cumplimiento=$post['fecha_cumplimiento'];
      //$fecha_cumplimiento=0;
    }
//...*/

    if( isset($_POST['no_control_date'])){
      $no_control_date=$post['no_control_date'];
    }else{
      $no_control_date=0;
    }

//    if(!empty($no_control_date)){
//      $fecha_cumplimiento=$estrategia_row->fecha_cumplimiento;
//    }

    if(isset($estrategia_row->nid) && !empty($estrategia_row->nid)){

      //if que te permite crear reto sin control date
      if(isset($no_control_date) && !empty($no_control_date)){
        $fecha_cumplimiento=$estrategia_row->fecha_cumplimiento;
      } 

      $query = \Drupal::database()->update('estrategia');
      $query->fields([
        //'grupo_nid' => $grupo_nid,
        'grupo_seguimiento_nid' => $grupo_nid,
        'importancia_reto' => $importancia_reto,
        'facilidad_reto' => $facilidad_reto,
        'fecha_cumplimiento' => $fecha_cumplimiento,
        'no_control_date' => $no_control_date,
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
        'no_control_date',        
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
        $no_control_date,
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
      //->fields('estrategia', array('nid','vid','origen_uid','grupo_nid','grupo_seguimiento_nid','importancia_reto','facilidad_reto','fecha_cumplimiento'))
      ->fields('estrategia')
      ->condition('estrategia.nid',$nid)
      ->condition('estrategia.vid',$vid)
      ->orderBy('estrategia.nid', 'DESC')
      //->limit($limit)
      ->execute();
    $rows=array();  
    while($row=$query->fetchObject()){
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
            break;
          default:
            break;
        }
      }
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

  public function estrategia_get_sel_estrategia_arbol_rows($my_list){
    $estrategia_desplegar_controller=new EstrategiaDesplegarController();
    $my_list=$estrategia_desplegar_controller->estrategia_get_estrategia_arbol_rows(0);
    $arbol=array();

    if(count($my_list)>0){
    $kont=0;
      foreach($my_list as $i=>$node){
        $arbol[$kont]['title']=$node->label();
        $arbol[$kont]['nid']=$node->id();
        $arbol[$kont]['my_level']=1;
        $kont++;

        /*$despliegue_list=estrategia_get_estrategia_despliegue_list($node->nid);
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

  function prepare_estrategia_arbol_by_pro($rows,$pro){
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



//DESCARGAR

  function estrategia_create_resumen_preguntas_clave_canales_data_csv_array(){
    $result=array();
    $rows=$this->estrategia_informacion_get_array();
    if(!empty($rows)){
        $canales=$this->estrategia_get_canales_del_grupo();        
        if(!empty($canales)){    
            $result[0]=estrategia_resumen_preguntas_clave_canales_headers_csv($canales);
            $kont=1;
            foreach($rows as $i=>$informacion){            
                $result[$kont][0]=$informacion->title;
                $k=1;
                if(!empty($canales)){
                    foreach($canales as $i=>$canal){
                        $responde_array=informacion_get_canal_informacion_array($canal->nid,$informacion->nid);
                        $ekis='';
                        if(count($responde_array)>0){
                            $ekis='X';
                        }
                        $result[$kont][$k]=$ekis;
                        $k++;
                    }
                }
                $kont++;
            }
        }    
    }
    return $result;
  }

  function estrategia_informacion_get_array($is_agrupar_por_estrategia=1,$is_valor_order=0){
/* comentado de forma PROVISIONAL

    $result=array();
    $sql=$this->informacion_define_list_sql();
    $res=db_query($sql);
    $kont=0;
    while($row=db_fetch_object($res)){
        $node=node_load($row->nid);
        $result[$kont]=$node;
        $kont++;
    }
*/    
    $result=$this->informacion_get_informacion_en_orden_de_despliegue_estrategico();
                
    if($this->estrategia_resumen_preguntas_clave_canales_is_order_importance() || $is_valor_order){
      $is_numeric=1;
      $result=array_ordenatu($result, 'puntuacion_total', 'desc', $is_numeric);
    }/*else{  
        //if($is_agrupar_por_estrategia && !empty($result)){
            //$ordenado=array();
            //$estrategia_array=estrategia_tabla(0);
            //$estrategia_array=estrategia_get_despliegue_estrategico_array();
            foreach($estrategia_array as $i=>$estrategia){
                foreach($result as $i=>$r){
                    $estrategia_informacion=informacion_get_estrategia($r);
                    if($estrategia_informacion->nid==$estrategia->nid){
                        $ordenado[]=$r;
                    }
                }
            }
            //return $ordenado;
        //}
    }*/
    return $result;
  }

  function informacion_define_list_sql($is_calendario=0,$time_ini='',$time_end=''){
    $sql='';  
    $where=array();
    //
    $where[]='n.promote = 1';
    $where[]='n.status = 1';
    $where[]='n.type="informacion"';
    if($is_calendario){
      $where[]='i.fecha_cumplimiento>='.$time_ini.' AND i.fecha_cumplimiento<='.$time_end.' AND i.no_control_date!=1';
    }
    //
    //$my_grupo=og_get_group_context(); 
    //if(!empty($my_grupo) && isset($my_grupo->nid) && !empty($my_grupo->nid)){
    //  $where[]='e.grupo_nid='.$my_grupo->nid;
    //}
//    $empresa_nid=my_get_empresa_nid();
    //print 'oportunidad_nid(list)='.$oportunidad_nid.'<BR>';
    //if(!empty($empresa_nid)){
    //  $where[]='i.empresa_nid='.$empresa_nid;
    //}
    //
    $num_rows = FALSE;
    //if(!empty($empresa_nid) || $user->uid==1){
    if($this->is_where_estrategia_por_grupo()){
        $where_grupo=get_where_estrategia_por_grupo("de","informacion");
        if(!empty($where_grupo)){
            $where[]=$where_grupo;
        }
        $decision_nid=my_get_informacion_decision_nid();
        //print 'idea_nid(list)='.$idea_nid.'<BR>';
        if(!empty($decision_nid) && $decision_nid!='todas'){
              $where[]='i.decision_nid='.$decision_nid;
        }
        //$order_by=' ORDER BY n.sticky DESC, n.created DESC';
        //$order_by=' ORDER BY n.sticky DESC, n.created ASC';
        //$order_by=' ORDER BY i.peso ASC,n.sticky DESC, n.created ASC';
        $order_by=' ORDER BY i.importancia ASC,n.sticky DESC, n.created ASC';
        //
        $sql='SELECT n.nid, n.sticky, n.created
        FROM {node} n
        LEFT JOIN {informacion} i ON n.nid=i.nid
        LEFT JOIN {decision} decision ON i.decision_nid=decision.nid
        LEFT JOIN {despliegue} de ON decision.despliegue_nid=de.nid
        WHERE '.implode(' AND ',$where).$order_by;
    }     
   return $sql;
  }

  function estrategia_resumen_preguntas_clave_canales_is_order_importance(){
    //$param0=arg(0);
    $my_array=array('resumen_preguntas_clave_canales','resumen_preguntas_clave_canales_fila_canal');
    if(!empty($param0) && in_array($param0,$my_array)){
        $param1=arg(1);
        if(!empty($param1) && $param1=='order_importance'){
            return 1;
        }    
    }
    return 0;
  }

  function is_where_estrategia_por_grupo(){
    global $user;
    if($user->uid==1){
        return 1;
    }
    if(isset($user->og_groups) && !empty($user->og_groups)){
        return 1;
    }
    return 0;
  }

  function informacion_get_informacion_en_orden_de_despliegue_estrategico(){
    $result=array();
    $despliegue_estrategico=$this->estrategia_get_despliegue_estrategico_info();
    $informacion_nid_array=$despliegue_estrategico['informacion_list'];
    if(!empty($informacion_nid_array)){
        foreach($informacion_nid_array as $i=>$informacion_nid){
            $informacion=node_load($informacion_nid);
            if(isset($informacion->nid) && !empty($informacion->nid)){
                $result[]=$informacion;
            }
        }
    }
    return $result;
  }

  function estrategia_get_despliegue_estrategico_info(){
    $result=array();
    //$rows=$this->estrategia_get_estrategia_arbol_rows(0);
    
    $estrategia_desplegar_controller= new EstrategiaDesplegarController();
    $rows=$estrategia_desplegar_controller->estrategia_get_estrategia_arbol_rows(0);
    return $this->estrategia_create_arbol_despliegue_estrategico($rows);
  }

  function is_reto_del_grupo($grupo_seguimiento_nid){
    if($this->is_reto_al_que_responde_pantalla()){
        $my_grupo=og_get_group_context();
        if(!empty($my_grupo) && isset($my_grupo->nid) && !empty($my_grupo->nid)){
            if($grupo_seguimiento_nid==$my_grupo->nid){
                return 1;
            }
        }
        return 0;
    }else{
        return 1;
    }
  }

  function is_reto_al_que_responde_pantalla(){
    //gemini-2014
    if($this->is_idea() 
    //|| is_oportunidad() || is_proyecto() || hontza_is_canal_formulario() || red_is_item_formulario() || red_is_noticia_de_usuario_formulario()
    ){        
        return 1;
    }
    //intelsat-2015
    if($this->estrategia_inc_is_reto_responde_formulario()){
        return 1;
    }
    //
    return 0;
  }


  //intelsat-2015
  function estrategia_inc_is_reto_responde_formulario(){
/*    
    if(hontza_is_canal_formulario() || red_is_item_formulario() || red_is_noticia_de_usuario_formulario()){
        return 1;
    }
    if(hontza_canal_rss_is_debate_formulario() || hontza_canal_rss_is_wiki_formulario()){
        return 1;
    }
    //intelsat-2015
    if(module_exists('canal_usuario')){
        if(canal_usuario_is_canal_usuario_formulario()){
            return 1;
        }
    }
*/
    return 0;
  }

  //gemini
  function is_idea($param_ideas=''){
/*
    if(strcmp(arg(0),'idea')==0){
      return 1;
    }
    if(strcmp(arg(0),'ideas')==0){
              if(empty($param_ideas)){
                  return 1;
              }else if(strcmp(arg(1),$param_ideas)==0){
                  return 1;
              }
          }
    if(is_ficha_node('idea')){
              if(idea_is_origenes()){                
                  return 0;
              }else{
                return 1;
              }  
    }
          if(idea_in_pantallas_enlace()){
              return 1;
          }
          //gemini-2014
          if(strcmp(arg(0),'criterios_de_evaluacion')==0){
      return 1;
    }
          //
*/          
    return 0;
  }

  function estrategia_create_arbol_despliegue_estrategico($my_list){
          $arbol=array();
          $result_informacion_list=array();
    if(count($my_list)>0){
      $estrategia_desplegar_controller= new EstrategiaDesplegarController();
      $kont=0;
      foreach($my_list as $i=>$node){
        //intelsat-2017-error-estrategia-nid-empty
        //se ha descomentado esto
        $node_nid=$node->id();

                    if(!$this->is_reto_del_grupo($node->grupo_seguimiento_nid)){                        
                        continue;
                    }
                    $arbol[$kont]['estrategia_nid']=$node->nid;

                    $despliegue_list=$estrategia_desplegar_controller->estrategia_get_estrategia_despliegue_list($node_nid);

                    if(count($despliegue_list)>0){
                        foreach($despliegue_list as $k=>$despliegue_row){
                            $arbol[$kont]['despliegue_list'][$k]['despliegue_nid']=$despliegue_row->nid;
                            $decision_list=$this->get_despliegue_decision_list($despliegue_row->nid);
                            if(count($decision_list)>0){
                                foreach($decision_list as $a=>$decision_row){
                                    $arbol[$kont]['despliegue_list'][$k]['decision_list'][$a]['decision_nid']=$decision_row->nid;
                                    $informacion_list=$this->get_decision_informacion_list($decision_row->nid);
                                    if(count($informacion_list)>0){
                                        foreach($informacion_list as $b=>$informacion_row){
                                            $arbol[$kont]['despliegue_list'][$k]['decision_list'][$a]['informacion_list'][$b]=$informacion_row->nid;
                                            $result_informacion_list[]=$informacion_row->nid;
                                        }
                                    }    
                                }
                            }    
                        }
                    }
                    $kont++;
                }
        }
        //print count($informacion_list);exit();
        $result=array();
        $result['arbol']=$arbol;
        $result['informacion_list']=$result_informacion_list;
        return $result;
  }

  //gemini
  function get_decision_informacion_list($decision_nid){
    $result=array();
    $where=array();
    $where[]="1";
    $where[]='i.decision_nid='.$decision_nid;
    //$sql="SELECT n.*,i.peso,i.decision_nid,i.grupo_seguimiento_nid
    
    $sql="SELECT n.*,i.peso,i.decision_nid 
    FROM {node} n LEFT JOIN {informacion} i ON n.nid=i.nid
    WHERE ".implode(" AND ",$where).$order_by;
    
    //ORDER BY i.peso ASC,n.created ASC;
    //print $sql;exit();
    $res=db_query($sql);
    //while($row=db_fetch_object($res)){ //drupal desactualizado
    while($row=$res->fetchObject()){
      $result[]=$row;
    }
    return $result;
  }




  //gemini
  function get_despliegue_decision_list($despliegue_nid){
    $result=array();
    $where=array();
    $where[]="1";
    $where[]='d.despliegue_nid='.$despliegue_nid;
    //$sql="SELECT n.*,d.peso,d.despliegue_nid,d.grupo_seguimiento_nid
    //intelsat-2016
    //      $order_by=" ORDER BY d.peso ASC,n.created ASC";
          //intelsat-2016
          //$order_by=$this->decision_inc_get_order_by($order_by);
    
    $sql="SELECT n.*,d.peso,d.despliegue_nid 
    FROM {node} n LEFT JOIN {decision} d ON n.nid=d.nid
    WHERE ".implode(" AND ",$where).$order_by;
    
    //print $sql;exit();
    $res=db_query($sql);
    //while($row=db_fetch_object($res)){ //drupal desactualizado
    while($row=$res->fetchObject()){
      $result[]=$row;
    }
    return $result;
  }

  //intelsat-2016
  function estrategia_tabla_csv_download($compartir_documentos_estrategia_nid='',$is_result=0){
    $is_tabla_csv_download=1;
    $my_list=$this->estrategia_tabla(0,1,$is_tabla_csv_download,$compartir_documentos_estrategia_nid);
    
    if(!empty($my_list)){
        $headers=array();
        $headers[0]=estrategia_tabla_define_headers($is_tabla_csv_download);
        $my_list=array_merge($headers,$my_list);
        if($is_result){
            return $my_list;
        }
        estrategia_call_download_resumen_preguntas_clave_canales_csv($my_list,'key-questions',";",$is_result);        
    }else{
      
        return t('There are no contents');
    }
  }

  function estrategia_tabla($is_html=1,$is_key_question_csv=0,$is_tabla_csv_download=0,$compartir_documentos_estrategia_nid='') {    
    $this->estrategia_active_tabs_access();
    //gemini-2014
    $this->hontza_grupo_shared_active_tabs_access();
    $where=array();
    //
    $where[]='node_field_data.promote = 1';
    $where[]='node_field_data.status = 1';
    $where[]='n.type="estrategia"';
    //
    /*$my_grupo=og_get_group_context(); 
    if(!empty($my_grupo) && isset($my_grupo->nid) && !empty($my_grupo->nid)){
    $where[]='e.grupo_nid='.$my_grupo->nid;
    }*/
    $empresa_nid=$this->my_get_empresa_nid();
    //print 'oportunidad_nid(list)='.$oportunidad_nid.'<BR>';
    if(!empty($empresa_nid)){
          //correo 21-05-2012 08:00 ->hemos comentado esto
      //$where[]='e.empresa_nid='.$empresa_nid;
    }

    //correo 21-05-2012 08:00
    $where_grupo=$this->get_where_estrategia_por_grupo("e","estrategia");
        //print 'where_grupo='.$where_grupo.'<BR>';
        if(!empty($where_grupo)){
            $where[]=$where_grupo;
        }

    //intelsat-2016
    if(!empty($compartir_documentos_estrategia_nid)){
        $where[]='n.nid='.$compartir_documentos_estrategia_nid;
    }    
        
    //
    //$order_by=' ORDER BY n.sticky DESC, node_field_data.created DESC';
    //$order_by=' ORDER BY n.sticky DESC, node_field_data.created ASC';
    $order_by=' ORDER BY e.peso ASC,node_field_data.sticky DESC, node_field_data.created ASC';
    //
    $sql='SELECT n.nid, node_field_data.sticky, node_field_data.created 
    FROM {node} n
    LEFT JOIN {estrategia} e ON n.vid=e.vid
    LEFT JOIN {node_field_data} ON n.vid=node_field_data.vid
    WHERE '.implode(' AND ',$where).$order_by;
    //print $sql;exit();
/*    $my_limit=variable_get('default_nodes_main', 100);
    
    if(!$is_html){
        $my_limit=20000;
    }
*/    
    //print $sql;
    //$result = pager_query(db_rewrite_sql($sql), variable_get('default_nodes_main', 10));
    $result = db_query($sql);

    $output = '';
    //$output .= my_create_boton_volver('estrategias');  
    //$output .= create_menu_estrategia();
    //$output .= estrategia_define_key_questions_botones();
    $num_rows = FALSE;
    $rows=array();
    $my_list=array();
    //while ($node = db_fetch_object($result)) {
    while($node=$result->fetchObject()){

      //$output .= node_view(node_load($node->nid), 1);
    $my_node=node_load($node->nid);
    //echo print_r($my_node,1);
    //$rows[]=array($my_node->title,$my_node->valor_reto);
    $my_list[]=$my_node;
      $num_rows = TRUE;
    }


    /*$my_list=calcular_puntuacion_total($my_list);

    if(is_estrategia('tabla_puntuacion_total')){
        $my_list=array_ordenatu($my_list,'puntuacion_total','asc',1);
    }*/

    $tabla=$this->create_tabla($my_list);
    
     
    if(!$is_html && !$is_key_question_csv){
        return $my_list;
    }
    
    $tabla=calcular_puntuacion_total($tabla);
   //echo print_r($tabla,1);exit();

    //$tabla_ordenada=array_ordenatu($tabla,'puntuacion_total','asc',1);
    $tabla_ordenada=array_ordenatu($tabla,'puntuacion_total','desc',1);
    
    if(is_estrategia('tabla_puntuacion_total')){
        $tabla=set_numero_ranking_ordenado($tabla_ordenada);
    }else{
        $tabla_ordenada=set_numero_ranking_ordenado($tabla_ordenada);
        $tabla=set_numero_ranking_sin_ordenar($tabla,$tabla_ordenada);
    }
   
    if(count($tabla)>0){
      foreach($tabla as $i=>$my_row){
                $rows[$i]=array();
                  if($is_tabla_csv_download){
                      $rows[$i][]=$my_row->fecha_control_reto;
                  }
      $rows[$i][]=$my_row->reto;
                  //intelsat-2015
      if($is_tabla_csv_download){
                      $rows[$i][]=$my_row->importancia_reto;  
                      $rows[$i][]=$my_row->facilidad_reto;  
                      $rows[$i][]=$my_row->fecha_control_despliegue;                
                  //    
                  }else{
                      $rows[$i][]=$my_row->valor_reto;                
                  }
      $rows[$i][]=$my_row->despliegue_del_reto;
                  $rows[$i][]=$my_row->importancia_despliegue;
      $rows[$i][]=$my_row->decision;
      $rows[$i][]=$my_row->valor_decision;
      $rows[$i][]=$my_row->informacion_necesaria;
      $rows[$i][]=$my_row->importancia;
      //$rows[$i][]=$my_row->esfuerzo;
                  $rows[$i][]=$my_row->accesibilidad;
      $rows[$i][]=$my_row->puntuacion_total_zero;
      $rows[$i][]=$my_row->numero_ranking;
                  //$rows[$i][]=$my_row->grupo_seguimiento;
    }
    }
    
    if($is_key_question_csv){
        return estrategia_tabla_strip_tags($rows);
    }
    
    $rows=my_set_estrategia_pager($rows,$my_limit);
       
   
    if ($num_rows) {
      /*$feed_url = url('estrategia_rss.xml', array('absolute' => TRUE));
      drupal_add_feed($feed_url, variable_get('site_name', 'Drupal') . ' ' . t('RSS'));*/
      
    /*$headers=array(t('Strategic challenge'),t('Valor reto'),t('Despliegue del reto'),t('Decision'),t('Valor decision'),
    t('Informacion necesaria'),t('Importance'),t('Esfuerzo'),t('Total punctuation'),t('Ranking'));*/
    /*$headers=array(t('Strategic challenge'),t('Valor reto'),t('Despliegue del reto'),t('ID'),t('Decision'),t('Valor decision'),
    t('Informacion necesaria'),t('Imp.'),t('IA'),t('Pt.'),t('R.'));*/
          /*$headers=array(t('Challenge'),t('Value'),t('SubChallenge'),t('Value'),t('Decision'),t('Value'),
    t('Information'),t('Value'),t('Accessibility'),t('Score'),t('Ranking'));*/
          $headers=estrategia_tabla_define_headers();
    $output .= theme('table',$headers,$rows);

    //print 'pager='.variable_get('default_nodes_main', 10).'<BR>';
    $output .= theme('pager', NULL, $my_limit);
    }
    else {
   
      $output = '<div id="first-time">' .t('There are no challenges'). '</div>';
    }
    //drupal_set_title(t('Strategic informations'));
    
    /*if(is_estrategia('tabla_puntuacion_total')){
        drupal_set_title(t('Orden Importancia'));      
    }else{
        drupal_set_title(t('Orden Jerárquico'));
    }*/
    drupal_set_title(t('Key Questions'));
    return $output;
  }

  function create_tabla($my_list){
    $tabla=array();
    $estrategia_desplegar_controller= new EstrategiaDesplegarController();
    //echo print_r($my_list,1);
    
    if(count($my_list)>0){
      $kont=0;
      
      foreach($my_list as $i=>$node){                    
        //print $node->nid.'<BR>'; exit;     
        $despliegue_list=$estrategia_desplegar_controller->estrategia_get_estrategia_despliegue_list($node->nid);

        if(count($despliegue_list)>0){
          
          foreach($despliegue_list as $k=>$despliegue){
            //echo print_r($row,1);
            //print $despliegue->nid.'<BR>';
            //print $despliegue->importancia_despliegue.'<BR>';
            //echo print_r($despliegue,1);exit();
            $decision_list=get_despliegue_decision_list($despliegue->nid);
            //print count($decision_list).'<BR>';
            
            if(count($decision_list)>0){
              
              foreach($decision_list as $a=>$decision){
                //print $decision->nid.'<BR>';
                $decision_row=node_load($decision->nid);
                $my_despliegue=node_load($decision_row->despliegue_nid);
                //echo print_r($decision_row,1);exit();
                $informacion_list=get_decision_informacion_list($decision->nid);
                //print count($informacion_list).'<BR>';
                
                if(count($informacion_list)>0){
                  
                  foreach($informacion_list as $b=>$informacion){
                    $informacion_row=node_load($informacion->nid);
                    $tabla[$kont]=create_row_tabla($node);
                    //if($k<1 && $a<1){
                      $tabla[$kont]->fecha_control_reto=estrategia_get_fecha_control($node);
                      $tabla[$kont]->reto=add_link_reto_title($node->title,$node->nid,$node->body);
                    //}
                    
                    $tabla[$kont]->valor_reto=$node->valor_reto;
                    
                    //if($a<1){
                    
                      $tabla[$kont]->fecha_control_despliegue=estrategia_get_fecha_control($my_despliegue);
                      $tabla[$kont]->despliegue_del_reto=add_link_despliegue_title($despliegue->title,$despliegue->nid,$my_despliegue->body);
                    //}
                    
                    $tabla[$kont]->decision=add_link_decision_title($decision->title,$decision->nid,$decision_row->body);
                    $tabla[$kont]->valor_decision=$decision_row->valor_decision;
                    //$tabla[$kont]->informacion_necesaria=l($informacion->title,'node/'.$informacion->nid);
                    $tabla[$kont]->informacion_necesaria=add_link_informacion_title($informacion->title, $informacion->nid, $informacion_row->body);
                    $tabla[$kont]->importancia=$informacion_row->importancia;
                    $tabla[$kont]->esfuerzo=$informacion_row->esfuerzo;
                    //$tabla[$kont]->puntuacion_total=$informacion_row->puntuacion_total;
                    $tabla[$kont]->numero_ranking=$informacion_row->numero_ranking;
                    $tabla[$kont]->grupo_seguimiento=get_informacion_grupo_seguimiento_link($informacion_row->grupo_seguimiento_nid,$informacion_row->decision_nid);
                    //print $informacion_row->grupo_seguimiento_nid.'----'.$informacion_row->decision_nid.'----'.$tabla[$kont]->grupo_seguimiento.'<BR>';
                    $tabla[$kont]->my_level=3;
                    $tabla[$kont]->importancia_despliegue=$my_despliegue->importancia_despliegue;
                    $tabla[$kont]->accesibilidad=$informacion_row->accesibilidad;
                    $kont++;
                  }
                }else{
                  $tabla[$kont]=create_row_tabla($node);
                  
                  //if($a<1){
                    $tabla[$kont]->fecha_control_reto=estrategia_get_fecha_control($node);
                    $tabla[$kont]->reto=add_link_reto_title($node->title,$node->nid,$node->body);
                  //}
                  
                  $tabla[$kont]->valor_reto=$node->valor_reto;                
                  
                  //if($a<1){
                    $tabla[$kont]->fecha_control_despliegue=estrategia_get_fecha_control($my_despliegue);
                    $tabla[$kont]->despliegue_del_reto=add_link_despliegue_title($despliegue->title,$despliegue->nid,$my_despliegue->body);
                  //}
                  
                  $tabla[$kont]->decision=add_link_decision_title($decision->title,$decision->nid,$decision_row->body);
                  $tabla[$kont]->valor_decision=$decision_row->valor_decision;
                  //$my_decision=node_load($decision->nid);
                  $tabla[$kont]->grupo_seguimiento=get_decision_grupo_seguimiento_link($decision_row->grupo_seguimiento_nid,$decision_row->despliegue_nid);
                  $tabla[$kont]->my_level=2;                                               
                  $tabla[$kont]->importancia_despliegue=$my_despliegue->importancia_despliegue;
                  $kont++;
                }
              }           
            }else{
              $tabla[$kont]=create_row_tabla($node);
              $my_despliegue=node_load($despliegue->nid);
              
              //if($k<1){
                $tabla[$kont]->reto=add_link_reto_title($node->title,$node->nid,$node->body);
              //}

              $tabla[$kont]->fecha_control_reto=estrategia_get_fecha_control($node);        
              $tabla[$kont]->valor_reto=$node->valor_reto;
              
              //if($a<1){
                $tabla[$kont]->fecha_control_despliegue=estrategia_get_fecha_control($my_despliegue);
                $tabla[$kont]->despliegue_del_reto=add_link_despliegue_title($despliegue->title,$despliegue->nid,$my_despliegue->body);
              //}

              //$my_despliegue=despliegue_load($despliegue);
              //echo print_r($my_despliegue,1);
              $tabla[$kont]->grupo_seguimiento=get_despliegue_grupo_seguimiento_link($my_despliegue->grupo_seguimiento_nid,$my_despliegue->estrategia_nid);
              //print $my_despliegue->estrategia_nid.'===='.$tabla[$kont]->grupo_seguimiento.'<BR>';
              $tabla[$kont]->my_level=1;
              $tabla[$kont]->importancia_despliegue=$my_despliegue->importancia_despliegue;
              $kont++;
            }
          }
        }else{
          $tabla[$kont]=create_row_tabla($node);
          $tabla[$kont]->reto=add_link_reto_title($node->title,$node->nid,$node->body);
          $tabla[$kont]->fecha_control_reto=estrategia_get_fecha_control($node);
          $tabla[$kont]->valor_reto=$node->valor_reto;
          $tabla[$kont]->grupo_seguimiento=get_grupo_seguimiento_link($node->grupo_seguimiento_nid);
          $tabla[$kont]->my_level=0;
          $tabla[$kont]->importancia_despliegue=0;
          $kont++;
        }
      }
    }
    return $tabla;
  }

  
  function estrategia_tabla_define_headers($is_tabla_csv_download=0){
    $headers=array();
    if($is_tabla_csv_download){
        $headers[]=t('Control Date');   
    }
    $headers[]=t('Challenge');    
    //intelsat-2015
    if($is_tabla_csv_download){
        $headers[]=t('Importance');
        $headers[]=t('Feasibility');
        $headers[]=t('Control Date');   
    }else{
        $headers[]=t('Value');        
    }
    //
    $headers[]=t('SubChallenge');
    $headers[]=t('Value');
    $headers[]=t('Decision');
    $headers[]=t('Value');
    $headers[]=t('Key Question');
    $headers[]=t('Importance');
    $headers[]=t('Accessibility');
    $headers[]=t('Score');
    $headers[]=t('Ranking');
    return $headers;
  }

  function estrategia_call_download_resumen_preguntas_clave_canales_csv($data_csv_array,$prefijo='resumen',$sep=";"){
      $file = date('Ymd-His');
      header("Content-type: text/csv");
      header("Content-Disposition: attachment; filename=$prefijo-$file.csv");
      header("Pragma: no-cache");
      header("Expires: 0");
      

      $output = fopen("php://output", "w");
      foreach ($data_csv_array as $val) {
          //fputcsv($output, $val,"\t");
          //fputcsv($output, $val,";");
          fputcsv($output, $val,$sep);
      }
      fclose($output);
      exit();
  }

  function estrategia_active_tabs_access(){
    //gemini-2014
//    if(is_estrategia()){
    //    
//        return hontza_grupos_active_access_tab('estrategia');
//    }
    return 1;
  }

  function hontza_grupos_active_access_tab($key){
    /*if(!hontza_grupos_is_activo_pestana($key)){
        drupal_set_message('The tab not is active in your group','error');
        drupal_access_denied();
        exit();
    }
    /*
    //intelsat-2016
    if(!hontza_grupos_mi_grupo_in_grupo()){
        drupal_access_denied();
        exit();
    }*/
  }

  function hontza_grupo_shared_active_tabs_access($is_return=0){
    if($this->hontza_is_servidor_red_alerta()){
    /*    if(module_exists('red_servidor')){
            if(red_servidor_is_grupo_shared()){
                //if(hontza_is_node_edit()){
                //if(!hontza_is_canal_edit()){
                    if($is_return){
                        return 0;
                    }else{
                        drupal_access_denied();
                        exit();
                    }
                //}
                //}    
            }
        }
    */
    }
    if($is_return){
        return 1;
    }
  }

  function hontza_is_servidor_red_alerta(){
//    if(hontza_is_sareko_id_red()){
//        if(red_is_servidor_central()){
            return 1;
//        }
//    }
  }

  //gemini
function my_get_empresa_nid(){
  global $user;
    $my_user=user_load($user->uid);
    //echo print_r($my_user,1);
    //
    $param=array('title'=>$my_user->profile_empresa,'type'=>'servicio');
    $node=node_load($param);
    if(isset($node->nid) && !empty($node->nid)){
      return $node->nid;
    }
    return '';
  }

  function get_where_estrategia_por_grupo($aurrizkia_in,$my_type){
    global $user;
    //AVISO::::por ahora que todos vean todo
    //return '';
    //
    /*if($user->uid==1){
        return '';
    }
    if(isset($user->og_groups) && !empty($user->og_groups)){
        $group_nid_array=array_keys($user->og_groups);
        return $aurrizkia.".grupo_nid IN(".implode(",",$group_nid_array).")";
    }*/
/*     $my_grupo=og_get_group_context();
      //
      $where=array();
      if(!empty($my_grupo) && isset($my_grupo->nid) && !empty($my_grupo->nid)){
            $aurrizkia=$aurrizkia_in;
            return "(".$aurrizkia.".grupo_seguimiento_nid = ".$my_grupo->nid.")";
      }
*/      
    return '';
  }



//PREGUNTAS CLAVE CANALES _ PREGUNTAS CANALES
  function boletin_report_no_group_selected_denied(){
    
    //call to undefined method error
    //$my_grupo=og_get_group_context();
    
    if(isset($my_grupo->nid) && !empty($my_grupo->nid)){
                
    }else{
        //drupal_access_denied();
        //exit();
        //print 'drupal_acces_denied()';exit();
    }    
  }

  function estrategia_create_menu_resumen_preguntas_clave_canales(){
    global $user;
    /*$html=array();
    $html[]='<div style="float:left"><b>'.t('Change view').':'.'&nbsp;</b></div>';
    $html[]='<div class="tab-wrapper clearfix primary-only" style="margin-top:0;">';
    $html[]='<div class="tabs primary" id="tabs-primary-alerta_user">';    
    $html[]='<ul>';
    $html[]='<li class="alerta_user_menu_li'.estrategia_resumen_preguntas_clave_get_class_active('resumen_preguntas_clave_canales').'" id="li_resumen_preguntas_clave_canales">';
    $html[]=l(t('Channel').' - '.t('Key Question'),'resumen_preguntas_clave_canales');
    $html[]='</li>';
    $html[]='<li class="alerta_user_menu_li'.estrategia_resumen_preguntas_clave_get_class_active('resumen_preguntas_clave_canales_fila_canal').'" id="li_resumen_preguntas_clave_canales_fila_canal">';
    $html[]=l(t('Key Question').' - '.t('Channel'),'resumen_preguntas_clave_canales_fila_canal');
    $html[]='</li>';    
    $html[]='</ul>';
    $html[]='</div></div>';*/
    $html=array();
    $html[]='<div class="tab-wrapper clearfix primary-only" style="margin:0;">';
    $html[]='<div class="tabs primary" id="tabs-primary-alerta_user" style="width:100%;">';    
    $html[]='<ul style="width:100%;">';
    $html[]='<li class="alerta_user_menu_li'.$this->estrategia_resumen_preguntas_clave_get_class_active('resumen_preguntas_clave_canales').'" id="li_resumen_preguntas_clave_canales" style="width:50%;">';

    /*$html[]=l(t('Channel').' - '.t('Key Question'),'resumen_preguntas_clave_canales',array('attributes'=>array('style'=>'width:100%;')));    
    $html[]='</li>';
    $html[]='<li class="alerta_user_menu_li'.estrategia_resumen_preguntas_clave_get_class_active('resumen_preguntas_clave_canales_fila_canal').'" id="li_resumen_preguntas_clave_canales_fila_canal" style="width:50%;border-right:none;">';
    

    $html[]=l(t('Key Question').' - '.t('Channel'),'resumen_preguntas_clave_canales_fila_canal',array('attributes'=>array('style'=>'width:100%;')));    
  */
    return implode('',$html);
  }

  function estrategia_create_menu_resumen_preguntas_clave_canales_para_ordenar(){
    global $user;
    /*$param0=arg(0);
    $html=array();
    $html[]='<div style="float:left"><b>'.t('Order of Key Questions').':'.'&nbsp;</b></div>';
    $html[]='<div class="tab-wrapper clearfix primary-only" style="margin-top:0;">';
    $html[]='<div class="tabs primary" id="tabs-primary-alerta_user">';
    $html[]='<ul>';
    $url_pregunta_canales='resumen_preguntas_clave_canales';
    if(!empty($param0) && $param0=='resumen_preguntas_clave_canales_fila_canal'){
        $url_pregunta_canales='resumen_preguntas_clave_canales_fila_canal';
    }
    $html[]='<li class="alerta_user_menu_li'.estrategia_resumen_preguntas_clave_get_class_active($url_pregunta_canales,'',1).'" id="li_resumen_preguntas_clave_canales">';
    $html[]=l(t('By Hierarchy'),$url_pregunta_canales);
    $html[]='</li>';
    $html[]='<li class="alerta_user_menu_li'.estrategia_resumen_preguntas_clave_get_class_active($url_pregunta_canales,'order_importance').'" id="li_resumen_preguntas_clave_canales_fila_canal">';
    $html[]=l(t('By Score'),$url_pregunta_canales.'/order_importance');
    $html[]='</li>';    
    $html[]='</ul>';
    $html[]='</div></div>';*/
    $html=array();
    $html[]='<div class="tab-wrapper clearfix primary-only" style="margin-top:0;">';
    $html[]='<div class="tabs primary" id="tabs-primary-alerta_user" style="width:100%;">';
    $html[]='<ul style="width:100%;">';
    $url_canales_pregunta='resumen_preguntas_clave_canales';
    //$a_style='width:100%;padding-left:0px;padding-right:0px;margin-top:0;';
    $a_style='width:100%;margin-top:0;';
    $active=$this->estrategia_resumen_preguntas_clave_get_class_active($url_canales_pregunta,'',1);
    $add_style='';
    
    /*
    $html[]='<li class="alerta_user_menu_li'.$active.'" id="li_resumen_preguntas_clave_canales" style="width:25%;">';
    $html[]=l(t('By Hierarchy'),$url_canales_pregunta,array('attributes'=>array('style'=>$a_style.$add_style)));
    $html[]='</li>';
    $active=estrategia_resumen_preguntas_clave_get_class_active($url_canales_pregunta,'order_importance');
   

    $html[]='<li class="alerta_user_menu_li'.$active.'" id="li_resumen_preguntas_clave_canales_fila_canal" style="width:25%;border-right:none;">';
    $html[]=l(t('By Score'),$url_canales_pregunta.'/order_importance',array('attributes'=>array('style'=>$a_style.$add_style)));
    $html[]='</li>';    
    //
    $url_pregunta_canales='resumen_preguntas_clave_canales_fila_canal';
    $active=estrategia_resumen_preguntas_clave_get_class_active($url_pregunta_canales,'',1);
    $add_style='';
    /*if(!empty($active)){
        $add_style='padding-top:0px;';
    }*/
    /*    
    $html[]='<li class="alerta_user_menu_li'.$active.'" id="li_resumen_preguntas_clave_canales" style="width:25%;">';
    $html[]=l(t('By Hierarchy'),$url_pregunta_canales,array('attributes'=>array('style'=>$a_style.$add_style)));
    $html[]='</li>';
    */
    $active=$this->estrategia_resumen_preguntas_clave_get_class_active($url_pregunta_canales,'order_importance');
    $add_style='';
    /*if(!empty($active)){
        $add_style='padding-top:0px;';
    }*/
    $html[]='<li class="alerta_user_menu_li'.$active.'" id="li_resumen_preguntas_clave_canales_fila_canal" style="width:25%;border-right:none;">';
   // $html[]=l(t('By Score'),$url_pregunta_canales.'/order_importance',array('attributes'=>array('style'=>$a_style.$add_style)));

    return implode('',$html);
  }

  function estrategia_resumen_preguntas_clave_canales_volver_link(){
    $volver='estrategias/arbol_estrategico';
    if(isset($_REQUEST['destination']) && !empty($_REQUEST['destination'])){
        $volver=$_REQUEST['destination'];
    }
    //return l(t('Return'),$volver,array('attributes'=>array('class'=>'back_resumen')));
    return '';
  }

  function estrategia_resumen_preguntas_clave_canales_mensaje_de_los_navegadores(){
    $html=array();
    /*$html[]='<p><i><b>'.t("This table is optimised to Firefox 26.0.").'</b></i></p>';
    $html[]='<p><i><b>'.t("If your browser doesn't support vertical displaying of column headings please download the csv file.").'</b></i></p>';*/
    $html[]='<p><i><b>'.t('If you have problems viewing this table, try another navigator (Firefox recommended) or download csv file').'.</b></i></p>';
    return implode('',$html);
  }

  function estrategia_resumen_preguntas_clave_canales_html($vertical=0){
    $html=array();
    $html[]='<table>';
    $rows=$this->estrategia_informacion_get_array();

    if(!empty($rows)){
        $canales=estrategia_get_canales_del_grupo();        
        if(!empty($canales)){
            $html[]=estrategia_resumen_preguntas_clave_canales_vertical($canales,$vertical);
            foreach($rows as $i=>$informacion){            
                $html[]='<tr>';
                $s=estrategia_set_title_una_linea_de_alto_fila($informacion,$my_title,$my_value);
                /*$html[]='<td style="white-space:nowrap;" title="'.$my_title.'">';                
                $html[]=$my_value;*/
                $html[]='<td style="white-space:nowrap;">';                
                $html[]=$s;
                $html[]='</td>';
                if(!empty($canales)){
                    foreach($canales as $i=>$canal){
                        $responde_array=informacion_get_canal_informacion_array($canal->nid,$informacion->nid);
                        $ekis='';
                        if(count($responde_array)>0){
                            if(empty($vertical)){
                                $ekis='<abbr title="'.$canal->title.'">X</abbr>';
                            }else if($vertical==1){
                                $ekis='<abbr title="'.$informacion->title.'">X</abbr>';
                            }else if($vertical==2){
                                $ekis='<abbr title="'.$informacion->title.'<->'.$canal->title.'">X</abbr>';
                            }
                        }
                        $html[]=estrategia_resumen_preguntas_clave_canales_set_td_ekis($ekis,$informacion->title.'<->'.$canal->title);    
                    }
                }
                $html[]='</tr>';
            }
        }    
    }
    $html[]='</table>';
    //return implode('',$html);
    return '1';
  }

  function estrategia_resumen_preguntas_clave_canales_vertical($canales,$vertical){
    $html=array();
    $html[]='<tr>';
    $html[]='<th></th>';
    if(!empty($canales)){
        foreach($canales as $i=>$canal){
            if(empty($vertical)){
                $html[]='<th><abbr title="'.$canal->title.'">'.($i+1).'</abbr></th>';
            }else if($vertical==1){
                $html[]='<th>'.$canal->title.'</th>';
            }else if($vertical==2){
                //$html[]='<th><div class="vertical-text"><div class="vertical-text__inner">'.estrategia_set_title_una_linea_de_alto_columna($canal->title).'</th>';
                $html[]='<th><div class="vertical-text"><div class="vertical-text__inner">'.estrategia_set_title_una_linea_de_alto_columna($canal->title).'('.$canal->valor_estrategico.')</th>';
            }    
        }
    }
    $html[]='</tr>';
    return implode('',$html);
  }

  function estrategia_resumen_preguntas_clave_get_class_active($konp,$konp2='',$is_view_empty=0){

  /*

    $ok=0;
    $param0=arg(0);
    if($param0==$konp){
        if(empty($konp2)){
            $param1=arg(1);
            if($is_view_empty){
                if(empty($param1)){
                    $ok=1;
                }
            }else{
                $ok=1;
            }    
        }else{
            $param1=arg(1);
            if(!empty($param1) && $param1==$konp2){
                 $ok=1;
            }
        }
    }
    if($ok){
        return ' active';
    }

  */
    return '';
  }



// IMPORTAR

  function estrategia_importar_form(){    
    $form = array();
    
//    $this->boletin_report_no_group_selected_denied();
    $form['browser'] = array(
      '#type' => 'fieldset',
      '#title' => t('Browser Upload'),
      '#collapsible' => TRUE,
      '#description' => t("Upload a CSV file."),
    );
    //intelsat-2016
    $form['browser']['import_type'] = array(
      '#type' => 'select',
      '#title' => t('Type'),
//      '#options'=>$this->estrategia_inc_get_import_type_options(),
      '#default_value'=>2,  
    );
    //$file_size = t('Maximum file size: !size MB.', array('!size' => file_upload_max_size()));
    $file_size ='';
    $form['browser']['upload_file'] = array(
      '#type' => 'file',
      '#title' => t('CSV File'),
      '#size' => 40,
      '#description' => t('Select the CSV file to be upload.').' '.$file_size,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Upload CSV File'),
    );

    $form['#attributes']['enctype'] = "multipart/form-data";
    return $form;
  }

  //intelsat-2016
  function estrategia_inc_get_import_type_options(){
    $result=array();
    $result[1]=t('Add');
    $result[2]=t('Replace');
    return $result;
  }

  function estrategia_get_canales_del_grupo(){
    //$informacion_ordenada=informacion_get_array(0,1);    
    $result=array();    
    //
    $where=array();
    $where[]='1';
    $where[]='n.type IN("canal_de_supercanal","canal_de_yql")';
    //$my_grupo=og_get_group_context();
    if(isset($my_grupo->nid) && !empty($my_grupo->nid)){
        $where[]='oa.group_nid='.$my_grupo->nid;
    }else{
        return $result;
    }
    //
    $sql='SELECT n.* 
    FROM {node} n
    LEFT JOIN {og_ancestry} oa ON n.nid=oa.nid
    WHERE '.implode(' AND ',$where).'
    GROUP BY n.nid 
    ORDER BY n.title ASC';
    $res=db_query($sql);
    $kont=0;
    while($row=db_fetch_object($res)){
        $node=node_load($row->nid);
        $result[$kont]=$node;
        $result[$kont]->valor_estrategico=estrategia_get_canal_valor_estrategico($node);
        $valor_array=estrategia_get_canal_valor_estrategico_array($node,$informacion_ordenada);
        $result[$kont]->valor_estrategico_array=array_values($valor_array);
        $result[$kont]->valor_order_array=array_keys($valor_array);
        //echo print_r($result[$kont]->valor_estrategico_array,1);
        $kont++;
    }
    if(count($result)>0){
        //$result=array_ordenatu($result,'valor_estrategico','desc',1);
        $result=estrategia_ordenar_canales_by_valor_estrategico_array($result);
    }
    /*echo print_r($result,1);
    exit();*/
    return $result;
  }
}//class EstrategiaController