<!doctype html>
<html>
<head>
<meta charset="utf-8">
<?php

/* To call Primo API JSON feeds your web server IP address must be entered into the Primo Back Office (if you're in the CSU contact David)
Advanced Configuration -> All Mapping Tables -> WS and XS IP */

//function to call JSON feeds
function PrimoSearch($primo_scope, $primo_tab, $query, $limit,$campus,$alma,$count) {
	if($limit !='') { //If $limit is not blank, see limit options below
		$limits = '&query=facet_rtype,exact,'.$limit; //For JSON
		$weblimits = '&facet=rtype,include,'.$limit; //For the Browser
	}
	else { //No limits!
		$limits = ''; //For JSON
		$weblimits =''; //For the browser
	}
	
	$url = 'https://'.$campus.'-primo.hosted.exlibrisgroup.com/PrimoWebServices/xservice/search/brief?onCampus=false&institution='.$alma.'&highlight=false&dym=true&indx=1&loc=adaptor,primo_central_multiple_fe&local,scope:'.$primo_scope.'&query=any,contains,'.$query.''.$limits.'&bulkSize='.$count.'&json=true';
	$json = file_get_contents($url);
	$data = json_decode($json,true);
	echo '<div><ol>';
	$totalcount = $data['SEGMENTS']['JAGROOT']['RESULT']['DOCSET']['@TOTALHITS']; //Get total results. This doesn't match for searches which include Alma data - maybe due to indexing
	$bibrecords = $data['SEGMENTS']['JAGROOT']['RESULT']['DOCSET']['DOC']; //This section of the JSON contains the records
	foreach($bibrecords as $bibrecord){ //for each bib record in the search results do the following
	   $recordidraw = $bibrecord['PrimoNMBib']['record']['control']['recordid']; //get the record ID so that we can send users to the record
	   $recordid = is_array($recordidraw) ? $recordidraw[0] : $recordidraw; //Is there more than one? Get the first one[0] or the only one
	   $type = $bibrecord['PrimoNMBib']['record']['display']['type']; // Get the type from JSON
	   $titleraw = $bibrecord['PrimoNMBib']['record']['display']['title']; // Get the title from JSON
	   $title = is_array($titleraw) ? $titleraw[0] : $titleraw; // Is there more than one title in an array? If so choose the first title[0], if not get the only title
	   $source = $bibrecord['PrimoNMBib']['record']['display']['ispartof']; // Get the source (journals) from JSON
	   $creationdate = $bibrecord['PrimoNMBib']['record']['display']['creationdate']; // Get the creation date (books) source from JSON
	   //$creationdate = (strlen($creationdateraw) == 4 ? $creationdateraw : substr($creationdateraw, -4)); // If creation date has more than four characters get the last four characters containing the year
	   $description = $bibrecord['PrimoNMBib']['record']['display']['description']; // Get the description from JSON
	   echo '<li>'.$type.': <a href="https://'.$campus.'-primo.hosted.exlibrisgroup.com/primo-explore/fulldisplay?docid='.$recordid.'&context=PC&vid='.$alma.'&lang=en_US&search_scope='.$primo_scope.'&adaptor=primo_central_multiple_fe&tab='.$primo_tab.'&query=any,contains,'.$query.'&sortby=rank&offset=0">'.$title.'</a>';
	   echo '<br />'.$source.' '.$creationdate;
	   //echo '<br />'.$description.'; // Description hidden
	   echo '</li>';
	}
	echo '</ol>';
	echo '<a href="https://'.$campus.'-primo.hosted.exlibrisgroup.com/primo-explore/search?query=any,contains,'.$query.',AND'.$weblimits.'&tab='.$primo_tab.'&search_scope='.$primo_scope.'&sortby=rank&vid='.$alma.'&lang=en_US&mode=advanced&offset=0">See All <strong>'.$totalcount.' ';
		if($limit == '')
			echo 'Results';
		elseif($limit == 'reference_entrys') //Fixed spelling
			echo 'Reference Entries';
		else
			echo ucwords(str_replace('_', ' ', $limit)); //replace _ with a space, then uppercase words
	echo '</strong></a></div>';
	echo '<br />JSON from API: <a href="'.$url.'" target="_new">'.$url.'</a>'; //show JSON URL for debugging
}
echo '<title>Primo Results for '.$queryfiltered.'</title>';
?>

</head>

<body>

<?PHP


//Option for $limit are the Primo resource types (rtypes), such as: books, articles, newspaper_articles,reference_entrys
//PrimoSearch('[scope]','[tab]',[search from URL],'[rtype limit]',[campus url prefix],[campus code],[number of results]);
//PrimoSearch('EVERYTHING','everything',$searchquery,'books',$campus_stem,$campus_alma,5);

$campus_stem = 'sdsu'; //beginning of URL e.g. [sdsu]-primo.hosted.exlibrisgroup.com
$campus_alma = '01CALS_SDL'; //Institution Code
$queryfiltered = filter_var($_GET['query'], FILTER_SANITIZE_STRING); //the ?query= value from the URL
$searchquery = urlencode($queryfiltered); // encoding for the call to the JSON feed, maybe not needed
echo '<h1>Results for <span>'.$queryfiltered.'</h1></span>';
echo '<h2>Everything</h2>';
PrimoSearch('EVERYTHING','everything',$searchquery,'',$campus_stem,$campus_alma,6);
echo '<h2>Books</h2>';
PrimoSearch('EVERYTHING','everything',$searchquery,'books',$campus_stem,$campus_alma,6);
echo '<h2>Articles (Primo Central Collections)</h2>';
PrimoSearch('EVERYTHING','everything',$searchquery,'articles',$campus_stem,$campus_alma,6);
echo '<h2>Newspaper Articles</h2>';
PrimoSearch('EVERYTHING','everything',$searchquery,'newspaper_articles',$campus_stem,$campus_alma,6);
echo '<h2>Reference Entries</h2>';
PrimoSearch('EVERYTHING','everything',$searchquery,'reference_entrys',$campus_stem,$campus_alma,6);

?>
</body>
</html>

