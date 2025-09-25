<?php

namespace Drupal\latam_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for IndiVideo host page.
 */
class IndiVideoController extends ControllerBase {

  protected $configFactory;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Builds the host page for IndiVideo.
   */
  public function build() {
    $config = $this->configFactory->get('latam_api.settings');
    $settings = $config->get('invideo') ?: [];

    // SECURITY STEP: enforce individeo.com only if strict_host = true
    $base_url = $settings['base_url'] ?? '';
    if (!empty($settings['strict_host']) && parse_url($base_url, PHP_URL_HOST) !== 'individeo.com') {
      return [
        '#markup' => $this->t('Invalid IndiVideo base URL.'),
      ];
    }

    return [
      '#theme' => 'latam_individeo_page',
      '#attached' => [
        'library' => ['latam_api/individeo.embed'],
      ],
      '#settings' => $settings,
      '#cache' => [
        'max-age' => (int) ($settings['cache_ttl'] ?? 0),
      ],
    ];
  }
}
