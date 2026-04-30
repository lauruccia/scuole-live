<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasAreaPermission
{
    /**
     * Ogni Resource può definire opzionalmente:
     * protected static function requiredPermission(): ?string
     *
     * Esempio: return 'view_any_student';
     */
    protected static function requiredPermission(): ?string
    {
        return null;
    }

    protected static function userCanAccessResource(?string $permission = null): bool
    {
        $u = auth()->user();
        if (! $u) {
            return false;
        }

        // Superadmin vede tutto
        if (method_exists($u, 'hasRole') && $u->hasRole('superadmin')) {
            return true;
        }

        // Se la Resource definisce un permesso specifico, usiamo quello
        if ($permission) {
            return $u->can($permission);
        }

        // Fallback: usa il permesso standard Shield "view_any_{model}"
        $model = static::getModel();
        $snake = \Illuminate\Support\Str::snake(class_basename($model));
        $fallback = "view_any_{$snake}";

        return $u->can($fallback);
    }

    // ✅ Mostra/nasconde voce di menu
    public static function shouldRegisterNavigation(): bool
    {
        return static::userCanAccessResource(static::requiredPermission());
    }

    // ✅ Blocca accesso (anche via URL)
    public static function canViewAny(): bool
    {
        return static::userCanAccessResource(static::requiredPermission());
    }

    public static function canCreate(): bool
    {
        $u = auth()->user();
        if (! $u) return false;
        if (method_exists($u, 'hasRole') && $u->hasRole('superadmin')) return true;

        $model = static::getModel();
        $snake = \Illuminate\Support\Str::snake(class_basename($model));

        return $u->can("create_{$snake}");
    }

    public static function canEdit(Model $record): bool
    {
        $u = auth()->user();
        if (! $u) return false;
        if (method_exists($u, 'hasRole') && $u->hasRole('superadmin')) return true;

        $model = static::getModel();
        $snake = \Illuminate\Support\Str::snake(class_basename($model));

        return $u->can("update_{$snake}");
    }

    public static function canDelete(Model $record): bool
    {
        $u = auth()->user();
        if (! $u) return false;
        if (method_exists($u, 'hasRole') && $u->hasRole('superadmin')) return true;

        $model = static::getModel();
        $snake = \Illuminate\Support\Str::snake(class_basename($model));

        return $u->can("delete_{$snake}");
    }
}
