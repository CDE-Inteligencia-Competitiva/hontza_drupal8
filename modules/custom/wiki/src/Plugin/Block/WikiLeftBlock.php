<?php
namespace Drupal\wiki\Plugin\Block;
 
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
 
/**
 * Provides a 'Example: empty block' block.
 *
 * @Block(
 *   id = "wiki_left",
 *   subject = @Translation("Collaboration"),
 *   admin_label = @Translation("Collaboration block")
 * )
 */
class WikiLeftBlock extends BlockBase {
   
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#title'=>t('Collaboration'),  		    		
      '#markup' => t('Simple example block data'),
      '#cache' => array('max-age' => 0),
    );
  }
 
} 
