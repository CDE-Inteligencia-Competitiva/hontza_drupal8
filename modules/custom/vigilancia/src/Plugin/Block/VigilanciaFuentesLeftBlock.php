<?php
namespace Drupal\vigilancia\Plugin\Block;
 
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
 
/**
 * Provides a 'Example: empty block' block.
 *
 * @Block(
 *   id = "vigilancia_fuentes_left",
 *   subject = @Translation("Source Types"),
 *   admin_label = @Translation("Source Types block")
 * )
 */
class VigilanciaFuentesLeftBlock extends BlockBase {
   
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#title'=>t('Source Types'),  		    		
      '#markup' => t('Simple example block data'),
      '#cache' => array('max-age' => 0),
    );
  }
 
} 
