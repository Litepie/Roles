<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create roles table with all fields
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id'); // role id
            $table->string('name');       // For MySQL 8.0 use string('name', 125);
            $table->string('guard_name'); // For MySQL 8.0 use string('guard_name', 125);
            $table->text('description')->nullable(); // Role description
            $table->timestamps();
            $table->softDeletes(); // Soft delete support

            // Unique constraint
            $table->unique(['name', 'guard_name']);
            
            // Performance indexes
            $table->index('name', 'roles_name_index');
            $table->index('guard_name', 'roles_guard_name_index');
        });

        // Create user_roles pivot table
        Schema::create('user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->timestamps(); // Track when role was assigned
            
            // Indexes for performance
            $table->index(['model_id', 'model_type'], 'user_roles_model_id_model_type_index');
            $table->index('role_id', 'user_roles_role_id_index');
            $table->index('model_type', 'user_roles_model_type_index');

            // Foreign key constraint
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            // Primary key
            $table->primary(['role_id', 'model_id', 'model_type'], 'user_roles_role_model_type_primary');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_roles');
        Schema::drop('roles');
    }
};