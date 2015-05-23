<?php

/*
 * class description
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    1.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Participants_Db class
 * 
 * This class provides the methods for building and altering the list query object. 
 * The class is designed to allow for a hierarchical series of filter to be applied, 
 * each overriding the next on a field-by-field basis.
 * 
 */
if ( ! defined( 'ABSPATH' ) ) die;
class PDb_List_Query {
  /**
   * @var array of where clause sets
   * 
   * array of where clause sets, indexed by the field name
   * 
   * array( $field_name => array(  List_Query_Filter object, ... ), ... )
   */
  private $subclauses = array();
  /**
   * @var array of sort terms
   */
  private $sort;
  /**
   * @var array of column names
   */
  private $columns;
  /**
   * @var bool true if current statement is whithin parnentheses
   */
  private $inparens;
  /**
   * @var bool true if the list is to be suppressed when no search is present
   */
  public $suppress;
  /**
   * @var int the total number of where clause elements
   */
  private $clause_count = 0;
  /**
   * this property is primarily used to set the values in the search controls of 
   * the user interface
   *
   * @var array of search fields and search values
   */
  public $filter;
  /**
   * @var array List class translation strings
   */
  var $i18n;
  /**
   * @var the sanitized post array
   */
  private $post_input;
  /**
   * @var the sanitized get array
   */
  private $get_input;
  /**
   * 
   * @var string name of the query session value
   */
  static $query_session = 'list_query';
  /**
   * @var bool true if current query is a search result
   */
  private $is_search_result = false;
  /**
   * each clause, as it's added to the where_clauses property, is given a sequential 
   * index. This index is used to reconstruct the filters into their original sequence 
   * so parenthesization will follow from the original filter sequence
   * @version 1.6
   * 
   * @var int index number for a clause
   */
  private $clause_index = 0;
  /**
   * holds the instance index value of the instantiating list instance
   */
  private $list_instance;
  /**
   * @var object the instantiating List class instance
   */
  /**
   * construct the object
   * 
   * @param PDb_List object $List
   * 
   * @param array  $shortcode_atts array of shortcode attributes
   *                 filter      a shortcode filter string
   *                 orderby     a comma-separated list of fields
   *                 order       a comma-separated list of sort directions, correlates 
   *                             to the $sort_fields argument
   *                 suppress    if true, the query should return zero results if no search is used
   * @param array  $columns      an array of column names to use in the SELECT statement
   * @param array  $i18n         translation strings from the List class
   */
  function __construct(PDb_List $List)
  {
    $this->list_instance = $List->instance_index;
    $this->i18n = $List->i18n;
    $this->_reset_filters();
    $this->set_sort($List->shortcode_atts['orderby'], $List->shortcode_atts['order']);
    $this->_set_columns($List->display_columns);
    $this->suppress = filter_var($List->shortcode_atts['suppress'], FILTER_VALIDATE_BOOLEAN);
    $this->_add_filter_from_shortcode_filter($List->shortcode_atts['filter']);
    /*
     * at this point, the object has been instantiated with the properties provided 
     * in the shortcode
     * 
     * now, we process the GET and POST arrays to arrive at the final query structure 
     * 
     * a working GET request must contain these three values to return a search result:
     *    search_field - the name of the field to search in
     *    value - the search string, can be empty depending on settings
     *    operator - one of the valid operators: ~,!,ne,eq,lt,gt
     * you cannot perform a search and specify a page number in the same GET request, 
     * the page number will get that page of the last query
     * 
     * a POST request will override any GET request on the same field, the two are 
     * not really meant to be combined
     */
    $this->_add_filter_from_get();
    $this->_add_filter_from_post();
    
    /*
     * if we're getting a paginated set of records, get the stored session, if not, 
     * and we are searching, save the session
     */
    if (!empty($this->get_input[Participants_Db::$list_page])) {
      $this->_restore_query_session();
    } elseif (filter_input(INPUT_POST, 'action') === 'pdb_list_filter' && $this->is_search_result) {
      $this->_save_query_session();
    }
  }
  /**
   * provides the completed mysql query
   * 
   * @return string the query
   */
  public function get_list_query() {
    $query = 'SELECT ' . $this->_column_select() . ' FROM ' . Participants_Db::$participants_table . ' p';
    $query .= $this->_where_clause();
    $query .= ' ORDER BY ' . $this->_order_clause();
    
    return $query;
  }
  /**
   * provides the completed mysql query for getting a records returned count
   * 
   * @return string the query
   */
  public function get_count_query() {
    $query = 'SELECT COUNT(*) FROM ' . Participants_Db::$participants_table . ' p';
    return $query . ' ' . $this->_where_clause();
  }
  /**
   * checks for user search errors
   * 
   * @return string|bool key string for the type of error or false if no error
   */
  public function get_search_error() {
    if ( $this->is_search_result() && $this->post_input['submit'] === 'search' ) {
      if (empty($this->post_input['search_field'])) {
        $this->is_search_result(false);
        return 'search';
      } elseif (empty($this->post_input['value'])) {
        $this->is_search_result(false);
        return 'value';
      }
    }
    return false;
  }
  /**
   * sets the sort property, given a set of fields and sort directions
   * 
   * @param array|string $fields array or comma-separated string of field names
   * @param array|string $ascdesc array or comma-separated list of sort direction strings
   * @return null
   */
  public function set_sort($fields, $ascdesc) {
    $this->sort = array();
    $fields = $this->_to_array($fields);
    $ascdesc = $this->_to_array($ascdesc);
    for ($i = 0;$i < count($fields);$i++) {
      if (!empty($fields[$i])) {
        $this->sort[$fields[$i]] = array(
            'field' => $fields[$i],
            'ascdesc' => (strtolower($ascdesc[$i]) === 'asc' ? 'ASC' : 'DESC')
            );
      }
    }
  }
  /**
   * provides current filter information
   * 
   * this is primarily used to pouplate the search form with submitted values
   * 
   * @param string $key the value to return, if not provided, returns the whole array
   * @return string|array value from the current filter
   */
  public function current_filter($key = false) {
    
    $this->_setup_filter_array();
    reset($this->sort);
    reset($this->filter);
    $sort = current($this->sort);
    $filter = array(
        'sort_field' => $sort['field'],
        'sort_order' => $sort['ascdesc'],
        'search_field' => current($this->filter['fields']),
        'search_term' => current($this->filter['values']),
        'search_fields' => $this->filter['fields'],
        'search_values' => $this->filter['values'],
        'sort_fields' => $this->sort,
    );
    if (!$key) return $filter;
    else return isset($filter[$key]) ? $filter[$key] : '';
  }
  /**
   * returns true if the current query is a search result
   * 
   * @param bool $set allows the value to be set by the instantiating class
   */
  public function is_search_result($set = '') {
    if (is_bool($set)) {
      $this->is_search_result = $set;
    }
    return $this->is_search_result;
  }
  /**
   * clears all filters from a field
   * 
   * @param string $fieldname
   */
  public function clear_field_filter($fieldname) {
    $this->_remove_field_filters($fieldname);
  }
  /**
   * provides current filter information for a field
   * 
   * returns the array of filters defined for a field
   * 
   * TODO: develop use cases for this public function to see if it's useful
   * 
   * @param string $field_name
   * @return array|bool of List_Query_Filter objects, false if none are defined
   */
  public function get_field_filters($field_name = false) {
    if ($field_name) {
      return isset($this->subclauses[$field_name]) ? $this->subclauses[$field_name] : false;
    }
    return isset($this->subclauses) ? $this->subclauses : false;
  }
  /**
   * adds a filter to a field
   * 
   * @param string $field the field name
   * @param string $operator the filter operator
   * @param string $term the search term used (optional)
   * @param string $logic the AND|OR logic to use
   */
  public function add_filter($field, $operator, $term, $logic = 'AND') {
    
    $this->_add_single_statement(
            filter_var($field, FILTER_SANITIZE_STRING),
            $this->_sanitize_operator($operator),
            filter_var($term, FILTER_SANITIZE_STRING),
            ($logic === 'OR' ? 'OR' : 'AND'),
            false
            );
  }
  /**
   * removes background clauses from the where clauses for fields that have incoming searches
   * 
   * @var string $field the field name
   * @return null
   */
  public function clear_background_clauses($field) {
    if (isset($this->subclauses[$field]) && is_array($this->subclauses[$field])) {
      foreach ($this->subclauses[$field] as $index => $clause) {
      if ($clause->is_shortcode()) {
          unset($this->subclauses[$field][$index]);
      }
    }
  }
  }
  /**
   * adds where clauses and sort from the GET array
   * 
   * @return null
   */
  private function _add_filter_from_get() {
    $this->get_input = filter_input_array(INPUT_GET, self::single_search_input_filter());
    if (isset($this->get_input)) {
      $this->_add_filter_from_input($this->_prepare_submit_value($this->get_input));
    }
  }
  /**
   * adds where clauses and sort from POST array
   * 
   * @return null
   */
  private function _add_filter_from_post() {
    
    if (! Participants_Db::nonce_check(filter_input(INPUT_POST, 'filterNonce', FILTER_SANITIZE_STRING), PDb_List::$list_filter_nonce_key)) {
      return null;
    }
    
    // look for the identifier of the list search submission
    if (filter_input(INPUT_POST, 'action') === 'pdb_list_filter') {
      if (isset($_POST['search_field']) && is_array($_POST['search_field'])) {
        // process a multi search
        $this->post_input = filter_input_array(INPUT_POST, self::multi_search_input_filter());
      } else {
        $this->post_input = filter_input_array(INPUT_POST, self::single_search_input_filter());
        if ($this->post_input['search_field'] === 'none' ) {
          $this->post_input['search_field'] = '';
        }
      }
      // accomodate several different submit button names and un-translate to get key values
      $this->post_input = $this->_prepare_submit_value($this->post_input);
      
      
      switch ($this->post_input['submit']) {
        case 'clear':
          $_GET[Participants_Db::$list_page] = 1;
          $this->is_search_result = false;
          $this->_clear_query_session();
          break;
        case 'search':
        case 'sort':
        case 'page':
          $this->_add_filter_from_input($this->post_input);
          break;
      }
    }
  }
  /**
   * provides the mysql where clause
   * 
   * returns a suppressing query if the list is to be suppressed
   * 
   * @return string the where clause
   */
  private function _where_clause() {
    
    $this->_set_up_clauses();
    $query = '';
    if ($this->suppress && !$this->is_search_result) {
      $query .= ' WHERE p.id = "0"';
    } elseif ($this->clause_count > 0) {
      $query .= ' WHERE ' . $this->_build_where_clause();
    }
    return $query;
  }
  /**
   * provides a mysql where clause drawn from the where_clauses property
   * 
   * @return string
   */
  private function _build_where_clause() {
    
    
    $subquery = '';
    $inparens = false;
    $clause_sequence = $this->_build_clause_sequence();
    //error_log(__METHOD__.' '.print_r($clause_sequence,1));
    /*
     * each element in the where_clauses property array is an array of statements 
     * acting on a single field; The key is the name of the field.
     */
    foreach ($clause_sequence as $clause) {
      $last_clause = $clause->index() === count($clause_sequence) - 1;
      /*
       * each element in the field clauses array is a PDb_List_Query_Filter object 
       * representing a statment acting on a particular field
       */
      if ($clause->is_or() && !$inparens) {
        $subquery .= '(';
          $inparens = true;
        }
        $subquery .= $clause->statement();
      if ($clause->is_or() && !$last_clause) {
        $subquery .= ' OR ';
        }
      if ($inparens && (!$clause->is_or() || $last_clause)) {
        $subquery .= ')';
            $inparens = false;
          }
      if (!$clause->is_or() && !$last_clause) {
        $subquery .= ' AND ';
      }
    }
    return $subquery;
  }
  /**
   * takes the where_clauses property and builds a sequence of clauses for processing
   * 
   * the where_clauses property is organized by field so that we can override the 
   * filters that are applied to a specific field, but the order in which the filters 
   * were submitted is important to correctly parenthesizing the resulting SQL, so 
   * before assembling the where clause SQL, we build a re-sequenced array of clauses 
   * according to how they were originally submitted
   * 
   * @return array a sequence of filter objects
   */
  private function _build_clause_sequence() {
    $sequence = array();
    foreach ($this->subclauses as $field_clauses) {
      foreach ($field_clauses as $clause) {
        $sequence[$clause->index()] = $clause;
      }
    }
    ksort($sequence);
    return $sequence;
  }
  /**
   * provides an order clause
   * 
   * @return string mysql order clause
   */
  private function _order_clause() {
    if (count($this->sort) === 0) {
      return '';
    }
    $subquery = array();
    $random = false;
    foreach($this->sort as $sort) {
      extract($sort); // yields $field and $ascdesc
      if ($field === 'random') {
        $random = true;
      }
      $subquery[] = $field . ' ' . $ascdesc;
    }
    if ($random) {
      return 'RAND()';
    } else {
      return 'p.' . implode(', p.',$subquery);
    }
  }
  /**
   * sets the columns property
   * 
   * @param array $columns array of column names
   * @return null
   */
  private function _set_columns($columns) {
    if (is_array($columns)) {
      array_unshift($columns, 'id');
      $this->columns = $columns;
    }
  }
  /**
   * provides a column select string
   * 
   * @return string the mysql select string
   */
  private function _column_select() {
    if (empty($this->columns)) {
      return '*';
    }
    return 'p.' . implode( ', p.', $this->columns);
  }
  /**
   * adds a filter statement from an input array
   * 
   * @param array $input from GET or POST array
   * @return null
   */
  private function  _add_filter_from_input($input) {
    
    $this->_reset_filters();
    if ($input['target_instance'] == $this->list_instance) { 
			if (empty($input['sortstring'])) {
				$input['sortstring'] = $input['sortBy'];
				$input['orderstring'] = $input['ascdesc'];
			}
			if (is_array($input['search_field'])) {
				for ($i = 0;$i < count($input['search_field']);$i++) {
					$search_field = $input['search_field'][$i];
					$this->_remove_field_filters($search_field);
					$this->_add_single_statement($search_field, $input['operator'][$i], $input['value'][$i]);
				}
				$this->is_search_result = true;
			} elseif (!empty($input['search_field'])) {
				$this->_remove_field_filters($input['search_field']);
				$this->_add_single_statement($input['search_field'], $input['operator'], $input['value']);
				$this->is_search_result = true;
			} elseif ($input['submit'] !== 'clear' && empty($input['value'])) {
				// we set this to true even for empty searches
				$this->is_search_result = true;
			}
			if (!empty($input['sortstring'])) {
				$this->set_sort($input['sortstring'], $input['orderstring']);
			}
      $this->_save_query_session();
    }
  }
  /**
   * clears the filter statements for a single column
   * 
   * @param string $column
   */
  private function _remove_field_filters($column) {
    unset($this->subclauses[$column]);
  }
  /**
   * sets the is search value
   * 
   * @return null
   */
  private function _set_search_status() {
    if ($this->post_input['submit'] == 'search' || !empty($this->get_input['search_field'])) {
        $this->is_search_result = true;
    } else {
        $this->is_search_result = false;
    }
  }
  /**
   * processes and sanitizes a search term
   * 
   * @param string $term the search term
   * @return string the prepared search term
   */
  private function prep_like_search_term($term) {
    
  }
  /**
   * resets the filter arrays
   * 
   * @return null
   */
  private function _reset_filters() {
    $this->filter['fields'] = array();
    $this->filter['values'] = array();
  }
  /**
   * adds values to the filter
   * 
   * @param string $field the field name
   * @param string $value the search value
   * @return null
   */
  private function _add_filter_value($field, $value) {
    $this->filter['fields'][] = $field;
    $this->filter['values'][] = $value;
  }
  /**
   * sets up the filter values from the query object
   * 
   * @return null
   */
  private function _setup_filter_array() {
    $this->_reset_filters();
    
    foreach($this->subclauses as $field_name => $filters) {
      foreach($filters as $filter) {
        /*
         * include the filter if it is a search filter
         * 
         * shortcode filter statements are not included here; that would expose 
         * them as these values are used to populate the search form
         */
        if ($filter->is_search()) {
          $this->_add_filter_value($field_name, $filter->get_raw_term());
        }
      }
    }
  }
  /**
   * processes a shortcode filter string, adding the clauses to the query object
   * 
   * @param string $filter_string a shortcode filter string
   * @return null
   */
  private function _add_filter_from_shortcode_filter($filter_string = '') {
    
    if (!empty($filter_string)) {

      $statements = preg_split('/([&|])/', html_entity_decode($filter_string), -1, PREG_SPLIT_DELIM_CAPTURE);
      $logic = 'AND';
        
      for ($i = 0;$i < count($statements);$i = $i + 2) {
          
        if (isset($statements[$i+1])) {
          $logic = $statements[$i+1] === '|' ? 'OR' : 'AND';
          }
          
        $this->_add_statement_from_filter_string($statements[$i], $logic);
        
      }// each $statement
      
    }// done processing shortcode filter statements
  }
  /**
   * parses a shortcode filter statement string and adds it to the where_clauses property
   * 
   * typical filter string contains subject - operator - value
   * 
   * 
   * @param string $statement a single statement drawn from the shortcode filter string
   * @param string $logic the logic term to add to the array
   * @return null
   * 
   */
  private function _add_statement_from_filter_string($statement, $logic = 'AND') {

    $operator = preg_match('#^([^\2]+)(\>|\<|=|!|~)(.*)$#', $statement, $matches);

    if ($operator === 0)
      return false; // no valid operator; skip to the next statement
    
    // get the parts
    list( $string, $column, $op_char, $search_term ) = $matches;
    
    $this->_add_single_statement($column, $op_char, $search_term, $logic, true);
  }
  /**
   * adds a List_Query_Filter object to the where clauses
   * 
   * @param string $column      the name of the field to target
   * @param string $operator     the operator
   * @param string $search_term the term to filter by
   * @param string $logic       the logic term to add to the array
   * @param bool   $shortcode   true if the current filter is from the shortcode
   * @return null
   */
  private function _add_single_statement($column, $operator, $search_term = '', $logic = 'AND', $shortcode = false) {
    
    /*
     * don't add an 'id = 0' clause if there is a user search. This gives us a 
     * way to create a "search results only" list if the shortcode contains 
     * a filter for 'id=0'
     * 
     * we flag it for suppression. Later, if there is no other clause for the ID 
     * column, the list display will be suppressed
     */
    if ($column == 'id' and $search_term == '0') {
      $this->suppress = true;
      return false;
    }

    /*
     * if the column is not valid skip this statement
     */
    if (!Participants_Db::is_column($column))
      return false;

    $field_atts = Participants_Db::get_column($column);
    
    $filter = new PDb_List_Query_Filter(array(
        'field' => $column,
        'logic' => $logic,
        'shortcode' => $shortcode,
        'term' => trim(urldecode($search_term)),
        'index' => $this->clause_index,
            )
    );

    // increment the index value
    $this->clause_index++;

    $statement = false;

    /*
     * set up special-case field types
     */
    if (in_array($field_atts->form_element, array('date', 'timestamp')) and $filter->is_string_search()) {

      /*
       * if we're dealing with a date element, the target value needs to be 
       * conditioned to get a correct comparison
       */
      $search_term = Participants_Db::parse_date($filter->get_raw_term(), $field_atts);

      // if we don't get a valid date, skip this statement
      if ($search_term === false)
        return false;

      $operator = in_array($operator, array('>','<')) ? $operator : '=';
      if ($field_atts->form_element == 'timestamp') {
        //$statement = 'DATE(p.' . $column . ') ' . $operator . ' CONVERT_TZ(FROM_UNIXTIME(' . $search_term . '), @@session.time_zone, "+00:00") ';
        $statement = 'DATE(p.' . $column . ') ' . $operator . ' DATE(FROM_UNIXTIME(' . $search_term . ')) ';
      } else {
        $statement = 'p.' . $column . ' ' . $operator . ' CAST(' . $search_term . ' AS SIGNED)';
      }
      
    } elseif ($filter->is_empty_search()) {
      
      if ($operator === 'NOT LIKE' or $operator === '!') {
        $pattern = '(p.%1$s IS NOT NULL AND p.%1$s <> "")';
      } else {
        $pattern = '(p.%1$s IS NULL OR p.%1$s = "")';
      }
      $statement = sprintf($pattern, $column);
      
    } else {
    
    	if ($operator === NULL)
    		$operator = 'LIKE';
    
      $delimiter = array('"', '"');

      /*
       * set the operator and delimiters
       */
      switch ($operator) {

        case '~':
        case 'LIKE':

          $operator = 'LIKE';
          $delimiter = $filter->wildcard_present() ? array('"','"') : array('"%', '%"');
          break;

        case '!':
        case 'NOT LIKE':

          $operator = 'NOT LIKE';
          $delimiter = $filter->wildcard_present() ? array('"','"') : array('"%', '%"');
          break;
        
        case 'ne':
        case '!=':
        case '<>':
          
          $operator = '<>';
          break;

        case 'eq':
        case '=':

          /*
           * if the field's exact value will be found in an array (actually a 
           * serialized array), we must prepare a special statement to search 
           * for the double quotes surrounding the value in the serialization
           */
          if (in_array($field_atts->form_element, array('multi-checkbox', 'multi-select-other', 'link', 'array'))) {
            $delimiter = array('\'%"', '"%\'');
            $operator = 'LIKE';
            /* 
             * this is so the search term will be treated as a comparison string 
             * in a LIKE statement
             */
            $filter->like_term = true; 
          } elseif ($filter->wildcard_present()) {
            $operator = 'LIKE';
          } else {
            $operator = '=';
          }
          break;
          
        case 'gt':
        case '>':
          
          $operator = '>=';
          break;
        
        case 'lt':
        case '<':
          
          $operator = '<';
          break;

        default:
          // invalid operator: don't add the statement
          return false;
      }
      $statement = sprintf('p.%s %s %s%s%s', $column, $operator, $delimiter[0], $filter->get_term(), $delimiter[1]);
    }
    if ($statement) {
      $filter->update_parameters(array('statement' => $statement));
      
      $this->subclauses[$column][] = $filter;
    }
  }
  
