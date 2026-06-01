/**
 * Survey system frontend and admin interactions
 */

function applyAdminBranding(appName, appLogoUrl = null, titleTemplate = null, copyrightText = null) {
    if (!appName) {
        return;
    }

    document.querySelectorAll('[data-app-name]').forEach((element) => {
        element.textContent = appName;
    });

    if (appLogoUrl !== null) {
        document.querySelectorAll('[data-app-logo]').forEach((element) => {
            if (appLogoUrl) {
                element.src = appLogoUrl;
                element.style.display = '';
            } else {
                element.removeAttribute('src');
                element.style.display = 'none';
            }
        });
    }

    if (copyrightText !== null) {
        document.querySelectorAll('[data-app-copyright]').forEach((element) => {
            const footer = element.closest('.app-footer');
            if (copyrightText) {
                element.textContent = copyrightText;
                if (footer) {
                    footer.style.display = '';
                }
            } else if (footer) {
                element.textContent = '';
                footer.style.display = 'none';
            }
        });
    }

    if (document.body && document.body.dataset && document.body.dataset.adminPage === '1') {
        const suffix = document.body.dataset.pageTitleSuffix || '';
        const template = titleTemplate || localStorage.getItem('browser_title_template') || '{page} - {app}';
        const pageTitle = suffix || appName;
        document.title = template.replace('{page}', pageTitle).replace('{app}', appName);
    }
}

function getCSRFToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function updateQuestionNumbers() {
    const editors = document.querySelectorAll('.question-editor');
    editors.forEach((editor, index) => {
        editor.dataset.index = String(index);

        const header = editor.querySelector('.question-editor-header h4');
        if (header) {
            header.textContent = `题目 ${index + 1}`;
        }

        editor.querySelectorAll('[name^="questions["]').forEach((field) => {
            field.name = field.name.replace(/questions\[\d+\]/, `questions[${index}]`);
        });

        const sortOrder = editor.querySelector('input[name$="[sort_order]"]');
        if (sortOrder) {
            sortOrder.value = String(index + 1);
        }
    });
}

function showUploadStatus(statusElement, text, tone = 'default') {
    if (!statusElement) {
        return;
    }

    statusElement.textContent = text;
    statusElement.classList.remove('is-empty', 'is-warning', 'is-success');

    if (tone === 'empty') {
        statusElement.classList.add('is-empty');
    } else if (tone === 'warning') {
        statusElement.classList.add('is-warning');
    } else if (tone === 'success') {
        statusElement.classList.add('is-success');
    }
}

function setupImageUploadField(config) {
    const fileInput = document.getElementById(config.fileInputId);
    const currentInput = document.getElementById(config.currentInputId);
    const removeInput = document.getElementById(config.removeInputId);
    const preview = document.getElementById(config.previewId);
    const placeholder = document.getElementById(config.placeholderId);
    const removeButton = document.getElementById(config.removeButtonId);
    const statusElement = document.getElementById(config.statusId);

    if (!fileInput || !currentInput || !removeInput || !preview || !placeholder || !removeButton) {
        return null;
    }

    let objectUrl = null;

    function revokePreviewUrl() {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    }

    function setPreview(url) {
        if (url) {
            preview.src = url;
            preview.style.display = '';
            placeholder.style.display = 'none';
        } else {
            preview.removeAttribute('src');
            preview.style.display = 'none';
            placeholder.style.display = '';
        }
    }

    function syncStatus() {
        const currentUrl = currentInput.value.trim();
        const selectedFile = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        const removeMarked = removeInput.value === '1';

        if (selectedFile) {
            showUploadStatus(statusElement, `已选择文件：${selectedFile.name}，保存后生效`, 'success');
            return;
        }

        if (removeMarked) {
            showUploadStatus(statusElement, `${config.label}已标记移除，保存后生效`, 'warning');
            return;
        }

        if (currentUrl) {
            showUploadStatus(statusElement, `当前已使用${config.label}`, 'default');
            return;
        }

        showUploadStatus(statusElement, `当前未设置${config.label}`, 'empty');
    }

    fileInput.addEventListener('change', function () {
        const file = this.files && this.files[0] ? this.files[0] : null;
        revokePreviewUrl();

        if (!file) {
            setPreview(currentInput.value.trim());
            syncStatus();
            return;
        }

        removeInput.value = '0';
        objectUrl = URL.createObjectURL(file);
        setPreview(objectUrl);
        syncStatus();
    });

    removeButton.addEventListener('click', function () {
        revokePreviewUrl();
        fileInput.value = '';
        currentInput.value = '';
        removeInput.value = '1';
        setPreview('');
        syncStatus();
    });

    setPreview(currentInput.value.trim());
    syncStatus();

    return {
        setCurrentUrl(url) {
            revokePreviewUrl();
            currentInput.value = url || '';
            removeInput.value = '0';
            fileInput.value = '';
            setPreview(currentInput.value.trim());
            syncStatus();
        },
        clearSelection() {
            revokePreviewUrl();
            fileInput.value = '';
            setPreview(currentInput.value.trim());
            syncStatus();
        }
    };
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.body || !document.body.dataset || document.body.dataset.adminPage !== '1') {
        return;
    }

    const storedAppName = localStorage.getItem('app_name');
    const storedLogo = localStorage.getItem('app_logo_url');
    const storedTemplate = localStorage.getItem('browser_title_template');
    const storedCopyright = localStorage.getItem('copyright');

    if (storedAppName) {
        applyAdminBranding(storedAppName, storedLogo, storedTemplate, storedCopyright);
    }
});

