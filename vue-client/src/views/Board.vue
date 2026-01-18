<template>
  <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-500">Loading board...</p>
    </div>

    <template v-else-if="board">
      <div class="flex justify-between items-center mb-6">
        <div>
          <router-link to="/" class="text-indigo-600 hover:text-indigo-800 text-sm">&larr; Back to boards</router-link>
          <h1 class="text-2xl font-bold text-gray-900">{{ board.name }}</h1>
          <p class="text-gray-600">{{ board.description }}</p>
        </div>
        <div class="flex space-x-2">
          <button
            @click="handleExport"
            :disabled="exporting"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
          >
            {{ exporting ? `Exporting ${exportProgress}%` : 'Export CSV' }}
          </button>
          <button
            @click="showTaskModal = true"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
          >
            Add Task
          </button>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-6">
        <TaskColumn
          title="To Do"
          status="todo"
          :tasks="todoTasks"
          @edit="editTask"
          @delete="deleteTask"
          @status-change="updateTaskStatus"
        />
        <TaskColumn
          title="In Progress"
          status="in_progress"
          :tasks="inProgressTasks"
          @edit="editTask"
          @delete="deleteTask"
          @status-change="updateTaskStatus"
        />
        <TaskColumn
          title="Done"
          status="done"
          :tasks="doneTasks"
          @edit="editTask"
          @delete="deleteTask"
          @status-change="updateTaskStatus"
        />
      </div>
    </template>

    <div v-if="showTaskModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">{{ editingTask ? 'Edit Task' : 'Add New Task' }}</h2>
        <form @submit.prevent="saveTask">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
            <input
              v-model="taskForm.title"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              v-model="taskForm.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            ></textarea>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              v-model="taskForm.status"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="todo">To Do</option>
              <option value="in_progress">In Progress</option>
              <option value="done">Done</option>
            </select>
          </div>
          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="closeTaskModal"
              class="px-4 py-2 text-gray-600 hover:text-gray-800"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
            >
              {{ editingTask ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, provide } from 'vue'
import { useRoute } from 'vue-router'
import { useBoardsStore } from '../stores/boards'
import { useAuthStore } from '../stores/auth'
import { initEcho, getEcho, disconnectEcho } from '../services/echo'
import TaskColumn from '../components/TaskColumn.vue'

const route = useRoute()
const boardsStore = useBoardsStore()
const authStore = useAuthStore()

// Shared drag state across columns
const draggingTaskId = ref(null)
provide('draggingTaskId', draggingTaskId)

const board = computed(() => boardsStore.currentBoard)
const loading = computed(() => boardsStore.loading)

const todoTasks = computed(() => board.value?.tasks?.filter(t => t.status === 'todo') || [])
const inProgressTasks = computed(() => board.value?.tasks?.filter(t => t.status === 'in_progress') || [])
const doneTasks = computed(() => board.value?.tasks?.filter(t => t.status === 'done') || [])

const showTaskModal = ref(false)
const editingTask = ref(null)
const taskForm = ref({ title: '', description: '', status: 'todo' })

const exporting = ref(false)
const exportProgress = ref(0)

let echoChannel = null

onMounted(async () => {
  await boardsStore.fetchBoard(route.params.id)
  setupWebSocket()
})

onUnmounted(() => {
  if (echoChannel) {
    echoChannel.stopListening('.TaskCreated')
    echoChannel.stopListening('.TaskUpdated')
    echoChannel.stopListening('.TaskDeleted')
  }
})

const setupWebSocket = () => {
  const echo = initEcho()
  echoChannel = echo.private(`board.${route.params.id}`)

  echoChannel
    .listen('.TaskCreated', (e) => {
      boardsStore.handleTaskCreated(e.task)
    })
    .listen('.TaskUpdated', (e) => {
      boardsStore.handleTaskUpdated(e.task)
    })
    .listen('.TaskDeleted', (e) => {
      boardsStore.handleTaskDeleted(e.task)
    })

  const userChannel = echo.private(`user.${authStore.user?.id}`)
  userChannel
    .listen('.JobStarted', (e) => {
      if (e.board_id == route.params.id) {
        exporting.value = true
        exportProgress.value = 0
      }
    })
    .listen('.JobProgress', (e) => {
      if (e.board_id == route.params.id) {
        exportProgress.value = e.progress
      }
    })
    .listen('.JobCompleted', (e) => {
      if (e.board_id == route.params.id) {
        exporting.value = false
        exportProgress.value = 100
        alert('Export completed! Download will start shortly.')
        window.open(`/api/jobs/${e.job_id}/download`, '_blank')
      }
    })
    .listen('.JobFailed', (e) => {
      if (e.board_id == route.params.id) {
        exporting.value = false
        alert('Export failed: ' + e.error_message)
      }
    })
}

const editTask = (task) => {
  editingTask.value = task
  taskForm.value = { ...task }
  showTaskModal.value = true
}

const deleteTask = async (task) => {
  if (confirm('Are you sure you want to delete this task?')) {
    await boardsStore.deleteTask(route.params.id, task.id)
  }
}

const updateTaskStatus = async (task, newStatus) => {
  await boardsStore.updateTask(route.params.id, task.id, { status: newStatus })
}

const closeTaskModal = () => {
  showTaskModal.value = false
  editingTask.value = null
  taskForm.value = { title: '', description: '', status: 'todo' }
}

const saveTask = async () => {
  if (editingTask.value) {
    await boardsStore.updateTask(route.params.id, editingTask.value.id, taskForm.value)
  } else {
    await boardsStore.createTask(route.params.id, taskForm.value)
  }
  closeTaskModal()
}

const handleExport = async () => {
  exporting.value = true
  exportProgress.value = 0
  try {
    await boardsStore.exportBoard(route.params.id)
  } catch (e) {
    exporting.value = false
    alert('Failed to start export')
  }
}
</script>
