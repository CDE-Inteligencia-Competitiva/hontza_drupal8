<?php
namespace Drupal\report\Plugin\Block;
 
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
 
/**
 * Provides a 'Example: empty block' block.
 *
 * @Block(
 *   id = "report_left",
 *   subject = @Translation("Reports Area"),
 *   admin_label = @Translation("Reports Area block")
 * )
 */
class ReportLeftBlock extends BlockBase {
   
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#title'=>t('Reports Area'),  		    		
      '#markup' => t('Simple example block data'),
      '#cache' => array('max-age' => 0),
    );
  }
 
} 
