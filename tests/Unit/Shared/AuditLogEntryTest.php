<?php

namespace Tests\Unit\Shared;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * The framework-neutral shape of one audited modification. Writing it to
 * `auditorias` is EloquentAuditLogRepository's job and stays covered by
 * the Feature suite.
 */
class AuditLogEntryTest extends TestCase
{
    public function test_an_entry_that_records_nothing_is_rejected(): void
    {
        // "Only an effective modification is audited" — an entry with no
        // changed field would be a silent no-op row.
        $this->expectException(InvalidArgumentException::class);

        new AuditLogEntry(
            actorUserId: 1,
            auditableType: 'academic_credential',
            auditableId: 1,
            action: AuditLogEntry::ACTION_UPDATED,
            changes: [],
        );
    }

    public function test_a_changed_field_preserves_both_the_before_and_after_values(): void
    {
        $entry = new AuditLogEntry(
            actorUserId: 3,
            auditableType: 'academic_credential',
            auditableId: 7,
            action: AuditLogEntry::ACTION_UPDATED,
            changes: ['institution' => ['before' => 'UTN', 'after' => 'UCR']],
        );

        $this->assertSame(['institution' => ['before' => 'UTN', 'after' => 'UCR']], $entry->changes());
        $this->assertSame(3, $entry->actorUserId());
        $this->assertSame('academic_credential', $entry->auditableType());
        $this->assertSame(7, $entry->auditableId());
        $this->assertSame(AuditLogEntry::ACTION_UPDATED, $entry->action());
    }

    public function test_a_creation_keeps_the_null_before_value_distinct_from_the_new_one(): void
    {
        $entry = new AuditLogEntry(
            actorUserId: null,
            auditableType: 'teacher_assignment',
            auditableId: 1,
            action: AuditLogEntry::ACTION_CREATED,
            changes: ['result' => ['before' => null, 'after' => 'matched']],
        );

        $this->assertNull($entry->changes()['result']['before']);
        $this->assertSame('matched', $entry->changes()['result']['after']);
    }

    public function test_a_deletion_keeps_the_destroyed_value_as_the_before_side(): void
    {
        $entry = new AuditLogEntry(
            actorUserId: 2,
            auditableType: 'teacher_assignment',
            auditableId: 1,
            action: AuditLogEntry::ACTION_DELETED,
            changes: ['teacher_id' => ['before' => 9, 'after' => null]],
        );

        $this->assertSame(9, $entry->changes()['teacher_id']['before']);
        $this->assertNull($entry->changes()['teacher_id']['after']);
    }

    public function test_a_system_triggered_entry_may_have_no_actor(): void
    {
        // The scheduled expiration of overdue Technical Notes runs with
        // no authenticated user.
        $entry = new AuditLogEntry(
            actorUserId: null,
            auditableType: 'technical_note',
            auditableId: 1,
            action: AuditLogEntry::ACTION_UPDATED,
            changes: ['status' => ['before' => 'pending_ratification', 'after' => 'expired']],
        );

        $this->assertNull($entry->actorUserId());
    }
}
