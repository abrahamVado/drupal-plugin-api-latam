
<?php

namespace Drupal\latam_api\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

final class ApiAccessCheck {
  public static function access(AccountInterface $account): AccessResult {
    if (!$account->hasPermission('access latam api')) {
      return AccessResult::forbidden();
    }

    $config = \Drupal::config('latam_api.settings');

    // Rate limiting
    $rate = (array) ($config->get('rate_limit') ?? []);
    if (!empty($rate['enabled'])) {
      $limit = (int) ($rate['requests_per_minute'] ?? 60);
      $flood = \Drupal::service('flood');
      $ip = \Drupal::request()->getClientIp();
      $name = 'latam_api_ratelimit_' . (\Drupal::routeMatch()->getRouteName() ?? 'unknown');
      if (!$flood->isAllowed($name, $limit, 60, $ip)) {
        return AccessResult::forbidden();
      }
      $flood->register($name, 60, $ip);
    }

    // Optional header token
    if ($config->get('require_header_token')) {
      $expected = (string) $config->get('header_token');
      $got = (string) (\Drupal::request()->headers->get('X-LATAM-TOKEN') ?? '');
      if ($expected === '' || !hash_equals($expected, $got)) {
        return AccessResult::forbidden();
      }
    }

    return AccessResult::allowed();
  }
}
