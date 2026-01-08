<?php
/**
 * Leave Manager - Professional Persistent Navigation Menu Template
 * 
 * This template displays the persistent navigation menu on all frontend pages.
 * 
 * @package LeaveManager
 * @subpackage Frontend
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$user_role = !empty($current_user->roles) ? $current_user->roles[0] : 'subscriber';
$is_employee = in_array($user_role, ['subscriber', 'contributor']);
$is_manager = in_array($user_role, ['editor', 'author']);
$is_admin = in_array($user_role, ['administrator']);

// Get menu items
$menu_items = apply_filters('leave_manager_nav_items', [
    [
        'label' => 'Dashboard',
        'url' => home_url('/leave-management/'),
        'icon' => '📊',
        'show' => true,
    ],
    [
        'label' => 'My Requests',
        'url' => home_url('/leave-management/requests/'),
        'icon' => '📋',
        'show' => $is_employee,
    ],
    [
        'label' => 'Team',
        'url' => home_url('/leave-management/team/'),
        'icon' => '👥',
        'show' => $is_manager || $is_admin,
        'submenu' => [
            [
                'label' => 'Team Requests',
                'url' => home_url('/leave-management/team-requests/'),
            ],
            [
                'label' => 'Team Calendar',
                'url' => home_url('/leave-management/team-calendar/'),
            ],
        ],
    ],
    [
        'label' => 'Reports',
        'url' => home_url('/leave-management/reports/'),
        'icon' => '📈',
        'show' => $is_manager || $is_admin,
    ],
    [
        'label' => 'Settings',
        'url' => home_url('/leave-management/settings/'),
        'icon' => '⚙️',
        'show' => $is_employee,
    ],
]);

// Filter out hidden items
$menu_items = array_filter($menu_items, function($item) {
    return $item['show'] !== false;
});
?>

<nav class="leave-manager-nav" role="navigation" aria-label="Main Navigation">
    <div class="nav-wrapper">
        <!-- Brand / Logo -->
        <a href="<?php echo esc_url(home_url('/leave-management/')); ?>" class="nav-brand">
            <span class="nav-brand-icon">LM</span>
            <span>Leave Manager</span>
        </a>

        <!-- Mobile Menu Toggle -->
        <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Navigation Menu -->
        <ul class="nav-menu" role="menubar">
            <?php foreach ($menu_items as $item) : ?>
                <?php
                $has_submenu = !empty($item['submenu']) && is_array($item['submenu']);
                $current_page = $_SERVER['REQUEST_URI'];
                $is_active = strpos($current_page, $item['url']) !== false;
                ?>

                <li class="nav-item <?php echo $has_submenu ? 'nav-dropdown' : ''; ?>" role="none">
                    <?php if ($has_submenu) : ?>
                        <!-- Dropdown Item -->
                        <button class="nav-link dropdown-toggle" role="menuitem" aria-haspopup="true" aria-expanded="false">
                            <?php if (!empty($item['icon'])) : ?>
                                <span class="nav-icon"><?php echo esc_html($item['icon']); ?></span>
                            <?php endif; ?>
                            <span><?php echo esc_html($item['label']); ?></span>
                        </button>

                        <!-- Dropdown Menu -->
                        <div class="dropdown-menu" role="menu">
                            <?php foreach ($item['submenu'] as $submenu_item) : ?>
                                <a href="<?php echo esc_url($submenu_item['url']); ?>" class="dropdown-item" role="menuitem">
                                    <?php echo esc_html($submenu_item['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <!-- Regular Link -->
                        <a href="<?php echo esc_url($item['url']); ?>" class="nav-link <?php echo $is_active ? 'active' : ''; ?>" role="menuitem">
                            <?php if (!empty($item['icon'])) : ?>
                                <span class="nav-icon"><?php echo esc_html($item['icon']); ?></span>
                            <?php endif; ?>
                            <span><?php echo esc_html($item['label']); ?></span>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Navigation Actions -->
        <div class="nav-actions">
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(home_url('/leave-management/profile/')); ?>" class="nav-button">
                    <?php echo esc_html($current_user->display_name); ?>
                </a>
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="nav-button">
                    Logout
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url(wp_login_url()); ?>" class="nav-button">
                    Login
                </a>
                <a href="<?php echo esc_url(home_url('/leave-management/signup/')); ?>" class="nav-button primary">
                    Sign Up
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
    /* Inline styles for critical rendering path */
    .leave-manager-nav {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
        border-bottom: 1px solid #e0e0e0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .nav-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 70px;
    }

    .nav-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        font-size: 20px;
        font-weight: 700;
        color: #4A5FFF;
        white-space: nowrap;
    }

    .nav-brand-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #4A5FFF 0%, #667EEA 100%);
        border-radius: 8px;
        color: white;
        font-weight: 700;
        font-size: 16px;
    }

    .nav-menu {
        display: flex;
        align-items: center;
        gap: 0;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 20px;
        color: #666666;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        background: none;
        border: none;
        cursor: pointer;
        position: relative;
        white-space: nowrap;
        transition: color 200ms ease-in-out;
    }

    .nav-link:hover,
    .nav-link.active {
        color: #4A5FFF;
    }

    .nav-actions {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .nav-button {
        padding: 8px 16px;
        background: transparent;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        color: #666666;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: all 200ms ease-in-out;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
    }

    .nav-button:hover {
        background: #f5f5f5;
        border-color: #d0d0d0;
        color: #4A5FFF;
    }

    .nav-button.primary {
        background: #4A5FFF;
        border-color: #4A5FFF;
        color: white;
    }

    .nav-button.primary:hover {
        background: #3A4FE8;
        border-color: #3A4FE8;
        box-shadow: 0 4px 12px rgba(74, 95, 255, 0.3);
    }

    @media (max-width: 768px) {
        .nav-wrapper {
            padding: 0 16px;
            height: 60px;
        }

        .nav-menu {
            position: absolute;
            top: 60px;
            left: 0;
            right: 0;
            flex-direction: column;
            background: white;
            border-bottom: 1px solid #e0e0e0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 300ms ease-in-out;
        }

        .nav-menu.active {
            max-height: 500px;
        }

        .nav-button {
            display: none;
        }

        .nav-button.primary {
            display: inline-flex;
        }
    }
</style>
