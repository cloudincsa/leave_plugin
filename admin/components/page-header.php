<?php
/**
 * Reusable Page Header Component
 * 
 * This component provides consistent header styling across all admin pages.
 * Based on the Staff Management page layout.
 *
 * Usage:
 * $page_title = 'Page Title';
 * $page_subtitle = 'Page description';
 * $tabs = array(
 *     array('slug' => 'tab1', 'label' => 'Tab 1'),
 *     array('slug' => 'tab2', 'label' => 'Tab 2'),
 * );
 * $current_tab = 'tab1';
 * $base_url = admin_url('admin.php?page=leave-manager-page');
 * include 'components/page-header.php';
 *
 * @package Leave_Manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure required variables are set
$page_title = isset($page_title) ? $page_title : 'Page Title';
$page_subtitle = isset($page_subtitle) ? $page_subtitle : '';
$tabs = isset($tabs) ? $tabs : array();
$current_tab = isset($current_tab) ? $current_tab : '';
$base_url = isset($base_url) ? $base_url : '';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><?php echo esc_html($page_title); ?></h1>
        <?php if ($page_subtitle) : ?>
            <p class="subtitle"><?php echo esc_html($page_subtitle); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($tabs)) : ?>
<!-- Tab Navigation -->
<div class="admin-tabs">
    <?php foreach ($tabs as $tab) : ?>
        <?php
        $active_class = ($current_tab === $tab['slug']) ? 'active' : '';
        $url = add_query_arg('tab', $tab['slug'], $base_url);
        ?>
        <button class="admin-tab <?php echo esc_attr($active_class); ?>" onclick="window.location.href='<?php echo esc_url($url); ?>'">
            <?php echo esc_html($tab['label']); ?>
        </button>
    <?php endforeach; ?>
</div>
<?php endif; ?>
