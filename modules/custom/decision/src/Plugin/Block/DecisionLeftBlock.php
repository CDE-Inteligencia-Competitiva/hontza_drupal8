<?php
namespace Drupal\decision\Plugin\Block;
 
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
 
/**
 * Provides a 'Example: empty block' block.
 *
 * @Block(
 *   id = "decision_left",
 *   subject = @Translation("Decisions"),
 *   admin_label = @Translation("Decisions block")
 * )
 */
class DecisionLeftBlock extends BlockBase {
   
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#title'=>t('Decisions'),             
      '#markup' => t('Simple example block data'),
      '#cache' => array('max-age' => 0),
    );
  }
} 