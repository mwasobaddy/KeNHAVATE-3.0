# Project X - KeNHAVATE

A comprehensive Laravel application featuring secure authentication, role-based onboarding, points system, and audit logging for the KeNHAVATE platform.

## 🚀 Features

### Authentication & Security
- **OTP Email Authentication**: Secure login using one-time passwords sent via email
- **Google OAuth Integration**: Social login with Google accounts
- **Two-Factor Authentication**: Built-in 2FA support with Laravel Fortify
- **Session Management**: Persistent OTP countdown timers and secure session handling
- **Rate Limiting**: Protection against brute force attacks on verification attempts

### Role-Based Onboarding
- **User Onboarding**: Standard user registration with personal details
- **Staff Onboarding**: Enhanced onboarding for staff members with work email and organizational details
- **Dynamic Routing**: Automatic redirection based on email domain (@kenha.co.ke for staff)
- **Form Validation**: Comprehensive validation with custom request classes

### Points & Rewards System
- **Transactional Points**: Individual point transactions stored in database
- **Configurable Rewards**: Admin-configurable point amounts per event
- **Audit Trail**: Complete history of point awards and deductions
- **Dynamic Calculation**: Total points calculated from transaction history

### Audit Logging
- **Comprehensive Tracking**: All system events logged with user context
- **Model References**: Audit logs include affected model type and ID
- **Security Events**: Login attempts, onboarding completions, and system changes
- **IP & User Agent Tracking**: Complete request metadata storage

### Technical Excellence
- **Clean Architecture**: Service layer pattern with proper separation of concerns
- **Comprehensive Testing**: 49 passing tests with Pest framework
- **Type Safety**: Full PHP type declarations and return types
- **Code Quality**: Laravel Pint code formatting and PSR standards
- **Modern Frontend**: React 19 with Inertia.js for SPA experience

## 🛠 Tech Stack

### Backend
- **Laravel 12**: PHP framework with latest features
- **PHP 8.4**: Modern PHP with type declarations
- **SQLite**: Database for development (configurable for production)
- **Spatie Permission**: Role and permission management
- **Laravel Fortify**: Headless authentication backend
- **Laravel Socialite**: OAuth integration

### Frontend
- **React 19**: Modern JavaScript library
- **Inertia.js v2**: SPA without API complexity
- **Tailwind CSS v4**: Utility-first CSS framework
- **TypeScript**: Type-safe JavaScript
- **ESLint & Prettier**: Code quality and formatting

### Development Tools
- **Pest**: PHP testing framework
- **Laravel Pint**: Code formatter
- **Composer**: PHP dependency management
- **NPM**: Node.js package management

## 📋 Prerequisites

- PHP 8.4 or higher
- Composer
- Node.js 18+ and NPM
- SQLite (or your preferred database)

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd project-x
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env.example .env
   ```

   Configure your environment variables:
   ```env
   APP_NAME="Project X"
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://localhost

   DB_CONNECTION=sqlite
   DB_DATABASE=/absolute/path/to/database/database.sqlite

   MAIL_MAILER=smtp
   MAIL_HOST=mailpit
   MAIL_PORT=1025
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   MAIL_ENCRYPTION=null
   MAIL_FROM_ADDRESS="hello@example.com"
   MAIL_FROM_NAME="${APP_NAME}"

   GOOGLE_CLIENT_ID=your_google_client_id
   GOOGLE_CLIENT_SECRET=your_google_client_secret
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Database Setup**
   ```bash
   # Create SQLite database file
   touch database/database.sqlite

   # Run migrations and seeders
   php artisan migrate:fresh --seed
   ```

7. **Build Frontend Assets**
   ```bash
   npm run build
   # OR for development with hot reload
   npm run dev
   ```

8. **Start the Development Server**
   ```bash
   php artisan serve
   ```

   Visit `http://localhost:8000` in your browser.

## 🧪 Testing

Run the comprehensive test suite:

```bash
# Run all tests
php artisan test

# Run specific test groups
php artisan test --compact tests/Feature/
php artisan test --compact tests/Unit/

# Run with coverage
php artisan test --coverage
```

### Test Coverage
- **Authentication Flow**: OTP login, Google OAuth, session management
- **Onboarding Process**: User and staff onboarding with validation
- **Audit Logging**: Event tracking with model references
- **Points System**: Transaction creation and calculation
- **Security Features**: Rate limiting and validation

## 📊 Database Schema

### Core Tables

#### Users
```sql
- id (primary key)
- name, email, password
- mobile, id_number, gender
- email_google_id, work_email, work_email_google_id
- onboarding_completed
- two_factor_secret, two_factor_recovery_codes
- timestamps
```

#### Points
```sql
- id (primary key)
- user_id (foreign key)
- amount (integer)
- reason (string)
- awarded_by (foreign key, nullable)
- awarded_at (datetime)
- timestamps
```

#### Points Configurations
```sql
- id (primary key)
- event (string, unique)
- points (integer)
- set_by (foreign key)
- timestamps
```