  /**
   * counts the where clauses
   * 
   * @return null
   */
  private function _set_up_clauses() {
    $count = 0;
    foreach($this->subclauses as $field) {
      if (is_array($field)) {
        foreach ($field as $clause) {
          $count++;
        }
      }
    }
    $this->clause_count = $count;
  }
  
  /**
   * converts a comma-separated string into an array
   * 
   * @param string $list
   * @return array
   */
  private function _to_array($list) {
    if (!is_array($list)) {
      if (is_string($list)) {
        $list = explode(',', str_replace(' ','',$list));
      } else {
        $list = array();
      }
    }
    return $list;
  }
  /**
   * tests for an empty value
   * 
   * @param array|string $value
   */
  public function is_empty($value) {
    if (is_array($value)) {
      $value = implode('',$value);
    }
    if (!is_string($value)) {
      $value = '';
    }
    return $value === '';
  }
  /**
   * saves the query object into a session variable
   * 
   * this is to support pagination
   * 
   * @return null
   */
  private function _save_query_session()
  {
    $this->_clear_query_session();
    
    Participants_Db::$session->set($this->query_session_name(), array(
        'where_clauses' => serialize($this->subclauses),
        'sort' => serialize($this->sort),
        'clause_count' => $this->clause_count,
        'is_search' => $this->is_search_result
    ));
  }
  /**
   * public method for saving the query session
   */
  public function save_query_session() {
    $this->_save_query_session();
  }
  /**
   * sets the query session
   * 
   * this is used to restore an instance from another target list
   * 
   * @param int $index the list instance to restore the query session from
   * 
   * @return bool false if no query session is found matching the supplied index
   */
  public function set_query_session($index) {
    return $this->_restore_query_session();
  }
  /**
   * clears the query session
   * 
   * @return null
   */
  private function _clear_query_session() {
   Participants_Db::$session->clear($this->query_session_name());
  }
  /**
   * restores the query session
   * 
   * @return bool true is valid session was found
   */
  private function _restore_query_session() {
    
    $data = Participants_Db::$session->get($this->query_session_name());
    
    $where_clauses = maybe_unserialize($data['where_clauses']);
    $sort = maybe_unserialize($data['sort']);
    $this->clause_count = $data['clause_count'];
    $this->is_search_result = $data['is_search'];
    if (is_array($where_clauses)) {
      $this->subclauses = $where_clauses;
    } else return false;
    if (is_array($sort)) {
      $this->sort = $sort;
    }
    return true;
  }
  /**
   * adds the instance index value to the query session name
   * 
   * this is so multiple query session scan be stored and retrieved according to 
   * the instance index
   * 
   * @param int $index the index value if not using current instance
   * 
   * @return string
   */
  private function query_session_name($index = '') {
    return self::$query_session . empty($index) ? $this->list_instance : $index;
  }
  /**
   * prepares a submission input array for use as a filter configuration
   * 
   * allows for the use of several different submit button names
   * converts translated submit button value to key string
   * 
   * @param array $input the input array
   * @retun array
   */
  private function _prepare_submit_value($input) {
    $submit = $input['submit'];
    if (!empty($input['submit_button'])) {
      $submit = $input['submit_button'];
    } elseif (!empty($input['submit-button'])) {
      $submit = $input['submit-button'];
    }
    unset($input['submit-button'], $input['submit_button']);
    $input['submit'] = $this->untranslate_value($submit);
    return $input;
  }
  /**
   * untranslates the submit value
   * 
   * @param string $value the submit value
   * 
   * @return string the key or untranslated value
   */
  private function untranslate_value($value) {
    if ($key = array_search($value, $this->i18n)) {
      $value = $key;
    }
    return $value;
  }
  /**
   * sanitizes the operator value from a POST input
   * 
   * @param string $operator
   * @return string the sanitized operator
   */
  private function _sanitize_operator($operator) {
    switch ($operator) {
      case '~':
      case 'LIKE':
        $operator = 'LIKE';
        break;
      case '!':
      case 'NOT LIKE':
        $operator = 'NOT LIKE';
        break;
      case 'ne':
      case '!=':
      case '<>':
        $operator = '<>';
        break;
      case 'eq':
      case '=':
        $operator = '=';
        break;
      case 'gt':
        $operator = '>=';
        break;
      case 'lt':
      case '<':
        $operator = '<';
        break;
      default:
        $operator = '=';
    }
    return $operator;
  }
  /**
   * provides a filter array for a single-column filter
   * 
   * @return array
   */
  public static function single_search_input_filter() {
    return array_merge(array(
        'value' => array(
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_FLAG_NO_ENCODE_QUOTES
            ),
        'search_field' => array(
            'filter' => FILTER_CALLBACK,
            'options' => array( __CLASS__, 'sanitize_search_field')
        ),
        'operator' => FILTER_SANITIZE_STRING,
        'sortstring' => FILTER_SANITIZE_STRING,
        'orderstring' => FILTER_SANITIZE_STRING,
        ), self::_common_search_input_filter()
    );
  }
  /**
   * provides a filter array for a multiple-column filter
   * 
   * @return array
   */
  public static function multi_search_input_filter() {
    $array_filter = array(
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_FLAG_NO_ENCODE_QUOTES
        );
    return array_merge(array(
        'value' => $array_filter,
        'search_field' => $array_filter,
        'operator' => $array_filter,
        'logic' => $array_filter,
        ), self::_common_search_input_filter()
    );
  }
  /**
   * supplies a common input filter array
   * 
   */
  private static function _common_search_input_filter() {
    return array(
        'submit' => FILTER_SANITIZE_STRING,
        'submit_button' => FILTER_SANITIZE_STRING,
        'submit-button' => FILTER_SANITIZE_STRING,
        'ascdesc' => FILTER_SANITIZE_STRING,
        'sortBy' => FILTER_SANITIZE_STRING,
        Participants_Db::$list_page => FILTER_VALIDATE_INT,  
        'target_instance' => FILTER_VALIDATE_INT,
    );
  }
  /**
   * sanitizes the search_field value
   * 
   * @param string $field the name of a field
   * @return string the sanitized value
   */
  public static function sanitize_search_field($field) {
    if ($field === 'none') {
      $field = '';
    }
    return preg_replace('#[^\p{L}\p{N}_]#u', '', strtolower($field));
  }

}

