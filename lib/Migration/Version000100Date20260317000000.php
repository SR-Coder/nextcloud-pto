<?php

declare(strict_types=1);

namespace OCA\PTO\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000100Date20260317000000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Policies table
        if (!$schema->hasTable('pto_policies')) {
            $table = $schema->createTable('pto_policies');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('name', Types::STRING, [
                'notnull' => true,
                'length' => 100,
            ]);
            $table->addColumn('type', Types::STRING, [
                'notnull' => true,
                'length' => 20,
                'comment' => 'unlimited, accrual, or fixed',
            ]);
            $table->addColumn('accrual_rate', Types::DECIMAL, [
                'notnull' => false,
                'precision' => 10,
                'scale' => 2,
                'comment' => 'Hours accrued per period (for accrual type)',
            ]);
            $table->addColumn('accrual_period', Types::STRING, [
                'notnull' => false,
                'length' => 20,
                'comment' => 'daily, weekly, monthly, yearly',
            ]);
            $table->addColumn('max_balance', Types::DECIMAL, [
                'notnull' => false,
                'precision' => 10,
                'scale' => 2,
                'comment' => 'Max hours that can be accrued',
            ]);
            $table->addColumn('fixed_annual_hours', Types::DECIMAL, [
                'notnull' => false,
                'precision' => 10,
                'scale' => 2,
                'comment' => 'Fixed hours per year (for fixed type)',
            ]);
            $table->addColumn('reset_date', Types::STRING, [
                'notnull' => false,
                'length' => 10,
                'comment' => 'MM-DD format for annual reset',
            ]);
            $table->addColumn('enabled', Types::BOOLEAN, [
                'notnull' => false,
            ]);
            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['type'], 'pto_policies_type_idx');
        }

        // User balances table
        if (!$schema->hasTable('pto_balances')) {
            $table = $schema->createTable('pto_balances');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('policy_id', Types::BIGINT, [
                'notnull' => true,
            ]);
            $table->addColumn('balance', Types::DECIMAL, [
                'notnull' => true,
                'precision' => 10,
                'scale' => 2,
                'default' => 0,
            ]);
            $table->addColumn('accrued_this_period', Types::DECIMAL, [
                'notnull' => true,
                'precision' => 10,
                'scale' => 2,
                'default' => 0,
            ]);
            $table->addColumn('used_this_year', Types::DECIMAL, [
                'notnull' => true,
                'precision' => 10,
                'scale' => 2,
                'default' => 0,
            ]);
            $table->addColumn('last_accrual_date', Types::DATETIME, [
                'notnull' => false,
            ]);
            $table->addColumn('updated_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['user_id', 'policy_id'], 'pto_balances_user_policy_idx');
            $table->addIndex(['user_id'], 'pto_balances_user_idx');
        }

        // Requests table
        if (!$schema->hasTable('pto_requests')) {
            $table = $schema->createTable('pto_requests');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('policy_id', Types::BIGINT, [
                'notnull' => true,
            ]);
            $table->addColumn('leave_type', Types::STRING, [
                'notnull' => true,
                'length' => 50,
                'comment' => 'vacation, sick, personal, etc',
            ]);
            $table->addColumn('start_date', Types::DATE, [
                'notnull' => true,
            ]);
            $table->addColumn('end_date', Types::DATE, [
                'notnull' => true,
            ]);
            $table->addColumn('hours', Types::DECIMAL, [
                'notnull' => true,
                'precision' => 10,
                'scale' => 2,
            ]);
            $table->addColumn('status', Types::STRING, [
                'notnull' => true,
                'length' => 20,
                'default' => 'pending',
                'comment' => 'pending, approved, denied, cancelled',
            ]);
            $table->addColumn('notes', Types::TEXT, [
                'notnull' => false,
            ]);
            $table->addColumn('submitted_by', Types::STRING, [
                'notnull' => true,
                'length' => 64,
                'comment' => 'User who submitted (may differ from user_id for delegation)',
            ]);
            $table->addColumn('calendar_event_id', Types::STRING, [
                'notnull' => false,
                'length' => 255,
                'comment' => 'CalDAV event UID',
            ]);
            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'pto_requests_user_idx');
            $table->addIndex(['status'], 'pto_requests_status_idx');
            $table->addIndex(['start_date', 'end_date'], 'pto_requests_dates_idx');
        }

        // Approvals table
        if (!$schema->hasTable('pto_approvals')) {
            $table = $schema->createTable('pto_approvals');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('request_id', Types::BIGINT, [
                'notnull' => true,
            ]);
            $table->addColumn('manager_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('action', Types::STRING, [
                'notnull' => true,
                'length' => 20,
                'comment' => 'approved, denied',
            ]);
            $table->addColumn('comments', Types::TEXT, [
                'notnull' => false,
            ]);
            $table->addColumn('acted_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['request_id'], 'pto_approvals_request_idx');
            $table->addIndex(['manager_id'], 'pto_approvals_manager_idx');
        }

        // User roles table
        if (!$schema->hasTable('pto_user_roles')) {
            $table = $schema->createTable('pto_user_roles');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('role', Types::STRING, [
                'notnull' => true,
                'length' => 20,
                'comment' => 'admin, manager, employee',
            ]);
            $table->addColumn('manager_id', Types::STRING, [
                'notnull' => false,
                'length' => 64,
                'comment' => 'Manager for this employee (null for admins/managers)',
            ]);
            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['user_id'], 'pto_user_roles_user_idx');
            $table->addIndex(['role'], 'pto_user_roles_role_idx');
            $table->addIndex(['manager_id'], 'pto_user_roles_manager_idx');
        }

        return $schema;
    }
}
