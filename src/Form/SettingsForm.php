
<?php

namespace Drupal\latam_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

final class SettingsForm extends ConfigFormBase {
  public function getFormId(): string {
    return 'latam_api_settings_form';
  }

  protected function getEditableConfigNames(): array {
    return ['latam_api.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $c = $this->config('latam_api.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable module'),
      '#default_value' => $c->get('enabled') ?? TRUE,
    ];

    $form['security'] = [
      '#type' => 'details',
      '#title' => $this->t('Security'),
      '#open' => TRUE,
    ];
    $form['security']['require_header_token'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require header token (X-LATAM-TOKEN)'),
      '#default_value' => $c->get('require_header_token') ?? FALSE,
    ];
    $form['security']['header_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header token'),
      '#default_value' => $c->get('header_token') ?? '',
      '#states' => [
        'visible' => [
          ':input[name="require_header_token"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['countries'] = [
      '#type' => 'details',
      '#title' => $this->t('Per-country settings'),
      '#open' => TRUE,
    ];

    foreach (['MX', 'CL', 'BR'] as $cc) {
      $form['countries'][$cc] = [
        '#type' => 'fieldset',
        '#title' => $this->t('@cc settings', ['@cc' => $cc]),
      ];
      $form['countries'][$cc]["{$cc}_base_url"] = [
        '#type' => 'url',
        '#title' => $this->t('Base URL'),
        '#default_value' => $c->get("countries.$cc.base_url") ?? '',
      ];
      $form['countries'][$cc]["{$cc}_api_key"] = [
        '#type' => 'textfield',
        '#title' => $this->t('API key (fallback if OAuth not set)'),
        '#default_value' => $c->get("countries.$cc.api_key") ?? '',
      ];
      $form['countries'][$cc]["{$cc}_locale"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Locale'),
        '#default_value' => $c->get("countries.$cc.locale") ?? '',
      ];
      $form['countries'][$cc]["{$cc}_oauth_token_url"] = [
        '#type' => 'url',
        '#title' => $this->t('OAuth token URL'),
        '#default_value' => $c->get("countries.$cc.oauth_token_url") ?? '',
      ];
      $form['countries'][$cc]["{$cc}_oauth_client_id"] = [
        '#type' => 'textfield',
        '#title' => $this->t('OAuth client id'),
        '#default_value' => $c->get("countries.$cc.oauth_client_id") ?? '',
      ];
      $form['countries'][$cc]["{$cc}_oauth_client_secret"] = [
        '#type' => 'textfield',
        '#title' => $this->t('OAuth client secret'),
        '#default_value' => $c->get("countries.$cc.oauth_client_secret") ?? '',
      ];
      $form['countries'][$cc]["{$cc}_oauth_scope"] = [
        '#type' => 'textfield',
        '#title' => $this->t('OAuth scope'),
        '#default_value' => $c->get("countries.$cc.oauth_scope") ?? '',
      ];
      $form['countries'][$cc]["{$cc}_pinpoint_url"] = [
        '#type' => 'url',
        '#title' => $this->t('Pinpoint endpoint URL'),
        '#default_value' => $c->get("countries.$cc.pinpoint_url") ?? '',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $v = $form_state->getValues();
    $save = [
      'enabled' => (bool) $v['enabled'],
      'require_header_token' => (bool) $v['require_header_token'],
      'header_token' => (string) $v['header_token'],
      'countries' => [
        'MX' => [
          'base_url' => (string) $v['MX_base_url'],
          'api_key'  => (string) $v['MX_api_key'],
          'locale'   => (string) $v['MX_locale'],
          'oauth_token_url' => (string) $v['MX_oauth_token_url'],
          'oauth_client_id' => (string) $v['MX_oauth_client_id'],
          'oauth_client_secret' => (string) $v['MX_oauth_client_secret'],
          'oauth_scope' => (string) $v['MX_oauth_scope'],
          'pinpoint_url' => (string) $v['MX_pinpoint_url'],
        ],
        'CL' => [
          'base_url' => (string) $v['CL_base_url'],
          'api_key'  => (string) $v['CL_api_key'],
          'locale'   => (string) $v['CL_locale'],
          'oauth_token_url' => (string) $v['CL_oauth_token_url'],
          'oauth_client_id' => (string) $v['CL_oauth_client_id'],
          'oauth_client_secret' => (string) $v['CL_oauth_client_secret'],
          'oauth_scope' => (string) $v['CL_oauth_scope'],
          'pinpoint_url' => (string) $v['CL_pinpoint_url'],
        ],
        'BR' => [
          'base_url' => (string) $v['BR_base_url'],
          'api_key'  => (string) $v['BR_api_key'],
          'locale'   => (string) $v['BR_locale'],
          'oauth_token_url' => (string) $v['BR_oauth_token_url'],
          'oauth_client_id' => (string) $v['BR_oauth_client_id'],
          'oauth_client_secret' => (string) $v['BR_oauth_client_secret'],
          'oauth_scope' => (string) $v['BR_oauth_scope'],
          'pinpoint_url' => (string) $v['BR_pinpoint_url'],
        ],
      ],
    ];

    $this->configFactory()->getEditable('latam_api.settings')->setData($save)->save();
    parent::submitForm($form, $form_state);
  }
}
