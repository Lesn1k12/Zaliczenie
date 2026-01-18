<template>
  <div
    class="bg-gray-200 rounded-lg p-4 min-h-[400px] transition-colors"
    :class="{ 'bg-indigo-100 ring-2 ring-indigo-400': isDragOver }"
    @dragover.prevent="onDragOver"
    @dragleave="onDragLeave"
    @drop="onDrop"
  >
    <h2 class="text-lg font-semibold text-gray-700 mb-4">{{ title }} ({{ tasks.length }})</h2>

    <div class="space-y-3">
      <div
        v-for="task in tasks"
        :key="task.id"
        class="bg-white rounded-lg shadow p-4 cursor-grab active:cursor-grabbing transition-all"
        :class="{ 'opacity-50 scale-95': draggingTaskId === task.id }"
        draggable="true"
        @dragstart="onDragStart($event, task)"
        @dragend="onDragEnd"
      >
        <h3 class="font-medium text-gray-900 mb-2">{{ task.title }}</h3>
        <p v-if="task.description" class="text-sm text-gray-600 mb-3">{{ task.description }}</p>

        <div class="flex justify-between items-center">
          <div class="flex space-x-1">
            <button
              v-if="status !== 'todo'"
              @click="$emit('status-change', task, getPrevStatus(status))"
              class="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200"
            >
              &larr;
            </button>
            <button
              v-if="status !== 'done'"
              @click="$emit('status-change', task, getNextStatus(status))"
              class="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200"
            >
              &rarr;
            </button>
          </div>

          <div class="flex space-x-2">
            <button
              @click="$emit('edit', task)"
              class="text-xs text-indigo-600 hover:text-indigo-800"
            >
              Edit
            </button>
            <button
              @click="$emit('delete', task)"
              class="text-xs text-red-600 hover:text-red-800"
            >
              Delete
            </button>
          </div>
        </div>
      </div>

      <div v-if="tasks.length === 0" class="text-center py-8 text-gray-400 text-sm border-2 border-dashed border-gray-300 rounded-lg">
        No tasks
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject } from 'vue'

const props = defineProps({
  title: String,
  status: String,
  tasks: Array
})

const emit = defineEmits(['edit', 'delete', 'status-change', 'drop'])

const isDragOver = ref(false)
const draggingTaskId = inject('draggingTaskId', ref(null))

const onDragStart = (event, task) => {
  draggingTaskId.value = task.id
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('application/json', JSON.stringify(task))
}

const onDragEnd = () => {
  draggingTaskId.value = null
}

const onDragOver = (event) => {
  event.dataTransfer.dropEffect = 'move'
  isDragOver.value = true
}

const onDragLeave = () => {
  isDragOver.value = false
}

const onDrop = (event) => {
  isDragOver.value = false
  const taskData = event.dataTransfer.getData('application/json')
  if (taskData) {
    const task = JSON.parse(taskData)
    if (task.status !== props.status) {
      emit('status-change', task, props.status)
    }
  }
}

const getNextStatus = (current) => {
  const statuses = ['todo', 'in_progress', 'done']
  const index = statuses.indexOf(current)
  return statuses[index + 1]
}

const getPrevStatus = (current) => {
  const statuses = ['todo', 'in_progress', 'done']
  const index = statuses.indexOf(current)
  return statuses[index - 1]
}
</script>
