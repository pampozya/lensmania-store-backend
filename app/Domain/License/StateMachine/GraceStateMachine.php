<?php

declare(strict_types=1);

namespace App\Domain\License\StateMachine;

final class GraceStateMachine
{
    public const STATE_NO_RECORD = 'NO_RECORD';
    public const STATE_ACTIVE_ONLINE = 'ACTIVE_ONLINE';
    public const STATE_GRACE_LOCKED = 'GRACE_LOCKED';
    public const STATE_GRACE_EXPIRED = 'GRACE_EXPIRED';
    public const STATE_DEACTIVATED = 'DEACTIVATED';
    public const STATE_LICENSE_REVOKED = 'LICENSE_REVOKED';

    public const EVENT_ACTIVATE = 'E1';
    public const EVENT_VALIDATE = 'E2';
    public const EVENT_OFFLINE_CHECK = 'E3';
    public const EVENT_GRACE_CLAIM = 'E4';
    public const EVENT_DEACTIVATE = 'E5';

    public const RESULT_ALLOW = 'allow';
    public const RESULT_DENY = 'deny';

    public static function transitions(): array
    {
        return [
            self::STATE_NO_RECORD => [
                self::EVENT_ACTIVATE => [
                    [
                        'to' => self::STATE_ACTIVE_ONLINE,
                        'result' => self::RESULT_ALLOW,
                        'reason' => 'ONLINE_ACTIVATE_OK',
                        'guard' => 'can_activate',
                        'actions' => ['create_or_restore_device_row', 'issue_signed_token'],
                    ],
                ],
                self::EVENT_VALIDATE => [
                    [
                        'to' => self::STATE_NO_RECORD,
                        'result' => self::RESULT_DENY,
                        'reason' => 'NO_RECORD_VALIDATE_DENIED',
                        'guard' => 'always',
                        'actions' => ['no_token'],
                    ],
                ],
                self::EVENT_OFFLINE_CHECK => [
                    [
                        'to' => self::STATE_NO_RECORD,
                        'result' => self::RESULT_DENY,
                        'reason' => 'NO_RECORD_OFFLINE_DENIED',
                        'guard' => 'always',
                        'actions' => ['hard_stop'],
                    ],
                ],
            ],

            self::STATE_ACTIVE_ONLINE => [
                self::EVENT_VALIDATE => [
                    [
                        'to' => self::STATE_ACTIVE_ONLINE,
                        'result' => self::RESULT_ALLOW,
                        'reason' => 'ONLINE_VALIDATE_OK',
                        'guard' => 'valid_online_request',
                        'actions' => ['refresh_last_validated_at', 'reissue_token', 'clear_open_grace_if_any'],
                    ],
                ],
                self::EVENT_OFFLINE_CHECK => [
                    [
                        'to' => self::STATE_GRACE_LOCKED,
                        'result' => self::RESULT_ALLOW,
                        'reason' => 'GRACE_ENTRY_OK',
                        'guard' => 'enter_grace_guard',
                        'actions' => ['set_grace_started_at', 'set_grace_used', 'persist_grace_claim_token'],
                    ],
                ],
                self::EVENT_DEACTIVATE => [
                    [
                        'to' => self::STATE_DEACTIVATED,
                        'result' => self::RESULT_ALLOW,
                        'reason' => 'SELF_DEACTIVATE_OK',
                        'guard' => 'is_active_device',
                        'actions' => ['deactivate_device', 'clear_device_token_and_grace'],
                    ],
                ],
            ],

            self::STATE_GRACE_LOCKED => [
                self::EVENT_VALIDATE => [
                    [
                        'to' => self::STATE_ACTIVE_ONLINE,
                        'result' => self::RESULT_ALLOW,
                        'reason' => 'GRACE_ONLINE_RECOVER',
                        'guard' => 'valid_online_request',
                        'actions' => ['refresh_last_validated_at', 'reissue_token', 'write_grace_used_event', 'clear_open_grace', 'reset_local_grace_flags'],
                    ],
                ],
                self::EVENT_GRACE_CLAIM => [
                    [
                        'to' => self::STATE_ACTIVE_ONLINE,
                        'result' => self::RESULT_ALLOW,
                        'reason' => 'GRACE_ONLINE_WITH_GRACE_CLAIM',
                        'guard' => 'valid_grace_reconciliation',
                        'actions' => ['refresh_last_validated_at', 'reissue_token', 'write_grace_used_event', 'clear_open_grace', 'reset_local_grace_flags'],
                    ],
                ],
                self::EVENT_OFFLINE_CHECK => [
                    [
                        'to' => self::STATE_GRACE_LOCKED,
                        'result' => self::RESULT_ALLOW,
                        'reason' => 'GRACE_CONTINUES',
                        'guard' => 'continue_grace_guard',
                        'actions' => ['maintain_lock'],
                    ],
                    [
                        'to' => self::STATE_GRACE_EXPIRED,
                        'result' => self::RESULT_DENY,
                        'reason' => 'GRACE_EXPIRED',
                        'guard' => 'grace_expired_guard',
                        'actions' => ['hard_stop', 'require_next_online_validate'],
                    ],
                ],
                self::EVENT_DEACTIVATE => [
                    [
                        'to' => self::STATE_DEACTIVATED,
                        'result' => self::RESULT_ALLOW,
                        'reason' => 'SELF_DEACTIVATE_OK',
                        'guard' => 'is_active_device',
                        'actions' => ['deactivate_device', 'clear_device_token_and_grace'],
                    ],
                ],
            ],

            self::STATE_GRACE_EXPIRED => [
                self::EVENT_VALIDATE => [
                    [
                        'to' => self::STATE_ACTIVE_ONLINE,
                        'result' => self::RESULT_ALLOW,
                        'reason' => 'GRACE_EXPIRED_RECOVER',
                        'guard' => 'valid_online_request',
                        'actions' => ['refresh_last_validated_at', 'reissue_token', 'clear_open_grace', 'reset_local_grace_flags'],
                    ],
                ],
                self::EVENT_OFFLINE_CHECK => [
                    [
                        'to' => self::STATE_GRACE_EXPIRED,
                        'result' => self::RESULT_DENY,
                        'reason' => 'GRACE_EXPIRED_OFFLINE_LOCKED',
                        'guard' => 'always',
                        'actions' => ['hard_stop'],
                    ],
                ],
                self::EVENT_DEACTIVATE => [
                    [
                        'to' => self::STATE_DEACTIVATED,
                        'result' => self::RESULT_ALLOW,
                        'reason' => 'DEACTIVATE_AFTER_EXPIRED',
                        'guard' => 'is_active_device',
                        'actions' => ['deactivate_device', 'clear_device_token_and_grace'],
                    ],
                ],
            ],

            self::STATE_DEACTIVATED => [
                self::EVENT_ACTIVATE => [
                    [
                        'to' => self::STATE_ACTIVE_ONLINE,
                        'result' => self::RESULT_ALLOW,
                        'reason' => 'ACTIVATE_AFTER_DEACTIVATE',
                        'guard' => 'can_activate',
                        'actions' => ['reactivate_device_row', 'issue_signed_token'],
                    ],
                ],
                self::EVENT_OFFLINE_CHECK => [
                    [
                        'to' => self::STATE_DEACTIVATED,
                        'result' => self::RESULT_DENY,
                        'reason' => 'DEACTIVATED_OFFLINE_DENIED',
                        'guard' => 'always',
                        'actions' => ['hard_stop'],
                    ],
                ],
            ],

            self::STATE_LICENSE_REVOKED => [
                'any' => [
                    [
                        'to' => self::STATE_LICENSE_REVOKED,
                        'result' => self::RESULT_DENY,
                        'reason' => 'LICENSE_REVOKED',
                        'guard' => 'always',
                        'actions' => ['hard_lock'],
                    ],
                ],
            ],
        ];
    }

