<?php
// $Id: index.php,v 1.10 2009/11/06 18:04:17 ohwada Exp $

//=========================================================
// webphoto module
// 2008-04-02 K.OHWADA
//=========================================================

//---------------------------------------------------------
// change log
// 2009-10-25 K.OHWADA
// webphoto_show_list
// 2009-09-25 K.OHWADA
// Notice [PHP]: Undefined variable: main_rows
// 2009-05-30 K.OHWADA
// random_more_url_s -> show_random_more
// 2009-04-10 K.OHWADA
// build_main_param()
// 2009-03-15 K.OHWADA
// add_box_list() -> add_show_js_windows()
// 2008-12-12 K.OHWADA
// public_class
// 2008-08-24 K.OHWADA
// photo_handler -> item_handler
// QR code
// 2008-07-01 K.OHWADA
// build_navi() -> build_main_navi()
//---------------------------------------------------------

if( ! defined( 'XOOPS_TRUST_PATH' ) ) die( 'not permit' ) ;

//=========================================================
// class webphoto_main_index
//=========================================================
class webphoto_main_index extends webphoto_show_list
{
	var $_QR_IMAGE_INDEX = 'qr_index.png';

//---------------------------------------------------------
// constructor
//---------------------------------------------------------
function webphoto_main_index( $dirname , $trust_dirname )
{
	$this->webphoto_show_list( $dirname , $trust_dirname );
	$this->set_template_main( 'main_index.html' );

	$this->init_preload();

	if ( _C_WEBPHOTO_COMMUNITY_USE ) {
		$this->set_template_main( 'main_photo.html' );
		$this->_SHOW_PHOTO_VIEW = true;
		$this->set_navi_mode( 'kind' );
	}

}

function &getInstance( $dirname , $trust_dirname )
{
	static $instance;
	if (!isset($instance)) {
		$instance = new webphoto_main_index( $dirname , $trust_dirname );
	}
	return $instance;
}

//---------------------------------------------------------
// main
//---------------------------------------------------------
function main()
{
	$show_photo      = false;
	$main_photos     = null;
	$photo           = null;
	$show_photo_desc = false;

// Notice [PHP]: Undefined variable: main_rows
	$main_rows       = null;

	$timeline_photos = null ;

	$mode = $this->_get_action();
	$this->set_mode( $mode );

	$limit = $this->_MAX_PHOTOS;
	$start = $this->pagenavi_calc_start( $limit );
	$total = $this->_public_class->get_count();
	$unit  = $this->_post_class->get_get_text('unit');
	$date  = $this->_post_class->get_get_text('date');

	if ( $total > 0 ) {
		$show_photo  = true;
		$main_rows   = $this->_get_rows_by_mode( $this->_MAX_PHOTOS, $start );
		$main_photos = $this->build_photo_show_from_rows( $main_rows );

		if ( $this->_SHOW_PHOTO_VIEW && isset( $main_rows[0] ) ) {
			$photo = $this->build_photo_show_photo( $main_rows[0] );
			$show_photo_desc = true;
		}
	}

	$sub_title_s = $this->sanitize( $this->get_constant( 'TITLE_'. $mode ) ); 

	$gmap_param = $this->_build_gmap_param();
	$show_gmap  = $gmap_param['show_gmap'];

	$this->assign_xoops_header( $mode, null, $show_gmap );

	$this->create_mobile_qr( 0 );

	$param = array(
		'xoops_pagetitle'   => $this->sanitize( $this->_MODULE_NAME ),
		'title_bread_crumb' => $sub_title_s,
		'total_bread_crumb' => $total,
		'sub_title_s'       => $sub_title_s ,
		'sub_desc_s'        => '' , 
		'photo_total'       => $total,
		'photos'            => $main_photos,
		'photo'             => $photo ,
		'show_photo'        => $show_photo , 
		'show_photo_desc'   => $show_photo_desc ,
		'show_nomatch'      => $this->build_show_nomatch( $total ) ,
		'show_random_more'  => $this->_build_show_random_more() ,
		'index_desc'        => $this->_build_index_desc() ,
		'mobile_email'      => $this->get_mobile_email() ,
		'mobile_url'        => $this->build_mobile_url( 0 ) ,
		'mobile_qr_image'   => $this->_QR_IMAGE_INDEX ,
	);

	$arr = array_merge( 
		$param, $gmap_param, 
		$this->build_main_param( $mode, true ) ,
		$this->_build_tagcloud_param() ,
		$this->_build_catlist_param() ,
		$this->_build_timeline_param( $unit, $date, $main_rows ) ,
		$this->_build_notification_select_param() ,
		$this->_build_navi_param( $total, $limit ) 
	);

	return $this->add_show_js_windows( $arr );
}

//---------------------------------------------------------
// get param from url
//---------------------------------------------------------
function _get_action()
{
	$this->get_pathinfo_param();

	if ( $this->_get_op == 'latest' ) {
		return 'latest';
	} elseif ( $this->_get_op == 'popular' ) {
		return 'popular';
	} elseif ( $this->_get_op == 'highrate' ) {
		return 'highrate';
	} elseif ( $this->_get_op == 'random' ) {
		return 'random';
	} elseif ( $this->_get_op == 'map' ) {
		return 'map';
	} elseif ( $this->_get_op == 'timeline' ) {
		return 'timeline';
	}

	return $this->_ACTION_DEFAULT;
}

//---------------------------------------------------------
// latest etc
//---------------------------------------------------------
function _get_rows_by_mode( $limit, $start )
{
	$orderby = $this->_sort_class->mode_to_orderby( $this->_mode );
	return $this->_public_class->get_rows_by_orderby( $orderby, $limit, $start );
}

function _build_show_random_more()
{
	if ( $this->_mode == 'random' ) {
		return true;
	}
	return false ;
}

//---------------------------------------------------------
// index desc
//---------------------------------------------------------
function _build_index_desc()
{
	if ( $this->check_show_desc( $this->_mode ) ) {
		return $this->_config_class->get_by_name('index_desc');
	}
	return null;
}

//---------------------------------------------------------
// cat list
//---------------------------------------------------------
function _build_catlist_param()
{
	if ( $this->check_show_catlist( $this->_mode ) ) {
		return $this->build_catlist(
			0, $this->_TOP_CATLIST_COLS, $this->_TOP_CATLIST_DELMITA );
	}

	$arr = array(
		'show_cat_list' => false
	);
	return $arr;
}

//---------------------------------------------------------
// tag cloud
//---------------------------------------------------------
function _build_tagcloud_param()
{
	$show     = false;
	$tagcloud = null;

	if ( $this->check_show_tagcloud( $this->_mode ) ) {
		$tagcloud = $this->_public_class->build_tagcloud( $this->_MAX_TAG_CLOUD );

		if ( is_array($tagcloud) && count($tagcloud) ) {
			$show = true;
		}

	}

	$arr = array(
		'show_tagcloud' => $show,
		'tagcloud'      => $tagcloud,
	);

	return $arr;
}

//---------------------------------------------------------
// gmap
//---------------------------------------------------------
function _build_gmap_param()
{
	if ( $this->check_show_gmap( $this->_mode ) ) {
		return $this->build_gmap( 0, $this->_MAX_GMAPS );
	}

	$arr = array(
		'show_gmap' => false
	);
	return $arr;
}

//---------------------------------------------------------
// timeline
//---------------------------------------------------------
function _build_timeline_param( $unit, $date, $rows )
{
	if ( $this->check_show_timeline( $this->_mode ) ) {
		return $this->build_timeline_param( $unit, $date, $rows );
	}

	$arr = array(
		'show_timeline' => false
	);
	return $arr;
}

//---------------------------------------------------------
// notification_select
//---------------------------------------------------------
function _build_notification_select_param()
{
	if ( $this->check_show_notification( $this->_mode ) ) {
		return $this->build_notification_select();
	}

	$arr = array(
		'show_notification_select' => false
	);
	return $arr;

}

//---------------------------------------------------------
// navi
//---------------------------------------------------------
function _build_navi_param( $total, $limit )
{
	if ( $this->check_show_navi( $this->_mode, $this->_get_sort ) ) {
		return $this->build_main_navi( $this->_mode, $total, $limit ) ;
	}

	$arr = array(
		'show_navi' => false
	);
	return $arr;
}

// --- class end ---
}

?>