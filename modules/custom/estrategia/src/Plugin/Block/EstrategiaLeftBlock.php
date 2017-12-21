<?php
namespace Drupal\estrategia\Plugin\Block;
 
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
 
/**
 * Provides a 'Example: empty block' block.
 *
 * @Block(
 *   id = "estrategia_left",
 *   subject = @Translation("Challenges"),
 *   admin_label = @Translation("Challenges block")
 * )
 */
class EstrategiaLeftBlock extends BlockBase {
   
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#title'=>t('Challenges'),              
      '#markup' => t('Simple example block data'),
      '#cache' => array('max-age' => 0),
    );
  }
} 