    public static function resolve(string $from, string $event, array $context = []): array
    {
        $context['event'] = $event;
        $context['from'] = $from;
        $transitions = self::transitions();

        if ($from === self::STATE_LICENSE_REVOKED) {
            $default = $transitions[self::STATE_LICENSE_REVOKED]['any'][0];
            return self::formatResult($from, $event, $default, $context);
        }

        $eventTransitions = $transitions[$from][$event] ?? [];
        foreach ($eventTransitions as $rule) {
            if (self::passesGuard($rule['guard'], $context)) {
                return self::formatResult($from, $event, $rule, $context);
            }
        }

        return [
            'from' => $from,
            'event' => $event,
            'to' => $from,
            'result' => self::RESULT_DENY,
            'reason' => 'NO_MATCHING_RULE',
            'actions' => ['hard_lock'],
            'meta' => ['context' => $context],
        ];
    }

    private static function passesGuard(string $guard, array $context): bool
    {
        return match ($guard) {
            'can_activate' => self::guardCanActivate($context),
            'valid_online_request' => self::guardValidOnlineRequest($context),
            'valid_grace_reconciliation' => self::guardGraceReconciliation($context),
            'enter_grace_guard' => self::guardEnterGrace($context),
            'continue_grace_guard' => self::guardContinueGrace($context),
            'grace_expired_guard' => self::guardGraceExpired($context),
            'is_active_device' => self::guardIsActiveDevice($context),
            'always' => true,
            default => false,
        };
    }

