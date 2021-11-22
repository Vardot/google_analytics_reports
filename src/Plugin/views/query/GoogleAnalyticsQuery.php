<?php

namespace Drupal\google_analytics_reports\Plugin\views\query;

use Filter\StringFilter\MatchType;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\FilterExpressionList;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy\OrderType;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\google_analytics_reports_api\GoogleAnalyticsReportsApiFeed;

/**
 * Defines a Views query class for Google Analytics Reports API.
 *
 * @ViewsQuery(
 *     id="google_analytics_query",
 *     title=@Translation("Google Analytics Query"),
 *     help=@Translation("Defines a Views query class for Google Analytics Reports API.")
 * )
 */
class GoogleAnalyticsQuery extends QueryPluginBase {
  use StringTranslationTrait;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * An array mapping table aliases and field names to field aliases.
   *
   * @var array
   */
  protected $fieldAliases = [];

  /**
   * An array of fields.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * The default operator to use when connecting the WHERE groups.
   *
   * @var string
   */
  protected $groupOperator = 'AND';

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * A simple array of order by clauses.
   *
   * @var array
   */
  protected $orderby = [];

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * A list of tables in the order they should be added, keyed by alias.
   *
   * @var array
   */
  protected $tableQueue = [];

  /**
   * An array of sections of the WHERE query.
   *
   * Each section is in itself an array of pieces and a flag as to whether
   * or not it should be AND or OR.
   *
   * @var array
   */
  protected $where = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    StateInterface $state,
    MessengerInterface $messenger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->messenger = $messenger;
  }

  /**
   * Add a metric or dimension to the query.
   *
   * @param string $table
   *   NULL in most cases, we could probably remove this altogether.
   * @param string $field
   *   The name of the metric/dimension/field to add.
   * @param string $alias
   *   Probably could get rid of this too.
   * @param array $params
   *   Probably could get rid of this too.
   *
   * @return string
   *   The name that this field can be referred to as.
   */
  public function addField($table, $field, $alias = '', array $params = []) {
    // We check for this specifically because it gets a special alias.
    if (
      $table === $this->view->storage->get('base_table')
      && $field === $this->view->storage->get('base_field')
      && empty($alias)
    ) {
      $alias = $this->view->storage->get('base_field');
    }

    if ($table && empty($this->tableQueue[$table])) {
      $this->ensureTable($table);
    }

    if (!$alias && $table) {
      $alias = $table . '_' . $field;
    }

    // Make sure an alias is assigned.
    $alias = $alias ? $alias : $field;

    // We limit the length of the original alias up to 60 characters
    // to get a unique alias later if its have duplicates.
    $alias = substr($alias, 0, 60);

    // Create a field info array.
    $field_info =
      [
        'field' => $field,
        'table' => $table,
        'alias' => $alias,
      ] + $params;

    // Test to see if the field is actually the same or not. Due to
    // differing parameters changing the aggregation function, we need
    // to do some automatic alias collision detection:
    $base = $alias;
    $counter = 0;

    while (
      !empty($this->fields[$alias])
      && $this->fields[$alias] !== $field_info
    ) {
      $field_info['alias'] = $alias = $base . '_' . ++$counter;
    }

    if (empty($this->fields[$alias])) {
      $this->fields[$alias] = $field_info;
    }

    // Keep track of all aliases used.
    $this->fieldAliases[$table][$field] = $alias;

    return $alias;
  }

  /**
   * Add SORT attribute to the query.
   *
   * @param string $table
   *   NULL, don't use this.
   * @param string $field
   *   The metric/dimensions/field.
   * @param string $order
   *   Either '' for ascending or '-' for descending.
   * @param string $alias
   *   Don't use this yet (at all?).
   * @param array $params
   *   Don't use this yet (at all?).
   */
  public function addOrderBy(
    $table,
    $field = NULL,
    $order = 'ASC',
    $alias = '',
    array $params = []
  ) {
    $this->orderby[] = [
      'field' => $field,
      'direction' => $order,
    ];
  }

  /**
   * Add a filter string to the query.
   *
   * @param string $group
   *   The filter group to add these to; groups are used to create AND/OR
   *   sections of the Google Analytics query. Groups cannot be nested.
   *   Use 0 as the default group.  If the group does not yet exist it will
   *   be created as an AND group.
   * @param string $field
   *   The name of the metric/dimension/field to check.
   * @param mixed $value
   *   The value to test the field against. In most cases, this is a scalar.
   * @param string $operator
   *   The comparison operator, such as =, <, or >=.
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }

    $this->where[$group]['conditions'][] = [
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ViewExecutable $view) {
    $this->moduleHandler->invokeAll('views_query_alter', [$view, $this]);
  }

  /**
   * Builds the necessary info to execute the query.
   */
  public function build(ViewExecutable $view) {
    // Store the view in the object to be able to use it later.
    $this->view = $view;

    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();

    $view->build_info['query'] = $this->query();
    $view->build_info['count_query'] = $this->query(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('messenger')
    );
  }

  /**
   * Make sure table exists.
   *
   * @param string $table
   *   Table name.
   * @param string $relationship
   *   Relationship.
   * @param string $join
   *   Join.
   */
  public function ensureTable($table, $relationship = NULL, $join = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    $account = GoogleAnalyticsReportsApiFeed::service();
    // Initial check to see if we should attempt to run the query.
    if (!$account) {
      // Optionally do not warn users on every query attempt before auth.
      \Drupal::messenger()->addMessage(
        $this->t(
          'You must <a href=":url">authorize your site</a> to use your Google Analytics account before you can view reports.',
          [
            ':url' => Url::fromRoute(
              'google_analytics_reports_api.settings'
            )->toString(),
          ]
        )
      );

      return;
    }

    $query = $view->build_info['query'];
    $count_query = $view->build_info['count_query'];
    $start = microtime(TRUE);
    // Query for total number of items.
    $count_feed = GoogleAnalyticsReportsApiFeed::service()->runReport(
      $count_query
    );

    // Process only if data is available.
    if ($count_feed && $count_feed->getRowCount()) {
      $view->pager->total_items = $count_feed->getRowCount();
      $view->pager->updatePageInfo();
      $feed = GoogleAnalyticsReportsApiFeed::service()->runReport($query);
      $rows = $feed->getRows();

      $views_result = [];
      $count = 0;

      $type_objects = [
        [
          'DIMENSION',
          'getDimensionHeaders',
        ],
        [
          'METRIC',
          'getMetricHeaders',
        ],
      ];

      foreach ($type_objects as $typeobj) {
        [$type, $att] = $typeobj;
        $index = -1;
        $mmap[$type] = array_reduce(
          _to_array($feed->$att())['container'],
          static function ($c, $i) use (&$index) {
            ++$index;

            return $c + [$i->getName() => $index];
          },
          []
        );
      }

      foreach ($rows as $row) {
        $dv = _to_array($row->getDimensionValues())['container'];
        $mv = _to_array($row->getMetricValues())['container'];
        $r = array_map(static function ($i) use ($mv, $dv, $mmap) {
          $f = $i['field'];
          $out = FALSE;
          _ga_convert_dimentrics(
            $f,
            static function () use (&$out, $f, $dv, $mmap) {
              $pos = $mmap['DIMENSION'][$f] ?? FALSE;

              if ($pos === FALSE) {
                return;
              }
              $out = $dv[$pos]->getValue();
            },
            static function () use (&$out, $f, $mv, $mmap) {
              $pos = $mmap['METRIC'][$f] ?? FALSE;

              if ($pos === FALSE) {
                return;
              }
              $out = $mv[$pos]->getValue();
            }
          );

          return $out;
        }, $this->fields);
        $r['index'] = $count;
        $views_result[] = new ResultRow($r);
        ++$count;
      }

      $view->result = isset($views_result) ? $views_result : [];
      $view->execute_time = microtime(TRUE) - $start;

      if ($view->pager->usePager()) {
        $view->total_rows = $view->pager->getTotalItems();
      }

      // Add to build_info['query'] to render query in Views UI query summary
      // area.
      $view->build_info['query'] = print_r(serialize($query), TRUE);
    }
    else {
      // Set empty query instead of current query array to prevent error
      // in Views UI.
      $view->build_info['query'] = '';
    }
  }

  /**
   * Constructor; Create the basic query object and fill with default values.
   *
   * {@inheritdoc}
   */
  public function init(
    ViewExecutable $view,
    DisplayPluginBase $display,
    ?array &$options = NULL
  ) {
    parent::init($view, $display, $options);
    $this->unpackOptions($this->options, $options);
  }

  /**
   * Convert query.
   *
   * To link https://developers.google.com/analytics/devguides/reporting/data/v1/rest/v1beta/properties/runReport.
   *
   * {@inheritdoc}
   * Convert query to https://developers.google.com/analytics/devguides/reporting/data/v1/rest/v1beta/properties/runReport.
   */
  public function query($get_count = FALSE) {
    $available_fields = google_analytics_reports_get_fields();
    $query = [];

    foreach ($this->fields as $field) {
      $field_name = $field['field'];
      _ga_convert_dimentrics(
        $field_name,
        static function () use (&$query, &$field) {
          $query['dimensions'][] = new Dimension([
            'name' => $field['field'],
          ]);
        },
        static function () use (&$query, &$field) {
          $query['metrics'][] = new Metric([
            'name' => $field['field'],
          ]);
        }
      );
    }

    $filters = [];

    if (isset($this->where)) {
      foreach ($this->where as $where_group => $where) {
        foreach ($where['conditions'] as $condition) {
          $field_name = $condition['field'];

          if ($field_name === '.start_date' || $field_name === '.end_date') {
            $query[ltrim($field_name, '.')] = (int) $condition['value'];
          }
          elseif (!empty($available_fields[$field_name])) {
            $filters[$available_fields[$field_name]->type][$where_group][] = [
              $condition['field'],
              $condition['operator'],
              $condition['value'],
            ];
          }
        }

        foreach (['DIMENSION', 'METRIC'] as $type) {
          if (!empty($filters[$type][$where_group])) {
            $filters[$type][$where_group] = [
              $where['type'],
              $filters[$type][$where_group],
            ];
          }
        }
      }
    }

    if ($query['start_date']) {
      $end_date = $query['end_date'] ?? time();
      $start_date = $query['start_date'] ?? time();
      $query['dateRanges'] = [
        new DateRange([
          'start_date' => date('Y-m-d', $start_date),
          'end_date' => date('Y-m-d', $end_date),
        ]),
      ];
      unset($query['start_date'], $query['end_date']);
    }

    if (!empty($filters)) {
      foreach ([['DIMENSION', 'dimensionFilter'], ['METRIC', 'metricFilter']] as $typeobj) {
        [$type, $att] = $typeobj;

        if ($filters[$type] ?? FALSE) {
          $options = ['AND' => ['setAndGroup'], 'OR' => ['setOrGroup']];
          $option = $options[$this->groupOperator];
          [$method] = $option;
          $query[$att] = new FilterExpression([]);
          $dt = [
            'expressions' => array_filter(
              array_map(static function ($i) use ($options) {
                [$groupOperator, $items] = $i;
                $option = $options[$groupOperator];
                [$method] = $option;
                $obj = new FilterExpression([]);
                $dt = [
                  'expressions' => array_filter(
                    array_map(static function ($it) {
                      [$field_name, $operator, $value] = $it;
                      $operator = $operator ? $operator : '=';
                      $origoperator = $operator;

                      $obj = new FilterExpression([]);
                      $operator =
                        [
                          '=' => MatchType::EXACT,
                          '==' => MatchType::EXACT,
                          '!=' => [
                            MatchType::FULL_REGEXP,
                            static function ($value) {
                              $value = preg_quote($value);

                              return "^(?!{$value})$";
                            },
                          ],
                          'contains' => MatchType::CONTAINS,
                          'not' => [
                            MatchType::FULL_REGEXP,
                            static function ($value) {
                              $value = preg_quote($value);

                              return "^((?!{$value}).)*$";
                            },
                          ],
                          'regular_expression' => MatchType::FULL_REGEXP,
                          'not_regular_expression' => FALSE,
                        ][$operator] ?? FALSE;
                      $operator = \is_array($operator)
                        ? $operator
                        : [$operator];
                      $operator += [
                        0,
                        static function ($value) {
                          return $value;
                        },
                      ];
                      [$operator, $callback] = $operator;

                      if (!$operator) {
                        \Drupal::messenger()->addMessage(
                          $this->t(
                            'The chosen operator is not available! Try use regular expression! @op',
                            [
                              '@op' => print_r(
                                [$origoperator, $it, $field_name],
                                1
                              ),
                            ]
                          ),
                          'error'
                        );

                        return FALSE;
                      }
                      $obj->setFilter(
                        new Filter([
                          'field_name' => $field_name,
                          'string_filter' => new StringFilter([
                            'match_type' => $operator,
                            'value' => $callback($value),
                            'case_sensitive' => FALSE,
                          ]),
                        ])
                      );

                      return $obj;
                    }, $items)
                  ),
                ];
                $dt = array_filter($dt);

                if ($dt) {
                  $obj->$method(new FilterExpressionList($dt));
                }
                else {
                  return FALSE;
                }

                return $obj;
              }, $filters[$type])
            ),
          ];

          $dt = array_filter($dt);

          if ($dt) {
            $query[$att]->$method(new FilterExpressionList($dt));
          }
          else {
            unset($query[$att]);
          }
        }
      }
    }

    if (isset($this->orderby)) {
      $query['orderBys'] = array_filter(
        array_map(static function ($it) {
          [$field_name, $direction] = [$it['field'], $it['direction'] ?? 'ASC'];
          $i = new OrderBy([]);
          $i->setDesc($direction === 'DESC');

          _ga_convert_dimentrics(
            $field_name,
            static function () use (&$i, $field_name) {
              $o = new DimensionOrderBy([]);
              $o->setDimensionName($field_name);
              $o->setOrderType(OrderType::ALPHANUMERIC);
              $i->setDimension($o);
            },
            static function () use (&$i, $field_name) {
              $o = new MetricOrderBy([]);
              $o->setMetricName($field_name);
              $i->setMetric($o);
            }
          );

          return $i;
        }, $this->orderby)
      );
    }

    $query['offset'] = $this->offset ?? 0;
    $query['limit'] = $this->limit ?? 100;

    return $query;
  }

}
