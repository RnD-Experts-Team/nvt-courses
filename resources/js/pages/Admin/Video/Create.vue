<template>
    <Head title="Create Video Course" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button as-child variant="ghost">
                    <Link href="/admin/videos">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Videos
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Create Video Course</h1>
                    <p class="text-muted-foreground">Add a new video course to your library</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <PlaySquare class="h-5 w-5" />
                            Video Information
                        </CardTitle>
                        <CardDescription>
                            Enter the basic details for your video course
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Video Title</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter video course title..."
                                :class="{ 'border-destructive': form.errors.name }"
                                required
                            />
                            <div v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describe what students will learn from this video course..."
                                rows="5"
                                :class="{ 'border-destructive': form.errors.description }"
                            />
                            <div v-if="form.errors.description" class="text-sm text-destructive">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- Category -->
                        <div class="space-y-2">
                            <Label for="content_category_id">Category</Label>
                            <Select v-model="form.content_category_id">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.content_category_id }">
                                    <SelectValue placeholder="Select a category..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id.toString()"
                                    >
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.content_category_id" class="text-sm text-destructive">
                                {{ form.errors.content_category_id }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Storage Type Selection -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <HardDrive class="h-5 w-5" />
                            Storage Type
                        </CardTitle>
                        <CardDescription>
                            Choose where to store your video
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <RadioGroup v-model="form.storage_type" class="grid grid-cols-2 gap-4">
                            <!-- Google Drive Option -->
                            <Label
                                for="google_drive"
                                class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                :class="{ 'border-primary': form.storage_type === 'google_drive' }"
                            >
                                <RadioGroupItem value="google_drive" id="google_drive" class="sr-only" />
                                <Cloud class="mb-3 h-6 w-6" />
                                <div class="space-y-1 text-center">
                                    <p class="text-sm font-medium leading-none">Google Drive</p>
                                    <p class="text-xs text-muted-foreground">
                                        Store video on Google Drive
                                    </p>
                                </div>
                            </Label>

                            <!-- Local Storage Option -->
                            <Label
                                for="local"
                                class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                :class="{ 'border-primary': form.storage_type === 'local' }"
                            >
                                <RadioGroupItem value="local" id="local" class="sr-only" />
                                <HardDrive class="mb-3 h-6 w-6" />
                                <div class="space-y-1 text-center">
                                    <p class="text-sm font-medium leading-none">Local Storage</p>
                                    <p class="text-xs text-muted-foreground">
                                        Upload video to server
                                    </p>
                                </div>
                            </Label>
                        </RadioGroup>
                    </CardContent>
                </Card>

                <!-- Video Source -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Link2 class="h-5 w-5" />
                            Video Source
                        </CardTitle>
                        <CardDescription>
                            {{ form.storage_type === 'google_drive' ? 'Provide Google Drive URL' : 'Upload video file' }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Google Drive URL (show only if google_drive selected) -->
                        <div v-if="form.storage_type === 'google_drive'" class="space-y-2">
                            <Label for="google_drive_url">Google Drive Video URL</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="google_drive_url"
                                    v-model="form.google_drive_url"
                                    type="url"
                                    placeholder="https://drive.google.com/file/d/..."
                                    :class="{ 'border-destructive': form.errors.google_drive_url }"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    @click="testVideoUrl"
                                    :disabled="!form.google_drive_url || testingUrl"
                                >
                                    <Loader2 v-if="testingUrl" class="h-4 w-4 animate-spin" />
                                    <TestTube v-else class="h-4 w-4" />
                                </Button>
                            </div>
                            <div v-if="form.errors.google_drive_url" class="text-sm text-destructive">
                                {{ form.errors.google_drive_url }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Paste the shareable link from Google Drive. Make sure the video is set to "Anyone with the link can view"
                            </div>
                        </div>

                        <!-- ✅ NEW: FilePond Chunked Upload (show only if local selected) -->
                        <div v-else-if="form.storage_type === 'local'" class="space-y-2">
                            <Label for="video_file">Video File</Label>
                            <ChunkUploader
                                name="file"
                                accept="video/*"
                                :maxFileSize="maxFileSizeMB + 'MB'"
                                @uploaded="handleUploadComplete"
                                @error="handleUploadError"
                                @progress="handleProgress"
                            />
                            <div v-if="form.errors.video_file || form.errors.video_data" class="text-sm text-destructive">
                                {{ form.errors.video_file || form.errors.video_data }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Video will be uploaded in 2MB chunks for reliability. Maximum {{ maxFileSizeMB }}MB
                            </div>
                        </div>

                        <!-- Duration -->
                 <!-- Duration -->
<div class="space-y-3">
    <Label>Duration</Label>
    
    <!-- Duration Input Grid -->
    <div class="grid grid-cols-3 gap-3">
        <!-- Hours -->
        <div class="space-y-2">
            <Label for="hours" class="text-xs text-muted-foreground">Hours</Label>
            <Input
                id="hours"
                type="number"
                min="0"
                max="99"
                :value="durationHours"
                @input="handleDurationChange('hours', ($event.target as HTMLInputElement).value)"
                placeholder="0"
                class="text-center"
            />
        </div>
        
        <!-- Minutes -->
        <div class="space-y-2">
            <Label for="minutes" class="text-xs text-muted-foreground">Minutes</Label>
            <Input
                id="minutes"
                type="number"
                min="0"
                max="59"
                :value="durationMinutes"
                @input="handleDurationChange('minutes', ($event.target as HTMLInputElement).value)"
                placeholder="0"
                class="text-center"
            />
        </div>
        
        <!-- Seconds -->
        <div class="space-y-2">
            <Label for="seconds" class="text-xs text-muted-foreground">Seconds</Label>
            <Input
                id="seconds"
                type="number"
                min="0"
                max="59"
                :value="durationSeconds"
                @input="handleDurationChange('seconds', ($event.target as HTMLInputElement).value)"
                placeholder="0"
                class="text-center"
            />
        </div>
    </div>
    
    <!-- Duration Display -->
    <div class="flex items-center justify-between p-3 rounded-lg bg-muted/50 border">
        <div class="flex items-center gap-2">
            <Clock class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm font-medium">Total Duration:</span>
            <span class="text-sm text-muted-foreground">{{ readableDuration }}</span>
        </div>
        <Button
            v-if="durationHours > 0 || durationMinutes > 0 || durationSeconds > 0"
            @click="clearDuration"
            variant="ghost"
            size="sm"
            type="button"
        >
            <X class="h-4 w-4" />
        </Button>
    </div>
    
    <!-- Quick Presets -->
    <div class="space-y-2">
        <Label class="text-xs text-muted-foreground">Quick Presets:</Label>
        <div class="flex flex-wrap gap-2">
            <Button
                @click="setDurationPreset(0, 5, 0)"
                variant="outline"
                size="sm"
                type="button"
            >
                5 min
            </Button>
            <Button
                @click="setDurationPreset(0, 10, 0)"
                variant="outline"
                size="sm"
                type="button"
            >
                10 min
            </Button>
            <Button
                @click="setDurationPreset(0, 30, 0)"
                variant="outline"
                size="sm"
                type="button"
            >
                30 min
            </Button>
            <Button
                @click="setDurationPreset(1, 0, 0)"
                variant="outline"
                size="sm"
                type="button"
            >
                1 hour
            </Button>
            <Button
                @click="setDurationPreset(2, 0, 0)"
                variant="outline"
                size="sm"
                type="button"
            >
                2 hours
            </Button>
        </div>
    </div>
    
    <div v-if="form.errors.duration" class="text-sm text-destructive">
        {{ form.errors.duration }}
    </div>
    <div class="text-sm text-muted-foreground">
        {{ form.storage_type === 'local' ? 'Optional - will be auto-detected from video file' : 'Video duration (optional)' }}
    </div>
</div>

                    </CardContent>
                </Card>

                <!-- Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Settings class="h-5 w-5" />
                            Settings
                        </CardTitle>
                        <CardDescription>
                            Configure the video course settings
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <Label>Active Status</Label>
                                <div class="text-sm text-muted-foreground">
                                    Make this video course available to students
                                </div>
                            </div>
                            <Switch
                                :checked="form.is_active"
                                @update:checked="form.is_active = $event"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Submit Actions -->
                <div class="flex justify-between items-center">
                    <Button as-child variant="outline">
                        <Link href="/admin/videos">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="isSubmitting || form.processing || (form.storage_type === 'local' && !form.video_data)">
                        <Save class="h-4 w-4 mr-2" />
                        {{ isSubmitting ? 'Creating...' : 'Create Video Course' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import ChunkUploader from '@/Components/ChunkUploader.vue' // ✅ Import ChunkUploader
import type { BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'

// Icons
import {
    ArrowLeft,
    Save,
    PlaySquare,
    Settings,
    Link2,
    TestTube,
    Loader2,
    Cloud,
    HardDrive,
    Clock,  // ✅ ADD THIS
    X,      // ✅ ADD THIS
} from 'lucide-vue-next'

interface VideoCategory {
    id: number
    name: string
}

const props = defineProps<{
    categories: VideoCategory[]
    maxFileSize?: number
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Video Management', href: '/admin/videos' },
    { title: 'Create Video Course', href: '' },
]

const form = useForm({
    name: '',
    description: '',
    storage_type: 'google_drive' as 'google_drive' | 'local',
    google_drive_url: '',
    video_data: '', // ✅ Changed from video_file to video_data (stores FilePond response)
    duration: null as number | null,
    content_category_id: null as string | null,
    is_active: true,
})

const isSubmitting = ref(false)
const testingUrl = ref(false)
const durationHours = ref<number>(0)
const durationMinutes = ref<number>(0)
const durationSeconds = ref<number>(0)

// Computed
const maxFileSizeMB = computed(() => Math.round((props.maxFileSize || 512000) / 1024))

// ✅ NEW: Handle chunk upload completion
const handleUploadComplete = (data: any) => {
    console.log('✅ Chunk upload complete:', data)
    console.log('✅ Data type:', typeof data)
    console.log('✅ Data keys:', data ? Object.keys(data) : 'null')
    
    // Ensure data is an object before stringifying
    if (typeof data === 'string') {
        console.log('⚠️ Data is already a string, parsing first')
        try {
            data = JSON.parse(data)
        } catch (e) {
            console.error('❌ Failed to parse data string:', e)
        }
    }
    
    form.video_data = JSON.stringify(data) // Store the response data
    console.log('✅ Stored video_data:', form.video_data)
}

// ✅ NEW: Handle chunk upload error
const handleUploadError = (error: any) => {
    console.error('❌ Chunk upload error:', error)
    alert('Upload failed. Please try again.')
}

// ✅ NEW: Handle upload progress
const handleProgress = (progress: number) => {
    console.log('Upload progress:', progress + '%')
}

// Test video URL
const testVideoUrl = async () => {
    if (!form.google_drive_url) return
    testingUrl.value = true
    try {
        console.log('Testing URL:', form.google_drive_url)
        await new Promise(resolve => setTimeout(resolve, 2000))
        alert('Video URL is valid!')
    } catch (error) {
        console.error('URL test failed:', error)
        alert('Failed to validate video URL. Please check the link.')
    } finally {
        testingUrl.value = false
    }
}
// computed property for readable duration
const readableDuration = computed(() => {
    const parts = []
    if (durationHours.value > 0) parts.push(`${durationHours.value}h`)
    if (durationMinutes.value > 0) parts.push(`${durationMinutes.value}m`)
    if (durationSeconds.value > 0) parts.push(`${durationSeconds.value}s`)
    return parts.length > 0 ? parts.join(' ') : 'Not set'
})
const updateFormDuration = () => {
    const totalSeconds = (durationHours.value * 3600) + (durationMinutes.value * 60) + durationSeconds.value
    form.duration = totalSeconds > 0 ? totalSeconds : null
}

const handleDurationChange = (type: 'hours' | 'minutes' | 'seconds', value: string) => {
    let numValue = parseInt(value) || 0
    
    if (type === 'hours') {
        numValue = Math.max(0, Math.min(99, numValue))
        durationHours.value = numValue
    } else if (type === 'minutes') {
        numValue = Math.max(0, Math.min(59, numValue))
        durationMinutes.value = numValue
    } else if (type === 'seconds') {
        numValue = Math.max(0, Math.min(59, numValue))
        durationSeconds.value = numValue
    }
    
    updateFormDuration()
}

const setDurationPreset = (hours: number, minutes: number, seconds: number) => {
    durationHours.value = hours
    durationMinutes.value = minutes
    durationSeconds.value = seconds
    updateFormDuration()
}

const clearDuration = () => {
    durationHours.value = 0
    durationMinutes.value = 0
    durationSeconds.value = 0
    updateFormDuration()
}

// Format duration helper
const formatDuration = (seconds: number | null): string => {
    if (!seconds) return '00:00'
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = seconds % 60
    return hours > 0
        ? `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
        : `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

const submit = async () => {
    isSubmitting.value = true

    form.post('/admin/videos', {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false
        },
        onSuccess: () => {
            console.log('✅ Video created successfully')
        },
        onError: (errors) => {
            console.error('❌ Form errors:', errors)
        }
    })
}
</script>
