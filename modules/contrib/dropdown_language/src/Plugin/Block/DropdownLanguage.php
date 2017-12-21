<?php

namespace Drupal\dropdown_language\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;

/**
 * Class DropdownLanguage.
 *
 * @package Drupal\dropdown_language\Form
 *
 * @Block(
 *   id = "dropdown_language",
 *   admin_label = @Translation("Dropdown Language Selector"),
 *   category = @Translation("Custom Blocks"),
 * )
 */
class DropdownLanguage extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];
    $language_manager = \Drupal::languageManager();
    $languages = $language_manager->getLanguages();
    if (count($languages) > 1) {
      $current_language = $language_manager->getCurrentLanguage()->getId();
      $links = $language_manager->getLanguageSwitchLinks("language_interface", Url::fromRoute('<current>'))->links;
      // Place active language ontop of list.
      if (isset($links[$current_language])) {
        $links = [$current_language => $links[$current_language]] + $links;
        // Set an active class for styling.
        $links[$current_language]['attributes']['class'][] = 'active-language';
      }
      $config = \Drupal::config('dropdown_language.setting');
      $wrapper_default = $config->get('wrapper');
      $display_language_id = $config->get('display_language_id');
      if ($display_language_id == 0) {
        foreach($links as $key => $link){
          $links[$key]['title'] = Unicode::strtoupper($key);
        }
      }
      $dropdown_button = [
        '#type' => 'dropbutton',
        '#subtype' => 'dropdown_language',
        '#links' => $links,
        '#attributes' => [
          'class' => ['dropdown-language-item'],
        ],
        '#attached' => [
          'library' => ['dropdown_language/dropdown-language-selector'],
        ],
      ];
      if ($wrapper_default == 1) {
        $block['switcher'] = [
          '#weight' => -100,
          '#type' => 'fieldset',
          '#title' => $this->t('Switch Language'),
        ];
        $block['switcher']['switch-language'] = $dropdown_button;
      }
      else {
        $block['switch-language'] = $dropdown_button;
      }
    }

    return $block;
  }

}
