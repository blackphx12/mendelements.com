<?php
function luc_referrer()
{	
	global $wpdb, $StatPressV_Option;
	$table_name = STATPRESS_V_TABLE_NAME;
	$action = "referrer";
	$referrer_color = "#419E0C";
	$graphdays = $StatPressV_Option['StatPressV_Graph_Days'];
	if ($graphdays == 0)
		$graphdays = 7;

	// $pa = pa and $pp = pp in the slug
	$pa = luc_page_posts();
	$pp = luc_page_periode();

	$today = gmdate('Ymd', current_time('timestamp'));
	$limitdate = gmdate('Ymd', current_time('timestamp') - 86400 * $graphdays * $pp +86400);
	$currentdate = gmdate('Ymd', current_time('timestamp') - 86400 * $graphdays * ($pp -1));
	$permalink = luc_permalink();

	$NP = luc_count_periode("date", "FROM $table_name", "", "referrer<>'' AND referrer NOT LIKE '%" . get_bloginfo('url') . "%' AND searchengine=''", "date", $graphdays);
	$start_of_week = get_option('start_of_week');

	$strqry = "SELECT  count(distinct referrer)
					FROM $table_name
					WHERE referrer<>''
						AND referrer NOT LIKE '%" . get_bloginfo('url') . "%'
						AND searchengine=''
						AND date BETWEEN $limitdate AND $currentdate;
				";
	$NumberPosts = $wpdb->get_var($strqry);
	$NumberDisplayPost = $StatPressV_Option['StatPressV_Graph_Per_Page'];
	if ($NumberDisplayPost == 0)
		$NumberDisplayPost = 20;
	$NA = ceil($NumberPosts / $NumberDisplayPost);

	$LimitValueArticles = ($pa * $NumberDisplayPost) - $NumberDisplayPost;

	// sort post or page by most unique visitors
	$strqry = "SELECT count(*) as total, referrer, urlrequested
					FROM $table_name
					WHERE referrer<>''
						AND referrer NOT LIKE '" . get_bloginfo('url') . "%'
						AND searchengine=''
						AND date BETWEEN $limitdate AND $currentdate
					GROUP BY referrer
					ORDER by total DESC LIMIT $LimitValueArticles, $NumberDisplayPost;
				";
	$query = $wpdb->get_results($strqry);
	echo "<div class='wrap'><h2>Most referrer these " . $graphdays . " days </h2>";
	luc_print_pp_pa_link($NP, $pp, $action, $NA, $pa);
	foreach ($query as $url)
	{
		for ($i = 0; $i < $graphdays; $i++)
		{
			$Date = gmdate('Ymd', current_time('timestamp') - 86400 * ($graphdays * $pp - $i -1));
			$total->visitors[$Date] = 0;
		}
		$total->totalvisitors = 0;
		//TOTAL VISITORS
		if ($url->post_name == 'page_accueil') //url == home
			$qry_visitors = luc_query_graph("DISTINCT ip", "urlrequested ='' AND spider='' AND feed=''", $limitdate, $currentdate);
		else
			$qry_visitors = luc_query_graph("*", "referrer = '" . $url->referrer . "'", $limitdate, $currentdate);
		foreach ($qry_visitors as $row)
		{
			$total->visitors[$row->date] = $row->total;
			$total->totalvisitors += $row->total;
		}
		$maxxday = luc_maxxday($total, $graphdays, $pp);
		$px = luc_pixel($total, $graphdays, $maxxday, $pp, $action);
		// Overhead of the graph, display the name of the post/page and the average by day of the feeds
		echo "<div class='wrap'>
				<table class='widefat'>
					<thead>
						<tr>
							<th scope='col' width='15%'>
								<div style='background:$referrer_color;width:10px;height:10px;float:left;margin-top:2px;margin-right:5px;'></div>" . $total->totalvisitors . " visits
							</th>
							<th scope='col' width='15%'>
								<div style='background:$referrer_color;width:10px;height:10px;float:left;margin-top:2px;margin-right:5px;'></div>Average " . round($total->totalvisitors / $graphdays, 1) . " by day
							</th>
							<th scope='col' width='70%'>
								<font font-size='1'>" . $url->referrer . "</font>
							</th>
						</tr>
					</thead>
				</table>
				<table class='graph'>
					<tbody>
						<tr>";
		luc_graph($px, $total, $graphdays, $pp, $action);
		echo "			</tr>
					</tbody>
				</table>
			</div>";
	}
	luc_print_pp_pa_link($NP, $pp, $action, $NA, $pa);
	echo "</div>";
	luc_StatPressV_load_time();
}

?>