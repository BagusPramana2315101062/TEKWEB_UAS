<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Comment;
use App\Models\Product;

class AdminCommentsTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Comments feature disabled for this project.');
    }

    public function test_admin_can_view_and_delete_comment()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $product = Product::factory()->create();
        $comment = Comment::create(['user_id' => null, 'commentable_type' => Product::class, 'commentable_id' => $product->id, 'content' => 'Nice product']);

        $this->actingAs($admin)
            ->get(route('admin.comments.index'))
            ->assertStatus(200)
            ->assertSee('Nice product');

        $this->actingAs($admin)
            ->delete(route('admin.comments.destroy', $comment->id))
            ->assertRedirect(route('admin.comments.index'));

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_non_admin_cannot_access_comments()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $this->get(route('admin.comments.index'))
            ->assertStatus(403);
    }
}