window.addEventListener('storage', (event) => {
    if (event.key === 'app_name' && event.newValue) {
        applyAdminBranding(
            event.newValue,
            localStorage.getItem('app_logo_url'),
            localStorage.getItem('browser_title_template'),
            localStorage.getItem('copyright')
        );
    }

    if (event.key === 'app_logo_url' || event.key === 'browser_title_template' || event.key === 'copyright') {
        const storedAppName = localStorage.getItem('app_name');
        if (storedAppName) {
            applyAdminBranding(
                storedAppName,
                localStorage.getItem('app_logo_url'),
                localStorage.getItem('browser_title_template'),
                localStorage.getItem('copyright')
            );
        }
    }
});

function validateForm() {
    let isValid = true;
    const requiredQuestions = document.querySelectorAll('.question-block[data-required="1"]');

    requiredQuestions.forEach((block) => {
        block.classList.remove('error');
        const errorMsg = block.querySelector('.error-msg');
        if (errorMsg) {
            errorMsg.classList.remove('show');
        }

        const radioChecked = block.querySelector('input[type="radio"]:checked');
        const checkboxChecked = block.querySelector('input[type="checkbox"]:checked');
        const textValue = block.querySelector('.text-input');

        let answered = false;
        if (radioChecked || checkboxChecked) {
            answered = true;
        } else if (textValue && textValue.value.trim() !== '') {
            answered = true;
        }

        if (!answered) {
            isValid = false;
            block.classList.add('error');
            if (errorMsg) {
                errorMsg.classList.add('show');
            }
        }
    });

    return isValid;
}

function submitSurvey(surveyId) {
    if (!validateForm()) {
        const firstError = document.querySelector('.question-block.error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
    }

    const submitBtn = document.getElementById('submitBtn');
    const defaultBtnText = submitBtn ? (submitBtn.dataset.defaultText || '提交') : '提交';
    submitBtn.disabled = true;
    submitBtn.textContent = '提交中...';

    const formData = {
        survey_id: surveyId,
        answers: {}
    };

    document.querySelectorAll('.question-block').forEach((block) => {
        const questionId = block.dataset.questionId;
        const radioChecked = block.querySelector('input[type="radio"]:checked');
        const textInput = block.querySelector('.text-input');
        const checkboxes = block.querySelectorAll('input[type="checkbox"]:checked');

        if (radioChecked) {
            formData.answers[questionId] = radioChecked.value;
        } else if (textInput) {
            formData.answers[questionId] = textInput.value.trim();
        } else if (checkboxes.length > 0) {
            formData.answers[questionId] = Array.from(checkboxes).map((checkbox) => checkbox.value);
        }
    });

    fetch('/api/submit.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
        .then((response) => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then((text) => {
                    throw new Error(`服务端返回了非 JSON 响应 (HTTP ${response.status}): ${text.substring(0, 200)}`);
                });
            }

            return response.text().then((text) => {
                if (!text) {
                    throw new Error(`服务端返回了空响应 (HTTP ${response.status})`);
                }
                return JSON.parse(text);
            });
        })
        .then((data) => {
            if (data.code === 0) {
                document.getElementById('surveyForm').style.display = 'none';
                document.getElementById('thankYou').style.display = 'block';
                return;
            }

            alert(data.message || '提交失败，请重试');
            submitBtn.disabled = false;
            submitBtn.textContent = defaultBtnText;
        })
        .catch((error) => {
            console.error('提交错误:', error);
            alert(`提交出错: ${error.message}`);
            submitBtn.disabled = false;
            submitBtn.textContent = defaultBtnText;
        });
}

function toggleSurveyStatus(surveyId, currentStatus) {
    const newStatus = currentStatus === 1 ? 0 : 1;

    fetch('/admin/surveys.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `csrf_token=${encodeURIComponent(getCSRFToken())}&action=toggle_status&survey_id=${surveyId}&status=${newStatus}`
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.code === 0) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
}

