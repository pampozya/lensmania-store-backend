<?php

use App\Domain\License\StateMachine\GraceStateMachine;

it('denies validate when no record exists', function () {
    $result = GraceStateMachine::resolve(GraceStateMachine::STATE_NO_RECORD, GraceStateMachine::EVENT_VALIDATE, [
        'event' => 'E2',
    ]);

    expect($result['to'])->toBe(GraceStateMachine::STATE_NO_RECORD);
    expect($result['result'])->toBe(GraceStateMachine::RESULT_DENY);
    expect($result['reason'])->toBe('NO_RECORD_VALIDATE_DENIED');
    expect($result['actions'])->toContain('no_token');
});

it('keeps grace expired state locked offline', function () {
    $result = GraceStateMachine::resolve(GraceStateMachine::STATE_GRACE_LOCKED, GraceStateMachine::EVENT_OFFLINE_CHECK, [
        'token_valid' => true,
        'grace_used' => true,
        'grace_elapsed_seconds' => 10801,
        'clock_rollback_detected' => false,
        'event' => 'E3',
        'max_offline_grace_seconds' => 10800,
    ]);

    expect($result['to'])->toBe(GraceStateMachine::STATE_GRACE_EXPIRED);
    expect($result['result'])->toBe(GraceStateMachine::RESULT_DENY);
    expect($result['reason'])->toBe('GRACE_EXPIRED');
});

it('transitions grace_locked to active_online on plain online validate', function () {
    $result = GraceStateMachine::resolve(GraceStateMachine::STATE_GRACE_LOCKED, GraceStateMachine::EVENT_VALIDATE, [
        'license_id' => 1,
        'device_id' => 'dev-1',
        'token_valid' => true,
        'license_status' => 'active',
        'clock_rollback_detected' => false,
        'event' => 'E2',
    ]);

    expect($result['to'])->toBe(GraceStateMachine::STATE_ACTIVE_ONLINE);
    expect($result['result'])->toBe(GraceStateMachine::RESULT_ALLOW);
});

it('transitions grace_locked to active_online on grace-claim validate', function () {
    $result = GraceStateMachine::resolve(GraceStateMachine::STATE_GRACE_LOCKED, GraceStateMachine::EVENT_GRACE_CLAIM, [
        'license_id' => 1,
        'device_id' => 'dev-1',
        'token_valid' => true,
        'license_status' => 'active',
        'clock_rollback_detected' => false,
        'grace_claim_present' => true,
        'grace_claim_signature_valid' => true,
        'event' => 'E4',
    ]);

    expect($result['to'])->toBe(GraceStateMachine::STATE_ACTIVE_ONLINE);
    expect($result['result'])->toBe(GraceStateMachine::RESULT_ALLOW);
});

it('clears grace on next online validate', function () {
    $result = GraceStateMachine::resolve(GraceStateMachine::STATE_GRACE_LOCKED, GraceStateMachine::EVENT_VALIDATE, [
        'license_id' => 1,
        'device_id' => 'dev-1',
        'token_valid' => true,
        'license_status' => 'active',
        'clock_rollback_detected' => false,
        'grace_claim_present' => true,
        'grace_claim_signature_valid' => true,
        'event' => 'E2',
    ]);

    expect($result['to'])->toBe(GraceStateMachine::STATE_ACTIVE_ONLINE);
    expect($result['result'])->toBe(GraceStateMachine::RESULT_ALLOW);
    expect($result['actions'])->toContain('clear_open_grace');
    expect($result['actions'])->toContain('reset_local_grace_flags');
});

