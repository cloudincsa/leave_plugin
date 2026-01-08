# Phase 2: Architecture Refactoring Roadmap

## 1. Current State Analysis

### 1.1 Codebase Metrics

| Metric | Value |
| :--- | :--- |
| Total Class Files | 91 |
| Handler Classes | 21 |
| Manager Classes | 15 |
| Files with direct $wpdb access | 99 |
| Total $wpdb calls | ~378 |

### 1.2 Database Access Distribution

The top files with the most database access are:
1. `class-admin-ajax-handler.php` - 129 $wpdb references
2. `class-database-migration.php` - 61 $wpdb references
3. `class-complete-ajax-handler.php` - 57 $wpdb references
4. `class-approval-task-manager.php` - 49 $wpdb references
5. `class-department-approval-system.php` - 47 $wpdb references

### 1.3 Core Entities (Active Tables)

| Entity | Table | Rows | Priority |
| :--- | :--- | :--- | :--- |
| Settings | settings | 37 | High |
| Leave Requests | leave_requests | 7 | High |
| Leave Users | leave_users | 7 | High |
| Sessions | sessions | 6 | High |
| Departments | departments | 4 | High |
| Leave Balances | leave_balances | 4 | High |
| Leave Types | leave_types | 4 | High |
| Users | users | 2 | High |
| Leave Policies | leave_policies | 1 | High |

## 2. Target Architecture

### 2.1 Namespace Structure

```
LeaveManager\
├── Core\
│   ├── Plugin.php
│   ├── Activator.php
│   ├── Deactivator.php
│   └── Autoloader.php
├── Database\
│   ├── Connection.php
│   ├── QueryBuilder.php
│   └── Migration.php
├── Repository\
│   ├── AbstractRepository.php
│   ├── LeaveRequestRepository.php
│   ├── LeaveUserRepository.php
│   ├── LeaveBalanceRepository.php
│   ├── LeaveTypeRepository.php
│   ├── DepartmentRepository.php
│   ├── SessionRepository.php
│   └── SettingsRepository.php
├── Model\
│   ├── AbstractModel.php
│   ├── LeaveRequest.php
│   ├── LeaveUser.php
│   ├── LeaveBalance.php
│   ├── LeaveType.php
│   ├── Department.php
│   └── Session.php
├── Service\
│   ├── AuthenticationService.php
│   ├── LeaveRequestService.php
│   ├── ApprovalService.php
│   ├── EmailService.php
│   └── ReportService.php
├── Handler\
│   ├── AjaxHandler.php
│   └── ShortcodeHandler.php
├── Admin\
│   ├── AdminMenu.php
│   └── Pages\
└── Frontend\
    ├── FrontendPages.php
    └── Shortcodes.php
```

### 2.2 Repository Pattern

Each repository will:
- Encapsulate all database access for its entity
- Provide CRUD operations
- Handle query building and sanitization
- Return Model objects instead of raw arrays

### 2.3 Migration Strategy

We will use a **gradual migration** approach:
1. Create new namespaced classes in `src/` directory
2. Create facade classes that bridge old and new code
3. Gradually move functionality from old classes to new
4. Keep old classes as thin wrappers during transition
5. Remove old classes once all references are updated

## 3. Implementation Plan

### Phase 2.2: Create PSR-4 Namespace Structure
- Create `src/` directory with proper namespace structure
- Create base abstract classes (AbstractRepository, AbstractModel)
- Update `composer.json` autoload configuration
- Create Container/DI class for dependency injection

### Phase 2.3: Implement Database Abstraction Layer
- Create `Connection` class wrapping $wpdb
- Create `QueryBuilder` for fluent query construction
- Implement `AbstractRepository` with common CRUD methods
- Create repositories for high-priority entities:
  - LeaveRequestRepository
  - LeaveUserRepository
  - LeaveBalanceRepository

### Phase 2.4: Refactor Core Classes
- Refactor `class-leave-request-handler.php` to use repositories
- Refactor `class-leave-approval-handler.php` to use repositories
- Update authentication classes to use SessionRepository
- Create service classes for business logic

### Phase 2.5: Standardize Database Schema
- Rename inconsistent columns (leave_type → leave_type_id)
- Add proper foreign key naming conventions
- Create migration script for schema changes
- Update all queries to use new column names

### Phase 2.6: Feature Audit
- Analyze each empty table for code references
- Remove tables with no code references
- Document tables kept for future features

## 4. Risk Mitigation

| Risk | Mitigation |
| :--- | :--- |
| Breaking existing functionality | Run E2E tests after each change |
| Data loss during schema changes | Create backups before migrations |
| Incomplete refactoring | Use facade pattern for gradual migration |
| Performance regression | Benchmark before and after |

## 5. Success Criteria

- [ ] All new code uses PSR-4 namespaces
- [ ] Database access is abstracted through repositories
- [ ] No direct $wpdb calls in handler/service classes
- [ ] All E2E tests pass
- [ ] Schema naming is consistent
- [ ] Unused tables are removed
