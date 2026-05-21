<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import * as FilePond from 'filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import 'filepond/dist/filepond.min.css';

const props = defineProps({
    name: {
        type: String,
        default: 'file'
    },
    accept: {
        type: String,
        default: 'video/*'
    },
    maxFileSize: {
        type: String,
        default: '10000MB'
    },
    modelValue: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'uploaded', 'error', 'progress']);

const fileInput = ref(null);
const fileData = ref(props.modelValue);
let pond = null;

FilePond.registerPlugin(
    FilePondPluginFileValidateType,
    FilePondPluginFileValidateSize
);

// Get XSRF token from cookie
const getXSRFToken = () => {
    const name = 'XSRF-TOKEN=';
    const decodedCookie = decodeURIComponent(document.cookie);
    const cookies = decodedCookie.split(';');

    for (let i = 0; i < cookies.length; i++) {
        let cookie = cookies[i].trim();
        if (cookie.indexOf(name) === 0) {
            return cookie.substring(name.length, cookie.length);
        }
    }
    return null;
};

onMounted(() => {
    const xsrfToken = getXSRFToken();

    if (!xsrfToken) {
        console.warn('⚠️ XSRF-TOKEN cookie not found');
    }

    // ✅ Store the final response here
    let finalResponse = null;

    pond = FilePond.create(fileInput.value, {
        acceptedFileTypes: props.accept ? [props.accept] : ['video/*'],
        maxFileSize: props.maxFileSize,
        
        chunkUploads: true,
        chunkSize: 1048576,
        chunkForce: true,
        chunkRetryDelays: [500, 1000, 3000],
        
        server: {
            process: {
                url: '/admin/videos/upload-chunk',
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': xsrfToken || '',
                },
                withCredentials: true,
                onload: (xhr) => {
                    console.log('✅ POST Response:', xhr.responseText);
                    return xhr.responseText;
                },
                onerror: (xhr) => {
                    console.error('❌ POST Error:', xhr.responseText);
                    return xhr.responseText || 'Upload failed';
                }
            },
            patch: {
                url: '/admin/videos/upload-chunk?patch=',
                method: 'PATCH',
                headers: {
                    'X-XSRF-TOKEN': xsrfToken || '',
                },
                withCredentials: true,
                onload: (xhr) => {
                    const responseText = xhr.responseText;
                    console.log('✅ PATCH Response:', responseText);
                    
                    // Try to parse and store the final response
                    try {
                        const parsed = JSON.parse(responseText);
                        if (parsed && parsed.path) {
                            console.log('✅ Saving final response:', parsed);
                            finalResponse = parsed;
                        }
                    } catch (e) {
                        console.log('Not JSON or incomplete');
                    }
                    
                    return responseText;
                },
                onerror: (xhr) => {
                    console.error('❌ PATCH Error:', xhr.responseText);
                    return xhr.responseText || 'Chunk upload failed';
                }
            },
            revert: '/admin/videos/upload-chunk/revert',
        },

        onprocessfile: (error, file) => {
            if (error) {
                console.error('❌ File process error:', error);
                emit('error', error);
                return;
            }
            
            console.log('✅ File uploaded successfully!');
            
            // Use the stored response from PATCH
            if (finalResponse && finalResponse.path) {
                console.log('✅ Using final response:', finalResponse);
                emit('uploaded', finalResponse);
                finalResponse = null;  // Reset for next upload
            } else {
                console.error('❌ No valid response found');
                emit('error', 'Upload completed but no valid response received');
            }
        },
        
        onprocessfileprogress: (file, progress) => {
            const progressPercent = Math.round(progress * 100);
            console.log(`📊 Progress: ${progressPercent}%`);
            emit('progress', progressPercent);
        },
        
        onprocessfileerror: (error) => {
            console.error('❌ Upload error:', error);
            emit('error', error.body || error.message || 'Upload failed');
        },

        labelIdle: 'Drag & Drop your video or <span class="filepond--label-action">Browse</span>',
    });

    console.log('✅ FilePond initialized');

    // Event listeners for debugging
    pond.on('addfile', (error, file) => {
        if (!error) {
            console.log('✅ File added:', file.filename, 'Size:', file.fileSize);
        }
    });
});

onBeforeUnmount(() => {
    if (pond) {
        pond.destroy();
    }
});

watch(() => props.modelValue, (newValue) => {
    fileData.value = newValue;
});
</script>

<template>
    <div class="chunk-uploader">
        <input
            ref="fileInput"
            type="file"
            :accept="accept"
            class="filepond"
        />
        <input type="hidden" :name="name" v-model="fileData" />
    </div>
</template>

<style scoped>
.chunk-uploader {
    width: 100%;
}

:deep(.filepond--root) {
    font-family: inherit;
}

:deep(.filepond--drop-label) {
    min-height: 200px;
}

:deep(.filepond--panel-root) {
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
}

:deep(.filepond--drip-blob) {
    background-color: #007bff;
}
</style>
