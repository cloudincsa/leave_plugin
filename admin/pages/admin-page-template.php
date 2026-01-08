<?php
/**
 * Admin Page Template with Dashboard Styling
 * Use this template for all admin pages to maintain consistent styling
 */

// This file should be included in each admin page
// Usage: include 'admin-page-template.php';

?>

<style>
/* Dashboard Styling - Applied to all admin pages */
.leave-manager-admin-page {
    background: #fff;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

.page-header {
    background: none;
    color: #333;
    padding: 30px 0px;
    border-radius: 30px 0px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: none;
}

.page-header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: #333;
}

.page-header .subtitle {
    margin: 5px 0 0 0;
    opacity: 0.7;
    font-size: 14px;
    color: #666;
    padding-left: 0px;
}

/* Cards */
.admin-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border-left: 4px solid #4A5FFF;
    margin-bottom: 20px;
}

.admin-card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.admin-card h2 {
    margin: 0 0 15px 0;
    font-size: 18px;
    color: #333;
    font-weight: 700;
}

/* Grid Layout */
.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.admin-grid-2 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* Tabs */
.admin-tabs {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 20px;
    gap: 0;
}

.admin-tab {
    padding: 15px 20px;
    cursor: pointer;
    border: none;
    background: none;
    font-weight: 600;
    color: #666;
    transition: all 0.3s ease;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    font-size: 14px;
}

.admin-tab:hover {
    color: #2172B1;
}

.admin-tab.active {
    color: #2172B1;
    border-bottom-color: #2172B1;
}

/* Forms */
.admin-form {
    max-width: 100%;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group label .required {
    color: #f44336;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-family: inherit;
    font-size: 14px;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #2172B1;
    box-shadow: 0 0 0 3px rgba(33, 114, 177, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

/* Buttons */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 14px;
    display: inline-block;
    text-decoration: none;
}

.btn-primary {
    background: #2172B1;
    color: white;
}

.btn-primary:hover {
    background: #1a5a8a;
    box-shadow: 0 4px 12px rgba(33, 114, 177, 0.3);
}

.btn-secondary {
    background: #e9ecef;
    color: #333;
}

.btn-secondary:hover {
    background: #dee2e6;
}

.btn-success {
    background: #4caf50;
    color: white;
}

.btn-success:hover {
    background: #388e3c;
}

.btn-danger {
    background: #f44336;
    color: white;
}

.btn-danger:hover {
    background: #d32f2f;
}

.btn-small {
    padding: 6px 12px;
    font-size: 12px;
}

/* Tables */
.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.admin-table thead {
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
}

.admin-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.admin-table td {
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
    color: #666;
}

.admin-table tbody tr:hover {
    background: #f8f9fa;
}

.admin-table tbody tr:last-child td {
    border-bottom: none;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-pending {
    background: #fff3cd;
    color: #856404;
}

.badge-approved {
    background: #d4edda;
    color: #155724;
}

.badge-rejected {
    background: #f8d7da;
    color: #721c24;
}

.badge-annual {
    background: #4A5FFF;
    color: #333;
}

.badge-sick {
    background: #f44336;
    color: white;
}

.badge-other {
    background: #2196f3;
    color: white;
}

/* Alerts */
.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid;
}

.alert-success {
    background: #d4edda;
    border-color: #28a745;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}

.alert-warning {
    background: #fff3cd;
    border-color: #4A5FFF;
    color: #856404;
}

.alert-info {
    background: #d1ecf1;
    border-color: #17a2b8;
    color: #0c5460;
}

/* Loading Spinner */
.spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #2172B1;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .admin-grid,
    .admin-grid-2,
    .form-row {
        grid-template-columns: 1fr;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .admin-table {
        font-size: 12px;
    }

    .admin-table th,
    .admin-table td {
        padding: 10px;
    }

    .btn {
        padding: 8px 16px;
        font-size: 12px;
    }
}
</style>