function deleteSurvey(surveyId) {
    if (!confirm('确定要删除这份问卷吗？删除后题目和已收集的数据都会一起移除。')) {
        return;
    }

    fetch('/admin/surveys.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `csrf_token=${encodeURIComponent(getCSRFToken())}&action=delete&survey_id=${surveyId}`
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.code === 0) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
}

function addQuestion() {
    const container = document.getElementById('questionsContainer');
    const questionCount = container.querySelectorAll('.question-editor').length;
    const newOrder = questionCount + 1;

    const html = `
        <div class="question-editor" data-index="${questionCount}">
            <input type="hidden" name="questions[${questionCount}][id]" value="0">
            <div class="question-editor-header">
                <h4>题目 ${newOrder}</h4>
                <div class="question-actions">
                    <button type="button" class="btn btn-sm" onclick="moveQuestion(this, -1)" title="上移">↑</button>
                    <button type="button" class="btn btn-sm" onclick="moveQuestion(this, 1)" title="下移">↓</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeQuestion(this)" title="删除">×</button>
                </div>
            </div>
            <div class="form-group">
                <label>题目内容</label>
                <input type="text" name="questions[${questionCount}][title]" class="form-control" placeholder="请输入题目内容" required>
            </div>
            <div class="form-group">
                <label>题目类型</label>
                <select name="questions[${questionCount}][type]" class="form-control" onchange="toggleOptionsInput(this)">
                    <option value="radio">单选题</option>
                    <option value="checkbox">多选题</option>
                    <option value="text">文本题</option>
                </select>
            </div>
            <div class="form-group options-group">
                <label>选项</label>
                <div class="option-inputs">
                    <div class="option-input-row">
                        <input type="text" name="questions[${questionCount}][options][]" class="form-control" placeholder="选项 1">
                        <button type="button" class="remove-option" onclick="removeOption(this)">×</button>
                    </div>
                    <div class="option-input-row">
                        <input type="text" name="questions[${questionCount}][options][]" class="form-control" placeholder="选项 2">
                        <button type="button" class="remove-option" onclick="removeOption(this)">×</button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm" onclick="addOption(this)" style="margin-top: 8px;">+ 添加选项</button>
            </div>
            <div class="form-group">
                <label class="checkbox-row">
                    <input type="checkbox" name="questions[${questionCount}][required]" value="1" checked>
                    必填
                </label>
            </div>
            <input type="hidden" name="questions[${questionCount}][sort_order]" value="${newOrder}">
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
    updateQuestionNumbers();
}

function removeQuestion(btn) {
    const editor = btn.closest('.question-editor');
    const idField = editor.querySelector('input[name$="[id]"]');
    const questionId = idField ? parseInt(idField.value, 10) : 0;

    if (questionId > 0) {
        const form = document.getElementById('surveyForm');
        const hasResponses = form && form.dataset.hasResponses === 'true';
        if (hasResponses) {
            const confirmMsg = '该问卷已有收集到的回答。删除此题目将永久删除已收集的所有历史答案，确定要删除吗？';
            if (!confirm(confirmMsg)) {
                return;
            }
        }
    }

    editor.remove();
    updateQuestionNumbers();
}

function moveQuestion(btn, direction) {
    const editor = btn.closest('.question-editor');
    const container = editor.parentElement;

    if (direction === -1 && editor.previousElementSibling) {
        container.insertBefore(editor, editor.previousElementSibling);
    } else if (direction === 1 && editor.nextElementSibling) {
        container.insertBefore(editor.nextElementSibling, editor);
    }

    updateQuestionNumbers();
}

function toggleOptionsInput(select) {
    const editor = select.closest('.question-editor');
    const optionsGroup = editor.querySelector('.options-group');
    optionsGroup.style.display = select.value === 'text' ? 'none' : 'block';
}

function addOption(btn) {
    const optionInputs = btn.previousElementSibling;
    const optionCount = optionInputs.querySelectorAll('.option-input-row').length;
    const questionIndex = btn.closest('.question-editor')
        .querySelector('input[name$="[title]"]')
        .name.match(/questions\[(\d+)\]/)[1];

    const html = `
        <div class="option-input-row">
            <input type="text" name="questions[${questionIndex}][options][]" class="form-control" placeholder="选项 ${optionCount + 1}">
            <button type="button" class="remove-option" onclick="removeOption(this)">×</button>
        </div>
    `;

    optionInputs.insertAdjacentHTML('beforeend', html);
}

function removeOption(btn) {
    const row = btn.closest('.option-input-row');
    const container = row.parentElement;

    if (container.querySelectorAll('.option-input-row').length <= 2) {
        alert('至少保留两个选项。');
        return;
    }

    row.remove();
}

function exportCSV(surveyId) {
    window.location.href = `/admin/responses.php?action=export&survey_id=${surveyId}`;
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function populateParsedQuestions(questions) {
    const container = document.getElementById('questionsContainer');
    if (!container) return;
    
    // Clear existing
    container.innerHTML = '';
    
    questions.forEach((question, index) => {
        const questionCount = index;
        const newOrder = index + 1;
        const optionsHtml = (question.options || []).map((option, optIdx) => `
            <div class="option-input-row">
                <input type="text" name="questions[${questionCount}][options][]" class="form-control" value="${escapeHtml(option)}" placeholder="选项 ${optIdx + 1}">
                <button type="button" class="remove-option" onclick="removeOption(this)">×</button>
            </div>
        `).join('');
        
        const html = `
            <div class="question-editor" data-index="${questionCount}">
                <input type="hidden" name="questions[${questionCount}][id]" value="0">
                <div class="question-editor-header">
                    <h4>题目 ${newOrder}</h4>
                    <div class="question-actions">
                        <button type="button" class="btn btn-sm" onclick="moveQuestion(this, -1)" title="上移">↑</button>
                        <button type="button" class="btn btn-sm" onclick="moveQuestion(this, 1)" title="下移">↓</button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeQuestion(this)" title="删除">×</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>题目内容</label>
                    <input type="text" name="questions[${questionCount}][title]" class="form-control" value="${escapeHtml(question.title)}" placeholder="请输入题目内容" required>
                </div>
                <div class="form-group">
                    <label>题目类型</label>
                    <select name="questions[${questionCount}][type]" class="form-control" onchange="toggleOptionsInput(this)">
                        <option value="radio" ${question.type === 'radio' ? 'selected' : ''}>单选题</option>
                        <option value="checkbox" ${question.type === 'checkbox' ? 'selected' : ''}>多选题</option>
                        <option value="text" ${question.type === 'text' ? 'selected' : ''}>文本题</option>
                    </select>
                </div>
                <div class="form-group options-group" style="${question.type === 'text' ? 'display:none;' : ''}">
                    <label>选项</label>
                    <div class="option-inputs">
                        ${optionsHtml !== '' ? optionsHtml : `
                            <div class="option-input-row">
                                <input type="text" name="questions[${questionCount}][options][]" class="form-control" placeholder="选项 1">
                                <button type="button" class="remove-option" onclick="removeOption(this)">×</button>
                            </div>
                            <div class="option-input-row">
                                <input type="text" name="questions[${questionCount}][options][]" class="form-control" placeholder="选项 2">
                                <button type="button" class="remove-option" onclick="removeOption(this)">×</button>
                            </div>
                        `}
                    </div>
                    <button type="button" class="btn btn-sm" onclick="addOption(this)" style="margin-top: 8px;">+ 添加选项</button>
                </div>
                <div class="form-group">
                    <label class="checkbox-row">
                        <input type="checkbox" name="questions[${questionCount}][required]" value="1" ${question.required ? 'checked' : ''}>
                        必填
                    </label>
                </div>
                <input type="hidden" name="questions[${questionCount}][sort_order]" value="${newOrder}">
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', html);
    });
    
    updateQuestionNumbers();
}

