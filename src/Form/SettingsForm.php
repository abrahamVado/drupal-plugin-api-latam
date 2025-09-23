<?php

namespace Drupal\key_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

final class SettingsForm extends ConfigFormBase {
  public function getFormId(): string {
    return 'key_manager_settings_form';
  }

  protected function getEditableConfigNames(): array {
    return ['key_manager.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $cfg = $this->config('key_manager.settings');

    $form['login_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Login endpoint URL'),
      '#default_value' => $cfg->get('login_endpoint') ?: '',
      '#required' => TRUE,
    ];

    $form['oauth_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OAuth client id'),
      '#default_value' => $cfg->get('oauth_client_id') ?: '',
      '#required' => TRUE,
    ];

    $form['oauth_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OAuth client secret'),
      '#default_value' => $cfg->get('oauth_client_secret') ?: '',
      '#required' => TRUE,
    ];

    $form['oauth_scope'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OAuth scope (optional)'),
      '#default_value' => $cfg->get('oauth_scope') ?: '',
    ];

    $form['token_ttl_seconds'] = [
      '#type' => 'number',
      '#title' => $this->t('Token TTL (seconds)'),
      '#default_value' => (int) ($cfg->get('token_ttl_seconds') ?? 780),
      '#min' => 60,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory()->getEditable('key_manager.settings')
      ->set('login_endpoint', (string) $form_state->getValue('login_endpoint'))
      ->set('oauth_client_id', (string) $form_state->getValue('oauth_client_id'))
      ->set('oauth_client_secret', (string) $form_state->getValue('oauth_client_secret'))
      ->set('oauth_scope', (string) $form_state->getValue('oauth_scope'))
      ->set('token_ttl_seconds', (int) $form_state->getValue('token_ttl_seconds'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
