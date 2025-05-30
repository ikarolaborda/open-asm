<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# OpenASM (Open Asset Management)

A modern, multi-tenant asset management system built with Laravel 12, featuring comprehensive REST API documentation with Swagger/OpenAPI 3.0.

## Features

- **Multi-tenant Architecture**: Complete organization-based data isolation
- **Comprehensive Asset Management**: Full lifecycle asset tracking
- **RESTful API**: Modern REST API with comprehensive documentation
- **Data Quality Management**: Automated scoring and tracking
- **Warranty Management**: Complete warranty tracking and notifications
- **Role-based Access Control**: Secure, scalable permission system

## API Documentation

### Accessing the Documentation

The API is fully documented using OpenAPI 3.0 specifications with Swagger UI:

**Swagger UI**: [http://localhost:8000/api/documentation](http://localhost:8000/api/documentation)
**OpenAPI JSON**: [http://localhost:8000/docs](http://localhost:8000/docs)

### Features of the API Documentation

- **Interactive Testing**: Test all endpoints directly from the browser
- **Complete Schema Documentation**: Detailed request/response schemas
- **Authentication Support**: Bearer token authentication
- **Comprehensive Examples**: Real-world usage examples
- **Multi-environment Support**: Easy switching between dev/staging/production

### Generating Documentation

The documentation is automatically generated from PHP 8.4 attributes in the codebase:

```bash
# Regenerate documentation
php artisan l5-swagger:generate

# Clear all caches
php artisan route:clear && php artisan config:clear && php artisan cache:clear
```

### Available API Endpoints

#### Assets Management
- `GET /api/v1/assets` - List assets with filtering and pagination
- `POST /api/v1/assets` - Create new asset
- `GET /api/v1/assets/{id}` - Get specific asset
- `PUT /api/v1/assets/{id}` - Update asset
- `DELETE /api/v1/assets/{id}` - Soft delete asset
- `PATCH /api/v1/assets/{id}/retire` - Retire asset
- `PATCH /api/v1/assets/{id}/reactivate` - Reactivate asset
- `GET /api/v1/assets/warranty/expiring-soon` - Assets with expiring warranties
- `GET /api/v1/assets/warranty/expired` - Assets with expired warranties

#### Customers Management
- Complete CRUD operations
- Bulk activation/deactivation
- Customer statistics and search
- Incomplete data reporting

All endpoints include:
- **Authentication**: Bearer token required
- **Validation**: Comprehensive request validation
- **Error Handling**: Consistent error responses
- **Pagination**: Standard Laravel pagination
- **Filtering**: Advanced filtering capabilities

## Development

### Requirements

- PHP 8.2 or higher
- Laravel 12
- MySQL/PostgreSQL
- Composer

### Installation

```bash
# Install dependencies
composer install

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Generate API documentation
php artisan l5-swagger:generate

# Start development server
php artisan serve
```

### Code Standards

The project follows PSR-12 coding standards with additional quality tools:

```bash
# Fix code style
./vendor/bin/pint

# Run tests
php artisan test
```

## Architecture

### Domain-Driven Design
- Organized by business domains (Asset, Customer, Organization)
- Service layer for business logic
- Repository pattern for data access
- Event-driven architecture

### Multi-tenancy
- Organization-based data isolation
- Global scopes for automatic filtering
- Cross-tenant validation

### API Design
- RESTful principles
- Resource-based URLs
- HTTP status codes
- JSON API responses

## Contributing

1. Fork the repository
2. Create a feature branch
3. Implement your changes
4. Add tests
5. Update documentation
6. Submit a pull request

## License

MIT License. See [LICENSE](LICENSE) file for details.

# OpenASM Frontend

A modern Vue 3 + TypeScript frontend application for the OpenASM (Asset Management System). This application provides a comprehensive interface for managing assets, customers, users, and organizations with role-based access control.

## 🚀 Features

### Core Features
- **Modern Vue 3 Architecture**: Built with Vue 3 Composition API and TypeScript
- **State Management**: Pinia for reactive state management
- **Authentication**: JWT-based authentication with automatic token refresh
- **Role-Based Access Control**: Fine-grained permissions and role management
- **Responsive Design**: Mobile-first design with TailwindCSS
- **Interactive Data Tables**: Advanced filtering, sorting, and pagination
- **Real-time Notifications**: Toast notifications for user feedback

### Authentication & Security
- JWT token management with automatic refresh
- Role-based navigation and UI components
- Permission-based feature access
- Secure API communication with automatic error handling

### Asset Management
- Comprehensive asset tracking and management
- Asset quality scoring and data integrity monitoring
- Warranty management and expiration tracking
- Advanced search and filtering capabilities

### Customer Relationship Management
- Customer profile management
- Asset associations and tracking
- Location management
- Contact management

### User & Organization Management
- Multi-tenant organization support
- User role and permission management
- Organization statistics and reporting
- Profile management

## 🛠 Tech Stack

### Core Technologies
- **Vue 3** - Progressive JavaScript framework
- **TypeScript** - Type-safe JavaScript
- **Pinia** - State management
- **Vue Router** - Client-side routing
- **Vite** - Fast build tool

### UI & Styling
- **TailwindCSS** - Utility-first CSS framework
- **Headless UI** - Unstyled, accessible UI components
- **Heroicons** - Beautiful hand-crafted SVG icons

### Development Tools
- **ESLint** - Code linting
- **Prettier** - Code formatting
- **TypeScript** - Type checking
- **PostCSS** - CSS processing

### Libraries & Utilities
- **Axios** - HTTP client
- **Vue Toastification** - Toast notifications
- **Date-fns** - Date utility library
- **Zod** - Schema validation
- **VueUse** - Composition utilities

## 📁 Project Structure

```
src/
├── assets/                 # Static assets
│   └── css/               # Stylesheets
├── components/            # Reusable Vue components
├── layouts/               # Layout components
│   ├── AuthLayout.vue     # Authentication layout
│   └── AppLayout.vue      # Main application layout
├── pages/                 # Page components
│   ├── auth/              # Authentication pages
│   ├── assets/            # Asset management pages
│   ├── customers/         # Customer management pages
│   ├── users/             # User management pages
│   └── organization/      # Organization pages
├── router/                # Vue Router configuration
├── services/              # API service layer
│   └── api/               # API clients
├── stores/                # Pinia stores
├── types/                 # TypeScript type definitions
└── utils/                 # Utility functions
```

## 🚦 Getting Started

### Prerequisites
- Node.js 18+ 
- npm or yarn or pnpm

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd OpenASM-frontend
   ```

2. **Install dependencies**
   ```bash
   npm install
   # or
   yarn install
   # or
   pnpm install
   ```

3. **Environment Configuration**
   
   Create a `.env` file in the root directory:
   ```env
   VITE_API_URL=http://127.0.0.1:8000/api
   VITE_APP_TITLE=OpenASM - Asset Management System
   ```

4. **Start the development server**
   ```bash
   npm run dev
   # or
   yarn dev
   # or
   pnpm dev
   ```

5. **Open your browser**
   
   Navigate to `http://localhost:3000`

### Build for Production

```bash
npm run build
npm run preview
```

## 🔐 Demo Credentials

The application comes with pre-configured demo accounts for testing:

| Role | Email | Password | Description |
|------|-------|----------|-------------|
| Super Admin | superadmin@test.com | password | Full system access |
| Admin | admin@test.com | password | Organization admin access |
| User | user@test.com | password | Limited user access |

## 🎨 Design System

### Color Palette
- **Primary**: Blue shades for main actions and branding
- **Secondary**: Gray shades for content and backgrounds
- **Success**: Green for positive actions and status
- **Warning**: Yellow for warnings and alerts
- **Danger**: Red for errors and destructive actions

### Typography
- **Font Family**: Inter (Google Fonts)
- **Font Weights**: 100-900
- **Responsive scaling** with TailwindCSS utilities

### Components
All components follow a consistent design system with:
- Standardized spacing using TailwindCSS
- Consistent border radius and shadows
- Accessible color combinations
- Responsive behavior

## 🔧 Development

### Available Scripts

```bash
# Development
npm run dev              # Start development server
npm run build           # Build for production
npm run preview         # Preview production build

# Code Quality
npm run lint            # Run ESLint
npm run format          # Format code with Prettier
npm run type-check      # Run TypeScript type checking
```

### Code Style Guidelines

- **TypeScript**: Use strict type checking
- **Vue 3**: Composition API with `<script setup>`
- **Naming**: PascalCase for components, camelCase for variables/functions
- **Imports**: Use path aliases (`@/` for src directory)
- **State**: Use Pinia stores for global state management

### Adding New Features

1. **Create types** in `src/types/`
2. **Add API services** in `src/services/api/`
3. **Create Pinia stores** if needed in `src/stores/`
4. **Build components** in `src/components/`
5. **Add pages** in appropriate `src/pages/` subdirectory
6. **Update router** configuration if needed

## 🔌 API Integration

The frontend communicates with the Laravel backend API through:

### Authentication Endpoints
- `POST /api/auth/login` - User login
- `POST /api/v1/auth/logout` - User logout
- `POST /api/v1/auth/refresh` - Token refresh
- `GET /api/v1/auth/me` - Current user info

### Resource Endpoints
- **Assets**: `/api/v1/assets/*`
- **Customers**: `/api/v1/customers/*`
- **Users**: `/api/v1/users/*`
- **Organization**: `/api/v1/organization/*`

### Error Handling
- Automatic token refresh on 401 errors
- Global error handling with toast notifications
- Validation error display
- Network error fallbacks

## 🎯 Key Components

### Authentication Store (`src/stores/auth.ts`)
- JWT token management
- User state management
- Permission and role checking
- Automatic token refresh

### API Client (`src/services/api/client.ts`)
- Axios-based HTTP client
- Request/response interceptors
- Error handling
- Token management

### Layouts
- **AuthLayout**: Clean layout for login/auth pages
- **AppLayout**: Main application layout with navigation

### Router Configuration
- Route-based authentication guards
- Permission-based route access
- Dynamic page titles
- Navigation breadcrumbs

## 🔒 Security Features

- **JWT Token Management**: Secure token storage and automatic refresh
- **Role-Based Access Control**: UI elements hidden based on permissions
- **Route Guards**: Protected routes based on authentication and permissions
- **XSS Protection**: Vue's built-in template sanitization
- **CSRF Protection**: API token-based requests

## 📱 Responsive Design

The application is fully responsive with:
- **Mobile-first approach** using TailwindCSS
- **Breakpoint system**: sm, md, lg, xl, 2xl
- **Touch-friendly interfaces** for mobile devices
- **Adaptive navigation** (sidebar collapses on mobile)

## 🚀 Performance Optimizations

- **Code Splitting**: Route-based code splitting
- **Lazy Loading**: Components loaded on demand
- **Tree Shaking**: Unused code elimination
- **Asset Optimization**: Vite's built-in optimizations
- **Caching**: API response caching where appropriate

## 🧪 Testing

Testing setup recommendations:
- **Unit Tests**: Vitest + Vue Test Utils
- **E2E Tests**: Playwright or Cypress
- **Type Checking**: TypeScript strict mode
- **Linting**: ESLint with Vue and TypeScript rules

## 📝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Support

For support and questions:
- Create an issue on GitHub
- Check the documentation
- Review the demo credentials and API endpoints

---

**Built with ❤️ using Vue 3, TypeScript, and TailwindCSS**
