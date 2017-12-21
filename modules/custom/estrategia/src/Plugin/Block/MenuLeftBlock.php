<?php
namespace Drupal\estrategia\Plugin\Block;
 
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
 
/**
 * Provides a 'Example: empty block' block.
 *
 * @Block(
 *   id = "menu_left",
 *   subject = @Translation("Menu"),
 *   admin_label = @Translation("Menu block")
 * )
 */
class MenuLeftBlock extends BlockBase {
   
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#title'=>t('Strategy'),              
      '#markup' => t('Simple example block data'),
      '#cache' => array('max-age' => 0),
    );
  }
} 
