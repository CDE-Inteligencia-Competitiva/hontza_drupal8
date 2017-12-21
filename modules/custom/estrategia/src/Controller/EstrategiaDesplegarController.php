<?php
namespace Drupal\estrategia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\vigilancia\Controller\VigilanciaController;

class EstrategiaDesplegarController extends ControllerBase {

  /**
   * Constructs a page with descriptive content.
   * Our router maps this method to the path 'examples/page-example'.
   */

function estrategia_create_arbol($my_list,$is_solo_preguntas_clave=0){
    $arbol=array();
    $my_padding=50;

    //hontza5
    //$konp_array=get_idea_responde_konp_array();

    $my_id_array=array();
    $kont_info=0;
    
    if(count($my_list)>0){
        $kont=0;
        
        foreach($my_list as $i=>$node){
             //intelsat-2017-error-estrategia-nid-empty
             //se han descomentado estas dos lineas
             $node_title=$node->label();
             $node_nid=$node->id();
             $node_body='';

            //hontza5
            /*if(!is_reto_del_grupo($node->grupo_seguimiento_nid)){                        
            }*/

            $my_img='';
            //if(!is_idea()){
                $my_img=$this->get_estrategia_simbolo_img();
            //}

            $div_title='';
            //$div_title=estrategia_get_arbol_div_title($node->body);
            
            $estrategia_class='';
            //$estrategia_class=red_copiar_get_title_imported_class($node);
            $compartir_link='';
            //$compartir_link=red_copiar_get_compartir_estrategia_link($node);
            $arbol[$kont][0]='<div'.$div_title.$estrategia_class.'>'.$compartir_link.$my_img.$this->estrategia_add_link_reto_title($node_title,$node_nid,$node_body).'</div>';

            if($this->estrategia_is_reto_al_que_responde_pantalla()){
                $my_id='estrategia_'.$node->nid.'_0_0_0';
                $my_id_array[]=$my_id;
                $checked='';
                $arbol[$kont][1]='';
                
                if(!estrategia_inc_is_reto_responde_formulario()){    
                    if(in_array($my_id,$konp_array)){
                        $checked=' checked="checked"';
                    }  

                    $arbol[$kont][1]='<input type="checkbox" id="'.$my_id.'" name="estrategia['.$node->nid.']" value="1"'.$checked.'/>';
                }
            }else{
                if($this->estrategia_is_estrategia('arbol')){
                    $arbol[$kont][1]=get_grupo_seguimiento_link($node->grupo_seguimiento_nid);
                }else{                  
                    //$borrar_img=my_get_icono_action("delete",t("Delete Challenge"));
                    $arbol[$kont][1]='';
                    $estrategia_row=$this->estrategia_get_estrategia_row($node->id(),$node->getRevisionId());
                    $arbol[$kont][1]=$this->estrategia_get_status_color($estrategia_row);
                    
                    //$arbol[$kont]['node']=$node;
                    $arbol[$kont][2]='';
                    
                    if($this->estrategia_is_admin_content()){
                        $arbol[$kont][2]=l($borrar_img,"node/".$node->nid."/delete",array('html'=>true,'query'=>drupal_get_destination()));
                        $arbol[$kont][2].='&nbsp;'.l(my_get_icono_action("edit",t("Edit Challenge")),"node/".$node->nid."/edit",array('html'=>true,'query'=>drupal_get_destination()));
                        $arbol[$kont][2].='&nbsp;'.l(my_get_icono_action("add",t("Add SubChallenge")),'node/add/despliegue/'.$node->nid,array('html'=>true));
                    }
                    $arbol[$kont][2]=$this->estrategia_add_div_actions($arbol[$kont][2]);  
                }
            }
            $kont++;

            //$despliegue_list=$this->estrategia_get_estrategia_despliegue_list($node_nid);
            //$despliegue_list=$this->estrategia_get_estrategia_despliegue_list($node_nid=$node->id());
            $despliegue_list=$this->estrategia_get_estrategia_despliegue_list($node_nid);

            /*
                        print $despliegue_list; 
                        print count($despliegue_list); exit;
            */

            if(count($despliegue_list)>0){
                foreach($despliegue_list as $k=>$despliegue){
                    //$despliegue_nid=$despliegue->id();

                    $my_img='';
                    //if(!is_idea()){
                        $my_img=$this->get_despliegue_simbolo_img();
                    //}
                    $my_despliegue=node_load($despliegue->nid);
                    $div_title=$this->estrategia_get_arbol_div_title($my_despliegue->body);
                    $arbol[$kont][0]='<div '.$div_title.'style="padding-left:'.$my_padding.'px">'.$my_img.$this->add_link_despliegue_title($despliegue->title,$despliegue->nid,$my_despliegue->body).'</div>';
                    //$arbol[$kont][1]='operaciones';

                    if($this->estrategia_is_reto_al_que_responde_pantalla()){
                        $my_id='estrategia_'.$node->nid.'_'.$despliegue->nid.'_0_0';
                        //print $my_id.'<BR>';
                        $my_id_array[]=$my_id;
                        $checked='';
                        //if(strcmp($my_id,$konp)==0){
                            $arbol[$kont][1]='';
                            //if(!hontza_is_canal_formulario() && !red_is_item_formulario()){
                            //intelsat-2015
                            //if(!hontza_is_canal_formulario() && !red_is_item_formulario() && !red_is_noticia_de_usuario_formulario()){
                                if(!estrategia_inc_is_reto_responde_formulario()){  
                                    if(in_array($my_id,$konp_array)){
                                        $checked=' checked="checked"';
                                    }    
                                $arbol[$kont][1]='<input type="checkbox" id="'.$my_id.'" name="estrategia['.$node->nid.'_'.$despliegue->nid.']" value="1"'.$checked.'/>';
                            }
                            //$arbol[$kont]=array_reverse($arbol[$kont]);
                        
                        }else{
                            //$arbol[$kont][1]=$despliegue->peso;
                        
                            if($this->estrategia_is_estrategia('arbol')){
                                $arbol[$kont][1]=get_despliegue_grupo_seguimiento_link($despliegue->grupo_seguimiento_nid,$despliegue->estrategia_nid);
                            }else{
                                $arbol[$kont][1]=$this->estrategia_get_status_color($my_despliegue);
                                //$arbol[$kont]['node']=$my_despliegue;                          
                                $arbol[$kont]['node']="";
                                $arbol[$kont][2]='';

                                if($this->estrategia_is_admin_content()){
                                    $arbol[$kont][2]=l(my_get_icono_action("delete",t("Delete SubChallenge")),"node/".$despliegue->nid."/delete",array('html'=>true,'query'=>drupal_get_destination()));
                                    $arbol[$kont][2].='&nbsp;'.l(my_get_icono_action("edit",t("Edit Subchallenge")),"node/".$despliegue->nid."/edit",array('html'=>true,'query'=>drupal_get_destination()));
                                    $arbol[$kont][2].='&nbsp;'.l(my_get_icono_action("add",t("Add Decision")),'node/add/decision/'.$despliegue->nid,array('html'=>true));
                                }
                                $arbol[$kont][2]=$this->estrategia_add_div_actions($arbol[$kont][2]);
                            }
                        }
                        
                        $kont++;

                        $decision_list=$this->estrategia_get_despliegue_decision_list($despliegue->nid);

                        //print $decision_list;
                        //print ': '; 
                        //print count($decision_list).'<br>'; 

                        //exit;

                        //prueba
                        //$decision_list=8;

                        if(count($decision_list)>0){
                            foreach($decision_list as $a=>$decision){
                                $my_img='';
                                $my_img=$this->get_decision_simbolo_img();
                                
                                //if(!is_idea()){
                                    //$my_img=get_decision_simbolo_img();
                                    $my_decision=node_load($decision->nid);
                                //}

                                    //print $decision->title; exit;

                                $div_title=$this->estrategia_get_arbol_div_title($my_decision->body);                                
                                $arbol[$kont][0]='<div '.$div_title.'style="padding-left:'.($my_padding*2).'px">'.$my_img.$this->add_link_decision_title($decision->title,$decision->nid,$my_decision->body).'</div>';
                                //$arbol[$kont][1]='operaciones';

                                if($this->estrategia_is_reto_al_que_responde_pantalla()){
                                    //$arbol[$kont][1]='';
                                    $my_id='estrategia_'.$node->nid.'_'.$despliegue->nid.'_'.$decision->nid.'_0';
                                    //print $my_id.'<BR>'; exit;
                                    $my_id_array[]=$my_id;
                                    $checked='';

                                    //if(strcmp($my_id,$konp)==0){
                                        $arbol[$kont][1]='';

                                        //if(!hontza_is_canal_formulario() && !red_is_item_formulario()){
                                            //if(!hontza_is_canal_formulario() && !red_is_item_formulario() && !red_is_noticia_de_usuario_formulario()){
                                                
                                                if(!estrategia_inc_is_reto_responde_formulario()){  
                                                    
                                                    if(in_array($my_id,$konp_array)){
                                                        $checked=' checked="checked"';
                                                    }                                               
                                                $arbol[$kont][1]='<input type="checkbox" id="'.$my_id.'" name="estrategia['.$node->nid.'_'.$despliegue->nid.'_'.$decision->nid.']" value="1"'.$checked.'/>';
                                            }    
                                             
                                            //intelsat-2015
                                            //$arbol[$kont]=array_reverse($arbol[$kont]);   
                                        }else{
                                            //$arbol[$kont][1]=$decision->peso;
                                            
                                            if($this->estrategia_is_estrategia('arbol')){
                                                $arbol[$kont][1]=get_decision_grupo_seguimiento_link($decision->grupo_seguimiento_nid,$decision->despliegue_nid);
                                            }else{
                                                $arbol[$kont][1]=$this->estrategia_get_status_color($my_decision);
                                                //$arbol[$kont]['node']=$my_decision;
                                                
                                                $arbol[$kont]['node']='';
                                                $arbol[$kont][2]='';
                                                
                                                if($this->estrategia_is_admin_content()){
                                                    $arbol[$kont][2]=l(my_get_icono_action("delete",t("Delete Decision")),"node/".$decision->nid."/delete",array('html'=>true,'query'=>drupal_get_destination()));
                                                    $arbol[$kont][2].='&nbsp;'.l(my_get_icono_action("edit",t("Edit Decision")),"node/".$decision->nid."/edit",array('html'=>true,'query'=>drupal_get_destination()));
                                                    $arbol[$kont][2].='&nbsp;'.l(my_get_icono_action("add",t("Add Key Question")),'node/add/informacion/'.$decision->nid,array('html'=>true));
                                                }    
                                                $arbol[$kont][2]=$this->estrategia_add_div_actions($arbol[$kont][2]);
                                            }
                                        }
                                        $kont++;
                                        //if($this->estrategia_is_estrategia('arbol')){
                                        //}
                                        //if(is_add_edit_respuesta()){
                                        //}

                                        $informacion_list=$this->estrategia_get_decision_informacion_list($decision->nid);

                        /*
                        print $informacion_list;
                        print ': '; 
                        print count($informacion_list).'<br>'; 
                        */
                        //exit;

                        //prueba
                        //$decision_list=8;
                                        
                                        if(count($informacion_list)>0){
                                            foreach($informacion_list as $b=>$informacion){
                                                $my_img='';
                                                //if(!is_idea()){
                                                    $my_img=$this->get_informacion_simbolo_img();
                                                    $my_informacion=node_load($informacion->nid);
                                                //}
                                                
                                                $div_title=$this->estrategia_get_arbol_div_title($my_informacion->body);
                                                
                                                $arbol[$kont][0]='<div '.$div_title.'style="padding-left:'.($my_padding*3).'px">'.$my_img.$this->add_link_informacion_title($informacion->title,$informacion->nid,$my_informacion->body).'</div>';
                                                //$arbol[$kont][1]='operaciones';
                                                
                                                if($this->estrategia_is_reto_al_que_responde_pantalla()){
                                                    //$arbol[$kont][1]='';
                                                    $my_id='estrategia_'.$node->nid.'_'.$despliegue->nid.'_'.$decision->nid.'_'.$informacion->nid;
                                                    //print $my_id.'<BR>';
                                                    $my_id_array[]=$my_id;
                                                    //print $my_id.'===='.$konp.'<BR>';
                                                    $checked='';
                                                    $arbol[$kont][1]='';
                                                    //if(strcmp($my_id,$konp)==0){

                                                        if(in_array($my_id,$konp_array)){
                                                            $checked=' checked="checked"';
                                                        }
                                                    
                                                    $arbol[$kont][1]='<input type="checkbox" id="'.$my_id.'" name="estrategia['.$node->nid.'_'.$despliegue->nid.'_'.$decision->nid.'_'.$informacion->nid.']" value="1"'.$checked.'/>';
                                                    
                                                    //intelsat-2015
                                                    //$arbol[$kont]=array_reverse($arbol[$kont]);                                                    
                                                }else{
                                                    //$arbol[$kont][1]=$informacion->peso;
                                                    
                                                    if($this->estrategia_is_estrategia('arbol')){
                                                        $arbol[$kont][1]=get_informacion_grupo_seguimiento_link($informacion->grupo_seguimiento_nid,$informacion->decision_nid);
                                                    }else{
                                                        $arbol[$kont][1]=$this->estrategia_get_status_color($my_informacion);
                                                        //$arbol[$kont]['node']=$my_informacion;
                                                        $arbol[$kont]['node']='';
                                                        $arbol[$kont][2]='';
                                                        
                                                        if($this->estrategia_is_admin_content()){
                                                            $arbol[$kont][2]=l(my_get_icono_action("delete",t("Delete Key Question")),"node/".$informacion->nid."/delete",array('html'=>true,'query'=>drupal_get_destination()));
                                                            $arbol[$kont][2].='&nbsp;'.l(my_get_icono_action("edit",t('Edit Key Question')),"node/".$informacion->nid."/edit",array('html'=>true,'query'=>drupal_get_destination()));
                                                        }    
                                                        $arbol[$kont][2]=$this->estrategia_add_div_actions($arbol[$kont][2]);
                                                    }
                                                }
                                                $kont++;
                                                $kont_info++;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
   
                //echo print_r($my_id_array,1);
                /*if($estrategia_convertir_data_row){
                    $arbol=estrategia_convertir_data_row($arbol);
                }
                if($is_solo_preguntas_clave){
                    $arbol=estrategia_set_preguntas_clave_arbol($arbol);    
                }
                //intelsat-2016
                $arbol=estrategia_inc_get_score($arbol);
                */
                //echo print_r($arbol,1); exit;

                $arbol=$this->estrategia_prepare_arbol($arbol);

                return $arbol;
            }

private function estrategia_prepare_arbol($arbol){
    $result=array();
    if(!empty($arbol)){
        foreach ($arbol as $i => $value_array) {
            $result[$i][0]=$value_array[0];
        }
    }
    return $result;
}

private function estrategia_add_link_reto_title($title_in,$estrategia_nid,$description=''){
    //return $title;
    //$img=get_add_img();
    //print $img.'<BR>';
    //return $title.'&nbsp;'.l($img,'node/add/despliegue/'.$estrategia_nid,array('html'=>true));
    //return l($title,'node/add/despliegue/'.$estrategia_nid);
    //return l($title,'node/'.$estrategia_nid,array('attributes'=>array('title'=>strip_tags($description))));
        $title=$this->estrategia_set_title_max_len($title_in);
        //gemini-2014
        //if(estrategia_is_arbol_sin_link()){
            return $title;
       //}
        //hontza5
        //return l($title,'node/'.$estrategia_nid,array('attributes'=>array('title'=>strip_tags($description)),'query'=>drupal_get_destination()));
}

//gemini-2014
function estrategia_get_arbol_div_title($description){    
    if($this->estrategia_is_arbol_sin_link()){
        $title=strip_tags($description);
        if(!empty($title)){
            return ' title="'.$title.'"';
        }
    }
    return '';
}

//gemini
function estrategia_get_despliegue_decision_list($despliegue_nid){
    $result=array();
    $where=array();
    $where[]="1";
    $where[]='d.despliegue_nid='.$despliegue_nid;
    
    $sql="SELECT n.*, node_field_data.title, d.peso,d.despliegue_nid 
    FROM {node} n 
    LEFT JOIN {node_field_data} ON n.vid=node_field_data.vid
    LEFT JOIN {decision} d ON n.nid=d.nid
    WHERE ".implode(" AND ",$where);
    
    //$sql="SELECT n.*,d.peso,d.despliegue_nid,d.grupo_seguimiento_nid
    //intelsat-2016
    //$order_by=" ORDER BY d.peso ASC,n.created ASC";
    //intelsat-2016
    //$order_by=decision_inc_get_order_by($order_by);
    $sql.=$order_by;

    //print $sql.'<br>';
    //exit();
    $res=db_query($sql);
    while($row=$res->fetchObject()){
        $result[]=$row;
    }
    return $result;
}

/*
function estrategia_get_despliegue_decision_list($despliegue_nid){
    $result=array();
    $where=array();
    $where[]="1";
    $where[]='d.despliegue_nid='.$despliegue_nid;
    //$sql="SELECT n.*,d.peso,d.despliegue_nid,d.grupo_seguimiento_nid
    //intelsat-2016
//        $order_by=" ORDER BY d.peso ASC,n.created ASC";
        //intelsat-2016
//        $order_by=decision_inc_get_order_by($order_by);
        $sql="SELECT n.*,d.peso,d.despliegue_nid 
        FROM {node} n LEFT JOIN {decision} d ON n.nid=d.nid
    WHERE ".implode(" AND ",$where).$order_by;
    //print $sql;exit();
    $res=db_query($sql);
    while($row=$res->fetchObject()){
        $result[]=$row;
    }
    return $result;
}
*/

//gemini
function add_link_despliegue_title($title_in,$despliegue_nid,$description=''){
    //return $title;
    //$img=get_add_img();
    //print $img.'<BR>';
    //return $title.'&nbsp;'.l($img,'node/add/despliegue/'.$estrategia_nid,array('html'=>true));
    //return l($title,'node/add/decision/'.$despliegue_nid);
    //return l($title,'node/'.$despliegue_nid,array('attributes'=>array('title'=>strip_tags($description))));
        $title=$this->estrategia_set_title_max_len($title_in);
        //gemini-2014
        if($this->estrategia_is_arbol_sin_link()){
            return $title;
        }
        //
        //return l($title,'node/'.$despliegue_nid,array('attributes'=>array('title'=>strip_tags($description)),'query'=>drupal_get_destination()));
        //return 0;
        return $title;
}

//gemini
function add_link_decision_title($title_in,$decision_nid,$decision=''){
    //return $title;
    //$img=get_add_img();
    //print $img.'<BR>';
    //return $title.'&nbsp;'.l($img,'node/add/despliegue/'.$estrategia_nid,array('html'=>true));
    //return l($title,'node/add/decision/'.$despliegue_nid);
    //return l($title,'node/'.$despliegue_nid,array('attributes'=>array('title'=>strip_tags($description))));
        $title=$this->estrategia_set_title_max_len($title_in);
        //gemini-2014
        if($this->estrategia_is_arbol_sin_link()){
            return $title;
        }
        //
        //return l($title,'node/'.$despliegue_nid,array('attributes'=>array('title'=>strip_tags($description)),'query'=>drupal_get_destination()));
        return $title;
}

function add_link_informacion_title($title_in,$informacion_nid,$description=''){
    //return l($title,'node/'.$informacion_nid,array('attributes'=>array('title'=>strip_tags($description))));
    $title=$this->estrategia_set_title_max_len($title_in);
    //gemini-2014
    if($this->estrategia_is_arbol_sin_link()){
        return $title;
    }
    //
    //return l($title,'node/'.$informacion_nid,array('attributes'=>array('title'=>strip_tags($description)),'query'=>drupal_get_destination()));
    return $title;
}
 
//gemini-2014
function estrategia_is_arbol_sin_link(){
    /*
    if(is_crear_canal_de_supercanal()){
        return 1;        
    }
    if(is_crear_canal_filtro_rss(0,0)){
        return 1;
    }
    if(is_node_add() || hontza_is_node_edit()){
        $node=my_get_node();
        if(isset($node->type) && !empty($node->type) && in_array($node->type,array('canal_de_supercanal','canal_de_yql'))){
            return 1;
        }
    }
    */
    return 0;
}
function get_despliegue_simbolo_img($is_taula_header=0,$title_in=''){
    //intelsat-2016
    global $base_url;
    $html=array();
    //intelsat-2015
    if(empty($title_in)){
        $title=t('Subchallenges');
    }else{
        $title=$title_in;
    }
    //
    //gemini-2014
    $style=$this->estrategia_get_simbolo_style($is_taula_header);    
    //
    $html[]='<img '.$style.' src="'.$base_url.'/sites/default/files/my_images/despliegue.png" alt="'.$title.'" title="'.$title.'"/>';
    if(!$is_taula_header){
        $html[]='&nbsp;';
    }
    return implode('',$html);
}

function get_informacion_simbolo_img($is_taula_header=0,$title=''){
    //intelsat-2016
    global $base_url;
    $html=array();
    //intelsat-2015
    if(empty($title_in)){
        $title=$title_in;
    }else{
        $title=t('Key Questions');
    }
    //
    //gemini-2014
    $style=$this->estrategia_get_simbolo_style($is_taula_header); 
    //
    $html[]='<img '.$style.' src="'.$base_url.'/sites/default/files/my_images/informacion.png" alt="'.$title.'" title="'.$title.'"/>';
    if(!$is_taula_header){
        $html[]='&nbsp;';
    }
    return implode('',$html);
}

function get_decision_simbolo_img($is_taula_header=0,$title_in=''){
    //intelsat-2016
    global $base_url;
    $html=array();
    //intelsat-2015
    if(empty($title_in)){
        $title=$title_in;
    }else{
        $title=t('Decisions');
    }
    //
    //gemini-2014
    $style=$this->estrategia_get_simbolo_style($is_taula_header); 
    //
    $html[]='<img '.$style.' src="'.$base_url.'/sites/default/files/my_images/decision.png" alt="'.$title.'" title="'.$title.'"/>';
    if(!$is_taula_header){
        $html[]='&nbsp;';
    }
    return implode('',$html);
}

//gemini-2014
function estrategia_get_simbolo_style($is_taula_header=0){
    $style='';
    if($is_taula_header){
        //intelsat-2016
        //$padding_right=8;
        $padding_right=5;
        $style=' style="vertical-align:middle;padding-right:'.$padding_right.'px;"';
    }
    return $style;
}

private function estrategia_set_title_max_len($title_in){
    $result=$title_in;
    $my_array=explode(" ",$result);
    if(count($my_array)>1){
        return $result;
    }
    //
    $len=strlen($result);
    $max_len=44;
    if($len>$max_len){
        $result=substr($result,0,$max_len).' ...';
    }
    return $result;
}

private function estrategia_is_reto_al_que_responde_pantalla(){

    return 0;

}

private function estrategia_is_estrategia($comp=''){

    return 0;

}

function estrategia_is_admin_content(){
    return $this->is_administrador_grupo(1);
}

function is_administrador_grupo($modo_estrategia=0,$grupo_nid_in=''){
  $grupo_nid=$grupo_nid_in;
  if(empty($grupo_nid)){
    $my_grupo=$this->og_get_group_context();
    //  
    if(!empty($my_grupo) && isset($my_grupo->nid) && !empty($my_grupo->nid)){
          $grupo_nid=$my_grupo->nid;
    }
    //intelsat-2015
    if(empty($grupo_nid)){
        if(isset($_REQUEST['my_grupo_nid']) && !empty($_REQUEST['my_grupo_nid'])){
            $grupo_nid=$_REQUEST['my_grupo_nid'];
        }
    }
  }  
  if($modo_estrategia){
    //return is_permiso_gestion_boletin_grupo($grupo_nid,1,'',1);
    return '';
  }else{  
    //return is_permiso_gestion_boletin_grupo($grupo_nid);  
    return '';
  }  
}

function estrategia_get_status_color($node,$field='fecha_cumplimiento'){
        //echo print_r($node,1);exit;
    if(isset($node->$field) && !empty($node->$field)){
        //$fecha_cumplimiento_array=array_values($node->$field);
        $fecha=implode('-',$fecha_cumplimiento_array);
        //return estrategia_get_status_fecha_control_color($fecha);
        return '';
        //return estrategia_get_status_fecha_control_color_html($fecha,$node->no_control_date);
    }
    return '';
}
function og_get_group_context() {
  return $this->og_set_group_context();
}

function og_set_group_context($node = NULL, $clear = FALSE) {
  static $stored_group_node;

  if ($clear) {
    $stored_group_node = NULL;
  }

  if (!empty($node) && og_is_group_type($node->type)) {
    $stored_group_node = $node;
  }
  return !empty($stored_group_node) ? $stored_group_node : NULL;
}

function og_is_group_type($type) {
  return variable_get('og_content_type_usage_'. $type, 'omitted') == 'group';
}

function estrategia_add_div_actions($content){
    return $this->add_div_actions($content,1,'estrategia_div_actions');
}

function add_div_actions($content,$with_class=1,$class='',$type=''){
    if(!in_array($type,array('boletin_grupo','boletin_report'))){
        //if(hontza_solr_search_is_usuario_lector()){
            return '';
        //}
    }    
    $html=array();
    if($with_class){
        if(empty($class)){
            $html[]='<div class="my_div_actions">';
        }else{
            $html[]='<div class="'.$class.'">';
        }    
    }else{
        $html[]='<div>';
    }
    $html[]=$content;
    $html[]='</div>';
    return implode('',$html);
}

    //gemini
  function estrategia_get_estrategia_despliegue_list($estrategia_nid,$with_grupo=1){
    $result=array();

        $where=array();
    
        $where[]="1";
            //echo print_r($estrategia_nid,1); exit;
        $where[]="d.estrategia_nid=".$estrategia_nid;
          


        if(($this->estrategia_is_reto_al_que_responde_pantalla() || $this->estrategia_is_dashboard()) && $with_grupo){
          $my_grupo=og_get_group_context();
          
          if(!empty($my_grupo) && isset($my_grupo->nid) && !empty($my_grupo->nid)){
            $where[]="(d.grupo_seguimiento_nid=".$my_grupo->nid." OR (d.grupo_seguimiento_nid=0 AND e.grupo_seguimiento_nid=".$my_grupo->nid."))";
          }
        }
        
        $sql="SELECT n.*, node_field_data.title,d.peso,d.estrategia_nid,d.grupo_seguimiento_nid 
        FROM {node} n 
        LEFT JOIN {node_field_data} ON n.vid=node_field_data.vid 
        LEFT JOIN {despliegue} d ON n.nid=d.nid
              LEFT JOIN {estrategia} e ON d.estrategia_nid=e.nid
        WHERE ".implode(" AND ",$where);
        
        //intelsat-2016
        //$sql.=" ORDER BY d.peso ASC,n.created ASC";
        //$order_by=" ORDER BY d.peso ASC,node_field_data.created,n.nid ASC";
        //$order_by=despliegue_inc_get_order_by($order_by);
        $sql.=$order_by;

        //print $sql;exit();
        $res=db_query($sql);
        while($row=$res->fetchObject()){
          $result[]=$row;
        }
        


    return $result;
  }
    
function estrategia_is_dashboard(){
    return 0;
}

function estrategia_get_estrategia_arbol_rows($is_link=1){
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
    $query->leftJoin('estrategia','e','node_field_data.vid=e.vid');
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
    
    //foreach ($result as $row=>$node){
    foreach ($result as $row){  
        //$node=Node::load($row->nid);
        //$my_list[]=$node;  

        //prueba
        //$output .= node_view(node_load($node->nid), 1);
        
        //intelsat-2017-error-estrategia-nid-empty
        //$my_node=node_load($node->nid);
        $my_node=node_load($row->nid);
        
        $my_list[]=$my_node;
        $rows = TRUE;

       // $node_title=$node->label();
       // $node_nid=$node->id();
       // $node_body='';
    }
    

    //echo print_r($my_list,1);exit();

    if($is_link){
     // print 'entra';exit();
      $estrategia_desplegar=new EstrategiaDesplegarController();
      $rows=$estrategia_desplegar->estrategia_create_arbol($my_list);


      //echo print_r($rows,1);exit();


      //nuevo codigo
      /*$estrategia_controller=new EstrategiaController();
      $rows=$estrategia_controller->estrategia_get_estrategia_arbol_rows();
    
      //echo print_r($rows,1);exit();
      $build=array('#type' => 'table',
      '#header' => $header,
      '#rows' => $rows);
   
      return $build;
      */
      return $rows;

    }else{
      return $my_list;
    }  
  }

  function get_estrategia_simbolo_img($is_taula_header=0){
    //intelsat-2016
    global $base_url;
    $html=array();
    //gemini-2014
    $style=$this->estrategia_get_simbolo_style($is_taula_header);    
    //
    $html[]='<img '.$style.' src="'.$base_url.'/sites/default/files/my_images/estrategia.png"/>';
    //gemini-2014
    if(!$is_taula_header){
        $html[]='&nbsp;';
    }
    return implode('',$html);
    }

    function estrategia_get_estrategia_row($estrategia_nid,$vid){
    $result=array();

        $where=array();
    
        $where[]="1";
            //echo print_r($estrategia_nid,1); exit;
        $where[]="estrategia.nid=".$estrategia_nid;
          $where[]="estrategia.vid=".$vid;

        $sql="SELECT estrategia.*
        FROM {estrategia}
        WHERE ".implode(" AND ",$where);
        
        //intelsat-2016
        //$sql.=" ORDER BY d.peso ASC,n.created ASC";
        //$order_by=" ORDER BY d.peso ASC,node_field_data.created,n.nid ASC";
        //$order_by=despliegue_inc_get_order_by($order_by);
        //$sql.=$order_by;

        //print $sql;exit();
        $res=db_query($sql);
        while($row=$res->fetchObject()){
          //$result[]=$row;
            return $row;
        }
    return $result;
  }

   function estrategia_get_decision_informacion_list($decision_nid){
    $result=array();
    $where=array();
    $where[]="1";
    $where[]='i.decision_nid='.$decision_nid;
    //$sql="SELECT n.*,i.peso,i.decision_nid,i.grupo_seguimiento_nid
    
    $sql="SELECT n.*, node_field_data.title, i.peso,i.decision_nid 
    FROM {node} n 
    LEFT JOIN {node_field_data} ON n.vid=node_field_data.vid
    LEFT JOIN {informacion} i ON n.nid=i.nid
    WHERE ".implode(" AND ",$where);
    
    $sql.=$order_by;
    //ORDER BY i.peso ASC,n.created ASC;
    //print $sql;
    //exit();
    $res=db_query($sql);
    //while($row=db_fetch_object($res)){ //drupal desactualizado
    while($row=$res->fetchObject()){
      $result[]=$row;
    }
    return $result;
  }
}//class EstrategiaDesplegarController