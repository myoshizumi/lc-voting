<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Idea;
use App\Models\User;
use App\Models\Status;
use Livewire\Livewire;
use App\Models\Category;
use App\Http\Livewire\SetStatus;
use App\Jobs\NotifyAllVoters;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminSetStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @group test
     */
    public function show_page_contains_set_status_livewire_component_when_user_is_admin()
    {
        $user = User::factory()->create([
            'email' => 'ann@example.com',
        ]);

        $categoryOne = Category::factory()->create(['name' => 'Category 1']);
        $categoryTwo = Category::factory()->create(['name' => 'Category 1']);

        $statusOpen = Status::factory()->create(['name' => 'Open']);

        $idea = Idea::factory()->create([
            'user_id' => $user->id,
            'category_id' => $categoryOne->id,
            'status_id' => $statusOpen->id,
            'title' => 'My First Idea',
            'description' => 'Description for my first idea',
        ]);

        $this->actingAs($user)
            ->get(route('idea.show', $idea))
            ->assertSeeLivewire('set-status');
    }

    /**
     * @test
     */
    public function show_page_does_not_contains_set_status_livewire_component_when_user_is_not_admin()
    {
        $user = User::factory()->create([
            'email' => 'joe@example.com',
        ]);

        $categoryOne = Category::factory()->create(['name' => 'Category 1']);
        $categoryTwo = Category::factory()->create(['name' => 'Category 1']);

        $statusOpen = Status::factory()->create(['name' => 'Open']);

        $idea = Idea::factory()->create([
            'user_id' => $user->id,
            'category_id' => $categoryOne->id,
            'status_id' => $statusOpen->id,
            'title' => 'My First Idea',
            'description' => 'Description for my first idea',
        ]);

        $this->actingAs($user)
            ->get(route('idea.show', $idea))
            ->assertDontSeeLivewire('set-status');
    }

    /**
     * @test
     */
    public function initial_status_is_set_correctly()
    {
        $user = User::factory()->create([
            'email' => 'ann@example.com',
        ]);

        $categoryOne = Category::factory()->create(['name' => 'Category 1']);
        $categoryTwo = Category::factory()->create(['name' => 'Category 1']);

        $statusConsidering = Status::factory()->create(['id' => 2, 'name' => 'Considering']);

        $idea = Idea::factory()->create([
            'user_id' => $user->id,
            'category_id' => $categoryOne->id,
            'status_id' => $statusConsidering->id,
            'title' => 'My First Idea',
            'description' => 'Description for my first idea',
        ]);

        Livewire::actingAs($user)
            ->test(SetStatus::class, [
                'idea' => $idea,
            ])
            ->assertSet('status', $statusConsidering->id);
    }

    /**
     * @test
     * @group test
     */
    // public function can_set_status_correctly()
    // {
    //     $user = User::factory()->create([
    //         'email' => 'ann@example.com',
    //     ]);

    //     $categoryOne = Category::factory()->create(['name' => 'Category 1']);
    //     $categoryTwo = Category::factory()->create(['name' => 'Category 1']);

    //     $statusConsidering = Status::factory()->create(['id' => 2, 'name' => 'Considering']);
    //     $statusInProgress = Status::factory()->create(['id' => 3, 'name' => 'In Progress']);

    //     $idea = Idea::factory()->create([
    //         'user_id' => $user->id,
    //         'category_id' => $categoryOne->id,
    //         'status_id' => $statusConsidering->id,
    //         'title' => 'My First Idea',
    //         'description' => 'Description for my first idea',
    //     ]);

    //     Livewire::actingAs($user)
    //         ->test(SetStatus::class, [
    //             'idea' => $idea,
    //         ])
    //         ->set('status', $statusInProgress->id)
    //         ->call('setStatus')
    //         ->assertEmitted('statusWasUpdatted');

    //     $this->assertDatabaseHas('idea', [
    //         'id' => $idea->id,
    //         'status_id' => $statusInProgress->id,
    //     ]);
    // }

    /**
     * @test
     */
    public function can_set_status_correctly_while_notifying_all_voters()
    {
        $user = User::factory()->create([
            'email' => 'ann@example.com',
        ]);

        $categoryOne = Category::factory()->create(['name' => 'Category 1']);
        $categoryTwo = Category::factory()->create(['name' => 'Category 1']);

        $statusConsidering = Status::factory()->create(['id' => 2, 'name' => 'Considering']);
        $statusInProgress = Status::factory()->create(['id' => 3, 'name' => 'In Progress']);

        $idea = Idea::factory()->create([
            'user_id' => $user->id,
            'category_id' => $categoryOne->id,
            'status_id' => $statusConsidering->id,
            'title' => 'My First Idea',
            'description' => 'Description for my first idea',
        ]);

        Queue::fake();

        Queue::assertNothingPushed();

        Livewire::actingAs($user)
            ->test(SetStatus::class, [
                'idea' => $idea,
            ])
            ->set('status', $statusInProgress->id)
            ->set('notifyAllVoters', true)
            ->call('setStatus');
        // ->assertEmitted('statusWasUpdatted');

        Queue::assertPushed(NotifyAllVoters::class);
    }
}