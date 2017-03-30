<?php
namespace Drupal\vigilancia\Plugin\Block;
 
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
 
/**
 * Provides a 'Example: empty block' block.
 *
 * @Block(
 *   id = "vigilancia_categorias_left",
 *   subject = @Translation("Categories"),
 *   admin_label = @Translation("Categories block")
 * )
 */
class VigilanciaCategoriasLeftBlock extends BlockBase {
   
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#title'=>t('Categories'),  		    		
      '#markup' => t('Simple example block data'),
      '#cache' => array('max-age' => 0),
    );
  }
 
} 
