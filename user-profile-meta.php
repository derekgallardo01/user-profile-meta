<?php
/*
Plugin Name: User Profile Meta Manager
Plugin URI: http://wordpress.org/plugins/user-profile-meta
Description: Allows administrators to quick manage custom user profile meta entries.
Version: 1.02
Author: Danny Vink
Author URI: http://www.dannyvink.com/
*/

/**
 * Add the custom user meta inputs
 *
 * @param object $user
 */

define("UPM_BASE_DIR", plugin_dir_url(__FILE__));

add_action( 'show_user_profile', 'insert_user_profile' );
add_action( 'edit_user_profile', 'insert_user_profile' );

function insert_user_profile( $user ) {
	if ( ! current_user_can( 'edit_users' ) ) return;

	$metas = get_user_meta($user->ID); ?>

	<style type="text/css">
	.user-profile-meta-table tbody td {
		padding-left: 0;
		vertical-align: top;
	}
	.user-profile-meta-table input[type='text'] {
		width: 100%;
	}
	.user-profile-meta-table input.user-profile-meta-value {
		width: 90%;
	}
	#user-profile-meta-dump, #user-profile-meta-fields {
		display: none;
	}
	.user-profile-meta-box {
		border: 1px solid #ccc;
		max-height: 500px;
		overflow: auto;
		padding: 10px;
	}
	.user-profile-meta-active {
		display: block !important;
	}
	.delete-user-profile-meta, .delete-user-profile-meta:hover, .delete-user-profile-meta:visited {
		color: #FF0000;
		text-decoration: none;
	}
	</style>

	<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#user-meta-fields").click(function() {
			jQuery("#user-profile-meta-dump").slideUp('fast', function() {
				jQuery("#user-profile-meta-fields").slideToggle('fast');	
			});
		});
		jQuery("#user-meta-dump").click(function() {
			jQuery("#user-profile-meta-fields").slideUp('fast', function() {
				jQuery("#user-profile-meta-dump").slideToggle('fast');	
			});
			
		});

		jQuery(document).on("click", ".delete-user-profile-meta", function() {
			var row = jQuery(this).parents('tr');
			var mkey = row.find('.user-profile-meta-key').data('meta-key');
			if (confirm("Are you sure you want to delete '" + mkey + "'?")) {
				jQuery.post(ajaxurl, {action: "delete_user_profile_meta", meta_key : mkey, user_id : <?php echo $user->ID; ?>}, function(resp) {
					alert(resp.response);
					if (resp.success) {
						row.fadeOut('fast', function() {
							row.remove();
						})
					}
				}, "json");	
			}
		});

		jQuery("#add-user-profile-meta").click(function() {
			addEditMeta();
		});

		jQuery(document).on("click", ".edit-user-profile-meta", function() {
			var row = jQuery(this).parents('tr');
			var mkey = row.find('.user-profile-meta-key').data('meta-key');
			addEditMeta(mkey);
		});

		function addEditMeta(mkey) {
			var edit = true;
			if (mkey == null) {
				mkey = prompt("Please provide a meta key. If this meta key exists, it will be overwritten.");	
				edit = false;
			}
			if (mkey != null) {
				var mvalue = prompt("Please provide a meta value.");
				jQuery.post(ajaxurl, {action: "add_user_profile_meta", meta_key : mkey, meta_value : mvalue, user_id : <?php echo $user->ID; ?>}, function(resp) {
					alert(resp.response);
					if (resp.success) {
						if (edit) {
							jQuery(".user-profile-meta-key[data-meta-key='" + mkey + "']").parents('tr').find('.user-profile-meta-value').attr('value', mvalue);
						} else {
							var clone = jQuery(".user-profile-meta-table tbody tr").last().clone();
							clone.find(".user-profile-meta-key").data('meta-key', mkey);
							clone.find(".user-profile-meta-value").data('meta-value', mvalue);
							clone.find(".user-profile-meta-key").attr('value', mkey);
							clone.find(".user-profile-meta-value").attr('value', mvalue);
							jQuery(".user-profile-meta-table tbody").append(clone);	
						}
					}
				}, "json");
			}
		}
	});
	</script>

	<div id="user-profile-meta-container">
		<button type="button" class="button" id="user-meta-fields">User Meta Fields</button>
		<button type="button" class="button" id="user-meta-dump">User Meta Dump</button>

		<button type="button" class="button" id="add-user-profile-meta">Add Meta Field</button>
		<div id="user-profile-meta-fields">
			<div class="user-profile-meta-box">
				<table class="form-table user-profile-meta-table">
					<thead>
						<tr>
							<th>Meta Key</th>
							<th>Meta Value</th>
						</tr>
					</thead>
					<tbody>
						<?php if ($metas) foreach ($metas as $key => $value) : ?>
						<tr>
							<td>
								<input type="text" class="user-profile-meta-key" data-meta-key="<?php echo $key; ?>" value="<?php echo $key; ?>" placeholder="Meta Key" readonly="readonly" />
							</td>
							<td>
								<?php foreach ($value as $key => $v) : ?>
								<input type="text" class="user-profile-meta-value" data-meta-value="<?php echo $v; ?>" value="<?php echo $v; ?>" placeholder="Meta Value" readonly="readonly" />
								<a title="Edit Meta Value" href="javascript:void(0);" class="edit-user-profile-meta"><img src="<?php echo UPM_BASE_DIR; ?>images/edit.png" alt="Edit Meta" /></a>
								<a title="Delete Meta" href="javascript:void(0);" class="delete-user-profile-meta"><img src="<?php echo UPM_BASE_DIR; ?>images/delete.png" alt="Delete Meta" /></a>
								<br />
								<?php endforeach; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div id="user-profile-meta-dump">
			<div class="user-profile-meta-box">
				<pre><?php var_dump($metas); ?></pre>
			</div>
		</div>

	</div>

<?php
}

add_action( 'wp_ajax_delete_user_profile_meta', 'user_profile_delete_meta' );
function user_profile_delete_meta() {
	if ( ! current_user_can( 'edit_users' ) ) return;
	$json = array("success" => false, "response" => "An error has occured. Please try again.");

	if (isset($_POST["user_id"]) && isset($_POST["meta_key"])) {
		$user = get_user_by('id', intval($_POST["user_id"]));
		if (delete_user_meta($user->ID, $_POST["meta_key"])) {
			$json["response"] = "Successfully removed '" . $_POST["meta_key"] . "' from '" . $user->user_login . "'!";
			$json["success"] = true;
		}
	}
	echo json_encode($json);
	die();
}

add_action( 'wp_ajax_add_user_profile_meta', 'user_profile_add_meta' );
function user_profile_add_meta() {
	if ( ! current_user_can( 'edit_users' ) ) return;
	$json = array("success" => false, "response" => "An error has occured. Please try again.");

	if (isset($_POST["user_id"]) && isset($_POST["meta_key"]) && isset($_POST["meta_value"])) {
		$json["debug"] = $_POST["meta_value"];
		$user = get_user_by('id', intval($_POST["user_id"]));
		update_user_meta($user->ID, $_POST["meta_key"], $_POST["meta_value"]);
		$json["response"] = "Successfully updated '" . $_POST["meta_key"] . "' for '" . $user->user_login . "'!";
		$json["success"] = true;
	}
	echo json_encode($json);
	die();
}