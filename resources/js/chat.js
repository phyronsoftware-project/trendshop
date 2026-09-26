// Run the shared customer and administrator support-chat interactions.
export const initializeChat = () => {
    document.querySelectorAll('[data-chat-shell]').forEach((shell) => {
        const messagesPanel = shell.querySelector('[data-chat-messages]');
        const form = shell.querySelector('[data-chat-form]');
        const body = shell.querySelector('[data-chat-body]');
        const fileInput = shell.querySelector('[data-chat-file]');
        const sendButton = shell.querySelector('[data-chat-send]');
        const feedback = shell.querySelector('[data-chat-feedback]');
        const imagePreviews = shell.querySelector('[data-chat-image-previews]');
        const audioPreview = shell.querySelector('[data-chat-audio-preview]');
        const audioName = shell.querySelector('[data-chat-audio-name]');
        const recordingStatus = shell.querySelector('[data-chat-recording-status]');
        const recordButton = shell.querySelector('[data-chat-record]');
        const conversationList = shell.querySelector('[data-chat-conversation-list]');
        const conversationSearch = shell.querySelector('[data-chat-conversation-search]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const initialConversation = shell.querySelector('[data-chat-conversation-active="true"]');
        let messagesUrl = shell.dataset.chatMessagesUrl ?? initialConversation?.dataset.chatMessagesUrl ?? '';
        let sendUrl = form?.action ?? '';
        let activeConversationId = initialConversation?.dataset.chatConversation ?? null;
        let lastMessageId = Number(messagesPanel?.querySelector('[data-chat-message-id]:last-of-type')?.dataset.chatMessageId ?? 0);
        let isLoadingMessages = false;
        let isSending = false;
        let mediaRecorder = null;
        let recordedFile = null;
        let selectedImages = [];
        let imagePreviewUrls = [];
        let recordingStartedAt = null;
        let audioDurationSeconds = null;

        const scrollToLatest = () => {
            if (messagesPanel) {
                messagesPanel.scrollTop = messagesPanel.scrollHeight;
            }
        };

        const showFeedback = (message) => {
            if (!feedback) {
                return;
            }

            feedback.textContent = message;
            feedback.classList.toggle('hidden', !message);
        };

        const clearAudioPreview = () => {
            recordedFile = null;
            audioDurationSeconds = null;
            audioPreview?.classList.add('hidden');
            audioPreview?.classList.remove('flex');
            if (audioName) {
                audioName.textContent = '';
            }
        };

        const syncImageInput = () => {
            if (!fileInput) {
                return;
            }

            const transfer = new DataTransfer();
            selectedImages.forEach((file) => transfer.items.add(file));
            fileInput.files = transfer.files;
        };

        const renderImagePreviews = () => {
            imagePreviewUrls.forEach((url) => URL.revokeObjectURL(url));
            imagePreviewUrls = [];
            imagePreviews?.replaceChildren();

            selectedImages.forEach((file, index) => {
                const previewUrl = URL.createObjectURL(file);
                imagePreviewUrls.push(previewUrl);

                const preview = document.createElement('div');
                preview.className = 'relative size-16 overflow-hidden border border-slate-200 bg-slate-50 shadow-sm';

                const image = document.createElement('img');
                image.src = previewUrl;
                image.alt = file.name;
                image.className = 'size-full object-cover';

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.dataset.chatImageRemove = String(index);
                removeButton.className = 'absolute right-1 top-1 grid size-5 place-items-center rounded-full bg-white text-sm font-bold text-red-600 shadow';
                removeButton.setAttribute('aria-label', `Remove ${file.name}`);
                removeButton.textContent = '×';

                preview.append(image, removeButton);
                imagePreviews?.append(preview);
            });

            imagePreviews?.classList.toggle('hidden', selectedImages.length === 0);
            imagePreviews?.classList.toggle('flex', selectedImages.length > 0);
        };

        const resetAttachment = () => {
            clearAudioPreview();
            selectedImages = [];
            if (fileInput) fileInput.value = '';
            renderImagePreviews();
        };

        const appendMessages = (messages) => {
            if (!messagesPanel || !Array.isArray(messages) || messages.length === 0) {
                return;
            }

            messagesPanel.querySelector('[data-chat-empty]')?.remove();
            messages.forEach((message) => {
                if (messagesPanel.querySelector(`[data-chat-message-id="${message.id}"]`)) {
                    return;
                }
                messagesPanel.insertAdjacentHTML('beforeend', message.html);
                lastMessageId = Math.max(lastMessageId, Number(message.id));
            });
            scrollToLatest();
        };

        const updateActiveHeader = (conversation) => {
            if (!conversation) {
                return;
            }

            const avatar = shell.querySelector('[data-chat-active-avatar]');
            if (avatar) {
                avatar.src = conversation.avatar_url;
                avatar.alt = conversation.user_name;
                avatar.classList.remove('invisible');
            }
            const name = shell.querySelector('[data-chat-active-name]');
            const email = shell.querySelector('[data-chat-active-email]');
            if (name) name.textContent = conversation.user_name;
            if (email) email.textContent = conversation.user_email;
        };

        const loadMessages = async (reset = false) => {
            if (!messagesUrl || isLoadingMessages || document.hidden) {
                return;
            }

            isLoadingMessages = true;
            try {
                const url = new URL(messagesUrl, window.location.origin);
                url.searchParams.set('after_id', reset ? '0' : String(lastMessageId));
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) {
                    return;
                }
                const payload = await response.json();
                updateActiveHeader(payload.conversation);
                appendMessages(payload.messages);
            } finally {
                isLoadingMessages = false;
            }
        };

        const filterConversations = () => {
            const keyword = conversationSearch?.value.trim().toLowerCase() ?? '';
            conversationList?.querySelectorAll('[data-chat-conversation]').forEach((conversation) => {
                conversation.classList.toggle('hidden', !conversation.textContent.toLowerCase().includes(keyword));
            });
        };

        const loadConversations = async () => {
            if (!shell.dataset.chatConversationsUrl || document.hidden) {
                return;
            }

            const url = new URL(shell.dataset.chatConversationsUrl, window.location.origin);
            if (activeConversationId) {
                url.searchParams.set('active_conversation', activeConversationId);
            }
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                return;
            }
            const payload = await response.json();
            if (conversationList) {
                conversationList.innerHTML = payload.html;
                filterConversations();
            }
        };

        const selectConversation = (button) => {
            activeConversationId = button.dataset.chatConversation;
            messagesUrl = button.dataset.chatMessagesUrl;
            sendUrl = button.dataset.chatSendUrl;
            if (form) form.action = sendUrl;
            if (sendButton) sendButton.disabled = false;
            if (messagesPanel) messagesPanel.innerHTML = '';
            lastMessageId = 0;
            resetAttachment();
            showFeedback('');
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('conversation', activeConversationId);
            window.history.replaceState({}, '', currentUrl);
            loadMessages(true);
            loadConversations();
        };

        conversationList?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-chat-conversation]');
            if (button) {
                selectConversation(button);
            }
        });
        conversationSearch?.addEventListener('input', filterConversations);

        shell.querySelector('[data-chat-emoji-toggle]')?.addEventListener('click', () => {
            shell.querySelector('[data-chat-emoji-panel]')?.classList.toggle('hidden');
            shell.querySelector('[data-chat-emoji-panel]')?.classList.toggle('grid');
        });
        shell.querySelectorAll('[data-chat-emoji]').forEach((button) => button.addEventListener('click', () => {
            if (body) {
                body.value += button.dataset.chatEmoji;
                body.focus();
            }
        }));

        fileInput?.addEventListener('change', () => {
            clearAudioPreview();
            selectedImages = Array.from(fileInput.files).slice(0, 10);
            syncImageInput();
            renderImagePreviews();
        });
        imagePreviews?.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-chat-image-remove]');
            if (!removeButton) return;

            selectedImages.splice(Number(removeButton.dataset.chatImageRemove), 1);
            syncImageInput();
            renderImagePreviews();
        });
        shell.querySelector('[data-chat-audio-remove]')?.addEventListener('click', clearAudioPreview);

        recordButton?.addEventListener('click', async () => {
            if (mediaRecorder?.state === 'recording') {
                mediaRecorder.stop();
                return;
            }

            if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
                showFeedback('Voice recording is not supported by this browser.');
                return;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const preferredType = ['audio/webm;codecs=opus', 'audio/mp4'].find((type) => MediaRecorder.isTypeSupported(type));
                const chunks = [];
                mediaRecorder = preferredType ? new MediaRecorder(stream, { mimeType: preferredType }) : new MediaRecorder(stream);
                recordingStartedAt = Date.now();
                mediaRecorder.addEventListener('dataavailable', (event) => {
                    if (event.data.size > 0) chunks.push(event.data);
                });
                mediaRecorder.addEventListener('stop', () => {
                    const mimeType = mediaRecorder.mimeType || 'audio/webm';
                    const extension = mimeType.includes('mp4') ? 'm4a' : 'webm';
                    recordedFile = new File([new Blob(chunks, { type: mimeType })], `voice-message.${extension}`, { type: mimeType });
                    audioDurationSeconds = Math.max(1, Math.round((Date.now() - recordingStartedAt) / 1000));
                    stream.getTracks().forEach((track) => track.stop());
                    if (fileInput) fileInput.value = '';
                    selectedImages = [];
                    renderImagePreviews();
                    if (audioName) audioName.textContent = `Voice message (${audioDurationSeconds}s)`;
                    audioPreview?.classList.remove('hidden');
                    audioPreview?.classList.add('flex');
                    recordingStatus?.classList.add('hidden');
                    recordButton.classList.remove('border-red-500', 'bg-red-50', 'text-red-500');
                    if (sendButton) sendButton.disabled = false;
                });
                resetAttachment();
                mediaRecorder.start();
                recordingStatus?.classList.remove('hidden');
                recordButton.classList.add('border-red-500', 'bg-red-50', 'text-red-500');
                if (sendButton) sendButton.disabled = true;
                showFeedback('');
            } catch {
                showFeedback('Microphone access is required to record a voice message.');
            }
        });

        body?.addEventListener('input', () => {
            body.style.height = 'auto';
            body.style.height = `${Math.min(body.scrollHeight, 128)}px`;
        });

        // Submit on Enter while preserving Shift+Enter for a new line.
        body?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                form?.requestSubmit();
            }
        });

        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (isSending || !sendUrl || mediaRecorder?.state === 'recording') {
                return;
            }

            const data = new FormData(form);
            if (recordedFile) {
                data.set('attachment', recordedFile);
                data.set('audio_duration_seconds', String(audioDurationSeconds));
            }
            isSending = true;
            if (sendButton) sendButton.disabled = true;
            showFeedback('');

            try {
                const response = await fetch(sendUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: data,
                });
                const payload = await response.json();
                if (!response.ok) {
                    const message = Object.values(payload.errors ?? {}).flat()[0] ?? payload.message ?? 'Unable to send message.';
                    showFeedback(message);
                    return;
                }
                appendMessages(payload.messages ?? [payload.message]);
                if (body) {
                    body.value = '';
                    body.style.height = 'auto';
                }
                resetAttachment();
                await loadConversations();
            } catch {
                showFeedback('Unable to connect. Please try again.');
            } finally {
                isSending = false;
                if (sendButton && sendUrl) sendButton.disabled = false;
            }
        });

        scrollToLatest();
        window.setInterval(loadMessages, 3000);
        if (shell.dataset.chatAdmin !== undefined) {
            window.setInterval(loadConversations, 3000);
        }
    });
};
