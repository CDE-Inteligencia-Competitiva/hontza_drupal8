<?php
/**
 * @file
 * Contains \Drupal\debate\Form\DebateSimpleSearchForm.
 */
namespace Drupal\debate\Form;
 
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grupo\Controller\GrupoController;
use Drupal\Core\Url;

class DebateSimpleSearchForm extends FormBase {

  public function getFormId() {
    /**
     * {@inheritdoc}
     */
    return 'simple_search_form';
  }
  public function buildForm(array $form, FormStateInterface $form_state) {
      /**
     * {@inheritdoc}
     */
    $my_request = \Drupal::request();
    $search=$my_request->get('search');    

      $form['simple_search'] = array(
      '#type' => 'textfield',
      '#default_value' => $search,
    //  '#title' => t('Simple Search:'),
    //  '#required' => TRUE,

      );
      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Search'),
        '#name'=>'search_btn',
        
      );
      $form['actions']['#type'] = 'actions';
      $form['actions']['previous'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Clean'),
        '#name'=>'clean_btn',
      );
      
    return $form;
  }
   /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    $grupo_controller=new GrupoController();
    $my_grupo=$grupo_controller->grupo_get_current_grupo();
    if(!empty($my_grupo)){
      $grupo_nid=$my_grupo->id();
    }   
    foreach ($form_state->getValues('simple_search') as $key => $value) {
    $triggerdElement=$form_state->getTriggeringElement();
      if($triggerdElement['#name']=='search_btn'){
        $search=$form_state->getValue('simple_search');
        
        $url = Url::fromRoute('debates_grupo',array('group'=>$grupo_nid,'search'=>$search));
        $form_state->setRedirectUrl($url);
   
      }else{
        $url = Url::fromRoute('debates_grupo',array('group'=>$grupo_nid));
        $form_state->setRedirectUrl($url);
      }
    }
  }  
} //class 
