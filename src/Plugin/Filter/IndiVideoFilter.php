<?php

namespace Drupal\latam_api\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;

/**
 * Provides a [individeo] shortcode filter.
 *
 * @Filter(
 *   id = "filter_individeo",
 *   title = @Translation("IndiVideo shortcode"),
 *   description = @Translation("Replaces [individeo] with configured IndiVideo embed."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class IndiVideoFilter extends FilterBase {

  public function process($text, $langcode) {
    if (strpos($text, '[individeo]') === FALSE) {
      return new FilterProcessResult($text);
    }

    $config = \Drupal::config('latam_api.settings')->get('invideo') ?: [];
    $enabled = !empty($config['enabled']);
    $base_url = $config['base_url'] ?? '';

    if (!$enabled || !$base_url) {
      return new FilterProcessResult($text);
    }

    // Build replacement markup
    $render = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['individeo-embed'],
        'data-video-base' => $base_url,
        'data-vars' => $config['vars_json'] ?? '{}',
      ],
      '#attached' => [
        'library' => ['latam_api/individeo.embed'],
      ],
    ];

    $replacement = \Drupal::service('renderer')->renderPlain($render);

    $text = str_replace('[individeo]', $replacement, $text);
    return new FilterProcessResult($text);
  }
}
