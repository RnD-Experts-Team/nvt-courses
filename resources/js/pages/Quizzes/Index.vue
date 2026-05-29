<script setup>
import { Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Pagination from '@/Components/Pagination.vue'
import AppLayout from "@/layouts/AppLayout.vue"
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    FileText,
    CheckCircle,
    Clock,
    Search,
    List,
    Grid3X3,
    Brain,
    BarChart3,
    AlertTriangle,
    Calendar
} from 'lucide-vue-next'

const props = defineProps({
    quizzes: {
        type: Object,
        required: true,
    },
})

const searchQuery = ref('')
const selectedCourse = ref('all')
const viewMode = ref('grid')

const breadcrumbs = [
    { name: 'Quizzes', route: null },
]

// EXISTING: Computed properties
const uniqueCourses = computed(() => {
    if (!props.quizzes.data || !props.quizzes.data.length) return []
    const courses = props.quizzes.data.map(quiz => quiz.course)
    return courses.filter((course, index, self) =>
        index === self.findIndex(c => c.id === course.id)
    )
})

const filteredQuizzes = computed(() => {
    if (!props.quizzes.data || !props.quizzes.data.length) return []

    let filtered = props.quizzes.data

    if (searchQuery.value) {
        filtered = filtered.filter(quiz =>
            quiz.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (quiz.description && quiz.description.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            quiz.course.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        )
    }

    if (selectedCourse.value && selectedCourse.value !== 'all') {
        filtered = filtered.filter(quiz => quiz.course.id.toString() === selectedCourse.value)
    }

    return filtered
})

const completedQuizzes = computed(() => {
    if (!props.quizzes.data || !props.quizzes.data.length) return 0
    return props.quizzes.data.filter(quiz =>
        quiz.has_passed || hasReachedMaxAttempts(quiz)
    ).length
})

const pendingQuizzes = computed(() => {
    if (!props.quizzes.data || !props.quizzes.data.length) return 0
    return props.quizzes.data.filter(quiz =>
        !quiz.has_passed && !hasReachedMaxAttempts(quiz)
    ).length
})

const hasReachedMaxAttempts = (quiz) => {
    if (!quiz.max_attempts) {
        return false
    }

    return quiz.attempts >= quiz.max_attempts
}

const getAttemptsLabel = (quiz) => {
    if (!quiz.max_attempts) {
        return `${quiz.attempts} attempts (unlimited)`
    }

    return `${quiz.attempts}/${quiz.max_attempts} attempts`
}

const getAttemptProgress = (quiz) => {
    if (quiz.has_passed) {
        return 100
    }

    if (!quiz.max_attempts) {
        return 0
    }

    return Math.min((quiz.attempts / quiz.max_attempts) * 100, 100)
}

// EXISTING: Methods
const toggleView = () => {
    viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid'
}

const clearFilters = () => {
    searchQuery.value = ''
    selectedCourse.value = 'all'
}

const canTakeQuiz = (quiz) => {
    if (quiz.has_passed) {
        return false
    }
    // NEW: Check if quiz is still available (deadline check)
    if (quiz.has_deadline && !quiz.is_available) {
        return false
    }
    return !hasReachedMaxAttempts(quiz)
}

const isQuizCompleted = (quiz) => {
    return quiz.has_passed || hasReachedMaxAttempts(quiz)
}

const getQuizStatus = (quiz) => {
    if (quiz.has_passed) return 'Passed'
    if (hasReachedMaxAttempts(quiz)) return 'Failed'
    if (quiz.attempts > 0) return 'In Progress'
    return 'Not Started'
}

const getQuizStatusColorClass = (quiz) => {
    if (quiz.has_passed) return 'bg-green-500'
    if (hasReachedMaxAttempts(quiz)) return 'bg-red-500'
    if (quiz.attempts > 0) return 'bg-yellow-500'
    return 'bg-blue-500'
}

const getQuizStatusVariant = (quiz) => {
    if (quiz.has_passed) return 'default'
    if (hasReachedMaxAttempts(quiz)) return 'destructive'
    if (quiz.attempts > 0) return 'secondary'
    return 'outline'
}

const getActiveButtonText = (quiz) => {
    if (quiz.attempts > 0) return 'Continue Quiz'
    return 'Start Quiz'
}

// NEW: Deadline related methods
const getDeadlineStatus = (quiz) => {
    if (!quiz.has_deadline) return null

    if (quiz.deadline_status?.status === 'urgent') return 'urgent'
    if (quiz.deadline_status?.status === 'soon') return 'warning'
    if (quiz.deadline_status?.status === 'expired') return 'expired'
    return 'normal'
}

const getDeadlineIcon = (quiz) => {
    if (!quiz.has_deadline) return null

    const status = getDeadlineStatus(quiz)
    switch (status) {
        case 'urgent':
            return '🚨'
        case 'warning':
            return '⚠️'
        case 'expired':
            return '❌'
        default:
            return '📅'
    }
}

const getDeadlineClass = (quiz) => {
    const status = getDeadlineStatus(quiz)
    switch (status) {
        case 'urgent':
            return 'text-red-600 bg-red-50 border-red-200'
        case 'warning':
            return 'text-orange-600 bg-orange-50 border-orange-200'
        case 'expired':
            return 'text-gray-600 bg-gray-50 border-gray-200'
        default:
            return 'text-blue-600 bg-blue-50 border-blue-200'
    }
}

const getCourseTypeBadge = (quiz) => {
    return quiz.course_type === 'online' ? 'Online' : 'Regular'
}

const getCourseTypeClass = (quiz) => {
    return quiz.course_type === 'online'
        ? 'bg-green-100 text-green-800 border-green-200'
        : 'bg-blue-100 text-blue-800 border-blue-200'
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-background to-secondary/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <!-- Header Section -->
                <div class="text-center lg:text-left">
                    <h1 class="text-4xl font-bold text-foreground mb-2">Available Quizzes</h1>
                    <p class="text-lg text-muted-foreground">Test your knowledge and track your progress</p>
                </div>

                <!-- Stats Overview -->
                <div v-if="quizzes.data && quizzes.data.length" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-primary/10 text-primary">
                                    <FileText class="w-6 h-6" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted-foreground">Total Quizzes</p>
                                    <p class="text-2xl font-bold text-foreground">{{ quizzes.data.length }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-500/10 text-green-600">
                                    <CheckCircle class="w-6 h-6" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted-foreground">Completed</p>
                                    <p class="text-2xl font-bold text-foreground">{{ completedQuizzes }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-500/10 text-yellow-600">
                                    <Clock class="w-6 h-6" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted-foreground">Pending</p>
                                    <p class="text-2xl font-bold text-foreground">{{ pendingQuizzes }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Filters and Search -->
                <Card v-if="quizzes.data && quizzes.data.length">
                    <CardContent class="p-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="relative">
                                    <Search
                                        class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search quizzes..."
                                        class="w-full sm:w-64 pl-9"
                                    />
                                </div>

                                <Select v-model="selectedCourse">
                                    <SelectTrigger class="w-full sm:w-48">
                                        <SelectValue placeholder="All Courses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Courses</SelectItem>
                                        <SelectItem v-for="course in uniqueCourses" :key="course.id"
                                                    :value="course.id.toString()">
                                            {{ course.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="flex items-center gap-2">
                                <Button
                                    @click="toggleView"
                                    variant="ghost"
                                    size="sm"
                                    :title="viewMode === 'grid' ? 'Switch to list view' : 'Switch to grid view'"
                                >
                                    <List v-if="viewMode === 'grid'" class="w-5 h-5" />
                                    <Grid3X3 v-else class="w-5 h-5" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Quiz Cards -->
                <div v-if="filteredQuizzes.length" class="space-y-6">
                    <div
                        :class="viewMode === 'grid' ? 'grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6' : 'space-y-4'">
                        <Card
                            v-for="quiz in filteredQuizzes"
                            :key="quiz.id"
                            class="group hover:shadow-lg transition-all duration-200 overflow-hidden"
                        >
                            <!-- Quiz Status Indicator -->
                            <div class="flex">
                                <div :class="[
                                    'w-1 shrink-0',
                                    getQuizStatusColorClass(quiz)
                                ]"></div>

                                <CardContent class="flex-1 p-6">
                                    <!-- Quiz Header -->
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1 pr-4">
                                            <div class="flex items-center gap-2 mb-2">
                                                <h2 class="text-xl font-semibold text-foreground">
                                                    {{ quiz.title }}
                                                </h2>
                                                <!-- NEW: Course Type Badge -->
                                                <Badge
                                                    :class="getCourseTypeClass(quiz)"
                                                    class="text-xs px-2 py-1"
                                                >
                                                    {{ getCourseTypeBadge(quiz) }}
                                                </Badge>
                                            </div>
                                            <p class="text-sm text-muted-foreground mb-3 line-clamp-3">
                                                {{ quiz.description || 'No description available' }}
                                            </p>
                                        </div>
                                        <Badge :variant="getQuizStatusVariant(quiz)" class="shrink-0">
                                            {{ getQuizStatus(quiz) }}
                                        </Badge>
                                    </div>

                                    <!-- NEW: Deadline Alert (if applicable) -->
                                    <div
                                        v-if="quiz.has_deadline"
                                        :class="[
                                            'mb-4 p-3 rounded-lg border text-sm',
                                            getDeadlineClass(quiz)
                                        ]"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">{{ getDeadlineIcon(quiz) }}</span>
                                            <div class="flex-1">
                                                <div class="font-medium">
                                                    {{ quiz.deadline_status?.message || 'Deadline Information' }}
                                                </div>
                                                <div class="text-xs opacity-80 mt-1">
                                                    Due: {{ quiz.deadline_formatted }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Quiz Metrics -->
                                    <div class="grid grid-cols-2 gap-4 mb-4">
                                        <Card class="bg-muted/50">
                                            <CardContent class="p-3">
                                                <p class="text-xs text-muted-foreground mb-1">Course</p>
                                                <p class="text-sm font-medium text-foreground">{{ quiz.course.name
                                                    }}</p>
                                            </CardContent>
                                        </Card>
                                        <Card class="bg-muted/50">
                                            <CardContent class="p-3">
                                                <p class="text-xs text-muted-foreground mb-1">Total Points</p>
                                                <p class="text-sm font-medium text-foreground">{{ quiz.total_points
                                                    }}</p>
                                            </CardContent>
                                        </Card>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="mb-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-xs text-muted-foreground">Progress</span>
                                            <span class="text-xs text-foreground">{{ getAttemptsLabel(quiz) }}</span>
                                        </div>
                                        <div class="w-full bg-secondary rounded-full h-2">
                                            <div
                                                :class="[
                                                    'h-2 rounded-full transition-all duration-300',
                                                    quiz.has_passed ? 'bg-green-500' : 'bg-gradient-to-r from-primary to-purple-600'
                                                ]"
                                                :style="{ width: `${getAttemptProgress(quiz)}%` }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Quiz Details -->
                                    <div class="flex items-center justify-between text-xs text-muted-foreground mb-4">
                                        <span>Pass: {{ quiz.pass_threshold }}%</span>
                                        <!-- NEW: Show time limit if available -->
                                        <span v-if="quiz.time_limit_minutes" class="flex items-center">
                                            <Clock class="w-3 h-3 mr-1" />
                                            {{ quiz.time_limit_minutes }} min
                                        </span>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="space-y-2">
                                        <!-- Active Quiz Button (Start/Continue) -->
                                        <Button
                                            v-if="canTakeQuiz(quiz)"
                                            asChild
                                            class="w-full"
                                        >
                                            <Link :href="route('quizzes.show', quiz.id)">
                                                <Brain class="w-4 h-4 mr-2" />
                                                {{ getActiveButtonText(quiz) }}
                                            </Link>
                                        </Button>

                                        <!-- Deadline Expired Button -->
                                        <Button
                                            v-else-if="quiz.has_deadline && !quiz.is_available"
                                            disabled
                                            variant="ghost"
                                            class="w-full"
                                        >
                                            <AlertTriangle class="w-4 h-4 mr-2" />
                                            Deadline Passed
                                        </Button>

                                        <!-- View Results Button (For completed/failed quizzes) -->
                                        <Button
                                            v-else-if="isQuizCompleted(quiz) && quiz.latest_attempt_id"
                                            asChild
                                            variant="secondary"
                                            class="w-full"
                                        >
                                            <Link :href="route('quiz-attempts.results', quiz.latest_attempt_id)">
                                                <BarChart3 class="w-4 h-4 mr-2" />
                                                View Results
                                            </Link>
                                        </Button>


                                        <!-- Fallback disabled button -->
                                        <Button
                                            v-else
                                            variant="ghost"
                                            disabled
                                            class="w-full"
                                        >
                                            <Brain class="w-4 h-4 mr-2" />
                                            Unavailable
                                        </Button>
                                    </div>
                                </CardContent>
                            </div>
                        </Card>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="!quizzes.data || !quizzes.data.length" class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-muted/50 rounded-full flex items-center justify-center mb-4">
                        <FileText class="w-12 h-12 text-muted-foreground" />
                    </div>
                    <h3 class="text-lg font-medium text-foreground mb-2">No quizzes available</h3>
                    <p class="text-muted-foreground mb-4">There are no quizzes available for your enrolled courses at
                        the moment.</p>
                </div>

                <!-- No Results State -->
                <div v-else class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-muted/50 rounded-full flex items-center justify-center mb-4">
                        <Search class="w-12 h-12 text-muted-foreground" />
                    </div>
                    <h3 class="text-lg font-medium text-foreground mb-2">No quizzes found</h3>
                    <p class="text-muted-foreground mb-4">Try adjusting your search or filter criteria.</p>
                    <Button @click="clearFilters">
                        Clear Filters
                    </Button>
                </div>

                <!-- Pagination -->
                <div v-if="quizzes.links && quizzes.data && quizzes.data.length" class="flex justify-center">
                    <pagination :links="quizzes.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