it('allows grace entry only once until clear', function () {
    $entry = GraceStateMachine::resolve(GraceStateMachine::STATE_ACTIVE_ONLINE, GraceStateMachine::EVENT_OFFLINE_CHECK, [
        'token_valid' => true,
        'clock_rollback_detected' => false,
        'first_activated_at' => '2026-06-06T00:00:00Z',
        'grace_used' => false,
        'has_open_grace_record' => false,
        'event' => 'E3',
    ]);

    expect($entry['to'])->toBe(GraceStateMachine::STATE_GRACE_LOCKED);
    expect($entry['result'])->toBe(GraceStateMachine::RESULT_ALLOW);

    $again = GraceStateMachine::resolve(GraceStateMachine::STATE_ACTIVE_ONLINE, GraceStateMachine::EVENT_OFFLINE_CHECK, [
        'token_valid' => true,
        'clock_rollback_detected' => false,
        'first_activated_at' => '2026-06-06T00:00:00Z',
        'grace_used' => true,
        'has_open_grace_record' => true,
        'event' => 'E3',
    ]);

    expect($again['to'])->toBe(GraceStateMachine::STATE_ACTIVE_ONLINE);
    expect($again['result'])->toBe(GraceStateMachine::RESULT_DENY);
});

it('detects rollback lock from token claim', function () {
    $result = GraceStateMachine::resolve(GraceStateMachine::STATE_GRACE_LOCKED, GraceStateMachine::EVENT_OFFLINE_CHECK, [
        'token_valid' => true,
        'grace_used' => true,
        'clock_rollback_detected' => true,
        'grace_elapsed_seconds' => 10,
        'event' => 'E3',
    ]);

    expect($result['to'])->toBe(GraceStateMachine::STATE_GRACE_LOCKED);
    expect($result['result'])->toBe(GraceStateMachine::RESULT_DENY);
});

it('stays in GRACE_EXPIRED offline until successful online validate', function () {
    $offline = GraceStateMachine::resolve(GraceStateMachine::STATE_GRACE_EXPIRED, GraceStateMachine::EVENT_OFFLINE_CHECK, [
        'token_valid' => true,
        'grace_used' => true,
        'clock_rollback_detected' => false,
        'event' => 'E3',
    ]);

    expect($offline['to'])->toBe(GraceStateMachine::STATE_GRACE_EXPIRED);
    expect($offline['result'])->toBe(GraceStateMachine::RESULT_DENY);

    $recovered = GraceStateMachine::resolve(GraceStateMachine::STATE_GRACE_EXPIRED, GraceStateMachine::EVENT_VALIDATE, [
        'license_id' => 1,
        'device_id' => 'dev-1',
        'token_valid' => true,
        'license_status' => 'active',
        'clock_rollback_detected' => false,
        'event' => 'E2',
    ]);

    expect($recovered['to'])->toBe(GraceStateMachine::STATE_ACTIVE_ONLINE);
    expect($recovered['result'])->toBe(GraceStateMachine::RESULT_ALLOW);
});

it('detects rollback during validate', function () {
    $result = GraceStateMachine::resolve(GraceStateMachine::STATE_ACTIVE_ONLINE, GraceStateMachine::EVENT_VALIDATE, [
        'license_id' => 1,
        'device_id' => 'dev-1',
        'token_valid' => true,
        'license_status' => 'active',
        'clock_rollback_detected' => true,
        'event' => 'E2',
    ]);

    expect($result['to'])->toBe(GraceStateMachine::STATE_ACTIVE_ONLINE);
    expect($result['result'])->toBe(GraceStateMachine::RESULT_DENY);
    expect($result['reason'])->toBe('NO_MATCHING_RULE');
});

it('denies never-activated offline launch path by design', function () {
    $result = GraceStateMachine::resolve(GraceStateMachine::STATE_NO_RECORD, GraceStateMachine::EVENT_OFFLINE_CHECK, [
        'event' => 'E3',
    ]);

    expect($result['to'])->toBe(GraceStateMachine::STATE_NO_RECORD);
    expect($result['result'])->toBe(GraceStateMachine::RESULT_DENY);
    expect($result['reason'])->toBe('NO_RECORD_OFFLINE_DENIED');
});
