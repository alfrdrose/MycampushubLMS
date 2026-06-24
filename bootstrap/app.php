<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureActiveTeacherAccount;
use App\Http\Middleware\EnsureAdminCurrentSectionAccess;
use App\Http\Middleware\EnsureStudentCurrentSectionAccess;
use App\Http\Middleware\EnsureTeacherCurrentSectionAccess;

// ✅ Correct Spatie middleware namespaces
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render (and most PaaS) sit behind a load balancer. Trust its
        // forwarded headers so Laravel knows the request is HTTPS.
        $middleware->trustProxies(at: '*');

        $middleware
            ->alias([
                'role' => RoleMiddleware::class,
                'permission' => PermissionMiddleware::class,
                'role_or_permission' => RoleOrPermissionMiddleware::class,
                'admin.current.section' => EnsureAdminCurrentSectionAccess::class,
                'student.current.section' => EnsureStudentCurrentSectionAccess::class,
                'teacher.active' => EnsureActiveTeacherAccount::class,
                'teacher.current.section' => EnsureTeacherCurrentSectionAccess::class,
            ])
            ->group('adminer', [
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\Auth\Middleware\Authenticate::class,
            ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })
    ->create();
