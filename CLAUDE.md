# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a full-stack intervention management system called "suivie-interventions" with the following architecture:

- **Frontend**: Nuxt.js 4 SPA with Tailwind CSS, primevue component, Pinia state management, and Vue components
- **Backend**: PHP REST API with MySQL database
- **Web Server**: Apache with mod_rewrite for API routing

## Development Commands

### Frontend (Nuxt.js)

```bash
cd frontend
npm run dev          # Start development server (http://localhost:3000)
npm run build        # Build for production
npm run generate     # Generate static site
npm run preview      # Preview production build
```

### Database Setup

```bash
# Import database schema
mysql -u root -p < database/schema_extended.sql
```

## Architecture Overview

### Directory Structure

```
├── frontend/           # Nuxt.js 3 application
│   ├── components/     # Vue components organized by feature
│   ├── pages/         # File-based routing
│   ├── middleware/    # Route middleware (auth, admin)
│   ├── composables/   # Vue composables for shared logic
│   ├── plugins/       # Nuxt plugins for API and auth
│   └── stores/        # Pinia stores for state management
├── backend/           # PHP REST API
│   ├── api/          # API endpoints
│   ├── config/       # Configuration files
│   └── models/       # PHP model classes
└── database/         # SQL schema and migrations
```

### Authentication System

- **Backend**: Session-based authentication with PHP sessions and database storage
- **Frontend**: Pinia store (`useAuthStore`) managing authentication state
- **Middleware**: `auth.js` for protected routes, `admin.js` for admin-only pages
- **API**: JWT-like session system with `auth.php` endpoints

### State Management (Pinia)

- Stores located in `frontend/stores/` directory
- Auto-imported with Nuxt configuration
- Authentication store handles login/logout and user state

### Database Schema

- **users**: User management with roles (admin, technicien, manager, client)
- **interventions**: Main intervention tracking with status, priority, and timestamps
- **clients**: Client information and contact details
- **fichiers_intervention**: File attachments for interventions
- **intervention_historique**: Audit trail for intervention changes

### API Structure

All API routes are accessible via `/api/` prefix through Apache rewrite rules:

- `/api/auth.php` - Authentication endpoints
- `/api/intervention.php` - Intervention CRUD operations
- `/api/users.php` - User management
- `/api/upload.php` - File upload handling

### Frontend Components

- **UI Components**: Located in `components/UI/`
- **Feature Components**: Organized by domain (Interventions, Users, Auth, Upload)
- **Pages**: File-based routing with dynamic routes for intervention details

### Security Configuration

- Apache `.htaccess` files for CORS and security headers
- Database credentials in `backend/config/database.php`
- Role-based access control through user roles and middleware

## Environment Configuration

### Frontend Environment

- API base URL configured in `nuxt.config.ts`
- Default: `http://localhost/api`
- Override with `API_BASE_URL` environment variable

### Backend Configuration

- Database connection: `backend/config/database.php`
- Authentication settings: `backend/config/auth.php`
- Logger configuration: `backend/config/logger.php`

## Key Features

- User management with role-based permissions
- Intervention lifecycle management (creation, assignment, tracking, completion)
- File upload and attachment system
- Real-time status updates and history tracking
- Client management and contact information
- Dashboard with intervention statistics

## Development Notes

- Frontend runs as SPA (`ssr: false` in nuxt.config.ts)
- CORS configured for `localhost:3000` in Apache configuration
- Database uses triggers for automatic intervention numbering
- File uploads stored with metadata tracking
- Session management for user authentication
