<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // UNSAVED CHANGES TRACKER & CUSTOM MODAL
    // ==========================================
    let hasUnsavedChanges = false;
    let pendingLocation = ''; 

    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.target === '_blank' || this.href.includes('javascript:') || this.href === '' || this.href.includes('#')) {
                return;
            }
            if (hasUnsavedChanges) {
                e.preventDefault(); 
                pendingLocation = this.href; 
                const modal = document.getElementById('unsaved-modal');
                if (modal) {
                    modal.classList.remove('hidden');
                } else {
                    if(confirm("You have unsaved changes. Leave anyway?")) {
                        window.location.href = this.href;
                    }
                }
            }
        });
    });

    window.closeUnsavedModal = function() {
        const modal = document.getElementById('unsaved-modal');
        if (modal) modal.classList.add('hidden');
        pendingLocation = '';
    }

    const confirmLeaveBtn = document.getElementById('confirm-leave-btn');
    if (confirmLeaveBtn) {
        confirmLeaveBtn.addEventListener('click', function() {
            hasUnsavedChanges = false; 
            window.location.href = pendingLocation; 
        });
    }

    window.addEventListener('beforeunload', function (e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = ''; 
        }
    });

    const ipcrForm = document.getElementById('ipcr-form');
    if (ipcrForm) {
        ipcrForm.addEventListener('submit', function() {
            hasUnsavedChanges = false;
        });
    }

    // ==========================================
    // REAL-TIME PROGRESS BAR LOGIC
    // ==========================================
    function updateProgress() {
        const taskRows = document.querySelectorAll('.task-row');
        const totalTasks = taskRows.length;
        let completedTasks = 0;

        // Count how many rows have at least one rating filled out
        taskRows.forEach(row => {
            const q = parseFloat(row.querySelector('input[name^="q["]').value) || 0;
            const e = parseFloat(row.querySelector('input[name^="e["]').value) || 0;
            const t = parseFloat(row.querySelector('input[name^="t["]').value) || 0;
            
            if (q > 0 || e > 0 || t > 0) {
                completedTasks++;
            }
        });

        const progressText = document.getElementById('progress-text');
        const progressBarFill = document.getElementById('progress-bar-fill');

        if (progressText && progressBarFill) {
            progressText.textContent = `${completedTasks}/${totalTasks}`;
            
            const percentage = totalTasks === 0 ? 0 : (completedTasks / totalTasks) * 100;
            progressBarFill.style.width = `${percentage}%`;

            // Turn Green when 100% complete!
            if (percentage === 100) {
                progressBarFill.classList.remove('bg-blue-600');
                progressBarFill.classList.add('bg-emerald-500');
                progressText.classList.remove('text-blue-600');
                progressText.classList.add('text-emerald-500');
            } else {
                progressBarFill.classList.add('bg-blue-600');
                progressBarFill.classList.remove('bg-emerald-500');
                progressText.classList.add('text-blue-600');
                progressText.classList.remove('text-emerald-500');
            }
        }
    }


    // ==========================================
    // RATING LOGIC & AUTO-GENERATION
    // ==========================================
    const ratingMatrix = <?= isset($ratingMatrixJson) ? $ratingMatrixJson : '{}' ?>;

    function escapeRegExp(string) { return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

    function getVariations(text) {
        if (!text) return [];
        const variations = [text.trim()]; 
        const numMap = { '1':'one (1)', '2':'two (2)', '3':'three (3)', '4':'four (4)', '5':'five (5)', '6':'six (6)', '7':'seven (7)', '8':'eight (8)', '9':'nine (9)', '10':'ten (10)' };
        const match = text.match(/^(\d+)\s+(.*)/);
        if (match) {
            const number = match[1];
            const rest = match[2];
            if (numMap[number]) variations.push(`${numMap[number]} ${rest}`); 
        }
        return variations;
    }

    function smartReplace(sentence, siCode, qVal, eVal, tVal) {
        if (!ratingMatrix[siCode]) return sentence;
        let newHtml = sentence;
        const replaceCategory = (category, userRating, standardRatingToFind) => {
            const r = Math.round(userRating);
            if (!r || !ratingMatrix[siCode][category]) return;
            const actualText = ratingMatrix[siCode][category][r];
            const standardText = ratingMatrix[siCode][category][standardRatingToFind];
            if (!actualText || !standardText) return;
            const searchPhrases = getVariations(standardText);
            let found = false;
            for (const phrase of searchPhrases) {
                if (!phrase) continue;
                const regex = new RegExp(escapeRegExp(phrase), 'i');
                if (regex.test(newHtml)) {
                    const replacement = `<u>${actualText}</u>`;
                    newHtml = newHtml.replace(regex, replacement);
                    found = true; break; 
                }
            }
            if (!found && category === 'E' && standardText.includes('100%') && newHtml.includes('100%')) {
                 const replacement = `<u>${actualText}</u>`;
                 newHtml = newHtml.replace('100%', replacement);
            }
        };
        replaceCategory('T', tVal, 3);
        replaceCategory('E', eVal, 5);
        replaceCategory('Q', qVal, 3);
        return newHtml;
    }

    function updateGrandTotal() {
        let totalSum = 0; let totalCount = 0;
        document.querySelectorAll('.avg-cell').forEach(cell => {
            const val = parseFloat(cell.textContent);
            if (val > 0) { totalSum += val; totalCount++; }
        });
        const grandAvg = totalCount > 0 ? (totalSum / totalCount).toFixed(2) : "0.00";
        document.getElementById('header-grand-avg').textContent = grandAvg;
        const avgNum = parseFloat(grandAvg);
        let adjectival = "---";
        let badgeClass = "bg-gray-100 text-gray-800";
        if (avgNum >= 4.51) { adjectival = "Outstanding"; badgeClass = "bg-green-100 text-green-800"; }
        else if (avgNum >= 3.51) { adjectival = "Very Satisfactory"; badgeClass = "bg-blue-100 text-blue-800"; }
        else if (avgNum >= 2.51) { adjectival = "Satisfactory"; badgeClass = "bg-yellow-100 text-yellow-800"; }
        else if (avgNum >= 1.51) { adjectival = "Unsatisfactory"; badgeClass = "bg-orange-100 text-orange-800"; }
        else if (avgNum > 0) { adjectival = "Poor"; badgeClass = "bg-red-100 text-red-800"; }
        const badge = document.getElementById('header-adjectival');
        badge.textContent = adjectival;
        badge.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeClass}`;
    }

    document.querySelectorAll('.rating-input').forEach(input => {
        if (input.disabled) return; 
        input.addEventListener('input', function() {
            
            hasUnsavedChanges = true;

            const row = this.closest('.task-row');
            const taskId = row.querySelector('.smart-cell').dataset.taskId;
            const siCode = row.querySelector('.smart-cell').dataset.taskId;
            const successIndicator = row.querySelector('.smart-cell').dataset.successIndicator;
            const divArea = document.getElementById('div-' + taskId);
            const hiddenInput = document.getElementById('input-' + taskId);
            const avgCell = document.getElementById('avg-' + taskId);
            const q = parseFloat(row.querySelector(`input[name="q[${taskId}]"]`).value || 0);
            const e = parseFloat(row.querySelector(`input[name="e[${taskId}]"]`).value || 0);
            const t = parseFloat(row.querySelector(`input[name="t[${taskId}]"]`).value || 0);
            
            let sum = 0, count = 0;
            if (q) { sum += q; count++; }
            if (e) { sum += e; count++; }
            if (t) { sum += t; count++; }
            const avg = count > 0 ? (sum / count).toFixed(2) : "0.00";
            avgCell.textContent = avg;
            if (avg >= 4.5) avgCell.className = "text-xl font-black text-green-600 avg-cell";
            else if (avg > 0 && avg < 3) avgCell.className = "text-xl font-black text-red-600 avg-cell";
            else avgCell.className = "text-xl font-black text-slate-800 avg-cell";
            
            if (q || e || t) {
                const generatedHtml = smartReplace(successIndicator, siCode, q, e, t);
                divArea.innerHTML = generatedHtml;
                hiddenInput.value = generatedHtml; 
            } else {
                divArea.innerHTML = "";
                hiddenInput.value = "";
            }
            
            // Trigger our new functions!
            updateGrandTotal();
            updateProgress();
        });
    });
    
    document.querySelectorAll('.smart-area').forEach(div => {
        div.addEventListener('input', function() {
            hasUnsavedChanges = true;
            const id = this.id.replace('div-', '');
            document.getElementById('input-' + id).value = this.innerHTML;
        });
    });

    // ==========================================
    // EXISTING MODALS
    // ==========================================
    window.openDeleteModal = function(deleteUrl) {
        document.getElementById('confirm-delete-btn').href = deleteUrl;
        document.getElementById('delete-modal').classList.remove('hidden');
    }
    window.closeDeleteModal = function() {
        document.getElementById('delete-modal').classList.add('hidden');
    }

    window.openEditModal = function(btn) {
        const id = btn.dataset.id;
        const code = btn.dataset.code;
        const title = btn.dataset.title;
        const si = btn.dataset.si;

        document.getElementById('edit-task-id').value = id;
        document.getElementById('edit-task-code').value = code;
        document.getElementById('edit-task-title').value = title;
        document.getElementById('edit-task-si').value = si;
        
        document.getElementById('edit-modal').classList.remove('hidden');
    }
    window.closeEditModal = function() {
        document.getElementById('edit-modal').classList.add('hidden');
    }

    // Auto-dismiss Undo Toast
    const toast = document.getElementById('undo-toast');
    if (toast) {
        setTimeout(function() {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }

    // Run calculations once on page load to fill the bar and averages immediately!
    updateGrandTotal();
    updateProgress();
});
</script>