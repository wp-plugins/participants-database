<?php

/**
 * pagination class for wordpress themes and plugins
 *
 *
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2011, xnau webdesign
 * @license    GPL2
 * @version    1.2
 *
 * adapted from: http://www.goodphptutorials.com/out/Simple_PHP_MySQL_Pagination
 *
 * 08-08-12 added support for bootstrap-style pagination HTML
 *          with methods for setting the class of the current page indicator and an option
 *          to wrap the current page indicator numeral with a dummy anchor tag
 */
class PDb_Pagination {

  /**
   * Current Page
   *
   * @var integer
   */
  var $page;

  /**
   * Size of the records per page
   *
   * @var integer
   */
  var $size;

  /**
   * Total records
   *
   * @var integer
   */
  var $total_records;

  /**
   * Link used to build navigation
   *
   * @var string
   */
  var $link;

  /**
   * Wrapper for the pagination links
   *
   * @var array
   *        'wrap_tag'            tag name for the overall wrapper; default: div
   *        'wrap_class'          classname for the overall wrapper; default: pagination 
   *        'all_button_wrap_tag' tag to wrap the buttons; default: ul
   *        'button_wrap_tag'     tag to wrap each button; default: li
   */
  public $wrappers;

  /**
   * class name for current page link
   *
   * @var string
   */
  private $current_page_class;

  /**
   * class name for a disabled link
   *
   * @var string
   */
  private $disabled_class;

  /**
   * flag to select wrapping dummy anchor tag around current page link
   *
   * @var bool
   */
  private $anchor_wrap;

  /**
   * flag to enable first/last page links
   *
   * @var bool
   */
  private $first_last;

  /**
   * was the object instantiated by a filtering operation?
   * 
   * @var bool
   */
  private $filtering;

  /**
   * Class Constructor
   *
   * @param array $args
   *                'page'          int the current page
   *                'size'          int the number of records to show per page
   *                'total_records' int the total records in the full query
   *                'link'          string the URL for page links
   *                'add_variables' additional GET string to add
   */
  function __construct($args) {
    extract(wp_parse_args($args, array(
                'page' => 1,
                'size' => 10,
                'total_records' => false,
                'link' => '',
                'current_page_class' => 'currentpage',
                'disabled_class' => 'disabled',
                'filtering' => 0,
                'anchor_wrap' => false,
                'first_last' => '',
                'add_variables' => '',
            )));
    $this->setPage($page);
    $this->setTotalRecords($total_records);
    $this->setSize($size);
    $this->setLink($link,$add_variables);
    $this->filtering = $filtering;
    $this->set_wrappers();
    $this->set_anchor_wrap($anchor_wrap);
    $this->current_page_class = $current_page_class;
    $this->disabled_class = $disabled_class;
    $this->first_last = empty($first_last) ? ($total_records/($size == 0 ? 1 : $size) > 5 ? true : false) : ($first_last == 'true' ? true : false); 
  }

  /**
   * returns the pagination links
   *
   */
  public function links() {
    return $this->create_links();
  }

  public function show() {

    echo $this->create_links();
  }

  /**
   * sets various object properties
   *
   * for use in the template
   *
   * @param array $props an array of properties to set
   *                current_page_class string the class name for the current page link
   *                disabled_class     string the class to apply to disabled links
   *                anchor_wrap        bool   whether to wrap the disabled link in an 'a' tag (true) or span (false)
   *                first_last         bool   whether to show the first and last page links
   *                wrappers           array  the HTML to wrap the links in (see set_wrappers())
   */
  public function set_props($array) {

    foreach ($array as $prop => $value) {

      switch ($prop) {

        case 'current_page_class':
        case 'disabled_class':

          if (is_string($value))
            $this->$prop = $value;
          break;
        case 'anchor_wrap':
        case 'first_last':

          $this->$prop = (bool) $value;
          break;
        case 'wrappers':

          if (is_array($value))
            $this->set_wrappers($value);
          break;
      }
    }
  }

  /**
   * Sets the current page
   *
   * @param unknown_type $page
   */
  function setPage($page) {
    $this->page = 0 + $page;
  }

  /**
   * Sets the records per page
   *
   * @param integer $size
   */
  function setSize($size) {
    $this->size = intval($size);
    if ($this->size < 1) {
      $this->size = $this->total_records;
    }
  }

  /**
   * Set's total records
   *
   * @param integer $total
   */
  function setTotalRecords($total) {
    $this->total_records = false === $total ? false : 0 + $total;
  }

  /**
   * Sets the link url for navigation pages
   *
   * @param string $url
   */
  function setLink($url, $add_variables) {
    
    if ( ! empty($add_variables) )
      $conj = false !== strpos($url,'?') ? '&' : '?';
    $this->link = $url . $conj . $add_variables;
  }

  /**
   * sets all the wrap HTML values
   */
  public function set_wrappers($wrappers = array()) {

    $defaults = array(
        'wrap_class' => '',
        'list_class' => '',
        'wrap_tag' => 'div',
        'all_button_wrap_tag' => 'ul',
        'button_wrap_tag' => 'li',
    );

    $this->wrappers = shortcode_atts($defaults, $wrappers);
    
    $this->wrappers['wrap_class'] = 'pagination ' . Participants_Db::$prefix . 'pagination ' . $this->wrappers['wrap_class'];
  }

  /**
   * sets the current page class
   *
   * @params string $class
   */
  public function set_current_page_class($class) {

    $this->current_page_class = $class;
  }

  /**
   * sets the current page anchor wrap flag
   *
   * @param bool $flag
   */
  public function set_anchor_wrap($flag) {

    $this->anchor_wrap = $flag;
  }

