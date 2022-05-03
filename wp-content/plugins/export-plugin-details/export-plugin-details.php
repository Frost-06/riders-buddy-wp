<?php
/*
Plugin Name: Export Plugin Details
description: Easy way to export plugin details and their version details in CSV.
Version: 1.1.7
Author: Boopathi Rajan
Author URI: http://www.boopathirajan.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

function register_export_settings_page() {
  add_submenu_page('tools.php', 'Export Plugin Details', 'Export Plugin Details', 'manage_options', 'export-plugins', 'export_plugin_details');
  add_submenu_page( null,'Plugin Details Export', 'Plugin Details Export', 'manage_options', 'export-plugins-csv', 'export_plugin_details_in_csv');
}
add_action('admin_menu', 'register_export_settings_page');

function export_plugin_details()
{
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	
	if ( ! function_exists( 'plugins_api' ) ) {
		  require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	}
	
	$plugins = get_plugins();
	?>
	<div class="wrap">
		<h1>Plugin Informations <a href="tools.php?page=export-plugins-csv" class="button button-primary" style="float:right">CSV Export</a></h1>
		<div class="notice notice-warning is-dismissible">
			<p><?php _e( 'Note: "Export plugin details" will not be added to this list!', 'export-plugin-details' ); ?></p>
		</div>
		<table class="wp-list-table widefat fixed posts">
			<thead>
				<tr>
					<th style="width:5%">S.No</th>
					<th>Plugin Name</th>
					<th>Description</th>
					<th>Author</th>
					<th>Active/Inactive</th>
					<th>Current Version</th>
					<th>Update Available</th>
					<th>New Version</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$sno=1;
				if($plugins)
				{
					foreach($plugins as $key=>$plugin)
					{
						if($plugin['TextDomain']!='export-plugin-details')
						{
							$version_latest='-';
							$args = array(
								'slug' => $plugin['TextDomain'],
								'fields' => array(
									'version' => true,
								)
							);
						
							$call_api = plugins_api( 'plugin_information', $args );
							/** Check for Errors & Display the results */
							if ( is_wp_error( $call_api ) ) 
							{							
								$api_error = $call_api->get_error_message();
							} 
							else 
							{
								if ( ! empty( $call_api->version ) ) 
								{
									$version_latest = $call_api->version;
								}
							}
							$status='Inactive';
							if(is_plugin_active($key))
							{
								$status="Active";
							}
							echo "<tr>";
								echo "<td>".$sno."</td>";
								echo "<td>".$plugin['Name']."</td>";
								echo "<td>".$plugin['Description']."</td>";
								echo "<td>".$plugin['Author']."</td>";
								echo "<td>".$status."</td>";
								echo "<td>".$plugin['Version']."</td>";
								if($version_latest==$plugin['Version'] || $version_latest=='-')
								{
									echo "<td>No</td>";
									echo "<td>-</td>";
								}
								else
								{
									echo "<td>Yes</td>";
									echo "<td>".$version_latest."</td>";
								}
							echo "</tr>";
							$sno++;
						}
					}
				}
				else
				{
					echo "<tr><td colspan='4'>No Plugins found..</td></tr>";
				}
				?>
			</tbody>
		</table>
	</div>
	<?php
}

/* Add action links to plugin list*/
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_export_plugin_action_links' );
function add_export_plugin_action_links ( $links ) {
	 $settings_link = array('<a href="' . admin_url( 'tools.php?page=export-plugins' ) . '">Export plugins</a>');
	return array_merge( $links, $settings_link );
}





function export_plugin_details_in_csv()
{
	$datas=array();
	$headers = array('S.No','Plugin Name','Description','Author','Active/Inactive','Current Version','Update Available','New Version');
	$filename=time()."_"."plugin-details.csv";
	//$filename="test.csv";
	$upload_dir   = wp_upload_dir();
	$file = fopen($upload_dir['basedir'].'/'.$filename,"w");
	fputcsv($file, $headers);	
	
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	
	if ( ! function_exists( 'plugins_api' ) ) {
		  require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	}
	
	$plugins = get_plugins();
	
	$sno=1;
	if($plugins)
	{
		foreach($plugins as $key=>$plugin)
		{
			if($plugin['TextDomain']!='export-plugin-details')
			{
				$version_latest='-';
				$args = array(
					'slug' => $plugin['TextDomain'],
					'fields' => array(
						'version' => true,
					)
				);
			
				$call_api = plugins_api( 'plugin_information', $args );
				/** Check for Errors & Display the results */
				if ( is_wp_error( $call_api ) ) 
				{							
					$api_error = $call_api->get_error_message();
				} 
				else 
				{
					if ( ! empty( $call_api->version ) ) 
					{
						$version_latest = $call_api->version;
					}
				}
				$status='Inactive';
				if(is_plugin_active($key))
				{
					$status="Active";
				}
				
				if($version_latest==$plugin['Version'] || $version_latest=='-')
				{
					$update_available='No';
					$new_version='-';
				}
				else
				{
					$update_available='Yes';
					$new_version=$version_latest;
				}
				$datas[]=$sno."|".$plugin['Name']."|".$plugin['Description']."|".$plugin['Author']."|".$status."|".$plugin['Version']."|".$update_available."|".$new_version;
				$sno++;
			}
		}
	}				
	
	foreach ($datas as $data)
	{
		fputcsv($file,explode('|',$data));
	}							
	fclose($file);
	
	$url=$upload_dir['baseurl'].'/'.$filename;
	$message = "Plugin Details Exported Successfully. Click <a href='".$url."' target='_blank'>here</a> to download";
	echo '<div class="wrap"><div style="width: 380px;margin: 20% 0% 0% 30%;"><div class="updated"><p>'.$message.'</p></div></div></div>';
}
?>