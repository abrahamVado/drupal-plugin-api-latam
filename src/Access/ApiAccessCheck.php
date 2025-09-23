
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
