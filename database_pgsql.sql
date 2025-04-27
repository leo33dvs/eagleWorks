-- EagleWorks Database Schema for PostgreSQL

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL CHECK (role IN ('FREELANCER', 'COMPANY')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Freelancers table
CREATE TABLE IF NOT EXISTS freelancers (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    profession VARCHAR(100) NOT NULL,
    resume TEXT,
    profile_picture VARCHAR(255) DEFAULT 'default-profile.jpg',
    phone VARCHAR(20),
    availability TEXT, -- JSON with days, hours, region
    social_links TEXT, -- JSON with social media links
    average_rating FLOAT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Companies table
CREATE TABLE IF NOT EXISTS companies (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    cnpj VARCHAR(20) UNIQUE NOT NULL,
    industry VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    logo VARCHAR(255) DEFAULT 'default-company.jpg',
    contact TEXT, -- JSON with email and phone
    average_rating FLOAT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Ratings table
CREATE TABLE IF NOT EXISTS ratings (
    id SERIAL PRIMARY KEY,
    freelancer_id INTEGER,
    company_id INTEGER,
    rated_by VARCHAR(10) NOT NULL CHECK (rated_by IN ('FREELANCER', 'COMPANY')),
    rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (freelancer_id) REFERENCES freelancers(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Messages table (optional)
CREATE TABLE IF NOT EXISTS messages (
    id SERIAL PRIMARY KEY,
    sender_id INTEGER NOT NULL,
    receiver_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create directories for uploads
-- (This must be handled in PHP code, not in SQL)