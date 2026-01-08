<?php
/**
 * Frontend Wrapper Class
 * Handles frontend page rendering without WordPress header/footer
 */

if (!defined('ABSPATH')) {
    exit;
}

class Leave_Manager_Frontend_Wrapper {

    /**
     * Initialize frontend wrapper
     */
    public static function init() {
        add_action('wp_head', array(__CLASS__, 'add_custom_styles'), 999);
        add_action('wp_footer', array(__CLASS__, 'add_custom_scripts'));
        add_filter('body_class', array(__CLASS__, 'add_body_class'));
    }

    /**
     * Add body class for Leave Manager pages
     */
    public static function add_body_class($classes) {
        if (self::is_leave_manager_page()) {
            $classes[] = 'leave-manager-page';
        }
        return $classes;
    }

    /**
     * Add custom styles to hide WordPress header/footer
     */
    public static function add_custom_styles() {
        if (self::is_leave_manager_page()) {
            ?>
            <style>
                /* Hide WordPress header and footer */
                #wpadminbar,
                .wp-admin,
                .wp-site-blocks,
                .wp-block-template-part,
                body > header,
                body > footer,
                .wp-block-site-title,
                .wp-block-site-tagline,
                .wp-block-navigation,
                .wp-block-social-links,
                .wp-block-latest-posts,
                .wp-block-calendar,
                .wp-block-categories,
                .wp-block-archives,
                .wp-block-search,
                .wp-block-rss,
                .wp-block-tag-cloud,
                .wp-block-latest-comments {
                    display: none !important;
                }

                /* Hide Twenty Twenty-Five theme elements */
                .site-header,
                .site-footer,
                .site-branding,
                .wp-block-template-part.header-footer,
                .wp-block-template-part.footer,
                .wp-block-template-part.header,
                header.wp-block-template-part,
                footer.wp-block-template-part {
                    display: none !important;
                }

                /* Full width layout */
                body {
                    margin: 0 !important;
                    padding: 0 !important;
                    background: #f5f7fa !important;
                }

                .wp-site-blocks {
                    display: none !important;
                }

                main {
                    margin: 0 !important;
                    padding: 0 !important;
                }

                .entry-content {
                    margin: 0 !important;
                    padding: 0 !important;
                }

                /* Leave Manager page styling */
                .leave-manager-page-wrapper {
                    min-height: 100vh;
                    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                    padding: 40px 20px;
                    max-width: 1200px;
                    margin: 0 auto;
                }

                /* Ensure content is visible */
                .leave-manager-page .post-content,
                .leave-manager-page .page-content,
                .leave-manager-page .entry-content,
                .leave-manager-page main,
                .leave-manager-page article {
                    color: #333;
                    font-size: 16px;
                    line-height: 1.6;
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                }

                .leave-manager-page h1,
                .leave-manager-page h2 {
                    color: #4A5FFF;
                    margin-top: 20px;
                    margin-bottom: 15px;
                }

                .leave-manager-page h1 {
                    font-size: 32px;
                    font-weight: 700;
                }

                .leave-manager-page h2 {
                    font-size: 24px;
                    font-weight: 600;
                }

                /* Remove default WordPress margins and padding */
                .post,
                .page,
                article {
                    margin: 0 !important;
                    padding: 0 !important;
                    background: transparent !important;
                    border: none !important;
                }

                /* Hide comments section */
                .comments-area,
                .comment-respond,
                .post-navigation,
                .posts-navigation,
                .pagination {
                    display: none !important;
                }

                /* Hide sidebar */
                .sidebar,
                .widget-area,
                aside {
                    display: none !important;
                }

                /* Responsive adjustments */
                @media (max-width: 768px) {
                    .leave-manager-page-wrapper {
                        padding: 0 !important;
                    }
                }
            </style>
            <?php
        }
    }

    /**
     * Add custom scripts
     */
    public static function add_custom_scripts() {
        if (self::is_leave_manager_page()) {
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Remove WordPress admin bar
                    const adminBar = document.getElementById('wpadminbar');
                    if (adminBar) {
                        adminBar.remove();
                    }

                    // Adjust body padding if admin bar was removed
                    document.body.style.paddingTop = '0';

                    // Hide all template parts
                    const templateParts = document.querySelectorAll('.wp-block-template-part');
                    templateParts.forEach(part => {
                        part.style.display = 'none';
                    });

                    // Hide site header and footer
                    const header = document.querySelector('.site-header, header.wp-block-template-part');
                    if (header) {
                        header.style.display = 'none';
                    }

                    const footer = document.querySelector('.site-footer, footer.wp-block-template-part');
                    if (footer) {
                        footer.style.display = 'none';
                    }
                });
            </script>
            <?php
        }
    }

    /**
     * Check if current page is a Leave Manager page
     */
    public static function is_leave_manager_page() {
        global $post;
        
        if (!$post) {
            return false;
        }

        // Check if page slug starts with 'leave-management'
        $page_slugs = array(
            'leave-management',
            'leave-management-dashboard',
            'leave-management-calendar',
            'leave-management-request',
            'leave-management-balance',
            'leave-management-history',
            'employee-signup'
        );

        foreach ($page_slugs as $slug) {
            if (strpos($post->post_name, str_replace('leave-management-', '', $slug)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render page header with logo
     */
    public static function render_page_header() {
        $logo_url = get_option('leave_manager_logo_url');
        if (!$logo_url) {
            $logo_url = LEAVE_MANAGER_PLUGIN_URL . 'assets/images/default-logo.png';
        }
        
        $org_name = get_option('leave_manager_organization_name', 'Leave Manager');
        ?>
        <div class="leave-manager-page-header">
            <div class="header-container">
                <div class="header-logo">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($org_name); ?>" class="logo-image">
                </div>
                <div class="header-title">
                    <h1><?php echo esc_html($org_name); ?></h1>
                </div>
                <div class="header-actions">
                    <a href="<?php echo home_url('/index.php/leave-management/'); ?>" class="header-link">Dashboard</a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="header-link">Logout</a>
                </div>
            </div>
        </div>

        <style>
            .leave-manager-page-header {
                background: white;
                border-bottom: 1px solid #eee;
                padding: 16px 0;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                position: sticky;
                top: 0;
                z-index: 100;
            }

            .header-container {
                max-width: 1400px;
                margin: 0 auto;
                padding: 0 30px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 30px;
            }

            .header-logo {
                flex-shrink: 0;
            }

            .logo-image {
                max-height: 50px;
                width: auto;
            }

            .header-title h1 {
                margin: 0;
                font-size: 20px;
                color: #1a1a1a;
                font-weight: 600;
            }

            .header-actions {
                display: flex;
                gap: 20px;
                margin-left: auto;
            }

            .header-link {
                color: #666;
                text-decoration: none;
                font-weight: 500;
                transition: color 0.3s ease;
            }

            .header-link:hover {
                color: #4A5FFF;
            }

            @media (max-width: 768px) {
                .header-container {
                    flex-direction: column;
                    align-items: flex-start;
                    padding: 0 16px;
                }

                .header-actions {
                    margin-left: 0;
                    width: 100%;
                }
            }
        </style>
        <?php
    }
}

// Initialize
Leave_Manager_Frontend_Wrapper::init();
