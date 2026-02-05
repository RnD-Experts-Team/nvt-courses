<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Textarea } from '@/components/ui/textarea'
import { Alert, AlertDescription } from '@/components/ui/alert'

import { Clock, ChevronLeft, ChevronRight, Send, AlertTriangle, CheckCircle } from 'lucide-vue-next'

interface Question {
    id: number
    question_text: string
    type: 'radio' | 'checkbox' | 'text'
    points: number
    options: string[]
    order: number
}

interface Module {
    id: number
    name: string
}

interface Course {
    id: number
    name: string
}

interface Quiz {
    id: number
    title: string
    total_points: number
    time_limit_minutes: number | null
}

interface Attempt {
    id: number
    attempt_number: number
    started_at: string
}

const props = defineProps<{
    module: Module
    course: Course
    quiz: Quiz
    attempt: Attempt
    questions: Question[]
    existingAnswers: Record<number, string[]>
    timeRemaining: number | null
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { name: "My Courses", href: "/courses-online" },
    { name: props.course.name, href: `/courses-online/${props.course.id}` },
    { name: props.module.name, href: `/courses-online/${props.course.id}` },
    { name: "Taking Quiz", href: "" },
]

// State
const currentQuestionIndex = ref(0)
const answers = ref<Record<number, string[]>>({})
const isSubmitting = ref(false)
const timeLeft = ref(props.timeRemaining)
let timerInterval: number | null = null

// Initialize answers from existing
onMounted(() => {
    if (props.existingAnswers) {
        answers.value = { ...props.existingAnswers }
    }
    
    // Start timer if time limit exists
    if (props.quiz.time_limit_minutes && timeLeft.value !== null) {
         timeLeft.value = Math.floor(timeLeft.value) // ADD THIS LINE
        startTimer()
    }
})

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval)
    }
})

const startTimer = () => {
    timerInterval = setInterval(() => {
        if (timeLeft.value !== null && timeLeft.value > 0) {
            timeLeft.value--
        } else if (timeLeft.value === 0) {
            // Auto-submit when time runs out
            submitQuiz()
        }
    }, 1000) as unknown as number
}

const formatTimeLeft = computed(() => {
    if (timeLeft.value === null) return null
    const totalSeconds = Math.floor(timeLeft.value) // Ensure integer
    const minutes = Math.floor(timeLeft.value / 60)
    const seconds = timeLeft.value % 60
    return `${minutes}:${seconds.toString().padStart(2, '0')}`
})

const isTimeLow = computed(() => {
    return timeLeft.value !== null && timeLeft.value < 60
})

const currentQuestion = computed(() => props.questions[currentQuestionIndex.value])

const progress = computed(() => {
    const answered = Object.keys(answers.value).filter(k => {
        const ans = answers.value[parseInt(k)]
        return ans && ans.length > 0 && ans.some(a => a.trim() !== '')
    }).length
    return (answered / props.questions.length) * 100
})

const answeredCount = computed(() => {
    return Object.keys(answers.value).filter(k => {
        const ans = answers.value[parseInt(k)]
        return ans && ans.length > 0 && ans.some(a => a.trim() !== '')
    }).length
})

// Navigation
const goToQuestion = (index: number) => {
    if (index >= 0 && index < props.questions.length) {
        saveCurrentAnswer()
        currentQuestionIndex.value = index
    }
}

const nextQuestion = () => goToQuestion(currentQuestionIndex.value + 1)
const prevQuestion = () => goToQuestion(currentQuestionIndex.value - 1)

// Answer handling
const getCurrentAnswer = () => {
    return answers.value[currentQuestion.value.id] || []
}

const setAnswer = (questionId: number, value: string[]) => {
    answers.value[questionId] = value
}

const toggleOption = (option: string) => {
    const q = currentQuestion.value
    const current = getCurrentAnswer()
    
    if (q.type === 'radio') {
        setAnswer(q.id, [option])
    } else if (q.type === 'checkbox') {
        const index = current.indexOf(option)
        if (index > -1) {
            current.splice(index, 1)
        } else {
            current.push(option)
        }
        setAnswer(q.id, [...current])
    }
}

const setTextAnswer = (text: string) => {
    setAnswer(currentQuestion.value.id, [text])
}

const isOptionSelected = (option: string) => {
    return getCurrentAnswer().includes(option)
}

const isQuestionAnswered = (questionId: number) => {
    const ans = answers.value[questionId]
    return ans && ans.length > 0 && ans.some(a => a.trim() !== '')
}

// Save answer to server (auto-save)
const saveCurrentAnswer = () => {
    const q = currentQuestion.value
    const answer = answers.value[q.id]
    
    if (answer) {
        fetch(route('courses-online.modules.quiz.save-answer', {
            courseOnline: props.course.id,
            courseModule: props.module.id,
            attempt: props.attempt.id
        }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                question_id: q.id,
                answer: answer
            })
        }).catch(console.error)
    }
}

