<?php
/**
 * Header Component with LFCC Logo
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo_url = '/home/ubuntu/wp-fresh/wp-content/uploads/2025/12/lfcc-logo-transparent.png';
$logo_exists = file_exists( $logo_url );
$logo_url_web = '/wp-content/uploads/2025/12/lfcc-logo-transparent.png';
?>

<header class="lm-frontend-header">
	<div class="lm-header-container">
		<div class="lm-logo-section">
			<?php if ( $logo_exists ) : ?>
				<img src="<?php echo esc_url( $logo_url_web ); ?>" alt="LFCC Logo" class="lm-logo">
			<?php else : ?>
				<div class="lm-logo-placeholder">LFCC</div>
			<?php endif; ?>
			<h1 class="lm-header-title">Leave Manager</h1>
		</div>
		
		<?php if ( is_user_logged_in() ) : ?>
			<div class="lm-user-menu">
				<span class="lm-user-name"><?php echo esc_html( wp_get_current_user()->display_name ); ?></span>
				<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="lm-logout-link">Logout</a>
			</div>
		<?php endif; ?>
	</div>
</header>

<style>
.lm-frontend-header {
	background: white;
	border-bottom: 1px solid #e5e7eb;
	padding: 16px 20px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.05);
	position: sticky;
	top: 0;
	z-index: 100;
}

.lm-header-container {
	max-width: 1200px;
	margin: 0 auto;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.lm-logo-section {
	display: flex;
	align-items: center;
	gap: 12px;
}

.lm-logo {
	height: 40px;
	width: auto;
	object-fit: contain;
}

.lm-logo-placeholder {
	width: 40px;
	height: 40px;
	background: #667eea;
	color: white;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 4px;
	font-weight: 600;
	font-size: 12px;
}

.lm-header-title {
	margin: 0;
	font-size: 18px;
	font-weight: 600;
	color: #1f2937;
}

.lm-user-menu {
	display: flex;
	align-items: center;
	gap: 16px;
}

.lm-user-name {
	font-size: 14px;
	color: #6b7280;
	font-weight: 500;
}

.lm-logout-link {
	color: #667eea;
	text-decoration: none;
	font-size: 14px;
	font-weight: 500;
}

.lm-logout-link:hover {
	text-decoration: underline;
}

@media (max-width: 768px) {
	.lm-header-container {
		flex-direction: column;
		gap: 12px;
	}
	
	.lm-header-title {
		font-size: 16px;
	}
}
</style>
