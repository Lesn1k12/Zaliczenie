<template>
  <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900">My Boards</h1>
      <button
        @click="showCreateModal = true"
        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
      >
        New Board
      </button>
    </div>

    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-500">Loading boards...</p>
    </div>

    <div v-else-if="boards.length === 0" class="text-center py-12">
      <p class="text-gray-500 mb-4">No boards yet. Create your first board!</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <router-link
        v-for="board in boards"
        :key="board.id"
        :to="`/boards/${board.id}`"
        class="block bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow"
      >
        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ board.name }}</h3>
        <p class="text-gray-600 text-sm mb-4">{{ board.description || 'No description' }}</p>
        <p class="text-gray-400 text-xs">{{ board.tasks?.length || 0 }} tasks</p>
      </router-link>
    </div>

    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Create New Board</h2>
        <form @submit.prevent="createBoard">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input
              v-model="newBoard.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              v-model="newBoard.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            ></textarea>
          </div>
          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 text-gray-600 hover:text-gray-800"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
            >
              Create
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useBoardsStore } from '../stores/boards'

const boardsStore = useBoardsStore()

const boards = computed(() => boardsStore.boards)
const loading = computed(() => boardsStore.loading)

const showCreateModal = ref(false)
const newBoard = ref({ name: '', description: '' })

onMounted(() => {
  boardsStore.fetchBoards()
})

const createBoard = async () => {
  try {
    await boardsStore.createBoard(newBoard.value)
    showCreateModal.value = false
    newBoard.value = { name: '', description: '' }
  } catch (e) {
    console.error('Failed to create board:', e)
  }
}
</script>
