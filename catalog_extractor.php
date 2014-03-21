<?php

/*************************************/
# Author 	: Giriraj
# Website	: http://ngiriraj.com/data/catalog_extractor.php
# Description:	Base version for extract data.gov.in catalog list

# Requirements :
# simple_html_dom.php
/*************************************/


// Include the library
// Download from http://kaz.dl.sourceforge.net/project/simplehtmldom/simplehtmldom/1.5/simplehtmldom_1_5.zip
include('simple_html_dom.php');
 
// Sector
$sector=(@$_GET['sector']) ? : "";

// Filter - Extend to customize filter qry
/*
items_per_page = 5,10,20,40,60
page = 0,1
*/
$filter="?keys=&ministry_department=&state_department=&group_name=&field_keywords_tid=&sector=".$sector."&asset_jurisdiction=&resource_category=&field_frequency_tid=&file_format=&visualized=All&api=All&sort_by=created&items_per_page=10&page=0%2C0";

// catalog url
$data_url = 'http://data.gov.in/catalogs'.$filter;

// Curl
function curl_read($data_url){
	$ch = curl_init ($data_url);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true); 
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$data=curl_exec($ch);
	curl_close ($ch);
	return  $data;
}


$html = str_get_html(curl_read($data_url));

$catalog_full_data=array();

foreach($html->find('span.field-content') as $e) {
	foreach($e->find('a[class^=node]') as $einner) {
		$catalog_url = "http://data.gov.in".$einner->href."?items_per_page=5";
		$catalog_title[] = $einner->innertext;		
		$fileName[] = str_replace('/catalog/','',$einner->href);
		$catalog_url_list[] = $catalog_url;		

		// Read Catalog sub page
		$dataset_html = str_get_html(curl_read($catalog_url));
		
		// Initialize
		$content = array();
		$title = array();
		$sector ="";
		$json = array();
		
		foreach($dataset_html->find('div.web_catalog_tab_container') as $d2) {
		
			// Description
			foreach($d2->find('div.views-field-body') as $li){
				foreach($li->find('.field-content') as $fl){
					$content = $fl->innertext;			 
				}
			}
			
			// Title
			foreach($d2->find('div.views-field-title') as $li){
				foreach($li->find('.field-content') as $fl){
					$title[]= $fl->innertext;			 
				}			 
			}
			
			// Sector
			foreach($d2->find('div.views-field-field-sector') as $li){
				foreach($li->find('.field-content') as $fl){
					$sector= $fl->innertext;			 
				}
			}
			
			// Download Links
			foreach($d2->find('a.data-extension') as $li){					
					$short[]= $li->href;			 					
			}
			
			// JSONP URL
			foreach($d2->find('a[title^=jsonp]') as $d) {
				$json[]= $d->href;
			}
			
			// Build Array
			$catalog_data = array("title" => $title,
						"description" =>$content,
						"sector"=>$sector,
						"jsonpLink"=>$json,
						"downloadLink"=>$short
						#"catalog_url"=>$data_url
					);

	   }
		
		// Common Array set
		$catalog_full_data[] = array ("catalog_title"=>$catalog_title,"catalogUrl" => $catalog_url_list,
					"fileName"=>$fileName,
					"data"=>$catalog_data,
					"Filter"=>$filter
					);
	}	
}


echo "<pre>";
print_r($catalog_full_data);
echo "</pre>";
		
?>
