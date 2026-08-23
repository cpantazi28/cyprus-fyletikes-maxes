<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tribes
        Schema::create('tribes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tag', 10)->unique();
            $table->text('description')->nullable();
            $table->foreignId('founder_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('points')->default(0);
            $table->unsignedInteger('rank')->default(1);
            $table->unsignedInteger('member_count')->default(1);
            $table->timestamps();
        });

        // 2. Tribe Members
        Schema::create('tribe_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tribe_id')->constrained('tribes')->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member'); // founder, baron, diplomat, recruiter, member
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
        });

        // 3. Tribe Diplomacy
        Schema::create('tribe_diplomacies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tribe_id')->constrained('tribes')->cascadeOnDelete();
            $table->foreignId('target_tribe_id')->constrained('tribes')->cascadeOnDelete();
            $table->string('type'); // ally, nap, enemy
            $table->string('status')->default('accepted'); // pending, accepted
            $table->timestamps();
        });

        // 4. Tribe Forum
        Schema::create('tribe_forum_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tribe_id')->constrained('tribes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_sticky')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('tribe_forum_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('tribe_forum_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        // 5. Villages
        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->default('Barbarian village');
            $table->integer('x');
            $table->integer('y');
            $table->string('continent', 4)->nullable(); // e.g. K55
            $table->unsignedInteger('points')->default(26);
            $table->decimal('loyalty', 5, 2)->default(100.00);
            $table->timestamp('last_loyalty_update')->nullable();

            // Resources
            $table->decimal('wood', 12, 2)->default(500.00);
            $table->decimal('clay', 12, 2)->default(500.00);
            $table->decimal('iron', 12, 2)->default(400.00);
            $table->timestamp('last_resource_update')->nullable();
            $table->unsignedInteger('coins_minted')->default(0);

            // Building levels
            $table->unsignedSmallInteger('main')->default(1);
            $table->unsignedSmallInteger('barracks')->default(0);
            $table->unsignedSmallInteger('stable')->default(0);
            $table->unsignedSmallInteger('garage')->default(0);
            $table->unsignedSmallInteger('snob')->default(0);
            $table->unsignedSmallInteger('smith')->default(0);
            $table->unsignedSmallInteger('place')->default(1);
            $table->unsignedSmallInteger('market')->default(0);
            $table->unsignedSmallInteger('wood_level')->default(1);
            $table->unsignedSmallInteger('stone_level')->default(1);
            $table->unsignedSmallInteger('iron_level')->default(1);
            $table->unsignedSmallInteger('farm_level')->default(1);
            $table->unsignedSmallInteger('storage_level')->default(1);
            $table->unsignedSmallInteger('hide_level')->default(1);
            $table->unsignedSmallInteger('wall_level')->default(0);

            $table->timestamps();

            $table->unique(['x', 'y']);
            $table->index('user_id');
            $table->index('continent');
        });

        // 6. Stationed Village Units
        Schema::create('village_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->unique()->constrained('villages')->cascadeOnDelete();
            $table->unsignedInteger('spear')->default(0);
            $table->unsignedInteger('sword')->default(0);
            $table->unsignedInteger('axe')->default(0);
            $table->unsignedInteger('archer')->default(0);
            $table->unsignedInteger('spy')->default(0);
            $table->unsignedInteger('light')->default(0);
            $table->unsignedInteger('marcher')->default(0);
            $table->unsignedInteger('heavy')->default(0);
            $table->unsignedInteger('ram')->default(0);
            $table->unsignedInteger('catapult')->default(0);
            $table->unsignedInteger('knight')->default(0);
            $table->unsignedInteger('snob')->default(0);
            $table->timestamps();
        });

        // 7. Stationed Foreign Support Units
        Schema::create('village_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('stationed_village_id')->constrained('villages')->cascadeOnDelete();
            $table->json('units'); // {spear: 10, sword: 5, ...}
            $table->timestamps();

            $table->index(['home_village_id', 'stationed_village_id']);
        });

        // 8. Building Queue
        Schema::create('building_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->string('building');
            $table->unsignedSmallInteger('level');
            $table->unsignedInteger('build_time_seconds');
            $table->timestamp('starts_at');
            $table->timestamp('finishes_at');
            $table->string('status')->default('in_progress'); // in_progress, completed, cancelled
            $table->timestamps();

            $table->index(['village_id', 'status', 'finishes_at']);
        });

        // 9. Recruitment Queue
        Schema::create('recruitment_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->string('building');
            $table->string('unit_type');
            $table->unsignedInteger('count');
            $table->unsignedInteger('count_finished')->default(0);
            $table->unsignedInteger('seconds_per_unit');
            $table->timestamp('starts_at');
            $table->timestamp('finishes_at');
            $table->timestamp('last_unit_finished_at')->nullable();
            $table->string('status')->default('in_progress');
            $table->timestamps();

            $table->index(['village_id', 'building', 'status']);
        });

        // 10. Troop Movements (Attacks, Supports, Returns)
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('target_village_id')->constrained('villages')->cascadeOnDelete();
            $table->string('type'); // attack, support, return
            $table->json('units'); // {spear: 50, light: 20, ...}
            $table->json('haul')->nullable(); // {wood: 120, clay: 200, iron: 50}
            $table->string('catapult_target')->nullable(); // building name if catapult target chosen
            $table->timestamp('departs_at');
            $table->timestamp('arrives_at');
            $table->boolean('is_cancelled')->default(false);
            $table->string('status')->default('en_route'); // en_route, completed, cancelled
            $table->timestamps();

            $table->index(['status', 'arrives_at']);
            $table->index('source_village_id');
            $table->index('target_village_id');
        });

        // 11. Market Transports
        Schema::create('market_transports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('target_village_id')->constrained('villages')->cascadeOnDelete();
            $table->unsignedInteger('wood')->default(0);
            $table->unsignedInteger('clay')->default(0);
            $table->unsignedInteger('iron')->default(0);
            $table->unsignedSmallInteger('merchants')->default(1);
            $table->timestamp('departs_at');
            $table->timestamp('arrives_at');
            $table->string('status')->default('en_route'); // en_route, completed
            $table->timestamps();

            $table->index(['status', 'arrives_at']);
        });

        // 12. Market Offers
        Schema::create('market_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('sell_resource'); // wood, clay, iron
            $table->unsignedInteger('sell_amount');
            $table->string('buy_resource');
            $table->unsignedInteger('buy_amount');
            $table->unsignedSmallInteger('max_trades')->default(1);
            $table->unsignedSmallInteger('trades_completed')->default(0);
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->timestamps();
        });

        // 13. Reports
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // attack, defense, support, trade, system
            $table->string('title');
            $table->json('data');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_read', 'created_at']);
        });

        // 14. Messages (In-game Mail)
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_deleted_by_sender')->default(false);
            $table->boolean('is_deleted_by_recipient')->default(false);
            $table->timestamps();

            $table->index(['recipient_id', 'is_read', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('market_offers');
        Schema::dropIfExists('market_transports');
        Schema::dropIfExists('movements');
        Schema::dropIfExists('recruitment_queues');
        Schema::dropIfExists('building_queues');
        Schema::dropIfExists('village_supports');
        Schema::dropIfExists('village_units');
        Schema::dropIfExists('villages');
        Schema::dropIfExists('tribe_forum_posts');
        Schema::dropIfExists('tribe_forum_threads');
        Schema::dropIfExists('tribe_diplomacies');
        Schema::dropIfExists('tribe_members');
        Schema::dropIfExists('tribes');
    }
};
