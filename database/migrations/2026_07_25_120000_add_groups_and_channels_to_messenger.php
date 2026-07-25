<?php

use App\Database\Schema\MigrationColumnHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the existing Messenger conversations into Groups & Channels.
 * Idempotent: skips columns/tables that already exist from earlier work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('conversations', 'title')) {
                $table->string('title', 128)->nullable()->after('type');
            }
            if (! Schema::hasColumn('conversations', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('conversations', 'avatar')) {
                $table->string('avatar', 512)->nullable();
            }
            if (! Schema::hasColumn('conversations', 'cover')) {
                $table->string('cover', 512)->nullable();
            }
            if (! Schema::hasColumn('conversations', 'username')) {
                $table->string('username', 64)->nullable();
            }
            if (! Schema::hasColumn('conversations', 'is_public')) {
                $table->boolean('is_public')->default(false);
            }
            if (! Schema::hasColumn('conversations', 'owner_id') && ! Schema::hasColumn('conversations', 'created_by')) {
                $table->unsignedBigInteger('owner_id')->nullable();
            }
            if (! Schema::hasColumn('conversations', 'member_count')) {
                $table->unsignedInteger('member_count')->default(0);
            }
            if (! Schema::hasColumn('conversations', 'message_count')) {
                $table->unsignedInteger('message_count')->default(0);
            }
            if (! Schema::hasColumn('conversations', 'is_verified')) {
                $table->boolean('is_verified')->default(false);
            }
            if (! Schema::hasColumn('conversations', 'is_archived')) {
                $table->boolean('is_archived')->default(false);
            }
            if (! Schema::hasColumn('conversations', 'slow_mode_seconds')) {
                $table->unsignedInteger('slow_mode_seconds')->default(0);
            }
            if (! Schema::hasColumn('conversations', 'history_visible')) {
                $table->boolean('history_visible')->default(true);
            }
            if (! Schema::hasColumn('conversations', 'join_approval_required')) {
                $table->boolean('join_approval_required')->default(false);
            }
            if (! Schema::hasColumn('conversations', 'reactions_enabled')) {
                $table->boolean('reactions_enabled')->default(true);
            }
            if (! Schema::hasColumn('conversations', 'comments_enabled')) {
                $table->boolean('comments_enabled')->default(false);
            }
            if (! Schema::hasColumn('conversations', 'who_can_send')) {
                $table->string('who_can_send', 20)->default('all');
            }
            if (! Schema::hasColumn('conversations', 'who_can_invite')) {
                $table->string('who_can_invite', 20)->default('admins');
            }
            if (! Schema::hasColumn('conversations', 'who_can_pin')) {
                $table->string('who_can_pin', 20)->default('admins');
            }
            if (! Schema::hasColumn('conversations', 'who_can_edit_info')) {
                $table->string('who_can_edit_info', 20)->default('admins');
            }
        });

        // Prefer owner_id; if only created_by exists, add owner_id and backfill.
        if (Schema::hasColumn('conversations', 'created_by') && ! Schema::hasColumn('conversations', 'owner_id')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unsignedBigInteger('owner_id')->nullable();
            });
            DB::table('conversations')->whereNotNull('created_by')->update([
                'owner_id' => DB::raw('created_by'),
            ]);
        }

        if (Schema::hasColumn('conversations', 'owner_id')) {
            $this->safeForeign('conversations', 'owner_id', 'users', 'conversations_owner_id_foreign');
            $this->safeIndex('conversations', 'owner_id', 'conversations_owner_id_index');
        }

        if (Schema::hasColumn('conversations', 'username')) {
            $this->safeUnique('conversations', 'username', 'conversations_username_unique');
        }
        $this->safeIndex('conversations', ['type', 'is_public', 'is_archived'], 'conversations_type_public_archived_index');
        $this->safeIndex('conversations', ['type', 'title'], 'conversations_type_title_index');

        Schema::table('conversation_user', function (Blueprint $table) {
            if (! Schema::hasColumn('conversation_user', 'role')) {
                $table->string('role', 20)->default('member');
            }
            if (! Schema::hasColumn('conversation_user', 'nickname')) {
                $table->string('nickname', 64)->nullable();
            }
            if (! Schema::hasColumn('conversation_user', 'custom_title')) {
                $table->string('custom_title', 64)->nullable();
            }
            if (! Schema::hasColumn('conversation_user', 'last_read_message_id')) {
                $table->unsignedBigInteger('last_read_message_id')->nullable();
            }
            if (! Schema::hasColumn('conversation_user', 'muted_until')) {
                $table->timestamp('muted_until')->nullable();
            }
            if (! Schema::hasColumn('conversation_user', 'is_banned')) {
                $table->boolean('is_banned')->default(false);
            }
            if (! Schema::hasColumn('conversation_user', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::hasColumn('conversation_user', 'joined_at')) {
                $table->timestamp('joined_at')->nullable();
            }
            if (! Schema::hasColumn('conversation_user', 'notification_mode')) {
                $table->string('notification_mode', 20)->default('all');
            }
            if (! Schema::hasColumn('conversation_user', 'message_count')) {
                $table->unsignedInteger('message_count')->default(0);
            }
            if (! Schema::hasColumn('conversation_user', 'badges')) {
                MigrationColumnHelpers::jsonColumn($table, 'badges', nullable: true);
            }
        });

        $this->safeIndex('conversation_user', ['conversation_id', 'role'], 'conversation_user_conv_role_index');
        $this->safeIndex('conversation_user', ['conversation_id', 'is_active', 'is_banned'], 'conversation_user_active_banned_index');
        $this->safeIndex('conversation_user', ['user_id', 'deleted_at'], 'conversation_user_user_deleted_index');

        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'is_silent')) {
                $table->boolean('is_silent')->default(false);
            }
            if (! Schema::hasColumn('messages', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable();
            }
            if (! Schema::hasColumn('messages', 'auto_delete_at')) {
                $table->timestamp('auto_delete_at')->nullable();
            }
            if (! Schema::hasColumn('messages', 'view_count')) {
                $table->unsignedInteger('view_count')->default(0);
            }
            if (! Schema::hasColumn('messages', 'mentions')) {
                MigrationColumnHelpers::jsonColumn($table, 'mentions', nullable: true);
            }
            if (! Schema::hasColumn('messages', 'meta')) {
                MigrationColumnHelpers::jsonColumn($table, 'meta', nullable: true);
            }
        });

        $this->safeIndex('messages', ['conversation_id', 'scheduled_at'], 'messages_conversation_scheduled_index');
        $this->safeIndex('messages', ['conversation_id', 'type'], 'messages_conversation_type_index');

        if (! Schema::hasTable('conversation_role_permissions')) {
            Schema::create('conversation_role_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->string('role', 20);
                $table->string('permission', 64);
                $table->boolean('allowed')->default(true);
                $table->timestamps();

                $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
                $table->unique(['conversation_id', 'role', 'permission'], 'conv_role_perm_unique');
                $table->index(['conversation_id', 'role'], 'conv_role_perm_role_index');
            });
        }

        if (! Schema::hasTable('conversation_invites')) {
            Schema::create('conversation_invites', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('created_by');
                $table->string('code', 64);
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('uses_count')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_temporary')->default(false);
                $table->boolean('is_revoked')->default(false);
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->unique('code', 'conversation_invites_code_unique');
                $table->index(['conversation_id', 'is_revoked'], 'conversation_invites_active_index');
            });
        }

        if (! Schema::hasTable('conversation_join_requests')) {
            Schema::create('conversation_join_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('user_id');
                $table->string('status', 20)->default('pending');
                $table->text('message')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_note')->nullable();
                $table->timestamps();

                $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['conversation_id', 'status'], 'conv_join_req_status_index');
                $table->index(['conversation_id', 'user_id', 'status'], 'conv_join_req_user_status_index');
            });
        }

        if (! Schema::hasTable('conversation_bans')) {
            Schema::create('conversation_bans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('banned_by');
                $table->text('reason')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('unbanned_at')->nullable();
                $table->unsignedBigInteger('unbanned_by')->nullable();
                $table->timestamps();

                $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('banned_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('unbanned_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['conversation_id', 'user_id', 'unbanned_at'], 'conv_bans_active_index');
                $table->index(['conversation_id', 'expires_at'], 'conv_bans_expires_index');
            });
        }

        if (! Schema::hasTable('conversation_audit_logs')) {
            Schema::create('conversation_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('action', 64);
                $table->unsignedBigInteger('target_user_id')->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('target_type', 64)->nullable();
                $table->text('reason')->nullable();
                MigrationColumnHelpers::jsonColumn($table, 'meta', nullable: true);
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
                $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('target_user_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['conversation_id', 'created_at'], 'conv_audit_conv_created_index');
                $table->index(['conversation_id', 'action'], 'conv_audit_action_index');
            });
        }

        if (! Schema::hasTable('message_reactions')) {
            Schema::create('message_reactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('message_id');
                $table->unsignedBigInteger('user_id');
                $table->string('emoji', 32);
                $table->timestamps();

                $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['message_id', 'user_id', 'emoji'], 'message_reactions_unique');
                $table->index(['message_id', 'emoji'], 'message_reactions_emoji_index');
            });
        }

        if (! Schema::hasTable('message_views')) {
            Schema::create('message_views', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('message_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamp('viewed_at')->useCurrent();

                $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['message_id', 'user_id'], 'message_views_unique');
                $table->index('message_id', 'message_views_message_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('message_views');
        Schema::dropIfExists('message_reactions');
        Schema::dropIfExists('conversation_audit_logs');
        Schema::dropIfExists('conversation_bans');
        Schema::dropIfExists('conversation_join_requests');
        Schema::dropIfExists('conversation_invites');
        Schema::dropIfExists('conversation_role_permissions');

        // Intentionally leave additive columns on conversations/conversation_user/messages
        // to avoid data loss if earlier schema already had title/role/etc.
    }

    protected function safeIndex(string $table, $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
                $blueprint->index($columns, $name);
            });
        } catch (\Throwable $e) {
            // Index may already exist.
        }
    }

    protected function safeUnique(string $table, $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
                $blueprint->unique($columns, $name);
            });
        } catch (\Throwable $e) {
            // Unique may already exist.
        }
    }

    protected function safeForeign(string $table, string $column, string $refTable, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $refTable, $name) {
                $blueprint->foreign($column, $name)->references('id')->on($refTable)->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // Foreign key may already exist.
        }
    }
};
