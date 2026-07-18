<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\AvatarUrl;

use Latch\Core\Application;
use Latch\Core\Plugins\HookName;
use Latch\Core\Plugins\PluginContext;
use Latch\Core\Plugins\PluginInterface;
use Latch\Core\Plugins\ProfileSaveContext;

final class Plugin implements PluginInterface
{
    public function register(PluginContext $context): void
    {
        $app = $context->app();
        $storageRoot = (string) $app->config()->get('paths.storage');
        $settings = Settings::load($storageRoot, $context->manifest());
        $validator = new AvatarUrlValidator($settings);
        $users = $app->users();

        $context->hooks()->add(
            HookName::AVATAR_RESOLVE,
            static function (string $url, string $email, int $size) use ($users): string {
                unset($size);
                $user = $users->findByEmail($email);
                if ($user === null) {
                    return $url;
                }

                $custom = trim((string) ($user['avatar_url'] ?? ''));
                if ($custom === '') {
                    return $url;
                }

                return $custom;
            },
        );

        $context->hooks()->add(
            HookName::PROFILE_FORM,
            static function (Application $hookApp, array $user) use ($settings): string {
                $canEdit = $settings->membersCanSet
                    || $hookApp->auth()->isMod();

                $current = trim((string) ($user['avatar_url'] ?? ''));
                if (!$canEdit && $current === '') {
                    return '';
                }

                $escaped = htmlspecialchars($current, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $hosts = $settings->allowedHosts === []
                    ? 'No hosts configured yet — ask an admin.'
                    : 'Allowed hosts: ' . htmlspecialchars(implode(', ', $settings->allowedHosts), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                $readonly = $canEdit ? '' : ' readonly';
                $hint = $canEdit
                    ? 'HTTPS image URL only. Leave blank for Gravatar/identicon. ' . $hosts
                    : 'Only staff can change avatar URLs right now.';

                return '<label class="avatar-url-field">'
                    . 'Custom avatar URL'
                    . '<input type="url" name="avatar_url" value="' . $escaped . '" '
                    . 'placeholder="https://cdn.example.com/me.png" maxlength="500" autocomplete="off"'
                    . $readonly . '>'
                    . '</label>'
                    . '<p class="muted avatar-url-hint">' . $hint . '</p>';
            },
        );

        $context->hooks()->add(
            HookName::PROFILE_BEFORE_SAVE,
            static function (ProfileSaveContext $ctx) use ($settings, $validator, $app): void {
                if ($ctx->avatarUrlInput === null) {
                    return;
                }

                $canEdit = $settings->membersCanSet || $app->auth()->isMod();
                if (!$canEdit) {
                    $ctx->reject('You cannot change your avatar URL.');

                    return;
                }

                $result = $validator->validate($ctx->avatarUrlInput);
                if (!$result['ok']) {
                    $ctx->reject($result['error']);

                    return;
                }

                $ctx->updateAvatarUrl = true;
                $ctx->avatarUrl = $result['url'] === '' ? null : $result['url'];
            },
        );

        $context->hooks()->add(
            HookName::CSP_IMG_SRC,
            static function () use ($settings): array {
                $hosts = [];
                foreach ($settings->allowedHosts as $rule) {
                    if (str_starts_with($rule, '*.') && strlen($rule) > 2) {
                        // CSP does not support *; allow bare apex if present elsewhere.
                        $hosts[] = substr($rule, 2);
                        continue;
                    }
                    $hosts[] = $rule;
                }

                return array_values(array_unique(array_filter($hosts)));
            },
        );
    }
}
