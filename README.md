# Online Chess Web Application with Chess Engine

A web-based chess application built with Laravel and Vue.js. The application allows users to play chess against a computer opponent, manage their profiles, and review saved games.

## How to Run

### 1. Clone the Repository

```bash
git clone https://github.com/Zielinski-Marcel/Chess.git
```

### 2. Navigate to the Project Directory

```bash
cd Chess
```

### 3. Install PHP Dependencies

```bash
composer install
```

### 4. Install JavaScript Dependencies

```bash
npm install
```

### 5. Configure the Environment

Create your local environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure your database connection in the `.env` file before proceeding.

### 6. Run Database Migrations

```bash
php artisan migrate
```

### 7. Start the Application

Run the development environment:

```bash
make init
make dev
```

Open the local URL displayed by Laravel in your terminal.

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js and npm
- A supported database configured in `.env`

## Technologies

- **Backend:** Laravel 12, PHP
- **Frontend:** Vue 3, Inertia.js
- **Styling:** Tailwind CSS
- **Build Tool:** Vite
- **Database:** Configurable through Laravel's database settings
