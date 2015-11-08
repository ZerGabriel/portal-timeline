<?php
/*	Project:	EQdkp-Plus
 *	Package:	Tag cloud Portal Module
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2015 EQdkp-Plus Developer Team
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published
 *	by the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( !defined('EQDKP_INC') ){
	header('HTTP/1.0 404 Not Found');exit;
}

class timeline_portal extends portal_generic {

	protected static $path		= 'timeline';
	protected static $data		= array(
		'name'			=> 'timeline',
		'version'		=> '0.1.0',
		'author'		=> 'Asitara',
		'contact'		=> EQDKP_PROJECT_URL,
		'description'	=> 'Shows a timeline of your articles',
		'lang_prefix'	=> 'timeline_',
		'multiple'		=> true,
		'icon'			=> 'fa-arrows-h',
	);

	protected static $multiple = true;
	protected static $apiLevel = 20;
	public $template_file = 'timeline_portal.html';


	public function get_settings($state){
		$arrCategories = $this->pdh->get('article_categories', 'id_list', array(true));
		$settings	= array(
			'categories'	=> array(
				'type'		=> 'multiselect',
				'options'	=> $this->pdh->aget('article_categories', 'name', 0, array($arrCategories)),
			),
			'interval'	=> array(
				'type'		=> 'int',
				'default'	=> 1,
			),
		);
		return $settings;
	}


	public function output(){
		$this->tpl->js_file($this->server_path.'portal/timeline/templates/js/timeline_portal.js');
		$this->tpl->css_file($this->server_path.'portal/timeline/templates/timeline_portal.css');
		
		$arrCategories	= $this->config('categories');
		$intInterval	= (int)$this->config('interval');
		$intStartYear	= $this->time->date('Y') - ($intInterval - 1);
		$arrMonthsNames	= $this->user->lang('time_monthnames');
		$arrArticles	= array();
		
		//fetch all articles
		foreach($arrCategories as $intCategoryID){
			$arrArticles = array_merge($arrArticles, $this->pdh->get('article_categories', 'published_id_list', array($intCategoryID, $this->user->id)));
		}
		$arrArticles = array_unique($arrArticles);
		$arrSortedArticles = $this->pdh->sort($arrArticles, 'articles', 'date', 'desc');
		
		//generate years & months
		for($intYear = 0; $intYear <= $intInterval; $intYear++){
			$this->tpl->assign_block_vars('pm_tl_years', array(
				'COUNT'		=> $intYear,
				'YEAR'		=> $intStartYear + $intYear,
				'TIMESTAMP'	=> $this->time->mktime(0, 0, 0, 1, 1, $intStartYear + $intYear),
			));
			
			if($intYear > 0){
				for($intMonth = 1; $intMonth < 12; $intMonth++){
					$this->tpl->assign_block_vars('pm_tl_months', array(
						'COUNT'	=> $intMonth,
						'YEAR'	=> $intYear,
						'MONTH'	=> mb_substr($arrMonthsNames[$intMonth], 0, 3, 'UTF-8'),
					));
				}
			}
		}
		
		#d($this->time->fromformat('31-12-2016', 'd-m-Y'));
		#d($this->time->mktime(0, 0, 0, 12, 31, 2016));
		
		//generate articles
		/*for($a = 1; $a < 13; $a++){
			$this->tpl->assign_block_vars('pm_tl_articles', array(
				'NAME'		=> $a,
				'TIMESTAMP'	=> $this->time->mktime(0, 0, 0, $a, 5, 2015),
				#'DATE'		=> $this->time->date('Y-m-d', $intArticleDate),
				'DATE'		=> '2015-0'.$a.'-01',
			));
		}*/
		
		foreach($arrSortedArticles as $intArticleID){
			//check Date
			$intArticleDate = $this->pdh->get('articles', 'date', array($intArticleID));
			if( ($intArticleDate+($intInterval*60*60*24*365)) < $this->time->time ) continue;
			
			//check Preview Image
			$strPreviewImage = $this->pdh->get('articles', 'previewimage', array($intArticleID));
			if(strlen($strPreviewImage)){
				$strImage = register('pfh')->FileLink($strPreviewImage, 'files', 'absolute');
			} else $strImage = "";
			
			//parse Informations
			$intWordcount = 200;
			$strText = $this->pdh->get('articles', 'text', array($intArticleID));
			$strText = $this->bbcode->remove_embeddedMedia($this->bbcode->remove_shorttags($strText));
			$strText = strip_tags(xhtml_entity_decode($strText));
			$strText = truncate($strText, $intWordcount, '...', false, true);
			
			$this->tpl->assign_block_vars('pm_tl_articles', array(
				'ID'		=> $intArticleID,
				'TIMESTAMP'	=> $intArticleDate,
				'DATE'		=> $this->time->date('d.m.Y', $this->pdh->get('articles', 'date', array($intArticleID))),
				'TITLE'		=> $this->pdh->get('articles', 'title', array($intArticleID)),
				'IMAGE'		=> $strImage,
				'TEXT'		=> $strText,
				'URL'		=> $this->controller_path.$this->pdh->get('articles', 'path', array($intArticleID)),
			));
		}
		
	

		
		
		
		$this->tpl->add_js("
			$('#timeline-0').timeline({  });
		", 'docready');
		
		
		
		
		
		
		
		return 'Error: Template file is empty.';
		
		//--------------------------------------------------------------------------------------------------------------------------
		
		$this->tpl->js_file($this->server_path.'portal/timeline/includes/timelinexml.min.js');
		$this->tpl->css_file($this->server_path.'portal/timeline/includes/timelinexml.modern.css');

		$arrCategories = $this->config('categories');
		if(empty($arrCategories)) $arrCategories = array();
		$arrArticles = array();
		foreach($arrCategories as $intCategoryID){
			$arrArticles = array_merge($arrArticles, $this->pdh->get('article_categories', 'published_id_list', array($intCategoryID, $this->user->id)));
		}
		$arrArticles = array_unique($arrArticles);
		$arrSortedArticles = $this->pdh->sort($arrArticles, 'articles', 'date', 'desc');
		
		$this->tpl->add_js('
			$("#my-timeline_'.$this->id.'").timelinexml({ src : $(".timeline-html-wrap_'.$this->id.'") });
		', 'docready');
		
		$strOut = '<div id="my-timeline_'.$this->id.'" style="margin-top: 40px; margin-left: 10px; margin-right: 10px; margin-bottom: 10px;"></div>';

		$intInterval = (int)$this->config('interval');
		
		foreach($arrSortedArticles as $intArticleID){
			//Check Date
			if ($intInterval){
				$articleDate = $this->pdh->get('articles', 'date', array($intArticleID));
				if (($articleDate+($intInterval*60*60*24*365)) < $this->time->time ) continue;
			}
			
			
			$strPreviewImage = $this->pdh->get('articles', 'previewimage', array($intArticleID));
			if (strlen($strPreviewImage)){
				$strImage = register('pfh')->FileLink($strPreviewImage, 'files', 'absolute');
			} else $strImage = "";
			
			$intWordcount = 200;
			$strText = $this->pdh->get('articles', 'text', array($intArticleID));
			$strText = $this->bbcode->remove_embeddedMedia($this->bbcode->remove_shorttags($strText));
			$strText = strip_tags(xhtml_entity_decode($strText));
			$strText = truncate($strText, $intWordcount, '...', false, true);

			
			$strOut .= '
				<div class="timeline-html-wrap_'.$this->id.'" style="display:none">
				    <div class="timeline-event">
				    	<div class="timeline-date">'.$this->time->date('d.m.Y', $this->pdh->get('articles', 'date', array($intArticleID))).'</div>
				    	<div class="timeline-title">'.$this->pdh->get('articles', 'title', array($intArticleID)).'</div>
				    	<div class="timeline-thumb">'.$strImage.'</div>
				    	<div class="timeline-content">'.$strText.'</div>
				    	<div class="timeline-link"><a href="'.$this->controller_path.$this->pdh->get('articles', 'path', array($intArticleID)).'">'.$this->user->lang('readmore_button').'</a></div>
					</div>
				</div>';

		}
		
		return $strOut;
	}

}
?>