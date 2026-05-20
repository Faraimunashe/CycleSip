# CycleSip - Operations Platform

CycleSip is a rider-driven alcohol marketplace for university and urban delivery zones.  
This codebase includes a production-grade admin and operations foundation with modular architecture for scale.

## Platform Stack

- Laravel 13 + PHP 8.3
- Inertia.js + Vue 3 Composition API
- Tailwind CSS v4
- Laratrust (roles/permissions)
- Laravel Sanctum (API auth)
- Laravel Queues + queued notifications/jobs
- Broadcasting-ready real-time architecture (Echo + Pusher/Reverb compatible)

## Core Modules Implemented

- **Admin Dashboard**
  - KPI cards (orders, revenue, riders, stores, customers)
  - analytics blocks (daily/weekly trends, products, zones, peak hours)
  - live activity feed with realtime hooks
- **Order Operations**
  - full status pipeline and timeline/audit trail
  - filtered paginated order board
  - workflow transition service + broadcasting events
- **Rider Management**
  - rider profile/approval workflow
  - online/offline tracking
  - rider metrics and zone coverage
- **Store Management**
  - store operational metadata (hours, commission, approvals)
- **Product Management**
  - categories, brands, featured/promoted flags
  - inventory-ready store product expansion
- **Delivery Zone Management**
  - geospatial center/radius, fee rules, ETA configuration
- **Finance Module**
  - transactions, payouts, rider earnings summary and dashboards
- **Customer Management**
  - profile, age verification flags, order volumes

## Roles & Permissions

Seeded roles:

- `super-admin`
- `admin`
- `operations-manager`
- `support-staff`
- `rider`
- `store-manager` (future-ready)
- `finance-officer`
- `customer`

Sample permissions:

- `manage-orders`, `manage-riders`, `manage-stores`, `manage-users`
- `manage-settings`, `view-analytics`, `manage-payments`
- `manage-zones`, `approve-riders`, `approve-stores`
- `manage-products`, `manage-customers`

## Database Domain

The schema includes:

- users, roles, permissions
- stores, product_categories, products, store_products
- delivery_zones, delivery_zone_store, delivery_zone_rider
- rider_profiles, rider_documents, rider_locations, rider_earnings
- orders, order_items, order_status_timelines
- transactions, payouts
- support_tickets, notifications, activity_logs

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run dev
php artisan serve
```

## Seeded Accounts

All seeded accounts use password `password`.

- `superadmin@cyclesip.app`
- `admin@cyclesip.app`
- `ops@cyclesip.app`
- `support@cyclesip.app`
- `finance@cyclesip.app`
- `rider@cyclesip.app`
- `customer@cyclesip.app`

## Real-Time Notes

- Frontend Echo bootstrap is ready in `resources/js/bootstrap/realtime.js`
- Private channels are defined in `routes/channels.php`
- Configure Pusher/Reverb environment variables to enable live updates:
  - `VITE_PUSHER_APP_KEY`
  - `VITE_PUSHER_APP_CLUSTER`
  - `VITE_PUSHER_HOST`
  - `VITE_PUSHER_PORT`
  - `VITE_PUSHER_SCHEME`

## Architecture Patterns Used

- Form Requests for validation
- Service classes (`DashboardAnalyticsService`, `OrderWorkflowService`)
- Repository (`OrderRepository`)
- Policies + Gates
- API Resources
- Queueable Jobs and Notifications
- Event-driven status transitions

## Test

```bash
php artisan test
```