    private static function guardCanActivate(array $context): bool
    {
        return ! empty($context['license_active'])
            && ($context['active_devices'] ?? PHP_INT_MAX) < ($context['max_active_devices'] ?? 2)
            && ! self::licenseRevoked($context)
            && ($context['device_id_stable'] ?? false);
    }

    private static function guardValidOnlineRequest(array $context): bool
    {
        return ! self::licenseRevoked($context)
            && ! ($context['clock_rollback_detected'] ?? false)
            && ($context['token_valid'] ?? false)
            && ($context['license_id'] ?? null) !== null
            && ($context['device_id'] ?? null) !== null;
    }

    private static function guardGraceReconciliation(array $context): bool
    {
        return self::guardValidOnlineRequest($context)
            && ! empty($context['grace_claim_present'])
            && ! empty($context['grace_claim_signature_valid']);
    }

    private static function guardEnterGrace(array $context): bool
    {
        return ($context['token_valid'] ?? false)
            && ! ($context['clock_rollback_detected'] ?? false)
            && ! empty($context['first_activated_at'])
            && empty($context['grace_used'])
            && ! ($context['has_open_grace_record'] ?? false);
    }

    private static function guardContinueGrace(array $context): bool
    {
        $elapsed = (int) ($context['grace_elapsed_seconds'] ?? PHP_INT_MAX);
        return ($context['token_valid'] ?? false)
            && ! ($context['clock_rollback_detected'] ?? false)
            && empty($context['grace_record_consumed_not_cleared'])
            && ($context['grace_used'] ?? false)
            && ($elapsed <= ($context['max_offline_grace_seconds'] ?? 10800));
    }

    private static function guardGraceExpired(array $context): bool
    {
        $elapsed = (int) ($context['grace_elapsed_seconds'] ?? 0);
        return ($context['token_valid'] ?? false)
            && ! ($context['clock_rollback_detected'] ?? false)
            && (
                ($elapsed > ($context['max_offline_grace_seconds'] ?? 10800))
                || ! empty($context['grace_record_consumed_not_cleared'])
            );
    }

    private static function guardIsActiveDevice(array $context): bool
    {
        return ($context['device_status'] ?? 'active') === 'active'
            && ! self::licenseRevoked($context);
    }

    private static function licenseRevoked(array $context): bool
    {
        return ($context['license_status'] ?? 'active') === 'revoked';
    }

    private static function formatResult(string $from, string $event, array $rule, array $context): array
    {
        return [
            'from' => $from,
            'to' => $rule['to'],
            'event' => $event,
            'result' => $rule['result'],
            'reason' => $rule['reason'],
            'actions' => $rule['actions'] ?? [],
            'meta' => $context,
        ];
    }
}
