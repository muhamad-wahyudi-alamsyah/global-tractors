# Global Tractors Indonesia - WordPress Child Theme

## Overview

Custom admin dashboard untuk PT Global Tractors Indonesia. Child theme dari **Themify Ultra** yang menambahkan admin panel tanpa menghapus website existing.

## Cara Kerja

```
Theme Structure:
├── themify-ultra (Parent Theme) ← Website existing
└── global-tractors (Child Theme) ← Admin dashboard

URL Structure:
├── website.com/ ← Website existing (dari themify-ultra)
├── website.com/dashboard/ ← Admin dashboard (dari global-tractors)
└── website.com/wp-admin/ ← WordPress admin (tetap bisa diakses)
```

## Features

- Website existing tetap berjalan normal
- Admin dashboard diakses via `/dashboard/`
- Menu admin di WordPress admin panel
- Role-based access control
- Custom database tables untuk data management
- Frontend customer views (equipment, spare parts, quotations)
- Customer self-service: sell equipment, request quotations, request equipment
- Media library for news articles
- Equipment filtering & search cards (shortcode-based)
- Rate limiting on login & register endpoints
- SEO: noindex/nofollow on all dashboard pages

## Installation

1. Copy folder `global-tractors` ke `wp-content/themes/`
2. Login ke WordPress admin
3. Appearance → Themes
4. Activate "Global Tractors Indonesia"
5. Database tables akan otomatis dibuat
6. Dummy data (customers, sell requests, news articles) terisi otomatis via `init` hook

## Access Dashboard

- **Frontend Dashboard**: `website.com/dashboard/` (harus login + capability `gti_access`)
- **WordPress Admin**: `website.com/wp-admin/` → Menu "GTI Admin"

## File Structure