function triggerCSVImport() {
    const fileInput = document.getElementById('csvImportFile');
    if (fileInput) {
        fileInput.value = ''; // Reset selection
        fileInput.click();
    }
}

function handleCSVImport(input) {
    const file = input.files && input.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('csrf_token', getCSRFToken());
    formData.append('action', 'parse_csv');
    formData.append('csv_file', file);

    const importBtn = document.querySelector('button[onclick="triggerCSVImport()"]');
    const originalText = importBtn ? importBtn.textContent : '导入 CSV 问卷';
    if (importBtn) {
        importBtn.disabled = true;
        importBtn.textContent = '导入中...';
    }

    fetch('/admin/surveys.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error('服务器未返回 JSON 格式，请检查文件格式。' + text.substring(0, 100));
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.code !== 0) {
            throw new Error(data.message || 'CSV 解析失败');
        }

        const questions = data.data.questions;
        if (!questions || questions.length === 0) {
            throw new Error('未在 CSV 中找到有效题目。');
        }

        const confirmMsg = `已成功解析出 ${questions.length} 个题目。\n导入操作将替换当前编辑区的所有题目，确定要导入吗？`;
        if (confirm(confirmMsg)) {
            populateParsedQuestions(questions);
            alert('导入成功，请确认无误后点击最下方的“保存问卷”保存到数据库。');
        }
    })
    .catch(error => {
        console.error('Import error:', error);
        alert('导入出错: ' + error.message);
    })
    .finally(() => {
        if (importBtn) {
            importBtn.disabled = false;
            importBtn.textContent = originalText;
        }
        input.value = ''; // Reset input selection
    });
}
