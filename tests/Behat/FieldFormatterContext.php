<?php

declare(strict_types = 1);

namespace Drupal\Tests\views_field_formatter\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;

class FieldFormatterContext extends RawDrupalContext {

  /**
   * @Given I reset the formatter of the :field field of the :bundle bundle of :entity entity
   * @Given I reset the formatter of the :field field of the :bundle bundle of :entity entity in :view_mode view mode
   */
  public function iResetTheFormatterOfTheFieldOfTheBundleOfEntity($field, $bundle, $entity, $view_mode = 'default')
  {
    $config_name = sprintf('core.entity_view_display.%s.%s.%s', $entity, $bundle, $view_mode);

    $config = \Drupal::configFactory()
      ->getEditable($config_name)
      ->getRawData();

    if (!isset($config['content'][$field]) || !isset($config['hidden'][$field])) {
      // @todo Throw an error.
      return;
    }

    $region = isset($config['content'][$field]) ? 'content' : 'hidden';

    if (!is_array($config[$region][$field])) {
      $config[$region][$field] = [
        'type' => NULL,
        'settings' => [],
        'third_party_settings' => [],
      ];
    } else {
      $config[$region][$field]['type'] = NULL;
      $config[$region][$field]['settings'] = [];
      $config[$region][$field]['third_party_settings'] = [];
    }

    \Drupal::configFactory()
      ->getEditable($config_name)
      ->setData($config)
      ->save();
  }

  /**
   * @Given I enable the display of the :field field of the :bundle bundle of :entity entity
   * @Given I enable the display of the :field field of the :bundle bundle of :entity entity in :view_mode view mode
   */
  public function iEnableTheDisplayOfTheFieldOfTheBundleOfEntity($field, $bundle, $entity, $view_mode = 'default')
  {
    $config_name = sprintf('core.entity_view_display.%s.%s.%s', $entity, $bundle, $view_mode);

    $config = \Drupal::configFactory()
      ->getEditable($config_name)
      ->getRawData();

    unset($config['hidden'][$field]);

    $config['content'][$field] = [
      'region' => 'content',
      'type' => NULL,
      'settings' => [],
      'third_party_settings' => [],
    ];

    \Drupal::configFactory()
      ->getEditable($config_name)
      ->setData($config)
      ->save();
  }

  /**
   * @Given I hide the display of the :field field of the :bundle bundle of :entity entity
   * @Given I hide the display of the :field field of the :bundle bundle of :entity entity in :view_mode view mode
   */
  public function iHideTheDisplayOfTheFieldOfTheBundleOfEntity($field, $bundle, $entity, $view_mode = 'default')
  {
    $config_name = sprintf('core.entity_view_display.%s.%s.%s', $entity, $bundle, $view_mode);

    $config = \Drupal::configFactory()
      ->getEditable($config_name)
      ->getRawData();

    if (!isset($config['content'][$field])) {
      // @todo Throw an error.
      return;
    }

    unset($config['content'][$field]);

    $config['hidden'][$field] = TRUE;

    \Drupal::configFactory()
      ->getEditable($config_name)
      ->setData($config)
      ->save();
  }

  /**
   * @Given I set the :formatter formatter to the field :field of the :bundle bundle of :entity entity
   * @Given I set the :formatter formatter to the :field field of the :bundle bundle of :entity entity in :view_mode view mode
   */
  public function iSetTheFormatterToTheFieldOfTheBundleOfEntity($formatter, $field, $bundle, $entity, $view_mode = 'default')
  {
    $config_name = sprintf('core.entity_view_display.%s.%s.%s', $entity, $bundle, $view_mode);

    $config = \Drupal::configFactory()
      ->getEditable($config_name)
      ->getRawData();

    $config['content'][$field] = [
      'region' => 'content',
      'type' => $formatter,
      'settings' => [],
      'third_party_settings' => [],
    ];

    \Drupal::configFactory()
      ->getEditable($config_name)
      ->setData($config)
      ->save();
  }

}
