<?php
/*
Plugin Name: Organization Hub Add-on
Plugin URI: https://github.com/clas-web/orghub-addon
Description: The Organization Hub (Network) is a collection of useful tools for maintaining a multisite WordPress instance for an organization.  The Users page keeps track of the organization users, their profile site, and any connections posts (see Connections Hub).  The Sites page is a listing of all the current sites, its posts and pages count, and the last time it was updated and by whom.  The Upload page is used to batch import large amounts of posts, pages, links, taxonomies, users, and sites.
Version: 0.0.1
Author: Aaron Forsyth
Author URI:
Network: True
GitHub Plugin URI: https://github.com/clas-web/orghub-addon
*/

function site_type_values(){
		$sites = get_sites( array( 'number' => 99999));
		$blogtypes = array('');
		foreach( $sites as &$site ){
			$site_blogtype = get_blog_option($site->blog_id,'blogtype');
			if(!in_array($site_blogtype, $blogtypes)){
				array_push($blogtypes, $site_blogtype);
			}
		}
		return $blogtypes;
}
function variation_values(){
		$sites = get_sites( array( 'number' => 99999));
		$variations = array();
		foreach( $sites as $site ){
			switch_to_blog( $site->blog_id );
			$site_variations = maybe_unserialize(get_theme_mod('vtt-variation-choices'));
			if($site_variations){
				foreach ($site_variations as $site_variation){
					if(!in_array($site_variation, $variations)){
						array_push($variations, $site_variation);
					}
				}
			}
			restore_current_blog();
		}
		return $variations;
}
/***************************************************************
 Filters for organization-hub\admin-pages\tabs\sites\list.php 
****************************************************************/
add_filter( 'orghub_filter_types', 'ohv_filter_types' );
function ohv_filter_types( $filter_types ) {
	$filter_types['filter_by_blogtype'] = array('default' => false );
	$filter_types['blogtype'] = array('values' => site_type_values(),'default' => '');
    return $filter_types;
}
	
add_filter('orghub_more_filters', 'ohv_more_filters', 10, 2);
function ohv_more_filters($extra_filter, $filters){
	$filter_values = "";
	foreach(site_type_values() as $value){
		$filter_values .= '<option value="'.$value.'"'.selected($value, $filters['blogtype'],false).'>'.$value.'</option>';
	}
	$extra_filter = '<div class="blogtype">
					<div class="title">
						<input type="checkbox"
						       name="filter_by_blogtype"
						       value="1"'
							   .checked(true, $filters['filter_by_blogtype'] !== false, false).
							   ' />
						Site Type
					</div>
					
					<select name="blogtype">'
					.$filter_values.
					'</select>
					
				</div>';
	return $extra_filter;
}

add_action('orghub_inline_filter','ohv_inline_filter');
function ohv_inline_filter($list_table){
	$blogtypes = site_type_values();
	$variations = variation_values();
	?>
	<table id="inline-change-blogtype"
			   class="list-table-inline-bulk-action"
			   table="orghub-sites"
			   action="change-blogtype"
			   style="display:none">
		<tr class="inline-bulk-action">
			<td colspan="<?php echo $list_table->get_column_count(); ?>" class="colspanchange">
				<fieldset class="inline-change-blogtype-col-left">
				<div class="inline-change-blogtype-col">
					<h4>Choose Site Type</h4>
					<select name="bulk[blogtype]">
						<?php foreach( $blogtypes as $blogtype ): ?>
							<option value="<?php echo $blogtype; ?>"><?php echo $blogtype; ?></option>
						<?php endforeach; ?>
					</select>
					<button class="bulk-save">Save</button>
					<button class="bulk-cancel">Cancel</button>						
				</div>
				</fieldset>
			</td>
		</tr>
	</table>
	<table id="inline-change-variations"
			   class="list-table-inline-bulk-action"
			   table="orghub-sites"
			   action="change-variations"
			   style="display:none">
		<tr class="inline-bulk-action">
			<td colspan="<?php echo $list_table->get_column_count(); ?>" class="colspanchange">
				<fieldset class="inline-change-variations-col-left">
				<div class="inline-change-variations-col">
					<h4>Choose Variations</h4>
						<?php $i = 0; foreach( $variations as $variation ):?>
							<input name="bulk[variations<?php echo $i;?>]" type="checkbox" value="<?php echo $variation; ?>"><?php echo $variation; ?></input><br>
						<?php $i++; endforeach; ?>
					<button class="bulk-save">Save</button>
					<button class="bulk-cancel">Cancel</button>						
				</div>
				</fieldset>
			</td>
		</tr>
	</table>
	<?php
}


/***************************************************************
 Filters for organization-hub\classes\model\sites-model.php 
****************************************************************/
add_filter('orghub_create_table', 'ohv_create_table');
function ohv_create_table($sql){
	$variations_default = maybe_serialize(array('default','dark'));
	$sql .= "blogtype NOT NULL DEFAULT '', variations NOT NULL DEFAULT $variations_default";
	return $sql;
}

