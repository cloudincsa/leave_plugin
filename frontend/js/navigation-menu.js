/**
 * Leave Manager - Professional Persistent Navigation Menu
 * 
 * Handles menu interactions, mobile toggle, dropdown menus, and scroll effects
 */

(function() {
  'use strict';

  // Configuration
  const config = {
    navSelector: '.leave-manager-nav, .lm-navigation, .frontend-navigation',
    toggleSelector: '.nav-toggle, .lm-nav-toggle, .mobile-menu-toggle',
    menuSelector: '.nav-menu, .lm-nav-menu',
    dropdownSelector: '.nav-dropdown, .lm-nav-dropdown',
    dropdownToggleSelector: '.dropdown-toggle, .lm-dropdown-toggle',
    dropdownMenuSelector: '.dropdown-menu, .lm-dropdown-menu',
    linkSelector: '.nav-link, .lm-nav-link, .nav-item a',
    scrollThreshold: 50
  };

  // State
  let state = {
    mobileMenuOpen: false,
    activeDropdown: null,
    scrolled: false
  };

  /**
   * Initialize the navigation menu
   */
  function init() {
    const nav = document.querySelector(config.navSelector);
    if (!nav) return;

    setupEventListeners();
    setActiveLink();
    handleScroll();
  }

  /**
   * Setup all event listeners
   */
  function setupEventListeners() {
    // Mobile menu toggle
    const toggle = document.querySelector(config.toggleSelector);
    if (toggle) {
      toggle.addEventListener('click', toggleMobileMenu);
    }

    // Dropdown menus
    const dropdowns = document.querySelectorAll(config.dropdownSelector);
    dropdowns.forEach(dropdown => {
      const toggle = dropdown.querySelector(config.dropdownToggleSelector);
      if (toggle) {
        toggle.addEventListener('click', (e) => {
          e.preventDefault();
          toggleDropdown(dropdown);
        });
      }
    });

    // Navigation links
    const links = document.querySelectorAll(config.linkSelector);
    links.forEach(link => {
      link.addEventListener('click', handleLinkClick);
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', handleDocumentClick);

    // Scroll effects
    window.addEventListener('scroll', handleScroll);

    // Close menu on window resize
    window.addEventListener('resize', handleResize);
  }

  /**
   * Toggle mobile menu
   */
  function toggleMobileMenu() {
    const toggle = document.querySelector(config.toggleSelector);
    const menu = document.querySelector(config.menuSelector);

    if (!toggle || !menu) return;

    state.mobileMenuOpen = !state.mobileMenuOpen;
    toggle.classList.toggle('active', state.mobileMenuOpen);
    menu.classList.toggle('active', state.mobileMenuOpen);
  }

  /**
   * Close mobile menu
   */
  function closeMobileMenu() {
    const toggle = document.querySelector(config.toggleSelector);
    const menu = document.querySelector(config.menuSelector);

    if (!toggle || !menu) return;

    state.mobileMenuOpen = false;
    toggle.classList.remove('active');
    menu.classList.remove('active');
  }

  /**
   * Toggle dropdown menu
   */
  function toggleDropdown(dropdown) {
    const isOpen = dropdown.classList.contains('open');
    
    // Close other dropdowns
    if (!isOpen && state.activeDropdown && state.activeDropdown !== dropdown) {
      state.activeDropdown.classList.remove('open');
    }

    // Toggle current dropdown
    dropdown.classList.toggle('open', !isOpen);
    state.activeDropdown = !isOpen ? dropdown : null;

    // On mobile, show/hide the dropdown menu
    if (window.innerWidth <= 768) {
      const menu = dropdown.querySelector(config.dropdownMenuSelector);
      if (menu) {
        menu.classList.toggle('show', !isOpen);
      }
    }
  }

  /**
   * Handle link clicks
   */
  function handleLinkClick(e) {
    const link = e.currentTarget;
    
    // Update active state
    const links = document.querySelectorAll(config.linkSelector);
    links.forEach(l => l.classList.remove('active'));
    link.classList.add('active');

    // Close mobile menu if open
    if (state.mobileMenuOpen) {
      closeMobileMenu();
    }
  }

  /**
   * Handle document clicks (close menu when clicking outside)
   */
  function handleDocumentClick(e) {
    const nav = document.querySelector(config.navSelector);
    if (!nav) return;

    // Check if click is inside nav
    if (nav.contains(e.target)) return;

    // Close mobile menu
    if (state.mobileMenuOpen) {
      closeMobileMenu();
    }

    // Close dropdowns
    const dropdowns = document.querySelectorAll(config.dropdownSelector);
    dropdowns.forEach(dropdown => {
      dropdown.classList.remove('open');
      const menu = dropdown.querySelector(config.dropdownMenuSelector);
      if (menu) {
        menu.classList.remove('show');
      }
    });
    state.activeDropdown = null;
  }

  /**
   * Handle scroll effects
   */
  function handleScroll() {
    const nav = document.querySelector(config.navSelector);
    if (!nav) return;

    const scrolled = window.scrollY > config.scrollThreshold;
    
    if (scrolled !== state.scrolled) {
      state.scrolled = scrolled;
      nav.classList.toggle('scrolled', scrolled);
    }
  }

  /**
   * Handle window resize
   */
  function handleResize() {
    // Close mobile menu on resize to desktop
    if (window.innerWidth > 768 && state.mobileMenuOpen) {
      closeMobileMenu();
    }
  }

  /**
   * Set active link based on current page
   */
  function setActiveLink() {
    const currentPath = window.location.pathname;
    const links = document.querySelectorAll(config.linkSelector);

    links.forEach(link => {
      const href = link.getAttribute('href');
      if (href && currentPath.includes(href)) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  }

  /**
   * Public API
   */
  window.LeaveManagerNav = {
    init: init,
    toggleMenu: toggleMobileMenu,
    closeMenu: closeMobileMenu,
    setActive: setActiveLink
  };

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
