<?php
require_once dirname(__FILE__) . '/statement.php';
//-----------------------------------------------------------------------------
/**
 * @name Posql_Pager
 *
 * A simple Pager class for client view
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet.aqua@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Pager {

/**
 * @var    number    number of all items
 * @access public
 */
 var $totalCount = 0;

/**
 * @var    number    current page number
 * @access public
 */
 var $currentPage = 1;

/**
 * @var    number    number of items per page
 * @access public
 */
 var $perPage = 10;

/**
 * @var    number    number of page links for each window
 * @access public
 */
 var $range = 10;

/**
 * @var    number    number of total pages
 * @access public
 */
 var $totalPages = null;

/**
 * @var    array     array with number of pages
 * @access public
 */
 var $pages = array();

/**
 * @var    number    number of start page
 * @access public
 */
 var $startPage = null;

/**
 * @var    number    number of end page
 * @access public
 */
 var $endPage = null;

/**
 * @var    number    number of previous page
 * @access public
 */
 var $prev = null;

/**
 * @var    number    number of next page
 * @access public
 */
 var $next = null;

/**
 * @var    number    number offset of SELECT statement
 * @access public
 */
 var $offset = null;

/**
 * @var    number    number limit of SELECT statement
 * @access public
 */
 var $limit = null;

/**
 * Class constructor
 *
 * @param  void
 * @return Posql_Pager
 * @access public
 */
 function __construct() {
 }

/**
 * Set each pages information for the Pager object
 *
 * @param  number  number of total items
 * @param  number  current page number
 * @param  number  number of items per page
 * @param  number  number of page links for each window
 * @return void
 * @access public
 */
 function setPager($total_count = null, $curpage = null,
                   $perpage     = null, $range = null){
   if (is_numeric($total_count)) {
     $this->totalCount = $total_count;
   }
   if (is_numeric($curpage)) {
     $this->currentPage = $curpage;
   }
   if (is_numeric($perpage)) {
     $this->perPage = $perpage;
   }
   if (is_numeric($range)) {
     $this->range = $range;
   }
   $this->totalCount  = $this->totalCount  - 0;
   $this->currentPage = $this->currentPage - 0;
   $this->perPage     = $this->perPage     - 0;
   $this->range       = $this->range       - 0;
   $this->totalPages = ceil($this->totalCount / $this->perPage);
   if ($this->totalPages < $this->range) {
     $this->range = $this->totalPages;
   }
   $this->startPage = 1;
   if ($this->currentPage >= ceil($this->range / 2)) {
     $this->startPage = $this->currentPage - floor($this->range / 2);
   }
   if ($this->startPage < 1) {
     $this->startPage = 1;
   }
   $this->endPage = $this->startPage + $this->range - 1;
   if ($this->currentPage > $this->totalPages - ceil($this->range / 2)) {
     $this->endPage = $this->totalPages;
     $this->startPage = $this->endPage - $this->range + 1;
   }
   $this->prev = null;
   if ($this->currentPage > $this->startPage) {
     $this->prev = $this->currentPage - 1;
   }
   $this->next = null;
   if ($this->currentPage < $this->endPage) {
     $this->next = $this->currentPage + 1;
   }
   $range_end = 1;
   if ($this->endPage) {
     $range_end = $this->endPage;
   }
   $this->pages = range($this->startPage, $range_end);
   $this->offset = ceil($this->currentPage - 1) * $this->perPage;
   $this->limit = $this->perPage;
   if ($this->totalCount < $this->perPage) {
     $this->limit = $this->totalCount;
   }
 }

}
