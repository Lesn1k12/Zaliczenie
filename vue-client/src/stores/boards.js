import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useBoardsStore = defineStore('boards', () => {
  const boards = ref([])
  const currentBoard = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetchBoards = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/boards')
      boards.value = response.data
      return response.data
    } catch (e) {
      error.value = e.response?.data?.error?.message || 'Failed to fetch boards'
      throw e
    } finally {
      loading.value = false
    }
  }

  const fetchBoard = async (id) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/boards/${id}`)
      currentBoard.value = response.data
      return response.data
    } catch (e) {
      error.value = e.response?.data?.error?.message || 'Failed to fetch board'
      throw e
    } finally {
      loading.value = false
    }
  }

  const createBoard = async (data) => {
    const response = await api.post('/boards', data)
    boards.value.push(response.data)
    return response.data
  }

  const updateBoard = async (id, data) => {
    const response = await api.patch(`/boards/${id}`, data)
    const index = boards.value.findIndex(b => b.id === id)
    if (index !== -1) {
      boards.value[index] = response.data
    }
    if (currentBoard.value?.id === id) {
      currentBoard.value = response.data
    }
    return response.data
  }

  const deleteBoard = async (id) => {
    await api.delete(`/boards/${id}`)
    boards.value = boards.value.filter(b => b.id !== id)
    if (currentBoard.value?.id === id) {
      currentBoard.value = null
    }
  }

  const createTask = async (boardId, data) => {
    // Optimistic update - add temporary task
    const tempId = `temp-${Date.now()}`
    const tempTask = { id: tempId, board_id: Number(boardId), ...data }

    if (currentBoard.value?.id == boardId) {
      currentBoard.value.tasks.push(tempTask)
    }

    try {
      const response = await api.post(`/boards/${boardId}/tasks`, data)
      // Replace temp task with real one
      if (currentBoard.value?.id == boardId) {
        const index = currentBoard.value.tasks.findIndex(t => t.id === tempId)
        if (index !== -1) {
          currentBoard.value.tasks[index] = response.data
        }
      }
      return response.data
    } catch (e) {
      // Remove temp task on error
      if (currentBoard.value?.id == boardId) {
        currentBoard.value.tasks = currentBoard.value.tasks.filter(t => t.id !== tempId)
      }
      throw e
    }
  }

  const updateTask = async (boardId, taskId, data) => {
    // Optimistic update - change locally first
    let oldTask = null
    if (currentBoard.value?.id == boardId) {
      const index = currentBoard.value.tasks.findIndex(t => t.id === taskId)
      if (index !== -1) {
        oldTask = { ...currentBoard.value.tasks[index] }
        currentBoard.value.tasks[index] = { ...oldTask, ...data }
      }
    }

    try {
      const response = await api.patch(`/boards/${boardId}/tasks/${taskId}`, data)
      // Update with server response (may contain additional fields)
      if (currentBoard.value?.id == boardId) {
        const index = currentBoard.value.tasks.findIndex(t => t.id === taskId)
        if (index !== -1) {
          currentBoard.value.tasks[index] = response.data
        }
      }
      return response.data
    } catch (e) {
      // Revert on error
      if (oldTask && currentBoard.value?.id == boardId) {
        const index = currentBoard.value.tasks.findIndex(t => t.id === taskId)
        if (index !== -1) {
          currentBoard.value.tasks[index] = oldTask
        }
      }
      throw e
    }
  }

  const deleteTask = async (boardId, taskId) => {
    // Optimistic update - remove immediately
    let deletedTask = null
    let deletedIndex = -1

    if (currentBoard.value?.id == boardId) {
      deletedIndex = currentBoard.value.tasks.findIndex(t => t.id === taskId)
      if (deletedIndex !== -1) {
        deletedTask = currentBoard.value.tasks[deletedIndex]
        currentBoard.value.tasks.splice(deletedIndex, 1)
      }
    }

    try {
      await api.delete(`/boards/${boardId}/tasks/${taskId}`)
    } catch (e) {
      // Restore task on error
      if (deletedTask && currentBoard.value?.id == boardId) {
        currentBoard.value.tasks.splice(deletedIndex, 0, deletedTask)
      }
      throw e
    }
  }

  const handleTaskCreated = (task) => {
    if (currentBoard.value?.id === task.board_id) {
      const exists = currentBoard.value.tasks.some(t => t.id === task.id)
      if (!exists) {
        currentBoard.value.tasks.push(task)
      }
    }
  }

  const handleTaskUpdated = (task) => {
    if (currentBoard.value?.id === task.board_id) {
      const index = currentBoard.value.tasks.findIndex(t => t.id === task.id)
      if (index !== -1) {
        currentBoard.value.tasks[index] = task
      }
    }
  }

  const handleTaskDeleted = (task) => {
    if (currentBoard.value?.id === task.board_id) {
      currentBoard.value.tasks = currentBoard.value.tasks.filter(t => t.id !== task.id)
    }
  }

  const exportBoard = async (boardId) => {
    const response = await api.post(`/boards/${boardId}/export`)
    return response.data
  }

  const getJobStatus = async (jobId) => {
    const response = await api.get(`/jobs/${jobId}`)
    return response.data
  }

  return {
    boards,
    currentBoard,
    loading,
    error,
    fetchBoards,
    fetchBoard,
    createBoard,
    updateBoard,
    deleteBoard,
    createTask,
    updateTask,
    deleteTask,
    handleTaskCreated,
    handleTaskUpdated,
    handleTaskDeleted,
    exportBoard,
    getJobStatus
  }
})
