# Kanban Board - Distributed Application

## Overview

A full-stack distributed Kanban board application demonstrating hybrid communication patterns:
- **REST API** for CRUD operations
- **WebSockets** for real-time updates
- **Queue** for background job processing

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              CLIENTS                                         │
├─────────────────────────────────┬───────────────────────────────────────────┤
│         Vue SPA (Client A)      │           Python CLI (Client B)           │
│  ┌───────────────────────────┐  │     ┌───────────────────────────────┐     │
│  │  - Boards Management      │  │     │  - CLI Commands (Typer)       │     │
│  │  - Drag & Drop Tasks      │  │     │  - Rich Terminal Output       │     │
│  │  - Real-time Updates      │  │     │  - REST API Integration       │     │
│  │  - Export Progress        │  │     │  - Auth Token Management      │     │
│  └───────────────────────────┘  │     └───────────────────────────────┘     │
│              │                  │                    │                       │
│      Laravel Echo               │              HTTP Requests                 │
│      (WebSocket)                │                    │                       │
└──────────────┼──────────────────┴────────────────────┼───────────────────────┘
               │                                       │
               ▼                                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                              NGINX (Reverse Proxy)                           │
│                                   Port 8000                                  │
└──────────────────────────────────┬───────────────────────────────────────────┘
                                   │
                                   ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                           LARAVEL API (PHP-FPM)                              │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────────────────────┐  │
│  │  Controllers   │  │    Events      │  │         Jobs                   │  │
│  │  - Auth        │  │  - TaskCreated │  │  - ExportBoardJob              │  │
│  │  - Board       │  │  - TaskUpdated │  │    (CSV generation with        │  │
│  │  - Task        │  │  - TaskDeleted │  │     progress reporting)        │  │
│  │  - Export      │  │  - JobProgress │  │                                │  │
│  └────────────────┘  └────────────────┘  └────────────────────────────────┘  │
│              │                │                         │                    │
│              ▼                ▼                         ▼                    │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │                        Service Layer                                   │  │
│  │   JWT Auth  │  Eloquent ORM  │  Broadcasting  │  Queue Dispatcher      │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
└──────────────┬────────────────────┬────────────────────┬─────────────────────┘
               │                    │                    │
               ▼                    ▼                    ▼
┌──────────────────────┐ ┌──────────────────┐ ┌─────────────────────────────────┐
│       MySQL 8.0      │ │   Redis          │ │         Soketi                  │
│  ┌────────────────┐  │ │  ┌────────────┐  │ │  (Pusher-compatible WebSocket)  │
│  │  users         │  │ │  │  Queues    │  │ │                                 │
│  │  boards        │  │ │  │  Cache     │  │ │  - Private Channels            │
│  │  tasks         │  │ │  │  Sessions  │  │ │  - board.{id}                  │
│  │  export_jobs   │  │ │  └────────────┘  │ │  - user.{id}                   │
│  └────────────────┘  │ │                  │ │                                 │
└──────────────────────┘ └──────────────────┘ └─────────────────────────────────┘
                                   │
                                   ▼
                    ┌──────────────────────────────┐
                    │       Queue Worker           │
                    │  (Background Job Processing) │
                    │  - Processes ExportBoardJob  │
                    │  - Broadcasts progress       │
                    └──────────────────────────────┘
