# 🧠 Wahmy Backend — Architectural Rules & Decision Ledger

> **Purpose:** This file acts as the persistent architectural memory and decision ledger of the Wahmy backend project. It prevents re-analysis of finalized decisions, preserves architectural boundaries, and stores non-negotiable technical constraints.

> **Last Updated:** 2026-02-05

---

## 🧠 PROJECT CORE PRINCIPLES

### Framework & Dependencies
- **Laravel 12** must NEVER be downgraded
- **Filament v4** is the only allowed admin panel version
- **Sanctum** is the only authentication mechanism
- `minimum-stability` must remain `"stable"` in `composer.json`
- `--ignore-platform-reqs` is **strictly forbidden**
- No beta or dev dependencies in production

### Code Quality Standards
- `declare(strict_types=1)` is required in all PHP files
- No hardcoded role strings — use `UserRole` enum exclusively
- No database-level ENUM restrictions — use PHP enums only
- All files must have proper type hints and docblocks

### Architectural Boundaries
- **No business logic inside Filament resources**
- DDD structure is mandatory and cannot be flattened
- All business logic must live inside `app/Domains/`
- Services layer handles all domain mutations
- Filament is **presentation layer only**
- Policies must enforce authorization at all levels

---

## 🏗 ARCHITECTURE STRUCTURE

### Approved Directory Structure

```
app/
├── Actions/          # Single-purpose action classes
├── Domains/          # Domain-driven design modules
│   ├── Auth/
│   ├── Branches/
│   │   ├── Models/
│   │   └── Services/
│   ├── Menu/
│   └── Orders/
├── DTOs/             # Data Transfer Objects
├── Enums/            # PHP enums (UserRole, etc.)
├── Filament/         # Admin panel resources (presentation only)
│   └── Resources/
│       └── Branches/
│           ├── Pages/
│           ├── Schemas/
│           └── Tables/
├── Http/             # API controllers
├── Integrations/     # External service integrations
├── Models/           # Core Laravel models (User, etc.)
├── Policies/         # Authorization policies
├── Providers/        # Service providers
├── Services/         # Application-level services
└── Support/          # Base classes and utilities
    ├── BaseAction.php
    ├── BaseDTO.php
    └── BaseDomainService.php
```

### Layer Responsibilities

| Layer | Responsibility | Location |
|-------|----------------|----------|
| **Presentation** | UI, Forms, Tables, Navigation | `app/Filament/` |
| **Domain** | Business logic, validation, mutations | `app/Domains/` |
| **Authorization** | Access control, permissions | `app/Policies/` |
| **Infrastructure** | Database, external APIs | `app/Integrations/`, Eloquent |

### Boundary Rules

1. **Presentation → Domain**: Filament pages MUST delegate to Services
2. **Domain → Infrastructure**: Services use Models, never raw queries
3. **Authorization → Domain**: Policies check user permissions before domain operations
4. **No reverse dependencies**: Domain layer must not depend on Filament

---

## 🔐 ROLE SYSTEM RULES

### Role Hierarchy

| Role | Access Level | Scope |
|------|--------------|-------|
| `SUPER_ADMIN` | Full system access | Global |
| `ADMIN` | Branch-level management | Single branch |
| `CUSTOMER` | API access only | Own data |

### Authorization Rules

- `SUPER_ADMIN` manages everything (branches, users, settings)
- `ADMIN` is branch-scoped (can only manage assigned branch)
- `CUSTOMER` **cannot access admin panel** — API only
- `UserRole` enum is the **single source of truth** for roles
- No role strings in database queries — always use enum values
- Panel access is controlled by `User::canAccessPanel()`

### Enum Location

```php
// app/Enums/UserRole.php
enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case CUSTOMER = 'customer';
}
```

---

## 🧩 DOMAIN IMPLEMENTATION RULES

### Required Domain Components

Every Domain module MUST include:

| Component | Required | Location |
|-----------|----------|----------|
| Model | ✅ Yes | `app/Domains/{Domain}/Models/` |
| Service | ✅ Yes | `app/Domains/{Domain}/Services/` |
| Policy | ✅ Yes (if admin-managed) | `app/Policies/` |
| Filament Resource | ⚙️ If admin-managed | `app/Filament/Resources/` |
| DTO | ⚙️ If complex data | `app/DTOs/` |
| Action | ⚙️ If single-purpose operation | `app/Actions/` |

