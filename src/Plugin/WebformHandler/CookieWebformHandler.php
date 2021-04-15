<?php


namespace Drupal\webform_cookie\Plugin\WebformHandler;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Annotation\WebformHandler;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\BrowserKit\Cookie;

/**
 *
 * @WebformHandler(
 *   id = "cookie",
 *   label = @Translation("Cookie"),
 *   category = @Translation("Session"),
 *   description = @Translation("Set an arbitrary cookie after webform submission."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED
 * )
 */
class CookieWebformHandler extends WebformHandlerBase
{
    public function defaultConfiguration()
    {
        return [
            'name' => '',
            'value' => '',
            'expires' => '',
            'domain' => '',
            'secure' => FALSE,
            'http_only' => FALSE,
        ];
    }

    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $settings = $this->getConfiguration()['settings'];

        $form = parent::buildConfigurationForm($form, $form_state);

        $php_url = Url::fromUri('https://www.php.net/manual/en/function.setcookie.php', ['attributes' => ['target' => '_blank']]);
        $php_link = Link::fromTextAndUrl($this->t('PHP documentation'), $php_url);
        $form['cookie_info'] = [
            '#markup' => $this->t('For more information on the parameters for <em>setcookie</em>, please see the @documentation', ['@documentation' => $php_link->toString()])
        ];

        $form['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Cookie name'),
            '#description' => $this->t('The name of the cookie to be set.'),
            '#default_value' => $settings['name'],
            '#required' => TRUE,
        ];

        $form['value'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Cookie value'),
            '#description' => $this->t('The value of the cookie to be set. Webform tokens are supported.'),
            '#default_value' => $settings['value'],
            '#required' => TRUE,
        ];

        $options = [300, 600, 900, 1800, 3600, 10800, 21600, 43200, 86400, 604800];
        $form['expires'] = [
          '#type' => 'select',
          '#title' => $this->t('Cookie expiration'),
          '#description' => $this->t('The expiration time for the cookie.'),
          '#default_value' => $settings['expires'] ?? 0,
          '#required' => TRUE,
          '#options' => [0 => t('Session')] + array_map([\Drupal::service('date.formatter'), 'formatInterval'], array_combine($options, $options)),
        ];

        $form['domain'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Cookie domain'),
            '#description' => $this->t('The (sub)domain that the cookie is available to. Leave empty to use default: %site', ['%site' => \Drupal::request()->getHost() ]),
            '#default_value' => $settings['domain'],
        ];

        $form['secure'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Secure'),
            '#description' => $this->t('When checked, indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.'),
            '#default_value' => $settings['secure'],
        ];

        $form['http_only'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('HTTP only'),
            '#description' => $this->t('When checked the cookie will be made accessible only through the HTTP protocol.'),
            '#default_value' => $settings['http_only'],
        ];

        return $form;
    }

    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        $this->applyFormStateToConfiguration($form_state);
    }

    public function getSummary()
    {
        return parent::getSummary();
    }

    /**
     * {@inheritDoc}
     */
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE)
    {
        $settings = $this->getConfiguration()['settings'];
        $name = trim($settings['name']);
        $value = rawurlencode($this->tokenManager->replace($settings['value'], $webform_submission));
        $expires = $settings['expires'] > 0 ? \Drupal::time()->getRequestTime() + $settings['expires'] : 0;
        $domain = trim($settings['domain']) ?: \Drupal::request()->getHost();
        $secure = (bool) $settings['secure'];
        $http_only = (bool) $settings['http_only'];
        setcookie($name, $value, $expires, '/', $domain, $secure, $http_only);
    }
}