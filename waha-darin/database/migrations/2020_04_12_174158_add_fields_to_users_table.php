<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'city_id')) {
                $table->unsignedBigInteger('city_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'bio')) {
                $table->mediumText('bio')->nullable();
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (! Schema::hasColumn('users', 'gender')) {
                $table->string('gender')->nullable();
            }
            if (! Schema::hasColumn('users', 'age')) {
                $table->unsignedSmallInteger('age')->nullable();
            }
            if (! Schema::hasColumn('users', 'address_line_one')) {
                $table->text('address_line_one')->nullable();
            }
            if (! Schema::hasColumn('users', 'address_line_two')) {
                $table->text('address_line_two')->nullable();
            }
            if (! Schema::hasColumn('users', 'postal_code')) {
                $table->text('postal_code')->nullable();
            }
        });

        $this->ensureForeignKey('users', 'country_id', 'countries', 'id');
        $this->ensureForeignKey('users', 'city_id', 'cities', 'id');
    }

    /**
     * Add FK only if the column exists and no FK is already defined (safe after partial migrations).
     */
    private function ensureForeignKey(string $table, string $column, string $refTable, string $refColumn): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        $conn = Schema::getConnection();
        if ($conn->getDriverName() !== 'mysql') {
            return;
        }

        $db = $conn->getDatabaseName();
        $exists = $conn->selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
             AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$db, $table, $column]
        );

        if ($exists !== null) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $refTable, $refColumn) {
            $blueprint->foreign($column)->references($refColumn)->on($refTable);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
