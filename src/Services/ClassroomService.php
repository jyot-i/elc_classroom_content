<?php

namespace Drupal\elc_classroom_content\Services;

use Drupal\views\Views;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\file\Entity\File;
use Drupal\Core\Archiver\Zip;
use Drupal\Core\Site\Settings;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Database\Connection;

/**
 * Class ClassroomService.
 */
class ClassroomService {

  /**
   * The file system service.
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;
  
  /**
   * LxLogger object.
   *
   * @var \Drupal\lx_utility\LxLogger
   */
  protected $loggerStdout;

  /**
   * The database connection to be used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
  * Class constructor.
  * @param \Drupal\Core\File\FileSystemInterface $file_system
  */
  public function __construct(FileSystemInterface $file_system, LoggerInterface $loggerStdout, Connection $database) {
    $this->fileSystem = $file_system;
    $this->loggerStdout = $loggerStdout;
    $this->database = $database;
  }


  /**
   * Get view result.
   * @return Jsonresponse
   *   return view seult markup.
   */
  public function getViewContent($viewName, $viewDisplay, array $arguments = []) {
    $view = Views::getView($viewName);
    $view->setDisplay($viewDisplay);
    $view->setArguments($arguments);
    $render_view = $view->render();
    return $render_view['#markup'];
  }

  /**
   * set moduleType and set description null if empty.
   *
   * @param $result
   *   Array of result set.
   * @param $moduleType
   *   moduleType field name.
   * @param $field
   *   file field name.
   * @param $desc_field
   *   description field name.
   *
   * @return Jsonresponse
   *   return access allowed HTTP code and message.
   */
  public function createLinkAndFileType($result, $moduleType = NULL, $field = NULL, $desc_field = NULL) {
    $value = Json::decode($result);
    foreach ($value as $val) {
      if ($val['field_content_document'] == 'URL' && $val['field_url']) {
        $val[$moduleType] = 'Link';
        $val[$field] = $val['field_url'];
      }
      if ($val[$desc_field] == "" || $val[$desc_field] == "NULL") {
       $val[$desc_field] = NULL;
     }
     else {
      $val[$field] = $val[$field];
    }
    unset($val['field_content_document']);
    unset($val['field_url']);
    unset($val['']);
    $resultValue[] = $val;
  }
  return Json::encode($resultValue);
}
  /**
   * Access allowed for API.
   *
   * @param $data
   *   Array of result set.
   *
   * @return Jsonresponse
   *   return access allowed HTTP code and message.
   */
  public  function accessAllowed($data, $json = TRUE) {
    // Calling custom logger service.
    $this->loggerStdout->log(RfcLogLevel::INFO, 'Access granted, Data saved Successfully');
    return new JsonResponse($data, 200, [], $json);
  }

/**
 * Helper Service to get the file type of the module files.
 *
 * @param int $fid
 *   The ID of the file.
 *
 * @return array
 *   The type of the file uploaded.
 */
  public function getFileType($fid) {

    $query = db_select('file_managed', 'fm')
    ->fields('fm', ['filemime'])
    ->condition('fid', $fid);
    return $query->execute()->fetchField();
  }  

/**
 * Extract archive file. Search for html file. If found then return its URL.
 *
 * @param int $file_id
 *   The fid of the uploaded file.
 *
 * @return string
 *   File url.
 */
public function getInteractiveContentUrl($file_id) {

  $interactive_file_url = '';

  if (!empty($file_id)) {
    $files_path = $this->fileSystem->realpath(file_default_scheme() . "://");
    $lx_file = File::load($file_id);
    $lx_file_uri = $lx_file->getFileUri();
    $zip_name = REQUEST_TIME;
    if (!empty($lx_file_uri)) {
      $folder_name = end(explode('/', $lx_file_uri));
      $zip_name = pathinfo($folder_name)['filename'];
    }
    $archive_real_path = $this->fileSystem->realpath($lx_file_uri);
    // Opens archive file and extract contents.
    $archiver = new Zip($archive_real_path);
    // Search for html file.
    $archive_contents = $archiver->listContents();
    $interactive_file = '';

    // Loop through all the files and find if story_html5.html file exists.
    foreach ($archive_contents as $key => $archive_file) {
      if (preg_match('/index\.html/', $archive_file)) {
        $interactive_file = $archive_file;
        break;
      }
    }

    if (empty($interactive_file)) {
      // Check for index.html file.
      foreach ($archive_contents as $key => $archive_file) {
        if (preg_match('/story_html5\.html/', $archive_file)) {
          $interactive_file = $archive_file;
          break;
        }
      }
    }
    // Check for story html file inside zip content.
    if (empty($interactive_file)) {
      foreach ($archive_contents as $key => $archive_file) {
        if (preg_match('/story\.html/', $archive_file)) {
          $interactive_file = $archive_file;
          break;
        }
      }
    }
    // Check for any html file inside zip content.
    if (empty($interactive_file)) {
      foreach ($archive_contents as $key => $archive_file) {
        if (preg_match('/^.*\.html/', $archive_file)) {
          $interactive_file = $archive_file;
        }
      }
    }
    // If html file exists then extract and create URL.
    if (!empty($interactive_file)) {
      $archiver->extract($files_path . '/' . $zip_name);
      $interactive_file_url = Settings::get('cdn_file_public_base_url') . '/' . explode('//', file_default_scheme() . "://" . $zip_name . '/' . $interactive_file)[1];
    }
  }

  return $interactive_file_url;
}
 /**
 * Helper Service to get all modules lying under level.
 *
 * @param int $level_id
 *   The ID of the level.
 *
 * @return array
 *   The type of the file uploaded.
 */
  public function getModuleListing($level_id) {
    $query = $this->database
              ->select('node__field_level_module', 'nflm')
             ->fields('nflm', ['field_level_module_target_id'])
             ->condition('langcode', 'en')
             ->condition('entity_id', $level_id);
   $moduleIds = $query->execute()->fetchAllKeyed(0,0);
    return $moduleIds;
  }

/**
 * Helper Service to get level id of provided module id.
 *
 * @param int $module_id
 *   The ID of the module.
 *
 * @return array
 *   The type of the file uploaded.
 */
  public function getLevel($module_id) {
      $query = $this->database
              ->select('node__field_level_module', 'nflm')
             ->fields('nflm', ['entity_id'])
             ->condition('langcode', 'en')
             ->condition('field_level_module_target_id', $module_id);
   $levelId = $query->execute()->fetchField();
    return $levelId;
  } 
}