add_filter('orghub_db_fields_insert', 'ohv_db_fields_insert');
function ohv_db_fields_insert($db_fields_insert){
	$db_fields_insert['blogtype'] = $args['blogtype'];
	$db_fields_insert['variations'] = $args['variations'];
	return $db_fields_insert;
}

add_filter('orghub_db_types_insert','ohv_db_types_insert');
function ohv_db_types_insert($db_types_update){
	array_push($db_types_insert, '%s', '%s');
	return $db_types_insert;
}

add_filter('orghub_db_fields_update', 'ohv_db_fields_update', 10, 2);
function ohv_db_fields_update($db_fields_update, $args){
	$db_fields_update['blogtype'] = $args['blogtype'];
	$db_fields_update['variations'] = $args['variations'];
	return $db_fields_update;
}

add_filter('orghub_db_types_update','ohv_db_types_update');
function ohv_db_types_update($db_types_update){
	array_push($db_types_update, '%s', '%s');
	return $db_types_update;
}

add_filter('orghub_fields_list_sites','ohv_fields_list_sites');
function ohv_fields_list_sites($fields_list){
	array_push($fields_list, 'blogtype', 'variations');
	return $fields_list;
}

add_filter('orghub_fields_list_site','ohv_fields_list_site');
function ohv_fields_list_site($fields_list){
	array_push($fields_list, 'blogtype', 'variations');
	return $fields_list;
}

add_filter( 'orghub_wherestring', 'ohv_filter', 10, 2);
function ohv_filter( $where_string, $filter ) {
	if( $filter['filter_by_blogtype'] !== false ){
		if( empty($where_string) ) $where_string = 'WHERE ';
		else $where_string .= ' AND ';
		
		$blogtype =  $filter['blogtype'];
		if ($blogtype == 'Not Set') {
			$where_string .= 'blogtype = ""';
		}
		else{
			$where_string .= 'blogtype = \''.$blogtype.'\'';
		}
	}
    return $where_string;
}

//THIS FILTER IS NOT WORKING
add_filter('orghub_site_fields', 'ohv_site_fields', 10, 2);
function ohv_site_fields($site, $blog_id){
	switch_to_blog( $blog_id );
	$site['status'] = 'QXZ';
	$site['blogtype'] = get_option('blogtype', 'Not Set');
    $site['variations'] = maybe_serialize(get_theme_mod('vtt-variation-choices'));
	restore_current_blog();
	return $site;
}

add_filter('orghub_csv_headers','ohv_csv_headers');
function ohv_csv_headers($headers){
	array_push($headers, 'blogtype', 'variations');
	return $headers;
}

add_filter('orghub_csv_values','ohv_csv_values');
function ohv_csv_values($extra_values){
	array_push($extra_values, 'blogtype', 'variations');
	return $extra_values;
}



/***************************************************************
 Filters for organization-hub\classes\sites-list-table.php 
****************************************************************/
add_filter('orghub_table_columns','ohv_table_columns');
function ohv_table_columns($table_columns){
	$table_columns['blogtype'] = 'Site Type';
	$table_columns['variations'] = 'Valid Variations';
	return $table_columns;
}

add_filter('orghub_actions','ohv_actions');
function ohv_actions($actions){
	$actions['change-blogtype'] = 'Change Site Type';
	$actions['change-variations'] = 'Change Variations';
	return $actions;
}

add_action('orghub_batch_action', 'ohv_batch_action', 10, 3);
function ohv_batch_action($action, $sites, $input){
	if($action == 'change-blogtype'){
		foreach( $sites as $site_id ){
			update_blog_option($site_id, 'blogtype', $input['blogtype']);			
			// $oh_model = new OrgHub_SitesModel;
			// $oh_model->refresh_site( $site_id );
		}
	}
	elseif($action == 'change-variations'){
		$matched_variations = array();
		foreach($input as $key=>$value){
			if(stristr($key,'variations')!==FALSE)
				array_push($matched_variations, $value);
		}
		foreach( $sites as $site_id ){
			switch_to_blog( $site_id );
			set_theme_mod('vtt-variation-choices',$matched_variations);
			restore_current_blog();
			// $oh_model = new OrgHub_SitesModel;
			// $oh_model->refresh_site( $site_id );
		}
	}
//Should refresh site to update but this code is in OrgHub sites-model.php 
//OrgHub_SitesModel class is protected.
	
}

add_filter('orghub_column_html', 'ohv_column_html', 10, 3);
function ohv_column_html($column, $item, $html){
	if($column == 'blogtype'){
		$html = $item['blogtype'];
	}
	if($column == 'variations'){
		if($item['variations']){
			$html = implode(", ",maybe_unserialize($item['variations'])); 
		}
	}
	return $html;
}