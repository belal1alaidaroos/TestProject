<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSqlServerMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:sqlserver-migration 
                            {table : The name of the table}
                            {column : The name of the column to modify}
                            {--type=string : The new data type for the column}
                            {--nullable : Whether the column should be nullable}
                            {--default= : Default value for the column}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a SQL Server migration that safely handles column modifications with dependencies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $table = $this->argument('table');
        $column = $this->argument('column');
        $type = $this->option('type');
        $nullable = $this->option('nullable');
        $default = $this->option('default');

        $this->info("Creating SQL Server migration for table: {$table}, column: {$column}");

        // Generate the migration file
        $migrationName = "modify_{$table}_{$column}_column";
        $migrationPath = $this->generateMigration($migrationName, $table, $column, $type, $nullable, $default);

        $this->info("Migration created successfully: {$migrationPath}");
        $this->info("Run: php artisan migrate to execute the migration");
    }

    /**
     * Generate the migration file content.
     */
    protected function generateMigration($name, $table, $column, $type, $nullable, $default)
    {
        $stub = $this->getMigrationStub($table, $column, $type, $nullable, $default);
        
        $filename = date('Y_m_d_His') . "_{$name}.php";
        $path = database_path("migrations/{$filename}");
        
        file_put_contents($path, $stub);
        
        return $filename;
    }

    /**
     * Get the migration stub content.
     */
    protected function getMigrationStub($table, $column, $type, $nullable, $default)
    {
        $nullableStr = $nullable ? '->nullable()' : '->notNull()';
        $defaultStr = $default ? "->default('{$default}')" : '';
        
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class Modify{$this->getClassName($table)}{$this->getClassName($column)}Column extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all dependencies before modifying the column
        \$dependencies = \$this->getColumnDependencies('{$table}', '{$column}');
        
        // Drop all dependent indexes and constraints
        \$this->dropDependencies(\$dependencies);
        
        // Modify the column
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->{$type}('{$column}')->change(){$nullableStr}{$defaultStr};
        });
        
        // Recreate all dependencies
        \$this->recreateDependencies(\$dependencies);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all dependencies before modifying the column
        \$dependencies = \$this->getColumnDependencies('{$table}', '{$column}');
        
        // Drop all dependent indexes and constraints
        \$this->dropDependencies(\$dependencies);
        
        // Revert the column modification (you may need to adjust this based on your original schema)
        Schema::table('{$table}', function (Blueprint \$table) {
            // Add your original column definition here
            // Example: \$table->string('{$column}')->change();
        });
        
        // Recreate all dependencies
        \$this->recreateDependencies(\$dependencies);
    }

    /**
     * Get all dependencies (indexes and constraints) for a specific column.
     */
    protected function getColumnDependencies(string \$table, string \$column): array
    {
        \$dependencies = [
            'indexes' => [],
            'foreign_keys' => [],
            'check_constraints' => [],
            'default_constraints' => []
        ];

        // Get indexes that include this column
        \$indexes = DB::select("
            SELECT 
                i.name as index_name,
                i.type_desc,
                ic.is_included_column,
                c.name as column_name
            FROM sys.indexes i
            INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
            INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
            WHERE i.object_id = OBJECT_ID('{$table}')
            AND c.name = '{$column}'
        ");

        foreach (\$indexes as \$index) {
            \$dependencies['indexes'][] = [
                'name' => \$index->index_name,
                'type' => \$index->type_desc,
                'is_included' => \$index->is_included_column,
                'column' => \$index->column_name
            ];
        }

        // Get foreign key constraints
        \$foreignKeys = DB::select("
            SELECT 
                fk.name as constraint_name,
                c.name as column_name
            FROM sys.foreign_keys fk
            INNER JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
            INNER JOIN sys.columns c ON fkc.parent_object_id = c.object_id AND fkc.parent_column_id = c.column_id
            WHERE fk.parent_object_id = OBJECT_ID('{$table}')
            AND c.name = '{$column}'
        ");

        foreach (\$foreignKeys as \$fk) {
            \$dependencies['foreign_keys'][] = [
                'name' => \$fk->constraint_name,
                'column' => \$fk->column_name
            ];
        }

        // Get check constraints
        \$checkConstraints = DB::select("
            SELECT 
                cc.name as constraint_name,
                cc.definition
            FROM sys.check_constraints cc
            INNER JOIN sys.columns c ON cc.parent_object_id = c.object_id AND cc.parent_column_id = c.column_id
            WHERE cc.parent_object_id = OBJECT_ID('{$table}')
            AND c.name = '{$column}'
        ");

        foreach (\$checkConstraints as \$cc) {
            \$dependencies['check_constraints'][] = [
                'name' => \$cc->constraint_name,
                'definition' => \$cc->definition
            ];
        }

        // Get default constraints
        \$defaultConstraints = DB::select("
            SELECT 
                dc.name as constraint_name,
                dc.definition
            FROM sys.default_constraints dc
            INNER JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
            WHERE dc.parent_object_id = OBJECT_ID('{$table}')
            AND c.name = '{$column}'
        ");

        foreach (\$defaultConstraints as \$dc) {
            \$dependencies['default_constraints'][] = [
                'name' => \$dc->constraint_name,
                'definition' => \$dc->definition
            ];
        }

        return \$dependencies;
    }

    /**
     * Drop all dependencies temporarily.
     */
    protected function dropDependencies(array \$dependencies): void
    {
        // Drop indexes
        foreach (\$dependencies['indexes'] as \$index) {
            if (\$index['name'] !== 'PK_' . \$this->getTableName('{$table}')) { // Don't drop primary key
                DB::statement("DROP INDEX [{\$index['name']}] ON [{$table}]");
            }
        }

        // Drop foreign keys
        foreach (\$dependencies['foreign_keys'] as \$fk) {
            DB::statement("ALTER TABLE [{$table}] DROP CONSTRAINT [{\$fk['name']}]");
        }

        // Drop check constraints
        foreach (\$dependencies['check_constraints'] as \$cc) {
            DB::statement("ALTER TABLE [{$table}] DROP CONSTRAINT [{\$cc['name']}]");
        }

        // Drop default constraints
        foreach (\$dependencies['default_constraints'] as \$dc) {
            DB::statement("ALTER TABLE [{$table}] DROP CONSTRAINT [{\$dc['name']}]");
        }
    }

    /**
     * Recreate all dependencies.
     */
    protected function recreateDependencies(array \$dependencies): void
    {
        // Recreate indexes
        foreach (\$dependencies['indexes'] as \$index) {
            if (\$index['name'] !== 'PK_' . \$this->getTableName('{$table}')) { // Don't recreate primary key
                \$this->recreateIndex('{$table}', \$index);
            }
        }

        // Recreate foreign keys
        foreach (\$dependencies['foreign_keys'] as \$fk) {
            \$this->recreateForeignKey('{$table}', \$fk);
        }

        // Recreate check constraints
        foreach (\$dependencies['check_constraints'] as \$cc) {
            DB::statement("ALTER TABLE [{$table}] ADD CONSTRAINT [{\$cc['name']}] CHECK ({\$cc['definition']})");
        }

        // Recreate default constraints
        foreach (\$dependencies['default_constraints'] as \$dc) {
            DB::statement("ALTER TABLE [{$table}] ADD CONSTRAINT [{\$dc['name']}] DEFAULT {\$dc['definition']} FOR [{\$dc['column']}]");
        }
    }

    /**
     * Recreate an index.
     */
    protected function recreateIndex(string \$table, array \$index): void
    {
        // This is a simplified recreation - you may need to enhance this based on your specific index types
        if (\$index['type'] === 'NONCLUSTERED') {
            DB::statement("CREATE NONCLUSTERED INDEX [{\$index['name']}] ON [{$table}] ([{\$index['column']}])");
        }
    }

    /**
     * Recreate a foreign key constraint.
     */
    protected function recreateForeignKey(string \$table, array \$fk): void
    {
        // This is a simplified recreation - you may need to enhance this to get the actual foreign key details
        // For now, we'll just log that we need to manually recreate it
        \Log::info("Foreign key constraint [{\$fk['name']}] needs to be manually recreated for table [{$table}]");
    }

    /**
     * Get the class name from a string.
     */
    protected function getClassName(string \$string): string
    {
        return str_replace('_', '', ucwords(\$string, '_'));
    }

    /**
     * Get the table name from a string.
     */
    protected function getTableName(string \$string): string
    {
        return str_replace('_', '', ucwords(\$string, '_'));
    }
}
PHP;
    }
}