```

## Technology Stack

### Backend (Laravel 12 + PHP 8.4)
| Component | Technology |
|-----------|------------|
| Framework | Laravel 12 |
| PHP Version | 8.4 |
| Authentication | JWT (php-open-source-saver/jwt-auth) |
| Database ORM | Eloquent |
| Queue Driver | Redis |
| WebSocket Server | Soketi (Pusher-compatible) |
| Broadcasting | Laravel Broadcasting with Pusher driver |

### Frontend (Vue 3 SPA)
| Component | Technology |
|-----------|------------|
| Framework | Vue 3 (Composition API) |
| State Management | Pinia |
| Routing | Vue Router |
| HTTP Client | Axios |
| WebSocket Client | Laravel Echo + Pusher.js |
| Styling | Tailwind CSS |
| Build Tool | Vite |

### Python CLI Client
| Component | Technology |
|-----------|------------|
| CLI Framework | Typer |
| HTTP Client | Requests |
| Terminal UI | Rich |

### Infrastructure
| Component | Technology |
|-----------|------------|
| Containerization | Docker + Docker Compose |
| Web Server | Nginx |
| Database | MySQL 8.0 |
| Cache/Queue | Redis |
| WebSocket Server | Soketi |

## Project Structure

```
myapp/
├── app/
│   ├── Events/                    # WebSocket broadcast events
│   │   ├── TaskCreated.php
│   │   ├── TaskUpdated.php
│   │   ├── TaskDeleted.php
│   │   ├── JobStarted.php
│   │   ├── JobProgress.php
│   │   ├── JobCompleted.php
│   │   └── JobFailed.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php      # JWT authentication
│   │   │   ├── BoardController.php     # Board CRUD
│   │   │   ├── TaskController.php      # Task CRUD + broadcasting
│   │   │   └── ExportController.php    # Export job management
│   │   └── Requests/                   # Form validation
│   ├── Jobs/
│   │   └── ExportBoardJob.php          # Background CSV export
│   ├── Models/
│   │   ├── User.php
│   │   ├── Board.php
│   │   ├── Task.php
│   │   └── ExportJob.php
│   └── Providers/
│       └── BroadcastServiceProvider.php
├── config/
│   ├── auth.php                   # JWT guard configuration
│   └── broadcasting.php           # Pusher/Soketi configuration
├── database/
│   ├── migrations/
│   │   ├── create_boards_table.php
│   │   ├── create_tasks_table.php
│   │   └── create_export_jobs_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── routes/
│   ├── api.php                    # API routes
│   └── channels.php               # WebSocket channel authorization
├── vue-client/                    # Vue SPA
│   ├── src/
│   │   ├── components/
│   │   │   └── TaskColumn.vue     # Drag & drop task column
│   │   ├── views/
│   │   │   ├── Login.vue
│   │   │   ├── Register.vue
│   │   │   ├── Boards.vue
│   │   │   └── Board.vue          # Kanban board with WebSocket
│   │   ├── stores/
│   │   │   ├── auth.js            # Authentication state
│   │   │   └── boards.js          # Boards & tasks state
│   │   ├── services/
│   │   │   ├── api.js             # Axios instance
│   │   │   └── echo.js            # Laravel Echo configuration
│   │   ├── router/
│   │   │   └── index.js
│   │   ├── App.vue
│   │   └── main.js
│   ├── vite.config.js             # Vite + HMR configuration
│   └── package.json
├── python-client/                 # Python CLI
│   ├── kanban_cli.py
│   └── requirements.txt
├── docker/
│   └── nginx/
│       └── default.conf
├── docker-compose.yml
├── Dockerfile
└── README.md
```

## API Endpoints

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/auth/login` | Login and get JWT token |
| POST | `/api/auth/logout` | Logout (invalidate token) |
| POST | `/api/auth/refresh` | Refresh JWT token |
| GET | `/api/auth/me` | Get current user info |

### Boards
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/boards` | List user's boards |
| POST | `/api/boards` | Create new board |
| GET | `/api/boards/{id}` | Get board with tasks |
| PATCH | `/api/boards/{id}` | Update board |
| DELETE | `/api/boards/{id}` | Delete board |

### Tasks
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/boards/{board}/tasks` | List board's tasks |
| POST | `/api/boards/{board}/tasks` | Create task |
| GET | `/api/boards/{board}/tasks/{task}` | Get task |
| PATCH | `/api/boards/{board}/tasks/{task}` | Update task |
| DELETE | `/api/boards/{board}/tasks/{task}` | Delete task |

