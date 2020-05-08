<?php

namespace Drupal\elc_classroom_content\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'file_raw_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "file_raw_formatter",
 *   label = @Translation("File Raw"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileRawFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    foreach ($files as $delta => $file) {
      $file_uri = $file->getFileUri();

      // Get absolute path for original file.
      $absolute_path = Url::fromUri(file_create_url($file_uri))->getUri();

      $elements[$delta] = [
        '#markup' => $absolute_path,
      ];
    }
    return $elements;
  }

}
