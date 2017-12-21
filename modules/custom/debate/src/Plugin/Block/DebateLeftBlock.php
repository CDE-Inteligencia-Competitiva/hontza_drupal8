<?php
namespace Drupal\debate\Plugin\Block;
 
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Example: empty block' block.
 *
 * @Block(
 *   id = "debate_left",
 *   subject = @Translation("Discussion"),
 *   admin_label = @Translation("Discussion block")
 * )
 */
class DebateLeftBlock extends BlockBase {
   
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#title'=>t('Discussion'),  		    		
      '#markup' => t('Simple example block data'),
      '#cache' => array('max-age' => 0),
    );
  }
}//class 
