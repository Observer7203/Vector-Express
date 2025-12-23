# Vector Express

**B2B Logistics Marketplace Platform** — A freight shipping aggregator (similar to Kayak/Skyscanner for cargo transportation)

## Overview

Vector Express is a full-stack logistics marketplace that connects shippers with multiple carriers, enabling real-time quote comparison and end-to-end shipment management.

## Tech Stack

- **Backend:** Laravel 12, REST API, Sanctum Authentication
- **Frontend:** Vue 3 (Composition API), Pinia, Vue Router, SCSS
- **Database:** MySQL (25+ tables)
- **Localization:** vue-i18n (Russian, English, Kazakh)

## Features

### Core Functionality
- **Multi-carrier Quote Aggregation** — Real-time quotes from multiple carriers with comparison (fastest/cheapest labels)
- **Zone-based Pricing Engine** — Configurable pricing zones with distance/weight calculations
- **Surcharges System** — Fuel, oversized cargo, customs clearance, insurance surcharges
- **Multi-currency Support** — KZT, USD, RUB, CNY with automatic conversion
- **Commission-based Monetization** — 5% platform fee per transaction

### User Roles
- **Customer** — Create shipments, compare quotes, track orders, chat with carriers
- **Carrier** — Manage rates, zones, terminals, surcharges, documents, handle orders
- **Admin** — Full platform management: users, companies, carriers, orders, settings

### Shipment Lifecycle
1. Create shipment (origin, destination, cargo details)
2. Get quotes from multiple carriers
3. Select carrier and confirm order
4. Real-time GPS tracking with route history
5. Delivery confirmation and documentation

### Additional Features
- Dark theme support
- Real-time chat system (customer-carrier communication)
- Document verification for carriers
- Terminal management
- Comprehensive admin dashboard with analytics

## Backend Architecture

- **30+ REST API endpoints** with role-based middleware
- **Modular structure:** Controllers, Services, Models, Resources
- **Key modules:**
  - Quote calculation engine
  - Order management system
  - User/Company/Carrier management
  - Rate and zone configuration
  - Surcharge calculations (fuel, insurance, customs)
  - GPS tracking integration

## Frontend Architecture

- **40+ Vue components** organized by feature
- **State management:** Pinia stores for auth, theme, notifications
- **Responsive design** with mobile support
- **Dark/Light theme** toggle with SCSS variables
- **i18n** — Full localization for RU/EN/KZ languages

## Project Structure

```
├── app/
│   ├── Http/Controllers/    # API Controllers
│   ├── Models/              # Eloquent Models
│   ├── Services/            # Business Logic
│   └── Http/Resources/      # API Resources
├── database/
│   ├── migrations/          # Database Schema
│   └── seeders/             # Test Data
├── routes/
│   └── api.php              # API Routes
└── frontend/
    ├── src/
    │   ├── components/      # Vue Components
    │   ├── views/           # Page Views
    │   ├── stores/          # Pinia Stores
    │   ├── services/        # API Services
    │   ├── i18n/            # Translations
    │   └── styles/          # SCSS Styles
    └── public/              # Static Assets
```

## Target Market

- Kazakhstan and CIS region
- China-Kazakhstan trade corridor
- B2B freight logistics

## License

Proprietary
