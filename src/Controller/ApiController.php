
<?php

namespace Drupal\latam_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\latam_api\Service\Client;

final class ApiController extends ControllerBase {

  public function __construct(private Client $client) {}

  public static function create(ContainerInterface $container): self {
    return new self($container->get('latam_api.client'));
  }

  /** GET /api/latam/ping?cc=MX */
  public function ping(): JsonResponse {
    $cc = strtoupper((string) (\Drupal::request()->query->get('cc') ?? 'MX'));
    $info = $this->client->getCountryInfo($cc);

    return new JsonResponse([
      'ok' => true,
      'country' => $cc,
      'base_url' => $info['base_url'] ?? null,
      'locale' => $info['locale'] ?? null,
      'auth_mode' => $this->client->authMode($cc),
      'timestamp' => (new \DateTimeImmutable('now'))->format(DATE_ATOM),
    ]);
  }

  /** POST /api/latam/pinpoint?cc=MX */
  public function postPinpoint(Request $request): JsonResponse {
    $cc = strtoupper((string) ($request->query->get('cc') ?? 'MX'));
    $info = $this->client->getCountryInfo($cc);
    $pinpointUrl = (string) ($info['pinpoint_url'] ?? '');
    if ($pinpointUrl === '') {
      return new JsonResponse(['ok' => false, 'error' => "pinpoint_url not configured for $cc"], 500);
    }

    $payload = json_decode($request->getContent(), true);
    if (!is_array($payload)) {
      return new JsonResponse(['ok' => false, 'error' => 'Invalid JSON body'], 400);
    }

    $required = ['email','perfil','invInicial','invMensual','plazo','NumContrato','fondo','clave','esceConservador','nameTemplateEcommerce','versionTemplateEcommerce','hrefEcommerce'];
    foreach ($required as $k) {
      if (!array_key_exists($k, $payload)) {
        return new JsonResponse(['ok' => false, 'error' => "Missing field: $k"], 400);
      }
    }

    $resp = $this->client->request($cc, 'POST', $pinpointUrl, [
      'json' => $payload,
    ]);

    return new JsonResponse(['ok' => true, 'data' => $resp]);
  }
}
