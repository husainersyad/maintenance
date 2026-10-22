<?php

namespace App\Support;

use App\Enums\MenuKey;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;

/**
 * Filters docs/TUTORIAL.md down to what a given user can use.
 *
 * Markers in the markdown (see CLAUDE.md):
 *   <!-- hide-in-app:start --> ... <!-- hide-in-app:end -->   never shown in the app
 *   <!-- only:a|b --> ... <!-- /only -->                      shown if ANY key matches; nestable
 *
 * Keys: a SidebarMenu value (e.g. "employees", "skill-matrix"), "edit:<MenuKey>"
 * (user may edit that menu), "guest", "not-guest", "admin" (can manage users),
 * "settings" (can manage settings).
 */
class HelpGuide
{
    /**
     * @return string[]
     */
    public static function keysFor(User $user): array
    {
        $keys = array_map(fn ($menu) => $menu->value, MenuAccess::visibleMenus($user));

        $isGuest = $user->hasRole(RoleName::Guest->value);
        $keys[] = $isGuest ? 'guest' : 'not-guest';

        if ($user->can(PermissionName::ManageUsers->value)) {
            $keys[] = 'admin';
        }
        if ($user->can(PermissionName::ManageSettings->value)) {
            $keys[] = 'settings';
        }
        foreach (MenuKey::cases() as $menuKey) {
            if ($user->canEditMenu($menuKey)) {
                $keys[] = 'edit:'.$menuKey->value;
            }
        }

        return $keys;
    }

    /**
     * @param  string[]  $keys
     */
    public static function filter(string $markdown, array $keys): string
    {
        $markdown = preg_replace('/<!-- hide-in-app:start -->.*?<!-- hide-in-app:end -->\n?/s', '', $markdown);

        $out = [];
        $stack = [];
        foreach (explode("\n", $markdown) as $line) {
            if (preg_match('/^<!-- only:(.+?) -->\s*$/', $line, $m)) {
                $stack[] = count(array_intersect(explode('|', $m[1]), $keys)) > 0;

                continue;
            }
            if (preg_match('/^<!-- \/only -->\s*$/', $line)) {
                array_pop($stack);

                continue;
            }
            if (! in_array(false, $stack, true)) {
                $out[] = $line;
            }
        }

        return implode("\n", $out);
    }
}
