<?php
declare(strict_types=1);
function h(?string $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function item_status(int $needed, int $available): array { if ($available >= $needed) return ['Tersedia', 'success']; if ($available === 0) return ['Belum Tersedia', 'danger']; return ['Kurang', 'warning']; }
function flash(string $key, ?string $value = null): ?string { start_secure_session(); if ($value !== null) { $_SESSION['flash'][$key] = $value; return null; } $result = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $result; }
function redirect_to(string $path): never { header('Location: ' . APP_URL . $path); exit; }
function require_post_csrf(): void { if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !valid_csrf()) { http_response_code(403); exit('Permintaan tidak valid.'); } }
