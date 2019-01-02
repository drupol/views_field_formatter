<?php

declare(strict_types = 1);

namespace Drupal\views_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Class ViewsFieldFormatter.
 *
 * @FieldFormatter(
 *   id = "views_field_formatter",
 *   label = @Translation("View"),
 *   description = @Translation("Todo"),
 *   weight = 100,
 *   field_types = {
 *     "boolean",
 *     "changed",
 *     "comment",
 *     "computed",
 *     "created",
 *     "datetime",
 *     "decimal",
 *     "email",
 *     "entity_reference",
 *     "entity_reference_revisions",
 *     "expression_field",
 *     "file",
 *     "float",
 *     "image",
 *     "integer",
 *     "language",
 *     "link",
 *     "list_float",
 *     "list_integer",
 *     "list_string",
 *     "map",
 *     "path",
 *     "string",
 *     "string_long",
 *     "taxonomy_term_reference",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "timestamp",
 *     "uri",
 *     "uuid"
 *     }
 * )
 */
class ViewsFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view' => '',
      'arguments' => [],
      'hide_empty' => FALSE,
      'multiple' => FALSE,
      'implode_character' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $options = [];
    foreach (Views::getAllViews() as $view) {
      foreach ($view->get('display') as $display) {
        $label = $view->get('label');

        $options[$label][$view->get('id') . '::' . $display['id']] =
          \sprintf('%s - %s', $label, $display['display_title']);
      }
    }

    if ([] === $options) {
      $element['help'] = [
        '#markup' => '<p>' . $this->t('No available Views were found.') . '</p>',
      ];

      return $element;
    }

    $default_arguments = \array_filter(
      $this->getSetting('arguments'),
      function ($argument) {
        return $argument['checked'];
      }
    );

    if (NULL === $form_state->get('arguments')) {
      $form_state->set('arguments', \count($default_arguments));
    }

    $userInput = $form_state->getUserInput();

    if (isset($userInput['_triggering_element_value']) && 'Add a new table row' === $userInput['_triggering_element_value']) {
      $form_state->set('arguments', $form_state->get('arguments') + 1);
    }

    $settings = $this->getSettings();
    $settings['arguments'] = \array_values($default_arguments);
    $this->setSettings($settings);

    $element['view'] = [
      '#title' => $this->t('View'),
      '#description' => $this->t("Select the view that will be displayed instead of the field's value."),
      '#type' => 'select',
      '#default_value' => $this->getSetting('view'),
      '#options' => $options,
    ];

    $element['arguments'] = [
      '#prefix' => '<div id="ajax_form_table_arguments">',
      '#suffix' => '</div>',
      '#type' => 'table',
      '#header' => [
        $this->t('View Arguments'),
        $this->t('Weight'),
        '',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'arguments-order-weight',
        ],
      ],
      '#caption' => $this->t(
        'Select the arguments to send to the views, you can reorder them.
         These arguments can be used as contextual filters in the selected View.'
      ),
    ];

    for ($i = 0; $i < $form_state->get('arguments'); $i++) {
      $element['arguments'][] = [
        'checked' => [
          '#type' => 'checkbox',
          '#title' => 'Token',
          '#default_value' => $this->getSettings()['arguments'][$i]['checked'],
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => 'token']),
          '#title_display' => 'invisible',
          '#attributes' => ['class' => ['arguments-order-weight']],
        ],
        'token' => [
          '#type' => 'textfield',
          '#title' => 'Token',
          '#description' => $this->t('String or token'),
          '#default_value' => $this->getSettings()['arguments'][$i]['token'],
        ],
        '#attributes' => ['class' => ['draggable']],
      ];
    }

    $element['addRow'] = [
      '#type' => 'button',
      '#button_type' => 'secondary',
      '#value' => t('Add a new table row'),
      '#ajax' => [
        'callback' => [$this, 'ajaxAddRow'],
        'event' => 'click',
        'wrapper' => 'ajax_form_table_arguments',
      ],
    ];

    $element['hide_empty'] = [
      '#title' => $this->t('Hide empty views'),
      '#description' => $this->t('Do not display the field if the view is empty.'),
      '#type' => 'checkbox',
      '#default_value' => (bool) $this->getSetting('hide_empty'),
    ];

    $element['multiple'] = [
      '#title' => $this->t('Multiple'),
      '#description' => $this->t(
        'If the field is configured as multiple (<em>greater than one</em>),
         should we display a view per item ? If selected, there will be one view per item.'
      ),
      '#type' => 'checkbox',
      '#default_value' => (bool) $this->getSetting('multiple'),
    ];

    $element['implode_character'] = [
      '#title' => $this->t('Implode with this character'),
      '#description' => $this->t(
        'If it is set, all field values are imploded with this character (<em>ex: a simple comma</em>)
         and sent as one views argument. Empty to disable.'
      ),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('implode_character'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' .
          $this->fieldDefinition->getName() .
          '][settings_edit_form][settings][multiple]"]' =>
            ['checked' => TRUE],
        ],
      ],
    ];

    $token_items = [];

    $token = \Drupal::token();

    $info = $token->getInfo();

    $types = ['site', 'user', 'node', 'entity', 'field'];

    $available_token = \array_intersect_key(
      $info['tokens'],
      \array_flip($types)
    );

    foreach ($available_token as $type => $tokens) {
      $item = [
        '#markup' => \ucfirst($type),
        'children' => [],
      ];
      foreach ($tokens as $name => $info) {
        $info += ['description' => $this->t('No description available')];
        $item['children'][$name] = "[$type:$name]" . ' - ' . $info['name'] . ': ' . $info['description'];
      }

      $token_items[$type] = $item;
    }

    $element['global_tokens'] = [
      '#type' => 'details',
      '#title' => $this->t('Available token replacements'),
    ];

    $element['global_tokens']['list'] = [
      '#theme' => 'item_list',
      '#items' => $token_items,
      '#attributes' => [
        'class' => ['global-tokens'],
      ],
    ];

    return $element;
  }

  /**
   * Custom ajax callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element.
   */
  public function ajaxAddRow(array &$form, FormStateInterface $form_state): array {
    /** @var \Drupal\field\FieldConfigInterface $fieldConfig */
    $fieldConfig = $this->fieldDefinition;

    $form_state->setRebuild(TRUE);

    return $form['fields'][$fieldConfig->getName()]['plugin']['settings_edit_form']['settings']['arguments'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    // For default settings, don't show a summary.
    if ('' === $settings['view']) {
      return [
        $this->t('Not configured yet.'),
      ];
    }

    list($view, $view_display) = \explode('::', $settings['view'], 2);
    $multiple = (TRUE === (bool) $settings['multiple']) ? 'Enabled' : 'Disabled';
    $hide_empty = (TRUE === (bool) $settings['hide_empty']) ? 'Hide' : 'Display';

    $arguments = \array_filter(
      $settings['arguments'],
      function ($argument) {
        return $argument['checked'];
      }
    );

    $arguments = \array_map(
      function ($argument) {
        return 'Token';
      },
      \array_keys($arguments)
    );

    if ([] === $arguments) {
      $arguments[] = $this->t('None');
    }

    if (NULL !== $view) {
      $summary[] = t('View: @view', ['@view' => $view]);
      $summary[] = t('Display: @display', ['@display' => $view_display]);
      $summary[] = t('Argument(s): @arguments', ['@arguments' => \implode(', ', $arguments)]);
      $summary[] = t('Empty views: @hide_empty empty views', ['@hide_empty' => $hide_empty]);
      $summary[] = t('Multiple: @multiple', ['@multiple' => $multiple]);
    }

    if ((TRUE === (bool) $settings['multiple']) && ('' !== $settings['implode_character'])) {
      $summary[] = t('Implode character: @character', ['@character' => $settings['implode_character']]);
    }

    return $summary;
  }

  /**
   * Helper function. Returns the arguments to send to the views.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The items.
   * @param mixed $item
   *   The item.
   * @param mixed $delta
   *   The item's delta.
   *
   * @return array
   *   The array.
   */
  private function getArguments(FieldItemListInterface $items, $item, $delta) {
    $settings = $this->getSettings();

    $user_arguments =
      \array_filter(
        $settings['arguments'],
        function ($argument) {
          return $argument['checked'];
        }
      );

    $token = \Drupal::token();

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getParent()->getValue();

    $arguments = [];
    foreach ($user_arguments as $argument) {
      $arguments[] = (array) $token->replace(
        $argument['token'],
        [
          $entity->getEntityTypeId() => $entity,
          'field' => [
            'definition' => $this->fieldDefinition,
            'items' => $items,
            'configuration' => $this->getSettings(),
            'delta' => $delta,
            'item' => $item,
          ],
          'user' => \Drupal::currentUser(),
        ]
      );
    }

    return \array_values($arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();
    $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();

    if (isset($settings['view']) && !empty($settings['view']) && FALSE !== \strpos($settings['view'], '::')) {
      list($view_id, $view_display) = \explode('::', $settings['view'], 2);
    }
    else {
      return $elements;
    }

    $arguments = $this->getArguments($items, $items[0], 0);

    // If empty views are hidden, execute view to count result.
    if (!empty($settings['hide_empty'])) {
      $view = Views::getView($view_id);
      if (!$view || !$view->access($view_display)) {
        return $elements;
      }

      // We try to reproduce the arguments which will be used below. We cannot
      // just use $this->getArguments($items, $items[0], 0) as this might return
      // items, which for example no longer exist, still you want to show the
      // view when there are more possible entries.
      if ((TRUE === (bool) $settings['multiple']) && (1 !== $cardinality)) {
        if (!empty($settings['implode_character'])) {
          $arguments = $this->getArguments($items, NULL, 0);
        }
      }

      $view->setArguments($arguments);
      $view->setDisplay($view_display);
      $view->preExecute();
      $view->execute();

      if (empty($view->result)) {
        return $elements;
      }
    }

    $elements = [
      '#cache' => [
        'max-age' => 0,
      ],
      [
        '#type' => 'view',
        '#name' => $view_id,
        '#display_id' => $view_display,
        '#arguments' => $arguments,
      ],
    ];

    if ((1 !== $cardinality) && (TRUE === (bool) $settings['multiple'])) {
      if (empty($settings['implode_character'])) {
        foreach ($items as $delta => $item) {
          $elements[$delta] = [
            '#type' => 'view',
            '#name' => $view_id,
            '#display_id' => $view_display,
            '#arguments' => $this->getArguments($items, $item, $delta),
          ];
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    list($view_id) = \explode('::', $this->getSetting('view'), 2);
    // Don't call the current view, as it would result into an
    // infinite recursion.
    // TODO: Check for infinite loop here.
    if (NULL !== $view_id && $view = View::load($view_id)) {
      $dependencies[$view->getConfigDependencyKey()][] = $view->getConfigDependencyName();
    }

    return $dependencies;
  }

}
