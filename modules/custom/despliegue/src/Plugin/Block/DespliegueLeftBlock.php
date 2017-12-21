<?php
namespace Drupal\despliegue\Plugin\Block;
 
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
 
/**
 * Provides a 'Example: empty block' block.
 *
 * @Block(
 *   id = "despliegue_left",
 *   subject = @Translation("Subchallenges"),
 *   admin_label = @Translation("Subchallenges block")
 * )
 */
class DespliegueLeftBlock extends BlockBase {
   
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#title'=>t('Subchallenges'),             
      '#markup' => t('Simple example block data'),
      '#cache' => array('max-age' => 0),
    );
  }
} 