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

- Laravel Reverb is included for websocket broadcasting (`php artisan reverb:start`)
- Set `BROADCAST_CONNECTION=reverb` and configure `REVERB_*` / `VITE_REVERB_*` in `.env`
- Admin dashboard listens on `ops.orders` and `ops.riders`
- Customer order pages listen on `orders.{id}` for status + rider GPS updates
- Rider marketplace listens on `riders.marketplace` for newly broadcast orders
- Mobile apps authenticate private channels via `POST /api/v1/broadcasting/auth` (Sanctum)
- Rider GPS: `PATCH /api/v1/rider/location` while on active deliveries

Mobile env (Expo):

- `EXPO_PUBLIC_REVERB_APP_KEY` — same as `REVERB_APP_KEY`
- `EXPO_PUBLIC_REVERB_HOST` — your machine LAN IP (not localhost on physical devices)
- `EXPO_PUBLIC_REVERB_PORT` — default `8080`
- `EXPO_PUBLIC_REVERB_SCHEME` — `http` for local dev

Legacy Pusher cloud vars (`VITE_PUSHER_*`) are no longer required when using Reverb.

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
