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
 * @version    1.1
 */

class Pagination 
{
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
	 * @var string
	 */
	public $wrap_tag;

	/**
	 * Close wrapper for the pagination links
	 *
	 * @var string
	 */
	public $wrap_tag_close;
	
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
	function __construct( $args )
	{
		extract( wp_parse_args( $args, array(
																				'page'=>1,
																				'size'=>10,
																				'total_records'=>false,
																				'link'=>'',
																				'wrap_tag'=>'<div class="pagination">',
																				'wrap_tag_close'=>'</div>',
																				) ) );
		$this->setPage( $page );
		$this->setSize( $size );
		$this->setTotalRecords( $total_records );
		$this->setLink( $link );

		$this->set_wrap_tag( $wrap_tag );
		$this->set_wrap_tag_close( $wrap_tag_close );
	}
	
	/**
	 * Set's the current page
	 *
	 * @param unknown_type $page
	 */
	function setPage($page)
	{
		$this->page = 0+$page;
	}
	
	/**
	 * Set's the records per page
	 *
	 * @param integer $size
	 */
	function setSize($size)
	{
		$this->size = 0+$size;
	}
		
	/**
	 * Set's total records
	 *
	 * @param integer $total
	 */
	function setTotalRecords($total)
	{
		$this->total_records = false === $total ? false : 0+$total;
	}
	
	/**
	 * Sets the link url for navigation pages
	 *
	 * @param string $url
	 */
	function setLink($url)
	{
		$this->link = $url;
	}

	/**
	 * sets the wrap tag
	 *
	 * @param string $tag
	 */
	public function set_wrap_tag( $tag )
	{
		$this->wrap_tag = $tag;
	}

	/**
	 * sets the wrap tag close
	 *
	 * @param string $tag
	 */
	public function set_wrap_tag_close( $tag )
	{
		$this->wrap_tag_close = $tag;
	}
	
	
	/**
	 * Returns the LIMIT sql statement
	 *
	 * @return string
	 */
	function getLimitSql()
	{
		$sql = "LIMIT " . $this->getLimit();
		return $sql;
	}
		
	/**
	 * Get the LIMIT statment
	 *
	 * @return string
	 */
	function getLimit()
	{
		if ($this->total_records == 0)
		{
			$lastpage = 0;
		}
		else 
		{
			$lastpage = ceil($this->total_records/$this->size);
		}
		
		$page = $this->page;		
		
		if ($this->page < 1)
		{
			$page = 1;
		} 
		else if ($this->page > $lastpage && $lastpage > 0)
		{
			$page = $lastpage;
		}
		else 
		{
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
	function create_links()
	{
		// object is not set up properly
		if ( false === $this->total_records ) return '';

		$totalItems = $this->total_records;
		$perPage = $this->size;
		$currentPage = $this->page;
		$link = $this->link;
		
		$totalPages = floor($totalItems / $perPage);
		$totalPages += ($totalItems % $perPage != 0) ? 1 : 0;

		if ($totalPages < 1 || $totalPages == 1){
			return null;
		}

		$output = null;
		//$output = '<span id="total_page">Page (' . $currentPage . '/' . $totalPages . ')</span>&nbsp;';
				
		$loopStart = 1; 
		$loopEnd = $totalPages;

		if ($totalPages > 5)
		{
			if ($currentPage <= 3)
			{
				$loopStart = 1;
				$loopEnd = 5;
			}
			else if ($currentPage >= $totalPages - 2)
			{
				$loopStart = $totalPages - 4;
				$loopEnd = $totalPages;
			}
			else
			{
				$loopStart = $currentPage - 2;
				$loopEnd = $currentPage + 2;
			}
		}

		if ($loopStart != 1){
			$output .= sprintf('<li class="disabledpage"><a href="' . $link . '">'.__('First',Participants_Db::PLUGIN_NAME ).'</a></li>', '1');
		}
		
		if ($currentPage > 1){
			$output .= sprintf('<li class="nextpage"><a href="' . $link . '">'.__('Previous',Participants_Db::PLUGIN_NAME ).'</a></li>', $currentPage - 1);
		}
		
		for ($i = $loopStart; $i <= $loopEnd; $i++)
		{
			if ($i == $currentPage){
				$output .= '<li class="currentpage">' . $i . '</li> ';
			} else {
				$output .= sprintf('<li><a href="' . $link . '">', $i) . $i . '</a></li> ';
			}
		}

		if ($currentPage < $totalPages){
			$output .= sprintf('<li class="nextpage"><a href="' . $link . '">'.__('Next',Participants_Db::PLUGIN_NAME ).'</a></li>', $currentPage + 1);
		}
		
		if ($loopEnd != $totalPages){
			$output .= sprintf('<li class="nextpage"><a href="' . $link . '">'.__('Last',Participants_Db::PLUGIN_NAME ).'</a></li>', $totalPages);
		}

		return $this->wrap_tag . '<ul>' . $output . '</ul>' . $this->wrap_tag_close;
	}

	/**
	 * echoes the pagination links
	 *
	 */
	public function links() {
		echo $this->create_links();
	}
}

?>