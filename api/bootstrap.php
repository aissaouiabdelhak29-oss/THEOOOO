<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/db.php';

function apiResponse(bool $success, mixed $data = null, ?string $message = null, int $status = 200): never
{
    http_response_code($status);

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

function apiBaseUrl(): string
{
    return 'https://yuki.wuaze.com';
}

function imageUrl(?string $folder, ?string $filename, ?string $fallback = null): ?string
{
    if (empty($filename)) {
        return $fallback;
    }

    return apiBaseUrl() . '/uploads/' . rawurlencode($folder) . '/' . rawurlencode(basename($filename));
}

function contentItem(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'slug' => $row['slug'],
        'type' => $row['type'],
        'description' => $row['description'] ?? '',
        'year' => isset($row['year']) ? (int) $row['year'] : null,
        'duration' => isset($row['duration']) ? (int) $row['duration'] : null,
        'rating' => isset($row['rating']) ? (float) $row['rating'] : 0,
        'quality' => $row['quality'] ?? null,
        'views' => isset($row['views']) ? (int) $row['views'] : 0,
        'category' => $row['category_name'] ?? null,
        'genre' => $row['genre_name'] ?? null,
        'poster' => imageUrl('posters', $row['poster'] ?? null),
        'backdrop' => imageUrl('backdrops', $row['backdrop'] ?? null),
    ];
}