-- Database: blog_system (run this if you are creating fresh)
CREATE DATABASE IF NOT EXISTS blog_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blog_system;

-- Drop tables in dependency order
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS blogs;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- ================= USERS TABLE =================
CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','editor','author','reader') DEFAULT 'reader',
  points INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================= CATEGORIES TABLE =================
CREATE TABLE IF NOT EXISTS categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================= BLOGS TABLE =================
CREATE TABLE IF NOT EXISTS blogs (
  blog_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT,
  author_id INT NOT NULL,
  co_author_id INT NULL,
  category_id INT NULL,
  -- workflow: draft -> pending -> approved -> published -> rejected
  status ENUM('draft','pending','approved','published','rejected') DEFAULT 'draft',
  editor_remark TEXT NULL,
  likes INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL,
  submitted_at DATETIME DEFAULT NULL,  -- track resubmission time
  CONSTRAINT fk_blogs_author FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_blogs_coauthor FOREIGN KEY (co_author_id) REFERENCES users(user_id) ON DELETE SET NULL,
  CONSTRAINT fk_blogs_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================= COMMENTS TABLE =================
CREATE TABLE IF NOT EXISTS comments (
  comment_id INT AUTO_INCREMENT PRIMARY KEY,
  blog_id INT NOT NULL,
  user_id INT NOT NULL,
  comment_text TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comments_blog FOREIGN KEY (blog_id) REFERENCES blogs(blog_id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================= LIKES TABLE =================
CREATE TABLE IF NOT EXISTS likes (
  like_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  blog_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_likes_blog FOREIGN KEY (blog_id) REFERENCES blogs(blog_id) ON DELETE CASCADE,
  UNIQUE KEY unique_like (user_id, blog_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Run this SQL in phpMyAdmin to add the reviewed_by column

ALTER TABLE blogs ADD COLUMN reviewed_by INT NULL AFTER editor_remark;

-- Add foreign key constraint (optional but recommended)
ALTER TABLE blogs ADD CONSTRAINT fk_blogs_reviewer 
FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL;