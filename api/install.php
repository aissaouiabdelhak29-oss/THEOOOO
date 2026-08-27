<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS genres (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS content (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    type VARCHAR(20) NOT NULL,
    description TEXT,
    year INTEGER,
    duration INTEGER,
    rating NUMERIC(4,2) DEFAULT 0,
    quality VARCHAR(10),
    views INTEGER DEFAULT 0,
    category_id INTEGER REFERENCES categories(id),
    genre_id INTEGER REFERENCES genres(id),
    status VARCHAR(20) DEFAULT 'published',
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    poster VARCHAR(500),
    backdrop VARCHAR(500)
);

INSERT INTO categories (name) VALUES ('أفلام'), ('مسلسلات'), ('أنمي'), ('كرتون') ON CONFLICT DO NOTHING;
INSERT INTO genres (name) VALUES ('دراما'), ('أكشن'), ('كوميديا'), ('رعب'), ('خيال علمي') ON CONFLICT DO NOTHING;

INSERT INTO content (title, slug, type, description, year, duration, rating, quality, views, category_id, genre_id, status, featured, poster) 
VALUES 
('Alpha', 'alpha', 'movie', 'Set in the distant past...', 2018, 96, 6.7, 'HD', 26, 1, 1, 'published', true, 'https://yuki.wuaze.com/uploads/posters/example.webp'),
('Inception', 'inception', 'movie', 'A thief who steals corporate secrets...', 2010, 148, 8.8, '4K', 150, 1, 5, 'published', true, NULL)
ON CONFLICT DO NOTHING;
";

try {
    $pdo->exec($sql);
    echo "DONE: Tables created and sample data inserted.\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
