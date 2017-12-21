<?php
namespace Drupal\wiki\Plugin\Block;
 
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Example: empty block' block.
 *
 * @Block(
 *   id = "wiki_left_simple_search",
 *   subject = @Translation("Simple Search"),
 *   admin_label = @Translation("Wiki Simple Search block")
 * )
 */
class WikiSimpleSearchBlock extends BlockBase {
   
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
   	 return array(
      '#title'=>t('Simple Search'),  		    		
      '#markup' => t('Simple example block data'),
      '#cache' => array('max-age' => 0),
    ); 	
  }
}//class 
