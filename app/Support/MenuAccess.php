<?php

namespace App\Support;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Enums\SidebarMenu;
use App\Models\Employee;
use App\Models\User;

/**
 * Which sidebar menus a user actually gets, mirroring the conditions in
 * resources/views/layouts/sidebar.blade.php (role permissions AND not hidden
 * by an Administrator). Used by the Help page so the guide only shows what
 * the signed-in user can reach; HelpTest checks it stays in step with the
 * sidebar.
 */
class MenuAccess
{
    /**
     * @return SidebarMenu[]
     */
    public static function visibleMenus(User $user): array
    {
        $isGuest = $user->hasRole(RoleName::Guest->value);
        $canViewEmployees = $user->can('viewAny', Employee::class);
        $can = fn (PermissionName $p) => $user->can($p->value);

        $allowed = [
            SidebarMenu::Tasks->value => ! $isGuest,
            SidebarMenu::ShiftComm->value => ! $isGuest,
            SidebarMenu::Employees->value => $canViewEmployees,
            SidebarMenu::OrganizationChart->value => $canViewEmployees,
            SidebarMenu::QrCodes->value => $canViewEmployees,
            SidebarMenu::PlantManager->value => $user->can('managePlantManager', Employee::class),
            SidebarMenu::Organization->value => $can(PermissionName::ManageMasterData),
            SidebarMenu::Overtime->value => $can(PermissionName::ManageOvertime)
                || ($user->employee?->directReports()->exists() ?? false),
            SidebarMenu::JobDescriptions->value => $can(PermissionName::ViewJobDescriptions),
            SidebarMenu::Skills->value => $can(PermissionName::ViewSkillMatrix),
            SidebarMenu::SkillMatrix->value => $can(PermissionName::ViewSkillMatrix),
            SidebarMenu::CompetencyGap->value => $can(PermissionName::ViewCompetencyGap),
            SidebarMenu::TrainingManagement->value => $can(PermissionName::ManageTraining),
            SidebarMenu::TrainingCalendar->value => $can(PermissionName::ViewTraining),
            SidebarMenu::TrainingRecords->value => $can(PermissionName::ViewTraining),
            SidebarMenu::Certificates->value => $can(PermissionName::ViewCertificates),
            SidebarMenu::Recertification->value => $can(PermissionName::ViewCertificates),
            SidebarMenu::Signatories->value => $can(PermissionName::ViewCertificates) && ! $isGuest,
            SidebarMenu::DevelopmentPlans->value => $can(PermissionName::ViewDevelopmentPlans),
            SidebarMenu::Lototo->value => $can(PermissionName::ViewSafety),
            SidebarMenu::Reports->value => $can(PermissionName::ViewReports),
            SidebarMenu::News->value => $can(PermissionName::ManageNews),
            SidebarMenu::Help->value => true,
        ];

        return array_values(array_filter(
            SidebarMenu::cases(),
            fn (SidebarMenu $menu) => ($allowed[$menu->value] ?? false) && ! $user->isMenuHidden($menu),
        ));
    }
}
