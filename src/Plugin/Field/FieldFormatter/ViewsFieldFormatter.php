<?php

declare(strict_types = 1);

namespace Drupal\views_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
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

    // Ensure we clicked the Ajax button.
    $trigger = $form_state->getTriggeringElement();
    if (\is_array($trigger['#array_parents']) && 'addRow' === \end($trigger['#array_parents'])) {
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

    $types = ['site', 'user', 'entity', 'field', 'date'];

    switch ($this->fieldDefinition->getTargetEntityTypeId()) {
      case 'taxonomy_term':
        $types[] = 'term';
        $types[] = 'vocabulary';

        break;

      default:
        $types[] = $this->fieldDefinition->getTargetEntityTypeId();

        break;
    }

    $token = \Drupal::token();
    $info = $token->getInfo();

    $available_token = \array_intersect_key(
      $info['tokens'],
      \array_flip($types)
    );

    $token_items = [];
    foreach ($available_token as $type => $tokens) {
      $item = [
        '#markup' => $this->t('@type tokens', ['@type' => \ucfirst($type)]),
        'children' => [],
      ];

      foreach ($tokens as $name => $info) {
        $info += [
          'description' => $this->t('No description available'),
        ];

        $item['children'][$name] = \sprintf('[%s:%s] - %s: %s', $type, $name, $info['name'], $info['description']);
      }

      $token_items[$type] = $item;
    }

    $element['token_tree_link'] = [
      '#type' => 'details',
      '#title' => $this->t('Available token replacements'),
      'description' => [
        '#markup' => $this->t('To have more tokens, please install the <a href="@token">Token contrib module</a>.', ['@token' => 'https://drupal.org/project/token']),
      ],
    ];

    $element['token_tree_link']['list'] = [
      '#theme' => 'item_list',
      '#items' => $token_items,
      '#attributes' => [
        'class' => ['global-tokens'],
      ],
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['token_tree_link'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => $types,
      ];
    }

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
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getParent()->getValue();

    switch ($this->fieldDefinition->getTargetEntityTypeId()) {
      case 'taxonomy_term':
        $replacements['term'] = $entity;
        $replacements['vocabulary'] = Vocabulary::load($entity->getVocabularyId());

        break;

      default:
        $replacements[$entity->getEntityTypeId()] = $entity;

        break;
    }

    $token = \Drupal::token();

    return \array_map(
      function ($argument) use ($token, $replacements) {
        return $token->replace(
          $argument['token'],
          $replacements
        );
      },
      \array_filter(
        $this->getSetting('arguments'),
        function ($argument) {
          return $argument['checked'];
        }
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();

    if (isset($settings['view']) && !empty($settings['view']) && FALSE !== \strpos($settings['view'], '::')) {
      list($view_id, $view_display) = \explode('::', $settings['view'], 2);
    }
    else {
      return $elements;
    }

    $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
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
      if ((1 !== $cardinality) && (TRUE === (bool) $settings['multiple'])) {
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