```
global-tractors/
├── style.css                         ← Theme declaration (child theme) + minor CSS overrides
├── functions.php                     ← Bootstrap: loads inc/, registers hooks, enqueue styles
├── index.php                         ← Required by WordPress
│
├── inc/                              ← Core logic (modular, loaded via functions.php)
│   ├── bootstrap.php                 ← Early bootstrap logic
│   ├── constants.php                 ← Global constants: GTI_VERSION, DB keys, role slugs, rate limits
│   │
│   ├── ajax/                         ← AJAX endpoint handlers (wp_ajax_*)
│   │   ├── ajax-helpers.php          ← Shared AJAX utilities (response formatters, auth checks)
│   │   ├── ajax-login.php            ← Login AJAX handler
│   │   ├── ajax-register.php         ← Registration AJAX handler
│   │   ├── ajax-orders.php           ← Orders/quotation AJAX handler
│   │   └── ajax-profile.php          ← Profile update AJAX handler
│   │
│   ├── auth/                         ← Authentication & authorization
│   │   ├── block-admin.php           ← Block wp-admin access for certain roles
│   │   ├── filters.php               ← Auth-related filters
│   │   ├── login.php                 ← Custom login logic
│   │   ├── logout.php                ← Custom logout logic
│   │   ├── register.php              ← Custom registration logic
│   │   └── reset-password.php        ← Password reset flow
│   │
│   ├── db/                           ← Database layer
│   │   ├── schema.php                ← Table creation (CREATE TABLE definitions)
│   │   ├── migrations.php            ← Versioned database migrations
│   │   └── queries.php               ← Reusable query helpers
│   │
│   ├── helpers/                      ← General utilities
│   │   ├── format-helpers.php        ← Number/date/string formatting
│   │   ├── template-loader.php       ← Custom template loading for gti_page rewrite
│   │   └── url-helpers.php           ← URL generation helpers
│   │
│   ├── security/                     ← Security layer
│   │   ├── capabilities.php          ← GTI capability registration & capability checks
│   │   ├── nonce.php                 ← Nonce verification helpers
│   │   ├── rate-limit.php            ← Rate limiting (login, register, resend verify)
│   │   └── sanitize.php              ← Input sanitization
│   │
│   ├── setup/                        ← Theme setup & initialization
│   │   ├── enqueue.php               ← Admin & frontend asset enqueue
│   │   ├── install.php               ← First-activation install logic
│   │   ├── rewrite.php               ← Custom rewrite rules (dashboard/ slug)
│   │   └── roles.php                 ← GTI role & capability registration
│   │
│   ├── shortcodes/                   ← Frontend shortcodes
│   │   ├── equipment-filter.php      ← Equipment filter shortcode
│   │   └── search-card.php           ← Equipment search card shortcode
│   │
│   └── user/                         ← User-related utilities
│       ├── user-helpers.php          ← User data helpers
│       ├── user-meta.php             ← User meta read/write
│       └── user-validation.php       ← User input validation
│
├── includes/                         ← Legacy class-based modules (being migrated to inc/)
│   ├── class-gti-activator.php       ← Database table creation on theme activation
│   ├── class-gti-admin-menu.php      ← WordPress admin menu registration
│   ├── class-gti-admin-pages.php     ← Admin page rendering
│   ├── class-gti-ajax.php            ← Legacy AJAX dispatcher
│   ├── class-gti-database.php        ← Legacy query helpers
│   ├── class-gti-frontend.php        ← Frontend dashboard controller
│   ├── class-gti-helpers.php         ← Legacy general helpers
│   └── class-gti-roles.php           ← Legacy role definitions
│
├── admin/                            ← Admin panel (wp-admin + frontend dashboard)
│   ├── css/
│   │   ├── gti-admin.css             ← Admin panel styles
│   │   └── gti-components.css        ← Reusable UI components (cards, tables, modals, etc.)
│   ├── js/
│   │   ├── gti-admin.js              ← Admin panel scripts
│   │   ├── gti-charts.js             ← Chart.js integration for dashboard widgets
│   │   └── gti-form.js               ← Multi-step form handler
│   └── views/
│       ├── dashboard.php             ← Admin dashboard (wp-admin)
│       ├── dashboard-frontend.php    ← Frontend dashboard (/dashboard/)
│       ├── customers/
│       ├── equipment/
│       ├── messages/
│       ├── quotations/
│       ├── requests/
│       ├── sell-equipment/
│       ├── spare-parts/
│       └── users/
│
├── templates/                        ← Frontend page templates (loaded via rewrite)
│   ├── page-dashboard.php            ← /dashboard/ landing
│   ├── page-login.php                ← /login/
│   ├── page-register.php             ← /register/
│   ├── page-forgot-password.php      ← /forgot-password/
│   ├── page-profile.php              ← /profile/
│   ├── page-rental-equipment.php     ← /rental-equipment/
│   ├── page-used-equipment.php       ← /used-equipment/
│   ├── page-spare-parts.php          ← /spare-parts/
│   ├── page-news-articles.php        ← /news-articles/
│   ├── page-add-rental-equipment.php ← /add-rental-equipment/
│   ├── page-add-used-equipment.php   ← /add-used-equipment/
│   ├── page-add-spare-part.php       ← /add-spare-part/
│   ├── page-add-news-article.php     ← /add-news-article/
│   ├── page-sell-equipment.php       ← /sell-equipment/
│   ├── page-request-equipment.php    ← /request-equipment/
│   ├── page-request-quotation.php    ← /request-quotation/
│   ├── page-customers.php            ← /customers/
│   ├── page-customer-detail.php      ← /customers/{id}
│   ├── page-users.php                ← /users/
│   ├── page-orders.php               ← /orders/
│   ├── page-activity-log.php         ← /activity-log/
│   ├── page-media-library.php        ← /media-library/
│   └── page-coming-soon.php          ← Coming soon fallback
│
├── template-parts/                   ← Reusable template partials
│   └── dashboard-sidebar.php         ← Dashboard sidebar navigation
│
├── assets/                           ← Frontend assets (enqueued on public pages)
│   ├── index.php                     ← Security: prevent directory listing
│   ├── css/
│   │   ├── add-equipment.css         ← Equipment submission form styles
│   │   ├── auth.css                  ← Login/register/forgot-password styles
│   │   ├── dashboard.css             ← Frontend dashboard layout styles
│   │   ├── equipment-filter.css      ← Equipment filter form styles
│   │   └── search-card.css           ← Search card component styles
│   ├── js/
│   │   ├── add-equipment.js          ← Equipment submission form logic
│   │   ├── dashboard.js              ← Frontend dashboard interactions
│   │   ├── equipment-filter.js       ← Equipment filter AJAX logic
│   │   └── search-card.js            ← Search card interactions
│   └── images/
│       └── index.php                 ← Security: prevent directory listing
│
├── database/                         ← Dummy/seed data
│   ├── dummy-customers.php           ← PHP: insert dummy customers
│   ├── dummy-customers.sql           ← SQL: customer seed data
│   ├── dummy-news-articles.php       ← PHP: insert dummy news articles
│   ├── dummy-quotations.php          ← PHP: insert dummy quotations
│   ├── dummy-quotations.sql          ← SQL: quotation seed data
│   ├── dummy-sell-requests.php       ← PHP: insert dummy sell requests
│   └── dummy-sell-requests.sql       ← SQL: sell request seed data
│
├── agenda-feature/                   ← Design specs & mockups for agenda features
│   ├── PRD-GTI-Admin-Panel.md        ← Product requirements document
│   ├── admin_panel/                  ← Admin panel page mockups (page-01..15.png)
│   └── page_website/                 ← Public page mockups (page-01..11.png)
│
└── README.md
```

