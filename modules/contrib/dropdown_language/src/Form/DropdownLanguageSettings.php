<?php

namespace Drupal\dropdown_language\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WrapperConfig.
 *
 * @package Drupal\dropdown_language\Form
 */
class DropdownLanguageSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropdown_language_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dropdown_language.setting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dropdown_language.setting');

    $wrapper_default = $config->get('wrapper');
    $form['wrapper'] = [
      '#type' => 'radios',
      '#options' => [
        '1' => $this->t('Yes'),
        '0' => $this->t('No')
      ],
      '#title' => $this->t('Use Fieldset wrapping around Dropdown'),
      '#default_value' => $wrapper_default,
    ];

    $display_language_id_default = $config->get('display_language_id');
    $form['display_language_id'] = [
      '#type' => 'radios',
      '#options' => [
        '1' => $this->t('Show Language Name'),
        '0' => $this->t('Show Language ID')
      ],
      '#title' => $this->t('Display Language Labelling'),
      '#default_value' => $display_language_id_default,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dropdown_language.setting')
      ->set('wrapper', $form_state->getValue('wrapper'))
      ->set('display_language_id', $form_state->getValue('display_language_id'))
      ->save();
    parent::submitForm($form, $form_state);
    Cache::invalidateTags(['rendered']);
  }

}