  /**
   * Returns the LIMIT sql statement
   *
   * @return string
   */
  function getLimitSql() {
    $sql = "LIMIT " . $this->getLimit();
    return $sql;
  }

  /**
   * Get the LIMIT statment
   *
   * @return string
   */
  function getLimit() {
    if ($this->total_records == 0) {
      $lastpage = 0;
    } else {
      $lastpage = ceil($this->total_records / $this->size);
    }

    $page = $this->page;

    if ($this->page < 1) {
      $page = 1;
    } else if ($this->page > $lastpage && $lastpage > 0) {
      $page = $lastpage;
    } else {
      $page = $this->page;
    }

    $sql = ($page - 1) * $this->size . "," . $this->size;

    return $sql;
  }

  /**
   * Creates page navigation links
   *
   * @return 	string
   */
  function create_links() {
    // object is not set up properly
    if (false === $this->total_records)
      return '';

    $totalItems = $this->total_records;
    $perPage = $this->size;
    $currentPage = $this->page;
    $link = $this->link;
    extract($this->wrappers);

    $totalPages = $perPage > 0 ? floor($totalItems / $perPage) : 0;
    $totalPages += $perPage > 0 ? ($totalItems % $perPage != 0 ? 1 : 0) : 0;

    if ($totalPages <= 1) {
      return null;
    } elseif ($totalPages > 5) {
      $this->first_last = true;
    }

    $output = '';
    //$output = '<span id="total_page">Page (' . $currentPage . '/' . $totalPages . ')</span>&nbsp;';

    $loopStart = 1;
    $loopEnd = $totalPages;

    if ($totalPages > 5) {
      if ($currentPage <= 3) {
        $loopStart = 1;
        $loopEnd = 5;
      } else if ($currentPage >= $totalPages - 2) {
        $loopStart = $totalPages - 4;
        $loopEnd = $totalPages;
      } else {
        $loopStart = $currentPage - 2;
        $loopEnd = $currentPage + 2;
      }
    }

    $button_pattern = '<' . $button_wrap_tag . ' class="%2$s"><a href="%1$s">%3$s</a></' . $button_wrap_tag . '>';
    $glyph_pattern = '<' . $button_wrap_tag . ' class="%2$s"><a title="%3$s" href="%1$s"><span class="glyphicon glyphicon-%4$s"></span></a></' . $button_wrap_tag . '>';
    $disabled_pattern = $this->anchor_wrap ?
            '<' . $button_wrap_tag . ' class="%2$s"><a href="#">%3$s</a></' . $button_wrap_tag . '> ' :
            '<' . $button_wrap_tag . ' class="%2$s"><span>%3$s</span></' . $button_wrap_tag . '> ';
    $disabled_glyph_pattern = $this->anchor_wrap ?
            '<' . $button_wrap_tag . ' class="%2$s"><a title="%3$s" href="#"><span class="glyphicon glyphicon-%4$s"></span></a></' . $button_wrap_tag . '>' :
            '<' . $button_wrap_tag . ' class="%2$s"><span><span title="%3$s" class="glyphicon glyphicon-%4$s"></span></span></' . $button_wrap_tag . '> ';

    // add the first page link
    if ($this->first_last) {
//      
      $output .= sprintf(
          ($currentPage > 1 ? $glyph_pattern : $disabled_glyph_pattern),
          $this->_sprintf($link, 1),
          ($currentPage > 1 ? 'firstpage' : $this->disabled_class),
          __('First', 'participants-database'),
          'first-page'
);
    }

    // add the previous page link
    $output .= sprintf(
            ($currentPage > 1 ? $glyph_pattern : $disabled_glyph_pattern), 
            $this->_sprintf($link, $currentPage - 1), 
            ($currentPage > 1 ? 'nextpage' : $this->disabled_class), 
            __('Previous', 'participants-database'),
            'previous-page'
    );

    for ($i = $loopStart; $i <= $loopEnd; $i++) {

      $output .= sprintf(
              ($i == $currentPage ? $disabled_pattern : $button_pattern), 
              $this->_sprintf($link, $i), 
              ($i == $currentPage ? $this->current_page_class : ''), 
              $i
      );
    }
    $output .= sprintf(
            ($currentPage < $totalPages ? $glyph_pattern : $disabled_glyph_pattern), 
            $this->_sprintf($link, $currentPage + 1), 
            ($currentPage < $totalPages ? 'nextpage' : $this->disabled_class), 
            __('Next', 'participants-database'),
            'next-page'
    );

    if ($this->first_last) {

//      
      
      $output .= sprintf(
          ($currentPage < $totalPages ? $glyph_pattern : $disabled_glyph_pattern),
          $this->_sprintf($link, $totalPages),
          ($currentPage < $totalPages ? 'lastpage' : $this->disabled_class),
          __('Last', 'participants-database'),
          'last-page'
);
    }

    return sprintf(
            '<%1$s class="%2$s"><%3$s%4$s>%5$s</%3$s></%1$s>', 
            $wrap_tag, 
            $wrap_class, 
            $all_button_wrap_tag, 
            (empty($list_class) ? '' : ' class="' . $list_class . '" '),
            $output
    );
  }
  
  /**
   * special implementation of sprintf for URL-encoded values
   * 
   * we need this because URL-encoded values use the % sign, which confuses sprintf
   * 
   * TODO: make a sprintf-like function that iterates through multiple placeholders
   * 
   * @param string $link
   * @param int $pagenum
   * @return string
   */
  private function _sprintf($link,$pagenum) {
    
    return str_replace('%1$s', $pagenum, $link);
    
  }

}

?>