### Export
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/boards/{id}/export` | Start export job |
| GET | `/api/jobs/{id}` | Get job status |
| GET | `/api/jobs/{id}/download` | Download export file |

## WebSocket Channels

### Private Channels

**`board.{boardId}`** - Board-specific events
- `TaskCreated` - New task added
- `TaskUpdated` - Task modified (including status change)
- `TaskDeleted` - Task removed

**`user.{userId}`** - User-specific events
- `JobStarted` - Export job started
- `JobProgress` - Export progress update (percentage)
- `JobCompleted` - Export finished, download ready
- `JobFailed` - Export failed with error

## Data Models

### User
```
id, name, email, password, created_at, updated_at
```

### Board
```
id, user_id, name, description, created_at, updated_at
```

### Task
```
id, board_id, user_id, title, description, status, position, created_at, updated_at
```
- `status`: enum('todo', 'in_progress', 'done')

### ExportJob
```
id, user_id, board_id, status, file_path, error_message, created_at, updated_at
```
- `status`: enum('pending', 'processing', 'completed', 'failed')

## Key Features

### 1. Optimistic UI Updates
Tasks update immediately in the UI before API response, with automatic rollback on error:
```javascript
const updateTask = async (boardId, taskId, data) => {
  // Update locally first
  const oldTask = { ...currentBoard.value.tasks[index] }
  currentBoard.value.tasks[index] = { ...oldTask, ...data }

  try {
    await api.patch(`/boards/${boardId}/tasks/${taskId}`, data)
  } catch (e) {
    // Revert on error
    currentBoard.value.tasks[index] = oldTask
    throw e
  }
}
```

### 2. Drag and Drop
Native HTML5 drag and drop for moving tasks between columns:
- Visual feedback during drag (opacity, highlighting)
- Drop zone detection
- Automatic status update on drop

### 3. Real-time Collaboration
Multiple users can work on the same board with instant updates:
```php
// Backend broadcasts task changes
broadcast(new TaskUpdated($task))->toOthers();
```
```javascript
// Frontend listens for changes
echoChannel.listen('.TaskUpdated', (e) => {
  boardsStore.handleTaskUpdated(e.task)
})
```

### 4. Background Job Processing
Export jobs run asynchronously with progress reporting:
```php
class ExportBoardJob implements ShouldQueue
{
    public function handle(): void
    {
        // Update progress and broadcast
        broadcast(new JobProgress($this->exportJob, $progress));

        // Generate CSV
        // ...

        broadcast(new JobCompleted($this->exportJob));
    }
}
```

### 5. JWT Authentication
Stateless authentication for API:
- Token in Authorization header: `Bearer {token}`
- Refresh mechanism for long sessions
- Secure password hashing with bcrypt

## Running the Application

### Prerequisites
- Docker & Docker Compose

### Quick Start
```bash
# Start all services
docker-compose up -d

# Wait for setup to complete (migrations, seeding)
docker-compose logs -f setup

# Access the application
# Frontend: http://localhost:5173
# API: http://localhost:8000/api
```

### Services
| Service | Port | Description |
|---------|------|-------------|
| frontend | 5173 | Vue SPA (Vite dev server) |
| nginx | 8000 | API gateway |
| api | 9000 | PHP-FPM (internal) |
| mysql | 3306 | Database |
| redis | 6379 | Cache & Queue |
| soketi | 6001 | WebSocket server |

### Python CLI Usage
```bash
cd python-client
pip install -r requirements.txt

# Register
python kanban_cli.py register --name "John" --email "john@example.com" --password "password123"

# Login
python kanban_cli.py login --email "john@example.com" --password "password123"

# List boards
python kanban_cli.py boards list

# Create board
python kanban_cli.py boards create --name "My Board"

# List tasks
python kanban_cli.py tasks list --board-id 1

# Create task
python kanban_cli.py tasks create --board-id 1 --title "New Task" --status todo

# Update task status
python kanban_cli.py tasks update --board-id 1 --task-id 1 --status in_progress
```

## Error Handling

### API Error Responses
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "email": ["The email field is required."]
    }
  }
}
```

### Error Codes
| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VALIDATION_ERROR` | 422 | Input validation failed |
| `USER_EXISTS` | 422 | Email already registered |
| `UNAUTHENTICATED` | 401 | Invalid or missing token |
| `FORBIDDEN` | 403 | Access denied |
| `NOT_FOUND` | 404 | Resource not found |
| `SERVER_ERROR` | 500 | Internal server error |

## Development

### Hot Module Replacement (HMR)
Vue frontend supports HMR in Docker:
```javascript
// vite.config.js
server: {
  watch: { usePolling: true },
  hmr: { host: 'localhost', port: 5173 }
}
```

### Queue Worker
Background jobs are processed by the queue worker container:
```bash
php artisan queue:work --tries=3
```

### Logs
```bash
# API logs
docker-compose logs -f api

# Queue worker logs
docker-compose logs -f queue

# All logs
docker-compose logs -f
```
