<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => 'password',
        ]);

        $board = Board::factory()->create([
            'user_id' => $user->id,
            'name' => 'My First Board',
            'description' => 'A sample Kanban board to get started',
        ]);

        Task::factory()->create([
            'board_id' => $board->id,
            'user_id' => $user->id,
            'title' => 'Setup project structure',
            'description' => 'Initialize the Laravel and Vue projects',
            'status' => 'done',
            'position' => 0,
        ]);

        Task::factory()->create([
            'board_id' => $board->id,
            'user_id' => $user->id,
            'title' => 'Implement authentication',
            'description' => 'Add JWT auth to the API',
            'status' => 'done',
            'position' => 1,
        ]);

        Task::factory()->create([
            'board_id' => $board->id,
            'user_id' => $user->id,
            'title' => 'Build task board UI',
            'description' => 'Create the Kanban board interface',
            'status' => 'in_progress',
            'position' => 2,
        ]);

        Task::factory()->create([
            'board_id' => $board->id,
            'user_id' => $user->id,
            'title' => 'Add real-time updates',
            'description' => 'Integrate WebSocket for live updates',
            'status' => 'todo',
            'position' => 3,
        ]);

        Task::factory()->create([
            'board_id' => $board->id,
            'user_id' => $user->id,
            'title' => 'Write documentation',
            'description' => 'Document API endpoints and setup instructions',
            'status' => 'todo',
            'position' => 4,
        ]);
    }
}