// Submit quiz
const submitQuiz = () => {
    if (isSubmitting.value) return
    
    isSubmitting.value = true
    saveCurrentAnswer()
    
    const formattedAnswers = Object.entries(answers.value).map(([questionId, answer]) => ({
        question_id: parseInt(questionId),
        answer: answer
    }))
    
    router.post(route('courses-online.modules.quiz.submit', {
        courseOnline: props.course.id,
        courseModule: props.module.id,
        attempt: props.attempt.id
    }), {
        answers: formattedAnswers
    })
}

const confirmSubmit = () => {
    const unanswered = props.questions.length - answeredCount.value
    if (unanswered > 0) {
        if (confirm(`You have ${unanswered} unanswered question(s). Are you sure you want to submit?`)) {
            submitQuiz()
        }
    } else {
        submitQuiz()
    }
}
</script>

<template>
    <Head :title="`Quiz - ${quiz.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full max-w-7xl mx-auto space-y-4 sm:space-y-6 px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8 pb-20 sm:pb-24">
            <!-- Header with Timer -->
            <Card class="sticky top-0 z-10 shadow-md">
                <CardContent class="p-3 sm:p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-base sm:text-lg lg:text-xl font-bold break-words truncate">{{ quiz.title }}</h1>
                        </div>
                        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                            <div v-if="formatTimeLeft" :class="['flex items-center gap-2 px-3 py-2 sm:px-4 sm:py-2.5 rounded-lg font-mono text-base sm:text-lg font-bold min-w-[100px] sm:min-w-[120px] justify-center', isTimeLow ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 animate-pulse' : 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300']">
                                <Clock class="h-4 w-4 sm:h-5 sm:w-5 shrink-0" />
                                <span class="tabular-nums">{{ formatTimeLeft }}</span>
                            </div>
                            <Button @click="confirmSubmit" :disabled="isSubmitting" size="default" class="shrink-0">
                                <Send class="h-4 w-4 mr-1.5 sm:mr-2" />
                                <span class="hidden sm:inline">Submit Quiz</span>
                                <span class="sm:hidden">Submit</span>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Progress -->
            <Card class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-950 dark:to-purple-950 border-blue-200 dark:border-blue-800">
                <CardContent class="py-3 sm:py-4">
                    <div class="flex flex-col xs:flex-row items-start xs:items-center justify-between gap-3 xs:gap-4 mb-3">
                        <div class="flex-1">
                            <span class="text-xs sm:text-sm font-medium text-blue-900 dark:text-blue-100">Your Progress</span>
                            <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5 hidden xs:block">Keep going! You're doing great 💪</p>
                        </div>
                        <div class="text-left xs:text-right">
                            <span class="text-xl sm:text-2xl font-bold text-blue-900 dark:text-blue-100">{{ answeredCount }}</span>
                            <span class="text-xs sm:text-sm text-blue-700 dark:text-blue-300"> / {{ questions.length }}</span>
                        </div>
                    </div>
                    <Progress :model-value="progress" class="h-2 sm:h-3" />
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-2 text-center">
                        {{ Math.round(progress) }}% Complete
                    </p>
                </CardContent>
            </Card>

            <!-- Question Navigation -->
            <Card>
                <CardHeader class="pb-2 sm:pb-3">
                    <CardTitle class="text-sm sm:text-base">Quick Navigation</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-1.5 sm:gap-2">
                        <Button
                            v-for="(q, index) in questions"
                            :key="q.id"
                            :variant="currentQuestionIndex === index ? 'default' : isQuestionAnswered(q.id) ? 'secondary' : 'outline'"
                            size="sm"
                            class="w-8 h-8 sm:w-10 sm:h-10 relative text-xs sm:text-sm p-0"
                            @click="goToQuestion(index)"
                        >
                            {{ index + 1 }}
                            <CheckCircle v-if="isQuestionAnswered(q.id) && currentQuestionIndex !== index" class="absolute -top-1 -right-1 h-3 w-3 sm:h-4 sm:w-4 text-green-500 bg-white dark:bg-gray-900 rounded-full" />
                        </Button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-4 mt-3 text-xs text-muted-foreground">
                        <div class="flex items-center gap-1">
                            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded bg-primary"></div>
                            <span>Current</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded bg-secondary"></div>
                            <span>Answered</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded border-2"></div>
                            <span>Not Answered</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Current Question -->
            <Card class="border-2 border-primary/20">
                <CardHeader class="bg-gradient-to-r from-primary/5 to-purple-500/5 p-4 sm:p-6">
                    <div class="flex flex-col xs:flex-row items-start xs:items-center justify-between gap-2 sm:gap-3">
                        <Badge variant="default" class="text-xs sm:text-sm">Question {{ currentQuestionIndex + 1 }} of {{ questions.length }}</Badge>
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <Badge v-if="currentQuestion.type === 'radio'" variant="outline" class="text-xs">
                                Single Choice
                            </Badge>
                            <Badge v-else-if="currentQuestion.type === 'checkbox'" variant="outline" class="text-xs">
                                Multiple Choice
                            </Badge>
                            <Badge v-else variant="outline" class="text-xs">
                                Text Answer
                            </Badge>
                            <Badge variant="secondary" class="text-xs sm:text-sm">
                                {{ currentQuestion.points }} pt{{ currentQuestion.points !== 1 ? 's' : '' }}
                            </Badge>
                        </div>
                    </div>
                    <CardTitle class="text-base sm:text-lg lg:text-xl mt-3 sm:mt-4 leading-relaxed break-words">{{ currentQuestion.question_text }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 sm:space-y-4 p-4 sm:p-6">
                    <!-- Radio/Checkbox Options -->
                    <div v-if="currentQuestion.type !== 'text'" class="space-y-2 sm:space-y-3">
                        <Alert class="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800 py-2 sm:py-3">
                            <AlertDescription class="text-xs sm:text-sm text-blue-800 dark:text-blue-200">
                                {{ currentQuestion.type === 'radio' ? '📌 Select ONE answer' : '📌 Select ALL that apply' }}
                            </AlertDescription>
                        </Alert>
                        <div
                            v-for="(option, index) in currentQuestion.options"
                            :key="index"
                            @click="toggleOption(option)"
                            :class="[
                                'flex items-start gap-2 sm:gap-3 p-3 sm:p-4 rounded-lg border-2 cursor-pointer transition-all duration-200',
                                isOptionSelected(option) 
                                    ? 'border-primary bg-primary/10 shadow-md' 
                                    : 'border-border hover:border-primary/50 hover:bg-muted/50 hover:shadow-sm'
                            ]"
                        >
                            <div class="flex items-center justify-center mt-0.5 shrink-0">
                                <div :class="[
                                    'w-5 h-5 sm:w-6 sm:h-6 rounded flex items-center justify-center border-2 transition-all',
                                    currentQuestion.type === 'radio' ? 'rounded-full' : 'rounded',
                                    isOptionSelected(option) ? 'border-primary bg-primary scale-110' : 'border-gray-400'
                                ]">
                                    <CheckCircle v-if="isOptionSelected(option)" class="h-3 w-3 sm:h-4 sm:w-4 text-primary-foreground" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start gap-1.5 sm:gap-2">
                                    <span class="font-medium text-primary text-xs sm:text-sm shrink-0">{{ String.fromCharCode(65 + index) }}.</span>
                                    <span :class="['text-sm sm:text-base break-words leading-relaxed', isOptionSelected(option) ? 'font-medium' : '']">{{ option }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Text Answer -->
                    <div v-else class="space-y-2 sm:space-y-3">
                        <Alert class="bg-purple-50 dark:bg-purple-950 border-purple-200 dark:border-purple-800 py-2 sm:py-3">
                            <AlertDescription class="text-xs sm:text-sm text-purple-800 dark:text-purple-200">
                                ✍️ Write your answer below. This will be manually graded by your instructor.
                            </AlertDescription>
                        </Alert>
                        <Textarea
                            :model-value="getCurrentAnswer()[0] || ''"
                            @update:model-value="setTextAnswer($event)"
                            placeholder="Type your detailed answer here..."
                            rows="6"
                            class="text-sm sm:text-base min-h-[120px] sm:min-h-[160px]"
                        />
                        <p class="text-xs text-muted-foreground">
                            💡 Tip: Be clear and detailed in your response for better evaluation.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Navigation Buttons -->
            <Card class="bg-muted/50">
                <CardContent class="py-3 sm:py-4">
                    <div class="flex items-center justify-between gap-2 sm:gap-3">
                        <Button
                            variant="outline"
                            size="default"
                            @click="prevQuestion"
                            :disabled="currentQuestionIndex === 0"
                            class="flex-1 sm:flex-initial min-w-0"
                        >
                            <ChevronLeft class="h-4 w-4 sm:mr-2" />
                            <span class="hidden xs:inline">Previous</span>
                        </Button>
                        
                        <div class="hidden md:block text-xs sm:text-sm text-muted-foreground whitespace-nowrap">
                            Question {{ currentQuestionIndex + 1 }} of {{ questions.length }}
                        </div>
                        
                        <Button
                            v-if="currentQuestionIndex < questions.length - 1"
                            size="default"
                            @click="nextQuestion"
                            class="flex-1 sm:flex-initial min-w-0"
                        >
                            <span class="hidden xs:inline">Next</span>
                            <ChevronRight class="h-4 w-4 sm:ml-2" />
                        </Button>
                        <Button
                            v-else
                            size="default"
                            @click="confirmSubmit"
                            :disabled="isSubmitting"
                            class="flex-1 sm:flex-initial bg-green-600 hover:bg-green-700 min-w-0"
                        >
                            <Send class="h-4 w-4 sm:mr-2" />
                            <span class="hidden xs:inline">Submit Quiz</span>
                            <span class="xs:hidden">Submit</span>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Warning -->
            <Alert v-if="isTimeLow" variant="destructive" class="flex-col sm:flex-row items-start sm:items-center gap-2">
                <AlertTriangle class="h-4 w-4 shrink-0" />
                <AlertDescription class="text-xs sm:text-sm">
                    Less than 1 minute remaining! Your quiz will be automatically submitted when time runs out.
                </AlertDescription>
            </Alert>
        </div>
    </AppLayout>
</template>
