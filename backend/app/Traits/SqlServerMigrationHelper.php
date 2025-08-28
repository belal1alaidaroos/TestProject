<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait SqlServerMigrationHelper
{
    /**
     * Safely modify a column by temporarily dropping and recreating dependencies.
     */
    protected function safeColumnModification(string $table, string $column, callable $modificationCallback): void
    {
        // Get all dependencies before modifying the column
        $dependencies = $this->getColumnDependencies($table, $column);
        
        // Log what we found
        $this->logDependencies($table, $column, $dependencies);
        
        // Drop all dependent indexes and constraints
        $this->dropDependencies($table, $dependencies);
        
        // Execute the column modification
        $modificationCallback();
        
        // Recreate all dependencies
        $this->recreateDependencies($table, $dependencies);
    }

    /**
     * Get all dependencies (indexes and constraints) for a specific column.
     */
    protected function getColumnDependencies(string $table, string $column): array
    {
        $dependencies = [
            'indexes' => [],
            'foreign_keys' => [],
            'check_constraints' => [],
            'default_constraints' => [],
            'unique_constraints' => []
        ];

        // Get indexes that include this column
        $indexes = DB::select("
            SELECT 
                i.name as index_name,
                i.type_desc,
                i.is_unique,
                ic.is_included_column,
                ic.key_ordinal,
                c.name as column_name,
                ic.is_descending_key
            FROM sys.indexes i
            INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
            INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
            WHERE i.object_id = OBJECT_ID(?)
            AND c.name = ?
            ORDER BY ic.key_ordinal
        ", [$table, $column]);

        foreach ($indexes as $index) {
            $dependencies['indexes'][] = [
                'name' => $index->index_name,
                'type' => $index->type_desc,
                'is_unique' => $index->is_unique,
                'is_included' => $index->is_included_column,
                'column' => $index->column_name,
                'key_ordinal' => $index->key_ordinal,
                'is_descending' => $index->is_descending_key
            ];
        }

        // Get foreign key constraints
        $foreignKeys = DB::select("
            SELECT 
                fk.name as constraint_name,
                c.name as column_name,
                OBJECT_NAME(fk.referenced_object_id) as referenced_table,
                COL_NAME(fkc.referenced_object_id, fkc.referenced_column_id) as referenced_column,
                fk.delete_referential_action,
                fk.update_referential_action
            FROM sys.foreign_keys fk
            INNER JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
            INNER JOIN sys.columns c ON fkc.parent_object_id = c.object_id AND fkc.parent_column_id = c.column_id
            WHERE fk.parent_object_id = OBJECT_ID(?)
            AND c.name = ?
        ", [$table, $column]);

        foreach ($foreignKeys as $fk) {
            $dependencies['foreign_keys'][] = [
                'name' => $fk->constraint_name,
                'column' => $fk->column_name,
                'referenced_table' => $fk->referenced_table,
                'referenced_column' => $fk->referenced_column,
                'delete_action' => $fk->delete_referential_action,
                'update_action' => $fk->update_referential_action
            ];
        }

        // Get check constraints
        $checkConstraints = DB::select("
            SELECT 
                cc.name as constraint_name,
                cc.definition
            FROM sys.check_constraints cc
            INNER JOIN sys.columns c ON cc.parent_object_id = c.object_id AND cc.parent_column_id = c.column_id
            WHERE cc.parent_object_id = OBJECT_ID(?)
            AND c.name = ?
        ", [$table, $column]);

        foreach ($checkConstraints as $cc) {
            $dependencies['check_constraints'][] = [
                'name' => $cc->constraint_name,
                'definition' => $cc->definition
            ];
        }

        // Get default constraints
        $defaultConstraints = DB::select("
            SELECT 
                dc.name as constraint_name,
                dc.definition,
                c.name as column_name
            FROM sys.default_constraints dc
            INNER JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
            WHERE dc.parent_object_id = OBJECT_ID(?)
            AND c.name = ?
        ", [$table, $column]);

        foreach ($defaultConstraints as $dc) {
            $dependencies['default_constraints'][] = [
                'name' => $dc->constraint_name,
                'definition' => $dc->definition,
                'column' => $dc->column_name   // ✅ تمت الإضافة
            ];
        }

        // Get unique constraints
        $uniqueConstraints = DB::select("
            SELECT 
                i.name as constraint_name,
                c.name as column_name
            FROM sys.indexes i
            INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
            INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
            WHERE i.object_id = OBJECT_ID(?)
            AND c.name = ?
            AND i.is_unique = 1
            AND i.is_primary_key = 0
        ", [$table, $column]);

        foreach ($uniqueConstraints as $uc) {
            $dependencies['unique_constraints'][] = [
                'name' => $uc->constraint_name,
                'column' => $uc->column_name
            ];
        }

        return $dependencies;
    }

    /**
     * Log dependencies for debugging purposes.
     */
    protected function logDependencies(string $table, string $column, array $dependencies): void
    {
        $logMessage = "Column dependencies for {$table}.{$column}:\n";
        
        if (!empty($dependencies['indexes'])) {
            $logMessage .= "- Indexes: " . implode(', ', array_column($dependencies['indexes'], 'name')) . "\n";
        }
        
        if (!empty($dependencies['foreign_keys'])) {
            $logMessage .= "- Foreign Keys: " . implode(', ', array_column($dependencies['foreign_keys'], 'name')) . "\n";
        }
        
        if (!empty($dependencies['check_constraints'])) {
            $logMessage .= "- Check Constraints: " . implode(', ', array_column($dependencies['check_constraints'], 'name')) . "\n";
        }
        
        if (!empty($dependencies['default_constraints'])) {
            $logMessage .= "- Default Constraints: " . implode(', ', array_column($dependencies['default_constraints'], 'name')) . "\n";
        }
        
        if (!empty($dependencies['unique_constraints'])) {
            $logMessage .= "- Unique Constraints: " . implode(', ', array_column($dependencies['unique_constraints'], 'name')) . "\n";
        }
        
        Log::info($logMessage);
    }

    /**
     * Drop all dependencies temporarily.
     */
    protected function dropDependencies(string $table, array $dependencies): void
    {
        // Drop indexes (except primary key)
        foreach ($dependencies['indexes'] as $index) {
            if (strpos($index['name'], 'PK_') !== 0) { // Don't drop primary key
                DB::statement("DROP INDEX [{$index['name']}] ON [{$table}]");
                Log::info("Dropped index [{$index['name']}] on table [{$table}]");
            }
        }

        // Drop foreign keys
        foreach ($dependencies['foreign_keys'] as $fk) {
            DB::statement("ALTER TABLE [{$table}] DROP CONSTRAINT [{$fk['name']}]");
            Log::info("Dropped foreign key [{$fk['name']}] on table [{$table}]");
        }

        // Drop check constraints
        foreach ($dependencies['check_constraints'] as $cc) {
            DB::statement("ALTER TABLE [{$table}] DROP CONSTRAINT [{$cc['name']}]");
            Log::info("Dropped check constraint [{$cc['name']}] on table [{$table}]");
        }

        // Drop default constraints
        foreach ($dependencies['default_constraints'] as $dc) {
            DB::statement("ALTER TABLE [{$table}] DROP CONSTRAINT [{$dc['name']}]");
            Log::info("Dropped default constraint [{$dc['name']}] on table [{$table}]");
        }

        // Drop unique constraints
        foreach ($dependencies['unique_constraints'] as $uc) {
            DB::statement("DROP INDEX [{$uc['name']}] ON [{$table}]");
            Log::info("Dropped unique constraint [{$uc['name']}] on table [{$table}]");
        }
    }

    /**
     * Recreate all dependencies.
     */
    protected function recreateDependencies(string $table, array $dependencies): void
    {
        // Recreate indexes
        foreach ($dependencies['indexes'] as $index) {
            if (strpos($index['name'], 'PK_') !== 0) { // Don't recreate primary key
                $this->recreateIndex($table, $index);
            }
        }

        // Recreate foreign keys
        foreach ($dependencies['foreign_keys'] as $fk) {
            $this->recreateForeignKey($table, $fk);
        }

        // Recreate check constraints
        foreach ($dependencies['check_constraints'] as $cc) {
            DB::statement("ALTER TABLE [{$table}] ADD CONSTRAINT [{$cc['name']}] CHECK ({$cc['definition']})");
            Log::info("Recreated check constraint [{$cc['name']}] on table [{$table}]");
        }

        // Recreate default constraints
        foreach ($dependencies['default_constraints'] as $dc) {
            DB::statement("ALTER TABLE [{$table}] ADD CONSTRAINT [{$dc['name']}] DEFAULT {$dc['definition']} FOR [{$dc['column']}]");
            Log::info("Recreated default constraint [{$dc['name']}] on table [{$table}]");
        }

        // Recreate unique constraints
        foreach ($dependencies['unique_constraints'] as $uc) {
            DB::statement("CREATE UNIQUE NONCLUSTERED INDEX [{$uc['name']}] ON [{$table}] ([{$uc['column']}])");
            Log::info("Recreated unique constraint [{$uc['name']}] on table [{$table}]");
        }
    }

    /**
     * Recreate an index with proper configuration.
     */
    protected function recreateIndex(string $table, array $index): void
    {
        $unique = $index['is_unique'] ? 'UNIQUE' : '';
        $clustered = $index['type'] === 'CLUSTERED' ? 'CLUSTERED' : 'NONCLUSTERED';
        
        DB::statement("CREATE {$unique} {$clustered} INDEX [{$index['name']}] ON [{$table}] ([{$index['column']}])");
        
        Log::info("Recreated index [{$index['name']}] on table [{$table}]");
    }

    /**
     * Recreate a foreign key constraint.
     */
    protected function recreateForeignKey(string $table, array $fk): void
    {
        $deleteAction = $this->getReferentialAction($fk['delete_action']);
        $updateAction = $this->getReferentialAction($fk['update_action']);
        
        $sql = "ALTER TABLE [{$table}] ADD CONSTRAINT [{$fk['name']}] 
                FOREIGN KEY ([{$fk['column']}]) 
                REFERENCES [{$fk['referenced_table']}] ([{$fk['referenced_column']}])";
        
        if ($deleteAction) {
            $sql .= " ON DELETE {$deleteAction}";
        }
        
        if ($updateAction) {
            $sql .= " ON UPDATE {$updateAction}";
        }
        
        DB::statement($sql);
        Log::info("Recreated foreign key [{$fk['name']}] on table [{$table}]");
    }

    /**
     * Get the referential action string.
     */
    protected function getReferentialAction(int $action): ?string
    {
        return match ($action) {
            1 => 'CASCADE',
            2 => 'SET NULL',
            3 => 'SET DEFAULT',
            4 => 'NO ACTION',
            default => null
        };
    }
}
