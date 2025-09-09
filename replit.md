# Sistema ConsultaProd - Health Data Management System

## Overview

A comprehensive health data management system built with a modern full-stack architecture. The system provides data management capabilities for health production data with dynamic report generation, user authentication, and administrative features. It supports CRUD operations for auxiliary tables (CBO occupations, providers, procedures, and financing sources) and includes a powerful report generator with advanced filtering and export capabilities.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture
- **Framework**: React 18 with TypeScript
- **Routing**: Wouter for client-side routing
- **State Management**: TanStack Query for server state management
- **UI Components**: Custom component library built on Radix UI primitives with Tailwind CSS
- **Styling**: Tailwind CSS with custom CSS variables for theming
- **Build Tool**: Vite for development and production builds

### Backend Architecture
- **Runtime**: Node.js with Express.js framework
- **Database ORM**: Drizzle ORM for type-safe database operations
- **API Design**: RESTful API with structured route handlers
- **Authentication**: JWT-based authentication with bcrypt for password hashing
- **Development**: Hot module replacement with Vite integration for seamless development experience

### Database Design
- **Database**: PostgreSQL (configured for Neon serverless)
- **Schema Management**: Drizzle Kit for migrations and schema management
- **Connection**: Connection pooling with Neon serverless driver
- **Tables**: User management, CBO (occupations), providers, procedures, financing sources (S-Rub), and main production data (ConsultaProd)

### Authentication & Authorization
- **Authentication**: JWT tokens with secure session management
- **Authorization**: Role-based access control (Admin and Operator roles)
- **Security**: Password hashing with bcrypt, secure token verification middleware
- **Session Management**: Client-side token storage with automatic logout on token expiration

### Data Management Features
- **CRUD Operations**: Complete create, read, update, delete functionality for all auxiliary tables
- **Pagination**: Server-side pagination for efficient data loading
- **Search & Filtering**: Advanced search capabilities with multiple filter options
- **Data Validation**: Comprehensive input validation using Zod schemas
- **Audit Trail**: System tracking for data changes and user actions

### Report Generation System
- **Dynamic Filtering**: Configurable filters with multiple operators (equals, contains, greater than, etc.)
- **Field Selection**: Users can choose which data fields to include in reports
- **Export Formats**: Multiple export options (CSV, Excel, PDF)
- **Real-time Preview**: Live data preview before report generation
- **Filter Combinations**: Support for complex filter combinations with AND/OR logic

## External Dependencies

### Core Framework Dependencies
- **@tanstack/react-query**: Server state management and caching
- **wouter**: Lightweight React routing library
- **drizzle-orm**: Type-safe ORM for database operations
- **@neondatabase/serverless**: Serverless PostgreSQL driver

### UI & Styling Dependencies
- **@radix-ui/***: Comprehensive set of accessible UI primitives (dialog, dropdown, select, etc.)
- **tailwindcss**: Utility-first CSS framework
- **class-variance-authority**: Utility for creating variant-based component APIs
- **lucide-react**: Icon library for consistent UI icons

### Authentication & Security
- **jsonwebtoken**: JWT token generation and verification
- **bcrypt**: Password hashing and verification
- **@hookform/resolvers**: Form validation integration
- **zod**: Runtime type checking and validation

### Development Tools
- **vite**: Build tool and development server
- **typescript**: Type safety and enhanced developer experience
- **@replit/vite-plugin-runtime-error-modal**: Development error handling
- **@replit/vite-plugin-cartographer**: Replit-specific development enhancements

### Database & Migration Tools
- **drizzle-kit**: Database migrations and schema management
- **connect-pg-simple**: PostgreSQL session store (for future session management)

### Form Handling
- **react-hook-form**: Performant form library with minimal re-renders
- **@hookform/resolvers**: Integration between react-hook-form and validation libraries