### Filament Resource Rules

1. **No direct model mutations** — always delegate to Service
2. **No `Model::create()` in Resources** — use `Service::create()`
3. **Authorization via `canViewAny()`, `canCreate()`, `canEdit()`, `canDelete()`**
4. Pages must override `handleRecordCreation()` and `handleRecordUpdate()`

### Service Method Naming

```php
// Standard service method signatures
public function createBranch(array $data): Branch;
public function updateBranch(Branch $branch, array $data): Branch;
public function deleteBranch(Branch $branch): bool;
public function deactivateBranch(Branch $branch): Branch;
```

---

## 📦 DATABASE STRATEGY

### Migration Rules

- Domain-by-domain migrations (one migration per feature)
- Avoid premature schema expansion
- Keep migrations reversible (`down()` must work)
- Use string columns for role/status (not DB enums)

### Data Integrity Rules

- **Prices copied to `order_items`** — immutability rule (never reference live prices)
- **Timestamps required** on all tables (`created_at`, `updated_at`)
- **Soft deletes** only where business logic requires history

### External Data Rules

- External integrations stored in **separate tables**
- Never pollute core domain tables with integration data
- Use dedicated integration models (e.g., `ExternalOrderSync`)

---

## 🔧 INSTALLED EXTENSIONS

Required PHP extensions for this project:

- `ext-intl` — Required by Filament v4
- `ext-zip` — Required by Filament v4 (OpenSpout)
- `ext-pdo_mysql` — Database driver
- `ext-mbstring` — String handling
- `ext-openssl` — Encryption

---

## 📋 FINALIZED DECISIONS LOG

| Date | Decision | Rationale | Status |
|------|----------|-----------|--------|
| 2026-02-05 | Filament v4 installed | Laravel 12 compatibility confirmed | FINAL |
| 2026-02-05 | DDD structure adopted | Scalability and separation of concerns | FINAL |
| 2026-02-05 | UserRole enum created | Centralized role management | FINAL |
| 2026-02-05 | BranchPolicy created | Proper Laravel authorization | FINAL |
| 2026-02-05 | Strict types enforced | Code quality standardization | FINAL |
| 2026-02-05 | Mandatory decision documentation rule | All architectural/technical decisions must be recorded in this file immediately | FINAL |

---

## 🔄 UPDATE PROTOCOL

> ⚠️ **CRITICAL RULE**

Any new architectural decision, constraint, domain rule, integration rule, or technical agreement **MUST be appended to this file immediately after implementation**.

### Mandatory Documentation Categories

The following types of decisions **must NOT remain only in chat/context** — they require permanent documentation:

| Category | Examples |
|----------|---------|
| Framework & Versions | Laravel, Filament, PHP version decisions |
| Packages | New dependencies, version locks, removals |
| Folder Structure | New directories, DDD modules, layer changes |
| Domain Logic | Business rules, validation logic, workflows |
| Authorization | Role changes, permission rules, policies |
| Database Strategy | Schema decisions, migration patterns, indexing |
| Authentication Flow | Login methods, token strategies, guards |
| Integrations | External APIs, webhooks, third-party services |
| Naming Conventions | Class naming, file naming, database naming |
| Business Rules | Domain-specific constraints, calculations |

### How to Update

1. Add new decisions to the **Finalized Decisions Log** table
2. If the appropriate section does not exist — **create a new structured section**
3. Include a **timestamp** (date of decision)
4. Briefly explain the **reasoning** behind the decision
5. Mark the decision as **FINAL** (unless explicitly temporary)
6. **Never overwrite previous decisions** — only extend the document

### What Must Be Documented

- New domain modules
- New integration patterns
- Authorization rule changes
- Database schema decisions
- Dependency additions
- Breaking changes
- Naming conventions
- Business rules and constraints

---

## 📎 RELATED FILES

| File | Purpose |
|------|---------|
| `composer.json` | Dependency management |
| `app/Enums/UserRole.php` | Role definitions |
| `app/Policies/BranchPolicy.php` | Branch authorization |
| `app/Providers/AppServiceProvider.php` | Policy registration |
| `database/seeders/AdminUserSeeder.php` | Admin user creation |

---

*This document is the single source of truth for architectural decisions in the Wahmy backend project.*
