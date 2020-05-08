<?php

namespace Drupal\elc_classroom_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\elc_classroom_content\Services\ClassroomService;

/**
 * Class ClassRoomController.
 *
 * @package Drupal\lx_utility\Controller
 */
class ClassRoomController extends ControllerBase {

  /**
   * Constant defined.
   *
   * @var string
   */
  const LEVEL_VIEW_NAME = 'level';
  const MODULE_VIEW_NAME = 'module_listing';
  const VIEW_MACH_NAME = 'rest_export_1';
  const MODULE_TYPE = 'moduleType';
  const FILES = 'Files';
  const DESC = 'Description';
  const LEVEL_DESC = 'levelDescription';

  /**
   * LxLogger object.
   *
   * @var \Drupal\lx_utility\LxLogger
   */
  protected $loggerStdout;

  /**
   * Custom service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $classService;

  /**
   * Constructor.
   *
   * @param \Drupal\lx_utility\LoggerInterface $loggerStdout
   *   LxLogger object.
   * @param \Drupal\elc_classroom_content\Services\CustomService $CustomService
   *   customservice object.
   */
  public function __construct(LoggerInterface $loggerStdout, ClassroomService $classService) {
    $this->loggerStdout = $loggerStdout;
    $this->classService = $classService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.stdout'),
      $container->get('elc_classroom_content.classroomservice')

    );
  }

  /**
   * Route callback function for featured content.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json response.
   */
  public function getLevelListing($tab_id, Request $request) {
    try {
      $resultData =  $this->classService->getViewContent(self::LEVEL_VIEW_NAME, self::VIEW_MACH_NAME, [$tab_id]);
      $data = $this->classService->createLinkAndFileType($resultData, null, null, self::LEVEL_DESC);
    }
    catch (\Exception $e) {
      // Log the information if Level listing is unsuccessful.
      $this->loggerStdout->log(RfcLogLevel::ERROR, $e->getMessage());
    }
    return $this->classService->accessAllowed($data);
  }


  /**
   * Route callback function for Leadership Module Listing.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json response.
   */
  public function getModuleListing($level_id, Request $request) {
    try {
      $resultData = $this->classService->getViewContent(self::MODULE_VIEW_NAME, self::VIEW_MACH_NAME, [$level_id]);
      $result = $this->classService->createLinkAndFileType($resultData, self::MODULE_TYPE, self::FILES, self::DESC);
    }
    catch (\Exception $e) {
      // Log the information if  module listing is unsuccessful.
      $this->loggerStdout->log(RfcLogLevel::ERROR, $e->getMessage());
    }
    return $this->classService->accessAllowed($result);
  }


}