#### Audits
```sql
- id (primary key)
- user_id (foreign key)
- event (string)
- model_type (string, nullable)
- model_id (integer, nullable)
- ip_address, user_agent
- timestamps
```

#### Staff
```sql
- id (primary key)
- user_id (foreign key)
- work_email
- personal_email (nullable)
- region_id, directorate_id, department_id (nullable)
- designation, employment_type (nullable)
- timestamps
```

## 🔌 API Endpoints

### Authentication
- `GET /login` - Login page
- `POST /login/send` - Send OTP to email
- `POST /login/verify` - Verify OTP code
- `GET /auth/google` - Google OAuth redirect
- `GET /auth/google/callback` - Google OAuth callback

### Onboarding
- `GET /user/onboarding` - User onboarding form
- `POST /user/onboarding/store` - Complete user onboarding
- `GET /staff/onboarding` - Staff onboarding form
- `POST /staff/onboarding/store` - Complete staff onboarding

### Dashboard
- `GET /dashboard` - Main dashboard (authenticated users only)

## 🔐 Security Features

- **CSRF Protection**: All forms protected against cross-site request forgery
- **Rate Limiting**: OTP verification attempts limited per user
- **Input Validation**: Comprehensive validation on all user inputs
- **SQL Injection Prevention**: Eloquent ORM with parameterized queries
- **XSS Protection**: Content sanitization and CSP headers
- **Session Security**: Secure session management with encryption

## 📈 Points System

### How Points Work
1. **Configuration**: Admins set point values for different events
2. **Awarding**: Points automatically awarded during onboarding
3. **Tracking**: Each transaction stored with reason and timestamp
4. **Calculation**: Total points = sum of all point amounts for user

### Current Point Events
- `first_login`: 50 points awarded upon successful onboarding

### Managing Points
```php
// Award points to user
$user = User::find(1);
$points = $user->points()->sum('amount'); // Get total points

// Create new point transaction
Point::create([
    'user_id' => $user->id,
    'amount' => 100,
    'reason' => 'Bonus reward',
    'awarded_by' => auth()->id(),
    'awarded_at' => now(),
]);
```

## 📝 Audit Logging

### Logged Events
- `user_onboarding_completed`
- `staff_onboarding_completed`
- `otp_login_successful`
- `google_login_successful`

### Audit Log Structure
```php
[
    'user_id' => 1,           // Who performed the action
    'event' => 'user_onboarding_completed',
    'model_type' => 'App\\Models\\User',  // Affected model
    'model_id' => 1,          // Affected model ID
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Mozilla/5.0...',
    'created_at' => '2026-01-30 17:13:05'
]
```

## 🏗 Architecture

### Service Layer Pattern
```
app/
├── Services/
│   ├── OtpService.php          # OTP generation and validation
│   ├── SocialAuthService.php   # Google OAuth handling
│   └── OnboardingService.php   # User/staff onboarding logic
├── Http/
│   ├── Controllers/
│   │   └── Onboarding/
│   │       ├── UserOnboardingController.php
│   │       └── StaffOnboardingController.php
│   └── Requests/
│       └── Onboarding/
│           ├── UserOnboardingRequest.php
│           └── StaffOnboardingRequest.php
└── Models/
    ├── User.php
    ├── Staff.php
    ├── Point.php
    ├── PointsConfiguration.php
    └── Audit.php
```

### Key Design Patterns
- **Service Layer**: Business logic separated from controllers
- **Repository Pattern**: Data access abstraction
- **Form Request Validation**: Input validation and authorization
- **Event-Driven**: Audit logging through events
- **Factory Pattern**: Test data generation

## 🚀 Deployment

### Production Setup
1. Set `APP_ENV=production` in `.env`
2. Configure production database (PostgreSQL/MySQL recommended)
3. Set up proper mail configuration
4. Configure Google OAuth credentials
5. Run `php artisan config:cache` and `php artisan route:cache`
6. Set up SSL certificate
7. Configure web server (Nginx/Apache)

### Environment Variables
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls

GOOGLE_CLIENT_ID=your_production_client_id
GOOGLE_CLIENT_SECRET=your_production_client_secret
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Standards
- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting
- Write comprehensive tests for new features
- Update documentation for API changes
- Use meaningful commit messages

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support, email support@kenha.co.ke or create an issue in the repository.

## 📊 Project Status

### ✅ Completed Features
- [x] OTP Email Authentication
- [x] Google OAuth Integration
- [x] Role-based Onboarding (User/Staff)
- [x] Points & Rewards System
- [x] Comprehensive Audit Logging
- [x] Security Features (Rate Limiting, CSRF, etc.)
- [x] Testing Suite (49 passing tests)
- [x] Clean Architecture & Code Quality

### 🔄 In Progress
- [ ] Admin Dashboard for Points Management
- [ ] Advanced Reporting & Analytics
- [ ] API Documentation
- [ ] Mobile App Integration

### 📋 Future Enhancements
- [ ] Multi-language Support (i18n)
- [ ] Advanced User Profile Management
- [ ] Notification System
- [ ] Advanced Points Redemption
- [ ] Integration with External Systems

---

**Built with ❤️ for KeNHAVATE**