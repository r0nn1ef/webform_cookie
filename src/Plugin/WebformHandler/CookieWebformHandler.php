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
        $config = $this->getConfiguration();
        $settings = $config['settings'];
        $timezone = \Drupal::config('system.date')->get('timezone')['default'];

        if (empty($settings['expires'])) {
            $expires = \Drupal::time()->getRequestTime() + 60 * 60 * 24;
            $exp = new DrupalDateTime('now', $timezone);
            $exp->setTimestamp($expires);
            $expires = $exp;
        } elseif (is_numeric($settings['expires'])) {
            $exp = new DrupalDateTime('now', $timezone);
            $exp->setTimestamp($settings['expires']);
            $expires = $exp;
        }

        $form = parent::buildConfigurationForm($form, $form_state);

        $php_url = Url::fromUserInput('https://www.php.net/manual/en/function.setcookie.php', ['attributes' => ['target' => '_blank']]);
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

        $form['expires'] = [
            '#type' => 'datetime',
            '#title' => $this->t('Cookie expiration'),
            '#description' => $this->t('The expiration date for the cookie. This can be a specific date in any valid date format.'),
            '#date_date_format' => 'Y-m-d',
            '#date_time_format' => 'H:i',
            '#date_timezone' => $timezone = \Drupal::config('system.date')->get('timezone')['default'],
            '#default_value' => $expires,
            '#required' => TRUE,
        ];

        $form['domain'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Cookie domain'),
            '#description' => $this->t('The (sub)domain that the cookie is available to. Default: %site', ['%site' => \Drupal::request()->getHost() ]),
            '#default_value' => empty($settings['domain']) ? \Drupal::request()->getHost() : $settings['domain'],
            '#required' => TRUE,
        ];

        $form['secure'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Secure'),
            '#description' => $this->t('When checked, indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.'),
            '#default_value' => $settings['secure'],
            '#required' => TRUE,
        ];

        $form['http_only'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('HTTP only'),
            '#description' => $this->t('When checked the cookie will be made accessible only through the HTTP protocol.'),
            '#default_value' => $settings['http_only'],
            '#required' => TRUE,
        ];

        return $form;
    }

    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        $timestamp = $form_state->getValue('expires')->getTimestamp();
        $form_state->setValue('expires', $timestamp);
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
        $config = $this->getConfiguration();
        $name = trim($config['settings']['name']);
        $value = rawurlencode($this->tokenManager->replace($config['settings']['value'], $webform_submission));
        $expires = trim($config['settings']['expires']);
        $domain = trim($config['settings']['domain']);
        $result = setcookie($name, $value, $expires, '/', $domain);
    }
}