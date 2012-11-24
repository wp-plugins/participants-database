<?php

/**
 * pagination class for wordpress themes and plugins
 *
 *
 * @package    WordPress
 * @author     Original Author http://www.goodphptutorials.com/out/Simple_PHP_MySQL_Pagination
 * @author     Rolanbd Barker <webdesign@xnau.com>
 * @copyright  2011, xnau webdesign
 * @license    GPL2
 * @version    1.2
 *
 * 08-08-12 added support for bootstrap-style pagination HTML
 *          with methoeds for setting the class of the current page indicator and an option
 *          to wrap the current page indicator numeral with a dummy anchor tag
 */
class Pagination {

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
   *        'open'        open html for the whole control
   *        'close'       close html for the whole control
   *        'all_buttons' tag to wrap the buttons (defaults to 'ul')
   *        'button'      tag to wrap each button (defaults to 'li')
   */
  public $wrappers;

  /**
   * class name for current page link
   *
   * @var string
   */
  private $current_page_class;

  /**
   * flag to select wrapping dummy anchor tag around current page link
   *
   * @var bool
   */
  private $anchor_wrap = false;

  /**
   * was the object instantiates by a filtering operation?
   * 
   * @var bool
   */
  private $filtering;

  /**
   * Class Constructor
   *
   * @param array $args
   *                page int the current page
   *                size int the number of records to show per page
   *                total_records int the total records in the full query
   *                link string the URL for page links
   *                wrap_tag string tag to wrap the pagination links (an unordered list) in, defaults
   *                                to '<div class="pagination">'
   *                wrap_tag_close - the closing tag for the wrapper (default: '</div>')
   */
  function __construct($args) {
    extract(wp_parse_args($args, array(
                'page' => 1,
                'size' => 10,
                'total_records' => false,
                'link' => '',
                'current_page_class' => 'currentpage',
                'filtering' => 0,
            )));
    $this->setPage($page);
    $this->setSize($size);
    $this->setTotalRecords($total_records);
    $this->setLink($link);
    $this->filtering = $filtering;
    $this->set_wrappers();

    $this->current_page_class = $current_page_class;
  }

  /**
   * Set's the current page
   *
   * @param unknown_type $page
   */
  function setPage($page) {
    $this->page = 0 + $page;
  }

  /**
   * Set's the records per page
   *
   * @param integer $size
   */
  function setSize($size) {
    $this->size = 0 + $size;
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
  function setLink($url) {
    $this->link = $url;
  }

  /**
   * sets all the wrap HTML values
   */
  public function set_wrappers($wrappers = array()) {

    $defaults = array(
        'wrap_tag' => '<div class="pagination">',
        'wrap_tag_close' => '</div>',
        'all_button_wrap_tag' => 'ul',
        'button_wrap_tag' => 'li',
    );

    $this->wrappers = shortcode_atts($defaults, $wrappers);
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
  public function set_anchor_wrap($flag = false) {

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

    $totalPages = floor($totalItems / $perPage);
    $totalPages += ($totalItems % $perPage != 0) ? 1 : 0;

    if ($totalPages < 1 || $totalPages == 1) {
      return null;
    }

    $output = null;
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

    $button_pattern = '<' . $button_wrap_tag . ' class="%1$s"><a href="%2$s">%3$s</a></' . $button_wrap_tag . '>';

    if ($loopStart != 1) {
      $output .= sprintf($button_pattern, 'disabledpage', sprintf($link, 1), __('First', 'participants-database'));
    }

    if ($currentPage > 1) {
      $output .= sprintf($button_pattern, 'nextpage', sprintf($link, $currentPage - 1), __('Previous', 'participants-database'));
    }

    for ($i = $loopStart; $i <= $loopEnd; $i++) {
      if ($i == $currentPage) {
        $output .= sprintf(
                ( $this->anchor_wrap ? '<' . $button_wrap_tag . ' class="%s"><a href="#">%s</a></' . $button_wrap_tag . '> ' : '<' . $button_wrap_tag . ' class="%s">%s</' . $button_wrap_tag . '> '), $this->current_page_class, $i
        );
      } else {
        $output .= sprintf('<' . $button_wrap_tag . '><a href="' . $link . '">', $i) . $i . '</a></' . $button_wrap_tag . '> ';
      }
    }

    if ($currentPage < $totalPages) {
      $output .= sprintf($button_pattern, 'nextpage', sprintf($link, $currentPage + 1), __('Next', 'participants-database'));
    }

    if ($loopEnd != $totalPages) {
      $output .= sprintf($button_pattern, 'lastpage', sprintf($link, $totalPages), __('Last', 'participants-database'));
    }

    return $wrap_tag . '<' . $all_button_wrap_tag . '>' . $output . '</' . $all_button_wrap_tag . '>' . $wrap_tag_close;
  }

  /**
   * echoes the pagination links
   *
   */
  public function links() {
    echo $this->create_links();
  }

  /* alias of above func that doesn't output if an AJAX filtering refresh is happening */

  public function show() {

    if (!$this->filtering)
      echo $this->create_links();
  }

}

?>