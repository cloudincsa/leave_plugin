<?php
/**
 * Roles and Permissions Page
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized' );
}

// Get instances
$db = new Leave_Manager_Database();
$logger = new Leave_Manager_Logger();
$permissions = new Leave_Manager_Permissions( $db, $logger );

$roles = $permissions->get_roles();
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="roles-permissions-container">
		<h2>Available Roles and Permissions</h2>
		
		<?php foreach ( $roles as $role_key => $role_data ) : ?>
			<div class="role-card">
				<h3><?php echo esc_html( $role_data['label'] ); ?></h3>
				<p class="role-description"><?php echo esc_html( $role_data['description'] ); ?></p>
				
				<div class="capabilities-list">
					<h4>Capabilities:</h4>
					<ul>
						<?php foreach ( $role_data['capabilities'] as $capability ) : ?>
							<li>
								<span class="capability-name"><?php echo esc_html( str_replace( '_', ' ', ucfirst( $capability ) ) ); ?></span>
								<span class="capability-key"><?php echo esc_html( $capability ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<style>
	.roles-permissions-container {
		margin-top: 20px;
	}

	.role-card {
		background: white;
		border: 1px solid #ccc;
		border-radius: 5px;
		padding: 20px;
		margin-bottom: 20px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.role-card h3 {
		margin-top: 0;
		color: #0073aa;
		border-bottom: 2px solid #0073aa;
		padding-bottom: 10px;
	}

	.role-description {
		color: #666;
		font-style: italic;
		margin: 10px 0 15px 0;
	}

	.capabilities-list h4 {
		margin-top: 15px;
		margin-bottom: 10px;
		color: #333;
	}

	.capabilities-list ul {
		list-style: none;
		padding: 0;
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
		gap: 10px;
	}

	.capabilities-list li {
		background-color: #f9f9f9;
		padding: 10px;
		border-left: 3px solid #0073aa;
		border-radius: 3px;
	}

	.capability-name {
		display: block;
		font-weight: 600;
		color: #333;
		margin-bottom: 3px;
	}

	.capability-key {
		display: block;
		font-size: 12px;
		color: #999;
		font-family: monospace;
	}
</style>
