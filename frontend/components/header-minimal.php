<?php
/**
 * Minimalistic Header Component
 */

$user = leave_manager_get_current_user();
if ( ! $user ) {
    return;
}

// Check if mobile
$is_mobile = wp_is_mobile();
?>

<style>
    :root {
        --primary-color: #667eea;
        --text-dark: #333;
        --text-light: #666;
        --border-color: #e0e0e0;
        --bg-light: #f9f9f9;
    }
    
    .lm-header {
        background: white;
        border-bottom: 1px solid var(--border-color);
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    
    .lm-header-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 70px;
    }
    
    .lm-header-logo {
        font-size: 20px;
        font-weight: 600;
        color: var(--primary-color);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .lm-header-nav {
        display: flex;
        gap: 30px;
        flex: 1;
        margin-left: 40px;
    }
    
    .lm-nav-item {
        text-decoration: none;
        color: var(--text-light);
        font-size: 14px;
        padding: 5px 10px;
        border-radius: 4px;
        transition: all 0.2s;
    }
    
    .lm-nav-item:hover,
    .lm-nav-item.active {
        color: var(--primary-color);
        background: var(--bg-light);
    }
    
    .lm-header-user {
        position: relative;
    }
    
    .lm-user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 4px;
        transition: background 0.2s;
    }
    
    .lm-user-profile:hover {
        background: var(--bg-light);
    }
    
    .lm-user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
    }
    
    .lm-user-name {
        font-size: 14px;
        color: var(--text-dark);
    }
    
    .lm-user-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        min-width: 180px;
        display: none;
        margin-top: 10px;
    }
    
    .lm-user-dropdown.active {
        display: block;
    }
    
    .lm-dropdown-item {
        display: block;
        padding: 12px 16px;
        color: var(--text-dark);
        text-decoration: none;
        font-size: 14px;
        transition: background 0.2s;
        border-bottom: 1px solid var(--border-color);
    }
    
    .lm-dropdown-item:last-child {
        border-bottom: none;
    }
    
    .lm-dropdown-item:hover {
        background: var(--bg-light);
    }
    
    .lm-dropdown-item.logout {
        color: #d32f2f;
    }
    
    .lm-mobile-toggle {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        flex-direction: column;
        gap: 5px;
    }
    
    .lm-mobile-toggle span {
        width: 24px;
        height: 3px;
        background: var(--text-dark);
        border-radius: 2px;
        transition: all 0.3s;
    }
    
    .lm-mobile-nav {
        display: none;
        flex-direction: column;
        gap: 0;
        background: var(--bg-light);
        border-top: 1px solid var(--border-color);
        padding: 10px 0;
    }
    
    .lm-mobile-nav.active {
        display: flex;
    }
    
    .lm-mobile-nav .lm-nav-item {
        padding: 12px 20px;
        border-radius: 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    @media (max-width: 768px) {
        .lm-header-container {
            height: 60px;
        }
        
        .lm-header-nav {
            display: none;
        }
        
        .lm-mobile-toggle {
            display: flex;
        }
        
        .lm-header-logo {
            font-size: 18px;
        }
    }
</style>

<header class="lm-header">
    <div class="lm-header-container">
        <!-- Logo -->
        <a href="<?php echo esc_url( leave_manager_dashboard_url() ); ?>" class="lm-header-logo">
            📋 Leave Manager
        </a>
        
        <!-- Desktop Navigation -->
        <nav class="lm-header-nav">
            <a href="<?php echo esc_url( home_url( '/leave-management/dashboard/' ) ); ?>" class="lm-nav-item">Dashboard</a>
            <a href="<?php echo esc_url( home_url( '/leave-management/request/' ) ); ?>" class="lm-nav-item">Request</a>
            <a href="<?php echo esc_url( home_url( '/leave-management/balance/' ) ); ?>" class="lm-nav-item">Balance</a>
            <a href="<?php echo esc_url( home_url( '/leave-management/calendar/' ) ); ?>" class="lm-nav-item">Calendar</a>
            <a href="<?php echo esc_url( home_url( '/leave-management/history/' ) ); ?>" class="lm-nav-item">History</a>
        </nav>
        
        <!-- Mobile Menu Toggle -->
        <button class="lm-mobile-toggle" id="lm-mobile-toggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <!-- User Menu -->
        <div class="lm-header-user">
            <div class="lm-user-profile" id="lm-user-profile">
                <div class="lm-user-avatar">
                    <?php echo strtoupper( substr( $user->first_name, 0, 1 ) ); ?>
                </div>
                <span class="lm-user-name"><?php echo esc_html( $user->first_name ); ?></span>
            </div>
            
            <div class="lm-user-dropdown" id="lm-user-dropdown">
                <a href="<?php echo esc_url( home_url( '/leave-management/profile/' ) ); ?>" class="lm-dropdown-item">Profile</a>
                <a href="<?php echo esc_url( home_url( '/leave-management/settings/' ) ); ?>" class="lm-dropdown-item">Settings</a>
                <a href="<?php echo esc_url( home_url( '/leave-manager/logout/' ) ); ?>" class="lm-dropdown-item logout">Logout</a>
            </div>
        </div>
    </div>
    
    <!-- Mobile Navigation -->
    <nav class="lm-mobile-nav" id="lm-mobile-nav">
        <a href="<?php echo esc_url( home_url( '/leave-management/dashboard/' ) ); ?>" class="lm-nav-item">Dashboard</a>
        <a href="<?php echo esc_url( home_url( '/leave-management/request/' ) ); ?>" class="lm-nav-item">Request</a>
        <a href="<?php echo esc_url( home_url( '/leave-management/balance/' ) ); ?>" class="lm-nav-item">Balance</a>
        <a href="<?php echo esc_url( home_url( '/leave-management/calendar/' ) ); ?>" class="lm-nav-item">Calendar</a>
        <a href="<?php echo esc_url( home_url( '/leave-management/history/' ) ); ?>" class="lm-nav-item">History</a>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileToggle = document.getElementById('lm-mobile-toggle');
    const mobileNav = document.getElementById('lm-mobile-nav');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            mobileNav.classList.toggle('active');
        });
    }
    
    // User dropdown
    const userProfile = document.getElementById('lm-user-profile');
    const userDropdown = document.getElementById('lm-user-dropdown');
    
    if (userProfile) {
        userProfile.addEventListener('click', function() {
            userDropdown.classList.toggle('active');
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (userProfile && !event.target.closest('.lm-header-user')) {
            userDropdown.classList.remove('active');
        }
    });
});
</script>
