<?php

/**
 * @file
 * Contains
 *   \Drupal\views_field_formatter\Plugin\Field\FieldFormatter\ViewsFieldFormatter.
 */

namespace Drupal\views_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldFormatter(
 *  id = "views_field_formatter",
 *  label = @Translation("View"),
 *  field_types = {
 *   "boolean",
 *   "changed",
 *   "comment",
 *   "computed",
 *   "created",
 *   "datetime",
 *   "decimal",
 *   "email",
 *   "entity_reference",
 *   "expression_field",
 *   "file",
 *   "float",
 *   "image",
 *   "integer",
 *   "language",
 *   "link",
 *   "list_float",
 *   "list_integer",
 *   "list_string",
 *   "map",
 *   "path",
 *   "string",
 *   "string_long",
 *   "taxonomy_term_reference",
 *   "text",
 *   "text_long",
 *   "text_with_summary",
 *   "timestamp",
 *   "uri",
 *   "uuid"
 *   }
 * )
 */
class ViewsFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'view' => '',
      'arguments' => ['field_value', 'entity_id', 'delta'],
      'multiple' => FALSE,
      'implode_character' => '',
    );
  }

  /**
   * @return array
   */
  protected function getDefaultArguments() {
    return [
      'field_value' => $this->t('Field value'),
      'entity_id' => $this->t('Entity ID'),
      'delta' => $this->t('Delta'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $options = array();
    foreach (\Drupal\views\Views::getAllViews() as $view) {
      foreach ($view->get('display') as $display) {
        $options[$view->get('label')][$view->get('id') . '::' . $display['id']] = $display['display_title'];
      }
    }

    if (!empty($options)) {
      $element['view'] = array(
        '#title' => t('View'),
        '#description' => t('Select the view that will be used to get the value of the field.'),
        '#type' => 'select',
        '#default_value' => $this->getSetting('view'),
        '#options' => $options,
      );

      $element['arguments'] = [
        '#type' => 'table',
        '#header' => [t('Arguments'), $this->t('Weight')],
        '#tabledrag' => [[
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'arguments-order-weight',
        ]],
        '#caption' => $this->t('Select the arguments to send to the views, you can reorder them.'),
      ];

      $default_arguments = array_keys(array_filter($this->getSetting('arguments'), function($argument) {
        return $argument['checked'];
      }));

      $arguments = array_combine($default_arguments, $default_arguments);
      foreach ($this->getDefaultArguments() as $argument_id => $argument_name) {
        $arguments[$argument_id] = $argument_name;
      }
      foreach ($arguments as $argument_id => $argument_name) {
        if (is_array($argument_name)) {
          continue;
        }
        $element['arguments'][$argument_id] = [
          'checked' => [
            '#type' => 'checkbox',
            '#title' => $argument_name,
            '#default_value' => in_array($argument_id, $default_arguments),
          ],
          'weight' => array(
            '#type' => 'weight',
            '#title' => $this->t('Weight for @title', ['@title' => $argument_name]),
            '#title_display' => 'invisible',
            '#attributes' => ['class' => ['arguments-order-weight']],
          ),
          '#attributes' => ['class' => ['draggable']],
        ];
      }

      $element['multiple'] = array(
        '#title' => t('Multiple'),
        '#description' => t('If the field is configured as multiple, should we display a view per item ? If selected, there will be one view per item. The arguments passed to that view are in this order: the field item value, the entity id and the item delta.'),
        '#type' => 'checkbox',
        '#default_value' => boolval($this->getSetting('multiple')),
      );
      $element['implode_character'] = array(
        '#title' => t('Implode with this character'),
        '#description' => t('If it is set, all field values are imploded with this character and sent as one views argument. Empty to disable.'),
        '#type' => 'textfield',
        '#default_value' => $this->getSetting('implode_character'),
        '#states' => array(
          'visible' => array(
            ':input[name="fields[body][settings_edit_form][settings][multiple]"]' => array('checked' => TRUE),
          ),
        ),
      );
    }
    else {
      $element['help'] = array(
        '#markup' => t('<p>No available Views were found.</p>'),
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();
    list($view, $view_display) = explode('::', $settings['view']);
    $multiple = ((bool) $settings['multiple'] === TRUE) ? 'Enabled' : 'Disabled';

    $arguments = array_filter($settings['arguments'], function($argument) {
      return $argument['checked'];
    });

    $all_arguments = $this->getDefaultArguments();
    $arguments = array_map(function($argument) use($all_arguments) {
      return $all_arguments[$argument];
    }, array_keys($arguments));

    if (empty($arguments)) {
      $arguments[] = $this->t('None');
    }

    if (isset($view)) {
      $summary[] = t('View: @view', array('@view' => $view));
      $summary[] = t('Display: @display', array('@display' => $view_display));
      $summary[] = t('Multiple: @multiple', array('@multiple' => t($multiple)));
      $summary[] = t('Argument(s): @arguments', array('@arguments' => implode(', ', $arguments)));
    }

    if ($multiple == 'Enabled') {
      if (!empty($settings['implode_character'])) {
        $summary[] = t('Implode character: @character', array('@character' => $settings['implode_character']));
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $settings = $this->getSettings();
    $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
    list($view, $view_display) = explode('::', $settings['view'], 2);

    if (((bool) $settings['multiple'] === TRUE) && ($cardinality != 1)) {
      if (!empty($settings['implode_character'])) {
        $elements[0] = [
          '#type' => 'view',
          '#name' => $view,
          '#display_id' => $view_display,
          '#arguments' => $this->getArguments($items, NULL, 0),
        ];
      }
      else {
        foreach ($items as $delta => $item) {
          $elements[$delta] = [
            '#type' => 'view',
            '#name' => $view,
            '#display_id' => $view_display,
            '#arguments' => $this->getArguments($items, $item, $delta),
          ];
        }
      }
    }
    else {
      $elements[0] = [
        '#type' => 'view',
        '#name' => $view,
        '#display_id' => $view_display,
        '#arguments' => $this->getArguments($items, $items[0], 0),
      ];
    }

    return $elements;
  }

  /**
   * Helper function. Returns the arguments to send to the views.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   * @param $item
   * @param $delta
   *
   * @return mixed
   */
  private function getArguments(FieldItemListInterface $items, $item, $delta) {
    $settings = $this->getSettings();

    $user_arguments = array_keys(array_filter($settings['arguments'], function($argument) {
      return $argument['checked'];
    }));

    $arguments = [];
    foreach ($user_arguments as $argument) {
      switch ($argument) {
        case 'field_value':
          $column = array_shift(
            array_keys(
              $items->getFieldDefinition()->getFieldStorageDefinition()->getSchema()['columns']
            )
          );
          $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();

          /** @var FieldItemInterface $item */
          $arguments[$argument] = isset($item->getValue()[$column]) ? $item->getValue()[$column] : NULL;

          if (((bool) $settings['multiple'] === TRUE) && ($cardinality != 1)) {
            if (!empty($settings['implode_character'])) {
              $values = array();

              /** @var FieldItemInterface $item */
              foreach ($items as $item) {
                $values[] = isset($item->getValue()[$column]) ? $item->getValue()[$column] : NULL;
              }

              $arguments[$argument] = implode($settings['implode_character'], array_filter($values));
            }
          }
          break;
        case 'entity_id':
          $arguments[$argument] = $items->getParent()->getValue()->id();
          break;
        case 'delta':
          $arguments[$argument] = isset($delta) ? $delta : NULL;
          break;
      }
    }

    return array_values($arguments);
  }

}