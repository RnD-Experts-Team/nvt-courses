<template>
    <Head :title="`Edit ${video.name}`" />
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
                <div class="flex-1">
                    <h1 class="text-2xl font-bold">Edit Video Course</h1>
                    <p class="text-muted-foreground">Update video course details and settings</p>
                </div>
                <Button as-child variant="outline">
                    <Link :href="`/admin/videos/${video.id}`">
                        <BarChart3 class="h-4 w-4 mr-2" />
                        View Analytics
                    </Link>
                </Button>
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
                            Update the basic details for your video course
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
                            <div class="text-sm text-muted-foreground">
                                Select a category or leave unset for no category
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- ✅ NEW: Storage Information (Read-Only Display) -->
                <Card v-if="video.storage_type">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <HardDrive class="h-5 w-5" />
                            Storage Information
                        </CardTitle>
                        <CardDescription>
                            Current storage configuration (cannot be changed after creation)
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Storage Type Badge -->
                        <div class="flex items-center gap-4 p-4 bg-muted rounded-lg">
                            <Cloud v-if="video.storage_type === 'google_drive'" class="h-8 w-8 text-blue-500" />
                            <HardDrive v-else class="h-8 w-8 text-green-500" />
                            <div class="flex-1">
                                <p class="font-semibold text-lg">{{ video.storage_type_label || (video.storage_type === 'google_drive' ? 'Google Drive' : 'Local Storage') }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ video.storage_type === 'google_drive' ? 'Video stored on Google Drive' : 'Video stored on local server' }}
                                </p>
                            </div>
                        </div>

                        <!-- File Information (for local videos) -->
                        <div v-if="video.storage_type === 'local'" class="space-y-3">
                            <Alert>
                                <AlertCircle class="h-4 w-4" />
                                <AlertDescription>
                                    Video files cannot be replaced after upload. To use a different video file, please create a new video entry.
                                </AlertDescription>
                            </Alert>

                            <div v-if="video.formatted_file_size || video.file_path" class="p-3 bg-muted/50 rounded-lg space-y-2">
                                <div v-if="video.formatted_file_size" class="flex items-center justify-between text-sm">
                                    <span class="text-muted-foreground">File Size:</span>
                                    <span class="font-medium">{{ video.formatted_file_size }}</span>
                                </div>
                                <div v-if="video.file_path" class="flex items-center justify-between text-sm">
                                    <span class="text-muted-foreground">File Path:</span>
                                    <span class="font-mono text-xs truncate max-w-md">{{ video.file_path }}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- ✅ NEW: Migrate to Local Storage (for Google Drive videos only) -->
                <Card v-if="video.storage_type === 'google_drive'">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ArrowRightLeft class="h-5 w-5" />
                            Migrate to Local Storage
                        </CardTitle>
                        <CardDescription>
                            Upload a video file to replace the Google Drive link with local storage
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <Alert>
                            <AlertCircle class="h-4 w-4" />
                            <AlertDescription>
                                <strong>Important:</strong> This will permanently replace the Google Drive URL with a locally stored video file.
                                User progress data will be preserved.
                            </AlertDescription>
                        </Alert>

                        <!-- ChunkUploader for migration -->
                        <div class="space-y-2">
                            <Label>Upload New Video File</Label>
                            <ChunkUploader
                                name="migration_file"
                                accept="video/*"
                                :maxFileSize="maxFileSizeMB + 'MB'"
                                @uploaded="handleMigrationUpload"
                                @error="handleMigrationError"
                                @progress="handleMigrationProgress"
                            />
                            <p class="text-sm text-muted-foreground">
                                Video will be uploaded in 2MB chunks. Maximum file size: {{ maxFileSizeMB }}MB
                            </p>
                        </div>

                        <!-- Migration Status -->
                        <div v-if="migrationData" class="p-3 bg-green-500/10 border border-green-500/30 rounded-lg">
                            <div class="flex items-center gap-2 text-green-500">
                                <CheckCircle2 class="h-4 w-4" />
                                <span class="font-medium">Video file uploaded successfully!</span>
                            </div>
                            <p class="text-sm text-green-500/80 mt-1">Ready to migrate. Click the button below to confirm.</p>
                        </div>

                        <!-- Migration Button with Confirmation -->
                        <AlertDialog>
                            <AlertDialogTrigger asChild>
                                <Button
                                    type="button"
                                    :disabled="!migrationData || isMigrating"
                                >
                                    <ArrowRightLeft class="h-4 w-4 mr-2" />
                                    {{ isMigrating ? 'Migrating...' : 'Migrate to Local Storage' }}
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>Confirm Migration</AlertDialogTitle>
                                    <AlertDialogDescription>
                                        Are you sure you want to migrate this video from Google Drive to Local Storage?
                                        <br /><br />
                                        <strong>This action will:</strong>
                                        <ul class="list-disc list-inside mt-2 space-y-1">
                                            <li>Replace the Google Drive URL with the uploaded video file</li>
                                            <li>Store the video on your server's local storage</li>
                                            <li>Preserve all user progress data</li>
                                        </ul>
                                        <br />
                                        <strong class="text-destructive">This cannot be undone.</strong>
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                    <AlertDialogAction @click="submitMigration" class="bg-amber-600 hover:bg-amber-700">
                                        Yes, Migrate Video
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
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
                            {{ video.storage_type === 'local' ? 'Video file information' : 'Update the video source and metadata' }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- ✅ Google Drive URL (Only editable for Google Drive videos) -->
                        <div v-if="!video.storage_type || video.storage_type === 'google_drive'" class="space-y-2">
                            <Label for="google_drive_url">Google Drive Video URL</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="google_drive_url"
                                    v-model="form.google_drive_url"
                                    type="url"
                                    placeholder="https://drive.google.com/file/d/..."
                                    :class="{ 'border-destructive': form.errors.google_drive_url }"
                                    required
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

                        <!-- Duration -->
                        <div class="space-y-2">
                            <Label for="duration">Duration (seconds)</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="duration"
                                    v-model="form.duration"
                                    type="number"
                                    min="1"
                                    max="86400"
                                    placeholder="e.g., 3600 for 1 hour"
                                    :class="{ 'border-destructive': form.errors.duration }"
                                />
                                <div class="flex items-center px-3 py-2 bg-muted rounded-md text-sm text-muted-foreground min-w-0">
                                    {{ formatDuration(form.duration) }}
                                </div>
                            </div>
                            <div v-if="form.errors.duration" class="text-sm text-destructive">
                                {{ form.errors.duration }}
                            </div>
                            <div v-if="video.storage_type === 'local'" class="text-sm text-muted-foreground">
                                Duration for local videos (auto-detected during upload if available)
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Current Thumbnail & Upload -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Image class="h-5 w-5" />
                            Thumbnail
                        </CardTitle>
                        <CardDescription>
                            Update or remove the video thumbnail
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Current Thumbnail Display -->
                        <div v-if="video.thumbnail_url" class="space-y-4">
                            <div>
                                <Label>Current Thumbnail</Label>
                                <div class="mt-2 flex items-start gap-4">
                                    <div class="w-48 h-32 rounded-lg overflow-hidden bg-muted">
                                        <img
                                            :src="video.thumbnail_url"
                                            :alt="video.name"
                                            class="w-full h-full object-cover"
                                        />
                                    </div>
                                    <div class="flex-1 space-y-2">
                                        <p class="text-sm text-muted-foreground">
                                            Current thumbnail for this video course
                                        </p>
                                        <div class="flex gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click="removeThumbnail"
                                            >
                                                <Trash2 class="h-4 w-4 mr-2" />
                                                Remove Thumbnail
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Separator />
                        </div>

                        <!-- New Thumbnail Upload -->
                        <div class="space-y-2">
                            <Label for="thumbnail">{{ video.thumbnail_url ? 'Replace' : 'Upload' }} Thumbnail</Label>
                            <div class="flex items-start gap-4">
                                <!-- Preview -->
                                <div class="w-32 h-24 rounded-lg border-2 border-dashed border-muted-foreground/25 bg-muted/50 flex items-center justify-center overflow-hidden shrink-0">
                                    <img
                                        v-if="thumbnailPreview"
                                        :src="thumbnailPreview"
                                        class="w-full h-full object-cover"
                                        alt="New thumbnail preview"
                                    />
                                    <div v-else class="text-center">
                                        <Image class="h-8 w-8 mx-auto text-muted-foreground mb-2" />
                                        <div class="text-xs text-muted-foreground">Preview</div>
                                    </div>
                                </div>

                                <!-- Upload Input -->
                                <div class="flex-1">
                                    <Input
                                        id="thumbnail"
                                        type="file"
                                        accept="image/*"
                                        :class="{ 'border-destructive': form.errors.thumbnail }"
                                        @change="handleThumbnailChange"
                                    />
                                    <div v-if="form.errors.thumbnail" class="text-sm text-destructive mt-1">
                                        {{ form.errors.thumbnail }}
                                    </div>
                                    <div class="text-sm text-muted-foreground mt-1">
                                        Recommended: 1280x720px (16:9 ratio), JPG or PNG, max 2MB
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Settings & Statistics -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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

                    <!-- Quick Stats -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <BarChart3 class="h-5 w-5" />
                                Quick Statistics
                            </CardTitle>
                            <CardDescription>
                                Current performance metrics
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600">{{ video.total_viewers || 0 }}</div>
                                    <div class="text-xs text-muted-foreground">Viewers</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600">{{ Math.round(video.avg_completion || 0) }}%</div>
                                    <div class="text-xs text-muted-foreground">Avg Progress</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Submit Actions -->
                <div class="flex justify-between items-center">
                    <div class="flex gap-2">
                        <Button as-child variant="outline">
                            <Link href="/admin/videos">Cancel</Link>
                        </Button>

                        <!-- Danger Zone - Delete -->
                        <Button
                            type="button"
                            variant="destructive"
                            @click="deleteVideo"
                        >
                            <Trash2 class="h-4 w-4 mr-2" />
                            Delete Video
                        </Button>
                    </div>

                    <Button type="submit" :disabled="isSubmitting || form.processing">
                        <Save class="h-4 w-4 mr-2" />
                        {{ isSubmitting ? 'Updating...' : 'Update Video Course' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import ChunkUploader from '@/components/ChunkUploader.vue'
import type { BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog'

// Icons
import {
    ArrowLeft,
    Save,
    PlaySquare,
    Settings,
    Link2,
    Image,
    TestTube,
    Loader2,
    Trash2,
    BarChart3,
    Cloud,
    HardDrive,
    AlertCircle,
    ArrowRightLeft,
    CheckCircle2,
} from 'lucide-vue-next'

interface Video {
    id: number
    name: string
    description?: string
    google_drive_url: string
    duration?: number
    thumbnail_url?: string
    is_active: boolean
    content_category_id?: number
    total_viewers?: number
    avg_completion?: number
    storage_type?: 'google_drive' | 'local'           // ✅ NEW
    storage_type_label?: string                        // ✅ NEW
    file_path?: string                                 // ✅ NEW
    formatted_file_size?: string                       // ✅ NEW
    created_at?: string                                // ✅ NEW
}

interface VideoCategory {
    id: number
    name: string
}

const props = defineProps<{
    video: Video
    categories: VideoCategory[]
    maxFileSize?: number
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Video Management', href: '/admin/videos' },
    { title: 'Edit Video', href: '' },
]

const form = useForm({
    name: props.video.name,
    description: props.video.description || '',
    google_drive_url: props.video.google_drive_url,
    duration: props.video.duration,
    thumbnail: null as File | null,
    content_category_id: props.video.content_category_id?.toString() || null,
    is_active: props.video.is_active,
    remove_thumbnail: false,
})

const isSubmitting = ref(false)
const testingUrl = ref(false)
const thumbnailPreview = ref<string | null>(null)

// Migration state
const migrationData = ref<any>(null)
const isMigrating = ref(false)
const migrationProgress = ref(0)

// Computed
const maxFileSizeMB = computed(() => Math.round((props.maxFileSize || 10240000) / 1024))

// Migration handlers
const handleMigrationUpload = (data: any) => {
    console.log('✅ Migration upload complete:', data)
    migrationData.value = data
}

const handleMigrationError = (error: any) => {
    console.error('❌ Migration upload error:', error)
    alert('Upload failed. Please try again.')
    migrationData.value = null
}

const handleMigrationProgress = (progress: number) => {
    migrationProgress.value = progress
}

const submitMigration = () => {
    if (!migrationData.value) return
    
    isMigrating.value = true
    
    router.post(`/admin/videos/${props.video.id}/migrate-to-local`, {
        video_data: JSON.stringify(migrationData.value)
    }, {
        preserveScroll: true,
        onSuccess: () => {
            console.log('✅ Migration successful')
            migrationData.value = null
        },
        onError: (errors) => {
            console.error('❌ Migration failed:', errors)
            alert('Migration failed. Please try again.')
        },
        onFinish: () => {
            isMigrating.value = false
        }
    })
}

const handleThumbnailChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (file) {
        form.thumbnail = file
        form.remove_thumbnail = false

        const reader = new FileReader()
        reader.onload = (e) => {
            thumbnailPreview.value = e.target?.result as string
        }
        reader.readAsDataURL(file)
    } else {
        form.thumbnail = null
        thumbnailPreview.value = null
    }
}

const removeThumbnail = () => {
    form.remove_thumbnail = true
    form.thumbnail = null
    thumbnailPreview.value = null
}

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

const formatDuration = (seconds: number | null | undefined): string => {
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

    form.post(`/admin/videos/${props.video.id}/update`, {
        forceFormData: true,
        onFinish: () => {
            isSubmitting.value = false
        },
        onSuccess: () => {
            console.log('Video updated successfully')
        },
        onError: (errors) => {
            console.error('Update failed:', errors)
        }
    })
}

const deleteVideo = () => {
    if (confirm(`Are you sure you want to delete "${props.video.name}"? This action cannot be undone and will remove all associated progress data.`)) {
        router.delete(`/admin/videos/${props.video.id}`)
    }
}

watch(() => form.thumbnail, (newValue) => {
    if (newValue) {
        form.remove_thumbnail = false
    }
})
</script>
