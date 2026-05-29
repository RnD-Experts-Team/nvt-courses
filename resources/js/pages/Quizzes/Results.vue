<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-background to-muted/20">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header Section -->
                <div class="text-center mb-8">
                    <h1 class="text-4xl font-bold text-foreground mb-2">Quiz Results</h1>
                    <p class="text-lg text-muted-foreground">
                        Performance review for <span class="font-semibold text-foreground">"{{ attempt.quiz?.title || 'Quiz' }}"</span>
                    </p>
                </div>

                <!-- Score Overview Card -->
                <Card class="mb-8">
                    <CardContent class="p-8">
                        <div class="text-center mb-6">
                            <!-- Score Circle -->
                            <div class="relative inline-flex items-center justify-center w-32 h-32 mb-4">
                                <svg class="transform -rotate-90 w-32 h-32">
                                    <circle
                                        cx="64" cy="64" r="56"
                                        stroke="currentColor"
                                        stroke-width="8"
                                        fill="none"
                                        class="text-border"
                                    />
                                    <circle
                                        cx="64" cy="64" r="56"
                                        stroke="currentColor"
                                        stroke-width="8"
                                        fill="none"
                                        :stroke-dasharray="circumference"
                                        :stroke-dashoffset="circumference - (scorePercentage / 100) * circumference"
                                        :class="scorePercentage >= (attempt.quiz?.pass_threshold || 70) ? 'text-primary' : 'text-destructive'"
                                        class="transition-all duration-1000 ease-out"
                                    />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-3xl font-bold text-foreground">{{ Math.round(scorePercentage) }}%</span>
                                    <span class="text-sm text-muted-foreground">Score</span>
                                </div>
                            </div>

                            <!-- Pass/Fail Status -->
                            <div class="mb-4">
                                <Badge
                                    :variant="attempt.passed ? 'default' : 'destructive'"
                                    class="px-6 py-3 text-lg font-semibold border-2"
                                >
                                    <CheckCircle v-if="attempt.passed" class="w-6 h-6 mr-2" />
                                    <XCircle v-else class="w-6 h-6 mr-2" />
                                    {{ attempt.passed ? 'PASSED' : 'FAILED' }}
                                </Badge>
                            </div>

                            <p class="text-muted-foreground">
                                You scored <span class="font-bold text-foreground">{{ attempt.total_score || 0 }}</span>
                                out of <span class="font-bold text-foreground">{{ attempt.quiz?.total_points || 0 }}</span> points
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Detailed Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Score Card -->
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-primary/10 text-primary">
                                    <BarChart3 class="w-6 h-6" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted-foreground">Final Score</p>
                                    <p class="text-xl font-bold text-foreground">{{ attempt.total_score || 0 }}/{{ attempt.quiz?.total_points || 0 }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Percentage Card -->
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div :class="[
                                    'p-3 rounded-full',
                                    scorePercentage >= (attempt.quiz?.pass_threshold || 70)
                                        ? 'bg-primary/10 text-primary'
                                        : 'bg-destructive/10 text-destructive'
                                ]">
                                    <TrendingUp class="w-6 h-6" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted-foreground">Percentage</p>
                                    <p class="text-xl font-bold text-foreground">{{ Math.round(scorePercentage) }}%</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Attempt Number Card -->
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-secondary text-secondary-foreground">
                                    <Clock class="w-6 h-6" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted-foreground">Attempt</p>
                                    <p class="text-xl font-bold text-foreground">{{ attempt.attempt_number || userAttempts.length }}/{{ attempt.quiz?.max_attempts ?? '∞' }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Attempts Left Card -->
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-accent text-accent-foreground">
                                    <RotateCcw class="w-6 h-6" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted-foreground">Attempts Left</p>
                                    <p class="text-xl font-bold text-foreground">{{ attemptsLeft }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Additional Info Card -->
                <Card class="mb-8">
                    <CardHeader>
                        <CardTitle>Attempt Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center md:text-left">
                                <p class="text-sm text-muted-foreground mb-1">Completed At</p>
                                <p class="text-sm font-medium text-foreground">
                                    {{ formatDate(attempt.completed_at) || 'Not completed' }}
                                </p>
                            </div>
                            <div class="text-center md:text-left">
                                <p class="text-sm text-muted-foreground mb-1">Pass Threshold</p>
                                <p class="text-sm font-medium text-foreground">{{ attempt.quiz?.pass_threshold || 70 }}%</p>
                            </div>
                            <div class="text-center md:text-left">
                                <p class="text-sm text-muted-foreground mb-1">Time Taken</p>
                                <p class="text-sm font-medium text-foreground">{{ attempt.time_taken || 'N/A' }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row justify-center gap-4 mb-8">
                    <Button
                        v-if="canRetry"
                        @click="retryQuiz"
                        class="shadow-lg hover:shadow-xl"
                        size="lg"
                    >
                        <RotateCcw class="w-5 h-5 mr-2" />
                        Retry Quiz
                    </Button>

                    <Button
                        asChild
                        variant="outline"
                        size="lg"
                    >
                        <Link :href="route('quizzes.index')">
                            <ArrowLeft class="w-5 h-5 mr-2" />
                            Back to Quizzes
                        </Link>
                    </Button>
                </div>

                <!-- Questions Review Section -->
                <Card>
                    <CardHeader class="bg-muted/50 border-b">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-xl">Question Review</CardTitle>
                            <div class="flex items-center space-x-2">
                                <!-- Show toggle button only when answers are available -->
                                <Button
                                    v-if="showCorrectAnswersAllowed"
                                    @click="toggleCorrectAnswers"
                                    variant="secondary"
                                    size="sm"
                                >
                                    {{ showAnswers ? 'Hide' : 'Show' }} Correct Answers
                                </Button>
                                <Badge :variant="showCorrectAnswersAllowed ? 'default' : 'secondary'">
                                    {{ showCorrectAnswersAllowed ? 'Answers Available' : 'Answers Locked' }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Show explanation when answers are available -->
                        <div v-if="showCorrectAnswersAllowed" class="mt-2 text-sm text-muted-foreground">
                            <p class="flex items-center">
                                <CheckCircle class="w-4 h-4 mr-1 text-primary" />
                                Correct answers unlocked - {{ attempt.passed ? 'Quiz passed!' : 'Maximum attempts reached' }}
                            </p>
                        </div>
                        <div v-else class="mt-2 text-sm text-muted-foreground">
                            <p class="flex items-center">
                                <AlertTriangle class="w-4 h-4 mr-1 text-accent-foreground" />
                                Correct answers will be shown after passing or reaching maximum attempts
                            </p>
                        </div>
                    </CardHeader>

                    <CardContent class="p-6">
                        <div v-if="attempt.responses && attempt.responses.length" class="space-y-6">
                            <Card v-for="(response, index) in attempt.responses" :key="response.id || index">
                                <!-- Question Header -->
                                <CardHeader class="bg-muted/30 border-b">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-primary text-primary-foreground rounded-full flex items-center justify-center text-sm font-bold mr-3">
                                                {{ index + 1 }}
                                            </div>
                                            <span class="font-medium text-foreground">
                                                Question {{ index + 1 }}
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Badge variant="outline">
                                                {{ response.question?.points || 0 }} pts
                                            </Badge>
                                            <div :class="[
                                                'w-3 h-3 rounded-full',
                                                getStatusColor(response)
                                            ]"></div>
                                        </div>
                                    </div>
                                </CardHeader>

                                <!-- Question Content -->
                                <CardContent class="p-4 space-y-4">
                                    <div class="bg-muted rounded-lg p-3">
                                        <p class="text-foreground font-medium">
                                            {{ response.question?.question_text || 'Question not available' }}
                                        </p>
                                    </div>

                                    <!-- Your Answer -->
                                    <div class="bg-primary/5 border border-primary/20 rounded-lg p-3">
                                        <p class="text-primary-foreground/90">
                                            {{ formatAnswer(response.answer) || 'No answer provided' }}
                                        </p>
                                    </div>

                                    <!-- Correct Answer (safely accessed) -->
                                    <div v-if="showCorrectAnswersAllowed && showAnswers && response.question?.correct_answer">
                                        <p class="text-sm font-medium text-muted-foreground mb-2">Correct Answer(s):</p>
                                        <div class="bg-primary/10 border border-primary/30 rounded-lg p-3">
                                            <p class="text-primary">
                                                {{ formatCorrectAnswer(response.question?.correct_answer) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Answer Explanation (safely accessed) -->
                                    <div v-if="showCorrectAnswersAllowed && showAnswers && response.question?.correct_answer_explanation">
                                        <p class="text-sm font-medium text-muted-foreground mb-2">Explanation:</p>
                                        <div class="bg-accent/50 border border-accent rounded-lg p-3">
                                            <p class="text-accent-foreground">
                                                {{ response.question?.correct_answer_explanation }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="flex items-center justify-between pt-2 border-t border-border">
                                        <div class="flex items-center">
                                            <Badge :variant="getStatusBadgeVariant(response)">
                                                <component :is="getStatusIcon(response)" class="w-3 h-3 mr-1" />
                                                {{ getStatusText(response) }}
                                            </Badge>
                                        </div>
                                        <div v-if="response.points_earned !== undefined" class="text-sm text-muted-foreground">
                                            Points: {{ response.points_earned }}/{{ response.question?.points || 0 }}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div class="mx-auto w-24 h-24 bg-muted rounded-full flex items-center justify-center mb-4">
                                <FileText class="w-12 h-12 text-muted-foreground" />
                            </div>
                            <CardTitle class="text-lg font-medium text-foreground mb-2">No responses found</CardTitle>
                            <p class="text-muted-foreground">This attempt has no recorded responses.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from "@/layouts/AppLayout.vue";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    CheckCircle,
    XCircle,
    BarChart3,
    TrendingUp,
    Clock,
    RotateCcw,
    ArrowLeft,
    AlertTriangle,
    FileText
} from 'lucide-vue-next';

export default {
    components: {
        AppLayout,
        Link,
        Card,
        CardContent,
        CardDescription,
        CardFooter,
        CardHeader,
        CardTitle,
        Badge,
        Button,
        CheckCircle,
        XCircle,
        BarChart3,
        TrendingUp,
        Clock,
        RotateCcw,
        ArrowLeft,
        AlertTriangle,
        FileText,
    },
    props: {
        attempt: {
            type: Object,
            required: true,
        },
        userAttempts: {
            type: Array,
            default: () => [],
        },
        showCorrectAnswersAllowed: {
            type: Boolean,
            default: false,
        },
    },
    setup(props) {
        const showAnswers = ref(false);
        const circumference = 2 * Math.PI * 56; // For the circular progress

        const breadcrumbs = computed(() => [
            { name: 'Quizzes', route: 'quizzes.index' },
            { name: props.attempt.quiz?.title || 'Quiz', route: null },
            { name: 'Results', route: null },
        ]);

        const scorePercentage = computed(() => {
            if (!props.attempt.quiz?.total_points || props.attempt.quiz.total_points === 0) return 0;
            return (props.attempt.total_score / props.attempt.quiz.total_points) * 100;
        });

        const attemptsLeft = computed(() => {
            const maxAttempts = props.attempt.quiz?.max_attempts;
            if (!maxAttempts) {
                return 'Unlimited';
            }

            return Math.max(0, maxAttempts - props.userAttempts.length);
        });

        const canRetry = computed(() => {
            const maxAttempts = props.attempt.quiz?.max_attempts;

            if (!maxAttempts) {
                return !props.attempt.passed;
            }

            return !props.attempt.passed && props.userAttempts.length < maxAttempts;
        });

        const showCorrectAnswersAllowed = computed(() => props.showCorrectAnswersAllowed);

        // Methods
        const formatDate = (dateString) => {
            if (!dateString) return null;
            return new Date(dateString).toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        };

        const formatAnswer = (answer) => {
            if (!answer) return null; // Return null instead of undefined
            if (Array.isArray(answer)) {
                return answer.join(', ');
            }
            return String(answer); // Ensure it's a string
        }

        const formatCorrectAnswer = (correctAnswer) => {
            if (Array.isArray(correctAnswer)) {
                return correctAnswer.join(', ');
            }
            return correctAnswer;
        };

        const getStatusText = (response) => {
            if (response.question?.type === 'text') {
                return response.is_correct !== null ? 'Manually Graded' : 'Pending Review';
            }
            return response.is_correct ? 'Correct' : 'Incorrect';
        };

        const getStatusColor = (response) => {
            if (response.is_correct === true) return 'bg-primary';
            if (response.is_correct === false) return 'bg-destructive';
            return 'bg-accent';
        };

        const getStatusBadgeVariant = (response) => {
            if (response.is_correct === true) return 'default';
            if (response.is_correct === false) return 'destructive';
            return 'secondary';
        };

        const getStatusIcon = (response) => {
            if (response.is_correct === true) return CheckCircle;
            if (response.is_correct === false) return XCircle;
            return Clock;
        };

        const toggleCorrectAnswers = () => {
            showAnswers.value = !showAnswers.value;
        };

        const retryQuiz = () => {
            router.visit(route('quizzes.show', props.attempt.quiz.id));
        };

        return {
            breadcrumbs,
            showAnswers,
            circumference,
            scorePercentage,
            attemptsLeft,
            canRetry,
            showCorrectAnswersAllowed,
            formatDate,
            formatAnswer,
            formatCorrectAnswer,
            getStatusText,
            getStatusColor,
            getStatusBadgeVariant,
            getStatusIcon,
            toggleCorrectAnswers,
            retryQuiz,
        };
    },
};
</script>

<style scoped>
/* Circular progress animation */
circle {
    transition: stroke-dashoffset 0.5s ease-in-out;
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Print styles */
@media print {
    .bg-gradient-to-br {
        background: hsl(var(--background)) !important;
    }

    button:not(.print-visible) {
        display: none !important;
    }

    .shadow-lg {
        box-shadow: none !important;
        border: 1px solid hsl(var(--border)) !important;
    }
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .text-4xl {
        font-size: 2.25rem;
        line-height: 2.5rem;
    }

    .w-32.h-32 {
        width: 6rem;
        height: 6rem;
    }

    .w-32.h-32 svg {
        width: 6rem;
        height: 6rem;
    }

    .grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
