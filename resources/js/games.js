const root = document.querySelector('[data-game-root]');

if (root) {
    const stateElement = document.getElementById(root.dataset.stateElement);
    let state = JSON.parse(stateElement.textContent);
    let soundEnabled = true;
    let submitting = false;
    let timerId;
    let timerDeadline = state.session.remaining_seconds === null
        ? null
        : Date.now() + (state.session.remaining_seconds * 1000);

    const elements = {
        choices: root.querySelector('[data-game-choices]'),
        context: root.querySelector('[data-game-context]'),
        feedback: root.querySelector('[data-game-feedback]'),
        level: root.querySelector('[data-game-level]'),
        lives: root.querySelector('[data-game-lives]'),
        loading: root.querySelector('[data-game-loading]'),
        listen: root.querySelector('[data-game-listen]'),
        progress: root.querySelector('[data-game-progress]'),
        progressBar: root.querySelector('[data-game-progress-bar]'),
        progressTrack: root.querySelector('.game-progress-track'),
        prompt: root.querySelector('[data-game-prompt]'),
        score: root.querySelector('[data-game-score]'),
        sound: root.querySelector('[data-game-sound]'),
        timer: root.querySelector('[data-game-timer]'),
        visual: root.querySelector('[data-game-visual]'),
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function playTone(isCorrect) {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;

        if (!soundEnabled || !AudioContextClass) {
            return;
        }

        const context = new AudioContextClass();
        const oscillator = context.createOscillator();
        const gain = context.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.value = isCorrect ? 660 : 220;
        gain.gain.setValueAtTime(0.12, context.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.18);
        oscillator.connect(gain);
        gain.connect(context.destination);
        oscillator.start();
        oscillator.stop(context.currentTime + 0.18);
        oscillator.addEventListener('ended', () => context.close());
    }

    function speak(text) {
        if (!soundEnabled || !text || !window.speechSynthesis) {
            return;
        }

        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'mr-IN';
        utterance.rate = 0.82;
        window.speechSynthesis.speak(utterance);
    }

    function remainingSeconds() {
        if (timerDeadline === null) {
            return null;
        }

        return Math.max(0, Math.ceil((timerDeadline - Date.now()) / 1000));
    }

    async function finishGame() {
        if (submitting) {
            return;
        }

        submitting = true;
        elements.loading.hidden = false;

        try {
            const response = await fetch(root.dataset.finishUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({}),
            });

            if (!response.ok) {
                throw new Error('Game could not be completed.');
            }

            state = await response.json();
            window.location.assign(root.dataset.resultUrl);
        } catch {
            showError();
        } finally {
            submitting = false;
            elements.loading.hidden = true;
        }
    }

    function updateTimer() {
        const seconds = remainingSeconds();

        if (seconds === null) {
            elements.timer.textContent = '∞';
            return;
        }

        elements.timer.textContent = `${seconds}s`;

        if (seconds === 0) {
            window.clearInterval(timerId);
            finishGame();
        }
    }

    function showFeedback(feedback) {
        if (!feedback) {
            elements.feedback.hidden = true;
            return;
        }

        elements.feedback.className = `game-feedback game-feedback-${feedback.tone}`;
        elements.feedback.replaceChildren();
        const title = document.createElement('strong');
        const message = document.createElement('span');
        title.textContent = feedback.title;
        message.textContent = feedback.message;
        elements.feedback.append(title, message);
        elements.feedback.hidden = false;
        playTone(feedback.tone === 'success');
    }

    function showError() {
        elements.feedback.className = 'game-feedback game-feedback-error';
        elements.feedback.replaceChildren();
        const title = document.createElement('strong');
        const message = document.createElement('span');
        title.textContent = 'पुन्हा प्रयत्न करा';
        message.textContent = 'उत्तर जतन झाले नाही. इंटरनेट जोडणी तपासा.';
        elements.feedback.append(title, message);
        elements.feedback.hidden = false;
    }

    async function submitAnswer(questionId, value, button) {
        if (submitting) {
            return;
        }

        submitting = true;
        elements.choices.querySelectorAll('button').forEach((choice) => {
            choice.disabled = true;
        });
        button.classList.add('is-selected');

        try {
            const answerUrl = root.dataset.answerUrl.replace('__QUESTION__', questionId);
            const response = await fetch(answerUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ answer: { value } }),
            });

            if (!response.ok) {
                throw new Error('Answer could not be saved.');
            }

            state = await response.json();
            timerDeadline = state.session.remaining_seconds === null
                ? null
                : Date.now() + (state.session.remaining_seconds * 1000);
            showFeedback(state.feedback);

            if (state.completed) {
                window.setTimeout(() => window.location.assign(root.dataset.resultUrl), 700);
                return;
            }

            window.setTimeout(() => {
                showFeedback(null);
                render();
                submitting = false;
            }, 650);
        } catch {
            showError();
            elements.choices.querySelectorAll('button').forEach((choice) => {
                choice.disabled = false;
            });
            submitting = false;
        }
    }

    function renderChoices(question) {
        elements.choices.innerHTML = '';

        question.choices.forEach((choice, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `game-choice game-choice-${(index % 4) + 1}`;
            button.textContent = choice.label;
            button.addEventListener('click', () => submitAnswer(question.id, choice.value, button));
            elements.choices.appendChild(button);
        });
    }

    function render() {
        const { stats, question, session } = state;
        const percent = stats.question_count === 0
            ? 0
            : Math.round((stats.answered_count / stats.question_count) * 100);

        elements.score.textContent = stats.score;
        elements.lives.textContent = `${'♥ '.repeat(stats.lives_remaining)}${'♡ '.repeat(Math.max(0, stats.lives_total - stats.lives_remaining))}`.trim();
        elements.progress.textContent = `${stats.answered_count} / ${stats.question_count}`;
        elements.progressBar.style.width = `${percent}%`;
        elements.progressTrack.setAttribute('aria-valuenow', percent);
        elements.level.textContent = `पातळी ${session.level} · ${session.level_name}`;

        if (question) {
            const presentation = question.presentation || {};
            elements.prompt.textContent = question.prompt_marathi;
            elements.context.textContent = presentation.context_marathi || '';
            elements.context.hidden = !presentation.context_marathi;
            elements.visual.textContent = presentation.visual || '';
            elements.visual.hidden = !presentation.visual;
            elements.listen.hidden = !presentation.audio_text;
            elements.listen.dataset.audioText = presentation.audio_text || '';
            renderChoices(question);
        }

        updateTimer();
    }

    elements.sound.addEventListener('click', () => {
        soundEnabled = !soundEnabled;
        elements.sound.setAttribute('aria-pressed', soundEnabled ? 'true' : 'false');
        elements.sound.querySelector('i').className = `bi bi-volume-${soundEnabled ? 'up' : 'mute'}`;
        elements.sound.querySelector('span').textContent = soundEnabled ? 'आवाज सुरू' : 'आवाज बंद';
    });
    elements.listen.addEventListener('click', () => speak(elements.listen.dataset.audioText));

    render();
    timerId = window.setInterval(updateTimer, 1000);
}
