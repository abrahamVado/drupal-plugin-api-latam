
<?php

namespace Drupal\latam_api\Service;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

final class KeyManagerService {

  public function __construct(
    private KeyValueFactoryInterface $keyValueFactory,
    private ClientInterface $http,
    private LoggerInterface $logger
  ) {}

  public function getToken(array $oauth, int $ttl = 780, ?string $cacheKey = NULL): string {
    $login = (string) ($oauth['login_endpoint'] ?? '');
    $id = (string) ($oauth['client_id'] ?? '');
    $secret = (string) ($oauth['client_secret'] ?? '');
    $scope = trim((string) ($oauth['scope'] ?? ''));

    if ($login === '' || $id === '' || $secret === '') {
      throw new \InvalidArgumentException('Missing OAuth parameters.');
    }

    if ($cacheKey === NULL) {
      $cacheKey = 'tok:' . sha1($login . '|' . $id . '|' . $scope);
    }

    $store = $this->keyValueFactory->get('latam_api');
    $row = $store->get($cacheKey, NULL);
    if (is_array($row) && isset($row['token'], $row['ts']) && (time() - (int) $row['ts'] < $ttl)) {
      return (string) $row['token'];
    }

    $form = [
      'grant_type' => 'client_credentials',
      'client_id' => $id,
      'client_secret' => $secret,
    ];
    if ($scope !== '') {
      $form['scope'] = $scope;
    }

    try {
      $resp = $this->http->request('POST', $login, [
        'form_params' => $form,
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'timeout' => 12,
      ]);
      $json = json_decode((string) $resp->getBody(), true);
      if (!is_array($json) || empty($json['access_token'])) {
        $this->logger->error('LATAM KeyManager: access_token missing. Body: @b', ['@b' => (string) $resp->getBody()]);
        throw new \RuntimeException('access_token not found');
      }
      $token = (string) $json['access_token'];
      $store->set($cacheKey, ['token' => $token, 'ts' => time()]);
      return $token;
    }
    catch (\Throwable $e) {
      $this->logger->error('LATAM KeyManager: fetch failed: @m', ['@m' => $e->getMessage()]);
      throw $e;
    }
  }
}
