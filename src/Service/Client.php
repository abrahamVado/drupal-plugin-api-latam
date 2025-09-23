
<?php

namespace Drupal\latam_api\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

final class Client {
  public function __construct(
    private ClientInterface $http,
    private ConfigFactoryInterface $configFactory,
    private LoggerInterface $logger,
    private KeyManagerService $keyManager
  ) {}

  public function getCountryInfo(string $cc): array {
    $cc = strtoupper($cc);
    $cfg = $this->configFactory->get('latam_api.settings');
    return (array) ($cfg->get("countries.$cc") ?? []);
  }

  public function authMode(string $cc): string {
    $info = $this->getCountryInfo($cc);
    if (!empty($info['oauth_token_url']) && !empty($info['oauth_client_id']) && !empty($info['oauth_client_secret'])) {
      return 'oauth';
    }
    if (!empty($info['api_key'])) {
      return 'api_key';
    }
    return 'none';
  }

  public function request(string $cc, string $method, string $pathOrAbsolute, array $opts = []): array {
    $info = $this->getCountryInfo($cc);
    $base = rtrim((string) ($info['base_url'] ?? ''), '/');
    $url = preg_match('/^https?:\/\//i', $pathOrAbsolute) ? $pathOrAbsolute : ($base === '' ? '' : $base . '/' . ltrim($pathOrAbsolute, '/'));

    if ($url === '') {
      throw new \RuntimeException("Country $cc is not configured.");
    }

    $mode = $this->authMode($cc);
    if ($mode === 'oauth') {
      $token = $this->keyManager->getToken([
        'login_endpoint' => (string) $info['oauth_token_url'],
        'client_id' => (string) $info['oauth_client_id'],
        'client_secret' => (string) $info['oauth_client_secret'],
        'scope' => (string) ($info['oauth_scope'] ?? ''),
      ]);
      $opts['headers']['Authorization'] = 'Bearer ' . $token;
    } elseif ($mode === 'api_key') {
      $opts['headers']['Authorization'] = 'Bearer ' . (string) $info['api_key'];
    }

    $opts['headers']['Accept'] = $opts['headers']['Accept'] ?? 'application/json';
    $resp = $this->http->request($method, $url, $opts);
    $raw = (string) $resp->getBody();
    $json = json_decode($raw, true);
    return is_array($json) ? $json : ['raw' => $raw];
  }
}