## Database Tables

- `wp_gti_equipment` — Equipment data (rental & used)
- `wp_gti_spare_parts` — Spare parts inventory
- `wp_gti_requests` — Equipment requests from customers
- `wp_gti_quotations` — Quotation data
- `wp_gti_customers` — Customer data
- `wp_gti_sell_requests` — Sell submissions from customers
- `wp_gti_messages` — Contact messages
- `wp_gti_activity_log` — Activity log

## User Roles

- **GTI Super Admin** — Full access to all features
- **GTI Admin** — General admin operations
- **GTI Sales** — Quotation & customer management
- **GTI Inventory** — Equipment & spare parts management
- **GTI Customer** (`gti_customer`) — Frontend customer role (self-service)

## Access Control

- `/dashboard/` requires login + `gti_access` capability
- Administrator automatically gets all GTI capabilities
- Non-admin GTI roles (Sales, Inventory) need explicit assignment
- Customer role uses frontend views only — blocked from wp-admin
- Rate limiting: login (5 attempts / 15 min), register (3 / 1 hour), resend verify (3 / 1 hour)

## Constants

Defined in `inc/constants.php`:

| Constant | Value | Purpose |
|---|---|---|
| `GTI_VERSION` | `2.0.0` | Theme version |
| `GTI_DB_VERSION` | `1.0.0` | Database schema version |
| `GTI_ROLE_CUSTOMER` | `gti_customer` | Customer role slug |
| `GTI_DASHBOARD_BASE` | `dashboard` | Dashboard URL slug |
| `GTI_RL_LOGIN_MAX` | `5` | Login rate limit |
| `GTI_RL_LOGIN_WINDOW` | `900` | Login window (seconds) |
| `GTI_RL_REGISTER_MAX` | `3` | Register rate limit |
| `GTI_RL_REGISTER_WINDOW` | `3600` | Register window (seconds) |

## Development

### Architecture Overview

The codebase follows a modular structure under `inc/`:

```
functions.php
  └── loads inc/constants.php (first)
  └── loads inc/helpers/*, inc/user/*, inc/security/*
  └── loads inc/setup/* (roles, rewrite, enqueue, migrations, install)
  └── loads inc/shortcodes/*
  └── loads inc/ajax/*
  └── loads includes/* (legacy classes)
```

### Adding New Features

1. **Database table** → `inc/db/schema.php` + `inc/db/migrations.php`
2. **Admin menu page** → `includes/class-gti-admin-menu.php`
3. **Admin view** → `admin/views/`
4. **Frontend page** → `templates/page-{slug}.php` + register rewrite in `inc/setup/rewrite.php`
5. **AJAX endpoint** → `inc/ajax/` (new file) + load in `functions.php`
6. **Database query** → `inc/db/queries.php` or `includes/class-gti-database.php`
7. **Shortcode** → `inc/shortcodes/` (new file) + load in `functions.php`
8. **Frontend asset** → `assets/css/` and `assets/js/` + enqueue in `inc/setup/enqueue.php`

### CSS Classes

- `admin/css/gti-admin.css` — Admin panel styles (wp-admin context)
- `admin/css/gti-components.css` — Reusable UI components (cards, tables, badges, modals)
- `assets/css/*.css` — Frontend page-specific styles

## Author

**Muhamad Wahyudi Alamsyah** — at (https://vodeco.co.id)

## Version

2.0.0