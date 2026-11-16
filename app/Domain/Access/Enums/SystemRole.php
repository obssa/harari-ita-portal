<?php

namespace App\Domain\Access\Enums;

use App\Domain\Access\Support\PermissionRegistry;

/**
 * The roles PLAN.md §7 defines, plus the Editor and Author the editorial
 * workflow needs (draft, review, publish) and a read-only Analyst. Their
 * default permissions are seeded by `portal:sync-permissions`; Super Admins
 * may change grants afterwards (except for the Super Admin role, which
 * always has everything). Permissions of modules still to come are granted
 * now, so each role is complete when its screens arrive.
 */
enum SystemRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case CommunicationsEditor = 'communications_editor';
    case Editor = 'editor';
    case Author = 'author';
    case Translator = 'translator';
    case ServiceOwner = 'service_owner';
    case ServiceDeskAgent = 'service_desk_agent';
    case InnovationOfficer = 'innovation_officer';
    case Judge = 'judge';
    case ProjectManager = 'project_manager';
    case DataSteward = 'data_steward';
    case AcademyCoordinator = 'academy_coordinator';
    case Instructor = 'instructor';
    case ProcurementOfficer = 'procurement_officer';
    case DirectorateHead = 'directorate_head';
    case Analyst = 'analyst';

    /** Permissions only a Super Admin holds (PLAN.md §7: the Admin has everything else). */
    public const SUPER_ADMIN_ONLY = [
        'settings.manage',
        'integrations.manage',
        'backups.manage',
        'audit.purge',
        'users.impersonate',
    ];

    public function label(): string
    {
        return __('access.role.'.$this->value);
    }

    public function requiresMfa(): bool
    {
        return in_array($this->value, (array) config('portal.mfa_required_roles'), true);
    }

    /**
     * @return list<ScopeType>
     */
    public function allowedScopes(): array
    {
        return match ($this) {
            self::CommunicationsEditor, self::Editor, self::Author => [ScopeType::Office, ScopeType::Pillar],
            self::Translator => [ScopeType::Language],
            self::ServiceOwner, self::ServiceDeskAgent, self::InnovationOfficer, self::ProjectManager, self::DirectorateHead => [ScopeType::Office],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public function defaultPermissions(): array
    {
        $staff = ['admin.access', 'dashboard.view'];

        $permissions = match ($this) {
            self::SuperAdmin => PermissionRegistry::all(),

            self::Admin => array_values(array_diff(PermissionRegistry::all(), self::SUPER_ADMIN_ONLY)),

            // News, pages, events, media, social, newsletter and advisories (PLAN.md §7).
            self::CommunicationsEditor => [
                ...$staff,
                'posts.view', 'posts.create', 'posts.update', 'posts.translate', 'posts.review',
                'posts.publish', 'posts.feature', 'posts.delete', 'social.view', 'social.manage', 'social.share',
                'pages.view', 'pages.create', 'pages.update', 'pages.translate', 'pages.publish', 'pages.delete',
                'homepage.manage', 'menus.manage', 'taxonomies.manage', 'redirects.manage', 'forms.manage',
                'inbox.view', 'inbox.reply', 'inbox.assign',
                'media.view', 'media.upload', 'media.update', 'media.delete',
                'galleries.manage', 'videos.manage',
                'leaders.view', 'leaders.manage', 'offices.view', 'offices.manage',
                'pillars.view', 'pillars.manage', 'events.view', 'events.manage', 'events.registrations',
                'documents.view', 'documents.manage', 'projects.view',
                'press_kit.manage', 'media_contacts.manage',
                'campaigns.view', 'campaigns.manage', 'campaigns.send', 'subscribers.view', 'subscribers.manage',
                'advisories.manage', 'translations.view', 'analytics.view',
            ],

            self::Editor => [
                ...$staff,
                'posts.view', 'posts.create', 'posts.update', 'posts.translate', 'posts.review',
                'pages.view', 'pages.create', 'pages.update', 'pages.translate', 'taxonomies.manage',
                'media.view', 'media.upload', 'media.update',
                'galleries.manage', 'videos.manage',
                'leaders.view', 'leaders.manage', 'offices.view', 'offices.manage',
                'pillars.view', 'pillars.manage', 'events.view', 'events.manage',
                'documents.view', 'documents.manage', 'projects.view', 'projects.manage',
                'translations.view',
            ],

            self::Author => [
                ...$staff,
                'posts.view', 'posts.create', 'posts.update_own',
                'media.view', 'media.upload',
            ],

            self::Translator => [
                ...$staff,
                'posts.view', 'posts.translate', 'pages.view', 'pages.translate',
                'translations.view', 'translations.export', 'translations.import', 'translations.glossary',
                ...array_map(
                    fn (string $locale): string => "translations.{$locale}.edit",
                    (array) config('portal.locales'),
                ),
            ],

            // Their services' catalogue entries and status components.
            self::ServiceOwner => [
                ...$staff,
                'services.view', 'services.manage', 'status.view', 'status.manage', 'tickets.view', 'documents.view',
            ],

            // Tickets and messages: view, reply, assign within their scope.
            self::ServiceDeskAgent => [
                ...$staff,
                'tickets.view', 'tickets.reply', 'tickets.assign', 'inbox.view', 'inbox.reply', 'inbox.assign',
                'services.view', 'status.view',
            ],

            // Startups, programmes, applications and challenges; mentors and tech volunteers.
            self::InnovationOfficer => [
                ...$staff,
                'startups.view', 'startups.manage', 'programmes.view', 'programmes.manage',
                'applications.view', 'applications.review', 'applications.decide',
                'challenges.view', 'challenges.manage', 'volunteers.view', 'volunteers.manage',
                'events.view', 'events.registrations', 'campaigns.send',
            ],

            // Only their assigned submissions and scores.
            self::Judge => [...$staff, 'judging.score'],

            self::ProjectManager => [...$staff, 'projects.view', 'projects.manage', 'documents.view'],

            self::DataSteward => [...$staff, 'datasets.view', 'datasets.manage', 'datasets.publish', 'projects.view'],

            self::AcademyCoordinator => [
                ...$staff,
                'courses.view', 'courses.manage', 'cohorts.view', 'cohorts.manage', 'cohorts.attendance',
                'certificates.issue', 'certificates.revoke', 'events.view', 'events.registrations',
            ],

            // Attendance for their cohorts.
            self::Instructor => [...$staff, 'courses.view', 'cohorts.view', 'cohorts.attendance'],

            self::ProcurementOfficer => [...$staff, 'opportunities.view', 'opportunities.manage', 'documents.view', 'documents.manage'],

            // Reads everything in their directorate and approves its publications.
            self::DirectorateHead => [
                ...$staff,
                'posts.view', 'posts.review', 'posts.publish', 'pages.view', 'events.view', 'documents.view',
                'projects.view', 'services.view', 'status.view', 'tickets.view', 'inbox.view', 'volunteers.view',
                'analytics.view',
            ],

            self::Analyst => [...$staff, 'analytics.view', 'analytics.export'],
        };

        return array_values(array_unique($permissions));
    }
}
