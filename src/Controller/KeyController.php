<?php

namespace Drupal\key_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\key_manager\Service\KeyManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class KeyController extends ControllerBase {

  public function __construct(private KeyManagerService $keyManager) {}

  public static function create(ContainerInterface $container): self {
    return new self($container->get('key_manager.service'));
  }

  public function getKey(): JsonResponse {
    try {
      $obj = $this->keyManager->getKey();
      return new JsonResponse(['ok' => true, 'obj' => $obj], 200);
    } catch (\Throwable $e) {
      return new JsonResponse([
        'ok' => false,
        'error' => 'Token fetch failed',
        'message' => $e->getMessage(),
      ], 500);
    }
  }
}
