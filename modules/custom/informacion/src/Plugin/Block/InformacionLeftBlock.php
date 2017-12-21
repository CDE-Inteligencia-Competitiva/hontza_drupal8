<?php
namespace Drupal\informacion\Plugin\Block;
 
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
 
/**
 * Provides a 'Example: empty block' block.
 *
 * @Block(
 *   id = "informacion_left",
 *   subject = @Translation("Key Questions"),
 *   admin_label = @Translation("Key Questions block")
 * )
 */
class InformacionLeftBlock extends BlockBase {
   
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#title'=>t('Key Questions'),             
      '#markup' => t('Simple example block data'),
      '#cache' => array('max-age' => 0),
    );
  }
} 