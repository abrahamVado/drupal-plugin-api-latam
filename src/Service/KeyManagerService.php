<?php

namespace Drupal\key_manager\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use GuzzleHttp\ClientInterface;

final class KeyManagerService {

  public function __construct(
    private KeyValueFactoryInterface $keyValueFactory,
    private ClientInterface $httpClient,
    private ConfigFactoryInterface $configFactory
  ) {}

  public function getKey(): array {
    $cfg = $this->configFactory->get('key_manager.settings');
    $ttl = (int) ($cfg->get('token_ttl_seconds') ?? 780);

    $store = $this->keyValueFactory->get('key_manager');
    $keyInfo = $store->get('key_info', []);

    if (empty($keyInfo) || $this->isExpired($keyInfo, $ttl)) {
      $token = $this->fetchNewToken();
      $keyInfo = [
        'key' => $token,
        'timestamp' => time(),
      ];
      $store->set('key_info', $keyInfo);
    }

    $remaining = max(0, ($keyInfo['timestamp'] + $ttl) - time());
    $keyInfo['remaining_time_seconds'] = $remaining;
    $keyInfo['remaining_time'] = gmdate('i:s', $remaining);

    return $keyInfo;
  }

  private function isExpired(array $info, int $ttl): bool {
    $ts = isset($info['timestamp']) ? (int) $info['timestamp'] : 0;
    return (time() - $ts) >= $ttl;
  }

  private function fetchNewToken(): string {
    $cfg = $this->configFactory->get('key_manager.settings');
    $loginUrl = (string) $cfg->get('login_endpoint');
    $clientId = (string) $cfg->get('oauth_client_id');
    $clientSecret = (string) $cfg->get('oauth_client_secret');
    $scope = trim((string) ($cfg->get('oauth_scope') ?? ''));

    $form = [
      'grant_type' => 'client_credentials',
      'client_id' => $clientId,
      'client_secret' => $clientSecret,
    ];
    if ($scope !== '') {
      $form['scope'] = $scope;
    }

    $resp = $this->httpClient->request('POST', $loginUrl, [
      'form_params' => $form,
      'headers' => ['Accept' => 'application/json'],
      'timeout' => 10,
    ]);

    $data = json_decode((string) $resp->getBody(), true);
    if (!is_array($data) || empty($data['access_token'])) {
      throw new \RuntimeException('access_token not found in response');
    }
    return (string) $data['access_token'];
  }
}
