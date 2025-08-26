# SQL Server Migration Helper for Laravel

This guide explains how to safely modify columns in SQL Server when they have dependencies (indexes, foreign keys, constraints).

## Problem

SQL Server doesn't allow altering columns that are part of indexes or foreign key constraints. You'll get errors like:

```
SQLSTATE[42000]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server] 
The index 'workers_status_nationality_id_profession_id_index' is dependent on column 'status'.
```

## Solution

We've created two tools to handle this:

1. **Artisan Command**: `make:sqlserver-migration` - Generates migration files
2. **Trait**: `SqlServerMigrationHelper` - Reusable helper methods

## Usage

### Method 1: Artisan Command (Recommended for quick migrations)

Generate a migration that handles dependencies automatically:

```bash
php artisan make:sqlserver-migration workers status --type=string --nullable
```

Options:
- `--type`: New data type (string, integer, etc.)
- `--nullable`: Make column nullable
- `--default`: Set default value

### Method 2: Use the Trait in Your Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\SqlServerMigrationHelper;

return new class extends Migration
{
    use SqlServerMigrationHelper;

    public function up(): void
    {
        $this->safeColumnModification('workers', 'status', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->string('status', 50)->nullable()->change();
            });
        });
    }

    public function down(): void
    {
        $this->safeColumnModification('workers', 'status', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->string('status', 20)->nullable()->change();
            });
        });
    }
};
```

## How It Works

The `safeColumnModification` method:

1. **Detects Dependencies**: Queries SQL Server system tables to find:
   - Indexes (including unique constraints)
   - Foreign key constraints
   - Check constraints
   - Default constraints

2. **Temporarily Drops Dependencies**: Removes all constraints/indexes safely

3. **Modifies Column**: Executes your column modification

4. **Recreates Dependencies**: Restores all constraints/indexes exactly as they were

## Supported Dependencies

- ✅ **Indexes**: Regular, unique, clustered, non-clustered
- ✅ **Foreign Keys**: With referential actions (CASCADE, SET NULL, etc.)
- ✅ **Check Constraints**: With original definitions
- ✅ **Default Constraints**: With original default values
- ✅ **Unique Constraints**: As unique indexes

## Example Migration

See `database/migrations/example_sqlserver_column_modification.php` for a complete example.

## Best Practices

1. **Always Test**: Test migrations in development/staging first
2. **Backup**: Ensure you have database backups before running migrations
3. **Downtime**: Be aware that dropping/recreating indexes can cause temporary performance impact
4. **Logging**: The trait logs all operations for debugging

## Troubleshooting

### Common Issues

1. **Primary Key Errors**: The trait automatically skips primary key constraints
2. **Complex Indexes**: Very complex indexes might need manual recreation
3. **Foreign Key Details**: Some foreign key details might need manual specification

### Debugging

Check Laravel logs for detailed information about what dependencies were found and recreated:

```bash
tail -f storage/logs/laravel.log
```

### Manual Override

If automatic recreation fails, you can manually specify constraints in your migration:

```php
$this->safeColumnModification('workers', 'status', function () {
    Schema::table('workers', function (Blueprint $table) {
        $table->string('status', 50)->nullable()->change();
    });
    
    // Manual recreation if needed
    DB::statement("CREATE INDEX [custom_index_name] ON [workers] ([status])");
});
```

## Migration Commands

```bash
# Generate migration
php artisan make:sqlserver-migration table_name column_name --type=string --nullable

# Run migration
php artisan migrate

# Rollback migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status
```

## File Structure

```
app/
├── Console/Commands/
│   └── CreateSqlServerMigration.php    # Artisan command
├── Traits/
│   └── SqlServerMigrationHelper.php    # Reusable trait
database/
└── migrations/
    └── example_sqlserver_column_modification.php  # Example migration
```

## Contributing

To enhance the migration helper:

1. Add new dependency types in `getColumnDependencies()`
2. Enhance recreation logic in `recreateDependencies()`
3. Add new options to the Artisan command
4. Improve error handling and logging

## Support

For issues or questions:
1. Check the Laravel logs
2. Verify SQL Server permissions
3. Test with a simple column first
4. Review the example migration