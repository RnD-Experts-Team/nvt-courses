<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-3xl font-bold text-white">Edit Quiz</h1>
                <Button as-child variant="outline">
                    <Link :href="route('admin.quizzes.index')">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Quizzes
                    </Link>
                </Button>
            </div>

            <!-- Enhanced Error Summary -->
            <div v-if="hasErrors" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-6 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-red-800">
                            {{ errorSummary.title }}
                        </h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p class="mb-3">{{ errorSummary.message }}</p>
                            <ul class="list-disc pl-5 space-y-1">
                                <li v-for="(error, field) in allErrors" :key="field" class="break-words">
                                    <span class="font-medium">{{ formatFieldName(field) }}:</span>
                                    {{ Array.isArray(error) ? error[0] : error }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <button
                            @click="scrollToFirstError"
                            class="inline-flex items-center rounded-md bg-red-100 px-3 py-1.5 text-xs font-medium text-red-800 hover:bg-red-200 transition-colors"
                        >
                            Go to first error
                        </button>
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            <div v-if="showSuccessMessage" class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="ml-3 text-sm text-green-700 font-medium">
                        Quiz saved successfully!
                    </p>
                </div>
            </div>

            <!-- Real-time Validation Status -->
            <div v-if="form.status === 'published' && hasPublishingRequirements" class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Publishing Requirements</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p class="mb-2">To publish this quiz, please ensure:</p>
                            <ul class="list-disc pl-5 space-y-1">
                                <li :class="publishingChecklist.hasTitle ? 'text-green-600' : 'text-red-600'">
                                    {{ publishingChecklist.hasTitle ? '✓' : '✗' }} Quiz has a title
                                </li>
                                <li :class="publishingChecklist.hasCourse ? 'text-green-600' : 'text-red-600'">
                                    {{ publishingChecklist.hasCourse ? '✓' : '✗' }} Course is selected
                                </li>
                                <li :class="publishingChecklist.hasValidThreshold ? 'text-green-600' : 'text-red-600'">
                                    {{ publishingChecklist.hasValidThreshold ? '✓' : '✗' }} Pass threshold is set (0-100%)
                                </li>
                                <li :class="publishingChecklist.hasQuestions ? 'text-green-600' : 'text-red-600'">
                                    {{ publishingChecklist.hasQuestions ? '✓' : '✗' }} At least one valid question
                                </li>
                                <li :class="publishingChecklist.allQuestionsValid ? 'text-green-600' : 'text-red-600'">
                                    {{ publishingChecklist.allQuestionsValid ? '✓' : '✗' }} All questions have correct answers
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitQuiz" class="space-y-8">
                <!-- NEW: Course Assignment -->
                <Card>
                    <CardHeader>
                        <CardTitle>Course Assignment</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <!-- Course Type Selection -->
                            <div>
                                <Label for="course_type" :class="getFieldError('course_type') ? 'text-red-700' : ''">
                                    Course Type <span class="text-red-500">*</span>
                                </Label>
                                <Select v-model="form.course_type" @update:model-value="resetCourseSelection" :disabled="form.processing">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select course type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="regular">Regular Course</SelectItem>
                                        <SelectItem value="online">Online Course</SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="getFieldError('course_type')" class="mt-1 text-sm text-red-600">
                                    {{ getFieldError('course_type') }}
                                </div>
                            </div>

                            <!-- Course Selection -->
                            <div v-if="form.course_type">
                                <Label for="course_id" :class="getFieldError('course_id') || getFieldError('course_online_id') ? 'text-red-700' : ''">
                                    Select {{ form.course_type === 'regular' ? 'Regular' : 'Online' }} Course <span class="text-red-500">*</span>
                                </Label>
                                <Select v-model="selectedCourseId" :disabled="form.processing">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Course" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="course in (form.course_type === 'regular' ? courses : onlineCourses)"
                                            :key="course.id"
                                            :value="course.id.toString()"
                                        >
                                            {{ course.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="getFieldError('course_id') || getFieldError('course_online_id')" class="mt-1 text-sm text-red-600">
                                    {{ getFieldError('course_id') || getFieldError('course_online_id') }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Quiz Details -->
                <Card>
                    <CardHeader>
                        <CardTitle>Quiz Details</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <Label for="status" :class="getFieldError('status') ? 'text-red-700' : ''">
                                    Status <span class="text-red-500">*</span>
                                </Label>
                                <Select
                                    v-model="form.status"
                                    :disabled="form.processing"
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="published">Published</SelectItem>
                                        <SelectItem value="archived">Archived</SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="getFieldError('status')" class="mt-1 text-sm text-red-600">
                                    {{ getFieldError('status') }}
                                </div>
                            </div>

                            <div>
                                <Label for="pass_threshold" :class="getFieldError('pass_threshold') ? 'text-red-700' : ''">
                                    Pass Threshold (%) <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="pass_threshold"
                                    v-model.number="form.pass_threshold"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    placeholder="Enter pass threshold (e.g., 80.00)"
                                    :disabled="form.processing"
                                />
                                <div v-if="getFieldError('pass_threshold')" class="mt-1 text-sm text-red-600">
                                    {{ getFieldError('pass_threshold') }}
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <Label for="title" :class="getFieldError('title') ? 'text-red-700' : ''">
                                    Title <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Enter quiz title"
                                    :disabled="form.processing"
                                />
                                <div v-if="getFieldError('title')" class="mt-1 text-sm text-red-600">
                                    {{ getFieldError('title') }}
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    placeholder="Enter quiz description (optional)"
                                    :disabled="form.processing"
                                />
                                <div v-if="getFieldError('description')" class="mt-1 text-sm text-red-600">
                                    {{ getFieldError('description') }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- NEW: Deadline Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle>⏰ Deadline Settings</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <!-- Enable Deadline -->
                            <div class="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    v-model="form.has_deadline"
                                    id="has_deadline"
                                    :disabled="form.processing"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <Label for="has_deadline">Set a deadline for this quiz</Label>
                            </div>

                            <!-- Deadline Configuration -->
                            <div v-if="form.has_deadline" class="space-y-4 p-4 border rounded-lg bg-muted/20">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label for="deadline_date">Deadline Date</Label>
                                        <Input
                                            id="deadline_date"
                                            type="date"
                                            v-model="form.deadline_date"
                                            :disabled="form.processing"
                                        />
                                        <div v-if="getFieldError('deadline_date')" class="mt-1 text-sm text-red-600">
                                            {{ getFieldError('deadline_date') }}
                                        </div>
                                    </div>
                                    <div>
                                        <Label for="deadline_time">Deadline Time</Label>
                                        <Input
                                            id="deadline_time"
                                            type="time"
                                            v-model="form.deadline_time"
                                            :disabled="form.processing"
                                        />
                                        <div v-if="getFieldError('deadline_time')" class="mt-1 text-sm text-red-600">
                                            {{ getFieldError('deadline_time') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        v-model="form.enforce_deadline"
                                        id="enforce_deadline"
                                        :disabled="form.processing"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <Label for="enforce_deadline">Strictly enforce deadline (no late submissions)</Label>
                                </div>

                                <div>
                                    <Label for="time_limit_minutes">Quiz time limit (minutes per attempt)</Label>
                                    <Input
                                        id="time_limit_minutes"
                                        type="number"
                                        v-model.number="form.time_limit_minutes"
                                        :disabled="form.processing"
                                        placeholder="Leave empty for no time limit"
                                        min="1"
                                        max="1440"
                                    />
                                    <p class="text-xs text-muted-foreground mt-1">Optional: Set how many minutes students have to complete the quiz</p>
                                    <div v-if="getFieldError('time_limit_minutes')" class="mt-1 text-sm text-red-600">
                                        {{ getFieldError('time_limit_minutes') }}
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        v-model="form.allows_extensions"
                                        id="allows_extensions"
                                        :disabled="form.processing"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <Label for="allows_extensions">Allow deadline extensions upon request</Label>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Attempt Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle>🎯 Attempt Settings</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <!-- Maximum Attempts -->
                            <div>
                                <Label for="max_attempts">Maximum Attempts</Label>
                                <Input
                                    id="max_attempts"
                                    v-model.number="form.max_attempts"
                                    type="number"
                                    min="1"
                                    max="100"
                                    :disabled="form.processing"
                                    placeholder="Leave empty for unlimited"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    How many times can a user attempt this quiz? Leave empty for unlimited.
                                </p>
                                <div v-if="getFieldError('max_attempts')" class="mt-1 text-sm text-red-600">
                                    {{ getFieldError('max_attempts') }}
                                </div>
                            </div>

                            <div>
                                <Label for="retry_delay_hours">Retry Delay (hours)</Label>
                                <Input
                                    id="retry_delay_hours"
                                    v-model.number="form.retry_delay_hours"
                                    type="number"
                                    min="0"
                                    max="168"
                                    :disabled="form.processing"
                                />
                                <p class="text-xs text-muted-foreground mt-1">0 = no delay between attempts.</p>
                                <div v-if="getFieldError('retry_delay_hours')" class="mt-1 text-sm text-red-600">
                                    {{ getFieldError('retry_delay_hours') }}
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <Label for="show_correct_answers">Show Correct Answers</Label>
                                <Select v-model="form.show_correct_answers" :disabled="form.processing">
                                    <SelectTrigger id="show_correct_answers">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in showCorrectAnswersOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="getFieldError('show_correct_answers')" class="mt-1 text-sm text-red-600">
                                    {{ getFieldError('show_correct_answers') }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Questions (EXISTING CODE UNCHANGED) -->
                <Card>
                    <CardHeader>
                        <CardTitle>Questions ({{ form.questions.length }}/20)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-for="(question, index) in form.questions" :key="`question-${index}`"
                             class="border dark rounded-lg p-4 mb-4"
                             :class="hasQuestionErrors(index) ? 'border-red-300 bg-red-50' : ''">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-sm font-medium" :class="hasQuestionErrors(index) ? 'text-red-900' : 'text-white'">
                                    Question {{ index + 1 }}
                                    <span v-if="hasQuestionErrors(index)" class="inline-flex items-center ml-2 px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                        Has errors
                                    </span>
                                </h3>
                                <Button
                                    v-if="form.questions.length > 1"
                                    @click="removeQuestion(index)"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    :disabled="form.processing"
                                >
                                    Remove
                                </Button>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <Label :for="`question_text_${index}`" :class="getFieldError(`questions.${index}.question_text`) ? 'text-red-700' : ''">
                                        Question Text <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        :id="`question_text_${index}`"
                                        v-model="question.question_text"
                                        type="text"
                                        placeholder="Enter question text"
                                        :disabled="form.processing"
                                    />
                                    <div v-if="getFieldError(`questions.${index}.question_text`)" class="mt-1 text-sm text-red-600">
                                        {{ getFieldError(`questions.${index}.question_text`) }}
                                    </div>
                                </div>

                                <div>
                                    <Label :for="`type_${index}`" :class="getFieldError(`questions.${index}.type`) ? 'text-red-700' : ''">
                                        Type <span class="text-red-500">*</span>
                                    </Label>
                                    <Select
                                        :id="`type_${index}`"
                                        v-model="question.type"
                                        :disabled="form.processing"
                                        @update:model-value="resetQuestionOptions(index)"
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select question type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="radio">Radio (Single Choice)</SelectItem>
                                            <SelectItem value="checkbox">Checkbox (Multiple Choice)</SelectItem>
                                            <SelectItem value="text">Text (Open-ended)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="getFieldError(`questions.${index}.type`)" class="mt-1 text-sm text-red-600">
                                        {{ getFieldError(`questions.${index}.type`) }}
                                    </div>
                                </div>

                                <div v-if="question.type !== 'text'">
                                    <Label :for="`points_${index}`" :class="getFieldError(`questions.${index}.points`) ? 'text-red-700' : ''">
                                        Points <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        :id="`points_${index}`"
                                        v-model.number="question.points"
                                        type="number"
                                        min="0"
                                        placeholder="Enter points"
                                        :disabled="form.processing"
                                    />
                                    <div v-if="getFieldError(`questions.${index}.points`)" class="mt-1 text-sm text-red-600">
                                        {{ getFieldError(`questions.${index}.points`) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Options and Correct Answers for non-text questions -->
                            <div v-if="question.type !== 'text'" class="mt-4">
                                <h4 class="mb-2 text-sm font-medium text-white">Options</h4>
                                <div v-for="(option, optIndex) in question.options" :key="`option-${index}-${optIndex}`" class="mb-2 flex items-center">
                                    <Input
                                        v-model="question.options[optIndex]"
                                        type="text"
                                        class="flex-1"
                                        :placeholder="`Option ${optIndex + 1}`"
                                        :disabled="form.processing"
                                    />
                                    <Button
                                        v-if="question.options.length > 2"
                                        @click="removeOption(index, optIndex)"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="ml-2 text-destructive"
                                        :disabled="form.processing"
                                    >
                                        Remove
                                    </Button>
                                </div>
                                <div v-if="getFieldError(`questions.${index}.options`)" class="mt-1 text-sm text-red-600">
                                    {{ getFieldError(`questions.${index}.options`) }}
                                </div>
                                <Button
                                    @click="addOption(index)"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="mt-2"
                                    :disabled="form.processing || question.options.length >= 10"
                                >
                                    Add Option
                                </Button>

                                <!-- Correct answers section -->
                                <div class="mt-4">
                                    <label class="mb-1 block text-sm font-medium text-white">
                                        Correct Answer(s) <span class="text-red-500">*</span>
                                    </label>
                                    <div v-if="question.type === 'radio'" class="space-y-2">
                                        <div v-for="(option, optIndex) in question.options" :key="`radio-${index}-${optIndex}`" class="flex items-center">
                                            <input
                                                :id="`correct_answer_${index}_${optIndex}`"
                                                :checked="question.correct_answer.includes(option)"
                                                :value="option"
                                                type="radio"
                                                :name="`correct_answer_${index}`"
                                                class="h-4 w-4 dark text-indigo-600 focus:ring-indigo-500"
                                                :disabled="form.processing || !option.trim()"
                                                @change="updateCorrectAnswer(index, option)"
                                            />
                                            <label :for="`correct_answer_${index}_${optIndex}`" class="ml-2 text-sm text-white">
                                                {{ option || `Option ${optIndex + 1}` }}
                                            </label>
                                        </div>
                                    </div>
                                    <div v-else-if="question.type === 'checkbox'" class="space-y-2">
                                        <div v-for="(option, optIndex) in question.options" :key="`checkbox-${index}-${optIndex}`" class="flex items-center">
                                            <input
                                                :id="`correct_answer_${index}_${optIndex}`"
                                                :value="option"
                                                type="checkbox"
                                                v-model="question.correct_answer"
                                                class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                :disabled="form.processing || !option.trim()"
                                            />
                                            <label :for="`correct_answer_${index}_${optIndex}`" class="ml-2 text-sm text-white">
                                                {{ option || `Option ${optIndex + 1}` }}
                                            </label>
                                        </div>
                                    </div>
                                    <div v-if="getFieldError(`questions.${index}.correct_answer`)" class="mt-1 text-sm text-red-600">
                                        {{ getFieldError(`questions.${index}.correct_answer`) }}
                                    </div>
                                </div>

                                <!-- Correct Answer Explanation -->
                                <div class="mt-4">
                                    <Label :for="`correct_answer_explanation_${index}`">Correct Answer Explanation</Label>
                                    <Textarea
                                        :id="`correct_answer_explanation_${index}`"
                                        v-model="question.correct_answer_explanation"
                                        rows="2"
                                        placeholder="Explain why this is correct (optional)"
                                        :disabled="form.processing"
                                    />
                                </div>
                            </div>

                            <!-- Note for text questions -->
                            <div v-if="question.type === 'text'" class="mt-4 rounded-lg bg-blue-50 p-3">
                                <p class="text-sm text-blue-700">
                                    📝 This is an open-ended text question. Students will provide their own written response.
                                </p>
                            </div>
                        </div>

                        <Button
                            @click="addQuestion"
                            type="button"
                            :disabled="form.processing || form.questions.length >= 20"
                        >
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Question
                        </Button>
                    </CardContent>
                </Card>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3">
                    <Button
                        as-child
                        variant="outline"
                        :disabled="form.processing"
                    >
                        <Link
                            :href="route('admin.quizzes.index')"
                            @click.prevent="confirmDiscard"
                        >
                            Cancel
                        </Link>
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || (form.status === 'published' && !canPublish)"
                    >
                        <span v-if="form.processing" class="flex items-center">
                            <svg class="mr-2 h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h-8z" />
                            </svg>
                            Saving...
                        </span>
                        <span v-else>Update Quiz</span>
                    </Button>
                </div>
            </form>

            <!-- Discard Confirmation Modal -->
            <Modal :show="showDiscardModal" @close="showDiscardModal = false">
                <div class="p-6 sm:p-8">
                    <h2 class="mb-3 text-xl font-semibold text-white">Discard Changes</h2>
                    <p class="mb-6 text-sm text-gray-600">Are you sure you want to discard your changes? This action cannot be undone.</p>
                    <div class="flex justify-end space-x-3">
                        <Button
                            @click="showDiscardModal = false"
                            variant="outline"
                            :disabled="form.processing"
                        >
                            Cancel
                        </Button>
                        <Button
                            @click="discardChanges"
                            variant="destructive"
                            :disabled="form.processing"
                        >
                            Discard
                        </Button>
                    </div>
                </div>
            </Modal>

            <!-- Toast Container (EXISTING CODE UNCHANGED) -->
            <div v-if="toasts.length > 0" class="fixed top-4 right-4 z-50 space-y-2">
                <div v-for="toast in toasts" :key="toast.id"
                     class="transform transition-all duration-300 ease-in-out"
                     :class="toast.show ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0'">
                    <div class="flex items-center w-full max-w-xs p-4 text-gray-500 bg-white rounded-lg shadow-lg border"
                         :class="toastClasses[toast.type]">
                        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg"
                             :class="toastIconClasses[toast.type]">
                            <svg v-if="toast.type === 'success'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <svg v-else-if="toast.type === 'error'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3 text-sm font-normal">{{ toast.message }}</div>
                        <button @click="removeToast(toast.id)" type="button"
                                class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-white rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { ref, computed, watch, nextTick } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default {
    components: {
        AdminLayout,
        Link,
        Modal,
        Button,
        Input,
        Label,
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
        SelectValue,
        Textarea,
        Card,
        CardContent,
        CardHeader,
        CardTitle,
    },
    props: {
        quiz: {
            type: Object,
            required: true,
        },
        courses: {
            type: Array,
            default: () => [],
        },
        // NEW: Add online courses prop
        onlineCourses: {
            type: Array,
            default: () => [],
        },
    },
    setup(props) {
        const page = usePage();
        const showDiscardModal = ref(false);
        const showSuccessMessage = ref(false);
        const toasts = ref([]);
        const toastIdCounter = ref(0);

        // Toast styling
        const toastClasses = {
            success: 'border-green-200 bg-green-50',
            error: 'border-red-200 bg-red-50',
            info: 'border-blue-200 bg-blue-50'
        };

        const toastIconClasses = {
            success: 'text-green-500 bg-green-100',
            error: 'text-red-500 bg-red-100',
            info: 'text-blue-500 bg-blue-100'
        };

        // Create a reactive reference for server errors
        const serverErrors = ref({});

        // Helper function to safely parse JSON that might be double-encoded
        const safeParseJson = (value) => {
            if (!value) return null;
            if (Array.isArray(value)) return value;
            if (typeof value === 'object') return Object.values(value);
            if (typeof value === 'string') {
                try {
                    let parsed = JSON.parse(value);
                    // Check if it's still a string (double-encoded)
                    while (typeof parsed === 'string') {
                        try {
                            parsed = JSON.parse(parsed);
                        } catch (e) {
                            break;
                        }
                    }
                    return Array.isArray(parsed) ? parsed : Object.values(parsed);
                } catch (e) {
                    return [value];
                }
            }
            return [];
        };

        // Helper function to safely process questions
        const processQuestions = (questions) => {
            if (!questions || !Array.isArray(questions)) {
                return [
                    {
                        question_text: '',
                        type: 'radio',
                        points: 0,
                        options: ['', ''],
                        correct_answer: [],
                        correct_answer_explanation: '',
                    },
                ];
            }

            return questions.map((questionItem) => {
                if (!questionItem) {
                    return {
                        question_text: '',
                        type: 'radio',
                        points: 0,
                        options: ['', ''],
                        correct_answer: [],
                        correct_answer_explanation: '',
                    };
                }

                if (questionItem.type === 'text') {
                    return {
                        id: questionItem.id || null,
                        question_text: questionItem.question_text || '',
                        type: 'text',
                        points: 0,
                        options: [],
                        correct_answer: [],
                        correct_answer_explanation: questionItem.correct_answer_explanation || '',
                    };
                }

                // Process options - handle various formats including double-encoded JSON
                let options = ['', ''];
                if (questionItem.options) {
                    const optionsArray = safeParseJson(questionItem.options) || [];
                    options = optionsArray.map((option) => {
                        if (typeof option === 'string') return option;
                        if (option && typeof option === 'object' && option.option_text) return option.option_text;
                        return '';
                    });
                    
                    // Ensure we have at least 2 options
                    while (options.length < 2) {
                        options.push('');
                    }
                }

                // Process correct_answer - handle various formats including double-encoded JSON
                let correctAnswer = [];
                if (questionItem.correct_answer) {
                    const correctArray = safeParseJson(questionItem.correct_answer) || [];
                    correctAnswer = correctArray.map((answer) => {
                        if (typeof answer === 'string') return answer;
                        if (answer && typeof answer === 'object' && answer.option_text) return answer.option_text;
                        return '';
                    }).filter(ans => ans !== '');
                }

                return {
                    id: questionItem.id || null,
                    question_text: questionItem.question_text || '',
                    type: questionItem.type || 'radio',
                    points: questionItem.points || 0,
                    options: options,
                    correct_answer: correctAnswer,
                    correct_answer_explanation: questionItem.correct_answer_explanation || '',
                };
            });
        };

        // Initialize form with NEW fields
        const form = useForm({
            // NEW: Course type fields
            course_type: props.quiz?.course_type || (props.quiz?.course_online_id ? 'online' : 'regular'),
            course_id: props.quiz?.course_id || '',
            course_online_id: props.quiz?.course_online_id || '',

            // Existing fields
            title: props.quiz?.title || '',
            description: props.quiz?.description || '',
            status: props.quiz?.status || 'draft',
            pass_threshold: props.quiz?.pass_threshold || 80.00,

            // NEW: Deadline fields
            has_deadline: props.quiz?.has_deadline || false,
            deadline_date: props.quiz?.deadline_date || '',
            deadline_time: props.quiz?.deadline_time || '',
            enforce_deadline: props.quiz?.enforce_deadline ?? true,
            time_limit_minutes: props.quiz?.time_limit_minutes || null,
            allows_extensions: props.quiz?.allows_extensions || false,

            // Attempt settings
            max_attempts: props.quiz?.max_attempts || null,
            retry_delay_hours: props.quiz?.retry_delay_hours ?? 0,
            show_correct_answers: props.quiz?.show_correct_answers || 'after_pass',

            questions: processQuestions(props.quiz?.questions),
        });

        const showCorrectAnswersOptions = [
            { value: 'never', label: 'Never show correct answers' },
            { value: 'after_pass', label: 'Show after passing' },
            { value: 'after_max_attempts', label: 'Show after all attempts used' },
            { value: 'always', label: 'Always show correct answers' },
        ];

        // NEW: Computed property for selected course ID
        const selectedCourseId = computed({
            get() {
                return form.course_type === 'regular' ? form.course_id : form.course_online_id;
            },
            set(value) {
                if (form.course_type === 'regular') {
                    form.course_id = value;
                    form.course_online_id = '';
                } else {
                    form.course_online_id = value;
                    form.course_id = '';
                }
            }
        });

        // NEW: Reset course selection when type changes
        const resetCourseSelection = () => {
            form.course_id = '';
            form.course_online_id = '';
        };

        // Watch for server errors from Inertia page props
        watch(
            () => page.props.errors,
            (newErrors) => {
                serverErrors.value = newErrors || {};
            },
            { immediate: true, deep: true }
        );

        // Combined errors from both form and server
        const allErrors = computed(() => {
            return { ...form.errors, ...serverErrors.value };
        });

        // Computed properties
        const hasErrors = computed(() => {
            return Object.keys(allErrors.value).length > 0;
        });

        // Publishing requirements validation
        const publishingChecklist = computed(() => {
            return {
                hasTitle: !!form.title?.trim(),
                hasCourse: !!(form.course_id || form.course_online_id), // UPDATED: Check both course types
                hasValidThreshold: form.pass_threshold >= 0 && form.pass_threshold <= 100,
                hasQuestions: form.questions.length > 0,
                allQuestionsValid: form.questions.every(questionItem => {
                    if (!questionItem.question_text?.trim()) return false;
                    if (questionItem.type === 'text') return true;
                    if (!questionItem.points || questionItem.points <= 0) return false;
                    if (!questionItem.options || questionItem.options.length < 2) return false;
                    if (!questionItem.options.every(opt => opt?.trim())) return false;
                    if (!questionItem.correct_answer || questionItem.correct_answer.length === 0) return false;
                    return true;
                })
            };
        });

        const canPublish = computed(() => {
            return Object.values(publishingChecklist.value).every(Boolean);
        });

        const hasPublishingRequirements = computed(() => {
            return !canPublish.value;
        });

        const errorSummary = computed(() => {
            const errorCount = Object.keys(allErrors.value).length;
            if (errorCount === 0) return { title: '', message: '' };

            return {
                title: errorCount === 1 ? 'Please fix the following issue:' : `Please fix the following ${errorCount} issues:`,
                message: 'Review the highlighted fields below and correct any errors before saving.'
            };
        });

        // Toast management (EXISTING CODE UNCHANGED)
        const addToast = (message, type = 'info', duration = 5000) => {
            const id = ++toastIdCounter.value;
            const toast = { id, message, type, show: false };
            toasts.value.push(toast);

            nextTick(() => {
                const currentToastIndex = toasts.value.findIndex(t => t.id === id);
                if (currentToastIndex !== -1) {
                    toasts.value[currentToastIndex].show = true;
                }
            });

            if (duration > 0) {
                setTimeout(() => removeToast(id), duration);
            }

            return id;
        };

        const removeToast = (id) => {
            const currentToastIndex = toasts.value.findIndex(toast => toast.id === id);
            if (currentToastIndex !== -1) {
                toasts.value[currentToastIndex].show = false;
                setTimeout(() => {
                    const index = toasts.value.findIndex(toast => toast.id === id);
                    if (index !== -1) {
                        toasts.value.splice(index, 1);
                    }
                }, 300);
            }
        };

        // Helper functions (EXISTING CODE UNCHANGED)
        const formatFieldName = (field) => {
            return field
                .replace(/_/g, ' ')
                .replace(/\b\w/g, l => l.toUpperCase())
                .replace(/questions\.(\d+)\./, 'Question $1 ');
        };

        const getFieldError = (fieldName) => {
            const error = allErrors.value[fieldName];
            if (Array.isArray(error)) {
                return error[0];
            }
            return error || null;
        };

        const hasQuestionErrors = (index) => {
            const questionFields = [
                `questions.${index}.question_text`,
                `questions.${index}.type`,
                `questions.${index}.points`,
                `questions.${index}.options`,
                `questions.${index}.correct_answer`
            ];
            return questionFields.some(field => getFieldError(field));
        };

        const scrollToFirstError = () => {
            nextTick(() => {
                const firstError = document.querySelector('.text-red-600, .border-red-300');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        };

        // Question management functions (EXISTING CODE UNCHANGED)
        const addQuestion = () => {
            try {
                if (form.questions.length < 20) {
                    form.questions.push({
                        question_text: '',
                        type: 'radio',
                        points: 0,
                        options: ['', ''],
                        correct_answer: [],
                        correct_answer_explanation: '',
                    });
                }
            } catch (error) {
                console.error('Error adding question:', error);
                addToast('Failed to add question. Please try again.', 'error');
            }
        };

        const removeQuestion = (index) => {
            try {
                if (form.questions.length > 1) {
                    form.questions.splice(index, 1);
                }
            } catch (error) {
                console.error('Error removing question:', error);
                addToast('Failed to remove question. Please try again.', 'error');
            }
        };

        const addOption = (questionIndex) => {
            try {
                if (form.questions[questionIndex].options.length < 10) {
                    form.questions[questionIndex].options.push('');
                }
            } catch (error) {
                console.error('Error adding option:', error);
                addToast('Failed to add option. Please try again.', 'error');
            }
        };

        const removeOption = (questionIndex, optionIndex) => {
            try {
                const currentQuestion = form.questions[questionIndex];
                if (currentQuestion.options.length > 2) {
                    const removedOption = currentQuestion.options[optionIndex];
                    currentQuestion.options.splice(optionIndex, 1);

                    if (Array.isArray(currentQuestion.correct_answer)) {
                        currentQuestion.correct_answer = currentQuestion.correct_answer.filter(opt => opt !== removedOption);
                    }
                }
            } catch (error) {
                console.error('Error removing option:', error);
                addToast('Failed to remove option. Please try again.', 'error');
            }
        };

        const resetQuestionOptions = (index) => {
            try {
                const currentQuestion = form.questions[index];

                if (currentQuestion.type === 'text') {
                    currentQuestion.options = [];
                    currentQuestion.correct_answer = [];
                    currentQuestion.points = 0;
                } else {
                    if (!currentQuestion.options || currentQuestion.options.length < 2) {
                        currentQuestion.options = ['', ''];
                    }
                    currentQuestion.correct_answer = [];
                }
                currentQuestion.correct_answer_explanation = '';
            } catch (error) {
                console.error('Error resetting question options:', error);
                addToast('Failed to reset question options. Please try again.', 'error');
            }
        };

        const updateCorrectAnswer = (index, option) => {
            try {
                const currentQuestion = form.questions[index];

                if (currentQuestion.type === 'radio') {
                    currentQuestion.correct_answer = [option];
                }
            } catch (error) {
                console.error('Error updating correct answer:', error);
                addToast('Failed to update correct answer. Please try again.', 'error');
            }
        };

        // UPDATED: Enhanced form submission with new fields
        const submitQuiz = () => {
            try {
                // Clear previous server errors and success messages
                serverErrors.value = {};
                showSuccessMessage.value = false;

                // Client-side validation for publishing
                if (form.status === 'published' && !canPublish.value) {
                    addToast('Cannot publish quiz. Please fix all required fields first.', 'error', 8000);
                    scrollToFirstError();
                    return;
                }

                // UPDATED: Enhanced form data with new fields
                form.transform((data) => ({
                    // Course assignment fields
                    course_type: form.course_type,
                    course_id: form.course_type === 'regular' ? form.course_id : null,
                    course_online_id: form.course_type === 'online' ? form.course_online_id : null,

                    // Basic quiz fields
                    title: form.title,
                    description: form.description,
                    status: form.status,
                    pass_threshold: form.pass_threshold,

                    // Deadline fields
                    has_deadline: form.has_deadline,
                    deadline_date: form.has_deadline ? form.deadline_date : null,
                    deadline_time: form.has_deadline ? form.deadline_time : null,
                    enforce_deadline: form.has_deadline ? form.enforce_deadline : true,
                    time_limit_minutes: form.time_limit_minutes || null,
                    allows_extensions: form.has_deadline ? form.allows_extensions : false,

                    // Attempt settings
                    max_attempts: form.max_attempts || null,
                    retry_delay_hours: form.retry_delay_hours ?? 0,
                    show_correct_answers: form.show_correct_answers,

                    // Questions
                    questions: form.questions.map((question) => ({
                        id: question.id || null,
                        question_text: question.question_text,
                        type: question.type,
                        points: question.type === 'text' ? 0 : question.points,
                        options: question.options ? question.options.filter(opt => opt && opt.trim()) : [],
                        correct_answer: Array.isArray(question.correct_answer)
                            ? question.correct_answer.filter(ans => ans && ans.trim())
                            : question.correct_answer ? [question.correct_answer] : [],
                        correct_answer_explanation: question.correct_answer_explanation || '',
                    })),
                })).put(route('admin.quizzes.update', props.quiz.id), {
                    preserveState: true,
                    preserveScroll: true,
                    onStart: () => {
                        toasts.value.forEach(toast => removeToast(toast.id));
                    },
                    onSuccess: (page) => {
                        showSuccessMessage.value = true;
                        addToast('Quiz updated successfully!', 'success', 4000);

                        setTimeout(() => {
                            router.visit(route('admin.quizzes.index'), { replace: true });
                        }, 1500);
                    },
                    onError: (errors) => {
                        serverErrors.value = errors;

                        const errorCount = Object.keys(errors).length;
                        const errorMessage = errorCount === 1
                            ? 'Please correct the error below.'
                            : `Please correct the ${errorCount} errors below.`;

                        addToast(errorMessage, 'error', 8000);

                        setTimeout(() => {
                            scrollToFirstError();
                        }, 100);
                    },
                    onFinish: () => {
                        // Optional: Add any cleanup logic here
                    },
                });
            } catch (error) {
                console.error('Critical error in submitQuiz:', error);
                addToast('An unexpected error occurred. Please try again.', 'error');
            }
        };

        // Discard functionality (EXISTING CODE UNCHANGED)
        const confirmDiscard = () => {
            if (form.isDirty) {
                showDiscardModal.value = true;
            } else {
                discardChanges();
            }
        };

        const discardChanges = () => {
            form.reset();
            serverErrors.value = {};
            showDiscardModal.value = false;
            showSuccessMessage.value = false;
            router.visit(route('admin.quizzes.index'));
        };

        const breadcrumbs = [
            { name: 'Quizzes', route: 'admin.quizzes.index' },
            { name: 'Edit', route: null },
        ];

        return {
            form,
            hasErrors,
            allErrors,
            errorSummary,
            publishingChecklist,
            canPublish,
            hasPublishingRequirements,
            showDiscardModal,
            showSuccessMessage,
            toasts,
            toastClasses,
            toastIconClasses,

            // NEW: Course type functionality
            selectedCourseId,
            resetCourseSelection,

            formatFieldName,
            getFieldError,
            hasQuestionErrors,
            scrollToFirstError,
            addQuestion,
            removeQuestion,
            addOption,
            removeOption,
            resetQuestionOptions,
            updateCorrectAnswer,
            submitQuiz,
            confirmDiscard,
            discardChanges,
            showCorrectAnswersOptions,
            addToast,
            removeToast,
            breadcrumbs,
        };
    },
};
</script>

<style scoped>
/* EXISTING STYLES UNCHANGED */
.max-w-7xl {
    @apply px-4 sm:px-6 lg:px-8;
}

form {
    @apply space-y-6;
}

input,
select,
textarea {
    @apply transition-colors duration-200;
}

input:disabled,
select:disabled,
textarea:disabled,
button:disabled {
    @apply cursor-not-allowed opacity-50;
}

.bg-gray-50 {
    @apply transition-all duration-200;
}

button,
a {
    @apply transition-colors duration-200;
}

.text-red-600 {
    @apply text-red-600 font-medium;
}

.border-red-300 {
    @apply border-red-300 shadow-sm;
}

.border-red-300:focus {
    @apply border-red-500 ring-2 ring-red-200;
}

.text-green-600 {
    @apply text-green-600 font-medium;
}

input:focus,
select:focus,
textarea:focus {
    @apply ring-2 ring-offset-2;
}

.transform {
    transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
}

.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

button:hover:not(:disabled) {
    @apply transform scale-105;
}

@media (max-width: 640px) {
    .grid-cols-1 {
        @apply space-y-4;
    }

    .sm:col-span-2 {
        @apply col-span-1;
    }

    .fixed.top-4.right-4 {
        @apply top-2 right-2 left-2;
    }

    .max-w-xs {
        @apply max-w-none;
    }
}
</style>
